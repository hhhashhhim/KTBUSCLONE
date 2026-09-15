<?php

namespace App\Http\Requests\Mobile;

class ResetPassengerPasswordRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(['mobile' => preg_replace('/\D+/', '', (string) $this->mobile)]);
    }

    public function rules()
    {
        return [
            'mobile' => ['required', 'string', 'min:10', 'max:15'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
