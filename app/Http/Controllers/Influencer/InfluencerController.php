<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Requests\CampStatusRequest;
use App\Http\Requests\InfluencerUpdateRequest;
use App\Models\Influencer\Influencer;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Mail\VerifyEmailMail;
use App\Models\Admin\CampaignApproval;
use App\Models\Admin\CampaignAssignInfluencerStatus;
use App\Models\User\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InfluencerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:influencer');
    }

    /**
     * list campaign
     */
    public function index(Request $request)
    {
        $influencer = $request->get('loggedInInfluencer'); // Logged-in influencer
        $influencerData = Influencer::with(['categories', 'platforms'])
            ->where('id', $influencer->id)->first();

        $pivotCategoryIds = $influencerData->categories->pluck('pivot.category_id')->toArray();
        // Get campaigns in these categories
        $campaigns = Campaign::whereIn('category_id', $pivotCategoryIds)->get();
        $campaignIds = $campaigns->pluck('id')->toArray();

        // Get only approved campaigns for this influencer
        $approvedCampaignIds = CampaignApproval::whereIn('campaign_id', $campaignIds)
            ->where('status', 'approved')         // only approved
            ->pluck('campaign_id')
            ->toArray();

        $page = $request->input('page', 1); // default page = 1
        $perPage = $request->input('per_page', 10);  // Dynamic per_page (default 10)    

        // Fetch campaigns that are approved
        $approvedCampaigns = Campaign::with([
            'brand',
            'campaignType',
            'user',
            'category',
            'approvals',
            'commission',
            'proposalForInfluencer',
            'campaignStatusByInflu'
        ])->whereIn('id', $approvedCampaignIds)
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($approvedCampaigns->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Campaign found'
            ], 404);
        }

        // Check if page exists or not
        if ($approvedCampaigns->isEmpty() && $page > 1) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Approved campaigns fetched successfully',
            'campaigns' => $approvedCampaigns->items(),    // current page users
            'pagination' => [
                'current_page' => $approvedCampaigns->currentPage(),
                'last_page' => $approvedCampaigns->lastPage(),
                'per_page' => $approvedCampaigns->perPage(),
                'total' => $approvedCampaigns->total(),
                'links' => $approvedCampaigns->linkCollection(), // dots (...) included
            ]
        ], 200);
    }

    /**
     * view profile 
     */

    public function viewProfile(Request $request)
    {
        try {
            $influencer = $request->get('loggedInInfluencer'); // Logged-in influencer

            if (!$influencer) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access. Please log in to view your profile.',
                ], 401);
            }

            // Load reviews and calculate stats

            $influencer->load(['influencerReviews' => function ($query) {
                $query->select('id', 'influencer_id', 'campaign_id', 'rating', 'comment', 'created_at');
            }]);

            $averageRating = $influencer->influencerReviews->avg('rating');
            $totalReviews  = $influencer->influencerReviews->count();

            return response()->json([
                'status' => true,
                'message' => 'User profile fetched successfully',
                'data' => [
                    'profile'        => $influencer,
                    'total_reviews'  => $totalReviews,
                    'average_rating' => round($averageRating, 1),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load profile. Please try again later.',
                'error'   => $e->getMessage(), // optional for debugging
            ], 500);
        }
    }

    /**
     * update profile 
     */

    public function updateProfile(InfluencerUpdateRequest $request, $id)
    {
        $influencer = $request->get('loggedInInfluencer'); // Logged-in influencer
        $influencerUpdate = Influencer::with(['categories', 'platforms'])
            ->where('id', $id)->where('id', $influencer->id)->first();

        if (!$influencerUpdate) {
            return response()->json([
                'status' => false,
                'message' => 'Influencer not found or unauthorized'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $newFileName = $influencer->profile_image;
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $extension = $file->getClientOriginalExtension();
                $newFileName = date('YmdHis') . time() . '.' . $extension;
                $uploadPath = public_path('influencer/profile_uploads');
                $file->move($uploadPath, $newFileName);

                if (!empty($influencer->profile_image)) {
                    $oldImagePath = $uploadPath . '/' . $influencer->profile_image;
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
            }

            $emailVerifiedAt = $influencer->email_verified_at;
            // email change check
            if ($request->has('email') && $request->email !== $influencer->email) {
                $emailVerifiedAt = null; // reset verification 
                $verificationToken = Str::random(60);

                $verificationUrl = url('/api/influencer/verify-email?' . $verificationToken);
                Mail::to($request->email)->queue(new VerifyEmailMail($verificationUrl));
            }

            $influencerUpdate->update([
                'name'            => ucwords(strtolower($request->name)),
                'email'           => $request->email,
                'phone'           => $request->phone,
                'bio'             => $request->bio,
                'profile_image'   => $newFileName,
                'region'          => $request->region,
                'address'         => ucwords(strtolower($request->address)),
                'audience_size'   => $request->audience_size,
                'engagement_rate' => $request->engagement_rate,
                'email_verified_at' => $emailVerifiedAt,
            ]);

            if ($request->has('categories') && is_array($request->categories)) {
                $influencerUpdate->categories()->sync($request->categories);
            }

            if ($request->has('platforms') && is_array($request->platforms)) {
                $influencerUpdate->platforms()->sync($request->platforms);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Influencer updated successfully',
                'influencer' => $influencerUpdate->refresh()->load(['categories', 'platforms'])
            ], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Profile update failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function campaignView($id)
    {
        try {
            $campaign = Campaign::with([
                'brand',
                'campaignType',
                'user',
                'category',
                'approvals',
                'commission',
                'proposalForInfluencer',
                'campaignStatusByInflu'
            ])->find($id);

            if (!$campaign) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Campaign not found.'
                ], 404);
            }

            return response()->json([
                'status'   => true,
                'message'  => 'Campaign view page successful.',
                'campaign' => $campaign
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'View load failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function campaignStatus(CampStatusRequest $request, $campaignId)
    {
        try {
            $influencer = $request->get('loggedInInfluencer'); // Logged-in influencer
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                return response()->json([
                    'status' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            $campaignStatus = CampaignAssignInfluencerStatus::where('campaign_id', $campaignId)
                ->where('influencer_id', $influencer->id)->first();

            if (!$campaignStatus) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not approved for this campaign. Please wait for admin approval.',
                ], 403);
            }

            $campaignStatus->update([
                'status' => $request->status
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Campaign status updated successfully',
                'campaign' => $campaignStatus
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
