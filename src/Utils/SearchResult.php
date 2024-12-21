<?php

namespace App\Utils;

class FileResult
{

    public
        $filename,              // le nom du fichier texte
        $kwFound = [],          // le tableau associatif kw=>nbre_occurences
        $kwNotFound = [],       // le tableau contenant les mots-clés non trouvés
        $tabkw,                 // le tableau contenant les mots-clés à rechercher
        $nbTotMatch;            // le nombre total d'occurences

    private
        $text,      // le texte contenu dans le fichier
        $fullword;

    // Le constructeur
    function __construct($fileName, $tabkw, $fullword, $text)
    {
        $this->filename = $fileName;
        $this->tabkw = $tabkw;
        $this->fullword = $fullword;
        $this->text = $text;

        return $this;
    }

    // la fonction de balayage du texte, qui remplit $kwFound et $kwNotFound 
    function scanText()
    {
        // on recherche chaque mot-clé dans le texte avec preg_match_all
        // - si on le trouve, on le mets dans comme clé dans $kwFound avec 
        //   son nombre d'occurences comme valeur
        // - si on ne le trouve pas, on le mets dans $kwNotFound
        foreach ($this->tabkw as $kw) {
            // recherche sur des mots entiers ?
            if ($this->fullword == "true") {
                // ~ est le délimiteur de l'expression régulière, \b est le délimiteur de mot,
                // ui à la fin est là pour matcher en UTF8 (pour les accents) sans tenir
                // compte de la casse. Voir la doc ici :
                //  https://www.php.net/manual/en/regexp.introduction.php
                $req = "~\b$kw\b~ui";
            } else {
                $req = "~$kw~ui";
            };
            // @ devant le nom de fonction -> pas de warning/errors dans le flux de sortie
            // Utile car l'utilisateur peut entrer une expression régulière incorrecte !
            $success = @preg_match_all($req, $this->text, $out, PREG_PATTERN_ORDER);
            if ($success !== false) {           // recherche sans erreur
                if (count($out[0]) > 0) {       // mot trouvé !
                    $this->kwFound[$kw] = count($out[0]);
                } else {
                    $this->kwNotFound[] = $kw;
                }
            }
        }
        // Finalisation
        // calcul du nombre d'occurences total
        $this->nbTotMatch = 0;
        foreach ($this->kwFound as $kwcount) {
            $this->nbTotMatch += $kwcount;
        }
        // libération du texte contenu dans le fichier (optimisation mémoire)
        $this->text = "";
    }

    // retourne le titre du mémoire à partir du nom du fichier
    function titre()
    {
        // nettoyage du nom de fichier pour ne garder que le titre du rapport
        $titre = explode('-', $this->filename, $limit = 5); // nom rapport = dernier élément
        $titre = substr(end($titre), 0, -4);    // retrait de ".txt"
        $titre = str_replace('-', ' ', $titre);  // élimination des "-"

        return $titre;
    }

    // retourne le n° de l'édition
    function edition()
    {
        [$edition, $reste] = explode('-', $this->filename, $limit = 2);

        return $edition;
    }

    // retourne l'url du PDF du rapport sur le site olymphys
    function pdfUrl()
    {
        //  fabrication de l'url du fichier pdf archivé
        // encodage du nom du fichier comme portion d'url valable
        $urlTitre = rawurlencode($this->filename);
        $pdfUrl = "https://www.olymphys.fr/public/odpf/odpf-archives/" .
            $this->edition() . "/fichiers/memoires/publie/" .
            substr($urlTitre, 0, -3) . "pdf";

        return $pdfUrl;
    }
}

class SearchResult
{
    private $repertoire, $kwArray, $fullword;

    public
        $result = [],
        $temps;

    function __construct(
        $repertoire = './textes',
        $textes_a_rechercher = '',
        $fullword = true
    ) {
        // Vérifie si le répertoire existe
        if (!is_dir($repertoire)) {
            throw new Exception("Le répertoire spécifié n'existe pas.");
            return;
        }

        $this->repertoire = $repertoire;
        $this->fullword = $fullword;

        // le tableau des mots-clés à rechercher
        // on découpe la chaîne tapée par l'utiisateur en utilisant l'espace comme séparateur
        // et on supprime les chaînes vides (explode retourne des "" pour les espaces ...)
        $this->kwArray = array_filter(
            explode(' ', $textes_a_rechercher),
            function ($k) {
                return $k != '';
            }
        );
    }

    function doSearch()
    {
        // pour le chronmétrage de la recherche
        $depart = microtime(true);

        // Exploration du répertoire des textes
        $scandir = scandir($this->repertoire);
        // balayage des fichiers
        foreach ($scandir as $fichier) {
            // élimination des fichiers spéciaux
            if ($fichier[0] == '.') continue;
            // récupération du contenu du fichier texte
            $filesContents = file_get_contents($this->repertoire . "/" . $fichier);

            $fr = new FileResult(
                $fichier,
                $this->kwArray,
                $this->fullword,
                $filesContents
            );
            $fr->scanText();
            if (count($fr->kwFound) > 0) {
                $this->result[] = $fr;
            }
        }
        // Depuis php 8.0.0, uasort est un tri stable. On peut donc ordonner 
        // les résultats en cascade pour gérer la pertinence des résultats.

        // tri du tableau précédent selon le nombre total d'occurences décroissant
        uasort($this->result, function ($a, $b) {
            if ($a->nbTotMatch > $b->nbTotMatch) return -1;
            if ($a->nbTotMatch < $b->nbTotMatch) return 1;
            return 0;
        });
        // on refait un tri en mettant en tête les fichiers avec le plus de mots clés trouvés
        uasort($this->result, function ($a, $b) {
            if (count($a->kwFound) > count($b->kwFound)) return -1;
            if (count($a->kwFound) < count($b->kwFound)) return 1;
            return 0;
        });

        $fin = microtime(true);
        $this->temps = $fin - $depart;

        return $this;
    }
}
