<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class PassengerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => optional($this->customer)->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'cnic' => optional($this->customer)->cnic,
            'gender' => $this->gender,
            'mobile_verified' => !is_null($this->mobile_verified_at),
        ];
    }
}
