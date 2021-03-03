<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Markup;

class DirService extends AbstractController
{
    protected $previousContainer;

    private $fileSystem;
    private $please;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->fileSystem = new Filesystem();
    }

    public function dirPath($dir = 'templates/layouts')
    {
        $dir_path = $this->getProjectDir()  . '/' . $dir;
        return preg_replace('~/+~', '/', $dir_path);
    }

    public function asOptions($dir, string $format = 'string', $file_extenstion = 'html.twig')
    {
        $this->finder = new Finder();

        $options = ($format === 'array') ? [] : '<option value="default">Default</option><option disabled></option>';

        $dirToOpen = $this->dirPath($dir);

        if (!$this->fileSystem->exists($dirToOpen)) {
            $this->fileSystem->mkdir($dirToOpen, 0777);
        }

        $this->finder->files()->name('/\.' . $file_extenstion . '$/')->in($dirToOpen);

        foreach ($this->finder as $file) {

            $layout_name = str_ireplace('.' . $file_extenstion, '', $file->getFileName());

            if( $layout_name !== 'default' ){
                if ($format === 'array') {
                    $options[$layout_name] = $layout_name;
                } else {
                    $options .= "<option value='$layout_name'>$layout_name</option>";
                }
            }
        }

        return new Markup($options, 'UTF-8');
    }

    public function listFolders($dir)
    {
        $list = [];
        if( is_dir( $dir=$this->getProjectDir()  . '/' . $dir ) ){
            $d = dir($dir);
            while (false !== ($entry = $d->read())){
                $entry_ = $dir.'/'.$entry;
                if (is_dir($entry_) && ($entry != '.') && ($entry != '..')){
                    $list[] = $entry;
                }
            }
        }
        return $list;
    }

    public function getThemeDirPath($dir='')
    {
        return 'public/theme/' . $dir;
    }

    public function isHome()
    {
        $urlServ = $this->please->getBundleService('url');
        $currUrl = $urlServ->getCurrentUrl();
        if(
            (trim($currUrl, '/') == trim($urlServ->getUrl(), '/'))
            //||
            //( $this->please->getGlobal('post') && $this->please->getGlobal('post')->getSlug() == 'home' )
        ){
            return true;
        }
        return false;
    }

    public function getThemeDirAbsDirPath($dir='')
    {
        return $this->dirPath('public/theme') . '/'. $dir;
    }

    public function widgetCards( $dir = 'templates/cards/skeletons', $file_extenstions = ['html.twig', 'png', 'jpg', 'jpeg', 'gif'] )
    {
        $this->finder = new Finder();

        $cards = 'La liste de vos cards est vide.';
        $cardsBag = [];

        $dirToOpen = $this->dirPath($dir);

        if (!$this->fileSystem->exists($dirToOpen)) {
            $this->fileSystem->mkdir($dirToOpen, 0777);
        }

        foreach( $file_extenstions as $file_extenstion ){
            $this->finder->files()->name('/\.' . $file_extenstion . '$/')->in($dirToOpen);
        }

        foreach ($this->finder as $absolutePath => $file) {
            $cardName = $file->getRelativePath();
            $filename = $file->getFileName();
            if( !isset($cardsBag[$cardName])){
                $cardsBag[ $cardName ] = [];
            }
            // lets check if card has required files ( index.html.twig and preview.(png|jpg|jpeg|gif) )
            if( false !== strpos($filename, 'index') ){ $cardsBag[ $cardName ]['index'] = $file->getPathname();}
            if( false !== strpos($filename, 'preview') ){ 
                $cardsBag[ $cardName ]['preview'] = $file->getPathname();
                // lets set rename card preview file
                $previewNewName = $this->please->getBundleService('string')->getSlug( $cardName ) . '-' . $filename;

                // lets copy the preview to public dir so it can be accessible
                $cardPreviewSrc = '/uploads/cards-preview/' . $previewNewName;
                $this->fileSystem->copy($file, $this->please->previousContainer->get('kernel')->getProjectDir() . '/public/' . preg_replace('/(.jpg|.jpeg|.gif)/', '.png', $cardPreviewSrc));

                // lets now delete the original preview
                $this->fileSystem->remove($file);

                // lets override the card preview name
                $cardsBag[ $cardName ]['preview'] =  $this->please->getBundleService('url')->getUrl( $cardPreviewSrc );
            }
        }

        if( $cardsBag ){
            
            $cards = '<ul class="swagg-cards-list">';
            $i = 0;
            $checked = '';
            foreach( $cardsBag as $cardName => $card ){
                if( isset($card['index']) ){
                    if( $i == 0 ){
                        $checked = ' checked="checked" ';
                    }

                    // lets copy the preview to public dir so it can be accessible
                    $preview = $this->please->getBundleService('url')->getUrl(
                        '/uploads/cards-preview/' . $this->please->getBundleService('string')->getSlug( $cardName ) . '-preview.png'
                    );

                    $cards .= '<li data-card="' . $cardName . '">';
                        $cards .= '<label>';
                            $cards .= '<p class="card-name text-center">'. $cardName .'</p>';
                            $cards .= '<div class="cell-container">';
                                $cards .= '<div class="cell cell-use-space"><input'. $checked .'name="card.default" type="radio" value="' . $cardName . '"></div>';
                                $cards .= '<div class="cell cell-spacer-10"></div>';
                                $cards .= '<div class="cell">';
                                    $cards .= '<div class="card-preview"><img alt="' . $cardName . '" src="'. $preview .'" /></div>';
                                $cards .= '</div>';
                            $cards .= '</div>';
                        $cards .= '</label>';
                    $cards .= '</li>';
                    $i++;
                }
            }

            $cards .= '</ul>';
        }

        return $cards;
    }

    public function getProjectDir()
    {
        return $this->please->previousContainer->get('kernel')->getProjectDir();
    }
    

    public function getProjectPath( $path=null )
    {
        return $this->please->previousContainer->get('kernel')->getProjectDir() . '/' . trim(preg_replace('~/+~', '/', $path), '/');
    }

    public function remove($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if($file->isDir()){
                //rmdir($file->getRealPath());
            }
            else {
                //unlink($file->getRealPath());
            }
        }
        //rmdir($dir);
    }

    public static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
         foreach ($files as $file) {
           (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
