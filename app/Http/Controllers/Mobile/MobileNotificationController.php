<?php
namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Models\PassengerAccount;
use App\Services\Mobile\MobileNotificationInbox as Inbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileNotificationController extends Controller
{
    use RespondsWithMobileApi;
    private function account(Request $request): PassengerAccount
    {
        $account = $request->user();
        abort_unless($account instanceof PassengerAccount && (int) $account->company_id === (int) config('mobile.company_id'), 403);
        abort_unless(Inbox::enabled(), 503, 'Notifications are not enabled.');
        return $account;
    }
    public function index(Request $request)
    {
        $account = $this->account($request);
        $input = $request->validate(['page' => 'sometimes|integer|min:1|max:100000', 'per_page' => 'sometimes|integer|min:1|max:50', 'unread' => 'sometimes|boolean', 'paginated' => 'sometimes|boolean']);
        $query = Inbox::query($account);
        if ($request->boolean('unread')) $query->whereNull('read_at');
        $page = $query->orderByDesc('id')->paginate($input['per_page'] ?? 20);
        $items = $page->getCollection()->map([Inbox::class, 'present'])->all();
        // Existing app versions expect a list. New clients explicitly request metadata.
        return $this->success($request->boolean('paginated') ? ['items' => $items,
            'unread_count' => Inbox::query($account)->whereNull('read_at')->count(),
            'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]] : $items);
    }
    public function show(Request $request, int $id)
    {
        $account = $this->account($request);
        $row = Inbox::query($account)->where('id', $id)->first();
        abort_unless($row, 404);
        return $this->success(Inbox::present($row));
    }
    public function read(Request $request, int $id)
    {
        $account = $this->account($request);
        abort_unless(Inbox::query($account)->where('id', $id)->exists(), 404);
        Inbox::query($account)->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);
        return $this->success(['unread_count' => Inbox::query($account)->whereNull('read_at')->count()]);
    }
    public function readAll(Request $request)
    {
        $account = $this->account($request);
        $input = $request->validate(['through_id' => 'required|integer|min:1']);
        Inbox::query($account)->where('id', '<=', $input['through_id'])->whereNull('read_at')->update(['read_at' => now()]);
        return $this->success(['unread_count' => Inbox::query($account)->whereNull('read_at')->count()]);
    }
    public function registerDevice(Request $request)
    {
        $account = $this->account($request);
        $input = $request->validate(['token' => 'required|string|min:20|max:4096|regex:/^[A-Za-z0-9_:.-]+$/', 'platform' => ['required', Rule::in(['android', 'ios'])]]);
        $access = $account->currentAccessToken();
        abort_unless($access && $access->id, 401);
        DB::table('mobile_push_devices')->updateOrInsert(['token_hash' => hash('sha256', $input['token'])],
            ['company_id' => $account->company_id, 'passenger_account_id' => $account->id, 'access_token_id' => $access->id,
                'token' => Crypt::encryptString($input['token']), 'platform' => $input['platform'], 'updated_at' => now(), 'created_at' => now()]);
        return $this->success(['registered' => true]);
    }
    public function unregisterDevice(Request $request)
    {
        $account = $this->account($request);
        $input = $request->validate(['token' => 'required|string|max:4096']);
        DB::table('mobile_push_devices')->where('company_id', $account->company_id)->where('passenger_account_id', $account->id)
            ->where('token_hash', hash('sha256', $input['token']))->delete();
        return $this->success(['registered' => false]);
    }
}
