<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\__PhpHtmlCssJsMinifierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Markup;

class UrlService extends AbstractController
{
    protected $please;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
    }

    public function getHome()
    {
        return $this->getUrl();
    }

    public function getRouterContext()
    {
        return $this->please->previousContainer->get('router')->getContext();
    }

    public function getRequestStack()
    {
        return $this->please->previousContainer->get('request_stack');
    }

    public function getQueryString(): string
    {
        return $this->getRouterContext()->getQueryString();
    }

    public function getUrl($href = '/'): string
    {
        // Ensuring we can return a proper absolute url weither the given href
        // for exemple even from C:\Apps\Web\sf4\public\uploads\2018\03
        $href = str_ireplace($this->please->previousContainer->get('kernel')->getProjectDir() . '/public', '', $href);

        // Replacing trailing backslash(es) to slash
        $href = preg_replace('~\\\+~', '/', $href);

        // Replacing slash(es) to slash
        //$href = $this->getAppBaseUrl() . '/' . trim(preg_replace('~/+~', '/', $href), '/');
        $href = $this->getBaseUrl() . '/' . trim(preg_replace('~/+~', '/', $href), '/');

        // Returning the absolute url
        return $href;
    }
    
    public function getHeadAssets( $arr=[] ) : string
    {
        $output = ''."\n";

        if( $arr ){
            foreach ($arr as $key => $p) {
                switch ($key) {
                    case 'prevent':
                            $output .= '<style>html .pending::before{background:rgba(255, 255, 255, 0.32) url("'. $this->getCDN('swagg/assets/img/loading-spinner.gif') .'") center center no-repeat}</style>'."\n";
                            
                            $output .= !in_array('imports', $p) ? '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->getCDN('swagg/assets/css/imports.css').'"/>'."\n" : '';
                            $output .= !in_array('animate', $p) ? '<link rel="stylesheet" type="text/css" media="screen" href="'. $this->getCDN('animate/css/animate.min.css') .'" />'."\n" : '';
                            $output .= !in_array('basic', $p) ? '<link rel="stylesheet" type="text/css" media="screen" href="'. $this->getCDN('swagg/assets/css/basic.css?v='. rand(0, 999)) .'" />'."\n" : '';
                            $output .= !in_array('tabs', $p) ? '<link rel="stylesheet" type="text/css" media="screen" href="'. $this->getCDN('swagg/assets/css/tabs.css?v='. rand(0, 999)) .'" />'."\n" : '';
                            $output .= !in_array('tiles', $p) ? '<link rel="stylesheet" type="text/css" media="screen" href="'. $this->getCDN('swagg/assets/css/tiles.css?v='. rand(0, 999)) .'" />'."\n" : '';
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }
        }
        else {
            $output .= '<style>html .pending::before{background:rgba(255, 255, 255, 0.32) url("'. $this->getCDN('swagg/assets/img/loading-spinner.gif') .'") center center no-repeat}</style>'."\n";
            $output .= $this->getCDN([ 'swagg/assets/css/imports', 'animate/css/animate.min', 'swagg/assets/css/basic|v', 'swagg/assets/css/tabs', 'swagg/assets/css/tiles' ]);
        }
        
        $output .= $this->getCDN([ 'jquery/jquery-1.11.3.min', 'underscore/js/underscore', 'swagg/helpers|v' ], 'js');
        
        /*if( !$this->isBackoffice() ){
            if( $this->getEnv() == 'dev' && $this->isLocalHost() ){
                $output .= '<link id="app_main_css" rel="stylesheet" href="http://localhost:'.$this->getEnv('ENCORE_PORT').'/assets/css/app.css?v='. rand(0, 999) .'">'."\n";
            }
            else {
                $output .= '<link id="app_main_css" rel="stylesheet" href="'.$this->getUrl('assets/css/app.css') . '?v='. rand(0, 999) .'">'."\n";
            }
        }*/

        return new Markup($output, 'UTF-8');
    }
    
    public function getCDN($path, $extension='css'): string
    {
        if( is_string($path) ){
            return new Markup(trim($this->please->getBundleService('env')->getAppEnv('CDN_HOST'), '/'). '/' . trim(preg_replace('~/+~', '/', $path), '/'), 'UTF-8');
        }
        elseif(is_array($path)){
            
            $output = '';

            foreach($path as $path_){

                $v = strpos($path_, '|v') !== false ? '?v='.rand(0, 999) : '';

                $path_ = str_replace('|v', '', $path_);
                $id = str_ireplace(['.css','.js','.','/', '-'], ['_','_','_','_','_'], $path_);
                $path_ = $this->getCDN($path_.'.'.$extension);
                
                switch($extension){
                    case 'css': $output .= '<link data-url="'.$path_.'" id="link_'.$id.'" rel="stylesheet" href="'.$path_.$v.'">' ."\n";
                        break;
                    case 'js' : $output .= '<script data-url="'.$path_.'" id="script_'.$id.'" src="'.$path_.$v.'"></script>' ."\n";
                        break;
                    default : $output .= '<img data-url="'.$path_.'" id="img_'.$id.'" src="'.$path_.$v.'">' ."\n";
                }
            }
            return new Markup($output, 'UTF-8');
        }
        return null;
    }
    
    public function getUsualsJsCDN($adds = [], $v=false): string
    {
        $v = $v ? '|v' : '';
        return $this->getCDN(array_merge([
            'swagg/services/window'.$v, 'swagg/services/bindonce'.$v, 'swagg/services/please'.$v, 'swagg/services/ajaxify'.$v, 'swagg/services/scss2css'.$v
        ], $adds), 'js');
    }

    public function getRelativeUrl($href = '/', $replace = ''): string
    {
        //replacing the host and app dev dir
        $href = str_ireplace($this->getBaseUrl() . $replace, '', $href);
        return $href;
    }

    public function getPath($path, $params=[])
    {
        $symfPath = $this->please->previousContainer->get('router')->generate($path, $params);
        return $this->please->getBundleService('env')->getAppEnv('APP_HOST') . $symfPath;
    }

    public function getUrlsManagedView(string $view)
    {
        return $view;

        //return $view;
        $devD = $this->please->getBundleService('view')->getViewsDir('storage/devDirectories.json');
        $devD = json_decode(file_get_contents($devD));

        $devDirectories = $replacement = [];
        foreach ($devD as $devDirectory => $v) {

            $devDirectories[] = "http://localhost/$devDirectory";
                $devDirectories[] = "http:\/\/localhost\/$devDirectory";

            $replacement[] = $this->getBaseUrl();
                $replacement[] = str_ireplace('/', '\/', $this->getBaseUrl());
        }

        $devDirectories[] = ';background-image:url(/swagg/img/choisissez-votre-image.png)';
            $devDirectories[] = '{background-color:}';
                // widget building was updated
                // so lets fix old widget name
                // for example
                // from __MySuper
                // to
                // MySuperWidget
                // until we can preg_match_all and replace
                // lets ...
                if ($dir = opendir( $this->please->getBundleService('view')->getViewsDir('page-builder/widgets') )) {
                    while (($file = readdir($dir)) !== false) {
                        if ($file != '.' && $file != '..' && $file != '.ranking') {
                            $filename = str_ireplace('.php', '', $file);

                            if( in_array($filename, [
                                'Component', 
                                'DescArticles', 
                                'DescCategories', 
                                'LatestArticles',
                                'Navigation', 
                                'Partial', 
                                'ParticularArticle', 
                                'ParticularCategory'
                            ])
                            ){
                                $devDirectories[] = 'data-swagg-widget-name="__'.$filename;
                            }
                            else if( $filename === 'TextEditor' ) {
                                $devDirectories[] = 'data-swagg-widget-name="Text_Editor';
                            }
                            else {
                                $devDirectories[] = 'data-swagg-widget-name="'.$filename;
                            }
                        };
                    }
                    closedir($dir);
                }
                $devDirectories[] = 'Titrage';
                    $devDirectories[] = 'WidgetWidget';

        $replacement[] = '';
            $replacement[] = '{}';
                if ($dir = opendir( $this->please->getBundleService('view')->getViewsDir('page-builder/widgets') )) {
                    while (($file = readdir($dir)) !== false) {
                        if ($file != '.' && $file != '..' && $file != '.ranking') {
                            $filename = str_ireplace('.php', '', $file);

                            if( in_array($filename, [
                                'Component', 
                                'DescArticles', 
                                'DescCategories', 
                                'LatestArticles',
                                'Navigation', 
                                'Partial', 
                                'ParticularArticle', 
                                'ParticularCategory'
                            ])
                            ){
                                $replacement[] = 'data-swagg-widget-name="'.$filename.'Widget';
                            }
                            else if( in_array($filename, ['Text_Editor']) ) {
                                $replacement[] = 'data-swagg-widget-name="'.str_ireplace('_', '', $filename).'Widget';
                            }
                            else {
                                $replacement[] = 'data-swagg-widget-name="'.$filename.'Widget';
                            }
                        };
                    }
                    closedir($dir);
                }
                $replacement[] = 'HeaderWidget';
                    $replacement[] = 'Widget';

        //dump( $devDirectories, $replacement );

        $view = str_ireplace($devDirectories, $replacement, $view);

        $view = str_ireplace(';background-image:url('. $this->getCDN('swagg/img/choisissez-votre-image.png') .')', '', $view);
        
        $pattern = '$(.swagg-)([a-z-]+)([a-z0-9]{6})(::before{})$';
        $view = preg_replace($pattern, '', $view);
        
        $pattern = '$(.swagg-)([a-z-]+)([a-z0-9]{6})(::before{background-color:})$';
        $view = preg_replace($pattern, '', $view);

        // widget building was updated
        // so lets fix old widget name
        // for example
        // from __MySuper
        // to
        // MySuperWidget
        // $pattern = '$(data-swagg-widget-name="__)([a-zA-z0-9]+)(")$';
        // preg_match_all($pattern, $view, $match);
        //$view = preg_replace($pattern, '', $view);

        if( $this->please->getBundleService('env')->getAppEnv('MINIFY_OUTPUT') == 'true' ){
            //minified
            $__PhpHtmlCssJsMinifierService = new __PhpHtmlCssJsMinifierService();
            $view = $__PhpHtmlCssJsMinifierService->getMinifiedHtml( $view );
            $view = $__PhpHtmlCssJsMinifierService->getMinifiedCss( $view );
        }
        if( $this->please->getBundleService('env')->getAppEnv('LAZY_LOAD_IMAGES') == 'true' ){

            $view = str_ireplace('<img src', '<img data-lazy-src', $view);
            $view = str_ireplace('</body>', '<script>$(function(){ __.lazy({ el: $("img[data-lazy-src]").not(".no-lazy") }); })</script></body>', $view);
        }

        return $view;
    }

    public function getCurrentUrl(): string
    {
        $getQueryString = $this->getRouterContext()->getQueryString();
        return $this->getHandledUrl() . (!empty($getQueryString) ? '?' . $getQueryString : '');
    }

    public function getCurrentUrlParamsLess(): string
    {
        return $this->getHandledUrl();
    }

    public function getBaseUrl()
    {
        return $this->please->getBundleService('env')->getAppBaseUrl();
    }

    private function getHandledUrl()
    {
        //$this->getRouterContext()->getPathInfo()

        $app_base_url = $this->getBaseUrl();

        //localhost
        //so lets remove app_dir
        if (false !== strpos($app_base_url, 'http://localhost')) {
            $exploded = explode('/', $app_base_url);
            $app_dir = end($exploded);
            $app_base_url = str_ireplace($app_dir, '', $app_base_url);
            //$app_base_url = preg_replace('~//+~', '/', $app_base_url);
        }
        return trim($app_base_url, '/') . '/' . trim($this->getRouterContext()->getPathInfo(), '/');
    }

    private function isPageBuilderMode()
    {
        return strpos($this->getQueryString(), 'swagg') !== false;
    }

    public function isDev()
    {
        return $this->getEnv() == 'dev';
    }

    public function getEnv($var='APP_ENV')
    {
        return $this->please->getBundleService('env')->getAppEnv($var);
    }

    public function isLocalHost()
    {
        return $this->please->getBundleService('env')->isLocalHost();
    }

    public function isBackoffice()
    {
        if( strpos($this->getCurrentUrl(), $this->geturl('_admin')) !== false ) {
            return true;
        }
        return false;
    }

}
