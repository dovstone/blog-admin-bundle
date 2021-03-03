<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EnvService extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
    }
    
    public function getAppEnv($var = 'APP_ENV')
    {
        return $_SERVER[ $var ];
        //return getenv($var);
    }
    
    public function isLocalHost()
    {
        if( strpos($this->please->getBundleService('url')->getCurrentUrl(), 'http://localhost') !== false ) {
            return true;
        }
        return false;
    }

    public function getAppSecret()
    {
        return $this->getAppEnv('APP_SECRET');
    }

    public function getAppName()
    {
        return $this->getAppEnv('APP_NAME');
    }

    public function getAppBaseUrl()
    {
        $APP_HOST = trim($this->getAppEnv('APP_HOST'), '/');
        $APP_PREFIX = trim($this->getAppEnv('APP_PREFIX'), '/');
        $APP_PREFIX = ($APP_PREFIX == '') ? '' : '/' . $APP_PREFIX;
        return $APP_HOST . $APP_PREFIX;
    }

    public function getAppDir()
    {
        $app_base_url = $this->getAppBaseUrl();

        if (false !== strpos($app_base_url, 'http://localhost') || false !== strpos($app_base_url, 'http://lvh.me')) {
            $exploded = explode('/', $app_base_url);
            return end($exploded);
        }
        return '';
    }

    public function getEncorePort()
    {
        return $this->getAppEnv('ENCORE_PORT');
    }
}
