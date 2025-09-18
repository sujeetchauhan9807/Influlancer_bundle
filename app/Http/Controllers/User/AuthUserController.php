<?php

namespace App\Http\Controllers\User;

use App\Services\PHPMailService;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\loginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;

class AuthUserController extends Controller
{
    public function __construct()
    {
        // Apply JWT auth middleware, but allow these routes without auth
        $this->middleware('auth:api')->except(['login', 'userRegister', 'forgotPassword', 'resetPassword','verifyEmail']);
    }

    /**
     * User Register
    */

    public function userRegister(RegisterRequest $request)
    {

        $newFileName = null;

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $extension = $file->getClientOriginalExtension();

            $newFileName = date('YmdHis') . time() . '.' . $extension;

            $file->move(public_path('user/profile_uploads'), $newFileName);
        }
        $verificationToken = Str::random(60);
        $user = User::create([
            'name'          => ucwords(strtolower($request->name)),
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => strtolower($request->role),
            'phone'         => $request->phone, 
            'profile_image' => $newFileName,
            'status'        => 'active',
            'verification_token'   => $verificationToken,
        ]);

        $verificationUrl = url('/api/user/verify-email?token=' . $verificationToken);

        Mail::to($user->email)->queue(new VerifyEmailMail($verificationUrl));

        $token = Auth::guard('api')->login($user);
        return $this->respondWithToken($token, $user);
         
    }

    /**
     * User verifyEmail
     */

    public function verifyEmail($token)
    {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired verification token.'
            ], 400);
        }

        // Expiry check (10 minutes)
        if (Carbon::parse($user->created_at)->addMinutes(10)->isPast()) {
            return response()->json([
                'message' => 'Token has expired'
            ], 400);
        }

        $result = $user->update([
            'email_verified_at' => Carbon::now(),
            'verification_token' => null
        ]);

       if(!$result){
          return response()->json([
            'message' => 'Email verification Failed. Please try again.'
          ],500);
        }
        
        return response()->json([
            'message' => 'Email verified successfully. You can now log in.'
        ],200);
    }


    /**
     * User Login
     */
    public function login(loginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('api')->user();

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'error' => 'Your account is deactivated.'
            ], 403);
        }
        return $this->respondWithToken($token, $user);
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out'
        ],200);
    }

    /**
     * Verify JWT token
     */
    public function verifyToken(Request $request)
    {
        try {
            // This will automatically validate token from Authorization: Bearer header
            $user = Auth::guard('api')->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Invalid or expired token.'
                ], 401);
            }

            return response()->json([
                'message' => 'Token is valid',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token validation failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Return formatted JWT response
     */

    protected function respondWithToken($token, $user = null)
    {
        return response()->json([
            'message'     => 'Success',
            'user'        => $user,
            'accessToken' => $token,
            'token_type'  => 'bearer',
            'expires_in'  => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }

    /**
     * Send reset token on email
     */

    public function forgotPassword(ForgotPasswordRequest $request, PHPMailService $mailService)
    {
       $user = User::where('email', $request->email)->first();
    
       if(!$user) {
         return response()->json([
            'message' => 'User Not found'
         ],404);
       }

       // Generate token and store
       $token = Str::random(60);

       DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        // Normally, send token via phpmailer email here
       
        $sent = $mailService->sendResetLink($user->email, $token);
        
        if ($sent !== true) {
            return response()->json([
                'message' => 'Could not send email', 
                'error' => $sent
            ], 500);
        }

        return response()->json([
            'message' => 'Reset token generated and sent to email.',
            'token'   => $token
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Invalid token or email'
            ], 400);
        }
        
        // Expiry check (10 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json([
                'message' => 'Token has expired'
            ], 400);
        }
        
        // Update user password
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete token after use
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password reset successful'
        ], 200);
        
    }
    
}
