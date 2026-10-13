<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\WorkshopController;
use App\Models\Vehicle;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('home');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('owner')
    ->name('owner.')
    ->middleware(['auth', 'role:owner'])
    ->group(function () {
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/vehicles', [OwnerController::class, 'vehicles'])
            ->name('vehicles');

        Route::post('/vehicles', [OwnerController::class, 'storeVehicle'])
            ->name('vehicles.store');

        Route::get('/history', [OwnerController::class, 'history'])
            ->name('history');

        Route::get('/profile', [OwnerController::class, 'profile'])
            ->name('profile');

        Route::put('/profile', [OwnerController::class, 'updateProfile'])
            ->name('profile.update');
    });


Route::prefix('workshop')
    ->name('workshop.')
    ->middleware(['auth', 'role:workshop'])
    ->group(function () {
        Route::get('/dashboard', [WorkshopController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/records', [WorkshopController::class, 'records'])
            ->name('records.index');

        Route::post('/records', [WorkshopController::class, 'storeRecord'])
            ->name('records.store');

        Route::get('/records/{service}', [WorkshopController::class, 'showRecord'])
            ->name('records.show');

        Route::get('/profile', [WorkshopController::class, 'profile'])
            ->name('profile');

        Route::put('/profile', [WorkshopController::class, 'updateProfile'])
            ->name('profile.update');

        // Search
        Route::get('/vehicles/search', function (Request $request) {
            $keyword = trim($request->query('q', ''));

            if (mb_strlen($keyword) < 2) {
                return response()->json([]);
            }

            $vehicles = Vehicle::query()
                ->with('owner:id,name')
                ->where('status', 'ACTIVE')
                ->where(function ($query) use ($keyword) {
                    $query->where('vehicle_id', 'like', "%{$keyword}%")
                        ->orWhere('brand', 'like', "%{$keyword}%")
                        ->orWhere('model', 'like', "%{$keyword}%")
                        ->orWhereHas('owner', function ($ownerQuery) use ($keyword) {
                            $ownerQuery->where('name', 'like', "%{$keyword}%");
                        });
                })
                ->orderBy('brand')
                ->limit(10)
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'vehicle_id' => $vehicle->vehicle_id,
                        'vehicle_name' => $vehicle->brand . ' ' . $vehicle->model,
                        'vehicle_type' => $vehicle->vehicle_type,
                        'year' => $vehicle->year,
                        'owner_name' => $vehicle->owner?->name ?? '-',
                    ];
                });

            return response()->json($vehicles);
        })->name('vehicles.search');
    });


Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('/users', [AdminController::class, 'users'])
            ->name('users.index');

        Route::post('/users/{user}/deactivate', [
            AdminController::class,
            'deactivateUser'
        ])->name('users.deactivate');

        Route::get('/workshops', [AdminController::class, 'workshops'])
            ->name('workshops.index');

        Route::post('/workshops/{workshop}/approve', [AdminController::class, 'approveWorkshop'])
            ->name('workshops.approve');

        Route::post('/workshops/{workshop}/reject', [AdminController::class, 'rejectWorkshop'])
            ->name('workshops.reject');

        Route::post('/workshops/{workshop}/deactivate', [AdminController::class, 'deactivateWorkshop'])
            ->name('workshops.deactivate');

        Route::get('/vehicles-services', [AdminController::class, 'vehiclesServices'])
            ->name('vehicles-services.index');
    });


