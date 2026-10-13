<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        $totalVehicles = DB::table('vehicles')
            ->where('owner_id', $user->id)
            ->count();

        $totalServices = DB::table('services')
            ->join('vehicles', 'services.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.owner_id', $user->id)
            ->count();

        $activeVehicles = DB::table('vehicles')
            ->where('owner_id', $user->id)
            ->where('status', 'ACTIVE')
            ->count();

        $vehicles = DB::table('vehicles')
            ->where('owner_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        $recentServices = DB::table('services')
            ->join('vehicles', 'services.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.owner_id', $user->id)
            ->select(
                'services.service_id',
                'services.service_type',
                'services.service_date',
                'services.workshop_name',
                'vehicles.vehicle_id as vehicle_code',
                'vehicles.brand',
                'vehicles.model'
            )
            ->orderByDesc('services.service_date')
            ->orderByDesc('services.id')
            ->limit(3)
            ->get();

        return view('owner.dashboard', compact(
            'totalVehicles',
            'totalServices',
            'activeVehicles',
            'vehicles',
            'recentServices'
        ));
    }

    public function vehicles(Request $request)
    {
        $search = trim($request->query('search', ''));
        $status = $request->query('status', 'all');
        $sort = $request->query('sort', 'newest');

        $query = Auth::user()->vehicles();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_id', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('license_plate', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $query->where('status', $status);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('registered_at', 'asc');
                break;

            case 'name-asc':
                $query->orderBy('brand')->orderBy('model');
                break;

            case 'name-desc':
                $query->orderBy('brand', 'desc')->orderBy('model', 'desc');
                break;

            default:
                $query->orderBy('registered_at', 'desc');
                break;
        }

        $vehicles = $query->paginate(9)->withQueryString();

        return view('owner.vehicles', compact(
            'vehicles',
            'search',
            'status',
            'sort'
        ));
    }
    
    public function storeVehicle(Request $request)
    {
        $data = $request->validate([
            'vehicle_type' => ['required', 'string', 'max:50'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => [
                'required',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 1),
            ],
            'license_plate' => [
                'required',
                'string',
                'max:20',
                'unique:vehicles,license_plate',
            ],
            'vin' => [
                'required',
                'string',
                'max:100',
                'unique:vehicles,vin',
            ],
            'color' => ['required', 'string', 'max:50'],
        ]);

        Auth::user()->vehicles()->create([
            'vehicle_id' => 'SC-VHC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'owner_address' => null,
            'vehicle_type' => $data['vehicle_type'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'license_plate' => $data['license_plate'],
            'vin' => $data['vin'],
            'color' => $data['color'],
            'registered_at' => now(),
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('owner.vehicles')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    // History
    public function history(Request $request)
    {
        $search = trim($request->query('search', ''));
        $sort = $request->query('sort', 'newest');

        $query = Service::with(['vehicle.owner', 'workshop'])
            ->whereHas('vehicle', function ($vehicleQuery) {
                $vehicleQuery->where('owner_id', Auth::id());
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('service_id', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery->where('vehicle_id', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('service_date', 'asc');
                break;
            case 'name-asc':
                $query->join('vehicles', 'services.vehicle_id', '=', 'vehicles.id')
                    ->orderBy('vehicles.brand')
                    ->orderBy('vehicles.model')
                    ->select('services.*');
                break;
            case 'name-desc':
                $query->join('vehicles', 'services.vehicle_id', '=', 'vehicles.id')
                    ->orderByDesc('vehicles.brand')
                    ->orderByDesc('vehicles.model')
                    ->select('services.*');
                break;
            default:
                $query->orderByDesc('service_date');
                break;
        }

        $services = $query->paginate(10)->withQueryString();

        return view('owner.history', compact('services', 'search', 'sort'));
    }
    
    public function profile()
    {
        $user = Auth::user();

        return view('owner.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        if ($data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return redirect()
            ->route('owner.profile')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
