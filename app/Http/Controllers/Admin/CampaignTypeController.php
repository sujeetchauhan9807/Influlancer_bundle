<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CampaignTypeUpdateRequest;
use App\Http\Requests\CampaignTypeRequest;
use App\Models\Admin\CampaignType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class CampaignTypeController extends Controller
{
  public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1); // default page = 1
            $perPage = $request->input('per_page', 10);  // Dynamic per_page (default 10)
            $campaignType = CampaignType::paginate($perPage, ['*'], 'page', $page);
            
            if ($campaignType->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No campaign types found'
                ], 404);
            }

            // Check if page exists or not
            if ($campaignType->isEmpty() && $page > 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'success',
                'campaignType' => $campaignType->items(),    // current page users
                'pagination' => [
                    'current_page' => $campaignType->currentPage(),
                    'last_page' => $campaignType->lastPage(),
                    'per_page' => $campaignType->perPage(),
                    'total' => $campaignType->total(),
                    'links' => $campaignType->linkCollection(), // dots (...) included
                ]
            ],200);
            
            return response()->json([
                'status' => false,
                'message' => 'Campaign type success',
                'campaignType' => $campaignType
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
     * Add  New campaignType
     */

    public function addCampaignType(CampaignTypeRequest $request)
    {
        try {
            $user = $request->get('loggedInUser');
            $campaignType = CampaignType::create([
                'name' => ucwords(strtolower($request->name)),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Campaign type created successfully',
                'campaignType' => $campaignType
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Campaign type created failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

   /**
    * Edit campaignType
    */
   public function updateCampaignType(CampaignTypeUpdateRequest $request, $id)
   {
        try {
            $campaignType = CampaignType::where('id', $id)->first();
        
            if (!$campaignType) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign Type not found or unauthorized'
                ], 404);
            }

            $campaignType->update([
                'name' => ucwords(strtolower($request->name))
            ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Campaign type updated successfully',
                'campaingType' => $campaignType
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Campaign type updated failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
    * Delete campaignType
    */
    public function deleteCampaignType(Request $request, $id)
    {
        try {
            $campaignType = CampaignType::where('id', $id)
                ->first();

            if (!$campaignType) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign type not found or unauthorized'
                ], 404);
            }

            $campaignType->delete();

            return response()->json([
                'status' => true,
                'message' => 'Campaign type deleted successfully'
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Campaign type delete failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}
