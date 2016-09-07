<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'item_id',
        'amount',
        'payment_resource'
    ];
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    
    public function item()
    {
        return $this->belongsTo('App\Item');
    }
}
