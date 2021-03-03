<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DataService extends AbstractController
{
    private $please;
    private $result = [];
    private $bagToUnset = [];
    private $QUERIES = [];
    private $Queries = [];

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
    }

    /* START OF forkDoctrineORM */
    public function forkDoctrineORM( string $orm, $reset = null, $bundle = null )
    {
        $this->please = $this->please;
        //$this->please->unsetStorage('ACFJoinIssueRows', true);
        
        if( $reset == true ){
            $this->please->unsetStorage('forkDoctrineORM' . md5($bundle), true);
        }

        $forkDoctrineORM = $this->please->getStorage('forkDoctrineORM' . md5($bundle), true);

        $mappingKey = md5($bundle);

        if( $forkDoctrineORM ){
            $this->$mappingKey = $forkDoctrineORM;
        }
        else {
            $xploded = array_map(function($v){
                if( !empty(trim($v)) ){
                    return trim($v);
                }
            }, explode(PHP_EOL, $orm));

            if( $xploded ){

                $r_1 = $r_2 = $r_3 = [];

                foreach ($xploded as $eachORM) {

                    if( $eachORM ){

                        $splitted = preg_split('/\s+/', $eachORM);
                        
                        if( in_array('mappedBy', $splitted) ){
                            $_ = $this->mappedBy($splitted);
                            if( isset($_->key) ){
                                $r_1[$splitted[0]][ $_->key ] = $_->data;
                            }
                        }
                        elseif (in_array('hasSiblings', $splitted)) {
                            $_ = $this->hasSiblings($splitted);
                            $r_2[$splitted[0]][ $_->key ] = $_->data;
                        }
                        else {
                            $_ = $this->inversedBy($splitted);
                            if(isset($_->key)){
                                $r_3[$splitted[0]][ $_->key ] = $_->data;
                            }
                        }
                    }
                }

                $this->please->setStorage([
                    'forkDoctrineORM' . md5($bundle) => [
                        'content' => function() use ($r_1, $r_2, $r_3, $mappingKey) {
                            $result = array_merge_recursive($r_1, $r_2, $r_3);
                            $this->$mappingKey = $result;
                            return $result;
                        }
                    ]
                ], true);
            }
        }
        //
        return $this;
    }

    public function fetchEager($items, $bundle = null, callable $joinWhere=null)
    {
        if( !isset($this->hasProps) ){
            $this->please = $this->please;
            //
            $this->bloggyRepo = $this->please->getBundleRepo('Bloggy');
            $this->bloggiesTable = $this->please->getTableName('bloggies');
            //
            $this->userRepo = $this->please->getBundleRepo('User');
            $this->usersTable = $this->please->getTableName('users');

            $this->hasProps = true;
        }

        $mappingKey = md5($bundle);

        if( !isset($this->$mappingKey) ){
            $this->$mappingKey = $this->please->getStorage('forkDoctrineORM' . md5($bundle), true);
        }
         
        if( $this->$mappingKey ){
            
            $foreignsKeysMapping = $this->$mappingKey;

            $this->result = [];

            $originalType = gettype($items);

            $originalsItems = $items;

            if( $originalType == 'object' ){ $items = [ $items ]; }

            if( !empty($items) ){

                foreach ($items as $i => $item) {

                    // Bloggy
                    if( method_exists($item, 'getType') && array_key_exists($item->getType(), $foreignsKeysMapping) ){

                        $bag = $foreignsKeysMapping[$item->getType()];

                        foreach ($bag as $keyToInject => $__) {

                            if(
                                //($keyToInject != '_acf' && $keyToInject != 'parent') && !isset($item->getInfo()->$keyToInject)
                                //($keyToInject != '_acf' && $keyToInject != 'parent') && isset($item->getInfo()->$keyToInject)
                                //($keyToInject != '_acf' && $keyToInject != 'parent') && (!isset($item->getInfo()->$keyToInject) || !isset($item->getInfo()->$keyToInject))
                                ($keyToInject != '_acf' && $keyToInject != 'parent')
                                ||
                                ($keyToInject == '_acf' || $keyToInject == 'parent') && (isset($item->getInfo()->$keyToInject))
                            ){

                                if( !isset($item->$keyToInject) ){ $item->$keyToInject = null; }

                                $xplodedKeyToInject = explode('|', $keyToInject);

                                $relation = $__['relationType'];
                                $type = $__['type'];
                                $verb = $__['verb'];
                                $notnull = $__['notnull'];
                                $onNotnull = $__['onNotnull'];


                                // hasMany || hasSibling
                                if( $relation == 'mappedBy' ){
                                    
                                    $inversedByKey = $__['inversedByKey'];

                                    $keyToInjectVal = null;
    
                                    $info = json_encode($item->getInfo());
                                    preg_match("/(\")($inversedByKey)(\")(:)([)(\")([a-zA-Z0-9,\"]+)(\")(\])/", $info, $m1);

                                    if(
                                        !empty($m1)
                                        &&
                                        sizeof($m1) == 8
                                    ){

                                        $keyToInjectVal = json_decode($m1[5] . "\"]");
                                    }

                                    // .. Users
                                    if( sizeof($__['collectionsNames']) == 1 && ($__['collectionsNames'][0] == '__Users' || $__['collectionsNames'][0] == 'Users') ){

                                        //$sql = "SELECT _u.id FROM $this->usersTable _u WHERE (_u.info LIKE '%\"$inversedByKey\":%' AND _u.info LIKE '%\"{$item->getId()}\"%')";
                                        $sql = "SELECT _u.id FROM $this->usersTable _u WHERE _u.info REGEXP '\"$inversedByKey\":\\\[.*\"{$item->getId()}\".*\\\]'";

                                        $IDs = $this->cacheIDS($sql);

                                        $rows = $this->cacheFinder([
                                            array_merge([
                                                'id' => $IDs
                                            ], (
                                                $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                ? $joinWhere()[$keyToInject]
                                                : ['enabled' => true]
                                            )), ['created'=>'DESC']],
                                            function($p){
                                                return $this->userRepo->findBy($p[0], $p[1]);
                                            }
                                        );
                                    }
                                    else {
                                        // .. Bloggies
                                        if( $inversedByKey !== '__user__' ){
                                                    
                                            $rows = array_merge(
                                                        // Fetching child having his parent key(:string)
                                                        // Ex: "inversedByKey" : "id-1"
                                                        $this->cacheFinder([
                                                                array_merge(
                                                                    [ "info[$inversedByKey]" => (string)$item->getId() ],
                                                                    (
                                                                        $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                                        ? $joinWhere()[$keyToInject]
                                                                        : ['enabled' => true]
                                                                    ),
                                                                    ($type ? ['type' => $type] : [])
                                                                ),
                                                                ['created'=>'DESC']
                                                            ],
                                                            function($p){
                                                                return $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1])['get']('rows');
                                                            }
                                                        ),
                                                        // Fetching children having id in parent (item) key(:array)
                                                        // Ex: "keyToInject" : ["id-1", "id-2", "id-3"]
                                                        $this->cacheFinder([
                                                            array_merge(
                                                                [ "id" => $keyToInjectVal ],
                                                                (
                                                                    $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                                    ? $joinWhere()[$keyToInject]
                                                                    : ['enabled' => true]
                                                                ),
                                                                ($type ? ['type' => $type] : [])
                                                            ),
                                                            ['created'=>'DESC']
                                                        ],
                                                        function($p){
                                                            return $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1])['get']('rows');
                                                        }
                                                    )
                                            );
                                        }

                                        else {

                                            // .. Users
                                            $rows = $this->cacheFinder([
                                                    array_merge(
                                                        ['id' => (string)$item->getId()],
                                                        (
                                                            $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                            ? $joinWhere()[$keyToInject]
                                                            : ['enabled' => true]
                                                        )
                                                    ),
                                                    ['created'=>'DESC']
                                                ],
                                                function($p){
                                                    return $this->please->findLike($this->userRepo, $this->usersTable, $p[0], $p[1])['get']('rows');
                                                }
                                            );
                                        }

                                        /*if($rows && sizeof($rows)>0){
                                            foreach($rows as $i => $row){

                                                $id = $row->getId();

                                                if($verb == 'hasSibling' && $item->getType() == $row->getType() ){
                                                    $item->$keyToInject[$id] = $row;
                                                }
                                                if( $type ){
                                                    if($verb == 'hasMany'){
                                                        $item->$keyToInject[$id] = $row;
                                                    }
                                                }
                                                else {
                                                    if($verb == 'hasMany' && $item->getType() != $row->getType() ){
                                                        $item->$keyToInject[$id] = $row;
                                                    }
                                                }
                                            }
                                        }*/
                                    }

                                    $item->$keyToInject = !empty($rows) ? $rows : null;
                                }
                                
                                // hasOne || belongsTo
                                else if( $relation == 'inversedBy' ){

                                    $mappedByKey = $__['mappedByKey'];

                                    $keyToInjectVal = null;

                                    $info = json_encode($item->getInfo());
                                    preg_match("/(\")($mappedByKey)(\")(:)(\")([a-zA-Z0-9]+)(\")/", $info, $m1);
                                    preg_match("/(\")($keyToInject)(\")(:)(\")([a-zA-Z0-9]+)(\")/", $info, $m2);
                                    
                                    if(
                                        !empty($m1) || !empty($m2)
                                        ||
                                        sizeof($m1) == 6
                                        ||
                                        sizeof($m2) == 6
                                    ){

                                        $keyToInjectVal = isset($m1[6]) ? $m1[6] : $m2[6];

                                        $uKey = md5($item->getId().$mappedByKey);
                                        
                                        //if( !isset($this->$uKey) ){
                                            $this->$uKey = true;
                                            $item = $this->_handleKeyToInjectVal($__, $item, $keyToInjectVal, $keyToInject, $joinWhere, $mappedByKey);
                                        //}
                                    }
                                }

                                if(!is_null($item->$keyToInject)){
                                    $this->result[ $item->getId() ] = $item;
                                }

                                if( $notnull === true && (!$item->$keyToInject || empty($item->$keyToInject)) ){

                                    if($onNotnull == 'delete'){
                                        
                                        /* DELETE */
                                        unset($this->result[$item->getId()]);
                                        $this->please->delete([
                                            'finder' => function() use ($item) {
                                                return $item;
                                            }
                                        ]);
                                    }
                                    else {

                                        /* NO ACTION */
                                        $itemKey = $item->getId();
                                        if( !isset($this->bagToUnset[$itemKey]) ){
                                            $this->bagToUnset[$itemKey] = ['item' => $item ];
                                        }
                                        $this->bagToUnset[$itemKey]['keysToInject'][] = $keyToInject;
                                    }

                                }

                            }
                        }
                    }

                    // User
                    elseif( method_exists($item, 'getRoles') && array_key_exists('user', $foreignsKeysMapping) ){

                        $bag = $foreignsKeysMapping['user'];

                        foreach ($bag as $keyToInject => $__) {
                            
                            if(
                                ($keyToInject != '_acf' && $keyToInject != 'parent')
                                ||
                                ($keyToInject == '_acf' || $keyToInject == 'parent') && isset($item->getInfo()->$keyToInject)
                            ){

                                if( !isset($item->$keyToInject) ){ $item->$keyToInject = null; }

                                $xplodedKeyToInject = explode('|', $keyToInject);

                                $relation = $__['relationType'];
                                $type = $__['type'];
                                $verb = $__['verb'];
                                $notnull = $__['notnull'];
                                $onNotnull = $__['onNotnull'];

                                // hasMany
                                if( $relation == 'mappedBy' ){
                                    
                                    $inversedByKey = $__['inversedByKey'];

                                    // user hasMany users
                                    if( isset($item->getInfo()->$inversedByKey) ){

                                        if( $inversedByKey == '__user__' ){
                                            /*$rows = $this->cacheFinder(
                                                [array_merge([
                                                    'id' => $item->getInfo()->$inversedByKey
                                                ], (
                                                    $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                    ? $joinWhere()[$keyToInject]
                                                    : ['enabled' => true]
                                                )), ['created'=>'DESC']],
                                                function($p){
                                                    return $this->userRepo->findBy($p[0], $p[1]);
                                                }
                                            );*/
                                            $rows = $this->cacheFinder(
                                                [array_merge([
                                                    "info[$inversedByKey]" => (string)$item->getId()
                                                ], (
                                                    $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                    ? $joinWhere()[$keyToInject]
                                                    : ['enabled' => true]
                                                ), ($type ? ['type' => $type] : []) ), ['created'=>'DESC']],
                                                function($p){
                                                    return $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1])['get']('rows');
                                                }
                                            );
                                        }
                                    }

                                    // user hasMany bloggies
                                    else {
                                    
                                        $type = $inversedByKey;

                                        $rows = $this->cacheFinder(
                                            [array_merge([
                                                'user_id' => $item->getId()
                                            ], (
                                                $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                ? $joinWhere()[$keyToInject]
                                                : ['enabled' => true]
                                            ), ($type ? ['type' => $type] : []) ), ['created'=>'DESC']],
                                            function($p){
                                                return $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1])['get']('rows');
                                            }
                                        );
                                    }

                                    $item->$keyToInject = !empty($rows) ? $rows : null;
                                }

                                // hasOne || belongsTo
                                else if( $relation == 'inversedBy' ){

                                    $mappedByKey = $__['mappedByKey'];

                                    if( isset($item->getInfo()->$mappedByKey) ){

                                        // user hasMany users
                                        if( $inversedByKey == '__user__' ){
                                    
                                            // user hasOne || belongsTo user
                                            $row = $this->cacheFinder(
                                                [array_merge(
                                                    ["info[$mappedByKey]" => (string)$item->getId()],
                                                    (
                                                        $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                        ? $joinWhere()[$keyToInject]
                                                        : ['enabled' => true]
                                                    ),
                                                    ($type ? ['type' => $type] : [])
                                                ),
                                                ['created'=>'DESC']],
                                                function($p){
                                                    return $this->please->findOneLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1])['get']('rows');
                                                }
                                            );
                                        }

                                    } else {

                                        $type = $mappedByKey;
    
                                        // user hasOne bloggy
                                        $row = $this->cacheFinder(
                                            [array_merge((
                                                $joinWhere && array_key_exists($keyToInject, $joinWhere())
                                                ? $joinWhere()[$keyToInject]
                                                : ['enabled' => true]
                                            ), ($type ? ['type' => $type] : []) ), ['created'=>'DESC']],
                                            function($p){
                                                return $this->please->findOneLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1])['get']('rows');
                                            }
                                        );
                                    }

                                    $item->$keyToInject = !empty($row) ? $row : null;
                                }

                                if(!is_null($item->$keyToInject)){
                                    $this->result[ $item->getId() ] = $item;
                                }

                                if( $notnull === true && (!$item->$keyToInject || empty($item->$keyToInject)) ){

                                    if($onNotnull == 'delete'){
                                        
                                        /* DELETE */
                                        unset($this->result[$item->getId()]);
                                        $this->please->delete([
                                            'finder' => function() use ($item) {
                                                return $item;
                                            }
                                        ]);
                                    }
                                    else {

                                        /* NO ACTION */
                                        $itemKey = $item->getId();
                                        if( !isset($this->bagToUnset[$itemKey]) ){
                                            $this->bagToUnset[$itemKey] = ['item' => $item ];
                                        }
                                        $this->bagToUnset[$itemKey]['keysToInject'][] = $keyToInject;
                                    }
                                }
                            }
                        }
                    }

                    else {
                        return $originalsItems;
                    }
                }

                if( $originalType == 'object' ) {
                    foreach ($this->result as $k => $v) {
                        return $v;
                    }
                }
                else {
                    foreach($this->bagToUnset as $v){
                        if( isset($this->result[ $v['item']->getId() ]) ){
                            $this->_setToJoinIssue();
                            unset($this->result[$v['item']->getId()]);
                        }
                    }
                    if( empty($this->bagToUnset) ){
                        $this->please->unsetStorage('ACFJoinIssueRows', true);
                    }
                                
                    return array_values($this->result);
                }
            }
            return $originalsItems;
        }

        return $items;
    }

    /* docComment approach*/
    public function forkIt_( $class )
    {
        $reflector = new \ReflectionClass( $class );

        $docComments = null;
        
        foreach ($reflector->getProperties() as $prop) {

            $docComment = $prop->getDocComment();

            //define the regular expression pattern to use for string matching
            $pattern = "#(@[ForkORM][a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";

            preg_match_all($pattern, $docComment, $matches, PREG_PATTERN_ORDER);

            if( $matches ){
                foreach ($matches as $match) {
                    if( !empty($match) ){
                        foreach ($match as $m) {
                            $docComments[ $prop->getName() ][ md5($m) ] = $m;
                        }
                    }
                }
            }
        }
        if( $docComments ){

            $r_1 = $r_2 = $r_3 = [];

            foreach ($docComments as $propName => $docComments_) {

                foreach ($docComments_ as $docComment) {
                
                    $docComment = trim(str_replace('@ForkORM', '', $docComment));

                    $splitted = preg_split('/\s+/', $docComment);

                    if( in_array('mappedBy', $splitted) ){
                        $_ = $this->mappedBy($splitted);
                        $r_1[$splitted[0]][ $_->key ] = $_->data;
                    }
                    elseif (in_array('hasSiblings', $splitted)) {
                        $_ = $this->hasSiblings($splitted);
                        $r_2[$splitted[0]][ $_->key ] = $_->data;
                    }
                    else {
                        $_ = $this->inversedBy($splitted);
                        $r_3[$splitted[0]][ $_->key ] = $_->data;
                    }
                }
            }

            $this->mapping = array_merge_recursive($r_1, $r_2, $r_3);
        }
    }

    private function mappedBy( $splitted )
    {
        if( in_array($splitted[1], ['hasMany', 'hasSibling']) && (sizeof($splitted) === 6 || sizeof($splitted) === 7) ){

            $collectionsNames=[];
            $xplodedCollectionsNames = explode('||', $splitted[3]);
            if( $xplodedCollectionsNames ){
                foreach ($xplodedCollectionsNames as $CN) {
                    $collectionsNames[] = str_replace('@', '', $CN);
                }
            }

            $keyXploded = explode('|', $splitted[2]);

            //dd( $keyXploded, $splitted );
            return (object)[
                'key' => $keyXploded[0],
                'data' => [
                    //'collectionsNames' => str_replace('@', '', $splitted[3]),
                    'collectionsNames' => $collectionsNames,
                    'inversedByKey'    => str_ireplace('#', '', $splitted[5]),
                    'relationType'   => 'mappedBy',
                    'verb' => $splitted[1],
                    'type' => isset($splitted[6]) ? str_ireplace('--type=', '', $splitted[6]) : null,
                    'notnull' => isset($keyXploded[1]) ? true : '',
                    'onNotnull' => isset($keyXploded[2]) ? $keyXploded[2] : ''
                ]
            ];
        }
        return null;
    }

    private function hasSiblings( $splitted )
    {
        if( sizeof($splitted) === 7 ){

            $collectionsNames=[];
            $xplodedCollectionsNames = explode('||', $splitted[3]);
            if( $xplodedCollectionsNames ){
                foreach ($xplodedCollectionsNames as $CN) {
                    $collectionsNames[] = str_replace('@', '', $CN);
                }
            }

            $siblingscollectionsNames=[];
            $xplodedSiblingscollectionsNames = explode('||', $splitted[6]);
            if( $xplodedSiblingscollectionsNames ){
                foreach ($xplodedSiblingscollectionsNames as $CN) {
                    $siblingscollectionsNames[] = str_replace('@', '', $CN);
                }
            }

            return (object)[
                'key' => $splitted[2],
                'data' => [
                    //'collectionsNames' => str_replace('@', '', $splitted[3]),
                    'collectionsNames' => $collectionsNames,
                    'inversedByKey' => $splitted[5],
                    //'siblingscollectionsNames' => str_replace('@', '', $splitted[6]),
                    'siblingscollectionsNames' => $siblingscollectionsNames,
                    'siblingsKey' => $splitted[0], // 
                ]
            ];
        }
        return null;
    }

    private function inversedBy( $splitted )
    {
        if( isset($splitted[1]) && in_array($splitted[1], ['belongsTo', 'hasOne']) && (sizeof($splitted) === 6  || sizeof($splitted) === 7) ){

            $collectionsNames=[];
            $xplodedCollectionsNames = explode('||', $splitted[3]);
            if( $xplodedCollectionsNames ){
                foreach ($xplodedCollectionsNames as $CN) {
                    $collectionsNames[] = str_replace('@', '', $CN);
                }
            }

            $keyXploded = explode('|', $splitted[2]);
            //dump( $keyXploded, $splitted );
            return (object)[
                'key' => $keyXploded[0],
                'data' => [
                    //'collectionsNames' => str_replace('@', '', $splitted[3]),
                    'collectionsNames' => $collectionsNames,
                    'mappedByKey' => str_ireplace('#', '', $splitted[5]),
                    'relationType'   => 'inversedBy',
                    'verb' => $splitted[1],
                    'type' => isset($splitted[6]) ? str_ireplace('--type=', '', $splitted[6]) : null,
                    'notnull' => isset($keyXploded[1]) ? true : '',
                    'onNotnull' => isset($keyXploded[2]) ? $keyXploded[2] : ''
                ]
            ];
        }
        return null;
    }

    private function getSiblings($item, $__, $bundle)
    {
        $siblingsIDs = null;
        $allSiblings = null;

        foreach ($__['collectionsNames'] as $cn) {

            foreach ($__['siblingscollectionsNames'] as $scn) {

                $collection = $this->please->getStorage($scn . 'Collection', $bundle);

                $siblingsCollection = $this->please->getStorage($scn . 'Collection', $bundle);

                if( $item->getEnabled() == true ){

                    $inversedByKey = $__['inversedByKey'];
                    
                    $siblingsIDs = $this->_getSiblings($siblingsCollection, $item->getId(), $inversedByKey);
                    $siblingsIDs[] = $item->getId();
                }
        
            }
        }

        if( $siblingsIDs ){
        
            foreach ($siblingsIDs as $siblingID) {

                foreach ($collection as $collect) {

                    $siblingsKey = $__['siblingsKey'];

                    if( 
                        $collect->getEnabled() == true 
                        &&
                        isset($collect->getInfo()->$siblingsKey)
                        &&
                        (int)$collect->getInfo()->$siblingsKey == $siblingID 
                    ){
                        $allSiblings[] = $collect;
                    }
                }
            }
        }
                
        return $allSiblings;
    }

    private function _getSiblings($collection, $parent_id, $inversedByKey)
    {
        $bag = null;

        foreach ($collection as $collect) {
            if (
                isset($collect->getInfo()->$inversedByKey)
                &&
                (int)$collect->getInfo()->$inversedByKey == $parent_id
            ) {
                $bag[] = $collect->getId();
            }
        }
        return $bag;
    }

    private function _injectMappedCollection($mappedByKey, $item, $collection, $isMappedByVers2, $isMappedByVers3)
    {
        $inversedCollection = [];

        foreach ($collection as $collect) {
            
            if( 
                $isMappedByVers2 === false
                && 
                $isMappedByVers3 === false
                && 
                !is_null($collect->getInfo()) 
                && 
                isset($collect->getInfo()->$mappedByKey) && $item->getId() == (int) $collect->getInfo()->$mappedByKey 
            ){
                $inversedCollection[] = $collect;
            }
            else {
                if( $isMappedByVers2 === true ){
                    if( in_array($collect->getId(), $item->getInfo()->$mappedByKey) ){
                        $inversedCollection[] = $collect;
                    }
                }
                else {

                    // lets gets foreignsKeys
                    $acf = $item->getInfo()->acf;
                    foreach ($acf as $d) {
                        if( array_key_exists($mappedByKey, (array)$d) ){
                            if( (is_array($d->$mappedByKey) || is_object($d->$mappedByKey)) ){
                                foreach ($d->$mappedByKey as $id) {
                                    if($collect->getId() == (int) $id){
                                        $inversedCollection[] = $collect;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $inversedCollection;
    }

    private function _setToJoinIssue()
    {
        $this->please->setStorage([
            'ACFJoinIssueRows' => [
                'content' => $this->bagToUnset
            ]
        ], true);
        return true;
    }

    private function _handleKeyToInjectVal($__, $item, $keyToInjectVal, $keyToInject, $joinWhere, $mappedByKey)
    {
        if( $keyToInjectVal !== null ){

            $by = array_merge([
                'id' => $keyToInjectVal
            ], (
                $joinWhere && array_key_exists($keyToInject, $joinWhere())
                ? $joinWhere()[$keyToInject]
                : ['enabled' => true]
            ));

            //dump($by);

            if( is_string($keyToInjectVal) &&  is_string($mappedByKey) ){

                $querySql = md5($keyToInjectVal . ($mappedByKey !== '__user__'));

                if( !isset($this->$querySql) ){

                    if(
                        $mappedByKey == '__user__'
                        ||
                        (sizeof($__['collectionsNames']) == 1 && ($__['collectionsNames'][0] == '__Users' || $__['collectionsNames'][0] == 'Users'))
                    ){
                        $rows = $this->cacheFinder(
                            [$by, ['created'=>'DESC']],
                            function($p){
                                return $this->please->findOneLike($this->userRepo, $this->usersTable, $p[0], $p[1]);
                                //return $this->userRepo->findOneBy($p[0], $p[1]);
                            }
                        )['get']('rows');
                        
                        $this->$querySql = $rows;
                    }
                    else {
                        $rows = $this->cacheFinder(
                            [$by, ['created'=>'DESC']],
                            function($p){
                                return $this->please->findOneLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1]);
                                //return $this->bloggyRepo->findOneBy($p[0], $p[1]);
                            }
                        )['get']('rows');

                        $this->$querySql = $rows;
                    }

                    $item->$keyToInject = !empty($this->$querySql) ? $this->$querySql : null;
                }
                else {
                    
                    $item->$keyToInject = !empty($this->$querySql) ? $this->$querySql : null;
                }
                    
            }
        }
        else {

            $query = $this->cacheFinder(
                [array_merge([
                    "info[$mappedByKey]" => (string)$item->getId()
                ], (
                    $joinWhere && array_key_exists($keyToInject, $joinWhere())
                        ? $joinWhere()[$keyToInject]
                        : ['enabled' => true]
                    )
                ), ['created'=>'DESC']],
                function($p){
                    return $this->please->findOneLike($this->bloggyRepo, $this->bloggiesTable, $p[0], $p[1]);
                }
            );
            
            $querySql = $query['getSql']();

            if( !isset($this->$querySql) ){

                $rows = $query['get']('rows');

                // lets cache rows
                $this->$querySql = !empty($rows) ? $rows : null;
                
                $item->$keyToInject = $this->$querySql;
            }
            else {
                $item->$keyToInject = !empty($this->$querySql) ? $this->$querySql : null; //<--- contain rows cached before
            }
        }

        return $item;
    }

    public function cacheIDS( $sql )
    {   
        if( !isset($this->$sql) ){
            $IDs = !is_null($sql) ? $this->please->fetchAll($sql) : [];
            $this->$sql = $IDs;
        }
        return $this->$sql;
    }

    public function cacheFinder( $params, callable $finder )
    {
        $key = md5(serialize($params));

        if( !isset($this->$key) ){
            $this->$key = $finder( $params );
        }
        return $this->$key;
    }

    /* END OF forkDoctrineORM */
}
