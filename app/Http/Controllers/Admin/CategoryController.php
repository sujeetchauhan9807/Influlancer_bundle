<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CategoryRequest;
use App\Models\Admin\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
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
        $category = Category::paginate($perPage, ['*'], 'page', $page);
        
        if ($category->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No category found'
            ], 404);
        }

        // Check if page exists or not
        if ($category->isEmpty() && $page > 1) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'success',
            'category' => $category->items(),    // current page users
            'pagination' => [
                'current_page' => $category->currentPage(),
                'last_page' => $category->lastPage(),
                'per_page' => $category->perPage(),
                'total' => $category->total(),
                'links' => $category->linkCollection(), // dots (...) included
            ]
        ],200);
        
        return response()->json([
            'status' => true,
            'message' => 'brand success',
            'category' => $category
        ], 200);
    }

    /**
     * Add  New Category
     */

    public function addCategory(CategoryRequest $request)
    {
        $category = Category::create([
            'name' => ucwords(strtolower($request->name)),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'category' => $category
        ], 200);
    }

    /**
    * Edit Category
    */
   public function updateCategory(CategoryRequest $request, $id)
   {
        $category = Category::where('id', $id)->first();
       
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found or unauthorized'
            ], 404);
        }

        $category->update([
            'name' => ucwords(strtolower($request->name))
        ]);
        
        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'category' => $category
        ], 200);
    }

    /**
     * Soft Delete Category
     */
    public function deleteCategory($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->delete(); 

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully',
            'category' => $category
        ], 200);
    }

}
