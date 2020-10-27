<style>
.extra-menu{min-width:500px}@media (min-width:320px) and (max-width:767px){.extra-menu{min-width:auto}}
</style>
<div class="navbar navbar-expand-md navbar-dark">
    <div class="navbar-brand wmin-0 mr-5">
        @role("Admin")
        <a href="{{ route('admin.dashboard') }}" class="d-inline-block">
        <img src="{{substr(url("/"), 0, strrpos(url("/"), '/'))}}/assets/backend/global_assets/images/dashboard-logo.png" alt="Dashboard">
        </a>
        @endif
        @role("Store Owner")
        <a href="{{ route('restaurant.dashboard') }}" class="d-inline-block">
        <img src="{{substr(url("/"), 0, strrpos(url("/"), '/'))}}/assets/backend/global_assets/images/dashboard-logo.png" alt="Dashboard">
        </a>
        @endrole
    </div>
    <div class="d-md-none">
        <button class="navbar-toggler dropdown-toggle" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <span>{{ Auth::user()->name }}</span>
        </button>
        <div class="dropdown-menu dropdown-menu-right" id="navbar-mobile">
            <a href="{{ route('logout') }}" class="dropdown-item"><i class="icon-switch2"></i> {{ __('storeDashboard.navLogout') }}</a>
            @role("Store Owner")
                <a href="#" class="dropdown-item dropdown-toggle" data-toggle="dropdown">
                <span><i class="icon-earth"></i></span>
                </a>
                <div class="dropdown-menu">
                    @foreach($translationLangs as $lang)
                    <a href="{{ url('locale', $lang) }}" class="dropdown-item @if(app()->getLocale() === $lang) active @endif" style="text-transform: uppercase;"> {{ $lang }}</a>
                    @endforeach
                </div>
            @endrole
        </div>
    </div>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="navbar-nav-link d-flex align-items-center dropdown-toggle" data-toggle="dropdown">
                <span>{{ Auth::user()->name }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('logout') }}" class="dropdown-item"><i class="icon-switch2"></i> {{ __('storeDashboard.navLogout') }}</a>
                </div>
            </li>
            @role("Store Owner")
                <li class="nav-item dropdown dropdown-user">
                    <a href="#" class="navbar-nav-link d-flex align-items-center dropdown-toggle" data-toggle="dropdown">
                    <span><i class="icon-earth mx-2"></i></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach($translationLangs as $lang)
                        <a href="{{ url('locale', $lang) }}" class="dropdown-item @if(app()->getLocale() === $lang) active @endif" style="text-transform: uppercase;"> {{ $lang }}</a>
                        @endforeach
                    </div>
                </li>
                @impersonating
                <li class="nav-item">
                    <a class="navbar-nav-link active" href="{{ route('impersonate.leave') }}"><i class="icon-arrow-left15 mr-1"></i>Go back to Admin</a>
                </li>
                @endImpersonating
            @endrole
        </ul>
    </div>
