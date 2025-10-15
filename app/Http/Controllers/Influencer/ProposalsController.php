<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Requests\ProposalRequest;
use App\Models\Admin\CampaignApproval;
use App\Models\Influencer\Proposal;
use App\Models\User\Campaign;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProposalsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:influencer');
    }

    public function postProposals(ProposalRequest $request, $id)
    {
        try {
            $influencer = $request->get('loggedInInfluencer'); // Logged-in influencer

            // Check if campaign exists
            $campaign = Campaign::find($id);
            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            // Check if campaign is approved
            $campaignApproval = CampaignApproval::where('campaign_id', $id)->first();
            if (!$campaignApproval || $campaignApproval->status !== 'approved') {
                return response()->json([
                    'status' => false,
                    'message' => 'This campaign has not been approved by admin'
                ], 403); // 403 Forbidden more appropriate
            }

            // Check if influencer belongs to the campaign category
            $categoryExists = DB::table('influencer_categories')
                ->where('influencer_id', $influencer->id)
                ->where('category_id', $campaign->category_id) // assuming campaign has category_id
                ->exists();

            if (!$categoryExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Influencer category not allowed for this campaign'
                ], 403);
            }

            $exists = Proposal::where('campaign_id', $id)
            ->where('influencer_id', $influencer->id)
            ->exists();

            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You have already submitted a proposal for this campaign.'
                ], 422);
            }

            // Create proposal
            $proposal = Proposal::create([
                'campaign_id'   => $campaign->id,
                'influencer_id' => $influencer->id,
                'proposed_fee'  => $request->proposed_fee,
                'note'          => $request->note
            ]);

            return response()->json([
                'status'   => true,
                'message'  => 'Proposal created successfully',
                'proposal' => $proposal
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Proposal creation failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
