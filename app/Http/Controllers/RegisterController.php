<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Mail\VerifyEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

        ]);

        if ($validator->fails()) {
            return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        }
        $user = User::create([
            
            'email'         => $request->all()['email'],
            'password'      => Hash::make($request->all()['password']),
        ]);

        if ($user) {

            $verify2 =  DB::table('password_resets')->where([
                ['email', $request->all()['email']]
            ]);

            if ($verify2->exists()) {
                $verify2->delete();
            }

            $pin = rand(100000, 999999);
            $password_reset = DB::table('password_resets')->insert([
                'email' => $request->all()['email'],
                'token' =>  $pin

            ]);
        }

        $verifyEmail = Mail::to($request->email)->send(new VerifyEmail($pin));
        $token = $user->createToken('myapptoken')->plainTextToken;
        return new JsonResponse(['success' => true, 'message' => 'Successful created user. 
        Please check your email for a 6-digit pin to verify your email.', 'token' => $token], 201);
    }

     public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'token' => ['required'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        }
        $user = User::where('email', $request->email);
        $select = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token);
        if ($select->get()->isEmpty()) {
            return new JsonResponse(['success' => false, 'message' => "Invalid token"], 401);
        }
       
        $difference = Carbon::now()->diffInSeconds($select->first()->created_at);
        if ($difference > 3600) {
            return new JsonResponse(['success' => false, 'message' => "Token Expired"], 400);
        }

        $select = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->delete();

        $user->update([
            'email_verified_at' => Carbon::now()
        ]);

        return new JsonResponse(['success' => true, 'message' => "Email is verified"], 200);
    }
    public function resendPin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        }


        $verify2 =  DB::table('password_resets')->where([
            ['email', $request->all()['email']]
        ]);

        if ($verify2->exists()) {
            $verify2->delete();
        }

        $token = random_int(100000, 999999);
        $password_reset = DB::table('password_resets')->insert([
            'email' => $request->all()['email'],
            'token' =>  $token,
            'created_at' => Carbon::now()

        ]);

        if ($password_reset) {
            $sendMail = Mail::to($request->all()['email'])->send(new VerifyEmail($token));

            return new JsonResponse(['success' => true, 'message' => "A verification mail has been resent"], 200);
        }
    }
}
