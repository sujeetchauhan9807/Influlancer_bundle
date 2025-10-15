<?php

namespace App\Models\Influencer;

use App\Models\User\Campaign;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{   
    protected $table = 'campaign_proposals';
    protected $fillable = ['campaign_id', 'influencer_id', 'proposed_fee', 'status', 'note'];

}
