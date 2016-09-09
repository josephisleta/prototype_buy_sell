<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDebit extends Model
{
    protected $fillable = [
        'amount',
        'type',
        'item_id'
    ];

    protected $hidden = [
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
