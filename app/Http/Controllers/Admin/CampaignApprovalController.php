<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CampaignApprovalRequest;
use App\Models\Admin\CampaignApproval;
use App\Models\User\Campaign;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class CampaignApprovalController extends Controller
{
    public function __construct()
    {
        // Only allow authenticated admins
        $this->middleware('auth:admin');
    }

    public function changeStatus(CampaignApprovalRequest $request, $campaignId)
    {
        try{
            $campaign = Campaign::where('id', $campaignId)->first();
            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }
            
            $approval = CampaignApproval::where('campaign_id', $campaignId)->first();
            
            if (!$approval) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            $approval->update([
                'status' => $request->status
            ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Campaign updated successfully',
                'campaign' => $approval
            ], 200);
            
            
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Campaign status updated failed',
                'error'   => $e->getMessage()
            ], 500);
        }

    }
}
