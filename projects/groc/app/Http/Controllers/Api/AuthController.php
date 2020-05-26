<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:55',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        $validatedData['password'] = bcrypt($validatedData['password']);

        $user = User::create($validatedData);

        $accessToken = $user->createToken('authToken')->accessToken;

        return response(['user'=>$user,'access_token'=>$accessToken]);

    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if(!auth()->attempt($loginData)){
            return response(['error'=>'Invalid Credentials'],401);
        }

        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        return response(['user'=>auth()->user(),'access_token'=>$accessToken]);

    }

}
