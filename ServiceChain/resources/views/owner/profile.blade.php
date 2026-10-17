 @extends('owner.layouts.app')

@section('title', 'Profil & Pengaturan')
@section('page-heading', 'Profil & Pengaturan')

@section('content')
<div class="profile-page">
    <div class="profile-page-heading">
        <div>
            <h1>Profil dan keamanan akun</h1>
            <p>Kelola informasi akun, wallet, dan preferensi keamanan.</p>
        </div>

        <button type="submit" form="profileForm" class="primary-button">
            Simpan Perubahan
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="profile-layout">
        <section class="profile-summary-card">
            <div class="profile-avatar">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>

            <h2>{{ $user->name }}</h2>
            <p class="profile-role">Pemilik Kendaraan</p>
        </section>

        <section class="profile-settings-card">
            <form id="profileForm"
                  method="POST"
                  action="{{ route('owner.profile.update') }}">
                @csrf
                @method('PUT')

                <h2>Informasi akun</h2>

                <div class="profile-form-grid">
                    <div class="profile-field">
                        <label for="fullName">Nama lengkap</label>
                        <input
                            type="text"
                            id="fullName"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="profile-field">
                        <label for="email">Alamat email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="profile-field">
                        <label for="walletAddress">Alamat wallet</label>
                        <input
                            type="text"
                            id="walletAddress"
                            value="{{ $user->wallet_address ?: 'Wallet belum terhubung' }}"
                            readonly
                        >
                    </div>
                </div>

                <div class="profile-security-section">
                    <h2>Keamanan</h2>

                    <div class="profile-security-actions">
                        <a href="{{ url('/owner/profile/password') }}"
                           class="profile-action-button">
                            <i class="bi bi-lock"></i>
                            Ubah kata sandi
                        </a>

                        <button
                            type="button"
                            class="profile-logout-button"
                            onclick="document.getElementById('logoutForm').submit()"
                        >
                            <i class="bi bi-box-arrow-right"></i>
                            Logout
                        </button>
                    </div>
                </div>
            </form>

            <form id="logoutForm"
                  action="{{ route('logout') }}"
                  method="POST"
                  hidden>
                @csrf
            </form>
        </section>
    </div>
</div>
@endsection