import "./bootstrap";
import { ethers } from "ethers";

import { getServiceChainWriteContract } from "./services/serviceChain";
import {
    getVehicleFromBlockchain,
    getServiceRecordFromBlockchain,
} from "./services/serviceChainRead";

import "./services/workshopWallet";
import "./services/workshopServiceRecord";
import "./services/adminWallet";
import "./services/adminServiceHistory";
import "./services/workshopServiceHistory";
import "./services/databaseCleanUpChainUi";
import "./services/visitorVerification";
import "./services/workshopCancelServiceRecord";

console.log("ServiceChain app.js berhasil dimuat");

let connectedWallet = null;
let walletConnectionConfirmed = false;

const HARDHAT_CHAIN_ID = "0x7a69";

function shortenAddress(address) {
    if (!address) return "Belum terhubung";

    return `${address.slice(0, 6)}...${address.slice(-4)}`;
}

function updateWalletUI(address) {
    connectedWallet = address || null;

    const walletAddressElement = document.getElementById("wallet-address");

    const connectButton = document.getElementById("connect-wallet-btn");

    if (walletAddressElement) {
        walletAddressElement.textContent = shortenAddress(connectedWallet);

        walletAddressElement.title = connectedWallet || "";
    }

    if (connectButton) {
        connectButton.textContent = connectedWallet
            ? "Wallet Terhubung"
            : "Hubungkan Wallet";
    }
}

async function ensureHardhatNetwork() {
    if (!window.ethereum) {
        throw new Error("MetaMask tidak ditemukan. Silakan pasang MetaMask.");
    }

    const currentChainId = await window.ethereum.request({
        method: "eth_chainId",
    });

    if (
        currentChainId &&
        currentChainId.toLowerCase() === HARDHAT_CHAIN_ID.toLowerCase()
    ) {
        return;
    }

    try {
        await window.ethereum.request({
            method: "wallet_switchEthereumChain",
            params: [
                {
                    chainId: HARDHAT_CHAIN_ID,
                },
            ],
        });
    } catch (error) {
        if (error.code !== 4902) {
            throw error;
        }

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
            params: [
                {
                    chainId: HARDHAT_CHAIN_ID,
                },
            ],
        });
    }
}

async function connectWallet() {
    if (!window.ethereum) {
        throw new Error("MetaMask tidak ditemukan. Silakan pasang MetaMask.");
    }

    await window.ethereum.request({
        method: "wallet_requestPermissions",
        params: [{ eth_accounts: {} }],
    });

    let accounts = await window.ethereum.request({
        method: "eth_accounts",
    });

    if (!accounts || accounts.length === 0) {
        throw new Error("Tidak ada akun MetaMask yang dipilih.");
    }

    await ensureHardhatNetwork();

    accounts = await window.ethereum.request({
        method: "eth_accounts",
    });

    if (!accounts || accounts.length === 0) {
        throw new Error("Akun MetaMask tidak tersedia.");
    }

    walletConnectionConfirmed = true;

    updateWalletUI(accounts[0]);

    return accounts[0];
}

function disconnectWalletUI() {
    updateWalletUI(null);
}

function showVehicleStatus(message, isError = false) {
    const element = document.getElementById("vehicle-blockchain-status");

    if (!element) return;

    element.textContent = message;
    element.style.color = isError ? "#dc2626" : "#2563eb";
}

function firstValidationError(data) {
    const errors = data?.errors || {};
    const messages = Object.values(errors).flat();

    return messages[0] || data?.message || "Terjadi kesalahan.";
}

async function prepareVehicle(form, walletAddress) {
    const formData = new FormData(form);

    formData.set("wallet_address", walletAddress);

    const response = await fetch(form.dataset.prepareUrl, {
        method: "POST",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
    });

    const result = await response.json();

    if (!response.ok) {
        throw new Error(firstValidationError(result));
    }

    if (!result.vehicle_id) {
        throw new Error("Laravel tidak mengembalikan ID kendaraan.");
    }

    return result.vehicle_id;
}

