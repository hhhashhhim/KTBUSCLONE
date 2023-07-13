<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

        $user= User::where(['email'=> $request->email,"hide"=>0])->first(["id","name","email","contact","password"]);
        // print_r($data);
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response([
                    'message' => ['These credentials do not match our records.']
                ], 404);
            }
        
            $token = $user->createToken('my-app-token')->plainTextToken;
            
            $response = [
                'user' => $user,
                'token' => $token
            ];
        
            return response($response, 201);
    }
}
