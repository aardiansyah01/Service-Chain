
@extends('admin.layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')
@section('page-subtitle', 'Pantau dan kelola aktivitas platform.')

@section('content')
<div class="users-page">

    <div class="users-heading">
        <h1>Daftar pengguna terdaftar</h1>
        <p>Cari, filter, dan tinjau data yang tersedia pada platform.</p>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="users-toolbar">
        <div class="users-search">
            <i class="bi bi-search"></i>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari berdasarkan nama atau email..."
                aria-label="Cari pengguna"
            >
        </div>

        <select name="status" class="users-select" aria-label="Filter status"
            onchange="this.form.submit()">
            <option value="all" @selected($status === 'all')>Semua status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>

        <select name="sort" class="users-select sort-select" aria-label="Urutkan pengguna"
            onchange="this.form.submit()">
            <option value="newest" @selected($sort === 'newest')>Terbaru</option>
            <option value="oldest" @selected($sort === 'oldest')>Terlama</option>
            <option value="name-asc" @selected($sort === 'name-asc')>Nama A-Z</option>
            <option value="name-desc" @selected($sort === 'name-desc')>Nama Z-A</option>
        </select>

        <button type="submit" class="users-filter-submit">Cari</button>
    </form>

    <div class="users-table-card">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>PENGGUNA</th>
                        <th>TIPE AKUN</th>
                        <th>WALLET</th>
                        <th>TANGGAL DAFTAR</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                
                <tbody id="usersTableBody">
                    @forelse ($users as $user)
                        <tr class="user-row">
                            <td>
                                <div class="user-name">{{ $user->name }}</div>
                                <div class="user-email">{{ $user->email }}</div>
                            </td>

                            <td>
                                <span class="account-type">
                                    @if ($user->role === 'workshop')
                                        Bengkel
                                    @else
                                        Pemilik Kendaraan
                                    @endif
                                </span>
                            </td>

                            <td>
                                <span class="wallet-address">
                                    {{ $user->workshop?->wallet_address ?: '—' }}
                                </span>
                            </td>

                            <td>
                                <span class="registration-date">
                                    {{ $user->created_at?->format('d M Y') ?? '—' }}
                                </span>
                            </td>

                            <td>
                                @if ($user->status === 'inactive')
                                    <span class="user-status inactive">
                                        <i class="bi bi-circle-fill"></i>
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="user-status active">
                                        <i class="bi bi-circle-fill"></i>
                                        Aktif
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if ($user->status !== 'inactive')
                                    <form
                                        action="{{ route('admin.users.deactivate', $user) }}"
                                        method="POST"
                                        onsubmit="return confirm('Apakah anda yakin ingin menonaktifkan akun {{ addslashes($user->name) }}? Pengguna ini tidak akan bisa melakukan login lagi.');"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="user-deactivate-btn"
                                            title="Nonaktifkan akun"
                                            aria-label="Nonaktifkan akun {{ $user->name }}"
                                        >
                                            <i class="bi bi-person-slash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="user-action-disabled" title="Akun sudah nonaktif">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="users-empty">
                                <i class="bi bi-search"></i>
                                <p>Pengguna tidak ditemukan.</p>
                                <small>Coba kata kunci atau filter yang berbeda.</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="users-pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
