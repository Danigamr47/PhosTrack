<?php

namespace App\Http\Controllers;

use App\Models\JournalActivite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Seul le mot de passe est modifiable par l'utilisateur.
        // Nom, prénom et matricule sont gérés par l'Administrateur.
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required'         => 'Le mot de passe actuel est obligatoire.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required'                 => 'Le nouveau mot de passe est obligatoire.',
            'password.confirmed'                => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.different'                => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        JournalActivite::enregistrerAction($user, 'Mot de passe modifié', 'Utilisateur', $user->id);

        return redirect()->route('profile.edit')
            ->with('succes', 'Mot de passe mis à jour avec succès.');
    }
}