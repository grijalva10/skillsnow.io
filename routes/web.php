<?php
Route::impersonate();

/* Setting the locale route */
Route::get('locale/{locale}', function ($locale) {
    Session::put('locale', $locale);
    return redirect()->back();
});

/* Installation Routes */
Route::get('install/start', 'InstallController@start');
Route::get('install/pre-installation', 'InstallController@preInstallation');
Route::get('install/configuration', 'InstallController@getConfiguration');
Route::post('install/configuration', 'InstallController@postConfiguration');
Route::get('install/complete', 'InstallController@complete');
/* END Installation Routes */

/* Update Routes */
Route::get('install/update', 'UpdateController@updatePage');
Route::post('install/update', 'UpdateController@update');
/* END Update Routes */

Route::get('/', 'PageController@indexPage')->name('get.index');

Route::get('/schedule/run/{password}', 'SchedulerController@run');
Route::get('/files-backup/run/{password}', 'BackupController@filesBackuprun');
Route::get('/database-backup/run/{password}', 'BackupController@dbBackuprun');

/* Auth Routes */
Route::get('/auth/login', 'PageController@loginPage')->name('get.login');
Route::post('/auth/login', 'Auth\LoginController@login')->name('post.login');
Route::get('auth/logout', 'Auth\LoginController@logout')->name('logout');

Route::post('auth/register', 'RegisterController@registerRestaurantDelivery')->name('registerRestaurantDelivery');

/* END Auth Routes */

