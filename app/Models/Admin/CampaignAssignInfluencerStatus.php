<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class CampaignAssignInfluencerStatus extends Model
{
    protected $table = 'campaign_assign_influencer_status';
    protected $fillable = ['campaign_id', 'influencer_id', 'status'];
}
