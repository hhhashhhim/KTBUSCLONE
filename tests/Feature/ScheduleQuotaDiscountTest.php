<?php

namespace Tests\Feature;

use App\Http\Controllers\Schedule\ScheduleQuotaDiscountController;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ScheduleQuotaDiscountService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleQuotaDiscountTest extends TestCase
{
    private $service;
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'quota_discount_test', 'database.connections.quota_discount_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('quota_discount_test'); Schema::clearResolvedInstance('db.schema');
        Carbon::setTestNow('2026-10-21 10:00:00');
        $this->table('schedules', ['company_id','route_id','intermediate_quota'], ['time']);
        $this->table('routes');
        $this->table('routes_fares', ['route_id','departure_city_id','destination_city_id']);
        $this->table('schedule_details', ['company_id','schedule_id','departure_id','destination_id'], ['schedule_date','departure_date','departure_time']);
        $this->table('tickets', ['company_id','schedule_id','departure_city_id','destination_city_id'], ['seat_no','schedule_date','type','booked_time']);
        $this->table('users', ['company_id','hide','terminal_id'], ['name','user_name']);
        $this->table('roles', [], ['permissions']);
        $this->table('activity_logs', ['company_id','activity_by'], ['message','requested_host']);
        require_once database_path('migrations/2026_10_21_000001_create_schedule_quota_discounts.php');
        (new \CreateScheduleQuotaDiscounts)->up();
        DB::table('schedules')->insert(['id'=>1,'company_id'=>1,'route_id'=>1,'intermediate_quota'=>10,'time'=>'23:00:00']);
        DB::table('routes')->insert(['id'=>1]);
        foreach ([[10,20],[20,30],[30,40]] as $pair) DB::table('routes_fares')->insert(['route_id'=>1,'departure_city_id'=>$pair[0],'destination_city_id'=>$pair[1]]);
        foreach ([[1,10,40],[2,10,20],[3,20,30],[4,30,40],[5,10,30]] as $d) DB::table('schedule_details')->insert(['id'=>$d[0],'company_id'=>1,'schedule_id'=>1,'departure_id'=>$d[1],'destination_id'=>$d[2],'schedule_date'=>'2026-10-21','departure_date'=>'2026-10-21','departure_time'=>'23:00:00']);
        foreach ([[1,1,0],[2,1,0],[3,2,0],[4,1,1]] as $u) DB::table('users')->insert(['id'=>$u[0],'company_id'=>$u[1],'hide'=>$u[2],'name'=>'Test user '.$u[0],'user_name'=>'test'.$u[0]]);
        DB::table('roles')->insert(['id'=>1,'permissions'=>json_encode([['allow'=>true,'childs'=>[['name'=>'schedules','allow'=>true,'buttons'=>[['name'=>'edit-schedule','allow'=>true]]]]]])]);
        Auth::setUser((new User)->forceFill(['id'=>1,'company_id'=>1,'hide'=>0,'role_id'=>1]));
        DB::table('schedule_quota_discounts')->insert(['company_id'=>1,'schedule_id'=>1,'user_id'=>1,'percentage'=>2,'updated_by'=>1]);
        $this->service = new ScheduleQuotaDiscountService;
        $this->ticket('1'); $this->ticket('2');
    }
    protected function tearDown(): void { Carbon::setTestNow(); DB::purge('quota_discount_test'); parent::tearDown(); }
    private function table($name, $ints=[], $strings=[]): void {
        Schema::create($name, function (Blueprint $t) use ($ints,$strings) { $t->id(); foreach ($ints as $c) $t->integer($c)->nullable(); foreach ($strings as $c) $t->string($c)->nullable(); $t->timestamps(); $t->softDeletes(); });
    }
    private function ticket($seat, $extra=[]): void { DB::table('tickets')->insert(array_merge(['company_id'=>1,'schedule_id'=>1,'schedule_date'=>'2026-10-21','seat_no'=>$seat,'departure_city_id'=>10,'destination_city_id'=>20,'type'=>'booked','booked_time'=>'2026-10-21 09:00:00'],$extra)); }
    private function map(): array { return [[['seatNo'=>'1','class'=>1,'reserved'=>true,'fare'=>1000,'original_fare'=>1100],['seatNo'=>'2','class'=>1,'reserved'=>true,'fare'=>1000],['seatNo'=>'3','class'=>1,'reserved'=>true,'fare'=>1000]]]; }
    private function preview($id=3, $map=null): array { return $this->service->preview(ScheduleDetail::findOrFail($id), Ticket::where('company_id',1)->where('schedule_date','2026-10-21')->get(), $map ?? $this->map(), Auth::user()); }
    private function request($payload): Request { $r = Request::create('/', 'POST', $payload); $r->setUserResolver(fn()=>Auth::user()); return $r; }

    public function test_only_reused_seats_receive_user_discount_and_partner_flags(): void {
        $map=$this->preview();
        $this->assertEquals([980,980,1000],array_column($map[0],'fare'));
        $this->assertSame(['Yes','Yes','No'],array_column($map[0],'is_special'));
        $this->assertSame(['Yes','Yes','No'],array_column($map[0],'is_speacial'));
        $this->assertEquals(120,$map[0][0]['discount']); // 100 existing + 20 quota, not 2% twice.
        $this->assertEquals(20,$map[0][0]['quota_discount']);
        $this->assertEquals(1100,$map[0][0]['original_fare']);
        $this->assertEquals(1000,$map[0][0]['fare_before_quota_discount']);
    }
    public function test_overlap_full_route_and_reserved_or_cancelled_seats_are_not_special(): void {
        $this->assertSame(['No','No','No'],array_column($this->preview(1)[0],'is_special'));
        $this->assertSame(['No','No','No'],array_column($this->preview(5)[0],'is_special'));
        DB::table('tickets')->where('seat_no','1')->update(['deleted_at'=>now()]);
        DB::table('tickets')->where('seat_no','2')->update(['type'=>'advance booking']);
        $this->assertSame(['No','No','No'],array_column($this->preview()[0],'is_special'));
    }
    public function test_before_and_after_segments_qualify_but_other_dates_and_post_departure_sales_do_not(): void {
        DB::table('tickets')->update(['departure_city_id'=>20,'destination_city_id'=>30]);
        $this->assertSame('Yes',$this->preview(2)[0][0]['is_special']);
        $this->assertSame('Yes',$this->preview(4)[0][0]['is_special']);
        DB::table('tickets')->where('seat_no','1')->update(['schedule_date'=>'2026-10-22']);
        DB::table('tickets')->where('seat_no','2')->update(['booked_time'=>'2026-10-21 23:05:00']);
        $this->assertSame(['No','No','No'],array_column($this->preview(4)[0],'is_special'));
    }
    public function test_user_company_permissions_and_blank_quota_bound_discount(): void {
        foreach ([2,3,4] as $id) { Auth::setUser(User::find($id)); $this->assertSame('No',$this->preview()[0][0]['is_special']); }
        Auth::setUser((new User)->forceFill(['id'=>1,'company_id'=>1,'hide'=>0]));
        DB::table('schedules')->update(['intermediate_quota'=>null]);
        $this->assertSame('No',$this->preview()[0][0]['is_special']);
    }
    public function test_blocked_seat_and_zero_or_full_percentage(): void {
        $map=$this->map(); $map[0][0]['terminal_allow']=false; $map[0][1]['type']='booked';
        $this->assertSame(['No','No','No'],array_column($this->preview(3,$map)[0],'is_special'));
        DB::table('schedule_quota_discounts')->update(['percentage'=>100]);
        $this->assertEquals(0,$this->preview()[0][0]['fare']);
        DB::table('schedule_quota_discounts')->update(['percentage'=>0]);
        $this->assertSame('No',$this->preview()[0][0]['is_special']);
    }
    public function test_server_validates_each_seat_class_and_net_price(): void {
        $map=$this->preview();
        $quotes=$this->service->validateSelection($map,['1','3'],[1,1],[980,1000]);
        $this->assertEquals(20,$quotes[0]['quota_discount']);
        $this->assertEquals(0,$quotes[1]['quota_discount']);
        foreach ([[['1','3'],[1,1],[980,980]],[['1'],[2],[980]],[['1','1'],[1,1],[980,980]],[['9'],[1],[980]]] as $case) {
            try { $this->service->validateSelection($map,...$case); $this->fail('Invalid selection accepted'); }
            catch (ValidationException $e) { $this->assertArrayHasKey('quota_discount',$e->errors()); }
        }
    }
    public function test_settings_are_company_scoped_audited_and_removable(): void {
        $c=new ScheduleQuotaDiscountController;
        $data=$c->show($this->request(['id'=>1]))->getData(true);
        $this->assertEquals([1,2],array_column($data['users'],'id'));
        $c->save($this->request(['id'=>1,'discounts'=>[['user_id'=>1,'percentage'=>2],['user_id'=>2,'percentage'=>3.5]]]));
        $this->assertSame(2,DB::table('schedule_quota_discounts')->count());
        $this->assertSame(1,DB::table('activity_logs')->count());
        $c->save($this->request(['id'=>1,'discounts'=>[]]));
        $this->assertSame(0,DB::table('schedule_quota_discounts')->where('active',true)->count());
        $this->assertSame(2,DB::table('schedule_quota_discounts')->count());
        $this->assertSame('No',$this->preview()[0][0]['is_special']);
    }
    public function test_duplicate_cross_company_hidden_users_and_invalid_percentages_cannot_be_saved(): void {
        $c=new ScheduleQuotaDiscountController;
        foreach ([[['user_id'=>3,'percentage'=>2]],[['user_id'=>4,'percentage'=>2]],[['user_id'=>1,'percentage'=>101]],[['user_id'=>1,'percentage'=>-1]],[['user_id'=>1,'percentage'=>2.555]],[['user_id'=>1,'percentage'=>2],['user_id'=>1,'percentage'=>3]]] as $rows) {
            try { $c->save($this->request(['id'=>1,'discounts'=>$rows])); $this->fail('Invalid settings accepted'); }
            catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertSame(0,DB::table('activity_logs')->count());
    }
}
