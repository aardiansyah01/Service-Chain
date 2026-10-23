import { ethers } from "ethers";
import ServiceChainABI from "../abi/ServiceChain.json";

const RPC_URL =
    import.meta.env.VITE_SERVICECHAIN_RPC_URL || "http://127.0.0.1:8545";

const CONTRACT_ADDRESS = import.meta.env.VITE_SERVICECHAIN_CONTRACT_ADDRESS;

function getReadContract() {
    if (!CONTRACT_ADDRESS) {
        throw new Error("Alamat smart contract belum dikonfigurasi.");
    }

    const provider = new ethers.JsonRpcProvider(RPC_URL);

    return new ethers.Contract(CONTRACT_ADDRESS, ServiceChainABI, provider);
}

export async function getVehicleFromBlockchain(vehicleId) {
    if (!vehicleId) {
        throw new Error("ID kendaraan tidak tersedia.");
    }

    const contract = getReadContract();
    const vehicle = await contract.getVehicle(vehicleId);

    if (!vehicle.registered) {
        throw new Error("Kendaraan tidak ditemukan di blockchain.");
    }

    return {
        vehicleId: vehicle.vehicleId,
        owner: vehicle.owner,
        brand: vehicle.brand,
        model: vehicle.model,
        active: vehicle.active,
        registered: vehicle.registered,
        color: vehicle.color,
        year: Number(vehicle.year),
    };
}

export async function getServiceRecordFromBlockchain(serviceId) {
    const id = String(serviceId);

    if (!/^[1-9]\d*$/.test(id)) {
        throw new Error("Service ID tidak valid.");
    }

    const contract = getReadContract();

    const record = await contract.getServiceRecord(id);

    if (!record.exists) {
        throw new Error("Riwayat servis tidak ditemukan di blockchain.");
    }

    const vehicle = await contract.getVehicle(record.vehicleId);

    if (!vehicle.registered) {
        throw new Error("Kendaraan terkait tidak ditemukan di blockchain.");
    }

    return {
        serviceId: record.serviceId.toString(),
        vehicleId: record.vehicleId,

        // Informasi kendaraan
        vehicle: `${vehicle.brand} ${vehicle.model}`.trim(),
        brand: vehicle.brand,
        model: vehicle.model,
        color: vehicle.color,
        year: Number(vehicle.year),
        ownerAddress: vehicle.owner,

        // Informasi servis
        workshop: record.workshop,
        serviceDate: record.serviceDate.toString(),
        mileage: record.mileage.toString(),
        cost: record.cost.toString(),
        serviceType: record.serviceType,
        description: record.description,
        serviceHash: record.serviceHash,
        exists: record.exists,
        cancelled: record.cancelled,
    };
}

export async function getVehicleServiceHistoryFromBlockchain(vehicleId) {
    if (!vehicleId || !String(vehicleId).trim()) {
        throw new Error("ID kendaraan tidak tersedia.");
    }

    const contract = getReadContract();
    const serviceIds = await contract.getVehicleServiceHistory(
        String(vehicleId).trim(),
    );
    return Array.from(serviceIds, (serviceId) => serviceId.toString());
}
