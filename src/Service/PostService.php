<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DovStone\Bundle\BlogAdminBundle\Repository\BundleBloggyRepository;
use Twig\Markup;

class PostService extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $routerContext = $this->please->previousContainer->get('router')->getContext();
        $this->urlService = $this->please->getBundleService('url');
        $this->dirService = $this->please->getBundleService('dir');
        $this->baseUrl = $this->urlService->getBaseUrl();

        $this->bloggyRepo = $this->please->getBundleRepo('Bloggy');
        $this->bloggiesTable = $this->please->getTableName($table='bloggies');
    }
    
    public function getTree($returnType='option', $collectionIndex='___Pages', $params=[])
    {
        $collection = is_string($collectionIndex) ? $this->please->getStorage($collectionIndex.'Collection', true) : $collectionIndex;

        /* lets inject acf that have articles props ($result1)
                BUT that are not checked as "Se comporter comme un Article" ($result2)
                    because they would behave as classics pages */
        $types = [];
        $result1 = $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, [
            'type' => 'acf',
            'info[inheritance]' => 'page' // null means we dont have the exact value
        ]);
        $rows = $result1['get']('rows');
        if( $rows ){foreach ($rows as $r_) {$types[] = $r_->getSlug();}}
        $result2 = $this->please->findNotLike($this->bloggyRepo, $this->bloggiesTable, [
            'info[_is_article]' => null // null means we dont have the exact value
        ]);
        // lets finaly use Doctrine for fetching
        $acf_as_pages = $this->bloggyRepo->findBy(['id' => $result2['getIds'](), 'type' => $types]);

        //
        $collection = array_merge((array)$collection, (array)$acf_as_pages);

        // prevent ul
        $preventUl = !isset($params->preventUl) || ((isset($params->preventUl) && $params->preventUl == false));

        $view = '';

        switch ($returnType) {
            case 'option':
                    $view = '<option value="null">Aucun</option><option disabled></option>';
                    if( $collection ){
                        $view .= $this->_getOptionsTree($collection, null, 0);
                    }
                break;
            
            case 'itemCheckable':
                    $view = '<ul>';
                    if( $collection ){
                        $view .= $this->_getItemsCheckableTree($collection, null, 0);
                    }
                    $view .= '</ul>';
                break;
            
            case 'list':

                    if($preventUl){
                        $view = '<ul '.(isset($params->mainUlId) ? 'id="'.$params->mainUlId.'"' : '').' '.(isset($params->mainUlClass) ? 'class="'.$params->mainUlClass.'"' : '').'>';
                    }

                    if( $collection ){
                        $view .= $this->_getAsUnorderedListRecursively($collection, null, 0, $params);
                    }

                    if($preventUl){
                        $view .= '</ul>';
                    }

                break;
            
            default:
                    $view = '';
                break;
        }

        return new Markup($view, 'UTF-8');
    }

    public function getPostHref($post)
    {
        if( !method_exists($post, 'getInfo') ){
            return '';
        }
        $postHref = $post->getInfo()->href ?? '';

        /*if ($postHref !== '') {
            return $this->urlService->getUrl();
        }*/
        if (!empty($postHref)) {
            return strpos($postHref, 'http') === false
            ? $this->urlService->getUrl($postHref)
            : $postHref;
        }

        $href = '';

        $collection = array_merge(
            $this->please->getStorage('___PagesCollection', true), 
            $this->please->getStorage('___ACFCollection', true),
            $this->please->getStorage('___ACFChildrenCollection', true),
            $this->please->getStorage('___ACFChildren2Collection', true)
        );
        
        if ($collection) {
            $href = $this->getRecursivePostHref($post, $collection);
        }

        // Adding the slug of the current post
        $href = $href . (
            isset($post->getInfo()->customed_slug) && !empty($post->getInfo()->customed_slug) 
            ? $post->getInfo()->customed_slug 
            : '/' . $post->getSlug()
        );

        // Beautifying the href in case of "homepage" with slug matching (/home|/accueil)
        $href = ($href === '/home' || $href === '/accueil' || $href === '/' || $href === '') ? '/' : $href;

        // Adding article prefix (default as html)
        if( 
               isset($post->getInfo()->_acf) 
            && (isset($post->_acf) && method_exists($post->_acf, 'getInfo'))
            && isset($post->_acf->getInfo()->inheritance)
            && $post->_acf->getInfo()->inheritance == 'article'
        ){
            $is_article = true;
        }

        $href .= 
            (
                $post->getType() == 'page' 
                || !isset($post->getInfo()->_is_article)
                && !isset($is_article)
            )
            ? '' : '.html';


        return $this->urlService->getUrl($href);
    }
    
    public function getBreadcrumb($home = '<i class="fa fa-home"></i>')
    {
        $post = $this->please->getGlobal("post");
        $breadcrumbBuilt = '';
        $this->breadcrumbRecursively($post);
        if (isset($this->breadcrumbBuilt)) {
            for ($i = sizeof($this->breadcrumbBuilt) - 1; $i > -1; $i--) {
                $breadcrumbBuilt .= $this->breadcrumbBuilt[$i];
            }
        }
        $this->breadcrumbBuilt = [];
        return '<li><a href="' . $this->urlService->getUrl() . '">' . $home . '</a></li>' . ( !$this->please->getBundleService('dir')->isHome() ? $breadcrumbBuilt : '' );
    }

    private function breadcrumbRecursively($parent): void
    {   
        if ( !is_null($parent) /*&& isset($parent->getInfo()->href) && $parent->getInfo()->href !== '/'*/ ) {

            $info = $parent->getInfo();
            $parentHref = $info->href ?? '';

            $url_match = (trim($parentHref, '/') === $this->urlService->getCurrentUrl());

            $item = !$url_match ? '<a href="' . $parentHref . '">' . $info->title . '</a>' : '<span>' . $info->title . '</span>';
            $active_class = $url_match ? ' class="active" ' : '';

            $this->breadcrumbBuilt[] = '<li' . $active_class . '>' . $item . '</li>';
            if (isset($parent->parent) && $parent->parent !== null) {
                //here we go again
                
                $pParent = $this->please->fetchBundleEager($parent->parent);
                $info = $pParent->getInfo();
                $info->href = $this->please->getBundleService('post')->getPostHref($pParent);
                $pParent = $pParent->setInfo($info);

                $this->breadcrumbRecursively( $this->please->fetchBundleEager($pParent) );
            }
        }
    }

    public function getNav($params)
    {
        return $this->getTree($returnType='list', '___Pages', (object)$params);
    }

    public function getPostRelatives($parent, $orderBy=[], $limit=null)
    {
        $pages = $articles = [];
        if( method_exists($parent, 'getId') ){
            $iDs = $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, [
                'info[parent]' => "{$parent->getId()}",
                'enabled' => true
            ])['getIds']();

            if( $iDs ){

                // lets apply orderBy and $limit
                $data = $this->bloggyRepo->findBy(['id' => $iDs], $orderBy, $limit);

                if( $data ){
                    
                    foreach ($data as $d) {
                        $d = $this->please->fetchEager($d, true);
                        if( 
                               isset($d->_acf) 
                            && method_exists($d->_acf, 'getInfo')
                            && isset($d->_acf->getInfo()->inheritance)
                            && $d->_acf->getInfo()->inheritance == 'article'
                        ){
                            $is_article = true;
                        }

                        if(isset($d->getInfo()->_is_article) || isset($is_article)){
                            $articles[] = $d;
                        }
                        else {
                            $pages[] = $d;
                        }
                    }
                }
            }
        }

        if( $parent ){
            $date = $parent->getCreated()->format('Y-m-d H:i:s');
    
            $prevArticle = $this->bloggyRepo->createQueryBuilder('b')
                                //->select('b.id')
                                ->andWhere('b.type = :type')->setParameter('type', $parent->getType())
                                ->andWhere('b.enabled = :enabled')->setParameter('enabled', true)
                                ->andWhere("b.created < '$date'")
                                ->orderBy('b.created', 'DESC')
                                ->getQuery()
                                ->getResult();
            $nextArticle = $this->bloggyRepo->createQueryBuilder('b')
                                //->select('b.id')
                                ->andWhere('b.type = :type')->setParameter('type', $parent->getType())
                                ->andWhere('b.enabled = :enabled')->setParameter('enabled', true)
                                ->andWhere("b.created > '$date'")
                                ->orderBy('b.created', 'DESC')
                                ->getQuery()
                                ->getResult();
        }
        else {
            $prevArticle = $nextArticle = null;
        }
        

        return (object)[
            'pages' => $pages,
            'articles' => $articles,
            'prevArticle' => $prevArticle && !empty($prevArticle) ? $prevArticle[0] : null,
            'nextArticle' => $nextArticle && !empty($nextArticle) ? $nextArticle[0] : null,
        ];
    }

    public function getDesc($parent, $orderBy=[], $limit=null)
    {
        return $this->getPostRelatives($parent, $orderBy, $limit);
    }

    public function findPost($criterias, $orderBy=[], $limit = null)
    {
        return $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, $criterias, $orderBy, $limit)['get']('rows');
    }


    private function _getOptionsTree($collection, $parent_id, $depth)
    {
        $childrenHtml = '';
        foreach ($collection as $collect) {
            if ( isset($collect->getInfo()->parent) && (int)$collect->getInfo()->parent == $parent_id) {
                $childrenHtml .= '<option value="' . $collect->getId() . '">';
                $childrenHtml .= str_repeat("--", $depth);
                $childrenHtml .= $collect->getInfo()->title;
                $childrenHtml .= $this->_getOptionsTree($collection, $collect->getId(), $depth+1);
                $childrenHtml .= '</option>';
            }
        }
        // Returns the HTML
        return $childrenHtml;
    }

    private function _getItemsCheckableTree($collection, $parent_id, $depth)
    {
        $childrenHtml = '';
        foreach ($collection as $collect) {
            if (isset($collect->getInfo()->parent) && (int)$collect->getInfo()->parent == $parent_id) {

                if ($depth === 0) {
                    $childrenHtml .= '<li data-id="' . $collect->getId() . '"><label class="switch"><input data-js="navs={click:appendToNav}" type="checkbox" value="' . $collect->getId() . '">';
                }

                if ($depth === 1) {
                    $childrenHtml .= '<em class="depth-1">';
                }

                $childrenHtml .= '<em data-id="' . $collect->getId() . '" ' . ($depth === 0 ? ' class="title"' : '') . '>';
                $childrenHtml .= str_repeat("<i style='color:#ccc'>--</i>", $depth);
                $childrenHtml .= ' <b>'.$collect->getInfo()->title.'</b>';
                $childrenHtml .= '</em>';
                $childrenHtml .= $this->_getItemsCheckableTree($collection, $collect->getId(), $depth+1);

                if ($depth === 1) {
                    $childrenHtml .= '</em>';
                }

                if ($depth === 0) {
                    $childrenHtml .= '<button data-js="navs={click:collapse}" type="button" class="btn btn-light"></button></label></li>';
                }

            }
        }
        // Returns the HTML
        return $childrenHtml;
    }
    
    private function _getAsUnorderedListRecursively($collection, $parent_id, $depth, $params)
    {
        //if( !isset($this->navCollect) ){
            $this->navCollect = $this->bloggyRepo->findOneBy([ 'type'=>'menu', 'slug'=>$params->nav]);
            if( $this->navCollect ){
                $this->pagesIds = json_decode($this->navCollect->getInfo()->pages_ids);

                // lets reOrder according to "rank"
                $zeroRanked = [];
                $ranking = [];
                if( !empty($collection) ){
                    foreach ($collection as $collect) {
                        if(isset($collect->getInfo()->rank)){
                            $rank = (int) $collect->getInfo()->rank;
                            if($rank !== 0 ){
                                $ranking[$rank] = $collect;
                            }
                            else {
                                $zeroRanked[] = $collect;
                            }
                        }
                    }
                }
                ksort($ranking);
                $collection = array_merge($zeroRanked, $ranking);

                $this->structure = "navs/{$this->navCollect->getInfo()->structure}.html.twig";
            }
        //}

        if ( $this->navCollect && $this->pagesIds ) {

            $html = '';
            foreach ($this->pagesIds as $pageId) {
                
                foreach ($collection as $index => $collect) {

                    if (
                            (int)$pageId == (int)$collect->getId() 
                            && $collect->getEnabled()===true 
                            && (isset($collect->getInfo()->in_menu))
                            && ($collect->getInfo()->in_menu=='1' || $collect->getInfo()->in_menu=='on')
                        )
                    {
                        $collected = $this->please->fetchBundleEager($collect);

                        $info = $collected->getInfo();
                        $info->href = $this->please->getBundleService('post')->getPostHref($collected);
                        $collected = $collected->setInfo($info);
                        $item = $this->please->fetchBundleEager($collected);
                        
                        if((trim($info->href, '/') == $this->urlService->getCurrentUrlParamsLess())){
                            $item->isActive = true;
                        }
                        
                        $sub = $this->_getChildren($collection, $collect->getId(), $depth+1, $params);
                        if( $sub ){
                            $item->children = $sub;
                            foreach($sub as $s){
                                if((trim($s->getInfo()->href, '/') == $this->urlService->getCurrentUrlParamsLess())){
                                    $item->isActive = true;
                                }
                            }
                        }
                        $index0 = $index++;

                        if( $this->structure !== 'navs/default.html.twig' ){
                            $structure = $this->structure;
                        }
                        else {
                            $structure = "navs/".($item->getInfo()->structure ?? 'default').".html.twig";
                        }
                        $html .= $this->renderView($structure, compact('item', 'index0', 'index'));
                    }
                }
            }

            // Returns the HTML
            return $html;
        }
    }

    private function _getChildren($collection, $parent_id, $depth, $params)
    {
        $children = [];

        foreach ($collection as $collect) {

            if (
                    (int)$collect->getInfo()->parent == $parent_id
                    && $collect->getEnabled()===true 
                    && (isset($collect->getInfo()->in_menu))
                    && ($collect->getInfo()->in_menu=='1')
                )
            {

                $realDepth = 1;

                $uId = $parent_id.$depth++;

                if( !isset($this->$uId) ){

                    $collected = $this->please->fetchBundleEager($collect);
                    $info = $collected->getInfo();
                    $info->href = $this->please->getBundleService('post')->getPostHref($collected);
                    $collected = $collected->setInfo($info);
                    $item = $this->please->fetchBundleEager($collected);
                    
                    if((trim($info->href, '/') == $this->urlService->getCurrentUrlParamsLess())){
                        $item->isActive = true;
                    }

                    $children[] = $item;

                    $this->$uId = true;
                }

            }
        }
        return $children;
    }

    private function getRecursivePostHref($post, $collection)
    {
        $parent = isset($post->parent) && !empty($post->parent) && method_exists($post->parent, 'getId') ? $post->parent->getId() : null;

        if( is_int($parent) ){
            foreach ($collection as $key => $collect) {
                if( $collect->getId() === $parent ){
                    $parent = $collect;
                }
            }
        }

        $href = '';
        foreach ($collection as $collect) {
            if( !is_null($parent) ){
                $parentId = is_int($parent) ? $parent : $parent->getId();
                if( $collect->getId() === $parentId ){
                    $href .= $collect->getSlug() . '/';
                    return $this->getRecursivePostHref($this->please->fetchEager($parent, $bundle=true), $collection) . '/' . $parent->getSlug();
                }
            }
        }
        return $href;
    }
}
