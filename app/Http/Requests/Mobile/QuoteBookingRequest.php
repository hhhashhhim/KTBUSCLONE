<?php

namespace App\Http\Requests\Mobile;

class QuoteBookingRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $passengers = collect($this->input('passengers', []))->map(function ($passenger) {
            $passenger['cnic'] = preg_replace('/\D+/', '', (string) ($passenger['cnic'] ?? ''));
            $passenger['mobile'] = preg_replace('/\D+/', '', (string) ($passenger['mobile'] ?? ''));
            return $passenger;
        })->all();
        $this->merge(['passengers' => $passengers]);
    }

    public function messages()
    {
        return [
            'passengers.*.gender.in' => 'This passenger gender is not supported for ticket booking. Please contact support.',
        ];
    }

    public function rules()
    {
        $maximum = (int) config('mobile.maximum_selectable_seats', 5);

        return [
            'origin_id' => ['required', 'integer', 'different:destination_id'],
            'destination_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'schedule_detail_id' => ['required', 'integer'],
            'seats' => ['required', 'array', 'min:1', 'max:' . $maximum],
            'seats.*' => ['required', 'string', 'distinct', 'max:20'],
            'passengers' => ['required', 'array', 'size:' . count($this->input('seats', []))],
            'passengers.*.seat_number' => ['required', 'string', 'in:' . implode(',', $this->input('seats', []))],
            'passengers.*.full_name' => ['required', 'string', 'max:150'],
            'passengers.*.cnic' => ['required', 'digits:13'],
            'passengers.*.mobile' => ['required', 'string', 'min:10', 'max:15'],
            'passengers.*.gender' => ['required', 'in:male,female'],
            'points_to_use' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
