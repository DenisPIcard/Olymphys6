<?php

namespace App\Controller\OdpfAdmin;

use AllowDynamicProperties;
use App\Controller\Admin\Filter\CustomEditionspasseesFilter;
use App\Entity\Odpf\OdpfArticle;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Odpf\OdpfFichierspasses;
use App\Entity\Photos;
use App\Entity\Uai;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

#[AllowDynamicProperties] class OdpfEquipesPasseesCrudController extends AbstractCrudController
{

    private EntityRepository $entityRepository;

    public function __construct(ManagerRegistry $doctrine, EntityRepository $entityRepository)
    {

        $this->doctrine = $doctrine;
        $this->entityRepository = $entityRepository;
    }

    public static function getEntityFqcn(): string
    {
        return OdpfEquipesPassees::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(CustomEditionspasseesFilter::new('editionspassees', 'edition'))
            ->add(BooleanFilter::new('selectionnee'));


    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('editionspassees', 'Edition'),
            TextField::new('numero'),
            TextField::new('lettre'),
            TextField::new('titreProjet'),
            TextField::new('lycee'),
            TextField::new('ville'),
            TextField::new('academie'),
            TextField::new('profs'),
            TextField::new('eleves'),
            TextField::new('palmares'),
            TextField::new('prix'),
            BooleanField::new('selectionnee'),
            BooleanField::new('autorisationsPhotos')->renderAsSwitch(true)
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $repositoryEdition = $this->doctrine->getRepository(OdpfEditionsPassees::class);
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->innerJoin('entity.editionspassees', 'edi')
            ->addOrderBy('edi.edition', 'DESC')
            ->addOrderBy('entity.numero', 'ASC');;


        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');;
        return parent::configureActions($actions); // TODO: Change the autogenerated stub
    }


    public function modifTexteEdition($edition)
    {
        $article = $this->doctrine->getRepository(OdpfArticle::class)->findOneBy(['choix' => 'edition' . $edition->getEdition()]);
        $texte = $article->getTexte();
        $intro = explode('<ul>', $texte)[0];
        $texteEquipes = explode('<ul>', $texte)[1];

        $equipes = $this->doctrine->getRepository(OdpfEquipesPassees::class)->createQueryBuilder('e')
            ->andWhere('e.editionspassees =:edition')
            ->setParameter('edition', $edition)
            ->addOrderBy('e.selectionnee', 'DESC')
            ->addOrderBy('e.lettre', 'ASC')
            ->addOrderBy('e.numero', 'ASC')
            ->getQuery()->getResult();
        $nouveauTexte = '';
        foreach ($equipes as $equipe) {
            $equipe->getLettre() ? $codeEquipe = $equipe->getLettre() : $codeEquipe = $equipe->getNumero();

            $nouveauTexte = $nouveauTexte . '<li><a href="/../public/index.php/odpf/editionspassees/equipe,' . $equipe->getId() . '"> ' . $codeEquipe . '- ' . $equipe->getTitreProjet() . '</a>, Lycée ' . $equipe->getLycee() . ', ' . $equipe->getVille() . '</a></li>';
        }
        $nouveauTexte = '<ul>' . $nouveauTexte . '</ul>';
        $texte = $intro . $nouveauTexte;
        $article->setTexte($texte);
        $this->doctrine->getManager()->persist($article);
        $this->doctrine->getManager()->flush();

    }

    public function delete(AdminContext $context)
    {
        $equipe = $this->doctrine->getRepository(OdpfEquipesPassees::class)->find(['id' => $context->getRequest()->query->get('entityId')]);
        $listeFichiers = $this->doctrine->getRepository(OdpfFichierspasses::class)->findBy(['equipepassee' => $equipe]);
        if ($listeFichiers !== null) {
            foreach ($listeFichiers as $fichier) {
                $this->doctrine->getManager()->remove($fichier);
                $this->doctrine->getManager()->flush();

            }
        }
        $listePhotos = $this->doctrine->getRepository(Photos::class)->findBy(['equipepassee' => $equipe]);
        if ($listePhotos !== null) {
            foreach ($listePhotos as $photo) {
                $this->doctrine->getManager()->remove($photo);
                $this->doctrine->getManager()->flush();

            }
        }

        return parent::delete($context); // TODO: Change the autogenerated stub
    }
}