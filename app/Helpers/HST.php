<?php

namespace App\Helpers;

class HST
{
    /**
     * @param $req
     * @return mixed
     */
    public function returnHST($req)
    {
        return $req->getHttpHost();
    }
}
