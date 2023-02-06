<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\City;
use App\Models\FareClass;
use App\Models\FareTable;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\ScheduleDetail;
use App\Models\Route\RouteFare;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Auth; 

class UpdateSchedulesTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $start_date = date("Y-m-d");
            $schedules = Schedule::where("end_date",'>=', $start_date)->where("company_id",$this->user->company_id)->get();
        
        foreach($schedules as $schedule)
        {
            $routeDetails = RouteFare::where('route_id', $schedule->route_id)->get()->groupBy('fare_class_id')->first();
            $days = $this->getDays($start_date, $schedule->end_date);

            for ($i = 0; $i <= $days; $i++) {
                $lastDepId = $routeDetails[0]->departure_city_id;
                $totalTime = strtotime(date("$start_date $schedule->time")) + ($i * 86400);
                $scheduleStartDate = date("Y-m-d", $totalTime);
                foreach ($routeDetails as $detail) {

                    if ($lastDepId == $detail->departure_city_id) {
                        $departureTime = date("Y-m-d H:i", $totalTime);
                    } else {
                        $fareTableTime = FareTable::where(['from_city_id' => $lastDepId, 'to_city_id' => $detail->departure_city_id])->first()->time_difference;
                        $timeDiff = explode(':', $fareTableTime);
                        $totalTime = $totalTime + (($timeDiff[0] * 3600) + ($timeDiff[1] * 60));
                        $departureTime = date("Y-m-d H:i", $totalTime);
                        $lastDepId = $detail->departure_city_id;
                        // this is single schedule end date to calculate schedule completion days
                    }
                    $scheduleEndDate = date("Y-m-d", $totalTime);

                    ScheduleDetail::where([
                        'company_id' => $this->user->company_id,
                        'schedule_id' => $schedule->id,
                        'departure_id' => $detail->departure_city_id,
                        'destination_id' => $detail->destination_city_id,
                        'schedule_date' => $scheduleStartDate,// schedule departure date
                    ])->update([
                        'departure_time' => date('H:i', strtotime($departureTime)),
                        'departure_date' => date('Y-m-d', strtotime($departureTime)),
                    ]);
                }

            }
            // get completion days of schedule
            $schedule_days = $this->getDays($scheduleStartDate, $scheduleEndDate);
            Schedule::where("id", $schedule->id)->update([
                'schedule_days' => $schedule_days,
            ]);
        }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
        
    }

    public function getDays($start, $end)
    {
        return (strtotime(date("Y-m-d", strtotime($end))) - strtotime(date("Y-m-d", strtotime($start)))) / 86400;
    }
}
