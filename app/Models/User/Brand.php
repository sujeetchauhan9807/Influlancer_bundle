<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'user_id'];

    // public function campaigns()
    // {
    //     return $this->hasMany(Campaign::class, 'brand_id');
    // }
}
