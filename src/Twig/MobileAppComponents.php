<?php

namespace DovStone\Bundle\BlogAdminBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;
use Symfony\Component\Filesystem\Filesystem;
use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;

class MobileAppComponents extends AbstractExtension
{
    private $please;
    //
    private $appUiD;

    public function __construct( PleaseService $please )
    {
        $this->please = $please;
        //
        $this->filesystem = new Filesystem();
        //
        $this->appUiD = sha1(getenv('APP_NAME'));
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('getAppComponent', array($this, 'getAppComponent')),
        );
    }

    public function getAppComponent($name, $params = null)
    {
        return new Markup($this->$name($params), 'UTF-8');
    }

    private function tabTargetButton($params)
    {
      $params = array_merge($params, [
        'cell cell-33 btn btn-block bttn-clean', '', 'home', 'Mon text'
      ]);
      return '<button class="cell '.$params[0].' btn btn-block bttn-clean" data-tab-target="'.$params[1].'">
        <i class="fa fa-'.$params[2].'"></i>
        <span>'.$params[3].'</span>
      </button>';
    }
}
