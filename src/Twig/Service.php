<?php

namespace DovStone\Bundle\BlogAdminBundle\Twig;

use DovStone\Bundle\BlogAdminBundle\Service\ExecuteBeforeService;
use Symfony\Component\Filesystem\Filesystem;
use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;

class Service extends AbstractExtension
{
    //
    private $container;
    //
    private $please;
    private $appUiD;

    //public function __construct( ExecuteBeforeService $execBeforeService, PleaseService $please )
    public function __construct( PleaseService $please )
    {
        $this->please = $please;
        $this->container = $this->please->getContainer();
        //
        $this->filesystem = new Filesystem();
        //
        $this->appUiD = sha1(getenv('APP_NAME'));
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('getSelectDays', array($this, 'getSelectDays')),
            new TwigFunction('getSelectMonths', array($this, 'getSelectMonths')),
            new TwigFunction('getSelectYears', array($this, 'getSelectYears')),
            //
            new TwigFunction('getEnv', array($this, 'getEnv')),
            new TwigFunction('isLocalHost', array($this, 'isLocalHost')),
            new TwigFunction('getAppDir', array($this, 'getAppDir')),
            new TwigFunction('getUrl', array($this, 'getUrl')),
            new TwigFunction('getCDN', array($this, 'getCDN')),
            new TwigFunction('getUsualsJsCDN', array($this, 'getUsualsJsCDN')),
            new TwigFunction('getThumbnail', array($this, 'getThumbnail')),
            new TwigFunction('getCurrentUrl', array($this, 'getCurrentUrl')),
            new TwigFunction('getCurrentUrlParamsLess', array($this, 'getCurrentUrlParamsLess')),
            new TwigFunction('getPostHref', array($this, 'getPostHref')),
            new TwigFunction('getDesc', array($this, 'getDesc')),
            new TwigFunction('getPostRelatives', array($this, 'getPostRelatives')),
            new TwigFunction('findPost', array($this, 'findPost')),
            new TwigFunction('getMDLAssets', array($this, 'getMDLAssets')),
            new TwigFunction('getThemeAsset', array($this, 'getThemeAsset')),
            new TwigFunction('getThemeAssets', array($this, 'getThemeAssets')),
            new TwigFunction('getAsset', array($this, 'getAsset')),
            new TwigFunction('getAssets', array($this, 'getAssets')),
            new TwigFunction('browseThemeAssets', array($this, 'browseThemeAssets')),
            new TwigFunction('getMedia', array($this, 'getMedia')),
            new TwigFunction('getHeadAssets', array($this, 'getHeadAssets')),
            new TwigFunction('getAppCss', array($this, 'getAppCss')),
            new TwigFunction('getSection', array($this, 'getSection')),
            new TwigFunction('getSections', array($this, 'getSections')),
            new TwigFunction('s', array($this, 's')),
            new TwigFunction('getPartial', array($this, 'getPartial')),
            new TwigFunction('p', array($this, 'p')),
            new TwigFunction('sanitizeFinalView', array($this, 'sanitizeFinalView')),
            new TwigFunction('path', array($this, 'path')),
            new TwigFunction('convertToKnpPaginatorBundle', array($this, 'convertToKnpPaginatorBundle')),
            new TwigFunction('getKnpPaginate', array($this, 'getKnpPaginate')),
            new TwigFunction('getKnpTplPath', array($this, 'getKnpTplPath')),
            new TwigFunction('isHome', array($this, 'isHome')),
            //
            new TwigFunction('getFrenchDate', array($this, 'getFrenchDate')),
            new TwigFunction('getFrenchDateFormatToUsFormat', array($this, 'getFrenchDateFormatToUsFormat')),
            new TwigFunction('getHumanDiff', array($this, 'getHumanDiff')),
            new TwigFunction('getTimeAgo', array($this, 'getTimeAgo')),
            new TwigFunction('getTimeRemaining', array($this, 'getTimeRemaining')),
            new TwigFunction('convertToHoursMins', array($this, 'convertToHoursMins')),
            new TwigFunction('getYearsOld', array($this, 'getYearsOld')),
            new TwigFunction('getMonth', array($this, 'getMonth')),
            new TwigFunction('getDateTime', array($this, 'getDateTime')),
            //
            new TwigFunction('getComponent', array($this, 'getComponent')),
            //
            new TwigFunction('getCountriesOptions', array($this, 'getCountriesOptions')),
            //
            new TwigFunction('getStorage', array($this, 'getStorage')),
            new TwigFunction('setStorage', array($this, 'setStorage')),
            new TwigFunction('getBag', array($this, 'getBag')),
            new TwigFunction('setGlobal', array($this, 'setGlobal')),
            new TwigFunction('getGlobal', array($this, 'getGlobal')),
            new TwigFunction('getCurl', array($this, 'getCurl')),
            //
            new TwigFunction('getBundleEmptyListView', array($this, 'getBundleEmptyListView')),
            new TwigFunction('getHtml2Text', array($this, 'getHtml2Text')),
            //
            new TwigFunction('isGranted', array($this, 'isGranted')),
            new TwigFunction('getUserRole', array($this, 'getUserRole')),
            new TwigFunction('getUserData', array($this, 'getUserData')),
            new TwigFunction('getUserFullName', array($this, 'getUserFullName')),
            new TwigFunction('getAttr', array($this, 'getAttr')),
            new TwigFunction('attr', array($this, 'attr')),
            new TwigFunction('getImageSrcRec', array($this, 'getImageSrcRec')),
            //
            new TwigFunction('getLatestValue', array($this, 'getLatestValue')),
            new TwigFunction('getLatestQueriedValue', array($this, 'getLatestQueriedValue')),
            new TwigFunction('getFormControl', array($this, 'getFormControl')),
            new TwigFunction('getFormControlSelect', array($this, 'getFormControlSelect')),
            new TwigFunction('getFormControlTextarea', array($this, 'getFormControlTextarea')),
            new TwigFunction('getValidatorHasErrorClass', array($this, 'getValidatorHasErrorClass')),
            new TwigFunction('getValidatorAlertHTMLTag', array($this, 'getValidatorAlertHTMLTag')),
            //
            new TwigFunction('getVisitorsCount', array($this, 'getVisitorsCount')),

            new TwigFunction('jsonDecode', array($this, 'jsonDecode')),
            new TwigFunction('makeEntityJsonifiable', array($this, 'makeEntityJsonifiable')),
            new TwigFunction('jsonifyEntity', array($this, 'jsonifyEntity')),

            new TwigFunction('encrypt', array($this, 'encrypt')),
            new TwigFunction('decrypt', array($this, 'decrypt')),

            new TwigFunction('ellipsis', array($this, 'ellipsis')),
            new TwigFunction('numberFormatShort', array($this, 'numberFormatShort')),

            // App\Entity\Data Doctrine fetch eacher approach
            new TwigFunction('fetchEager', array($this, 'fetchEager')),
            new TwigFunction('fetchBundleEager', array($this, 'fetchBundleEager')),
            new TwigFunction('findBloggy', array($this, 'findBloggy')),
            new TwigFunction('findUserBy', array($this, 'findUserBy')),

            new TwigFunction('getACFFormControls', array($this, 'getACFFormControls')),
            new TwigFunction('find_acf', array($this, 'find_acf')),
            new TwigFunction('get_acf_field', array($this, 'get_acf_field')),
            new TwigFunction('acff', array($this, 'acff')),
            new TwigFunction('get_acf_post_field', array($this, 'get_acf_post_field')),
            new TwigFunction('acfpf', array($this, 'acfpf')),

            new TwigFunction('arrayfy', array($this, 'arrayfy')),
            new TwigFunction('intfy', array($this, 'intfy')),
            new TwigFunction('dd', array($this, 'dd')),
            new TwigFunction('_dump', array($this, '_dump')),

            //
            new TwigFunction('listEntityPath', array($this, 'listEntityPath')),
            new TwigFunction('readEntityPath', array($this, 'readEntityPath')),
            new TwigFunction('updateEntityPath', array($this, 'updateEntityPath')),
            new TwigFunction('deleteEntityPath', array($this, 'deleteEntityPath')),

        );
    }

    public function getSelectDays($label = null)
    {
        return new Markup($this->please->getBundleService('time')->getSelectDays($label), 'UTF-8');
    }

    public function getSelectMonths($short = null)
    {
        return new Markup($this->please->getBundleService('time')->getSelectMonths($short), 'UTF-8');
    }

    public function getSelectYears($label = null, $from = null)
    {
        return new Markup($this->please->getBundleService('time')->getSelectYears($label, $from), 'UTF-8');
    }

    public function getEnv($var = 'APP_ENV')
    {
        return $this->please->getBundleService('env')->getAppEnv($var);
    }

    public function isLocalHost()
    {
        return $this->please->getBundleService('env')->isLocalHost();
    }

    public function getAppDir()
    {
        return $this->please->getBundleService('env')->getAppDir();
    }

    public function getUrl(string $path = '/')
    {
        return $this->please->getBundleService('url')->getUrl($path);
    }

    public function getCDN($path='/', $extension='css')
    {
        return new Markup($this->please->getBundleService('url')->getCDN($path, $extension), 'UTF-8');
    }

    public function getUsualsJsCDN(array $adds = [], $v=false)
    {
        return new Markup($this->please->getBundleService('url')->getUsualsJsCDN($adds, $v), 'UTF-8');
    }

    public function getThumbnail($thumbnail = null, $height = 900)
    {
        return $this->please->getBundleService('media')->getThumbnail($thumbnail, $height);
    }

    public function getCurrentUrl()
    {
        return $this->please->getBundleService('url')->getCurrentUrl();
    }

    public function getCurrentUrlParamsLess()
    {
        return $this->please->getBundleService('url')->getCurrentUrlParamsLess();
    }

    public function getPostHref($post)
    {
        return $this->please->getBundleService('post')->getPostHref($post);
    }

    public function getDesc($post, $orderBy=[], $limit=null)
    {
        return $this->please->getBundleService('post')->getDesc($post, $orderBy, $limit);
    }

    public function getPostRelatives($post, $orderBy=[], $limit=null)
    {
        return $this->please->getBundleService('post')->getPostRelatives($post, $orderBy, $limit);
    }

    public function findPost($criterias, $orderBy=[], $limit = null)
    {
        return $this->please->getBundleService('post')->findPost($criterias, $orderBy, $limit);
    }

    public function getComponent(string $componentName, $params = [], $dir='components')
    {
        $component_path = "$dir/$componentName.html.twig";
        $component = "templates/$component_path";
        $view = $this->please->getBundleService('dir')->dirPath($component);

        $fileSystem = new Filesystem();
        if ($fileSystem->exists($view)) {
            return new Markup($this->please->getBundleService('view')->getRenderView($component_path, $params), 'UTF-8');
        }
        return '';
    }
    
    public function getCountriesOptions($selected='')
    {
        return $this->please->getCountriesOptions($selected);
    }

    public function setStorage($bigData, $bundleStorage=null, $sessionIdRelated = null)
    {
        return $this->please->setStorage($bigData, $bundleStorage, $sessionIdRelated);
    }

    public function getStorage($fileName, $bundleStorage=null)
    {
        return $this->please->getStorage($fileName, $bundleStorage);
    }

    public function getCurl($url, $isRoute = true)
    {
        return $this->please->getCurl($url, $isRoute);
    }

    public function getBag($collectionName)
    {
        return $this->please->getBag($collectionName);
    }

    public function setGlobal($bigData)
    {
        return $this->please->setGlobal($bigData);
    }

    public function getGlobal($globalName)
    {
        return $this->please->getGlobal($globalName);
    }

    public function getBundleEmptyListView($message = 'Le dossier est vide.', $icon='ban')
    {
        return new Markup($this->please->getBundleService('view')->getBundleEmptyListView($message, $icon), 'UTF-8');
    }

    public function getHtml2Text($html = '')
    {
        return $this->please->getBundleService('string')->getHtml2Text($html);
    }

    public function isGranted($user, $roles = [])
    {
        $ROLES = [];
        foreach ($roles as $role) {
            if( $role === '__ANY__' ) {
                return $user !== null;
            }
            else if( $role === '__NONE__' ||  $role === '__ANON__' ||  $role === '__ANONYMOUS__' ) {
                return $user === null;
            }
            else {
                $ROLES[] = 'ROLE_' . trim($role);
            }
        }
        foreach ($roles as $role) {
            if( in_array($user->getRoles(), $ROLES) ){
                return true;
            }
        }
        return false;
    }

    public function getUserRole($user=null)
    {
        return $this->please->getUserRole($user);
    }

    public function getUserData($user=null)
    {
        return $this->please->getUserData($user);
    }

    public function getAttr($obj, $attrs, $onNull=null)
    {
        return $this->please->getAttr($obj, $attrs, $onNull);
    }

    /* alias of getAttr */
    public function attr($obj, $attrs, $onNull=null, $isEmptibale=true)
    {
        return $this->please->getAttr($obj, $attrs, $onNull, $isEmptibale);
    }

    public function getImageSrcRec($src)
    {
        return $this->please->getBundleService('media')->getImageSrcRec($src);
    }

    public function getLatestValue($fieldName)
    {
        $latestValue = $this->please->getBundleService('session')->getSession()->get("__form_control_latest_value__{$fieldName}");
        if (!empty($latestValue)) {
            return $latestValue;
        }
        return '';
    }

    public function getLatestQueriedValue($fieldName)
    {
        return htmlentities($this->please->getRequestStack()->getCurrentRequest()->query->get($fieldName));
    }

    public function getValidatorHasErrorClass($fieldName)
    {
        if( $fieldName === 'second' ){
            $fieldName = 'password';
        }
        $flash = $this->please->getBundleService('session')->getSession()->get("__validator_flash__{$fieldName}_alias");
        if (!empty($flash) && !is_null($flash)) {
            return 'has-error';
        }
        return '';
    }

    public function getValidatorAlertHTMLTag($fieldName, $tag = 'em', $type = 'danger')
    {
        if( $fieldName === 'second' ){
            $fieldName = 'password';
        }
        $flash = $this->please->getBundleService('session')->getSession()->get("__validator_flash__{$fieldName}");
        if (!empty($flash)) {
            //deleting from bag before return
            $this->please->getBundleService('session')->getSession()->set("__validator_flash__{$fieldName}", null);

            return new Markup('<span class="help-block text-' . $type .'"><ul class="list-unstyled"><li><span class="glyphicon glyphicon-exclamation-sign"></span> ' . $flash . '</li></ul></span>', 'UTF-8');
            //return new Markup("<$tag class='validator-alert text-$type'>" . $flash . "</$tag>", 'UTF-8');
        }
        return '';
    }

    public function getVisitorsCount()
    {
        return $this->please->getBundleService('string')->getVisitorsCount();
    }

    public function getKnpPaginate( $items, $limit = 15, $page = 1 )
    {
        return $this->please->getKnpPaginate( $items, $limit, $page );
    }

    public function getKnpTplPath()
    {
        return '@DovStoneBlogAdminBundle/partials/twitter_bootstrap_v4_pagination.html.twig';
    }

    public function isHome()
    {
        return $this->please->getBundleService('dir')->isHome();
    }

    public function getMedia( $path )
    {
        return $this->please->getBundleService('media')->getMedia( $path );
    }

    public function getHeadAssets( $arr=[] )
    {
        return new Markup($this->please->getBundleService('url')->getHeadAssets( $arr ), 'UTF-8');
    }

    public function getUserFullName($user, $onNull='Inconnu')
    {
        return new Markup($this->please->getUserFullName($user, $onNull), 'UTF-8');
    }

    public function jsonDecode($value)
    {
        return json_decode($value);
    }

    public function makeEntityJsonifiable($entities)
    {
        return $this->please->makeEntityJsonifiable($entities);
    }

    public function jsonifyEntity($entities)
    {
        return $this->please->jsonifyEntity($entities);
    }

    public function encrypt($message, $key)
    {
        return $this->please->getBundleService('string')->encrypt($message, $key);
    }

    public function decrypt($encrypted, $key)
    {
        return $this->please->getBundleService('string')->decrypt($encrypted, $key);
    }

    public function ellipsis($string, $max=100, $append='...')
    {
        return $this->please->getBundleService('string')->ellipsis($string, $max, $append);
    }

    public function numberFormatShort($number)
    {
        return $this->please->getBundleService('string')->numberFormatShort($number);
    }

    public function fetchEager($items, $bundle=null, callable $joinWhere=null)
    {
        return $this->please->fetchEager($items, $bundle, $joinWhere);
    }

    public function fetchBundleEager($items, callable $joinWhere=null)
    {
        return $this->please->fetchEager($items, $bundle=true, $joinWhere);
    }

    public function getACFFormControls($fields, $item=null)
    {
        return $this->please->getBundleService('acf')->getACFFormControls($fields, $item);
    }

    public function find_acf($type, $limit=10, $acfOrderBy=null, $doctrineOrderBy=['created'=>'DESC'], $ACFItemsCond=[])
    {
        return $this->please->getBundleService('acf')->findACF($type, $limit, $acfOrderBy, $doctrineOrderBy, $ACFItemsCond);
    }

    public function findBloggy($by, $orderBy=['created'=>'DESC'], $limit=10)
    {
        return $this->please->getBundleService('acf')->findBloggy($by, $orderBy, $limit);
    }

    public function findUserBy($criteria = [], $orderBy = [], $findOne=null)
    {
        return $this->please->findBy($this->please->getBundleRepo('User'), $criteria, $orderBy, $findOne=null);
    }

    public function get_acf_field($acf, $field)
    {
        return $this->please->getBundleService('acf')->getACFField($acf, $field);
    }

    public function acff($acf, $field)
    {
        return $this->get_acf_field($acf, $field);
    }

    public function get_acf_post_field($acf, $field)
    {
        return $this->please->getBundleService('acf')->getACFPostField($acf, $field);
    }

    public function acfpf($acf, $field)
    {
        return $this->get_acf_post_field($acf, $field);
    }

    public function listEntityPath( $entity, array $params = [] )
    {
        $params['entity'] = $entity;
        return $this->please->getContainer()->get('router')->generate('listEntity', $params );
    }

    public function readEntityPath( $entity, array $params = [] )
    {
        $params['entity'] = $entity;
        return $this->please->getContainer()->get('router')->generate('readEntity', $params );
    }

    public function updateEntityPath( $entity, array $params = [] )
    {
        $params['entity'] = $entity;
        return $this->please->getContainer()->get('router')->generate('updateEntity', $params );
    }

    public function deleteEntityPath( $entity, array $params = [] )
    {
        $params['entity'] = $entity;
        return $this->please->getContainer()->get('router')->generate('deleteEntity', $params );
    }

    public function getFormControl($type, $name, $valeur, $attr = null, $champ_requis_texte = null)
    {
        return new Markup($this->please->getBundleService('form_builder')->formControl($type, $name, $valeur, $attr, $champ_requis_texte), 'UTF-8');
    }

    public function getFormControlSelect($name, $options, $attr = '', $valeur_a_selectionner = null, $desactiver_label = true)
    {
        return new Markup($this->please->getBundleService('form_builder')->formControlSelect($name, $options, $attr, $valeur_a_selectionner, $desactiver_label),'UTF-8') ;
    }

    public function getFormControlTextarea($name, $attr = '', $valeur = '')
    {
        return new Markup($this->please->getBundleService('form_builder')->formControlTextarea($name, $attr, $valeur),'UTF-8');
    }

    public function getFrenchDate($date, $format = "D/d/M/Y à H:i:s")
    {
        return $this->please->getBundleService('time')->getFrenchDate($date, $format);
    }

    public function getFrenchDateFormatToUsFormat($date, $delimiter = "-")
    {
        return $this->please->getBundleService('time')->getFrenchDateFormatToUsFormat($date, $delimiter);
    }

    public function getHumanDiff($timestamp, $tokens = null)
    {
        return $this->please->getBundleService('time')->getHumanDiff($timestamp, $tokens);
    }

    public function getTimeAgo($timestamp, $tokens = null)
    {
        return $this->please->getBundleService('time')->getTimeAgo($timestamp, $tokens);
    }

    public function getTimeRemaining($timestamp, $format = "%d jours, %h heures, %i minutes, %s secondes")
    {
        return $this->please->getBundleService('time')->getTimeRemaining($timestamp, $format);
    }

    public function convertToHoursMins($time, $format = '%02dh%02dmin')
    {
        return $this->please->getBundleService('time')->convertToHoursMins($time, $format);
    }

    public function getYearsOld($birthdate)
    {
        return $this->please->getBundleService('time')->getYearsOld($birthdate);
    }

    public function getMonth($dateTime = null, $type = null, $months_prefixed = null)
    {
        return $this->please->getBundleService('time')->getMonth($dateTime, $type, $months_prefixed);
    }

    public function getDateTime($datetime = null)
    {
        return $this->please->getBundleService('time')->getDateTime($datetime);
    }

    public function getThemeAsset($asset, $attr='')
    {
        return new Markup($this->please->getBundleService('media')->getThemeAsset($asset, $attr), 'UTF-8');
    }

    public function getAsset($asset, $attr='')
    {
        return $this->getThemeAsset($asset, $attr);
    }

    public function getThemeAssets($assets, $extension='css')
    {
        return new Markup($this->please->getBundleService('media')->getThemeAssets($assets, $extension), 'UTF-8');
    }

    public function getAssets($assets, $extension='css')
    {
        return $this->getThemeAssets($assets, $extension);
    }

    public function browseThemeAssets($extension='css', $v=true, $prevent=[])
    {
        return new Markup($this->please->getBundleService('media')->browseThemeAssets($extension, $v, $prevent), 'UTF-8');
    }

    public function getMDLAssets()
    {
        return new Markup($this->please->getBundleService('media')->getMDLAssets(), 'UTF-8');
    }

    public function getAppCss($versionify=false)
    {
        if( $this->please->getBundleService('url')->isDev() && $this->please->getBundleService('url')->isLocalHost() ){
            $href = 'http://localhost:' . $this->please->getBundleService('env')->getEncorePort() . '/assets/css/app.css';
        }
        else {
            $href = $this->please->getBundleService('url')->getUrl('theme/assets/css/app.css');
        }
        
        return new Markup('<link id="app_main_css" rel="stylesheet" href="'.$href.($versionify==true?'?v='.rand(0,999):'').'">', 'UTF-8');
    }

    public function getSection($section, $params=[])
    {
        return new Markup($this->please->getBundleService('view')->getSection($section, $params), 'UTF-8');
    }
    
    public function getSections($sections)
    {
        $view = '';
        foreach ($sections as $section) {
            $view .= $this->getSection($section[0], $section[1] ?? []);
        }
        return $view;
    }

    public function s($section, $params=[])
    {
        return $this->getSection($section, $params);
    }

    public function getPartial($partial, $params=[])
    {
        return new Markup($this->please->getBundleService('view')->getPartial($partial, $params), 'UTF-8');
    }

    public function p($partial, $params=[])
    {
        return $this->getPartial($partial, $params);
    }

    public function sanitizeFinalView($view)
    {
        return new Markup($this->please->getBundleService('view')->sanitizeFinalView($view), 'UTF-8');
    }

    public function path($path, $params=[])
    {
        return $this->please->getBundleService('url')->getPath($path, $params);
    }

    public function convertToKnpPaginatorBundle($items=[], $perPage=10)
    {
        return $this->please->convertToKnpPaginatorBundle($items, $perPage);
    }
    
    public function arrayfy($data) { return (array)$data; }
    public function intfy($val) { return (int)$val; }
    public function dd($data) { dd($data); }
    public function _dump($data) { dump($data); }
}
