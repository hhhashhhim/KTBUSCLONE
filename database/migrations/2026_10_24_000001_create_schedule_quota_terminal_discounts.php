<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateScheduleQuotaTerminalDiscounts extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('schedule_quota_terminal_discounts')) {
            Schema::create('schedule_quota_terminal_discounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('schedule_id');
                $table->unsignedBigInteger('terminal_id');
                $table->decimal('percentage', 5, 2);
                $table->boolean('active')->default(true);
                $table->unsignedBigInteger('updated_by');
                $table->string('migration_note')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'schedule_id', 'terminal_id'], 'schedule_quota_discount_terminal');
            });
        }
        // Keep the original per-user table intact for audit/rollback. Never overwrite
        // terminal settings if this migration is retried after a partial deployment.
        if (!Schema::hasTable('schedule_quota_discounts')) return;
        $groups = DB::table('schedule_quota_discounts as d')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->join('terminals as t', 't.id', '=', 'u.terminal_id')
            ->whereColumn('u.company_id', 'd.company_id')->whereColumn('t.company_id', 'd.company_id')
            ->whereNull('u.deleted_at')->whereNull('t.deleted_at')->where('u.hide', 0)->where('t.hide', 0)
            ->select('d.*', 't.id as terminal_id')->get()
            ->groupBy(function ($row) { return $row->company_id . ':' . $row->schedule_id . ':' . $row->terminal_id; });
        foreach ($groups as $rows) {
            $row = $rows->first();
            $rates = $rows->where('active', true)->pluck('percentage')->map(function ($rate) { return (float) $rate; })->unique();
            $conflict = $rates->count() > 1;
            DB::table('schedule_quota_terminal_discounts')->insertOrIgnore([
                'company_id' => $row->company_id, 'schedule_id' => $row->schedule_id, 'terminal_id' => $row->terminal_id,
                'percentage' => $rates->count() === 1 ? $rates->first() : 0,
                'active' => $rates->count() === 1, 'updated_by' => $row->updated_by,
                'migration_note' => $conflict ? 'Previous users on this terminal had different discount rates. Set the terminal percentage and save to enable it.' : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Preserve terminal settings and the original user rules on rollback.
    }
}
