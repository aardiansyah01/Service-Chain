import { ethers } from "ethers";
import { getServiceChainWriteContract } from "./serviceChain";

const HARDHAT_CHAIN_ID = "0x7a69";

function shortenAddress(address) {
    return address
        ? `${address.slice(0, 6)}...${address.slice(-4)}`
        : "Belum terhubung";
}

function showStatus(message, isError = false) {
    const element = document.getElementById("workshopWalletStatus");

    if (!element) return;

    element.textContent = message;
    element.style.color = isError ? "#dc2626" : "#2563eb";
}

function updateWallet(address) {
    const element = document.getElementById("workshopWalletAddress");

    if (element) {
        element.textContent = shortenAddress(address);
        element.title = address || "";
    }
}

function getEventValue(event, name, index) {
    return event.args?.[name] ?? event.args?.[index];
}

async function ensureHardhatNetwork() {
    if (!window.ethereum) {
        throw new Error("MetaMask tidak ditemukan.");
    }

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

async function checkWalletAvailability(button, walletAddress) {
    if (!button.dataset.checkUrl) {
        throw new Error("URL pemeriksaan wallet belum dikonfigurasi.");
    }

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]',
    )?.content;

    if (!csrfToken) {
        throw new Error("Token CSRF tidak ditemukan. Muat ulang halaman.");
    }

    showStatus("Memeriksa wallet pada akun ServiceChain...");

    const response = await fetch(button.dataset.checkUrl, {
        method: "POST",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
            wallet_address: walletAddress,
        }),
    });

    const result = await response.json();

    if (!response.ok || result.available !== true) {
        const message = Object.values(result.errors || {}).flat()[0];

        throw new Error(
            message ||
                result.message ||
                "Wallet tidak dapat digunakan oleh bengkel ini.",
        );
    }

    return csrfToken;
}

function hasRegistrationEvent(contract, receipt, walletAddress, workshopName) {
    return receipt.logs.some((log) => {
        try {
            const parsed = contract.interface.parseLog({
                topics: log.topics,
                data: log.data,
            });

            if (parsed?.name !== "WorkshopRegistered") {
                return false;
            }

            const eventWallet = getEventValue(parsed, "wallet", 0);
            const eventName = getEventValue(parsed, "name", 1);

            return (
                typeof eventWallet === "string" &&
                eventWallet.toLowerCase() === walletAddress.toLowerCase() &&
                eventName === workshopName
            );
        } catch {
            return false;
        }
    });
}

async function findWorkshopRegistrationTx(
    contract,
    walletAddress,
    expectedName,
) {
    const provider = contract.runner?.provider;

    if (!provider) {
        throw new Error("Provider blockchain tidak ditemukan.");
    }

    const latestBlock = await provider.getBlockNumber();

    const filter = contract.filters.WorkshopRegistered(walletAddress);

    const events = await contract.queryFilter(filter, 0, latestBlock);

    const matchedEvent = [...events].reverse().find((event) => {
        const eventWallet = getEventValue(event, "wallet", 0);
        const eventName = getEventValue(event, "name", 1);

        return (
            typeof eventWallet === "string" &&
            eventWallet.toLowerCase() === walletAddress.toLowerCase() &&
            eventName === expectedName
        );
    });

    return matchedEvent?.transactionHash ?? null;
}

