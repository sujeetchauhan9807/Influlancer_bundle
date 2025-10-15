<?php

namespace App\Http\Controllers\User;

use App\Http\Requests\CampaignRequest;
use App\Http\Requests\CampaignUpdateRequest;
use App\Models\Admin\CampaignType;
use App\Models\Admin\Category;
use App\Models\User\Brand;
use App\Models\User\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class CampaignController extends Controller
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
            $user = $request->get('loggedInUser');
            $query = Campaign::with([
                'brand',
                'campaignType',
                'category',
                'user',
                'approvals',
                'proposalApprovals',
                'campaignStatusByInflu'
            ])
                ->where('user_id', $user->id);

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
                    'message' => 'No compaigns found'
                ], 404);
            }

            // Check if page exists or not
            if ($campaign->isEmpty() && $page > 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'success',
                'data' => $campaign->items(),    // current page users
                'pagination' => [
                    'current_page' => $campaign->currentPage(),
                    'last_page' => $campaign->lastPage(),
                    'per_page' => $campaign->perPage(),
                    'total' => $campaign->total(),
                    'links' => $campaign->linkCollection(), // dots (...) included
                ]
            ], 200);

            return response()->json([
                'status' => true,
                'message' => 'brand success',
                'campaign' => $campaign
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
     *  Add new Campaign
     * */
    public function addCampaign(CampaignRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->get('loggedInUser');

            $brandExists = $this->brandExists($request, $user);

            if (!$brandExists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Brand not found or unauthorized'
                ], 404);
            }

            $campaign = Campaign::create([
                'brand_id'             => $request->brand_id,
                'campaign_type_id'     => $request->campaign_type_id,
                'user_id'              => $user->id,
                'category_id'          => $request->category_id,
                'title'                => ucwords(strtolower($request->title)),
                'description'          => $request->description,
                'budget'               => $request->budget,
                'currency'             => $request->currency ?? 'INR',
                'require_influencers'  => $request->require_influencers,
                'start_date'           => $request->start_date,
                'end_date'             => $request->end_date,
            ]);

            if (!empty($campaign->id)) {
                $campaign->approvals()->create([
                    'campaign_id' => $campaign->id,
                    'user_id'     => $user->id
                ]);
            }

            if (!empty($campaign->id)) {
                $campaign->commission()->create([
                    'campaign_id' => $campaign->id,
                    'user_id'     => $user->id
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Campaign created successfully',
                'campaign' => $campaign
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Campaign created failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    /**
     *  Update Campaign
     * */
    public function updateCampaign(CampaignUpdateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = $request->get('loggedInUser');
            $campaign = Campaign::where('id', $id)
                ->where('user_id', $user->id)->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found or unauthorized'
                ], 404);
            }

            $brandExists = $this->brandExists($request, $user);

            if (!$brandExists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Brand not found or unauthorized'
                ], 404);
            }

            $campaign->update([
                'brand_id'              => $request->brand_id,
                'campaign_type_id'      => $request->campaign_type_id,
                'user_id'               => $user->id,
                'category_id'           => $request->category_id,
                'title'                 => ucwords(strtolower($request->title)),
                'description'           => $request->description,
                'budget'                => $request->budget,
                'currency'              => $request->currency ?? 'INR',
                'status'                => $request->status,
                'require_influencers'   => $request->require_influencers,
                'start_date'            => $request->start_date,
                'end_date'              => $request->end_date
            ]);

            if ($request->filled('approval_status')) {
                $campaign->approvals()->update([
                    'status' => $request->approval_status,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Campaing updated successfully',
                'data' => $campaign->refresh()->load('approvals')
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

    /**
     *  Delete Campaign
     */
    public function deleteCampaign(Request $request, $id)
    {
        try {
            $user = $request->get('loggedInUser');
            $campaign = Campaign::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found or unauthorized'
                ], 404);
            }

            $campaign->delete();

            return response()->json([
                'status' => true,
                'message' => 'Campaign deleted successfully'
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Campaign delete failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  View Campaign
     */

    public function viewPage(Request $request)
    {
        try {
            $user = $request->get('loggedInUser');
            $campaign = Campaign::with([
                'brand',
                'campaignType',
                'category',
                'user',
                'approvals',
                'proposalApprovals',
                'campaignStatusByInflu'
            ])
                ->where('user_id', $user->id)
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

    /**
     *  Brand exists 
     */

    public function BrandExists($request, $user)
    {
        $brandExists = Brand::where('user_id', $user->id)->where('id', $request->brand_id)->exists();
        return $brandExists;
    }

    public function categoriesList()
    {
        try {
            $categories = Category::all();
            if ($categories->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No categories found.',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Categories fetched successfully.',
                'categories' => $categories,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch categories.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function campaignsTypeList()
    {
        try {
            $campaignsTypeList = CampaignType::all();
            if ($campaignsTypeList->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No campaign type found.',
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Campaign type fetched successfully.',
                'data'    => $campaignsTypeList,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch campaign type.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function brandList(Request $request)
    {
        try {
            $user = $request->get('loggedInUser'); // Current logged-in user
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $brandList = Brand::where('user_id', $user->id)->get();

            if ($brandList->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No brands found for this user.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Brand list fetched successfully.',
                'data' => $brandList
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch brands.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
