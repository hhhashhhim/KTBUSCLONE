<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CreatedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toResponse($request)
    {
        return response([
            "status" => 'created',
            "message" => "Booked successfully",
            'data' => $this->resource,
            'error' => null,
        ],201);
    }
}
