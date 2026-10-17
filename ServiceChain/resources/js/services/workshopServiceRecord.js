import { ethers } from "ethers";
import { getServiceChainWriteContract } from "./serviceChain";

const HARDHAT_CHAIN_ID = "0x7a69";

function showServiceStatus(message, isError = false) {
    const element = document.getElementById("serviceBlockchainStatus");

    if (!element) return;

    element.textContent = message;
    element.style.color = isError ? "#dc2626" : "#2563eb";
}

async function ensureHardhatNetwork() {
    const chainId = await window.ethereum.request({
        method: "eth_chainId",
    });

    if (chainId.toLowerCase() === HARDHAT_CHAIN_ID) {
        return;
    }

    try {
        await window.ethereum.request({
            method: "wallet_switchEthereumChain",
            params: [{ chainId: HARDHAT_CHAIN_ID }],
        });
    } catch (error) {
        if (error.code !== 4902) throw error;

        await window.ethereum.request({
            method: "wallet_addEthereumChain",
            params: [
                {
                    chainId: HARDHAT_CHAIN_ID,
                    chainName: "Hardhat Localhost",
                    nativeCurrency: {
                        name: "Ether",
                        symbol: "ETH",
                        decimals: 18,
                    },
                    rpcUrls: ["http://127.0.0.1:8545"],
                },
            ],
        });

        await window.ethereum.request({
            method: "wallet_switchEthereumChain",
            params: [{ chainId: HARDHAT_CHAIN_ID }],
        });
    }
}

function getFormValue(form, name) {
    return form.elements.namedItem(name)?.value?.trim() || "";
}

