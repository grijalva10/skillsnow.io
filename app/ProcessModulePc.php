<?php

namespace App;

use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Jenssegers\Agent\Agent;

class ProcessModulePc
{
    /**
     * @param $pc
     */
    public function process($pc, $host)
    {
        $agent = new Agent();
        $response = Curl::to('https://stackcanyon.com/api/module/check-purchase')
            ->withData(array(
                'purchase_code' => $pc,
                'host' => $host,
                'device' => $agent->device(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
            ))->post();
        $check = json_decode($response);
        if (isset($check->error)) {
            $response = [
                'success' => false,
                'message' => $check->error,
            ];
            return $response;
        }

        $response = [
            'success' => true,
        ];
        return $response;
    }

}
