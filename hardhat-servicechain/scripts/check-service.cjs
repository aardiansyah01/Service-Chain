const { ethers } = require("ethers");
const path = require("node:path");

async function main() {
  // Konfigurasi koneksi Hardhat lokal
  const rpcUrl = process.env.SERVICECHAIN_RPC_URL || "http://127.0.0.1:8545";

  const contractAddress =
    process.env.SERVICECHAIN_CONTRACT_ADDRESS ||
    "0x5FbDB2315678afecb367f032d93F642f64180aa3";

  // Ambil Service ID dari argumen terminal
  const serviceId = process.argv[2];
  const vehicleId = process.argv[3];

  if (!serviceId || !/^[1-9]\d*$/.test(serviceId)) {
    throw new Error(
      "Masukkan Service ID. Contoh: node scripts/check-service.cjs 7",
    );
  }

  // Baca ABI smart contract
  const artifact = require(path.resolve(
    __dirname,
    "../artifacts/contracts/ServiceChain.sol/ServiceChain.json",
  ));

  const abi = Array.isArray(artifact) ? artifact : artifact.abi;

  if (!abi) {
    throw new Error("ABI ServiceChain tidak ditemukan.");
  }

  // Hubungkan ke blockchain tanpa signer
  const provider = new ethers.JsonRpcProvider(rpcUrl);
  const contract = new ethers.Contract(contractAddress, abi, provider);

  console.log("======================================");
  console.log("       CEK SERVICE RECORD");
  console.log("======================================");
  console.log("RPC             :", rpcUrl);
  console.log("Contract        :", contractAddress);
  console.log("Service ID      :", serviceId);
  console.log("--------------------------------------");

  // Ambil data service record dari blockchain
  const record = await contract.getServiceRecord(serviceId);

  if (!record.exists) {
    console.log("HASIL: Service record tidak ditemukan.");
    console.log("Data dengan Service ID tersebut belum ada.");
    return;
  }

  // Tampilkan hasil pembacaan blockchain
  const result = {
    source: "Hardhat Blockchain",
    contractAddress,
    serviceId: record.serviceId.toString(),
    vehicleId: record.vehicleId,
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

  console.log("HASIL: SERVICE RECORD DITEMUKAN");
  console.log(JSON.stringify(result, null, 2));

  // Opsional: periksa riwayat servis kendaraan
  if (vehicleId) {
    console.log("--------------------------------------");
    console.log("RIWAYAT SERVIS KENDARAAN");
    console.log("Vehicle ID:", vehicleId);

    const history = await contract.getVehicleServiceHistory(vehicleId);

    console.log(
      JSON.stringify(
        history.map((id) => id.toString()),
        null,
        2,
      ),
    );
  }

  console.log("======================================");
  console.log("Pemeriksaan selesai.");
}

main().catch((error) => {
  console.error("\nGAGAL MEMERIKSA SERVICE RECORD");
  console.error(error.shortMessage || error.message || error);
  process.exitCode = 1;
});
