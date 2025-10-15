<?php

namespace App\Models\Influencer;

use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Admin\Category;
use App\Models\Admin\Platform;
use App\Models\User\Review;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Influencer extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'influencers';

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'role', 
        'phone',
        'bio', 
        'profile_image',
        'region',
        'address',
        'platform_id',
        'audience_size',
        'engagement_rate',
        'status', 
        'email_verified_at',      
        'verification_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'platform_id'      => 'array',
            'password' => 'hashed'
        ];
    }

        /**
     * Accessor – automatically return full image URL.
     */

    protected function profileImage():Attribute
    {
        return Attribute::make(
            get: fn($value) => $value
            ? asset('influencer/profile_uploads/' . $value)
            : asset('influencer/profile_uploads/default.png')
        );

    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Relationships
     */

    public function categories()
    {
       return $this->belongsToMany(Category::class, 'influencer_categories', 'influencer_id', 'category_id')->withTimestamps();
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'influencer_platforms', 'influencer_id', 'platform_id')->withTimestamps();
    }

    public function influencerReviews()
    {
        return $this->hasMany(Review::class, 'influencer_id');
    }
}
