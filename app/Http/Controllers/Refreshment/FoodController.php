<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenancePartLink;
use App\Models\Refreshment\Hotel;
use App\Models\Bus\Bus;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
class FoodController extends Controller
{
    public $company_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->company_id = Auth::user()->company_id;
            return $next($request);
        });
    }
    
    public function index(Request $request)
    {
        return $request->all();
        return Hotel::with("user")->where("company_id",$this->company_id)->get();
    }

    
    
}
