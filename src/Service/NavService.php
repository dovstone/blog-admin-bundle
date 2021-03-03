<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NavService extends AbstractController
{
    private $please;
    protected $previousContainer;
    private $breadcrumbBuilt;
    private $appUiD;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->urlService = $this->please->getBundleService('url');
        //
        $this->appUiD = sha1(getenv('APP_NAME'));
    }

    public function asOptions($criteria = [])
    {
        $options = '';
        $navs = $this->navRepo->findBy($criteria);
        if ($navs) {
            foreach ($navs as $nav) {
                $options .= '<option value="' . $nav->getId() . '">' . $nav->getTitle() . '</option>';
            }
        }
        return $options;
    }

    public function getNavigationHtmlStructure($widget)
    {

        if( 
            isset($widget->dynamic_context) // mandatory
            &&
            $dynamic_context = json_decode($widget->dynamic_context)
            
        ){

            $navs = $this->navRepo->find((int) $dynamic_context->menuid);


            $nav_built = '';

            if ($navs) {
                $params = json_decode($navs->getParams());

                if (!is_null($params)) {
                    foreach ($params as $param) {
                        $nav_built .= $this->buildNav($param);
                    }
                }
            }

            $id = (isset($dynamic_context->menuulid) ? ' id="' . $dynamic_context->menuulid . '"' : '');
            $class = (isset($dynamic_context->menuulclass) ? ' class="' . $dynamic_context->menuulclass . '"' : '');
            
            $mobileMenuIcon = (isset($dynamic_context->mobilemenuicon) ? $dynamic_context->mobilemenuicon : 'fa-bars');
            $mobileMenuTitle = (isset($dynamic_context->mobilemenutitle) ? $dynamic_context->mobilemenutitle : 'MENU');
            $mobileMenuAttrs = (isset($dynamic_context->mobilemenuattrs) ? $dynamic_context->mobilemenuattrs : '');

            $mobileNavTogglerView = '<ul class="mobile-menu-toggler"><li class="item-depth-0 item-mobile-nav-toggler"><a href="#" ' . $mobileMenuAttrs . '><div class="mobile-menu-toggler-icon-container"><i class="fa ' . $mobileMenuIcon . '"></i> <span class="mobile-menu-icon-title">' . $mobileMenuTitle . '</span></div></a></li></ul>';

            return '<nav' . $id . $class . '><ul class="menu">' . $nav_built . '</ul>' .  $mobileNavTogglerView . '</nav>';
        
        }
        return '';
    }

    public function getBreadcrumb($home)
    {
        $post = $this->please->getGlobal("post");

        $breadcrumbBuilt = '';

        $this->breadcrumbRecursively($post);
        if ($this->breadcrumbBuilt) {
            for ($i = sizeof($this->breadcrumbBuilt) - 1; $i > -1; $i--) {
                $breadcrumbBuilt .= $this->breadcrumbBuilt[$i];
            }
        }
        return '<li><a href="' . $this->urlService->getUrl() . '">' . $home . '</a></li>' . $breadcrumbBuilt;
    }

    private function buildNav($param)
    {
        return $this->pageService->asUnorderedList((int) $param->postId, $param->navTemplate);
    }

    private function breadcrumbRecursively($parent): void
    {
        if ( !is_null($parent) && isset($parent->getInfo()->href) && $parent->getInfo()->href !== '/') {

            $info = $parent->getInfo();
            $parentHref = $info->href;

            $url_match = (trim($parentHref, '/') === $this->urlService->getCurrentUrl());

            $item = !$url_match ? '<a href="' . $parentHref . '">' . $info->title . '</a>' : '<span>' . $info->title . '</span>';
            $active_class = $url_match ? ' class="active" ' : '';

            $this->breadcrumbBuilt[] = '<li' . $active_class . '>' . $item . '</li>';
            if ($parent->parent !== null) {
                //here we go again
                
                $pParent = $this->please->fetchBundleEager($parent->parent);
                $info = $pParent->getInfo();
                $info->href = $this->please->getBundleService('post')->getPostHref($pParent);
                $pParent = $pParent->setInfo($info);

                $this->breadcrumbRecursively( $this->please->fetchBundleEager($pParent) );
            }
        }
    }
}
