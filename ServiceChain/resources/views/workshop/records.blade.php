@extends('workshop.layouts.app')

@section('title', 'Catatan Servis')
@section('page-heading', 'Catatan Servis')

@section('content')

@php
    $currentUser = auth()->user();
    $currentWorkshop = $currentUser?->workshop;

    $accountActive = strtolower(
        (string) $currentUser?->status
    ) === 'active';

    $workshopActive = strtoupper(
        (string) $currentWorkshop?->status
    ) === 'ACTIVE';

    $userWallet = strtolower(trim(
        (string) $currentUser?->wallet_address
    ));

    $workshopWallet = strtolower(trim(
        (string) $currentWorkshop?->wallet_address
    ));

    $walletLinked = $userWallet !== ''
        && $workshopWallet !== ''
        && hash_equals($userWallet, $workshopWallet);

    $canCreateService = $accountActive
        && $workshopActive
        && $walletLinked;
@endphp

<div class="workshop-page workshop-records-page">

    <div class="workshop-page-heading">
        <div>
            <h2>Riwayat dan catatan terverifikasi</h2>
            <p>Cari, filter, dan tinjau catatan servis bengkel Anda.</p>
        </div>

        @if ($canCreateService)
            <button
                type="button"
                class="workshop-primary-button"
                id="openAddServiceModal"
            >
                <i class="bi bi-plus-lg"></i>
                Tambah Catatan
            </button>
        @else
            <button
                type="button"
                class="workshop-primary-button"
                disabled
                aria-disabled="true"
                title="Hubungkan wallet bengkel terlebih dahulu"
            >
                <i class="bi bi-lock"></i>
                Tambah Catatan
            </button>
        @endif
    </div>

    @if (!$canCreateService)
        <div class="workshop-record-alert error" role="alert">
            <i class="bi bi-exclamation-circle"></i>

            <div>
                <strong>Fitur pencatatan servis belum tersedia.</strong>

                <p>
                    @if (!$accountActive || !$workshopActive)
                        Akun bengkel harus berstatus aktif terlebih dahulu.
                    @else
                        Hubungkan wallet bengkel melalui tombol
                        "Hubungkan Wallet" di bagian atas halaman
                        untuk membuka fitur pencatatan servis.
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- Filter --}}
    <form action="{{ route('workshop.records.index') }}"
          method="GET" class="workshop-filter-bar">

        <label class="workshop-search-box">
            <i class="bi bi-search"></i>
            <input type="search" name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari nama kendaraan atau Vehicle ID...">
        </label>

        <select name="sort" aria-label="Urutkan catatan">
            <option value="newest" @selected(request('sort', 'newest') === 'newest')>
                Terbaru
            </option>
            <option value="oldest" @selected(request('sort') === 'oldest')>
                Terlama
            </option>
            <option value="name" @selected(request('sort') === 'name')>
                Nama kendaraan
            </option>
        </select>

        <button type="submit" class="workshop-filter-submit">
            Terapkan
        </button>
    </form>

    @if (session('success'))
        <div class="workshop-record-alert success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="workshop-record-alert error">
            <strong>Catatan servis gagal disimpan.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabel catatan servis --}}
    <section class="workshop-records-card">
        <div class="workshop-records-heading">
            <div>
                <h3>Catatan Servis</h3>
                <p>Daftar servis yang dibuat oleh bengkel Anda</p>
            </div>

            <span class="workshop-record-count">
                {{ $services->total() }} catatan
            </span>
        </div>

        <div class="workshop-records-table-wrapper">
            <table class="workshop-records-table">
                <thead>
                    <tr>
                        <th>Kendaraan</th>
                        <th>Jenis Servis</th>
                        <th>Tanggal</th>
                        <th>Kilometer</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($services as $service)
                        <tr
                            class="workshop-service-row"
                            data-service-id="{{ $service->service_id }}"
                        >
                            <td>
                                <div class="workshop-vehicle-name js-chain-vehicle">
                                    Memuat blockchain...
                                </div>

                                <small class="workshop-vehicle-id js-chain-vehicle-id">
                                    Memuat...
                                </small>
                            </td>

                            <td>
                                <span class="js-chain-service-type">Memuat...</span>
                            </td>

                            <td>
                                <span class="js-chain-service-date">Memuat...</span>
                            </td>

                            <td>
                                <span class="js-chain-mileage">Memuat...</span>
                            </td>

                            <td>
                                <span class="workshop-record-status">
                                    <i class="bi bi-circle-fill"></i>
                                    Tersimpan
                                </span>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="workshop-record-detail open-service-detail"
                                    aria-label="Detail servis"
                                    data-service-id="{{ $service->service_id }}"
                                    data-owner-name="{{ $service->owner?->name ?? '-' }}"
                                    data-owner-id="{{ $service->owner_id ?? '' }}"
                                    data-workshop-name="{{ $service->workshop_name ?? '-' }}"
                                    data-workshop-address="{{ $service->workshop_address ?? '-' }}"
                                    data-receipt-url="{{ $service->receipt_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($service->receipt_path) : '' }}"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="workshop-records-no-data">
                                <i class="bi bi-file-earmark-text"></i>
                                <strong>Belum ada catatan servis</strong>
                                <span>Catatan servis yang Anda buat akan muncul di sini.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($services->hasPages())
            <div class="workshop-records-pagination">
                {{ $services->withQueryString()->links() }}
            </div>
        @endif
    </section>
