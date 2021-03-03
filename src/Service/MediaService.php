<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Service\__VerotUploadService;
use DovStone\Bundle\BlogAdminBundle\Service\__PhpHtmlCssJsMinifierService;
use DovStone\Bundle\BlogAdminBundle\Service\__ImgCompressorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Markup;


class MediaService extends AbstractController
{
    protected $previousContainer;
    //
    private $filesystem;
    private $urlService;
    private $__PhpHtmlCssJsMinifierService;
    private $__ImgCompressorService;

    public $please;
    public $uploadsDir;
    public $timeService;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->filesystem = new Filesystem();
        $this->timeService = $this->please->getBundleService('time');
        $this->urlService = $this->please->getBundleService('url');
        $this->uploadsDir = $this->please->previousContainer->get('kernel')->getProjectDir() . "/public/uploads";
        $this->themeDir = $this->please->previousContainer->get('kernel')->getProjectDir() . "/public/theme";
    }

    public function readDir(string $dir): string
    {
        $dir = $dir . DIRECTORY_SEPARATOR;

        $items = '';

        // Open a known directory, and proceed to read its contents
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..' && $file != 'index.php') {
                        if (filetype($dir . $file) == 'dir') {
                            $items .= $this->readDir($dir . $file);
                        } else {
                            $img = $dir . $file;
                            $img_prop = getimagesize($img);
                            $basename = basename($img);
                            $date = date("F d Y H:i:s", filectime($img));
                            $creation = $this->timeService->getFrenchDate($date, "D d M - H:i:s");
                            $thumb = $this->thumb($this->please->getBundleService('url')->getUrl($dir . $file));

                            $items .= '<li><a
                                        data-js="filesBrowser={click:showFileDetails}"
                                        data-image-width="' . $img_prop[0] . '"
                                        data-image-height="' . $img_prop[1] . '"
                                        data-image-size="' . filesize($img) . '"
                                        data-image-basename="' . $basename . '"
                                        data-image-creation="' . $creation . '"
                                        href="#"><span class="hidden">' . $basename . '</span><img src="' . $thumb . '" alt="' . $basename . '"></a></li>';
                        }
                    };
                }
                closedir($dh);
            }
        }

        return $items;
    }

    public function thumb(string $fileName = null, int $width = null): string
    {
        return $fileName;
    }

    public function getThumbnail($thumbnail = null, $height = 900): string
    {
        //deprecated
        /*if( strpos($thumbnail, 'no-image-found') !== false ){
            $thumbnail = 'no-image-found--900x420.jpg';
        }*/

        $orignal = $thumbnail;
        $orignalCleaned = preg_replace('/--\d+x\d+.png/', '', $orignal);
        $orignalCleaned = preg_replace('/--\d+x\d+.jpg/', '', $orignalCleaned);
        $orignalCleaned = preg_replace('/--\d+x\d+.jpeg/', '', $orignalCleaned);
        $orignalCleaned = preg_replace('/--\d+x\d+.gif/', '', $orignalCleaned);

        //original
        if( $height === 0 || strpos($thumbnail, '.svg') !== false ){
            return $this->urlService->getUrl($orignal);
        }

        // lets get extension
        $extension = explode('.', $orignal);
        $extension = end($extension);

        switch ($height) {
            case 150: $dims = [150, 133]; break;
            case 300: $dims = [300, 216]; break;
            default : $dims = [900, 420]; break;
        }
        if(strpos($orignalCleaned, 'no-image-found') !== false){
            return $this->urlService->getUrl('uploads/no-image-found.png');
        } else {
            $src = $height == -1 ? $thumbnail : ($orignalCleaned . '--' . $dims[0] . 'x' . $dims[1] . '.' . $extension);
            return strpos($src, 'http') !== false ? $src : $this->urlService->getUrl($src);
        }
    }

    public function getImageSrcRec($src): array
    {
        // Ex: http://localhost/lesboutika2bf.com/uploads/2020/09/img-20200923-wa0083--5f6f--1080x810.jpg
        preg_match('/([a-z-0-9]+)(--)([0-9]+)(x)([0-9]+)(.)([a-z]+)/', $src, $m);
        if(sizeof($m)===8){
            return ['x'=>$m[3], 'y'=>$m[5]];
        }
        return ['x'=>0, 'y'=>0];
    }

    public function readDirAlias(string $dir)
    {
        $dir = $dir . DIRECTORY_SEPARATOR;

        $items = [];

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..' && $file != 'index.php') {

                        $basename = basename($dir . $file);
                        $date = date("F d Y H:i:s", filectime($dir . $file));
                        $created = $this->timeService->getFrenchDate($date, "D d M Y - H:i:s");

                        if (filetype($dir . $file) == 'dir') {
                            $items['folders'][$file] = [
                                'basename' => $basename,
                                'absolute_url' => $this->please->getBundleService('url')->getUrl('_admin/medias/read?dir=' . $dir . $file),
                                'created' => $created,
                            ];
                        } else {
                            /* $img = $dir . $file;
                            $img_prop = getimagesize($img);
                            $basename = basename($img);
                            $date = date("F d Y H:i:s", filectime($img));
                            $creation = $this->timeService->getFrenchDate("D d M - H:i:s", $date);
                            $thumb = $this->thumb($this->please->getBundleService('url')->getUrl($dir . $file)); */

                            $xploded = explode('.', $file);
                            $extension = end($xploded);
                            ///$size = getimagesize($dir . $file); // only if file == image
                            $file__ = $dir . $file;
                            $items['files'][$file] = [
                                'basename' => $basename,
                                'relative_url' => preg_replace('~/+~', '/', str_ireplace('\\', '/', $file__)),
                                'absolute_url' => $this->please->getBundleService('url')->getUrl($file__),
                                //'image' => $extension === 'jpg' || $extension === 'jpeg' || $extension === 'png' || $extension === 'gif' || $extension === 'bmp',
                                'extension' => $extension,
                                'weight' => filesize($file__),
                                ///'size' => is_array($size) ? $size[0] . ' x ' . $size[1] : false,
                                'created' => $created,
                            ];
                        }
                    };
                }
                closedir($dh);
            }
        }

        return $items;
    }

    public function uploadImg($params)
    {
        $image_ratio_crop = $params['imageRatioCrop'] ?? true;
        $params = array_merge([
            'inputName' => null,
            'fileName' => uniqid(),
            'x' => 100, 'y' => 100,
            'dirPath' => $this->uploadsDir . '/' . $this->timeService->getDateTime()->format('Y') . '/' . $this->timeService->getMonth(null, 'number'),
            'settings' => function($file) use ($params, $image_ratio_crop){

                $img_prop = getimagesize($_FILES[$params['inputName']]['tmp_name']);

                $file->image_x = $params['x'] ?? $img_prop[0];
                $file->image_y = $params['y'] ?? $img_prop[1];
                $file->image_convert = 'jpg';
                $file->image_is_transparent = true;
                $file->file_max_size = '1G';
                $file->allowed = 'image/*';
                $file->image_ratio_fill = true;
                $file->image_background_color = false;
                $file->image_ratio_crop = $image_ratio_crop;
                $file->image_resize = true;
                $file->image_ratio = true;
                $file->image_resize = true;

                //
                return $file;
             },
            'onSuccess' => function ($params) {},
            'onError' => function ($err) {},
        ], $params);

        if (isset($_FILES[$params['inputName']])) {

            $file = new __VerotUploadService($_FILES[$params['inputName']]);

            if ($file->uploaded) {

                $settings = $params['settings']($file);

                $file->file_new_name_body = !is_null($params['fileName']) ? $params['fileName'] : $file->file_src_name_body .'--'.$params['x'].'x'.$params['y'];

                //lets ensure we unlink the file if already exists on the same name
                if (file_exists($file_path_to_unlink = $params['dirPath'] . DIRECTORY_SEPARATOR . $file->file_new_name_body . '.' . $file->image_convert)) {
                    unlink($file_path_to_unlink);
                }

                $file->process($params['dirPath']);

                if ($file->processed) {
                    //$file->clean();
                    $p['extended_filename'] = $file->file_dst_name_body . "." . $file->file_dst_name_ext;
                    $p['filename'] = $file->file_dst_name_body;
                    $relativePath = str_ireplace([$this->please->previousContainer->get('kernel')->getProjectDir() . '/public', '\\'], ['/', '/'], $params['dirPath']) . '/'. $p['extended_filename'];
                    $p['extension'] = $file->file_dst_name_ext;
                    $p['relative_url'] = trim(preg_replace('~/+~', '/', $relativePath), '/');
                    $p['absolute_url'] = $this->urlService->getUrl($p['relative_url']);
                    $p['x'] = (int)$file->image_dst_x;
                    $p['y'] = (int)$file->image_dst_y;
                    $p['dir_path'] = $params['dirPath'] . '/'. $p['extended_filename'];
                    $p['file_src_size'] = $file->file_src_size;
                    $p['is_image'] = true;
                    return $params['onSuccess']((object)$p);
                } else return $params['onError']('Cant process the image');
            } else return $params['onError']('Cant upload the image');
        }
        return $params['onError']('Cant find inputName');
    }

    public function uploadFile($params)
    {
        $params = array_merge([
            'inputName' => null,
            'fileName' => uniqid(),
            'dirPath' => $this->uploadsDir . '/' . $this->timeService->getDateTime()->format("Y") . '/' . $this->timeService->getDateTime()->format("m"),
            'onSuccess' => function () {},
            'onError' => function ($err) {},
        ], $params);

        if (isset($_FILES[$params['inputName']])) {

            $file = new __VerotUploadService($_FILES[$params['inputName']]);

            if( $this->isImage($file->file_src_pathname) ){
                return $this->uploadImg($params);
            }

            if ($file->uploaded) {

                $file->file_is_image = false;
                $file->file_new_name_body = str_ireplace('--x', '', (!is_null($params['fileName']) ? $params['fileName'] : $file->file_src_name_body));

                //lets ensure we unlink the file if already exists on the same name
                if (file_exists($file_path_to_unlink = $params['dirPath'] . DIRECTORY_SEPARATOR . $file->file_new_name_body . '.' . $file->file_src_name_ext)) {
                    unlink($file_path_to_unlink);
                }

                $file->process($params['dirPath']);

                if ($file->processed) {
                    //$file->clean();
                    $p['extended_filename'] = $file->file_dst_name_body . "." . $file->file_dst_name_ext;
                    $p['filename'] = $file->file_dst_name_body;
                    $relativePath = str_ireplace([$this->please->previousContainer->get('kernel')->getProjectDir() . '/public', '\\'], ['/', '/'], $params['dirPath']) . '/'. $p['extended_filename'];
                    $p['extension'] = $file->file_dst_name_ext;
                    $p['relative_url'] = trim(preg_replace('~/+~', '/', $relativePath), '/');
                    $p['absolute_url'] = $this->urlService->getUrl($p['relative_url']);
                    $p['dir_path'] = $params['dirPath'] . '/'. $p['extended_filename'];
                    $p['file_src_size'] = $file->file_src_size;
                    $p['is_image'] = false;
                    return $params['onSuccess']((object)$p);
                } else return $params['onError']('Cant process the image');
            } else return $params['onError']('Cant upload the image');
        }
        return $params['onError']('Cant find inputName');
    }

    public function getMedia($path)
    {
        // lets get original css file
        $filecontent = file_get_contents( $path );

        $xploded = explode(".", $path);
        $type = end($xploded);
        $fileName = str_ireplace($type, "", $path);

        if( is_null( $this->__PhpHtmlCssJsMinifierService ) ){
            $this->__PhpHtmlCssJsMinifierService = new __PhpHtmlCssJsMinifierService();
        }
        if( is_null( $this->__ImgCompressorService ) ){
            $setting = array(
                'directory' => '/public/uploads',
                'file_type' => array(
                    'image/png',
                    'image/jpeg',
                    'image/gif'
                )
            );
            $this->__ImgCompressorService = new __ImgCompressorService($setting);
        }

        if( $type == 'css' ){

            $minified = $this->__PhpHtmlCssJsMinifierService->getMinifiedCss( $filecontent );
        }
        elseif ( $type == 'js' ) {
            $minified = $this->__PhpHtmlCssJsMinifierService->getMinifiedJs( $filecontent );
        }
        elseif ( $type == 'png' ||  $type == 'jpeg' ||  $type == 'gif' ||  $type == 'jpg' ) {

            $image = true;
            $minified = $this->__ImgCompressorService->run($path, 'jpg', 5);
        }

        if( !isset($image) ){
            // lets write the new file
            $filename = "assets/{$type}/{$fileName}" . md5( $path ) . ".min.{$type}";
            $this->filesystem->dumpFile($filename, $minified);
        }
        else {

            print_r($minified);die;

        }

        return $this->please->getBundleService('url')->getUrl($filename);
    }

    public function getFileName($originalName)
    {
        return str_ireplace('.' . $this->getFileExtension($originalName), '', $originalName);
    }

    public function getFileExtension($originalName)
    {
        $exploded = explode('.', $originalName);
        return end($exploded);
    }

    public function getThemeAssets(array $assets=[], $extension='css')
    {
        $output = '';
        foreach ($assets as $v) {
            $output .= $this->getThemeAsset($v.'.'.$extension)."\n";
        }
        return new Markup($output, 'UTF-8');
    }

    public function getThemeAsset($asset, $attr='')
    {
        $urlService = $this->please->getBundleService('url');

        if(strpos($asset, '.css') !== false){
            $suffix = "css/$asset";
            $isCss = true;
        }
        elseif (strpos($asset, '.js') !== false) {
            $suffix = "js/$asset";
            $isJs = true;
        }
        else {
            $suffix = "img/$asset";
            $isImage = true;
        }

        if(strpos($suffix, '|v')!==false){
            $s = str_replace('|v', '', $suffix);
            $data_url = $s;
            $suffix = $s.'?v='.rand();
        }
        else {
            $s = $suffix;
            $data_url = $s;
        }

        $s = str_ireplace(['css','js','.','/'], ['_','_','_','_'], $s);
        $url = $urlService->getUrl("theme/assets/".$suffix);
        $data_url = $urlService->getUrl("theme/assets/".$data_url);
        
        if( isset($isCss) ){ return new Markup('<link data-ref="'.$data_url.'" id="link_'.$s.'" '.$attr.' rel="stylesheet" href="'.$url.'">', 'UTF-8'); }
        else if( isset($isJs) ){ return new Markup('<script data-ref="'.$data_url.'" id="script_'.$s.'" '.$attr.' src="'.$url.'"></script>', 'UTF-8'); }
        else return $url;
    }

    public function browseThemeAssets($extension='css', $v=false, $prevent=[])
    {
        $items = $this->readDirAlias($this->themeDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $extension );
        
        $assets = '';

        if( isset($items['files']) ){
            foreach($items['files'] as $filename => $fileInfo){
                $url = $fileInfo['absolute_url'] . ($v == false ? '' : '?v=' . rand(0, 999));
                if( $prevent ){
                    foreach($prevent as $str){

                        if( strpos($url, $str) === false && !isset($this->$url) ){
                            $this->$url = true;
                            if( $extension == 'css' ){
                                $assets .= '<link rel="stylesheet" href="'.$url.'">'."\n";
                            }
                            elseif( $extension == 'js' ){
                                $assets .= '<script src="'.$url.'"></script>'."\n";
                            }
                        }
                    }
                }
                else {
                    if( $extension == 'css' ){
                        $assets .= '<link rel="stylesheet" href="'.$url.'">'."\n";
                    }
                    elseif( $extension == 'js' ){
                        $assets .= '<script src="'.$url.'"></script>'."\n";
                    }
                }
            }
        }

        return new Markup($assets, 'UTF-8');
    }

    public function getMDLAssets()
    {
        $urlServ = $this->please->getBundleService('url');
        $assets = '<link rel="stylesheet" href="'.$urlServ->getCDN('material-design-light/css/icon.css?family=Material+Icons').'">'."\n";
        $assets .= '<link rel="stylesheet" href="'.$urlServ->getCDN('material-design-light/css/material.indigo-pink.min.css').'">'."\n";
        $assets .= '<script src="'.$urlServ->getCDN('material-design-light/js/material.min.js?v='. rand(0, 999)).'"></script>'."\n";
        $assets .= '<script src="'.$urlServ->getCDN('material-design-light/core.js?v='. rand(0, 999)).'"></script>'."\n";
        return new Markup($assets, 'UTF-8');
    }

    public function getServerSidePathUploadedFile($path)
    {   
        return trim(preg_replace('~/+~', '/', $this->uploadsDir."/$path"), '/');
    }
    
    public function unlinkFiles($filesToUnlink)
    {   
        if( $filesToUnlink && is_array($filesToUnlink) || is_object($filesToUnlink) ){
            foreach ($filesToUnlink as $key => $filePublicPath) {
                $file = $this->please->getBundleService('dir')->getProjectPath('public/'.$filePublicPath);
                if( file_exists($file) ){
                    unlink($file);
                }
            }
        }
    }

    private function isImage($path)
    {
        if( !empty($path) ){
            $a = getimagesize($path);
            $image_type = $a[2];
            if(in_array($image_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP))) {
                return true;
            }
        }
        return false;
    }

    public function isDev()
    {
        return $this->please->getBundleService('env')->getAppEnv() == 'dev';
    }

    public function isBackoffice()
    {
        $urlServ = $this->please->getBundleService('url');
        if( strpos($urlServ->getCurrentUrl(), $urlServ->geturl('_admin')) !== false ) {
            return true;
        }
        return false;
    }
}
