@extends('owner.layouts.app')

@section('title', 'Kendaraan Saya')
@section('page-heading', 'Kendaraan Saya')

@section('content')
<div class="vehicles-page">

    <div class="vehicles-title-row">
        <div class="vehicles-heading">
            <h1>Semua kendaraan Anda</h1>
            <p>Kelola kendaraan dan lihat informasi pendaftarannya.</p>
        </div>

        <button type="button" class="primary-button" id="openVehicleModal">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Kendaraan</span>
        </button>
    </div>

    @if (session('success'))
        <div class="vehicle-alert vehicle-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="vehicle-alert vehicle-alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div>
                <strong>Data belum bisa disimpan.</strong>
                <span>Periksa kembali kolom yang ditandai pada formulir.</span>
            </div>
        </div>
    @endif

    <form action="{{ route('owner.vehicles') }}"
          method="GET"
          class="vehicle-filters">

        <label class="vehicle-search">
            <i class="bi bi-search"></i>
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari merek, model, pelat nomor, atau Vehicle ID..."
                aria-label="Cari kendaraan"
            >
        </label>

        <select name="status" id="vehicleStatusFilter" aria-label="Filter status">
            <option value="all" @selected($status === 'all')>
                Semua status
            </option>
            <option value="ACTIVE" @selected($status === 'ACTIVE')>
                Aktif
            </option>
            <option value="INACTIVE" @selected($status === 'INACTIVE')>
                Tidak aktif
            </option>
        </select>

        <select name="sort" aria-label="Urutkan kendaraan">
            <option value="newest" @selected($sort === 'newest')>
                Terbaru
            </option>
            <option value="oldest" @selected($sort === 'oldest')>
                Terlama
            </option>
            <option value="name-asc" @selected($sort === 'name-asc')>
                Nama A–Z
            </option>
            <option value="name-desc" @selected($sort === 'name-desc')>
                Nama Z–A
            </option>
        </select>

        <button type="submit" class="vehicle-filter-button">
            <i class="bi bi-funnel"></i>
            <span>Terapkan</span>
        </button>
    </form>

    @if ($vehicles->count() > 0)
        <div class="vehicles-result-heading">
            <span>
                Kendaraan terdaftar
                <strong>{{ $vehicles->total() }}</strong>
            </span>
            <span>Menampilkan {{ $vehicles->firstItem() }}–{{ $vehicles->lastItem() }}</span>
        </div>
    @endif

    <div class="vehicles-grid">
        @forelse ($vehicles as $vehicle)
            <article class="vehicle-card" data-vehicle-id="{{ $vehicle->vehicle_id }}" data-chain-status="unknown">

                <div class="vehicle-card-cover">
                    @if (strtolower($vehicle->vehicle_type) === 'motor')
                        <i class="bi bi-scooter vehicle-cover-icon"></i>
                    @else
                        <i class="bi bi-car-front vehicle-cover-icon"></i>
                    @endif

                    <span class="vehicle-status js-chain-vehicle-status" data-chain-status="unknown">
                        <i class="bi bi-circle-fill"></i>
                        Memuat status blockchain...
                    </span>
                </div>

                <div class="vehicle-card-body">
                    <h2>{{ $vehicle->brand }} {{ $vehicle->model }}</h2>

                    <p class="vehicle-subtitle">
                        {{ $vehicle->vehicle_type }} <span>·</span> <span class="js-chain-vehicle-year">Memuat tahun...</span>
                    </p>

                    <div class="vehicle-card-id">
                        <span>VEHICLE ID</span>
                        <strong>{{ $vehicle->vehicle_id }}</strong>
                    </div>

                    <button
                        type="button"
                        class="vehicle-detail-button"
                        data-vehicle-id="{{ $vehicle->vehicle_id }}"
                        data-owner-name="{{ Auth::user()->name }}"
                        data-owner-id="{{ Auth::user()->id }}"
                        data-brand="{{ $vehicle->brand }}"
                        data-model="{{ $vehicle->model }}"
                        data-type="{{ $vehicle->vehicle_type }}"
                        data-year=""
                        data-plate="{{ $vehicle->license_plate }}"
                        data-color=""
                        data-vin="{{ $vehicle->chassis_number ?? '' }}"
                        data-status=""
                        data-registered="{{ $vehicle->created_at?->format('d M Y, H:i') ?? '-' }}"
                    >
                        <span>Lihat Detail</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </article>
        @empty
            <div class="vehicles-empty">
                <div class="vehicles-empty-icon">
                    <i class="bi bi-car-front"></i>
                </div>

                <h3>
                    {{ $search !== ''
                        ? 'Kendaraan tidak ditemukan'
                        : 'Belum ada kendaraan' }}
                </h3>

                <p>
                    {{ $search !== ''
                        ? 'Coba kata kunci pencarian yang berbeda.'
                        : 'Tambahkan kendaraan pertama Anda untuk mulai mencatat riwayat servis.' }}
                </p>

                @if ($search === '')
                    <button type="button"
                            class="empty-add-button"
                            id="openVehicleModalEmpty">
                        <i class="bi bi-plus-lg"></i>
                        Tambah kendaraan pertama
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    <div class="vehicles-empty js-vehicle-filter-empty" hidden>
        <div class="vehicles-empty-icon"><i class="bi bi-car-front"></i></div>
        <h3>Tidak ada kendaraan dengan status ini</h3>
        <p>Status kendaraan diperiksa langsung dari blockchain.</p>
    </div>

    @if ($vehicles->hasPages())
        <div class="vehicle-pagination">
            {{ $vehicles->links() }}
        </div>
    @endif