function getConfirmedVehicleEvent(
    receipt,
    vehicleId,
    walletAddress,
    contractAddress,
) {
    const eventTopic = ethers
        .id("VehicleRegistered(string,address)")
        .toLowerCase();

    const vehicleIdHash = ethers
        .keccak256(ethers.toUtf8Bytes(vehicleId))
        .toLowerCase();

    const ownerTopic = ethers.zeroPadValue(walletAddress, 32).toLowerCase();

    const abiCoder = ethers.AbiCoder.defaultAbiCoder();

    return receipt.logs.some((log) => {
        if (log.address.toLowerCase() !== contractAddress.toLowerCase()) {
            return false;
        }

        const topics = (log.topics || []).map((topic) => topic.toLowerCase());

        if (topics[0] !== eventTopic) {
            return false;
        }

        if (topics.length >= 3) {
            return topics[1] === vehicleIdHash && topics[2] === ownerTopic;
        }

        if (topics.length === 1) {
            try {
                const decoded = abiCoder.decode(
                    ["string", "address"],
                    log.data,
                );

                return (
                    decoded[0] === vehicleId &&
                    decoded[1].toLowerCase() === walletAddress.toLowerCase()
                );
            } catch {
                return false;
            }
        }

        if (topics.length === 2) {
            if (topics[1] === vehicleIdHash) {
                try {
                    const decoded = abiCoder.decode(["address"], log.data);

                    return (
                        decoded[0].toLowerCase() === walletAddress.toLowerCase()
                    );
                } catch {
                    return false;
                }
            }

            if (topics[1] === ownerTopic) {
                try {
                    const decoded = abiCoder.decode(["string"], log.data);

                    return decoded[0] === vehicleId;
                } catch {
                    return false;
                }
            }
        }

        return false;
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const connectButton = document.getElementById("connect-wallet-btn");

    walletConnectionConfirmed = false;
    updateWalletUI(null);

    if (connectButton) {
        connectButton.addEventListener("click", async () => {
            connectButton.disabled = true;

            try {
                await connectWallet();
            } catch (error) {
                walletConnectionConfirmed = false;
                updateWalletUI(null);

                console.error("Gagal menghubungkan wallet:", error);

                window.alert(error.message || "Gagal menghubungkan wallet.");
            } finally {
                connectButton.disabled = false;
            }
        });
    }

    if (window.ethereum) {
        window.ethereum.on?.("accountsChanged", (accounts) => {
            if (!walletConnectionConfirmed) {
                updateWalletUI(null);
                return;
            }

            if (accounts && accounts.length > 0) {
                updateWalletUI(accounts[0]);
            } else {
                walletConnectionConfirmed = false;
                updateWalletUI(null);
            }
        });

        window.ethereum.on?.("chainChanged", (chainId) => {
            if (chainId.toLowerCase() !== HARDHAT_CHAIN_ID.toLowerCase()) {
                walletConnectionConfirmed = false;
                updateWalletUI(null);

                window.alert(
                    "Network berubah. Silakan hubungkan kembali menggunakan Hardhat Localhost.",
                );

                return;
            }

            if (!walletConnectionConfirmed) {
                updateWalletUI(null);
                return;
            }

            window.ethereum
                .request({
                    method: "eth_accounts",
                })
                .then((accounts) => {
                    updateWalletUI(accounts?.[0] || null);
                })
                .catch(() => {
                    walletConnectionConfirmed = false;
                    updateWalletUI(null);
                });
        });
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("vehicleDetailModal");

    if (!modal) return;

    const closeButtons = [
        document.getElementById("closeVehicleDetail"),
        document.getElementById("closeVehicleDetailFooter"),
    ].filter(Boolean);

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent =
                value !== null && value !== undefined && value !== ""
                    ? String(value)
                    : "-";
        }
    }

    const ownerServiceRows = document.querySelectorAll(".js-service-row");
    const detailStatusBanner = document.getElementById(
        "detailServiceStatusBanner",
    );

    async function refreshOwnerServiceStatus(row) {
        const serviceId = String(row.dataset.serviceId || "").trim();
        const badge = row.querySelector(".js-chain-service-status");

        if (!badge) return;

        if (!/^[1-9]\d*$/.test(serviceId)) {
            badge.textContent = "Status tidak tersedia di blockchain";
            badge.className =
                "history-service-status js-chain-service-status is-unavailable";
            return;
        }

        badge.textContent = "Memeriksa status...";
        badge.className =
            "history-service-status js-chain-service-status is-checking";

        try {
            const record = await getServiceRecordFromBlockchain(serviceId);
            const cancelled = Boolean(record.cancelled);

            badge.textContent = cancelled ? "Dibatalkan" : "Tersimpan";
            badge.className =
                "history-service-status js-chain-service-status " +
                (cancelled ? "is-cancelled" : "is-active");

            row.dataset.serviceCancelled = String(cancelled);
            row.classList.toggle("is-service-cancelled", cancelled);
        } catch (error) {
            console.error(`Gagal membaca status servis ${serviceId}:`, error);

            badge.textContent = "Status tidak dapat diperiksa";
            badge.className =
                "history-service-status js-chain-service-status is-unavailable";
        }
    }

    ownerServiceRows.forEach((row) => {
        refreshOwnerServiceStatus(row);
    });

    function openDetailModal() {
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("vehicle-modal-open");
    }

    function closeDetailModal() {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("vehicle-modal-open");
    }

    function setStatus(message, active = false) {
        const status = document.getElementById("detailVehicleStatus");

        if (!status) return;

        status.className = `vehicle-status ${active ? "active" : "inactive"}`;

        status.textContent = message;
    }

    async function openDetail(button) {
        const vehicleId = button.dataset.vehicleId;

        setText("detailVehicleOwnerName", button.dataset.ownerName);
        setText("detailVehicleOwnerId", button.dataset.ownerId);
        setText("detailVehicleType", button.dataset.type);
        setText("detailVehiclePlate", button.dataset.plate);
        setText(
            "detailVehicleVin",
            button.dataset.vin || button.dataset.chassisNumber,
        );
        setText("detailVehicleRegistered", button.dataset.registered);

        setText("detailVehicleId", vehicleId);
        setText("detailVehicleOwnerWallet", "Memuat...");
        setText("detailVehicleBrand", "Memuat...");
        setText("detailVehicleModel", "Memuat...");
        setText("detailVehicleYear", "Memuat...");
        setText("detailVehicleColor", "Memuat...");
        setText("detailVehicleName", "Memuat data kendaraan...");
        setText("detailVehicleSubtitle", "Menghubungkan ke blockchain...");

        setStatus("Memuat status...");

        const icon = document.getElementById("detailVehicleIcon");

        if (icon) {
            icon.innerHTML = '<i class="bi bi-car-front"></i>';
        }

        openDetailModal();

        try {
            const vehicle = await getVehicleFromBlockchain(vehicleId);

            setText("detailVehicleId", vehicle.vehicleId);
            setText("detailVehicleOwnerWallet", vehicle.owner);
            setText("detailVehicleBrand", vehicle.brand);
            setText("detailVehicleModel", vehicle.model);
            setText("detailVehicleYear", vehicle.year);
            setText("detailVehicleColor", vehicle.color);

            setText(
                "detailVehicleName",
                `${vehicle.brand} ${vehicle.model}`.trim(),
            );

            setText(
                "detailVehicleSubtitle",
                `${button.dataset.type || "Kendaraan"} · Tahun ${vehicle.year}`,
            );

            const isActive = vehicle.active && vehicle.registered;

            setStatus(isActive ? "Aktif" : "Tidak aktif", isActive);

            if (icon) {
                const type = (button.dataset.type || "").toLowerCase();

                icon.innerHTML =
                    type === "motor"
                        ? '<i class="bi bi-scooter"></i>'
                        : '<i class="bi bi-car-front"></i>';
            }
        } catch (error) {
            console.error(
                "Gagal mengambil detail kendaraan dari blockchain:",
                error,
            );

            setText("detailVehicleOwnerWallet", "-");
            setText("detailVehicleBrand", "-");
            setText("detailVehicleModel", "-");
            setText("detailVehicleYear", "-");
            setText("detailVehicleColor", "-");
            setText("detailVehicleName", "Data blockchain gagal dimuat");
            setText(
                "detailVehicleSubtitle",
                error.shortMessage ||
                    error.reason ||
                    error.message ||
                    "Tidak dapat membaca smart contract.",
            );

            setStatus("Gagal memuat blockchain");
        }
    }

    document
        .querySelectorAll(".vehicle-detail-button[data-vehicle-id]")
        .forEach((button) => {
            button.addEventListener("click", () => {
                openDetail(button);
            });
        });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeDetailModal);
    });

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeDetailModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("is-open")) {
            closeDetailModal();
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("vehicleForm");

    if (!form) return;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (form.dataset.busy === "true") return;

        if (
            !window.ethereum ||
            !connectedWallet ||
            !walletConnectionConfirmed
        ) {
            showVehicleStatus(
                "Hubungkan MetaMask terlebih dahulu sebelum mendaftarkan kendaraan.",
                true,
            );
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');

        const originalButtonText = submitButton?.textContent;

        form.dataset.busy = "true";

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Memproses...";
        }

        try {
            await ensureHardhatNetwork();

            const accounts = await window.ethereum.request({
                method: "eth_accounts",
            });

            if (!accounts || accounts.length === 0) {
                walletConnectionConfirmed = false;
                disconnectWalletUI();

                throw new Error(
                    "Wallet MetaMask tidak tersedia. Hubungkan kembali.",
                );
            }

            const activeWallet = accounts[0];

            updateWalletUI(activeWallet);

            const savedWallet = (form.dataset.savedWallet || "")
                .trim()
                .toLowerCase();

            if (savedWallet && savedWallet !== activeWallet.toLowerCase()) {
                throw new Error(
                    "Wallet MetaMask tidak sesuai dengan wallet akun ini.",
                );
            }

            showVehicleStatus("Memvalidasi data kendaraan...");

            const vehicleId = await prepareVehicle(form, activeWallet);

            showVehicleStatus(
                "Silakan setujui transaksi pendaftaran kendaraan di MetaMask.",
            );

            const contract = await getServiceChainWriteContract();

            const signerAddress = await contract.runner.getAddress();

            if (signerAddress.toLowerCase() !== activeWallet.toLowerCase()) {
                throw new Error(
                    "Akun MetaMask berubah. Hubungkan wallet yang benar.",
                );
            }

            const transaction = await contract.registerVehicle(
                vehicleId,
                form.querySelector('[name="brand"]').value.trim(),
                form.querySelector('[name="model"]').value.trim(),
                form.querySelector('[name="license_plate"]').value.trim(),
                form.querySelector('[name="color"]').value.trim(),
                Number(form.querySelector('[name="year"]').value),
            );

            showVehicleStatus(
                "Transaksi dikirim. Menunggu konfirmasi blockchain...",
            );

            const receipt = await transaction.wait();

            if (!receipt || Number(receipt.status) !== 1) {
                throw new Error("Transaksi blockchain gagal.");
            }

            const contractAddress = await contract.getAddress();

            const registeredEvent = getConfirmedVehicleEvent(
                receipt,
                vehicleId,
                activeWallet,
                contractAddress,
                contract,
            );

            if (!registeredEvent) {
                console.error("Receipt transaksi:", receipt);

                console.error("Vehicle ID:", vehicleId);

                console.error("Wallet aktif:", activeWallet);

                throw new Error(
                    "Transaksi berhasil, tetapi event VehicleRegistered tidak cocok. Periksa ABI kontrak.",
                );
            }

            const vehicleIdInput = form.querySelector('[name="vehicle_id"]');

            const walletInput = form.querySelector('[name="wallet_address"]');

            const transactionHashInput = form.querySelector(
                '[name="transaction_hash"]',
            );

            if (!vehicleIdInput || !walletInput || !transactionHashInput) {
                throw new Error("Field transaksi kendaraan tidak ditemukan.");
            }

            vehicleIdInput.value = String(vehicleId);
            walletInput.value = activeWallet;
            transactionHashInput.value = receipt.hash || transaction.hash;

            if (!vehicleIdInput.value.trim()) {
                throw new Error(
                    "Vehicle ID kosong. Form tidak dikirim ke Laravel.",
                );
            }

            console.log("[ServiceChain] Transaksi kendaraan terkonfirmasi:", {
                vehicleId: vehicleIdInput.value,
                walletAddress: walletInput.value,
                transactionHash: transactionHashInput.value,
            });

            showVehicleStatus(
                "Transaksi terkonfirmasi. Menyinkronkan data kendaraan ke MySQL...",
            );

            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            console.error("Pendaftaran kendaraan gagal:", error);

            showVehicleStatus(
                error.reason ||
                    error.shortMessage ||
                    error.message ||
                    "Pendaftaran kendaraan gagal.",
                true,
            );

            form.dataset.busy = "false";

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent =
                    originalButtonText || "Simpan Kendaraan";
            }
        }
    });
});

