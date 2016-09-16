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
        'category_data',
        'brand_data',
        'condition_data',
        'like_count',
        'comment_count',
        'user_data',
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
    
    public function condition()
    {
        return $this->hasOne('App\Condition', 'id', 'condition_id');
    }

    public function brand()
    {
        return $this->hasOne('App\Brand', 'id', 'brand_id');
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
        if (isset($this->attributes['pictures'])) {
            $pictures = [];
            foreach (unserialize($this->attributes['pictures']) as $picture) {
                $pictures[] = URL::to("/images/items/{$this->id}/{$picture}");
            }

            return $pictures;
        }

        return [URL::to('/images/default_item.jpg')];
    }

    public function getCategoryDataAttribute()
    {
        return $this->category()->get();
    }

    public function getConditionDataAttribute()
    {
        return $this->condition()->get();
    }

    public function getBrandDataAttribute()
    {
        return $this->brand()->get();
    }
    
    public function isAvailable()
    {
        return $this->attributes['status'] == config('constant.ITEM_STATUS.available');
    }

    /**
     * @param array $excludes
     * @return array
     */
    public function getReturn($excludes = array())
    {
        $data = [
            'id'                => $this->id,
            'category'          => $this->category,
            'brand'             => $this->brand,
            'name'              => $this->name,
            'description'       => $this->description,
            'price'             => $this->price,
            'pictures'          => $this->pictures,
            'condition'         => $this->condition,
            'size'              => $this->size,
            'shipping_fee'      => $this->shipping_fee,
            'ships_from'        => $this->ships_from,
            'shipping_duration' => $this->shipping_duration,
            'status'            => $this->status,
            'likes'             => $this->likes,
            'comments'          => $this->comments,
            'user'              => $this->user,
            'created_at'        => $this->attributes['created_at'],
            'updated_at'        => $this->attributes['updated_at'],
        ];
        
        if (!$excludes) return $data;
        
        foreach ($excludes as $exclude) {
            unset($data[$exclude]);
        }

        return $data;
    }
}