</div>

{{-- Modal tambah kendaraan --}}
<div class="vehicle-modal-overlay {{ $errors->any() ? 'is-open' : '' }}"
     id="vehicleModal"
     aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">

    <section class="vehicle-modal"
             role="dialog"
             aria-modal="true"
             aria-labelledby="vehicleModalTitle">

        <div class="vehicle-modal-header">
            <div>
                <span class="vehicle-modal-eyebrow">DATA KENDARAAN</span>
                <h2 id="vehicleModalTitle">Tambah Kendaraan</h2>
                <p>Lengkapi informasi kendaraan Anda di bawah ini.</p>
            </div>

            <button type="button"
                    class="vehicle-modal-close"
                    id="closeVehicleModal"
                    aria-label="Tutup modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form
            action="{{ route('owner.vehicles.store') }}"
            method="POST"
            id="vehicleForm"
            data-prepare-url="{{ route('owner.vehicles.prepare') }}"
            data-saved-wallet="{{ auth()->user()->wallet_address ?? '' }}"
        >
            @csrf

            <input type="hidden" name="vehicle_id" value="">
            <input type="hidden" name="wallet_address" value="">
            <input type="hidden" name="transaction_hash" value="">

            <div class="vehicle-modal-body">

                <div class="vehicle-form-section-title">
                    <i class="bi bi-car-front"></i>
                    Informasi Kendaraan
                </div>

                <div class="vehicle-form-grid">
                    <div class="vehicle-form-group">
                        <label for="vehicle_type">Jenis kendaraan <span>*</span></label>
                        <select id="vehicle_type"
                                name="vehicle_type"
                                class="@error('vehicle_type') is-invalid @enderror"
                                required>
                            <option value="">Pilih jenis kendaraan</option>
                            <option value="Mobil" @selected(old('vehicle_type') === 'Mobil')>Mobil</option>
                            <option value="Motor" @selected(old('vehicle_type') === 'Motor')>Motor</option>
                        </select>
                        @error('vehicle_type')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="vehicle-form-group">
                        <label for="year">Tahun kendaraan <span>*</span></label>
                        <input
                            type="number"
                            id="year"
                            name="year"
                            min="1900"
                            max="{{ date('Y') + 1 }}"
                            value="{{ old('year') }}"
                            placeholder="Contoh: {{ date('Y') }}"
                            class="@error('year') is-invalid @enderror"
                            required
                        >
                        @error('year')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="vehicle-form-group">
                        <label for="brand">Merek <span>*</span></label>
                        <input
                            type="text"
                            id="brand"
                            name="brand"
                            value="{{ old('brand') }}"
                            placeholder="Contoh: Toyota"
                            maxlength="100"
                            class="@error('brand') is-invalid @enderror"
                            required
                        >
                        @error('brand')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="vehicle-form-group">
                        <label for="model">Model <span>*</span></label>
                        <input
                            type="text"
                            id="model"
                            name="model"
                            value="{{ old('model') }}"
                            placeholder="Contoh: Avanza"
                            maxlength="100"
                            class="@error('model') is-invalid @enderror"
                            required
                        >
                        @error('model')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="vehicle-form-group">
                        <label for="license_plate">Nomor pelat <span>*</span></label>
                        <input
                            type="text"
                            id="license_plate"
                            name="license_plate"
                            value="{{ old('license_plate') }}"
                            placeholder="Contoh: N 1234 AB"
                            maxlength="20"
                            class="@error('license_plate') is-invalid @enderror"
                            required
                        >
                        @error('license_plate')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="vehicle-form-group">
                        <label for="color">Warna <span>*</span></label>
                        <input
                            type="text"
                            id="color"
                            name="color"
                            value="{{ old('color') }}"
                            placeholder="Contoh: Hitam"
                            maxlength="50"
                            class="@error('color') is-invalid @enderror"
                            required
                        >
                        @error('color')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="vehicle-form-group vehicle-form-full">
                        <label for="vin">Nomor VIN / nomor rangka <span>*</span></label>
                        <input
                            type="text"
                            id="vin"
                            name="vin"
                            value="{{ old('vin') }}"
                            placeholder="Masukkan nomor rangka kendaraan"
                            maxlength="100"
                            class="@error('vin') is-invalid @enderror"
                            required
                        >
                        @error('vin')
                            <small class="vehicle-field-error">{{ $message }}</small>
                        @enderror
                        <small class="vehicle-field-hint">
                            Pastikan nomor rangka sesuai dengan dokumen kendaraan.
                        </small>
                    </div>
                </div>

                <p class="vehicle-required-note">
                    <span>*</span> Wajib diisi
                </p>
            </div>

            <div class="vehicle-modal-footer">
                <button type="button"
                        class="vehicle-cancel-button"
                        id="cancelVehicleModal">
                    Batal
                </button>

                <button type="submit" class="vehicle-save-button" id="saveVehicleButton">
                    <i class="bi bi-check2-circle"></i>
                    Simpan Kendaraan
                </button>
            </div>
            <p
                id="vehicle-blockchain-status"
                role="status"
                aria-live="polite"
            ></p>
        </form>
    </section>
