import {
    getVehicleFromBlockchain,
    getServiceRecordFromBlockchain,
} from "./serviceChainRead";

const text = (element, value) => {
    if (element)
        element.textContent =
            value == null || value === "" ? "-" : String(value);
};

const asNumber = (value) => {
    if (typeof value === "bigint") return Number(value);
    if (value && typeof value.toString === "function")
        return Number(value.toString());
    return Number(value ?? 0);
};

const formatDate = (value) => {
    const timestamp = asNumber(value);
    if (!Number.isFinite(timestamp) || timestamp <= 0) return "-";
    const milliseconds =
        timestamp < 100000000000 ? timestamp * 1000 : timestamp;
    const date = new Date(milliseconds);
    if (Number.isNaN(date.getTime())) return "-";
    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const formatMileage = (value) => {
    const number = asNumber(value);
    return Number.isFinite(number)
        ? `${new Intl.NumberFormat("id-ID").format(number)} km`
        : "-";
};

const formatCost = (value) => {
    const number = asNumber(value);
    return Number.isFinite(number)
        ? `Rp${new Intl.NumberFormat("id-ID", { maximumFractionDigits: 0 }).format(number)}`
        : "-";
};

async function populateOwnerVehicleCards() {
    const cards = Array.from(document.querySelectorAll(".vehicle-card"));
    const filter = document.getElementById("vehicleStatusFilter");
    const emptyAfterFilter = document.querySelector(".js-vehicle-filter-empty");

    const applyFilter = () => {
        const selected = filter?.value || "all";
        let visible = 0;

        cards.forEach((card) => {
            const chainStatus = card.dataset.chainStatus || "unknown";
            const matches = selected === "all" || chainStatus === selected;
            card.hidden = !matches;
            if (matches) visible += 1;
        });

        if (emptyAfterFilter)
            emptyAfterFilter.hidden = visible > 0 || cards.length === 0;
    };

    if (filter) filter.addEventListener("change", applyFilter);

    await Promise.all(
        cards.map(async (card) => {
            const button = card.querySelector(".vehicle-detail-button");
            const vehicleId =
                button?.dataset.vehicleId || card.dataset.vehicleId;
            if (!vehicleId) return;

            try {
                const vehicle = await getVehicleFromBlockchain(vehicleId);
                const active = Boolean(vehicle?.active && vehicle?.registered);
                const statusValue = active ? "ACTIVE" : "INACTIVE";

                card.dataset.chainStatus = statusValue;
                text(
                    card.querySelector(".js-chain-vehicle-year"),
                    vehicle?.year,
                );

                const status = card.querySelector(".js-chain-vehicle-status");
                text(status, active ? "Aktif" : "Tidak aktif");
                if (status) {
                    status.dataset.chainStatus = statusValue;
                    status.classList.toggle("active", active);
                    status.classList.toggle("inactive", !active);
                }

                if (button) {
                    button.dataset.year = String(vehicle?.year ?? "");
                    button.dataset.color = String(vehicle?.color ?? "");
                    button.dataset.status = statusValue;
                    button.dataset.ownerWallet = String(vehicle?.owner ?? "");
                }
            } catch (error) {
                card.dataset.chainStatus = "unknown";
                text(
                    card.querySelector(".js-chain-vehicle-year"),
                    "Tidak tersedia",
                );
                text(
                    card.querySelector(".js-chain-vehicle-status"),
                    "Gagal memuat",
                );
                console.error(
                    `Gagal memuat kendaraan ${vehicleId} dari blockchain:`,
                    error,
                );
            }
        }),
    );

    applyFilter();
}

async function populateOwnerDashboard() {
    const activeLabel = document.getElementById("activeVehicleCount");
    if (activeLabel)
        text(activeLabel, "Status kendaraan diverifikasi melalui blockchain");

    const vehicleRows = Array.from(
        document.querySelectorAll(".js-owner-dashboard-vehicle"),
    );
    await Promise.all(
        vehicleRows.map(async (row) => {
            const vehicleId = row.dataset.vehicleId;
            if (!vehicleId) return;

            try {
                const vehicle = await getVehicleFromBlockchain(vehicleId);
                text(
                    row.querySelector(".js-chain-vehicle-year"),
                    vehicle?.year,
                );
                text(
                    row.querySelector(".js-chain-vehicle-status"),
                    vehicle?.active && vehicle?.registered
                        ? "Aktif"
                        : "Tidak aktif",
                );
            } catch (error) {
                text(
                    row.querySelector(".js-chain-vehicle-year"),
                    "Tidak tersedia",
                );
                text(
                    row.querySelector(".js-chain-vehicle-status"),
                    "Gagal memuat",
                );
                console.error(
                    `Gagal memuat kendaraan ${vehicleId} dari blockchain:`,
                    error,
                );
            }
        }),
    );

    const serviceRows = Array.from(
        document.querySelectorAll(".js-owner-dashboard-service"),
    );
    await Promise.all(
        serviceRows.map(async (row) => {
            const serviceId = row.dataset.serviceId;
            if (!serviceId) return;

            try {
                const record = await getServiceRecordFromBlockchain(serviceId);
                text(
                    row.querySelector(".js-chain-service-type"),
                    record?.serviceType,
                );
                text(
                    row.querySelector(".js-chain-service-date"),
                    formatDate(record?.serviceDate),
                );
            } catch (error) {
                text(
                    row.querySelector(".js-chain-service-type"),
                    "Gagal memuat dari blockchain",
                );
                text(row.querySelector(".js-chain-service-date"), "-");
                console.error(
                    `Gagal memuat servis ${serviceId} dari blockchain:`,
                    error,
                );
            }
        }),
    );
}

async function populateWorkshopDashboard() {
    const rows = Array.from(
        document.querySelectorAll(".js-workshop-dashboard-service"),
    );
    await Promise.all(
        rows.map(async (row) => {
            const serviceId = row.dataset.serviceId;
            if (!serviceId) return;

            try {
                const record = await getServiceRecordFromBlockchain(serviceId);
                text(
                    row.querySelector(".js-chain-service-type"),
                    record?.serviceType,
                );
                text(
                    row.querySelector(".js-chain-service-date"),
                    formatDate(record?.serviceDate),
                );
                text(
                    row.querySelector(".js-chain-service-mileage"),
                    formatMileage(record?.mileage),
                );
            } catch (error) {
                text(
                    row.querySelector(".js-chain-service-type"),
                    "Gagal memuat",
                );
                text(row.querySelector(".js-chain-service-date"), "-");
                text(row.querySelector(".js-chain-service-mileage"), "-");
                console.error(
                    `Gagal memuat servis ${serviceId} dari blockchain:`,
                    error,
                );
            }
        }),
    );
}

document.addEventListener("DOMContentLoaded", () => {
    void populateOwnerVehicleCards();
    void populateOwnerDashboard();
    void populateWorkshopDashboard();
});

void formatCost;
