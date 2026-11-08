<?php

namespace App\Http\Controllers;

use App\Models\Dragline;
use App\Models\JournalActivite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DraglineController extends Controller
{
    /** Suivi des draglines : un onglet par dragline (tous les rôles peuvent consulter). */
    public function index(Request $request)
    {
        $draglines = Dragline::orderBy('id')->get();
        $active = $draglines->firstWhere('id', (int) $request->query('dragline')) ?? $draglines->first();

        $points = [];
        $historique = collect();
        $resume = ['nb' => 0, 'derniere' => null, 'distance' => 0.0];

        if ($active) {
            $positions = $active->positions()
                ->with('technicien')
                ->orderBy('date_mesure')
                ->orderBy('id')
                ->get();

            // Longitude / latitude calculées par PostGIS, pour afficher les positions sur la carte
            $geo = collect(DB::select(
                'SELECT id,
                        ST_X(ST_Transform(ST_SetSRID(ST_MakePoint(x, y), 32628), 4326)) AS lon,
                        ST_Y(ST_Transform(ST_SetSRID(ST_MakePoint(x, y), 32628), 4326)) AS lat
                 FROM position_draglines
                 WHERE dragline_id = ?',
                [$active->id]
            ))->keyBy('id');

            $precedente = null;

            foreach ($positions as $p) {
                // Avancement = distance en mètres depuis la position précédente (coordonnées UTM)
                $avancement = $precedente
                    ? sqrt(($p->x - $precedente->x) ** 2 + ($p->y - $precedente->y) ** 2)
                    : null;

                $p->avancement = $avancement;
                $p->lon = (float) $geo[$p->id]->lon;
                $p->lat = (float) $geo[$p->id]->lat;
                $resume['distance'] += $avancement ?? 0;
                $precedente = $p;

                $points[] = [
                    'id' => $p->id,
                    'date' => $p->date_mesure->format('d/m/Y'),
                    'x' => $p->x,
                    'y' => $p->y,
                    'lon' => (float) $geo[$p->id]->lon,
                    'lat' => (float) $geo[$p->id]->lat,
                    'avancement' => $avancement,
                ];
            }

            $resume['nb'] = $positions->count();
            $resume['derniere'] = $positions->last()?->date_mesure;
            $historique = $positions->reverse()->values();
        }

        return view('draglines.index', compact('draglines', 'active', 'points', 'historique', 'resume'));
    }

    public function store(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            ['nom' => ['required', 'string', 'max:50', 'unique:draglines,nom']],
            [
                'nom.required' => 'Le nom de la dragline est obligatoire.',
                'nom.unique' => 'Une dragline porte déjà ce nom.',
                'nom.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            ]
        );

        if ($validation->fails()) {
            return back()->with('erreur', $validation->errors()->first('nom'));
        }

        $dragline = Dragline::create(['nom' => trim($request->input('nom'))]);
        JournalActivite::enregistrerAction($request->user(), 'Dragline ajoutée', 'Dragline', $dragline->id);

        return redirect()->route('draglines.index', ['dragline' => $dragline->id])
            ->with('succes', 'Dragline « '.$dragline->nom.' » ajoutée.');
    }

    public function update(Request $request, Dragline $dragline)
    {
        $validation = Validator::make(
            $request->all(),
            ['nom' => ['required', 'string', 'max:50', Rule::unique('draglines', 'nom')->ignore($dragline->id)]],
            [
                'nom.required' => 'Le nom de la dragline est obligatoire.',
                'nom.unique' => 'Une dragline porte déjà ce nom.',
                'nom.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            ]
        );

        if ($validation->fails()) {
            return back()->with('erreur', $validation->errors()->first('nom'));
        }

        $dragline->update(['nom' => trim($request->input('nom'))]);
        JournalActivite::enregistrerAction($request->user(), 'Dragline renommée', 'Dragline', $dragline->id);

        return redirect()->route('draglines.index', ['dragline' => $dragline->id])
            ->with('succes', 'Dragline renommée.');
    }
}