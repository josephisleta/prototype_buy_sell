<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseRating extends Model
{
    protected $fillable = [
        'item_id',
        'rating',
        'message'
    ];

    protected $appends = [
        'user_data'
    ];
    
    protected $hidden = [
        'updated_at'
    ];
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    
    public function item()
    {
        return $this->belongsTo('App\Item');
    }

    public function getUserDataAttribute()
    {
        return $this->user()->get();
    }
}
