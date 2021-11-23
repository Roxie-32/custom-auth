<?php

namespace App\Http\Controllers;

use App\Mail\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        }


        $verify = User::where('email', $request->all()['email'])->exists();

        if ($verify) {

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
                $sendMail = Mail::to($request->all()['email'])->send(new ResetPassword($token));

                return new JsonResponse(['success' => true, 'message' => "Please check your email for a 6 digit pin"], 200);
            }
        } else {
            return new JsonResponse(['success' => false, 'message' => "This email does not exist"], 400);
        }
    }
    public function verifyPin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required'],

        ]);

        if ($validator->fails()) {
            return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        }

        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['token', $request->all()['token']],
        ]);

        if ($check->exists()) {

            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 3600) {
                return new JsonResponse(['success' => false, 'message' => "Token Expired"], 400);
            }

            $delete = DB::table('password_resets')->where([
                ['email', $request->all()['email']],
                ['token', $request->all()['token']],
            ])->delete();
            return new JsonResponse(['success' => true, 'message' => "You can now reset your password"], 200);
        } else {
            return new JsonResponse(['success' => false, 'message' => "Invalid token"], 401);
        }
    }

}
