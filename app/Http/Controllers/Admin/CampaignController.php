<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CampaignUpdateRequest;
use App\Models\User\Campaign;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class CampaignController extends Controller
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
        //filter
        $brandId = $request->input('brand_id');
        $categoryId = $request->input('category_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Sorting
        $sortBy = $request->input('sort_by', 'id'); // default 'id'
        $sortOrder = strtolower($request->input('sort_order', 'desc')); // default 'desc'

        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query = Campaign::with([
            'brand',
            'campaignType',
            'category',
            'user',
            'approvals',
            'commission',
            'proposals',
            'campaignStatusByInflu'
        ]);

        if (!empty($brandId)) {
            $query->where('brand_id', $brandId);
        }

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        $campaign = $query->paginate($perPage, ['*'], 'page', $page)
            ->appends([
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]);

        if ($campaign->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No campaign found.'
            ], 404);
        }

        // Check if page exists or not
        if ($campaign->isEmpty() && $page > 1) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'success',
            'campaign' => $campaign->items(),    // current page users
            'pagination' => [
                'current_page' => $campaign->currentPage(),
                'last_page' => $campaign->lastPage(),
                'per_page' => $campaign->perPage(),
                'total' => $campaign->total(),
                'links' => $campaign->linkCollection(),
            ]
        ], 200);

        return response()->json([
            'status' => true,
            'message' => 'Campaign listing pages.',
            'campaign' => $campaign
        ], 200);
    }

    /**
     *  Update Campaign
     * */
    public function updateCampaign(CampaignUpdateRequest $request, $id)
    {

        DB::beginTransaction();
        try {
            $campaign = Campaign::where('id', $id)->first();
            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found or unauthorized'
                ], 404);
            }

            $campaign->update([
                'brand_id'          => $request->brand_id,
                'campaign_type_id'  => $request->campaign_type_id,
                'category_id'       => $request->category_id,
                'title'             => ucwords(strtolower($request->title)),
                'description'       => $request->description,
                'budget'            => $request->budget,
                'currency'          => $request->currency ?? 'INR',
                'status'            => $request->status,
                'commission_amount' => $request->commission_amount,
                'start_date'        => $request->start_date,
                'end_date'          => $request->end_date
            ]);

            if ($request->filled('approval_status')) {
                $campaign->approvals()->updateOrCreate(
                    ['campaign_id' => $campaign->id], // condition to find record
                    [
                        'campaign_id' => $campaign->id,
                        'user_id'     => $campaign->user_id,
                        'status' => $request->approval_status,
                    ]
                );
            }

            if ($request->filled('commission_amount')) {
                $campaign->commission()->updateOrCreate(
                    ['campaign_id' => $campaign->id], // condition to find record
                    [
                        'campaign_id' => $campaign->id,
                        'user_id'     => $campaign->user_id,
                        'commission_amount' => $request->commission_amount,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Campaing updated successfully',
                'data' => $campaign->refresh()->load([
                    'brand',
                    'campaignType',
                    'user',
                    'approvals',
                    'commission'
                ])
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Campaing updated failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function viewPage(Request $request)
    {
        try {
            $campaign = Campaign::with([
                'brand',
                'campaignType',
                'category',
                'user',
                'approvals',
                'commission',
                'proposals',
                'campaignStatusByInflu'
            ])
                ->where('id', $request->id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'No campaign found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'View page success',
                'campaign' => $campaign
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'View page failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
