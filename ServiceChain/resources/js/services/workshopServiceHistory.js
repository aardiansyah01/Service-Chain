import { getServiceRecordFromBlockchain } from "./serviceChainRead";

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("serviceDetailModal");

    if (!modal) return;

    const detailButtons = document.querySelectorAll(".open-service-detail");
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
            month: "long",
            year: "numeric",
            timeZone: "UTC",
        }).format(date);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat("id-ID").format(Number(value));
    }

    function formatCost(value) {
        return `Rp ${formatNumber(value)}`;
    }

    function getRecord(serviceId) {
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

    function openModal() {
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("workshop-modal-open");
    }

    function setReceipt(button) {
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

    function setBlockchainLoading() {
        [
            "detailServiceIdField",
            "detailVehicleId",
            "detailVehicle",
            "detailDate",
            "detailType",
            "detailCost",
            "detailMileage",
            "detailWorkshopWallet",
            "detailDescription",
            "detailServiceHash",
            "detailVehicleColor",
            "detailVehicleYear",
        ].forEach((id) => setText(id, "Memuat blockchain..."));
    }

    function updateTable(row, record) {
        const values = {
            ".js-chain-vehicle": record.vehicle,
            ".js-chain-vehicle-id": record.vehicleId,
            ".js-chain-service-type": record.serviceType,
            ".js-chain-service-date": formatDate(record.serviceDate),
            ".js-chain-mileage": `${formatNumber(record.mileage)} km`,
        };

        Object.entries(values).forEach(([selector, value]) => {
            const element = row.querySelector(selector);

            if (element) element.textContent = value;
        });
    }

    async function openDetail(button) {
        setText("detailOwner", button.dataset.ownerName);
        setText("detailOwnerId", button.dataset.ownerId);
        setText("detailWorkshop", button.dataset.workshopName);
        setText("detailAddress", button.dataset.workshopAddress);

        setReceipt(button);
        setBlockchainLoading();

        setText(
            "detailServiceId",
            `ID Catatan: ${button.dataset.serviceId || "-"}`,
        );

        openModal();

        try {
            const record = await getRecord(button.dataset.serviceId);

            setText("detailServiceId", `ID Catatan: ${record.serviceId}`);
            setText("detailServiceIdField", record.serviceId);
            setText("detailVehicleId", record.vehicleId);
            setText("detailVehicle", record.vehicle);
            setText("detailDate", formatDate(record.serviceDate));
            setText("detailType", record.serviceType);
            setText("detailCost", formatCost(record.cost));
            setText("detailMileage", `${formatNumber(record.mileage)} km`);
            setText("detailWorkshopWallet", record.workshop);
            setText("detailDescription", record.description);
            setText("detailServiceHash", record.serviceHash);

            setText("detailVehicleColor", record.color);
            setText("detailVehicleYear", record.year);

            if (record.cancelled) {
                setText(
                    "detailDescription",
                    `${record.description} (Catatan servis dibatalkan)`,
                );
            }

            const row = button.closest(".workshop-service-row");

            if (row) updateTable(row, record);
        } catch (error) {
            console.error(
                "Gagal membaca riwayat servis dari blockchain:",
                error,
            );

            [
                "detailServiceIdField",
                "detailVehicleId",
                "detailVehicle",
                "detailDate",
                "detailType",
                "detailCost",
                "detailMileage",
                "detailWorkshopWallet",
                "detailDescription",
                "detailServiceHash",
                "detailVehicleColor",
                "detailVehicleYear",
            ].forEach((id) => setText(id, "Gagal memuat blockchain"));

            setText("detailServiceId", "Data blockchain gagal dimuat");
        }
    }

    document.querySelectorAll(".workshop-service-row").forEach(async (row) => {
        try {
            const record = await getRecord(row.dataset.serviceId);
            updateTable(row, record);
        } catch (error) {
            console.error(
                `Gagal memuat Service ID ${row.dataset.serviceId}:`,
                error,
            );

            row.querySelectorAll(
                ".js-chain-vehicle, .js-chain-vehicle-id, " +
                    ".js-chain-service-type, .js-chain-service-date, " +
                    ".js-chain-mileage",
            ).forEach((element) => {
                element.textContent = "Gagal memuat blockchain";
                element.title = error.message || "";
            });
        }
    });

    detailButtons.forEach((button) => {
        button.addEventListener("click", () => openDetail(button));
    });
});
