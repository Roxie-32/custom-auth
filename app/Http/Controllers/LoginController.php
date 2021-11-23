<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request){
        $validator = Validator::make($request->all(), [

            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],

        ]);

        if ($validator->fails()) {
            return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->all()['email'])->first();


        //Check Password
        if (!$user || !Hash::check($request->all()['password'], $user->password)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid Credentials'], 400);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;
        return new JsonResponse(['success' => true, 'token' => $token], 200);
    }

     public function logout(Request $request){
        auth()->user()->tokens()->delete();

        return new JsonResponse(['success' => true, 'message' =>'Logged Out Successfully'], 200);

    }
}
