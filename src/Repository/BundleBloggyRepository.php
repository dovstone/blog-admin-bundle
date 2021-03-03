<?php

namespace DovStone\Bundle\BlogAdminBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use DovStone\Bundle\BlogAdminBundle\Entity\Bloggy;
use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\DataService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Bloggy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bloggy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bloggy[]    findAll()
 * @method Bloggy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BundleBloggyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, PleaseService $please, DataService $dataService)
    {
        parent::__construct($registry, Bloggy::class);
        //
        $this->please = $please;
        
        $this->bloggiesTable = $this->please->getTableName($table='bloggies');
        //

        $ORM__Array = [ 'page', 'article' ];
        
        //lets get all acf children
        // that have "article" properties
        
        $r = $dataService->cacheFinder(
            [['info[_acf]' => null]],
            function($p) {
                return $this->please->findLike($this, $this->bloggiesTable, $p[0]);
            }
        );

        $acfAsArticles = $dataService->cacheFinder(
            [['id' => $r['getIds']()]],
            function($p) {
                return $this->findBy($p[0]);
            }
        );

        if( $acfAsArticles ){
            foreach ($acfAsArticles as $item) {
                $ORM__Array[] = $item->getType();
            }
        }

        $ORM__String = '';

        foreach ($ORM__Array as $val) {
            if( !isset($this->$val) ){
                $ORM__String .= $val . " hasOne parent @___Pages||@___ACF||@___ACFChildren||@___ACFChildren2||@___Users inversedBy parent\r\n";
                if( !in_array($val, ['page', 'article']) ){
                    $ORM__String .= $val . " hasOne _acf|notnull @___ACF inversedBy acf_\r\n";
                }
            }
            $this->$val = true;
        }

        // acf has acf
        $sql = "SELECT id FROM ".$this->bloggiesTable." WHERE info LIKE '%acftype%' OR info LIKE '%controltype%'";
        
        $ids = $dataService->cacheIDS($sql);

        if($ids){

            $r = $dataService->cacheFinder(
                [['id'=>$ids]],
                function($p) {
                    return $this->findBy($p[0]);
                }
            );

            foreach ($r as $acf) {
                $fields = $acf->getInfo()->fields;
                $parsed = $this->_parseFields($fields);
                if($parsed){
                    foreach ($parsed as $p) {
                        $ORM__String .= "{$acf->getSlug()} $p->relVerb $p->formatted{$p->notnull} @___ACF||@___ACFChildren||@___ACFChildren2||@___Users $p->relType $p->key\r\n";
                    }
                }
            }
        }

        $tableRelations = $dataService->cacheFinder(
            [['type' => 'table-relations']],
            function($p) {
                return $this->findOneBy($p[0]);
            }
        );

        if( $tableRelations ){
            $ORM__String .= str_ireplace('@tables', '@___Pages||@___ACF||@___ACFChildren||@___ACFChildren2||@___Users', $tableRelations->getInfo()->relations)."\r\n";
        }

        isset($_GET['dd']) ? dd($ORM__String) : '';

        $dataService->forkDoctrineORM($ORM__String, $reset = null, $bundle = true);
    }
    
    private function _parseFields($fields)
    { 
        $fieldsData = null;

        //define the regular expression pattern to use for string matching
        preg_match_all("#([a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#", $fields, $matches, PREG_PATTERN_ORDER);

        if( $matches ){
            foreach ($matches[0] as $rowString) {

                //preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}]+)(\")/ui", $rowString, $matches1, PREG_PATTERN_ORDER);
                preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}0-9_>-]+)(\")/ui", $rowString, $matches1, PREG_PATTERN_ORDER);
                if( $matches1 ){
                    foreach ($matches1[0] as $matchesString) {
                        //preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}]+)(\")/ui", $matchesString, $matches2, PREG_PATTERN_ORDER);
                        preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}0-9_>-]+)(\")/ui", $matchesString, $matches2, PREG_PATTERN_ORDER);
                        if( $matches2 ){
                            foreach ($matches2[0] as $row) {
                                $row = explode('=', $row);
                                if( sizeof($row) == 2 ){
                                    if( $row[0] == 'acf' || $row[0] == "controltype" ){
                                        $fieldsData[] = $this->_getACFValues($matches1[0], $matches1);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $fieldsData;
    }

    private function _getACFValues($data, $matches1)
    {
        $mixed = [];
        $v = [];
        $i = 0;

        $strServ = $this->please->getBundleService('string');

        foreach ($data as $d) {

            // key
            if( strpos($d, 'acf="')         !== false ){ $mixed[$i]['acf'] = trim(str_ireplace('acf="', '', $d), '"'); }
            if( strpos($d, 'type="user"')   !== false ){ $mixed[$i]['acf'] = "__user__"; }

            // notnull ?
            if( strpos($d, 'notnull')       !== false ){ $mixed[$i]['notnull'] = '|notnull'; } else { $mixed[$i]['notnull'] = ''; }

            // relType
            if( strpos($d, 'acftype="')     !== false ){ $mixed[$i]['type'] = trim(str_ireplace('acftype="', '', $d), '"'); }
            if( strpos($d, 'controltype="') !== false ){ $mixed[$i]['type'] = trim(str_ireplace('controltype="', '', $d), '"'); }
            
            // formatted ( runtime added dynamic key )
            if( strpos($d, 'acfname="')     !== false ){ $mixed[$i]['acfname__name'] = trim(str_ireplace('acfname="', '', $d), '"'); }
            if( strpos($d, 'name="')        !== false ){ $mixed[$i]['name__name'] = trim(str_ireplace('name="', '', $d), '"'); }

        }

        if($mixed){
            foreach($mixed as $m){

                $v['relVerb'] = ($m['type'] == 'checkbox') ? 'hasMany' : 'hasOne';
                $v['relType'] = ($m['type'] == 'checkbox') ? 'mappedBy' : 'inversedBy';
                
                $v['key'] = $m['acf'] ?? null;
                $v['notnull'] = $m['notnull'] ;// notnull means DELETION of the item to pop if prop(key) was not found when joined

                $formatted = lcfirst(str_ireplace(' ', '', ucwords(str_ireplace('-', ' ', $m['acfname__name'] ?? $m['name__name'] ?? $m['acf'] ))));
                $v['formatted'] = $strServ->getAccentsLess($formatted);
            }
        }

        /*
            if( strpos($d, 'acf="') !== false ){
                $key = trim($d, 'acf="');
                $v['key'] = $key;
                $v['formatted'] = lcfirst(str_ireplace(' ', '', ucwords(str_ireplace('-', ' ', $key))));
            }
            else if( strpos($d, 'controltype="') !== false ){
                dd($data);
                $key = trim($d, 'name="');
                $v['key'] = $key;
                $v['formatted'] = lcfirst(str_ireplace(' ', '', ucwords(str_ireplace('-', ' ', $key))));
            }


            if( strpos($d, 'acftype="') !== false ){
                $type = trim(trim($d, 'acftype='), '"');
                $v['relVerb'] = ($type == 'checkbox') ? 'hasMany' : 'hasOne';
                $v['relType'] = ($type == 'checkbox') ? 'mappedBy' : 'inversedBy';
            }
            else if( strpos($d, 'acfname="') !== false ){
                $type = trim(trim($d, 'acfname='), '"');
                $v['relVerb'] = ($type == 'checkbox') ? 'hasMany' : 'hasOne';
                $v['relType'] = ($type == 'checkbox') ? 'mappedBy' : 'inversedBy';
            }
            else if( strpos($d, 'controltype="') !== false ){
                $type = trim(trim($d, 'controltype='), '"');
                $v['relVerb'] = ($type == 'checkbox') ? 'hasMany' : 'hasOne';
                $v['relType'] = ($type == 'checkbox') ? 'mappedBy' : 'inversedBy';
            }*/

        //dump($v);

        return (object) $v;
    }
}