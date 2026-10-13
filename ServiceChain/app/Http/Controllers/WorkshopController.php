<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkshopController extends Controller
{
    private function currentWorkshop()
    {
        $workshop = Auth::user()->workshop;

        abort_unless($workshop, 403, 'Data bengkel tidak ditemukan.');

        return $workshop;
    }

    public function records(Request $request)
    {
        $workshop = $this->currentWorkshop();

        $search = trim($request->query('search', ''));
        $sort = $request->query('sort', 'newest');

        $query = Service::with('vehicle.owner')
            ->where('workshop_id', $workshop->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('service_id', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery
                            ->where('vehicle_id', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('service_date', 'asc');
        } elseif ($sort === 'name') {
            $query->orderBy(
                Vehicle::select('brand')
                    ->whereColumn('vehicles.id', 'services.vehicle_id')
            );
        } else {
            $query->orderBy('service_date', 'desc');
        }

        $services = $query->paginate(10)->withQueryString();

        $vehicles = Vehicle::with('owner')
            ->where('status', 'ACTIVE')
            ->orderBy('brand')
            ->orderBy('model')
            ->get();

        return view('workshop.records', compact(
            'services',
            'vehicles',
            'search',
            'sort'
        ));
    }

    public function storeRecord(Request $request)
    {
        $workshop = $this->currentWorkshop();

        $data = $request->validate([
            'vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
            ],
            'service_type' => [
                'required',
                'string',
                'max:100',
            ],
            'service_date' => [
                'required',
                'date',
            ],
            'mileage' => [
                'required',
                'integer',
                'min:0',
            ],
            'cost' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],
            'description' => [
                'required',
                'string',
                'max:10000',
            ],
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        abort_unless(
            $vehicle->status === 'ACTIVE',
            422,
            'Kendaraan yang dipilih tidak aktif.'
        );

        do {
            $serviceId = 'SC-SRV-' . now()->format('Ymd') . '-'
                . Str::upper(Str::random(6));
        } while (Service::where('service_id', $serviceId)->exists());

        Service::create([
            'service_id' => $serviceId,
            'vehicle_id' => $vehicle->id,
            'workshop_id' => $workshop->id,
            'workshop_name' => $workshop->workshop_name,
            'workshop_address' => trim(
                $workshop->address . ', ' . $workshop->city,
                ', '
            ),
            'service_type' => $data['service_type'],
            'service_date' => $data['service_date'],
            'mileage' => $data['mileage'],
            'cost' => $data['cost'],
            'description' => $data['description'],
        ]);

        return redirect()
            ->route('workshop.records.index')
            ->with('success', 'Catatan servis berhasil disimpan.');
    }

    public function showRecord(Service $service)
    {
        $workshop = $this->currentWorkshop();

        abort_unless(
            $service->workshop_id === $workshop->id,
            404
        );

        $service->load('vehicle.owner');

        return view('workshop.record-detail', compact('service'));
    }

    // Profile
    public function profile()
    {
        $workshop = $this->currentWorkshop();

        return view('workshop.profile', compact('workshop'));
    }

    public function updateProfile(Request $request)
    {
        $workshop = $this->currentWorkshop();
        $user = Auth::user();

        $data = $request->validate([
            'workshop_name' => [
                'required',
                'string',
                'max:150',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $workshop->update([
            'workshop_name' => $data['workshop_name'],
        ]);

        $user->update([
            'email' => $data['email'],
        ]);

        return redirect()
            ->route('workshop.profile')
            ->with('success', 'Profil bengkel berhasil diperbarui.');
    }

    // Dashboard
    public function dashboard()
    {
        $workshop = $this->currentWorkshop();

        $servicesQuery = Service::where('workshop_id', $workshop->id);

        $totalServices = (clone $servicesQuery)->count();

        $servicesThisMonth = (clone $servicesQuery)
            ->whereMonth('service_date', now()->month)
            ->whereYear('service_date', now()->year)
            ->count();

        $vehiclesServed = (clone $servicesQuery)
            ->distinct('vehicle_id')
            ->count('vehicle_id');

        $vehiclesThisMonth = (clone $servicesQuery)
            ->whereMonth('service_date', now()->month)
            ->whereYear('service_date', now()->year)
            ->distinct('vehicle_id')
            ->count('vehicle_id');

        $latestService = (clone $servicesQuery)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->first();

        $recentServices = Service::with('vehicle')
            ->where('workshop_id', $workshop->id)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('workshop.dashboard', compact(
            'workshop',
            'totalServices',
            'servicesThisMonth',
            'vehiclesServed',
            'vehiclesThisMonth',
            'latestService',
            'recentServices'
        ));
    }
}
