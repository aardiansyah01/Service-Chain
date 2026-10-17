const { ethers } = require("ethers");
const path = require("node:path");

async function main() {
    const rpcUrl = process.env.SERVICECHAIN_RPC_URL;
    const contractAddress = process.env.SERVICECHAIN_CONTRACT_ADDRESS;
    const privateKey = process.env.SERVICECHAIN_ADMIN_PRIVATE_KEY;

    const walletAddress = process.argv[2];
    const expectedName = process.argv[3];

    if (!rpcUrl || !contractAddress || !privateKey) {
        throw new Error("Konfigurasi RPC, kontrak, atau admin belum lengkap.");
    }

    if (!walletAddress || !expectedName) {
        throw new Error("Alamat wallet dan nama bengkel wajib diisi.");
    }

    const artifact = require(
        path.resolve(__dirname, "../resources/js/abi/ServiceChain.json"),
    );

    const abi = Array.isArray(artifact) ? artifact : artifact.abi;

    if (!abi) {
        throw new Error("ABI ServiceChain tidak ditemukan.");
    }

    const provider = new ethers.JsonRpcProvider(rpcUrl);
    const network = await provider.getNetwork();

    if (network.chainId !== 31337n) {
        throw new Error("RPC tidak mengarah ke jaringan Hardhat lokal.");
    }

    const signer = new ethers.Wallet(privateKey, provider);

    const contract = new ethers.Contract(contractAddress, abi, signer);

    const adminAddress = await contract.admin();

    if (adminAddress.toLowerCase() !== signer.address.toLowerCase()) {
        throw new Error("Private key server bukan admin kontrak ServiceChain.");
    }

    const workshop = await contract.workshops(walletAddress);

    const name = workshop.name ?? workshop[0];
    const status = Number(workshop.status ?? workshop[1]);
    const registered = Boolean(workshop.registered ?? workshop[2]);

    if (!registered) {
        throw new Error("Wallet belum terdaftar di smart contract.");
    }

    if (name !== expectedName) {
        throw new Error(
            "Nama bengkel pada blockchain tidak cocok dengan profil MySQL.",
        );
    }

    if (status === 1) {
        console.log(
            JSON.stringify({
                status: "ACTIVE",
                activation_tx_hash: null,
                block_number: null,
                already_active: true,
            }),
        );

        return;
    }

    if (status !== 0) {
        throw new Error(
            "Status blockchain bukan PENDING. Aktivasi otomatis ditolak.",
        );
    }

    const tx = await contract.approveWorkshop(walletAddress);

    const receipt = await tx.wait();

    if (!receipt || Number(receipt.status) !== 1) {
        throw new Error("Transaksi aktivasi workshop gagal.");
    }

    const updated = await contract.workshops(walletAddress);

    const updatedStatus = Number(updated.status ?? updated[1]);

    if (updatedStatus !== 1) {
        throw new Error(
            "Status workshop belum menjadi ACTIVE setelah transaksi.",
        );
    }

    console.log(
        JSON.stringify({
            status: "ACTIVE",
            activation_tx_hash: receipt.hash,
            block_number: Number(receipt.blockNumber),
            already_active: false,
        }),
    );
}

main().catch((error) => {
    console.error(error.message || error);
    process.exitCode = 1;
});
