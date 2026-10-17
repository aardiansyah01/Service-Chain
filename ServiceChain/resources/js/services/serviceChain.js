import { ethers } from "ethers";
import serviceChainAbi from "../abi/ServiceChain.json";

const CONTRACT_ADDRESS = import.meta.env.VITE_SERVICECHAIN_CONTRACT_ADDRESS;

const RPC_URL =
    import.meta.env.VITE_SERVICECHAIN_RPC_URL || "http://127.0.0.1:8545";

function getContractAddress() {
    if (!CONTRACT_ADDRESS) {
        throw new Error("Alamat kontrak ServiceChain belum dikonfigurasi.");
    }

    return CONTRACT_ADDRESS;
}

export function getServiceChainReadContract() {
    const provider = new ethers.JsonRpcProvider(RPC_URL);

    return new ethers.Contract(getContractAddress(), serviceChainAbi, provider);
}

export async function getServiceChainWriteContract() {
    if (!window.ethereum) {
        throw new Error(
            "MetaMask tidak ditemukan. Silakan pasang atau aktifkan MetaMask.",
        );
    }

    const provider = new ethers.BrowserProvider(window.ethereum);

    await provider.send("eth_requestAccounts", []);

    const signer = await provider.getSigner();

    return new ethers.Contract(getContractAddress(), serviceChainAbi, signer);
}
