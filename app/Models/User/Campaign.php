<?php

namespace App\Models\User;

use App\Models\Admin\CampaignApproval;
use App\Models\Admin\CampaignAssignInfluencerStatus;
use App\Models\Admin\CampaignCommission;
use App\Models\Admin\CampaignType;
use App\Models\Admin\Category;
use App\Models\Influencer\Proposal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;
    protected $fillable = ['brand_id', 'campaign_type_id', 'user_id', 'category_id', 'title', 'description', 'budget', 'currency', 'require_influencers', 'start_date', 'end_date', 'status'];

    // One-to-One (belongsTo) Relationships

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function campaignType()
    {
        return $this->belongsTo(CampaignType::class, 'campaign_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // A campaign has Many status
    public function approvals()
    {
        return $this->hasMany(CampaignApproval::class, 'campaign_id');
    }

    // A campaign has one commission
    public function commission()
    {
        return $this->hasOne(CampaignCommission::class, 'campaign_id');
    }

    public function proposalForInfluencer()
    {
        return $this->hasOne(Proposal::class, 'campaign_id')
            ->where('influencer_id', auth('influencer')->id());
    }

    public function proposalApprovals()
    {
        return $this->hasMany(Proposal::class, 'campaign_id')
            ->whereIn('status', ['negotiation', 'accepted', 'rejected', 'withdrawn']);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'campaign_id');
    }

    public function campaignStatusByInflu()
    {
        if (auth('influencer')->check() && auth('influencer')->user()->role === 'influencer') {
            return $this->hasOne(CampaignAssignInfluencerStatus::class, 'campaign_id')
                ->where('influencer_id', auth('influencer')->id());
        } elseif (auth('api')->check() && auth('api')->user()->role === 'brand') {
            return $this->hasMany(CampaignAssignInfluencerStatus::class, 'campaign_id');
        } else {
            return $this->hasMany(CampaignAssignInfluencerStatus::class, 'campaign_id');
        }
    }
}
