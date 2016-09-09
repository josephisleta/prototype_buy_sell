<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = [
        'following_user_id'
    ];
    
    protected $hidden = [
        'updated_at'
    ];
    
    public function follower()
    {
        return $this->belongsTo('App\User', 'id', 'follower_user_id');
    }

    public function following()
    {
        return $this->belongsTo('App\User', 'id', 'following_user_id');
    }
}
