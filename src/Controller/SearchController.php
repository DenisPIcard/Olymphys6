<?php

namespace App\Controller;

use App\Utils\SearchResult;

use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Odpf\OdpfFichierspasses;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Mpdf\Tag\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SearchController extends AbstractController
{
    private ManagerRegistry $doctrine;


    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    #[Route("/search", name: "search")]
    public function search(Request $request): Response
    {
        $repertoire = 'search/textes';

        $form = $this->createFormBuilder()
            ->add('text', TextType::class, [
                'label' => false,
                'required' => true,

            ])
            ->add('fullword', CheckboxType::class, [
                'label' => 'recherche sur le mot complet',
                'required' => false,
                'data' => true,

            ])
            ->add('nbres', ChoiceType::class, [
                'label' => false,
                'data' => '25',
                'choices' => ['25' => 25, '50' => '50', '100' => '100'],
                'attr' => ['style' => 'width: 100px']
            ])
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'Lancer la recherche ...',
                ]
            )
            ->getForm();

        $titre = null;
        $pdfUrl = null;
        $nbKeyWord = null;
        $occurence = null;
        $statResult = null;
        $nsecmax = null;
        $equipes = null;
        $backColor = [];
        $kwNotFoundByIndex = null;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $textes_a_rechercher = $form->get('text')->getData();
            $fullword = $form->get('fullword')->getData();
            $nsecmax = $form->get('nbres')->getData();

            if (!is_dir($repertoire)) {
                return $this->redirectToRoute('search');
            }

            // l'objet responsable de la recherche
            $s = new SearchResult(
                repertoire: $repertoire,
                textes_a_rechercher: $textes_a_rechercher,
                fullword: $fullword
            );

            // Lancement de la recherche
            $s->doSearch();
            // $s possède deux propriétés accessibles :
            // - result qui contient un tableau (trié selon la pertinence) de FileResult
            // - temps qui contient la durée de la recherche
            // Voir le fichier SearchResult.php pour plus de détails.


            // pour l'affichage de la performance de la recherche
            $statResult = count($s->result);
            (count($s->result) > 1) ?
                $statResult .= " fichiers trouvés en " :
                $statResult .= " fichier trouvé en ";
            $statResult .=  number_format($s->temps, 3, ',') . " s";

            if (count($s->result) > $nsecmax) {
                $statResult .= ', ' . $nsecmax . ' affichés :';
            } else {
                $statResult .= ' :';
            }

            // balayage des fichiers trouvés lors de la recherche
            $i = 0;
            $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
            foreach ($s->result as $r) {

                $fichier = $r->filename;
                // nettoyage du nom de fichier pour ne garder que le titre de l'équipe
                $edition = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition' => explode('-', $fichier)[0]]);
                $numEq = explode('-', $fichier)[2];
                !in_array($numEq, $letters) ?
                    $equipes[$i] = $this->doctrine->getRepository(OdpfEquipesPassees::class)->findOneBy(['editionspassees' => $edition, 'numero' => $numEq]) :
                    $equipes[$i] = $this->doctrine->getRepository(OdpfEquipesPassees::class)->findOneBy(['editionspassees' => $edition, 'lettre' => $numEq]);

                $titre[$i] = ($i + 1) . ' - ' . 'Edition ' . $edition->getEdition() . ' - Equipe ' . $numEq . ' - ' . $equipes[$i]->getTitreProjet();

                // récupération de l'url du fichier pdf archivé
                $pdfUrl[$i] = $r->pdfUrl();

                $nbKeyWord[$i] =    count($r->kwFound) . "/" .  count($r->tabkw);

                $occurence[$i] = $r->nbTotMatch;
                ($r->nbTotMatch > 1) ?
                    $occurence[$i] .= " occurences : " :
                    $occurence[$i] .= " occurence : ";
                
                foreach ($r->kwFound as $kw => $count) {
                    $occurence[$i] = $occurence[$i] . "$kw ($count)" . " ";
                }

                $kwNotFoundByIndex[$i] = $r->kwNotFound;
                if (count($r->kwNotFound) == 0) {
                    $backColor[$i] = 'lightgreen';
                } else {
                    $backColor[$i] = 'lightgrey';
                }

                $i++;
            }
        }


        return $this->render('search/search.html.twig', [
            'form' => $form->createView(),
            'titre' => $titre,
            'statresult' => $statResult,
            'pdfUrl' => $pdfUrl,
            'nbKeyWord' => $nbKeyWord,
            'kwNotFound' => $kwNotFoundByIndex,
            'backColor' => $backColor,
            'occurence' => $occurence ?? -1,
            'nsecmax' => $nsecmax,
            'equipes' => $equipes
        ]);
    }
}
