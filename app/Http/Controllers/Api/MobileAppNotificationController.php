<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileAppAdminData;
use App\Support\MobileAppScope as Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MobileAppNotificationController extends Controller
{
    private function authorizeNotification(Request $r, string $permission = 'view-notifications'): void
    {
        abort_unless($r->user() instanceof User && $r->user()->company_id && Scope::allows($r->user(), 'mobile-app-' . $permission), 403, 'Notification access denied.');
        abort_unless(Schema::hasTable('mobile_notifications') && Schema::hasTable('mobile_notification_campaigns') && Schema::hasTable('mobile_app_admin_audits')
            && Schema::hasTable('mobile_notification_deliveries') && Schema::hasTable('mobile_notification_runtime'), 503, 'Install the Mobile App notification migrations first.');
    }
    private function result($data) { return response()->json(['status' => true, 'data' => $data]); }
    private function payload(Request $r): array
    {
        $this->authorizeNotification($r); $this->authorizeNotification($r, 'send-notifications');
        $features = Schema::hasTable('mobile_app_configs') ? DB::table('mobile_app_configs')->where('company_id', $r->user()->company_id)->value('features') : null;
        $features = $features ? (json_decode($features, true) ?: []) : [];
        abort_if(array_key_exists('notifications', $features) && !$features['notifications'], 422, 'Notifications are paused in App Settings. Enable them before sending.');
        $input = $r->validate(['title' => 'required|string|max:120', 'body' => 'required|string|max:1500', 'reason' => 'required|string|min:5|max:500',
            'audience' => ['required', Rule::in(['selected', 'trip', 'all'])], 'passenger_ids' => 'nullable|array|max:200',
            'passenger_ids.*' => 'integer|min:1|distinct', 'schedule_detail_id' => 'nullable|integer|min:1', 'request_key' => 'required|uuid']);
        foreach (['title', 'body', 'reason'] as $key) {
            $input[$key] = trim($input[$key]); abort_if($input[$key] === '', 422, ucfirst($key) . ' is required.');
        }
        abort_if(mb_strlen($input['reason']) < 5, 422, 'Enter an internal reason of at least five characters.');
        $ids = array_values(array_unique(array_map('intval', $input['passenger_ids'] ?? []))); sort($ids);
        return ['title' => $input['title'], 'body' => $input['body'], 'reason' => $input['reason'], 'audience' => $input['audience'],
            'passenger_ids' => $input['audience'] === 'selected' ? $ids : [],
            'schedule_detail_id' => $input['audience'] === 'trip' ? ($input['schedule_detail_id'] ?? null) : null, 'request_key' => $input['request_key']];
    }
    private function recipients(Request $r, array $input): array
    {
        $data = app(MobileAppAdminData::class); $data->requireCapability('users');
        $query = DB::table('passenger_accounts as a')->where('a.company_id', $r->user()->company_id);
        if ($input['audience'] === 'all') {
            $this->authorizeNotification($r, 'broadcast-notifications');
            abort_unless(Scope::isCompanyAdmin($r->user()) || $data->globalPassengers($r), 403, 'Companywide announcements require company Admin access or all company terminals and routes.');
        } elseif ($input['audience'] === 'selected') {
            abort_unless(Scope::allows($r->user(), 'mobile-app-view-users'), 403, 'View app users is required.');
            abort_unless(count($input['passenger_ids']), 422, 'Choose at least one passenger.');
            $visible = $data->users($r, false)->whereIn('a.id', $input['passenger_ids'])->pluck('a.id')->map(function ($id) { return (int) $id; })->all();
            abort_unless(count($visible) === count($input['passenger_ids']), 403, 'One or more passengers are outside your permitted scope.');
            $query->whereIn('a.id', $visible);
        } else {
            abort_unless(Scope::allows($r->user(), 'mobile-app-view-bookings'), 403, 'View mobile bookings is required.');
            $data->requireCapability('bookings');
            abort_unless($input['schedule_detail_id'], 422, 'Enter a schedule departure ID.');
            $visible = $data->boundary($r, DB::table('tickets as t'), 't')->join('invoices as i', function ($j) {
                $j->on('i.id', '=', 't.invoice_id')->on('i.company_id', '=', 't.company_id');
            })->where('t.schedule_details_id', $input['schedule_detail_id'])->whereNull('t.deleted_at')
                ->whereIn('t.type', ['booked', 'advance booking'])->whereNotNull('i.passenger_account_id')->select('i.passenger_account_id');
            $query->whereIn('a.id', $visible);
        }
        if (Schema::hasTable('mobile_app_passenger_controls')) $query->whereNotExists(function ($q) {
            $q->selectRaw('1')->from('mobile_app_passenger_controls as ctl')->whereColumn('ctl.company_id', 'a.company_id')
                ->whereColumn('ctl.passenger_account_id', 'a.id')->where('ctl.status', '!=', 'active');
        });
        $ids = $query->orderBy('a.id')->limit(10001)->pluck('a.id')->map(function ($id) { return (int) $id; })->all();
        abort_if(count($ids) > 10000, 422, 'This audience exceeds 10,000 passengers. Select a trip or smaller audience.');
        abort_unless(count($ids), 422, 'No active passengers match this audience.');
        return $ids;
    }
    private function proof(Request $r, array $input, array $ids): string
    {
        return hash('sha256', json_encode([$r->user()->company_id, $r->user()->id, $input, $ids]));
    }
    public function preview(Request $r)
    {
        $input = $this->payload($r); $ids = $this->recipients($r, $input);
        return $this->result(['recipient_count' => count($ids), 'title' => $input['title'], 'body' => $input['body'],
            'preview_token' => Crypt::encryptString(json_encode(['proof' => $this->proof($r, $input, $ids), 'expires' => time() + 600])),
            'notice' => 'Saves to the passenger inbox. Phone alerts depend on permission, an active session, and configured push delivery.']);
    }
    public function send(Request $r)
    {
        $input = $this->payload($r); $company = $r->user()->company_id;
        $hash = hash('sha256', json_encode($input));
        $existing = DB::table('mobile_notification_campaigns')->where('company_id', $company)->where('request_key', $input['request_key'])->first();
        if ($existing) {
            abort_unless((int) $existing->actor_id === (int) $r->user()->id && hash_equals($existing->payload_hash, $hash), 409, 'This send reference has already been used.');
            return $this->result(['id' => $existing->id, 'recipient_count' => $existing->recipient_count, 'already_sent' => true]);
        }
        $r->validate(['preview_token' => 'required|string|max:10000']);
        try { $preview = json_decode(Crypt::decryptString($r->preview_token), true); } catch (\Throwable $e) { abort(422, 'Preview this announcement again.'); }
        $ids = $this->recipients($r, $input);
        abort_unless(($preview['expires'] ?? 0) >= time() && hash_equals($preview['proof'] ?? '', $this->proof($r, $input, $ids)), 409, 'The message or audience changed. Preview it again before sending.');
        $id = DB::transaction(function () use ($r, $input, $ids, $hash, $company) {
            // Serialize duplicate send requests without sending the audience twice.
            DB::table('companies')->where('id', $company)->lockForUpdate()->first();
            $prior = DB::table('mobile_notification_campaigns')->where('company_id', $company)->where('request_key', $input['request_key'])->first();
            if ($prior) { abort_unless($prior->payload_hash === $hash && (int) $prior->actor_id === (int) $r->user()->id, 409); return $prior->id; }
            $audience = ['type' => $input['audience'], 'schedule_detail_id' => $input['schedule_detail_id']];
            $id = DB::table('mobile_notification_campaigns')->insertGetId(['company_id' => $company, 'actor_id' => $r->user()->id,
                'request_key' => $input['request_key'], 'payload_hash' => $hash, 'title' => $input['title'], 'body' => $input['body'],
                'audience' => json_encode($audience), 'reason' => $input['reason'], 'recipient_count' => count($ids), 'created_at' => now()]);
            foreach (array_chunk($ids, 200) as $chunk) DB::table('mobile_notifications')->insert(array_map(function ($account) use ($id, $input, $company) {
                return ['company_id' => $company, 'passenger_account_id' => $account, 'campaign_id' => $id,
                    'event_key' => 'campaign:' . $id, 'kind' => 'announcement', 'title' => $input['title'], 'body' => $input['body'], 'created_at' => now()];
            }, $chunk));
            DB::table('mobile_app_admin_audits')->insert(['company_id' => $company, 'actor_id' => $r->user()->id,
                'terminal_id' => $r->user()->terminal_id, 'action' => 'send_notification', 'subject_type' => 'notification_campaign', 'subject_id' => $id,
                'reason' => $input['reason'], 'changes' => json_encode(['title' => $input['title'], 'audience' => $audience, 'recipient_count' => count($ids)]), 'created_at' => now()]);
            return $id;
        });
        return $this->result(['id' => $id, 'recipient_count' => count($ids), 'already_sent' => false]);
    }
    public function index(Request $r)
    {
        $this->authorizeNotification($r);
        $r->validate(['page' => 'sometimes|integer|min:1|max:100000']);
        $query = DB::table('mobile_notification_campaigns')->where('company_id', $r->user()->company_id);
        // Limited staff see only their own sends; an announcement may include other routes.
        if (!Scope::isCompanyAdmin($r->user()) && !app(MobileAppAdminData::class)->globalPassengers($r)) $query->where('actor_id', $r->user()->id);
        $page = $query->orderByDesc('id')->paginate(20);
        $items = $page->getCollection()->map(function ($campaign) {
            $base = DB::table('mobile_notifications')->where('company_id', $campaign->company_id)->where('campaign_id', $campaign->id);
            return ['id' => $campaign->id, 'title' => $campaign->title, 'body' => $campaign->body, 'created_at' => $campaign->created_at,
                'audience' => json_decode($campaign->audience, true), 'inbox_count' => $campaign->recipient_count,
                'read_count' => (clone $base)->whereNotNull('read_at')->count(),
                'push_accepted' => DB::table('mobile_notification_deliveries as d')->join('mobile_notifications as n', 'n.id', '=', 'd.notification_id')
                    ->where('n.company_id', $campaign->company_id)->where('n.campaign_id', $campaign->id)->where('d.status', 'accepted')->distinct()->count('n.passenger_account_id'),
                'push_failed' => DB::table('mobile_notification_deliveries as d')->join('mobile_notifications as n', 'n.id', '=', 'd.notification_id')
                    ->where('n.company_id', $campaign->company_id)->where('n.campaign_id', $campaign->id)->where('d.status', 'failed')->count()];
        });
        $runtime = DB::table('mobile_notification_runtime')->where('company_id', $r->user()->company_id)->first();
        return $this->result(['items' => $items, 'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'delivery' => ['last_run_at' => $runtime->last_run_at ?? null, 'push_configured' => (bool) ($runtime->push_configured ?? false)]]);
    }
}