</div>

{{-- MODAL TAMBAH CATATAN --}}
@if ($canCreateService)
<div class="workshop-modal-overlay" id="addServiceModal"
     aria-hidden="true">
    <section class="workshop-modal" role="dialog"
             aria-modal="true" aria-labelledby="addServiceModalTitle">

        <div class="workshop-modal-header">
            <div>
                <h3 id="addServiceModalTitle">Tambah Catatan Servis</h3>
                <p>Masukkan informasi servis kendaraan yang telah dilakukan.</p>
            </div>

            <button type="button" class="workshop-modal-close"
                    data-close-modal aria-label="Tutup modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form
            action="{{ route('workshop.records.store') }}"
            method="POST"
            enctype="multipart/form-data"
            id="addServiceForm"
            data-prepare-url="{{ route('workshop.records.prepare') }}"
            data-workshop-wallet="{{ auth()->user()->workshop?->wallet_address ?? '' }}"
        >
            @csrf

            <input type="hidden" name="service_id">
            <input type="hidden" name="wallet_address">
            <input type="hidden" name="transaction_hash">
            <input type="hidden" name="receipt_hash">

            <div class="workshop-modal-body">
                <div class="workshop-form-grid">

                    <div class="workshop-form-field workshop-form-full">
                        <label for="serviceVehicleSearch">
                            Cari Kendaraan <span>*</span>
                        </label>

                        <input
                            type="search"
                            id="serviceVehicleSearch"
                            placeholder="Cari nama kendaraan, Vehicle ID, atau nama pemilik..."
                            autocomplete="off"
                        >

                        <div id="serviceVehicleResults" class="workshop-vehicle-results">
                            @forelse ($vehicles as $vehicle)
                                <button
                                    type="button"
                                    class="workshop-vehicle-option"
                                    data-id="{{ $vehicle->id }}"
                                    data-brand="{{ $vehicle->brand }}"
                                    data-model="{{ $vehicle->model }}"
                                    data-vehicle-id="{{ $vehicle->vehicle_id }}"
                                    data-owner="{{ $vehicle->owner?->name ?? $vehicle->owner?->full_name ?? '-' }}"
                                >
                                    <strong>
                                        {{ $vehicle->brand }} {{ $vehicle->model }}
                                    </strong>

                                    <span>
                                        ID: {{ $vehicle->vehicle_id }}
                                        · Pemilik: {{ $vehicle->owner?->name ?? $vehicle->owner?->full_name ?? '-' }}
                                    </span>
                                </button>
                            @empty
                                <p class="workshop-vehicle-no-results">
                                    Belum ada kendaraan yang tersedia.
                                </p>
                            @endforelse

                            <p
                                id="serviceVehicleNoResults"
                                class="workshop-vehicle-no-results"
                                hidden
                            >
                                Kendaraan tidak ditemukan.
                            </p>
                        </div>

                        <input
                            type="hidden"
                            name="vehicle_id"
                            id="serviceVehicle"
                            value="{{ old('vehicle_id') }}"
                            required
                        >

                        @error('vehicle_id')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror

                        <small id="serviceSelectedVehicle" class="workshop-selected-vehicle">
                            Belum ada kendaraan yang dipilih.
                        </small>
                    </div>

                    <div class="workshop-form-field">
                        <label for="serviceVehicleName">Nama Kendaraan</label>
                        <input
                            type="text"
                            id="serviceVehicleName"
                            placeholder="Otomatis dari kendaraan"
                            readonly
                        >
                    </div>

                    <div class="workshop-form-field">
                        <label for="serviceVehicleId">Vehicle ID</label>
                        <input
                            type="text"
                            id="serviceVehicleId"
                            placeholder="Otomatis dari kendaraan"
                            readonly
                        >
                    </div>

                    <div class="workshop-form-field workshop-form-full">
                        <label for="serviceOwner">Nama Pemilik</label>
                        <input
                            type="text"
                            id="serviceOwner"
                            placeholder="Otomatis dari data pemilik"
                            readonly
                        >
                    </div>

                    <div class="workshop-form-field">
                        <label for="serviceType">Jenis Servis <span>*</span></label>
                        <input
                            type="text"
                            name="service_type"
                            id="serviceType"
                            value="{{ old('service_type') }}"
                            maxlength="100"
                            required
                            placeholder="Contoh: Servis berkala"
                        >
                        @error('service_type')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="workshop-form-field">
                        <label for="serviceDate">Tanggal Servis <span>*</span></label>
                        <input
                            type="date"
                            name="service_date"
                            id="serviceDate"
                            value="{{ old('service_date', now()->format('Y-m-d')) }}"
                            max="{{ now()->format('Y-m-d') }}"
                            required
                        >
                        @error('service_date')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="workshop-form-field">
                        <label for="serviceMileage">Kilometer <span>*</span></label>
                        <input
                            type="number"
                            name="mileage"
                            id="serviceMileage"
                            value="{{ old('mileage') }}"
                            min="0"
                            step="1"
                            required
                            placeholder="Contoh: 42180"
                        >
                        @error('mileage')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="workshop-form-field">
                        <label for="serviceCost">Harga Servis (Rp) <span>*</span></label>
                        <input
                            type="number"
                            name="cost"
                            id="serviceCost"
                            value="{{ old('cost') }}"
                            min="0"
                            step="1"
                            required
                            placeholder="Contoh: 500000"
                        >
                        @error('cost')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="workshop-form-field workshop-form-full">
                        <label for="serviceDescription">
                            Deskripsi Detail Servis <span>*</span>
                        </label>
                        <textarea
                            name="description"
                            id="serviceDescription"
                            rows="4"
                            required
                            placeholder="Jelaskan pekerjaan dan komponen yang diservis..."
                        >{{ old('description') }}</textarea>

                        @error('description')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="workshop-form-field workshop-form-full">
                        <label for="serviceReceipt">Bukti Nota Servis <span>*</span></label>

                        <input
                            type="file"
                            name="receipt"
                            id="serviceReceipt"
                            accept="image/*"
                            required
                        >

                        @error('receipt')
                            <small class="workshop-field-error">{{ $message }}</small>
                        @enderror

                        <small class="workshop-field-hint">
                            Unggah foto nota servis. Maksimal 10 MB.
                        </small>
                    </div>

                    <p
                        id="serviceBlockchainStatus"
                        role="status"
                        aria-live="polite"
                    ></p>
                </div>

                <p class="workshop-form-note">
                    <i class="bi bi-info-circle"></i>
                    Nama dan alamat bengkel diambil otomatis dari akun bengkel yang sedang login.
                </p>
            </div>

            <div class="workshop-modal-footer">
                <button type="button" class="workshop-modal-cancel"
                        data-close-modal>Batal</button>
                <button type="submit" class="workshop-modal-submit">
                    <i class="bi bi-floppy"></i>
                    Simpan Catatan
                </button>
            </div>
        </form>
    </section>
