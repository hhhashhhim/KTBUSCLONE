<?php

namespace App\Http\Requests\Mobile;

use App\Models\PassengerAccount;

class DeletePassengerAccountRequest extends MobileFormRequest
{
    public function authorize()
    {
        return $this->user() instanceof PassengerAccount
            && (int) $this->user()->company_id === (int) config('mobile.company_id');
    }

    public function rules()
    {
        return [
            'password' => ['required', 'string', 'max:1024'],
            'confirmation' => ['required', 'in:DELETE'],
        ];
    }
}
