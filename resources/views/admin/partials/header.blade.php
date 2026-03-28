<div class="shell">
    <div class="topbar">
        <div class="brand">
            <h1>POS Saia Admin</h1>
            <p>Stato database, inventario operativo e fatture passive.</p>
        </div>

        <div class="nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.inventory') }}" class="{{ request()->routeIs('admin.inventory') || request()->routeIs('admin.products.*') || request()->routeIs('admin.variants.*') || request()->routeIs('admin.suppliers.*') || request()->routeIs('admin.warehouses.*') ? 'active' : '' }}">Magazzino</a>
            <a href="{{ route('admin.invoices.index') }}" class="{{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">Fatture</a>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>

    <div class="content">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>
</div>
