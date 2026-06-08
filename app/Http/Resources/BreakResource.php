<?php

namespace App\Http\Resources;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;

class BreakResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toResponse($request)
    {
        Log::error('Er->: ' . json_encode($this->resource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return response([
            "status" => 'break',
            "message" => "Internal Server Error",
            'data' => null,
            'error' => null,
        ],500);
    }
}
