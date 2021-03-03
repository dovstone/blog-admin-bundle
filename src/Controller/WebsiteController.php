<?php

namespace DovStone\Bundle\BlogAdminBundle\Controller;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Form\BloggyType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/")
 */
class WebsiteController extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->please->getBundleService('execute_before')->executeBefore();
        $this->please->previousContainer->get('service.execute_before')->__run();

        $this->filesystem = new Filesystem();
        //
        $this->bloggyRepo = $this->please->getBundleRepo('Bloggy');
    }

    /**
     * @Route("/", name="_getHomePage")
     */
    public function _getHomePage()
    {
        $this->execBefore();
        //
        $pages = $this->bloggyRepo->findBy(['type' => 'page', 'enabled' => true]);

        if($pages){
            foreach ($pages as $page) {
                if($page->getSlug() == 'home'){
                    return $this->render200($page);
                }
            }
        }
        return $this->render404();
    }

    /**
     * @Route("{slug}",
     *  requirements={"slug":"[a-z-/0-9]+"},
     *  name="_getPage")
     */
    public function _getPage($slug)
    {
        $this->execBefore();

        $slug_ = $slug;

        //
        $slug = explode('/', $slug);
        $slug = end($slug);

        $pages = $this->bloggyRepo->findBy(['slug' => $slug, 'enabled' => true]);

        if ($pages) {
            return $this->browsePostsFound($pages, $slug);
        }
        return $this->render404();
    }

    /**
     * @Route(
     *  "{parent_slug}/{slug}.html",
     *  requirements={
     *      "parent_slug":"[a-z-/0-9]+",
     *      "slug":"[a-z-/0-9]+"
     *  },
     *  name="_getArticleWithParent")
     */
    public function _getArticleWithParent($parent_slug, $slug)
    {
        $this->execBefore();

        //
        $slug = explode('/', $slug);
        $slug = end($slug);

        $posts = $this->bloggyRepo->findBy(['slug' => $slug, 'enabled' => true]);

        if (!$posts) {
            return $this->render404();
        }

        return $this->browsePostsFound($posts, $slug = $parent_slug . '/' . $slug);
    }

    
    private function render200($post)
    {
        foreach (['title', 'description', 'keywords', 'author'] as $metaName) {
            $this->please->unsetGlobal('meta'.ucfirst($metaName));
        }

        //getting next and prev articles
        //$post->setPrevArticle($this->postRepo->findPrevArticle($post));
        //$post->setNextArticle($this->postRepo->findNextArticle($post));

        //getting next and prev posts
        //$post->setPrevPost($this->postRepo->findPrevArticle($post, $type = 'both'));
        //$post->setNextPost($this->postRepo->findNextArticle($post, $type = 'both'));

        //getting desc_posts and desc_posts
        //$post->setDescPages($this->postRepo->findPostsSectionsLess($criteria = [ 'type' => 'page', 'parent' => $post ]));
        //$post->setDescArticles($this->postRepo->findPostsSectionsLess($criteria = [ 'type' => 'article', 'parent' => $post ]));
        $postServ = $this->please->getBundleService('post');

        $sibling = $postServ->getPostRelatives($post);

        $post->descPages = $sibling->pages;
        $post->descArticles = $sibling->articles;
        $post->prevArticle = $sibling->prevArticle;
        $post->nextArticle = $sibling->nextArticle;
        
        $this->please->setGlobal([ "post" => $post ]);

        $layout = $post->getInfo()->layout ?? 'article';

        if (!empty($layout)) {
            //user layout
            $fileSystem = new Filesystem();
            $layoutFile = $this->please->getBundleService('dir')->getThemeDirAbsDirPath('layouts')."/$layout.html.twig";
            if ($fileSystem->exists($layoutFile)) {
                $view = $this->renderFinalView(
                    $this->renderView("layouts/$layout.html.twig", compact('post'))
                );
                return new Response($view);
            }
        }

        if ($post->getType() === 'header' || $post->getType() === 'footer' || $post->getType() === 'aside') {
            //default header or footer layout
            $view = $this->renderView("@DovStoneBlogAdminBundle/website/build-header-footer.html.twig", compact('post'));
        } else {
            //default system layout
            $view = $this->renderView("@DovStoneBlogAdminBundle/website/default.html.twig", compact('post'));
        }

        return new Response($this->renderFinalView($view));
    }

    private function browsePostsFound($posts, $slug)
    {
        $post = $posts[0];

        foreach ($posts as $post) {

            //injecting href so we could match the post
            $post = $this->please->fetchEager($post, true);

            $info = $post->getInfo();
            $info->href = $this->please->getBundleService('post')->getPostHref($post);
            $post = $post->setInfo($info);

            if ($info->href === $this->please->getBundleService('url')->getCurrentUrlParamsLess()) {
                return $this->render200($post);
            }
        }
        return $this->render404();
    }

    private function render404()
    {
        $fileSystem = new Filesystem();
        $_404path = $this->please->getBundleService('dir')->getThemeDirAbsDirPath('layouts')."/404.html.twig";
        if ($fileSystem->exists($_404path) ) {
            $view = $this->renderView("layouts/404.html.twig");
        } else {
            //default system 404 template
            $view = $this->renderView("@DovStoneBlogAdminBundle/website/404.html.twig");
        }
        return new Response($this->renderFinalView($view), 404);
    }

    private function renderFinalView(string $view)
    {
        return $this->please->getBundleService('view')->sanitizeFinalView($view);
    }

    private function execBefore()
    {
        ####################################################
        if ($this->filesystem->exists($this->please->previousContainer->get('kernel')->getProjectDir() . '/src/Service/ExecuteBeforeService.php')) {
            if (method_exists(\App\Service\ExecuteBeforeService::class, '__run') && $this->container->has('service.execute_before')) {
                $this->please->previousContainer->get('service.execute_before')->__run();
            }
        }
        ####################################################
    }
}
