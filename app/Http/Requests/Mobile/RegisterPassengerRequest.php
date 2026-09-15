<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Validation\Rule;

class RegisterPassengerRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'mobile' => preg_replace('/\D+/', '', (string) $this->mobile),
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
                'min:10',
                'max:15',
                Rule::unique('passenger_accounts', 'mobile')->where(function ($query) {
                    return $query->where('company_id', config('mobile.company_id'));
                }),
            ],
            'email' => ['nullable', 'email', 'max:190'],
            'cnic' => ['required', 'digits:13'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
