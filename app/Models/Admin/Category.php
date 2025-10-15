<?php

namespace App\Models\Admin;

use App\Models\Influencer\Influencer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use SoftDeletes;
    protected $fillable = ['name'];

    /**
    * Relationships
    */

    public function influencers()
    {
        return $this->belongsToMany(Influencer::class, 'influencer_categories', 'category_id', 'influencer_id')->withTimestamps();
    }

}


