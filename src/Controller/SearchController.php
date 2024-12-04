<?php

namespace App\Controller;

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
    #[Route("/search", name:"search")]
 public function search(Request $request) : Response
 {
            $repertoire='search/textes';
            $form=$this->createFormBuilder()
                ->add('text',TextType::class,[
                    'label'=>false,
                    'required'=>true,

                ])
                ->add('fullword',CheckboxType::class,[
                    'label'=> 'recherche sur le mot complet',
                    'required'=>false,
                    'data'=>true,

                ])
                ->add('nbres',ChoiceType::class,[
                    'label'=>false,
                    'data'=> '25',
                    'choices'=>['25'=>25,'50'=>'50','100'=>'100'],
                    'attr'=>['style'=>'width: 100px']
                ])
                ->add('submit', SubmitType::class,
                [
                    'label' => 'Lancer la recherche ...',
                ])
                ->getForm();
            $titre=null;
            $pdfUrl=null;
            $nbKeyWord=null;
            $occurence=null;
            $statResult=null;
            $nsecmax=null;
            $equipes=null;
            $kwNotFoundByName = null;
            $kwNotFoundByIndex = null;
            
           $form->handleRequest($request);
           if ($form->isSubmitted() && $form->isValid()) {

               $textes_a_rechercher=$form->get('text')->getData();
               $fullword=$form->get('fullword')->getData();
               $nsecmax=$form->get('nbres')->getData();

               if (!is_dir($repertoire)) {

                   return $this->redirectToRoute('search');
               }

               // le tableau des mots-clés à rechercher
               // on découpe la chaîne tapée par l'utiisateur en utilisant l'espace comme séparateur
               // et on supprime les chaînes vides (explode retourne des "" pour les espaces ...)
               $kwArray = array_filter(
                   explode(' ', $textes_a_rechercher),
                   function ($k) {
                       return $k != '';
                   }
               );

               // le tableau principal
               // C'est une liste d'associations <nom_fichier> => <tableau assoc. keyword => nbr occurences>
               $assocFileKWCount = [];

               // pour le chronmétrage de la recherche
               $depart = microtime(true);

               // Exploration du répertoire des textes
               $scandir = scandir($repertoire);
               // balayage des fichiers
               foreach ($scandir as $fichier) {
                   // élimination des fichiers spéciaux
                   if ($fichier[0] == '.') continue;
                   // récupération du contenu du fichier texte
                   $filesContents = file_get_contents($repertoire.'/' . $fichier);

                   // on recherche chaque mot-clé dans le texte avec preg_match_all
                   // si on le trouve, on l'associe au nom de fichier avec son nombre d'occurences
                   
                   $kwInFile = false;   // sera vrai si au moins un mot-clé trouvé dans le fichier
                   foreach ($kwArray as $kw) {
                       // recherche sur des mots entiers ?
                       if ($fullword == "true") {
                           // ~ est le délimiteur de l'expression régulière, \b est le délimiteur de mot,
                           // ui à la fin est là pour matcher en UTF8 (pour les accents) sans tenir
                           // compte de la casse. Voir la doc ici :
                           //  https://www.php.net/manual/en/regexp.introduction.php
                           $req = "~\b$kw\b~ui";
                       } else {
                           $req = "~$kw~ui";
                       };
                       $success = @preg_match_all($req, $filesContents, $out, PREG_PATTERN_ORDER);
                       if ($success !== false) {           // recherche sans erreur
                           if (count($out[0]) > 0) {       // mot trouvé !
                                $assocFileKWCount[$fichier][$kw] = count($out[0]);
                                $kwInFile = true;
                           }
                       }
                   }
                   if ($kwInFile) {
                   foreach ($kwArray as $kw) {
                    if (!in_array($kw, array_keys($assocFileKWCount[$fichier]))) {
                        $kwNotFoundByName[$fichier][] = $kw;
                        // echo "<span class='non-trouve'>
                        // $kw</span>&nbsp;";
                    }}}
               }
               // on ajoute pour chaque fichier le nombre total d'occurences de tous les mots clés
               // avec "nb match" comme mot clé.
               // "nb match" ne peut pas correspondre à un mot clé utilisateur, car contenant " "

               foreach ($assocFileKWCount as &$assocKWCount) {
                   $nbTotMatch = 0;
                   foreach ($assocKWCount as $c) {
                       $nbTotMatch += $c;
                   }
                   $assocKWCount["nb match"] = $nbTotMatch;
               }
               // tri du tableau précédent selon le nombre total d'occurences décroissant
               uasort($assocFileKWCount, function ($a, $b) {
                   if ($a["nb match"] > $b["nb match"]) return -1;
                   if ($a["nb match"] < $b["nb match"]) return 1;
                   return 0;
               });
               // on refait un tri en mettant en tête les fichiers avec le plus de mots clés trouvés
               uasort($assocFileKWCount, function ($a, $b) {
                   if (count($a) > count($b)) return -1;
                   if (count($a) < count($b)) return 1;
                   return 0;
               });

               // fin de la recherche : calcul de la durée totale
               $fin = microtime(true);
               $temps = $fin - $depart;

               // fabrication du contenu html exposant le résutat de la recherche
               // en limitant le nombre de sections à nsecmax
               //echo "<span style='font-style:italic;'>" .
               $statResult = count($assocFileKWCount) . " fichiers(s) trouvé(s) en " .
                   number_format($temps, 3, ',') .'s';
               //    " s</span><br>";
               // $nsec = $nsecmax;
               $idxDoc = 0;
               $i=0;
               $letters=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
               foreach ($assocFileKWCount as $fichier => $kwCount) {

                   $idxDoc += 1;
                   // nettoyage du nom de fichier pour ne garder que le titre de l'équipe
                   $edition = $this->doctrine->getRepository(OdpfEditionsPassees::class)->findOneBy(['edition'=>explode('-', $fichier)[0]]);
                   $numEq = explode('-', $fichier)[2];
                    !in_array($numEq,$letters)? 
                        $equipes[$i]=$this->doctrine->getRepository(OdpfEquipesPassees::class)->findOneBy(['editionspassees'=>$edition,'numero'=>$numEq]):
                       $equipes[$i]=$this->doctrine->getRepository(OdpfEquipesPassees::class)->findOneBy(['editionspassees'=>$edition,'lettre'=>$numEq]);
                   /*$titre[$i] = explode('-', $fichier, $limit = 5); // nom rapport = dernier élément
                   $titre[$i] = substr(end($titre[$i]), 0, -4);    // retrait de ".txt"
                   $titre[$i] = str_replace('-', ' ', $titre[$i]);  // élimination des "-"*/
                   $titre[$i]=$idxDoc.' - '.'Edition '.$edition->getEdition().' - Equipe '.$numEq.' - '. $equipes[$i]->getTitreProjet();
                   //echo "<h3>$idxDoc -  $titre</h3><br>"; // le nom du fichier
                   // Affichage de l'édition

                   //echo "Édition n°$edition - ";
                   $numEd=$edition;
                   //$edition ="Édition n°$edition - ";
                   //  fabrication de l'url du fichier pdf archivé
                   // encodage du nom du fichier comme portion d'url valable
                   $urlTitre = rawurlencode($fichier);
                   $pdfUrl[$i] = "https://www.olymphys.fr/public/odpf/odpf-archives/" .
                       $numEd. "/fichiers/memoires/publie/" .
                       substr($urlTitre, 0, -3) . "pdf";

                   // écriture de la ligne d'info sur les termes trouvés et le lien vers le pdf
                   //echo "</span>&nbsp;<a href=$pdfUrl target='_blank'>afficher le mémoire</a><br>";
                   //echo "<span style='font-style:italic;'>";
                   //echo "<b>mots-clés trouvés : <span style='background-color:yellow;'>" .
                   $nbKeyWord[$i]=    count($kwCount) - 1 . "/" .  count($kwArray);
                   //    "</span></b>&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;";
                  $occurence[$i]= $kwCount["nb match"] . " occurences : ";
                   foreach ($kwCount as $kw => $count) {
                       if ($kw == "nb match") continue;
                       $occurence[$i]= $occurence[$i]. "$kw ($count)" . " ";
                    }
                   //echo "</span><br>";
                   $i++;
               }
            foreach ($kwArray as $kw) {
                if (!in_array($kw, array_keys($kwCount))) {
                    $kwNotFound[] = $kw;
                    // echo "<span class='non-trouve'>
                    // $kw</span>&nbsp;";
                }
            }

            $backColor = [];
            $idx = 0;
            foreach (array_keys($assocFileKWCount) as $f) {
                try {
                    $kwNotFoundByIndex[$idx] = $kwNotFoundByName[$f];
                    $backColor[$idx] = 'lightgrey';
                } catch (Exception $e) {
                    $kwNotFoundByIndex[$idx] = [];
                    $backColor[$idx] = 'lightgreen';
                }
                $idx++;
            }
           }


        return $this->render('search/search.html.twig', ['form' => $form->createView(),
            'titre'=>$titre,
            'statresult'=>$statResult,
            'pdfUrl'=>$pdfUrl,
            'nbKeyWord'=>$nbKeyWord,
            'kwNotFound'=>$kwNotFoundByIndex,
            'backColor' => $backColor,
            'occurence'=>$occurence ?? -1,
            'nsecmax'=>$nsecmax,
            'equipes'=>$equipes]);
    }
}