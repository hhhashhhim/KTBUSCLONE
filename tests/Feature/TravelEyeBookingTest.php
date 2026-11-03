<?php

namespace Tests\Feature;

use App\Jobs\VerifyTravelEyeTicket;
use App\Models\Terminal;
use App\Models\TravelEyeApiLog;
use App\Services\TravelEyeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TravelEyeBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'travel_eye_test', 'database.connections.travel_eye_test'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'',
        ], 'app.key'=>'base64:'.base64_encode(str_repeat('t', 32)),
            'services.travel_eye.base_url'=>'https://travel-eye.test', 'services.travel_eye.verify_path'=>'/TravelEyeApiPost/verify']);
        DB::purge('travel_eye_test'); Schema::clearResolvedInstance('db.schema');
        $this->table('tickets', ['company_id','terminal_id','customer_id','departure_city_id','destination_city_id','bus_id','schedule_id','ticket_closing_id','invoice_id'], ['seat_no','type','date','schedule_date','schedule_time','schedule_time_exact']);
        $this->table('terminals', ['company_id','city_id'], ['name','smarteye_id','smarteye_api','smarteye_password']);
        $this->table('customers', [], ['name','cnic','contact']);
        $this->table('companies', [], ['name']);
        $this->table('cities', [], ['name']);
        $this->table('buses', [], ['bus_number']);
        $this->table('ticket_closing_members', ['company_id','ticket_closing_id','type','user_id']);
        $this->table('employees', ['company_id'], ['name','contact']);
        require_once database_path('migrations/2026_09_11_000002_create_travel_eye_api_logs_table.php');
        (new \CreateTravelEyeApiLogsTable)->up();
        DB::table('cities')->insert([['id'=>1,'name'=>'Rawalpindi'], ['id'=>2,'name'=>'Karachi']]);
        DB::table('companies')->insert(['id'=>1,'name'=>'Kainat Travels']);
        DB::table('customers')->insert(['id'=>1,'name'=>'Test passenger','cnic'=>'11111-1111111-1','contact'=>'0300-0000000']);
        DB::table('buses')->insert(['id'=>1,'bus_number'=>'TEST-BUS']);
        Terminal::create(['id'=>1,'company_id'=>1,'city_id'=>1,'name'=>'Test terminal','smarteye_id'=>'test-api-login','smarteye_api'=>'test-api-key','smarteye_password'=>'not-the-api-key']);
        $this->fake(['*'=>Http::response(['status'=>'true', 'message'=>'Data updated successfully!'], 200)]);
    }

    protected function tearDown(): void { DB::purge('travel_eye_test'); parent::tearDown(); }

    private function fake($responses): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake($responses);
    }

    private function table($name, array $integers = [], array $strings = []): void
    {
        Schema::create($name, function (Blueprint $table) use ($integers, $strings) {
            $table->id(); foreach ($integers as $column) $table->integer($column)->nullable();
            foreach ($strings as $column) $table->text($column)->nullable();
            $table->timestamps(); $table->softDeletes();
        });
    }

    private function ticket(int $id, array $extra = []): void
    {
        DB::table('tickets')->insert(array_merge(['id'=>$id,'company_id'=>1,'terminal_id'=>1,'customer_id'=>1,
            'departure_city_id'=>1,'destination_city_id'=>2,'bus_id'=>1,'schedule_id'=>10,'invoice_id'=>100,
            'seat_no'=>(string)$id,'type'=>'booked','date'=>'2026-11-03','schedule_date'=>'2026-11-03',
            'schedule_time'=>'23:50:00','schedule_time_exact'=>'23:50:00'], $extra));
    }

    public function test_each_ticket_has_a_unique_voucher_and_matches_postman_form_fields(): void
    {
        DB::table('employees')->insert(['id'=>9,'company_id'=>1,'name'=>'Test driver','contact'=>'03000000001']);
        DB::table('ticket_closing_members')->insert(['company_id'=>1,'ticket_closing_id'=>5,'type'=>1,'user_id'=>9]);
        $service = new TravelEyeService;
        foreach ([1,2,3,4] as $id) { $this->ticket($id, ['ticket_closing_id'=>5]); $service->verifyTicket($id); }
        $this->assertSame('success', TravelEyeApiLog::first()->status, TravelEyeApiLog::first()->exception_message ?? '');
        Http::assertSentCount(4);
        $this->assertSame(['KT-C1-T1','KT-C1-T2','KT-C1-T3','KT-C1-T4'], TravelEyeApiLog::orderBy('ticket_id')->pluck('voucher')->all());
        Http::assertSent(function ($request) {
            return $request->url() === 'https://travel-eye.test/TravelEyeApiPost/verify'
                && $request->method() === 'POST'
                && strpos(implode(' ', $request->header('Content-Type')), 'multipart/form-data') !== false
                && strpos($request->body(), 'name="usrName"') !== false
                && strpos($request->body(), 'test-api-login') !== false
                && strpos($request->body(), 'name="usrPass"') !== false
                && strpos($request->body(), 'test-api-key') !== false
                && strpos($request->body(), 'not-the-api-key') === false;
        });
        $log = TravelEyeApiLog::where('ticket_id',1)->firstOrFail();
        $this->assertSame('success', $log->status);
        $this->assertSame('2026-11-03 23:50:00', $log->request_payload['departDateTime']);
        $this->assertSame('1111111111111', $log->request_payload['cnic']);
        $this->assertSame('Test driver', $log->request_payload['driverName']);
        $this->assertSame('Rawalpindi', $log->request_payload['terminal']);
        $this->assertSame('Karachi', $log->request_payload['to']);
        $this->assertSame('TEST-BUS', $log->request_payload['busRegno']);
        $this->assertArrayNotHasKey('usrPass', $log->request_payload);
        $this->assertArrayNotHasKey('usrName', $log->request_payload);
        $service->verifyTicket(1); Http::assertSentCount(4);
    }

    public function test_only_confirmed_tickets_of_configured_booking_terminal_are_sent(): void
    {
        Terminal::create(['id'=>2,'company_id'=>1,'city_id'=>1,'name'=>'Unconfigured']);
        foreach (['advance booking','reserved','cancelled'] as $index=>$type) {
            $this->ticket($index+1, ['type'=>$type]); (new TravelEyeService)->verifyTicket($index+1);
        }
        $this->ticket(4, ['terminal_id'=>2]); (new TravelEyeService)->verifyTicket(4);
        $this->ticket(5, ['company_id'=>2]); (new TravelEyeService)->verifyTicket(5);
        $this->ticket(6, ['deleted_at'=>now()]); (new TravelEyeService)->verifyTicket(6);
        Http::assertNothingSent();
        DB::table('tickets')->where('id',1)->update(['type'=>'booked']);
        (new TravelEyeService)->verifyTicket(1);
        Http::assertSentCount(1);
    }

    public function test_http_200_rejection_is_failed_and_retry_keeps_the_ticket_voucher(): void
    {
        $this->fake(['*'=>Http::sequence()->push(['status'=>'false','message'=>'Rejected'],200)->push(['status'=>true],200)]);
        $this->ticket(1);
        $service = new TravelEyeService;
        $service->verifyTicket(1);
        $this->assertSame('failed', TravelEyeApiLog::first()->status);
        $voucher = TravelEyeApiLog::first()->voucher;
        $service->verifyTicket(1);
        $log = TravelEyeApiLog::first();
        $this->assertSame('success', $log->status);
        $this->assertSame($voucher, $log->voucher);
        $this->assertSame(2, $log->attempts);
        Http::assertSentCount(2);
    }

    public function test_missing_fields_and_transport_failure_never_modify_the_booking(): void
    {
        $this->ticket(1, ['date'=>null,'schedule_date'=>null]);
        (new TravelEyeService)->verifyTicket(1);
        Http::assertNothingSent();
        $this->assertSame('skipped', TravelEyeApiLog::first()->status);
        $this->ticket(2);
        $this->fake(function () { throw new ConnectionException('Timeout test-api-key'); });
        (new VerifyTravelEyeTicket(2))->handle(new TravelEyeService);
        $log = TravelEyeApiLog::where('ticket_id',2)->firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertStringNotContainsString('test-api-key', $log->exception_message);
        $this->assertSame('booked', DB::table('tickets')->where('id',2)->value('type'));
    }

    public function test_response_without_confirmed_success_does_not_become_success(): void
    {
        $this->fake(['*'=>Http::response('<html>Gateway response</html>',200)]);
        $this->ticket(1); (new TravelEyeService)->verifyTicket(1);
        $this->assertSame('failed', TravelEyeApiLog::first()->status);
    }

    public function test_http_failure_and_echoed_credentials_are_logged_safely(): void
    {
        $this->fake(['*'=>Http::response(['status'=>'true', 'message'=>'test-api-login test-api-key'], 500)]);
        $this->ticket(1); (new TravelEyeService)->verifyTicket(1);
        $log = TravelEyeApiLog::first();
        $this->assertSame('failed', $log->status);
        $this->assertSame(500, $log->http_status);
        $this->assertStringNotContainsString('test-api-key', $log->response_payload);
        $this->assertStringNotContainsString('test-api-login', $log->response_payload);
    }

    public function test_booking_terminal_credentials_are_used_and_historical_success_is_not_resent(): void
    {
        Terminal::create(['id'=>2,'company_id'=>1,'city_id'=>2,'name'=>'Second terminal','smarteye_id'=>'other-login','smarteye_api'=>'other-api-key']);
        $this->ticket(1, ['terminal_id'=>2]);
        (new TravelEyeService)->verifyTicket(1);
        Http::assertSent(fn($request) => strpos($request->body(),'other-api-key') !== false
            && strpos($request->body(),'test-api-key') === false);
        $this->assertSame(2, TravelEyeApiLog::first()->terminal_id);
        TravelEyeApiLog::first()->update(['voucher'=>'KT-1-S10-20261103-2350']);
        (new TravelEyeService)->verifyTicket(1);
        Http::assertSentCount(1);
        $this->assertSame('KT-1-S10-20261103-2350', TravelEyeApiLog::first()->voucher);
    }

    public function test_job_is_deferred_until_after_response_and_contains_unexpected_errors(): void
    {
        $calls = 0;
        $service = \Mockery::mock(TravelEyeService::class);
        $service->shouldReceive('verifyTicket')->once()->with(7)->andReturnUsing(function () use (&$calls) { $calls++; });
        $this->app->instance(TravelEyeService::class, $service);
        VerifyTravelEyeTicket::dispatchAfterResponse(7);
        $this->assertSame(0, $calls);
        $this->app->terminate();
        $this->assertSame(1, $calls);
        $service = \Mockery::mock(TravelEyeService::class);
        $service->shouldReceive('verifyTicket')->with(8)->andThrow(new \RuntimeException('sensitive error'));
        Log::shouldReceive('error')->once()->with('Travel Eye background verification could not complete.', ['ticket_id'=>8,'exception'=>\RuntimeException::class]);
        (new VerifyTravelEyeTicket(8))->handle($service);
        $source = file_get_contents(app_path('Http/Controllers/Booking/BookingController.php'));
        $dispatch = strpos($source, 'VerifyTravelEyeTicket::dispatchAfterResponse($ticketId)');
        $this->assertNotFalse($dispatch);
        $this->assertNotFalse(strrpos(substr($source,0,$dispatch), 'DB::commit();'));
    }
}
