<?php

namespace App\Http\Controllers\api\v1\auth;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\SMS_module;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;

class SocialAuthController extends Controller
{
    public function social_register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'unique_id' => 'required',
            'email' => 'required|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'medium' => 'required|in:google,facebook',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client = new Client();
        $token = $request['token'];
        $email = $request['email'];
        $unique_id = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
            } elseif ($request['medium'] == 'facebook') {
                $res = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email');
                $data = json_decode($res->getBody()->getContents(), true);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'wrong credential.','message'=>$e->getMessage()],403);
        }
        if (strcmp($email, $data['email']) === 0) {
            $name = explode(' ', $data['name']);
            if (count($name) > 1) {
                $fast_name = implode(" ", array_slice($name, 0, -1));
                $last_name = end($name);
            } else {
                $fast_name = implode(" ", $name);
                $last_name = '';
            }
            $user = User::where('email', $email)->first();
            if (isset($user) == false) {
                if(!isset($data['id']) && !isset($data['kid'])){
                    return response()->json(['error' => 'wrong credential.'],403);
                }
                $pk = isset($data['id'])?$data['id']:$data['kid'];
                $user = User::create([
                    'f_name' => $fast_name,
                    'l_name' => $last_name,
                    'email' => $email,
                    'phone' => $request->phone,
                    'password' => bcrypt($pk),
                    'login_medium' => $request['medium'],
                    'social_id' => $pk,
                ]);
            } else {
                return response()->json([
                    'errors' => [
                        ['code' => 'auth-004', 'message' => translate('messages.email_already_exists')]
                    ]
                ], 403);
            }

            $data = [
                'phone' => $user->phone,
                'password' => $user->social_id
            ];
            $customer_verification = BusinessSetting::where('key','customer_verification')->first()->value;
            if (auth()->attempt($data)) {
                $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
                if(!auth()->user()->status)
                {
                    $errors = [];
                    array_push($errors, ['code' => 'auth-003', 'message' => translate('messages.your_account_is_blocked')]);
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }
                if($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo')
                {
                    $otp = rand(1000, 9999);
                    DB::table('phone_verifications')->updateOrInsert(['phone' => $request['phone']],
                        [
                        'token' => $otp,
                        'created_at' => now(),
                        'updated_at' => now(),
                        ]);
                    $response = SMS_module::send($request['phone'],$otp);
                    if($response != 'success')
                    {

                        $errors = [];
                        array_push($errors, ['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]);
                        return response()->json([
                            'errors' => $errors
                        ], 403);
                    }
                }
                return response()->json(['token' => $token, 'is_phone_verified'=>auth()->user()->is_phone_verified], 200);
            } else {
                $errors = [];
                array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthorized.']);
                return response()->json([
                    'errors' => $errors
                ], 401);
            }


        }

        return response()->json(['error' => translate('messages.email_does_not_match')]);
    }


    public function social_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'unique_id' => 'required',
            'email' => 'required|exists:users,email',
            'medium' => 'required|in:google,facebook',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client = new Client();
        $token = $request['token'];
        $email = $request['email'];
        $unique_id = $request['unique_id'];
        try {
            if ($request['medium'] == 'google') {
                $res = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
            } elseif ($request['medium'] == 'facebook') {
                $res = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email');
                $data = json_decode($res->getBody()->getContents(), true);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'wrong credential.','message'=>$e->getMessage()],403);
        }
        if (strcmp($email, $data['email']) != 0 || (!isset($data['id']) && !isset($data['kid']))) {
            return response()->json(['error' => translate('messages.email_does_not_match')],403);
        }


        $user = User::where('email', $email)->first();

        if(isset($user) == false )
        {
            return response()->json(['token' => null, 'is_phone_verified'=>0], 200);
        }

        if($user->social_id == null )
        {
            return response()->json(['token' => null, 'is_phone_verified'=>0], 200);
        }

        $data = [
            'phone' => $user->phone,
            'password' => $user->social_id
        ];
        $customer_verification = BusinessSetting::where('key','customer_verification')->first()->value;
        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
            if(!auth()->user()->status)
            {
                $errors = [];
                array_push($errors, ['code' => 'auth-003', 'message' => translate('messages.your_account_is_blocked')]);
                return response()->json([
                    'errors' => $errors
                ], 403);
            }
            if($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo')
            {
                $otp = rand(1000, 9999);
                DB::table('phone_verifications')->updateOrInsert(['phone' => $user->phone],
                    [
                    'token' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                    ]);
                $response = SMS_module::send($user->phone,$otp);
                if($response != 'success')
                {

                    $errors = [];
                    array_push($errors, ['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]);
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }
            }
            return response()->json(['token' => $token, 'is_phone_verified'=>auth()->user()->is_phone_verified, 'phone'=>$user->phone], 200);
        } else {
            $errors = [];
            array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthorized.']);
            return response()->json([
                'errors' => $errors
            ], 401);
        }

        return response()->json([
            'errors'=>[
                ['code'=>'not-found','message' => translate('messages.user_not_found')]
            ]
        ], 404);
    }

}
