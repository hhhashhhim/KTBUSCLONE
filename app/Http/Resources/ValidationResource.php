<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ValidationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toResponse($request)
    {
        // this will be used for validation
        return response([
            "status" => 'error',
            "message" => "Validation failed",
            'data' => null,
            'error' => $this->resource,
        ],422);
    }
}
