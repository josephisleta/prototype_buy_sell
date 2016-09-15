<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;


class CheckUser
{
    public $attributes;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (TokenExpiredException $e) {
            $data = [
                'success' => false,
                'error' => ['Token has expired. Please login.']
            ];

            return response()->json($data, $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            $data = [
                'success' => false,
                'error' => ['Token is invalid.']
            ];

            return response()->json($data, $e->getStatusCode());
        } catch (JWTException $e) {
            $data = [
                'success' => false,
                'error' => ['Token is absent.']
            ];

            return response()->json($data, $e->getStatusCode());
        }
        
        return $next($request);
    }
}
