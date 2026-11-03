<?php

namespace App\Services;

/**
 * Parseur tolérant des fichiers de points topographiques (PIED / SEMIS).
 *
 * Colonnes attendues : ID, X, Y, Z (l'ID est obligatoire).
 * Tolère : séparateur tabulation, point-virgule, espace ou virgule ;
 * présence ou non d'une ligne d'en-tête ; décimale point ou virgule.
 */
class ParseurPoints
{
    private const SEPARATEURS = [
        'tabulation' => "\t",
        'point-virgule' => ';',
        'espace' => ' ',
        'virgule' => ',',
    ];

    private const MAX_MESSAGES = 5;

    public static function analyser(string $contenu): array
    {
        $contenu = self::normaliser($contenu);

        // Lignes non vides, avec leur numéro d'origine
        $lignes = [];
        foreach (preg_split('/\r\n|\r|\n/', $contenu) as $i => $texte) {
            if (trim($texte) !== '') {
                $lignes[] = [$i + 1, rtrim($texte, "\r\n")];
            }
        }

        if ($lignes === []) {
            return self::echec('Le fichier est vide.');
        }

        $separateur = self::detecterSeparateur($lignes);

        if ($separateur === null) {
            return self::echec(
                'Format non reconnu : le fichier doit contenir quatre colonnes — ID, X, Y, Z — '
                .'séparées par une tabulation, un point-virgule, un espace ou une virgule. '
                .'L\'ID est obligatoire.'
            );
        }

        [$libelle, $caractere] = $separateur;

        $points = [];
        $ignorees = [];
        $erreurs = [];
        $vus = [];
        $entete = false;

        foreach ($lignes as $position => [$num, $texte]) {
            $cellules = self::decouper($texte, $caractere);

            // Première ligne : en-tête si X, Y, Z ne sont pas numériques
            if ($position === 0 && self::estEntete($cellules, $caractere)) {
                $entete = true;
                continue;
            }

            $id = $cellules[0] ?? '';

            if ($id === '') {
                $erreurs[] = "Ligne $num : ID manquant.";
                continue;
            }

            $x = self::nombre($cellules[1] ?? '', $caractere);
            $y = self::nombre($cellules[2] ?? '', $caractere);
            $z = self::nombre($cellules[3] ?? '', $caractere);

            if ($x === null || $y === null) {
                $ignorees[] = ['ligne' => $num, 'raison' => 'coordonnée X ou Y invalide'];
                continue;
            }

            if ($z === null) {
                $ignorees[] = ['ligne' => $num, 'raison' => 'altitude (Z) manquante'];
                continue;
            }

            if (isset($vus[$id])) {
                $erreurs[] = "ID en double : $id (lignes {$vus[$id]} et $num).";
                continue;
            }

            $vus[$id] = $num;
            $points[] = ['id_point' => $id, 'x' => $x, 'y' => $y, 'z' => $z];
        }

        if ($points === [] && $erreurs === []) {
            $erreurs[] = 'Aucun point valide dans ce fichier (colonnes attendues : ID, X, Y, Z).';
        }

        if ($points !== []) {
            $xs = array_column($points, 'x');
            $ys = array_column($points, 'y');

            if (min($xs) < 100000 || max($xs) > 900000 || min($ys) < 0 || max($ys) > 10000000) {
                $erreurs[] = 'Les coordonnées ne ressemblent pas à de l\'UTM 28N (en mètres) : '
                    .'vérifiez le système de coordonnées du fichier.';
            }
        }

        return [
            'points' => $erreurs === [] ? $points : [],
            'ignorees' => $ignorees,
            'erreurs' => self::limiter($erreurs),
            'separateur' => $libelle,
            'entete' => $entete,
        ];
    }

    private static function normaliser(string $contenu): string
    {
        // Retire le BOM UTF-8
        if (str_starts_with($contenu, "\xEF\xBB\xBF")) {
            $contenu = substr($contenu, 3);
        }

        // Fichiers exportés en ANSI (Windows-1252 / ISO-8859-1)
        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'ISO-8859-1');
        }

        return $contenu;
    }

    /**
     * Premier séparateur pour lequel l'échantillon est cohérent :
     * X et Y numériques partout, et au moins la moitié des lignes complètes (ID, X, Y, Z).
     * Les lignes sans altitude restent tolérées (elles seront ignorées puis signalées).
     */
    private static function detecterSeparateur(array $lignes): ?array
    {
        $echantillon = array_slice($lignes, 0, 20);

        foreach (self::SEPARATEURS as $libelle => $caractere) {
            $valide = true;
            $donnees = 0;
            $completes = 0;

            foreach ($echantillon as $i => [, $texte]) {
                $cellules = self::decouper($texte, $caractere);

                if (count($cellules) < 3) {
                    $valide = false;
                    break;
                }

                $num = array_map(
                    fn ($c) => self::nombre($c, $caractere) !== null,
                    array_slice($cellules, 1, 3)
                );

                // Première ligne : en-tête possible (aucune valeur numérique)
                if ($i === 0 && ! in_array(true, $num, true)) {
                    continue;
                }

                if (! ($num[0] && $num[1])) {
                    $valide = false;
                    break;
                }

                $donnees++;

                if (count($cellules) >= 4 && ($num[2] ?? false)) {
                    $completes++;
                }
            }

            if ($valide && $donnees > 0 && $completes * 2 >= $donnees) {
                return [$libelle, $caractere];
            }
        }

        return null;
    }

    private static function decouper(string $ligne, string $caractere): array
    {
        if ($caractere === ' ') {
            return preg_split('/\s+/', trim($ligne));
        }

        return array_map('trim', explode($caractere, $ligne));
    }

    private static function estEntete(array $cellules, string $caractere): bool
    {
        if (count($cellules) < 4) {
            return false;
        }

        foreach (array_slice($cellules, 1, 3) as $c) {
            if (self::nombre($c, $caractere) !== null) {
                return false;
            }
        }

        return true;
    }

    private static function nombre(string $valeur, string $caractere): ?float
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            return null;
        }

        // Virgule décimale, sauf si la virgule est le séparateur
        if ($caractere !== ',') {
            $valeur = str_replace(',', '.', $valeur);
        }

        return preg_match('/^[+-]?\d+(\.\d+)?$/', $valeur) ? (float) $valeur : null;
    }

    private static function limiter(array $messages): array
    {
        if (count($messages) <= self::MAX_MESSAGES) {
            return $messages;
        }

        $reste = count($messages) - self::MAX_MESSAGES;

        return [...array_slice($messages, 0, self::MAX_MESSAGES), "… et $reste autre(s) erreur(s)."];
    }

    private static function echec(string $message): array
    {
        return ['points' => [], 'ignorees' => [], 'erreurs' => [$message], 'separateur' => null, 'entete' => false];
    }
}
