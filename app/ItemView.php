<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemView extends Model
{
    protected $fillable = [
        'item_id'
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
}
