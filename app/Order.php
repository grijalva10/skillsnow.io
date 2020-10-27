<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * @var array
     */
    protected $casts = ['user_id' => 'integer', 'orderstatus_id' => 'integer', 'restaurant_charge' => 'float', 'total' => 'float'];

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
    public function orderstatus()
    {
        return $this->belongsTo('App\Orderstatus');
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
    public function orderitems()
    {
        return $this->hasMany('App\Orderitem');
    }

    /**
     * @return mixed
     */
    public function gpstable()
    {
        return $this->hasOne('App\GpsTable');
    }

    /**
     * @return mixed
     */
    public function accept_delivery()
    {
        return $this->hasOne('App\AcceptDelivery');
    }

}
