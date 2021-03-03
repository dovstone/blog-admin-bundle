<?php

namespace DovStone\Bundle\BlogAdminBundle\Controller;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Entity\Bloggy;
use DovStone\Bundle\BlogAdminBundle\Form\BloggyType;
use DovStone\Bundle\BlogAdminBundle\Repository\BundleBloggyRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("_admin/")
 */
class ACFBloggyController extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->please->getBundleService('execute_before')->executeBefore();
        $this->please->previousContainer->get('service.execute_before')->__run();
    }

    /**
     * @Route("bloggy/acf/{slug}/create", name="_createACFBloggy")
     */
    public function _createACFBloggy($slug, BundleBloggyRepository $bloggyRepo)
    {
      return $this->please->read([
          'isXml' => '_base',
          'isGranted' => ["byUserRoles" => $this->please->getAdminRoles(), 'otherwise' => $this->otherwise()],
          'finder' => function () use ($slug, $bloggyRepo) {
            return $bloggyRepo->findOneBy(['type'=> 'acf', 'slug'=> $slug ]);
          },
          'onFound' => function ($bloggy) use ($slug) {
            return $this->please->create([
                'type' => BloggyType::class,
                'entity' => new Bloggy(),
                'sanitizer' => function ($posted, $handled) use ($bloggy, $slug) {
                  $handled
                    ->setType($slug)
                    ->setSlug(
                      $this->please->getBundleService('string')->getSlug(
                          isset($posted->_info->slug) && !empty($posted->_info->slug) 
                          ? $posted->_info->slug 
                          : $posted->info->title_singular ?? $posted->info->title
                      ))
                    ->setInfo($posted->info)
                    ->setUser($this->getUser())
                    ->setEnabled(true)
                    ->setCreated(new \DateTime($posted->_info->created ?? "now"))
                    ;
                    return $handled;
                },
                'formView' => function ($form) use ($bloggy) {

                    $bundleDir = $this->please->getBundleService('dir');
                    $options = [
                        'layouts' =>  $bundleDir->asOptions( $bundleDir->getThemeDirPath('layouts') ),
                        'pages' => $this->please->getBundleService('post')->getTree(),
                        'menus' => $this->please->getBundleService('post')->getTree('itemCheckable'),
                        'navs' => $bundleDir->asOptions( $bundleDir->getThemeDirPath('navs') ),
                    ];
                    
                    $default_title = $bloggy->getInfo()->title;
                    return new JsonResponse([
                        'view' => $this->getTemplate('acf-add', compact('form', 'bloggy', 'default_title', 'options')),
                        'title' => $default_title
                    ]);
                },
                'onSuccess' => function ($item) {

                    $this->unsetStorages();

                    return new JsonResponse([
                        'success' => true,
                        'msg' => 'Données enregistrées avec succès',
                        'redirect' => $this->generateUrl('_updateACFBloggy', ['type'=> $item->getType(), 'id'=> $item->getId()])
                    ]);
                },
            ]);
          },
      ]);
    }

    /**
     * @Route("bloggy/acf/{type}/list", name="_listACFBloggy")
     */
    public function _listACFBloggy($type, BundleBloggyRepository $bloggyRepo)
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles(), 'otherwise' => $this->otherwise()],
            'finder' => function() use ($bloggyRepo, $type){
                return $bloggyRepo->findOneBy(['type' => 'acf', 'slug' => $type]);
            },
            'onFound' => function($acf_parent) use ($type, $bloggyRepo){

                return $this->please->readList([
                    'items' => function ($filterQuery) use ($type, $bloggyRepo) {
                      return $bloggyRepo->findBy([ 'type' => $type ], ['created' => 'DESC']);
                    },
                    'perPage' => $this->please->getRequestStackQuery()->get('per_page', 25),
                    'view' => function ($bloggy_, $filterForm, $total) use ($acf_parent, $type, $bloggyRepo) {
          
                      $isREADING = true;
          
                      $bloggy = $this->please->fetchEager($bloggy_?$bloggy_->getItems():null, $bundle=true);

                      return new JsonResponse([
                          'view' => $this->getTemplate('acf-add', compact('bloggy', 'bloggy_', 'filterForm', 'total', 'type', 'isREADING', 'acf_parent')),
                          'title' => $acf_parent->getInfo()->title . ' ('.$total.')'
                      ]);
                    },
                ]);
            }
        ]);
    }
    
    /**
     * @Route("bloggy/acf/{type}/{id}/update", name="_updateACFBloggy")
    */
    public function _updateACFBloggy($type, $id, BundleBloggyRepository $bloggyRepo)
    {
      return $this->please->update([
          'isXml' => '_base',
          'isGranted' => ["byUserRoles" => $this->please->getAdminRoles(), 'otherwise' => $this->otherwise()],
          'type' => BloggyType::class,
          'entity' => new Bloggy(),
          'finder' => function() use ($type, $id, $bloggyRepo){
            return $bloggyRepo->findOneBy(['type'=> $type, 'id'=> $id]);
          },
          'onNotFound' => function(){
              return new JsonResponse([
                'success' => true,
                'msg' => 'Donnée introuvable',
                'redirect' => $this->generateUrl('_dashboard')
               ]);
          },
          'sanitizer' => function ($posted, $handled) {
            $handled
                ->setSlug(
                    $this->please->getBundleService('string')->getSlug(
                        isset($posted->_info->slug) && !empty($posted->_info->slug) 
                        ? $posted->_info->slug 
                        : $posted->info->title_singular ?? $posted->info->title
                    ))
                ->setUser($this->getUser())
                ->setInfo($posted->info)
                ->setCreated(new \DateTime($posted->_info->created ?? "now" ))
                ;
                return $handled;
          },
          'formView' => function ($form, $bloggy_item) use ($bloggyRepo) {

            $bloggy = $bloggyRepo->find($bloggy_item->getInfo()->_acf); // required to generate the form fields through "bloggy.info.fields"

            $bundleDir = $this->please->getBundleService('dir');
            $options = [
                'layouts' =>  $bundleDir->asOptions( $bundleDir->getThemeDirPath('layouts') ),
                'pages' => $this->please->getBundleService('post')->getTree(),
                'menus' => $this->please->getBundleService('post')->getTree('itemCheckable'),
                'navs' => $bundleDir->asOptions( $bundleDir->getThemeDirPath('navs') ),
            ];

            return new JsonResponse([
                'view' => $this->getTemplate('acf-add', compact('form', 'bloggy', 'bloggy_item', 'options')),
                'title' => $bloggy_item->getInfo()->title . " <em style='font-size:15px;display:block;color:#a7a7a7;font-family:monospace;margin-left:45px'>{$bloggy->getSlug()}</em>"
            ]);
          },
          'onSuccess' => function ($item) {

            $this->unsetStorages();

            return new JsonResponse([
                'success' => true,
                'msg' => 'Données enregistrées avec succès',
            ]);
          },
      ]);
    }
    
    /**
     * @Route("bloggy/acf/{type}/search", name="_searchInACF")
     */
    public function _searchInACF($type, BundleBloggyRepository $bloggyRepo)
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles(), 'otherwise' => $this->otherwise()],
            'finder' => function() use ($bloggyRepo, $type){
                return $bloggyRepo->findOneBy(['type' => 'acf', 'slug' => $type]);
            },
            'onFound' => function($acf_parent) use ($type, $bloggyRepo){

                return $this->please->search([
                    'isXml' => '_base',
                    'repository' => $bloggyRepo,
                    'type' => $type,
                    'table' => $this->please->getTableName('bloggies'),
                    'fieldName' => 'q',
                    'columns' => ['id', 'info'],
                    'onSearch' => function($data) use ($type, $bloggyRepo, $acf_parent) {
                        
                        return $this->please->readList([
                            'items' => function () use ($data, $type, $bloggyRepo) {
                                return $data['get']('rows');
                            },
                            'perPage' => $this->please->getRequestStackQuery()->get('per_page', 25),
                            'view' => function ($bloggy_, $filterForm, $total) use ($acf_parent, $type, $bloggyRepo) {
                                $isREADING = true;
                                $bloggy = $this->please->fetchEager($bloggy_?$bloggy_->getItems():null, $bundle=true);
                                return new JsonResponse([
                                    'view' => $this->getTemplate('acf-add', compact('bloggy', 'bloggy_', 'filterForm', 'total', 'type', 'isREADING', 'acf_parent')),
                                    'title' => $acf_parent->getInfo()->title . ' ('.$total.')'
                                ]);
                            },
                        ]);
                    },
                    'otherwise' => function() use ($type, $bloggyRepo){
                        return $this->_listACFBloggy($type, $bloggyRepo);
                    }
                ]);
            }
        ]);
    }

    private function otherwise()
    {
      return new JsonResponse([
          'success' => false,
          'msg' => 'Accès interdit',
          'redirect' => $this->generateUrl('_authAdmin'). '?redirect_url=coming-soon'
      ]);
    }

    private function unsetStorages()
    {
        $this->please->unsetStorage([ '___PagesCollection', '___ACFCollection', '___ACFChildrenCollection', '___ACFChildren2Collection', '__UsersCollection', 'forkDoctrineORM' . md5(true) ], true);
        $this->please->getBundleService('dir')->delTree( $this->please->previousContainer->get('kernel')->getProjectDir() . '/var/storage' );
    }

    private function getTemplate(string $templateName, array $parameters = array(), $response = null)
    {
        return $this->please->getBundleService('view')->sanitizeFinalView(
            $this->renderView("@DovStoneBlogAdminBundle/{$templateName}.html.twig", $parameters, $response)
        );
    }

}
