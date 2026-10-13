@extends('owner.layouts.app')

@section('title', 'Ringkasan Owner')
@section('page-heading', 'Ringkasan')

@section('content')
<div class="welcome-row">
    <div>
        <p class="date-label">{{ now()->translatedFormat('l, d F Y') }}</p>

        <h1>
            Selamat datang kembali,
            {{ explode(' ', auth()->user()->name)[0] }}.
        </h1>

        <p class="welcome-description">
            Berikut ringkasan kendaraan dan aktivitas servis Anda.
        </p>
    </div>

    <a href="{{ route('owner.vehicles') }}" class="primary-button">
        <i class="bi bi-plus-lg"></i>
        Tambah Kendaraan
    </a>
</div>

<div class="stats-grid">
    <article class="stat-card">
        <div class="stat-icon blue">
            <i class="bi bi-car-front"></i>
        </div>

        <div>
            <p>Total Kendaraan</p>
            <h2>{{ number_format($totalVehicles, 0, ',', '.') }}</h2>
            <small class="positive">
                {{ $activeVehicles }} kendaraan aktif
            </small>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon green">
            <i class="bi bi-clock-history"></i>
        </div>

        <div>
            <p>Riwayat Servis</p>
            <h2>{{ number_format($totalServices, 0, ',', '.') }}</h2>
            <small class="positive">
                Total catatan servis kendaraan Anda
            </small>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon purple">
            <i class="bi bi-shield-check"></i>
        </div>

        <div>
            <p>Catatan Terverifikasi</p>
            <h2>{{ number_format($totalServices, 0, ',', '.') }}</h2>
            <small class="positive">
                Catatan servis tersimpan di Blockchain
            </small>
        </div>
    </article>
</div>

<div class="dashboard-grid">
    <section class="panel vehicle-panel">
        <div class="panel-heading">
            <div>
                <h3>Kendaraan Saya</h3>
                <p>Kendaraan yang terhubung ke akun Anda</p>
            </div>

            <a href="{{ route('owner.vehicles') }}" class="view-all">
                Lihat semua
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="vehicle-list">
            @forelse ($vehicles as $vehicle)
                <div class="vehicle-row">
                    <div class="vehicle-icon">
                        @if (strtolower($vehicle->vehicle_type) === 'motor')
                        <i class="bi bi-scooter vehicle-cover-icon"></i>
                        @else
                            <i class="bi bi-car-front vehicle-cover-icon"></i>
                        @endif
                    </div>

                    <div class="vehicle-name">
                        <strong>
                            {{ $vehicle->brand }} {{ $vehicle->model }}
                        </strong>

                        <small>
                            {{ $vehicle->vehicle_type }} · {{ $vehicle->year }}
                        </small>
                    </div>

                    <div class="vehicle-id">
                        <small>VEHICLE ID</small>
                        <strong>{{ $vehicle->vehicle_id }}</strong>
                    </div>

                    <span class="status-badge">
                        <i></i>
                        {{ $vehicle->status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            @empty
                <div class="dashboard-empty">
                    <i class="bi bi-car-front"></i>
                    <p>Anda belum memiliki kendaraan.</p>

                    <a href="{{ route('owner.vehicles') }}">
                        Lihat daftar kendaraan Anda
                    </a>
                </div>
            @endforelse
        </div>
    </section>

    <section class="panel activity-panel">
        <div class="panel-heading">
            <div>
                <h3>Aktivitas Terbaru</h3>
                <p>Pembaruan catatan servis kendaraan Anda</p>
            </div>
        </div>

        <div class="activity-list">
            @forelse ($recentServices as $service)
                <div class="activity-row">
                    <div class="activity-icon blue">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="activity-description">
                        <strong>{{ $service->service_type }}</strong>

                        <small>
                            {{ $service->brand }} {{ $service->model }}
                            · {{ $service->workshop_name }}
                        </small>
                    </div>

                    <time datetime="{{ $service->service_date }}">
                        {{ \Carbon\Carbon::parse($service->service_date)->translatedFormat('d M Y') }}
                    </time>
                </div>
            @empty
                <div class="dashboard-empty">
                    <i class="bi bi-clock-history"></i>
                    <p>Belum ada riwayat servis kendaraan.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection