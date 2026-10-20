<?php

namespace App\Http\Requests\Mobile;

use App\Services\Mobile\MobilePassengerControls;

class RegisterPassengerRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'mobile' => MobilePassengerControls::mobile((string) $this->mobile),
            'cnic' => preg_replace('/\D+/', '', (string) $this->cnic),
        ]);
    }

    public function rules()
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'mobile' => [
                'required',
                'string',
                'regex:/^923[0-9]{9}$/',
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'cnic' => ['required', 'digits:13'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
