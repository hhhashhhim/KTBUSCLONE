<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Models\MobileFleetMedia;
use Illuminate\Http\Request;
use App\Services\Mobile\MobileTravelService;

class MobileFleetController extends Controller
{
    use RespondsWithMobileApi;

    public function index(Request $request, MobileTravelService $travel)
    {
        $request->validate(['include_placements' => 'sometimes|boolean']);
        $includePlacements = $request->boolean('include_placements');
        $companyId = $travel->companyId();
        $items = MobileFleetMedia::query()
            ->with(['busClass:id,company_id,name,is_active,hide'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(function (MobileFleetMedia $item) use ($companyId) {
                return !$item->bus_class_id
                    || ($item->busClass && (int) $item->busClass->company_id === $companyId && $item->busClass->is_active && !$item->busClass->hide);
            })
            ->filter(function (MobileFleetMedia $item) use ($includePlacements) {
                // Old clients have one shared gallery. Keep banners/promotions out of it.
                return $includePlacements || count(array_intersect($this->placements($item), ['home_carousel', 'bus_guide'])) > 0;
            })
            ->values()
            ->map(function (MobileFleetMedia $item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'media_type' => $item->media_type,
                    'image_url' => $this->imageUrl($item->image_url),
                    'placements' => $this->placements($item),
                    'sort_order' => $item->sort_order,
                    'bus_class' => $item->busClass ? [
                        'id' => $item->busClass->id,
                        'name' => $item->busClass->name,
                    ] : null,
                ];
            });

        return $this->success($items, 'Fleet gallery retrieved.');
    }

    private function placements(MobileFleetMedia $item): array
    {
        $placements = $item->placements;
        if ($placements === null) { return ['home_carousel', 'bus_guide']; }
        return array_values(array_intersect(is_array($placements) ? $placements : [],
            ['home_banner', 'home_carousel', 'bus_guide', 'home_promotion']));
    }

    private function imageUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/' . ltrim($path, '/'));
    }
}
