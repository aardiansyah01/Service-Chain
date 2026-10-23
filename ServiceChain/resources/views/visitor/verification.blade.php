<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Verifikasi kendaraan dan riwayat servis ServiceChain melalui blockchain.">
    <title>Verifikasi Riwayat Kendaraan - ServiceChain</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/verification.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="visitor-page" data-visitor-verification data-metadata-url-base="{{ url('/verifikasi/metadata') }}">
    <header class="visitor-navbar">
        <a href="{{ url('/') }}" class="visitor-brand" aria-label="ServiceChain, halaman utama">
            <span class="visitor-brand-icon"><i class="bi bi-shield-check"></i></span>
            <span>ServiceChain</span>
        </a>

        <nav class="visitor-nav-links" aria-label="Navigasi utama">
            <a href="{{ url('/') }}">Beranda</a>
            <a href="{{ url('/verifikasi') }}" class="is-current">Verifikasi Kendaraan</a>
            <a href="{{ url('/#tentang') }}">Tentang Kami</a>
        </nav>

        <div class="visitor-nav-actions">
            <a href="{{ url('/login') }}" class="visitor-login-link">Masuk</a>
            <a href="{{ url('/register') }}" class="visitor-button visitor-button-primary visitor-register-link">Daftar</a>
        </div>
        <button class="visitor-menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false" data-menu-toggle>
            <i class="bi bi-list"></i>
        </button>
    </header>

    <main class="visitor-main">
        <section class="visitor-hero" aria-labelledby="verification-title">
            <div class="visitor-hero-icon"><i class="bi bi-shield-check"></i></div>
            <p class="visitor-eyebrow">VERIFIKASI PUBLIK</p>
            <h1 id="verification-title">Verifikasi riwayat kendaraan</h1>
            <p class="visitor-hero-description">Periksa informasi dan riwayat servis kendaraan langsung dari blockchain tanpa perlu membuat akun.</p>
        </section>

        <section class="visitor-search-card" aria-label="Cari kendaraan">
            <form class="visitor-search-form" data-vehicle-search-form novalidate>
                <label for="vehicleIdInput">Vehicle ID</label>
                <div class="visitor-search-input-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="vehicleIdInput" type="text" placeholder="Contoh: SC-VHC-20261017-AB12CD34" autocomplete="off" spellcheck="false" data-vehicle-id-input>
                    <button class="visitor-button visitor-button-primary visitor-search-button" type="submit" data-search-button>
                        <i class="bi bi-search" aria-hidden="true"></i>
                        Cari Kendaraan
                    </button>
                </div>
                <p class="visitor-search-help">Vehicle ID dapat ditemukan pada informasi kendaraan milik owner.</p>
            </form>
            <div class="visitor-alert visitor-alert-info" role="status" data-search-status hidden></div>
        </section>

        <div class="visitor-results" data-verification-results hidden>
            <section class="visitor-panel" data-vehicle-section hidden aria-labelledby="vehicle-section-title">
                <div class="visitor-panel-heading">
                    <div>
                        <p class="visitor-section-kicker">RINGKASAN KENDARAAN</p>
                        <h2 id="vehicle-section-title">Informasi kendaraan</h2>
                        <p class="visitor-panel-description">Data kendaraan dibaca dari smart contract, sementara informasi akun owner dicocokkan melalui wallet.</p>
                    </div>
                    <span class="visitor-status" data-vehicle-status>Memuat</span>
                </div>

                <div class="visitor-vehicle-feature">
                    <div class="visitor-vehicle-symbol"><i class="bi bi-car-front-fill"></i></div>
                    <div class="visitor-vehicle-title">
                        <span>KENDARAAN</span>
                        <h3 data-vehicle-field="vehicleName">-</h3>
                        <p><span data-vehicle-field="brand">-</span><span class="visitor-dot-separator">·</span><span data-vehicle-field="model">-</span></p>
                    </div>
                    <div class="visitor-vehicle-id-block">
                        <span>VEHICLE ID</span>
                        <strong data-vehicle-field="vehicleId">-</strong>
                    </div>
                </div>

                <div class="visitor-information-grid">
                    <div class="visitor-information-item">
                        <span>Merek (brand)</span>
                        <strong data-vehicle-field="brand">-</strong>
                    </div>
                    <div class="visitor-information-item">
                        <span>Tipe / model</span>
                        <strong data-vehicle-field="model">-</strong>
                    </div>
                    <div class="visitor-information-item">
                        <span>Warna</span>
                        <strong data-vehicle-field="color">-</strong>
                    </div>
                    <div class="visitor-information-item">
                        <span>Tahun</span>
                        <strong data-vehicle-field="year">-</strong>
                    </div>
                    <div class="visitor-information-item">
                        <span>Nama owner</span>
                        <strong data-vehicle-field="ownerName">-</strong>
                    </div>
                    <div class="visitor-information-item">
                        <span>ID owner (ownerId)</span>
                        <strong data-vehicle-field="ownerId">-</strong>
                    </div>
                    <div class="visitor-information-item visitor-information-wallet">
                        <span>Wallet address owner</span>
                        <strong class="visitor-wallet-value" data-vehicle-field="ownerWallet">-</strong>
                    </div>
                </div>
            </section>

            <section class="visitor-panel visitor-service-panel" data-service-section hidden aria-labelledby="service-section-title">
                <div class="visitor-panel-heading visitor-service-heading">
                    <div>
                        <p class="visitor-section-kicker">CATATAN BLOCKCHAIN</p>
                        <h2 id="service-section-title">Riwayat servis kendaraan</h2>
                        <p class="visitor-panel-description">Ringkasan ditampilkan di tabel. Tekan Detail untuk informasi lengkap atau Verifikasi untuk memeriksa hash.</p>
                    </div>
                    <span class="visitor-data-source"><i class="bi bi-link-45deg"></i> Data on-chain</span>
                </div>

                <div class="visitor-table-wrap">
                    <table class="visitor-service-table">
                        <thead>
                            <tr>
                                <th scope="col">Kendaraan / ID servis</th>
                                <th scope="col">Jenis servis</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Kilometer</th>
                                <th scope="col">Biaya</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-service-table-body></tbody>
                    </table>
                </div>

                <div class="visitor-empty-history" data-empty-history hidden>
                    <span class="visitor-empty-icon"><i class="bi bi-journal-check"></i></span>
                    <h3>Belum ada riwayat servis</h3>
                    <p>Belum ada catatan servis yang tersimpan untuk Vehicle ID ini.</p>
                </div>

                <p class="visitor-integrity-note"><i class="bi bi-info-circle"></i> Hash tidak ditampilkan di halaman publik. Untuk menguji integritas catatan, masukkan hash yang diberikan oleh owner.</p>
            </section>
        </div>
    </main>

    <footer class="visitor-footer">
        <a href="{{ url('/') }}" class="visitor-footer-brand"><span class="visitor-brand-icon"><i class="bi bi-shield-check"></i></span>ServiceChain</a>
        <p>Riwayat kendaraan yang transparan dan dapat diverifikasi.</p>
        <span>© {{ date('Y') }} ServiceChain</span>
    </footer>

    <dialog class="visitor-dialog" id="visitorServiceDetailDialog" aria-labelledby="visitor-detail-title">
        <div class="visitor-dialog-header">
            <div>
                <p class="visitor-section-kicker">INFORMASI LENGKAP</p>
                <h2 id="visitor-detail-title">Detail riwayat servis</h2>
                <p class="visitor-dialog-subtitle">Service ID <strong data-detail-field="serviceId">-</strong></p>
            </div>
            <button type="button" class="visitor-icon-button" data-dialog-close aria-label="Tutup detail"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="visitor-dialog-body">
            <div
                class="visitor-cancelled-warning"
                data-cancelled-warning
                role="status"
                hidden
            ></div>
            <h3><i class="bi bi-car-front"></i> Informasi kendaraan dan owner</h3>
            <div class="visitor-detail-grid">
                <div class="visitor-detail-item"><span>ID kendaraan</span><strong data-detail-field="vehicleId">-</strong></div>
                <div class="visitor-detail-item"><span>Kendaraan</span><strong data-detail-field="vehicle">-</strong></div>
                <div class="visitor-detail-item"><span>Nama owner</span><strong data-detail-field="ownerName">-</strong></div>
                <div class="visitor-detail-item"><span>ID owner</span><strong data-detail-field="ownerId">-</strong></div>
            </div>

            <h3><i class="bi bi-tools"></i> Informasi riwayat servis</h3>
            <div class="visitor-detail-grid">
                <div class="visitor-detail-item"><span>Tanggal servis</span><strong data-detail-field="serviceDate">-</strong></div>
                <div class="visitor-detail-item"><span>Jenis servis</span><strong data-detail-field="serviceType">-</strong></div>
                <div class="visitor-detail-item"><span>Biaya</span><strong data-detail-field="cost">-</strong></div>
                <div class="visitor-detail-item"><span>Kilometer</span><strong data-detail-field="mileage">-</strong></div>
                <div class="visitor-detail-item"><span>Nama bengkel</span><strong data-detail-field="workshopName">-</strong></div>
                <div class="visitor-detail-item"><span>Alamat bengkel</span><strong data-detail-field="workshopAddress">-</strong></div>
                <div class="visitor-detail-item visitor-detail-full"><span>Wallet bengkel</span><strong class="visitor-wallet-value" data-detail-field="workshopWallet">-</strong></div>
                <div class="visitor-detail-item visitor-detail-full"><span>Detail servis</span><p data-detail-field="description">-</p></div>
                <div class="visitor-detail-item visitor-detail-full"><span>Status catatan</span><strong data-detail-field="recordStatus">-</strong></div>
            </div>
        </div>
        <div class="visitor-dialog-footer">
            <button type="button" class="visitor-button visitor-button-secondary" data-dialog-close>Tutup</button>
        </div>
    </dialog>

    <dialog class="visitor-dialog visitor-hash-dialog" id="visitorHashDialog" aria-labelledby="visitor-hash-title">
        <div class="visitor-dialog-header">
            <div>
                <p class="visitor-section-kicker">PEMERIKSAAN INTEGRITAS</p>
                <h2 id="visitor-hash-title">Verifikasi hash servis</h2>
                <p class="visitor-dialog-subtitle" data-hash-service-id>Service ID</p>
            </div>
            <button type="button" class="visitor-icon-button" data-dialog-close aria-label="Tutup verifikasi"><i class="bi bi-x-lg"></i></button>
        </div>
        <form class="visitor-hash-form" data-hash-form>
            <label for="visitorServiceHash">Hash dari owner</label>
            <textarea id="visitorServiceHash" data-hash-input rows="3" placeholder="Tempel hash lengkap yang diberikan oleh owner (0x…)"></textarea>
            <p class="visitor-hash-help">Hash yang Anda masukkan akan dibandingkan dengan serviceHash yang tercatat pada smart contract.</p>
            <div class="visitor-alert" data-hash-status role="status" hidden></div>
            <div class="visitor-dialog-footer">
                <button type="button" class="visitor-button visitor-button-secondary" data-dialog-close>Batal</button>
                <button type="submit" class="visitor-button visitor-button-primary"><i class="bi bi-shield-check"></i> Verifikasi Hash</button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const button = document.querySelector('[data-menu-toggle]');
            const nav = document.querySelector('.visitor-nav-links');
            if (!button || !nav) return;
            button.addEventListener('click', () => {
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!expanded));
                nav.classList.toggle('is-open', !expanded);
            });
        })();
    </script>
</body>
</html>
