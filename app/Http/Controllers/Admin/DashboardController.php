<?php

namespace App\Http\Controllers\Admin;

use App\Models\Influencer\Influencer;
use App\Models\User\Campaign;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function stats(Request $request)
    {
        try {

            $startDate       = $request->start_date ?  Carbon::parse($request->start_date) : null;
            $endDate         = $request->end_date  ? Carbon::parse($request->end_date) : null;
            $categoryId      = $request->category_id;
            $campaignTypeId  = $request->campaign_type_id;

            // Filter for Campaigns
            $campaignFilter = function ($query) use ($startDate, $endDate, $categoryId, $campaignTypeId) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }

                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }

                if ($campaignTypeId) {
                    $query->where('campaign_type_id', $campaignTypeId);
                }
            };

            // Filter only by date for users and influencers
            $filterByDate = function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            };

            $totalCampaigns     = Campaign::tap($campaignFilter)->count();
            $totalUsers         = User::tap($filterByDate)->count();
            $totalInfluencers   = Influencer::tap($filterByDate)->count();

            $campaignStatus     = Campaign::select('status')
                ->tap($campaignFilter)
                ->get()
                ->groupBy('status')
                ->map(fn($row) => $row->count());

            return response()->json([
                'status' => true,
                'message' => 'Dashboard data loaded successfully',
                'dashboard' => [
                    'total_campaigns'    => $totalCampaigns,
                    'total_users'        => $totalUsers,
                    'total_influencers'  => $totalInfluencers,
                    'campaign_status'    => $campaignStatus
                ]
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
