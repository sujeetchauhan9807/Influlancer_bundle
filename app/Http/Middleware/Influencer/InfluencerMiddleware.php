<?php

namespace App\Http\Middleware\Influencer;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class InfluencerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Validate & authenticate the token
            $influencer = JWTAuth::parseToken()->authenticate();

            if (!$influencer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Influencer not found or token is invalid',
                ], 401);
            }
       
            // role check 
            if ($influencer->role !== 'influencer') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Only brand influencers allowed.',
                ], 403);
            }

            // Check if Influencer is active
            if ($influencer->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'error' => 'Your account is deactivated.'
                ], 403);
            }

            // Profile ownership check (for routes with {id})
            // $profileId = $request->route('id');
            // if ($profileId && $profileId != $influencer->id) {
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
        
        // Share influencer object for controller use
        $request->merge(['loggedInInfluencer' => $influencer]);
        return $next($request);
    }
}
