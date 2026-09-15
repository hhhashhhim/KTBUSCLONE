<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Models\MobileFleetMedia;
use App\Services\Mobile\MobileTravelService;

class MobileFleetController extends Controller
{
    use RespondsWithMobileApi;

    public function index(MobileTravelService $travel)
    {
        $items = MobileFleetMedia::query()
            ->with(['busClass:id,name,is_active,hide'])
            ->where('company_id', $travel->companyId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(function (MobileFleetMedia $item) {
                return !$item->bus_class_id
                    || ($item->busClass && $item->busClass->is_active && !$item->busClass->hide);
            })
            ->values()
            ->map(function (MobileFleetMedia $item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'media_type' => $item->media_type,
                    'image_url' => $this->imageUrl($item->image_url),
                    'bus_class' => $item->busClass ? [
                        'id' => $item->busClass->id,
                        'name' => $item->busClass->name,
                    ] : null,
                ];
            });

        return $this->success($items, 'Fleet gallery retrieved.');
    }

    private function imageUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/' . ltrim($path, '/'));
    }
}