/* Restaurant Order Routes */
Route::group(['prefix' => 'store-owner', 'middleware' => 'storeowner'], function () {

    Route::get('/orders', 'RestaurantOwnerController@orders')->name('restaurant.orders');
    Route::post('/orders/get-new-orders', 'RestaurantOwnerController@getNewOrders')->name('restaurant.getNewOrders');

    Route::get('/orders/accept-order/{id}', 'RestaurantOwnerController@acceptOrder')->name('restaurant.acceptOrder');
    Route::get('/orders/mark-order-ready/{id}', 'RestaurantOwnerController@markOrderReady')->name('restaurant.markOrderReady');
    Route::get('/orders/mark-selfpickup-order-completed/{id}', 'RestaurantOwnerController@markSelfPickupOrderAsCompleted')->name('restaurant.markSelfPickupOrderAsCompleted');

    Route::get('/orders/cancel-order/{id}', 'RestaurantOwnerController@cancelOrder')->name('restaurant.cancelOrder');

    Route::get('/stores', 'RestaurantOwnerController@restaurants')->name('restaurant.restaurants');
    Route::get('/store/edit/{id}', 'RestaurantOwnerController@getEditRestaurant')->name('restaurant.get.editRestaurant');
    Route::post('/store/new/save', 'RestaurantOwnerController@saveNewRestaurant')->name('restaurant.saveNewRestaurant');
    Route::get('/store/disable/{id}', 'RestaurantOwnerController@disableRestaurant')->name('restaurant.disableRestaurant');
    Route::post('/store/edit/save', 'RestaurantOwnerController@updateRestaurant')->name('restaurant.updateRestaurant');
    Route::post('/store/new/save', 'RestaurantOwnerController@saveNewRestaurant')->name('restaurant.saveNewRestaurant');

    Route::post('/store/schedule/save', 'RestaurantOwnerController@updateRestaurantScheduleData')->name('restaurant.updateRestaurantScheduleData');

    Route::get('/itemcategories', 'RestaurantOwnerController@itemcategories')->name('restaurant.itemcategories');
    Route::post('/itemcategories/new/save', 'RestaurantOwnerController@createItemCategory')->name('restaurant.createItemCategory');
    Route::get('/itemcategory/disable/{id}', 'RestaurantOwnerController@disableCategory')->name('restaurant.disableCategory');
    Route::post('/itemcategory/edit/save', 'AdminController@updateItemCategory')->name('restaurant.updateItemCategory');

    Route::get('/addoncategories', 'RestaurantOwnerController@addonCategories')->name('restaurant.addonCategories');
    Route::get('/addoncategories/searchAddonCategories', 'RestaurantOwnerController@searchAddonCategories')->name('restaurant.post.searchAddonCategories');
    Route::get('/addoncategory/edit/{id}', 'RestaurantOwnerController@getEditAddonCategory')->name('restaurant.editAddonCategory');
    Route::post('/addoncategory/edit/save', 'RestaurantOwnerController@updateAddonCategory')->name('restaurant.updateAddonCategory');
    Route::get('/addoncategory/new', 'RestaurantOwnerController@newAddonCategory')->name('restaurant.newAddonCategory');
    Route::post('/addoncategory/new/save', 'RestaurantOwnerController@saveNewAddonCategory')->name('restaurant.saveNewAddonCategory');

    Route::get('/addons', 'RestaurantOwnerController@addons')->name('restaurant.addons');
    Route::get('/addons/searchAddons', 'RestaurantOwnerController@searchAddons')->name('restaurant.post.searchAddons');
    Route::get('/addon/edit/{id}', 'RestaurantOwnerController@getEditAddon')->name('restaurant.editAddon');
    Route::post('/addon/edit/save', 'RestaurantOwnerController@updateAddon')->name('restaurant.updateAddon');
    Route::post('/addon/new/save', 'RestaurantOwnerController@saveNewAddon')->name('restaurant.saveNewAddon');
    Route::get('/addon/disable/{id}', 'RestaurantOwnerController@disableAddon')->name('restaurant.disableAddon');
    Route::get('/addon/delete/{id}', 'RestaurantOwnerController@deleteAddon')->name('restaurant.deleteAddon');

    Route::get('/items', 'RestaurantOwnerController@items')->name('restaurant.items');
    Route::get('/stores/searchItems', 'RestaurantOwnerController@searchItems')->name('restaurant.post.searchItems');
    Route::get('/items/edit/{id}', 'RestaurantOwnerController@getEditItem')->name('restaurant.get.editItem');
    Route::get('/item/disable/{id}', 'RestaurantOwnerController@disableItem')->name('restaurant.disableItem');
    Route::post('/item/edit/save', 'RestaurantOwnerController@updateItem')->name('restaurant.updateItem');
    Route::post('/item/new/save', 'RestaurantOwnerController@saveNewItem')->name('restaurant.saveNewItem');
    Route::post('/item/bulk/save', 'BulkUploadController@itemBulkUploadFromRestaurant')->name('restaurant.itemBulkUpload');

    Route::get('/orders', 'RestaurantOwnerController@orders')->name('restaurant.orders');
    Route::get('/orders/searchOrders', 'RestaurantOwnerController@postSearchOrders')->name('restaurant.post.searchOrders');
    Route::get('/order/{order_id}', 'RestaurantOwnerController@viewOrder')->name('restaurant.viewOrder');

    Route::get('/earnings/{restaurant_id?}', 'RestaurantOwnerController@earnings')->name('restaurant.earnings');
    Route::post('/earnings/send-payout-request', 'RestaurantOwnerController@sendPayoutRequest')->name('restaurant.sendPayoutRequest');

    Route::post('/save-store-owner-notification-token', 'NotificationController@saveRestaurantOwnerNotificationToken')->name('saveRestaurantOwnerNotificationToken');

    Route::get('/dashboard', 'RestaurantOwnerController@dashboard')->name('restaurant.dashboard');

});
/* END Restaurant Owner Routes */

