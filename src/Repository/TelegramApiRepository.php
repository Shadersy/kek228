<?php

namespace App\Repository;

use App\Entity\TelegramApi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TelegramApi|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramApi|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramApi[]    findAll()
 * @method TelegramApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramApi::class);
    }
}
