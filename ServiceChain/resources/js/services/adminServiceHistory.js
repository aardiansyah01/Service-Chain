import { getServiceRecordFromBlockchain } from "./serviceChainRead";

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("serviceDetailModal");

    if (!modal) return;

    const detailButtons = document.querySelectorAll(".service-detail-trigger");

    const closeButtons = [
        document.getElementById("closeServiceModal"),
        document.getElementById("doneServiceModal"),
    ].filter(Boolean);

    const cache = new Map();

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent =
                value !== null && value !== undefined && value !== ""
                    ? String(value)
                    : "-";
        }
    }

    function formatDate(timestamp) {
        const date = new Date(Number(timestamp) * 1000);

        if (Number.isNaN(date.getTime())) return "-";

        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            timeZone: "UTC",
        }).format(date);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat("id-ID").format(Number(value));
    }

    function formatCost(value) {
        return `Rp${formatNumber(value)}`;
    }

    function openModal() {
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("service-modal-open");
    }

    function closeModal() {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("service-modal-open");
    }

    function loadBlockchainRecord(serviceId) {
        const key = String(serviceId);

        if (!cache.has(key)) {
            cache.set(
                key,
                getServiceRecordFromBlockchain(key).catch((error) => {
                    cache.delete(key);
                    throw error;
                }),
            );
        }

        return cache.get(key);
    }

    function updateTableRow(row, record) {
        const setRowText = (selector, value) => {
            const element = row.querySelector(selector);

            if (element) element.textContent = value;
        };

        setRowText(".js-chain-vehicle", record.vehicle);
        setRowText(".js-chain-vehicle-id", record.vehicleId);
        setRowText(".js-chain-service-type", record.serviceType);
        setRowText(".js-chain-service-date", formatDate(record.serviceDate));
        setRowText(".js-chain-mileage", `${formatNumber(record.mileage)} km`);
        setRowText(".js-chain-cost", formatCost(record.cost));
    }

    function showReceipt(button) {
        const link = document.getElementById("detailReceiptLink");
        const unavailable = document.getElementById("detailReceiptUnavailable");

        const url = button.dataset.receiptUrl;

        if (link) {
            link.hidden = !url;

            if (url) {
                link.href = url;
            } else {
                link.removeAttribute("href");
            }
        }

        if (unavailable) {
            unavailable.hidden = Boolean(url);
        }
    }

    function showBlockchainLoading() {
        [
            "detailServiceIdField",
            "detailServiceDate",
            "detailServiceType",
            "detailCost",
            "detailMileage",
            "detailWorkshopWallet",
            "detailDescription",
            "detailServiceHash",
            "detailVehicleId",
            "detailVehicleBrand",
            "detailVehicleModel",
            "detailVehicleColor",
            "detailVehicleYear",
            "detailOwnerWallet",
        ].forEach((id) => setText(id, "Memuat blockchain..."));
    }

    function showBlockchainError(message) {
        [
            "detailServiceIdField",
            "detailServiceDate",
            "detailServiceType",
            "detailCost",
            "detailMileage",
            "detailWorkshopWallet",
            "detailDescription",
            "detailServiceHash",
            "detailVehicleId",
            "detailVehicleBrand",
            "detailVehicleModel",
            "detailVehicleColor",
            "detailVehicleYear",
            "detailOwnerWallet",
        ].forEach((id) => setText(id, "Gagal memuat dari blockchain"));

        setText("detailServiceId", "Gagal memuat blockchain");
        console.error("Pembacaan data servis gagal:", message);
    }

    async function openDetail(button) {
        const serviceId = button.dataset.serviceId;

        setText("detailOwner", button.dataset.ownerName);
        setText("detailOwnerId", button.dataset.ownerId);
        setText("detailWorkshop", button.dataset.workshopName);
        setText("detailWorkshopAddress", button.dataset.workshopAddress);

        showReceipt(button);
        showBlockchainLoading();
        setText("detailServiceId", `Service ID ${serviceId}`);

        openModal();

        try {
            const record = await loadBlockchainRecord(serviceId);

            setText("detailServiceId", `Service ID ${record.serviceId}`);
            setText("detailServiceIdField", record.serviceId);
            setText("detailServiceDate", formatDate(record.serviceDate));
            setText("detailServiceType", record.serviceType);
            setText("detailCost", formatCost(record.cost));
            setText("detailMileage", `${formatNumber(record.mileage)} km`);
            setText("detailWorkshopWallet", record.workshop);
            setText("detailDescription", record.description);
            setText("detailServiceHash", record.serviceHash);

            setText("detailVehicleId", record.vehicleId);
            setText("detailVehicleBrand", record.brand);
            setText("detailVehicleModel", record.model);
            setText("detailVehicleColor", record.color);
            setText("detailVehicleYear", record.year);
            setText("detailOwnerWallet", record.ownerAddress);

            if (record.cancelled) {
                setText(
                    "detailDescription",
                    `${record.description} (Catatan servis dibatalkan)`,
                );
            }

            const row = button.closest(".admin-service-row");

            if (row) {
                updateTableRow(row, record);
            }
        } catch (error) {
            showBlockchainError(
                error.shortMessage ||
                    error.reason ||
                    error.message ||
                    "Jaringan tidak tersedia.",
            );
        }
    }

    document.querySelectorAll(".admin-service-row").forEach(async (row) => {
        const serviceId = row.dataset.serviceId;

        try {
            const record = await loadBlockchainRecord(serviceId);
            updateTableRow(row, record);
        } catch (error) {
            const selectors = [
                ".js-chain-vehicle",
                ".js-chain-vehicle-id",
                ".js-chain-service-type",
                ".js-chain-service-date",
                ".js-chain-mileage",
                ".js-chain-cost",
            ];

            selectors.forEach((selector) => {
                const element = row.querySelector(selector);

                if (element) {
                    element.textContent = "Gagal memuat blockchain";
                    element.title = error.message || "";
                }
            });
        }
    });

    detailButtons.forEach((button) => {
        button.addEventListener("click", () => openDetail(button));
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("is-open")) {
            closeModal();
        }
    });
});
