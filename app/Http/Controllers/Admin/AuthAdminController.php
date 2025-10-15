<?php

namespace App\Http\Controllers\Admin;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\LoginRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthAdminController extends Controller
{
    public function __construct()
    {
        // Only these routes can be accessed without authentication
        $this->middleware('auth:admin')->except(['login']);
    }

    /**
     * Admin Login
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            if (!$token = Auth::guard('admin')->attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid email or password'
                ], 401);
            }

            $admin = Auth::guard('admin')->user();

            if ($admin->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'error' => 'Your account is deactivated.'
                ], 403);
            }

            return $this->respondWithToken($token, $admin);
        } catch(Throwable $e){
            return request()->json([
                'status' => false,
                'message' => 'Login failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout (invalidate token)
     */
    public function logout()
    {
        try {
            Auth::guard('admin')->logout();

            return response()->json([
                'status' => true,
                'message' => 'Successfully logged out'
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token has already expired'
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
    }
    /**
     * Verify JWT token
     */
    public function verifyToken()
    {
        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired token.'
                ], 401);
            }

            return response()->json([
                'status' => false,
                'message' => 'Token is valid',
                'admin'   => $admin
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format JWT response
     */
    protected function respondWithToken($token, $admin)
    {
        try {
            return response()->json([
                'status' => true,
                'message'     => 'Login successful',
                'admin'       => $admin,
                'accessToken' => $token,
                'token_type'  => 'bearer',
                'expires_in'  => Auth::guard('admin')->factory()->getTTL() * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Access token failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
