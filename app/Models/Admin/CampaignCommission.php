<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class CampaignCommission extends Model
{
    protected $fillable = ['campaign_id' , 'user_id' , 'commission_amount'];
}
