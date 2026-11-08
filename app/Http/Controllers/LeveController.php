<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutLeve;
use App\Models\Campagne;
use App\Models\JournalActivite;
use App\Models\Leve;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeveController extends Controller
{
    public function index(Request $request)
    {
    $stockFiltre = $request->filled('stock') ? Stock::find((int) $request->query('stock')) : null;

    $leves = $this->portee($request)
        ->when($stockFiltre, fn ($q) => $q->where('stock_id', $stockFiltre->id))
        ->with(['stock.typeMateriau', 'campagne', 'technicien'])
        ->orderByDesc('date_leve')
        ->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

     return view('leves.index', compact('leves', 'stockFiltre'));
    }

    public function create()
    {
    return view('leves.atelier', $this->donneesFormulaire() + ['leve' => null, 'stats' => collect(), 'precedent' => null]);

    }

    public function store(Request $request)
    {
        $data = $this->validerRequete($request); // <-- Changé ici

        $leve = Leve::create([
            'date_leve' => $data['date_leve'],
            'commentaire' => $data['commentaire'] ?? null,
            'stock_id' => $data['stock_id'],
            'campagne_id' => $this->campagneDepuisCle($data['campagne'])->id,
            'technicien_id' => $request->user()->id,
            'statut' => StatutLeve::Brouillon,
        ]);

        JournalActivite::enregistrerAction($request->user(), 'Levé créé (brouillon)', 'Leve', $leve->id);

        return redirect()->route('leves.edit', $leve)
            ->with('succes', 'Brouillon enregistré. Déposez maintenant les fichiers de points.');
    }

    public function show(Request $request, Leve $leve)
    {
        abort_unless($this->portee($request)->whereKey($leve->id)->exists(), 404);

        $leve->load(['stock.typeMateriau', 'campagne', 'technicien']);

          $leve->load(['stock.typeMateriau', 'campagne', 'technicien']);
            $stats = $this->statistiques($leve);

            return view('leves.show', [
                'leve' => $leve,
                'stats' => $stats,
                'precedent' => $this->levePrecedent($leve),
            ]);
    }

    public function edit(Leve $leve)
    {
    $this->verifierModifiable($leve);

    return view('leves.atelier', $this->donneesFormulaire($leve) + [
        'leve' => $leve,
        'stats' => $this->statistiques($leve),
        'precedent' => $this->levePrecedent($leve),
    ]);
    }

    private function statistiques(Leve $leve)
    {
    return $leve->points()
        ->selectRaw('type, count(*) as n, min(z) as zmin, max(z) as zmax')
        ->groupBy('type')
        ->get()
        ->keyBy('type');
    }

    public function update(Request $request, Leve $leve)
    {
        $this->verifierModifiable($leve);
        $data = $this->validerRequete($request); // <-- Changé ici

        $leve->update([
            'date_leve' => $data['date_leve'],
            'commentaire' => $data['commentaire'] ?? null,
            'stock_id' => $data['stock_id'],
            'campagne_id' => $this->campagneDepuisCle($data['campagne'])->id,
        ]);

        JournalActivite::enregistrerAction($request->user(), 'Levé modifié', 'Leve', $leve->id);

        return redirect()->route('leves.edit', $leve)->with('succes', 'Informations enregistrées.');
    }

    public function destroy(Request $request, Leve $leve)
    {
        $this->verifierModifiable($leve);

        $leve->delete();

        JournalActivite::enregistrerAction($request->user(), 'Levé supprimé', 'Leve', $leve->id);

        return redirect()->route('leves.index')->with('succes', 'Levé supprimé.');
    }

    /** Le Consultant ne voit que les levés validés. */
    private function portee(Request $request)
    {
        $requete = Leve::query();

        if ($request->user()->aLeRole(Role::Consultant)) {
            $requete->where('statut', StatutLeve::Valide->value);
        }

        return $requete;
    }

    private function verifierModifiable(Leve $leve): void
    {
        abort_if($leve->estValide(), 403, 'Un levé validé ne peut plus être modifié ni supprimé.');
    }

    private function campagneDepuisCle(string $cle): Campagne
    {
        [$annee, $mois] = array_map('intval', explode('-', $cle));

        return Campagne::firstOrCreate(['annee' => $annee, 'mois' => $mois]);
    }

    private function donneesFormulaire(?Leve $leve = null): array
    {
        $campagnes = [];
        for ($i = 0; $i < 13; $i++) {
            $mois = now()->startOfMonth()->subMonths($i);
            $campagnes[$mois->format('Y-m')] = ucfirst($mois->locale('fr')->translatedFormat('F Y'));
        }

        if ($leve && ! isset($campagnes[$leve->campagne->cle])) {
            $campagnes[$leve->campagne->cle] = $leve->campagne->libelle;
        }

        return [
            'stocks' => Stock::with('typeMateriau')->orderBy('nom')->get(),
            'campagnes' => $campagnes,
            'campagneChoisie' => $leve?->campagne->cle ?? now()->format('Y-m'),
        ];
    }

    public function calculer(Request $request, Leve $leve)
    {
        $this->verifierModifiable($leve);

        $stats = $this->statistiques($leve);
        $pied = $stats->get('pied');
        $semis = $stats->get('semis');

        if (! $pied || ! $semis) {
            return back()->with('erreur', 'Déposez les fichiers PIED et SEMIS avant de calculer.');
        }

        if ((int) $pied->n < 3) {
            return back()->with('erreur', 'Il faut au moins 3 points de pied pour construire le modèle du sol.');
        }

        try {
            $r = DB::selectOne('SELECT * FROM calculer_volume_leve(?)', [$leve->id]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('erreur', 'Le calcul a échoué : vérifiez les points (alignés ou en double).');
        }

        if (! $r || $r->volume === null) {
            return back()->with('erreur', 'Calcul impossible : les points de pied doivent entourer les points de semis.');
        }

        $hors = (int) $r->nb_semis_hors_emprise;

        if ($hors > (int) $semis->n) {
            return back()->with('erreur', "Les fichiers PIED et SEMIS ne semblent pas correspondre au même tas : {$hors} points de semis sur {$semis->n} sont hors du contour des points de pied.");
        }

        if ($r->volume <= 0) {
            return back()->with('erreur', 'Volume nul ou négatif : vérifiez que les fichiers PIED et SEMIS correspondent au même tas.');
        }

        $leve->update([
            'volume' => $r->volume,
            'surface_sol' => $r->surface_sol,
            'hauteur_max' => $r->hauteur_max,
            'statut' => StatutLeve::Calcule,
        ]);

        JournalActivite::enregistrerAction($request->user(), 'Volume calculé', 'Leve', $leve->id);

        $retour = redirect()->route('leves.edit', $leve)
            ->with('succes', 'Volume calculé : '.number_format($r->volume, 1, ',', ' ').' m³.');

        if ($hors > 0) {
            $retour->with('avertissement', "{$hors} point(s) de semis sont en dehors du contour des points de pied : le volume peut être sous-estimé.");
        }

        return $retour;
    }

    public function valider(Request $request, Leve $leve)
    {
        $this->verifierModifiable($leve);

        if ($leve->statut !== StatutLeve::Calcule || $leve->volume === null) {
            return back()->with('erreur', 'Calculez le volume avant de valider le levé.');
        }

        $leve->update(['statut' => StatutLeve::Valide]);

        JournalActivite::enregistrerAction($request->user(), 'Levé validé', 'Leve', $leve->id);

        return redirect()->route('leves.show', $leve)->with('succes', 'Levé validé et enregistré.');
    }

    /** Dernier levé validé du même stock, avant la date de celui-ci. */
    private function levePrecedent(Leve $leve): ?Leve
    {
        return Leve::where('stock_id', $leve->stock_id)
            ->where('statut', StatutLeve::Valide->value)
            ->where('id', '!=', $leve->id)
            ->where('date_leve', '<', $leve->date_leve)
            ->orderByDesc('date_leve')
            ->orderByDesc('id')
            ->first();
    }
    private function validerRequete(Request $request): array
    {
        return $request->validate([
            'date_leve' => ['required', 'date'],
            'commentaire' => ['nullable', 'string'],
            'stock_id' => ['required', 'exists:stocks,id'],
            'campagne' => ['required', 'string'], // format 'Y-m'
        ]);
    }


}