@extends('admin.layouts.master')
@section("title")
{{__('storeDashboard.dashboardTitle')}}
@endsection
@section('content')
<div class="content mb-5">
    <div class="row mt-3">
        <div class="col-6 col-xl-3 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left" href="javascript:void(0)">
                    <div class="block-content block-content-full clearfix">
                        <div class="float-right mt-10 d-none d-sm-block">
                            <i class="dashboard-display-icon icon-city"></i>
                        </div>
                        <div class="dashboard-display-number">{{ $restaurantsCount }}</div>
                        <div class="font-size-sm text-uppercase text-muted">{{__('storeDashboard.dashboardStores')}}</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-3 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left" href="javascript:void(0)">
                    <div class="block-content block-content-full clearfix">
                        <div class="float-right mt-10 d-none d-sm-block">
                            <i class="dashboard-display-icon icon-basket"></i>
                        </div>
                        <div class="dashboard-display-number">{{ $ordersCount }}</div>
                        <div class="font-size-sm text-uppercase text-muted">{{__('storeDashboard.dashboardOrdersProcessed')}}</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-3 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left" href="javascript:void(0)">
                    <div class="block-content block-content-full clearfix">
                        <div class="float-right mt-10 d-none d-sm-block">
                            <i class="dashboard-display-icon icon-stack-star"></i>
                        </div>
                        <div class="dashboard-display-number">{{ $orderItemsCount }}</div>
                        <div class="font-size-sm text-uppercase text-muted">{{__('storeDashboard.dashboardItemsSold')}}</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-6 col-xl-3 mt-2">
            <div class="col-xl-12 dashboard-display p-3">
                <a class="block block-link-shadow text-left" href="javascript:void(0)">
                    <div class="block-content block-content-full clearfix">
                        <div class="float-right mt-10 d-none d-sm-block">
                            <i class="dashboard-display-icon icon-coin-dollar"></i>
                        </div>
                        <div class="dashboard-display-number">{{ config('settings.currencyFormat') }}
                            {{ $totalEarning }}
                        </div>
                        <div class="font-size-sm text-uppercase text-muted">{{__('storeDashboard.dashboardEarnings')}}</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="row pt-4 p-0">
        <div class="col-xl-12">
            <div class="panel panel-flat dashboard-main-col mt-4">
                {{-- <button class="btn btn-default btn-labeled btn-lg mr-2 mt-2 stopSound float-right" data-popup="tooltip" data-placement="right" title="{{ __('storeDashboard.dashboardStopSound') }}" style="background-color: #F5F5F5;"><i class="icon-volume-mute5"></i></button> --}}
                <div class="panel-heading">
                    <h4 class="panel-title pl-3 pt-3"><strong>{{__('storeDashboard.dashboardNewOrders')}}</strong></h4>
                    <hr>
                </div>
                <div id="newOrdersTable" class="table-responsive @if(!count($newOrders)) hidden @endif">
                    <table class="table text-nowrap">
                        <thead>
                            <tr>
                                <th>{{__('storeDashboard.dashboardOrderID')}}</th>
                                <th class="text-center"><i class="
                                    icon-circle-down2"></i></th>
                                <th>{{__('storeDashboard.dashboardStore')}}</th>
                                <th>{{__('storeDashboard.dashboardPrice')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderStatus')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($newOrders as $nO)
                            <tr>
                                <td>
                                    <a href="{{ route('restaurant.viewOrder', $nO->unique_order_id) }}"
                                        class="letter-icon-title">{{ $nO->unique_order_id }}</a>
                                </td>
                                <td class="text-center new-order-actions">
                                    <a href="{{ route('restaurant.acceptOrder', $nO->id) }}"
                                        class="btn btn-primary btn-labeled btn-labeled-left mr-2 accpetOrderBtnTableList"> <b><i
                                        class="icon-checkmark3 ml-1"></i> </b> {{__('storeDashboard.dashboardAcceptOrder')}} </a>
                                    <a href="{{ route('restaurant.cancelOrder', $nO->id) }}"
                                        class="btn btn-danger btn-labeled btn-labeled-right mr-2 cancelOrderBtnTableList" data-popup="tooltip"
                                        data-placement="right" title="{{ __('storeDashboard.dashboardDoubleClickMsg') }}"> <b><i
                                        class="icon-cross ml-1"></i></b> {{__('storeDashboard.dashboardCancelOrder')}} </a>
                                </td>
                                <td>
                                    {{ $nO->restaurant->name }}
                                </td>
                                <td>
                                    <span class="text-semibold no-margin">{{ config('settings.currencyFormat') }}
                                    {{ $nO->total }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                                    {{__('storeDashboard.dashboardNew')}}
                                    </span>
                                    @if($nO->delivery_type == 2)
                                    <span class="badge badge-flat border-danger-800 text-default text-capitalize">
                                    {{__('storeDashboard.dashboardSelfPickup')}}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(!count($newOrders))
                <div class="text-center text-muted pb-2" id="newOrdersNoOrdersMessage">
                    <h4> {{__('storeDashboard.dashboardNoOrders')}} </h4>
                </div>
                @endif
            </div>
        </div>
        <div class="col-xl-12">
            <div class="panel panel-flat dashboard-main-col mt-4">
                <div class="panel-heading">
                    <h4 class="panel-title pl-3 pt-3"><strong>{{__('storeDashboard.dashboardPreparingOrders')}}</strong></h4>
                    <hr>
                </div>
                <div class="table-responsive">
                    @if(count($preparingOrders))
                    <table class="table text-nowrap">
                        <thead>
                            <tr>
                                <th>{{__('storeDashboard.dashboardOrderID')}}</th>
                                <th class="text-center"><i class="
                                    icon-circle-down2"></i></th>
                                <th>{{__('storeDashboard.dashboardPrice')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderPlacedTime')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderAcceptedTime')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preparingOrders as $pO)
                            <tr>
                                <td>
                                    <a href="{{ route('restaurant.viewOrder', $pO->unique_order_id) }}"
                                        class="letter-icon-title">{{ $pO->unique_order_id }}</a>
                                </td>
                                <td class="text-center accepted-order-actions">
                                    @if($pO->delivery_type == 2 && $pO->orderstatus_id == 2)
                                    <a href="{{ route('restaurant.markOrderReady', $pO->id) }}"
                                        class="btn btn-warning btn-labeled btn-labeled-left mr-2 actionAfterAccept"> <b><i
                                        class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardMarkAsReady')}} </a>
                                    @endif
                                    @if($pO->delivery_type == 2 && $pO->orderstatus_id == 7)
                                    <a href="{{ route('restaurant.markSelfPickupOrderAsCompleted', $pO->id) }}"
                                        class="btn btn-success btn-labeled btn-labeled-left mr-2 actionAfterAccept"> <b><i
                                        class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardMarkAsCompleted')}} </a>
                                    @endif
                                    @if($pO->delivery_type == 1)
                                    <span>--</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-semibold no-margin">{{ config('settings.currencyFormat') }}
                                    {{ $pO->total }}</span>
                                </td>
                                <td>
                                    {{ $pO->created_at->diffForHumans() }}
                                </td>
                                <td>
                                    {{ $pO->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center text-muted pb-2">
                        <h4> {{__('storeDashboard.dashboardNoOrders')}} </h4>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            <div class="panel panel-flat dashboard-main-col mt-4">
                <div class="panel-heading">
                    <h4 class="panel-title pl-3 pt-3"><strong>Self-Pickup Orders</strong></h4>
                    <hr>
                </div>
                <div class="table-responsive">
                    @if(count($selfpickupOrders))
                    <table class="table text-nowrap">
                        <thead>
                            <tr>
                                <th>{{__('storeDashboard.dashboardOrderID')}}</th>
                                <th class="text-center"><i class="
                                    icon-circle-down2"></i></th>
                                <th>{{__('storeDashboard.dashboardPrice')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderPlacedTime')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderAcceptedTime')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selfpickupOrders as $spO)
                            <tr>
                                <td>
                                    <a href="{{ route('restaurant.viewOrder', $spO->unique_order_id) }}"
                                        class="letter-icon-title">{{ $spO->unique_order_id }}</a>
                                </td>
                                <td class="text-center accepted-order-actions">
                                    @if($spO->delivery_type == 2 && $spO->orderstatus_id == 2)
                                    <a href="{{ route('restaurant.markOrderReady', $spO->id) }}"
                                        class="btn btn-warning btn-labeled btn-labeled-left mr-2 actionAfterAccept"> <b><i
                                        class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardMarkAsReady')}} </a>
                                    @endif
                                    @if($spO->delivery_type == 2 && $spO->orderstatus_id == 7)
                                    <a href="{{ route('restaurant.markSelfPickupOrderAsCompleted', $spO->id) }}"
                                        class="btn btn-success btn-labeled btn-labeled-left mr-2 actionAfterAccept"> <b><i
                                        class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardMarkAsCompleted')}} </a>
                                    @endif
                                    @if($spO->delivery_type == 1)
                                    <span>--</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-semibold no-margin">{{ config('settings.currencyFormat') }}
                                    {{ $spO->total }}</span>
                                </td>
                                <td>
                                    {{ $spO->created_at->diffForHumans() }}
                                </td>
                                <td>
                                    {{ $spO->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center text-muted pb-2">
                        <h4> {{__('storeDashboard.dashboardNoOrders')}} </h4>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-12">
            <div class="panel panel-flat dashboard-main-col mt-4">
                <div class="panel-heading">
                    <h4 class="panel-title pl-3 pt-3"><strong>OnGoing Deliveries</strong></h4>
                    <hr>
                </div>
                @if(count($ongoingOrders))
                <div class="table-responsive">
                    <table class="table text-nowrap">
                        <thead>
                            <tr>
                                <th>{{__('storeDashboard.dashboardOrderID')}}</th>
                                <th class="text-center"><i class="
                                    icon-circle-down2"></i></th>
                                <th>{{__('storeDashboard.dashboardPrice')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderPlacedTime')}}</th>
                                <th>{{__('storeDashboard.dashboardOrderAcceptedTime')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ongoingOrders as $ogO)
                            <tr>
                                <td>
                                    <a href="{{ route('restaurant.viewOrder', $ogO->unique_order_id) }}"
                                        class="letter-icon-title">{{ $ogO->unique_order_id }}</a>
                                </td>
                                <td class="text-center accepted-order-actions">
                                    @if($ogO->delivery_type == 2 && $ogO->orderstatus_id == 2)
                                    <a href="{{ route('restaurant.markOrderReady', $ogO->id) }}"
                                        class="btn btn-warning btn-labeled btn-labeled-left mr-2 actionAfterAccept"> <b><i
                                        class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardMarkAsReady')}} </a>
                                    @endif
                                    @if($ogO->delivery_type == 2 && $ogO->orderstatus_id == 7)
                                    <a href="{{ route('restaurant.markSelfPickupOrderAsCompleted', $ogO->id) }}"
                                        class="btn btn-success btn-labeled btn-labeled-left mr-2 actionAfterAccept"> <b><i
                                        class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardMarkAsCompleted')}} </a>
                                    @endif
                                    @if($ogO->delivery_type == 1)
                                    <span>--</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-semibold no-margin">{{ config('settings.currencyFormat') }}
                                    {{ $ogO->total }}</span>
                                </td>
                                <td>
                                    {{ $ogO->created_at->diffForHumans() }}
                                </td>
                                <td>
                                    {{ $ogO->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center text-muted pb-2">
                        <h4> {{__('storeDashboard.dashboardNoOrders')}} </h4>
                    </div> 
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<input type="hidden" value="{{ csrf_token() }}" class="csrfToken">

<div id="newOrderModal" class="modal fade mt-5" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title mt-3"> <i class="icon-bell3 animated-bell"></i> </h5>
            </div>
            <div class="float-right pr-3 pt-3" style="position: absolute; right: 0;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="newOrdersData">
                <div class="d-flex justify-content-center">
                    <h3 class="text-muted"> {{__('storeDashboard.dashboardNoOrders')}} </h3>
                </div>
            </div>
            {{-- <div class="modal-footer">
                <button class="btn btn-default btn-labeled btn-lg mr-2 stopSound" data-popup="tooltip" data-placement="right" title="{{ __('storeDashboard.dashboardStopSound') }}" style="background-color: #F5F5F5;">
                <i class="icon-volume-mute5"></i>
                </button>
            </div> --}}
        </div>
    </div>
</div>
<script>
    $(function() {
    
        let notification = document.createElement('audio');
        let notificationFileRoute = '{{substr(url("/"), 0, strrpos(url("/"), '/'))}}/assets/backend/tones/{{ config('settings.restaurantNotificationAudioTrack') }}.mp3';
           notification.setAttribute('src', notificationFileRoute);
           notification.setAttribute('type', 'audio/mp3');
           // notification.setAttribute('muted', 'muted');
           notification.setAttribute('loop', 'true');
        
        $(".stopSound").click(function(event) {
            notification.pause();
            notification.currentTime = 0;
        });
        
      
        const newOrderJson = @json($newOrders);
        console.log(newOrderJson);
    
        setInterval(function() {
            $.ajax({
                url: '{{ route('restaurant.getNewOrders') }}',
                type: 'POST',
                dataType: 'json',
                data: {listed_order_ids: @json($newOrdersIds), _token: $('.csrfToken').val()},
            })
    
            .done(function(newArray) {
                console.log("New Array", newArray)
                console.log(newOrderJson.length);
                console.log(newArray.length );
    
                if (newArray.length > 0) {
                    //if new orders, then lenght will be greater, if order cancelled, then it should not go inside this 
                    console.log("NEW ORDER")
                
                    $('#newOrderModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
    
                    //play sound
                    notification.play();
    
                    console.log("New Array", newArray)
                    // const newOrder = newArray[0];
    
                    let newOrderData = "";
                    $.map(newArray, function(order, index) {
                        if(order.delivery_type == 2) {
                            var selfPickup = '<span class="badge badge-flat border-danger-800 text-default text-capitalize ml-1">{{__('storeDashboard.dashboardSelfPickup')}}</span>'
                        } else {
                            selfPickup = "";
                        }
    
                        let viewOrderURL = "{{ url('/store-owner/order') }}/" + order.unique_order_id;
    
                        newOrderData +='<div class="popup-order mb-3"><div class="text-center my-3 h5"><strong><span class="text-semibold no-margin">{{ config('settings.currencyFormat') }}'+order.total+'</span> <i class="icon-arrow-right5"></i> <a href="'+viewOrderURL+'">'+order.unique_order_id+'</a> <i class="icon-arrow-right5"></i>'+order.restaurant.name+'</strong> '+ selfPickup +'</div>';
    
                        newOrderData += '<div class="d-flex justify-content-center"><button data-id="'+order.id+'" class="btn btn-primary btn-labeled btn-labeled-left mr-2 acceptOrderBtn"><b><i class="icon-checkmark3 ml-1"></i></b> {{__('storeDashboard.dashboardAcceptOrder')}} </a> <button data-id="'+order.id+'" class="btn btn-danger btn-labeled btn-labeled-right mr-2 cancelOrderBtnPopup" data-popup="tooltip" data-placement="top" title="{{__('storeDashboard.dashboardDoubleClickMsg')}}"> <b><i class="icon-cross ml-1"></i></b> {{__('storeDashboard.dashboardCancelOrder')}}  </a></div></div>'
                        
    
                        $('#newOrdersData').html(newOrderData);
                        $('#newOrdersNoOrdersMessage').remove();
                    });
                    
                } else {
                    console.log("NO New Order")
                    //when orde has been accepted or denied, length will be 0, close the model
                    $('#newOrderModal').modal('hide');
                }
            })
            .fail(function() {
                console.log("error");
            })  
        }, {{ config("settings.restaurantNewOrderRefreshRate") }} * 1000); //all API every x seconds (config settings from admin)
        
        //reload page when popup closed
        $('#newOrderModal').on('hidden.bs.modal', function () {
            window.location.reload();
        })
    
    
        //on single click, accpet order and disable block
        $('body').on("click", ".acceptOrderBtn", function(e) {
            
            let context = $(this).parents('.popup-order');
            context.addClass('popup-order-processing').prepend('<div class="d-flex pt-2 pr-2 float-right"><i class="icon-spinner10 spinner"></i></div>')
            console.log("HERE", context);
    
            let id = $(this).attr("data-id");
            let acceptOrderUrl = "{{ url('/store-owner/orders/accept-order') }}/" +id;
            $.ajax({
                url: acceptOrderUrl,
                type: 'GET',
                dataType: 'JSON',
            })
            .done(function(data) {
                $(context).remove();
                //count number of order on popup, if 0 then remove popup
                if ($('.popup-order').length == 0) {
                    $('#newOrderModal').modal('hide');
                }
                $.jGrowl("{{__('storeDashboard.orderAcceptedNotification')}}", {
                    position: 'bottom-center',
                    header: '{{__('storeDashboard.successNotification')}}',
                    theme: 'bg-success',
                    life: '5000'
                });
            })
            .fail(function() {
                console.log("error")
                $.jGrowl("{{__('storeDashboard.orderSomethingWentWrongNotification')}}", {
                    position: 'bottom-center',
                    header: '{{__('storeDashboard.woopssNotification')}}',
                    theme: 'bg-warning',
                    life: '5000'
                });
            })
        });
        
        $('body').on("click", ".accpetOrderBtnTableList", function(e) {
            $(this).parents('.new-order-actions').addClass('popup-order-processing');
        });
    
        //on Single click donot cancel order table list
        $('body').on("click", ".cancelOrderBtnTableList", function(e) {
            return false;
        });
        $('body').on("dblclick", ".cancelOrderBtnTableList", function(e) {
            $(this).parents('.new-order-actions').addClass('popup-order-processing');
            window.location = this.href;
            return false;
        });
    
        //on Single click donot cancel order popup
        $('body').on("click", ".cancelOrderBtnPopup", function(e) {
            return false;
        });
        
        $('.actionAfterAccept').click(function(event) {
          $(this).parents('.accepted-order-actions').addClass('popup-order-processing');
        });
        
        //cancel order on double click popup
        $('body').on("dblclick", ".cancelOrderBtnPopup", function(e) {
    
            let context = $(this).parents('.popup-order');
            context.addClass('popup-order-processing').prepend('<div class="d-flex pt-2 pr-2 float-right"><i class="icon-spinner10 spinner"></i></div>')
            console.log("HERE", context);
    
            let id = $(this).attr("data-id");
            let cancelOrderURL = "{{ url('/store-owner/orders/cancel-order') }}/" +id;
            $.ajax({
                url: cancelOrderURL,
                type: 'GET',
                dataType: 'JSON',
            })
            .done(function(data) {
                $(context).remove();
                //count number of order on popup, if 0 then remove popup
                if ($('.popup-order').length == 0) {
                    $('#newOrderModal').modal('hide');
                }
                $.jGrowl("{{__('storeDashboard.orderCanceledNotification')}}", {
                    position: 'bottom-center',
                    header: '{{__('storeDashboard.successNotification')}}',
                    theme: 'bg-success',
                    life: '5000'
                });
            })
            .fail(function() {
                console.log("error");
                $.jGrowl("{{__('storeDashboard.orderSomethingWentWrongNotification')}}", {
                    position: 'bottom-center',
                    header: '{{__('storeDashboard.woopssNotification')}}',
                    theme: 'bg-warning',
                    life: '5000'
                });
            })
         });
    });
</script>
@endsection