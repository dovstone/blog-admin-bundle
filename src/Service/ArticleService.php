<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticleService extends AbstractController
{

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
    }
}
