<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use App\Models\Stock;
use App\Models\TypeMateriau;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with('typeMateriau')->orderBy('nom')->get();

        return view('stocks.index', compact('stocks'));
    }

    public function create()
    {
        $types = TypeMateriau::orderBy('libelle')->get();

        return view('stocks.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150', 'unique:stocks,nom'],
            'type_materiau_id' => ['required', 'exists:types_materiau,id'],
        ]);

        $stock = Stock::create($data);
        JournalActivite::enregistrerAction($request->user(), 'Stock créé', 'Stock', $stock->id);

        return redirect()->route('stocks.index')->with('succes', 'Stock créé.');
    }

    public function edit(Stock $stock)
    {
        $types = TypeMateriau::orderBy('libelle')->get();

        return view('stocks.edit', compact('stock', 'types'));
    }

    public function update(Request $request, Stock $stock)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150', Rule::unique('stocks', 'nom')->ignore($stock->id)],
            'type_materiau_id' => ['required', 'exists:types_materiau,id'],
        ]);

        $stock->update($data);
        JournalActivite::enregistrerAction($request->user(), 'Stock modifié', 'Stock', $stock->id);

        return redirect()->route('stocks.index')->with('succes', 'Stock modifié.');
    }

    public function destroy(Request $request, Stock $stock)
    {
        try {
            $stock->delete();
        } catch (QueryException $e) {
            return back()->with('erreur', 'Ce stock possède des levés : suppression impossible.');
        }

        JournalActivite::enregistrerAction($request->user(), 'Stock supprimé', 'Stock', $stock->id);

        return back()->with('succes', 'Stock supprimé.');
    }
}