/* Admin Routes */
Route::group(['prefix' => 'admin', 'middleware' => ['admin']], function () {

    Route::get('/processPayment', 'AdminController@processPayment')->name('admin.processPayment');

    Route::get('/manage-delivery-guys', 'AdminController@manageDeliveryGuys')->name('admin.manageDeliveryGuys');
    Route::get('/manage-delivery-guys-stores/{id}', 'AdminController@getManageDeliveryGuysRestaurants')->name('admin.get.manageDeliveryGuysRestaurants');
    Route::post('/update-delivery-guys-stores', 'AdminController@updateDeliveryGuysRestaurants')->name('admin.updateDeliveryGuysRestaurants');

    Route::get('/manage-store-owners', 'AdminController@manageRestaurantOwners')->name('admin.manageRestaurantOwners');
    Route::get('/manage-store-owners-stores/{id}', 'AdminController@getManageRestaurantOwnersRestaurants')->name('admin.get.getManageRestaurantOwnersRestaurants');
    Route::post('/update-store-owners-stores', 'AdminController@updateManageRestaurantOwnersRestaurants')->name('admin.updateManageRestaurantOwnersRestaurants');

    Route::get('/users', 'AdminController@users')->name('admin.users');

    Route::post('/saveNewUser', 'AdminController@saveNewUser')->name('admin.saveNewUser');

    Route::get('/users/searchUsers', 'AdminController@postSearchUsers')->name('admin.post.searchUsers');

    Route::get('/user/edit/{id}', 'AdminController@getEditUser')->name('admin.get.editUser');
    Route::post('/user/edit/save', 'AdminController@updateUser')->name('admin.updateUser');

    Route::get('/user/ban/{id}', 'AdminController@banUser')->name('admin.banUser');

    Route::post('/user/add-money-to-wallet', 'AdminController@addMoneyToWallet')->name('admin.addMoneyToWallet');
    Route::post('/user/substract-money-from-wallet', 'AdminController@substractMoneyFromWallet')->name('admin.substractMoneyFromWallet');

    Route::get('/wallet/transactions', 'AdminController@walletTransactions')->name('admin.walletTransactions');
    Route::get('/wallet/searchWalletTransactions', 'AdminController@searchWalletTransactions')->name('admin.searchWalletTransactions');

    Route::get('/settings', 'SettingController@settings')->name('admin.settings');
    Route::post('/settings', 'SettingController@saveSettings')->name('admin.saveSettings');
    Route::post('/settings/send-test-mail', 'SettingController@sendTestmail')->name('admin.sendTestmail');
    Route::post('/settings/payment-gateways-toggle', 'PaymentController@togglePaymentGateways')->name('admin.togglePaymentGateways');

    Route::get('/orders', 'AdminController@orders')->name('admin.orders');
    Route::get('/orders/searchOrders', 'AdminController@postSearchOrders')->name('admin.post.searchOrders');
    Route::get('/order/{order_id}', 'AdminController@viewOrder')->name('admin.viewOrder');
    Route::post('/order/cancel-order', 'AdminController@cancelOrderFromAdmin')->name('admin.cancelOrderFromAdmin');
    Route::post('/order/accept-order', 'AdminController@acceptOrderFromAdmin')->name('admin.acceptOrderFromAdmin');
    Route::post('/order/assign-delivery', 'AdminController@assignDeliveryFromAdmin')->name('admin.assignDeliveryFromAdmin');
    Route::post('/order/reassign-delivery', 'AdminController@reAssignDeliveryFromAdmin')->name('admin.reAssignDeliveryFromAdmin');

    Route::get('/sliders', 'AdminController@sliders')->name('admin.sliders');
    Route::get('/sliders/disable/{id}', 'AdminController@disableSlider')->name('admin.disableSlider');
    Route::get('/sliders/delete/{id}', 'AdminController@deleteSlider')->name('admin.deleteSlider');
    Route::get('/sliders/{id}', 'AdminController@getEditSlider')->name('admin.get.editSlider');
    Route::post('/slider/create', 'AdminController@createSlider')->name('admin.createSlider');
    Route::post('/slider/save', 'AdminController@saveSlide')->name('admin.saveSlide');
    Route::post('/sliders/edit/save', 'AdminController@updateSlider')->name('admin.updateSlider');

    Route::get('/slider/delete/{id}', 'AdminController@deleteSlide')->name('admin.deleteSlide');
    Route::get('/slider/disable/{id}', 'AdminController@disableSlide')->name('admin.disableSlide');

    Route::get('/slide/edit/{id}', 'AdminController@editSlide')->name('admin.editSlide');
    Route::post('/slide/edit/save', 'AdminController@updateSlide')->name('admin.updateSlide');
    Route::post('/slide/edit/position/save', 'AdminController@updateSlidePosition')->name('admin.updateSlidePosition');

    Route::get('/stores', 'AdminController@restaurants')->name('admin.restaurants');
    Route::get('/stores/pending-acceptance', 'AdminController@pendingAcceptance')->name('admin.pendingAcceptance');
    Route::get('/stores/sort', 'AdminController@sortStores')->name('admin.sortStores');
    Route::post('/stores/sort/save', 'AdminController@updateStorePosition')->name('admin.updateStorePosition');

    Route::get('/stores/pending-acceptance/accept/{id}', 'AdminController@acceptRestaurant')->name('admin.acceptRestaurant');
    Route::get('/stores/searchRestaurants', 'AdminController@searchRestaurants')->name('admin.post.searchRestaurants');
    Route::get('/store/edit/{id}', 'AdminController@getEditRestaurant')->name('admin.get.editRestaurant');
    Route::get('/store/disable/{id}', 'AdminController@disableRestaurant')->name('admin.disableRestaurant');
    Route::get('/store/delete/{id}', 'AdminController@deleteRestaurant')->name('admin.deleteRestaurant');
    Route::post('/store/edit/save', 'AdminController@updateRestaurant')->name('admin.updateRestaurant');
    Route::post('/store/new/save', 'AdminController@saveNewRestaurant')->name('admin.saveNewRestaurant');
    Route::post('/store/bulk/save', 'BulkUploadController@restaurantBulkUpload')->name('admin.restaurantBulkUpload');
    Route::get('/store/{restaurant_id}/items', 'AdminController@getRestaurantItems')->name('admin.getRestaurantItems');

    Route::post('/store/schedule/save', 'AdminController@updateRestaurantScheduleData')->name('admin.updateRestaurantScheduleData');

    Route::post('/store/update-slug', 'AdminController@updateSlug')->name('admin.updateSlug');

    Route::get('/addoncategories', 'AdminController@addonCategories')->name('admin.addonCategories');
    Route::get('/addoncategories/searchAddonCategories', 'AdminController@searchAddonCategories')->name('admin.post.searchAddonCategories');
    Route::get('/addoncategory/edit/{id}', 'AdminController@getEditAddonCategory')->name('admin.editAddonCategory');
    Route::post('/addoncategory/edit/save', 'AdminController@updateAddonCategory')->name('admin.updateAddonCategory');
    Route::get('/addoncategory/new', 'AdminController@newAddonCategory')->name('admin.newAddonCategory');
    Route::post('/addoncategory/new/save', 'AdminController@saveNewAddonCategory')->name('admin.saveNewAddonCategory');
    Route::get('/addoncategory/get-addons/{id}', 'AdminController@addonsOfAddonCategory')->name('admin.addonsOfAddonCategory');

    Route::get('/addons', 'AdminController@addons')->name('admin.addons');
    Route::get('/addons/searchAddons', 'AdminController@searchAddons')->name('admin.post.searchAddons');
    Route::get('/addon/edit/{id}', 'AdminController@getEditAddon')->name('admin.editAddon');
    Route::post('/addon/edit/save', 'AdminController@updateAddon')->name('admin.updateAddon');
    Route::post('/addon/new/save', 'AdminController@saveNewAddon')->name('admin.saveNewAddon');
    Route::get('/addon/disable/{id}', 'AdminController@disableAddon')->name('admin.disableAddon');
    Route::get('/addon/delete/{id}', 'AdminController@deleteAddon')->name('admin.deleteAddon');

    Route::get('/items', 'AdminController@items')->name('admin.items');
    Route::get('/items/searchItems', 'AdminController@searchItems')->name('admin.post.searchItems');
    Route::get('/item/edit/{id}', 'AdminController@getEditItem')->name('admin.get.editItem');
    Route::get('/item/disable/{id}', 'AdminController@disableItem')->name('admin.disableItem');
    Route::post('/item/edit/save', 'AdminController@updateItem')->name('admin.updateItem');
    Route::post('/item/new/save', 'AdminController@saveNewItem')->name('admin.saveNewItem');
    Route::post('/item/bulk/save', 'BulkUploadController@itemBulkUpload')->name('admin.itemBulkUpload');

    Route::get('/itemcategories', 'AdminController@itemcategories')->name('admin.itemcategories');
    Route::post('/itemcategories/new/save', 'AdminController@createItemCategory')->name('admin.createItemCategory');
    Route::get('/itemcategory/disable/{id}', 'AdminController@disableCategory')->name('admin.disableCategory');
    Route::post('/itemcategory/edit/save', 'AdminController@updateItemCategory')->name('admin.updateItemCategory');

    Route::get('/coupons', 'CouponController@coupons')->name('admin.coupons');
    Route::post('/coupon/new/save', 'CouponController@saveNewCoupon')->name('admin.post.saveNewCoupon');
    Route::get('/coupon/edit/{id}', 'CouponController@getEditCoupon')->name('admin.get.getEditCoupon');
    Route::post('/coupon/edit/save', 'CouponController@updateCoupon')->name('admin.updateCoupon');
    Route::get('/coupon/delete/{id}', 'CouponController@deleteCoupon')->name('admin.deleteCoupon');

    Route::get('/notifications', 'NotificationController@notifications')->name('admin.notifications');
    Route::post('/notifications/upload', 'NotificationController@uploadNotificationImage')->name('admin.uploadNotificationImage');
    Route::post('/notifications/send', 'NotificationController@sendNotifiaction')->name('admin.sendNotifiaction');
    Route::post('/notification-to-users/send', 'NotificationController@sendNotificationToSelectedUsers')->name('admin.sendNotificationToSelectedUsers');

    Route::get('/popular-geo-locations', 'AdminController@popularGeoLocations')->name('admin.popularGeoLocations');
    Route::post('/popular-geo-location/new/save', 'AdminController@saveNewPopularGeoLocation')->name('admin.saveNewPopularGeoLocation');
    Route::get('/popular-geo-location/disable/{id}', 'AdminController@disablePopularGeoLocation')->name('admin.disablePopularGeoLocation');
    Route::get('/popular-geo-location/delete/{id}', 'AdminController@deletePopularGeoLocation')->name('admin.deletePopularGeoLocation');

    Route::get('/pages', 'AdminController@pages')->name('admin.pages');
    Route::post('/page/new/save', 'AdminController@saveNewpage')->name('admin.saveNewPage');
    Route::get('/page/edit/{id}', 'AdminController@getEditPage')->name('admin.getEditPage');
    Route::post('/page/edit/save', 'AdminController@updatePage')->name('admin.updatePage');
    Route::get('/page/delete/{id}', 'AdminController@deletePage')->name('admin.deletePage');

    Route::get('/store-payouts', 'AdminController@restaurantpayouts')->name('admin.restaurantpayouts');
    Route::get('/store-payouts/{id}', 'AdminController@viewRestaurantPayout')->name('admin.viewRestaurantPayout');
    Route::post('/store-payouts/save', 'AdminController@updateRestaurantPayout')->name('admin.updateRestaurantPayout');

    Route::get('/update/check', '\pcinaglia\laraupdater\LaraUpdaterController@check')->name('admin.checkForUpdates');
    Route::get('/update/perform-update', '\pcinaglia\laraupdater\LaraUpdaterController@update')->name('admin.performUpdate');

    Route::get('/translations', 'AdminController@translations')->name('admin.translations');
    Route::get('/translation/new', 'AdminController@newTranslation')->name('admin.newTranslation');
    Route::post('/translation/new/save', 'AdminController@saveNewTranslation')->name('admin.saveNewTranslation');
    Route::get('/translation/edit/{id}', 'AdminController@editTranslation')->name('admin.editTranslation');
    Route::post('/translation/edit/save', 'AdminController@updateTranslation')->name('admin.updateTranslation');
    Route::get('/translation/disable/{id}', 'AdminController@disableTranslation')->name('admin.disableTranslation');
    Route::get('/translation/delete/{id}', 'AdminController@deleteTranslation')->name('admin.deleteTranslation');
    Route::get('/translation/make-default/{id}', 'AdminController@makeDefaultLanguage')->name('admin.makeDefaultLanguage');

    Route::get('/delivery-collections', 'DeliveryCollectionController@deliveryCollections')->name('admin.deliveryCollections');
    Route::post('/delivery-collection/collect/{id}', 'DeliveryCollectionController@collectDeliveryCollection')->name('admin.collectDeliveryCollection');

    Route::get('/delivery-collection-logs', 'DeliveryCollectionController@deliveryCollectionLogs')->name('admin.deliveryCollectionLogs');
    Route::get('/delivery-collection-logs/{id}', 'DeliveryCollectionController@deliveryCollectionLogsForSingleUser')->name('admin.deliveryCollectionLogsForSingleUser');

    Route::get('/store-category-slider', 'RestaurantCategoryController@restaurantCategorySlider')->name('admin.restaurantCategorySlider');
    Route::get('/store-category-slider/delete/{id}', 'RestaurantCategoryController@deleteRestaurantCategorySlide')->name('admin.deleteRestaurantCategorySlide');
    Route::get('/store-category-slider/disable/{id}', 'RestaurantCategoryController@disableRestaurantCategorySlide')->name('admin.disableRestaurantCategorySlide');
    Route::post('/store-category-slider/new', 'RestaurantCategoryController@newRestaurantCategory')->name('admin.newRestaurantCategory');
    Route::post('/store-category-slider/update', 'RestaurantCategoryController@updateRestaurantCategory')->name('admin.updateRestaurantCategory');
    Route::post('/store-category-slider/save-settings', 'RestaurantCategoryController@saveRestaurantCategorySliderSettings')->name('admin.saveRestaurantCategorySliderSettings');

    Route::post('/create-store-category-slide', 'RestaurantCategoryController@createRestaurantCategorySlide')->name('admin.createRestaurantCategorySlide');
    Route::post('/store-category-slider/edit/position/save', 'RestaurantCategoryController@updateCategorySlidePosition')->name('admin.updateCategorySlidePosition');

    Route::get('/modules', 'ModuleController@modules')->name('admin.modules');
    Route::post('/module/upload', 'ModuleController@uploadModuleZipFile')->name('admin.uploadModuleZipFile');
    Route::post('/module/install', 'ModuleController@installModule')->name('admin.installModule');
    Route::get('/module/disable/{id}', 'ModuleController@disableModule')->name('admin.disableModule');
    Route::get('/module/enable/{id}', 'ModuleController@enableModule')->name('admin.enableModule');

    Route::get('/fix-update-issues', 'AdminController@fixUpdateIssues')->name('admin.fixUpdateIssues');

    Route::get('/update-foodomaa', 'UpdateController@updateFoodomaa')->name('admin.updateFoodomaa');
    Route::get('/update-foodomaa-now', 'UpdateController@updateFoodomaaNow')->name('admin.updateFoodomaaNow');
    Route::post('/update-foodomaa/upload', 'UpdateController@uploadUpdateZipFile')->name('admin.uploadUpdateZipFile');

    Route::post('/force-clear', 'SettingController@forceClear')->name('admin.forceClear');
    Route::post('/clean-everything', 'SettingController@cleanEverything')->name('admin.cleanEverything');

    Route::get('/reports/top-items', 'ReportController@viewTopItems')->name('admin.viewTopItems');
    Route::get('/impersonate/{id}', 'AdminController@impersonate')->name('admin.impersonate');

    Route::get('/backup/files', 'BackupController@filesBackup')->name('admin.filesBackup');
    Route::get('/backup/database', 'BackupController@dbBackup')->name('admin.dbBackup');

    Route::get('/dashboard', 'AdminController@dashboard')->name('admin.dashboard');

});
/* END Admin Routes */

/* EXTRAS */
// Route::get('/init', 'InitController@init')->name('init');
