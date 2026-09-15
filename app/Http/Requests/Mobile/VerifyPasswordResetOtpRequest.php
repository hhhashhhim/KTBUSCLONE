<?php

namespace App\Http\Requests\Mobile;

class VerifyPasswordResetOtpRequest extends MobileFormRequest
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
            'code' => ['required', 'digits:6'],
        ];
    }
}
