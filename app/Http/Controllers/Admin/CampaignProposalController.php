<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProposalStatusRequest;
use App\Models\Admin\CampaignAssignInfluencerStatus;
use App\Models\Influencer\Proposal;
use App\Models\User\Campaign;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class CampaignProposalController extends Controller
{
    public function __construct()
    {
        // Only allow authenticated admins
        $this->middleware('auth:admin');
    }

    public function changeStatus(ProposalStatusRequest $request, $campaignId)
    {
        DB::beginTransaction();
        try {
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            $status = Proposal::where('campaign_id', $campaignId)
                ->where('influencer_id', $request->influencer_id)->first();

            if (!$status) {
                return response()->json([
                    'status' => false,
                    'message' => 'Proposal not found'
                ], 404);
            }

            $status->update([
                'status' => $request->status
            ]);

            if ($status->status == 'accepted') {
                // Check if already assigned
                $exists = CampaignAssignInfluencerStatus::where('campaign_id', $campaignId)
                    ->where('influencer_id', $request->influencer_id)
                    ->exists();

                if (!$exists) {
                    $result = CampaignAssignInfluencerStatus::create([
                        'campaign_id'   => $campaignId,
                        'influencer_id' => $request->influencer_id,
                    ]);

                    if (!$result) {
                        DB::rollBack();
                        return response()->json([
                            'status'  => false,
                            'message' => 'Proposal status update failed while assigning influencer.',
                        ], 500);
                    }
                } else {
                    // Already assigned, no duplicate entry
                    return response()->json([
                        'status'  => true,
                        'message' => 'This influencer has already been assigned to this campaign.',
                    ], 200);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Proposal updated successfully',
                'proposal' => $status
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Proposal status updated failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
