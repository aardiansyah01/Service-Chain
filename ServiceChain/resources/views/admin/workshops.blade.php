@extends('admin.layouts.app')

@section('title', 'Manajemen Bengkel')
@section('page-title', 'Manajemen Bengkel')
@section('page-subtitle', 'Pantau dan kelola aktivitas platform.')

@section('content')
<div class="management-page">
    <div class="management-heading">
        <h1>Pengajuan dan status bengkel</h1>
        <p>Cari, filter, dan tinjau data bengkel yang terdaftar pada platform.</p>
    </div>

    @if (session('success'))
        <div class="workshop-alert success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="workshop-alert error">
            <i class="bi bi-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="management-toolbar">
        <div class="management-search">
            <i class="bi bi-search"></i>
            <input type="text" id="workshopSearch"
                   placeholder="Cari nama bengkel, kota, pemilik, atau email..."
                   aria-label="Cari bengkel">
        </div>

        <select id="workshopStatus" class="management-select"
                aria-label="Filter status">
            <option value="all">Semua status</option>
            <option value="PENDING">Menunggu</option>
            <option value="ACTIVE">Aktif</option>
            <option value="REJECTED">Ditolak</option>
            <option value="INACTIVE">Nonaktif</option>
        </select>

        <select id="workshopSort" class="management-select sort-select"
                aria-label="Urutkan bengkel">
            <option value="newest">Terbaru</option>
            <option value="oldest">Terlama</option>
            <option value="name-asc">Nama A-Z</option>
            <option value="name-desc">Nama Z-A</option>
        </select>
    </div>

    {{-- Tabel pengajuan yang menunggu persetujuan --}}
    <section class="management-panel workshop-section">
        <div class="management-panel-header">
            <div>
                <h3>Menunggu Persetujuan</h3>
                <p><span id="pendingWorkshopCount">0</span> pengajuan perlu ditinjau</p>
            </div>
        </div>

        <div class="workshop-table-wrap">
            <table class="workshop-data-table">
                <thead>
                    <tr>
                        <th>NAMA BENGKEL</th>
                        <th>KOTA</th>
                        <th>TANGGAL DAFTAR</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody id="pendingWorkshopBody">
                    @foreach ($workshops->where('status', 'PENDING') as $workshop)
                        <tr class="workshop-row"
                            data-status="{{ $workshop->status }}"
                            data-name="{{ $workshop->workshop_name }}"
                            data-city="{{ $workshop->city }}"
                            data-owner="{{ $workshop->owner_name }}"
                            data-email="{{ $workshop->user?->email ?? '' }}"
                            data-date="{{ $workshop->created_at?->format('Y-m-d') }}"
                            data-id="{{ $workshop->id }}"
                            data-wallet="{{ $workshop->wallet_address ?? '' }}"
                            data-address="{{ $workshop->address }}"
                            data-phone="{{ $workshop->phone }}"
                            data-license="{{ $workshop->license_number }}"
                        >
                            <td>
                                <div class="workshop-name">{{ $workshop->workshop_name }}</div>
                                <small class="workshop-subtext">
                                    Pemilik: {{ $workshop->owner_name }}
                                </small>
                            </td>
                            <td>{{ $workshop->city }}</td>
                            <td>{{ $workshop->created_at?->format('d M Y') }}</td>
                            <td><span class="management-status pending">Menunggu</span></td>
                            <td>
                                <button type="button"
                                    class="management-detail-button workshop-review-btn">
                                    Tinjau
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="workshop-empty" id="pendingWorkshopEmpty" hidden>
                Tidak ada pengajuan untuk saat ini.
            </div>
        </div>
    </section>

    {{-- Tabel bengkel yang sudah diproses --}}
    <section class="management-panel workshop-section">
        <div class="management-panel-header">
            <div>
                <h3>Bengkel Terdaftar</h3>
                <p>Bengkel yang telah mendapatkan verifikasi dari Admin.</p>
            </div>
        </div>

        <div class="workshop-table-wrap">
            <table class="workshop-data-table">
                <thead>
                    <tr>
                        <th>NAMA BENGKEL</th>
                        <th>KOTA</th>
                        <th>TANGGAL DAFTAR</th>
                        <th>STATUS</th>
                        <th>DETAIL</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody id="processedWorkshopBody">
                    @foreach ($workshops->where('status', '!=', 'PENDING') as $workshop)
                        <tr class="workshop-row"
                            data-status="{{ $workshop->status }}"
                            data-name="{{ $workshop->workshop_name }}"
                            data-city="{{ $workshop->city }}"
                            data-owner="{{ $workshop->owner_name }}"
                            data-email="{{ $workshop->user?->email ?? '' }}"
                            data-date="{{ $workshop->created_at?->format('Y-m-d') }}"
                            data-id="{{ $workshop->id }}"
                            data-wallet="{{ $workshop->wallet_address ?? '' }}"
                            data-address="{{ $workshop->address }}"
                            data-phone="{{ $workshop->phone }}"
                            data-license="{{ $workshop->license_number }}"
                        >
                            <td>
                                <div class="workshop-name">{{ $workshop->workshop_name }}</div>
                                <small class="workshop-subtext">
                                    Pemilik: {{ $workshop->owner_name }}
                                </small>
                            </td>
                            <td>{{ $workshop->city }}</td>
                            <td>{{ $workshop->created_at?->format('d M Y') }}</td>
                            <td>
                                @if ($workshop->status === 'ACTIVE')
                                    <span class="management-status active">Aktif</span>
                                @elseif ($workshop->status === 'REJECTED')
                                    <span class="management-status rejected">Ditolak</span>
                                @else
                                    <span class="management-status inactive">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <button type="button"
                                    class="workshop-icon-btn workshop-detail-btn"
                                    aria-label="Lihat detail {{ $workshop->workshop_name }}"
                                    title="Lihat detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                            <td>
                                @if ($workshop->status === 'ACTIVE')
                                    <form method="POST"
                                        action="{{ route('admin.workshops.deactivate', $workshop) }}"
                                        class="workshop-action-form"
                                        data-confirm="Nonaktifkan bengkel {{ $workshop->workshop_name }}? Bengkel ini tidak boleh mencatat servis selama nonaktif.">
                                        @csrf
                                        <button type="submit"
                                            class="workshop-icon-btn danger"
                                            aria-label="Nonaktifkan {{ $workshop->workshop_name }}"
                                            title="Nonaktifkan bengkel">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="workshop-no-action">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="workshop-empty" id="processedWorkshopEmpty" hidden>
                Tidak ada data bengkel untuk saat ini.
            </div>
        </div>
    </section>
