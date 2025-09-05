<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\CreatorPlan;
use App\Models\UserProfile;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user profile associated with the user.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function creatorPlans(): HasMany 
    { 
        return $this->hasMany(CreatorPlan::class, 'creator_id'); 
    }
  

    public function isCreator(): bool
    {
        return optional($this->profile)->is_creator === true;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

     /**
     * As a creator: the one subscription plan this user owns.
     * (A creator can only create one subscription.)
     */
    public function ownedSubscription()
    {
        return $this->hasOne(Subscription::class, 'creator_id');
    }

    /**
     * As a subscriber: all subscriptions the user has purchased.
     * (Users can subscribe to many subscriptions.)
     */
    public function subscriptions()
    {
        return $this->belongsToMany(Subscription::class, 'subscription_user')
            ->withPivot(['starts_at','ends_at','status','is_active','provider','provider_subscription_id','price_snapshot'])
            ->withTimestamps();
    }

    /** Convenience scope */
    public function activeSubscriptions()
    {
        return $this->subscriptions()->wherePivot('is_active', true)->wherePivot('status', 'active');
    }

}
