<?php

namespace App;

use Event;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    /**
     * @var array
     */
    protected $hidden = array('created_at', 'updated_at');

    public static function boot()
    {
        parent::boot();

        static::created(function ($itemcategory) {
            Event::dispatch('itemcategory.created', $itemcategory);
        });

        static::updated(function ($itemcategory) {
            Event::dispatch('itemcategory.updated', $itemcategory);
        });

        static::deleted(function ($itemcategory) {
            Event::dispatch('itemcategory.deleted', $itemcategory);
        });
    }

    /**
     * @return mixed
     */
    public function items()
    {
        return $this->hasMany('App\Item');
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * @return mixed
     */
    public function toggleEnable()
    {
        $this->is_enabled = !$this->is_enabled;
        return $this;
    }
}
