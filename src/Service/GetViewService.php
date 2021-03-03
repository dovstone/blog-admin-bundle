<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetViewService extends AbstractController
{
    private $filesystem;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
    }

    public function getRenderView($view, $params = [])
    {
        return $this->renderView($view, $params);
    }

    public function getViewsDir(string $dir = ''): string
    {
        return $this->please->previousContainer->get('kernel')->getProjectDir() . "/vendor/dovstone/blog-admin-bundle/src/Resources/views/$dir";
    }

    public function renderViewsAlias($view, $params = [])
    {   
        return $this->renderView('@DovStoneBlogAdminBundle/' . $view, $params);
    }
}
