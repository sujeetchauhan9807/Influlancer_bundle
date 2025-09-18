<?php

namespace App\Http\Controllers\Admin;

use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    // public function __construct()
    // {
    //     // Only allow authenticated admins
    //     $this->middleware(['auth:api']);
    // }

    /**
     * Get all users (Only for admin)
     */
    public function index()
    {
        try {
            // Optional: ensure current user is admin
            $admin = Auth::guard('admin')->user();
            if (!$admin) {
                return response()->json([
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            $users = User::all();

            return response()->json([
                'message' => 'Success',
                'data' => $users
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
