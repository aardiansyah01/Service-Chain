<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showRegister(Request $request)
    {
        $role = $request->query('role', 'owner');

        if (!in_array($role, ['owner', 'workshop'], true)) {
            $role = 'owner';
        }

        return view('auth.register', compact('role'));
    }

    public function register(Request $request)
    {
        $role = $request->input('role');

        if (!in_array($role, ['owner', 'workshop'], true)) {
            return back()
                ->withErrors([
                    'role' => 'Pilihan jenis akun tidak valid.',
                ])
                ->withInput();
        }

        $rules = [
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($role === 'owner') {
            $rules['name'] = 'required|string|max:255';
        } else {
            $rules += [
                'workshop_name' => 'required|string|max:255',
                'owner_name' => 'required|string|max:255',
                'address' => 'required|string|max:2000',
                'city' => 'required|string|max:100',
                'phone' => 'required|string|max:30',
                'license_number' => 'required|string|max:100|unique:workshops,license_number',
            ];
        }

        $data = $request->validate($rules);

        $user = DB::transaction(function () use ($role, $data) {
            $user = User::create([
                'name' => $role === 'owner'
                    ? $data['name']
                    : $data['owner_name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'workshop_status' => $role === 'workshop'
                    ? 'pending'
                    : null,
            ]);

            if ($role === 'workshop') {
                Workshop::create([
                    'user_id' => $user->id,
                    'workshop_name' => $data['workshop_name'],
                    'owner_name' => $data['owner_name'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'phone' => $data['phone'],
                    'license_number' => $data['license_number'],
                    'status' => 'PENDING',
                ]);
            }

            return $user;
        });

        if ($role === 'workshop') {
            return redirect()
                ->route('login')
                ->with('success', 'Registrasi bengkel berhasil. Silakan login setelah akun Anda mendapatkan persetujuan dari admin.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return back()
                ->withErrors([
                    'email' => 'Email atau password salah.',
                ])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()
                ->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
                ])
                ->onlyInput('email');
        }

        if ($user->role === 'workshop') {
            $workshopStatus = strtoupper($user->workshop?->status ?? '');

            if ($workshopStatus === 'PENDING') {
                Auth::logout();

                return back()
                    ->withErrors([
                        'email' => 'Akun bengkel Anda sedang dalam peninjauan oleh admin, silakan tunggu notifikasi dari kami.',
                    ])
                    ->onlyInput('email');
            }

            if ($workshopStatus !== 'ACTIVE') {
                Auth::logout();

                $message = match ($workshopStatus) {
                    'REJECTED' => 'Pengajuan bengkel Anda ditolak oleh admin. Silakan hubungi administrator untuk informasi lebih lanjut.',
                    'INACTIVE' => 'Akun bengkel Anda sedang dinonaktifkan. Silakan hubungi administrator.',
                    default => 'Status akun bengkel Anda belum valid. Silakan hubungi administrator.',
                };

                return back()
                    ->withErrors(['email' => $message])
                    ->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    
    private function redirectByRole(User $user)
    {
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),

            'workshop' => strtoupper($user->workshop?->status ?? '') === 'ACTIVE'
                ? redirect()->route('workshop.dashboard')
                : redirect('/workshop/status'),

            'owner' => redirect()->route('owner.dashboard'),

            default => abort(403, 'Role akun tidak valid.'),
        };
    }

}
