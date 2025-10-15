<?php

namespace App\Http\Controllers\User;

use App\Services\PHPMailService;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\loginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterUpdateRequest;
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
use Throwable;

class AuthUserController extends Controller
{
    public function __construct()
    {
        // Apply JWT auth middleware, but allow these routes without auth
        $this->middleware('auth:api')->except(['login', 'userRegister', 'forgotPassword', 'resetPassword', 'verifyEmail']);
    }

    /**
     * User Register
     */

    public function userRegister(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
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
                'role'          => 'brand',
                'phone'         => $request->phone,
                'profile_image' => $newFileName,
                'status'        => 'active',
                'verification_token'   => $verificationToken,
            ]);

            $verificationUrl = url('/api/user/verify-email?' . $verificationToken);

            Mail::to($user->email)->queue(new VerifyEmailMail($verificationUrl));

            DB::commit();

            $token = Auth::guard('api')->login($user);
            return $this->respondWithToken($token, $user);
        } catch (Throwable $e) {
            DB::rollBack();
            return request()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(RegisterUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::guard('api')->user();

            $updateUser = User::find($user->id);

            if (!$updateUser) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized user.',
                ], 401);
            }

            $newFileName = $updateUser->profile_image;
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $extension = $file->getClientOriginalExtension();
                $newFileName = date('YmdHis') . time() . '.' . $extension;
                $uploadPath = public_path('user/profile_uploads');
                $file->move($uploadPath, $newFileName);

                if (!empty($updateUser->profile_image)) {
                    $oldImagePath = $uploadPath . '/' . $updateUser->profile_image;
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
            }

            $emailVerifiedAt = $updateUser->email_verified_at;
            // email change check
            if ($request->has('email') && $request->email !== $updateUser->email) {
                $emailVerifiedAt = null; // reset verification 
                $verificationToken = Str::random(60);

                $verificationUrl = url('/api/user/verify-email?' . $verificationToken);
                Mail::to($request->email)->queue(new VerifyEmailMail($verificationUrl));
            }

            $updateUser->update([
                'name'              => ucwords(strtolower($request->name)),
                'email'             => $request->email,
                'phone'             => $request->phone,
                'profile_image'     => $newFileName,
                'email_verified_at' => $emailVerifiedAt,
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Profile updated successfully.',
                'data'    => $updateUser
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Profile update failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * User verifyEmail
     */

    public function verifyEmail($token)
    {
        try {
            $user = User::where('verification_token', $token)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired verification token.'
                ], 400);
            }

            // Expiry check (10 minutes)
            if (Carbon::parse($user->created_at)->addMinutes(10)->isPast()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token has expired'
                ], 400);
            }

            $result = $user->update([
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
                'status' => true,
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
     * User Login
     */
    public function login(loginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            if (!$token = Auth::guard('api')->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $user = Auth::guard('api')->user();

            // Check if user is active
            if ($user->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'error' => 'Your account is deactivated.'
                ], 403);
            }
            return $this->respondWithToken($token, $user);
        } catch (Throwable $e) {
            return request()->json([
                'status' => false,
                'message' => 'Login failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout()
    {
        try {
            Auth::guard('api')->logout();
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
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired token.'
                ], 401);
            }

            return response()->json([
                'status' => false,
                'message' => 'Token is valid',
                'user' => $user
            ]);
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

    protected function respondWithToken($token, $user = null)
    {
        try {
            return response()->json([
                'status'     => true,
                'message'     => 'Login successful',
                'user'        => $user,
                'accessToken' => $token,
                'token_type'  => 'bearer',
                'expires_in'  => Auth::guard('api')->factory()->getTTL() * 60,
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
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User Not found'
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

            $sent = $mailService->sendResetLink($user->email, $token);

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
                'status' => true,
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

            // Update user password
            User::where('email', $request->email)->update([
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
