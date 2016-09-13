<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'item_id',
        'body'
    ];

    protected $appends = [
        'user_data'
    ];
    
    protected $hidden = [
        'id'
    ];
    
    public function item()
    {
        return $this->belongsTo('App\Item');
    }
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function getUserDataAttribute()
    {
        return $this->user()->first();
    }
}
