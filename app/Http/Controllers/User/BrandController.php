<?php

namespace App\Http\Controllers\User;

use App\Http\Requests\BrandRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Models\User\Brand;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1); // default page = 1
            $perPage = $request->input('per_page', 10);  // Dynamic per_page (default 10)
            $user = $request->get('loggedInUser');
            $brands = Brand::where('user_id', $user->id)
            ->orderBy('id', 'desc') 
            ->paginate($perPage, ['*'], 'page', $page);
            
            if ($brands->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No brands found'
                ], 404);
            }

            // Check if page exists or not
            if ($brands->isEmpty() && $page > 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'success',
                'brand' => $brands->items(),    // current page users
                'pagination' => [
                    'current_page' => $brands->currentPage(),
                    'last_page' => $brands->lastPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
                    'links' => $brands->linkCollection(), // dots (...) included
                ]
            ],200);
            
            return response()->json([
                'status' => true,
                'message' => 'brand success',
                'brand' => $brands
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Page load failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add  New Brand
     */

    public function addBrand(BrandRequest $request)
    {
        try {
            $user = $request->get('loggedInUser');
            $brands = Brand::create([
                'name' => ucwords(strtolower($request->name)),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Brand created successfully',
                'brand' => $brands
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Brand created failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

   /**
    * Edit Brand
    */
   public function updateBrand(BrandUpdateRequest $request, $id)
   {
        try {
            $user = $request->get('loggedInUser');
            $brands = Brand::where('id', $id)
            ->where('user_id', $user->id)->first();
        
            if (!$brands) {
                return response()->json([
                    'status' => false,
                    'message' => 'Brand not found or unauthorized'
                ], 404);
            }

            $brands->update([
                'name' => ucwords(strtolower($request->name))
            ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Brand updated successfully',
                'brand' => $brands
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Brand updated failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
    * Delete Brand
    */
    public function deleteBrand(Request $request, $id)
    {
        try {
            $user = $request->get('loggedInUser');
            $brand = Brand::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$brand) {
                return response()->json([
                    'status' => false,
                    'message' => 'Brand not found or unauthorized'
                ], 404);
            }

            $brand->delete();

            return response()->json([
                'status' => true,
                'message' => 'Brand deleted successfully'
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Brand delete failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}
