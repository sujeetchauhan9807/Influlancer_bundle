<?php

namespace App\Http\Controllers\User;

use App\Http\Requests\ReviewRequest;
use App\Models\Admin\CampaignAssignInfluencerStatus;
use App\Models\Influencer\Proposal;
use App\Models\User\Campaign;
use App\Models\User\Review;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(ReviewRequest $request)
    {
        try {
            $user = $request->get('loggedInUser');

            $existingReview = Review::where('campaign_id', $request->campaign_id)
                ->where('user_id', $user->id)->where('influencer_id', $request->influencer_id)
                ->exists();

            if ($existingReview) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already submitted a review for this influencer on this campaign.',
                ], 409);
            }

            $campaign = Campaign::find($request->campaign_id);

            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found.',
                ], 404);
            }

            // Check if logged-in user is owner of the campaign
            if ($campaign->user_id != $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. You cannot submit a review for this campaign.',
                ], 403);
            }

            $campaignStatusInflu = CampaignAssignInfluencerStatus::where('campaign_id', $request->campaign_id)
                ->where('influencer_id', $request->influencer_id)->first();

            // Check if campaign is completed
            if ($campaign->status != 'completed' && $campaignStatusInflu->status != 'completed') {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only submit a review after the campaign is completed.',
                ], 409);
            }

            $proposalStatus = Proposal::where('campaign_id', $request->campaign_id)
                ->where('influencer_id', $request->influencer_id)->first();

            if (!$proposalStatus || $proposalStatus->status != 'accepted') {
                return response()->json([
                    'status'  => false,
                    'message' => $proposalStatus
                        ? 'This proposal cannot be reviewed because it has already been ' . $proposalStatus->status . '.'
                        : 'No valid proposal found for this influencer.',
                ], 409);
            }

            $review = Review::create([
                'campaign_id'    => $request->campaign_id,
                'user_id'        => $user->id,
                'influencer_id'  => $request->influencer_id,
                'rating'         => $request->rating,
                'comment'        => $request->comment ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Review submitted successfully',
                'data' => $review,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Review submission failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing review
     */

    public function update(ReviewRequest $request, $id)
    {
        try {
            $user = $request->get('loggedInUser');
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review not found.'
                ], 404);
            }

            // Only review owner can update
            if ($review->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. You cannot edit this review.',
                ], 403);
            }

            $review->update([
                'rating' => $request->rating ?? $review->rating,
                'comment' => $request->comment ?? $review->comment,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Review updated successfully.',
                'data'    => $review,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update review.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  Delete a review
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->get('loggedInUser');
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review not found.',
                ], 404);
            }

            // Only review owner can delete
            if ($review->user_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. You cannot delete this review.',
                ], 403);
            }

            $review->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Review deleted successfully.',
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete review.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
