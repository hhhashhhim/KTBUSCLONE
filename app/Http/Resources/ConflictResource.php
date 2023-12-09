<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConflictResource extends JsonResource
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
            "status" => 'conflict',
            "message" => "processing faild due to some wrong entry",
            'data' => null,
            'error' => $this->resource,
        ],409);
    }
}
