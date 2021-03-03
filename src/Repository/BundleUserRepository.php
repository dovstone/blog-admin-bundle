<?php

namespace DovStone\Bundle\BlogAdminBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use DovStone\Bundle\BlogAdminBundle\Entity\User;
use DovStone\Bundle\BlogAdminBundle\Service\SessionService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BundleUserRepository extends ServiceEntityRepository
{
    private $sessionService;
    //
    private $appUiD;

    public function __construct(ManagerRegistry $registry, SessionService $sessionService)
    {
        parent::__construct($registry, User::class);
        //
        $this->sessionService = $sessionService;
        //
        $this->appUiD = sha1(getenv('APP_NAME'));
    }

}
