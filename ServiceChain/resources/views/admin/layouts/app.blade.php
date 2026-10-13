
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - ServiceChain</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <aside class="admin-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-icon">
                <i class="bi bi-shield-check"></i>
            </span>
            <span>ServiceChain</span>
        </a>

        <div class="sidebar-divider"></div>

        <div class="sidebar-section-title">
            MENU UTAMA
        </div>

        <nav class="sidebar-menu">
            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid"></i>
                <span>Ringkasan</span>
            </a>

            <a href="{{ route('admin.users.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Pengguna</span>
            </a>

            <a href="{{ route('admin.workshops.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.workshops.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Manajemen Bengkel</span>
                @if (($pendingWorkshopCount ?? 0) > 0)
                    <span class="menu-badge">{{ $pendingWorkshopCount }}</span>
                @endif
            </a>

            <a href="{{ route('admin.vehicles-services.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.vehicles-services.*') ? 'active' : '' }}">
                <i class="bi bi-car-front"></i>
                <span>Kendaraan &amp; Servis</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
                @csrf
                <button type="submit" class="sidebar-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </button>
            </form>

            <a href="{{ url('/') }}" class="back-home">
                <i class="bi bi-arrow-left"></i>
                Kembali ke beranda
            </a>
        </div>
    </aside>

    <header class="admin-topbar">
        <div class="topbar-heading">
            <h5>@yield('page-title', 'Ringkasan')</h5>
            <p>@yield('page-subtitle', 'Pantau dan kelola aktivitas platform.')</p>
        </div>

        <div class="topbar-actions">
            <div class="wallet-box">
                <span class="wallet-icon">
                    <i class="bi bi-wallet2"></i>
                </span>
                <div>
                    <small>WALLET TERHUBUNG</small>
                    <strong>Belum terhubung</strong>
                </div>
            </div>

            <button type="button" class="icon-button notification-button" aria-label="Notifikasi">
                <i class="bi bi-bell"></i>
                <span class="notification-dot"></span>
            </button>

            <div class="admin-account">
                <div class="admin-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                </div>
                <div class="admin-account-info">
                    <strong>{{ auth()->user()->name ?? 'Administrator' }}</strong>
                    <small>Administrator</small>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-main">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
