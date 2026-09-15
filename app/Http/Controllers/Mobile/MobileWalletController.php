<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Services\Mobile\MobileLoyaltyService;
use Illuminate\Http\Request;

class MobileWalletController extends Controller
{
    use RespondsWithMobileApi;

    public function show(Request $request, MobileLoyaltyService $loyalty)
    {
        return $this->success(
            $loyalty->wallet($request->user()),
            'Loyalty wallet retrieved.'
        );
    }
}
