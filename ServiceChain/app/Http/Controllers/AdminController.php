<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $search = trim($request->query('search', ''));
        $status = $request->query('status', 'all');
        $sort = $request->query('sort', 'newest');

        $query = User::with('workshop')
            ->whereIn('role', ['owner', 'workshop']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('wallet_address', 'like', "%{$search}%")
                    ->orWhereHas('workshop', function ($workshopQuery) use ($search) {
                        $workshopQuery->where(
                            'wallet_address',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;

            case 'name-asc':
                $query->orderBy('name');
                break;

            case 'name-desc':
                $query->orderByDesc('name');
                break;

            default:
                $query->orderByDesc('created_at');
                break;
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users', compact(
            'users',
            'search',
            'status',
            'sort'
        ));
    }

    public function deactivateUser(User $user)
    {
        if (!in_array($user->role, ['owner', 'workshop'], true)) {
            abort(403, 'Akun ini tidak dapat dinonaktifkan melalui fitur ini.');
        }

        $user->update([
            'status' => 'inactive',
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Akun pengguna berhasil dinonaktifkan.');
    }

    public function workshops(Request $request)
    {
        $workshops = Workshop::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.workshops', compact('workshops'));
    }

    public function approveWorkshop(Workshop $workshop)
    {
        if ($workshop->status !== 'PENDING') {
            return back()->with(
                'error',
                'Pengajuan bengkel ini sudah diproses.'
            );
        }

        $workshop->update([
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('admin.workshops.index')
            ->with('success', 'Bengkel berhasil disetujui.');
    }

    public function rejectWorkshop(Workshop $workshop)
    {
        if ($workshop->status !== 'PENDING') {
            return back()->with(
                'error',
                'Pengajuan bengkel ini sudah diproses.'
            );
        }

        $workshop->update([
            'status' => 'REJECTED',
        ]);

        return redirect()
            ->route('admin.workshops.index')
            ->with('success', 'Pengajuan bengkel berhasil ditolak.');
    }

    public function deactivateWorkshop(Workshop $workshop)
    {
        if ($workshop->status !== 'ACTIVE') {
            return back()->with(
                'error',
                'Hanya bengkel aktif yang dapat dinonaktifkan.'
            );
        }

        $workshop->update([
            'status' => 'INACTIVE',
        ]);

        return redirect()
            ->route('admin.workshops.index')
            ->with('success', 'Bengkel berhasil dinonaktifkan.');
    }

    public function vehiclesServices(Request $request)
    {
        $search = trim($request->query('search', ''));
        $serviceType = $request->query('service_type', 'all');
        $sort = $request->query('sort', 'newest');

        $query = Service::with([
            'vehicle.owner',
            'workshop',
            'owner',
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('service_id', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('workshop_name', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery
                            ->where('vehicle_id', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                                $ownerQuery->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            });
                    })
                    ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                        $ownerQuery->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        if ($serviceType !== 'all') {
            $query->where('service_type', $serviceType);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('service_date');
                break;

            case 'name-asc':
                $query->join(
                    'vehicles',
                    'service_records.vehicle_id',
                    '=',
                    'vehicles.vehicle_id'
                )
                    ->orderBy('vehicles.brand')
                    ->orderBy('vehicles.model')
                    ->select('service_records.*');
                break;

            case 'name-desc':
                $query->join(
                    'vehicles',
                    'service_records.vehicle_id',
                    '=',
                    'vehicles.vehicle_id'
                )
                    ->orderByDesc('vehicles.brand')
                    ->orderByDesc('vehicles.model')
                    ->select('service_records.*');
                break;

            default:
                $query->orderByDesc('service_date');
                break;
        }

        $services = $query->paginate(10)->withQueryString();

        $serviceTypes = Service::query()
            ->select('service_type')
            ->distinct()
            ->orderBy('service_type')
            ->pluck('service_type');

        return view('admin.vehicles-services', compact(
            'services',
            'search',
            'serviceType',
            'serviceTypes',
            'sort'
        ));
    }

    public function dashboard()
    {
        $totalUsers = User::whereIn('role', ['owner', 'workshop'])->count();

        $totalVehicles = Vehicle::count();

        $activeWorkshops = Workshop::where('status', 'ACTIVE')->count();

        $pendingWorkshops = Workshop::with('user')
            ->where('status', 'PENDING')
            ->latest()
            ->take(5)
            ->get();

        $pendingWorkshopCount = Workshop::where('status', 'PENDING')->count();

        $totalServices = Service::count();

        $recentActivities = collect();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalVehicles',
            'activeWorkshops',
            'pendingWorkshops',
            'pendingWorkshopCount',
            'totalServices',
            'recentActivities'
        ));
    }
}