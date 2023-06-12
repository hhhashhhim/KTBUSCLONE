<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toResponse($request)
    {
        // this will be used for if data found
        return response([
            "status" => 'success',
            "message" => "Data found",
            'data' => $this->resource,
            'error' => null,
        ],200);
    }
}
