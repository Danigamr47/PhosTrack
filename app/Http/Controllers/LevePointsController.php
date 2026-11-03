<?php

namespace App\Http\Controllers;

use App\Enums\StatutLeve;
use App\Models\JournalActivite;
use App\Models\Leve;
use App\Models\PointLeve;
use App\Services\ParseurPoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LevePointsController extends Controller
{
    public function store(Request $request, Leve $leve, string $type)
    {
        abort_if($leve->estValide(), 403, 'Un levé validé ne peut plus être modifié.');

        $fichier = $request->file('fichier');

        if (! $fichier || ! $fichier->isValid()) {
            return $this->erreur($type, ['Le fichier n\'a pas pu être reçu (taille maximale dépassée ?).']);
        }

        if (! in_array(strtolower($fichier->getClientOriginalExtension()), ['txt', 'csv'], true)) {
            return $this->erreur($type, ['Format accepté : TXT ou CSV.']);
        }

        if ($fichier->getSize() > 20 * 1024 * 1024) {
            return $this->erreur($type, ['Le fichier dépasse 20 Mo.']);
        }

        $resultat = ParseurPoints::analyser(file_get_contents($fichier->getRealPath()));

        if ($resultat['erreurs'] !== []) {
            return $this->erreur($type, $resultat['erreurs']);
        }

        DB::transaction(function () use ($leve, $type, $resultat) {
            // Un nouveau fichier remplace l'ancien
            $leve->points()->where('type', $type)->delete();

            foreach (array_chunk($resultat['points'], 1000) as $lot) {
                PointLeve::insert(array_map(
                    fn (array $p) => $p + ['leve_id' => $leve->id, 'type' => $type],
                    $lot
                ));
            }

            $this->invaliderCalcul($leve);
        });

        JournalActivite::enregistrerAction(
            $request->user(),
            'Points '.strtoupper($type).' importés ('.count($resultat['points']).')',
            'Leve',
            $leve->id
        );

        return back()->with('apercu', [
            'type' => $type,
            'fichier' => $fichier->getClientOriginalName(),
            'separateur' => $resultat['separateur'],
            'ignorees' => $resultat['ignorees'],
            'entete' => $resultat['entete'],
        ]);
    }

    public function destroy(Request $request, Leve $leve, string $type)
    {
        abort_if($leve->estValide(), 403, 'Un levé validé ne peut plus être modifié.');

        DB::transaction(function () use ($leve, $type) {
            $leve->points()->where('type', $type)->delete();
            $this->invaliderCalcul($leve);
        });

        JournalActivite::enregistrerAction(
            $request->user(), 'Points '.strtoupper($type).' retirés', 'Leve', $leve->id
        );

        return back()->with('succes', 'Fichier '.strtoupper($type).' retiré.');
    }

    /** Si les points changent, un résultat déjà calculé n'est plus valable. */
    private function invaliderCalcul(Leve $leve): void
    {
        if ($leve->statut === StatutLeve::Calcule) {
            $leve->update([
                'statut' => StatutLeve::Brouillon,
                'volume' => null,
                'surface_sol' => null,
                'hauteur_max' => null,
            ]);
        }
    }

    private function erreur(string $type, array $messages)
    {
        return back()->with('erreurs_fichier', ['type' => $type, 'messages' => $messages]);
    }
}