<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileAppAdminData;
use App\Services\MobileAppControlData as Controls;
use App\Support\MobileAppScope as Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Company-scoped administration of the existing mobile backend configuration. */
class MobileAppSettingsController extends Controller
{
    private $data;

    private const MEDIA_PLACEMENTS = ['home_banner', 'home_carousel', 'bus_guide', 'home_promotion'];
    private const LEGACY_PLACEMENTS = ['home_carousel', 'bus_guide'];

    private const TEXT_SETTINGS = [
        'maintenance_message', 'android_store_url', 'ios_store_url',
        'support_phone', 'support_whatsapp', 'support_email',
    ];

    public function __construct(MobileAppAdminData $data) { $this->data = $data; }

    private function access(Request $request, string $section, bool $write = false): void
    {
        abort_unless($request->user() instanceof User && $request->user()->company_id, 403, 'ERP staff access is required.');
        foreach (['mobile-app', 'mobile-app-view-' . $section] as $permission) {
            abort_unless(Scope::allows($request->user(), $permission), 403, 'Mobile App access denied.');
        }
        if ($write) {
            abort_unless(Scope::allows($request->user(), 'mobile-app-manage-' . $section), 403, 'Your role cannot change these mobile app settings.');
            abort_unless($this->data->configurationScope($request), 403, 'Company configuration requires a company Admin or access to all company terminals and routes.');
            $this->data->requireCapability('audit');
        }
        $this->data->requireCapability($section);
    }

    private function response(array $data, int $status = 200)
    {
        return response()->json(['status' => true, 'data' => $data], $status);
    }

    private function unknownKeys(array $input, array $allowed, string $prefix = ''): void
    {
        $errors = [];
        foreach (array_diff(array_keys($input), $allowed) as $key) { $errors[$prefix . $key] = ['This field is not supported.']; }
        if ($errors) { throw ValidationException::withMessages($errors); }
    }

