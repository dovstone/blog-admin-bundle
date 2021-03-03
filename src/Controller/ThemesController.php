<?php

namespace DovStone\Bundle\BlogAdminBundle\Controller;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/_admin/themes/")
 */
class ThemesController extends AbstractController
{

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
        $this->please->getBundleService('execute_before')->executeBefore();
        $this->please->previousContainer->get('service.execute_before')->__run();
    }

    /**
     * @Route("list", name="_listThemes")
     */
    public function _listThemes()
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles()],
            'onFound' => function ($item, $params) {
                $themes = [];
                $themes = $this->please->getBundleService('dir')->listFolders('public/themes');
                return new JsonResponse([
                    'success' => true,
                    'title' => 'Thèmes',
                    'view' => $this->getTemplate('themes', compact('themes'))
                ]);
            },
        ]);
    }

    /**
     * @Route("save", name="_saveTheme")
     */
    public function _saveTheme()
    {
        return $this->please->read([
            'isXml' => '_base',
            'isGranted' => ["byUserRoles" => $this->please->getAdminRoles()],
            'onFound' => function ($item, $params) {
                $themes = $this->please->getBundleService('dir')->listFolders('public/themes');
                if( isset($_POST['theme']) && in_array($_POST['theme'], $themes) ){

                    $this->please->unsetStorage('__AppThemeSelected', true);
                    $this->please->setStorage([
                        '__AppThemeSelected' => [
                            'content'=> function(){
                                return $_POST['theme'];
                            }
                        ]
                    ], true);
                    return new JsonResponse([
                        'success' => true,
                        'msg' => 'Données enregistrées avec succès',
                    ]);
                }
                else {
                    return new JsonResponse([
                        'success' => false,
                        'msg' => 'Les données envoyées ne peuvent êtres traitées',
                    ]);
                }
            },
        ]);
    }


    private function getTemplate(string $templateName, array $parameters = array(), $response = null)
    {
        return $this->please->getBundleService('view')->sanitizeFinalView(
            $this->renderView("@DovStoneBlogAdminBundle/{$templateName}.html.twig", $parameters, $response)
        );
    }

}
