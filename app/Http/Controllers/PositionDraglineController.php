<?php

namespace App\Http\Controllers;

use App\Models\Dragline;
use App\Models\JournalActivite;
use App\Models\PositionDragline;
use App\Services\ParseurPositions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PositionDraglineController extends Controller
{
    /**
     * Saisie manuelle d'une position, au choix
     */
    public function store(Request $request, Dragline $dragline)
    {
        $wgs84 = $request->input('systeme') === 'wgs84';

        if ($wgs84) {
            [$x, $y, $date] = $this->depuisWgs84($request);
        } else {
            [$x, $y, $date] = $this->depuisUtm($request);
        }

        $position = PositionDragline::create([
            'dragline_id' => $dragline->id,
            'technicien_id' => $request->user()->id,
            'date_mesure' => Carbon::parse($date)->startOfDay(),
            'x' => $x,
            'y' => $y,
        ]);

        JournalActivite::enregistrerAction(
            $request->user(),
            'Position de dragline enregistrée ('.$dragline->nom.($wgs84 ? ', saisie en WGS84' : '').')',
            'PositionDragline',
            $position->id
        );

        return redirect()->route('draglines.index', ['dragline' => $dragline->id])
            ->with('succes', 'Position enregistrée.');
    }

    /** @return array{0: float, 1: float, 2: string} X, Y (UTM) et date */
    private function depuisUtm(Request $request): array
    {
        // Virgule décimale et espaces acceptés (« 301 543,93 »)
        $request->merge([
            'x' => str_replace([' ', ','], ['', '.'], (string) $request->input('x')),
            'y' => str_replace([' ', ','], ['', '.'], (string) $request->input('y')),
        ]);

        $data = $request->validateWithBag('manuelle', [
            'date_mesure' => ['required', 'date', 'before_or_equal:today'],
            'x' => ['required', 'numeric', 'between:100000,900000'],
            'y' => ['required', 'numeric', 'between:0,10000000'],
        ], [
            'date_mesure.required' => 'La date de la position est obligatoire.',
            'date_mesure.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'x.required' => 'X est obligatoire.',
            'y.required' => 'Y est obligatoire.',
            'x.numeric' => 'X doit être un nombre (mètres).',
            'y.numeric' => 'Y doit être un nombre (mètres).',
            'x.between' => 'X doit être compris entre 100 000 et 900 000 (UTM 28N, en mètres).',
            'y.between' => 'Y doit être compris entre 0 et 10 000 000 (UTM 28N, en mètres).',
        ]);

        return [(float) $data['x'], (float) $data['y'], $data['date_mesure']];
    }

    /** @return array{0: float, 1: float, 2: string} X, Y (UTM, convertis par PostGIS) et date */
    private function depuisWgs84(Request $request): array
    {
        // « 15°03'46"N », « 15,0629 », « 16 49 15 O »… : lecture tolérante
        $donnees = [
            'date_mesure' => $request->input('date_mesure'),
            'lat' => $this->degres($request->input('lat')),
            'lon' => $this->degres($request->input('lon')),
        ];

        $valide = Validator::make($donnees, [
            'date_mesure' => ['required', 'date', 'before_or_equal:today'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'date_mesure.required' => 'La date de la position est obligatoire.',
            'date_mesure.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'lat.required' => 'La latitude est absente ou non reconnue (exemples : 15,0629 ou 15°03\'46"N).',
            'lon.required' => 'La longitude est absente ou non reconnue (exemples : -16,8210 ou 16°49\'15"O).',
            'lat.between' => 'La latitude doit être comprise entre -90 et 90 degrés.',
            'lon.between' => 'La longitude doit être comprise entre -180 et 180 degrés.',
        ])->validateWithBag('manuelle');

        // Conversion WGS84 (EPSG:4326) -> UTM 28N (EPSG:32628) par PostGIS
        $converti = DB::selectOne(
            'SELECT ST_X(g) AS x, ST_Y(g) AS y
               FROM (SELECT ST_Transform(ST_SetSRID(ST_MakePoint(CAST(? AS double precision), CAST(? AS double precision)), 4326), 32628) AS g) t',
            [$valide['lon'], $valide['lat']]
        );

        $x = (float) $converti->x;
        $y = (float) $converti->y;

        if ($x < 100000 || $x > 900000 || $y < 0 || $y > 10000000) {
            throw ValidationException::withMessages([
                'lon' => 'Position hors de la zone UTM 28N : vérifiez la latitude, la longitude et le signe '
                    .'(la longitude du site est négative, vers -16,8).',
            ])->errorBag('manuelle');
        }

        return [$x, $y, $valide['date_mesure']];
    }

    /**
     * Lecture tolérante d'une coordonnée angulaire (latitude ou longitude) saisie à la main.
     *
     * Formats acceptés :
     *  - degrés décimaux : 15.0629   15,0629   -16,8210
     *  - degrés minutes secondes : 15°03'46,7"N   15 03 46.7 N   16°49'15"O
     *  - degrés minutes décimales : 15°03,78'N   N 15 03.78
     * Hémisphères : N, S, E, O (ou W). Sud et Ouest donnent une valeur négative.
     */
    private function degres(?string $texte): ?float
    {
        if ($texte === null) {
            return null;
        }

        $s = trim($texte);

        if ($s === '') {
            return null;
        }

        // Symboles typographiques -> symboles simples
        $s = strtr($s, [
            '′' => "'", '’' => "'", '‘' => "'", '″' => '"', '”' => '"', '“' => '"',
            'º' => '°', '˚' => '°', '−' => '-', '–' => '-',
        ]);
        $s = str_replace("''", '"', $s);

        // Hémisphère en tête ou en fin de saisie
        $lettre = null;

        if (preg_match('/^([NSEOW])\b\s*(.*)$/iu', $s, $m)) {
            $lettre = strtoupper($m[1]);
            $s = $m[2];
        } elseif (preg_match('/^(.*?)\s*([NSEOW])$/iu', $s, $m)) {
            $lettre = strtoupper($m[2]);
            $s = $m[1];
        }

        // Il ne doit rester que des chiffres, signes, points, virgules, espaces et symboles ° ' "
        if (! preg_match('/^[\s\-+\d.,°\'"]+$/u', $s)) {
            return null;
        }

        $negatif = str_contains($s, '-');
        $estSudOuest = in_array($lettre, ['S', 'O', 'W'], true);

        // Un signe moins contredit un hémisphère Nord ou Est
        if ($negatif && in_array($lettre, ['N', 'E'], true)) {
            return null;
        }

        // Valeurs numériques (virgule décimale acceptée)
        $propre = str_replace(',', '.', $s);

        if (! preg_match_all('/\d+(?:\.\d+)?/', $propre, $m)) {
            return null;
        }

        $nombres = array_map('floatval', $m[0]);
        $textes = $m[0];

        if (count($nombres) > 3 || preg_match('/\d\s*[.]\s*\d+\s*[.]/', $propre) === 1 && count($nombres) === 1) {
            return null;
        }

        // Les degrés (et les minutes, si des secondes suivent) doivent être des entiers
        for ($i = 0; $i < count($nombres) - 1; $i++) {
            if (str_contains($textes[$i], '.')) {
                return null;
            }
        }

        [$d, $mn, $sec] = [$nombres[0], $nombres[1] ?? 0.0, $nombres[2] ?? 0.0];

        if ($mn >= 60 || $sec >= 60) {
            return null;
        }

        $valeur = $d + $mn / 60 + $sec / 3600;

        return ($negatif || $estSudOuest) ? -$valeur : $valeur;
    }

    /** Import d'un fichier de positions (une ligne = une position, X et Y en UTM 28N). */
    public function import(Request $request, Dragline $dragline)
    {
        $request->validateWithBag('import', [
            'date_mesure' => ['required', 'date', 'before_or_equal:today'],
            'fichier' => ['required', 'file', 'max:2048'],
        ], [
            'date_mesure.required' => 'La date de la position est obligatoire.',
            'date_mesure.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'fichier.required' => 'Choisissez un fichier.',
            'fichier.file' => 'Le fichier n\'a pas pu être reçu.',
            'fichier.max' => 'Le fichier dépasse 2 Mo.',
        ]);

        $fichier = $request->file('fichier');

        if (! in_array(strtolower($fichier->getClientOriginalExtension()), ['txt', 'csv'], true)) {
            return back()->withInput()->withErrors(['fichier' => 'Format accepté : TXT ou CSV.'], 'import');
        }

        $resultat = ParseurPositions::analyser(file_get_contents($fichier->getRealPath()));

        if ($resultat['erreurs'] !== []) {
            return back()->withInput()->withErrors(['fichier' => implode(' ', $resultat['erreurs'])], 'import');
        }

        $date = Carbon::parse($request->input('date_mesure'))->startOfDay();

        DB::transaction(function () use ($resultat, $dragline, $request, $date) {
            foreach ($resultat['positions'] as $p) {
                PositionDragline::create([
                    'dragline_id' => $dragline->id,
                    'technicien_id' => $request->user()->id,
                    'date_mesure' => $date,
                    'x' => $p['x'],
                    'y' => $p['y'],
                ]);
            }
        });

        $nb = count($resultat['positions']);

        JournalActivite::enregistrerAction(
            $request->user(), 'Positions de dragline importées ('.$dragline->nom.', '.$nb.')', 'Dragline', $dragline->id
        );

        return redirect()->route('draglines.index', ['dragline' => $dragline->id])
            ->with('succes', $nb.' position'.($nb > 1 ? 's importées' : ' importée').'.');
    }

    /** Suppression d'une position saisie par erreur. */
    public function destroy(Request $request, PositionDragline $position)
    {
        $dragline = $position->dragline;
        $position->delete();

        JournalActivite::enregistrerAction(
            $request->user(), 'Position de dragline supprimée ('.$dragline->nom.')', 'Dragline', $dragline->id
        );

        return redirect()->route('draglines.index', ['dragline' => $dragline->id])
            ->with('succes', 'Position supprimée.');
    }
}