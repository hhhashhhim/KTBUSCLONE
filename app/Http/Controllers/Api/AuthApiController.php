<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\ActivityLog;
use App\Http\Resources\ValidationResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    function login(Request $request)
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

        $user= User::where(['email'=> $request->email,"hide"=>0])->first(["id","name","email","contact","password","company_id"]);
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => ['These credentials do not match our records.']
            ], 404);
        }
    
        $token = $user->createToken('my-app-token')->plainTextToken;
        
        // ActivityLog::create([
        //     "activity_by" => $user->id,
        //     "message" => $user->name." | login",
        //     "requested_host" => $request->ip(),
        //     "company_id" => $user->company_id
        // ]);

        $response = [
            'user' => $user,
            'token' => $token
        ];
    
        return response($response, 201);
    }
}
