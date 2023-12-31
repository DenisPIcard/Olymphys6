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
use Symfony\Component\Routing\Annotation\Route;
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
            BooleanField::new('selectionnee'),
            BooleanField::new('autorisationsPhotos')
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
        $ajoute_equipe = Action::new('ajouter_equipe_passee', 'Ajouter des équipes passée', 'fa fa-file-download')
            ->linkToRoute('charger_equipes_passees')->createAsGlobalAction();
        $actions->add(Crud::PAGE_INDEX, $ajoute_equipe)
            ->setPermission($ajoute_equipe, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN');;
        return parent::configureActions($actions); // TODO: Change the autogenerated stub
    }

    #[Route("/OdpfAdmin/Crud/charger-equipes_passees", name: "charger_equipes_passees")]
    public function charger_equipes_passees(Request $request)
    {// A partir du tableau excel fourni par odpf fonction provisoire pour la transfert odpf vers olymphys
        $form = $this->createFormBuilder()
            ->add(
                'file', FileType::class
            )
            ->add('editionspassees', ChoiceType::class, [
                'choices' => range(1, 30),
                'label' => 'Choisir le numéro de l\'édition'

            ])
            ->add('submit', SubmitType::class)->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $fichier = $form->get('file')->getData();
            $numeroEd = $form->get('editionspassees')->getData() - 1;
            $editionPasseeRepository = $this->doctrine->getRepository(OdpfEditionsPassees::class);
            $equipesPasseeRepository = $this->doctrine->getRepository(OdpfEquipesPassees::class);
            $edition = $editionPasseeRepository->findOneBy(['edition' => $numeroEd]);
            $equipes = $equipesPasseeRepository->findBy(['editionspassees' => $edition]);

            $spreadsheet = IOFactory::load($fichier);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $spreadsheet->getActiveSheet()->getHighestRow();


            for ($row = 2; $row <= $highestRow; ++$row) {

                $titreProjet = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $uai = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                $uaiObj = $this->doctrine->getRepository(Uai::class)->findOneBy(['uai' => $uai]);
                $numeroEquipe = intval($worksheet->getCellByColumnAndRow(4, $row)->getValue());
                $lettreEquipe = $worksheet->getCellByColumnAndRow(5, $row)->getValue();


                if ($uaiObj !== null) {
                    $nomLycee = $uaiObj->getNom();
                    $localiteLycee = $uaiObj->getCommune();
                    $academieLycee = $uaiObj->getAcademie();

                }
                $prenomProf1 = u(u($worksheet->getCellByColumnAndRow(22, $row)->getValue())->lower())->camel()->title()->toString();
                $nomProf1 = strtoupper($worksheet->getCellByColumnAndRow(23, $row)->getValue());
                $profs = $prenomProf1 . ' ' . $nomProf1;
                $prenomProf2 = u(u($worksheet->getCellByColumnAndRow(24, $row)->getValue())->lower())->camel()->title()->toString();
                $nomProf2 = strtoupper($worksheet->getCellByColumnAndRow(25, $row)->getValue());
                if ($prenomProf2 !== '') {
                    $profs = $profs . ', ' . $prenomProf2 . ' ' . $nomProf2;
                }
                $equipe = $equipesPasseeRepository->createQueryBuilder('e')
                    ->where('e.editionspassees =:edition')
                    ->andWhere('e.numero =:numero or e.lettre =:lettre')
                    ->setParameters(['edition' => $edition, 'numero' => $numeroEquipe, 'lettre' => $lettreEquipe])
                    ->getQuery()->getOneOrNullResult();
                if ($equipe === null) {
                    $equipe = new OdpfEquipesPassees();
                    $equipe->setEditionspassees($edition);

                }
                $equipe->setNumero($numeroEquipe);
                if ($lettreEquipe == '~') {
                    $equipe->setLettre(null);
                } else {
                    $equipe->setLettre(($lettreEquipe));
                    $equipe->setSelectionnee(true);
                }
                $equipe->setTitreProjet($titreProjet);
                $equipe->setLycee($nomLycee);
                $equipe->setVille($localiteLycee);
                $equipe->setAcademie(($academieLycee));
                $equipe->setProfs($profs);
                $this->doctrine->getManager()->persist($equipe);
                $this->doctrine->getManager()->flush();
            }
            $this->modifTexteEdition($edition);
            return $this->redirectToRoute('odpfadmin');
        }
        return $this->renderForm('recup_odpf/recup-profs-eleves.html.twig', array('form' => $form));


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