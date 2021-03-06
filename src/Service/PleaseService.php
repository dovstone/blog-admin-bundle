<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use DovStone\Bundle\BlogAdminBundle\Entity\User;
use DovStone\Bundle\BlogAdminBundle\Entity\Bloggy;
use PHPMailer\PHPMailer\PHPMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Markup;

/*use DovStone\Bundle\BlogAdminBundle\Service\SessionService;
use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\NavService;
use DovStone\Bundle\BlogAdminBundle\Service\PostService;
use DovStone\Bundle\BlogAdminBundle\Service\PageService;
use DovStone\Bundle\BlogAdminBundle\Service\ArticleService;
use DovStone\Bundle\BlogAdminBundle\Service\SectionService;
use DovStone\Bundle\BlogAdminBundle\Service\DirService;
use DovStone\Bundle\BlogAdminBundle\Service\MediaService;
use DovStone\Bundle\BlogAdminBundle\Service\TimeService;
use DovStone\Bundle\BlogAdminBundle\Service\StringService;
use DovStone\Bundle\BlogAdminBundle\Service\UrlService;
use DovStone\Bundle\BlogAdminBundle\Service\EnvService;
use DovStone\Bundle\BlogAdminBundle\Service\ExecuteBeforeService;
use DovStone\Bundle\BlogAdminBundle\Service\WidgetBaseService;
use DovStone\Bundle\BlogAdminBundle\Service\WidgetFriendlyWebsiteTemplatesBuilderService;
use DovStone\Bundle\BlogAdminBundle\Service\ViewService;
use DovStone\Bundle\BlogAdminBundle\Service\FormBuilderService;
use DovStone\Bundle\BlogAdminBundle\Service\MixService;
use DovStone\Bundle\BlogAdminBundle\Service\DataService;
use DovStone\Bundle\BlogAdminBundle\Service\__PhpHtmlCssJsMinifierService;
use DovStone\Bundle\BlogAdminBundle\Service\ACFService;
use DovStone\Bundle\BlogAdminBundle\Service\ShortcodeService;
use DovStone\Bundle\BlogAdminBundle\Service\QueryBuilderService;*/

