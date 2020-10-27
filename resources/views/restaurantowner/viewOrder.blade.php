@extends('admin.layouts.master')
@section("title") {{__('storeDashboard.ovTitle')}}
@endsection
@section('content')
<style>
    .content-wrapper {
        overflow: hidden;
    }
</style>
<div class="content">
    <div class="row">
        <div class="col-xl-8" id="printThis">
            <div class="sidebar-category mt-4" style="box-shadow: 0 1px 6px 1px rgba(0, 0, 0, 0.05);background-color: #fff;">
                <div class="category-content">
                    <div href="#" class="btn btn-block content-group" style="text-align: left; background-color: #8360c3; color: #fff; border-radius: 0;"><strong style="font-size: 1.3rem;">{{ $order->unique_order_id }}</strong>
                        <a href="javascript:void(0)" id="printButton" class="btn btn-sm" style="color: #fff; border: 1px solid #ccc; float: right;">{{__('storeDashboard.ovPrint')}}</a>
                    </div>
                    <div class="p-3">
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovOrderPlaced')}}: </strong></label>
                            {{ $order->created_at}}  ( {{ $order->created_at->diffForHumans() }} )
                        </div>
                        <hr>
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovCustomerDetails')}}: </strong></label>
                            <br>
                            <p><b>{{__('storeDashboard.ovName')}}: </b> {{ $order->user->name }}</p>
                            <p><b>{{__('storeDashboard.ovEmail')}}: </b> {{ $order->user->email }}</p>
                            <p><b>{{__('storeDashboard.ovContactNumber')}}: </b> {{ $order->user->phone }}</p>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovStoreName')}}: </strong></label>
                            {{ $order->restaurant->name }}
                        </div>
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>Status:</strong></label>
                            <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                            @if ($order->orderstatus_id == 1) {{__('storeDashboard.opOrderStatus1')}} @endif
                            @if ($order->orderstatus_id == 2) {{__('storeDashboard.opOrderStatus2')}} @endif
                            @if ($order->orderstatus_id == 3) {{__('storeDashboard.opOrderStatus3')}} @endif
                            @if ($order->orderstatus_id == 4) {{__('storeDashboard.opOrderStatus4')}} @endif
                            @if ($order->orderstatus_id == 5) {{__('storeDashboard.opOrderStatus5')}} @endif
                            @if ($order->orderstatus_id == 6) {{__('storeDashboard.opOrderStatus6')}} @endif
                            @if ($order->orderstatus_id == 7) {{__('storeDashboard.opOrderStatus7')}} @endif
                            </span>
                            @if($order->delivery_type == 2)
                            <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                            Self-pickup
                            </span>
                            @endif
                            @if($order->accept_delivery !== null)
                            @if($order->orderstatus_id > 2 && $order->orderstatus_id  < 6)
                            <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                            {{__('storeDashboard.opDeliveryBy')}}: <b>{{ $order->accept_delivery->user->name }}</b>
                            </span>
                            @endif
                            @endif
                           
                        </div>
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovAddress')}}: </strong></label>
                            <p>{{ $order->address }}</p>
                        </div>
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovPaymentMode')}}: </strong></label>
                            <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                            {{ $order->payment_mode }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovCommentSuggestion')}}: </strong></label>
                            <span>
                            {{ $order->order_comment }}
                            </span>
                        </div>
                        <hr>
                        @php
                        function calculateAddonTotal($addons) {
                            $total = 0;
                            foreach ($addons as $addon) {
                                $total += $addon->addon_price;
                            }
                            return $total;
                        }
                        @endphp
                        <div class="text-right">
                            <div class="form-group">
                                <div class="clearfix"></div>
                                <div class="row">
                                <div class="col-md-12 p-2 mb-3" style="background-color: #f7f8fb; float: right; text-align: left;">
                                    @foreach($order->orderitems as $item)
                                    <div>
                                    <div class="d-flex mb-1 align-items-start" style="font-size: 1.2rem;">
                                        <span class="badge badge-flat border-grey-800 text-default mr-2">x{{ $item->quantity }}</span>
                                        <strong class="mr-2" style="width: 100%;">{{ $item->name }}</strong>
                                        
                                        <span class="badge badge-flat border-grey-800 text-default">{{ config('settings.currencyFormat') }} {{ ($item->price +calculateAddonTotal($item->order_item_addons)) * $item->quantity }}</span>
                                    </div>
                                    @if(count($item->order_item_addons))
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>{{__('storeDashboard.ovTableCategory')}}</th>
                                                    <th>{{__('storeDashboard.ovTableAddon')}}</th>
                                                    <th>{{__('storeDashboard.ovTablePrice')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($item->order_item_addons as $addon)
                                                <tr>
                                                    <td>{{ $addon->addon_category_name }}</td>
                                                    <td>{{ $addon->addon_name }}</td>
                                                    <td>{{ config('settings.currencyFormat') }}{{ $addon->addon_price }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        </div>
                                        @endif
                                        @if(!$loop->last)
                                            <div class="mb-2" style="border-bottom: 2px solid #c9c9c9;"></div>
                                        @endif
                                        </div>
                                    @endforeach
                                </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group">
                                <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovCoupon')}}: </strong></label>
                                @if($order->coupon_name == NULL) {{__('storeDashboard.opNone')}} @else
                                <span class="badge badge-flat border-grey-800 text-default text-capitalize">
                                {{ $order->coupon_name }}
                                </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovStoreCharge')}}: </strong></label>
                                {{ config('settings.currencyFormat') }}{{ $order->restaurant_charge }}
                            </div>
                            <div class="form-group">
                                <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovDeliveryCharge')}}: </strong></label>
                                {{ config('settings.currencyFormat') }}{{ $order->delivery_charge }}
                            </div>
                            @if(!$order->tax == NULL)
                            <div class="form-group">
                                <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovTax')}}: </strong></label>
                                {{ $order->tax }}%
                                @endif
                            </div>
                            <hr>
                            <div class="text-right">
                                <div class="form-group">
                                    <h3>
                                        <label class="control-label no-margin text-semibold mr-2"><strong>{{__('storeDashboard.ovTotal')}}</strong></label>
                                        <strong> {{ config('settings.currencyFormat') }} {{ $order->total }} </strong>
                                    </h3>
                                </div>
                                @if($order->delivery_type == 2)
                                <div class="form-group">
                                    <h3>
                                        <label class="control-label no-margin text-semibold mr-2"><strong>Payable: </strong></label>
                                        <strong> {{ config('settings.currencyFormat') }} {{ $order->payable }} </strong>
                                    </h3>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-5 mt-2">
            <div class="col-md-8">
                <div class="d-flex justify-content-end">
                    @if($order->orderstatus_id == 1)
                    <a href="{{ route('restaurant.acceptOrder', $order->id) }}"
                        class="mr-2 btn btn-lg acceptOrderBtn btn-primary"> {{__('storeDashboard.ovAccept')}} <i
                        class="icon-checkmark3 ml-1"></i></a>
                    <a href="{{ route('restaurant.cancelOrder', $order->id) }}"
                        class="btn btn-lg cancelOrderBtn btn-danger" data-popup="tooltip" data-placement="top" title="{{__('storeDashboard.ovTooltipCancel')}}"> {{__('storeDashboard.ovCancel')}} <i
                        class="icon-cross ml-1"></i></a>
                    @endif
                    @if($order->delivery_type == 2 && $order->orderstatus_id == 2)
                    <a href="{{ route('restaurant.markOrderReady', $order->id) }}"
                        class="btn btn-lg btn-warning"> {{__('storeDashboard.ovMarkAsReady')}} <i
                        class="icon-checkmark3 ml-1"></i></a>
                    @endif
                    @if($order->delivery_type == 2 && $order->orderstatus_id == 7)
                    <a href="{{ route('restaurant.markSelfPickupOrderAsCompleted', $order->id) }}"
                        class="btn btn-lg btn-success"> {{__('storeDashboard.ovMarkAsCompleted')}} <i
                        class="icon-checkmark3 ml-1"></i></a>
                    @endif
                </div>
            </div>
        </div>
</div>
<script>
    $('#printButton').on('click',function(){
        $('#printThis').printThis();
    })
    //on single click, accpet order and disable button
    $('body').on("click", ".acceptOrderBtn", function(e) {
        $(this).addClass('pointer-none');
    });
    
    //on Single click donot cancel order
    $('body').on("click", ".cancelOrderBtn", function(e) {
        return false;
    });
    
    //cancel order on double click
    $('body').on("dblclick", ".cancelOrderBtn", function(e) {
        $(this).addClass('pointer-none');
        window.location = this.href;
        return false;
     });
</script>
@endsection