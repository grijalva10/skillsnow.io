<?php

namespace App;

use Event;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use willvincent\Rateable\Rateable;

class Restaurant extends Model implements Sortable
{
    use Rateable, SortableTrait;

    /**
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    /**
     * @var array
     */
    protected $casts = ['is_active' => 'integer', 'is_accepted' => 'integer', 'is_featured' => 'integer', 'delivery_type' => 'integer', 'delivery_radius' => 'integer', 'base_delivery_distance' => 'integer', 'extra_delivery_distance' => 'integer'];

    /**
     * @var array
     */
    protected $hidden = array('created_at', 'updated_at');

    public static function boot()
    {
        parent::boot();

        static::created(function ($restaurant) {
            Event::dispatch('store.created', $restaurant);
        });

        static::updated(function ($restaurant) {
            Event::dispatch('store.updated', $restaurant);
        });

        static::deleted(function ($restaurant) {
            Event::dispatch('store.deleted', $restaurant);
        });
    }

    /**
     * @return mixed
     */
    public function location()
    {
        return $this->belongsTo('App\Location');
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
    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    /**
     * @return mixed
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return mixed
     */
    public function restaurant_categories()
    {
        return $this->belongsToMany('App\RestaurantCategory', 'restaurant_category_restaurant');
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
    public function toggleAcceptance()
    {
        $this->is_accepted = !$this->is_accepted;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        $this->where('is_active', 1);
        return $this;
    }
    /**
     * @return mixed
     */
    public function isNotActive()
    {
        $this->where('is_active', 0);
        return $this;
    }

    /**
     * @return mixed
     */
    public function delivery_areas()
    {
        if (class_exists("\Modules\DeliveryAreaPro\Entities\DeliveryArea")) {
            return $this->belongsToMany(\Modules\DeliveryAreaPro\Entities\DeliveryArea::class);
        }
        return $this->belongsToMany(User::class);
    }
}
