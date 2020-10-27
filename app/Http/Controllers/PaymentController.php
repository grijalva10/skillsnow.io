<?php

namespace App\Http\Controllers;

use App\Order;
use App\PaymentGateway;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Ixudra\Curl\Facades\Curl;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function getPaymentGateways()
    {
        $paymentGateways = PaymentGateway::where('is_active', 1)->get();
        return response()->json($paymentGateways);
    }

    /**
     * @param Request $request
     */
    public function togglePaymentGateways(Request $request)
    {
        $paymentGateway = PaymentGateway::where('id', $request->id)->first();

        $activeGateways = PaymentGateway::where('is_active', '1')->get();

        if (!$paymentGateway->is_active || count($activeGateways) > 1) {
            $paymentGateway->toggleActive()->save();
            $success = true;
            return response()->json($success, 200);
        } else {
            $success = false;
            return response()->json($success, 401);
        }
    }

    /**
     * @param Request $request
     */
    public function processRazorpay(Request $request)
    {
        $api_key = config('settings.razorpayKeyId');
        $api_secret = config('settings.razorpayKeySecret');

        $api = new Api($api_key, $api_secret);

        try {
            $response = Curl::to('https://api.razorpay.com/v1/orders')
                ->withOption('USERPWD', "$api_key:$api_secret")
                ->withData(array('amount' => $request->totalAmount * 100, 'currency' => config('settings.currencyId'), 'payment_capture' => 1))
                ->post();

            $response = json_decode($response);
            $response = [
                'razorpay_success' => true,
                'response' => $response,
            ];
            return response()->json($response);
        } catch (\Throwable $th) {
            $response = [
                'razorpay_success' => false,
                'message' => $th->getMessage(),
            ];
            return response()->json($response);
        }
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function processMercadoPago(Request $request, $id)
    {
        $order = Order::where('id', $id)->where('orderstatus_id', '8')->where('payment_mode', 'MERCADOPAGO')->first();

        if ($order == null) {
            echo 'Order not found, already paid or payment method is different.';
        } else {

            $amount = number_format((float) $order->total, 2, '.', '');

            \MercadoPago\SDK::setAccessToken(config('settings.mercadopagoAccessToken'));

            $preference = new \MercadoPago\Preference();

            // Crea un ítem en la preferencia
            $item = new \MercadoPago\Item();
            $item->title = 'Online Service';
            $item->quantity = 1;
            $item->unit_price = $amount;
            $preference->items = array($item);

            // $preference->back_urls = array(
            //     'success' => 'http://localhost/swiggy-laravel-react/public/api/payment/return-mercado-pago',
            //     'pending' => 'http://localhost/swiggy-laravel-react/public/api/payment/return-mercado-pago',
            //     'failure' => 'http://localhost/swiggy-laravel-react/public/api/payment/return-mercado-pago',
            // );

            $preference->back_urls = array(
                'success' => 'https://' . $request->getHttpHost() . '/public/api/payment/return-mercado-pago',
                'pending' => 'https://' . $request->getHttpHost() . '/public/api/payment/return-mercado-pago',
                'failure' => 'https://' . $request->getHttpHost() . '/public/api/payment/return-mercado-pago',
            );

            $preference->auto_return = 'all';
            $preference->save();

            // Save preference ID in database
            $order->transaction_id = $preference->id;
            $order->save();
            // dd($preference);
            return redirect()->away($preference->init_point);

        }

    }
    /**
     * @param Request $request
     */
    public function returnMercadoPago(Request $request)
    {
        $order = Order::where('transaction_id', $request->preference_id)->where('orderstatus_id', '8')->where('payment_mode', 'MERCADOPAGO')->first();

        $txnStatus = $request->collection_status;

        if ($order == null) {
            echo 'Order not found, already paid or payment method is different.';
        } else {
            $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

            if ($restaurant->auto_acceptable) {
                $orderstatus_id = '2';
            } else {
                $orderstatus_id = '1';
            }

            if ($txnStatus == 'approved') {
                $order->orderstatus_id = $orderstatus_id;
                $order->save();
                $redirectUrl = 'https://' . $request->getHttpHost() . '/running-order/' . $order->unique_order_id;
                // $redirectUrl = 'http://localhost:3000/running-order/' . $order->unique_order_id;
                return redirect()->away($redirectUrl);
            } else {
                $orderUpdate = Order::find($order->id);
                $order->orderstatus_id = 9;
                $order->save();
                $redirectUrl = 'https://' . $request->getHttpHost() . '/my-orders';
                return redirect()->away($redirectUrl);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function acceptStripePayment(Request $request)
    {
        $user = auth()->user();

        if (in_array('ideal', $request->payment_method_types)) {
            //some logic later to be added
        }

        if ($user) {
            \Stripe\Stripe::setApiKey(config('settings.stripeSecretKey'));

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $request->amount,
                'payment_method' => $request->id,
                'payment_method_types' => $request->payment_method_types,
                'currency' => $request->currency,
                // 'return_url' => route('stripeRedirectCapture'),
            ]);

            return response()->json($intent);

        } else {
            return response()->json(['success' => false], 401);
        }
    }

    /**
     * @param Request $request
     */
    public function stripeRedirectCapture(Request $request)
    {
        // \Log::info($request->all());
        \Stripe\Stripe::setApiKey(config('settings.stripeSecretKey'));
        $intent = \Stripe\PaymentIntent::retrieve($request->payment_intent);

        if ($request->has('order_id')) {
            //get the order ID from url params
            $order = Order::where('id', $request->order_id)->first();

            if ($intent->status == 'succeeded') {
                // dd('Success');
                //check if the order id of that order is 8 (waiting payment)
                if ($order && $order->orderstatus_id == 8) {

                    //change orderstatus id, process notification and stuff
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

                    if ($restaurant->auto_acceptable) {
                        $orderstatus_id = '2';
                        if (config('settings.enablePushNotificationOrders') == 'true') {
                            //to user
                            $notify = new PushNotify();
                            $notify->sendPushNotification('2', $newOrder->user_id, $newOrder->unique_order_id);
                        }
                    } else {
                        $orderstatus_id = '1';
                        if (config('settings.smsRestaurantNotify') == 'true') {
                            $restaurant_id = $request['order'][0]['restaurant_id'];
                            $this->smsToRestaurant($restaurant_id, $orderTotal);
                        }
                    }

                    $order->orderstatus_id = $orderstatus_id;
                    $order->save();

                    //redirect to running order page
                    $redirectUrl = 'https://' . $request->getHttpHost() . '/running-order/' . $order->unique_order_id;
                    // $redirectUrl = 'http://localhost:3000/running-order/' . $order->unique_order_id;

                    return redirect()->away($redirectUrl);
                }
            } else {
                // dd("Failed");
                $order->orderstatus_id = 9; //payment failed
                $order->save();

                $redirectUrl = 'https://' . $request->getHttpHost() . '/running-order/' . $order->unique_order_id;
                // $redirectUrl = 'http://localhost:3000/running-order/' . $order->unique_order_id;

                return redirect()->away($redirectUrl);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function processPaymongo(Request $request)
    {
        $error = '';

        $paymongoPK = config('settings.paymongoPK');

        $validator = Validator::make($request->all(), [
            'ccNum' => 'required',
            'ccExp' => 'required',
            'ccCvv' => 'required|numeric',
            'amount' => 'required|numeric|min:100',
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            $error = 'Please check if you have filled in the form correctly. Minimum order amount is PHP 100.';
        }

        $ccNum = str_replace(' ', '', $request->ccNum);
        $ccExp = $request->ccExp;
        $ccCvv = $request->ccCvv;
        $amount = $request->amount;
        $name = $request->name;
        $email = $request->email;
        $phone = $request->phone;
        $ccExp = (explode('/', $ccExp));
        $ccMon = $ccExp[0];
        $ccYear = $ccExp[1];

        // Create payment method
        $paymentMethodData = array(
            'data' => array(
                'attributes' => array(
                    'details' => array(
                        'card_number' => $ccNum,
                        'exp_month' => intval($ccMon),
                        'exp_year' => intval($ccYear),
                        'cvc' => $ccCvv,
                    ),
                    'billing' => array(
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ),
                    'type' => 'card',
                ),
            ),
        );

        $paymentMethodUrl = 'https://api.paymongo.com/v1/payment_methods';
        $paymentMethod = $this->apiPaymongo($paymentMethodUrl, $paymentMethodData);

        if ($paymentMethod->status == 200) {
            $paymentMethodId = $paymentMethod->content->data->id;
        } else {
            foreach ($paymentMethod->content->errors as $error) {
                $error = $error->detail . ' ';
            }
        }

        // Create payment intent
        if (isset($paymentMethodId)) {
            // Create payment intent
            $paymentIntentData = array(
                'data' => array(
                    'attributes' => array(
                        'amount' => $amount * 100,
                        'payment_method_allowed' => array(
                            0 => 'card',
                        ),
                        'payment_method_options' => array(
                            'card' => array(
                                'request_three_d_secure' => 'automatic',
                            ),
                        ),
                        'currency' => config('settings.currencyId'),
                        'description' => 'Food Delivery',
                        'statement_descriptor' => config('settings.storeName'),
                    ),
                ),
            );

            $paymentIntentUrl = 'https://api.paymongo.com/v1/payment_intents';
            $paymentIntent = $this->apiPaymongo($paymentIntentUrl, $paymentIntentData);

            if ($paymentIntent->status == 200) {
                $paymentIntentId = $paymentIntent->content->data->id;
            } else {
                foreach ($paymentIntent->content->errors as $error) {
                    $error = $error->detail . ' ';
                }
            }

        }

        // Attach payment method with payment intent
        if ((isset($paymentMethodId)) && (isset($paymentIntentId))) {
            $returnUrl = 'https://' . $request->getHttpHost() . '/public/api/payment/handle-process-paymongo/' . $paymentIntentId;
            $attachPiData = array(
                'data' => array(
                    'attributes' => array(
                        'payment_method' => $paymentMethodId,
                        'client_key' => $paymongoPK,
                        'return_url' => $returnUrl,
                    ),
                ),
            );

            // 'https://' . $request->getHttpHost() . '/my-orders'
            $attachPiUrl = 'https://api.paymongo.com/v1/payment_intents/' . $paymentIntentId . '/attach';
            $attachPi = $this->apiPaymongo($attachPiUrl, $attachPiData);

            if ($attachPi->status == 200) {
                $attachPiStatus = $attachPi->content->data->attributes->status;
            } else {
                foreach ($attachPi->content->errors as $error) {
                    $error = $error->detail . ' ';
                }
            }

        }

        if (($error == '') && ($attachPiStatus == 'succeeded')) {
            $response = [
                'paymongo_success' => true,
                'token' => $paymentIntentId,
                'status' => $attachPiStatus,
            ];
        } elseif (($error == '') && ($attachPiStatus == 'awaiting_next_action')) {
            $response = [
                'paymongo_success' => true,
                'token' => $paymentIntentId,
                'redirect_url' => $attachPi->content->data->attributes->next_action->redirect->url,
                'status' => $attachPiStatus,
            ];
        } else {
            $response = [
                'paymongo_success' => true,
                'error' => $error,
            ];
        }

        return response()->json($response);

    }

    /**
     * @param Request $request
     */
    public function handlePayMongoRedirect(Request $request, $id)
    {
        //pi_q8NhrK7VoZTLwAYBnXU5eNL7
        $order = Order::where('transaction_id', $id)->where('orderstatus_id', '8')->where('payment_mode', 'PAYMONGO')->first();

        if ($order == null) {
            echo 'Order not found, already paid or payment method is different.';
        } else {
            //change orderstatus id, process notification and stuff
            $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

            if ($restaurant->auto_acceptable) {
                $orderstatus_id = '2';
                if (config('settings.enablePushNotificationOrders') == 'true') {
                    //to user
                    $notify = new PushNotify();
                    $notify->sendPushNotification('2', $newOrder->user_id, $newOrder->unique_order_id);
                }
            } else {
                $orderstatus_id = '1';
                if (config('settings.smsRestaurantNotify') == 'true') {
                    $restaurant_id = $request['order'][0]['restaurant_id'];
                    $this->smsToRestaurant($restaurant_id, $orderTotal);
                }
            }

            $order->orderstatus_id = $orderstatus_id;
            $order->save();

            $order->orderstatus_id = $orderstatus_id;
            $order->save();
            $redirectUrl = 'https://' . $request->getHttpHost() . '/running-order/' . $order->unique_order_id;
            // $redirectUrl = 'http://localhost:3000/running-order/' . $order->unique_order_id;
            return redirect()->away($redirectUrl);
        }
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
     */
    public function apiPaymongo($url, $data)
    {
        $paymongoSK = config('settings.paymongoSK');

        $response = Curl::to($url)
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic ' . base64_encode($paymongoSK))
            ->withData($data)
            ->returnResponseObject()
            ->asJson()
            ->post();
        return $response;
    }
};
