<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'master_category_id',
        'name'
    ];

    protected $appends = [
        'parent_category'
    ];

    protected $hidden = [
        'master_category_id',
        'created_at',
        'updated_at'
    ];

    public function getParentCategoryAttribute()
    {
        return config("constant.ITEM_CATEGORIES.{$this->attributes['master_category_id']}");
    }
}
