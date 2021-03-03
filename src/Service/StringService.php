<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\__Html2TextService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StringService extends AbstractController
{
    protected $please;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
    }

    public function getSlug(string $string, $replacement='-', $lowercase=true): string
    {
        $string = $this->getAccentsLess($string);
        
        // replace non letter or digits by -
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);

        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);

        // remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', $string);

        // trim
        $string = trim($string, '-');

        // remove duplicate -
        $string = preg_replace('~-+~', '-', $string);

        // lowercase
        if( $lowercase ){
            $string = strtolower($string);
        }

        $string = str_replace('-', $replacement, $string);

        if (empty($string)) {
            return 'n-a';
        }

        return $string;
    }

    public function getTag(string $string): string
    {
        return trim(preg_replace('/\s+/', ', ', str_ireplace(',', '', $string)), ', ');
    }

    public function ellipsis(string $string, int $max = 100, string $append = '...'): string
    {
        if (strlen($string) <= $max) {
            return $string;
        }

        $out = substr($string, 0, $max);
        if (strpos($string, ' ') === false) {
            return $out . $append;
        }

        return preg_replace('/\w+$/', '', $out) . $append;

        //strlen($in) > 50 ? substr($in,0,50)."..." : $in;
    }

    public function getAccentsLess(string $string, $charset = 'utf-8'): string
    {
        $string = htmlentities($string, ENT_NOQUOTES, $charset);
        $string = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $string);
        $string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string); // pour les ligatures e.g. '&oelig;'
        $string = preg_replace('#&[^;]+;#', '', $string); // supprime les autres caractères
        $string = trim($string); // supprime les autres caractères
        return $string;
    }

    public function getArrayToString(array $array, $separateur = ' '): string
    {
        $string = '';
        foreach ($array as $valeur) {
            if (is_string($valeur)) {
                $string .= $valeur . $separateur;
            }
        }
        return trim($string);
    }

    public function getTrimAll(string $str, $what = null, $with = ' '): string
    {
        if ($what === null) {
            //  Character      Decimal      Use
            //  "\0"            0           Null Character
            //  "\t"            9           Tab
            //  "\n"           10           New line
            //  "\x0B"         11           Vertical Tab
            //  "\r"           13           New Line in Mac
            //  " "            32           Space
            $what = "\\x00-\\x20"; //all white-spaces and control chars
        }
        return trim(preg_replace("/[" . $what . "]+/", $with, $str), $what);
    }

    public function getSqlSearchQuery($params)
    {
        $params = array_merge([
            'keyword' => '',
            'columnsToSearchIn' => [],
            'operator' => 'LIKE',
            'condition' => 'AND',
            'handleSql' => null,
            'forEachKeywords' => function ($col, $keyWord) {
                return [
                    $col => "%$keyWord%"
                ];
            },
        ], $params);

        $sql = '';
        $parameters = [];
        $keywords = explode(' ', $params['keyword']);
        $handleSql = is_callable($params['handleSql']);
        for ($i = 0; $i < sizeof($keywords); $i++) {
            //$kw = htmlentities(trim($keywords[$i]));
            $keyWord = trim($keywords[$i]);
            if (strlen($keyWord) > 0) {
                if( !$handleSql ){
                    $sql .= ' (';
                }
                $j = 0;
                foreach ($params['columnsToSearchIn'] as $column) {

                    // lets make request on info with LIKE possible
                    // if( strpos($column, '[') !== false ){

                    //     $flag = ''
                    //     foreach($flags as $flag){
                    //         if( strpos($column, '|'.$flag) !== false ){
                    //             $flag = $flag;
                    //             $column = trim($column, '|'.$flag);
                    //         }
                    //     }
                    //     $column = trim($column, ']');
                    //     $colmunXpld = explode('[', $column);
                    //     if( sizeof($colmunXpld) == 2 ){
                            
                    //     }
                    //     dd($colmunXpld, $flag);
                    // }
                    // else {
                        
                    // }

                    $col = (str_ireplace('.', '_', $column)) . '_' . $i;
                    
                    if( $handleSql ){
                        $res = (object)$params['handleSql'](
                            (object)[
                                'column' => $column,
                                'col' => $col . '_'. rand(0, 999999),
                                'keyword' => $keyWord,
                                'params' => $params
                            ]
                        );
                        $sql .= $res->sql;
                        $parameters = array_merge($parameters, $res->parameter);
                    }
                    else {
                        $parameters = array_merge($parameters, $params['forEachKeywords']($col, $keyWord));
                        $sql .= $column . ' ' . $params['operator'] . ' :' . $col;
                        if ($j < sizeof($params['columnsToSearchIn']) - 1) {
                            $sql .= ' OR ';
                        }
                    }

                    $j++;
                }
                if( !$handleSql ){
                    $sql .= ') ';
                }
                if ($i < sizeof($keywords) - 1) {
                    $sql .= $params['condition'];
                }
            }
        };

        if( !$handleSql ){
            $sql = trim($sql, 'AND ');
        }
        return (object) [
            'sql' => $sql,
            'parameters' => $parameters,
        ];
    }

    public function getHtml2Text( $html = ''): string
    {
        $__Html2TextService = new __Html2TextService( $html );
        return str_ireplace('>', '', $__Html2TextService->getText());
    }

    public function getBootstrapAlert($message, $label = 'Error', $class = 'danger')
    {
        return "<p class='alert alert-$class'><b>$label:</b> $message.</p>";
    }

    public function getVisitorsCount()
    {
        $counterFile = $this->please->getBundleService('view')->getViewsDir("storage/counter.txt");
        if(!file_exists($counterFile)){file_put_contents($counterFile, '');}
        if(!isset($_SESSION['counter'])) { // It's the first visit in this session
            $handle = fopen($counterFile, "r");
            if(!$handle){
                return 0;
            }
            else {
                $counter = ( int ) fread ($handle, 20) ;
                fclose ($handle) ;
                $counter++ ;
                $handle = fopen($counterFile, "w" ) ;
                fwrite($handle, $counter);
                fclose ($handle);
                $_SESSION['counter'] = $counter;
            }

           } else { // It's not the first time, do not update the counter but show the total hits stored in session
                $counter = $_SESSION['counter'];
                return $counter;
           }
    }

    public function regexRange($from, $to, $clean = null)
    {
      if($from < 0 || $to < 0) {
        throw new Exception("Negative values not supported");
      }

      if($from > $to) {
        throw new Exception("Invalid range $from..$to, from > to");
      }

      $ranges = array($from);
      $increment = 1;
      $next = $from;
      $higher = true;

      while(true) {

        $next += $increment;

        if($next + $increment > $to) {
          if($next <= $to) {
            $ranges[] = $next;
          }
          $increment /= 10;
          $higher = false;
        }
        else if($next % ($increment*10) === 0) {
          $ranges[] = $next;
          $increment = $higher ? $increment*10 : $increment/10;
        }

        if(!$higher && $increment < 10) {
          break;
        }
      }

      $ranges[] = $to + 1;

      if( is_null($clean) || $clean === true ){
        //$regex = '(?:';
        //$regex = '([^>]*';
        $regex = '(';
      }
      else {
        //$regex = '/^(?:';
        $regex = '/^([^>]*';
      }

      for($i = 0; $i < sizeof($ranges) - 1; $i++) {
        $str_from = (string)($ranges[$i]);
        $str_to = (string)($ranges[$i + 1] - 1);

        for($j = 0; $j < strlen($str_from); $j++) {
          if($str_from[$j] == $str_to[$j]) {
            $regex .= $str_from[$j];
          }
          else {
            $regex .= "[" . $str_from[$j] . "-" . $str_to[$j] . "]";
          }
        }
        $regex .= "|";
      }

      $rule = substr($regex, 0, strlen($regex)-1) . ')';

      if( !is_null($clean) && $clean !== true ){
        $rule = $rule . '$/';
      }

      return $rule;
    }

    public function sqlRegexRange(string $column, array $columnsKeys, $closeQuotes=true, $concatCondition = 'AND')
    {
        $sql = '';
        if( $columnsKeys ){
            foreach ($columnsKeys as $jsonKey => $jsonValue) {
                $sql .= $column . ' REGEXP \'("'.$jsonKey.'":")'.$this->regexRange($jsonValue[0], $jsonValue[1]).''.($closeQuotes==true?'(")':'').'\'' . ' '.$concatCondition.' ';
            }
        }
        return trim($sql, ' '.$concatCondition.' ');
    }

    public function sqlRegex($table, $clauses, $parseArrayString=false)
    {
        $sql = '';
        $queryIndex = '_'.$table[0];

        foreach ($clauses as $k => $v) {

            // means a REGEX
            if( strpos($k, '[') !== false && strpos($k, ']') !== false ){
                $k = explode('[', trim($k, ']'));
                if(sizeof($k)===2){
                    if(is_callable($v)){
                        $sql .= $v("$queryIndex.$k[0] REGEXP '\"$k[1]\":") . ' OR ';
                    }
                    else if(is_array($v)){

                        $sql .= '(';

                        foreach ($v as $vv) {
                            if($parseArrayString){
                                $sql .= "$queryIndex.$k[0] REGEXP '\"$k[1]\":\"$vv\"' OR ";
                            }
                            else {
                                //$sql .= "$queryIndex.$k[0] REGEXP '(\"{$k[1]}\":)([)(\"{$vv}\")(])' OR ";
                                $sql .= "$queryIndex.$k[0] REGEXP '\"$k[1]\":\\\[.*\"$vv\".*\\\]' OR ";
                            }
                        }
                        
                        $sql .= ') AND ';
                    }
                    else {
                        //$sql .= "$queryIndex.$k[0] REGEXP '(\"{$k[1]}\":)(\"{$v}\")' OR ";
                        $sql .= $this->sqlLike($table, $clauses);
                    }
                }
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

        $trimed = trim(trim($sql, 'OR '), 'AND ');
        $trimed = str_ireplace(' OR )', ')', $trimed);
        return $trimed;
    }

    public function sqlLike($table, $like)
    {
        $sql = '';
        $queryIndex = '_'.$table[0];

        foreach ($like as $k => $v) {

            // means a LIKE
            if( strpos($k, '[') !== false && strpos($k, ']') !== false ){
                $k = explode('[', trim($k, ']'));
                if(sizeof($k)===2){
                    if(is_array($v)){
                        foreach ($v as $vv) {
                            $vv = is_string($vv) ? "$vv" : $vv;
                            $val = ($vv == null ? '%"'.$k[1].'":%' : '%"'.$k[1].'":'.(is_string($vv) ? '"'.$vv.'"' : $vv).'%'); // null means we dont have the exact value
                            $sql .= sprintf("$queryIndex.$k[0] LIKE '%s' OR ", $val);
                        }
                    }
                    else {
                        $v = is_string($v) ? "$v" : $v;
                        $val = ($v == null ? '%"'.$k[1].'":%' : '%"'.$k[1].'":'.(is_string($v) ? '"'.$v.'"' : $v).'%'); // null means we dont have the exact value
                        $sql .= sprintf("$queryIndex.$k[0] LIKE '%s' AND ", $val);
                    }
                }
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

        return trim(trim($sql, 'OR '), 'AND ');
    }

    public function sanitizeOutput($buffer)
    {

        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            //'/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            //''
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }

    public function encrypt($message, $key)
    {
        return urlencode(openssl_encrypt($message, "AES-128-ECB", $key));
    }

    public function decrypt($encrypted, $key)
    {
        return openssl_decrypt(urldecode($encrypted), "AES-128-ECB", $key);
    }

    /**
     * @param $n
     * @return string
     * Use to convert large positive numbers in to short form like 1K+, 100K+, 199K+, 1M+, 10M+, 1B+ etc
     */
    function numberFormatShort( $n ) {
        if ($n >= 0 && $n < 1000) {
            // 1 - 999
            $n_format = floor($n);
            $suffix = '';
        } else if ($n >= 1000 && $n < 1000000) {
            // 1k-999k
            $n_format = floor($n / 1000);
            $suffix = 'K+';
        } else if ($n >= 1000000 && $n < 1000000000) {
            // 1m-999m
            $n_format = floor($n / 1000000);
            $suffix = 'M+';
        } else if ($n >= 1000000000 && $n < 1000000000000) {
            // 1b-999b
            $n_format = floor($n / 1000000000);
            $suffix = 'B+';
        } else if ($n >= 1000000000000) {
            // 1t+
            $n_format = floor($n / 1000000000000);
            $suffix = 'T+';
        }

        return !empty($n_format . $suffix) ? $n_format . $suffix : 0;
    }
}
