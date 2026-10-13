
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ServiceChain Workshop')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/owner.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workshop.css') }}">
</head>
<body class="workshop-body">

    <aside class="workshop-sidebar">
        <a href="{{ route('workshop.dashboard') }}" class="workshop-brand">
            <span class="workshop-brand-icon">
                <i class="bi bi-shield-check"></i>
            </span>
            <span>ServiceChain</span>
        </a>

        <div class="workshop-sidebar-divider"></div>

        <div class="workshop-menu-label">MENU UTAMA</div>

        <nav class="workshop-navigation">
            <a href="{{ route('workshop.dashboard') }}"
               class="workshop-nav-link {{ request()->routeIs('workshop.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid"></i>
                <span>Ringkasan</span>
            </a>

            <a href="{{ route('workshop.records.index') }}"
                class="workshop-nav-link {{ request()->routeIs('workshop.records.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i>
                <span>Catatan Servis</span>
            </a>

            <a href="{{ route('workshop.profile') }}"
               class="workshop-nav-link {{ request()->routeIs('workshop.profile') ? 'active' : '' }}">
                <i class="bi bi-shop"></i>
                <span>Profil Bengkel</span>
            </a>
        </nav>

        <a href="{{ url('/') }}" class="workshop-back-link">
            <i class="bi bi-arrow-left"></i>
            Kembali ke beranda
        </a>
    </aside>

    <header class="workshop-topbar">
        <div class="workshop-topbar-heading">
            <h1>@yield('page-heading', 'Ringkasan')</h1>
            <p>Kelola aktivitas bengkel Anda dengan mudah.</p>
        </div>

        <div class="workshop-topbar-actions">
            <div class="workshop-wallet">
                <span class="workshop-wallet-icon">
                    <i class="bi bi-wallet2"></i>
                </span>
                <span class="workshop-wallet-text">
                    <small>WALLET TERHUBUNG</small>
                    <strong id="workshopWalletAddress">Belum terhubung</strong>
                </span>
            </div>

            <button type="button" class="workshop-icon-button"
                    aria-label="Notifikasi">
                <i class="bi bi-bell"></i>
                <span class="workshop-notification-dot"></span>
            </button>

            @php
                $workshop = auth()->user()->workshop;

                $workshopName = $workshop?->workshop_name ?? 'Nama Bengkel';

                $workshopInitials = collect(
                    preg_split('/\s+/', trim($workshopName))
                )
                    ->filter()
                    ->take(2)
                    ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                    ->implode('');
            @endphp

            <div class="workshop-account">
                <div class="workshop-account-avatar">
                    {{ $workshopInitials ?: 'BK' }}
                </div>

                <div class="workshop-account-info">
                    <strong>{{ $workshopName }}</strong>
                    <small>Bengkel</small>
                </div>

                <i class="bi bi-chevron-right"></i>
            </div>
        </div>
    </header>

    <main class="workshop-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
