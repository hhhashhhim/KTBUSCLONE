<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class PassengerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->mobile_verified_at ? optional($this->customer)->name : null,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'cnic' => $this->mobile_verified_at ? optional($this->customer)->cnic : null,
            'gender' => $this->gender,
            'mobile_verified' => !is_null($this->mobile_verified_at),
        ];
    }
}
