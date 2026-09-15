<?php

namespace App\Http\Requests\Mobile;

class SavePassengerRequest extends MobileFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'cnic' => preg_replace('/\D+/', '', (string) $this->cnic),
            'mobile' => preg_replace('/\D+/', '', (string) $this->mobile),
        ]);
    }

    public function rules()
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'cnic' => ['required', 'digits:13'],
            'mobile' => ['required', 'string', 'min:10', 'max:15'],
            'gender' => ['required', 'in:male,female,other'],
        ];
    }
}