async function prepareService(form, walletAddress) {
    const formData = new FormData(form);
    formData.set("wallet_address", walletAddress);

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]',
    )?.content;

    const response = await fetch(form.dataset.prepareUrl, {
        method: "POST",
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": csrfToken || "",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
    });

    const result = await response.json();

    if (!response.ok || result.ready !== true) {
        const message = Object.values(result.errors || {}).flat()[0];

        throw new Error(
            message || result.message || "Validasi data servis gagal.",
        );
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("addServiceForm");

    if (!form) return;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (form.dataset.busy === "true") return;

        if (!window.ethereum) {
            showServiceStatus("MetaMask tidak ditemukan.", true);
            return;
        }

        const savedWallet = (form.dataset.workshopWallet || "").toLowerCase();

        if (!savedWallet) {
            showServiceStatus(
                "Hubungkan wallet bengkel terlebih dahulu.",
                true,
            );
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');

        const originalButtonText = submitButton?.textContent;

        form.dataset.busy = "true";

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Memproses transaksi...";
        }

        try {
            await ensureHardhatNetwork();

            const accounts = await window.ethereum.request({
                method: "eth_requestAccounts",
            });

            if (!accounts.length) {
                throw new Error("Tidak ada wallet MetaMask yang dipilih.");
            }

            const walletAddress = accounts[0];

            if (walletAddress.toLowerCase() !== savedWallet) {
                throw new Error(
                    "Wallet MetaMask tidak sesuai dengan wallet bengkel.",
                );
            }

            const contract = await getServiceChainWriteContract();
            const signerAddress = await contract.runner.getAddress();

            if (signerAddress.toLowerCase() !== savedWallet) {
                throw new Error("Wallet penandatangan tidak cocok.");
            }

            const workshopState = await contract.workshops(walletAddress);

            const registered = Boolean(
                workshopState.registered ?? workshopState[2],
            );

            const status = Number(workshopState.status ?? workshopState[1]);

            if (!registered || status !== 1) {
                throw new Error(
                    "Wallet bengkel belum ACTIVE di smart contract.",
                );
            }

            const vehicleId = getFormValue(form, "vehicle_id");

            if (!vehicleId) {
                throw new Error("Pilih kendaraan yang akan diservis.");
            }

            const vehicle = await contract.getVehicle(vehicleId);

            if (!Boolean(vehicle.registered ?? vehicle[6])) {
                throw new Error("Kendaraan belum terdaftar di blockchain.");
            }

            if (!Boolean(vehicle.active ?? vehicle[5])) {
                throw new Error("Kendaraan tidak aktif di blockchain.");
            }

            const file = form.elements.namedItem("receipt").files?.[0];

            if (!file) {
                throw new Error("Unggah foto nota terlebih dahulu.");
            }

            showServiceStatus("Memvalidasi data dan nota...");
            await prepareService(form, walletAddress);

            const receiptHash = ethers.keccak256(
                new Uint8Array(await file.arrayBuffer()),
            );

            const [year, month, day] = getFormValue(form, "service_date")
                .split("-")
                .map(Number);

            const serviceDate = BigInt(
                Math.floor(Date.UTC(year, month - 1, day) / 1000),
            );

            const mileage = BigInt(getFormValue(form, "mileage"));

            const cost = BigInt(getFormValue(form, "cost"));

            const serviceType = getFormValue(form, "service_type");

            const description = getFormValue(form, "description");

            showServiceStatus(
                "Setujui transaksi addServiceRecord() di MetaMask.",
            );

            const tx = await contract.addServiceRecord(
                vehicleId,
                serviceDate,
                mileage,
                cost,
                serviceType,
                description,
                receiptHash,
            );

            showServiceStatus(
                "Transaksi dikirim. Menunggu konfirmasi blockchain...",
            );

            const receipt = await tx.wait();

            console.log("Receipt logs:", receipt.logs);

            for (const log of receipt.logs) {
                try {
                    const parsed = contract.interface.parseLog({
                        topics: log.topics,
                        data: log.data,
                    });

                    console.log("Parsed event:", parsed?.name);
                    console.log(
                        "Parsed args:",
                        parsed ? Array.from(parsed.args) : null,
                    );
                } catch (error) {
                    console.log("Gagal decode log:", error.message);
                }
            }

            if (!receipt || Number(receipt.status) !== 1) {
                throw new Error("Transaksi pencatatan servis gagal.");
            }

            let serviceId = null;

            const expectedVehicleHash = ethers.keccak256(
                ethers.toUtf8Bytes(vehicleId),
            );

            const contractAddress = (await contract.getAddress()).toLowerCase();

            for (const log of receipt.logs) {
                if (log.address.toLowerCase() !== contractAddress) {
                    continue;
                }

                try {
                    const parsed = contract.interface.parseLog({
                        topics: log.topics,
                        data: log.data,
                    });

                    if (parsed?.name !== "ServiceRecordAdded") {
                        continue;
                    }

                    const eventServiceId = parsed.args[0].toString();
                    const indexedVehicle = parsed.args[1];
                    const eventWorkshop = parsed.args[2];

                    const indexedVehicleHash = indexedVehicle?.hash;

                    if (
                        typeof indexedVehicleHash !== "string" ||
                        indexedVehicleHash.toLowerCase() !==
                            expectedVehicleHash.toLowerCase()
                    ) {
                        continue;
                    }

                    if (
                        eventWorkshop.toLowerCase() !==
                        walletAddress.toLowerCase()
                    ) {
                        continue;
                    }

                    serviceId = eventServiceId;
                    break;
                } catch (error) {
                    console.debug(
                        "Log bukan event ServiceRecordAdded yang sesuai:",
                        error.message,
                    );
                }
            }

            if (!serviceId) {
                console.error("Receipt logs:", receipt.logs);

                throw new Error(
                    "Transaksi berhasil, tetapi event ServiceRecordAdded " +
                        "tidak ditemukan atau argumennya tidak cocok.",
                );
            }

            const record = await contract.getServiceRecord(serviceId);

            if (
                record.vehicleId !== vehicleId ||
                record.workshop.toLowerCase() !== walletAddress.toLowerCase() ||
                record.serviceHash === ethers.ZeroHash
            ) {
                throw new Error("Data servis pada blockchain tidak cocok.");
            }

            form.elements.namedItem("service_id").value = serviceId;
            form.elements.namedItem("wallet_address").value = walletAddress;
            form.elements.namedItem("transaction_hash").value =
                receipt.hash || tx.hash;
            form.elements.namedItem("receipt_hash").value = receiptHash;

            showServiceStatus(
                "Transaksi terkonfirmasi. Menyinkronkan data ke MySQL...",
            );

            form.submit();
        } catch (error) {
            showServiceStatus(
                error.reason ||
                    error.shortMessage ||
                    error.message ||
                    "Pencatatan servis gagal.",
                true,
            );

            form.dataset.busy = "false";

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent =
                    originalButtonText || "Simpan Catatan";
            }
        }
    });
});
