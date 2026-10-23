@extends('workshop.layouts.app')

@section('title', 'Ringkasan Bengkel')
@section('page-heading', 'Ringkasan')

@section('content')
<div class="workshop-dashboard">

    @php
        $user = Auth::user();
        $workshopName = $workshop->workshop_name ?? 'Bengkel';
        $initials = collect(explode(' ', trim($workshopName)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    @endphp

    {{-- Header --}}
    <div class="workshop-welcome">
        <div>
            <p class="workshop-business-label">
                {{ strtoupper($workshopName) }}
            </p>

            <h2>Selamat datang, {{ $user->name }}.</h2>

            <p class="workshop-welcome-description">
                Pantau aktivitas dan catatan servis bengkel Anda.
            </p>
        </div>

        <a href="{{ route('workshop.records.index') }}"
           class="workshop-primary-button">
            <i class="bi bi-plus-lg"></i>
            Tambah Catatan Servis
        </a>
    </div>

    {{-- Statistik --}}
    <div class="workshop-stats-grid">

        <div class="workshop-stat-card">
            <div class="workshop-stat-icon workshop-stat-blue">
                <i class="bi bi-file-earmark-text"></i>
            </div>

            <div class="workshop-stat-info">
                <p>Total Servis Dicatat</p>
                <h3>{{ number_format($totalServices, 0, ',', '.') }}</h3>
                <small>
                    {{ number_format($servicesThisMonth, 0, ',', '.') }}
                    catatan baru dicatat bulan ini
                </small>
            </div>
        </div>

        <div class="workshop-stat-card">
            <div class="workshop-stat-icon workshop-stat-green">
                <i class="bi bi-car-front"></i>
            </div>

            <div class="workshop-stat-info">
                <p>Kendaraan Dilayani</p>
                <h3>{{ number_format($vehiclesServed, 0, ',', '.') }}</h3>
                <small>
                    kendaraan pada catatan yang dibuat bulan ini
                </small>
            </div>
        </div>

        <div class="workshop-stat-card">
            <div class="workshop-stat-icon workshop-stat-orange">
                <i class="bi bi-clock"></i>
            </div>

            <div class="workshop-stat-info">
                <p>Catatan Terbaru</p>

                @if ($latestService)
                    <h3>
                        {{ $latestService->created_at->isToday()
                            ? 'Dicatat hari ini'
                            : $latestService->created_at->translatedFormat('d M Y') }}
                    </h3>

                    <small>
                        {{ $latestService->created_at->format('H.i') }} WIB
                    </small>
                @else
                    <h3>Belum ada</h3>
                    <small>Belum ada catatan servis</small>
                @endif
            </div>
        </div>

    </div>

    {{-- Catatan servis terbaru --}}
    <section class="workshop-records-card">

        <div class="workshop-records-heading">
            <div>
                <h3>Catatan Servis Terbaru</h3>
                <p>Catatan servis yang terakhir disinkronkan</p>
            </div>

            <a href="{{ route('workshop.records.index') }}">
                Lihat semua
                <i class="bi bi-arrow-right"></i>
            </a>
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
                    @forelse ($recentServices as $service)
                        <tr class="js-workshop-dashboard-service" data-service-id="{{ $service->service_id }}">
                            <td>
                                <div class="workshop-vehicle-name">
                                    {{ $service->vehicle->brand ?? '-' }}
                                    {{ $service->vehicle->model ?? '' }}
                                </div>

                                <small class="workshop-vehicle-id">
                                    {{ $service->vehicle->vehicle_id ?? '-' }}
                                </small>
                            </td>

                            <td><span class="js-chain-service-type">Memuat dari blockchain...</span></td>

                            <td>
                                <span class="js-chain-service-date">Memuat...</span>
                            </td>

                            <td>
                                <span class="js-chain-service-mileage">Memuat...</span>
                                km
                            </td>

                            <td>
                                <span class="workshop-record-status">
                                    <i class="bi bi-circle-fill"></i>
                                    Tersimpan
                                </span>
                            </td>

                            <td>
                                <a
                                    href="{{ route('workshop.records.index') }}"
                                    class="workshop-record-detail"
                                    aria-label="Lihat catatan servis"
                                >
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="workshop-records-no-data">
                                <i class="bi bi-file-earmark-text"></i>
                                <strong>Belum ada catatan servis</strong>
                                <span>
                                    Catatan servis yang Anda buat akan muncul di sini.
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

</div>
@endsection