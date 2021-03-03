<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Repository\BloggyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Markup;

class ACFService extends AbstractController
{
    private $please;
    private $fieldsGroupedCount = 0;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->bloggyRepo = $this->please->getBundleRepo('Bloggy');
        $this->bloggiesTable = $this->please->getTableName($table='bloggies');
        $this->dataService = $this->please->getBundleService('data');
        $this->stringService = $this->please->getBundleService('string');
    }

    public function getACFFormControls($fields, $item=null)
    {
        $controls = '';

        //define the regular expression pattern to use for string matching
        preg_match_all("#([a-zA-Z]+\s*[a-zA-Z0-9,()_].*)#", $fields, $matches, PREG_PATTERN_ORDER);

        if( $matches ){
            foreach ($matches[0] as $rowString) {

                //preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}]+)(\")/ui", $rowString, $matches1, PREG_PATTERN_ORDER);
                    //preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}0-9_>-]+)(\")/ui", $rowString, $matches1, PREG_PATTERN_ORDER);
                preg_match_all("/([a-z_]+)(=)(\")(.*?)(\")/ui", $rowString, $matches1, PREG_PATTERN_ORDER);
                if( $matches1 ){
                    foreach ($matches1[0] as $matchesString) {
                        //preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}]+)(\")/ui", $matchesString, $matches2, PREG_PATTERN_ORDER);
                            //preg_match_all("/([a-z_]+)(=)(\")([\s\p{L}0-9_>-]+)(\")/ui", $matchesString, $matches2, PREG_PATTERN_ORDER);
                            preg_match_all("/([a-z_]+)(=)(\")(.*?)(\")/ui", $matchesString, $matches2, PREG_PATTERN_ORDER);
                        if( $matches2 ){
                            foreach ($matches2[0] as $row) {
                                $row = explode('=', $row);
                                if( sizeof($row) == 2 ){

                                    if( $row[0] == 'type' ) {

                                        if( $row[1] == '"fields_group"' ){

                                            if($this->fieldsGroupedCount>0){
                                                $controls .= '</div></div>';
                                            }

                                            $controls .= '<div class="tile tile-full">
                                            <h3 class="tile-title">'.$this->_getFieldsGroupTitle($matches1[0]).'</h3>
                                            <hr>
                                            <div class="row">';

                                            $this->fieldsGroupedCount++;
                                        }
                                        $controls .= $this->_getACFFormControl($row[1], $matches1[0], $item);

                                    }

                                }
                            }
                        }
                    }
                }
            }
        }

        $controls .= '<script src="'. $this->please->getBundleService('url')->getCDN('swagg/plugins/acf/acf.js?v='. rand()).'"></script>';

        return new Markup($controls, 'UTF-8');
    }

    public function findBLOGGY($by, $orderBy=['created'=>'DESC'], $limit=10)
    {
        $verb = $limit == 1 ? 'findOneBy' : 'findBy';
        return $this->bloggyRepo->$verb($by, $orderBy, $limit);
    }

    public function findACF($type, $limit=10, $acfOrderBy=null, $doctrineOrderBy=['created'=>'DESC'], $ACFItemsCond=[])
    {   
        // lets check acf
        return $this->please->read([
            'finder' => function () use ($type) {
                return $this->bloggyRepo->findOneBy([ 'slug' => $type, 'type'=> 'acf' ]);
            },
            'onNotFound' => function () { return null; },
            'onFound' => function ($acf_parent) use ($type, $limit, $acfOrderBy, $doctrineOrderBy, $ACFItemsCond) {
                $page = (int)$this->please->getRequestStackQuery()->get('page') - 1 ?? 0;
                $page = $page < 0 ? 0 : $page;
                $LIMIT = $limit == -1 ? '' : " LIMIT ". $page * $limit .", $limit";
                $sql = "SELECT id FROM ".$this->bloggiesTable." b WHERE b.type = '$type' AND b.info LIKE '%\"_acf\":\"{$acf_parent->getId()}\"%' ORDER BY b.created DESC $LIMIT";

                $iDs = $this->dataService->cacheIDS($sql);
                $rows = $this->dataService->cacheFinder(
                    [ array_merge(['id' => $iDs], $ACFItemsCond) ],
                    function($p) use ($doctrineOrderBy) {
                        return $this->bloggyRepo->findBy($p[0], $doctrineOrderBy);
                    }
                );
                
                $rows = $iDs ? $rows : [];

                $rows = !empty($rows) ? ( $limit === 1 ? $rows[0] : $rows ) : [];


                /* if( $page ){
                    $rows = $this->please->readList([
                        'items' => function() use ($iDs) { return $this->bloggyRepo->findBy([ 'id' => $iDs ]); },
                        'view' => function ($items) { return $items; },
                        //'perPage' => $limit
                    ]);
                    dd($rows, $iDs);
                    if($rows) { $rows = $rows->getItems();}
                } */
                
                if( $rows && $acfOrderBy && $xploded = explode('.', $acfOrderBy) ){
                    if( sizeof($xploded) === 2 ){
                        $k1 = $xploded[0]; $k2 = $xploded[1];
                        $rows_ordered = $zeroRanked = [];
                        foreach ($rows as $row) {

                            if(!isset($row->getInfo()->$k2)){
                                $info = $row->getInfo()->acf;
                                if( isset($info->$k1->$k2) && (int)$info->$k1->$k2 !== 0 ){
                                    $rows_ordered[$info->$k1->$k2] = $row;
                                }
                                else {
                                    $zeroRanked[] = $row;
                                }
                            }else {
                                $info = $row->getInfo();
                                if( isset($info->$k2) && (int)$info->$k2 !== 0 ){
                                    $rows_ordered[$info->$k2] = $row;
                                }
                                else {
                                    $zeroRanked[] = $row;
                                }
                            }
                        }
                        /* until we use php for reOrdering */
                        /*for ($i=0; $i < sizeof($rows_ordered)+1; $i++) {
                            if(isset($rows_ordered[$i])){
                                $rows_ordered_2[] = $rows_ordered[$i];
                            }
                        }*/
                        ksort($rows_ordered);/* php use for reOrdering :) */
                        $rows = array_merge($zeroRanked, $rows_ordered);
                    }
                    return $rows;
                }
                return $rows;
            },
        ]);
        //dd( $slug, $limit );
    }

    public function getACFField($acf, $field)
    {
        if( $acf && method_exists($acf, 'getInfo') ){
            $field = explode('.', $field);
            $info = $acf->getInfo();
            if(isset($info->acf)){
                foreach ($info->acf as $k => $v) {
                    if(isset($field[1])){
                        $v_ = $field[1];
                        if( $k == $field[0] && isset($v->$v_) ){
                            return $v->$v_;
                        }
                    }
                }
            }
        }
        return '';
    }
    
    public function acff($acf, $field)
    {
        return $this->getACFField($acf, $field);
    }

    public function getACFPostField($acf, $field)
    {
        if( $acf && method_exists($acf, 'getInfo') ){
            if( isset($acf->getInfo()->$field) ){
                return $acf->getInfo()->$field;
            }
        }
        return '';
    }

    public function acfpf($acf, $field)
    {
        return $this->getACFPostField($acf, $field);
    }

    private function _getFieldsGroupTitle($matches1)
    {
        foreach ($matches1 as $m) {
            if( strpos($m, 'label') !== false ){
                $trim = trim($m, 'label');
                return trim($trim, '="');
            }
        }
        return 'Groupe de champs';
    }

    private function _getACFFormControl($type, $rows, $item)
    {
        $fields = $this->_getFields($rows);

        $propName = 
              empty($fields->acf->acf) && empty($fields->acf->acfname)
            ? $fields->acf->name
            : (!empty($fields->acf->acfname) ? $fields->acf->acfname : $fields->acf->acf);
        
        if(!isset($this->groupName)){
            $this->groupName = $propName;
        }
        if( $fields->acf->type == 'fields_group' ){
            $this->groupName = $fields->acf->name;
        }

        $value = $this->_getValue($item, $fields) ?? $fields->acf->value;

        $attrs = (object)[
            'clsNames' => (object)[
                'select' => 'class="select2-basic"'
            ],
            'multiple' => $fields->acf->multiple == 'true' ? 'multiple="multiple"':'',
            'dataSelectOption' => is_array($value) ? 'data-select-option="'.htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8').'"' : 'data-select-option="'.$value.'"'
        ];

        /*$name = 'name="info[acf]['. $this->groupName .']['. (!empty($fields->acf->acf) ? (strpos($fields->acf->name, 'name__')!==false ? $fields->acf->acf : $fields->acf->name ) : $fields->acf->name) .']'.(!empty($fields->acf->acf) && $fields->acf->acftype=='checkbox'?'[]':'').'"';
        $em = $this->groupName . '.' . (!empty($fields->acf->acf) ? (strpos($fields->acf->name, 'name__')!==false ? $fields->acf->acf : $fields->acf->name ) : $fields->acf->name);*/
        
        // lets ensure retrocompatibility
        $fields->acf->acftype = $fields->acf->controltype !== '' ? $fields->acf->controltype : $fields->acf->acftype;

        $name = 'name="info[acf]['. $this->groupName .']['. $propName .']'.(
            !empty($fields->acf->acf) && $fields->acf->acftype=='checkbox'
            ?'[]'
            : $fields->acf->multiple=='true' ? '[]' : ''
        ).'"';

        $em = $this->groupName . '.' . $propName;

        switch (trim($type, '"')) {
            case 'text':
            case 'email':
            case 'number':
            case 'range':
            case 'date':
                    $control = '
                        <div data-col class="col-md-'. $fields->acf->col .'">
                            <div class="field field-'. $fields->acf->type .' '. (empty($value) ? 'has-empty-val' : '') .'">
                                <input '. ($fields->acf->required=='true'?'required':'') .' 
                                    min="'. $fields->acf->min .'" 
                                    max="'. $fields->acf->max .'" 
                                    type="'. $fields->acf->type .'" 
                                    '.$name.'
                                    value="'.$value.'" />
                                <label>'. $fields->acf->label .'</label>
                                <button data-js="ACF={click:emptyFieldValue}" type="button" class="btn-reset btn btn-xs bttn-radius-deep"><i class="fa fa-close"></i></button>
                            </div>
                            <em>'. $em .'</em>
                        </div>
                    ';
                break;
            case 'textarea':
                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-textarea">
                            <textarea '. ($fields->acf->required=='true'?'required':'') .' 
                                '.$name.'
                                rows="'. $fields->acf->rows .'">'.$value.'</textarea>
                            <label>'. $fields->acf->label .'</label>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                ';
                break;
                    $control = '
                        <div data-col class="col-md-'. $fields->acf->col .'">
                            <div class="field">
                                <input '. ($fields->acf->required=='true'?'required':'') .' 
                                    min="'. $fields->acf->min .'" 
                                    max="'. $fields->acf->max .'" 
                                    type="'. $fields->acf->type .'" 
                                    '.$name.'
                                    value="'.$value.'" />
                                <label>'. $fields->acf->label .'</label>
                            </div>
                            <em>'. $em .'</em>
                        </div>
                    ';
                break;
            case 'text-rich':
                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-text-rich">
                            <textarea class="trumbowyg" '. ($fields->acf->required=='true'?'required':'') .' '.$name.' >'.$value.'</textarea>
                            <label>'. $fields->acf->label .'</label>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                    <style>
                        body.trumbowyg-body-fullscreen .main--header, 
                        body.trumbowyg-body-fullscreen .main--footer 
                        {display:none}
                    </style>
                    <script>
                    $(function(){
                        __.trumbowyg({
                            el: $(".trumbowyg")
                        });
                    })
                    </script>
                ';
                break;
            case 'image':
            case 'file':
                $exploded = explode('.', $value);
                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-image" data-is-image="true">
                            <input data-onpageloaded="click" 
                                data-js="ACF={click:fileControl__previewPicked}" type="hidden"
                                data-info='. json_encode([ 'extension' => end($exploded), 'extended_filename' => $value ]) .'
                                name="info[acf]['. $this->groupName .']['. htmlspecialchars($fields->acf->name) .']" 
                                value="'.htmlspecialchars($value).'" />
                            <label>'. $fields->acf->label .'</label>
                            <div data-js="ACF={click:fileControl__getLibrary}" class="widget-preview-area is-empty">
                                <a data-js="ACF={click:fileControl__removeFile}" title="Retirer"><i class="fa fa-trash"></i></a>
                            </div>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                ';
                break;
            case 'icon':
                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-icon">
                            <input data-onpageloaded="click" 
                                data-js="ACF={click:iconControl__previewPicked}" 
                                type="hidden" 
                                name="info[acf]['. $this->groupName .']['. htmlspecialchars($fields->acf->name) .']" 
                                value="'.htmlspecialchars($value).'" />
                            <label>'. $fields->acf->label .'</label>
                            <div data-js="ACF={click:iconControl__getLibrary}" class="widget-preview-area is-empty">
                                <a data-js="ACF={click:iconControl__removeIcon}" title="Retirer"><i class="fa fa-trash"></i></a>
                            </div>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                ';
                break;
            case 'page':
                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-page">
                            <select '.$name.' '.$attrs->clsNames->select.' '.$attrs->dataSelectOption.' '.$attrs->multiple.'>
                                '. $this->please->getBundleService('post')->getTree() .'
                            </select>
                            <input type="hidden" name="info[parent]" value="'.$value.'">
                            <label>'. $fields->acf->label .'</label>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                ';
                break;
            case 'post':

                if( !isset($this->postOptions) ){
                    
                    $this->postOptions = '<option value>AUCUN</option><option disabled></option>';

                    $posts = $this->please->findLike($this->bloggyRepo, $this->bloggiesTable, [
                        'type' => explode('|', $fields->acf->posttype),
                        'info[_acf]' => null // null means we dont have the exact value
                    ])['get']('rows');
                    if($posts){
                        foreach($posts as $post){
                            $this->postOptions .= '<option value="'.$post->getId().'">[[[ '.strtoupper($post->getType()).' ]]] -- '.$this->acfpf($post, 'title').'</option>';
                        }
                    }
                }

                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-page">
                            <select '.$name.' '.$attrs->clsNames->select.' '.$attrs->dataSelectOption.' '.$attrs->multiple.'>
                                '. $this->postOptions .'
                            </select>
                            <label>'. $fields->acf->label .'</label>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                ';
                break;
            case 'acf':
                    $control = $acf_view = $acf_label = '';
                    // lets get acf info
                    $acf = $this->findACF($fields->acf->acf, $limit=-1);
                    if( $acf ){
                        $acfParentInfo = $this->bloggyRepo->findOneBy(['slug'=>$fields->acf->acf, 'type'=>'acf'])->getInfo();

                        //dd($fields);
                        switch (
                            isset($fields->acf->acftype) && !empty($fields->acf->acftype)
                            ? $fields->acf->acftype
                            : ($fields->acf->controltype ?? 'select')
                        ) {
                            case 'select':
                                    $options = $fields->acf->required !== 'true' ? '<option value="null" selected>AUCUN</option><option disabled></option>' : '';
                                    /*foreach (is_array($value) && is_empty($value) ? $value : $acf as $d) {
                                        $options .= '<option '. ($d->getId()==$value?'selected':'') .' value="'. $d->getId() .'">'. $d->getInfo()->title .'</option>';
                                    }*/
                                    foreach ($acf as $d) {
                                        $options .= '<option '. ($d->getId()==$value?'selected':'') .' value="'. $d->getId() .'">'. $d->getInfo()->title .'</option>';
                                    }
                                    $acf_view = '<select data-acf="true" '.$name.' '.$attrs->clsNames->select.' '. ($fields->acf->required=='true'?'required':'') .' '.$attrs->multiple.'>
                                                    '. $options .'
                                                </select>';
                                    $control = '
                                        <div data-col class="col-md-'. $fields->acf->col .'">
                                            <div class="field">'. $acf_view .'<label>'. (
                                                $fields->acf->label !== 'Label'
                                                ? $fields->acf->label
                                                : $acfParentInfo->title_singular
                                            ) .'</label></div>
                                            <em>'.$em.'</em>
                                        </div>
                                    ';
                                break;
                            case 'checkbox':
                            case 'radio':
                                    $inputs = '';
                                    foreach ($acf as $d) {

                                        $checkedAttr = '';

                                        if(is_array($value)){
                                            if(in_array($d->getId(), $value)){
                                                $checkedAttr = 'checked';
                                            }
                                        }
                                        else {
                                            if($d->getId() == (int)$value){
                                                $checkedAttr = 'checked';
                                            }
                                        }

                                        $inputs .= '<div data-col class="col-sm-4 col-md-3"><label class="field field-'.$fields->acf->acftype.' acf-field '.($fields->acf->acftype=='checkbox'?'checkbox':'radio').'">
                                                        <input 
                                                            '.$name.' '. ($fields->acf->required=='true'?'required':'') .'
                                                            type="'. $fields->acf->acftype .'" 
                                                            '.$name.' 
                                                            '.$checkedAttr.' 
                                                            value="'.$d->getId().'" />
                                                        <b>'. $d->getInfo()->title .'</b>
                                                    </label></div>';
                                    }
                                    $acf_view = $inputs;
                                    $acf_label = $fields->acf->acftype == 'checkbox' ? $acfParentInfo->title : $acfParentInfo->title_singular;

                                    $control = '
                                        <div data-col class="col-md-'. $fields->acf->col .'">
                                            <div class="acf-label">'.($fields->acf->label == 'Label' ? $acf_label : $fields->acf->label).'</div>
                                            <div class="mdl-textfield-wrapper"><div class="row">'. $acf_view .'</div></div>
                                            <em class="em">'.$em.'</em>
                                        </div>
                                    ';
                                break;
                            
                            default:
                                # code...
                                break;
                        }

                        if( $propName == 'parent' ){
                            $control .= '<input type="hidden" name="info[parent]" value="'.$value.'">';
                        }
                    }
                break;
            case 'country':
                $control = '
                    <div data-col class="col-md-'. $fields->acf->col .'">
                        <div class="field field-country">
                            <select '.$name.' '.$attrs->clsNames->select.' '.$attrs->dataSelectOption.' '.$attrs->multiple.'>
                                '. $this->please->getCountriesOptions() .'
                            </select>
                            <label>'. $fields->acf->label .'</label>
                        </div>
                        <em>'.$em.'</em>
                    </div>
                ';
                break;
            case 'user':

                    $users = $this->please->getStorage('___UsersCollection', true);

                    $control = $acf_view = $acf_label = '';
                    // lets get acf info
                    $acf = $this->findACF($fields->acf->acf, $limit=-1);
                    if( $users ){

                        switch ($fields->acf->controltype) {
                            case 'select':
                                    $options = '';
                                    foreach ($users as $u) {
                                        $options .= '<option '. ($u->getId()==$value?'selected':'') .' value="'. $u->getId() .'">'. $this->please->getUserIdentRoleLess($u) .'</option>';
                                    }
                                    $acf_view = '<select '.$name.' '.$attrs->clsNames->select.' '.$attrs->dataSelectOption.' '. ($fields->acf->required=='true'?'required':'') .' '.$attrs->multiple.'>
                                                    '. $options .'
                                                </select>';
                                    $control = '
                                        <div data-col class="col-md-'. $fields->acf->col .'">
                                            <div class="field">'. $acf_view .'<label>'. ($fields->acf->label ?? 'Utilisateurs') .'</label></div>
                                            <em>'.$em.'</em>
                                        </div>
                                    ';
                                break;
                            case 'checkbox':
                            case 'radio':
                                    $inputs = '';
                                    foreach ($users as $u) {

                                        $checkedAttr = '';

                                        if(is_array($value)){
                                            if(in_array($u->getId(), $value)){
                                                $checkedAttr = 'checked';
                                            }
                                        }
                                        else {
                                            if($u->getId() == (int)$value){
                                                $checkedAttr = 'checked';
                                            }
                                        }

                                        $inputs .= '<div data-col class="col-sm-4 col-md-3"><label class="field field-'.$fields->acf->acftype.' acf-field '.($fields->acf->acftype=='checkbox'?'checkbox':'radio').'">
                                                        <input 
                                                            '.$name.' '. ($fields->acf->required=='true'?'required':'') .'
                                                            type="'. $fields->acf->acftype .'" 
                                                            '.$name.' 
                                                            '.$checkedAttr.' 
                                                            value="'.$u->getId().'" />
                                                        <b>'. $this->please->getUserIdentRoleLess($u) .'</b>
                                                    </label></div>';
                                    }
                                    $acf_view = $inputs;
                                    $acf_label = $fields->acf->acftype == 'checkbox' ? ($fields->acf->label ?? 'Utilisateurs') : ($fields->acf->label ?? 'Utilisateur');

                                    $control = '
                                        <div data-col class="col-md-'. $fields->acf->col .'">
                                            <div class="acf-label">'.($fields->acf->label == 'Label' ? $acf_label : $fields->acf->label).'</div>
                                            <div class="mdl-textfield-wrapper"><div class="row">'. $acf_view .'</div></div>
                                            <em class="em">'.$em.'</em>
                                        </div>
                                    ';
                                break;
                            
                            default:
                                # code...
                                break;
                        }
                    }
                ;
                break;
            default:
                    return '';
                break;
        }
        return $control ?? '';
    }

    private function _getFields($rows)
    {
        foreach ($rows as $row) {
            $row = explode('=', $row);
            if(sizeof($row)==2){
                $left_hand = $row[0]; $right_hand = $row[1];
                switch ($left_hand) {
                    case 'type'     :  $type    = $right_hand; break;
                    case 'name'     :  $name    = $right_hand; break;
                    case 'label'    : $label    = $right_hand; break;
                    case 'min'      :   $min    = $right_hand; break;
                    case 'max'      :   $max    = $right_hand; break;
                    case 'rows'     :  $rows_   = $right_hand; break;
                    case 'col'      :   $col    = $right_hand; break;
                    case 'required' : $required = $right_hand; break;
                    case 'acf'      : $acf      = $right_hand; break;
                    case 'acftype'  : $acftype  = $right_hand; break;
                    case 'acfname'  : $acfname  = $right_hand; break;
                    case 'value'    : $value  = $right_hand; break;
                    case 'controltype' : $controltype = $right_hand; break;
                    case 'nullable' : $nullable = $right_hand; break;
                    case 'multiple' : $multiple = $right_hand; break;
                    case 'posttype' : $posttype = $right_hand; break;
                    default: break;
                }
            }
        }
        return (object)[
            'acf' => (object)[
                'type' => trim($type ?? 'text', '"'),
                'name' => trim($name ?? 'name__' . uniqid(), '"'),
                'label' => trim($label ?? 'Label', '"'),
                'min' => trim($min ?? '', '"'),
                'max' => trim($max ?? '', '"'),
                'rows' => trim($rows_ ?? 5, '"'),
                'col' => trim($col ?? 12, '"'),
                'required' => trim($required ?? false, '"'),
                'acf' => trim($acf ?? false, '"'),
                'acftype' => trim($acftype ?? false, '"'),
                'acfname' => trim($acfname ?? false, '"'),
                'controltype' => trim($controltype ?? false, '"'),
                'value' => trim($value ?? false, '"'),
                'nullable' => trim($nullable ?? false, '"'),
                'multiple' => trim($multiple ?? false, '"'),
                'posttype' => trim($posttype ?? false, '"'),
            ]
        ];
    }

    private function _getValue($item, $fields)
    {
        if( $item && method_exists($item, 'getInfo') && isset($item->getInfo()->acf) ) {
            foreach ($item->getInfo()->acf as $fieldGroupName => $arrValue) {
                    foreach ($arrValue as $key => $value) {
                        $uiKey = $fieldGroupName.$key;

                        if(
                            !isset($this->$uiKey)
                            &&
                            (
                                ($key == $fields->acf->acf || $key == $fields->acf->acfname)
                                ||
                                ($key == $fields->acf->name)
                            )
                        ){
                            $this->$uiKey = true;
                            return $value;
                        }

                        /*if( 
                            ( $key ==
                                !empty($fields->acf->acf) 
                                ? 
                                (
                                    !empty($fields->acf->acfname)
                                    ? $fields->acf->acfname
                                    : $fields->acf->acf
                                ) 
                                : !empty($fields->acf->acfname) ? $fields->acf->acfname : $fields->acf->name
                            ) 
                            && !isset($this->$uiKey)
                        ){
                            $this->$uiKey = true;
                            return $value;
                        }*/
                    }
            }
        }
        return null;
    }
}
