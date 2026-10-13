
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - ServiceChain</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
<div class="auth-container">
    <a href="/" class="brand"><span class="brand-shield">♢</span> ServiceChain</a>

    <h1>Buat akun baru</h1>
    <p class="subtitle">Pilih jenis akun untuk mulai menggunakan ServiceChain.</p>

    @if ($errors->any())
        <div class="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="role-switch">
        <a href="{{ route('register', ['role' => 'owner']) }}"
           class="{{ $role === 'owner' ? 'active' : '' }}">
            <i class="role-symbol">♙</i>
            <span>Owner Vehicle</span>
        </a>

        <a href="{{ route('register', ['role' => 'workshop']) }}"
           class="{{ $role === 'workshop' ? 'active' : '' }}">
            <i class="role-symbol">▦</i>
            <span>Workshop</span>
        </a>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">

        @if ($role === 'owner')
            <label>Nama lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <label>Password</label>
            <input type="password" name="password" required minlength="8">

            <label>Konfirmasi password</label>
            <input type="password" name="password_confirmation" required minlength="8">
        @else
            <label>Nama bengkel</label>
            <input type="text" name="workshop_name" value="{{ old('workshop_name') }}" required>

            <label>Nama pemilik / penanggung jawab</label>
            <input type="text" name="owner_name" value="{{ old('owner_name') }}" required>

            <label>Alamat bengkel</label>
            <textarea name="address" rows="3" required>{{ old('address') }}</textarea>

            <label>Kota</label>
            <input type="text" name="city" value="{{ old('city') }}" required>

            <label>Nomor telepon</label>
            <input type="tel" name="phone" value="{{ old('phone') }}" required>

            <label>Email akun</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <label>Nomor lisensi / legalitas bengkel</label>
            <input type="text" name="license_number" value="{{ old('license_number') }}" required>

            <label>Password</label>
            <input type="password" name="password" required minlength="8">

            <label>Konfirmasi password</label>
            <input type="password" name="password_confirmation" required minlength="8">

            <p class="review-note">
                <i>ⓘ</i> Pendaftaran bengkel akan diperiksa oleh Admin sebelum Anda dapat mencatat servis.
            </p>
        @endif

        <button type="submit">Daftar sebagai {{ $role === 'owner' ? 'Owner' : 'Workshop' }}</button>
    </form>

    <p class="switch-auth">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</div>
</body>
</html>
