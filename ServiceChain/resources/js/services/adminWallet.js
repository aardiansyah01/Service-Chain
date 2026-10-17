import { ethers } from "ethers";
import serviceChainAbi from "../abi/ServiceChain.json";

const HARDHAT_CHAIN_ID = "0x7a69";
console.log("[AdminWallet] File berhasil dimuat");

const CONTRACT_ADDRESS = import.meta.env.VITE_SERVICECHAIN_CONTRACT_ADDRESS;

document.addEventListener("DOMContentLoaded", () => {
    console.log("[AdminWallet] DOMContentLoaded berjalan");

    const connectButton = document.getElementById("connectAdminWalletButton");

    if (!connectButton) return;

    const addressElement = document.getElementById("adminWalletAddress");
    const statusElement = document.getElementById("adminWalletStatus");
    const approveButton = document.getElementById("approveWorkshopButton");
    const approveForm = document.getElementById("approveWorkshopForm");

    let adminWallet = null;
    let busy = false;

    window.serviceChainAdminWalletValid = false;

    function showStatus(message = "", isError = false) {
        if (!statusElement) return;

        statusElement.textContent = message;
        statusElement.hidden = !message;
        statusElement.style.color = isError ? "#dc2626" : "#16a34a";
    }

    function updateUI(address = null) {
        adminWallet = address;

        window.serviceChainAdminWalletValid = Boolean(address);

        if (addressElement) {
            addressElement.textContent = address
                ? `${address.slice(0, 6)}...${address.slice(-4)}`
                : "Belum terhubung";

            addressElement.title = address || "";
        }

        connectButton.textContent = address
            ? "Ganti Wallet"
            : "Hubungkan Wallet";

        const button = document.getElementById("approveWorkshopButton");

        if (button) {
            button.disabled = !window.serviceChainAdminWalletValid || busy;
        }
    }

    async function ensureHardhatNetwork() {
        if (!window.ethereum) {
            throw new Error("MetaMask tidak ditemukan.");
        }

        let chainId = await window.ethereum.request({
            method: "eth_chainId",
        });

        if (chainId.toLowerCase() === HARDHAT_CHAIN_ID) return;

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

        chainId = await window.ethereum.request({
            method: "eth_chainId",
        });

        if (chainId.toLowerCase() !== HARDHAT_CHAIN_ID) {
            throw new Error("Gagal beralih ke jaringan Hardhat.");
        }
    }

    async function verifyAdminWallet(address) {
        if (!CONTRACT_ADDRESS) {
            throw new Error(
                "Alamat contract belum dikonfigurasi. Periksa VITE_SERVICECHAIN_CONTRACT_ADDRESS.",
            );
        }

        const provider = new ethers.BrowserProvider(window.ethereum);

        const network = await provider.getNetwork();

        if (network.chainId !== 31337n) {
            throw new Error("Wallet tidak berada di jaringan Hardhat.");
        }

        const contract = new ethers.Contract(
            CONTRACT_ADDRESS,
            serviceChainAbi,
            provider,
        );

        const contractAdmin = await contract.admin();

        if (contractAdmin.toLowerCase() !== address.toLowerCase()) {
            throw new Error(
                "Wallet ini bukan Admin smart contract. Pilih akun yang digunakan saat deploy contract.",
            );
        }

        return address;
    }

    async function connectAdminWallet() {
        if (!window.ethereum) {
            throw new Error(
                "MetaMask tidak ditemukan. Pasang atau aktifkan MetaMask terlebih dahulu.",
            );
        }

        await ensureHardhatNetwork();

        const accounts = await window.ethereum.request({
            method: "eth_requestAccounts",
        });

        if (!accounts.length) {
            throw new Error("Tidak ada akun MetaMask yang dipilih.");
        }

        return verifyAdminWallet(accounts[0]);
    }

    async function refreshConnection() {
        if (!window.ethereum) {
            updateUI(null);
            return;
        }

        try {
            const chainId = await window.ethereum.request({
                method: "eth_chainId",
            });

            if (chainId.toLowerCase() !== HARDHAT_CHAIN_ID) {
                updateUI(null);
                showStatus("Pilih jaringan Hardhat Localhost.", true);
                return;
            }

            const accounts = await window.ethereum.request({
                method: "eth_accounts",
            });

            if (!accounts.length) {
                updateUI(null);
                showStatus();
                return;
            }

            const address = await verifyAdminWallet(accounts[0]);

            updateUI(address);
            showStatus();
        } catch (error) {
            updateUI(null);
            showStatus(error.message || "Wallet Admin belum valid.", true);
        }
    }

    connectButton.addEventListener("click", async () => {
        if (busy) return;

        console.log("Tombol Hubungkan Wallet diklik!");

        connectButton.disabled = true;
        showStatus();

        try {
            const address = await connectAdminWallet();

            updateUI(address);
            showStatus();
        } catch (error) {
            updateUI(null);
            showStatus(
                error.shortMessage ||
                    error.message ||
                    "Gagal menghubungkan wallet.",
                true,
            );
        } finally {
            connectButton.disabled = false;
        }
    });

    if (approveForm) {
        approveForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (busy) return;

            busy = true;
            if (approveButton) approveButton.disabled = true;

            try {
                const accounts = await window.ethereum?.request({
                    method: "eth_accounts",
                });

                if (!accounts?.length) {
                    throw new Error("Hubungkan wallet Admin terlebih dahulu.");
                }

                const address = await verifyAdminWallet(accounts[0]);

                if (
                    !adminWallet ||
                    adminWallet.toLowerCase() !== address.toLowerCase()
                ) {
                    throw new Error("Hubungkan ulang wallet Admin.");
                }

                const confirmed = window.confirm(
                    "Setujui bengkel ini dan ubah statusnya menjadi ACTIVE di MySQL?",
                );

                if (confirmed) {
                    HTMLFormElement.prototype.submit.call(approveForm);
                    return;
                }
            } catch (error) {
                showStatus(error.message || "Validasi wallet gagal.", true);
                window.alert(error.message || "Validasi wallet gagal.");
            } finally {
                busy = false;

                if (approveButton) {
                    approveButton.disabled = !adminWallet;
                }
            }
        });
    }

    if (window.ethereum) {
        window.ethereum.on?.("accountsChanged", refreshConnection);
        window.ethereum.on?.("chainChanged", refreshConnection);
    }

    refreshConnection();
});