</div>
@endif

{{-- MODAL DETAIL CATATAN --}}
<div class="workshop-modal-overlay" id="serviceDetailModal"
     aria-hidden="true">
    <section class="workshop-modal workshop-detail-modal" role="dialog"
             aria-modal="true" aria-labelledby="serviceDetailModalTitle">

        <div class="workshop-modal-header">
            <div>
                <h3 id="serviceDetailModalTitle">Detail Catatan Servis</h3>
                <p id="detailServiceId">ID Catatan</p>
            </div>

            <button type="button" class="workshop-modal-close"
                    data-close-modal aria-label="Tutup modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="workshop-modal-body">
            <div class="workshop-detail-grid">
                <div class="workshop-detail-field">
                    <span>ID Servis (serviceId)</span>
                    <strong id="detailServiceIdField">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Tanggal Servis</span>
                    <strong id="detailDate">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Jenis Servis</span>
                    <strong id="detailType">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Biaya Servis</span>
                    <strong id="detailCost">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Kilometer</span>
                    <strong id="detailMileage">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Nama Bengkel</span>
                    <strong id="detailWorkshop">-</strong>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Alamat Bengkel</span>
                    <p id="detailAddress">-</p>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Wallet Bengkel</span>
                    <strong id="detailWorkshopWallet" class="break-wallet">-</strong>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Detail Servis</span>
                    <p id="detailDescription">-</p>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Nilai Hash (serviceHash)</span>
                    <strong id="detailServiceHash" class="break-wallet">-</strong>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Bukti Nota</span>

                    <a
                        id="detailReceiptLink"
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        hidden
                    >
                        <i class="bi bi-file-earmark-image"></i>
                        Lihat bukti nota
                    </a>

                    <span id="detailReceiptUnavailable">
                        Bukti nota tidak tersedia
                    </span>
                </div>

                <div class="workshop-detail-field">
                    <span>ID Kendaraan (vehicleId)</span>
                    <strong id="detailVehicleId">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Kendaraan</span>
                    <strong id="detailVehicle">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Nama Owner</span>
                    <strong id="detailOwner">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>ID Owner (ownerId)</span>
                    <strong id="detailOwnerId">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Warna Kendaraan</span>
                    <strong id="detailVehicleColor">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Tahun Kendaraan</span>
                    <strong id="detailVehicleYear">-</strong>
                </div>
            </div>
        </div>

        <div class="workshop-modal-footer">
            <button type="button" class="workshop-modal-cancel"
                    data-close-modal>Tutup</button>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.getElementById('openAddServiceModal');
    const addModal = document.getElementById('addServiceModal');
    const detailModal = document.getElementById('serviceDetailModal');

    const searchInput = document.getElementById('serviceVehicleSearch');
    const results = document.getElementById('serviceVehicleResults');
    const noResults = document.getElementById('serviceVehicleNoResults');

    const vehicleInput = document.getElementById('serviceVehicle');
    const vehicleNameInput = document.getElementById('serviceVehicleName');
    const vehicleIdInput = document.getElementById('serviceVehicleId');
    const ownerInput = document.getElementById('serviceOwner');
    const selectedLabel = document.getElementById('serviceSelectedVehicle');

    const options = results
        ? Array.from(results.querySelectorAll('.workshop-vehicle-option'))
        : [];

    function openModal(modal) {
        if (!modal) return;

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('workshop-modal-open');
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.workshop-modal-overlay.show')) {
            document.body.classList.remove('workshop-modal-open');
        }
    }

    // Modal tambah catatan
    addButton?.addEventListener('click', function (event) {
        event.preventDefault();
        openModal(addModal);
    });

    // Tutup modal
    document.querySelectorAll('[data-close-modal]').forEach(button => {
        button.addEventListener('click', function () {
            closeModal(button.closest('.workshop-modal-overlay'));
        });
    });

    // Tutup modal ketika area luar diklik
    document.querySelectorAll('.workshop-modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    // Tutup modal dengan tombol Escape
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.workshop-modal-overlay.show')
                .forEach(closeModal);
        }
    });

    // Pemilihan dan pencarian kendaraan
    if (
        searchInput &&
        results &&
        vehicleInput &&
        vehicleNameInput &&
        vehicleIdInput &&
        ownerInput &&
        selectedLabel
    ) {
        function selectVehicle(option) {
            const vehicleId = option.dataset.vehicleId || '';

            // Gunakan vehicle_id blockchain/string,
            // bukan ID numerik primary key MySQL.
            vehicleInput.value = vehicleId;

            vehicleNameInput.value =
                `${option.dataset.brand || ''} ${option.dataset.model || ''}`.trim();

            vehicleIdInput.value = vehicleId;
            ownerInput.value = option.dataset.owner || '-';

            selectedLabel.textContent =
                `Kendaraan dipilih: ${vehicleNameInput.value}`;

            searchInput.value =
                `${vehicleNameInput.value} — ${ownerInput.value}`;

            results.hidden = true;
        }

        function filterVehicles(clearSelection = false) {
            const keyword = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            options.forEach(option => {
                const searchableText = [
                    option.dataset.brand,
                    option.dataset.model,
                    option.dataset.vehicleId,
                    option.dataset.owner
                ].join(' ').toLowerCase();

                const matches = searchableText.includes(keyword);

                option.hidden = !matches;

                if (matches) visibleCount++;
            });

            if (noResults) {
                noResults.hidden = visibleCount > 0;
            }

            results.hidden = false;

            // Pilihan direset hanya ketika isi pencarian berubah,
            // bukan setiap kali kolom mendapatkan fokus.
            if (clearSelection) {
                vehicleInput.value = '';
                vehicleNameInput.value = '';
                vehicleIdInput.value = '';
                ownerInput.value = '';

                selectedLabel.textContent =
                    'Pilih kendaraan dari hasil pencarian.';
            }
        }

        searchInput.addEventListener('input', function () {
            filterVehicles(true);
        });

        searchInput.addEventListener('focus', function () {
            filterVehicles(false);
        });

        options.forEach(option => {
            option.addEventListener('click', function () {
                selectVehicle(option);
            });
        });

        document.addEventListener('click', function (event) {
            if (
                !searchInput.contains(event.target) &&
                !results.contains(event.target)
            ) {
                results.hidden = true;
            }
        });

        // Pulihkan pilihan kendaraan setelah validasi gagal.
        const oldVehicleId = vehicleInput.value;

        if (oldVehicleId) {
            const oldOption = options.find(option =>
                option.dataset.vehicleId === oldVehicleId
            );

            if (oldOption) {
                selectVehicle(oldOption);
            }
        }
    }

    @if ($errors->any())
        openModal(addModal);
    @endif
});
</script>
@endpush
