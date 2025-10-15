<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserStatusRequest;
use App\Http\Requests\UserEditRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Influencer\Influencer;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct()
    {
        // Only allow authenticated admins
        $this->middleware('auth:admin');
    }

    /**
     * Get all users (Only for admin)
     */
    public function index(Request $request)
    {
        try {
            // ensure current user is admin
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            $page = $request->input('page', 1); // default page = 1
            $perPage = $request->input('per_page', 10);  // Dynamic per_page (default 10)
            $result = User::paginate($perPage, ['*'], 'page', $page);

            // Check if page exists or not
            if ($result->isEmpty() && $page > 1) {
                return response()->json([
                    'message' => 'Page not found.'
                ], 404);
            }

            return response()->json([
                'message' => 'success',
                'user' => $result->items(),    // current page users
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'links' => $result->linkCollection(), // dots (...) included
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all Influencer (Only for admin)
     */
    public function influencer(Request $request)
    {
        try {
            // ensure current user is admin
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            $page = $request->input('page', 1); // default page = 1
            $perPage = $request->input('per_page', 10);  // Dynamic per_page (default 10)
            // Fetch influencers with categories ,platforms,influencerReviews
            $result = Influencer::with(['categories', 'platforms'])
                ->withCount('influencerReviews as total_reviews')
                ->withAvg('influencerReviews as average_rating', 'rating')
                ->paginate($perPage, ['*'], 'page', $page);


            // Check if page exists or not
            if ($result->isEmpty() && $page > 1) {
                return response()->json([
                    'message' => 'Page not found.'
                ], 404);
            }

            // Round average ratings and structure response
            $influencers = $result->getCollection()->transform(function ($influencer) {
                $influencer->average_rating = round($influencer->average_rating ?? 0, 1);
                return $influencer;
            });

            return response()->json([
                'message' => 'success',
                'influencer' => $influencers,    // current page users
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'links' => $result->linkCollection(), // dots (...) included
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Active and Deactive use status
     */

    public function UserStatus(UserStatusRequest $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->status = $request->status;
            $user->save();

            return response()->json([
                'message' => 'User status updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'status' => $user->status
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get user by ID (Edit)
     */
    public function edit($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'message' => 'success',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user details
     */

    public function update(UserUpdateRequest $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            // Check if email is changing
            if ($request->has('email') && $request->email !== $user->email) {
                $user->email_verified_at = null; // reset email verification
            }

            $result = $user->update([
                'name' => ucwords(strtolower($request->name)),
                'email' => $request->email,
                'role' => strtolower($request->role),
                'phone' => $request->phone,
            ]);

            if (!$result) {
                return response()->json([
                    'message' => 'User updated failed',
                    'data' => $result
                ], 500);
            }

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user details
     */

    public function destroy($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            $result = $user->delete();

            if (!$result) {
                return response()->json([
                    'message' => 'User not deleted',
                    'data' => $result
                ], 500);
            }

            return response()->json([
                'message' => 'User deleted successfully',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
