<?php

namespace Tests\Feature;

use App\Models\PassengerAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileMediaPlacementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'media_testing', 'database.connections.media_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'mobile.company_id' => 1]);
        DB::purge('media_testing');
        Schema::clearResolvedInstance('db.schema');
        Schema::create('bus_classes', function (Blueprint $table) {
            $table->id(); $table->integer('company_id'); $table->string('name');
            $table->boolean('is_active')->default(true); $table->boolean('hide')->default(false); $table->softDeletes();
        });
        require_once database_path('migrations/2026_07_22_000006_create_mobile_fleet_media_table.php');
        (new \CreateMobileFleetMediaTable())->up();
        Schema::table('mobile_fleet_media', function (Blueprint $table) { $table->json('placements')->nullable(); });
        Sanctum::actingAs(new PassengerAccount(['id' => 99, 'company_id' => 1, 'mobile_verified_at' => now()]));
    }

    private function media(string $title, array $changes = []): void
    {
        DB::table('mobile_fleet_media')->insert(array_replace([
            'company_id' => 1, 'title' => $title, 'media_type' => 'exterior',
            'image_url' => 'https://media.example.test/' . rawurlencode($title) . '.jpg',
            'sort_order' => 10, 'is_active' => true, 'placements' => null,
        ], $changes));
    }

    public function test_placement_catalog_is_published_company_scoped_ordered_and_compatible_with_old_clients(): void
    {
        $this->media('Legacy gallery');
        $this->media('Home banner', ['placements' => json_encode(['home_banner']), 'sort_order' => 1]);
        $this->media('Promotion', ['placements' => json_encode(['home_promotion']), 'sort_order' => 2]);
        $this->media('Guide only', ['placements' => json_encode(['bus_guide']), 'sort_order' => 3]);
        $this->media('Hidden', ['is_active' => false]);
        $this->media('Deleted', ['deleted_at' => now()]);
        $this->media('Foreign', ['company_id' => 2]);
        $rows = $this->getJson('/api/mobile/v1/fleet?include_placements=1')->assertOk()->json('data');
        $this->assertSame(['Home banner', 'Promotion', 'Guide only', 'Legacy gallery'], array_column($rows, 'title'));
        $this->assertSame(['home_banner'], $rows[0]['placements']);
        $this->assertSame(['home_carousel', 'bus_guide'], $rows[3]['placements']);
        $this->assertSame(1, $rows[0]['sort_order']);
        $legacy = $this->getJson('/api/mobile/v1/fleet')->assertOk()->json('data');
        $this->assertSame(['Guide only', 'Legacy gallery'], array_column($legacy, 'title'));
        $this->getJson('/api/mobile/v1/fleet?include_placements=bad')->assertUnprocessable();
    }

    public function test_foreign_hidden_inactive_and_deleted_bus_classes_are_not_exposed(): void
    {
        foreach ([['company_id' => 2], ['hide' => true], ['is_active' => false], ['deleted_at' => now()]] as $i => $changes) {
            DB::table('bus_classes')->insert(array_replace(['id' => $i + 1, 'company_id' => 1, 'name' => 'Restricted'], $changes));
            $this->media('Restricted ' . $i, ['bus_class_id' => $i + 1]);
        }
        $this->media('Broken class link', ['bus_class_id' => 999]);
        $this->media('Visible');
        $rows = $this->getJson('/api/mobile/v1/fleet?include_placements=1')->assertOk()->json('data');
        $this->assertSame(['Visible'], array_column($rows, 'title'));
    }

    public function test_reader_works_before_the_erp_placement_migration_is_installed(): void
    {
        Schema::table('mobile_fleet_media', function (Blueprint $table) { $table->dropColumn('placements'); });
        DB::table('mobile_fleet_media')->insert(['company_id' => 1, 'title' => 'Existing photo', 'image_url' => 'https://example.test/bus.jpg']);
        $this->getJson('/api/mobile/v1/fleet?include_placements=1')->assertOk()
            ->assertJsonPath('data.0.placements', ['home_carousel', 'bus_guide']);
    }
}
