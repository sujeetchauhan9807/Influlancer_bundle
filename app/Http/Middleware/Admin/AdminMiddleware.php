<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Parse and authenticate token
            $admin = JWTAuth::parseToken()->authenticate();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Admins only.'
                ], 403);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token has expired'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is invalid'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is missing or could not be parsed'
            ], 400);
        }
        $request->merge(['loggedInAdmin' => $admin]);
        return $next($request);
    }
}
