<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitorVerificationController extends Controller
{

    public function index(): View
    {
        return view('visitor.verification');
    }

    public function metadata(Request $request, string $vehicleId): JsonResponse
    {
        $ownerWallet = strtolower(trim((string) $request->query('owner_wallet', '')));

        if (!preg_match('/^0x[a-f0-9]{40}$/', $ownerWallet)) {
            return response()->json([
                'message' => 'Alamat wallet owner tidak valid.',
            ], 422);
        }

        $owner = User::query()
            ->whereRaw('LOWER(wallet_address) = ?', [$ownerWallet])
            ->first(['id', 'name', 'wallet_address']);

        $serviceMetadata = Service::query()
            ->with(['workshop:id,workshop_name,address,city'])
            ->where('vehicle_id', $vehicleId)
            ->get(['id', 'service_id', 'vehicle_id', 'workshop_id'])
            ->map(function (Service $service): array {
                $workshop = $service->workshop;
                $addressParts = array_filter([
                    trim((string) ($workshop?->address ?? '')),
                    trim((string) ($workshop?->city ?? '')),
                ], static fn (string $part): bool => $part !== '');

                return [
                    'serviceId' => (string) $service->service_id,
                    'workshopName' => $workshop?->workshop_name,
                    'workshopAddress' => $addressParts
                        ? implode(', ', $addressParts)
                        : null,
                ];
            })
            ->values();

        return response()->json([
            'owner' => $owner ? [
                'id' => (string) $owner->id,
                'name' => $owner->name,
                'walletAddress' => $owner->wallet_address,
            ] : null,
            'services' => $serviceMetadata,
        ]);
    }
}