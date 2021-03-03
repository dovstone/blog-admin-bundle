<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Markup;

class ViewService extends AbstractController
{
    private $please;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
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

    public function getBundleEmptyListView($message, $icon=null)
    {      
        if( is_null($icon) ){ $icon = 'ban'; }
        return $this->renderViewsAlias('partials/empty-list.html.twig', compact('message', 'icon'));
    }
    

    public function getSection($section, $params=[])
    {
        return new Markup('<div sg-section="'.$section.'">' . $this->container->get('twig')->render("sections/$section.html.twig", $params) . '</div>', 'UTF-8');
    }

    public function s($section, $params=[])
    {
        return $this->getSection($section, $params);
    }

    public function getPartial($partial, $params=[])
    {
        return new Markup($this->container->get('twig')->render("partials/$partial.html.twig", $params), 'UTF-8');
    }

    public function p($partial, $params=[])
    {
        return $this->getPartial($partial, $params);
    }

    public function sanitizeFinalView($view)
    {
        $envService = $this->please->getBundleService('env');

        $oldHosts = $replacements = [];

        $old_hosts = $envService->getAppEnv('APP_OLD_HOSTS');
        if( !empty($old_hosts) ){
            $baseUrl = $this->please->getBundleService('url')->getBaseUrl();
            $old_hosts = explode('|', $old_hosts);
            foreach ($old_hosts as $old_host) { 

                $old_host = $old_host . '/';
                $lastChar = $baseUrl[strlen($baseUrl)-1];
                $baseUrl = $baseUrl . ($lastChar == '/' ? '' : '/');

                $oldHosts[] = $old_host;
                $replacements[] = $baseUrl;

                    // any json version
                    $oldHosts[] = trim(json_encode($old_host), '"');
                    $replacements[] = trim(json_encode($baseUrl), '"');
            }
        }

        //dd($oldHosts, $replacements);

        // lets interprete shortcode only on front
        if( strpos($this->please->getBundleService('url')->getQueryString(), 'swagg') === false ){
            // lets handle shortcodes
            // '<div sg-shortcode>[ shortcode_here ]</div>'
            //$view = preg_replace_callback('/(<div sg-shortcode)([\s\S]+)(>)([)([\s\S]+)(\])(<\/div>)/', function($m) {
            
            //$view = preg_replace_callback('/(>\[)([a-zA-Z\s="0-9-]+)(\]<\/div>)/', function($m) {
            $view = preg_replace_callback('/(>\[)(.*)(\]<\/div>)/', function($m) { // edited on 20-10-10 16:32
            //$view = preg_replace_callback('/(sg-shortcode)(>)([\S\s]+)(<\/div>)/', function($m) {
                return $this->please->getBundleService('shortcode')->getView($m[2]);
                //return $this->please->getBundleService('shortcode')->getView( trim(trim($m[4], '[')) );
            }, $view);
        }

        $view = str_ireplace($oldHosts, $replacements, $view);


        // themes path removal
        // lets preg_replace from "themes/demo/assets" to "theme/assets"
        $view = preg_replace_callback('/(themes\/)([a-zA-Z0-9-]+)(\/assets)/', function($m) {
            return 'theme/assets';
        }, $view);

        if( $envService->getAppEnv('MINIFY_OUTPUT') == 'true' ){
            //minified
            $__PhpHtmlCssJsMinifierService = new __PhpHtmlCssJsMinifierService();
            $view = $__PhpHtmlCssJsMinifierService->getMinifiedHtml( $view );
            $view = $__PhpHtmlCssJsMinifierService->getMinifiedCss( $view );
        }
        if( $envService->getAppEnv('LAZY_LOAD_IMAGES') == 'true' ){
            $view = str_ireplace('<img src', '<img data-lazy-src', $view);
            $view = str_ireplace('</body>', '<script>$(function(){ __.lazy({ el: $("img[data-lazy-src]").not(".no-lazy") }); })</script></body>', $view);
        }

        //
        return new Markup($view, 'UTF-8');
    }
}
