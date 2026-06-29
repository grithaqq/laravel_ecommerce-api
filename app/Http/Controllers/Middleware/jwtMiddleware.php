<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\ApiFormatter;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return response()->json(
                ApiFormatter::createJson(401, 'Authorization header not provided'),
                401
            );
        }

        try {

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(
                    ApiFormatter::createJson(401, 'Unauthorized'),
                    401
                );
            }

        } catch (TokenExpiredException $e) {

            return response()->json(
                ApiFormatter::createJson(401, 'Token has expired'),
                401
            );

        } catch (TokenInvalidException $e) {

            return response()->json(
                ApiFormatter::createJson(401, 'Token is invalid'),
                401
            );

        } catch (TokenBlacklistedException $e) {

            return response()->json(
                ApiFormatter::createJson(401, 'Token has been blacklisted'),
                401
            );

        } catch (JWTException $e) {

            return response()->json(
                ApiFormatter::createJson(401, 'Token could not be parsed'),
                401
            );
        }

        return $next($request);
    }


}
