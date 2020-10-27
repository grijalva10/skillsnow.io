<?php

namespace App;

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
