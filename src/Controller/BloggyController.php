<?php

namespace DovStone\Bundle\BlogAdminBundle\Controller;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Form\BloggyType;
use DovStone\Bundle\BlogAdminBundle\Entity\Bloggy;
use DovStone\Bundle\BlogAdminBundle\Repository\BundleBloggyRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/_admin/")
 */
class BloggyController extends AbstractController
{

    public function __construct(PleaseService $please)
    {
      $this->please = $please;
      //
      $this->please->getBundleService('execute_before')->executeBefore();
      $this->please->previousContainer->get('service.execute_before')->__run();

        $this->bloggyTable = $this->please->getTableName($table='bloggies');
    }

    /**
     * @Route("", name="_base")
     */
    public function adminBase()
    {
      return $this->please->read([
          'meta' => function(){
              return 'Bienvenue';
          },
          'onFound' => function () {
              return new Response($this->getTemplate('_base'));
          }
      ]);
    }

    /**
     * @Route("dashboard", name="_dashboard")
     */
    public function _dashboard()
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => [
                'byUserRoles' => $this->please->getAdminRoles(),
                'otherwise' => function(){
                  return $this->otherwise();
                }
            ],
            'onFound' => function () {
                return new JsonResponse([
                  'title' => 'Tableau de bord',
                    'view' => $this->getTemplate('dashboard'),
                    'success' => true
                ]);
            }
        ]);
    }

    /**
     * @Route("bloggy/{type}/create", name="_createBloggy")
     */
    public function _createBloggy($type, BundleBloggyRepository $bloggyRepo)
    {
      if( $type == 'table-relations' ){
        $tableRelations = $bloggyRepo->findOneBy(['type' => 'table-relations']);
        if($tableRelations){
            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('_updateBloggy', ['type' => $type, 'id' => $tableRelations->getId()])
            ]);
        }
      }

      return $this->please->create([
          'isXml' => '_base',
          'isGranted' => [
              'byUserRoles' => $this->please->getAdminRoles(),
              'otherwise' => $this->otherwise()
          ],
          'validator' => $this->bloggyValidator($type, $bloggyRepo),
          'type' => BloggyType::class,
          'entity' => new Bloggy(),
          'sanitizer' => function ($posted, $handled) use ($type) {
              $handled
                ->setType($type)
                ->setSlug(
                  $this->please->getBundleService('string')->getSlug(
                      isset($posted->_info->slug) && !empty($posted->_info->slug) 
                      ? $posted->_info->slug 
                      : $posted->info->title
                  ))
                ->setInfo($this->sanitizeInfo($type, $posted))
                ->setUser($this->getUser())
                ->setEnabled(true)
                ;
                return $handled;
          },
          'formView' => function ($form) use ($type, $bloggyRepo) {

                $dirService = $this->please->getBundleService('dir');
                $options = [
                    'layouts' =>  $dirService->asOptions( $dirService->getThemeDirPath('layouts') ),
                    'pages' => $this->please->getBundleService('post')->getTree(),
                    'menus' => $this->please->getBundleService('post')->getTree('itemCheckable'),
                    'navs' => $dirService->asOptions( $dirService->getThemeDirPath('navs') ),
                ];

              return new JsonResponse([
                  'view' => $this->getTemplate($type, compact('form', 'type', 'options')),
                  'title' => $this->getTitle($type, 0)
              ]);
          },
          'onSuccess' => function ($item) use ($type) {

              $this->unsetStorages();

              return new JsonResponse([
                  'success' => true,
                  'msg' => 'Données enregistrées avec succès',
                  //'reload' => $type=='acf'?true:false,
                  'redirect' => $this->generateUrl('_updateBloggy', ['type' => $item->getType(), 'id' => $item->getId()])
              ]);
          },
      ]);
    }

    /**
     * @Route("bloggy/{type}/list", name="_listBloggy")
     */
    public function _listBloggy($type, BundleBloggyRepository $bloggyRepo)
    {
      return $this->please->readList([
          'isXml' => '_base',
          'isGranted' => [
              'byUserRoles' => $this->please->getAdminRoles(),
              'otherwise' => $this->otherwise()
          ],
          //'filterCriterias' => function() use ($type) {},
          'items' => function ($filterQuery) use ($type, $bloggyRepo) {

              if( $filterQuery ){
                $sql = "SELECT i.id FROM ".$this->bloggyTable." i WHERE $filterQuery AND i.type = '$type'";
              }
              else {
                $sql = "SELECT i.id FROM ".$this->bloggyTable." i WHERE i.type = '$type'";
              }

              $iDs = $this->please->fetchAll($sql);
              
              return $iDs ? $bloggyRepo->findBy(['id' => $iDs], ['created' => 'DESC']) : [];
          },
          'perPage' => $this->please->getRequestStackQuery()->get('per_page', 15),
          'view' => function ($bloggy_, $filterForm, $total) use ($type) {

              $isREADING = true;

              //$bloggy = $this->please->fetchEager($bloggy_?$bloggy_->getItems():null, $bundle=true);
              $bloggy = $bloggy_?$bloggy_->getItems():null;

              //dump( $bloggy_, $bloggy );

              return new JsonResponse([
                  'view' => $this->getTemplate($type, compact('bloggy', 'bloggy_', 'filterForm', 'total', 'type', 'isREADING')),
                  'title' => $this->getTitle($type, 1)
              ]);
          },
      ]);
    }
    
    /**
     * @Route("bloggy/{type}/search", name="_searchIn")
     */
    public function _searchIn($type, BundleBloggyRepository $bloggyRepo)
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles(), 'otherwise' => $this->otherwise()],
            'finder' => function() use ($bloggyRepo, $type){
                return $bloggyRepo->findOneBy(['type' => $type]);
            },
            'onFound' => function() use ($type, $bloggyRepo){

                return $this->please->search([
                    'isXml' => '_base',
                    'repository' => $bloggyRepo,
                    'type' => $type,
                    'table' => $this->please->getTableName('bloggies'),
                    'fieldName' => 'q',
                    'columns' => ['id', 'info'],
                    'onSearch' => function($data) use ($type, $bloggyRepo) {

                        return $this->please->readList([
                            'items' => function () use ($data) {
                                return $data['get']('rows');
                            },
                            'perPage' => $this->please->getRequestStackQuery()->get('per_page', 25),
                            'view' => function ($bloggy_, $filterForm, $total) use ($type) {
                                $isREADING = true;
                                $bloggy = $this->please->fetchBundleEager($bloggy_?$bloggy_->getItems():null);
                                return new JsonResponse([
                                    'view' => $this->getTemplate($type, compact('bloggy', 'bloggy_', 'filterForm', 'total', 'type', 'isREADING')),
                                    'title' => $this->getTitle($type, 1)
                                ]);
                            },
                        ]);
                    },
                    'otherwise' => function() use ($type, $bloggyRepo){
                        return $this->_listBloggy($type, $bloggyRepo);
                    }
                ]);
            }
        ]);
    }

    /**
     * @Route("bloggy/{type}/{id}/update", name="_updateBloggy")
    */
    public function _updateBloggy($type, $id, BundleBloggyRepository $bloggyRepo)
    {
      return $this->please->update([
          'isXml' => '_base',
          'isGranted' => [
              'byUserRoles' => $this->please->getAdminRoles(),
              'otherwise' => $this->otherwise()
          ],
          'validator' => $this->bloggyValidator($type, $bloggyRepo),
          'type' => BloggyType::class,
          'entity' => new Bloggy(),
          'finder' => function() use ($id, $bloggyRepo){
            return $bloggyRepo->find($id);
          },
          'sanitizer' => function ($posted, $handled) use ($type) {

              $handled
                ->setType($type)
                ->setSlug($this->please->getBundleService('string')->getSlug(isset($posted->_info->slug) && !empty($posted->_info->slug) ? $posted->_info->slug : $posted->info->title ) )
                ->setInfo(array_merge((array)$handled->getInfo(), (array)$this->sanitizeInfo($type, $posted)))
                ->setUser($this->getUser())
                ;
                return $handled;
          },
          'formView' => function ($form, $item) use ($type) {

                $dirService = $this->please->getBundleService('dir');
                $options = [
                    'layouts' =>  $dirService->asOptions( $dirService->getThemeDirPath('layouts') ),
                    'pages' => $this->please->getBundleService('post')->getTree(),
                    'menus' => $this->please->getBundleService('post')->getTree('itemCheckable'),
                    'navs' => $dirService->asOptions( $dirService->getThemeDirPath('navs') )
                ];

              $isEditMode = true;

              if( $type == 'acf' ){ $acf = $item; } else { $acf = null; }

              return new JsonResponse([
                  'view' => $this->getTemplate($type, compact('form', 'isEditMode', 'options', 'acf')),
                  'title' => $this->getTitle($type, 2)
              ]);
          },
          'onSuccess' => function ($sanitized) use ($type) {

              $this->unsetStorages();

              if( $type == 'table-relations' ){
                $this->please->unsetStorage(['forkDoctrineORM' . md5(true)], true);
              }

              return new JsonResponse([
                  'success' => true,
                  'msg' => 'Données mises à jour avec succès',
                  'reload' => $type=='acf'?true:false
              ]);
          },
      ]);
    }

    /**
     * @Route("bloggy/{id}/basic-update", name="_basicUpdateBloggy", methods="POST")
    */
    public function _basicUpdateBloggy($id, BundleBloggyRepository $bloggyRepo)
    {
      return $this->please->basicUpdate([
          //'isXml' => '_base',
          'isGranted' => [
              'byUserRoles' => $this->please->getAdminRoles(),
              'otherwise' => $this->otherwise()
          ],
          'validator' => [
              'sections' => function(){},
              'info[html]' => function(){},
              'info[ctx]' => function(){},
              'info[css]' => function(){}
          ],
          'finder' => function() use ($id, $bloggyRepo){
            return $bloggyRepo->find($id);
          },
          'sanitizer' => function ($posted, $handled) {
              
              $info = $handled->getInfo();
              
              //$info->html = $this->please->getBundleService('minifier')->getMinifiedHtml($posted->info->html);
              //$info->css = $this->please->getBundleService('minifier')->getMinifiedCss($posted->info->css);
              
              $info->html = $posted->info->html;
              $info->css = $posted->info->css;
              
              parse_str($posted->info->ctx, $ctx);
              $info->ctx = $ctx;

            $handled->setInfo($info);
            
            return $handled;
          },
          'onSuccess' => function ($sanitized) {

              $this->unsetStorages();

              // lets delete "var" dir
                $dirService = $this->please->getBundleService('dir');

                $dirService->remove( $dirService->dirPath('var') );

              // lets update sections
              $sections = json_decode($this->please->getRequestStackRequest()->get('sections'));
              if( $sections ){
                foreach ($sections as $section) {
                    $file = $dirService->getThemeDirAbsDirPath('sections') . '/' . $section->name . '.html.twig';
                    if( file_exists($file) ){
                        file_put_contents($file, $section->content);
                    }
                }
              }

              return new JsonResponse([
                  'success' => true,
                  'msg' => 'Données mises à jour avec succès'
              ]);
          },
      ]);
    }

    /**
     * @Route("bloggy/{id}/{action}", requirements={"action":"on|off"}, name="_onOffBloggy")
    */
    public function _onOffBloggy( $id, $action, BundleBloggyRepository $bloggyRepo )
    {
        return $this->please->basicUpdate([
            'isXml' => '_base',
            'isGranted' => [
                'byUserRoles' => $this->please->getAdminRoles(),
                'otherwise' => $this->otherwise()
            ],
            'finder' => function () use ($bloggyRepo, $id) {
                return $bloggyRepo->find($id);
            },
            'sanitizer' => function ($posted, $handled) use ($action) {
                $handled->setEnabled($action == 'on');
                return $handled;
            },
            'onSuccess' => function ($user) use ($action) {
                $this->unsetStorages();
                return new JsonResponse([
                    'success' => true,
                    'msg' => 'Données ' . ($action=='on'?'activées ':'désactivées') . " avec succès"
                ]);
            }
        ]);
    }

    /**
     * @Route("bloggy/{id}/delete", name="_deleteBloggy")
    */
    public function _deleteBloggy( $id, BundleBloggyRepository $bloggyRepo )
    {
        return $this->please->delete([
            'isXml' => '_base',
            'isGranted' => [
                'byUserRoles' => $this->please->getAdminRoles(),
                'otherwise' => $this->otherwise()
            ],
            'finder' => function () use ($bloggyRepo, $id) {
                return $bloggyRepo->find($id);
            },
            'onSuccess' => function ($bloggy) {
                $this->unsetStorages();
                return new JsonResponse([
                    'success' => true,
                    'msg' => 'Données supprimées avec succès',
                    'reload' => $bloggy->getType()=='acf'?true:false
                ]);
            }
        ]);
    }

    private function getTitle($type, $i)
    {
        return [
            'page'    => ['Ajouter une nouvelle page', 'Toutes les pages', 'Modifier page'],
            'article' => ['&Eacute;crire un nouvel article', 'Tous les articles', 'Modifier article'],
            'menu'    => ['Ajouter un menu', 'Menus de navigation', 'Modifier menu'],
            'role'    => ['Ajouter un rôle', 'Rôles utilisateurs', 'Modifier rôle'],
            'acf'     => ['Ajouter un ACF', 'ACF', 'Modifier ACF'],
            'table-relations' => ['Relations entre entités', '', 'Relations entre entités'],
            'issue'   => ['', 'Enregistrements présentant des erreurs', ''],
        ][$type][$i];
    }

    private function unsetStorages()
    {
        $this->please->unsetStorage([ '___PagesCollection', '___ACFCollection', '___ACFChildrenCollection', '___ACFChildren2Collection', '___UserCollection' ], true);
        $this->please->getBundleService('dir')->delTree( $this->please->previousContainer->get('kernel')->getProjectDir() . '/var/storage' );
    }

    private function bloggyValidator($type, $bloggyRepo)
    {
      switch ($type) {

        case 'page':
        case 'article':
          return [
            'info[parent]' => function($posted) use ($bloggyRepo) {
              if( !isset($posted->info->parent) ){
                  $err=true;
              }
              else {
                if( 
                    $posted->info->parent !== 'null' 
                    && 
                    !$bloggyRepo->findOneBy(['type'=> 'page', 'id'=> $posted->info->parent, 'enabled' => true])
                    &&
                    !$bloggyRepo->findOneBy(['id'=> $posted->info->parent, 'enabled' => true]) // acf_as_pages
                ){
                  $err=true;
                }
            }
            if(isset($err)){
                return 'Merci de renseigner un <b>Parent</b> valide.';
              }
            },
            'info[title]' => function($posted){
              if( !isset($posted->info->title) || empty($posted->info->title) ){
                return 'Merci de renseigner un <b>Titre</b> valide.';
              }
            }
          ];
          break;
        case 'menu':
          return [
            'info[title]' => function($posted){
              if( !isset($posted->info->title) || empty($posted->info->title) ){
                return 'Merci de renseigner un <b>Nom du menu</b> valide.';
              }
            },
            'info[pages_ids]' => function($posted) use ($bloggyRepo) {
              if( !isset($posted->info->pages_ids) || empty($posted->info->pages_ids) || $posted->info->pages_ids == '[]' ){
                return '';
              }
            }
          ];
          break;
        /*case 'acf':
          return [
            'info[title]' => function($posted) use ($bloggyRepo) {
              if( !isset($posted->info->title) || empty($posted->info->title) ){
                return '';
              }
            },
            'field[name]' => function($posted){
              if( !isset($posted->field->name) || empty($posted->field->name) ){
                return '';
              }
            },
            'field[key]' => function($posted) use ($bloggyRepo) {
              if( !isset($posted->field->key) || empty($posted->field->key) ){
                return '';
              }
            },
            'field[type]' => function($posted) use ($bloggyRepo) {
              if( !isset($posted->field->type) || empty($posted->field->type) ){
                return '';
              }
            },
            'field[col]' => function($posted) use ($bloggyRepo) {
              if( !isset($posted->field->col) || empty($posted->field->col) ){
                return '';
              }
            },
          ];
          break;*/

        default:
          # code...
          break;
      }
    }

    private function sanitizeInfo($type, $posted)
    {
      switch ($type) {

        case 'page':
            $info = $posted->info;
            return (object) array_merge( (array) $info, [
              'long_title'  => isset($info->long_title) && !empty($info->long_title) ? $info->long_title : $info->title,
              'keywords'    => $this->please->getBundleService('string')->getTag(isset($info->keywords) && !empty($info->keywords) ? $info->keywords : $info->title),
              'description' => isset($info->description) && !empty($info->description) ? $info->description : $info->title,
              'rank'        => isset($info->rank) && !empty($info->rank) ? $info->rank : $info->rank,
              'href'        => isset($info->href) && !empty($info->href) ? $info->href : $info->href,
              'thumb'       => isset($info->thumb) && !empty($info->thumb) ? $info->thumb : $info->thumb,
              'layout'      => isset($info->layout) && !empty($info->layout) ? $info->layout : $info->layout,

              'in_menu'     => isset($info->in_menu) ? 1 : 0,
              'auth'        => isset($info->auth) ? 1 : 0,
              'comments'    => isset($info->comments) ? 1 : 0,
            ]);
          break;

        case 'article':
            $info = $posted->info;
            return (object) array_merge( (array) $info, [
              'keywords'    => $this->please->getBundleService('string')->getTag(isset($info->keywords) && !empty($info->keywords) ? $info->keywords : $info->title),
              'description' => isset($info->description) && !empty($info->description) ? $info->description : $info->title,
              'thumb'       => isset($info->thumb) && !empty($info->thumb) ? $info->thumb : $info->thumb,
              'auth'        => isset($info->auth) ? 1 : 0,
              'comments'    => isset($info->comments) ? 1 : 0,
            ]);
          break;

        case 'menu':
            return $posted->info; // it allows us to unset _info[enabled] and _token
          break;
        
        default:
            return $posted->info; // it allows us to unset _info[enabled] and _token
          break;
      }
    }


    private function getTemplate(string $templateName, array $parameters = array(), $response = null)
    {
        return $this->please->getBundleService('view')->sanitizeFinalView(
            $this->renderView("@DovStoneBlogAdminBundle/{$templateName}.html.twig", $parameters, $response)
        );
    }

    private function otherwise()
    {
      return new JsonResponse([
          'success' => false,
          'msg' => 'Accès interdit',
          'redirect' => $this->generateUrl('_authAdmin'). '?redirect_url=coming-soon'
      ]);
    }
}
