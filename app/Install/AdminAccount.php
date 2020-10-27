<?php

namespace App\Install;

use App\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class AdminAccount
{
    /**
     * @param $data
     */
    public function setup($data)
    {
        //creating roles
        $roleAdmin = Role::create(['name' => 'Admin']);

        $roleDeliveryGuy = Role::create(['name' => 'Delivery Guy']);

        $roleRestaurantOwner = Role::create(['name' => 'Store Owner']);

        $customer = Role::create(['name' => 'Customer']);

        //create admin account
        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'delivery_pin' => strtoupper(str_random(5)),
        ]);

        $admin->assignRole('Admin');
    }
}
