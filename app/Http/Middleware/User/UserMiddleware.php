<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Validate & authenticate the token
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found or token is invalid',
                ], 401);
            }

            // role check 
            if ($user->role !== 'brand') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Only brand users allowed.',
                ], 403);
            }

            // Check if User is active
            if ($user->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'error' => 'Your account is deactivated.'
                ], 403);
            }

            // Profile ownership check (for routes with {id})
            // $profileId = $request->route('id');
            // if ($profileId && $profileId != $user->id) {
            //     return response()->json([
            //         'message' => 'Unauthorized. You cannot access this profile.',
            //     ],403);
            // }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token has expired',
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is invalid',
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is missing',
            ], 400);
        }

        // Share user object for controller use
        $request->merge(['loggedInUser' => $user]);
        return $next($request);
    }
}
