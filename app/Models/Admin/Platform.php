<?php

namespace App\Models\Admin;

use App\Models\Influencer\Influencer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use SoftDeletes;
    protected $fillable = ['name'];

    public function influencers()
    {
        return $this->belogsTomany(Influencer::class, 'influencer_platforms', 'platform_id', 'influencer_id')->withTimestamps();
    }
}
