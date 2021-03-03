<?php

namespace DovStone\Bundle\BlogAdminBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;
use DovStone\Bundle\BlogAdminBundle\Controller\WebsiteController;
use DovStone\Bundle\BlogAdminBundle\Service\DirService;
use DovStone\Bundle\BlogAdminBundle\Service\EnvService;
use DovStone\Bundle\BlogAdminBundle\Service\ViewService;
use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\SessionService;
use DovStone\Bundle\BlogAdminBundle\Service\TimeService;
use DovStone\Bundle\BlogAdminBundle\Service\UrlService;
use DovStone\Bundle\BlogAdminBundle\Service\MediaService;
use DovStone\Bundle\BlogAdminBundle\Service\StringService;
use DovStone\Bundle\BlogAdminBundle\Twig\FriendlyWebsiteTemplatesBuilderView;
use Symfony\Component\Filesystem\Filesystem;

class RenderPageBuilderView extends AbstractExtension
{
    private $websiteController;
    private $getGenericsAreas;
    //
    private $please;
    private $sessService;
    private $envService;
    private $urlService;
    private $mediaService;
    private $navService;
    private $viewService;
    private $timeService;
    private $dirService;
    private $stringService;
    //
    private $appUiD;

    public function __construct(
        WebsiteController $websiteController,
        PleaseService $please,
        SessionService $sessService,
        EnvService $envService,
        UrlService $urlService,
        MediaService $mediaService,
        ViewService $viewService,
        TimeService $timeService,
        DirService $dirService,
        StringService $stringService,
        FriendlyWebsiteTemplatesBuilderView $fwtbv
    ) {
        $this->please = $please;
        $this->sessService = $sessService;
        $this->envService = $envService;
        $this->urlService = $urlService;
        $this->mediaService = $mediaService;
        $this->viewService = $viewService;
        $this->timeService = $timeService;
        $this->dirService = $dirService;
        $this->stringService = $stringService;
        $this->fwtbv = $fwtbv;
        //
        $this->filesystem = new Filesystem();
        //
        $this->appUiD = sha1(getenv('APP_NAME'));
        //
        $this->websiteController = $websiteController;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('getPost', array($this, 'getPost')),
            new TwigFunction('getLatest', array($this, 'getLatest')),
            //
            new TwigFunction('beginHTML', array($this, 'beginHTML')),
            new TwigFunction('endHTML', array($this, 'endHTML')),
            //

            new TwigFunction('getAppConfig', array($this, 'getAppConfig')),
            new TwigFunction('getBody', array($this, 'getBody')),
            new TwigFunction('getNav', array($this, 'getNav')),
            new TwigFunction('getBreadcrumb', array($this, 'getBreadcrumb')),

            new TwigFunction('getHeader', array($this, 'getHeader')),
            new TwigFunction('getUnlayerBody', array($this, 'getUnlayerBody')), // dev only
            new TwigFunction('getSideBar', array($this, 'getSideBar')),
            new TwigFunction('getFooter', array($this, 'getFooter')),
        );
    }

    public function getAppConfig( $jsonify = true )
    {
        $appConfig = [
            'post_id' => $this->getPost() ? $this->getPost()->getId() : null,
            'type' => $this->getPost() ? $this->getPost()->getType() : null,
            'name' => $this->envService->getAppEnv('APP_NAME'),
            'env' => $this->envService->getAppEnv(),
            'dir' => $this->envService->getAppDir(),
            'encore_port' => $this->envService->getEncorePort(),
            'cdn_host' => $this->envService->getAppEnv('CDN_HOST')
        ];
        
        return $jsonify ? new Markup(json_encode($appConfig), 'UTF-8') : $appConfig;
    }

    public function beginHTML(array $assets = null)
    {
        $robotsMeta = ($this->getPost()->getType() == 'page' && $this->getPost()->getInfo()->auth == 0) ? "\n".'<meta name="robots" content="noindex, nofollow">' : '';

        $cssAssets = '';
        if (is_array($assets)) {
            foreach ($assets as $i => $css) {
                $href = strpos($css, 'http') !== false
                ? $css
                : 'http://localhost:' . $this->envService->getEncorePort() . '/assets/css/' . $css . '.css';

                if ($this->envService->getAppEnv() === 'dev') {
                    $cssAssets .= '<link id="link_stylesheet" rel="stylesheet" href="' . $href . '">';
                } else {
                    $cssAssets .= '<link rel="stylesheet" href="' . (strpos($css, 'http') !== false ? $css : $this->urlService->getUrl('assets/css/' . $css)) . '.css?v=' . uniqid() . '">';
                }
                if ($i !== sizeof($assets) - 1) {
                    $cssAssets .= "\n";
                }
            }
        }

        $title = $this->_getTitle();
        $description =  $this->stringService->getHtml2Text( $this->getPost()->getInfo()->description );
        $keywords = $this->getPost()->getInfo()->keywords;
        $author = null;
        $href = $this->please->getBundleService('post')->getPostHref($this->getPost());
        $thumbnail = $this->getPost()->getInfo()->thumb;

        
        foreach (['description', 'keywords', 'author'] as $metaName) {
            $meta = $this->please->getGlobal("meta" . ucfirst($metaName));
            if (!is_null($meta)) {
                $$metaName = $meta;
            }
        }
        
        $currentHref = $this->urlService->getCurrentUrl();

        $view = '<!DOCTYPE html>
<html lang="fr-FR" data-app-config=\''. $this->getAppConfig() .'\'>
<head>'. $robotsMeta .'
<meta charset="utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>' . $title . $this->envService->getAppName() . '</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content="' . $description . '">
<meta name="keywords" content="' . $keywords . '">
<meta name="author" content="' . $author . '">
<meta name="url" content="' . $currentHref . '">
<link rel="canonical" href="' . $currentHref . '" />
<link rel="icon" type="image/png" sizes="16x16" href="'. $this->urlService->getUrl('favicon.ico') . '">
<meta property="og:url" content="' . $currentHref . '" />
<meta property="og:type" content="webWebsite" />
<meta property="og:title" content="' . $title . $this->envService->getAppName() . '" />
<meta property="og:description" content="' . $description . '" />
<meta property="og:image" content="' .$this->mediaService->getThumbnail( $thumbnail ) . '" />
<link data-dont-reload-asset="true" rel="stylesheet" type="text/css" href="' . $this->urlService->getCDN('swagg/assets/css/imports.css') . '" />
' . $cssAssets . '
<script data-dont-reload-asset="true" src="' . $this->urlService->getCDN('jquery/jquery-1.11.3.min.js') . '"></script>
<script data-dont-reload-asset="true" _src="' . $this->urlService->getCDN('swagg/js/2.0/install.js') . '"></script>
<script data-dont-reload-asset="true" src="' . $this->urlService->getCDN('underscore/js/underscore.js') . '"></script>
<script src="' . $this->urlService->getCDN('swagg/helpers.js?' . uniqid() ) . '"></script>
</head>
<body class="layout-'. ($this->getPost()->getType() == 'page' ? $this->getPost()->getInfo()->layout : 'article') . '">
<div id="site_content">';
        
        return new Markup($this->urlService->getUrlsManagedView($view), 'UTF-8');
    }
    
    public function endHTML(array $assets = null)
    {
        return new Markup('</div></div></body></html>', 'UTF-8');
    }
    
    public function getHeader(array $attr = [])
    {
        $view = $this->beginHTML();
        
        return new Markup($view, 'UTF-8');
    }

    public function getBody($default=null)
    {
        $hasBody = $this->fwtbv->FriendlyWebsiteTemplates()['hasBody'];
        return new Markup('<div id="swagg_main">'.($hasBody ? $this->fwtbv->FriendlyWebsiteTemplates()['getBody'] : ($default ?? '<div sg-empty></div>')).'</div>', 'UTF-8');
    }

    public function getBody__DEP($clean=null)
    {
        if($clean){return new Markup('<div id="editor"></div>', 'UTF-8');}

        $html = $this->getPost()->getInfo()->html ?? '';
        $ctx = $this->getPost()->getInfo()->ctx ?? '';
        $css = $this->getPost()->getInfo()->css ?? '';

        if( $this->_isPageBuilderMode() ){

            if( is_array($ctx) || is_object($ctx) ){
                $ctx_form = '<form id="editor__ctx" class="hidden">';
                foreach ($ctx as $swaggId => $v) {
                    if(!empty($v)){
                        foreach ($v as $controlName => $controlVal) {
                            $ctx_form .= '<input data-swagg-id="'.$swaggId.'" data-name="'.$controlName.'" data-value="'.$controlVal.'" value="' .$controlVal. '" name="'.$swaggId.'['.$controlName.']" />';
                        }
                    }
                }
                $ctx_form .= '</form>';
            }
            else {
                $ctx_form = '';
            }

            $view = '<div id="editor" class="hidden">
                        <div id="editor__panel" class="editor--panel editor--from-panel"></div>
                        <div data-js="editor={click:togglePanel}" id="editor__panel_toggler" class="editor--panel-toggler editor--from-panel"><i class="fa fa-angle-left"></i></div>
                        <div id="editor__view" class="swagg--view">'. $html .'</div>
                        <div id="editor__layer_panel" class="editor--layer-panel editor--from-panel unselectable"></div>
                     </div>'. $ctx_form;
        }
        else {
            $view = $html;
        }
                
        return new Markup('<style>'. $css .'</style>'.$this->urlService->getUrlsManagedView($view), 'UTF-8');
    }

    public function getUnlayerBody()
    {
        $view = '<script src="' . $this->urlService->getCDN('swagg/2.0/unlayer/assets/js/embed.js') . '"></script>
                <div id="editor_container"></div>
                <script>
                  unlayer.init({
                    id: "editor_container",
                    projectId: 1234,
                    displayMode: "email"
                  });
                </script>';
                
        return new Markup($view, 'UTF-8');
    }

    public function getBreadcrumb($home = '<i class="fa fa-home"></i>')
    {
        return new Markup($this->please->getBundleService('post')->getBreadcrumb($home), 'UTF-8');
    }

    public function getNav($params)
    {
        return new Markup($this->please->getBundleService('post')->getNav($params), 'UTF-8');
    }

    public function getSideBar(array $attr = [], $tag = 'aside')
    {
        return new Markup('getSideBar', 'UTF-8');
    }

    public function getFooter(array $attr = [])
    {
        $view = $this->endHTML();

        return new Markup($view, 'UTF-8');
    }

    public function getPost()
    {
        return $this->please->getGlobal("post");
    }

    public function getLatest($limit = 15, $criteria = ['auth' => true, 'trash' => false])
    {
        if (!$this->_somePostQueried()) {
            return null;
        }
        return $this->please->list([
            'items' => $this->postRepo->findArticles($criteria),
            'perPage' => $limit,
            'onEmpty' => function () {
                return null;
            },
            'view' => function ($cards) {
                return $cards;
            },
        ]);
    }

    private function _isPageBuilderMode()
    {
        return strpos($this->urlService->getQueryString(), 'swagg') !== false;
    }

    private function _somePostQueried()
    {
        return !is_null($this->please->getGlobal("post"));
    }

    private function _getTitle()
    {
        if (empty($this->getPost()->getInfo()->long_title)) {
            $metaTitle = $this->please->getGlobal("metaTitle");
            $title = !is_null($metaTitle) ? $metaTitle . ' | ' : '';
        } else {
            $title = $this->getPost()->getInfo()->long_title . ' | ';
        }
        return $title;
    }
}
