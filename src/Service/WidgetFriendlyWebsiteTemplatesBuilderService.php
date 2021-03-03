<?php
/**
 * User: stOne
 * Date: 03/05/2019
 * Time: 09:26
 */

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WidgetFriendlyWebsiteTemplatesBuilderService extends AbstractController
{
    private $please;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $APP_HOST = trim($_SERVER['APP_HOST'], '/');
        $APP_PREFIX = trim($_SERVER['APP_PREFIX'], '/');
        $BASE_URL = $APP_HOST . '/' . $APP_PREFIX;
        $this->swaggPickImage = $BASE_URL . '/cdn/swagg/img/choisissez-votre-image.png';

    }

    public function LatestArticles( $ctx )
    {
        $criteria = [];
        $criteria['trash'] = false;
        if( $ctx->auth == false ){ $criteria['trash'] = false; }
        $cards = $this->please->previousContainer->get('knp_paginator')->paginate(
            $this->please->getBundleRepo('Post')->findArticles($criteria),
            $this->please->previousContainer->get('request_stack')->getCurrentRequest()->query->getInt('page', 1),
            (isset($ctx->perpage) && $ctx->perpage > 0 ? $ctx->perpage : 10)
        );
        return $cards;
    }

    public function ParticularArticle( $ctx )
    {
        $ctx->postid = $ctx->article;
        return $this->please->getBundleService('section')->getParticularPost($this->please->getBundleRepo('Post'), $ctx);
    }

    public function DescArticles( $ctx )
    {
        $ctx->postid = $ctx->category;
        return $this->please->getBundleService('section')->getDescendantsArticles($this->please->getBundleRepo('Post'), $ctx);
    }
}
