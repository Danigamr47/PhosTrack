<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalActivite;
use App\Models\TypeMateriau;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TypeMateriauController extends Controller
{
    public function index()
    {
        $types = TypeMateriau::orderBy('libelle')->get();

        return view('admin.types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100', 'unique:types_materiau,libelle'],
        ]);

        $type = TypeMateriau::create($data);
        JournalActivite::enregistrerAction($request->user(), 'Type de matériau créé', 'TypeMateriau', $type->id);

        return back()->with('succes', 'Type de matériau ajouté.');
    }

    public function edit(TypeMateriau $type)
    {
        return view('admin.types.edit', compact('type'));
    }

    public function update(Request $request, TypeMateriau $type)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100',
                Rule::unique('types_materiau', 'libelle')->ignore($type->id)],
        ]);

        $type->update($data);
        JournalActivite::enregistrerAction($request->user(), 'Type de matériau modifié', 'TypeMateriau', $type->id);

        return redirect()->route('admin.types-materiau.index')->with('succes', 'Type de matériau modifié.');
    }

    public function destroy(Request $request, TypeMateriau $type)
    {
        try {
            $type->delete();
        } catch (QueryException $e) {
            return back()->with('erreur', 'Ce type est utilisé par au moins un stock : suppression impossible.');
        }

        JournalActivite::enregistrerAction($request->user(), 'Type de matériau supprimé', 'TypeMateriau', $type->id);

        return back()->with('succes', 'Type de matériau supprimé.');
    }
}