async function connectWorkshopWallet(button) {
    await ensureHardhatNetwork();

    const accounts = await window.ethereum.request({
        method: "eth_requestAccounts",
    });

    if (!accounts.length) {
        throw new Error("Tidak ada wallet yang dipilih.");
    }

    const walletAddress = accounts[0];
    const normalizedWallet = walletAddress.toLowerCase();

    const savedWallet = (button.dataset.savedWallet || "").toLowerCase();

    if (savedWallet && savedWallet !== normalizedWallet) {
        throw new Error(
            "Wallet berbeda dari wallet yang sudah terhubung ke bengkel ini.",
        );
    }

    const csrfToken = await checkWalletAvailability(button, walletAddress);

    const contract = await getServiceChainWriteContract();
    const signerAddress = await contract.runner.getAddress();

    if (signerAddress.toLowerCase() !== normalizedWallet) {
        throw new Error("Akun MetaMask berubah. Periksa wallet yang dipilih.");
    }

    const workshopName = button.dataset.workshopName?.trim();

    if (!workshopName) {
        throw new Error("Nama bengkel tidak ditemukan.");
    }

    const workshopState = await contract.workshops(walletAddress);

    const registered = Boolean(workshopState.registered ?? workshopState[2]);

    const status = Number(workshopState.status ?? workshopState[1]);

    const pendingKey = `servicechain:workshop-registration:${normalizedWallet}`;

    let registrationTxHash = sessionStorage.getItem(pendingKey);

    if (registered && (status === 2 || status === 3)) {
        throw new Error(
            "Wallet berstatus REJECTED atau INACTIVE di blockchain. Aktivasi otomatis tidak diizinkan.",
        );
    }

    if (registered && status === 1 && savedWallet === normalizedWallet) {
        sessionStorage.removeItem(pendingKey);
        updateWallet(walletAddress);
        showStatus("Wallet bengkel sudah terhubung dan aktif.");
        return;
    }

    if (!registered) {
        sessionStorage.removeItem(pendingKey);
        registrationTxHash = null;

        showStatus("Meminta persetujuan transaksi registrasi wallet...");

        const tx = await contract.registerWorkshop(workshopName);

        const receipt = await tx.wait();

        if (!receipt || Number(receipt.status) !== 1) {
            throw new Error("Transaksi registrasi wallet gagal.");
        }

        if (
            !hasRegistrationEvent(
                contract,
                receipt,
                walletAddress,
                workshopName,
            )
        ) {
            registrationTxHash = receipt.hash || tx.hash;

            sessionStorage.setItem(pendingKey, registrationTxHash);

            throw new Error(
                "Transaksi berhasil, tetapi event registrasi tidak cocok. Registrasi tidak diulang; periksa ABI dan receipt.",
            );
        }

        registrationTxHash = receipt.hash || tx.hash;

        sessionStorage.setItem(pendingKey, registrationTxHash);
    }

    if (registered && !registrationTxHash) {
        showStatus("Memulihkan transaksi registrasi dari blockchain...");

        registrationTxHash = await findWorkshopRegistrationTx(
            contract,
            walletAddress,
            workshopName,
        );

        if (registrationTxHash) {
            sessionStorage.setItem(pendingKey, registrationTxHash);
        }
    }

    if (!registrationTxHash) {
        throw new Error(
            "Transaksi registrasi tidak ditemukan. Tidak ada registrasi baru yang dikirim.",
        );
    }

    if (!button.dataset.connectUrl) {
        throw new Error("URL aktivasi wallet belum dikonfigurasi.");
    }

    showStatus(
        "Memverifikasi registrasi dan mengaktifkan wallet di blockchain...",
    );

    const response = await fetch(button.dataset.connectUrl, {
        method: "POST",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
            wallet_address: walletAddress,
            registration_transaction_hash: registrationTxHash,
        }),
    });

    const result = await response.json();

    if (!response.ok) {
        const message = Object.values(result.errors || {}).flat()[0];

        throw new Error(
            message ||
                result.message ||
                "Aktivasi wallet gagal. Hash registrasi tetap disimpan untuk pemulihan.",
        );
    }

    if (
        result.blockchain_status !== "ACTIVE" ||
        result.wallet_address?.toLowerCase() !== normalizedWallet
    ) {
        throw new Error(
            "Backend belum mengonfirmasi wallet aktif. Coba sinkronisasi ulang.",
        );
    }

    sessionStorage.removeItem(pendingKey);

    updateWallet(walletAddress);

    showStatus("Wallet berhasil terhubung dan aktif di blockchain.");

    window.location.reload();
}

document.addEventListener("DOMContentLoaded", () => {
    const button = document.getElementById("connect-workshop-wallet");

    if (!button) return;

    if (window.ethereum) {
        const refreshWorkshopWalletDisplay = (accounts) => {
            const savedWallet = (
                button.dataset.savedWallet || ""
            ).toLowerCase();

            const account = accounts?.[0] || null;

            if (
                account &&
                savedWallet &&
                account.toLowerCase() === savedWallet
            ) {
                updateWallet(account);
            } else {
                updateWallet(null);
            }
        };

        window.ethereum
            .request({ method: "eth_accounts" })
            .then(refreshWorkshopWalletDisplay)
            .catch(() => updateWallet(null));

        window.ethereum.on?.("accountsChanged", (accounts) => {
            refreshWorkshopWalletDisplay(accounts);
        });

        window.ethereum.on?.("chainChanged", () => {
            window.location.reload();
        });
    }

    button.addEventListener("click", async () => {
        if (button.dataset.busy === "true") return;

        button.dataset.busy = "true";
        button.disabled = true;

        try {
            await connectWorkshopWallet(button);
        } catch (error) {
            showStatus(
                error.shortMessage || error.message || "Koneksi wallet gagal.",
                true,
            );
        } finally {
            button.dataset.busy = "false";
            button.disabled = false;
        }
    });
});
