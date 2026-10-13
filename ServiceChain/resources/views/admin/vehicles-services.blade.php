@extends('admin.layouts.app')

@section('title', 'Kendaraan & Servis')
@section('page-title', 'Kendaraan & Servis')
@section('page-subtitle', 'Pantau dan kelola aktivitas platform.')

@section('content')
<div class="management-page service-management">
    <div class="management-heading">
        <h1>Riwayat dan catatan servis</h1>
        <p>Cari, filter, dan lihat detail catatan servis kendaraan.</p>
    </div>

    <form action="{{ route('admin.vehicles-services.index') }}" method="GET" class="management-toolbar">
        <div class="management-search">
            <i class="bi bi-search"></i>
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari kendaraan, pemilik, bengkel, atau Service ID..."
                aria-label="Cari catatan servis"
            >
        </div>

        <select name="service_type" class="management-select" aria-label="Filter jenis servis">
            <option value="all" @selected($serviceType === 'all')>Semua jenis servis</option>
            @foreach ($serviceTypes as $type)
                <option value="{{ $type }}" @selected($serviceType === $type)>
                    {{ $type }}
                </option>
            @endforeach
        </select>

        <select name="sort" class="management-select sort-select" aria-label="Urutkan catatan">
            <option value="newest" @selected($sort === 'newest')>Terbaru</option>
            <option value="oldest" @selected($sort === 'oldest')>Terlama</option>
            <option value="name-asc" @selected($sort === 'name-asc')>Nama A–Z</option>
            <option value="name-desc" @selected($sort === 'name-desc')>Nama Z–A</option>
        </select>

        <button type="submit" class="service-filter-button">Terapkan</button>
    </form>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="management-panel">
        <div class="management-panel-header">
            <div>
                <h3>Catatan Servis</h3>
                <p>Daftar aktivitas servis yang tercatat pada platform.</p>
            </div>
            <span class="service-total">{{ $services->total() }} catatan</span>
        </div>

        <div class="management-table-wrap">
            <table class="management-table">
                <thead>
                    <tr>
                        <th>KENDARAAN</th>
                        <th>JENIS SERVIS</th>
                        <th>TANGGAL</th>
                        <th>KILOMETER</th>
                        <th>BIAYA</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td>
                                <strong>
                                    {{ $service->vehicle->brand ?? 'Kendaraan dihapus' }}
                                    {{ $service->vehicle->model ?? '' }}
                                </strong>
                                <small>{{ $service->vehicle->vehicle_id ?? '-' }}</small>
                            </td>
                            <td>{{ $service->service_type }}</td>
                            <td>{{ $service->service_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ number_format($service->mileage, 0, ',', '.') }} km</td>
                            <td>Rp{{ number_format((float) $service->cost, 0, ',', '.') }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="table-detail-button service-detail-trigger"
                                    aria-label="Lihat detail servis {{ $service->service_id }}"
                                    data-service-id="{{ $service->service_id }}"
                                    data-vehicle-id="{{ $service->vehicle->vehicle_id ?? '-' }}"
                                    data-vehicle-type="{{ $service->vehicle->vehicle_type ?? '-' }}"
                                    data-vehicle-brand="{{ $service->vehicle->brand ?? '-' }}"
                                    data-vehicle-model="{{ $service->vehicle->model ?? '-' }}"
                                    data-owner="{{ $service->vehicle->owner->name ?? '-' }}"
                                    data-workshop="{{ $service->workshop_name }}"
                                    data-workshop-address="{{ $service->workshop_address }}"
                                    data-service-type="{{ $service->service_type }}"
                                    data-description="{{ $service->description }}"
                                    data-service-date="{{ $service->service_date?->format('d M Y') ?? '-' }}"
                                    data-cost="{{ number_format((float) $service->cost, 0, ',', '.') }}"
                                    data-mileage="{{ number_format($service->mileage, 0, ',', '.') }}"
                                    data-created-at="{{ $service->created_at?->format('d M Y, H:i') ?? '-' }}"
                                >
                                    <i class="bi bi-eye"></i>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="management-empty table-empty">
                                <i class="bi bi-search"></i>
                                <strong>Catatan servis tidak ditemukan</strong>
                                <p>Belum ada data yang cocok dengan pencarian atau filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($services->hasPages())
            <div class="service-pagination">
                {{ $services->links() }}
            </div>
        @endif
    </section>
</div>

<div class="service-modal-backdrop" id="serviceDetailModal" aria-hidden="true">
    <section class="service-modal" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle">
        <div class="service-modal-header">
            <div>
                <span class="service-modal-eyebrow">INFORMASI CATATAN</span>
                <h2 id="serviceModalTitle">Detail Servis</h2>
                <p id="detailServiceId">-</p>
            </div>
            <button type="button" class="service-modal-close" id="closeServiceModal" aria-label="Tutup modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="service-modal-content">
            <h3><i class="bi bi-tools"></i> Informasi Servis</h3>
            <div class="service-detail-grid">
                <div class="service-detail-item">
                    <span>Service ID</span>
                    <strong id="detailServiceIdField">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Vehicle ID</span>
                    <strong id="detailVehicleId">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Nama Bengkel</span>
                    <strong id="detailWorkshop">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Jenis Servis</span>
                    <strong id="detailServiceType">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Tanggal Servis</span>
                    <strong id="detailServiceDate">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Biaya Servis</span>
                    <strong id="detailCost">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Kilometer</span>
                    <strong id="detailMileage">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Catatan Dibuat</span>
                    <strong id="detailCreatedAt">-</strong>
                </div>
                <div class="service-detail-item service-detail-full">
                    <span>Deskripsi</span>
                    <p id="detailDescription">-</p>
                </div>
            </div>

            <h3><i class="bi bi-car-front"></i> Informasi Kendaraan</h3>
            <div class="service-detail-grid">
                <div class="service-detail-item">
                    <span>Jenis Kendaraan</span>
                    <strong id="detailVehicleType">-</strong>
                </div>
                <div class="service-detail-item">
                    <span>Merek dan Model</span>
                    <strong id="detailVehicleBrand">-</strong>
                </div>
                <div class="service-detail-item service-detail-full">
                    <span>Pemilik Kendaraan</span>
                    <strong id="detailOwner">-</strong>
                </div>
            </div>

            <h3><i class="bi bi-geo-alt"></i> Informasi Bengkel</h3>
            <div class="service-detail-item service-detail-full">
                <span>Alamat Bengkel</span>
                <p id="detailWorkshopAddress">-</p>
            </div>
        </div>

        <div class="service-modal-footer">
            <button type="button" class="service-modal-done" id="doneServiceModal">Tutup</button>
        </div>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-vehicles-services.css') }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('serviceDetailModal');
    const closeButtons = [
        document.getElementById('closeServiceModal'),
        document.getElementById('doneServiceModal')
    ];

    const fields = {
        detailServiceId: 'serviceId',
        detailServiceIdField: 'serviceId',
        detailVehicleId: 'vehicleId',
        detailWorkshop: 'workshop',
        detailServiceType: 'serviceType',
        detailServiceDate: 'serviceDate',
        detailCost: 'cost',
        detailMileage: 'mileage',
        detailCreatedAt: 'createdAt',
        detailDescription: 'description',
        detailVehicleType: 'vehicleType',
        detailVehicleBrand: 'vehicleBrand',
        detailOwner: 'owner',
        detailWorkshopAddress: 'workshopAddress'
    };

    document.querySelectorAll('.service-detail-trigger').forEach(button => {
        button.addEventListener('click', function () {
            Object.entries(fields).forEach(([elementId, dataKey]) => {
                document.getElementById(elementId).textContent =
                    this.dataset[dataKey] || '-';
            });

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('service-modal-open');
        });
    });

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('service-modal-open');
    }

    closeButtons.forEach(button => button.addEventListener('click', closeModal));

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
});
</script>
@endpush
