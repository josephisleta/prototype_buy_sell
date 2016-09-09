<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Item extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'brand_id',
        'name',
        'description',
        'price',
        'pictures',
        'condition',
        'size',
        'shipping_fee',
        'ships_from',
        'shipping_duration',
        'status'
    ];

    public $appends = [
        'like_count',
        'comment_count',
        'user_data'
    ];
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function likes()
    {
        return $this->hasMany('App\Like');
    }
    
    public function views()
    {
        return $this->hasMany('App\ItemView');
    }

    public function comments()
    {
        return $this->hasMany('App\Comment');
    }
    
    public function purchase_rating()
    {
        return $this->hasOne('App\PurchaseRating');
    }

    public function getLikeCountAttribute()
    {
        return $this->likes->count();
    }

    public function getCommentCountAttribute()
    {
        return $this->comments->count();
    }

    public function getUserDataAttribute()
    {
        return $this->user()->get();
    }
    
    public function getPicturesAttribute()
    {
        if ($this->attributes['pictures']) {
            $pictures = [];
            foreach (unserialize($this->attributes['pictures']) as $picture) {
                $pictures[] = URL::to("/images/{$picture}");
            }

            return $pictures;
        }

        return [URL::to('/images/default_item.jpg')];
    }
    
    public function isAvailable()
    {
        return $this->attributes['status'] == config('constant.ITEM_STATUS.available');
    }
}
