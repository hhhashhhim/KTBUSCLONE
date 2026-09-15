<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Resources\Mobile\ScheduleResource;
use App\Services\Mobile\MobileTravelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class MobileTravelController extends Controller
{
    use RespondsWithMobileApi;

    public function cities(MobileTravelService $travel)
    {
        try {
            return $this->success($travel->cities(), 'Cities retrieved.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function destinations(Request $request, MobileTravelService $travel)
    {
        $validator = Validator::make($request->all(), ['origin_id' => ['required', 'integer']]);
        if ($validator->fails()) {
            return $this->failure('The request is invalid.', $validator->errors()->toArray(), 422);
        }
        try {
            return $this->success($travel->destinations((int) $request->origin_id), 'Destinations retrieved.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function schedules(Request $request, MobileTravelService $travel)
    {
        $validator = Validator::make($request->all(), [
            'origin_id' => ['required', 'integer', 'different:destination_id'],
            'destination_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);
        if ($validator->fails()) {
            return $this->failure('The request is invalid.', $validator->errors()->toArray(), 422);
        }
        try {
            $data = $travel->schedules(
                (int) $request->origin_id,
                (int) $request->destination_id,
                $request->date
            )->map(function ($schedule) use ($request) {
                return (new ScheduleResource($schedule))->resolve($request);
            });
            return $this->success($data, 'Schedules retrieved.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }

    public function seats(Request $request, $scheduleDetail, MobileTravelService $travel)
    {
        $validator = Validator::make($request->all(), [
            'origin_id' => ['required', 'integer', 'different:destination_id'],
            'destination_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);
        if ($validator->fails()) {
            return $this->failure('The request is invalid.', $validator->errors()->toArray(), 422);
        }
        try {
            return $this->success($travel->seatLayout(
                (int) $scheduleDetail,
                (int) $request->origin_id,
                (int) $request->destination_id,
                $request->date
            ), 'Seat availability retrieved.');
        } catch (RuntimeException $exception) {
            return $this->failure($exception->getMessage(), [], $exception->getCode() ?: 422);
        }
    }
}
