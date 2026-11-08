<?php

namespace App\Services;

/**
 * Parseur tolérant des fichiers de positions de dragline.
 *
 * Une ligne = une position : X et Y en UTM 28N (mètres). Tolère :
 *  - un libellé facultatif avant les coordonnées (ex. « MARION.1 ») et une altitude Z facultative après ;
 *  - un séparateur tabulation, point-virgule, espace ou virgule ;
 *  - une ligne d'en-tête ; une décimale point ou virgule.
 * X et Y sont reconnus par leur plage de valeurs UTM (X : 100 000 à 900 000, Y : 0 à 10 000 000).
 */
class ParseurPositions
{
    private const SEPARATEURS = [
        'tabulation' => "\t",
        'point-virgule' => ';',
        'espace' => ' ',
        'virgule' => ',',
    ];

    private const MAX_LIGNES = 200;

    public static function analyser(string $contenu): array
    {
        $contenu = self::normaliser($contenu);

        $lignes = [];
        foreach (preg_split('/\r\n|\r|\n/', $contenu) as $i => $texte) {
            if (trim($texte) !== '') {
                $lignes[] = [$i + 1, rtrim($texte, "\r\n")];
            }
        }

        if ($lignes === []) {
            return self::echec('Le fichier est vide.');
        }

        if (count($lignes) > self::MAX_LIGNES) {
            return self::echec('Le fichier contient plus de '.self::MAX_LIGNES.' lignes : un fichier de positions de dragline est beaucoup plus court.');
        }

        foreach (self::SEPARATEURS as $libelle => $caractere) {
            $resultat = self::essayer($lignes, $caractere);

            if ($resultat !== null) {
                return [
                    'positions' => $resultat,
                    'erreurs' => [],
                    'separateur' => $libelle,
                ];
            }
        }

        return self::echec(
            'Aucune position reconnue : chaque ligne doit contenir X et Y en UTM 28N (mètres), '
            .'par exemple « MARION 301543.93 1669668.19 ». Un libellé avant les coordonnées est facultatif.'
        );
    }

    /** Positions trouvées avec ce séparateur, ou null si une ligne n'est pas exploitable. */
    private static function essayer(array $lignes, string $caractere): ?array
    {
        $positions = [];

        foreach ($lignes as $rang => [$num, $texte]) {
            $cellules = self::decouper($texte, $caractere);
            $xy = self::trouverXY($cellules, $caractere);

            if ($xy === null) {
                // Première ligne sans aucun nombre : en-tête, on l'ignore
                if ($rang === 0 && ! self::contientUnNombre($cellules, $caractere)) {
                    continue;
                }

                return null;
            }

            $positions[] = ['ligne' => $num] + $xy;
        }

        return $positions === [] ? null : $positions;
    }

    /** Premier couple de cellules consécutives dont les valeurs ressemblent à (X, Y) en UTM 28N. */
    private static function trouverXY(array $cellules, string $caractere): ?array
    {
        for ($i = 0; $i < count($cellules) - 1; $i++) {
            $x = self::nombre($cellules[$i], $caractere);
            $y = self::nombre($cellules[$i + 1], $caractere);

            if ($x !== null && $y !== null && $x >= 100000 && $x <= 900000 && $y >= 0 && $y <= 10000000) {
                $libelle = $i > 0 ? trim($cellules[0]) : '';

                return ['x' => $x, 'y' => $y, 'libelle' => $libelle];
            }
        }

        return null;
    }

    private static function contientUnNombre(array $cellules, string $caractere): bool
    {
        foreach ($cellules as $c) {
            if (self::nombre($c, $caractere) !== null) {
                return true;
            }
        }

        return false;
    }

    private static function decouper(string $ligne, string $caractere): array
    {
        if ($caractere === ' ') {
            return preg_split('/\s+/', trim($ligne));
        }

        return array_map('trim', explode($caractere, $ligne));
    }

    private static function nombre(string $valeur, string $caractere): ?float
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            return null;
        }

        if ($caractere !== ',') {
            $valeur = str_replace(',', '.', $valeur);
        }

        return preg_match('/^[+-]?\d+(\.\d+)?$/', $valeur) ? (float) $valeur : null;
    }

    private static function normaliser(string $contenu): string
    {
        if (str_starts_with($contenu, "\xEF\xBB\xBF")) {
            $contenu = substr($contenu, 3);
        }

        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'ISO-8859-1');
        }

        return $contenu;
    }

    private static function echec(string $message): array
    {
        return ['positions' => [], 'erreurs' => [$message], 'separateur' => null];
    }
}