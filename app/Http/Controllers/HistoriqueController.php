<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutLeve;
use App\Models\Campagne;
use App\Models\Leve;
use App\Models\Stock;
use App\Models\TypeMateriau;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoriqueController extends Controller
{
    private const COULEURS = [
        'Phosphate' => '#C9A24B',
        'Stérile'   => '#8A7F72',
        'Charbon'   => '#2B2B2E',
    ];

    public function index(Request $request)
    {
        $utilisateur = auth()->user();
        $estConsultant = $utilisateur->aLeRole(Role::Consultant);

        $stockId = $request->integer('stock_id') ?: null;
        $campagneId = $request->integer('campagne_id') ?: null;
        $typeMateriauId = $request->integer('type_materiau_id') ?: null;

        $query = Leve::with(['stock.typeMateriau', 'campagne', 'technicien'])
            ->where('statut', StatutLeve::Valide->value)
            ->when($stockId, fn ($q) => $q->where('stock_id', $stockId))
            ->when($campagneId, fn ($q) => $q->where('campagne_id', $campagneId))
            ->when($typeMateriauId, fn ($q) => $q->whereHas('stock', fn ($sq) => $sq->where('type_materiau_id', $typeMateriauId)))
            ->orderByDesc('date_leve')
            ->orderByDesc('id');

        $leves = $query->paginate(15)->withQueryString();

        $leveIds = $leves->pluck('id')->all();
        $deltas = collect();

        if (! empty($leveIds)) {
            $placeholders = implode(',', array_fill(0, count($leveIds), '?'));
            $deltas = collect(DB::select("
                WITH fenetre AS (
                    SELECT
                        id,
                        volume,
                        LAG(volume) OVER (PARTITION BY stock_id ORDER BY date_leve ASC, id ASC) AS volume_precedent,
                        LAG(date_leve) OVER (PARTITION BY stock_id ORDER BY date_leve ASC, id ASC) AS date_precedente
                    FROM leves
                    WHERE statut = ?
                )
                SELECT id, volume_precedent, date_precedente
                FROM fenetre
                WHERE id IN ($placeholders)
            ", [StatutLeve::Valide->value, ...$leveIds]))->keyBy('id');
        }

        $leves->getCollection()->transform(function ($leve) use ($deltas) {
            $materiau = $leve->stock->typeMateriau->libelle ?? 'Inconnu';
            $leve->couleur = self::COULEURS[$materiau] ?? '#2F7DB5';

            $infoPrec = $deltas->get($leve->id);
            $volPrec = $infoPrec && $infoPrec->volume_precedent !== null ? (float) $infoPrec->volume_precedent : null;

            $leve->volume_precedent = $volPrec;
            $leve->date_precedente = $infoPrec?->date_precedente;
            $leve->delta = $volPrec !== null ? ((float) $leve->volume - $volPrec) : null;
            $leve->variation_pct = ($volPrec !== null && $volPrec > 0) ? (($leve->delta / $volPrec) * 100) : null;

            return $leve;
        });

        // 3. Comparaison dès qu'un stock est sélectionné
        $comparaison = null;
        $leveAId = $request->integer('leve_a') ?: null;
        $leveBId = $request->integer('leve_b') ?: null;

        if ($stockId) {
            $stock = Stock::with('typeMateriau')->find($stockId);
            $levesDuStock = Leve::with('campagne')
                ->where('stock_id', $stockId)
                ->where('statut', StatutLeve::Valide->value)
                ->orderByDesc('date_leve')
                ->orderByDesc('id')
                ->get();

            $recent = $leveAId ? $levesDuStock->firstWhere('id', $leveAId) : $levesDuStock->get(0);
            $ancien = $leveBId ? $levesDuStock->firstWhere('id', $leveBId) : $levesDuStock->get(1);

            if ($stock && $recent && $ancien && $recent->id !== $ancien->id) {
                $deltaComp = (float) $recent->volume - (float) $ancien->volume;
                $pctComp = $ancien->volume > 0 ? ($deltaComp / (float) $ancien->volume) * 100 : null;
                $matLibelle = $stock->typeMateriau->libelle ?? 'Inconnu';

                $comparaison = (object) [
                    'stock'         => $stock,
                    'couleur'       => self::COULEURS[$matLibelle] ?? '#2F7DB5',
                    'recent'        => $recent,
                    'ancien'        => $ancien,
                    'delta'         => $deltaComp,
                    'variation_pct' => $pctComp,
                    'leves_dispo'   => $levesDuStock,
                ];
            }
        }

        $stocks = Stock::with('typeMateriau')->orderBy('nom')->get();
        $campagnes = Campagne::orderByDesc('annee')->orderByDesc('mois')->get();
        $typesMateriaux = TypeMateriau::orderBy('libelle')->get()->each(function ($t) {
            $t->couleur = self::COULEURS[$t->libelle] ?? '#2F7DB5';
        });

        return view('historique.index', [
            'leves'          => $leves,
            'stocks'         => $stocks,
            'campagnes'      => $campagnes,
            'typesMateriaux' => $typesMateriaux,
            'comparaison'    => $comparaison,
            'estConsultant'  => $estConsultant,
            'filtres'        => [
                'stock_id'         => $stockId,
                'campagne_id'      => $campagneId,
                'type_materiau_id' => $typeMateriauId,
                'leve_a'           => $leveAId,
                'leve_b'           => $leveBId,
            ],
        ]);
    }
}