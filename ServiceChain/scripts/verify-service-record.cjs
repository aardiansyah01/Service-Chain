const { ethers } = require("ethers");
const path = require("node:path");

async function main() {
    const rpcUrl = process.env.SERVICECHAIN_RPC_URL;
    const contractAddress = process.env.SERVICECHAIN_CONTRACT_ADDRESS;
    const transactionHash = process.argv[2];

    if (!rpcUrl || !contractAddress || !transactionHash) {
        throw new Error(
            "RPC, alamat kontrak, dan transaction hash wajib tersedia.",
        );
    }

    const artifact = require(
        path.resolve(__dirname, "../resources/js/abi/ServiceChain.json"),
    );

    const abi = Array.isArray(artifact) ? artifact : artifact.abi;

    if (!abi) {
        throw new Error("ABI ServiceChain tidak ditemukan.");
    }

    const provider = new ethers.JsonRpcProvider(rpcUrl);
    const contract = new ethers.Contract(contractAddress, abi, provider);

    const tx = await provider.getTransaction(transactionHash);
    const receipt = await provider.getTransactionReceipt(transactionHash);

    if (!tx || !receipt) {
        throw new Error("Transaksi atau receipt tidak ditemukan.");
    }

    if (Number(receipt.status) !== 1) {
        throw new Error("Transaksi blockchain gagal.");
    }

    if (!tx.to || tx.to.toLowerCase() !== contractAddress.toLowerCase()) {
        throw new Error("Transaksi bukan menuju kontrak ServiceChain.");
    }

    const parsedTx = contract.interface.parseTransaction({
        data: tx.data,
        value: tx.value,
    });

    if (parsedTx?.name !== "addServiceRecord") {
        throw new Error("Transaksi bukan pemanggilan addServiceRecord().");
    }

    let registeredEvent = null;

    for (const log of receipt.logs) {
        if (log.address.toLowerCase() !== contractAddress.toLowerCase()) {
            continue;
        }

        try {
            const parsed = contract.interface.parseLog({
                topics: log.topics,
                data: log.data,
            });

            if (parsed?.name === "ServiceRecordAdded") {
                registeredEvent = parsed;
                break;
            }
        } catch {}
    }

    if (!registeredEvent) {
        throw new Error("Event ServiceRecordAdded tidak ditemukan.");
    }

    const serviceId = registeredEvent.args.serviceId.toString();
    const indexedVehicle = registeredEvent.args.vehicleId;
    const eventWorkshop = registeredEvent.args.workshop;

    const args = {
        vehicleId: parsedTx.args[0],
        serviceDate: parsedTx.args[1].toString(),
        mileage: parsedTx.args[2].toString(),
        cost: parsedTx.args[3].toString(),
        serviceType: parsedTx.args[4],
        description: parsedTx.args[5],
        receiptHash: parsedTx.args[6],
    };

    const expectedVehicleHash = ethers.keccak256(
        ethers.toUtf8Bytes(args.vehicleId),
    );

    if (
        typeof indexedVehicle?.hash !== "string" ||
        indexedVehicle.hash.toLowerCase() !==
            expectedVehicleHash.toLowerCase() ||
        eventWorkshop.toLowerCase() !== tx.from.toLowerCase()
    ) {
        throw new Error("Data event tidak cocok dengan transaksi.");
    }

    const record = await contract.getServiceRecord(serviceId);

    if (!record.exists) {
        throw new Error("Catatan servis tidak ditemukan di blockchain.");
    }

    if (
        record.vehicleId !== args.vehicleId ||
        record.workshop.toLowerCase() !== tx.from.toLowerCase() ||
        record.serviceDate.toString() !== args.serviceDate ||
        record.mileage.toString() !== args.mileage ||
        record.cost.toString() !== args.cost ||
        record.serviceType !== args.serviceType ||
        record.description !== args.description
    ) {
        throw new Error(
            "Data pada state kontrak tidak cocok dengan input transaksi.",
        );
    }

    const workshopState = await contract.workshops(tx.from);

    if (!workshopState.registered || Number(workshopState.status) !== 1) {
        throw new Error("Wallet pengirim bukan bengkel yang berstatus ACTIVE.");
    }

    console.log(
        JSON.stringify({
            success: true,
            transactionHash: receipt.hash,
            blockNumber: Number(receipt.blockNumber),
            contractAddress,
            from: tx.from,
            serviceId,
            args: {
                ...args,
                receiptHash: args.receiptHash,
            },
            record: {
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
            },
        }),
    );
}

main().catch((error) => {
    console.error(error.message || error);
    process.exitCode = 1;
});
