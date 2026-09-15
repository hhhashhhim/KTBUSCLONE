<?php

namespace App\Http\Requests\Mobile;

class VerifyPassengerOtpRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return ['code' => ['required', 'digits:6']];
    }
}
