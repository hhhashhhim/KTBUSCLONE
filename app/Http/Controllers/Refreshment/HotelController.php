<?php

namespace App\Http\Controllers\Refreshment;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Department\Department;
use App\Models\Maintenance\MaintenancePart;
use App\Models\Maintenance\MaintenancePartLink;
use App\Models\Refreshment\Hotel;
use App\Models\Bus\Bus;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class HotelController extends Controller
{
    public function index()
    {
        if(!checkForSubmenu("hotels"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        $checkHotelLogin = Hotel::where("user_id",Auth::user()->id)->where("company_id",Auth::user()->company_id)->first();
        if($checkHotelLogin)
        {
            return Hotel::with("user")->where("company_id",Auth::user()->company_id)->where("user_id",Auth::user()->id)->get();
        }
        else
        {
            return Hotel::with("user")->where("company_id",Auth::user()->company_id)->get();
        }
    }

    public function store(Request $request)
    {
        if(!checkPermissionButtons("add-hotel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "name" => 'required',
                    // unique:table,column,except,idColumn,anotherColumn,anotherColumnValue
                    "hotelName" => 'required|unique:hotels,name,Null,id,company_id,'.Auth::user()->company_id,
                    "email" => 'required|email|unique:users',
                    "role" => 'required',
                    "password" => 'required',
                    "contact" => 'required',
                    "commission" => 'required',
                    "location" => 'required',
                ]);

                $user = User::create([
                    "name" => $request->name,
                    "email" => $request->email,
                    "password" => Hash::make($request->password),
                    "contact" => plainContactAndCnic($request->contact),
                    "role_id" => $request->role??0,
                    'company_id' => Auth::user()->company_id,
                ]);

                $hotel = Hotel::create([
                    "user_id" => $user->id,
                    "name" => $request->hotelName,
                    "contact" => plainContactAndCnic($request->contact),
                    "logo" => $request->logo ? $this->image($request->logo) : null,
                    "location" => $request->location,
                    "commission" => $request->commission,
                    "balance" => $request->balance??0,
                    "company_id" => Auth::user()->company_id,
                    "added_by" => Auth::user()->id,
                ]);
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | added hotel ($request->hotelName)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
                return $hotel;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    public function update(Request $request)
    {
        if(!checkPermissionButtons("edit-hotel"))
        {
            return response()->json(["Error" => ['You are not authorized to access this url']], 403);
        }
        try {
                DB::beginTransaction();
                $request->validate([
                    "name" => 'required',
                    // unique:table,column,except,idColumn,anotherColumn,anotherColumnValue
                    "hotelName" => 'required|unique:hotels,name,'.$request->hotelId.',id,company_id,'.Auth::user()->company_id,
                    "email" => 'required|email|unique:users,email,'.$request->userId,
                    "contact" => 'required',
                    "commission" => 'required',
                    "location" => 'required',
                ]);

                User::where("id",$request->userId)->update([
                    "name" => $request->name,
                    "email" => $request->email,
                    "contact" => plainContactAndCnic($request->contact),
                ]);

                if($request->password)
                {
                    User::where("id",$request->userId)->update([
                        "password" => Hash::make($request->password),
                    ]);
                }

                Hotel::where("id",$request->hotelId)->update([
                    "name" => $request->hotelName,
                    "contact" => plainContactAndCnic($request->contact),
                    "location" => $request->location,
                    "commission" => $request->commission,
                    "balance" => $request->balance??0,
                    "company_id" => Auth::user()->company_id,
                    "added_by" => Auth::user()->id,
                ]);

                if($request->logo)
                {
                    Hotel::where("id",$request->hotelId)->update([
                        "logo" => $request->logo ? $this->image($request->logo) : null,
                    ]);
                }
                ActivityLog::create([
                    "activity_by" => Auth::user()->id,
                    "message" => Auth::user()->name." | updated hotel ($request->hotelName)",
                    "requested_host" => $request->ip(),
                    "company_id" => Auth::user()->company_id
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database transaction error: ' . $e->getMessage());
                return response()->json(["errors" => ["Error" => ['An error occurred during the database transaction.']]], 422);
            }
    }

    // Image Upload
    public function image($image){

        $filenameWithExt = $image->getClientOriginalName();
        //get just filename
        $filename        = pathinfo($filenameWithExt);
        //get just extension
        $extension       = $image->extension();
        $nameToStore     = $filename['filename'] . "_" . time() . "." . $extension;
        //Move to folder
        $path            = $image->move(public_path('uploads/refreshment/hotel'), $nameToStore);
        return $nameToStore;
    }

}
