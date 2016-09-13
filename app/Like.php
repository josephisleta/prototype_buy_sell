<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'item_id'
    ];

    protected $appends = [
        'user_data'
    ];

    protected $hidden = [
        'id',
        'updated_at'
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
