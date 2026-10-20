<?php
namespace Tests\Feature;
use App\Models\admin\Role;
use App\Models\Company;
use App\Models\User;
use App\Support\MobileAppPermissions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAppNotificationsTest extends TestCase
{
    private const BASE='/api/web/v1/mobile-app/notifications';
    protected function setUp(): void {
        parent::setUp(); config(['database.default'=>'erp_notice_test','database.connections.erp_notice_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'cache.default'=>'array']);
        foreach(['companies','customers','routes'] as $name) Schema::create($name,function(Blueprint $t){$t->id();$t->integer('company_id')->nullable();$t->string('name')->nullable();$t->string('cnic')->nullable();$t->softDeletes();});
        DB::table('companies')->insert(['id'=>1]);
        Schema::create('passenger_accounts',function(Blueprint $t){$t->id();$t->integer('company_id');$t->integer('customer_id')->nullable();$t->string('mobile');$t->string('email')->nullable();$t->timestamp('mobile_verified_at')->nullable();$t->timestamps();});
        Schema::create('invoices',function(Blueprint $t){$t->id();$t->integer('company_id');$t->integer('passenger_account_id');$t->integer('terminal_id');$t->integer('route_id');});
        Schema::create('tickets',function(Blueprint $t){$t->id();$t->integer('company_id');$t->integer('invoice_id');$t->integer('terminal_id');$t->integer('route_id');$t->integer('schedule_details_id');$t->string('type');$t->softDeletes();});
        require_once database_path('migrations/2026_10_20_000001_create_mobile_app_admin_tables.php'); (new \CreateMobileAppAdminTables)->up();
        require_once database_path('migrations/2026_10_20_000008_create_mobile_notifications.php'); (new \CreateMobileNotifications)->up();
        DB::table('passenger_accounts')->insert([['id'=>1,'company_id'=>1,'mobile'=>'03001234567'],['id'=>2,'company_id'=>1,'mobile'=>'03007654321'],['id'=>3,'company_id'=>2,'mobile'=>'03001112222']]);
        $this->login();
    }
    protected function tearDown(): void { DB::purge('erp_notice_test'); parent::tearDown(); }
    private function login(array $denied=[],bool $global=true): void {
        $modules=MobileAppPermissions::append([],true);$permissions=$modules;
        foreach($permissions[0]['buttons'] as &$button) if(in_array($button['name'],$denied)||(!$global&&$button['name']==='mobile-app-all-terminals'))$button['allow']=false;
        unset($button);
        $user=new User(['id'=>1,'company_id'=>1,'terminal_id'=>10,'route_ids'=>$global?'all':'[5]','hide'=>0]);
        $user->setRelation('company',new Company(['id'=>1,'modules'=>$modules]));$user->setRelation('role',new Role(['company_id'=>1,'name'=>'Operator','permissions'=>$permissions]));Sanctum::actingAs($user,['*']);
    }
    private function body(array $extra=[]): array { return array_replace(['title'=>'Departure update','body'=>'Please check your boarding time.','reason'=>'Operations approved update','audience'=>'selected','passenger_ids'=>[1],'request_key'=>(string)Str::uuid()],$extra); }
    private function ready(array $body): array { return $body+['preview_token'=>$this->postJson(self::BASE.'/preview',$body)->assertOk()->json('data.preview_token')]; }
    public function test_preview_has_no_side_effect_and_send_is_idempotent_and_audited() {
        $body=$this->ready($this->body());$this->assertSame(0,DB::table('mobile_notifications')->count());
        $this->postJson(self::BASE.'/send',$body)->assertOk()->assertJsonPath('data.recipient_count',1);
        $this->postJson(self::BASE.'/send',$body)->assertOk()->assertJsonPath('data.already_sent',true);
        $this->assertSame(1,DB::table('mobile_notifications')->count());$this->assertSame(1,DB::table('mobile_app_admin_audits')->count());
        $this->getJson(self::BASE)->assertOk()->assertJsonPath('data.items.0.inbox_count',1)->assertJsonPath('data.items.0.read_count',0)->assertJsonPath('data.items.0.push_accepted',0);
    }
    public function test_cross_company_recipients_and_missing_send_privilege_are_denied() {
        $this->postJson(self::BASE.'/preview',$this->body(['passenger_ids'=>[3]]))->assertForbidden();
        $this->login(['mobile-app-send-notifications']);$this->postJson(self::BASE.'/preview',$this->body())->assertForbidden();
        $this->login(['mobile-app-view-notifications']);$this->getJson(self::BASE)->assertForbidden();
        $this->assertSame(0,DB::table('mobile_notifications')->count());
    }
    public function test_changed_message_audience_or_preview_expiry_requires_new_preview() {
        $body=$this->ready($this->body());$body['body']='Different text';$this->postJson(self::BASE.'/send',$body)->assertStatus(409);
        $body=$this->ready($this->body(['audience'=>'all']));DB::table('passenger_accounts')->insert(['id'=>4,'company_id'=>1,'mobile'=>'03003334444']);
        $this->postJson(self::BASE.'/send',$body)->assertStatus(409);
        $this->assertSame(0,DB::table('mobile_notifications')->count());
    }
    public function test_broadcast_requires_its_own_privilege_and_excludes_other_companies_and_blocked_accounts() {
        $this->login(['mobile-app-broadcast-notifications']);$this->postJson(self::BASE.'/preview',$this->body(['audience'=>'all']))->assertForbidden();
        $this->login([],false);$this->postJson(self::BASE.'/preview',$this->body(['audience'=>'all']))->assertForbidden();
        $this->login();DB::table('mobile_app_passenger_controls')->insert(['company_id'=>1,'passenger_account_id'=>2,'status'=>'blacklisted']);
        $body=$this->ready($this->body(['audience'=>'all']));$this->postJson(self::BASE.'/send',$body)->assertOk()->assertJsonPath('data.recipient_count',1);
        $this->assertEquals(1,DB::table('mobile_notifications')->value('passenger_account_id'));
    }
    public function test_selected_and_trip_audiences_respect_assigned_routes_and_terminals() {
        DB::table('invoices')->insert([['id'=>1,'company_id'=>1,'passenger_account_id'=>1,'terminal_id'=>10,'route_id'=>5],['id'=>2,'company_id'=>1,'passenger_account_id'=>2,'terminal_id'=>11,'route_id'=>6]]);
        DB::table('tickets')->insert([['company_id'=>1,'invoice_id'=>1,'terminal_id'=>10,'route_id'=>5,'schedule_details_id'=>8,'type'=>'booked'],['company_id'=>1,'invoice_id'=>2,'terminal_id'=>11,'route_id'=>6,'schedule_details_id'=>8,'type'=>'booked']]);
        $this->login([],false);
        $this->postJson(self::BASE.'/preview',$this->body(['passenger_ids'=>[2]]))->assertForbidden();
        $this->postJson(self::BASE.'/preview',$this->body(['audience'=>'trip','schedule_detail_id'=>8]))->assertOk()->assertJsonPath('data.recipient_count',1);
    }
    public function test_payload_requires_reason_and_preview_and_rejects_duplicate_key_reuse() {
        $this->postJson(self::BASE.'/preview',$this->body(['reason'=>'']))->assertUnprocessable();
        $this->postJson(self::BASE.'/send',$this->body())->assertUnprocessable();
        $body=$this->ready($this->body());$this->postJson(self::BASE.'/send',$body)->assertOk();$body['title']='Changed';
        $this->postJson(self::BASE.'/send',$body)->assertStatus(409);
    }
}
