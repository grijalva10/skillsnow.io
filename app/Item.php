<?php

namespace App;

use Event;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /**
     * @var array
     */
    protected $casts = ['is_recommended' => 'integer', 'is_popular' => 'integer', 'is_new' => 'integer', 'is_veg' => 'integer', 'is_active' => 'integer'];

    /**
     * @var array
     */
    protected $hidden = array('created_at', 'updated_at');

    public static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            Event::dispatch('item.created', $item);
        });

        static::updated(function ($item) {
            Event::dispatch('item.updated', $item);
        });

        static::deleted(function ($item) {
            Event::dispatch('item.deleted', $item);
        });
    }

    /**
     * @return mixed
     */
    public function restaurant()
    {
        return $this->belongsTo('App\Restaurant');
    }

    /**
     * @return mixed
     */
    public function item_category()
    {
        return $this->belongsTo('App\ItemCategory');
    }

    /**
     * @return mixed
     */
    public function toggleActive()
    {
        $this->is_active = !$this->is_active;
        return $this;
    }

    /**
     * @return mixed
     */
    public function addon_categories()
    {
        return $this->belongsToMany(AddonCategory::class);
    }

}