</div>

{{-- Modal detail dan tinjau bengkel --}}
<div class="workshop-modal" id="workshopModal" hidden>
    <div class="workshop-modal-backdrop" data-close-modal></div>
    <div class="workshop-modal-dialog" role="dialog"
         aria-modal="true" aria-labelledby="workshopModalTitle">
        <div class="workshop-modal-header">
            <div>
                <h3 id="workshopModalTitle">Detail Bengkel</h3>
                <p>Informasi lengkap bengkel yang terdaftar.</p>
            </div>
            <button type="button" class="workshop-modal-close"
                    data-close-modal aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="workshop-modal-body">
            <div class="workshop-detail-grid">
                <div><small>Workshop ID</small><strong id="detailId">-</strong></div>
                <div><small>Wallet Address</small><strong id="detailWallet">-</strong></div>
                <div><small>Nama Bengkel</small><strong id="detailName">-</strong></div>
                <div><small>Nama Pemilik</small><strong id="detailOwner">-</strong></div>
                <div class="full-width"><small>Alamat</small><strong id="detailAddress">-</strong></div>
                <div><small>Kota</small><strong id="detailCity">-</strong></div>
                <div><small>Nomor Telepon</small><strong id="detailPhone">-</strong></div>
                <div><small>Email</small><strong id="detailEmail">-</strong></div>
                <div><small>Nomor Lisensi</small><strong id="detailLicense">-</strong></div>
                <div><small>Status</small><strong id="detailStatus">-</strong></div>
            </div>
        </div>

        <div class="workshop-modal-footer" id="workshopModalActions">
            <button type="button" class="workshop-secondary-btn" data-close-modal>
                Tutup
            </button>

            <form method="POST" id="rejectWorkshopForm">
                @csrf
                <button type="submit" class="workshop-reject-btn">
                    Tolak Pengajuan
                </button>
            </form>

            <form method="POST" id="approveWorkshopForm">
                @csrf
                <button type="submit" class="workshop-approve-btn">
                    <i class="bi bi-check-lg"></i> Setujui Bengkel
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('workshopSearch');
    const statusFilter = document.getElementById('workshopStatus');
    const sortFilter = document.getElementById('workshopSort');

    const pendingBody = document.getElementById('pendingWorkshopBody');
    const processedBody = document.getElementById('processedWorkshopBody');
    const pendingEmpty = document.getElementById('pendingWorkshopEmpty');
    const processedEmpty = document.getElementById('processedWorkshopEmpty');
    const pendingCount = document.getElementById('pendingWorkshopCount');

    const modal = document.getElementById('workshopModal');
    const approveForm = document.getElementById('approveWorkshopForm');
    const rejectForm = document.getElementById('rejectWorkshopForm');
    const modalActions = document.getElementById('workshopModalActions');

    const statusLabels = {
        PENDING: 'Menunggu Persetujuan',
        ACTIVE: 'Aktif',
        REJECTED: 'Ditolak',
        INACTIVE: 'Nonaktif'
    };

    function getRows() {
        return Array.from(document.querySelectorAll('.workshop-row'));
    }

    function renderWorkshops() {
        const keyword = search.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedSort = sortFilter.value;
        const rows = getRows();

        rows.forEach(row => {
            const searchable = [
                row.dataset.name,
                row.dataset.city,
                row.dataset.owner,
                row.dataset.email,
                row.dataset.license,
                row.dataset.id
            ].join(' ').toLowerCase();

            const matchesKeyword = searchable.includes(keyword);
            const matchesStatus =
                selectedStatus === 'all' || row.dataset.status === selectedStatus;

            row.hidden = !(matchesKeyword && matchesStatus);
        });

        [pendingBody, processedBody].forEach(body => {
            const visibleRows = Array.from(body.querySelectorAll('.workshop-row'))
                .filter(row => !row.hidden);

            visibleRows.sort((a, b) => {
                switch (selectedSort) {
                    case 'oldest':
                        return (a.dataset.date || '').localeCompare(b.dataset.date || '');
                    case 'name-asc':
                        return a.dataset.name.localeCompare(b.dataset.name);
                    case 'name-desc':
                        return b.dataset.name.localeCompare(a.dataset.name);
                    default:
                        return (b.dataset.date || '').localeCompare(a.dataset.date || '');
                }
            });

            visibleRows.forEach(row => body.appendChild(row));
        });

        const visiblePending = Array.from(pendingBody.querySelectorAll('.workshop-row'))
            .filter(row => !row.hidden).length;

        const visibleProcessed = Array.from(processedBody.querySelectorAll('.workshop-row'))
            .filter(row => !row.hidden).length;

        pendingCount.textContent = visiblePending;
        pendingEmpty.hidden = visiblePending !== 0;
        processedEmpty.hidden = visibleProcessed !== 0;
    }

    function fillDetail(row) {
        const data = row.dataset;

        document.getElementById('detailId').textContent = data.id || '-';
        document.getElementById('detailWallet').textContent = data.wallet || 'Belum terhubung';
        document.getElementById('detailName').textContent = data.name || '-';
        document.getElementById('detailOwner').textContent = data.owner || '-';
        document.getElementById('detailAddress').textContent = data.address || '-';
        document.getElementById('detailCity').textContent = data.city || '-';
        document.getElementById('detailPhone').textContent = data.phone || '-';
        document.getElementById('detailEmail').textContent = data.email || 'Belum tersedia';
        document.getElementById('detailLicense').textContent = data.license || '-';
        document.getElementById('detailStatus').textContent =
            statusLabels[data.status] || data.status || '-';

        const isPending = data.status === 'PENDING';

        approveForm.action = `/admin/workshops/${data.id}/approve`;
        rejectForm.action = `/admin/workshops/${data.id}/reject`;

        approveForm.hidden = !isPending;
        rejectForm.hidden = !isPending;

        modalActions.classList.toggle('pending-actions', isPending);
        modal.hidden = false;
        document.body.classList.add('workshop-modal-open');
    }

    document.querySelectorAll('.workshop-detail-btn, .workshop-review-btn')
        .forEach(button => {
            button.addEventListener('click', function () {
                fillDetail(button.closest('.workshop-row'));
            });
        });

    document.querySelectorAll('[data-close-modal]').forEach(button => {
        button.addEventListener('click', function () {
            modal.hidden = true;
            document.body.classList.remove('workshop-modal-open');
        });
    });

    document.querySelectorAll('.workshop-action-form').forEach(form => {
        form.addEventListener('submit', function (event) {
            const message = form.dataset.confirm || 'Lanjutkan tindakan ini?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    approveForm.addEventListener('submit', function (event) {
        if (!window.confirm('Setujui pengajuan bengkel ini?')) {
            event.preventDefault();
        }
    });

    rejectForm.addEventListener('submit', function (event) {
        if (!window.confirm('Tolak pengajuan bengkel ini?')) {
            event.preventDefault();
        }
    });

    search.addEventListener('input', renderWorkshops);
    statusFilter.addEventListener('change', renderWorkshops);
    sortFilter.addEventListener('change', renderWorkshops);

    renderWorkshops();
});
</script>
@endpush
