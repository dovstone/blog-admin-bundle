<?php

namespace DovStone\Bundle\BlogAdminBundle\Controller;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use DovStone\Bundle\BlogAdminBundle\Repository\BundleBloggyRepository;
use DovStone\Bundle\BlogAdminBundle\Entity\Bloggy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/_admin/media/")
 */
class MediaController extends AbstractController
{

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->please->getBundleService('execute_before')->executeBefore();
        $this->please->previousContainer->get('service.execute_before')->__run();
    }

    /**
     * @Route("list-images", name="_listImages")
     */
    public function _listImages(BundleBloggyRepository $bloggyRepo)
    {
        return $this->please->readList([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles()],
            'items' => function () use ($bloggyRepo) {
                return $bloggyRepo->findBy([
                    'type' => [ 'image', 'file--jpg', 'file--jpeg', 'file--png', 'file--gif', 'file--pdf', 'file--doc', 'file--docx', 'file--txt' ]
                ], ['created' => 'DESC']);
            },
            'perPage' => 24,//24
            'view' => function ($files_) {
                $files = $this->please->fetchEager($files_?$files_->getItems():null, $bundle=true);
                return new JsonResponse([
                    'title' => 'Tableau de bord',
                    'view' => $this->getTemplate('files', compact('files', 'files_')),
                    'success' => true
                ]);
            }
        ]);
    }

    /**
     * @Route("upload", name="_uploadFile")
     */
    public function _uploadFile(BundleBloggyRepository $bloggyRepo)
    {
        $handled_ = null;

        $imagesize = getimagesize($_FILES['file']['tmp_name']);
        $filename = $this->please->getBundleService('string')->getSlug(
            $this->please->getBundleService('media')->getFileName(
                $this->please->getRequest()->files->get('file')->getClientOriginalName()
            )
        );

        $img_x = $imagesize[0];
        $img_y = $imagesize[1];

        if( $img_x > 1920 ){
            $img_x = 1920;
            $ratio = $imagesize[0] / 1920;
            $img_y = intval($imagesize[1] / $ratio);
        }

        $genThumbnails = $this->please->getRequestStackRequest()->get('gen_thumbnails');
        $crop = $this->please->getRequestStackRequest()->get('crop');


        foreach( $genThumbnails ? [
            [150, 133],
            [300, 216],
            [900, 420],
            //[420, 900],
            [$img_x, $img_y]
        ] : [[$img_x, $img_y]] as $i => $sizes){

            $x = $sizes[0];
            $y = $sizes[1];

            $handled_ = $this->please->getBundleService('media')->uploadFile([
                'inputName' => 'file',
                'fileName' => $filename . '--'. substr(uniqid(), 0, 4) . '--' . $x . 'x' . $y,
                'x' => $x, 'y' => $y,
                'imageRatioCrop' => !is_null($crop),
                'onSuccess' => function ($p) use ($i, $sizes) {

                    return $this->please->basicCreate([
                        'entity' => new Bloggy(),
                        'sanitizer' => function ($posted, $handled) use ($p, $i, $sizes) {

                            $info = (object)[];

                            $info->extended_filename = $p->extended_filename;
                            $info->filename = $p->filename; 
                            $info->extension = $p->extension;
                            $info->relative_url = $p->relative_url; 
                            $info->absolute_url = $p->absolute_url; 
                            $info->file_src_size = $p->file_src_size;

                            if( $p->is_image ){
                                $info->x = $p->x;
                                $info->y = $p->y;
                            }
                            else {
                                $info->is_image = false;
                            }

                            $handled
                                ->setType('file--'.$p->extension)
                                ->setSlug($info->filename)
                                ->setInfo($info);

                            return $handled;

                        },
                        'onSuccess' => function ($sanitized) {
                            return new JsonResponse([
                                'success' => true,
                                'msg' => 'Données mises à jour avec succès',
                                'data' => [
                                    'id' => $sanitized->getId(),
                                    'info' => $sanitized->getInfo(),
                                    'created' => $this->please->getBundleService('time')->getFrenchDate($sanitized->getCreated()),
                                ]
                            ]);
                        }
                    ]);
                },
                'onError' => function ($e) {
                    dd($e);
                },
            ]);
        }

        return $handled_;
    }

    
    /**
     * @Route("unlinkfile/{id}/{encKey}", name="_unlinkFile")
     */
    public function _unlinkFile($id, $encKey)
    {   
        return $this->please->unlinkFile([
            'id' => $id,
            'encryptionKey' => $encKey
        ]);
    }

    /**
     * @Route("delete", name="_deleteFile")
     */
    public function _deleteFile(BundleBloggyRepository $bloggyRepo)
    {
        return $this->please->delete([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles()],
            'finder' => function () use ($bloggyRepo) {
                if( $info = $this->please->getRequestStackRequest()->get('info') ){
                    return $bloggyRepo->find(json_decode($info)->native_info->id);
                }
            },
            'onSuccess' => function ($f) {
                // after deleted from db
                // lets delete from disk
                $relative_url = json_decode($this->please->getRequestStackRequest()->get('info'))->relative_url;
                if( file_exists($file = $this->please->getBundleService('dir')->dirPath('public/' . $relative_url)) ){
                    unlink($file);
                }
                return new JsonResponse([
                    'success' => true,
                    'msg' => 'Données supprimées avec succès'
                ]);
            }
        ]);
    }

    private function getTemplate(string $templateName, array $parameters = array(), $response = null)
    {
        return $this->please->getBundleService('view')->sanitizeFinalView(
            $this->renderView("@DovStoneBlogAdminBundle/{$templateName}.html.twig", $parameters, $response)
        );
    }

}
