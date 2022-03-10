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

    // /**
    //  * @return TelegramApi[] Returns an array of TelegramApi objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TelegramApi
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
