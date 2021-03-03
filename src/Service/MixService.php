<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\MixService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MixService extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
    }

    public function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function getRef($length=5)
    {
        return substr(md5(substr(uniqid(''), 0, 20)), 0, $length);
    }


    public function arrayKeysExists(array $keys, array $arr)
    {
       return !array_diff_key(array_flip($keys), $arr);
    }

    public function arrayMergeRecursiveEx(array $array1, array $array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->arrayMergeRecursiveEx($merged[$key], $value);
            } else if (is_numeric($key)) {
                 if (!in_array($value, $merged)) {
                    $merged[] = $value;
                 }
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }
}
