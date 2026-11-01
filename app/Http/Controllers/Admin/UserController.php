<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Connexion;
use App\Models\JournalActivite;

class UserController extends Controller
{
    public function index()
    {

        // Utilisateurs actifs dans les 5 dernières minutes (table sessions)
        $enLigne = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->pluck('user_id')
            ->unique();

        return view('admin.users.index', [
            'utilisateurs' => User::withMax('connexions', 'date_connexion')->paginate(15),
            'enLigne' => $enLigne,
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:20', 'unique:users,matricule'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data); JournalActivite::enregistrerAction($request->user(), 'Compte créé', 'Utilisateur', $user->id);

        return redirect()->route('admin.utilisateurs.index')->with('succes', 'Compte créé.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:20', Rule::unique('users', 'matricule')->ignore($user->id)],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->is($request->user()) && $data['role'] !== Role::Administrateur->value) {
            return back()->with('erreur', 'Vous ne pouvez pas retirer votre propre rôle d\'administrateur.');
        }

        if (empty($data['password'])) {
            unset($data['password']); // mot de passe inchangé
        }

        $user->update($data);

        return redirect()->route('admin.utilisateurs.index')->with('succes', 'Compte modifié.');
    }

    public function basculerStatut(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('erreur', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

       $user->update(['actif' => ! $user->actif]);

        JournalActivite::enregistrerAction(
            $request->user(),
            $user->actif ? 'Compte activé' : 'Compte désactivé',
            'Utilisateur',
            $user->id
        );

        return back()->with('succes', $user->actif ? 'Compte activé.' : 'Compte désactivé.');
       
    }
}