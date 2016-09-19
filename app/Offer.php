<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'item_id',
        'value'
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
