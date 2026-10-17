const { ethers } = require("ethers");

const artifact = require("./artifacts/contracts/ServiceChain.sol/ServiceChain.json");

const RPC_URL = "http://127.0.0.1:8545";

const CONTRACT_ADDRESS = "0x5FbDB2315678afecb367f032d93F642f64180aa3";

const VEHICLE_ID = "SC-VHC-20261016-UKK2RTQK";

async function main() {
  const provider = new ethers.JsonRpcProvider(RPC_URL);

  const network = await provider.getNetwork();

  console.log("Chain ID:", network.chainId.toString());

  const code = await provider.getCode(CONTRACT_ADDRESS);

  if (code === "0x") {
    throw new Error("Kontrak tidak ditemukan pada alamat tersebut.");
  }

  const contract = new ethers.Contract(
    CONTRACT_ADDRESS,
    artifact.abi,
    provider,
  );

  const result = await contract.getVehicle(VEHICLE_ID);

  // Mendukung hasil berupa struct maupun tuple biasa.
  const vehicle =
    result.vehicleId !== undefined
      ? result
      : result[0]?.vehicleId !== undefined
      ? result[0]
      : result;

  console.log("\n=== DATA KENDARAAN BLOCKCHAIN ===");

  console.log({
    vehicleId: vehicle.vehicleId ?? vehicle[0],
    owner: vehicle.owner ?? vehicle[1],
    brand: vehicle.brand ?? vehicle[2],
    model: vehicle.model ?? vehicle[3],
    licensePlate: vehicle.licensePlate ?? vehicle[4],
    active: vehicle.active ?? vehicle[5],
    registered: vehicle.registered ?? vehicle[6],
    color: vehicle.color ?? vehicle[7],
    year: String(vehicle.year ?? vehicle[8]),
  });
}

main().catch((error) => {
  console.error("Gagal membaca data blockchain:");
  console.error(error);
  process.exitCode = 1;
});
