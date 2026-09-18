<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateScheduleDetailsNext60Days extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('The upcoming schedule copy requires MySQL/MariaDB triggers.');
        }
        if (Schema::hasTable('schedule_details_next_60_days') || Schema::hasTable('schedule_projection_syncs')) {
            throw new RuntimeException('Upcoming schedule tables already exist; inspect the previous migration before retrying.');
        }

        try {
            // Copy the actual deployed schema, including original IDs and all detail fields.
            DB::statement('CREATE TABLE `schedule_details_next_60_days` LIKE `schedule_details`');
            Schema::table('schedule_details_next_60_days', function (Blueprint $table) {
                $table->index(['company_id', 'departure_date', 'departure_id', 'destination_id', 'schedule_id'], 'upcoming_departure_lookup');
            });
            Schema::create('schedule_projection_syncs', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->primary();
                $table->date('window_start');
                $table->date('window_end');
                $table->unsignedBigInteger('row_count')->default(0);
                $table->timestamp('synced_at')->nullable();
            });

            $columns = Schema::getColumnListing('schedule_details');
            $quoted = implode(', ', array_map(function ($column) { return '`' . $column . '`'; }, $columns));
            $values = implode(', ', array_map(function ($column) { return 'NEW.`' . $column . '`'; }, $columns));
            $updates = implode(', ', array_map(function ($column) { return '`' . $column . '` = NEW.`' . $column . '`'; }, $columns));
            // Pakistan has a fixed UTC offset. Do not depend on the DB session timezone.
            $offset = (int) (now(config('app.timezone'))->offset / 60);
            $today = "DATE(UTC_TIMESTAMP() + INTERVAL {$offset} MINUTE)";
            $copy = "IF NEW.departure_date >= {$today} AND NEW.departure_date < DATE_ADD({$today}, INTERVAL 60 DAY) THEN
                INSERT INTO `schedule_details_next_60_days` ({$quoted}) VALUES ({$values})
                ON DUPLICATE KEY UPDATE {$updates};
            END IF;";

            DB::unprepared("CREATE TRIGGER upcoming_schedule_details_insert AFTER INSERT ON schedule_details
                FOR EACH ROW BEGIN {$copy} END");
            DB::unprepared("CREATE TRIGGER upcoming_schedule_details_update AFTER UPDATE ON schedule_details
                FOR EACH ROW BEGIN
                    IF OLD.id <> NEW.id OR NEW.departure_date IS NULL OR NEW.departure_date < {$today}
                        OR NEW.departure_date >= DATE_ADD({$today}, INTERVAL 60 DAY) THEN
                        DELETE FROM schedule_details_next_60_days WHERE id = OLD.id;
                    END IF;
                    {$copy}
                END");
            DB::unprepared('CREATE TRIGGER upcoming_schedule_details_delete AFTER DELETE ON schedule_details
                FOR EACH ROW DELETE FROM schedule_details_next_60_days WHERE id = OLD.id');
        } catch (Throwable $exception) {
            // MySQL DDL is not transactional; undo partial setup if trigger privileges are missing.
            $this->down();
            throw $exception;
        }
    }

    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS upcoming_schedule_details_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS upcoming_schedule_details_update');
        DB::unprepared('DROP TRIGGER IF EXISTS upcoming_schedule_details_delete');
        Schema::dropIfExists('schedule_projection_syncs');
        Schema::dropIfExists('schedule_details_next_60_days');
    }
}
