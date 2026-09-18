<?php

namespace App\Services;

use App\Models\Schedule\ScheduleDetail;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class UpcomingScheduleService
{
    public const TABLE = 'schedule_details_next_60_days';

    public function query($date, int $companyId)
    {
        $model = new ScheduleDetail();
        $day = Carbon::parse($date)->toDateString();
        $today = Carbon::today()->toDateString();
        $lastDay = Carbon::today()->addDays(59)->toDateString();

        // Never restrict an existing terminal's booking horizon or historical access.
        if ($day < $today || $day > $lastDay) {
            return $model->newQuery();
        }
        try {
            $sync = DB::table('schedule_projection_syncs')->where('company_id', $companyId)->first();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '42S02') {
                throw $exception;
            }
            return $model->newQuery(); // Code may be deployed before the migration.
        }
        if ($sync && $sync->synced_at && $day >= $sync->window_start && $day <= $sync->window_end) {
            $model->setTable(self::TABLE);
        }

        return $model->newQuery();
    }

    public function sync(int $companyId): array
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasTable('schedule_projection_syncs')) {
            throw new RuntimeException('Run the upcoming-schedules database migration before syncing.');
        }
        $lock = 'upcoming-schedules:' . substr(sha1(DB::getDatabaseName()), 0, 12) . ':' . $companyId;
        $acquired = DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock]);
        if ((int) $acquired->acquired !== 1) {
            throw new RuntimeException('A schedule sync is already running. Please wait and try again.', 409);
        }

        try {
            $from = Carbon::today()->toDateString();
            $to = Carbon::today()->addDays(59)->toDateString();
            $columns = Schema::getColumnListing('schedule_details');
            if ($columns !== Schema::getColumnListing(self::TABLE)) {
                throw new RuntimeException('The upcoming schedule copy needs a schema update before it can be synced.');
            }
            return DB::transaction(function () use ($companyId, $from, $to, $columns) {
                // Lock source rows before copying them so a concurrent edit cannot be
                // overwritten by an older snapshot. Triggers use the same source->copy order.
                DB::table('schedule_details')->where('company_id', $companyId)
                    ->whereBetween('departure_date', [$from, $to])->lockForUpdate()
                    ->chunkById(1000, function ($rows) use ($columns) {
                        DB::table(self::TABLE)->upsert($rows->map(function ($row) {
                            return (array) $row;
                        })->all(), ['id'], array_values(array_diff($columns, ['id'])));
                    });

                // Only the copy is pruned. Original schedule history is never changed.
                DB::table(self::TABLE)->where('company_id', $companyId)
                    ->where(function ($query) use ($from, $to) {
                        $query->whereNull('departure_date')->orWhere('departure_date', '<', $from)
                            ->orWhere('departure_date', '>', $to);
                    })->delete();
                DB::table(self::TABLE)->where('company_id', $companyId)
                    ->whereNotExists(function ($query) use ($from, $to) {
                        $query->selectRaw('1')->from('schedule_details as source')
                            ->whereColumn('source.id', self::TABLE . '.id')
                            ->whereColumn('source.company_id', self::TABLE . '.company_id')
                            ->whereBetween('source.departure_date', [$from, $to]);
                    })->delete();

                $state = [
                    'window_start' => $from,
                    'window_end' => $to,
                    'row_count' => DB::table(self::TABLE)->where('company_id', $companyId)->count(),
                    'synced_at' => now()->toDateTimeString(),
                ];
                DB::table('schedule_projection_syncs')->updateOrInsert(['company_id' => $companyId], $state);

                return $state;
            }, 3);
        } finally {
            DB::select('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }
}
