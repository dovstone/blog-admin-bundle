<?php

namespace DovStone\Bundle\BlogAdminBundle\Controller;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Entity\User;
use DovStone\Bundle\BlogAdminBundle\Form\UserType;
use DovStone\Bundle\BlogAdminBundle\Repository\BundleUserRepository;
use DovStone\Bundle\BlogAdminBundle\Repository\BundleBloggyRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/_admin/")
 */
class AdminController extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->please->getBundleService('execute_before')->executeBefore();
        $this->please->previousContainer->get('service.execute_before')->__run();

        $this->adminRoles = $this->please->getAdminRoles();
        $this->usersTable = $this->please->getTableName($table='users');
        $this->bloggyRepo = $this->please->getBundleRepo('Bloggy');
    }

    /**
     * @Route("is-authed", name="_isAuthed")
     */
    public function _isAuthed()
    {
        //if( !$this->getUser() || $this->please->getUserRole() !== '_blog_admin' ){
        if( !$this->getUser() || !in_array(strtoupper($this->please->getUserRole()), $this->adminRoles) ){
            $ref = $this->please->getReferer();
            if(strpos($ref, '/_admin/logout') === false){
                $urlService = $this->please->getBundleService('url');
                if( $ref == $urlService->getUrl('_admin').'/' ){
                    $ref = $urlService->getUrl('_admin/dashboard');
                }
                $p['url'] = $this->generateUrl('_authAdmin') . '?redirect_url=' . $ref;
            }
            else { $p = []; }
            $userWasNotFound = null;
            return new JsonResponse(array_merge([
                'success' => true,
                'authed' => false,
                'newDom' => $this->getTpl('login', compact('userWasNotFound'))
            ], $p));
        }
        else {
            return new JsonResponse([
                'success' => true,
                'authed' => true
            ]);
        }
    }

    /**
     * @Route("auth", name="_authAdmin")
     */
    public function _authAdmin(BundleUserRepository $userRepo)
    {
        return $this->please->login([
            'isXml' => '_base',
            'finder' => function ($posted) use ($userRepo) {

                // username attempt
                $attempt = $this->please->findOneLike($userRepo, $this->usersTable, [
                    'roles' => $this->adminRoles,
                    'username' => $posted->ident ?? '',
                    'password' => sha1($posted->password),
                    'enabled' => true,
                    'validated' => true
                ])['get']('rows');

                // username email
                if(!$attempt){
                    $attempt = $this->please->findOneLike($userRepo, $this->usersTable, [
                        'roles' => $this->adminRoles,
                        'email' => $posted->ident ?? '',
                        'password' => sha1($posted->password),
                        'enabled' => true,
                        'validated' => true
                    ])['get']('rows');
                }

                // username mle
                if(!$attempt){
                    $attempt = $this->please->findOneLike($userRepo, $this->usersTable, [
                        'roles' => $this->adminRoles,
                        'mle' => $posted->ident ?? '',
                        'password' => sha1($posted->password),
                        'enabled' => true,
                        'validated' => true
                    ])['get']('rows');
                }

                return $attempt;
            },
            'formView' => function ($userWasNotFound) {
                return new JsonResponse([
                    'success' => true,
                    'newDom' => $this->getTpl('login', compact('userWasNotFound'))
                ]);
            },
            'onSuccess' => function ($user) {
                return new JsonResponse([
                    'success' => true,
                    'newDom' => $this->getTpl('login')
                ]);
            },
            "onAlreadyLoggedIn" => function($user){
                return new JsonResponse([
                    'success' => true,
                    'authed' => true,
                    'msg' => "Vous êtes déja connecté en tant que {$user->getUsername()}",
                    'redirect' => $this->please->getRequestStackQuery()->get('redirect_url') ?? $this->generateUrl('_dashboard')
                ]);
            }
        ]);
    }

    /**
     * @Route("logout", name="_logoutAdmin")
     */
    public function _logoutAdmin()
    {
        return $this->please->logout([
            'isXml' => '_base',
            'onAlreadyLoggedOut' => function(){
                return new JsonResponse([
                    'success' => true,
                    'authed' => false,
                    'newDom' => $this->getTpl('login'),
                ]);
            },
            'onLoggedOut' => function(){
                return new JsonResponse([
                    'success' => true,
                    'authed' => false,
                    'newDom' => $this->getTpl('login'),
                ]);
            }
        ]);
    }

    /**
     * @Route("profile", name="_adminProfile")
     */
    public function _adminProfile()
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->adminRoles],
            'finder' => function () {
                return $this->getUser();
            },
            'onFound' => function ($user) {
                return new JsonResponse([
                    'success' => true,
                    'title' => $this->please->getUserFullName($user),
                    'view' => $this->getTpl('admin/profile')
                ]);
            },
        ]);
    }

    /**
     * @Route("edit/password", name="_editAdminPassword")
     */
    public function _editAdminPassword()
    {
        $this->errors = [];

        return $this->please->update([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->adminRoles],
            'validator' => [
                'info[old_password]' => function($posted){
                    if( sha1($posted->info->old_password) !== $this->getUser()->getPassword() ){
                        return $this->errors['info[old_password]'] = 'Mot de passe actuel incorrect';
                    }
                },
                'info[new_password]' => function($posted){
                    if( $posted->info->new_password !== $posted->info->confirm_password ){
                        return $this->errors['info[confirm_password]'] = $this->errors['info[new_password]'] = 'Les mots de passe ne correspondent pas';
                    }
                }
            ],
            'type' => UserType::class,
            'finder' => function () {
                return $this->getUser();
            },
            'sanitizer' => function ($posted, $handled) {
                $psw = sha1($posted->info->new_password);
                $handled
                    ->setOldPassword($psw)
                    ->setPassword($psw)
                    ;
                return $handled;
            },
            'formView' => function ($form) {
                $page = 'edit--password';

                $r = [
                    'success' => true,
                    'title' => $this->please->getUserFullName($this->getUser()),
                    'view' => $this->getTpl('admin/profile', compact('form', 'page'))
                ];
                if( !empty($this->errors) ){
                    unset($r['view']);
                    $r['success'] = false;
                    $r['errors'] = $this->errors;
                    $r['msg'] = 'Merci de vérifier les informations saisies';
                }

                return new JsonResponse($r);
            },
            'onSuccess' => function () {
                
                $this->unsetStorages();

                return $this->please->reLoginUser([
                    'onSuccess' => function ($reloggedInUser) {
                        return new JsonResponse([
                            'success' => true,
                            'msg' => 'Mot de passe modifié avec succès',
                        ]);
                    },
                ]);
            },
        ]);
    }

    /**
     * @Route("create", name="_createAdmin")
     */
    public function _createAdmin($params__=[], BundleUserRepository $userRepo)
    {   
        $params = array_merge([

            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->adminRoles],
            /*'validator' => [
                'email' => function ($posted) use ($userRepo) {
                    if ($userRepo->findBy(['email' => $posted->u->email])) {
                        //return 'Cette adresse mail est déjà rattachée à un compte';
                    }
                },
            ],*/
            'type' => UserType::class,
            'entity' => new User(),
            'sanitizer' => function ($posted, $handled) use ($params__) {

                $stringServ = $this->please->getBundleService('string');

                // create
                if( empty($params__) ){
                  
                    $psw = sha1($posted->u->password);

                    $handled
                        ->setRoles([ strtoupper($posted->u->roles) ])
                        ->setUsername($posted->u->username)
                        ->setUsernameSlugged( $stringServ->getSlug($posted->u->lastname) )
                        ->setFirstname($posted->u->firstname)
                        ->setLastname($posted->u->lastname)
                        ->setEnabled(true)
                        ->setValidated(true)
                        ->setContact($posted->u->contact)
                        ->setEmail($posted->u->email)
                        ->setPassword($psw)
                        ->setOldPassword($psw)
                        ->setInfo($posted->info ?? [])
                        ;
                }
                // edit
                else {
                    $handled
                        ->setRoles([ strtoupper($posted->u->roles) ])
                        ->setUsername($posted->u->username)
                        ->setUsernameSlugged( $stringServ->getSlug($posted->u->lastname) )
                        ->setFirstname($posted->u->firstname)
                        ->setLastname($posted->u->lastname)
                        ->setContact($posted->u->contact)
                        ->setEmail($posted->u->email)
                        ->setInfo($posted->info ?? [])
                        ;
                }

                return $handled;
            },
            'formView' => function ($form) use ($params__) {

                $roles = $this->please->getAllRoles();

                if(!empty($params__)) {
                    $isUpdating = true;
                    $compact = compact('form', 'roles', 'isUpdating');
                }
                else {
                    $isCreating = true;
                    $compact = compact('form', 'roles', 'isCreating');
                }


                return new JsonResponse([
                    'view' => $this->getTpl('admin/admin', $compact),
                    'title' => 'Ajouter un compte Utilisateur'
                ]);
            },
            'onSuccess' => function ($user) {
                
                $this->unsetStorages();

                return new JsonResponse([
                    'success' => true,
                    'msg' => empty($params__) ? 'Données enregistrées' : 'Données mises à jour',
                    'redirect' => $this->generateUrl('_updateAdmin', ['id' => $user->getId()]),
                ]);
            }

        ], $params__);

        //dd($params__, $params);
        return empty($params__) ? $this->please->create($params) : $this->please->update($params);
    }

    /**
     * @Route("users/list", name="_listAdmins")
     */
    public function _listAdmins(BundleUserRepository $userRepo)
    {
        return $this->please->readList([
            'isXml' => '_base',
            /*'isGranted' => [
                'isGranted' => ["byUserRoles" => $this->adminRoles],
            ],*/
            'items' => $userRepo->findBy([], ['created' => 'DESC']),
            'perPage' => 15,
            'view' => function ($admins) {
                $isLISTING = true;
                return new JsonResponse([
                    'success' => true,
                    'title' => 'Utilisateurs',
                    'view' => $this->getTpl('admin/admin', compact('admins', 'isLISTING'))
                ]);
            },
        ]);
    }

    /**
     * @Route("users/{id}/update", name="_updateAdmin")
    */
    public function _updateAdmin($id, BundleUserRepository $userRepo)
    {
        return $this->_createAdmin($params__=[
            'finder' => function() use ($id, $userRepo) {
                return $userRepo->find($id);
            }
        ], $userRepo);
    }
    
    /**
     * @Route("users/{id}/delete", requirements={"id":"[0-9]+"}, name="_deleteAdmin")
     */
    public function _deleteAdmin($id, BundleUserRepository $userRepo)
    {
        return $this->please->delete([
            'isGranted' => [
                "byUserRoles" => $this->adminRoles,
                "otherwise" => function(){return $this->redirectToRoute('_authAdmin');}
            ],
            'finder' => function () use ($id, $userRepo) {
                return $userRepo->find($id);
            },
            'onNotFound' => function ($id) {},
            'onSuccess' => function ($user) {
                $this->unsetStorages();
                return new JsonResponse([
                    'success' => true,
                    'msg' => 'Compte supprimé définitivement avec succès'
                ]);
            }
        ]);
    }
    
    /**
     * @Route("users/{id}/{action}", requirements={"action":"on|off"}, name="_onOffAdmin")
    */
    public function _onOffAdmin( $id, $action, BundleUserRepository $userRepo )
    {
        return $this->please->basicUpdate([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->adminRoles],
            'finder' => function () use ($userRepo, $id) {
                return $userRepo->find($id);
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

    private function unsetStorages()
    {
        $this->please->unsetStorage([ '___PagesCollection', '___ACFCollection', '___ACFChildrenCollection', '___ACFChildren2Collection', '___UsersCollection' ], true);
        $this->please->getBundleService('dir')->delTree( $this->please->previousContainer->get('kernel')->getProjectDir() . '/var/storage' );
    }































    /**
     * Route("role", name="_admins_role")
     */
    public function _admins_role()
    {
        return new JsonResponse([
            'roles' => $this->getUser() ? $this->getUser()->getRoles() : null
        ]);
    }


    /**
     * Route("{id}/{action}", requirements={"action":"disable|enable"}, name="_admins_ED")
     */
    public function _admins_ED($id, $action, BundleUserRepository $userRepo)
    {
        return $this->please->basicEdit([
            'isGranted' => [
                "byUserRoles" => $this->adminRoles,
                "otherwise" => function(){return $this->redirectToRoute('_admins_auth');}
            ],
            'finder' => function () use ($userRepo, $id) {
                return $userRepo->find($id);
            },
            'onNotFound' => function () {return $this->redirectToRoute('_admins_auth');},
            'sanitizer' => function ($handled) use ($action) {
                $handled->setEnabled($action == 'disable');
                return $handled;
            },
            'onSuccess' => function ($user) use ($action) {
                switch ($action) {
                    case 'disable':$v='désactivé';break;
                    case 'enable':$v='activé';break;
                    default:$v='supprimé';break;
                }
                $this->addFlash('success', "Compte $v avec succès");
                return $this->please->redirectToReferer();
            }
        ]);
    }

    /**
     * Route("settings", name="_admins_settings")
     */
    public function _admins_settings()
    {
        return $this->please->show([
            'isGranted' => [
                "byUserRoles" => ['_BLOG_ADMIN', '_BLOG_OPERATOR'],
                "otherwise" => function(){return $this->redirectToRoute('_admins_auth');}
            ],
            'finder' => function () {return true;},
            'onFound' => function () {
                return $this->view(
                    'Paramètres Administrateur',
                    'settings'
                );
            },
        ]);
    }

    private function getTpl(string $templateName, array $parameters = array(), $response = null)
    {
        return $this->please->getBundleService('view')->sanitizeFinalView(
            $this->renderView("@DovStoneBlogAdminBundle/{$templateName}.html.twig", $parameters, $response)
        );
    }
}
