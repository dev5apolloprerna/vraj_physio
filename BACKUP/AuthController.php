<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;


use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:employee', ['except' => ['login']]);
    // }

      public function login(Request $request)
    {
       try {

            $credentials = $request->only('mobile_number','password');

            if (Auth::guard('api')->attempt($credentials)) 
            {
                $AuthCustomer = Auth::guard('api')->user();
    
                    $update = User::where('mobile_number', $request->mobile_number)
                        ->update([
                            'GUID' => Str::uuid(),
                        ]);
                        

                $token = JWTAuth::fromUser($AuthCustomer);
                $User = User::where(['status' => 1, 'mobile_number' => $request->mobile_number])->first();
               
                if (!empty($AuthCustomer) && !empty($User)) 
                {
                     if (!$token) 
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Unauthorized',
                        ], 401);
                    }if($User->device_token == null)
                    {
                            $updateToken=DB::table('users')
                            ->where(['id' => $User->id])
                            ->update([
                                'device_token' => $request->device_token,
                            ]);
                            
                          
                    }
                    else if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }
                    if($User->device_token == null)
                    {
                          $device_token=$request->device_token;
                    }else
                    {
                     $device_token=$User->device_token;
   
                    }
                     
                    $userdata = array(
                        "user_id" => $User->id,
                        "name" => $User->name,
                        "email" => $User->email,
                        "mobile_number" => $User->mobile_number,
                        "role_id" => $User->role_id,
                        "clinic_id" =>1,
                        "GUID" => $User->GUID,
                        "device_token" =>$device_token,

                    );

                    return response()->json([
                        "ErrorCode" => "0",
                        'Status' => 'Success',
                        'Message' => 'Login Successfully',
                        'Reseller' => $userdata,
                        'key' => env('RAZORPAY_KEY'),
                        'salt' => env('RAZORPAY_SECRET'),
                        'authorisation' => [
                                'token' => $token,
                                'type' => 'bearer',
                            ]
                    ]);
                } else {
                    return response()->json([
                        "ErrorCode" => "1",
                        'Status' => 'Failed',
                        'Message' => 'User Not Found.',
                    ], 401);
                }
            } else {

                return response()->json([
                    "ErrorCode" => "1",
                    'Status' => 'Failed',
                    'Message' => 'Invalid Login Id Or Password',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function logout()
    {
        auth()->guard('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function change_password(Request $request)
    {

        try
        {
            $request->validate([
                    'new_password' => 'required',
                    'confirm_password' => 'required'
            ]);

            if(auth()->guard('api')->user())
            {
                $User = auth()->guard('api')->user();
                 
                    if($request->device_token != $User->device_token)
                    {
                        return response()->json([
                            "ErrorCode" => "1",
                            'Status' => 'Failed',
                            'Message' => 'Device Token Not Match',
                        ], 401);
                    }

                    $newpassword = $request->new_password;
                    $confirmpassword = $request->confirm_password;

                
                        if($newpassword == $confirmpassword) 
                        {
                            $emp = DB::table('users')
                                ->where(['status' => 1, 'id' => $User->id])
                                ->update([
                                    'password' => Hash::make($confirmpassword),
                                ]);
                                return response()->json([
                                        'status' => 'success',
                                        'message' => 'Password Updated Successfully'
                                    ]);

                        }else 
                        {
                            return response()->json([
                                    'status' => 'error',
                                    'message' => 'password and confirm password does not match',
                                ], 401);
                        }

                    
            }else{
                return response()->json([
                        'status' => 'error',
                        'message' => 'User is not Authorised.',
                ], 401);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }

    }
   
}