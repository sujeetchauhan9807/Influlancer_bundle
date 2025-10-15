<?php

namespace App\Models\Admin;

use App\Models\Influencer\Influencer;
use App\Models\User\Campaign;
use Illuminate\Database\Eloquent\Model;

class CampaignApproval extends Model
{
    protected $fillable = ['campaign_id', 'user_id', 'status'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    // public function influencer()
    // {
    //     return $this->belongsTo(Influencer::class, 'influencer_id');
    // }
}
