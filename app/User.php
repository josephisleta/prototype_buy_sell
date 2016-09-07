<?php

namespace App;

use Illuminate\Notifications\Notifiable;
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
}
