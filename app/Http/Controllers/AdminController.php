<?php

namespace App\Http\Controllers;

use App\AcceptDelivery;
use App\Addon;
use App\AddonCategory;
use App\DeliveryGuyDetail;
use App\Helpers\TranslationHelper;
use App\Http\Middleware\SCLC;
use App\Http\Middleware\SCLCC;
use App\Http\Middleware\SelfHelpM;
use App\Item;
use App\ItemCategory;
use App\Order;
use App\Orderstatus;
use App\Page;
use App\PaymentGateway;
use App\PopularGeoPlace;
use App\PromoSlider;
use App\PushNotify;
use App\Restaurant;
use App\RestaurantCategory;
use App\RestaurantPayout;
use App\Setting;
use App\Slide;
use App\Sms;
use App\SmsGateway;
use App\Translation;
use App\User;
use Artisan;
use Auth;
use Bavix\Wallet\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Image;
use Ixudra\Curl\Facades\Curl;
use Omnipay\Omnipay;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{

    public function __construct()
    {
        // $this->middleware(SCLC::class);
        // $this->middleware(SCLCC::class);
        // $this->middleware(SelfHelpM::class);
    }

    /**
     * @return mixed
     */
    public function dashboard(Request $request)
    {
        $displayUsers = User::count();

        $displayRestaurants = Restaurant::count();

        $displaySales = Order::where('orderstatus_id', 5)->get();
        $displayEarnings = $displaySales;

        $displaySales = count($displaySales);

        $total = 0;
        foreach ($displayEarnings as $de) {
            $total += $de->total;
        }
        $displayEarnings = $total;

        $orders = Order::orderBy('id', 'DESC')->with('orderstatus')->take(10)->get();

        $users = User::orderBy('id', 'DESC')->with('roles')->take(10)->get();

        $todaysDate = Carbon::now()->format('Y-m-d');

        $orderStatusesName = '[';

        $orderStatuses = Orderstatus::get(['name'])
            ->pluck('name')
            ->toArray();
        foreach ($orderStatuses as $key => $value) {
            $orderStatusesName .= "'" . $value . "' ,";
        }
        $orderStatusesName = rtrim($orderStatusesName, ' ,');
        $orderStatusesName = $orderStatusesName . ']';

        $orderStatusOrders = Order::all();
        $ifAnyOrders = $orderStatusOrders->count();
        if ($ifAnyOrders == 0) {
            $ifAnyOrders = false;
        } else {
            $ifAnyOrders = true;
        }

        $orderStatusOrders = $orderStatusOrders->groupBy('orderstatus_id')->map(function ($orderCount) {
            return $orderCount->count();
        });

        $orderStatusesData = '[';
        foreach ($orderStatusOrders as $key => $value) {
            if ($key == 1) {
                $orderStatusesData .= '{value:' . $value . ", name: 'Order Placed'},";
            }
            if ($key == 2) {
                $orderStatusesData .= '{value:' . $value . ", name: 'Preparing Order'},";
            }
            if ($key == 3) {
                $orderStatusesData .= '{value:' . $value . ", name: 'Delivery Guy Assigned'},";
            }
            if ($key == 4) {
                $orderStatusesData .= '{value:' . $value . ", name: 'Order Picked Up'},";
            }
            if ($key == 5) {
                $orderStatusesData .= '{value:' . $value . ", name: 'Delivered'},";
            }
            if ($key == 6) {
                $orderStatusesData .= '{value:' . $value . ", name: 'Canceled'},";
            }
        }
        $orderStatusesData = rtrim($orderStatusesData, ',');
        $orderStatusesData .= ']';

        return view('admin.dashboard', array(
            'displayUsers' => $displayUsers,
            'displayRestaurants' => $displayRestaurants,
            'displaySales' => $displaySales,
            'displayEarnings' => $displayEarnings,
            'orders' => $orders,
            'users' => $users,
            'todaysDate' => $todaysDate,
            'orderStatusesName' => $orderStatusesName,
            'orderStatusesData' => $orderStatusesData,
            'ifAnyOrders' => $ifAnyOrders,
        ));
    }

    public function users()
    {

        $users = User::orderBy('id', 'DESC')->with('roles', 'wallet')->paginate(20);
        $count = $users->total();

        $roles = Role::all();
        return view('admin.users', array(
            'count' => $count,
            'users' => $users,
            'roles' => $roles,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewUser(Request $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'delivery_pin' => strtoupper(str_random(5)),
                'password' => \Hash::make($request->password),
            ]);

            if ($request->has('role')) {
                $user->assignRole($request->role);
            }

            if ($user->hasRole('Delivery Guy')) {

                $deliveryGuyDetails = new DeliveryGuyDetail();
                $deliveryGuyDetails->name = $request->delivery_name;
                $deliveryGuyDetails->age = $request->delivery_age;
                if ($request->hasFile('delivery_photo')) {
                    $photo = $request->file('delivery_photo');
                    $filename = time() . str_random(10) . '.' . strtolower($photo->getClientOriginalExtension());
                    Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
                    $deliveryGuyDetails->photo = $filename;
                }
                $deliveryGuyDetails->description = $request->delivery_description;
                $deliveryGuyDetails->vehicle_number = $request->delivery_vehicle_number;
                if ($request->delivery_commission_rate != null) {
                    $deliveryGuyDetails->commission_rate = $request->delivery_commission_rate;
                }
                $deliveryGuyDetails->save();
                $user->delivery_guy_detail_id = $deliveryGuyDetails->id;
                $user->save();

            }

            return redirect()->back()->with(['success' => 'User Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param Request $request
     */
    public function postSearchUsers(Request $request)
    {
        $query = $request['query'];

        $users = User::where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('email', 'LIKE', '%' . $query . '%')
            ->with('roles', 'wallet')
            ->paginate(20);

        $roles = Role::all();

        $count = $users->total();

        return view('admin.users', array(
            'users' => $users,
            'query' => $query,
            'count' => $count,
            'roles' => $roles,
        ));
    }

    /**
     * @param $id
     */
    // public function getEditUser($id)
    // {
    //     $user = User::where('id', $id)->first();
    //     $roles = Role::get();
    //     // dd($user->delivery_guy_detail);
    //     return view('admin.editUser', array(
    //         'user' => $user,
    //         'roles' => $roles,
    //     ));
    // }
    public function getEditUser($id)
    {
        $user = User::where('id', $id)->with('orders')->first();
        $roles = Role::get();

        // dd($user->delivery_guy_detail);
        return view('admin.editUser', array(
            'orders' => $user->orders,
            'user' => $user,
            'roles' => $roles,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateUser(Request $request)
    {
        // dd($request->all());
        $user = User::where('id', $request->id)->first();
        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            if ($request->has('password') && $request->password != null) {
                $user->password = \Hash::make($request->password);
            }
            if ($request->roles != null) {
                $user->syncRoles($request->roles);
            }
            $user->save();

            if ($user->hasRole('Delivery Guy')) {

                if ($user->delivery_guy_detail == null) {

                    $deliveryGuyDetails = new DeliveryGuyDetail();
                    $deliveryGuyDetails->name = $request->delivery_name;
                    $deliveryGuyDetails->age = $request->delivery_age;
                    if ($request->hasFile('delivery_photo')) {
                        $photo = $request->file('delivery_photo');
                        $filename = time() . str_random(10) . '.' . strtolower($photo->getClientOriginalExtension());
                        Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
                        $deliveryGuyDetails->photo = $filename;
                    }
                    $deliveryGuyDetails->description = $request->delivery_description;
                    $deliveryGuyDetails->vehicle_number = $request->delivery_vehicle_number;

                    if ($request->delivery_commission_rate != null) {
                        $deliveryGuyDetails->commission_rate = $request->delivery_commission_rate;
                    }

                    if ($request->is_notifiable == 'true') {
                        $deliveryGuyDetails->is_notifiable = true;
                    } else {
                        $deliveryGuyDetails->is_notifiable = false;
                    }

                    if ($request->max_accept_delivery_limit != null) {
                        $deliveryGuyDetails->max_accept_delivery_limit = $request->max_accept_delivery_limit;
                    }

                    $deliveryGuyDetails->save();
                    $user->delivery_guy_detail_id = $deliveryGuyDetails->id;
                    $user->save();
                } else {
                    $user->delivery_guy_detail->name = $request->delivery_name;
                    $user->delivery_guy_detail->age = $request->delivery_age;
                    if ($request->hasFile('delivery_photo')) {
                        $photo = $request->file('delivery_photo');
                        $filename = time() . str_random(10) . '.' . strtolower($photo->getClientOriginalExtension());
                        Image::make($photo)->resize(250, 250)->save(base_path('/assets/img/delivery/' . $filename));
                        $user->delivery_guy_detail->photo = $filename;
                    }
                    $user->delivery_guy_detail->description = $request->delivery_description;
                    $user->delivery_guy_detail->vehicle_number = $request->delivery_vehicle_number;
                    if ($request->delivery_commission_rate != null) {
                        $user->delivery_guy_detail->commission_rate = $request->delivery_commission_rate;
                    }
                    if ($request->is_notifiable == 'true') {
                        $user->delivery_guy_detail->is_notifiable = true;
                    } else {
                        $user->delivery_guy_detail->is_notifiable = false;
                    }

                    if ($request->max_accept_delivery_limit != null) {
                        $user->delivery_guy_detail->max_accept_delivery_limit = $request->max_accept_delivery_limit;
                    }

                    $user->delivery_guy_detail->save();
                }
            }

            return redirect()->back()->with(['success' => 'User Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param $id
     */
    public function banUser($id)
    {
        $user = User::where('id', $id)->firstOrFail();
        $user->toggleActive()->save();
        return redirect()->back()->with(['success' => 'Operation Successful']);
    }

    public function manageDeliveryGuys()
    {
        $users = User::role('Delivery Guy')->orderBy('id', 'DESC')->with('roles')->paginate(20);
        $count = $users->total();
        return view('admin.manageDeliveryGuys', array(
            'users' => $users,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function getManageDeliveryGuysRestaurants($id)
    {
        $user = User::where('id', $id)->first();
        if ($user->hasRole('Delivery Guy')) {
            $userRestaurants = $user->restaurants;
            $userRestaurantsIds = $user->restaurants->pluck('id')->toArray();

            $allRestaurants = Restaurant::get();

            return view('admin.manageDeliveryGuysRestaurants', array(
                'user' => $user,
                'userRestaurants' => $userRestaurants,
                'allRestaurants' => $allRestaurants,
                'userRestaurantsIds' => $userRestaurantsIds,
            ));
        }
    }

    /**
     * @param Request $request
     */
    public function updateDeliveryGuysRestaurants(Request $request)
    {
        $user = User::where('id', $request->id)->first();
        $user->restaurants()->sync($request->user_restaurants);
        $user->save();
        return redirect()->back()->with(['success' => 'Delivery Guy Updated']);
    }

    public function manageRestaurantOwners()
    {
        $users = User::role('Store Owner')->orderBy('id', 'DESC')->with('roles')->paginate(20);
        $count = $users->total();

        return view('admin.manageRestaurantOwners', array(
            'users' => $users,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function getManageRestaurantOwnersRestaurants($id)
    {
        $user = User::where('id', $id)->first();
        if ($user->hasRole('Store Owner')) {
            $userRestaurants = $user->restaurants;
            $userRestaurantsIds = $user->restaurants->pluck('id')->toArray();
            $allRestaurants = Restaurant::get();

            return view('admin.manageRestaurantOwnersRestaurants', array(
                'user' => $user,
                'userRestaurants' => $userRestaurants,
                'allRestaurants' => $allRestaurants,
                'userRestaurantsIds' => $userRestaurantsIds,
            ));
        }
    }

    /**
     * @param Request $request
     */
    public function updateManageRestaurantOwnersRestaurants(Request $request)
    {
        $user = User::where('id', $request->id)->first();
        $user->restaurants()->sync($request->user_restaurants);
        $user->save();
        return redirect()->back()->with(['success' => 'Store Owner Updated']);
    }

    public function orders()
    {
        $orders = Order::orderBy('id', 'DESC')->with('accept_delivery.user', 'restaurant')->paginate(20);
        $count = $orders->total();
        return view('admin.orders', array(
            'orders' => $orders,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function postSearchOrders(Request $request)
    {
        $query = $request['query'];

        $orders = Order::where('unique_order_id', 'LIKE', '%' . $query . '%')->with('accept_delivery.user', 'restaurant')->paginate(20);

        $count = $orders->total();

        return view('admin.orders', array(
            'orders' => $orders,
            'count' => $count,
        ));
    }

    /**
     * @param $order_id
     */
    public function viewOrder($order_id)
    {
        $order = Order::where('unique_order_id', $order_id)->with('orderitems.order_item_addons')->first();
        $users = User::role('Delivery Guy')->get();
        if ($order) {
            return view('admin.viewOrder', array(
                'order' => $order,
                'users' => $users,
            ));
        } else {
            return redirect()->route('admin.orders');
        }
    }

    public function sliders()
    {
        $sliders = PromoSlider::orderBy('id', 'DESC')->with('slides')->get();
        $count = count($sliders);
        return view('admin.sliders', array(
            'sliders' => $sliders,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function getEditSlider($id)
    {
        $restaurants = Restaurant::get();
        $slider = PromoSlider::where('id', $id)->first();

        if ($slider) {
            return view('admin.editSlider', array(
                'restaurants' => $restaurants,
                'slider' => $slider,
                'slides' => $slider->slides,
            ));
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlider(Request $request)
    {
        $slider = PromoSlider::where('id', $request->id)->first();
        $slider->name = $request->name;
        $slider->position_id = $request->position_id;
        $slider->size = $request->size;
        $slider->save();

        return redirect()->back()->with(['success' => 'Slider Updated']);

    }
    /**
     * @param Request $request
     */
    public function createSlider(Request $request)
    {
        $sliderCount = PromoSlider::where('is_active', 1)->count();

        if ($sliderCount >= 2) {
            return redirect()->back()->with(['message' => 'Only two sliders can be created. Disbale or delete some Sliders to create more.']);
        }

        $slider = new PromoSlider();
        $slider->name = $request->name;
        $slider->location_id = '0';
        $slider->position_id = $request->position_id;
        $slider->size = $request->size;
        $slider->save();
        return redirect()->back()->with(['success' => 'New Slider Created']);
    }

    /**
     * @param $id
     */
    public function disableSlider($id)
    {
        $slider = PromoSlider::where('id', $id)->first();
        if ($slider) {
            $slider->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param $id
     */
    public function deleteSlider($id)
    {
        $slider = PromoSlider::where('id', $id)->first();
        if ($slider) {
            $slides = $slider->slides;
            foreach ($slides as $slide) {
                $slide->delete();
            }
            $slider->delete();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param Request $request
     */
    public function saveSlide(Request $request)
    {
        $url = url('/');
        $url = substr($url, 0, strrpos($url, '/')); //this will give url without "/public"

        $slide = new Slide();
        $slide->promo_slider_id = $request->promo_slider_id;
        $slide->name = $request->name;
        $slide->url = $request->url;

        $image = $request->file('image');
        $rand_name = time() . str_random(10);
        $filename = $rand_name . '.' . $image->getClientOriginalExtension();

        Image::make($image)
            ->resize(384, 384)
            ->save(base_path('assets/img/slider/' . $filename));
        $slide->image = '/assets/img/slider/' . $filename;

        if ($request->customUrl != null) {
            $slide->url = $request->customUrl;
        }

        $slide->save();

        return redirect()->back()->with(['success' => 'New Slide Created']);
    }

    /**
     * @param $id
     */
    public function editSlide($id)
    {
        $slide = Slide::where('id', $id)->with('promo_slider')->first();
        $restaurants = Restaurant::get();
        if ($slide) {
            return view('admin.editSlide', array(
                'slide' => $slide,
                'restaurants' => $restaurants,
            ));
        } else {
            return redirect()->route('admin.sliders')->with(['message' => 'Slide Not Found']);
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlide(Request $request)
    {
        $slide = Slide::where('id', $request->id)->first();
        if ($slide) {
            $slide->name = $request->name;
            if ($request->url != null) {
                $slide->url = $request->url;
            }
            if ($request->hasFile('image')) {

                $image = $request->file('image');
                $rand_name = time() . str_random(10);
                $filename = $rand_name . '.' . $image->getClientOriginalExtension();
                Image::make($image)
                    ->resize(384, 384)
                    ->save(base_path('assets/img/slider/' . $filename));
                $slide->image = '/assets/img/slider/' . $filename;
            }
            if ($request->customUrl != null) {
                $slide->url = $request->customUrl;
            }
            $slide->save();
            return redirect()->back()->with(['success' => 'Slide Updated']);
        } else {
            return redirect()->route('admin.sliders')->with(['message' => 'Slide Not Found']);
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlidePosition(Request $request)
    {
        Slide::setNewOrder($request->newOrder);
        Artisan::call('cache:clear');
        return response()->json(['success' => true]);
    }

    /**
     * @param $id
     */
    public function deleteSlide($id)
    {
        $slide = Slide::where('id', $id)->first();
        if ($slide) {
            $slide->delete();
            return redirect()->back()->with(['success' => 'Deleted']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    /**
     * @param $id
     */
    public function disableSlide($id)
    {
        $slide = Slide::where('id', $id)->first();
        if ($slide) {
            $slide->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.sliders');
        }
    }

    public function restaurants()
    {
        $restaurants = Restaurant::where('is_accepted', '1')->with('users.roles')->ordered()->paginate(20);
        $count = $restaurants->total();

        $dapCheck = false;
        if (\Module::find('DeliveryAreaPro') && \Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        return view('admin.restaurants', array(
            'restaurants' => $restaurants,
            'count' => $count,
            'dapCheck' => $dapCheck,
        ));
    }

    public function sortStores()
    {
        $restaurants = Restaurant::where('is_accepted', '1')->with('users.roles')->ordered()->get();
        $count = $restaurants->count();
        return view('admin.sortStores', array(
            'restaurants' => $restaurants,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateStorePosition(Request $request)
    {
        Restaurant::setNewOrder($request->newOrder);
        Artisan::call('cache:clear');
        return response()->json(['success' => true]);
    }

    public function pendingAcceptance()
    {
        $count = Restaurant::count();
        $restaurants = Restaurant::orderBy('id', 'DESC')->where('is_accepted', '0')->with('users.roles')->paginate(10000);
        $count = count($restaurants);

        $dapCheck = false;
        if (\Module::find('DeliveryAreaPro') && \Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        return view('admin.restaurants', array(
            'restaurants' => $restaurants,
            'count' => $count,
            'dapCheck' => $dapCheck,
        ));
    }

    /**
     * @param $id
     */
    public function acceptRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant) {
            $restaurant->toggleAcceptance()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.restaurants');
        }
    }

    /**
     * @param Request $request
     */
    public function searchRestaurants(Request $request)
    {
        $query = $request['query'];

        $restaurants = Restaurant::where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('sku', 'LIKE', '%' . $query . '%')->with('users.roles')->paginate(20);

        $count = $restaurants->total();

        $dapCheck = false;
        if (\Module::find('DeliveryAreaPro') && \Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        return view('admin.restaurants', array(
            'restaurants' => $restaurants,
            'query' => $query,
            'count' => $count,
            'dapCheck' => $dapCheck,
        ));
    }

    /**
     * @param $id
     */
    public function disableRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant) {
            $restaurant->is_schedulable = false;
            $restaurant->toggleActive();
            $restaurant->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.restaurants');
        }
    }

    /**
     * @param $id
     */
    public function deleteRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        if ($restaurant) {
            $items = $restaurant->items;
            foreach ($items as $item) {
                $item->delete();
            }
            $restaurant->delete();
            return redirect()->route('admin.restaurants');
        } else {
            return redirect()->route('admin.restaurants');
        }
    }

    /**
     * @param Request $request
     */
    public function saveNewRestaurant(Request $request)
    {
        $restaurant = new Restaurant();

        $restaurant->name = $request->name;
        $restaurant->description = $request->description;

        $image = $request->file('image');
        $rand_name = time() . str_random(10);
        $filename = $rand_name . '.jpg';
        Image::make($image)
            ->resize(160, 117)
            ->save(base_path('assets/img/restaurants/' . $filename), config('settings.uploadImageQuality '), 'jpg');
        $restaurant->image = '/assets/img/restaurants/' . $filename;

        $restaurant->rating = $request->rating;
        $restaurant->delivery_time = $request->delivery_time;
        $restaurant->price_range = $request->price_range;

        if ($request->is_pureveg == 'true') {
            $restaurant->is_pureveg = true;
        } else {
            $restaurant->is_pureveg = false;
        }

        if ($request->is_featured == 'true') {
            $restaurant->is_featured = true;
        } else {
            $restaurant->is_featured = false;
        }

        $restaurant->slug = str_slug($request->name) . '-' . str_random(15);
        $restaurant->certificate = $request->certificate;

        $restaurant->address = $request->address;
        $restaurant->pincode = $request->pincode;
        $restaurant->landmark = $request->landmark;
        $restaurant->latitude = $request->latitude;
        $restaurant->longitude = $request->longitude;

        $restaurant->restaurant_charges = $request->restaurant_charges;
        $restaurant->delivery_charges = $request->delivery_charges;
        $restaurant->commission_rate = $request->commission_rate;

        if ($request->has('delivery_type')) {
            $restaurant->delivery_type = $request->delivery_type;
        }

        if ($request->delivery_charge_type == 'FIXED') {
            $restaurant->delivery_charge_type = 'FIXED';
            $restaurant->delivery_charges = $request->delivery_charges;
        }
        if ($request->delivery_charge_type == 'DYNAMIC') {
            $restaurant->delivery_charge_type = 'DYNAMIC';
            $restaurant->base_delivery_charge = $request->base_delivery_charge;
            $restaurant->base_delivery_distance = $request->base_delivery_distance;
            $restaurant->extra_delivery_charge = $request->extra_delivery_charge;
            $restaurant->extra_delivery_distance = $request->extra_delivery_distance;
        }
        if ($request->delivery_radius != null) {
            $restaurant->delivery_radius = $request->delivery_radius;
        }

        $restaurant->sku = time() . str_random(10);
        $restaurant->is_active = 0;
        $restaurant->is_accepted = 1;

        $restaurant->min_order_price = $request->min_order_price;

        try {
            $restaurant->save();
            return redirect()->back()->with(['success' => 'Restaurant Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param $id
     */
    public function getRestaurantItems($id)
    {
        $items = Item::where('restaurant_id', $id)->paginate(20);

        $count = Item::where('restaurant_id', $id)->count();

        $restaurants = Restaurant::all();

        $itemCategories = ItemCategory::where('is_enabled', '1')->get();

        $addonCategories = AddonCategory::all();

        return view('admin.items', array(
            'items' => $items,
            'count' => $count,
            'restaurants' => $restaurants,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
        ));

    }
    /**
     * @param $id
     */
    public function getEditRestaurant($id)
    {
        $restaurant = Restaurant::where('id', $id)->first();
        $restaurantCategories = RestaurantCategory::where('is_active', '1')->get();

        $dapCheck = false;
        if (\Module::find('DeliveryAreaPro') && \Module::find('DeliveryAreaPro')->isEnabled()) {
            $dapCheck = true;
        }

        return view('admin.editRestaurant', array(
            'restaurant' => $restaurant,
            'restaurantCategories' => $restaurantCategories,
            'schedule_data' => json_decode($restaurant->schedule_data),
            'dapCheck' => $dapCheck,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateRestaurant(Request $request)
    {
        $restaurant = Restaurant::where('id', $request->id)->first();

        if ($restaurant) {
            $restaurant->name = $request->name;
            $restaurant->description = $request->description;

            if ($request->image == null) {
                $restaurant->image = $request->old_image;
            } else {

                $image = $request->file('image');
                $rand_name = time() . str_random(10);
                $filename = $rand_name . '.jpg';
                Image::make($image)
                    ->resize(160, 117)
                    ->save(base_path('assets/img/restaurants/' . $filename), config('settings.uploadImageQuality '), 'jpg');
                $restaurant->image = '/assets/img/restaurants/' . $filename;

            }

            $restaurant->rating = $request->rating;
            $restaurant->delivery_time = $request->delivery_time;
            $restaurant->price_range = $request->price_range;

            if ($request->is_pureveg == 'true') {
                $restaurant->is_pureveg = true;
            } else {
                $restaurant->is_pureveg = false;
            }

            if ($request->is_featured == 'true') {
                $restaurant->is_featured = true;
            } else {
                $restaurant->is_featured = false;
            }

            $restaurant->certificate = $request->certificate;

            $restaurant->address = $request->address;
            $restaurant->pincode = $request->pincode;
            $restaurant->landmark = $request->landmark;
            $restaurant->latitude = $request->latitude;
            $restaurant->longitude = $request->longitude;

            $restaurant->restaurant_charges = $request->restaurant_charges;
            $restaurant->delivery_charges = $request->delivery_charges;
            $restaurant->commission_rate = $request->commission_rate;

            if ($request->has('delivery_type')) {
                $restaurant->delivery_type = $request->delivery_type;
            }

            if ($request->delivery_charge_type == 'FIXED') {
                $restaurant->delivery_charge_type = 'FIXED';
                $restaurant->delivery_charges = $request->delivery_charges;
            }
            if ($request->delivery_charge_type == 'DYNAMIC') {
                $restaurant->delivery_charge_type = 'DYNAMIC';
                $restaurant->base_delivery_charge = $request->base_delivery_charge;
                $restaurant->base_delivery_distance = $request->base_delivery_distance;
                $restaurant->extra_delivery_charge = $request->extra_delivery_charge;
                $restaurant->extra_delivery_distance = $request->extra_delivery_distance;
            }
            if ($request->delivery_radius != null) {
                $restaurant->delivery_radius = $request->delivery_radius;
            }

            $restaurant->min_order_price = $request->min_order_price;

            if ($request->is_schedulable == 'true') {
                $restaurant->is_schedulable = true;
            } else {
                $restaurant->is_schedulable = false;
            }

            if ($request->is_notifiable == 'true') {
                $restaurant->is_notifiable = true;
            } else {
                $restaurant->is_notifiable = false;
            }

            if ($request->auto_acceptable == 'true') {
                $restaurant->auto_acceptable = true;
            } else {
                $restaurant->auto_acceptable = false;
            }

            $restaurant->custom_message = $request->custom_message;

            try {
                if (isset($request->restaurant_category_restaurant)) {
                    $restaurant->restaurant_categories()->sync($request->restaurant_category_restaurant);
                }
                $restaurant->save();

                try {

                    $restaurant->slug = Str::slug($request->store_url);
                    $restaurant->save();

                } catch (\Illuminate\Database\QueryException $qe) {
                    $errorCode = $qe->errorInfo[1];
                    if ($errorCode == 1062) {
                        return redirect()->back()->with(['message' => 'URL should be unique, it should not match with other store URLs']);
                    }
                    return redirect()->back()->with(['message' => $qe->getMessage()]);
                }
                // dd('here');
                // return redirect()->back()->with(['success' => 'Store Updated']);
                return redirect(route('admin.get.editRestaurant', $restaurant->id) . $request->window_redirect_hash)->with(['success' => 'Store Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function updateSlug(Request $request)
    {
        $restaurant = Restaurant::where('id', $request->id)->firstOrFail();

        try {

            $restaurant->slug = Str::slug($request->store_url);
            $restaurant->save();
            return redirect()->back()->with(['success' => 'URL Updated']);

        } catch (\Illuminate\Database\QueryException $qe) {
            $errorCode = $qe->errorInfo[1];
            if ($errorCode == 1062) {
                return redirect()->back()->with(['message' => 'URL should be unique, it should not match with other store URLs']);
            }
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }

    }

    public function items()
    {
        $items = Item::orderBy('id', 'DESC')->with('item_category', 'restaurant')->paginate(20);
        $count = $items->total();

        $restaurants = Restaurant::all();
        $itemCategories = ItemCategory::where('is_enabled', '1')->get();
        $addonCategories = AddonCategory::all();

        return view('admin.items', array(
            'items' => $items,
            'count' => $count,
            'restaurants' => $restaurants,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchItems(Request $request)
    {
        $query = $request['query'];

        $items = Item::where('name', 'LIKE', '%' . $query . '%')->with('item_category', 'restaurant')->paginate(20);
        $count = $items->total();

        $restaurants = Restaurant::get();
        $itemCategories = ItemCategory::where('is_enabled', '1')->get();
        $addonCategories = AddonCategory::all();

        return view('admin.items', array(
            'items' => $items,
            'count' => $count,
            'restaurants' => $restaurants,
            'query' => $query,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewItem(Request $request)
    {
        // dd($request->all());

        $item = new Item();

        $item->name = $request->name;
        $item->price = $request->price;
        $item->old_price = $request->old_price == null ? 0 : $request->old_price;
        $item->restaurant_id = $request->restaurant_id;
        $item->item_category_id = $request->item_category_id;

        $image = $request->file('image');
        $rand_name = time() . str_random(10);
        $filename = $rand_name . '.jpg';
        Image::make($image)
            ->resize(486, 355)
            ->save(base_path('assets/img/items/' . $filename), config('settings.uploadImageQuality '), 'jpg');
        $item->image = '/assets/img/items/' . $filename;

        if ($request->is_recommended == 'true') {
            $item->is_recommended = true;
        } else {
            $item->is_recommended = false;
        }

        if ($request->is_popular == 'true') {
            $item->is_popular = true;
        } else {
            $item->is_popular = false;
        }

        if ($request->is_new == 'true') {
            $item->is_new = true;
        } else {
            $item->is_new = false;
        }

        if ($request->is_veg == 'true') {
            $item->is_veg = true;
        } else {
            $item->is_veg = false;
        }

        $item->desc = $request->desc;

        try {
            $item->save();
            if (isset($request->addon_category_item)) {
                $item->addon_categories()->sync($request->addon_category_item);
            }
            return redirect()->back()->with(['success' => 'Item Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }

    }

    /**
     * @param $id
     */
    public function getEditItem($id)
    {
        $item = Item::where('id', $id)->first();
        $restaurants = Restaurant::get();
        $itemCategories = ItemCategory::where('is_enabled', '1')->get();
        $addonCategories = AddonCategory::all();

        return view('admin.editItem', array(
            'item' => $item,
            'restaurants' => $restaurants,
            'itemCategories' => $itemCategories,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param $id
     */
    public function disableItem($id)
    {
        $item = Item::where('id', $id)->first();
        if ($item) {
            $item->toggleActive()->save();
            if (\Illuminate\Support\Facades\Request::ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.items');
        }
    }

    /**
     * @param Request $request
     */
    public function updateItem(Request $request)
    {
        $item = Item::where('id', $request->id)->first();

        if ($item) {

            $item->name = $request->name;
            $item->restaurant_id = $request->restaurant_id;
            $item->item_category_id = $request->item_category_id;

            if ($request->image == null) {
                $item->image = $request->old_image;
            } else {
                $image = $request->file('image');
                $rand_name = time() . str_random(10);
                $filename = $rand_name . '.jpg';
                Image::make($image)
                    ->resize(486, 355)
                    ->save(base_path('assets/img/items/' . $filename), config('settings.uploadImageQuality '), 'jpg');
                $item->image = '/assets/img/items/' . $filename;
            }

            $item->price = $request->price;
            $item->old_price = $request->old_price == null ? 0 : $request->old_price;

            if ($request->is_recommended == 'true') {
                $item->is_recommended = true;
            } else {
                $item->is_recommended = false;
            }

            if ($request->is_popular == 'true') {
                $item->is_popular = true;
            } else {
                $item->is_popular = false;
            }

            if ($request->is_new == 'true') {
                $item->is_new = true;
            } else {
                $item->is_new = false;
            }

            if ($request->is_veg == 'true') {
                $item->is_veg = true;
            } else {
                $item->is_veg = false;
            }

            $item->desc = $request->desc;

            try {
                $item->save();
                if (isset($request->addon_category_item)) {
                    $item->addon_categories()->sync($request->addon_category_item);
                }
                if ($request->remove_all_addons == '1') {
                    $item->addon_categories()->sync($request->addon_category_item);
                }
                return redirect()->back()->with(['success' => 'Item Updated']);

            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        }
    }

    public function addonCategories()
    {
        $addonCategories = AddonCategory::orderBy('id', 'DESC')->paginate(20);
        $addonCategories->loadCount('addons');

        $count = $addonCategories->total();

        return view('admin.addonCategories', array(
            'addonCategories' => $addonCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchAddonCategories(Request $request)
    {
        $query = $request['query'];

        $addonCategories = AddonCategory::where('name', 'LIKE', '%' . $query . '%')->paginate(20);
        $addonCategories->loadCount('addons');

        $count = $addonCategories->total();

        return view('admin.addonCategories', array(
            'addonCategories' => $addonCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewAddonCategory(Request $request)
    {
        $addonCategory = new AddonCategory();

        $addonCategory->name = $request->name;
        $addonCategory->type = $request->type;
        $addonCategory->description = $request->description;
        $addonCategory->user_id = Auth::user()->id;

        try {
            $addonCategory->save();
            if ($request->has('addon_names')) {
                foreach ($request->addon_names as $key => $addon_name) {
                    $addon = new Addon();
                    $addon->name = $addon_name;
                    $addon->price = $request->addon_prices[$key];
                    $addon->user_id = Auth::user()->id;
                    $addon->addon_category_id = $addonCategory->id;
                    $addon->save();
                }
            }
            return redirect()->route('admin.editAddonCategory', $addonCategory->id)->with(['success' => 'Addon Category Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    public function newAddonCategory()
    {
        return view('admin.newAddonCategory');
    }

    /**
     * @param $id
     */
    public function deleteAddon($id)
    {
        $addon = Addon::find($id);
        $addon->delete();

        return redirect()->back()->with(['success' => 'Addon Deleted']);
    }

    /**
     * @param $id
     */
    public function getEditAddonCategory($id)
    {
        $addonCategory = AddonCategory::where('id', $id)->with('addons')->first();

        return view('admin.editAddonCategory', array(
            'addonCategory' => $addonCategory,
            'addons' => $addonCategory->addons,
        ));

    }

    /**
     * @param Request $request
     */
    public function updateAddonCategory(Request $request)
    {
        $addonCategory = AddonCategory::where('id', $request->id)->first();

        if ($addonCategory) {

            $addonCategory->name = $request->name;
            $addonCategory->type = $request->type;
            $addonCategory->description = $request->description;

            try {
                $addonCategory->save();
                $addons_old = $request->input('addon_old');

                if ($request->has('addons_old')) {
                    foreach ($addons_old as $ad) {
                        $addon_old_update = Addon::find($ad['id']);
                        $addon_old_update->name = $ad['name'];
                        $addon_old_update->price = $ad['price'];
                        $addon_old_update->user_id = Auth::user()->id;
                        $addon_old_update->save();
                    }
                }

                if ($request->addon_names) {
                    foreach ($request->addon_names as $key => $addon_name) {
                        $addon = new Addon();
                        $addon->name = $addon_name;
                        $addon->price = $request->addon_prices[$key];
                        $addon->addon_category_id = $addonCategory->id;
                        $addon->user_id = Auth::user()->id;
                        $addon->save();
                    }
                }

                return redirect()->back()->with(['success' => 'Addon Category Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        }
    }

    public function addons()
    {

        $addons = Addon::orderBy('id', 'DESC')->with('addon_category')->paginate(20);
        $count = $addons->total();

        $addonCategories = AddonCategory::all();

        return view('admin.addons', array(
            'addons' => $addons,
            'count' => $count,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function searchAddons(Request $request)
    {
        $query = $request['query'];

        $addons = Addon::where('name', 'LIKE', '%' . $query . '%')->with('addon_category')->paginate(20);

        $count = $addons->total();

        $addonCategories = AddonCategory::all();

        return view('admin.addons', array(
            'addons' => $addons,
            'count' => $count,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewAddon(Request $request)
    {
        // dd($request->all());
        $addon = new Addon();

        $addon->name = $request->name;
        $addon->price = $request->price;
        $addon->user_id = Auth::user()->id;
        $addon->addon_category_id = $request->addon_category_id;

        try {
            $addon->save();
            return redirect()->back()->with(['success' => 'Addon Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }

    }

    /**
     * @param $id
     */
    public function getEditAddon($id)
    {
        $addon = Addon::where('id', $id)->first();
        $addonCategories = AddonCategory::all();
        return view('admin.editAddon', array(
            'addon' => $addon,
            'addonCategories' => $addonCategories,
        ));
    }

    /**
     * @param Request $request
     */
    public function updateAddon(Request $request)
    {
        $addon = Addon::where('id', $request->id)->first();

        if ($addon) {

            $addon->name = $request->name;
            $addon->price = $request->price;
            $addon->addon_category_id = $request->addon_category_id;

            try {
                $addon->save();
                return redirect()->back()->with(['success' => 'Addon Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        }
    }

    /**
     * @param $id
     */
    public function disableAddon($id)
    {
        $addon = Addon::where('id', $id)->firstOrFail();
        if ($addon) {
            $addon->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->back()->with(['message' => 'Something Went Wrong']);
        }
    }

    /**
     * @param $id
     */
    public function addonsOfAddonCategory($id)
    {
        $addons = Addon::orderBy('id', 'DESC')->where('addon_category_id', $id)->with('addon_category')->paginate(20);
        $count = $addons->total();
        $addonCategories = AddonCategory::all();

        return view('admin.addons', array(
            'addons' => $addons,
            'count' => $count,
            'addonCategories' => $addonCategories,
        ));
    }

    public function itemcategories()
    {
        $itemCategories = ItemCategory::orderBy('id', 'DESC')->with('user')->get();
        $itemCategories->loadCount('items');

        $count = count($itemCategories);

        return view('admin.itemcategories', array(
            'itemCategories' => $itemCategories,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function createItemCategory(Request $request)
    {
        $itemCategory = new ItemCategory();

        $itemCategory->name = $request->name;
        $itemCategory->user_id = Auth::user()->id;

        try {
            $itemCategory->save();
            return redirect()->back()->with(['success' => 'Category Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param $id
     */
    public function disableCategory($id)
    {
        $itemCategory = ItemCategory::where('id', $id)->first();
        if ($itemCategory) {
            $itemCategory->toggleEnable()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.itemcategories');
        }
    }

    /**
     * @param Request $request
     */
    public function updateItemCategory(Request $request)
    {
        $itemCategory = ItemCategory::where('id', $request->id)->firstOrFail();
        $itemCategory->name = $request->name;
        $itemCategory->save();
        return redirect()->back()->with(['success' => 'Operation Successful']);
    }

    public function pages()
    {
        $pages = Page::all();
        return view('admin.pages', array(
            'pages' => $pages,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewPage(Request $request)
    {
        $page = new Page();
        $page->name = $request->name;
        $page->slug = Str::slug($request->slug, '-');
        $page->body = $request->body;

        try {
            $page->save();
            return redirect()->back()->with(['success' => 'New Page Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param $id
     */
    public function getEditPage($id)
    {
        $page = Page::where('id', $id)->first();

        if ($page) {
            return view('admin.editPage', array(
                'page' => $page,
            ));
        } else {
            return redirect()->route('admin.pages');
        }
    }

    /**
     * @param Request $request
     */
    public function updatePage(Request $request)
    {
        $page = Page::where('id', $request->id)->first();

        if ($page) {
            $page->name = $request->name;
            $page->slug = Str::slug($request->slug, '-');
            $page->body = $request->body;
            try {
                $page->save();
                return redirect()->back()->with(['success' => 'Page Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        } else {
            return redirect()->route('admin.pages');
        }
    }

    /**
     * @param $id
     */
    public function deletePage($id)
    {
        $page = Page::where('id', $id)->first();
        if ($page) {
            $page->delete();
            return redirect()->back()->with(['success' => 'Deleted']);
        } else {
            return redirect()->route('admin.pages');
        }
    }

    public function restaurantpayouts()
    {
        $count = RestaurantPayout::count();

        $restaurantPayouts = RestaurantPayout::paginate(20);

        return view('admin.restaurantPayouts', array(
            'restaurantPayouts' => $restaurantPayouts,
            'count' => $count,
        ));
    }

    /**
     * @param $id
     */
    public function viewRestaurantPayout($id)
    {
        $restaurantPayout = RestaurantPayout::where('id', $id)->first();

        if ($restaurantPayout) {
            return view('admin.viewRestaurantPayout', array(
                'restaurantPayout' => $restaurantPayout,
            ));
        }
    }

    /**
     * @param Request $request
     */
    public function updateRestaurantPayout(Request $request)
    {
        $restaurantPayout = RestaurantPayout::where('id', $request->id)->first();

        if ($restaurantPayout) {
            $restaurantPayout->status = $request->status;
            $restaurantPayout->transaction_mode = $request->transaction_mode;
            $restaurantPayout->transaction_id = $request->transaction_id;
            $restaurantPayout->message = $request->message;
            try {
                $restaurantPayout->save();
                return redirect()->back()->with(['success' => 'Restaurant Payout Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }

        }
    }

    /**
     * @return mixed
     */
    /**
     * @param $duplicate
     */
    public function fixUpdateIssues()
    {
        try {

            $duplicates = AcceptDelivery::whereIn('order_id', function ($query) {
                $query->select('order_id')->from('accept_deliveries')->groupBy('order_id')->havingRaw('count(*) > 1');
            })->get();

            foreach ($duplicates as $duplicate) {

                if ($duplicate->is_completed == 0 && ($duplicate->order->orderstatus_id == 5 || $duplicate->order->orderstatus_id == 6)) {
                    //just delete
                    $duplicate->delete(); //delete the duplicate entry in db
                }

                if ($duplicate->is_completed == 0 && $duplicate->order->orderstatus_id == 3) {
                    //delete and change orderstatus to 2
                    $duplicate->order->orderstatus_id = 2; //change order status to not delivery assigned
                    $duplicate->order->save(); //save the order
                    $duplicate->delete(); //delete the duplicate entry in db
                }

            }

            // ** MIGRATE ** //
            //first migrate the db if any new db are avaliable...
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            // ** MIGRATE END ** //

            // ** SETTINGS ** //
            // get the latest settings json file from the server...
            $data = Curl::to('https://stackcanyon.com/products/foodoma/updates/settings.json')->get();
            $data = json_decode($data);
            if (!$data) {
                $data = file_get_contents(storage_path('data/data.json'));
                $data = json_decode($data);
            }
            $dbSet = [];
            foreach ($data as $s) {
                //check if the setting key already exists, if exists, do nothing..
                $settingAlreadyExists = Setting::where('key', $s->key)->first();
                //else create an array of settings which doesnt exists...
                if (!$settingAlreadyExists) {
                    $dbSet[] = [
                        'key' => $s->key,
                        'value' => $s->value,
                    ];
                }
            }
            //insert new settings keys into settings table.
            DB::table('settings')->insert($dbSet);
            // ** SETTINGS END ** //

            // ** PAYMENTGATEWAYS ** //
            // check if paystack is installed
            $hasPayStack = PaymentGateway::where('name', 'PayStack')->first();
            if (!$hasPayStack) {
                //if not, then install new payment gateway "PayStack"
                $payStackPaymentGateway = new PaymentGateway();
                $payStackPaymentGateway->name = 'PayStack';
                $payStackPaymentGateway->description = 'PayStack Payment Gateway';
                $payStackPaymentGateway->is_active = 0;
                $payStackPaymentGateway->save();
            }
            // check if razorpay is installed
            $hasRazorPay = PaymentGateway::where('name', 'Razorpay')->first();
            if (!$hasRazorPay) {
                //if not, then install new payment gateway "Razorpay"
                $razorPayPaymentGateway = new PaymentGateway();
                $razorPayPaymentGateway->name = 'Razorpay';
                $razorPayPaymentGateway->description = 'Razorpay Payment Gateway';
                $razorPayPaymentGateway->is_active = 0;
                $razorPayPaymentGateway->save();
            }
            // ** END PAYMENTGATEWAYS ** //

            $hasPayMongo = PaymentGateway::where('name', 'PayMongo')->first();
            if (!$hasPayMongo) {
                //if not, then install new payment gateway "PayMongo"
                $payMongoPaymentGateway = new PaymentGateway();
                $payMongoPaymentGateway->name = 'PayMongo';
                $payMongoPaymentGateway->description = 'PayMongo Payment Gateway';
                $payMongoPaymentGateway->is_active = 0;
                $payMongoPaymentGateway->save();
            }

            $hasMercadoPago = PaymentGateway::where('name', 'MercadoPago')->first();
            if (!$hasMercadoPago) {
                //if not, then install new payment gateway "MercadoPago"
                $mercadoPagoPaymentGateway = new PaymentGateway();
                $mercadoPagoPaymentGateway->name = 'MercadoPago';
                $mercadoPagoPaymentGateway->description = 'MercadoPago payment gateway';
                $mercadoPagoPaymentGateway->is_active = 0;
                $mercadoPagoPaymentGateway->save();
            }

            $hasMsg91 = SmsGateway::where('gateway_name', 'MSG91')->first();
            if (!$hasMsg91) {
                //if not, then install new sms gateway gateway "MSG91"
                $msg91Gateway = new SmsGateway();
                $msg91Gateway->gateway_name = 'MSG91';
                $msg91Gateway->save();
            }

            $hasTwilio = SmsGateway::where('gateway_name', 'TWILIO')->first();
            if (!$hasTwilio) {
                //if not, then install new sms gateway gateway "TWILIO"
                $twilioGateway = new SmsGateway();
                $twilioGateway->gateway_name = 'TWILIO';
                $twilioGateway->save();
            }

            // ** ORDERSTATUS ** //
            // check if ready status is inserted
            $hasReadyOrderStatus = Orderstatus::where('id', '7')->first();
            if (!$hasReadyOrderStatus) {
                //if not, then insert it.
                $orderStatus = new Orderstatus();
                $orderStatus->name = 'Ready For Pick Up';
                $orderStatus->save();
            }
            // ** END ORDERSTATUS ** //
            $hasAwaitingPayment = Orderstatus::where('id', '8')->first();
            if (!$hasAwaitingPayment) {
                //if not, then insert it.
                $orderStatus = new Orderstatus();
                $orderStatus->name = 'Awaiting Payment';
                $orderStatus->save();
            }
            $hasPaymentFailed = Orderstatus::where('id', '9')->first();
            if (!$hasPaymentFailed) {
                //if not, then insert it.
                $orderStatus = new Orderstatus();
                $orderStatus->name = 'Payment Failed';
                $orderStatus->save();
            }

            // ** CHANGEURL ** //
            $jsFiles = glob(base_path('static/js') . '/*');
            foreach ($jsFiles as $file) {
                //read the entire string
                $str = file_get_contents($file);
                $baseUrl = substr(url('/'), 0, strrpos(url('/'), '/'));
                //replace string
                $str = str_replace('http://127.0.0.1/swiggy-laravel-react', $baseUrl, $str);
                //write the entire string
                file_put_contents($file, $str);
            }

            /** CLEAR LARAVEL CACHES **/
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            /** END CLEAR LARAVEL CACHES **/

            return redirect()->back()->with(['success' => 'Operation Successful']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }

    }

    /**
     * @param Request $request
     */
    public function addMoneyToWallet(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();

        if ($user) {
            try {
                $user->deposit($request->add_amount * 100, ['description' => $request->add_amount_description]);

                $alert = new PushNotify();
                $alert->sendWalletAlert($request->user_id, $request->add_amount, $request->add_amount_description, $type = 'deposit');

                return redirect()->back()->with(['success' => config('settings.walletName') . ' Updated']);
            } catch (\Illuminate\Database\QueryException $qe) {
                return redirect()->back()->with(['message' => $qe->getMessage()]);
            } catch (Exception $e) {
                return redirect()->back()->with(['message' => $e->getMessage()]);
            } catch (\Throwable $th) {
                return redirect()->back()->with(['message' => $th]);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function substractMoneyFromWallet(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();

        if ($user) {
            if ($user->balanceFloat * 100 >= $request->substract_amount * 100) {
                try {
                    $user->withdraw($request->substract_amount * 100, ['description' => $request->substract_amount_description]);

                    $alert = new PushNotify();
                    $alert->sendWalletAlert($request->user_id, $request->substract_amount, $request->substract_amount_description, $type = 'withdraw');

                    return redirect()->back()->with(['success' => config('settings.walletName') . ' Updated']);
                } catch (\Illuminate\Database\QueryException $qe) {
                    return redirect()->back()->with(['message' => $qe->getMessage()]);
                } catch (Exception $e) {
                    return redirect()->back()->with(['message' => $e->getMessage()]);
                } catch (\Throwable $th) {
                    return redirect()->back()->with(['message' => $th]);
                }
            } else {
                return redirect()->back()->with(['message' => 'Substract amount is less that the user balance amount.']);
            }

        }
    }

    public function walletTransactions()
    {
        $count = $transactions = Transaction::count();

        $transactions = Transaction::paginate(20);

        return view('admin.viewAllWalletTransactions', array(
            'transactions' => $transactions,
            'count' => $count,
        ));

    }
    /**
     * @param Request $request
     */
    public function searchWalletTransactions(Request $request)
    {
        $query = $request['query'];

        $transactions = Transaction::where('uuid', 'LIKE', '%' . $query . '%')
            ->paginate(20);

        $count = $transactions->total();

        return view('admin.viewAllWalletTransactions', array(
            'transactions' => $transactions,
            'query' => $query,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function cancelOrderFromAdmin(Request $request, TranslationHelper $translationHelper)
    {
        $keys = ['orderRefundWalletComment', 'orderPartialRefundWalletComment'];
        $translationData = $translationHelper->getDefaultLanguageValuesForKeys($keys);

        $order = Order::where('id', $request->order_id)->first();

        $user = User::where('id', $order->user_id)->first();

        try {
            //check if user is cancelling their own order...
            if ($order->orderstatus_id != 5 || $order->orderstatus_id != 6) {
                //check refund type
                // if refund_type === NOREFUND -> do nothing
                if ($request->refund_type === 'FULL') {
                    $user->deposit($order->total * 100, ['description' => $translationData->orderRefundWalletComment . $order->unique_order_id]);
                }

                if ($request->refund_type === 'HALF') {
                    $user->deposit($order->total / 2 * 100, ['description' => $translationData->orderPartialRefundWalletComment . $order->unique_order_id]);
                }

                //cancel order
                $order->orderstatus_id = 6; //6 means canceled..
                $order->save();

                //throw notification to user
                if (config('settings.enablePushNotificationOrders') == 'true') {
                    $notify = new PushNotify();
                    $notify->sendPushNotification('6', $order->user_id, $order->unique_order_id);
                }

                return redirect()->back()->with(['success' => 'Operation Successful']);
            }
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }

    }

    /**
     * @param Request $request
     */
    public function acceptOrderFromAdmin(Request $request)
    {

        $order = Order::where('id', $request->id)->first();

        if ($order->orderstatus_id == '1') {
            $order->orderstatus_id = 2;
            $order->save();
            // Send SMS Notification to Delivery Guy
            if (config('settings.smsDeliveryNotify') == 'true') {
                //get restaurant
                $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                if ($restaurant) {
                    //get all pivot users of restaurant (delivery guy/ res owners)
                    $pivotUsers = $restaurant->users()
                        ->wherePivot('restaurant_id', $order->restaurant_id)
                        ->get();
                    //filter only res owner and send notification.
                    foreach ($pivotUsers as $pU) {
                        if ($pU->hasRole('Delivery Guy')) {
                            //send Notification to Delivery Guy
                            $message = config('settings.defaultSmsDeliveryMsg');
                            $otp = null;
                            $smsnotify = new Sms();
                            $smsnotify->processSmsAction('OD_NOTIFY', $pU->phone, $otp, $message);
                        }
                    }
                }
            }
            // END Send SMS Notification to Delivery Guy

            return redirect()->back()->with(array('success' => 'Order Accepted'));
        } else {
            return redirect()->back()->with(array('message' => 'Something went wrong.'));
        }
    }

    /**
     * @param Request $request
     */
    public function assignDeliveryFromAdmin(Request $request)
    {
        try {
            $assignment = new AcceptDelivery;
            $assignment->order_id = $request->order_id;
            $assignment->user_id = $request->user_id;
            $assignment->customer_id = $request->customer_id;
            $assignment->is_complete = 0;
            $assignment->created_at = Carbon::now();
            $assignment->updated_at = Carbon::now();
            $assignment->save();

            $order = Order::where('id', $request->order_id)->first();
            $order->orderstatus_id = 3;
            $order->save();
            return redirect()->back()->with(array('success' => 'Order Assigned'));
        } catch (Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return redirect()->back()->with(array('message' => 'Something Went Wrong'));
            }
        }
    }

    /**
     * @param Request $request
     */
    public function reAssignDeliveryFromAdmin(Request $request)
    {

        $assignment = AcceptDelivery::where('order_id', $request->order_id)->first();
        $assignment->user_id = $request->user_id;
        $assignment->is_complete = 0;
        $assignment->updated_at = Carbon::now();
        $assignment->save();

        return redirect()->back()->with(array('success' => 'Order reassigned successfully'));
    }

    public function popularGeoLocations()
    {
        $locations = PopularGeoPlace::orderBy('id', 'DESC')->paginate(20);
        $locationsAll = PopularGeoPlace::all();
        $count = count($locationsAll);
        return view('admin.popularGeoLocations', array(
            'locations' => $locations,
            'count' => $count,
        ));
    }

    /**
     * @param Request $request
     */
    public function saveNewPopularGeoLocation(Request $request)
    {

        // dd($request->all());
        $location = new PopularGeoPlace();

        $location->name = $request->name;

        $location->latitude = $request->latitude;
        $location->longitude = $request->longitude;

        if ($request->is_active == 'true') {
            $location->is_active = true;
        } else {
            $location->is_active = false;
        }

        try {
            $location->save();
            return redirect()->back()->with(['success' => 'Location Saved']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param $id
     */
    public function disablePopularGeoLocation($id)
    {
        $location = PopularGeoPlace::where('id', $id)->first();
        if ($location) {
            $location->toggleActive()->save();
            return redirect()->back()->with(['success' => 'Location Disabled']);
        } else {
            return redirect()->route('admin.popularGeoLocations');
        }
    }

    /**
     * @param $id
     */
    public function deletePopularGeoLocation($id)
    {
        $location = PopularGeoPlace::where('id', $id)->first();

        if ($location) {
            $location->delete();

            return redirect()->route('admin.popularGeoLocations')->with(['success' => 'Location Deleted']);
        } else {
            return redirect()->route('admin.popularGeoLocations');
        }
    }

    public function translations()
    {
        $translations = Translation::orderBy('id', 'DESC')->get();
        $count = count($translations);

        return view('admin.translations', array(
            'translations' => $translations,
            'count' => $count,
        ));
    }

    public function newTranslation()
    {
        return view('admin.newTranslation');
    }

    /**
     * @param Request $request
     */
    public function saveNewTranslation(Request $request)
    {
        // dd($request->all());
        // dd(json_encode($request->except(['language_name'])));

        $translation = new Translation();

        $translation->language_name = $request->language_name;
        $translation->data = json_encode($request->except(['language_name', '_token']));

        try {
            $translation->save();
            return redirect()->route('admin.translations')->with(['success' => 'Translation Created']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }
    }

    /**
     * @param $id
     */
    public function editTranslation($id)
    {

        $translation = Translation::where('id', $id)->first();
        // dd(json_decode($translation->data));

        if ($translation) {
            return view('admin.editTranslation', array(
                'translation_id' => $translation->id,
                'language_name' => $translation->language_name,
                'data' => json_decode($translation->data),
            ));
        } else {
            return redirect()->route('admin.translations')->with(['message' => 'Translation Not Found']);
        }

    }
    /**
     * @param Request $request
     */
    public function updateTranslation(Request $request)
    {
        $translation = Translation::where('id', $request->translation_id)->first();

        $translation->language_name = $request->language_name;
        $translation->data = json_encode($request->except(['translation_id', 'language_name', '_token']));

        try {
            $translation->save();
            return redirect()->back()->with(['success' => 'Translation Updated']);
        } catch (\Illuminate\Database\QueryException $qe) {
            return redirect()->back()->with(['message' => $qe->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->with(['message' => $e->getMessage()]);
        } catch (\Throwable $th) {
            return redirect()->back()->with(['message' => $th]);
        }

    }

    /**
     * @param $id
     */
    public function disableTranslation($id)
    {
        $translation = Translation::where('id', $id)->first();
        if ($translation) {
            $translation->toggleEnable()->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.translations');
        }
    }

    /**
     * @param $id
     */
    public function deleteTranslation($id)
    {
        $translation = Translation::where('id', $id)->first();
        if ($translation) {
            $translation->delete();
            return redirect()->route('admin.translations')->with(['success' => 'Translation Deleted']);
        } else {
            return redirect()->route('admin.translations');
        }
    }

    /**
     * @param $id
     */
    public function makeDefaultLanguage($id)
    {
        $translation = Translation::where('id', $id)->first();
        if ($translation) {
            //remove default of other
            $currentDefaults = Translation::where('is_default', '1')->get();
            // dd($currentDefault);
            if (!empty($currentDefaults)) {
                foreach ($currentDefaults as $currentDefault) {
                    $currentDefault->is_default = 0;
                    $currentDefault->save();
                }
            }

            //make this default
            $translation->is_default = 1;
            $translation->save();
            return redirect()->back()->with(['success' => 'Operation Successful']);
        } else {
            return redirect()->route('admin.translations');
        }
    }

    /**
     * @param Request $request
     */
    public function updateRestaurantScheduleData(Request $request)
    {
        $data = $request->except(['_token', 'restaurant_id']);

        $i = 0;
        $str = '{';
        foreach ($data as $day => $times) {
            $str .= '"' . $day . '":[';
            if ($times) {
                foreach ($times as $key => $time) {

                    if ($key % 2 == 0) {
                        $t1 = $time;
                        $str .= '{"open" :' . '"' . $time . '"';

                    } else {
                        $t2 = $time;
                        $str .= '"close" :' . '"' . $time . '"}';
                    }

                    //check if last, if last then dont add comma,
                    if (count($times) != $key + 1) {
                        $str .= ',';
                    }
                }
                // dd($t1);
                if (Carbon::parse($t1) >= Carbon::parse($t2)) {

                    return redirect()->back()->with(['message' => 'Opening and Closing time is incorrect']);
                }
            } else {
                $str .= '}]';
            }

            if ($i != count($data) - 1) {
                $str .= '],';
            } else {
                $str .= ']';
            }
            $i++;
        }
        $str .= '}';

        // Fetches The Restaurant
        $restaurant = Restaurant::where('id', $request->restaurant_id)->first();
        // Enters The Data
        $restaurant->schedule_data = $str;
        // Saves the Data to Database
        $restaurant->save();

        return redirect()->back()->with(['success' => 'Scheduling data saved successfully']);

    }

    /**
     * @param $id
     */
    public function impersonate($id)
    {
        $user = User::where('id', $id)->first();
        if ($user && $user->hasRole('Store Owner')) {
            Auth::user()->impersonate($user);
            return redirect()->route('get.login');
        } else {
            return redirect()->route('admin.dashboard')->with(['message' => 'User not found']);
        }
    }
}
