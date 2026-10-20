<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileAppEngagement
{
    public function read(Request $request, MobileAppAdminData $data): array
    {
        $days = (int) $request->input('days', 7);
        $end = CarbonImmutable::now('Asia/Karachi')->startOfDay()->addDay();
        $start = $end->subDays($days);
        $installed = Schema::hasTable('mobile_app_login_events') && Schema::hasTable('mobile_app_runtime');
        $runtime = $installed ? DB::table('mobile_app_runtime')->where('company_id', $request->user()->company_id)->first() : null;
        $result = ['available' => (bool) $runtime, 'from' => $start->toDateString(), 'to' => $end->subDay()->toDateString(),
            'tracking_started_at' => $runtime ? CarbonImmutable::parse($runtime->tracking_started_at, 'UTC')->toIso8601String() : null,
            'last_seen_at' => $runtime ? CarbonImmutable::parse($runtime->last_seen_at, 'UTC')->toIso8601String() : null,
            'total_logins' => null, 'period_logins' => null, 'first_logins' => null, 'unique_users' => null, 'daily' => [],
            'downloads' => ['available' => false, 'message' => 'Connect Google Play or App Store reports to see new downloads.'],
        ];
        if (!$runtime || !$data->capabilities()['users']) { $result['available'] = false; return $result; }
        // Strip reporting dates/status/search: scope this aggregate by company, terminals and assigned routes only.
        $scope = Request::create('/', 'GET', $request->only(['terminal_id', 'route_id']));
        $scope->setUserResolver($request->getUserResolver());
        $visible = $data->users($scope, false)->select('a.id');
        $query = DB::table('mobile_app_login_events as e')->where('e.company_id', $request->user()->company_id)
            ->whereIn('e.passenger_account_id', $visible);
        $result['total_logins'] = (clone $query)->count();
        $period = (clone $query)->where('e.occurred_at', '>=', $start->setTimezone('UTC')->format('Y-m-d H:i:s'))
            ->where('e.occurred_at', '<', $end->setTimezone('UTC')->format('Y-m-d H:i:s'));
        $result['period_logins'] = (clone $period)->count();
        $result['first_logins'] = (clone $period)->where('e.first_login', 1)->count();
        $result['unique_users'] = (clone $period)->distinct()->count('e.passenger_account_id');
        // UTC storage, explicit PKT bucketing on both supported test and production databases.
        $dateSql = DB::connection()->getDriverName() === 'sqlite' ? "date(e.occurred_at, '+5 hours')" : 'DATE(DATE_ADD(e.occurred_at, INTERVAL 5 HOUR))';
        $counts = $period->selectRaw($dateSql . ' as day, COUNT(*) as logins, SUM(e.first_login) as first_logins')
            ->groupByRaw($dateSql)->get()->keyBy('day');
        for ($day = $start; $day->lessThan($end); $day = $day->addDay()) {
            $key = $day->toDateString(); $row = $counts->get($key);
            $tracked = $day->addDay()->greaterThan(CarbonImmutable::parse($runtime->tracking_started_at, 'UTC'));
            $result['daily'][] = ['date' => $key, 'logins' => $tracked ? (int) ($row->logins ?? 0) : null,
                'first_logins' => $tracked ? (int) ($row->first_logins ?? 0) : null];
        }
        return $result;
    }
}
