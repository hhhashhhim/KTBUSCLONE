<?php

namespace App\Http\Requests\Mobile;

class UpdatePassengerProfileRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('cnic')) {
            $this->merge(['cnic' => preg_replace('/\D+/', '', (string) $this->cnic)]);
        }
    }

    public function rules()
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'cnic' => ['sometimes', 'required', 'digits:13'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,other'],
        ];
    }
}
