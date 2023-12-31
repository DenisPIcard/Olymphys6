<?php

namespace App\Repository\Odpf;


use App\Entity\Odpf\OdpfCategorie;
use App\Entity\Odpf\OdpfLogos;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method OdpfLogos|null find($id, $lockMode = null, $lockVersion = null)
 * @method OdpfLogos|null findOneBy(array $criteria, array $orderBy = null)
 * @method OdpfLogos[]    findAll()
 * @method OdpfLogos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OdpfLogosRepository extends ServiceEntityRepository
{
    private RequestStack $requestStack;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack, ManagerRegistry $doctrine)
    {
        parent::__construct($registry, OdpfLogos::class);
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }

    public function logospartenaires($choix): array
    {

        $logos = $this->createQueryBuilder('e')
            ->select('e')
            ->andWhere('e.choix =:choix')
            ->andWhere('e.en_service =:value')
            ->setParameters(['choix' => $choix, 'value' => 1])
            ->getQuery()
            ->getResult();
        $titre = 'Partenaires';
        $categorie = $this->doctrine->getRepository(OdpfCategorie::class)->findOneBy(['categorie' => 'Partenaires']);
        $edition = $this->requestStack->getSession()->get('edition');


        return [
            'logos' => $logos,
            'titre' => $titre,
            'choix' => $choix,
            'categorie' => $categorie,
            'edition' => $edition
        ];
    }
    /*   public function findByExampleField($value)
       {
           return $this->createQueryBuilder('i')
               ->andWhere('i.exampleField = :val')
               ->setParameter('val', $value)
               ->orderBy('i.id', 'ASC')
               ->setMaxResults(10)
               ->getQuery()
               ->getResult()
           ;
       }
       */

    /*
    public function findOneBySomeField($value): ?Imagescarousels
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
