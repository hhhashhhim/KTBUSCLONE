<?php

namespace Tests\Feature;

use App\Models\admin\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\MobileAppPermissions;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppSettingsTest extends TestCase
{
    private const BASE = '/api/web/v1/mobile-app';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost', 'database.default' => 'mobile_app_settings_test',
            'database.connections.mobile_app_settings_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        $this->app['url']->forceRootUrl('http://localhost');
        DB::purge('mobile_app_settings_test');
        Schema::clearResolvedInstance('db.schema');
        Schema::create('companies', function (Blueprint $table) { $table->id(); $table->string('name'); });
        DB::table('companies')->insert([['id' => 1, 'name' => 'Kainat'], ['id' => 2, 'name' => 'Other company']]);
        Schema::create('bus_classes', function (Blueprint $table) { $table->id(); $table->integer('company_id'); $table->string('name'); $table->softDeletes(); });
        DB::table('bus_classes')->insert([['id' => 1, 'company_id' => 1, 'name' => 'Business'], ['id' => 2, 'company_id' => 2, 'name' => 'Other fleet']]);
        Schema::create('mobile_app_configs', function (Blueprint $table) {
            $table->id(); $table->integer('company_id')->unique(); $table->string('latest_version')->default('1.0.0');
            $table->string('minimum_supported_version')->default('1.0.0'); $table->boolean('force_update')->default(false);
            $table->boolean('maintenance_mode')->default(false); $table->text('maintenance_message')->nullable();
            foreach (['android_store_url', 'ios_store_url', 'support_phone', 'support_whatsapp', 'support_email'] as $field) { $table->string($field)->nullable(); }
            $table->json('payment_methods')->nullable(); $table->json('features')->nullable(); $table->timestamps();
        });
        Schema::create('mobile_fleet_media', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->integer('bus_class_id')->nullable(); $table->string('title', 150);
            $table->string('media_type', 30); $table->string('image_url', 2048); $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        require_once database_path('migrations/2026_10_20_000001_create_mobile_app_admin_tables.php');
        (new \CreateMobileAppAdminTables())->up();
        require_once database_path('migrations/2026_10_20_000004_add_mobile_user_controls.php');
        (new \AddMobileUserControls())->up();
        require_once database_path('migrations/2026_10_20_000003_add_mobile_media_placements.php');
        (new \AddMobileMediaPlacements())->up();
        Storage::fake('public', ['url' => 'https://media.example.test/storage']);
        config(['filesystems.disks.public.url' => 'https://media.example.test/storage']);
    }

    private function login(array $denied = [], bool $all = true, string $routes = 'all', string $roleName = 'Mobile operator'): void
    {
        $modules = MobileAppPermissions::append([], true);
        $permissions = $modules;
        foreach ($permissions[0]['buttons'] as &$button) {
            if (in_array($button['name'], $denied, true) || (!$all && $button['name'] === 'mobile-app-all-terminals')) { $button['allow'] = false; }
        }
        unset($button);
        $user = new User(['id' => 1, 'company_id' => 1, 'terminal_id' => 10, 'route_ids' => $routes, 'hide' => 0]);
        $user->setRelation('company', new Company(['id' => 1, 'modules' => $modules]));
        $user->setRelation('role', new Role(['company_id' => 1, 'name' => $roleName, 'permissions' => $permissions]));
        Sanctum::actingAs($user, ['*']);
    }

    private function configRow(int $company = 1): void
    {
        DB::table('mobile_app_configs')->insert(['company_id' => $company, 'latest_version' => '2.0.0',
            'minimum_supported_version' => '1.0.0', 'force_update' => false, 'maintenance_mode' => false,
            'support_email' => 'support@example.test', 'payment_methods' => json_encode(['counter', 'bank_alfalah']),
            'features' => json_encode(['wallet' => true, 'online_payments' => true, 'notifications' => true, 'future_feature' => true]),
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function settingsBody(array $changes = []): array
    {
        $data = $this->getJson(self::BASE . '/settings')->assertOk()->json('data');
        $settings = array_replace($data['settings'], $changes);
        // The ERP form intentionally submits only its editable feature.
        $settings['features'] = $changes['features'] ?? ['wallet' => $settings['features']['wallet']];
        unset($settings['payment_methods']);
        return ['settings' => $settings, 'revision' => $data['revision'], 'reason' => 'Reviewed app configuration'];
    }

    private function contentBody(array $changes = []): array
    {
        return array_replace(['bus_class_id' => 1, 'title' => 'Business exterior', 'media_type' => 'exterior',
            'image_url' => 'https://images.example.test/bus.jpg', 'sort_order' => 0, 'is_active' => true,
            'reason' => 'Publish approved fleet photo'], $changes);
    }

    public function test_missing_mobile_schema_returns_dependency_errors_without_creating_it(): void
    {
        $this->login();
        Schema::drop('mobile_app_configs');
        Schema::drop('mobile_fleet_media');
        $this->getJson(self::BASE . '/settings')->assertStatus(503);
        $this->getJson(self::BASE . '/content')->assertStatus(503);
        $this->assertFalse(Schema::hasTable('mobile_app_configs'));
    }

    public function test_passenger_principals_and_missing_section_permissions_are_denied(): void
    {
        $this->actingAs(new GenericUser(['id' => 9, 'company_id' => 1]), 'sanctum');
        $this->getJson(self::BASE . '/settings')->assertForbidden();
        $this->postJson(self::BASE . '/content', $this->contentBody())->assertForbidden();
        $this->login(['mobile-app-view-settings', 'mobile-app-view-content']);
        $this->getJson(self::BASE . '/settings')->assertForbidden();
        $this->putJson(self::BASE . '/settings', [])->assertForbidden();
        $this->getJson(self::BASE . '/content')->assertForbidden();
        $this->postJson(self::BASE . '/content', $this->contentBody())->assertForbidden();
        $this->login(['mobile-app-manage-settings', 'mobile-app-manage-content']);
        $this->getJson(self::BASE . '/settings')->assertOk();
        $this->putJson(self::BASE . '/settings', $this->settingsBody())->assertForbidden();
        $this->getJson(self::BASE . '/content')->assertOk();
        $this->postJson(self::BASE . '/content', $this->contentBody())->assertForbidden();
    }

    public function test_initial_configuration_identifies_defaults_and_does_not_expose_credentials_or_other_companies(): void
    {
        $this->login();
        $this->configRow(2);
        config(['mobile.otp.whatsapp.api_key' => 'never-expose-whatsapp-key', 'mobile_payments.bank_alfalah.secret' => 'never-expose-payment-key']);
        $response = $this->getJson(self::BASE . '/settings')->assertOk()->assertJsonPath('data.source', 'erp_defaults')
            ->assertJsonPath('data.settings.latest_version', '1.0.0')->assertJsonPath('data.settings.features.online_payments', null);
        $this->assertStringContainsString('verify deployed mobile API defaults', $response->json('data.notices.0'));
        $this->assertStringNotContainsString('never-expose', $response->getContent());
        $this->putJson(self::BASE . '/settings', $this->settingsBody())->assertOk()->assertJsonPath('data.source', 'company_override');
        $this->assertSame(2, DB::table('mobile_app_configs')->count());
        $this->assertSame('2.0.0', DB::table('mobile_app_configs')->where('company_id', 2)->value('latest_version'));
    }

    public function test_settings_update_preserves_gateway_and_future_features_and_audits_reason(): void
    {
        $this->login(); $this->configRow();
        $before = $this->getJson(self::BASE . '/settings')->json('data.revision');
        $body = $this->settingsBody(['features' => ['wallet' => false], 'maintenance_mode' => true, 'maintenance_message' => 'Scheduled work until 10 PM']);
        $body['reason'] = '  Scheduled maintenance approved  ';
        $response = $this->putJson(self::BASE . '/settings', $body)->assertOk()
            ->assertJsonPath('data.settings.features.wallet', false)->assertJsonPath('data.settings.maintenance_mode', true);
        $this->assertNotSame($before, $response->json('data.revision'));
        $row = DB::table('mobile_app_configs')->where('company_id', 1)->first();
        $this->assertSame(['counter', 'bank_alfalah'], json_decode($row->payment_methods, true));
        $features = json_decode($row->features, true);
        $this->assertTrue($features['online_payments']); $this->assertTrue($features['notifications']); $this->assertTrue($features['future_feature']);
        $this->assertFalse($features['wallet']);
        $audit = DB::table('mobile_app_admin_audits')->first();
        $this->assertSame('Scheduled maintenance approved', $audit->reason);
        $this->assertSame('update_settings', $audit->action);
        $this->assertSame(false, json_decode($audit->changes, true)['to']['features']['wallet']);
    }

    public function test_stale_configuration_revisions_cannot_overwrite_another_staff_update(): void
    {
        $this->login(); $this->configRow();
        $body = $this->settingsBody(['support_phone' => '+923001111111']);
        DB::table('mobile_app_configs')->where('company_id', 1)->update(['support_phone' => '+923002222222']);
        $this->putJson(self::BASE . '/settings', $body)->assertStatus(409);
        $this->assertSame('+923002222222', DB::table('mobile_app_configs')->value('support_phone'));
        $this->assertSame(0, DB::table('mobile_app_admin_audits')->count());
        $body = $this->settingsBody(); unset($body['revision']);
        $this->putJson(self::BASE . '/settings', $body)->assertUnprocessable()->assertJsonValidationErrors('revision');
    }

    public function test_null_gateway_and_feature_overrides_remain_inherited_from_mobile_server_environment(): void
    {
        $this->login(); $this->configRow();
        DB::table('mobile_app_configs')->where('company_id', 1)->update(['payment_methods' => null, 'features' => null]);
        config(['mobile.payment_methods' => ['bank_alfalah'], 'mobile.features.online_payments' => true,
            'mobile.features.backend_owned_switch' => true]);
        $body = $this->settingsBody(['support_phone' => '+923001234567', 'features' => ['wallet' => false]]);
        $this->putJson(self::BASE . '/settings', $body)->assertOk();
        $row = DB::table('mobile_app_configs')->where('company_id', 1)->first();
        $this->assertNull($row->payment_methods);
        $this->assertSame(['wallet' => false], json_decode($row->features, true));
    }

    public function test_new_company_override_does_not_copy_erp_gateway_or_backend_feature_defaults(): void
    {
        $this->login();
        config(['mobile.payment_methods' => ['counter'], 'mobile.features.online_payments' => true,
            'mobile.features.notifications' => true, 'mobile.features.backend_owned_switch' => true]);
        $body = $this->settingsBody(['support_phone' => '+923001234567']);
        $body['settings']['payment_methods'] = ['counter'];
        $this->putJson(self::BASE . '/settings', $body)->assertOk();
        $row = DB::table('mobile_app_configs')->where('company_id', 1)->first();
        $this->assertNull($row->payment_methods);
        $this->assertSame(['wallet' => true], json_decode($row->features, true));
    }

    public function test_existing_gateway_json_is_preserved_without_reencoding(): void
    {
        $this->login(); $this->configRow();
        $raw = '[ "counter", "bank_alfalah" ]';
        DB::table('mobile_app_configs')->where('company_id', 1)->update(['payment_methods' => $raw]);
        $this->putJson(self::BASE . '/settings', $this->settingsBody(['support_phone' => '+923001234567']))->assertOk();
        $this->assertSame($raw, DB::table('mobile_app_configs')->where('company_id', 1)->value('payment_methods'));
    }

    public function test_version_maintenance_unknown_and_readonly_fields_are_validated_without_writes(): void
    {
        $this->login(); $this->configRow();
        foreach ([
            [['latest_version' => '1.0'], 'settings.latest_version'],
            [['minimum_supported_version' => '3.0.0'], 'settings.minimum_supported_version'],
            [['minimum_supported_version' => '1.1.0'], 'settings.android_store_url'],
            [['force_update' => true], 'settings.ios_store_url'],
            [['maintenance_mode' => true, 'maintenance_message' => '   '], 'settings.maintenance_message'],
            [['android_store_url' => 'http://example.test/app'], 'settings.android_store_url'],
            [['android_store_url' => 'https://user:secret@example.test/app'], 'settings.android_store_url'],
            [['database_password' => 'secret'], 'settings.database_password'],
            [['payment_methods' => ['bank_alfalah']], 'settings.payment_methods'],
            [['features' => ['wallet' => true, 'online_payments' => true]], 'settings.features.online_payments'],
            [['features' => ['wallet' => true, 'promotions' => true]], 'settings.features.promotions'],
            [['features' => ['wallet' => true, 'new_feature' => true]], 'settings.features.new_feature'],
        ] as [$changes, $error]) {
            $body = $this->settingsBody($changes);
            // settingsBody removes read-only methods to mirror the form; inject deliberately for this validation case.
            if (array_key_exists('payment_methods', $changes)) { $body['settings']['payment_methods'] = $changes['payment_methods']; }
            $this->putJson(self::BASE . '/settings', $body)->assertUnprocessable()->assertJsonValidationErrors($error);
        }
        $body = $this->settingsBody(); $body['reason'] = '   ';
        $this->putJson(self::BASE . '/settings', $body)->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertSame('2.0.0', DB::table('mobile_app_configs')->value('latest_version'));
        $this->assertSame(0, DB::table('mobile_app_admin_audits')->count());
        $this->putJson(self::BASE . '/settings', $this->settingsBody(['minimum_supported_version' => '1.1.0', 'force_update' => true,
            'android_store_url' => 'https://play.google.com/store/apps/details?id=example.app',
            'ios_store_url' => 'https://apps.apple.com/app/id123456789']))->assertOk();
    }

    public function test_companywide_settings_and_content_mutations_require_global_terminal_and_route_scope(): void
    {
        foreach ([[false, 'all'], [true, '[3]']] as [$all, $routes]) {
            $this->login([], $all, $routes);
            $this->getJson(self::BASE . '/settings')->assertOk();
            $this->putJson(self::BASE . '/settings', $this->settingsBody())->assertForbidden();
            $this->postJson(self::BASE . '/content', $this->contentBody())->assertForbidden();
        }
        $this->assertSame(0, DB::table('mobile_app_configs')->count());
        $this->assertSame(0, DB::table('mobile_fleet_media')->count());
    }

    public function test_fleet_content_crud_is_company_scoped_audited_and_soft_deleted(): void
    {
        $this->login();
        DB::table('mobile_fleet_media')->insert(['id' => 99, 'company_id' => 2, 'bus_class_id' => 2, 'title' => 'Other company',
            'media_type' => 'exterior', 'image_url' => 'https://example.test/other.jpg']);
        $this->getJson(self::BASE . '/content')->assertOk()->assertJsonCount(0, 'data.items')->assertJsonCount(1, 'data.bus_classes');
        $created = $this->postJson(self::BASE . '/content', $this->contentBody())->assertCreated()->assertJsonPath('data.title', 'Business exterior')->json('data');
        $updated = $this->putJson(self::BASE . '/content/' . $created['id'], $this->contentBody(['title' => 'Business cabin', 'media_type' => 'interior',
            'revision' => $created['revision']]))->assertOk()->assertJsonPath('data.media_type', 'interior')->json('data');
        $this->getJson(self::BASE . '/content?media_type=interior')->assertOk()->assertJsonCount(1, 'data.items');
        $this->deleteJson(self::BASE . '/content/' . $updated['id'], ['revision' => $updated['revision'], 'reason' => 'Replaced with new fleet photo'])
            ->assertOk()->assertJsonPath('data.deleted', true);
        $row = DB::table('mobile_fleet_media')->where('id', $updated['id'])->first();
        $this->assertNotNull($row->deleted_at); $this->assertFalse((bool) $row->is_active);
        $this->getJson(self::BASE . '/content')->assertOk()->assertJsonCount(0, 'data.items');
        $this->assertSame(['create_content', 'update_content', 'delete_content'], DB::table('mobile_app_admin_audits')->orderBy('id')->pluck('action')->all());
        $this->assertSame(2, DB::table('mobile_fleet_media')->count());
    }

    public function test_fleet_content_rejects_foreign_bus_classes_unsafe_urls_stale_revisions_and_foreign_rows(): void
    {
        $this->login();
        foreach ([[['bus_class_id' => 2], 'bus_class_id'], [['image_url' => 'http://example.test/image.jpg'], 'image_url'],
            [['image_url' => 'https://user:password@example.test/image.jpg'], 'image_url'], [['title' => '   '], 'title'],
            [['sort_order' => -1], 'sort_order'], [['api_key' => 'secret'], 'api_key']] as [$changes, $field]) {
            $this->postJson(self::BASE . '/content', $this->contentBody($changes))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $created = $this->postJson(self::BASE . '/content', $this->contentBody())->assertCreated()->json('data');
        DB::table('mobile_fleet_media')->where('id', $created['id'])->update(['title' => 'Changed by another staff member']);
        $this->putJson(self::BASE . '/content/' . $created['id'], $this->contentBody(['revision' => $created['revision']]))->assertStatus(409);
        $this->deleteJson(self::BASE . '/content/' . $created['id'], ['revision' => $created['revision'], 'reason' => 'Remove old image'])->assertStatus(409);
        DB::table('mobile_fleet_media')->where('id', $created['id'])->update(['company_id' => 2]);
        $this->putJson(self::BASE . '/content/' . $created['id'], $this->contentBody(['revision' => $created['revision']]))->assertNotFound();
        $this->deleteJson(self::BASE . '/content/' . $created['id'], ['revision' => $created['revision'], 'reason' => 'Remove old image'])->assertNotFound();
        $this->assertSame(1, DB::table('mobile_app_admin_audits')->count());
    }

    public function test_missing_audit_schema_prevents_unaudited_settings_and_content_writes(): void
    {
        $this->login();
        $body = $this->settingsBody();
        Schema::drop('mobile_app_admin_audits');
        $this->putJson(self::BASE . '/settings', $body)->assertStatus(503);
        $this->postJson(self::BASE . '/content', $this->contentBody())->assertStatus(503);
        $this->assertSame(0, DB::table('mobile_app_configs')->count());
        $this->assertSame(0, DB::table('mobile_fleet_media')->count());
    }

    public function test_upload_creates_public_image_with_placements_and_audit_and_can_replace_using_multipart(): void
    {
        $this->login();
        $body = $this->contentBody(['image' => UploadedFile::fake()->image('bus.jpg', 800, 400),
            'placements' => ['home_banner', 'home_promotion']]);
        unset($body['image_url']);
        $created = $this->post(self::BASE . '/content', $body, ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.source', 'upload')
            ->assertJsonPath('data.placements', ['home_banner', 'home_promotion'])->json('data');
        $row = DB::table('mobile_fleet_media')->first();
        Storage::disk('public')->assertExists($row->image_path);
        $this->assertStringStartsWith('mobile-app/1/', $row->image_path);
        $this->assertStringStartsWith('https://media.example.test/storage/', $created['image_url']);
        $this->assertSame('create_content', DB::table('mobile_app_admin_audits')->first()->action);
        $body['image'] = UploadedFile::fake()->image('replacement.png', 640, 320);
        $body['_method'] = 'PUT'; $body['revision'] = $created['revision'];
        $updated = $this->post(self::BASE . '/content/' . $created['id'], $body, ['Accept' => 'application/json'])
            ->assertOk()->json('data');
        $this->assertNotSame($created['image_url'], $updated['image_url']);
        $newRow = DB::table('mobile_fleet_media')->first();
        Storage::disk('public')->assertExists($newRow->image_path);
        Storage::disk('public')->assertExists($row->image_path); // Keep historical media for audit/recovery.
        $this->assertSame(2, DB::table('mobile_app_admin_audits')->count());
        $edit = $this->contentBody(['image_url' => $updated['image_url'], 'revision' => $updated['revision'], 'placements' => ['bus_guide']]);
        $this->putJson(self::BASE . '/content/' . $created['id'], $edit)->assertOk()->assertJsonPath('data.source', 'upload');
        $this->assertSame($newRow->image_path, DB::table('mobile_fleet_media')->first()->image_path);
    }

    public function test_upload_validation_rejects_unsafe_content_size_dimensions_empty_and_unknown_placements(): void
    {
        $this->login();
        foreach ([
            [UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'image'],
            [UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "not an image";'), 'image'],
            [UploadedFile::fake()->image('tiny.png', 1, 1), 'image'],
            [UploadedFile::fake()->image('large.jpg', 100, 100)->size(5121), 'image'],
        ] as [$file, $field]) {
            $body = $this->contentBody(['image' => $file]); unset($body['image_url']);
            $this->post(self::BASE . '/content', $body, ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors($field);
        }
        foreach ([[], ['unknown'], ['bus_guide', 'bus_guide']] as $placements) {
            $this->postJson(self::BASE . '/content', $this->contentBody(['placements' => $placements]))->assertUnprocessable();
        }
        $this->postJson(self::BASE . '/content', $this->contentBody(['image_path' => '../other-company.jpg']))->assertUnprocessable();
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, DB::table('mobile_fleet_media')->count());
    }

    public function test_upload_is_guarded_by_content_permissions_scope_and_stale_revisions_before_writing_files(): void
    {
        $this->login(['mobile-app-manage-content']);
        $body = $this->contentBody(['image' => UploadedFile::fake()->image('bus.png', 100, 100)]); unset($body['image_url']);
        $this->post(self::BASE . '/content', $body, ['Accept' => 'application/json'])->assertForbidden();
        $this->login([], false);
        $this->post(self::BASE . '/content', $body, ['Accept' => 'application/json'])->assertForbidden();
        $this->login();
        $created = $this->postJson(self::BASE . '/content', $this->contentBody())->assertCreated()->json('data');
        $body['_method'] = 'PUT'; $body['revision'] = str_repeat('0', 64);
        $this->post(self::BASE . '/content/' . $created['id'], $body, ['Accept' => 'application/json'])->assertStatus(409);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_audit_rolls_back_image_record_and_removes_new_file(): void
    {
        $this->login();
        $service = \Mockery::mock(\App\Services\MobileAppAdminData::class)->makePartial();
        $service->shouldReceive('audit')->once()->andThrow(new \RuntimeException('Audit unavailable'));
        $this->app->instance(\App\Services\MobileAppAdminData::class, $service);
        $body = $this->contentBody(['image' => UploadedFile::fake()->image('bus.jpg', 100, 100)]); unset($body['image_url']);
        $this->withoutExceptionHandling();
        try {
            $this->post(self::BASE . '/content', $body, ['Accept' => 'application/json']);
            $this->fail('Expected audit failure.');
        } catch (\RuntimeException $error) { $this->assertSame('Audit unavailable', $error->getMessage()); }
        $this->assertSame(0, DB::table('mobile_fleet_media')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function policyBody(array $changes = []): array
    {
        $data = $this->getJson(self::BASE . '/policies')->assertOk()->json('data');
        return ['settings' => array_replace($data['settings'], $changes), 'revision' => $data['revision'], 'reason' => 'Reviewed company app controls'];
    }

    public function test_app_policy_permission_scope_validation_and_stale_revision(): void
    {
        $this->login(); $body = $this->policyBody(['registrations_enabled' => false, 'daily_seat_limit' => 0, 'otp_per_day' => 7]);
        $this->login(['mobile-app-manage-app-controls']);
        $this->putJson(self::BASE . '/policies', $body)->assertForbidden();
        $this->login([], false); $this->putJson(self::BASE . '/policies', $body)->assertForbidden();
        $this->login([], true, '[1]'); $this->putJson(self::BASE . '/policies', $body)->assertForbidden();
        $this->login(); $this->putJson(self::BASE . '/policies', $body)->assertOk()
            ->assertJsonPath('data.settings.registrations_enabled', false)->assertJsonPath('data.settings.daily_seat_limit', 0)
            ->assertJsonPath('data.settings.monthly_seat_limit', null)->assertJsonPath('data.settings.otp_per_day', 7);
        $this->putJson(self::BASE . '/policies', $body)->assertStatus(409);
        $this->assertDatabaseHas('mobile_app_admin_audits', ['action' => 'app_controls_changed', 'company_id' => 1]);
        $this->assertSame(0, DB::table('mobile_app_policies')->where('company_id', 2)->count());
        foreach (['otp_per_minute' => 0, 'otp_per_day' => 1001, 'monthly_seat_limit' => -1, 'unexpected' => true] as $key => $value) {
            $this->putJson(self::BASE . '/policies', $this->policyBody([$key => $value]))->assertUnprocessable();
        }
    }

    public function test_company_admin_can_edit_settings_policies_and_upload_images_with_limited_booking_routes(): void
    {
        $this->configRow(2);
        $this->login([], false, '[3]', 'Admin');
        $this->putJson(self::BASE . '/settings', $this->settingsBody(['support_email' => 'admin-support@example.test']))->assertOk();
        $this->putJson(self::BASE . '/policies', $this->policyBody(['daily_seat_limit' => 4, 'monthly_seat_limit' => 30, 'otp_per_day' => 6]))
            ->assertOk()->assertJsonPath('data.settings.daily_seat_limit', 4);
        $body = $this->contentBody(['image' => UploadedFile::fake()->image('admin-banner.jpg', 800, 320), 'placements' => ['home_banner']]);
        unset($body['image_url']);
        $image = $this->post(self::BASE . '/content', $body, ['Accept' => 'application/json'])->assertCreated()->json('data');
        $this->assertDatabaseHas('mobile_fleet_media', ['id' => $image['id'], 'company_id' => 1]);
        $this->assertSame('support@example.test', DB::table('mobile_app_configs')->where('company_id', 2)->value('support_email'));
        $this->assertDatabaseCount('mobile_app_admin_audits', 3);
        $this->assertSame('[3]', auth('sanctum')->user()->route_ids);
        $this->assertSame(0, DB::table('mobile_app_policies')->where('company_id', 2)->count());
    }

    public function test_admin_exception_still_requires_company_module_grants_and_active_matching_company_role(): void
    {
        $this->login([], false, '[]', 'Admin');
        $body = $this->policyBody(['daily_seat_limit' => 1]);
        $user = auth('sanctum')->user();
        $modules = $user->company->modules;
        foreach ($modules[0]['buttons'] as &$button) {
            if (in_array($button['name'], ['mobile-app-manage-settings', 'mobile-app-manage-app-controls', 'mobile-app-manage-content'], true)) { $button['allow'] = false; }
        }
        unset($button); $user->company->modules = $modules;
        $this->putJson(self::BASE . '/policies', $body)->assertForbidden();
        $this->putJson(self::BASE . '/settings', $this->settingsBody())->assertForbidden();
        $this->postJson(self::BASE . '/content', $this->contentBody())->assertForbidden();
        $this->login([], false, '[3]', 'Admin'); $user = auth('sanctum')->user(); $user->hide = 1;
        $this->putJson(self::BASE . '/policies', $body)->assertForbidden();
        $this->login([], false, '[3]', 'Admin'); $user = auth('sanctum')->user(); $user->role->company_id = 2;
        $this->putJson(self::BASE . '/policies', $body)->assertForbidden();
        $this->assertDatabaseCount('mobile_app_policies', 0);
        $this->assertDatabaseCount('mobile_app_admin_audits', 0);
    }
    public function test_notification_switch_is_editable_without_overwriting_backend_features(): void
    {
        $this->login(); $this->configRow();
        $body=$this->settingsBody(['features'=>['wallet'=>true,'notifications'=>false]]);
        $this->putJson(self::BASE.'/settings',$body)->assertOk()->assertJsonPath('data.settings.features.notifications',false);
        $features=json_decode(DB::table('mobile_app_configs')->where('company_id',1)->value('features'),true);
        $this->assertTrue($features['online_payments']); $this->assertTrue($features['future_feature']); $this->assertFalse($features['notifications']);
    }

}
