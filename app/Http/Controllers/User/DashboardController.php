<?php

namespace App\Http\Controllers\User;

use App\Models\User\Campaign;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function stats(Request $request)
    {
        try {

            $user = $request->get('loggedInUser');
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
            $endDate   = $request->end_date   ? Carbon::parse($request->end_date) : null;

            $filterByDate = function ($query)  use ($startDate, $endDate, $user) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }

                $query->where('user_id', $user->id);
            };

            $totalCampaigns = Campaign::tap($filterByDate)->count();
            $campaignStatus = Campaign::select('status')
                ->tap($filterByDate)
                ->get()
                ->groupBy('status')
                ->map(fn($row) => $row->count());
            return response()->json([
                'status' => true,
                'message' => 'Dashboard data loaded successfully',
                'dashboard' => [
                    'total_campaigns'    => $totalCampaigns,
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
