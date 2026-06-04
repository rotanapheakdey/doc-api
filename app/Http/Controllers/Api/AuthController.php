<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class AuthController extends Controller
{
    //
    public function login( Request $request){
        $request->validate([
            'email'=>'required|email',
            'password'=>'required'
        ]);


        if(!Auth::attempt($request->only('email', 'password'))){
            return response()->json([
                'message' => 'invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' =>[
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'department_id'=>$user->department_id,
            ]
        ]);
    }

    public function logout( Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'successfully logged out'
        ]);
    }

}
