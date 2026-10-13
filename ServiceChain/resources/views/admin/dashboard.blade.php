@extends('admin.layouts.app')

@section('title', 'Ringkasan Admin')
@section('page-title', 'Ringkasan')
@section('page-subtitle', 'Pantau dan kelola aktivitas platform.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
@endpush

@section('content')
<div class="admin-dashboard">
    <div class="dashboard-intro">
        <div class="dashboard-intro-text">
            <span class="dashboard-eyebrow">PUSAT KONTROL SISTEM</span>
            <h1>Ringkasan operasional.</h1>
            <p>Pantau pengguna, kendaraan, dan aktivitas platform.</p>
        </div>

        <a href="{{ route('admin.workshops.index') }}" class="dashboard-review-button">
            <i class="bi bi-shop"></i>
            <span>Tinjau Pengajuan</span>
            <span class="dashboard-review-count">{{ $pendingWorkshopCount }}</span>
        </a>
    </div>

    <section class="dashboard-stats">
        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon dashboard-icon-blue">
                <i class="bi bi-people"></i>
            </div>
            <div class="dashboard-stat-content">
                <p>Total Pengguna</p>
                <h2>{{ number_format($totalUsers, 0, ',', '.') }}</h2>
                <small>Pengguna terdaftar</small>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon dashboard-icon-green">
                <i class="bi bi-car-front"></i>
            </div>
            <div class="dashboard-stat-content">
                <p>Total Kendaraan</p>
                <h2>{{ number_format($totalVehicles, 0, ',', '.') }}</h2>
                <small>Kendaraan terdaftar</small>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon dashboard-icon-purple">
                <i class="bi bi-shop"></i>
            </div>
            <div class="dashboard-stat-content">
                <p>Bengkel Aktif</p>
                <h2>{{ number_format($activeWorkshops, 0, ',', '.') }}</h2>
                <small>{{ $pendingWorkshopCount }} menunggu tinjauan</small>
            </div>
        </div>

        <div class="dashboard-stat-card">
            <div class="dashboard-stat-icon dashboard-icon-orange">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="dashboard-stat-content">
                <p>Catatan Servis</p>
                <h2>{{ number_format($totalServices, 0, ',', '.') }}</h2>
                <small>Total catatan tersimpan</small>
            </div>
        </div>
    </section>

    <section class="dashboard-panels">
        <div class="dashboard-panel dashboard-pending-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Menunggu Persetujuan</h3>
                    <p>{{ $pendingWorkshopCount }} pengajuan bengkel perlu ditinjau</p>
                </div>

                <a href="{{ route('admin.workshops.index') }}" class="dashboard-view-all">
                    Lihat semua
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="dashboard-pending-list">
                @forelse ($pendingWorkshops as $workshop)
                    <div class="dashboard-pending-item">
                        <div class="dashboard-list-icon">
                            <i class="bi bi-shop"></i>
                        </div>

                        <div class="dashboard-pending-info">
                            <strong>{{ $workshop->workshop_name }}</strong>
                            <small>
                                {{ $workshop->city }}
                                · {{ $workshop->created_at->format('d M Y, H.i') }}
                            </small>
                        </div>

                        <span class="dashboard-pending-badge">
                            <span></span>
                            Menunggu
                        </span>

                        <a href="{{ route('admin.workshops.index') }}" class="dashboard-detail-button">
                            Tinjau
                        </a>
                    </div>
                @empty
                    <div class="dashboard-empty-state">
                        <i class="bi bi-check-circle"></i>
                        <strong>Semua pengajuan sudah diproses</strong>
                        <p>Belum ada pengajuan bengkel yang menunggu persetujuan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="dashboard-panel dashboard-activity-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h3>Aktivitas Terbaru</h3>
                    <p>Pembaruan data platform</p>
                </div>
            </div>

            <div class="dashboard-activity-list">
                @forelse ($recentActivities as $activity)
                    <div class="dashboard-activity-item">
                        <div class="dashboard-activity-icon
                            @switch($activity['type'])
                                @case('workshop')
                                    dashboard-icon-blue
                                    @break
                                @case('user')
                                    dashboard-icon-green
                                    @break
                                @case('vehicle')
                                    dashboard-icon-purple
                                    @break
                                @default
                                    dashboard-icon-orange
                            @endswitch
                        ">
                            @switch($activity['type'])
                                @case('workshop')
                                    <i class="bi bi-shop"></i>
                                    @break
                                @case('user')
                                    <i class="bi bi-person-plus"></i>
                                    @break
                                @case('vehicle')
                                    <i class="bi bi-car-front"></i>
                                    @break
                                @default
                                    <i class="bi bi-file-earmark-text"></i>
                            @endswitch
                        </div>

                        <div class="dashboard-activity-info">
                            <strong>{{ $activity['title'] }}</strong>
                            <small>{{ $activity['description'] }}</small>
                        </div>

                        <span class="dashboard-activity-time">
                            {{ $activity['created_at']->diffForHumans() }}
                        </span>
                    </div>
                @empty
                    <div class="dashboard-empty-state">
                        <i class="bi bi-clock-history"></i>
                        <strong>Belum ada aktivitas</strong>
                        <p>Aktivitas terbaru akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection