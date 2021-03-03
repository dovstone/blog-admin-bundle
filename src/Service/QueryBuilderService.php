<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\__Html2TextService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QueryBuilderService extends AbstractController
{
    protected $please;
    private $query;
    private $fromIndex = 0;
    private $select = 'SELECT ';
    private $columns = '';
    private $from = ' FROM ';
    private $table = '';
    private $innerJoinOn = '';
    private $where = '';
    private $foreignKeysTable = [];

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        $this->bloggyRepo = $this->please->getBundleRepo('Bloggy');
        $this->relationsMap = [
            'marque belongsTo categorie',
            'marque hasMany order',
            'order belongsTo categorie'
        ];
    }

    public function select($columns='*')
    {
        dd($table, $clauses);
        return $this;
    }

    public function query($table, $clauses=[])
    {
        if( $table == 'B' ){
            $t = 'Bloggies';
        }
        else {
            $t = 'Users';
        }

        $lowered = strtolower($t);
        $table = $this->please->getTableName($lowered);
        $alias = $lowered[0];
        //
        //$this->table = $this->please->getTableName($lowered). " " .$alias;

        if( $t == 'Bloggies' ){

            $this->columns .= $this->getColumns($t, $alias);

            if($clauses){
                $this->commonCode($table, $alias, $clauses);
            }
        }
        else {
            $this->columns .= $this->getColumns($t, $alias);

            if($clauses){
                $this->commonCode($table, $alias, $clauses);
            }
        }

        $this->fromIndex++;

        return $this;
    }

    public function subQuery($table, $clauses)
    {
        $this->subQuery = true;

        $subQuery = (object)[
            'fromIndex' => 0,
            'select' => 'SELECT ',
            'columns' => '',
            'from' => ' FROM ',
            'table' => '',
            'innerJoinOn' => '',
            'where' => ''
        ];

        if( $table == 'B' ){
            $t = 'Bloggies';
        }
        else {
            $t = 'Users';
        }

        $lowered = strtolower($t);
        $table = $this->please->getTableName($lowered);
        $alias = $lowered[0];

        if( $t == 'Bloggies' ){

            $subQuery->columns .= ",    $alias.id as bId,
                                        $alias.slug as bSlug,
                                        $alias.type as bType,
                                        $alias.info as bInfo,
                                        $alias.user_id as bUserId,
                                        $alias.enabled as bEnabled,
                                        $alias.created as bCreated, ";

            $this->commonCode($table, $alias, $clauses, $subQuery);
        }
        else {
            $subQuery->columns .= ",    $alias.id as uId,
                                        $alias.roles as uRoles,
                                        $alias.password as uPassword,
                                        $alias.old_password as uOldPassword,
                                        $alias.username as uUsername,
                                        $alias.salt as uSalt,
                                        $alias.forgot_token as uForgotToken,
                                        $alias.mle as uMle,
                                        $alias.lastname as uLastname,
                                        $alias.firstname as uFirstname,
                                        $alias.contact as uContact,
                                        $alias.email as uEmail,
                                        $alias.birthdate as uBirthdate,
                                        $alias.location as uLocation,
                                        $alias.thumbnail as uThumbnail,
                                        $alias.validated as uValidated,
                                        $alias.enabled as uEnabled,
                                        $alias.created as uCreated,
                                        $alias.updated as uUpdated,
                                        $alias.username_slugged as uUsernameSlugged,
                                        $alias.info as uInfo, ";

            $this->commonCode($table, $alias, $clauses, $subQuery);
        }

        return $this;
    }

    private function getColumns($t, $alias)
    {
        return [
            'Bloggies' => ", $alias.id as bId,
                        $alias.slug as bSlug,
                        $alias.type as bType,
                        $alias.info as bInfo,
                        $alias.user_id as bUserId,
                        $alias.enabled as bEnabled,
                        $alias.created as bCreated, ",
            'Users' => ", $alias.id as uId,
                        $alias.roles as uRoles,
                        $alias.password as uPassword,
                        $alias.old_password as uOldPassword,
                        $alias.username as uUsername,
                        $alias.salt as uSalt,
                        $alias.forgot_token as uForgotToken,
                        $alias.mle as uMle,
                        $alias.lastname as uLastname,
                        $alias.firstname as uFirstname,
                        $alias.contact as uContact,
                        $alias.email as uEmail,
                        $alias.birthdate as uBirthdate,
                        $alias.location as uLocation,
                        $alias.thumbnail as uThumbnail,
                        $alias.validated as uValidated,
                        $alias.enabled as uEnabled,
                        $alias.created as uCreated,
                        $alias.updated as uUpdated,
                        $alias.username_slugged as uUsernameSlugged,
                        $alias.info as uInfo, "
        ][$t];
    }

    private function commonCode($table, $alias, $clauses, $subQuery=null)
    {
        if( is_null($subQuery) ){
            $this->table .= ($this->fromIndex > 0 ? " INNER JOIN " : "") . " $table $alias ";
            $this->innerJoinOn .= ($this->fromIndex > 0 ? " ON b.user_id = u.id " : "");
            $this->where .= ($this->fromIndex == 0 ? " WHERE " : "");
            $this->where .= $this->buildClause($alias, $clauses, null);
        }
        else {
            $subQuery->table .= ($subQuery->fromIndex > 0 ? " INNER JOIN " : "") . " $table $alias ";
            $subQuery->innerJoinOn .= ($subQuery->fromIndex > 0 ? " ON b.user_id = u.id " : "");
            $subQuery->where .= $subQuery->where. " " .$this->buildClause($alias, $clauses, $subQuery); 
        }
    }

    private function buildClause($alias, $clauses, $subQuery): void
    {
        is_null($subQuery) ? $this->where .= "(" : $subQuery->where .=  "(";

        foreach($clauses as $k => $v){
            
            if( $k === 'info' ) {
                foreach($v as $vv){

                    if( in_array(sizeof($vv), [2, 3, 4]) ){
                        $chainer = $vv[3] ?? ' AND ';
                        $prop = $vv[0];
                        //
                        $this->switchOperands([
                            'prop' => $prop,
                            'operand' => $vv[1],
                            'val' => (isset($vv[2]) && is_callable($vv[2])) ? $this->execCallable($vv[2]) : $vv[2] ?? null,
                            'alias' => $alias,
                            'isInfoProp' => true,
                            'subQuery' => $subQuery,
                        ]);
                        //
                        if( is_null($subQuery) ){
                            $this->where .= " $chainer ";
                        }
                        else {
                            $subQuery->where .=  " $chainer ";
                        }
                        //
                        $t = strtoupper($alias);
                        if( !isset($this->foreignKeysTable[$t]) ){
                            $this->foreignKeysTable[$t] = [];
                        }
                        $this->foreignKeysTable[$t][] = $prop;
                    }
                }
            }
            elseif( in_array(sizeof($v), [2, 3, 4]) ) {

                $chainer = $v[3] ?? ' AND ';
                //
                $this->switchOperands([
                    'prop' => $v[0],
                    'operand' => $v[1],
                    'val' => (isset($v[2]) && is_callable($v[2])) ? $this->execCallable( $v[2] ) : $v[2] ?? null,
                    'alias' => $alias,
                    'subQuery' => $subQuery
                ]);
                //
                if( is_null($subQuery) ){
                    $this->where .= " $chainer ";
                }
                else {
                    $subQuery->where .=  " $chainer ";
                }

            }
        }
        
        is_null($subQuery) ? $this->where .= ")" : $subQuery->where .=  ")";

        if($subQuery){
            $this->subQuery = $subQuery;
        }
    }

    private function switchOperands($p)
    {
        $p = (object)$p;
        $val = $p->val;
        $alias = $p->alias;
        $isInfoProp = isset($p->isInfoProp);
        $prop = $isInfoProp ? $p->prop : $alias.".".$p->prop;
        $subQuery = $p->subQuery;
        $str = '';

        $strServ = $this->please->getBundleService('string');

        // boolean
        if($val===false){  $val = 0; }

        switch ($p->operand) {
            case '=' : case 'like':

                    if( $isInfoProp ) {
                        if(is_array($val)){
                            foreach($val as $i => $vv){
                                if($i==0){              $str  = "("; }
                                                        $str .= "$alias.info LIKE '%\"$prop\":\"$vv%'";
                                if($i<sizeof($val)-1){  $str .= " OR "; }
                                if($i==sizeof($val)-1){ $str .= ")"; }
                            }
                        }
                        else {
                            $str = "$alias.info LIKE '%\"$prop\":\"$val%'";
                        }
                    }
                    else {

                        if(is_array($val)){
                            foreach($val as $i => $vv){
                                if($i==0){              $str  = "("; }
                                                        $str .= ($p->operand == '=') ? "$prop = '$vv'" : "$prop LIKE '%$vv%'";
                                if($i<sizeof($val)-1){  $str .= " OR "; }
                                if($i==sizeof($val)-1){ $str .= ")"; }
                            }
                        }
                        else {
                            $str = ($p->operand == '=') ? "$prop = '$val'" : "$prop LIKE '%$val%'";
                        }
                    }
                    
                break;

            case '!=' : case 'not like':

                    if( $isInfoProp ) {
                        $str = "$alias.info NOT LIKE '%\"$prop\":\"$val%'";
                    }
                    else {
                        $str = "$prop NOT LIKE '%$val%'";
                    }

                break;

            case 'in' :

                    $inChainer = $vv[3] ?? ' OR ';
                    $str = "(" . $this->loopValues("$alias.info", $prop, $val, $inChainer) . ")";
                    
                break;

            case 'not in' :

                    
                break;

            case 'is null' : //is null means is empty
                    if( $isInfoProp ) {
                        $str = "$alias.info LIKE '%\"$prop\":\"\"%'";
                    }
                    else {
                        $str = in_array($prop, ["$alias.user_id"]) ? "$prop is null" : "$prop LIKE '%\"\"%'";
                    }

                    $chainer = $vv[2] ?? ' AND ';
                break;

            case 'is not null' : //is not null means is not empty
                    
                    if( $isInfoProp ) {
                        $str = "$alias.info NOT LIKE '%\"$prop\":\"\"%'";
                    }
                    else {
                        $str = in_array($prop, ["$alias.user_id"]) ? "$prop is not null" : "$prop NOT LIKE '%\"\"%'";
                    }

                    $chainer = $vv[2] ?? ' AND ';
                break;

            case '<' : case '<=' : case '>' : case '>=' : 
                    
                    if( $isInfoProp ) {
                        
                        //$str = $alias.'.info '.$p->operand.' \''.$val.'\'';

                        $str = $strServ->sqlRegexRange($alias.'.info', [
                            $prop => [ '2020-09-13', '2020-09-14' ]
                        ], $closeQuotes=false);
                    }
                    else {
                        $str = $prop.' '.$p->operand.' \''.$val.'\'';
                    }

                    $chainer = $vv[2] ?? ' AND ';
                break;

            default:
                # code...
                break;
        }

        is_null($subQuery)
        ? $this->where .= $str
        : $subQuery->where .= $str
        ;
    }

    public function getQuery($dumpSql=false)
    {
        if( !isset($this->subQuery) ){
            $this->query = $this->select . $this->columns . $this->from . $this->table . $this->innerJoinOn . $this->where;
        }
        else {
            $subQuery = $this->subQuery;
            $this->query = $subQuery->select . $subQuery->columns . $subQuery->from . $subQuery->table . $subQuery->innerJoinOn . " WHERE ". $subQuery->where;
            unset($this->subQuery);
        }

        $this->query = str_ireplace('  AND  )(', ' AND (', $this->query);
        $this->query = str_ireplace('  AND  )', ($this->fromIndex > 1 ? "))" : ")"), $this->query);
        $this->query = str_ireplace('SELECT ,', 'SELECT ', $this->query);
        $this->query = str_ireplace(',  FROM', ' FROM', $this->query);
        $this->query = str_ireplace(', ,', ', ', $this->query);
        $this->query = str_ireplace(' AND ()', ')', $this->query);

        // lets cache result cause of getTotal() and getUnlimited()
        $this->unlimited = $this->please->fetchAll($this->query, [], \PDO::FETCH_CLASS);

        if( $dumpSql ){ dd($this->query); }
        else { return $this; }
    }

    public function getTotal()
    {   
        return count($this->unlimited);
    }

    public function orderBy($table, array $columns)
    {
        return $this;
    }

    public function getLimited($limit=50, $pageVar='page')
    {   
        $offset = ((int)$this->please->getRequestStackQuery()->get($pageVar, 1) - 1);
        $offset = $limit * ($offset < 0 ? 0 : $offset);
        $query = $this->query . " LIMIT $limit OFFSET $offset";
        $limited = $this->please->fetchAll($query, [], \PDO::FETCH_CLASS);

        if($limited && $this->foreignKeysTable){
            foreach($limited as $k => $row){

                $row = $this->convertRow($row);

                if( isset($row->bInfo) ){
                    $info = json_decode($row->bInfo);
                    if($info){
                        $this->findFK($row);
                    }
                }
            }
        }
        //
        $this->resetQuery();
        //
        return $limited;
    }

    public function getUnlimited()
    {   
        //
        $this->resetQuery();
        //
        return $this->unlimited;
    }

    public function getIds($dumpIDs=false)
    {
        $iDs = $this->please->fetchAll($this->query);
        if( $dumpIDs ){ dd($iDs); }
        else { return $iDs; }
    }

    public function getId($dumpId=false)
    {
        $iD = $this->please->fetchAll($this->query);
        $result = isset($iD[0]) ? (int)$iD[0] : null;
        if( $dumpId ){ dd($result); }
        else { return $result; }
    }

    public function pushFK($row)
    {
        if( $this->relationsMap ){
            $this->foreignKeysTable['B'] = [];
            foreach($this->relationsMap as $map){
                $v = preg_split('/\s+/', $map);
                if( sizeof($v) == 3 ){
                    $type = $v[0]; $verb = $v[1]; $fk = $v[2];
                    if( $row->bType == $type ){
                        $this->foreignKeysTable['B'][] = $fk;
                        $this->findFK($row);
                    }
                }
            }
        }
        return $row;
    }

    private function loopValues($alias, $prop, $values, $chainer)
    {   
        $subQuery = '(';
        if($values){
            foreach($values as $i => $v){
                $subQuery .= "$alias REGEXP '\"$prop\":\\\[.*\"$v\".*\\\]'";
                if( $i < sizeof($values)-1 ){ $subQuery .= " ".$chainer." "; }
            }
        }
        else {
            $subQuery .= "$alias REGEXP '\"$prop\":\\\[.*\"\".*\\\]'";
        }
        $subQuery .= ")";

        return $subQuery;
    }

    private function execCallable(callable $callable)
    {   
        //$this->query = $this->columns = $this->table = $this->innerJoinOn = $this->where = '';
        return $callable($this);
    }

    private function findFK($row)
    {
        foreach( $this->foreignKeysTable as $table => $fKeys ){
            $t = strtolower($table);
            foreach($fKeys as $fk){

                // belongsTo || hasOne
                preg_match("/(\")($fk)(\")(:)(\")([a-zA-Z0-9]+)(\")/", $row->bInfo, $m);
                
                // hasMany
                preg_match("/(\")($fk)(\")(:)(\[)([a-zA-Z0-9,\"]+)(\])/", $row->bInfo, $m2);

                $prop = $t . ucwords($fk);

                if(!empty($m)){
                    $id = $m[6];

                    $found = $this->subQuery('B',[
                        ['id', '=', $id],
                        ['enabled', '=', true]
                    ])->getQuery()->getLimited(1);

                    $row->$prop = $this->getCachedRows('bUid'.$id, 
                        isset($found[0])
                        ? $this->convertRow($found[0])
                        : null
                    );
                }
                elseif(!empty($m2)){
                    $ids = json_decode("[$m2[6]]");

                    $found = $this->subQuery('B',[
                        ['id', '=', $ids],
                        ['enabled', '=', true]
                    ])->getQuery()->getUnLimited();

                    $row->$prop = $this->getCachedRows('bUid'.$m2[6], $found);
                }
            }
        }
    }

    private function getCachedRows($key, $rows)
    {
        if( !isset($this->$key) ){
            $this->$key = $rows;
        }
        return $this->$key;
    }

    private function resetQuery()
    {
        $this->query;
        $this->fromIndex = 0;
        $this->select = 'SELECT ';
        $this->columns = '';
        $this->from = ' FROM ';
        $this->table = '';
        $this->innerJoinOn = '';
        $this->where = '';
        $this->foreignKeysTable = [];
    }

    private function convertRow($row)
    {
        if(isset($row->bId)){ $row->bId = (int)$row->bId; }
        if(isset($row->bUserId)){ $row->bUserId = (int)$row->bUserId; }
        if(isset($row->bEnabled) && $row->bEnabled == 1){ $row->bEnabled = true; }
        if(isset($row->bEnabled) && $row->bEnabled == 0){ $row->bEnabled = false; }
        if(isset($row->bCreated) && is_string($row->bCreated)){ $row->bCreated = new \DateTime($row->bCreated); }

        if(isset($row->uId)){ $row->uId = (int)$row->uId; }
        if(isset($row->uEnabled) && $row->uEnabled == 1){ $row->uEnabled = true; }
        if(isset($row->uEnabled) && $row->uEnabled == 0){ $row->uEnabled = false; }
        if(isset($row->uCreated) && is_string($row->bCreated)){ $row->uCreated = new \DateTime($row->uCreated); }

        if(isset($row->bUserId) && is_int($row->bUserId)){
            $row->bUser = $this->getCachedRows(
                'bUid'.$row->bUserId,
                $this->please->getBundleRepo('User')->find($row->bUserId)
            );
        }
        return $row;
    }
}