// riwayat service owner
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("historyDetailModal");

    if (!modal) return;

    const detailButtons = document.querySelectorAll(".js-history-detail");

    const closeButtons = modal.querySelectorAll("[data-history-close]");

    const setText = (id, value) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent =
                value !== null && value !== undefined && value !== ""
                    ? String(value)
                    : "-";
        }
    };

    const formatDate = (timestamp) => {
        const date = new Date(Number(timestamp) * 1000);

        if (Number.isNaN(date.getTime())) {
            return "-";
        }

        return new Intl.DateTimeFormat("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
            timeZone: "UTC",
        }).format(date);
    };

    const formatMileage = (value) => {
        return `${new Intl.NumberFormat("id-ID").format(Number(value))} km`;
    };

    const formatCost = (value) => {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0,
        }).format(Number(value));
    };

    function openModal() {
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("history-detail-open");
    }

    function closeModal() {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("history-detail-open");
    }

    function setReceiptLink(button) {
        const link = document.getElementById("detailReceiptLink");
        const unavailable = document.getElementById("detailReceiptUnavailable");

        const receiptUrl = button.dataset.receiptUrl;

        if (link) {
            if (receiptUrl) {
                link.href = receiptUrl;
                link.hidden = false;
            } else {
                link.removeAttribute("href");
                link.hidden = true;
            }
        }

        if (unavailable) {
            unavailable.hidden = Boolean(receiptUrl);
            unavailable.textContent = receiptUrl
                ? ""
                : "Bukti nota tidak tersedia";
        }
    }

    async function openDetail(button) {
        setText("detailOwnerName", button.dataset.ownerName);
        setText("detailOwnerId", button.dataset.ownerId);
        setText("detailWorkshop", button.dataset.workshop);
        setText("detailAddress", button.dataset.address);

        setReceiptLink(button);

        // Field blockchain
        setText("detailServiceId", button.dataset.serviceId);
        setText("detailVehicle", "Memuat...");
        setText("detailVehicleId", "Memuat...");
        setText("detailServiceType", "Memuat...");
        setText("detailDate", "Memuat...");
        setText("detailMileage", "Memuat...");
        setText("detailCost", "Memuat...");
        setText("detailWorkshopWallet", "Memuat...");
        setText("detailDescription", "Memuat...");
        setText("detailServiceHash", "Memuat...");

        openModal();

        try {
            const record = await getServiceRecordFromBlockchain(
                button.dataset.serviceId,
            );

            if (detailStatusBanner) {
                detailStatusBanner.hidden = false;

                detailStatusBanner.className =
                    "history-service-status-banner " +
                    (record.cancelled ? "is-cancelled" : "is-active");

                detailStatusBanner.textContent = record.cancelled
                    ? "CATATAN SERVIS DIBATALKAN — Catatan ini sudah dibatalkan " +
                      "oleh bengkel melalui blockchain. Data tetap tersedia " +
                      "sebagai jejak audit."
                    : "CATATAN SERVIS TERSIMPAN — Belum dibatalkan di blockchain.";
            }

            setText("detailServiceId", record.serviceId);
            setText("detailVehicle", record.vehicle);
            setText("detailVehicleId", record.vehicleId);
            setText("detailServiceType", record.serviceType);
            setText("detailDate", formatDate(record.serviceDate));
            setText("detailMileage", formatMileage(record.mileage));
            setText("detailCost", formatCost(record.cost));
            setText("detailWorkshopWallet", record.workshop);
            setText("detailDescription", record.description);
            setText("detailServiceHash", record.serviceHash);

            if (record.cancelled) {
                setText(
                    "detailDescription",
                    `${record.description} (Catatan servis dibatalkan)`,
                );
            }

            const row = button.closest(".js-service-row");

            if (row) {
                const setRowText = (selector, value) => {
                    const element = row.querySelector(selector);

                    if (element) {
                        element.textContent = value;
                    }
                };

                setRowText(".js-chain-vehicle", record.vehicle);
                setRowText(".js-chain-vehicle-id", record.vehicleId);
                setRowText(".js-chain-service-type", record.serviceType);
                setRowText(
                    ".js-chain-service-date",
                    formatDate(record.serviceDate),
                );
                setRowText(".js-chain-mileage", formatMileage(record.mileage));
                setRowText(".js-chain-cost", formatCost(record.cost));
            }
        } catch (error) {
            console.error(
                "Gagal membaca riwayat servis dari blockchain:",
                error,
            );

            if (detailStatusBanner) {
                detailStatusBanner.hidden = false;
                detailStatusBanner.className =
                    "history-service-status-banner is-unavailable";
                detailStatusBanner.textContent =
                    "Status catatan tidak dapat diperiksa dari blockchain.";
            }

            setText("detailVehicle", "Gagal memuat blockchain");
            setText("detailVehicleId", "-");
            setText("detailServiceType", "-");
            setText("detailDate", "-");
            setText("detailMileage", "-");
            setText("detailCost", "-");
            setText("detailWorkshopWallet", "-");
            setText("detailDescription", "Data blockchain gagal dimuat.");
            setText("detailServiceHash", "-");
        }
    }

    detailButtons.forEach((button) => {
        button.addEventListener("click", () => {
            openDetail(button);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("is-open")) {
            closeModal();
        }
    });
});
