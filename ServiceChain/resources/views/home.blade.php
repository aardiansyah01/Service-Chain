
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceChain - Riwayat Servis Kendaraan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>
<body>
    <header class="navbar">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand-icon"><i class="bi bi-shield-check"></i></span>
            <span>ServiceChain</span>
        </a>

        <nav class="nav-links">
            <a href="#beranda">Beranda</a>
            <a href="{{ url('/verifikasi') }}">Verifikasi Kendaraan</a>
            <a href="#tentang">Tentang Kami</a>
        </nav>

        <div class="nav-actions">
            <a href="{{ url('/login') }}" class="login-link">Masuk</a>
            <a href="{{ url('/register') }}" class="btn btn-primary nav-register">Daftar</a>
        </div>

        <button class="menu-toggle" aria-label="Buka menu" onclick="document.querySelector('.nav-links').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
    </header>

    <main>
        <section class="hero" id="beranda">
            <div class="hero-content">
                <div class="trust-pill">
                    <span class="pill-icon"><i class="bi bi-shield-check"></i></span>
                    Riwayat kendaraan yang dapat dipercaya
                </div>

                <h1>Riwayat servis kendaraan, kini lebih <span>transparan.</span></h1>

                <p class="hero-description">
                    ServiceChain membantu Anda mencatat, melacak, dan memverifikasi
                    riwayat servis kendaraan melalui teknologi blockchain yang aman.
                </p>

                <div class="hero-actions">
                    <a href="{{ url('/verifikasi') }}" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Verifikasi Kendaraan
                    </a>
                    <a href="{{ url('/register') }}" class="btn btn-secondary">
                        <span>Mulai Sekarang</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="trust-note">
                    <div class="avatar-stack">
                        <span>AS</span>
                        <span>BW</span>
                        <span>DR</span>
                    </div>
                    <div>
                        <strong>Dipercaya pemilik kendaraan</strong>
                        <p>Catatan servis aman dan mudah dilacak</p>
                    </div>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-image"></div>

                <div class="verified-card">
                    <span class="verified-icon"><i class="bi bi-check-lg"></i></span>
                    <div>
                        <strong>Data terverifikasi</strong>
                        <p>Tercatat di blockchain</p>
                    </div>
                </div>

                <div class="service-card">
                    <div class="service-card-top">
                        <span class="document-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <span class="service-status"><i class="bi bi-circle-fill"></i> Berhasil</span>
                    </div>
                    <p class="service-label">Servis berkala</p>
                    <strong class="service-title">Toyota Camry 2021</strong>
                    <div class="service-card-bottom">
                        <span>0x71F...9A2C</span>
                        <i class="bi bi-copy"></i>
                    </div>
                </div>
            </div>
        </section>

        <section class="features section" id="tentang">
            <div class="section-heading">
                <span class="eyebrow">MENGAPA SERVICECHAIN</span>
                <h2>Satu platform untuk riwayat<br class="desktop-break"> kendaraan yang lebih jelas</h2>
                <p>Data terpercaya membantu pemilik, bengkel, dan calon pembeli mengambil keputusan<br class="desktop-break"> dengan yakin.</p>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-icon"><i class="bi bi-clock-history"></i></span>
                    <h3>Riwayat servis transparan</h3>
                    <p>Lihat catatan perawatan kendaraan secara kronologis, lengkap, dan mudah dipahami.</p>
                    <a href="{{ url('/verifikasi') }}">Pelajari lebih lanjut <i class="bi bi-arrow-right"></i></a>
                </article>

                <article class="feature-card">
                    <span class="feature-icon"><i class="bi bi-shop"></i></span>
                    <h3>Bengkel terverifikasi</h3>
                    <p>Setiap catatan dibuat oleh bengkel yang telah melewati proses verifikasi.</p>
                    <a href="#cara-kerja">Pelajari lebih lanjut <i class="bi bi-arrow-right"></i></a>
                </article>

                <article class="feature-card">
                    <span class="feature-icon"><i class="bi bi-shield-check"></i></span>
                    <h3>Tercatat di blockchain</h3>
                    <p>Catatan transaksi dapat ditelusuri dan terlindungi dari perubahan sepihak.</p>
                    <a href="#cara-kerja">Pelajari lebih lanjut <i class="bi bi-arrow-right"></i></a>
                </article>
            </div>
        </section>

        <section class="how-it-works section" id="cara-kerja">
            <div class="section-heading">
                <span class="eyebrow">CARA KERJA</span>
                <h2>Verifikasi kendaraan dalam tiga<br class="desktop-break"> langkah</h2>
            </div>

            <div class="steps-grid">
                <article class="step">
                    <span class="step-number">01</span>
                    <div>
                        <h3>Masukkan Vehicle ID</h3>
                        <p>Gunakan ID unik kendaraan yang ingin Anda periksa.</p>
                    </div>
                </article>

                <article class="step">
                    <span class="step-number">02</span>
                    <div>
                        <h3>Temukan kendaraan</h3>
                        <p>Sistem mencari data publik yang tersedia di jaringan.</p>
                    </div>
                </article>

                <article class="step">
                    <span class="step-number">03</span>
                    <div>
                        <h3>Lihat riwayat</h3>
                        <p>Periksa detail servis dan status verifikasi blockchain.</p>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-main">
            <div class="footer-brand">
                <a href="{{ url('/') }}" class="brand">
                    <span class="brand-icon"><i class="bi bi-shield-check"></i></span>
                    <span>ServiceChain</span>
                </a>
                <p>Riwayat kendaraan yang transparan untuk perjalanan<br> yang lebih tenang.</p>
            </div>

            <div class="footer-column">
                <h4>Produk</h4>
                <a href="{{ url('/verifikasi') }}">Verifikasi Kendaraan</a>
                <a href="{{ url('/register') }}">Daftar Kendaraan</a>
            </div>

            <div class="footer-column">
                <h4>Dukungan</h4>
                <a href="#dukungan">Pusat Bantuan</a>
                <a href="#privasi">Kebijakan Privasi</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© 2025 ServiceChain. Seluruh hak dilindungi.</span>
            <span>Dibangun untuk ekosistem kendaraan Indonesia.</span>
        </div>
    </footer>
</body>
</html>
