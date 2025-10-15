<?php

namespace App\Http\Controllers\influencer;

use App\Services\PHPMailService;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\InfluencerRegisterRequest;
use App\Http\Requests\loginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\Influencer\Influencer;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AuthInfluencerController extends Controller
{
    public function __construct()
    {
        // Apply JWT auth middleware, but allow these routes without auth
        $this->middleware('auth:influencer')->except(['login', 'influencerRegister', 'forgotPassword', 'resetPassword', 'verifyEmail']);
    }

    /**
     * Influencer Register
     */

    public function influencerRegister(InfluencerRegisterRequest $request)
    {

        DB::beginTransaction();
        try {
            $newFileName = null;
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $extension = $file->getClientOriginalExtension();
                $newFileName = date('YmdHis') . time() . '.' . $extension;
                $file->move(public_path('influencer/profile_uploads'), $newFileName);
            }

            $verificationToken = Str::random(60);
            $influencer = Influencer::create([
                'name'          => ucwords(strtolower($request->name)),
                'email'         => $request->email,
                'password'      => Hash::make($request->password),
                'role'          => 'influencer',
                'phone'         => $request->phone,
                'bio'           => $request->bio,
                'profile_image' => $newFileName,
                'region'        => $request->region,
                'address'       => ucwords(strtolower($request->address)),
                'audience_size' => $request->audience_size,
                'engagement_rate' => $request->engagement_rate,
                'status'        => 'active',
                'verification_token'   => $verificationToken,
            ]);

            if ($request->has('categories') && is_array($request->categories)) {
                $influencer->categories()->sync($request->categories);
            }

            if ($request->has('platforms') && is_array($request->platforms)) {
                $influencer->platforms()->sync($request->platforms);
            }

            $verificationUrl = url('/api/influencer/verify-email?' . $verificationToken);

            Mail::to($influencer->email)->queue(new VerifyEmailMail($verificationUrl));

            DB::commit();

            $token = Auth::guard('influencer')->login($influencer);
            return $this->respondWithToken($token, $influencer);
        } catch (Throwable $e) {
            DB::rollBack();
            return request()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Influencer verifyEmail
     */

    public function verifyEmail($token)
    {
        try {
            $influencer = Influencer::where('verification_token', $token)->first();
            if (!$influencer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired verification token.'
                ], 400);
            }

            // Expiry check (10 minutes)
            if (Carbon::parse($influencer->created_at)->addMinutes(10)->isPast()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token has expired'
                ], 400);
            }

            $result = $influencer->update([
                'email_verified_at' => Carbon::now(),
                'verification_token' => null
            ]);

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email verification Failed. Please try again.'
                ], 500);
            }

            return response()->json([
                'status' => false,
                'message' => 'Email verified successfully. You can now log in.'
            ], 200);
        } catch (Throwable $e) {
            return request()->json([
                'status' => false,
                'message' => 'Verify failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Influencer Login
     */
    public function login(loginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            if (!$token = Auth::guard('influencer')->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $influencer = Auth::guard('influencer')->user();

            // Check if Influencer is active
            if ($influencer->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'error' => 'Your account is deactivated.'
                ], 403);
            }
            return $this->respondWithToken($token, $influencer);
        } catch (Throwable $e) {
            return request()->json([
                'status' => false,
                'message' => 'Login failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout Influencer (invalidate token)
     */
    public function logout()
    {
        try {
            Auth::guard('influencer')->logout();
            return response()->json([
                'status' => true,
                'message' => 'Successfully logged out'
            ], 200);
        } catch (Throwable $e) {
            return request()->json([
                'status' => false,
                'message' => 'logged out failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify JWT token
     */
    public function verifyToken(Request $request)
    {
        try {
            // This will automatically validate token from Authorization: Bearer header
            $influencer = Auth::guard('influencer')->user();

            if (!$influencer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired token.'
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => 'Token is valid',
                'Influencer' => $influencer
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return formatted JWT response
     */

    protected function respondWithToken($token, $influencer = null)
    {
        try {
            return response()->json([
                'message'     => 'Login successful',
                'Influencer'  => $influencer,
                'accessToken' => $token,
                'token_type'  => 'bearer',
                'expires_in'  => Auth::guard('influencer')->factory()->getTTL() * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Access token failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send reset token on email
     */

    public function forgotPassword(ForgotPasswordRequest $request, PHPMailService $mailService)
    {
        DB::beginTransaction();
        try {
            $influencer = Influencer::where('email', $request->email)->first();

            if (!$influencer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Influencer Not found'
                ], 404);
            }

            // Generate token and store
            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]
            );

            // Normally, send token via phpmailer email here

            $sent = $mailService->sendResetLink($influencer->email, $token);

            if ($sent !== true) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Could not send email',
                    'error' => $sent
                ], 500);
            }

            DB::commit();

            return response()->json([
                'status' => false,
                'message' => 'Reset token generated and sent to email.',
                'token'   => $token
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to process forgot password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$record) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token or email'
                ], 400);
            }

            // Expiry check (10 minutes)
            if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token has expired'
                ], 400);
            }

            // Update Influencer password
            Influencer::where('email', $request->email)->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete token after use
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Password reset successful'
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