</div>

{{-- Modal detail kendaraan --}}
<div class="vehicle-modal-overlay" id="vehicleDetailModal" aria-hidden="true">
    <section class="vehicle-modal vehicle-detail-modal" role="dialog" aria-modal="true" aria-labelledby="vehicleDetailTitle">
        <div class="vehicle-modal-header">
            <div>
                <span class="vehicle-modal-eyebrow">INFORMASI KENDARAAN</span>
                <h2 id="vehicleDetailTitle">Detail Kendaraan</h2>
                <p>Informasi kendaraan yang terdaftar di ServiceChain.</p>
            </div>
            <button type="button" class="vehicle-modal-close" id="closeVehicleDetail" aria-label="Tutup modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="vehicle-modal-body">
            <div class="vehicle-detail-modal-hero">
                <div class="vehicle-detail-modal-icon" id="detailVehicleIcon">
                    <i class="bi bi-car-front"></i>
                </div>
                <div>
                    <span class="vehicle-modal-eyebrow">KENDARAAN TERDAFTAR</span>
                    <h3 id="detailVehicleName">-</h3>
                    <p id="detailVehicleSubtitle">-</p>
                    <span id="detailVehicleStatus" class="vehicle-status active">Aktif</span>
                </div>
            </div>

            <div class="vehicle-form-section-title">
                <i class="bi bi-info-circle"></i>
                Informasi Kendaraan
            </div>

            <div class="vehicle-detail-grid">
                <div class="vehicle-detail-item">
                    <span>Vehicle ID</span>
                    <strong id="detailVehicleId">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Wallet Pemilik</span>
                    <strong
                        id="detailVehicleOwnerWallet"
                        style="word-break: break-all;"
                    >-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Nama Owner</span>
                    <strong id="detailVehicleOwnerName">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>ID Owner</span>
                    <strong id="detailVehicleOwnerId">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Jenis Kendaraan</span>
                    <strong id="detailVehicleType">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Merek</span>
                    <strong id="detailVehicleBrand">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Model</span>
                    <strong id="detailVehicleModel">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Tahun</span>
                    <strong id="detailVehicleYear">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Nomor Pelat</span>
                    <strong id="detailVehiclePlate">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Warna</span>
                    <strong id="detailVehicleColor">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Nomor VIN / Nomor Rangka</span>
                    <strong id="detailVehicleVin">-</strong>
                </div>
                <div class="vehicle-detail-item">
                    <span>Tanggal Registrasi</span>
                    <strong id="detailVehicleRegistered">-</strong>
                </div>
            </div>
        </div>

        <div class="vehicle-modal-footer">
            <button
                type="button"
                class="vehicle-transfer-button"
                id="transferVehicleButton"
            >
                <i class="bi bi-arrow-left-right"></i>
                Transfer Kepemilikan
            </button>

            <button
                type="button"
                class="vehicle-cancel-button"
                id="closeVehicleDetailFooter"
            >
                Tutup
            </button>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('vehicleModal');
    const openButtons = [
        document.getElementById('openVehicleModal'),
        document.getElementById('openVehicleModalEmpty')
    ].filter(Boolean);

    const closeButtons = [
        document.getElementById('closeVehicleModal'),
        document.getElementById('cancelVehicleModal')
    ].filter(Boolean);

    const form = document.getElementById('vehicleForm');
    const saveButton = document.getElementById('saveVehicleButton');

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('vehicle-modal-open');

        const firstInput = modal.querySelector('select, input');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('vehicle-modal-open');
    }

    openButtons.forEach(button => {
        button.addEventListener('click', openModal);
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    @if ($errors->any())
        openModal();
    @endif
});
</script>
@endsection
