<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmptyResource extends JsonResource
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
            "status" => 'empty',
            "message" => "Data not found",
            'data' => $this->resource,
            'error' => null,
        ],200);
    }
}
