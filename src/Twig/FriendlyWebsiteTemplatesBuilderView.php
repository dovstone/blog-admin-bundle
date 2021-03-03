<?php

namespace DovStone\Bundle\BlogAdminBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;
use Symfony\Component\Filesystem\Filesystem;
use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;

class FriendlyWebsiteTemplatesBuilderView extends AbstractExtension
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
            new TwigFunction('FriendlyWebsiteTemplates', array($this, 'FriendlyWebsiteTemplates')),
            new TwigFunction('FWT', array($this, 'FWT'))
        );
    }

    public function FriendlyWebsiteTemplates()
    {
        $currentPost = $this->please->getGlobal('post');
        $info = $currentPost->getInfo();
        if(isset($info->html) && isset($info->ctx)) {
            return [
                'hasBody' => isset($info->html) && !empty($info->html),
                //'getBody' => new Markup($this->please->getBundleService('url')->getUrlsManagedView($this->rebuildHTMLStep_1($info->html, $info->ctx)), 'UTF-8'),
                'getBody' => new Markup($this->please->getBundleService('url')->getUrlsManagedView($info->html), 'UTF-8'),
                //'getContext' => new Markup('<script>if(window.Builder==undefined){window.Builder={}};Builder.bigData='.json_encode($info->ctx).';</script>', 'UTF-8')
            ];
        }
        else {
            return [
                'hasBody' => false,
                'getContext' => '{}'
            ];
        }
    }

    public function FWT()
    {
        return $this->FriendlyWebsiteTemplates();
    }

    private function rebuildHTMLStep_1( $html, $ctx )
    {
        $rebuilt = '';
        preg_match_all('/(<div)(.*)(data-swagg-builder-widget-id=")([a-z0-9]{5})(")(.*)(<\/div>)/', $html, $matches);

        for($i = 0; $i < count($matches[1]); $i++)
        {   
            $widget_id = $matches[4][$i];
            $rebuilt = str_replace($matches[7][$i], $this->rebuildHTMLStep_2( $widget_id, $ctx ) . '</div>', $html);
        }
        return $rebuilt;
    }

    private function rebuildHTMLStep_2( $widget_id, $ctxs )
    {   
        return '';
        /*$ctx = null;
        $ctxs = json_decode($ctxs);
        if( $ctxs && isset($ctxs->$widget_id) ){
            $ctx = $ctxs->$widget_id;
        }
        return $this->please->getBundleService('section')->buildFriendlyWebsiteTemplatesDynamicWidget( $ctx );*/
    }
}
