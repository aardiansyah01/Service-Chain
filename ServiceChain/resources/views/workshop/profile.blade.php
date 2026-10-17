@extends('workshop.layouts.app')

@section('title', 'Profil Bengkel')
@section('page-heading', 'Profil Bengkel')

@section('content')
<div class="workshop-page workshop-profile-page">

    <div class="workshop-page-heading">
        <div>
            <h2>Profil dan keamanan akun</h2>
            <p>Kelola informasi akun, wallet, dan preferensi keamanan.</p>
        </div>

        <button type="submit" form="workshopProfileForm"
                class="workshop-primary-button">
            Simpan Perubahan
        </button>
    </div>

    @if (session('success'))
        <div class="workshop-record-alert success">
            <i class="bi bi-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="workshop-record-alert error">
            <strong>Perubahan gagal disimpan.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $workshop = Auth::user()->workshop;
        $workshopName = old('workshop_name', $workshop->workshop_name);
        $status = strtoupper($workshop->status ?? 'PENDING');

        $statusLabels = [
            'ACTIVE' => 'Terverifikasi',
            'PENDING' => 'Menunggu Verifikasi',
            'REJECTED' => 'Ditolak',
            'INACTIVE' => 'Tidak Aktif',
        ];

        $initials = collect(explode(' ', trim($workshopName)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    @endphp

    <div class="workshop-profile-layout">

        <section class="workshop-profile-summary">
            <div class="workshop-profile-avatar">
                {{ $initials ?: 'B' }}
            </div>

            <h3>{{ $workshop->workshop_name }}</h3>

            <p>
                {{ $statusLabels[$status] ?? $status }}
            </p>

            <span class="workshop-record-status">
                <i class="bi bi-circle-fill"></i>
                {{ $statusLabels[$status] ?? $status }}
            </span>
        </section>

        <section class="workshop-profile-settings">

            <form id="workshopProfileForm"
                  method="POST"
                  action="{{ route('workshop.profile.update') }}">
                @csrf
                @method('PUT')

                <h3>Informasi akun</h3>

                <div class="workshop-profile-form-grid">

                    <div class="workshop-profile-field">
                        <label for="workshopName">Nama bengkel</label>
                        <input
                            type="text"
                            id="workshopName"
                            name="workshop_name"
                            value="{{ $workshopName }}"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="workshop-profile-field">
                        <label for="workshopEmail">Alamat email</label>
                        <input
                            type="email"
                            id="workshopEmail"
                            name="email"
                            value="{{ old('email', Auth::user()->email) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="workshop-profile-field">
                        <label for="workshopWallet">Alamat wallet</label>
                        <input
                            type="text"
                            id="workshopWallet"
                            value="{{ $workshop->wallet_address ?: 'Wallet belum terhubung' }}"
                            readonly
                        >
                        <small>Alamat wallet tidak dapat diubah melalui halaman ini.</small>
                    </div>

                </div>

                <div class="workshop-profile-security">
                    <h3>Keamanan</h3>

                    <div class="workshop-profile-actions">

                        <a href="{{ url('/workshop/profile/password') }}"
                           class="workshop-profile-action">
                            <i class="bi bi-lock"></i>
                            Ubah kata sandi
                        </a>

                        <button type="submit"
                                form="workshopLogoutForm"
                                class="workshop-profile-action workshop-logout-action">
                            <i class="bi bi-box-arrow-right"></i>
                            Logout
                        </button>

                    </div>
                </div>

            </form>

            <form id="workshopLogoutForm"
                  action="{{ route('logout') }}"
                  method="POST"
                  hidden>
                @csrf
            </form>

        </section>
    </div>
</div>
@endsection