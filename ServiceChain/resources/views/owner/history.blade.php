@extends('owner.layouts.app')

@section('title', 'Riwayat Servis')
@section('page-heading', 'Riwayat Servis')

@section('content')
<div class="service-history-page">
    <div class="history-heading">
        <h1>Riwayat dan catatan servis</h1>
        <p>Cari, filter, dan tinjau riwayat servis kendaraan Anda.</p>
    </div>

    <form action="{{ route('owner.history') }}" method="GET" class="history-filters">
        <label class="history-search">
            <i class="bi bi-search"></i>
            <input
                type="search"
                name="search"
                id="historySearch"
                value="{{ $search }}"
                placeholder="Cari nama kendaraan, Vehicle ID, atau jenis servis..."
            >
        </label>

        <select name="service_type" id="historyType" aria-label="Filter jenis servis">
            <option value="all" {{ request('service_type', 'all') === 'all' ? 'selected' : '' }}>Semua jenis servis</option>
            @foreach ($services->getCollection()->pluck('service_type')->unique()->filter() as $type)
                <option value="{{ $type }}" {{ request('service_type') === $type ? 'selected' : '' }}>
                    {{ $type }}
                </option>
            @endforeach
        </select>

        <select name="sort" id="historySort" aria-label="Urutkan riwayat">
            <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Terbaru</option>
            <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Terlama</option>
            <option value="name-asc" {{ $sort === 'name-asc' ? 'selected' : '' }}>Nama A-Z</option>
            <option value="name-desc" {{ $sort === 'name-desc' ? 'selected' : '' }}>Nama Z-A</option>
        </select>

        <button type="submit" class="history-filter-submit">Cari</button>
    </form>

    <section class="history-table-card">
        <div class="history-card-heading">
            <div>
                <h2>Catatan Servis</h2>
                <p>Riwayat servis kendaraan yang Anda miliki</p>
            </div>

            <a href="{{ route('owner.history') }}" class="history-see-all">
                Lihat semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="history-table-wrapper">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Kendaraan</th>
                        <th>Jenis Servis</th>
                        <th>Tanggal</th>
                        <th>Kilometer</th>
                        <th>Harga</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="historyTableBody">
                    @forelse ($services as $service)
                        <tr>
                            <td>
                                <div class="history-vehicle-name">
                                    {{ $service->vehicle->brand }} {{ $service->vehicle->model }}
                                </div>
                                <span class="history-vehicle-id">
                                    {{ $service->vehicle->vehicle_id }}
                                </span>
                            </td>
                            <td>{{ $service->service_type }}</td>
                            <td>{{ $service->service_date->translatedFormat('d F Y') }}</td>
                            <td>{{ number_format($service->mileage, 0, ',', '.') }} km</td>
                            <td>Rp{{ number_format((float) $service->cost, 0, ',', '.') }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="history-detail-button js-history-detail"
                                    aria-label="Lihat detail servis"
                                    data-service-id="{{ $service->service_id }}"
                                    data-vehicle="{{ $service->vehicle->brand }} {{ $service->vehicle->model }}"
                                    data-vehicle-id="{{ $service->vehicle->vehicle_id }}"
                                    data-service-type="{{ $service->service_type }}"
                                    data-date="{{ $service->service_date->translatedFormat('d F Y') }}"
                                    data-mileage="{{ number_format($service->mileage, 0, ',', '.') }} km"
                                    data-cost="Rp{{ number_format((float) $service->cost, 0, ',', '.') }}"
                                    data-workshop="{{ $service->workshop_name }}"
                                    data-address="{{ $service->workshop_address }}"
                                    data-description="{{ $service->description }}"
                                >
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="history-empty-cell">
                                <i class="bi bi-search"></i>
                                <strong>Catatan servis tidak ditemukan</strong>
                                <p>Riwayat servis kendaraan Anda akan muncul di sini setelah tersedia.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($services->hasPages())
            <div class="history-pagination">
                {{ $services->withQueryString()->links() }}
            </div>
        @endif
    </section>
</div>

<div class="history-detail-modal" id="historyDetailModal" aria-hidden="true">
    <div class="history-detail-overlay" data-history-close></div>

    <div class="history-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="historyDetailTitle">
        <div class="history-detail-header">
            <div>
                <span>DETAIL RIWAYAT SERVIS</span>
                <h2 id="historyDetailTitle">Detail Servis</h2>
            </div>

            <button type="button" class="history-modal-close" data-history-close aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="history-detail-content">
            <div class="history-detail-service-id">
                <span>Service ID</span>
                <strong id="detailServiceId">-</strong>
            </div>

            <div class="history-detail-grid">
                <div class="history-detail-field">
                    <span>Kendaraan</span>
                    <strong id="detailVehicle">-</strong>
                </div>

                <div class="history-detail-field">
                    <span>ID Kendaraan</span>
                    <strong id="detailVehicleId">-</strong>
                </div>

                <div class="history-detail-field">
                    <span>Jenis Servis</span>
                    <strong id="detailServiceType">-</strong>
                </div>

                <div class="history-detail-field">
                    <span>Tanggal Servis</span>
                    <strong id="detailDate">-</strong>
                </div>

                <div class="history-detail-field">
                    <span>Kilometer</span>
                    <strong id="detailMileage">-</strong>
                </div>

                <div class="history-detail-field">
                    <span>Harga Servis</span>
                    <strong id="detailCost">-</strong>
                </div>

                <div class="history-detail-field history-detail-wide">
                    <span>Nama Bengkel</span>
                    <strong id="detailWorkshop">-</strong>
                </div>

                <div class="history-detail-field history-detail-wide">
                    <span>Alamat Bengkel</span>
                    <strong id="detailAddress">-</strong>
                </div>

                <div class="history-detail-field history-detail-wide">
                    <span>Deskripsi Servis</span>
                    <p id="detailDescription">-</p>
                </div>
            </div>
        </div>

        <div class="history-detail-footer">
            <button type="button" data-history-close>Tutup</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('historyDetailModal');
    const detailButtons = document.querySelectorAll('.js-history-detail');
    const closeButtons = modal.querySelectorAll('[data-history-close]');

    const fields = {
        serviceId: document.getElementById('detailServiceId'),
        vehicle: document.getElementById('detailVehicle'),
        vehicleId: document.getElementById('detailVehicleId'),
        serviceType: document.getElementById('detailServiceType'),
        date: document.getElementById('detailDate'),
        mileage: document.getElementById('detailMileage'),
        cost: document.getElementById('detailCost'),
        workshop: document.getElementById('detailWorkshop'),
        address: document.getElementById('detailAddress'),
        description: document.getElementById('detailDescription')
    };

    detailButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            fields.serviceId.textContent = button.dataset.serviceId;
            fields.vehicle.textContent = button.dataset.vehicle;
            fields.vehicleId.textContent = button.dataset.vehicleId;
            fields.serviceType.textContent = button.dataset.serviceType;
            fields.date.textContent = button.dataset.date;
            fields.mileage.textContent = button.dataset.mileage;
            fields.cost.textContent = button.dataset.cost;
            fields.workshop.textContent = button.dataset.workshop;
            fields.address.textContent = button.dataset.address;
            fields.description.textContent = button.dataset.description;

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('history-detail-open');
        });
    });

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('history-detail-open');
    }

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
});
</script>
@endsection
