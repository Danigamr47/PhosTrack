@php
    $user = auth()->user();
    $admin = [\App\Enums\Role::Administrateur];

    // [route, motif de la page active, libellé, rôles autorisés (null = tous les rôles)]
    $menu = [
        ['carte',                      'carte*',                 'Carte',              null],
        ['dashboard',                  'dashboard',              'Tableau de bord',    null],
        ['stocks.index',               'stocks.*',               'Stocks',             null],
        ['leves.index',                'leves.*',                'Levés',              null],
        ['admin.utilisateurs.index',   'admin.utilisateurs.*',   'Utilisateurs',       $admin],
        ['admin.types-materiau.index', 'admin.types-materiau.*', 'Types de matériau',  $admin],
        ['admin.journal.index',        'admin.journal.*',        "Journal d'activité", $admin],
    ];
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-phos-nav">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('carte') }}">
            <span class="rounded px-2 fw-bold text-white" style="background: var(--phos-alerte)">P</span>
            <span class="lh-1">PhosTrack<br><small class="fw-normal opacity-75" style="font-size:.65rem">WebSIG ICS</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                @foreach ($menu as [$route, $motif, $libelle, $roles])
                    @if ($roles === null || $user->aLeRole(...$roles))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs($motif) ? 'active' : '' }}"
                               href="{{ route($route) }}">{{ $libelle }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <span class="navbar-text lh-sm text-end text-white me-3">
                <span class="fw-semibold">{{ $user->role->libelle() }}</span><br>
                <small class="opacity-75">{{ $user->nom_complet }}</small>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm">Déconnexion</button>
            </form>
        </div>
    </div>
</nav>