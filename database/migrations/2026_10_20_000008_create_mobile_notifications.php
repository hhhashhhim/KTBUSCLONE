<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileNotifications extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('mobile_notification_campaigns')) Schema::create('mobile_notification_campaigns', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('actor_id');
            $t->string('request_key', 64); $t->string('payload_hash', 64); $t->string('title', 120); $t->text('body');
            $t->json('audience'); $t->text('reason'); $t->unsignedInteger('recipient_count'); $t->dateTime('created_at');
            $t->unique(['company_id', 'request_key'], 'mobile_campaign_request_unique');
        });
        if (!Schema::hasTable('mobile_notifications')) Schema::create('mobile_notifications', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('passenger_account_id');
            $t->unsignedBigInteger('campaign_id')->nullable()->index(); $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('event_key', 128); $t->string('kind', 40); $t->string('title', 120); $t->text('body');
            $t->timestamp('read_at')->nullable(); $t->dateTime('created_at');
            $t->timestamp('push_queued_at')->nullable();
            $t->unique(['company_id', 'passenger_account_id', 'event_key'], 'mobile_notice_event_unique');
            $t->index(['company_id', 'passenger_account_id', 'id'], 'mobile_notice_inbox_index');
            $t->index(['company_id', 'push_queued_at', 'id'], 'mobile_notice_queue_index');
        });
        if (!Schema::hasTable('mobile_push_devices')) Schema::create('mobile_push_devices', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('company_id'); $t->unsignedBigInteger('passenger_account_id');
            $t->unsignedBigInteger('access_token_id'); $t->string('token_hash', 64)->unique();
            $t->text('token'); $t->string('platform', 10); $t->timestamps();
            $t->index(['company_id', 'passenger_account_id'], 'mobile_push_account_index');
        });
        if (!Schema::hasTable('mobile_notification_deliveries')) Schema::create('mobile_notification_deliveries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('notification_id'); $t->unsignedBigInteger('device_id');
            $t->string('status', 20)->default('pending'); $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('next_attempt_at')->nullable()->index(); $t->timestamp('accepted_at')->nullable();
            $t->string('error_code', 80)->nullable(); $t->unique(['notification_id', 'device_id'], 'mobile_push_delivery_unique');
        });
        if (!Schema::hasTable('mobile_notification_booking_states')) Schema::create('mobile_notification_booking_states', function (Blueprint $t) {
            $t->unsignedBigInteger('invoice_id')->primary(); $t->unsignedBigInteger('company_id');
            $t->json('snapshot'); $t->unsignedInteger('sequence')->default(0); $t->dateTime('updated_at');
        });
        if (!Schema::hasTable('mobile_notification_runtime')) Schema::create('mobile_notification_runtime', function (Blueprint $t) {
            $t->unsignedBigInteger('company_id')->primary(); $t->unsignedBigInteger('invoice_cursor')->default(0);
            $t->dateTime('started_at'); $t->timestamp('last_run_at')->nullable();
            $t->boolean('push_configured')->default(false);
        });
    }
    public function down() { /* Preserve passenger inboxes, deduplication and send audit on rollback. */ }
}
