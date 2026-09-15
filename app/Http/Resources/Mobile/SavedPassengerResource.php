<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class SavedPassengerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'cnic' => $this->cnic,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
        ];
    }
}
