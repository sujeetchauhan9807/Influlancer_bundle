<?php

namespace App\Http\Controllers\Admin;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Requests\LoginRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

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
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('admin')->attempt($credentials)) {
            return response()->json([
                'error' => 'Invalid email or password'
            ], 401);
        }

        $admin = Auth::guard('admin')->user();

        if ($admin->status !== 'active') {
            return response()->json([
                'error' => 'Your account is deactivated.'
            ], 403);
        }

        return $this->respondWithToken($token, $admin);
    }

    /**
     * Logout (invalidate token)
     */
    public function logout()
    {
        try {
            Auth::guard('admin')->logout();

            return response()->json([
                'message' => 'Successfully logged out'
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token has already expired'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token is invalid'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token is missing or could not be parsed'
            ], 400);
        }
    }
    /**
     * Verify JWT token
     */
    public function verifyToken()
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'message' => 'Invalid or expired token.'
            ], 401);
        }

        return response()->json([
            'message' => 'Token is valid',
            'admin'   => $admin
        ], 200);
    }

    /**
     * Format JWT response
     */
    protected function respondWithToken($token, $admin)
    {
        return response()->json([
            'message'     => 'Login successful',
            'admin'       => $admin,
            'accessToken' => $token,
            'token_type'  => 'bearer',
            'expires_in'  => Auth::guard('admin')->factory()->getTTL() * 60,
        ]);
    }
}
