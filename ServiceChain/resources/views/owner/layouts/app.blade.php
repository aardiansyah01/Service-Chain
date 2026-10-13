
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ServiceChain') - ServiceChain</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/owner.css') }}">
</head>
<body>
    <div class="owner-layout">
        <aside class="sidebar">
            <a href="{{ url('/') }}" class="sidebar-brand">
                <span class="brand-icon">
                    <i class="bi bi-shield-check"></i>
                </span>
                <span>ServiceChain</span>
            </a>

            <nav class="sidebar-nav">
                <p class="nav-label">MENU UTAMA</p>

                <a href="{{ url('/owner/dashboard') }}"
                   class="nav-item {{ request()->is('owner/dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid nav-icon"></i>
                    <span>Ringkasan</span>
                </a>

                <a href="{{ url('/owner/vehicles') }}"
                   class="nav-item {{ request()->is('owner/vehicles*') ? 'active' : '' }}">
                    <i class="bi bi-car-front nav-icon"></i>
                    <span>Kendaraan Saya</span>
                </a>

                <a href="{{ url('/owner/history') }}"
                   class="nav-item {{ request()->is('owner/history*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history nav-icon"></i>
                    <span>Riwayat Servis</span>
                </a>

                <a href="{{ url('/owner/profile') }}"
                   class="nav-item {{ request()->is('owner/profile*') ? 'active' : '' }}">
                    <i class="bi bi-gear nav-icon"></i>
                    <span>Profil &amp; Pengaturan</span>
                </a>
            </nav>

            <a href="{{ url('/') }}" class="back-home">
                <i class="bi bi-arrow-left"></i>
                Kembali ke beranda
            </a>
        </aside>

        <main class="owner-main">
            <header class="topbar">
                <div class="topbar-heading">
                    <h2>@yield('page-heading', 'Ringkasan')</h2>
                    <p>Kelola aktivitas Anda dengan mudah.</p>
                </div>

                <div class="topbar-actions">
                    <div class="wallet-card">
                        <span class="wallet-icon">
                            <i class="bi bi-wallet2"></i>
                        </span>

                        <div>
                            <small>WALLET TERHUBUNG</small>
                            <strong id="wallet-address">Belum terhubung</strong>
                        </div>
                    </div>

                    <button type="button"
                            class="notification-button"
                            aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                    </button>

                    <div class="profile-summary">
                        <div class="avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>

                        <div class="profile-text">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>Pemilik Kendaraan</small>
                        </div>

                        <i class="bi bi-chevron-right profile-arrow"></i>
                    </div>
                    
                </div>
            </header>

            <div class="page-content">
                @if (session('success'))
                    <div class="alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-error">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
