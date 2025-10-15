<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PlatformRequest;
use App\Models\Admin\Platform;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PlatformController extends Controller
{
    public function __construct()
    {
        // Only allow authenticated admins
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $page = $request->input('page', 1); // default page = 1
        $perPage = $request->input('per_page', 10);  // Dynamic per_page (default 10)
        $platform = Platform::paginate($perPage, ['*'], 'page', $page);
        
        if ($platform->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No platform found'
            ], 404);
        }

        // Check if page exists or not
        if ($platform->isEmpty() && $page > 1) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'success',
            'platform' => $platform->items(),    // current page users
            'pagination' => [
                'current_page' => $platform->currentPage(),
                'last_page' => $platform->lastPage(),
                'per_page' => $platform->perPage(),
                'total' => $platform->total(),
                'links' => $platform->linkCollection(), // dots (...) included
            ]
        ],200);
        
        return response()->json([
            'status' => false,
            'message' => 'brand success',
            'platform' => $platform
        ], 200);
    }

    /**
     * Add  New platform
     */

    public function addPlatform(PlatformRequest $request)
    {
        $platform = Platform::create([
            'name' => ucwords(strtolower($request->name)),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Platform created successfully',
            'platform' => $platform
        ], 200);
    }

    /**
    * Edit platform
    */
   public function updatePlatform(PlatformRequest $request, $id)
   {
        $platform = Platform::where('id', $id)->first();
       
        if (!$platform) {
            return response()->json([
                'status' => false,
                'message' => 'Platform not found or unauthorized'
            ], 404);
        }

        $platform->update([
            'name' => ucwords(strtolower($request->name))
        ]);
        
        return response()->json([
            'status' => true,
            'message' => 'Platform updated successfully',
            'platform' => $platform
        ], 200);
    }

    /**
     * Soft Delete platform
     */
    public function deletePlatform($id)
    {
        $platform = Platform::find($id);

        if (!$platform) {
            return response()->json([
                'status' => false,
                'message' => 'Platform not found'
            ], 404);
        }

        $platform->delete(); 

        return response()->json([
            'status' => true,
            'message' => 'Platform deleted successfully',
            'platform' => $platform
        ], 200);
    }
}
