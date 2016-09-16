<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'firstname',
        'lastname',
        'email',
        'password',
        'gender',
        'location',
        'contact',
        'avatar',
        'birth_date'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];
    
    public function items()
    {
        return $this->hasMany('App\Item');
    }

    public function likes()
    {
        return $this->hasMany('App\Like');
    }

    public function comments()
    {
        return $this->hasMany('App\Comment');
    }
    
    public function purchases()
    {
        return $this->hasMany('App\Purchase');
    }
    
    public function payments()
    {
        return $this->hasMany('App\Payment');
    }
    
    public function item_views()
    {
        return $this->hasMany('App\ItemView');
    }
    
    public function follows()
    {
        return $this->hasMany('App\Follow', 'follower_user_id', 'id');
    }

    public function followers()
    {
        return $this->hasMany('App\Follow', 'following_user_id', 'id');
    }
    
    public function purchase_ratings()
    {
        return $this->hasMany('App\PurchaseRating');
    }
    
    public function debits()
    {
        return $this->hasMany('App\UserDebit');
    }

    public function getFullNameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getGenderAttribute()
    {
        switch ($this->attributes['gender']) {
            case 'm':
                $gender = 'Male';
                break;
            case 'f':
                $gender = 'Female';
                break;
            default:
                $gender = null;
                break;
        }

        return $gender;
    }

    public function getNotificationsAttribute()
    {
        return Notification::where('user_ids', 'like', "%{$this->id},%")->orderBy('created_at', 'desc')->get();
    }

    public function getAvatarAttribute()
    {
        if ($this->attributes['avatar']) {
            return URL::to("/images/users/{$this->user->avatar}");
        }

        return URL::to('/images/default_user.png');
    }
    
    public function getRatings($type)
    {
        $ratings = collect();
        
        foreach ($this->items as $item) {
            if (!$item->purchase_rating) continue;
            if ($item->purchase_rating->rating == $type) $ratings[] = $item->purchase_rating;
        }
        
        return $ratings;
    }

    /**
     * @param array $excludes
     * @return array
     */
    public function getReturn($excludes = array())
    {
        $data =  [
            'id'            => $this->id,
            'username'      => $this->username,
            'firstname'     => $this->firstname,
            'lastname'      => $this->lastname,
            'email'         => $this->email,
            'gender'        => $this->gender,
            'location'      => $this->location,
            'contact'       => $this->contact,
            'avatar'        => $this->avatar,
            'birthdate'     => $this->birthdate,
            'notifications' => $this->notifications
        ];

        if (!$excludes) return $data;

        foreach ($excludes as $exclude) {
            unset($data[$exclude]);
        }

        return $data;
    }
}
