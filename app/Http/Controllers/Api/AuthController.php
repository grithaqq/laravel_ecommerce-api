<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiFormatter;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiFormatter::createJson(400, 'Bad Request', $validator->errors()->all());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return ApiFormatter::createJson(401, 'Unauthorized', 'Email or Password wrong');
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return ApiFormatter::createJson(200, 'Success', auth('api')->user());
    }

    public function logout()
    {
        auth('api')->logout();
        return ApiFormatter::createJson(200, 'Success', 'Successfully logged out');
    }

    protected function respondWithToken($token)
    {
        return ApiFormatter::createJson(200, 'Success', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => auth('api')->user()
        ]);
    }
}