class PleaseService extends AbstractController
{
    protected $requestStack;
    public $previousContainer;
    //
    private $em;
    private $authChecker;
    private $appUiD;
    private $request;
    private $filesystem;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authChecker,
        RequestStack $requestStack
    )
    {
        $this->previousContainer = $container;
        $this->prevContainer = $container;

        $this->requestStack = $requestStack;
        //
        $this->em = $entityManager;
        $this->authChecker = $authChecker;
        $this->filesystem = new Filesystem();
        $this->request = $requestStack->getCurrentRequest();
        //
        $this->appUiD = sha1($_SERVER['APP_NAME']);

        $this->adminRoles[] = '_BLOG_ADMIN';

        if( !defined('__IS_EMPTY__') ){ define('__IS_EMPTY__', '__IS_EMPTY__'); }
    }

    /**
     * C
     */
    public function create($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'meta' => function () {},
            'isValid' => false,
            'validator' => [],
            'onInvalid' => function () {},
            'type' => null,
            'entity' => null,
            'preFill' => null,
            'action' => $this->generateUrl($this->request->attributes->get('_route'), $this->request->get('_route_params')),
            'sanitizer' => function ($posted, $handled, $params) {},
            'formView' => function ($formView, $form, $params) {},
            'onSuccess' => function ($sanitized, $params) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $request = $this->request;

        $params['entity'] = $this->_guessEntity($params);

        $item = $params['entity'];

        $preFill = $params['preFill'];
        $params['item'] = !is_null($preFill) && is_callable($preFill) ? $preFill($item) : $item;

        $this->_cacheItemFound(serialize($params['item']));

        $form = $this->_getCreatedForm($params);

        $form->handleRequest($request);

        $params['form'] = $form;

        //setting meta
        $this->_setMetaData($params, $item);

        if ($request->isMethod('POST')) {

            if( !$form->isSubmitted() ){
                $form->submit($request->request->get($form->getName()));
            }

            if ($form->isSubmitted() /*&& $form->isValid()*/) {

                return $this->_getValidatorFlash($params, function ($params) use ($form) {

                    if( $form->isValid() || $params['isValid'] || !empty($params['validator']) ){

                        $sanitizedBag = $this->_getSanitizedData($params);
                        $this->_persistThenFlushSanitizedBag($sanitizedBag);
                        $this->_setLog($params, $sanitizedBag);
                        //
                        $this->unsetStorage(['___UsersCollection'], true);
                        //
                        return $params['onSuccess']($sanitizedBag, $params);
                    }
                    else {
                        $this->addFlash('error', 'Merci de renseigner des valeurs valides');
                        return $params['formView']($form->createView(), $form, $params);
                    }

                }, $validatorRedirection = false);
            }
        }

        return $params['formView']($form->createView(), $form, $params);
    }

    /**
     * R
     */
    public function read($params)
    {
        return $this->show($params);
    }

    public function readList($params)
    {
        return $this->list($params);
    }

    /**
     * U
     */
    public function update($params)
    {
        return $this->edit($params);
    }

    /**
     * D
     */
    public function delete($params)
    {
        $params = array_merge([
            'isXml' => null,
            //'repository' => null,
            'isGranted' => null,
            'finder' => function () {return true;},
            'onNotFound' => function () {
                //default onNotFound
                return $this->_defaultFallback('onNotFound');
            },
            'onSuccess' => function ($item) {},
            'log' => null
        ], $params);
        
        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        //$item = $params['repository']->find($params['id']);

        $item = $params['finder']();

        if (!$item) {
            return $params['onNotFound']();
        } else {
            if (is_array($item)) {
                foreach ($item as $each_item) {
                    $this->em->remove($each_item);
                    $this->em->flush();
                }
            } else {
                $this->em->remove($item);
                $this->em->flush();
            }

            $this->_setLog($params, $item);

            return $params['onSuccess']($item);
        }
    }

    /**
     * BEGIN BASICS
     */
    public function basicCreate($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'meta' => null,
            'validator' => null,
            'onInvalid' => function () {},
            'entity' => null,
            'sanitizer' => function ($posted, $handled) {},
            'onSuccess' => function ($sanitized) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $request = $this->request;

        //setting meta
        $this->_setMetaData($params, null);

        $userLatestPostedData = $this->_setFormControlsLatestValue($request);

        return $this->_getValidatorFlash($params, function ($params) use ($userLatestPostedData) {
            $sanitizedBag = $params['sanitizer']($userLatestPostedData, $params['entity']);
            $this->_persistThenFlushSanitizedBag($sanitizedBag);
            $this->_setLog($params, $sanitizedBag);
            return $params['onSuccess']($sanitizedBag);
        });
    }

    /**
     * Will be deprecated soon
     * Use basicUpdate instead
     */
    public function basicEdit($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'meta' => null,
            'validator' => null,
            'onInvalid' => function () {},
            'finder' => function () {return true;},
            'onNotFound' => function () {
                //default onNotFound
                return $this->_defaultFallback('onNotFound');
            },
            'sanitizer' => function ($posted, $handled) {},
            'onSuccess' => function ($item) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $item = $params['finder']();

        if (!$item) {
            return $params['onNotFound']();
        } else {

            $this->_cacheItemFound(serialize($item));

            $request = $this->request;

            //setting meta
            $this->_setMetaData($params, null);

            $userLatestPostedData = $this->_setFormControlsLatestValue($request);

            return $this->_getValidatorFlash($params, function ($params) use ($userLatestPostedData, $item) {
                $sanitizedBag = $params['sanitizer'](json_decode(json_encode($_POST)), $item);
                $this->_persistThenFlushSanitizedBag($sanitizedBag);
                $this->_setLog($params, $sanitizedBag);
                return $params['onSuccess']($sanitizedBag);
            });

            /*$sanitizedBag = $params['sanitizer']($this->_getPostedData($params), $item);
            $this->_persistThenFlushSanitizedBag($sanitizedBag);
            $this->_setLog($params, $item);*/
        }
        return $params['onSuccess']($item);
    }

    public function basicUpdate($params)
    {
        return $this->basicEdit($params);
    }

    public function basicDelete($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'finder' => function () {return true;},
            /*'onNotFound' => function () {
                //default onNotFound
                return $this->_defaultFallback('onNotFound');
            },*/
            'onSuccess' => function ($item) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $item = $params['finder']();

        if ($item) {

            if (is_array($item)) {
                foreach ($item as $each_item) {
                    $this->em->remove($each_item);
                    $this->em->flush();
                }
            } else {
                $this->em->remove($item);
                $this->em->flush();
            }

            $this->_setLog($params, $item);
        }

        return $params['onSuccess']($item);

    }
    /**
     * END BASICS
     */

    /**
     * Will be deprecated soon
     * Use readList instead
     */
    public function list($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'meta' => null,
            'filterCriterias' =>  null,
            'items' => null,
            'perPage' => 15,
            'onEmpty' => function () {
                //default onEmpty
                return $this->_defaultFallback('onEmpty');
            },
            'view' => function ($items, $filterForm, $perPage, $total, $params) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }
        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }
        
        $filterForm = '';
        if( $params['filterCriterias'] && is_callable($params['filterCriterias']) ){
            $hasFilterCriterias = true;
            $filterCriterias = $params['filterCriterias']();
            $filterForm = $this->getFilterCriteriasForm($filterCriterias, $params);
        }

        $perPage = (int) $params['perPage'] == 0 ? 1 : (int) $params['perPage'];

        if( is_callable($params['items']) ){
            $items = $params['items'](null);
        }
        else {
            $items = $params['items'];
        }

        if( isset($hasFilterCriterias) && $this->getRequestStackQuery()->get('_filter') ){
            $filterQuery = $this->getFilterSQL();
            $items = $params['items']($filterQuery);
        }

        $items = $this->previousContainer->get('knp_paginator')->paginate(
            $items,
            (int) $this->previousContainer->get('request_stack')->getCurrentRequest()->query->get('page', 1),
            $perPage
        );

        if (empty($items->getItems())) {
            //return $params['onEmpty']();
            return $params['view']($items, $filterForm, 0, 0, $params);
        }

        $view = $params['view']($items, $filterForm, $items->getTotalItemCount(), $perPage, $params);

        //setting meta
        if( isset($items->getItems()[0]) ){ // cause of filesbrowser
            $this->_setMetaData($params, $items->getItems()[0]);
        }

        $this->_setLog($params, $items);

        return $view;
    }

    /**
     * Will be deprecated soon
     * Use read instead
     */
    public function show($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'meta' => null,
            'finder' => function () {return true;},
            'onNotFound' => function () {
                //default onNotFound
                return $this->_defaultFallback('onNotFound');
            },
            'onFound' => function ($item, $params) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $item = $params['finder']();

        if (!$item) {
            return $params['onNotFound']($params);
        } else {

            $onFound = $params['onFound']($item, $params);

            //setting meta
            $this->_setMetaData($params, $item);

            $this->_setLog($params, $item);

            return $onFound;
        }
    }

    /**
     * Will be deprecated soon
     * Use update instead
     */
    public function edit($params)
    {
        $params = array_merge([
            'isXml' => null,
            'isGranted' => null,
            'meta' => null,
            'validator' => null,
            'onInvalid' => function () {},
            'onNotFound' => function () {
                //default onNotFound
                return $this->_defaultFallback('onNotFound');
            },
            'type' => null,
            'entity' => null,
            'isValid' => false,
            'finder' => function ($posted) {},
            'action' => $this->generateUrl($this->request->attributes->get('_route'), $this->request->get('_route_params')),
            'sanitizer' => function ($posted, $handled, $params) {},
            'formView' => function ($formView, $item, $form, $params) {},
            'onSuccess' => function ($sanitized, $params) {},
            'log' => null
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        //dump($this->request);die;

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $request = $this->request;

        $params['entity'] = $this->_guessEntity($params);

        $userLatestPostedData = $this->_setFormControlsLatestValue($request);

        $item = $params['finder']((object) $userLatestPostedData);

        //$item = $params['repository']->find($params['id']);


        if (!$item) {
            return $params['onNotFound']();
        } else {

            $params['item'] = $item;

            $this->_cacheItemFound(serialize($item));

            $form = $this->_getCreatedForm($params);

            $form->handleRequest($request);

            $params['form'] = $form;

            //setting meta
            $this->_setMetaData($params, $item);

            if ($request->isMethod('POST')) {

                if( !$form->isSubmitted() ){
                    $form->submit($request->request->get($form->getName()));
                }

                if ($form->isSubmitted() /*&& $form->isValid()*/) {

                    if( $form->isValid() || $params['isValid'] || !empty($params['validator']) ){

                        $this->_setFormControlsLatestValue($request);

                        return $this->_getValidatorFlash($params, function ($params) use ($item) {
                            $sanitizedBag = $this->_getSanitizedData($params);
                            $this->_persistThenFlushSanitizedBag($sanitizedBag);
                            $this->_setLog($params, $item);
                            //
                            $this->unsetStorage(['___UsersCollection'], true);
                            //
                            return $params['onSuccess']($sanitizedBag, $params);
                        });
                    }
                    else {
                        $this->addFlash('error', 'Merci de renseigner des valeurs valides');
                    }
                }
            }
        }
        return $params['formView']($form->createView(), $item, $form, $params);
    }

    public function login($params)
    {
        //Required to create <input type="hidden" name="login_token" />
        //into your form
        $params = array_merge([
            'isXml' => null,
            'meta' => null,
            'finder' => function ($posted) {},
            'formView' => function ($notFound = null, $params) {}, // will be TRUE if user was not found
            'onSuccess' => function ($found, $params) {},
            'onAlreadyLoggedIn' => null,
            'log' => null,
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        //forcing
        $params['isGranted'] = [
            "byUserRoles" => ['__NONE__']
        ];

        if(false === $this->__isGranted($params)){
            /* if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else { */
                //default onAlreadyLoggedIn
                if( is_callable($params['onAlreadyLoggedIn']) && !is_null($this->getUser()) ){
                    return $params['onAlreadyLoggedIn']($this->getUser());
                }
                return $this->_defaultFallback('onAlreadyLoggedIn');
            //}
        }

        $request = $this->request;

        $submittedToken = $request->request->get('_token');

        //setting meta
        $this->_setMetaData($params);

        if (is_string($submittedToken) && $this->isCsrfTokenValid('login_token', $submittedToken)) {

            $userLatestPostedData = $this->_setFormControlsLatestValue($request);

            $user = $params['finder']((object) $userLatestPostedData);

            if (!$user) {
                return $params['formView']($notFound = true, $params);
            }
            //
            //Handle getting or creating the user entity likely with a posted form
            // The third parameter "main" can change according to the name of your firewall in security.yml
            $token = new UsernamePasswordToken($user, null, 'main', [$user->getRoles()]);
            $this->get('security.token_storage')->setToken($token);

            // If the firewall name is not main, then the set value would be instead:
            // $this->previousContainer->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
            $this->get('session')->set('_security_main', serialize($token));

            // Fire the login event manually
            $event = new InteractiveLoginEvent($request, $token);
            $this->previousContainer->get("event_dispatcher")->dispatch($event, "security.interactive_login");

            $this->_setLog($params);
            //
            return $params['onSuccess']($user, $params);
        }
        return $params['formView'](null, $params);
    }

    public function loginInstant($params)
    {
        $params = array_merge([
            'user' => null,
            'onSuccess' => function ($user) {}
        ], $params);

        //forcing
        $params['isGranted'] = [
            "byUserRoles" => ['__NONE__']
        ];

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $request = $this->request;

        $user = $params['user'];

        //Handle getting or creating the user entity likely with a posted form
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, null, 'main', [$user->getRoles()]);
        $this->previousContainer->get('security.token_storage')->setToken($token);

        // If the firewall name is not main, then the set value would be instead:
        // $this->previousContainer->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->previousContainer->get('session')->set('_security_main', serialize($token));

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        $this->previousContainer->get("event_dispatcher")->dispatch($event, "security.interactive_login");

        return $params['onSuccess']($params['user']);
    }

    public function logout($params)
    {
        $params = array_merge([
            'isXml' => null,
            'onAlreadyLoggedOut' => null,
            'onLoggedOut' => function($cachedUser){},
            'log' => null,
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        if( !$this->getUser() && is_callable($params['onAlreadyLoggedOut']) ){
            return $params['onAlreadyLoggedOut']();
        }

        //forcing
        $params['isGranted']['byUserRoles'] = ['__ANY__'];

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        if ($this->getUser()) {
            $this->setGlobal(["cachedUser" => $this->getUser()]);
            $this->previousContainer->get('security.token_storage')->setToken(null);
            $this->previousContainer->get('session')->set('_security_main', null);
            //$this->previousContainer->get('request_stack')->getSession()->invalidate();
            //$this->previousContainer->get('service.request')->getSession()->invalidate();

            //lets clean any
            // - forms controls flash
            // - forms validator
            // - globals
            // EXERPT cachedUser ( off course! )
            $sess = $this->previousContainer->get('session');
            foreach($sess as $key => $val){
                if(
                    (
                        false !== strpos($key, '__form_control_latest_value')
                        ||
                        false !== strpos($key, '__validator_flash__')
                        ||
                        false !== strpos($key, '__global__')
                    )
                    && false == strpos( $key, 'cachedUser' )
                ){
                    $sess->set($key, null);
                }
            }

            $this->_setLog($params);
            //
            //$cachedUser = $this->previousContainer->get('session')->get("cachedUser");
            $cachedUser = $this->getGlobal("cachedUser");
            return $params['onLoggedOut']($cachedUser);
        }
        return $this->redirectToHome();
    }

    public function reLoginUser($params)
    {
        $params = array_merge([
            'onSuccess' => function ($cachedUser) {},
        ], $params);

        return $this->logout([
            'onLoggedOut' => function($cachedUser) use ($params){
                return $this->loginInstant([
                    'user' => $cachedUser,
                    'onSuccess' => function () use ($params, $cachedUser) {
                        return $params['onSuccess']($cachedUser);
                    },
                ]);
            }
        ]);
    }

    public function log($message = 'Log message', $entity = null)
    {
        //lets guess the entity for the programmer
        if (null === $entity) {
            $entityDir = $this->getBundleService('dir')->dirPath('src/Entity/');
            if (file_exists($guessedEntityPath = $entityDir . 'Log.php')) {
                include_once $guessedEntityPath;
                $guessedEntityName = "App\Entity\Log";
                $entity = new $guessedEntityName();
            }
        }
        return $this->basicCreate([
            'entity' => $entity,
            'sanitizer' => function ($handled) use ($message) {
                $handled->setMessage($message);
                return $handled;
            },
            'onSuccess' => function ($sanitized) {
                return $sanitized;
            },
        ]);
    }

    public function getUserIdent($user)
    {
        return "[{$user->getRoles()}] **{$user->getLastname()} {$user->getFirstname()} ({$user->getMle()})**";
    }

    public function getUserIdentRoleLess($user)
    {
        return "{$user->getLastname()} {$user->getFirstname()} ({$user->getMle()})";
    }

    public function getUserFullName($user=null, $onNull='Inconnu')
    {
        $user = $user ?? $this->getUser() ?? null;
        if($user){
            return $user->getLastname().' '.$user->getFirstname();
        }
        return new Markup("<em style='text-smoked'>{$onNull}</em>", 'UTF-8');
    }

    public function getUserRole($user=null)
    {
        if(is_null($user)){
            $user = $this->getUser();
        }
        return is_null($user) ? null : str_ireplace('role_', '', strtolower($user->getRoles()));
    }

    public function getCurrentUser($user=null)
    {
       return $this->getUser();
    }

    public function findBy($repository, $criteria = [], $orderBy = [], $findOne = null)
    {
        $criteria__ = [];

        foreach ($criteria as $key => $value) {

            if(is_array($value)){
                foreach($value as $val){
                    if ($key === 'roles') {
                        $criteria__[ $key ][] = serialize(['ROLE_' . $val]);
                    }
                }
            }
            else {
                if ($key === 'roles') {
                    $criteria__[ $key ] = serialize(['ROLE_' . $value]);
                }
            }
        }

        $criteria = array_merge($criteria, $criteria__);

        return is_null($findOne) ? $repository->findBy($criteria, $orderBy) : $repository->findOneBy($criteria, $orderBy);
    }

    public function findOneBy($repository, $table, $criteria = [], $orderBy = ['created'=>'DESC'])
    {
        return $this->findBy($repository, $criteria, $orderBy, $findOne = true);
    }
    
    public function findLike($repository, $table, $like, $orderBy = ['created'=>'DESC'], $limit = null)
    {
        $sql = '';
        $queryIndex = '_'.$table[0];

        foreach ($like as $k => $v) {

            // means a LIKE || REGEXP
            if( strpos($k, '[') !== false && strpos($k, ']') !== false ){
                $k = explode('[', trim($k, ']'));
                if(sizeof($k)===2){
                    if(is_array($v)){
                        foreach ($v as $vv) {
                            $vv = is_string($vv) ? "$vv" : $vv;
                            //$val = ($vv == null ? '"'.$k[1].'":' : '"'.$k[1].'":'.(is_string($vv) ? '"'.$vv.'"' : $vv).''); // null means we dont have the exact value
                            $val = ($vv == null ? '"'.$k[1].'":' : '"'.$k[1].'":'.(is_string($vv) ? '"'.$vv : $vv).''); // null means we dont have the exact value
                            $sql .= sprintf("$queryIndex.$k[0] REGEXP '%s' OR ", $val);
                        }
                    }
                    else {
                        $v = is_string($v) ? "$v" : $v;
                        //$val = ($v == null ? '"'.$k[1].'":' : '"'.$k[1].'":'.(is_string($v) ? '"'.$v.'"' : $v).''); // null means we dont have the exact value
                        $val = ($v == null ? '"'.$k[1].'":' : '"'.$k[1].'":'.(is_string($v) ? '"'.$v : $v).''); // null means we dont have the exact value
                        $sql .= sprintf("$queryIndex.$k[0] REGEXP '%s' AND ", $val);
                    }
                }
            }
            
            else if($k=='created'){

                $sql .= " created $v AND ";
            }

            // means an EQUAL
            else {
                if(is_array($v)){
                    $sql .= '(';
                    foreach ($v as $k_ => $v_) {
                        
                        if( $k=='roles' ){
                            $sql .= sprintf("$queryIndex.$k = '%s'", serialize([strtoupper('ROLE_'.$v_)])) . (($k_<sizeof($v)-1)?' OR ':'');
                        }
                        else {
                            $sql .= sprintf("$queryIndex.$k = '%s'", $v_) . (($k_<sizeof($v)-1)?' OR ':'');
                        }
                    }
                    $sql .= ') AND ';
                }
                else {
                    $sql .= sprintf("$queryIndex.$k = '%s' AND ", $k=='roles'?serialize([strtoupper('ROLE_'.$v)]):$v);
                }
            }
        }

        $sql = "SELECT $queryIndex.id FROM $table $queryIndex WHERE " . trim(trim($sql, 'OR '), 'AND ');
        
        $sql = str_ireplace(__IS_EMPTY__, '', $sql);

        return [
            'getSql' => function() use ($sql) { return $sql; },
            'getIds' => function() use ($sql) { return $this->fetchAll($sql); },
            'get' => function($key=null) use ($sql, $limit, $repository, $orderBy, $table){

                $iDs = $this->getBundleService('data')->cacheIDS($sql);

                $rows = $this->getBundleService('data')->cacheFinder(
                    compact('iDs', 'orderBy', 'limit', 'table'),
                    function($p) use ($repository) {
                        $p = (object)$p;
                        return $repository->findBy(['id' => $p->iDs], $p->orderBy, $p->limit);
                    }
                );

                $arr = [
                    'iDs' => $iDs,
                    'rows' => !empty($rows) ? ( $limit === 1 ? $rows[0] : $rows ) : []
                ];

                return is_null($key) || !$this->getBundleService('mix')->arrayKeysExists(['iDs', 'rows'], $arr) ? $arr : $arr[$key];
            }
        ];
    }

    public function findOneLike($repository, $table, $like, $orderBy = ['created'=>'DESC'])
    {
        return $this->findLike($repository, $table, $like, $orderBy, $limit = 1);
    }
    
    public function findNotLike($repository, $table, $notLike, $orderBy = ['created'=>'DESC'], $limit = null)
    {
        $sql = '';
        $queryIndex = '_'.$table[0];
        
        foreach ($notLike as $k => $v) {

            // means a NOTLIKE
            if( strpos($k, '[') !== false && strpos($k, ']') !== false ){
                $k = explode('[', trim($k, ']'));
                if(sizeof($k)===2){
                    $val = ($v == null ? '"'.$k[1].'":' : '"'.$k[1].'":'.(is_string($v) ? '"'.$v.'"' : $v).''); // null means we dont have the exact value
                    $sql .= sprintf("$queryIndex.$k[0] NOT REGEXP '%s' AND ", $val);
                }
            } 
            // means an EQUAL
            else {
                $sql .= sprintf("$queryIndex.$k = '%s' AND ", $k=='roles'?serialize([strtoupper('ROLE_'.$v)]):$v);
            }
        }

        $sql = "SELECT $queryIndex.id FROM $table $queryIndex WHERE " . trim($sql, 'AND ');

        $sql = str_ireplace(__IS_EMPTY__, '', $sql);

        return [
            'getSql' => function() use ($sql) { return $getSqlSearchQuery($params); },
            'getIds' => function() use ($sql) { return $this->fetchAll($sql); },
            'get' => function($key=null) use ($sql, $limit, $repository, $orderBy, $table){

                $iDs = $this->getBundleService('data')->cacheIDS($sql);

                $rows = $this->getBundleService('data')->cacheFinder(
                    compact('iDs', 'orderBy', 'limit', 'table'),
                    function($p) use ($repository) {
                        $p = (object)$p;
                        return $repository->findBy(['id' => $p->iDs], $p->orderBy, $p->limit);
                    }
                );

                $arr = [
                    'iDs' => $iDs,
                    'rows' => !empty($rows) ? ( $limit === 1 ? $rows[0] : $rows ) : []
                ];
                return is_null($key) || !$this->getBundleService('mix')->arrayKeysExists(['iDs', 'rows'], $arr) ? $arr : $arr[$key];
            }
        ];
    }
    
    public function appendSql(string $sql)
    {
        if( !isset($this->appendedSql) ){
            $this->appendedSql = '';
        }
        $this->appendedSql .= $sql;
        return $this;
    }
    
    public function getAppendedSql()
    {
        return $this->appendedSql ?? '';
    }

    public function findOneNotLike($repository, $table, $notLike, $orderBy = [])
    {
        return $this->findNotLike($repository, $table, $notLike, $orderBy, $limit = 1);
    }

    public function search($params)
    {
        $params = array_merge([
            'isXml' => null,
            'repository' => null,
            'table' => null,
            'type' => null,
            'fieldName' => 'q',
            'limit' => null,
            'orderBy' => ['created' => 'DESC'],
            'columns' => null,
            'onSearch' => function($data){},
            'otherwise' => function($params) {}
        ], $params);

        if( is_string($params['isXml']) ){
            if( !$this->isXML() ) {
              return $this->XMLReturn($params);
            }
        }

        $params['fieldName'] = $this->getRequestStackQuery()->get($params['fieldName']) ;

        if(is_string($params['fieldName']) && strlen($params['fieldName']) > 0 && is_array($params['columns']) ){

            $table = $params['table'];
            $repository = $params['repository'];
            $limit = $params['limit'];
            $orderBy = $params['orderBy'];

            $queryIndex = '_'.$table[0];
            $queryColumns = [];
            foreach($params['columns'] as $column){
                $queryColumns[] = $queryIndex . ".$column";
            }
            $sqlSearch = $this->getBundleService('string')->getSqlSearchQuery([
                'keyword' => $params['fieldName'],
                'columnsToSearchIn' => $queryColumns
            ]);
            $type = $params['type'] ? "$queryIndex.type='".$params['type']."' AND" : '';

            $sql = "SELECT $queryIndex.id $queryIndex FROM $table $queryIndex WHERE $type " . $sqlSearch->sql;

            return $params['onSearch']([
                'getSql' => function() use ($sql) { return $sql; },
                'getParameters' => function() use ($sqlSearch) { return $sqlSearch->parameters; },
                'getIds' => function() use ($sql, $sqlSearch) { return $this->fetchAll($sql, $sqlSearch->parameters); },
                'get' => function($key=null) use ($sql, $limit, $repository, $orderBy, $sqlSearch ){
                    $iDs = $this->fetchAll($sql, $sqlSearch->parameters);
                    $rows = $limit ? $repository->findBy(['id' => $iDs], $orderBy, $limit) : $repository->findBy(['id' => $iDs]);
                    $arr = [
                        'iDs' => $iDs,
                        'rows' => !empty($rows) ? ( $limit === 1 ? $rows[0] : $rows ) : []
                    ];
                    return is_null($key) || !$this->getBundleService('mix')->arrayKeysExists(['iDs', 'rows'], $arr) ? $arr : $arr[$key];
                }
            ]);
        }
        else {
            return $params['otherwise']($params);
        }
    }

    public function handleSql($repository, $table, $sql, $parameters=[], $orderBy = ['created'=>'DESC'], $limit = null)
    {
        $queryIndex = '_'.$table[0];

        $sql = str_ireplace(' AND AND ', ' AND ', $sql);
        $sql = str_ireplace(' ANDAND ', ' AND ', $sql);
        $sql = str_ireplace('(AND (', 'AND ((', $sql);
        $sql = str_ireplace(') AND)', '))', $sql);
        $sql = str_ireplace('AND)', ')', $sql);
        $sql = str_ireplace('(AND', '(', $sql);
        $sql = str_ireplace(' AND AND ', ' AND ', $sql);
        $sql = str_ireplace('AND ()', '', $sql);
        
        $sql = str_ireplace('AND (OR', 'AND (', $sql);
        $sql = str_ireplace(' OROR ', ' OR ', $sql);
        $sql = str_ireplace(') OR)', ') )', $sql);

        $sql = "SELECT $queryIndex.id FROM $table $queryIndex WHERE " . trim($sql, 'AND ');

        return [
            'getSql' => function() use ($sql) { return $sql; },
            'getIds' => function() use ($sql, $parameters) { return $this->fetchAll($sql, $parameters); },
            'get' => function($key=null) use ($sql, $limit, $repository, $parameters, $orderBy){
                $iDs = $this->fetchAll($sql, $parameters);
                $rows = $repository->findBy(['id' => $iDs], $orderBy, $limit);
                $arr = [
                    'iDs' => $iDs,
                    'rows' => !empty($rows) ? ( $limit === 1 ? $rows[0] : $rows ) : []
                ];
                return is_null($key) || !$this->getBundleService('mix')->arrayKeysExists(['iDs', 'rows'], $arr) ? $arr : $arr[$key];
            }
        ];
    }

    public function getAdvancedSearch($params)
    {
        $this->advancedSearchSQL = '';
        $this->advancedSearchParameters = [];

        $params = array_merge([
            'queryKey' => 'info', // Ex: info[]
            'repository' => null,
            'table' => null,
            'type' => null,
            'enabled' => true,
            'keys' => []
        ], $params);

        $params['keys'] = array_merge([
            'role' => function($p, $strServ){
                $_ = $strServ->getSqlSearchQuery([
                    'keyword' => serialize(['ROLE_'.strtoupper($p['val'])]),
                    'columnsToSearchIn' => [ 'roles' ]
                ]);
                $p['addSQL']( $_->sql );
                $p['addParameters']( $_->parameters );
            },
            'username' => function($p, $strServ){
                //$xpld = explode(' ', $p['val']);

                if(!empty($p['val'])){
                    $_ = $strServ->getSqlSearchQuery([
                        'keyword' => $p['val'],
                        'columnsToSearchIn' => [ 'firstname', 'lastname' ]
                    ]);
                    $p['addSQL']( $_->sql );
                    $p['addParameters']( $_->parameters );
                }

                /*foreach($xpld as $k => $v){
                    if(!empty($v)){
                        $_ = $strServ->getSqlSearchQuery([
                            'keyword' => $v,
                            'columnsToSearchIn' => [ 'firstname', 'lastname' ]
                        ]);
                        $p['addSQL']( $_->sql );
                        $p['addParameters']( $_->parameters );
                    }
                }*/
            },
            'mle' => function($p, $strServ){
                $_ = $strServ->getSqlSearchQuery([
                    'keyword' => str_ireplace("'", "\\'", $p['val']),
                    'columnsToSearchIn' => [ 'mle' ]
                ]);
                $p['addSQL']( $_->sql );
                $p['addParameters']( $_->parameters );
            },
        ], $params['keys']);

        $strServ = $this->getBundleService('string');
        $stackRequest = $this->getRequestStackRequest();
        $stackQuery = $this->getRequestStackQuery();
        $queryKey = $stackRequest->get($params['queryKey']) ?? $stackQuery->get($params['queryKey']);

        foreach ($params['keys'] as $pKey => $callback) {

            if(
                ($queryKey && is_array($queryKey) && array_key_exists($pKey, $queryKey))
                ||
                ($stackRequest->get($pKey) || $stackQuery->get($pKey))
            ){
                $val = $queryKey[$pKey] ?? $stackRequest->get($pKey) ?? $stackQuery->get($pKey);

                if( !empty($val) ){
                    $p = [
                        'classicAdd' => function($table) use ($params, $strServ, $pKey, $val){
                            $k = ''.$params['queryKey'].'['.$pKey.']';
                            $sql = $strServ->sqlLike( $table, [$k => str_ireplace("'", "\\'", $val)] );
                            $this->advancedSearchSQL .= $this->_chainSql( $sql );
                        },
                        'key' => $pKey,
                        'val' => $val,
                        'infoColumnTo' => '_'.$params['table'][0].'.info',
                        'addSQL' => function($sql){ $this->advancedSearchSQL .= $this->_chainSql( $sql ); },
                        'addParameters' => function($parameter){
                            $this->advancedSearchParameters = array_merge($parameter, $this->advancedSearchParameters);
                        }
                    ];
                    $callback($p, $strServ);
                }
            }
            else {
                /*$_ = $callback( [
                    'key' => $pKey,
                    'addSQL' => function($sql){ $this->advancedSearchSQL .= $this->_chainSql( $sql ); },
                    'addParameters' => function($parameter){
                        $this->advancedSearchParameters = array_merge($parameter, $this->advancedSearchParameters);
                    }
                ], $strServ);*/
            }
        }

        // dd($this->handleSql(
        //     $params['repository'],
        //     $params['table'],
        //     $this->advancedSearchSQL,
        //     $this->advancedSearchParameters
        // ));

        return $this->handleSql(
            $params['repository'],
            $params['table'],
                ' AND ' . ($params['type'] ? '_'.$params['table'][0].'.type="'.$params['type'].'" ' : '') .
                ' AND ' . ($params['enabled'] ? '_'.$params['table'][0].'.enabled="'.$params['enabled'].'" ' : '') .
                ' AND (' . $this->advancedSearchSQL .')'
            ,$this->advancedSearchParameters
        );
    }

    public function getAllRoles($case='option')
    {
        $stringServ = $this->getBundleService('string');

        $options = '';
        $collection = [];
        $rolesBag = [];

        $users = $this->getStorage('___UsersCollection', true);

        foreach($users as $user){
            $role = $this->getUserRole($user);
            if( strpos('_blog_admin', $role) === false ){
                $rolesBag[$stringServ->getSlug($role, '_')] = $role;
            }
        }

        $roles = $this->getBundleRepo('Bloggy')->findBy([ 'type' => 'role' ]);
        if($roles){
            foreach($roles as $r){
                $role = $r->getInfo()->title;
                $rolesBag[$stringServ->getSlug($role, '_')] = $role;
            }

        }

        if( $rolesBag ){
            foreach($rolesBag as $role){

                $val = strtoupper('role_'.$stringServ->getSlug($role, '_'));
                $label = strtoupper(str_ireplace('_', ' ', $role));

                switch($case){
                    case 'option':
                        $options .= '<option value="'.$val.'">'.$label.'</option>';
                        break;
                    case 'collection':
                            $collection[] = (object)[
                                'val' => $val,
                                'label' => $label
                            ];
                        break;
                    default: break;
                }
            }
        }

        return [
            'option' => $options,
            'collection' => $collection
        ][$case];
    }

    public function getRequestStack()
    {
        return $this->previousContainer->get('request_stack');
    }

    public function getRequest()
    {
        return $this->previousContainer->get('request_stack')->getCurrentRequest();
    }

    public function getRequestStackQuery()
    {
        return $this->getRequestStack()->getCurrentRequest()->query;
    }

    public function getRequestStackRequest()
    {
        return $this->getRequestStack()->getCurrentRequest()->request;
    }

    public function getRequestUri()
    {
        return $this->getRequestStack()->getCurrentRequest()->getRequestUri();
    }

    public function getThis()
    {
        return $this;
    }

    public function getContainer()
    {
        return $this->previousContainer;
    }

    public function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    public function getRepository($repositoryName)
    {
        if( in_array($repositoryName, ['PostRepository', 'PostRepo', 'Post']) ) {
            return $this->previousContainer->get('session')->get('PostRepository__WidgetPurpose');
        }
        else if( in_array($repositoryName, ['NavRepository', 'NavRepo', 'Nav']) ) {
            return $this->previousContainer->get('session')->get('NavRepository__WidgetPurpose');
        }
        else if( in_array($repositoryName, ['SectionRepository', 'SectionRepo', 'Section']) ) {
            return $this->previousContainer->get('session')->get('SectionRepository__WidgetPurpose');
        }
        else if( in_array($repositoryName, ['AdminRepository', 'AdminRepo', 'Admin']) ) {
            return $this->previousContainer->get('session')->get('AdminRepository__WidgetPurpose');
        }
        else {
            return null;
        }
    }

    public function getBundleRepo($repositoryName)
    {
        if( in_array($repositoryName, ['BloggyRepository', 'BloggyRepo', 'Bloggy']) ) {
            return $this->getDoctrine()->getRepository(Bloggy::class);
        }
        else if( in_array($repositoryName, ['UserRepository', 'UserRepo', 'User']) ) {
            return $this->getDoctrine()->getRepository(User::class);
        }
        else if( in_array($repositoryName, ['AdminRepository', 'AdminRepo', 'Admin']) ) {
            return $this->getDoctrine()->getRepository(Admin::class);
        }
        else {
            return null;
        }
    }

    function makeEntityJsonifiable($entities)
    {
        if($entities){
            $originalType = gettype($entities);
            if( $originalType == 'object' ){ $entities = [ $entities ]; }
    
            $this->entityColumnValues = [];
            
            $strServ = $this->getBundleService('string');

            if(!is_string($entities)){
                foreach($entities as $entity){
                    $cols = $this->getEntityManager()->getClassMetadata(get_class($entity))->getColumnNames();
                    $values = array();
                    foreach($cols as $col){
                    $getter = 'get'.ucfirst($col);
                    $getter = str_ireplace(
                        ['getOld_password', 'getUsername_slugged', 'getForgot_token'],
                        ['getOldPassword', 'getUsernameSlugged', 'getForgotToken'],
                    $getter);
                    $values[$col] = $entity->$getter();
                    if($col=='id'){
                        $encKey = sha1(uniqid());
                        $unlinkPath = $this->previousContainer->get('router')->generate('_unlinkFile', ['id' => $strServ->encrypt($entity->$getter(), $encKey), 'encKey' => $encKey ]);
                        $values['unlinkPath'] = $unlinkPath;
                    }
                    }
                    $this->entityColumnValues[] = $values;
                }
            }
            return !empty($this->entityColumnValues) ? $this->entityColumnValues : $entities;
        }
        return $entities;
    }
    
    function jsonifyEntity($entities)
    {
        return json_encode($this->makeEntityJsonifiable($entities));
    }

    public function getAuthChecker()
    {
        return $this->authChecker;
    }

    public function getBundleService($serviceName)
    {
        return $this->previousContainer->get("_service.{$serviceName}");
    }

    public function getService($serviceName)
    {
        return $this->previousContainer->get("service.{$serviceName}");
    }

    public function getControllerContainer()
    {
        return $this->previousContainer;
    }

    public function buildEntityAdvancedSearch($entity)
    {
        $inputBag = [];

        if( !is_null($entity) ){

            $className = "App\Entity\\$entity";
            $metadata = $this->em->getClassMetadata($className);
            $formBuilderService = $this->getBundleService('form_builder');

            foreach ($metadata->fieldMappings as $fielName => $field) {

                $fieldPrefix = strtolower(substr($entity, 0, 1));
                $fieldType = $field['type'];
                $fieldTypeToSearch = $fieldPrefix.'.'.$fielName;
                dump($fielName, $fieldType, $fieldTypeToSearch);
            }
            dump($inputBag, $metadata, $metadata->fieldMappings);
        }
    }
    

    public function sendEmail($params)
    {
        $MAILER = explode('|', $this->getBundleService('env')->getAppEnv('MAILER'));

        $username = $MAILER[0];
        $password = $MAILER[1];

        $smtp = explode('::', $MAILER[2]);
        $SMTPSecure = $smtp[0];
        $Host = $smtp[1];
        $Port = $smtp[2];

        $params = array_merge([
            'IsSMTP' => true, // false means IsMail
            'SMTPAuth' =>  true,
            'SMTPSecure' => $SMTPSecure,
            'Host' => $Host,
            'Port' => $Port, //465
            'isHTML' => true,
            //
            'username' => $username,
            'password' => $password,
            //
            'subject' => 'Wonderful Subject',
            'from' => ['john@doe.com' => 'John Doe'],
            'to' => ['receiver@domain.org', 'other@domain.org' => 'A name'],

            'body' => function($mail, $mediaService, $urlService, $params){},
            'onSuccess' => function($params){},
            'onError' => function($e){},
        ], $params);

        // Instantiation and passing `true` enables exceptions
        $mail = new PHPMailer();

        $mail->SMTPDebug = false;

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        if( $params['IsSMTP'] ){
	        $mail->IsSMTP(); // telling the class to use SMTP
        }
	    $mail->SMTPAuth = $params['SMTPAuth']; // enable SMTP authentication
	    $mail->SMTPSecure = $params['SMTPSecure']; // sets the prefix to the servier
	    $mail->Host = $params['Host']; // sets the SMTP server
        $mail->Port = $params['Port']; // set the SMTP port
		$mail->isHTML($params['isHTML']);
        //
	    $mail->Username = $params['username']; // SMTP account username
        $mail->Password = $params['password']; // SMTP account password

		foreach ($params['to'] as $k => $v) { $k === 0 ? $mail->AddAddress($v) : $mail->AddAddress($k, $v); }

		foreach ($params['from'] as $k => $v) { $k === 0  ? $mail->SetFrom($v) : $mail->SetFrom($k, $v); }

		$mail->Subject = $params['subject'];
        $mail->Body = $params['body']( $mail, $this->getBundleService('media'), $this->getBundleService('url'), (object)$params );

        if($mail->Send() && $mail->ErrorInfo == ""){
            return $params['onSuccess']($params, $mail);
        }
        else {
            return $params['onError']($mail->ErrorInfo);
        }

        /*
            Working demo
          return $this->sendEmail([
            'username' => 'silvere.dovoui.stone@gmail.com',
            'password' => 'stonegmailpass',
            'from' => ['silvere.dovoui.stone@gmail.com' => 'silvère stOne'],
            'to' => ['silveredovoui@gmail.com' => 'stOne'],
            'attachments' => function($mail, $mediaService, $urlService){
              $mail->addAttachment( $mediaService->getServerSidePathUploadedFile('logo/logo-unstagepourtous-82x63.png'), 'Logo' );
              return $mail;
            },
            'body' => function( $params, $mail ){
              $data = [
                'logo' => 'http://cdn.babichap.net/swagg/img/swagg-loader.png',
                'welcome' => 'http://cdn.babichap.net/swagg/flaticon/welcome.png',
                'subscriber' => [
                  'firstname' => 'John Doe'
                ],
                'link' => '#'
              ];
              return $this->renderView('eUser/email--validation.html.twig', compact('data'));
            },
            'onSuccess' => function(){
              return $this->view('home');
            },
            'onError' => function(){
              dd('error');
            }
          ]);
          */
    }

    public function outGoingEmailConfirmation($params)
    {

        $params = array_merge([
            'subject' => "Validation de compte",
            'subscriber' => null,
            'inComingEmailConfirmationRouteName' => 'inComingEmailConfirmation',
            'body' => function($mail, $subscriber, $link, $token, $mediaService, $urlService){},
            'onSuccess' => function($subscriber, $reSendLink, $validationLink){},
            'onError' => function($e){}
        ], $params);

        $params['isGranted'] = [
            "byUserRoles" => ['__NONE__']
        ];

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        return $this->basicEdit([
            'finder' => function () use ($params) {
                return $params['subscriber'];
            },
            'sanitizer' => function ($handled) {
                $validationToken = sha1(uniqid());
                $handled->setValidationToken($validationToken);
                return $handled;
            },
            'onSuccess' => function ($subscriber) use ($params) {

                $envS = $this->getBundleService('env');

                $token = $subscriber->getValidationToken();
                $subscriberEmail = $subscriber->getEmail();
                $validationLink = trim($envS->getAppEnv('APP_HOST'), '/') . $this->generateUrl($params['inComingEmailConfirmationRouteName'], ['token' => $token]);
                $reSendLink = $this->generateUrl($this->request->attributes->get('_route'), ['email' => $subscriberEmail]);


                $MAILER = explode('|', $envS->getAppEnv('MAILER'));
                $username = $MAILER[0];

                return $this->sendEmail([
                    'subject' => $params['subject'],
                    'from' => [$username => $envS->getAppEnv('APP_NAME')],
                    'to' => [$subscriberEmail => $this->getUserFullName($subscriber)],
                    'body' => function( $mail, $mediaService, $urlService ) use ($params, $subscriber, $validationLink, $token) {
                        return $params['body']( $mail, $subscriber, $validationLink, $mediaService, $urlService, $token );
                    },
                    'onSuccess' => function() use ($params, $subscriber, $reSendLink, $validationLink, $token) {
                        return $params['onSuccess']($subscriber, $reSendLink, $validationLink, $token);
                    },
                    'onError' => function($e){
                        return $params['onError']($e);
                    }
                ]);
            }
        ]);
    }

    public function forgot($params)
    {
        $params = array_merge([
            'meta' => null,
            'validator' => null,
            'finder' => function ($posted){},
            'formView' => function($emailNotFound = null){}, // will be TRUE if user was not found
            'onFound' => function ($user) {},
            'log' => null
        ], $params);

        //forcing
        $params['isGranted'] = [
            "byUserRoles" => ['__NONE__']
        ];

        if(false === $this->__isGranted($params)){
            if(is_callable($otherwise = $this->_otherwise($params))){
                //devloper otherwise
                return $otherwise();
            }
            else {
                //default otherwise
                return $this->_defaultFallback('otherwise');
            }
        }

        $request = $this->request;

        $submittedToken = $request->request->get('_token');

        //setting meta
        $this->_setMetaData($params);

        if (is_string($submittedToken) && $this->isCsrfTokenValid('forgot_token', $submittedToken)) {

            $userLatestPostedData = $this->_setFormControlsLatestValue($request);

            $user = $params['finder']($userLatestPostedData);

            if (!$user) {
                return $params['formView']($emailNotFound = true);
            }

            $this->_setLog($params, $user);

            return $params['onFound']($user);
        }
        return $params['formView']($emailNotFound = null);
    }

    /*
        Retrieve user account
        Send Account Retrieving Token Mail
    */
    public function outGoingEmailRetrieveAccount($params)
    {
        //Required to create <input type="hidden" name="forgot_token" />
        //into your form
        $params = array_merge([
            'subject' => "Récupération de compte",
            'from' => ['me@domain.com'],
            'user' => null,
            'inComingEmailRetrieveAccountRouteName' => 'inComingEmailRetrieveAccount',
            'body' => function($mail, $user, $link, $mediaService, $urlService){},
            'onError' => function($params){},
            'onSuccess' => function($user, $reSendLink, $token){},
        ], $params);

        //forcing
        $params['isGranted'] = [
            "byUserRoles" => ['__NONE__']
        ];

        return $this->basicEdit([
            'finder' => function () use ($params) {
                return $params['user'];
            },
            'sanitizer' => function ($handled) {
                $forgotToken = sha1(uniqid());
                $handled->setForgotToken($forgotToken);
                return $handled;
            },
            'onSuccess' => function ($user) use ($params) {
                $token = $user->getForgotToken();
                $userEmail = $user->getEmail();
                $retrieveLink = trim($this->getBundleService('env')->getAppEnv('APP_HOST'), '/') . $this->generateUrl($params['inComingEmailRetrieveAccountRouteName'], ['token' => $token]);
                $reSendLink = $this->generateUrl($this->request->attributes->get('_route'), ['id' => $user->getId()]);

                $envS = $this->getBundleService('env');
                $MAILER = explode('|', $envS->getAppEnv('MAILER'));
                $username = $MAILER[0];

                return $this->sendEmail([
                    'subject' => $params['subject'],
                    'from' => [$username => $envS->getAppEnv('APP_NAME')],
                    'to' => [$userEmail => $this->getUserFullName($user)],
                    'body' => function($mail, $mediaService, $urlService) use ($params, $user, $retrieveLink) {
                        return $params['body']( $mail, $user, $retrieveLink, $mediaService, $urlService );
                    },
                    'onSuccess' => function() use ($params, $user, $reSendLink, $token, $retrieveLink) {
                        return $params['onSuccess']($user, $reSendLink, $token, $retrieveLink);
                    },
                    'onError' => function($e){
                        return $params['onError']($e);
                    }
                ]);
            }
        ]);
    }

    /*public function setBags($bigData)
    {
        if( $bigData ){
            foreach($bigData as $fileName => $data){

                if( isset($data['content']) ){

                    $file = $this->previousContainer->get('kernel')->getProjectDir() . "/templates/storage/$fileName.txt";

                    //creating file if not exists yet
                    if( !file_exists($file)){
                        file_put_contents($file, '');
                    }

                    $content = $data['content'];

                    //only put content if file is empty
                    if( empty(file_get_contents($file)) ){
                        //exec content if it is callable
                        if( is_callable($content) ){
                            $content = $content();
                        }
                        file_put_contents($file, serialize($content));

                        if( isset($data['onOnce']) && is_callable($data['onOnce']) ){
                            $data['onOnce']( $this->getStorage($fileName) );
                        }

                    }
                    if( isset($data['onAlways']) && is_callable($data['onAlways']) ){
                        $data['onAlways']( $this->getStorage($fileName) );
                    }
                }
            }
        }
        return true;
    }*/

    public function getCurl($url, $isRoute = true)
    {
        if( true === $isRoute ){
            $url = $this->getBundleService('env')->getAppEnv('APP_HOST') . $this->generateUrl( $url);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    public function getPropValue($object, $keyPath, $noFoundMessage = '<em>Aucune donnée</em>')
    {
        $keys = explode('.', $keyPath);
        if( $keys ){
            foreach ($keys as $key) {
                if( is_object($key) || is_array($key) ){
                    //array_key_exists()
                }
            }
        }
        dd($keys, $object);
    }

    public function setStorage($bigData, $bundleStorage = null, $sessionIdRelated = null): void
    {
        $appStoragePath = $this->previousContainer->get('kernel')->getProjectDir() . '/var/storage';
        if( !file_exists($appStoragePath) ) { mkdir($appStoragePath, 0777, true); }

        if( $bigData ){
            foreach($bigData as $fileName => $data){

                if( $sessionIdRelated === true ){
                    $this->unsetSessionStorage($fileName, $bundleStorage); // provisoire
                    $fileName = session_id().'_'.$fileName;
                }

                if( isset($data['content']) ){

                    $file =
                    is_null($bundleStorage)
                    ? $this->previousContainer->get('kernel')->getProjectDir() . "/var/storage/$fileName.txt"
                    : $this->getBundleService('view')->getViewsDir("storage/$fileName.txt")
                    ;

                    //creating file if not exists yet
                    if( !file_exists($file)){
                        file_put_contents($file, '');
                    }

                    $content = $data['content'];

                    //only put content if file is empty
                    if( file_exists($file) && empty(file_get_contents($file)) ){
                        //exec content if it is callable
                        if( is_callable($content) ){
                            $content = $content();
                        }

                        file_put_contents($file, serialize($content));

                        if( isset($data['onOnce']) && is_callable($data['onOnce']) ){
                            $data['onOnce']( $this->getStorage($fileName) );
                        }

                    }
                    if( isset($data['onAlways']) && is_callable($data['onAlways']) ){
                        $data['onAlways']( $this->getStorage($fileName), $data );
                    }
                }
            }
        }
    }

    public function setSessionStorage($bigData, $bundleStorage = null)
    {
        return $this->setStorage($bigData, $bundleStorage, $sessionIdRelated = true);
    }

    public function getStorage($fileName, $bundleStorage = null, $sessionIdRelated = null)
    {
        if( $sessionIdRelated === true ){
            $fileName = session_id().'_'.$fileName;
        }

        $file = $fileName.'File';
        $content = $fileName.'Content';

        $file =
        is_null($bundleStorage)
        ? $this->previousContainer->get('kernel')->getProjectDir() . "/var/storage/$fileName.txt"
        : $this->getBundleService('view')->getViewsDir("storage/$fileName.txt")
        ;

        if( file_exists($file) ){
            return unserialize(file_get_contents($file));
        }
        return null;
    }

    public function getSessionStorage($fileName, $bundleStorage = null)
    {
        return $this->getStorage($fileName, $bundleStorage, $sessionIdRelated = true);
    }

    public function unsetStorage($filesNames, $bundleStorage = null, $sessionIdRelated = null)
    {
        if( is_string($filesNames) ){
            $filesNames = [$filesNames];
        }
        foreach($filesNames as $fileName){
            
            if( $sessionIdRelated === true ){
                $fileName = session_id().'_'.$fileName;
            }

            $file = $fileName.'File';

            $file =
            is_null($bundleStorage)
            ? $this->previousContainer->get('kernel')->getProjectDir() . "/templates/storage/$fileName.txt"
            : $this->getBundleService('view')->getViewsDir("storage/$fileName.txt")
            ;

            if( file_exists($file) ){
                unlink($file);
            }
        }
        return null;
    }

    public function unsetSessionStorage($filesNames, $bundleStorage)
    {
        return $this->unsetStorage($filesNames, $bundleStorage, $sessionIdRelated = true);
    }

    public function setGlobal($bigData)
    {
        if( $bigData ){
            foreach($bigData as $globalName => $data){
                if( is_callable($data) ){
                    $data = $data();
                }
                $this->previousContainer->get('session')->set('__global__' . $this->appUiD . '__' . $globalName, serialize($data));
            }
        }
    }

    public function getGlobal($globalName = null)
    {
        if( is_null($globalName) ){
            return $this->previousContainer->get('session');
        }
        $globalValue = $this->previousContainer->get('session')->get('__global__' . $this->appUiD . '__' . $globalName);
        if( !is_null($globalValue) ){
            return unserialize($globalValue);
        }
        return null;
    }

    public function unsetGlobal($globalName)
    {
        if( is_array($globalName) ){
            foreach($globalName as $gbName){
                $this->previousContainer->get('session')->set('__global__' . $this->appUiD . '__' . $gbName, null);
            }
        }
        else {
            $this->previousContainer->get('session')->set('__global__' . $this->appUiD . '__' . $globalName, null);
        }
        return true;
    }

    public function getBag($collectionName)
    {
        $__setBags = $this->__setBags();

        if( $__setBags ){

            foreach($__setBags as $__collectionName => $content){

                if( $collectionName === $__collectionName ){
                    return is_callable($content) ? $content() : $content;
                }
            }
        }
        return null;
    }

    public function getAttr($obj, $attrs, $onNull=null, $isEmptibale=true)
    {   
        $obj = (object)$obj;
        $attrFetchedVal = null;
        $attrs = explode('.', $attrs);
        $size = sizeof($attrs);
        for ($i=0; $i < $size; $i++) {
            $val = $this->_getAttrLoop($obj, $attrs, $i, $onNull);
            if( $isEmptibale==false && (is_null($val) || empty($val)) ){
                return $onNull;
            }
            return $val;
        }
        return $onNull ?? null;
    }

    public function _isGranted($params)
    {
        $r = ['ok' => true, 'fail' => false];

        $params['isGranted'] = $params;
        $isGranted = $this->__isGranted($params);
        if( false === $isGranted ){
            $r = ['ok' => false, 'fail' => true];
        }

        return (object) $r;
    }

    public function _getUser()
    {
        return $this->getUser();
    }

    public function redirectToReferer()
    {
        $referer = $this->request->headers->get('referer');
        return $this->redirect(is_null($referer) ? $this->getBundleService('url')->getUrl('/') : $referer);
    }

    public function redirectToHome()
    {
        return $this->redirect(
            $this->getBundleService('url')->getHome()
        );
    }

    public function getReferer()
    {
        return $this->request->headers->get('referer');
    }

    public function _redirect($href = '/')
    {
        return $this->redirect($this->getBundleService('url')->getUrl($href));
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    public function getKnpPaginate( $items, $limit = 15, $page = 1 )
    {
        return $this->previousContainer->get('knp_paginator')->paginate(
            $items,
            (int) $this->previousContainer->get('request_stack')->getCurrentRequest()->query->get('page', $page),
            $limit
        );
    }

    public function registerShortCode($name, $shortCodeString, $params=null)
    {
        $shortCodeKey = '__shortCode__'.$name;

        $this->$shortCodeKey = $params;
        $this->shortCodeString = $shortCodeString;

        if ($this->filesystem->exists($this->previousContainer->get('kernel')->getProjectDir() . '/src/Service/ExecuteBeforeService.php')) {
            if (method_exists(\App\Service\ExecuteBeforeService::class, '__registerShortsCodes') && $this->previousContainer->has('service.execute_before')) {
                $this->previousContainer->get('service.execute_before')->__registerShortsCodes();
            }
        }
    }

    public function renderShortcode($name, callable $callback)
    {   
        $shortCodeKey = '__shortCode__'.$name;
        if( isset($this->$shortCodeKey) && is_string($rendered=$callback($this->$shortCodeKey)) ){
            $this->$shortCodeKey = $rendered;
        }
    }

    public function getRenderedShortcode($name)
    {
        $shortCodeKey = '__shortCode__'.$name;
        return is_string($this->$shortCodeKey) ? $this->$shortCodeKey : '<div data-dynamic-widget="shortcode">'.$this->shortCodeString.'</div>';
    }


    ##################################################
    public function setMetaData($metaShortcut)
    {
        $this->_metaDataShortcutLoop($metaShortcut);
    }

    public function getCollection($collectionName)
    {
        ${$collectionName.'File'} = $this->getBundleService('view')->getViewsDir("storage/{$collectionName}Collection.json");
        return unserialize(file_get_contents(${$collectionName.'File'}));
    }


    # BEGIN PRIVATE #######################
    private function _setMetaData($params, $item = null)
    {
        if (isset($params['meta'])) {
            $this->_metaDataShortcutLoop($params['meta']($item));
        }
    }

    private function _metaDataShortcutLoop($metaShortcut)
    {
        if (is_array($metaShortcut)) {
            foreach (['title', 'description', 'keywords', 'author'] as $metaName) {
                if (isset($metaShortcut[$metaName])) {
                    $this->setGlobal([
                        "meta" . ucfirst($metaName) => $metaShortcut[$metaName]
                    ]);
                } else {
                    $this->unsetGlobal("meta" . ucfirst($metaName));
                }
            }
        } elseif (is_string($metaShortcut)) {
            $this->setGlobal([
                "metaTitle" => $metaShortcut
            ]);
        }
    }

    private function _getPostedData($params)
    {
        $postedData = null;
        foreach ($this->request->request as $v) {
            if( is_object($this->request->request) ){
                $postedData = (object) [];
                foreach($this->request->request as $formKey => $data){
                    /*if( is_array($data) ){
                        foreach($data as $key => $v){
                            $postedData->$key = $v;
                        }
                    }*/
                    $postedData->$formKey = (object) $data;
                }
            }
            else {
                $postedData = (object) $v;
            }
        }
        return $postedData;
    }

    private function _persistThenFlushSanitizedBag($sanitizedBag)
    {
        if (is_array($sanitizedBag)) {
            foreach ($sanitizedBag as $sanitized) {
                $this->em->persist($sanitized);
                $this->em->flush();
            }
        } else {
            $this->em->persist($sanitizedBag);
            $this->em->flush();
        }
    }

    public function __isGranted($params)
    {
        $byUserId = false;
        $byUserRoles = false;

        if( is_array($params['isGranted']) && isset($params['isGranted']['byUserId']) && is_array($params['isGranted']['byUserId']) ){
            $byUserIdIsRequired = true;
            $byUserId = $this->_isGrantedById($params['isGranted']['byUserId']);
        }
        if( is_array($params['isGranted']) && isset($params['isGranted']['byUserRoles']) && is_array($params['isGranted']['byUserRoles']) ){
            $byUserRolesIsRequired = true;
            $byUserRoles = $this->_isGrantedByRoles($params['isGranted']['byUserRoles']);
        }

        /*dd(
            $params['isGranted'],
            [$byUserId,
            isset($byUserIdIsRequired)],
            [$byUserRoles,
            isset($byUserRolesIsRequired)],
            [$byUserId,
            $byUserRoles]
        );*/

        if(
            // no restriction
            (is_null($params['isGranted']))
            ||
            // restriction by id
            (isset($byUserIdIsRequired) && $byUserId)
            ||
            // restriction by role
            (isset($byUserRolesIsRequired) && $byUserRoles)
        ){
            return true;
        }

        /*
        if( isset($byUserIdIsRequired) && isset($byUserRolesIsRequired) && (!$byUserId || !$byUserRoles) ){
            return false;
        }
        if( $byUserId || $byUserRoles || is_null($params['isGranted']) ) {
            return true; 
        }*/

        return false;

        /*$byUserRoles = null;
        $byUserId = null;

        if( is_array($params['isGranted']) && isset($params['isGranted']['byUserRoles']) && is_array($params['isGranted']['byUserRoles']) ){
            $byUserRoles = $this->_isGrantedByRoles($params['isGranted']['byUserRoles']);
        }
        if( is_array($params['isGranted']) && isset($params['isGranted']['byUserId']) && is_array($params['isGranted']['byUserId']) ){
            $byUserId = $this->_isGrantedById($params['isGranted']['byUserId']);
        }

        if( $byUserRoles || $byUserId ) {
            return true;
        }

        return false;*/
    }

    public function convertToKnpPaginatorBundle($items=[], $perPage)
    {
        return $this->previousContainer->get('knp_paginator')->paginate(
            $items,
            (int) $this->previousContainer->get('request_stack')->getCurrentRequest()->query->get('page', 1),
            $perPage
        );
    }
    
    public function setBackOfficeNav($data)
    {
        $nav = '';
        if($data){
            foreach ($data as  $d) {
                if( sizeof($d) === 3 ){
                    $nav .= '<a title="'.$d[0].'" data-active="'.$d[2].'" data-href="'.$d[2].'" data-js="BackOffice={click:getPage}">
                                <i class="fa fa-'.$d[1].'"></i>
                                <span>'.$d[0].'</span>
                            </a>';
                }
            }
        }
        $this->setGlobal([ '___backOfficeNav' => new Markup($nav, 'UTF-8') ]);
    }
    
    public function addAdminRole(array $roles = null)
    {   
        if(!empty($roles)){
            foreach ($roles as $r) { $this->adminRoles[] = strtoupper($r); }
        }
        $this->setGlobal([ 'adminRoles' => $this->adminRoles ]);
    }
    
    public function removeAdminRole(string $role)
    {   
        $roles = $this->getAdminRoles();
        if( isset($roles[$role]) ){
            unset($roles[$role]);
        }
        $this->setGlobal([ 'adminRoles' => $roles ]);
    }
    
    public function getAdminRoles()
    {
        //return $this->adminRoles;
        return $this->getGlobal('adminRoles') ?? $this->adminRoles;
    }

    public function unsetBackOfficeNav()
    {
        $this->unsetGlobal('___backOfficeNav');
    }

    public function getUserData($user=null)
    {
        $user = $user ?? $this->getUser();

        if( $user ){
            return [
                'role' => $user->getRoles(),
                'username' => $user->getUsername(),
                'mle' => $user->getMle(),
                'lastname' => $user->getLastname(),
                'firstname' => $user->getFirstName(),
                'contact' => $user->getContact(),
                'email' => $user->getEmail(),
                'birthdate' => $user->getBirthdate(),
                'location' => $user->getLocation(),
                'location' => $user->getLocation(),
                'validated' => $user->getValidated(),
                'enabled' => $user->getEnabled(),
                'created' => $user->getCreated(),
                'updated' => $user->getUpdated(),
                'usernameSlugged' => $user->getUsernameSlugged(),
                'info' => $user->getInfo(),
            ];
        }
        return null;
    }

    public function isXML()
    {
        return $this->request->isXmlHttpRequest();
    }

    public function mergeData(...$data)
    {
        $merged = [];
        $data = json_decode(json_encode($data), true);
        $mixServ = $this->getBundleService('mix');
        foreach($data as $d){
            $merged = $mixServ->arrayMergeRecursiveEx($merged, $d);
        }
        return $merged;
    }

    private function _isGrantedByRoles($byUserRoles)
    {
        $ROLES = [];
        foreach ($byUserRoles as $role) {
            if( $role === '__ANY__' ) {
                return $this->getUser() !== null;
            }
            else if( $role === '__NONE__' ||  $role === '__ANON__' ||  $role === '__ANONYMOUS__' ) {
                return $this->getUser() === null;
            }
            else {
                $ROLES[] = 'ROLE_' . trim(strtoupper($role));
            }
        }
        return $this->_loopOverRoles($ROLES);
    }

    private function _isGrantedById($byUserId)
    {
        foreach ($byUserId as $context) {
            if( !$this->_isGrantedByIdLoop($context) ){
                return false;
            }
        }
        return true;
    }

    private function _isGrantedByIdLoop($context)
    {
        if( sizeof($context) === 1 ){
            $context[1] = $this->getUser() ? $this->getUser()->getId() : null;
        }
        if( sizeof($context) === 2 ){
            $ROLES = [];
            $r = explode(',', $context[0]);
            $id = $context[1];
            $userAndIdCheck = $this->getUser() && $this->getUser()->getId() == $id;
            foreach ($r as $role) {
                if( $role === '__ANY__' ) {
                    return $userAndIdCheck;
                }
                else {
                    $ROLES[] = 'ROLE_' . trim(strtoupper($role));
                }
            }
            return $this->_loopOverRoles($ROLES) && $userAndIdCheck;
        }
        return false;
    }

    private function _loopOverRoles(array $roles)
    {
        $isGranted = false;

        if (!($this->getUser() instanceof UserInterface)) {
            return false;
        }

        foreach ($roles as $role) {
            if ($this->authChecker->isGranted($role)) {
                $isGranted = true;
                break;
            }
        }

        return $isGranted;
    }

    private function _otherwise($params)
    {
        if( is_array($params['isGranted']) && isset($params['isGranted']['otherwise']) && is_callable($params['isGranted']['otherwise']) ){
            $this->addFlash('error', 'Accès Interdit');
            return $params['isGranted']['otherwise'];
        }
        return false;
    }

    private function _guessEntity($params)
    {
        //lets guess the entity for the programmer
        if (null === $params['entity']) {
            $guessedEntity = str_ireplace(["App\Form\\", 'Type'], ['', ''], $params['type']);
            $entityDir = $this->getBundleService('dir')->dirPath('src/Entity/');
            if (file_exists($guessedEntityPath = $entityDir . $guessedEntity . '.php')) {
                include_once $guessedEntityPath;
                $guessedEntityName = "App\Entity\\" . $guessedEntity;
                $params['entity'] = new $guessedEntityName();
            }
        }
        return $params['entity'];
    }

    private function _getCreatedForm($params)
    {
        return $this->createForm($params['type'], $params['item'], ['action' => $params['action']]);
    }

    private function _setFormControlsLatestValue($request)
    {
        $userLatestPostedData = [];
        $userRequested = (object) $request->request;
        foreach ($userRequested as $k => $v) {
            //dont understand but when form sent has attachments
            //value is an array
            //so lets browse it
            $userLatestPostedData = $this->_formControlsLatestValueLoop($userLatestPostedData, $k, $v);
        }
        return (object) $userLatestPostedData;
    }

    private function _formControlsLatestValueLoop($userLatestPostedData, $key, $val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                //here we go again
                //$this->_formControlsLatestValueLoop($userLatestPostedData, $k, $v);
                $userLatestPostedData[$key] = $val;
            }
        } else {
            //clean flash
            $this->previousContainer->get('session')->get("__form_control_latest_value__$key");
            //pop flash
            $userLatestPostedData[$key] = $val;
            $this->previousContainer->get('session')->set("__form_control_latest_value__$key", $val);
        }
        return $userLatestPostedData;
    }

    private function _getValidatorFlash($p, $cB, $validatorRedirection = null)
    {
        $validationFailed = false;

        if (isset($p['validator']) && is_array($p['validator'])) {

            foreach ($p['validator'] as $cP => $cC) {

                $r = $cC(
                    $this->_getPostedData($p), //posted
                    $this->_getCachedItemFound(), //found
                    $this->_getRepo($p)
                );

                if (is_string($r)) {

                    //clean flash
                    $this->previousContainer->get('session')->get("__validator_flash__$cP");
                    $this->previousContainer->get('session')->get("__validator_flash__{$cP}_alias");
                    //pop flash
                    $validationFailed = true;
                    $this->previousContainer->get('session')->set("__validator_flash__$cP", $r);
                    $this->previousContainer->get('session')->set("__validator_flash__{$cP}_alias", $r);
                    //$this->addFlash("__validator_flash_$cP", $r);
                    //$this->addFlash("__validator_flash_{$cP}_alias", $r);
                }
            }

            //deleting file
            $file = $this->_itemFoundFile();
            if(file_exists($file)){
                unlink($file);
            }
        }

        if ($validationFailed === true) {
            //prevent validator redirection if $this->create()
            //if (false === $validatorRedirection) {
            if(isset($p['onInvalid'])){
                return $p['onInvalid']();
            }
            elseif (isset($p['form'])) {
                return $p['formView']($p['form']->createView(), $p['item'], $p['form'], $p);
            }

            if(isset($p['form'])){
                return $p['formView']($p['form']->createView(), $p['item'], $p['form'], $p);
            }
            elseif (isset($p['onInvalid'])) {
                return $p['onInvalid']();
            }
            //}
            //return $this->redirectToReferer();
            //return $this->redirect($p['action']);
        }

        return $cB($p);
    }

    private function _getSanitizedData($p)
    {
        if (isset($p['sanitizer']) && is_callable($p['sanitizer'])) {
            $sanitized = $p['sanitizer'](
                $this->_getPostedData($p), //posted
                isset($p['item']) ? $p['item'] : null, //handled
                $this->_getRepo($p),
                $p
            );
        } else {
            $sanitized = $p['item'];
        }
        return $sanitized;
    }

    private function _chainSql($sql, $chain='OR')
    {
       return $chain.' '. $sql.' ' .$chain;
    }

    private function _cacheItemFound($item)
    {
        return serialize(file_put_contents($this->_itemFoundFile(), $item));
    }

    private function _getCachedItemFound()
    {
        $item = file_get_contents($this->_itemFoundFile());
        return unserialize($item);
    }

    private function _getRepo($p)
    {
        return isset($p['repository']) ? $p['repository'] : null; // repo only set for edition
    }

    private function _itemFoundFile()
    {
        $ip = $this->getBundleService('mix')->getIp();
        $ipSluggified = $this->getBundleService('string')->getSlug($ip);
        return $this->previousContainer->get('kernel')->getProjectDir() . '/var/cache/' . $ipSluggified . '-item-found.txt';
    }

    private function _setLog($params, $found = null)
    {
        if (!is_null($params['log']) && is_callable($params['log'])) {
            //$user = $this->getUser() ? $this->getUser() : $this->previousContainer->get('session')->get("cachedUser");
            $user = $this->getUser() ? $this->getUser() : $this->getGlobal("cachedUser");
            $author = !is_null($user) ? $this->getUserIdent($user) : null;
            $logMessage = $params['log']($author, $found);
            // $logMessage could be false
            // if I (as the programmer) need to login incognito :)
            if( $logMessage !== false ){
                $this->log($logMessage);
            }
        }
        return true;
    }

    private function _defaultFallback($key)
    {
        $__defaultFallbacks = $this->__defaultFallbacks();

        if( isset($__defaultFallbacks[$key]) && is_callable($__defaultFallbacks[$key]) ){
            return $__defaultFallbacks[$key]();
        }
        else {
            //force redirection
            return $this->redirectToReferer();
        }
    }
    # BEGIN PRIVATE #######################

    ##################################################
    private function __defaultFallbacks()
    {
        if( $this->previousContainer->has('service.execute_before') && method_exists($defaults = $this->previousContainer->get('service.execute_before'), '__defaultFallbacks') ){
            return $defaults->__defaultFallbacks();
        }
    }

    private function __setBags()
    {
        if( $this->previousContainer->has('service.execute_before') && method_exists($collections = $this->previousContainer->get('service.execute_before'), '__setBags') ){
            return $collections->__setBags();
        }
    }

    private function getFilterCriteriasForm($filterCriterias, $params)
    {   
        $form = '

        <style>

            input {
                -webkit-appearance: searchfield!important;
            }

            *::-webkit-search-cancel-button {
                -webkit-appearance: searchfield-cancel-button!important;
            }

            form.filter-criterias-form:not(.is-open) .collapsable-icon::before { font-family: FontAwesome; content: "\f106"; }
            form.filter-criterias-form:not(.is-open) .labels-wrapper { display: none }

            form.filter-criterias-form label[data-js] { cursor: pointer; }
            form.filter-criterias-form .collapsable-icon { float: right;
                border: 1px solid #ccc;
                width: 20px;
                height: 20px;
                line-height: 20px;
                text-align: center;
                font-size: 15px;
                border-radius: 24px;
                background-color: #fff; }

            form.filter-criterias-form { margin-bottom: 30px; display: table; width: 100% }

            form.filter-criterias-form ._label { padding:5px; background-color: #fafafa; }

            form.filter-criterias-form .select2-container {width:100%!important;margin-bottom:0!important}
            form.filter-criterias-form .select2-container .select2-selection__rendered{line-height:30px}
            form.filter-criterias-form .select2-container .select2-selection__arrow b{margin-top:0}
            form.filter-criterias-form .select2-container .select2-selection--single{transition:border-color ease-in-out .15s,box-shadow ease-in-out .15s;border:1px solid #ccc!important;box-shadow:inset 0 1px 1px rgba(0,0,0,.075);height:30px!important}
            form.filter-criterias-form .select2-container.select2-container--open .select2-selection--single{border-color:#66afe9;box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6)}

            form.filter-criterias-form label {
                border: 1px solid rgba(221, 221, 221, 0.34);
                padding: 5px 10px 10px 5px;
                margin-bottom: -1px!important;
            }
            form.filter-criterias-form label span { font-size: 12px; }

        </style>

        <script>
        $(function(){
            "use strict";
            __.dataJs({
                filterCriteriasForm: {
                    toggleForm: function($label){
                        var $form = $label.parents("form"),
                            $labelsWrapper = $form.find(".labels-wrapper");
                            $form.toggleClass("is-open");
                            $form.hasClass("is-open");
                            if($form.hasClass("is-open")){
                                window.filterCriteriasForm_filter = "isVisible";
                            }
                            else {
                                window.filterCriteriasForm_filter = "isHidden";
                            }
                    },
                    resetForm: function($button){
                        $button.parents("form")[0].reset();
                    }
                }
            });
            if( window.filterCriteriasForm_filter == "isHidden" ){
                $(\'[data-js="filterCriteriasForm={click:toggleForm}"]\').trigger("click");
            }
            __.select2();
        });
        </script>

        <div class="container-fluid">
            <form data-mymacosx-handleform class="filter-criterias-form is-open" action="'. $this->generateUrl($this->request->attributes->get('_route'), $this->request->get('_route_params')) .'" method="get">
                <input type="hidden" name="_filter" value="true" />
                <label data-js="filterCriteriasForm={click:toggleForm}" class="_label col-xs-12 col-md-12">Filtre <i class="fa fa-angle-down collapsable-icon"></i></label>
                <div class="labels-wrapper">
        ';

        // default
        $filterCriterias = array_merge([
            'per_page' => [
                'type' => 'StaticSelect',
                'col' => 2,
                'label' => 'Nombre de lignes',
                'collection' => [ 10 => 10, 25 => 25, 50 => 50 ],
                'resetable' => false
            ],
            'enabled' => [
                'type' => 'StaticSelect',
                'col' => 2,
                'label' => 'Statut',
                'collection' => [ 1 => 'En ligne', 0 => 'Hors-ligne' ]
            ],
        ], is_array($filterCriterias) ? $filterCriterias : [] );

        foreach ($filterCriterias as $propName => $propParams) {
            $form .= $this->getFilterCriteriaFormControl($propName, $propParams);
        }

        $script = '';
            $xploded = explode('?', urldecode($this->getRequestUri()));
            if( $xploded && sizeof($xploded) === 2 ){
                $script = '
                    var request_uri = "' . '?' . $xploded[1] . '",
                        regex = /[?&]([^=#]+)=([^&#]*)/g,
                        params = {},
                        match;
                        while(match = regex.exec(request_uri)) {  params[match[1]] = match[2]; }
                        if( _.size(params)>0 ){
                            $.each(params, function(queryName, queryValue){
                                if( queryValue !== "" ){
                                    var $el = $(\'[name="\' +queryName+ \'"]\');
                                    if( $el.is("select") ){
                                        $el.find(\'option[value="\' +queryValue+ \'"]\').attr("selected", "selected");
                                    } 
                                    else if ($el.is("input") ) {
                                        $el.val(queryValue);
                                    }
                                    else if ( $el.is("textarea") ){
                                        $el.html(queryValue);
                                    }
                                }
                            });
                        }
                ';
            }

        $form .= '<label class="_label col-xs-12 col-md-12"><button type="submit" style="margin:0">Filtrer</button></label>
                </div> <!-- end of .labels-wrapper -->
            </form>
        </div><script>$(function(){'.$script.';});</script>';

        return new Markup($form, 'UTF-8');
    }

    private function getFilterCriteriaFormControl($propName, $propParams)
    {
        $query = $this->getRequestStackQuery();

        // mind keys existation control
        $control = '';

        switch ($propParams['type']) {

            case 'text':
            case 'search':
            case 'email':
                    $control = '<label class="col-xs-12 col-md-'. ($propParams['col'] ?? 2) .'">';
                        $control .= '<span>'. $propParams['label'] .'</span>';
                        $control .= '<input type="'. $propParams['type'] .'" value="'. $query->get($propName, '') .'" name="'. $propName .'" />';
                    $control .= '</label>';
                break;

            case 'Select':

                    $propParams = [
                        'collection' => array_merge([
                            'bundle' => false,
                            'name' => '',
                            'option' => function($item, $i){ return [ 'value' => $item->getId(), 'text' => $item->getInfo()->name ]; },
                            'select2' => true,
                        ], $propParams['collection']),
                        'col' => $propParams['col'] ?? 2,
                        'label' => $propParams['label'] ?? 'Sélectionnez un élément',
                        'resetable' => $propParams['resetable'] ?? 'Tout'
                    ];

                    $control = '<label class="col-xs-12 col-md-'. $propParams['col'] .'">
                                    <span>'. $propParams['label'] .'</span>
                                    <select class="form-control '.($propParams['collection']['select2'] ? 'select2-basic' : '').'" name="'. $propName .'">
                    '. (is_string($propParams['resetable']) ? '<option value="">'. $propParams['resetable'] .'</option><option disabled>-</option>' : '' );

                    $items = $this->getStorage($propParams['collection']['name']. 'Collection', $propParams['collection']['bundle']);
                    if( $items ){
                        foreach($items as $i => $item){
                            $option = $propParams['collection']['option']($item, $i);
                            if( is_array($option) && $this->getBundleService('mix')->arrayKeysExists(['value', 'text'], $option) ){
                                $ok = true; // so we dont return empty select

                                $selected = $query->get($propName, '') == $option['value'] ? 'selected="selected"' : '';

                                $control .= '<option '.$selected.' value="'. $option['value'] .'">'. $option['text'] .'</option>';
                            }
                        }
                    }

                    $control .= '</select></label>';
                break;

            case 'StaticSelect':

                    $propParams = [
                        'collection' => $propParams['collection'] ?? [ 
                            'option-1' => 'Option 1',
                            'option-2' => 'Option 2',
                        ],
                        'col' => $propParams['col'] ?? 2,
                        'label' => $propParams['label'] ?? 'Sélectionnez un élément',
                        'resetable' => $propParams['resetable'] ?? 'Tout'
                    ];

                    $control = '<label class="col-xs-12 col-md-'. $propParams['col'] .'"><span>'. $propParams['label'] .'</span><select class="form-control" name="'. $propName .'">
                    '. ( is_string($propParams['resetable']) ? '<option value="">'. $propParams['resetable'] .'</option><option disabled>-</option>' : '' );

                    if( is_array($propParams['collection']) ){
                        $ok = true; // so we dont return empty select
                        foreach($propParams['collection'] as $value => $text){
                            $control .= '<option value="'. $value .'">'. $text .'</option>';
                        }
                    }

                    $control .= '</select></label>';
                break;
            
            default:
                # code...
                break;
        }

        return $control;
    }

    private function getFilterSQL(array $query_reserved=['_filter', 'per_page', 'page'])
    {
        $query = $this->getRequestStackQuery();
        
        $sql = null;
        if( $query ){
            foreach ($query as $key => $value) {

                // lets unset word reserved
                if( !in_array($key, $query_reserved) ) {
                  if( is_array($value) ){
                    foreach ($value as $field => $val) {
                      if( strlen(trim($val))>0 ){
                        $sql .= sprintf("i.$key LIKE '%s' AND ", '%"'.$field.'":"'.$val.'"%');
                      }
                    }
                  }
                  else {
                    if( strlen(trim($value))>0 ) {
                      $sql .= "i.$key LIKE '%".$value."%' AND ";
                    }
                  }
                }
            }
            return trim($sql, ' AND ');
        }
        return '';
    }

    public function fetchAll(string $sql, $parameters=[], $fetch=\PDO::FETCH_COLUMN)
    {
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll($fetch);
    }

    public function resetAppBags($collectionsNames, $reset = true)
    {
        $collectionBag = [];

        foreach ($collectionsNames as $collectionName) {
            ${$collectionName.'File'} = $this->previousContainer->get('kernel')->getProjectDir() . "/templates/storage/$collectionName.json";
            ${$collectionName.'Content'} = file_get_contents(${$collectionName.'File'});

            if( true === $reset ){
                file_put_contents(${$collectionName.'File'}, '');
                die('resetAppBags!');
            }

            $collectionBag[ str_ireplace('Bag', '', $collectionName) ] = (object) [
                'name' => $collectionName,
                'content' => ${$collectionName.'Content'},
                'file' => ${$collectionName.'File'}
            ];
        }

        return $collectionBag;
    }

    public function resetBundleBags()
    {
        $collectionsNames = ['postsBag', 'articlesBag', 'pagesBag', 'navPagesBag'];

        $postsBagName = $collectionsNames[0];
        $articlesBagName = $collectionsNames[1];
        $pagesBagName = $collectionsNames[2];
        $navPagesBagName = $collectionsNames[3];

        foreach ($collectionsNames as $collectionName) {
            ${$collectionName.'File'} = $this->getBundleService('view')->getViewsDir("storage/$collectionName.json");
            ${$collectionName.'Content'} = file_get_contents(${$collectionName.'File'});
            file_put_contents(${$collectionName.'File'}, '');
        }
    }

    public function resetGenericsAreas(): void
    {
        //$this->previousContainer->get('session')->remove("genericsAreas");
        $this->unsetGlobal("genericsAreas");
    }

    public function XMLReturn($params)
    {

        $xml_continue = $this->getRequest()->getRequestUri();
        $xml_continue = strpos($xml_continue, '/logout')!==false?'':'?xml_continue='.$xml_continue;

        if(isset($params['isXml'])){
            if(
                strpos($this->getRequest()->getRequestUri(), '/_admin/') !== false
                ||
                $params['isXml'] == '_base'
            ){
                return new Response($this->renderView('@DovStoneBlogAdminBundle/'.$params['isXml'].'.html.twig'));
            }
            else {
                return $this->redirect($this->generateUrl($params['isXml'], $params['params'] ?? []).$xml_continue);
            }
        }
        else {
            return new Response($this->renderView('app/base.html.twig'));
        }

        
        return $this->redirect($this->generateUrl($params['isXml'], $params['params'] ?? []).$xml_continue);
    }

    public function getTableName($table='users')
    {
        return $this->getBundleService('env')->getAppEnv('DATABASE_TABLES_PREFIX') .( $table == 'data' ? '__': '____' ) . $table;
    }

    public function getCountriesOptions($selected='')
    {
        $countries = ["Afghanistan","Afrique du Sud","Albanie","Algerie","Allemagne","Andorre","Angola","Antigua-et-Barbuda","Arabie saoudite","Argentine","Armenie","Australie","Autriche","Azerbaidjan","Bahamas","Bahrein","Bangladesh","Barbade","Belau","Belgique","Belize","Bénin","Bhoutan","Bielorussie","Birmanie","Bolivie","Bosnie-Herzégovine","Botswana","Bresil","Brunei","Bulgarie","Burkina","Burundi","Cambodge","Cameroun","Canada","Cap-Vert","Chili","Chine","Chypre","Colombie","Comores","Congo","Cook","Corée du Nord","Corée du Sud","Costa Rica","Côte d'Ivoire","Croatie","Cuba","Danemark","Djibouti","Dominique","Egypte","Émirats arabes unis","Equateur","Erythree","Espagne","Estonie","Etats-Unis","Ethiopie","France","Fidji","Finlande","Gabon","Gambie","Georgie","Ghana","Grèce","Grenade","Guatemala","Guinée","Guinée-Bissao","Guinée équatoriale","Guyana","Haiti","Honduras","Hongrie","Inde","Indonesie","Iran","Iraq","Irlande","Islande","Israël","Italie","Jamaique","Japon","Jordanie","Kazakhstan","Kenya","Kirghizistan","Kiribati","Koweit","Laos","Lesotho","Lettonie","Liban","Liberia","Libye","Liechtenstein","Lituanie","Luxembourg","Macedoine","Madagascar","Malaisie","Malawi","Maldives","Mali","Malte","Maroc","Marshall","Maurice","Mauritanie","Mexique","Micronesie","Moldavie","Monaco","Mongolie","Mozambique","Namibie","Nauru","Nepal","Nicaragua","Niger","Nigeria","Niue","Norvège","Nouvelle-Zelande","Oman","Ouganda","Ouzbekistan","Pakistan","Panama","Papouasie-Nouvelle Guinee","Paraguay","Pays-Bas","Perou","Philippines","Pologne","Portugal","Qatar","Republique centrafricaine","Republique dominicaine","Republique tcheque","Roumanie","Royaume-Uni","Russie","Rwanda","Saint-Christophe-et-Nieves","Sainte-Lucie","Saint-Marin","Saint-Siège","Saint-Vincent-et-les Grenadines","Salomon","Salvador","Samoa occidentales","Sao Tome-et-Principe","Senegal","Seychelles","Sierra Leone","Singapour","Slovaquie","Slovenie","Somalie","Soudan","Sri Lanka","Suède","Suisse","Suriname","Swaziland","Syrie","Tadjikistan","Tanzanie","Tchad","Thailande","Togo","Tonga","Trinite-et-Tobago","Tunisie","Turkmenistan","Turquie","Tuvalu","Ukraine","Uruguay","Vanuatu","Venezuela","Viet Nam","Yemen","Yougoslavie","Zaire","Zambie","Zimbabwe"];

        $options = '';
        foreach ($countries as $c) {
            $options .= '<option '. ($selected==$c?'selected':'') .' value="'.$c.'">'.$c.'</option>';
        }
        return new Markup($options, 'UTF-8');
    }


    # DataRepository #######################

    ##################################################

    public function fetchEager($items, $bundle = null, callable $joinWhere=null)
    {
        return $this->getBundleService('data')->fetchEager($items, $bundle, $joinWhere);
    }

    public function fetchBundleEager($items, callable $joinWhere=null)
    {
        return $this->getBundleService('data')->fetchEager($items, true, $joinWhere);
    }

    public function pushFK($item)
    {
        return $this->getBundleService('query_builder')->pushFK($item);
    }

    private function _getAttrLoop($obj, $attrs, $i, $onNull)
    {
        $size = sizeof($attrs);
        $attrToFetch = $attrs[$i];
        $attrToFetchAsGetMethod = 'get'.ucwords($attrToFetch);
        $methodExists = method_exists($obj, $attrToFetchAsGetMethod);
        if( isset($obj->$attrToFetch) || (is_array($obj) && isset($obj[$attrToFetch])) || $methodExists ){
            $val = $methodExists ? $obj->$attrToFetchAsGetMethod() : ( isset($obj->$attrToFetch) ? $obj->$attrToFetch : $obj[$attrToFetch] );
            if($i == $size-1){
                return $val;
            }
            return $this->_getAttrLoop($val, $attrs, ++$i, $onNull);
        }
        return $onNull ?? null;
    }

    private $imgSizes = [];
    private $fileBag = [];

    public function setImagesUploadingSizesMapping(array $data)
    {
        $this->setGlobal([ 'DataRepository__imagesUploadingSizesMapping' => $data ]);
    }

    public function handlePostedFiles($params)
    {
        $params = array_merge([
            'info' => [],
            'onSuccess' => function($result){},
            'onError' => function($err){}
        ], $params);

        $info = $params['info'];

        if( $_FILES ){
            $mediaServ = $this->getBundleService('media');
            $stringServ = $this->getBundleService('string');
            foreach ($_FILES as $k => $f) {
                $xploded = explode('@', $k);
                $pattern = "/([a-zA-Z0-9-]+)(--)([0-9a-zA-Z]+)(x)([0-9a-zA-Z]+)/ui";
                preg_match($pattern, $xploded[0] ?? '', $m);

                $filename = $stringServ->getSlug($mediaServ->getFileName($f['name']));
                $filename = (new \DateTime())->format('His-')  . $filename;

                $imgSize = getimagesize($f['tmp_name']);
                if($imgSize){
                    $x = $imgSize[0]; $y = $imgSize[1];
                    $filename = $filename . '--'.$x.'x'.$y;
                }
                else {
                    $x = $y = null;
                }

                $result = $mediaServ->uploadFile([
                    'inputName' => $k,
                    'x' => $x, 'y' => $y,
                    'fileName' =>  $filename,
                    'onSuccess' => function ($p) use ($info, $m) {
                        
                        // lets save in DB
                        return $this->basicCreate([
                            'entity' => new Bloggy(),
                            'sanitizer' => function ($posted, $handled) use ($p, $m, $info) {
                                $handled
                                    ->setSlug($p->filename)
                                    ->setType($m[1] ?? ($p->is_image ? 'image' : $p->extension))
                                    ->setInfo(array_merge((array)$p, (array)$info));
                                return $handled;
                            },
                            'onSuccess' => function(){
                                return true;
                            }
                        ]);
                    },
                    'onError' => function ($e) use ($params) {
                        $params['onError']($e);
                    },
                ]);
            }
            $params['onSuccess']($result);
        } 
        return $params['onSuccess'](true);
    }

    public function unlinkFile($params)
    {
        $params = array_merge([
            'id' => null,
            'encryptionKey' => null,
            'repository' => $this->getBundleRepo('Bloggy')
        ], $params);

        $id = $params['id'];
        $encryptionKey = $params['encryptionKey'];

        if( !$id || !$encryptionKey ){
            return new JsonResponse([ 'success' => false ]);
        }

        $repository = $params['repository'];

        $id = $this->getBundleService('string')->decrypt($id, $encryptionKey);
        
        return $this->read([
            'finder' => function() use ($id, $repository){
                return $repository->find($id);
            },
            'onFound' => function($file){
                $relative_url = $file->getInfo()->relative_url;
                
                // lets delete from DB
                $this->delete([
                    'finder' => function() use ($file){ return $file; }
                ]);

                // lets delete from webspace
                $filename = $this->getBundleService('dir')->getProjectPath('public/' . $relative_url);
                if(file_exists($filename)){
                    unlink($filename);
                }

                return new JsonResponse([ 'success' => true ]);
            },
            'onNotFound' => function(){
                return new JsonResponse([ 'success' => false ]);
            }
        ]);
    }

    public function handleFiles($type, $handled = null)
    {
        //
        $imagesUploadingSizesMap = $this->getGlobal('DataRepository__imagesUploadingSizesMapping');

        if( isset($imagesUploadingSizesMap[$type]) ){

            //
            unset($_FILES['files']);

            // lets init $this->fileBag from images cached
            $files_cached = $this->getRequestStackRequest()->get('files_cached');


            if(
                ($files_cached && is_array($files_cached) && $handled) 
                ||
                (!$files_cached && $handled && method_exists($handled, 'getInfo') && isset($handled->getInfo()->images) )
            ){
                $itemImages = (array)json_decode($handled->getInfo()->images);

                // a priori no deletion till the constrary
                // so lets just value fileBag with the content into db
                $this->fileBag = $itemImages;

                foreach ($itemImages as $itemImageKey => $f) {

                    // if file name is not into bag
                    // meaning deletion
                    if(
                        ($files_cached && !in_array($itemImageKey, $files_cached))
                        ||
                        (!$files_cached)
                    ){
                        unset( $this->fileBag[ $itemImageKey ] );
                        $f = (array)$f;
                        $this->getBundleService('media')->unlinkFiles( $f['sizes'] );
                    }
                }
            }

            foreach($_FILES as $inputName => $file){
                $file = $_FILES[ $inputName ];
                $this->uploadImages($type, $inputName, $imagesUploadingSizesMap);
            }

        }

        return json_encode($this->fileBag);
    }

    private function uploadImages($type, $inputName, $imagesUploadingSizesMap)
    {
        $response;
        $fileName = uniqid();

        foreach ($imagesUploadingSizesMap[$type] as $i => $sizes) {

            $x = $sizes[0];
            $y = $sizes[1];

            $response = $this->getBundleService('media')->uploadImg([
                'inputName' => $inputName,
                'fileName' =>  $fileName . '--' . $x . 'x' . $y,
                'dirPath' => $this->getBundleService('dir')->getProjectPath('public/uploads/'.$type),
                'settings' => function($file) use ($x, $y){

                    $file->image_x = $x;
                    $file->image_y = $y;
                    $file->image_convert = 'gif';
                    $file->image_is_transparent = true;
                    $file->file_max_size = '1G';
                    $file->allowed = 'image/*';
                    $file->image_ratio_fill ='C';
                    $file->image_resize = true;
                    $file->image_ratio = true;
                    $file->image_resize = true;

                    //
                    return $file;
                },
                'onSuccess' => function() use ($x, $y, $fileName, $type) {

                    $this->imgSizes[$x.'x'.$y] = 'uploads/'.$type.'/'.$fileName.'--'.$x . 'x' . $y.'.gif';

                    $this->fileBag[ $fileName ] = [
                        'sizes' => $this->imgSizes
                    ];
                },
                'onError' => function ($err) {
                    return false;
                }
            ]);

            if ($i == sizeof($sizes) ) {
                return $response;
            }
        }
    }
}
