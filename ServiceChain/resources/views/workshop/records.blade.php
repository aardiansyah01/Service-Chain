@extends('workshop.layouts.app')

@section('title', 'Catatan Servis')
@section('page-heading', 'Catatan Servis')

@section('content')
<div class="workshop-page workshop-records-page">

    {{-- Header --}}
    <div class="workshop-page-heading">
        <div>
            <h2>Riwayat dan catatan terverifikasi</h2>
            <p>Cari, filter, dan tinjau catatan servis bengkel Anda.</p>
        </div>

        <button type="button" class="workshop-primary-button"
                id="openAddServiceModal">
            <i class="bi bi-plus-lg"></i>
            Tambah Catatan
        </button>
    </div>

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
                        <tr>
                            <td>
                                <div class="workshop-vehicle-name">
                                    {{ $service->vehicle->brand ?? '-' }}
                                    {{ $service->vehicle->model ?? '' }}
                                </div>
                                <small class="workshop-vehicle-id">
                                    {{ $service->vehicle->vehicle_id ?? '-' }}
                                </small>
                            </td>

                            <td>{{ $service->service_type }}</td>

                            <td>
                                {{ $service->service_date->translatedFormat('d F Y') }}
                            </td>

                            <td>
                                {{ number_format($service->mileage, 0, ',', '.') }} km
                            </td>

                            <td>
                                <span class="workshop-record-status">
                                    <i class="bi bi-circle-fill"></i>
                                    Tersimpan
                                </span>
                            </td>

                            <td>
                                <button type="button"
                                    class="workshop-record-detail open-service-detail"
                                    aria-label="Detail servis"
                                    data-service-id="{{ $service->service_id }}"
                                    data-vehicle="{{ ($service->vehicle->brand ?? '-') . ' ' . ($service->vehicle->model ?? '') }}"
                                    data-vehicle-id="{{ $service->vehicle->vehicle_id ?? '-' }}"
                                    data-owner="{{ $service->vehicle->owner->name ?? '-' }}"
                                    data-type="{{ $service->service_type }}"
                                    data-date="{{ $service->service_date->translatedFormat('d F Y') }}"
                                    data-mileage="{{ number_format($service->mileage, 0, ',', '.') }} km"
                                    data-cost="Rp {{ number_format((float) $service->cost, 0, ',', '.') }}"
                                    data-workshop="{{ $service->workshop_name }}"
                                    data-address="{{ $service->workshop_address }}"
                                    data-description="{{ $service->description }}">
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

        <form action="{{ route('workshop.records.store') }}"
              method="POST" id="addServiceForm">
            @csrf

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
                            step="0.01"
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
                    <span>Kendaraan</span>
                    <strong id="detailVehicle">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>ID Kendaraan</span>
                    <strong id="detailVehicleId">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Nama Pemilik</span>
                    <strong id="detailOwner">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Jenis Servis</span>
                    <strong id="detailType">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Tanggal Servis</span>
                    <strong id="detailDate">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Kilometer</span>
                    <strong id="detailMileage">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Harga Servis</span>
                    <strong id="detailCost">-</strong>
                </div>

                <div class="workshop-detail-field">
                    <span>Nama Bengkel</span>
                    <strong id="detailWorkshop">-</strong>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Alamat Bengkel</span>
                    <strong id="detailAddress">-</strong>
                </div>

                <div class="workshop-detail-field workshop-detail-full">
                    <span>Deskripsi Detail Servis</span>
                    <p id="detailDescription">-</p>
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

    document.getElementById('openAddServiceModal')
        ?.addEventListener('click', function () {
            openModal(addModal);
        });

    document.querySelectorAll('[data-close-modal]').forEach(button => {
        button.addEventListener('click', function () {
            closeModal(button.closest('.workshop-modal-overlay'));
        });
    });

    document.querySelectorAll('.workshop-modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.workshop-modal-overlay.show')
                .forEach(modal => closeModal(modal));
        }
    });

    if (searchInput && results && vehicleInput) {
        const options = Array.from(
            results.querySelectorAll('.workshop-vehicle-option')
        );

        function selectVehicle(option) {
            vehicleInput.value = option.dataset.id || '';

            vehicleNameInput.value =
                `${option.dataset.brand || ''} ${option.dataset.model || ''}`.trim();

            vehicleIdInput.value = option.dataset.vehicleId || '';
            ownerInput.value = option.dataset.owner || '-';

            selectedLabel.textContent =
                `Kendaraan dipilih: ${vehicleNameInput.value}`;

            searchInput.value =
                `${vehicleNameInput.value} — ${ownerInput.value}`;

            results.hidden = true;
        }

        function filterVehicles() {
            const keyword = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            options.forEach(option => {
                const text = [
                    option.dataset.brand,
                    option.dataset.model,
                    option.dataset.vehicleId,
                    option.dataset.owner
                ].join(' ').toLowerCase();

                const matches = text.includes(keyword);

                option.hidden = !matches;

                if (matches) visibleCount++;
            });

            if (noResults) {
                noResults.hidden = visibleCount > 0;
            }

            results.hidden = false;

            vehicleInput.value = '';
            vehicleNameInput.value = '';
            vehicleIdInput.value = '';
            ownerInput.value = '';
            selectedLabel.textContent = 'Pilih kendaraan dari hasil pencarian.';
        }

        searchInput.addEventListener('input', filterVehicles);

        searchInput.addEventListener('focus', function () {
            filterVehicles();
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

        const oldVehicleId = vehicleInput.value;

        if (oldVehicleId) {
            const oldOption = options.find(
                option => option.dataset.id === oldVehicleId
            );

            if (oldOption) {
                selectVehicle(oldOption);
            }
        }
    }

    document.querySelectorAll('.open-service-detail').forEach(button => {
        button.addEventListener('click', function () {
            const data = button.dataset;

            document.getElementById('detailServiceId').textContent =
                'ID Catatan: ' + (data.serviceId || '-');

            document.getElementById('detailVehicle').textContent =
                data.vehicle || '-';

            document.getElementById('detailVehicleId').textContent =
                data.vehicleId || '-';

            document.getElementById('detailOwner').textContent =
                data.owner || '-';

            document.getElementById('detailType').textContent =
                data.type || '-';

            document.getElementById('detailDate').textContent =
                data.date || '-';

            document.getElementById('detailMileage').textContent =
                data.mileage || '-';

            document.getElementById('detailCost').textContent =
                data.cost || '-';

            document.getElementById('detailWorkshop').textContent =
                data.workshop || '-';

            document.getElementById('detailAddress').textContent =
                data.address || '-';

            document.getElementById('detailDescription').textContent =
                data.description || '-';

            openModal(detailModal);
        });
    });

    @if ($errors->any())
        openModal(addModal);
    @endif
});
</script>
@endpush
