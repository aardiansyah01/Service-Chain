import { getServiceChainWriteContract } from "./serviceChain";
import { getServiceRecordFromBlockchain } from "./serviceChainRead";

const SERVICE_ID_PATTERN = /^[1-9]\d*$/;

function ensureNotice() {
    let notice = document.getElementById("workshopCancelServiceNotice");
    if (notice) return notice;

    notice = document.createElement("div");
    notice.id = "workshopCancelServiceNotice";
    notice.className = "workshop-cancel-notice";
    notice.setAttribute("role", "status");
    notice.setAttribute("aria-live", "polite");

    const heading = document.querySelector(".workshop-page-heading");
    if (heading) {
        heading.insertAdjacentElement("afterend", notice);
    } else {
        document.body.prepend(notice);
    }

    return notice;
}

function notify(message, kind = "info") {
    const notice = ensureNotice();
    notice.textContent = message;
    notice.classList.remove("is-success", "is-error", "is-info");
    notice.classList.add(`is-${kind}`);
    notice.hidden = false;
}

function setRowStatus(row, kind, label) {
    const badge = row.querySelector(".js-service-status");
    if (!badge) return;

    badge.classList.remove(
        "is-stored",
        "is-cancelled",
        "is-unavailable",
        "is-checking",
    );
    badge.classList.add(kind);

    const labelElement = badge.querySelector("[data-service-status-label]");
    if (labelElement) {
        labelElement.textContent = label;
    } else {
        badge.lastChild.textContent = ` ${label}`;
    }
}

function updateCancelButton(row, { disabled, label, title } = {}) {
    const button = row.querySelector("[data-cancel-service]");
    if (!button) return;

    if (typeof disabled === "boolean") {
        button.disabled = disabled;
        button.setAttribute("aria-disabled", String(disabled));
    }
    if (label) button.dataset.idleLabel = label;
    if (title) button.title = title;
}

async function refreshServiceRow(row) {
    const serviceId = String(row.dataset.serviceId || "").trim();
    const cancelButton = row.querySelector("[data-cancel-service]");

    if (!SERVICE_ID_PATTERN.test(serviceId)) {
        setRowStatus(row, "is-unavailable", "Tidak tersedia di blockchain");
        if (cancelButton) {
            cancelButton.disabled = true;
            cancelButton.title =
                "Catatan lama ini tidak memiliki Service ID numerik di blockchain.";
        }
        return;
    }

    setRowStatus(row, "is-checking", "Memeriksa blockchain...");
    if (cancelButton) cancelButton.disabled = true;

    try {
        const record = await getServiceRecordFromBlockchain(serviceId);

        if (record.cancelled) {
            setRowStatus(row, "is-cancelled", "Dibatalkan");
            updateCancelButton(row, {
                disabled: true,
                title: "Catatan servis ini sudah dibatalkan di blockchain.",
            });
            row.dataset.serviceCancelled = "true";
        } else {
            setRowStatus(row, "is-stored", "Tersimpan");
            updateCancelButton(row, {
                disabled: false,
                title: "Batalkan catatan servis ini di blockchain.",
            });
            row.dataset.serviceCancelled = "false";
        }
    } catch (error) {
        console.error(`Gagal membaca status Service ID ${serviceId}:`, error);
        row.dataset.serviceCancelled = "unknown";
        setRowStatus(row, "is-unavailable", "Status tidak dapat diperiksa");
        updateCancelButton(row, {
            disabled: true,
            title: "Status blockchain tidak bisa diverifikasi. Coba muat ulang halaman.",
        });
    }
}

async function cancelServiceRecord(button) {
    const row = button.closest(".workshop-service-row");
    if (!row) return;

    const serviceId = String(
        button.dataset.serviceId || row.dataset.serviceId || "",
    ).trim();
    const expectedWallet = String(button.dataset.workshopWallet || "")
        .trim()
        .toLowerCase();

    if (!SERVICE_ID_PATTERN.test(serviceId)) {
        notify(
            "Service ID tidak valid. Catatan ini tidak bisa dibatalkan melalui blockchain.",
            "error",
        );
        return;
    }

    if (row.dataset.serviceCancelled === "true") {
        notify("Catatan servis ini sudah dibatalkan sebelumnya.", "error");
        return;
    }

    const confirmed = window.confirm(
        `Batalkan catatan servis #${serviceId}?\n\n` +
            "Catatan tidak akan dihapus permanen. Blockchain akan menandainya sebagai Dibatalkan dan tindakan ini tidak bisa dibatalkan kembali.",
    );
    if (!confirmed) return;

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.classList.add("is-processing");
    button.innerHTML =
        '<i class="bi bi-hourglass-split" aria-hidden="true"></i><span>Memproses...</span>';

    try {
        const currentRecord = await getServiceRecordFromBlockchain(serviceId);
        if (currentRecord.cancelled) {
            row.dataset.serviceCancelled = "true";
            setRowStatus(row, "is-cancelled", "Dibatalkan");
            updateCancelButton(row, {
                disabled: true,
                title: "Catatan servis ini sudah dibatalkan di blockchain.",
            });
            notify(
                `Catatan servis #${serviceId} sudah dibatalkan sebelumnya.`,
                "error",
            );
            return;
        }

        const contract = await getServiceChainWriteContract();
        const signerAddress = String(
            await contract.runner.getAddress(),
        ).toLowerCase();

        if (expectedWallet && signerAddress !== expectedWallet) {
            throw new Error(
                "Wallet MetaMask tidak cocok dengan wallet bengkel yang tercatat. Pilih akun bengkel yang benar.",
            );
        }

        notify(
            `Menunggu konfirmasi MetaMask untuk membatalkan catatan #${serviceId}...`,
            "info",
        );
        const transaction = await contract.cancelServiceRecord(serviceId);
        notify("Transaksi dikirim. Menunggu konfirmasi blockchain...", "info");

        const receipt = await transaction.wait();
        if (!receipt || Number(receipt.status) !== 1) {
            throw new Error(
                "Transaksi pembatalan tidak berhasil dikonfirmasi.",
            );
        }

        row.dataset.serviceCancelled = "true";
        setRowStatus(row, "is-cancelled", "Dibatalkan");
        updateCancelButton(row, {
            disabled: true,
            title: "Catatan servis ini sudah dibatalkan di blockchain.",
        });
        notify(
            `Catatan servis #${serviceId} berhasil dibatalkan di blockchain. Catatan tetap tersimpan sebagai jejak audit.`,
            "success",
        );
    } catch (error) {
        console.error("Pembatalan catatan servis gagal:", error);
        const message =
            error?.shortMessage ||
            error?.reason ||
            error?.message ||
            "Terjadi kesalahan yang tidak diketahui.";
        notify(`Pembatalan gagal: ${message}`, "error");
        await refreshServiceRow(row);
    } finally {
        button.classList.remove("is-processing");
        button.innerHTML = originalHtml;
        if (row.dataset.serviceCancelled === "true") button.disabled = true;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const rows = Array.from(
        document.querySelectorAll(".workshop-service-row[data-service-id]"),
    );
    rows.forEach((row) => {
        refreshServiceRow(row);
    });

    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-cancel-service]");
        if (!button || button.disabled) return;
        cancelServiceRecord(button);
    });
});