</div>
<div class="navbar navbar-expand-md navbar-light navbar-sticky">
    <div class="container">
        <div class="text-center d-md-none w-100">
            <button type="button" class="navbar-toggler dropdown-toggle" data-toggle="collapse" data-target="#navbar-navigation">
            <i class="icon-unfold mr-2"></i>
            Navigation
            </button>
        </div>
        <div class="navbar-collapse collapse" id="navbar-navigation">
            <ul class="navbar-nav">
                @role("Admin")
                <li class="nav-item">
                    <a href="{{ route("admin.restaurants") }}" class="navbar-nav-link {{ Request::is('admin/stores') ? 'active' : '' }}">
                    <i class="icon-store2 mr-2"></i>
                    Stores
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="javascript:void(0)" class="navbar-nav-link dropdown-toggle {{ Request::is('admin/items')  || Request::is('admin/addons') ? 'active' : '' }}" data-toggle="dropdown">
                    <i class="icon-stack-star mr-2"></i>
                    Items & Menu
                    </a>
                    <div class="dropdown-menu">
                    <a href="{{ route("admin.addonCategories") }}" class="dropdown-item {{ Request::is('admin/addoncategories') ? 'active' : '' }}">
                    <i class="icon-tree6 mr-2"></i>
                    Addon Categories
                    </a>
                    <a href="{{ route("admin.addons") }}" class="dropdown-item {{ Request::is('admin/addons') ? 'active' : '' }}">
                    <i class="icon-list2 mr-2"></i>
                    Addons
                    </a>
                    <a href="{{ route("admin.itemcategories") }}" class="dropdown-item {{ Request::is('admin/itemcategories') ? 'active' : '' }}">
                    <i class="icon-grid52 mr-2"></i>
                    Menu Categories
                    </a>
                    <a href="{{ route("admin.items") }}" class="dropdown-item {{ Request::is('admin/items') ? 'active' : '' }}">
                    <i class="icon-grid mr-2"></i>
                    Items
                    </a>
                     </div>
                </li>
                <li class="nav-item dropdown">
                    <a href="javascript:void(0)" class="navbar-nav-link dropdown-toggle  {{ Request::is('admin/users') || Request::is('admin/manage-delivery-guys') ? 'active' : '' }}" data-toggle="dropdown">
                    <i class="icon-users2 mr-2"></i>
                    Users
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route("admin.users") }}" class="dropdown-item {{ Request::is('admin/users') ? 'active' : ''}}"> <i class="icon-users4 mr-2"></i> All Users</a>
                        <a href="{{ route('admin.manageRestaurantOwners') }}" class="dropdown-item {{ Request::is('admin/manage-store-owners') ? 'active' : ''}}"> <i class="icon-user-tie mr-2"></i> Store Owners</a>
                        <a href="{{ route('admin.manageDeliveryGuys') }}" class="dropdown-item {{ Request::is('admin/manage-delivery-guys') ? 'active' : ''}}"> <i class="icon-truck mr-2"></i> Delivery Guys</a> 
                    </div>
                </li>
                <li class="nav-item">
                    <a href="{{ route("admin.orders") }}" class="navbar-nav-link {{ Request::is('admin/orders') ? 'active' : '' }}">
                    <i class="icon-basket mr-2"></i>
                    Orders
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="javascript:void(0)" class="navbar-nav-link dropdown-toggle  {{ Request::is('admin/sliders') || Request::is('admin/coupons') || Request::is('admin/notifications') ? 'active' : '' }}" data-toggle="dropdown">
                    <i class="icon-strategy mr-2"></i>
                    Modules
                    </a>
                    <div class="dropdown-menu">
                        <div class="row extra-menu">
                            <div class="col-md-6">
                                <a href="{{ route('admin.sliders') }}" class="dropdown-item {{ Request::is('admin/sliders') ? 'active' : '' }}">
                                <i class="icon-image2 mr-2"></i>
                                Promo Sliders
                                </a>
                                 <a href="{{ route("admin.restaurantCategorySlider") }}" class="dropdown-item {{ Request::is('admin/store-category-slider') ? 'active' : '' }}">
                                <i class="icon-grid52 mr-2"></i>
                                Store Category Slider
                                </a>
                                <a href="{{ route('admin.coupons') }}" class="dropdown-item {{ Request::is('admin/coupons') ? 'active' : '' }}">
                                <i class="icon-price-tags2 mr-2"></i>
                                Coupons
                                </a>
                                <a href="{{ route('admin.pages') }}" class="dropdown-item {{ Request::is('admin/pages') ? 'active' : '' }}">
                                <i class="icon-files-empty mr-2"></i>
                                Pages
                                </a>
                                <a href="{{ route("admin.popularGeoLocations") }}" class="dropdown-item {{ Request::is('admin/popular-geo-locations') ? 'active' : '' }}">
                                <i class="icon-location3 mr-2"></i>
                                Popular Geo Locations
                                </a>
                                <a href="{{ route('admin.notifications') }}" class="dropdown-item {{ Request::is('admin/notifications') ? 'active' : '' }}">
                                <i class="icon-bubble-dots4 mr-2"></i>
                                Send Push Notifications
                                </a>
                                <a href="{{ route("admin.modules") }}" class="dropdown-item {{ Request::is('admin/modules') ? 'active' : '' }}">
                                    <i class="icon-stars mr-2"></i>
                                    Premium Modules <span class="badge badge-flat border-grey-800 text-danger text-capitalize ml-1 float-right">NEW</span>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('admin.restaurantpayouts') }}" class="dropdown-item {{ Request::is('admin/store-payouts') ? 'active' : '' }}">
                                <i class="icon-piggy-bank mr-2"></i>
                                Store Payouts
                                </a>
                                <a href="{{ route("admin.translations") }}" class="dropdown-item {{ Request::is('admin/translations') ? 'active' : '' }}">
                                <i class="icon-font-size2 mr-2"></i>
                                Translations
                                </a>
                                <a href="{{ route("admin.deliveryCollections") }}" class="dropdown-item {{ Request::is('admin/delivery-collections') ? 'active' : '' }}">
                                <i class="icon-cash3 mr-2"></i>
                                Delivery Collections
                                </a>
                                <a href="{{ route("admin.deliveryCollectionLogs") }}" class="dropdown-item {{ Request::is('admin/delivery-collection-logs') ? 'active' : '' }}">
                                <i class="icon-database-time2 mr-2"></i>
                                Delivery Collection Logs
                                </a>
                                <a href="{{ route("admin.walletTransactions") }}" class="dropdown-item {{ Request::is('admin/wallet/transactions') ? 'active' : '' }}">
                                <i class="icon-transmission mr-2"></i>
                                Wallet Transactions
                                </a>
                                <a href="{{ route("admin.viewTopItems") }}" class="dropdown-item {{ Request::is('admin/reports/top-items') ? 'active' : '' }}">
                                <i class="icon-graph mr-2"></i>
                                Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <ul class="navbar-nav ml-md-auto">
                    <li class="nav-item">
                        <a href="{{ route('admin.settings') }}" class="navbar-nav-link {{ Request::is('admin/settings') ? 'active' : '' }}" data-popup="tooltip" title="All Settings" data-placement="bottom">
                        <i class="icon-cog3 mr-2"></i>
                        Settings
                        </a>
                    </li>
                </ul>
                @endrole
                @role("Store Owner")
                <li class="nav-item">
                    <a href="{{ route("restaurant.restaurants") }}" class="navbar-nav-link {{ Request::is('restaurant-owner/restaurants') ? 'active' : '' }}">
                    <i class="icon-store2 mr-2"></i>
                    {{__('storeDashboard.navStores')}}
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="javascript:void(0)" class="navbar-nav-link dropdown-toggle {{ Request::is('restaurant-owner/items')  || Request::is('restaurant-owner/addons') ? 'active' : '' }}" data-toggle="dropdown">
                    <i class="icon-stack-star mr-2"></i>
                    {{__('storeDashboard.navItemsMenu')}}
                    </a>
                    <div class="dropdown-menu">
                    <a href="{{ route("restaurant.addonCategories") }}" class="dropdown-item {{ Request::is('restaurant-owner/addoncategories') ? 'active' : '' }}">
                    <i class="icon-tree6 mr-2"></i>
                    {{__('storeDashboard.navSubAddonCat')}}
                    </a>
                    <a href="{{ route("restaurant.addons") }}" class="dropdown-item {{ Request::is('restaurant-owner/addons') ? 'active' : '' }}">
                    <i class="icon-list2 mr-2"></i>
                    {{__('storeDashboard.navSubAddon')}}
                    </a>
                    <a href="{{ route("restaurant.itemcategories") }}" class="dropdown-item {{ Request::is('restaurant-owner/itemcategories') ? 'active' : '' }}">
                    <i class="icon-grid52 mr-2"></i>
                    {{__('storeDashboard.navSubMenuCat')}}
                    </a>
                    <a href="{{ route("restaurant.items") }}" class="dropdown-item {{ Request::is('restaurant-owner/items') ? 'active' : '' }}">
                    <i class="icon-grid mr-2"></i>
                    {{__('storeDashboard.navSubItems')}}
                    </a>
                     </div>
                </li>
                <li class="nav-item">
                    <a href="{{ route("restaurant.orders") }}" class="navbar-nav-link {{ Request::is('restaurant-owner/orders') ? 'active' : '' }}">
                    <i class="icon-basket mr-2"></i>
                    {{__('storeDashboard.navOrders')}}
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route("restaurant.earnings") }}" class="navbar-nav-link {{ Request::is('restaurant-owner/earnings/*') ? 'active' : '' }}">
                    <i class="icon-coin-dollar mr-2"></i>
                    {{__('storeDashboard.navEarnings')}}
                    </a>
                </li>
                @endrole
            </ul>
        </div>
    </div>
</div>