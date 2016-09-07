<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_ids',
        'seen',
        'link',
        'message',
        'trigger',
        'user_id',
        'item_id'
    ];

    protected $hidden = [
        'user_id',
        'item_id',
        'updated_at'
    ];

    public static function createNotif($data)
    {
        $defaults = [
            'user_ids' => ',',
            'seen'     => '',
            'link'     => null,
            'message'  => '',
            'trigger'  => null,
            'user_id'  => null,
            'item_id'  => null,
        ];

        $data = array_merge($defaults, $data);

        self::create($data);
    }
}