    private function reason(Request $request): string
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        $reason = trim($request->input('reason'));
        if (mb_strlen($reason) < 3) { throw ValidationException::withMessages(['reason' => ['Enter a reason with at least 3 characters.']]); }
        return $reason;
    }

    private function revision(Request $request, $row): void
    {
        $request->validate(['revision' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/']);
        abort_unless(hash_equals($this->data->revision($row), $request->input('revision')), 409,
            'This record has changed. Reload it and review the latest values before saving.');
    }

    private function lockCompany(Request $request): void
    {
        abort_unless(DB::table('companies')->where('id', $request->user()->company_id)->lockForUpdate()->first(), 404, 'Company not found.');
    }

    private function jsonArray($value): array
    {
        if (is_array($value)) { return $value; }
        $decoded = is_string($value) ? json_decode($value, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    private function storedSettings($row): array
    {
        $settings = [
            'latest_version' => $row->latest_version ?? config('mobile.latest_version', '1.0.0'),
            'minimum_supported_version' => $row->minimum_supported_version ?? config('mobile.minimum_supported_version', '1.0.0'),
            'force_update' => $row ? (bool) $row->force_update : (bool) config('mobile.force_update', false),
            'maintenance_mode' => $row ? (bool) $row->maintenance_mode : (bool) config('mobile.maintenance_mode', false),
        ];
        foreach (self::TEXT_SETTINGS as $key) { $settings[$key] = $row ? $row->{$key} : config('mobile.' . $key); }
        $settings['payment_methods'] = $row && $row->payment_methods !== null
            ? $this->jsonArray($row->payment_methods) : (array) config('mobile.payment_methods', []);
        $settings['features'] = array_merge(['wallet' => true, 'online_payments' => false, 'notifications' => false, 'promotions' => false],
            (array) config('mobile.features', []), $row ? $this->jsonArray($row->features) : []);
        return $settings;
    }

    private function presentSettings($row): array
    {
        $settings = $this->storedSettings($row);
        // Gateway availability is calculated by the mobile API, possibly on a different host.
        // Do not advertise a payment status that this ERP cannot verify.
        $settings['features'] = ['wallet' => (bool) $settings['features']['wallet'],
            'online_payments' => null, 'notifications' => (bool) (($row ? $this->jsonArray($row->features) : [])['notifications'] ?? true), 'promotions' => false];
        return [
            'settings' => $settings, 'revision' => $this->data->revision($row),
            'source' => $row ? 'company_override' : 'erp_defaults',
            'notices' => array_values(array_filter([
                $row ? null : 'No company override exists; verify deployed mobile API defaults before saving. ERP defaults may differ from the mobile server.',
                'Payment methods are read-only. Online payments are managed by the deployed payment gateway; their live availability cannot be verified here.',
                'Notifications require the notification migration and scheduler on the mobile server. Phone alerts also require Firebase configuration. Promotions remain unavailable.',
            ])),
            'read_only_features' => ['online_payments', 'promotions'],
        ];
    }

    public function settings(Request $request)
    {
        $this->access($request, 'settings');
        return $this->response($this->presentSettings(DB::table('mobile_app_configs')->where('company_id', $request->user()->company_id)->first()));
    }

    private function httpsRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $parts = is_string($value) ? parse_url($value) : false;
            if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
                $fail('Use a public HTTPS URL without embedded credentials.');
            }
        };
    }

    private function settingsInput(Request $request, $row): array
    {
        $this->unknownKeys($request->all(), ['settings', 'revision', 'reason']);
        $request->validate(['settings' => 'required|array']);
        $input = $request->input('settings');
        $this->unknownKeys($input, array_merge(self::TEXT_SETTINGS, [
            'latest_version', 'minimum_supported_version', 'force_update', 'maintenance_mode', 'payment_methods', 'features',
        ]), 'settings.');
        $rules = [
            'settings.latest_version' => ['required', 'string', 'max:30', 'regex:/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/'],
            'settings.minimum_supported_version' => ['required', 'string', 'max:30', 'regex:/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/'],
            'settings.force_update' => 'required|boolean', 'settings.maintenance_mode' => 'required|boolean',
            'settings.maintenance_message' => 'present|nullable|string|max:5000',
            'settings.android_store_url' => ['present', 'nullable', 'string', 'max:255', 'url', $this->httpsRule()],
            'settings.ios_store_url' => ['present', 'nullable', 'string', 'max:255', 'url', $this->httpsRule()],
            'settings.support_phone' => 'present|nullable|string|max:255',
            'settings.support_whatsapp' => 'present|nullable|string|max:255',
            'settings.support_email' => 'present|nullable|string|email|max:255',
            'settings.payment_methods' => 'sometimes|array', 'settings.payment_methods.*' => 'string|max:100',
            'settings.features' => 'required|array', 'settings.features.wallet' => 'required|boolean', 'settings.features.notifications' => 'sometimes|boolean',
        ];
        $request->validate($rules);
        $this->unknownKeys($input['features'], ['wallet', 'online_payments', 'notifications', 'promotions'], 'settings.features.');
        $old = $this->storedSettings($row);
        $public = $this->presentSettings($row)['settings'];
        foreach (['online_payments', 'promotions'] as $key) {
            if (array_key_exists($key, $input['features']) && $input['features'][$key] !== $public['features'][$key]) {
                throw ValidationException::withMessages(['settings.features.' . $key => ['This feature is managed by the mobile backend and cannot be changed here.']]);
            }
        }
        if (array_key_exists('payment_methods', $input) && $input['payment_methods'] !== $old['payment_methods']) {
            throw ValidationException::withMessages(['settings.payment_methods' => ['Payment methods are managed by the deployed payment gateway and are read-only here.']]);
        }
        if (version_compare($input['minimum_supported_version'], $input['latest_version'], '>')) {
            throw ValidationException::withMessages(['settings.minimum_supported_version' => ['The minimum supported version cannot exceed the latest version.']]);
        }
        if ((bool) $input['force_update'] || version_compare($input['minimum_supported_version'], $old['minimum_supported_version'], '>')) {
            if (empty($input['android_store_url']) || empty($input['ios_store_url'])) {
                throw ValidationException::withMessages(['settings.android_store_url' => ['Both HTTPS store links are required when forcing updates or raising the minimum version.'],
                    'settings.ios_store_url' => ['Both HTTPS store links are required when forcing updates or raising the minimum version.']]);
            }
        }
        if ((bool) $input['maintenance_mode'] && trim((string) $input['maintenance_message']) === '') {
            throw ValidationException::withMessages(['settings.maintenance_message' => ['Provide the message passengers will see during maintenance.']]);
        }
        $input['force_update'] = (bool) $input['force_update'];
        $input['maintenance_mode'] = (bool) $input['maintenance_mode'];
        foreach (self::TEXT_SETTINGS as $key) { $input[$key] = is_string($input[$key]) ? trim($input[$key]) : null; }
        // NULL means the mobile server uses its own environment configuration. Keep
        // gateway JSON byte-for-byte and avoid freezing ERP defaults into that server.
        $input['payment_methods'] = $row ? $row->payment_methods : null;
        $features = $row ? $this->jsonArray($row->features) : [];
        $editableFeatures = ['wallet' => (bool) $input['features']['wallet']];
        if (array_key_exists('notifications', $input['features'])) $editableFeatures['notifications'] = (bool) $input['features']['notifications'];
        $input['features'] = json_encode(array_merge($features, $editableFeatures));
        return $input;
    }

    public function updateSettings(Request $request)
    {
        $this->access($request, 'settings', true);
        $reason = $this->reason($request);
        return DB::transaction(function () use ($request, $reason) {
            $this->lockCompany($request);
            $query = DB::table('mobile_app_configs')->where('company_id', $request->user()->company_id);
            $old = (clone $query)->lockForUpdate()->first();
            $this->revision($request, $old);
            $input = $this->settingsInput($request, $old);
            $input['updated_at'] = now();
            if ($old) { $query->update($input); }
            else { DB::table('mobile_app_configs')->insert($input + ['company_id' => $request->user()->company_id, 'created_at' => now()]); }
            $row = $query->first();
            $this->data->audit($request, 'update_settings', 'app_config', $row->id, $reason,
                ['from' => $this->presentSettings($old)['settings'], 'to' => $this->presentSettings($row)['settings']]);
            return $this->response($this->presentSettings($row));
        });
    }

    public function policies(Request $request)
    {
        $this->access($request, 'settings');
        abort_unless(Schema::hasTable('mobile_app_policies'), 503, 'Install the Mobile App user-controls migration first.');
        $row = DB::table('mobile_app_policies')->where('company_id', $request->user()->company_id)->first();
        return $this->response(['settings' => Controls::policy((int) $request->user()->company_id), 'revision' => $this->data->revision($row)]);
    }

    public function updatePolicies(Request $request)
    {
        $this->access($request, 'settings');
        abort_unless(Scope::allows($request->user(), 'mobile-app-manage-app-controls') && $this->data->configurationScope($request), 403, 'App controls require their dedicated privilege and a company Admin or companywide access.');
        $this->data->requireCapability('audit');
        abort_unless(Schema::hasTable('mobile_app_policies'), 503, 'Install the Mobile App user-controls migration first.');
        $this->unknownKeys($request->all(), ['settings', 'revision', 'reason']);
        $reason = $this->reason($request);
        $request->validate(['settings' => 'required|array']);
        $this->unknownKeys($request->input('settings'), array_keys(Controls::DEFAULTS), 'settings.');
        $rules = [];
        $max = ['otp_per_minute' => 10, 'otp_per_hour' => 100, 'otp_per_day' => 1000, 'otp_per_month' => 10000,
            'login_per_minute' => 20, 'otp_verify_per_ten_minutes' => 10, 'password_reset_per_minute' => 10];
        foreach (Controls::DEFAULTS as $key => $default) {
            $rules['settings.' . $key] = is_bool($default) ? 'required|boolean'
                : ($default === null ? 'present|nullable|integer|min:0|max:10000' : 'required|integer|min:1|max:' . $max[$key]);
        }
        $input = $request->validate($rules)['settings'];
        DB::transaction(function () use ($request, $input, $reason) {
            $this->lockCompany($request);
            $query = DB::table('mobile_app_policies')->where('company_id', $request->user()->company_id);
            $old = (clone $query)->lockForUpdate()->first(); $this->revision($request, $old);
            $values = ['settings' => json_encode($input), 'updated_by' => $request->user()->id, 'updated_at' => now()];
            if ($old) { $query->update($values); }
            else { DB::table('mobile_app_policies')->insert($values + ['company_id' => $request->user()->company_id, 'created_at' => now()]); }
            $this->data->audit($request, 'app_controls_changed', 'app_policy', $request->user()->company_id, $reason,
                ['from' => $old ? json_decode($old->settings, true) : Controls::DEFAULTS, 'to' => $input]);
        });
        return $this->policies($request);
    }

    private function presentContent($row): array
    {
        return ['id' => (int) $row->id, 'bus_class_id' => $row->bus_class_id === null ? null : (int) $row->bus_class_id,
            'title' => $row->title, 'media_type' => $row->media_type, 'image_url' => $row->image_url,
            'placements' => ($row->placements ?? null) ? json_decode($row->placements, true) : self::LEGACY_PLACEMENTS,
            'source' => empty($row->image_path) ? 'url' : 'upload',
            'sort_order' => (int) $row->sort_order, 'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at, 'updated_at' => $row->updated_at, 'revision' => $this->data->revision($row)];
    }

    public function content(Request $request)
    {
        $this->access($request, 'content');
        $request->validate(['page' => 'nullable|integer|min:1|max:100000', 'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:150', 'is_active' => 'nullable|boolean',
            'media_type' => ['nullable', Rule::in(['exterior', 'interior', 'seats', 'lounge'])]]);
        $query = DB::table('mobile_fleet_media')->where('company_id', $request->user()->company_id)->whereNull('deleted_at');
        if ($request->filled('search')) { $query->where('title', 'like', '%' . addcslashes($request->search, '%_\\') . '%'); }
        if ($request->filled('media_type')) { $query->where('media_type', $request->media_type); }
        if ($request->filled('is_active')) { $query->where('is_active', $request->boolean('is_active')); }
        $page = $query->orderBy('sort_order')->orderBy('id')->paginate((int) $request->input('per_page', 20));
        $busClasses = Schema::hasTable('bus_classes') ? DB::table('bus_classes')->where('company_id', $request->user()->company_id)
            ->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']) : collect();
        return $this->response($this->data->pagination($page, $page->getCollection()->map(function ($row) { return $this->presentContent($row); })->all())
            + ['bus_classes' => $busClasses]);
    }

    private function contentInput(Request $request, $old = null): array
    {
        $this->unknownKeys($request->all(), ['bus_class_id', 'title', 'media_type', 'image_url', 'image', 'placements', 'sort_order', 'is_active', 'reason', 'revision', '_method']);
        abort_unless(Schema::hasColumn('mobile_fleet_media', 'placements') && Schema::hasColumn('mobile_fleet_media', 'image_path'),
            503, 'Install the Mobile App image-placement migration before saving content.');
        if ($request->hasFile('image') && $request->filled('image_url')) {
            throw ValidationException::withMessages(['image' => ['Choose a file or an image URL, not both.']]);
        }
        $keepUpload = $old && !empty($old->image_path) && $request->input('image_url') === $old->image_url;
        $input = $request->validate([
            'bus_class_id' => 'nullable|integer|min:1', 'title' => 'required|string|max:150',
            'media_type' => ['required', Rule::in(['exterior', 'interior', 'seats', 'lounge'])],
            'image_url' => array_merge(['required_without:image', 'nullable', 'string', 'url', 'max:2048'], $keepUpload ? [] : [$this->httpsRule()]),
            'image' => ['required_without:image_url', 'nullable', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:min_width=32,min_height=32,max_width=4096,max_height=4096'],
            'placements' => ['sometimes', 'required', 'array', 'min:1', 'max:4'],
            'placements.*' => ['required', 'string', 'distinct', Rule::in(self::MEDIA_PLACEMENTS)],
            'sort_order' => 'required|integer|min:0|max:4294967295', 'is_active' => 'required|boolean',
        ]);
        $input['bus_class_id'] = $input['bus_class_id'] ?? null;
        if ($input['bus_class_id'] !== null) {
            if (!Schema::hasTable('bus_classes') || !DB::table('bus_classes')->where('company_id', $request->user()->company_id)
                ->where('id', $input['bus_class_id'])->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['bus_class_id' => ['Choose an active bus class belonging to this company.']]);
            }
        }
        $input['title'] = trim($input['title']);
        if ($input['title'] === '') { throw ValidationException::withMessages(['title' => ['A title is required.']]); }
        $input['is_active'] = (bool) $input['is_active'];
        $input['placements'] = json_encode($input['placements'] ?? ($old && $old->placements ? json_decode($old->placements, true) : self::LEGACY_PLACEMENTS));
        $input['image_path'] = $keepUpload ? $old->image_path : null;
        unset($input['image']);
        return $input;
    }

    private function saveContent(Request $request, ?int $id = null)
    {
        $this->access($request, 'content', true);
        $reason = $this->reason($request);
        $storedPath = null;
        try {
            return DB::transaction(function () use ($request, $id, $reason, &$storedPath) {
                $this->lockCompany($request);
                $query = DB::table('mobile_fleet_media')->where('company_id', $request->user()->company_id)->whereNull('deleted_at');
                $old = $id === null ? null : (clone $query)->where('id', $id)->lockForUpdate()->first();
                if ($id !== null) {
                    abort_unless($old, 404, 'Fleet content not found.');
                    $this->revision($request, $old);
                }
                $input = $this->contentInput($request, $old);
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$file->getMimeType()];
                    $directory = 'mobile-app/' . (int) $request->user()->company_id;
                    $storedPath = $directory . '/' . Str::uuid() . '.' . $extension;
                    $disk = Storage::disk('public');
                    $url = $disk->url($storedPath);
                    $parts = parse_url($url);
                    abort_unless(!empty($parts['host']) && in_array($parts['scheme'] ?? '', ['https', 'http'], true)
                        && ($parts['scheme'] === 'https' || app()->environment(['local', 'testing'])),
                        503, 'Configure a public HTTPS storage URL before uploading images.');
                    $saved = $disk->putFileAs($directory, $file, basename($storedPath), 'public');
                    abort_unless($saved, 503, 'The image could not be stored. Please try again.');
                    $input['image_path'] = $storedPath;
                    $input['image_url'] = $url;
                }
                $input['updated_at'] = now();
                if ($old) {
                    (clone $query)->where('id', $id)->update($input);
                } else {
                    $id = DB::table('mobile_fleet_media')->insertGetId($input + ['company_id' => $request->user()->company_id, 'created_at' => now()]);
                }
                $row = (clone $query)->where('id', $id)->first();
                $changes = ['to' => $this->presentContent($row)];
                if ($old) { $changes['from'] = $this->presentContent($old); }
                $this->data->audit($request, $old ? 'update_content' : 'create_content', 'fleet_media', $id, $reason, $changes);
                return $this->response($this->presentContent($row), $old ? 200 : 201);
            });
        } catch (\Throwable $exception) {
            // A failed validation, stale revision, or audit must never leave a new public file behind.
            if ($storedPath !== null) { Storage::disk('public')->delete($storedPath); }
            throw $exception;
        }
    }

    public function storeContent(Request $request) { return $this->saveContent($request); }

    public function updateContent(Request $request, int $id) { return $this->saveContent($request, $id); }

    public function destroyContent(Request $request, int $id)
    {
        $this->access($request, 'content', true);
        $this->unknownKeys($request->all(), ['reason', 'revision']);
        $reason = $this->reason($request);
        return DB::transaction(function () use ($request, $id, $reason) {
            $this->lockCompany($request);
            $query = DB::table('mobile_fleet_media')->where('company_id', $request->user()->company_id)->where('id', $id)->whereNull('deleted_at');
            $row = (clone $query)->lockForUpdate()->first();
            abort_unless($row, 404, 'Fleet content not found.');
            $this->revision($request, $row);
            $query->update(['deleted_at' => now(), 'updated_at' => now(), 'is_active' => false]);
            $this->data->audit($request, 'delete_content', 'fleet_media', $id, $reason, ['from' => $this->presentContent($row), 'deleted' => true]);
            return $this->response(['id' => $id, 'deleted' => true]);
        });
    }
}
