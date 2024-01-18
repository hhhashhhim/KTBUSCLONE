<?php

namespace App\Http\Controllers;

use App\Models\Bus\BusClass;
use App\Models\Customer;
use App\Models\Expense\TicketMergeExpense;
use App\Models\ReportHeaderLink;
use App\Models\Schedule\TicketClosingMerge;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ValidationResource;
use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(Request $request)
    {
//        if (!Auth::check() && $request->path() != "login") {
//            return redirect('/login');
//        }
//        if (Auth::check() && $request->path() == "login") {
//            return redirect('/');
//        }
        return view('admin.index');
    }


    public function checkForPermission($user, $request)
    {
        $permission = collect($user->role
            ->permissions);
        return $permission->where('name', $request->path())
            ->where('read', true)
            ->first();
    }
    
    public function updatePassword(Request $request)
    {
        $request->validate([
            "currentPassword" => 'required',
            "newPassword" => 'required',
            "confirmPassword" => 'required|same:newPassword',
        ]);

        if(!Hash::check($request->currentPassword, Auth::user()->password))
        {
            return response()->json(["errors" => ["Error" => ['Current Password Not Matched']]], 422);
        }

        User::where("id",Auth::user()->id)->update([
            "password" => Hash::make($request->newPassword)
        ]);
        ActivityLog::create([
            "activity_by" => Auth::user()->id,
            "message" => Auth::user()->name." | updated password",
            "requested_host" => $request->ip(),
            "company_id" => Auth::user()->company_id
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // Revoke the user's personal access token
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
            
            // Log the logout activity
            ActivityLog::create([
                "activity_by" => $user->id,
                "message" => $user->name . " | logout",
                "requested_host" => $request->ip(),
                "company_id" => $user->company_id
            ]);
        }
        // Logout the user
        Auth::guard('web')->logout();
        
        return response(['message' => 'Successfully logged out']);
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
    
        // if validation fails
        if ($validator->fails())
        {
            return new ValidationResource($validator->errors());
        }

        $user= User::where(['email'=> $request->email,"hide"=>0])->with("role")->first(["id","name","email","contact","password","is_super_admin","role_id","company_id"]);
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => ['These credentials do not match our records.']
            ], 404);
        }
        
        $token = $user->createToken('my-app-token')->plainTextToken;
        
        ActivityLog::create([
            "activity_by" => $user->id,
            "message" => $user->name." | login",
            "requested_host" => $request->ip(),
            "company_id" => $user->company_id
        ]);

        $response = [
            'user' => $user,
            'token' => $token
        ];
    
        return response($response, 201);
    }

    public function doubleCheck(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);
        if (Hash::check($request->password, auth()->user()->password)) {
            return response()->json([], 200);
        } else {
            return response()->json([], 403);
        }
    }
}
