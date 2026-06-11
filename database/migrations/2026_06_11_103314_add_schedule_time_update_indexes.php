<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddScheduleTimeUpdateIndexes extends Migration
{
    public function up()
    {
        if (!$this->indexExists('schedule_details', 'schedule_details_company_schedule_date_index')) {
            DB::statement('ALTER TABLE schedule_details ADD INDEX schedule_details_company_schedule_date_index (company_id, schedule_id, schedule_date)');
        }

        if (!$this->indexExists('tickets', 'tickets_company_schedule_date_index')) {
            DB::statement('ALTER TABLE tickets ADD INDEX tickets_company_schedule_date_index (company_id, schedule_id, schedule_date)');
        }
    }

    public function down()
    {
        if ($this->indexExists('tickets', 'tickets_company_schedule_date_index')) {
            Schema::table('tickets', function ($table) {
                $table->dropIndex('tickets_company_schedule_date_index');
            });
        }

        if ($this->indexExists('schedule_details', 'schedule_details_company_schedule_date_index')) {
            Schema::table('schedule_details', function ($table) {
                $table->dropIndex('schedule_details_company_schedule_date_index');
            });
        }
    }

    private function indexExists($table, $index)
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
}
