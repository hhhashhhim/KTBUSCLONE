<?php

namespace App\Http\Requests\Mobile;

class CreateMobileBookingRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'quote_token' => ['required', 'string', 'size:64'],
            'payment_method' => ['required', 'string', 'max:40'],
        ];
    }
}
