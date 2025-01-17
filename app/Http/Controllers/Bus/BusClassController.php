<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Models\Bus\BusClass;
use App\Models\FareClass;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusClassController extends Controller
{

//    public $company_id;
//
//    public function __construct()
//    {
//        $this->middleware(function ($request, $next) {
//            Auth::user()->company_id = Auth::user()->company_id;
//            return $next($request);
//        });
//    }

    protected function index()
    {
        if(!checkForSubmenu("bus-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return BusClass::with('addedBy')->orderBy('id')->where(['company_id'=> Auth::user()->company_id,"hide" => 0])->get();
    }

    public function storeBusClass(Request $request)
    {
        if(!checkPermissionButtons("add-bus-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'BusClassName' => ['required', Rule::unique('bus_classes', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
        //            'BusClassColor' => 'required',
                    'noOfRows' => 'required|integer',
                    'noOfCols' => 'required|integer',
                ];

                $customMessages = [
                    'BusClassName.required' => 'Bus Class Name is Required!',
        //            'BusClassColor.required' => 'Bus Class Color is Required!',
                    'name.unique' => 'Bus Class Name is already available!',
                    'noOfRows.required' => 'No of Rows of Bus  is Required!',
                    'noOfCols.required' => 'No of Cols of Bus  is Required!',
                ];
                $this->validate($request, $rules, $customMessages);
                $busClass =  BusClass::create([
                    'name' => $request->BusClassName,
                    'color' => !$request->BusClassColor ? '#000000' : $request->BusClassColor,
                    'is_active' => !$request->isActive ? 1 : $request->isActive,
                    'seat_map' => $request->seatMap,
                    'no_of_rows' => $request->noOfRows,
                    'no_of_cols' => $request->noOfCols,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | stored bus class $request->BusClassName ($request->noOfRows by $request->noOfCols)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $busClass;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function updateBusClass(Request $request)
    {
        if(!checkPermissionButtons("edit-bus-Class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $busClass = BusClass::where('id', $request->id)->update([
                    'name' => $request->name,
                    'color' => $request->busClassColor,
                    'front_icons' => $request->front_icons,
                    'seat_map' => $request->seat_map,
                    'no_of_rows' => $request->no_of_rows,
                    'no_of_cols' => $request->no_of_cols,
                    'is_active' => $request->is_active,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated bus class $request->name ($request->no_of_rows by $request->no_of_cols)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $busClass;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function hideBusClass(Request $request)
    {
        if(!checkPermissionButtons("delete-bus-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $busClass = BusClass::find($request->id);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | deleted bus class $busClass->name ($busClass->no_of_rows by $busClass->no_of_cols)",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
        return $busClass->update([
            "hide" => 1
        ]);
    }

    public function duplicateBusClass(Request $request)
    {
        if(!checkPermissionButtons("duplicate-bus-Class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $busClass = BusClass::where('company_id', Auth::user()->company_id)->where('id', $request->id)->first();
                $busClass->name = $busClass->name .'-' .'Duplicate';
                $busClass->time = now();
                $new = $busClass->replicate();
                $new->created_at  = now();
                $new->save();
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | duplicated bus class $busClass->name",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $new;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
    public function fareClasses(){
        if(!checkForSubmenu("bus-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        return FareClass::with('addedBy')
        ->where('company_id', Auth::user()->company_id)->orderBy('id')
        ->get();
    }
    public function saveFareClass(Request $request)
    {
        if(!checkPermissionButtons("add-class"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $rules = [
                    'FareClassName' => ['required', Rule::unique('fare_classes', 'name')->where('company_id', Auth::user()->company_id)->whereNull('deleted_at')],
                ];

                $customMessages = [
                    'FareClassName.required' => 'Fare Class Name is Required!',
                    'FareClassName.unique' => 'This Fare Class Name is Already Exist!',
                ];
                $this->validate($request, $rules, $customMessages);
                $fareClass =  FareClass::create([
                    'name' => $request->FareClassName,
                    'is_active' => 1,
                    'company_id' => Auth::user()->company_id,
                    'added_by' => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added fare class $request->FareClassName",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $fareClass;
            
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }
}
