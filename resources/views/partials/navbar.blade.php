<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <span class="rounded px-2 fw-bold text-white" style="background: var(--phos-accent)">P</span>
            <span class="lh-1">PhosTrack<br><small class="fw-normal opacity-75" style="font-size:.65rem">WebSIG ICS</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       href="{{ route('dashboard') }}">Tableau de bord</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('journal.*') ? 'active' : '' }}"
                       href="{{ route('journal.index') }}">Journal d'activité</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.types-materiau.*') ? 'active' : '' }}"
                    href="{{ route('admin.types-materiau.index') }}">Types de matériau</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}"
                    href="{{ route('stocks.index') }}">Stocks</a>
                </li>

                @if (auth()->user()->aLeRole(\App\Enums\Role::Administrateur))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.utilisateurs.*') ? 'active' : '' }}"
                           href="{{ route('admin.utilisateurs.index') }}">Utilisateurs</a>
                    </li>
                @endif
            </ul>

            <span class="navbar-text me-3">
                {{ auth()->user()->nom_complet }} ({{ auth()->user()->role->libelle() }})
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm">Déconnexion</button>
            </form>
        </div>
    </div>
</nav>