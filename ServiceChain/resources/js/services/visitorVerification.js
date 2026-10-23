import {
    getVehicleFromBlockchain,
    getVehicleServiceHistoryFromBlockchain,
    getServiceRecordFromBlockchain,
} from "./serviceChainRead";

(() => {
    const root = document.querySelector("[data-visitor-verification]");
    if (!root) return;

    const form = root.querySelector("[data-vehicle-search-form]");
    const input = root.querySelector("[data-vehicle-id-input]");
    const searchButton = root.querySelector("[data-search-button]");
    const searchStatus = root.querySelector("[data-search-status]");
    const resultContainer = root.querySelector("[data-verification-results]");
    const vehicleSection = root.querySelector("[data-vehicle-section]");
    const serviceSection = root.querySelector("[data-service-section]");
    const serviceTableBody = root.querySelector("[data-service-table-body]");
    const emptyHistory = root.querySelector("[data-empty-history]");
    const detailDialog = root.querySelector("#visitorServiceDetailDialog");
    const hashDialog = root.querySelector("#visitorHashDialog");
    const hashForm = root.querySelector("[data-hash-form]");
    const hashInput = root.querySelector("[data-hash-input]");
    const hashStatus = root.querySelector("[data-hash-status]");

    const recordsById = new Map();
    const serviceMetadataById = new Map();
    let currentVehicle = null;
    let currentOwnerMetadata = null;
    let selectedServiceId = null;

    const missing = "Tidak tersedia pada data sistem";

    function setText(selector, value, scope = root) {
        const element = scope.querySelector(selector);
        if (element) element.textContent = value ?? "-";
    }

    function setSearchStatus(message, type = "info") {
        searchStatus.textContent = message;
        searchStatus.className = `visitor-alert visitor-alert-${type}`;
        searchStatus.hidden = !message;
    }

    function formatDate(timestamp) {
        const milliseconds = Number(timestamp) * 1000;
        if (!Number.isFinite(milliseconds)) return "-";
        const date = new Date(milliseconds);
        if (Number.isNaN(date.getTime())) return "-";
        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        }).format(date);
    }

    function formatMoney(value) {
        try {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                maximumFractionDigits: 0,
            }).format(BigInt(String(value)));
        } catch {
            const number = Number(value);
            return Number.isFinite(number)
                ? `Rp${new Intl.NumberFormat("id-ID", { maximumFractionDigits: 0 }).format(number)}`
                : "-";
        }
    }

    function formatNumber(value) {
        try {
            return new Intl.NumberFormat("id-ID").format(BigInt(String(value)));
        } catch {
            const number = Number(value);
            return Number.isFinite(number)
                ? new Intl.NumberFormat("id-ID").format(number)
                : "-";
        }
    }

    function shortenAddress(address) {
        if (!address) return "-";
        return `${address.slice(0, 8)}…${address.slice(-6)}`;
    }

    function setVehicleField(field, value) {
        const elements = root.querySelectorAll(
            `[data-vehicle-field="${field}"]`,
        );
        elements.forEach((element) => {
            element.textContent = value || missing;
        });
    }

    function clearResults() {
        resultContainer.hidden = true;
        vehicleSection.hidden = true;
        serviceSection.hidden = true;
        serviceTableBody.replaceChildren();
        emptyHistory.hidden = true;
        recordsById.clear();
        serviceMetadataById.clear();
        currentVehicle = null;
        currentOwnerMetadata = null;
    }

    async function fetchOffChainMetadata(vehicleId, ownerWallet) {
        const base = root.dataset.metadataUrlBase;
        const url = `${base}/${encodeURIComponent(vehicleId)}?owner_wallet=${encodeURIComponent(ownerWallet)}`;
        const response = await fetch(url, {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });

        if (!response.ok) {
            throw new Error(`Metadata HTTP ${response.status}`);
        }

        return response.json();
    }

    function renderVehicle(vehicle, metadata) {
        const owner = metadata?.owner ?? null;
        const vehicleLabel =
            `${vehicle.brand ?? ""} ${vehicle.model ?? ""}`.trim();

        setVehicleField("vehicleId", vehicle.vehicleId);
        setVehicleField("vehicleName", vehicleLabel || "Kendaraan");
        setVehicleField("brand", vehicle.brand);
        setVehicleField("model", vehicle.model);
        setVehicleField("color", vehicle.color);
        setVehicleField("year", vehicle.year ? String(vehicle.year) : "-");
        setVehicleField("ownerName", owner?.name || missing);
        setVehicleField("ownerWallet", vehicle.owner || missing);
        setVehicleField("ownerId", owner?.id || missing);

        const status = root.querySelector("[data-vehicle-status]");
        if (status) {
            status.textContent = vehicle.active ? "Aktif" : "Nonaktif";
            status.className = `visitor-status ${vehicle.active ? "is-active" : "is-inactive"}`;
        }

        vehicleSection.hidden = false;
    }

    function createCell(text, className = "") {
        const cell = document.createElement("td");
        if (className) cell.className = className;
        cell.textContent = text ?? "-";
        return cell;
    }

    function createActionButton(
        label,
        iconClass,
        action,
        serviceId,
        variant = "secondary",
    ) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `visitor-action-button visitor-action-${variant}`;
        button.dataset.serviceAction = action;
        button.dataset.serviceId = serviceId;

        const icon = document.createElement("i");
        icon.className = `bi ${iconClass}`;
        icon.setAttribute("aria-hidden", "true");

        const span = document.createElement("span");
        span.textContent = label;
        button.append(icon, span);
        return button;
    }

    function createServiceCell(record) {
        const cell = document.createElement("td");

        const strong = document.createElement("strong");
        strong.className = "visitor-service-vehicle";
        strong.textContent =
            record.vehicle ||
            `${record.brand ?? ""} ${record.model ?? ""}`.trim() ||
            "Kendaraan";

        const small = document.createElement("small");
        small.className = "visitor-service-id";
        small.textContent = `Service ID #${record.serviceId}`;

        const status = document.createElement("span");
        status.className =
            "visitor-service-status " +
            (record.cancelled ? "is-cancelled" : "is-active");

        status.textContent = record.cancelled ? "DIBATALKAN" : "TERCATAT";

        cell.append(strong, small, status);

        return cell;
    }

    function renderServiceRows(records) {
        serviceTableBody.replaceChildren();
        recordsById.clear();

        if (!records.length) {
            emptyHistory.hidden = false;
            serviceSection.hidden = false;
            return;
        }

        emptyHistory.hidden = true;
        for (const record of records) {
            recordsById.set(String(record.serviceId), record);
            const row = document.createElement("tr");

            if (record.loadError) {
                row.append(
                    createCell(`Service ID #${record.serviceId}`),
                    createCell("Data tidak dapat dimuat"),
                    createCell("-"),
                    createCell("-"),
                    createCell("-"),
                );
                const actionCell = document.createElement("td");
                actionCell.textContent = "Tidak tersedia";
                row.append(actionCell);
                serviceTableBody.append(row);
                continue;
            }

            row.append(
                createServiceCell(record),
                createCell(record.serviceType),
                createCell(formatDate(record.serviceDate)),
                createCell(`${formatNumber(record.mileage)} km`),
                createCell(formatMoney(record.cost)),
            );

            const actionCell = document.createElement("td");
            actionCell.className = "visitor-action-cell";
            actionCell.append(
                createActionButton(
                    "Detail",
                    "bi-eye",
                    "detail",
                    String(record.serviceId),
                ),
                createActionButton(
                    "Verifikasi",
                    "bi-shield-check",
                    "verify",
                    String(record.serviceId),
                    "primary",
                ),
            );
            row.append(actionCell);
            serviceTableBody.append(row);
        }

        serviceSection.hidden = false;
    }

    function setDialogField(field, value) {
        const element = detailDialog.querySelector(
            `[data-detail-field="${field}"]`,
        );
        if (element) element.textContent = value || missing;
    }

    function openServiceDetail(serviceId) {
        const record = recordsById.get(String(serviceId));
        if (!record || record.loadError) return;

        const currentOwner = currentOwnerMetadata;
        const metadata = serviceMetadataById.get(String(serviceId));
        const vehicleLabel =
            `${record.brand ?? ""} ${record.model ?? ""}`.trim();

        setDialogField("serviceId", record.serviceId);
        setDialogField("vehicleId", record.vehicleId);
        setDialogField("vehicle", vehicleLabel);
        setDialogField("ownerName", currentOwner?.name || missing);
        setDialogField("ownerId", currentOwner?.id || missing);
        setDialogField("serviceDate", formatDate(record.serviceDate));
        setDialogField("serviceType", record.serviceType);
        setDialogField("cost", formatMoney(record.cost));
        setDialogField("mileage", `${formatNumber(record.mileage)} km`);
        setDialogField("workshopName", metadata?.workshopName || missing);
        setDialogField("workshopAddress", metadata?.workshopAddress || missing);
        setDialogField("workshopWallet", record.workshop);
        setDialogField("description", record.description);
        setDialogField(
            "recordStatus",
            record.cancelled ? "Dibatalkan" : "Tercatat di blockchain",
        );
        const cancelledWarning = detailDialog.querySelector(
            "[data-cancelled-warning]",
        );

        if (cancelledWarning) {
            cancelledWarning.hidden = !record.cancelled;

            cancelledWarning.textContent = record.cancelled
                ? "PERINGATAN: CATATAN SERVIS INI TELAH DIBATALKAN. " +
                  "Detail tetap ditampilkan sebagai jejak audit blockchain."
                : "";
        }

        if (typeof detailDialog.showModal === "function")
            detailDialog.showModal();
    }

    function openHashVerification(serviceId) {
        const record = recordsById.get(String(serviceId));
        if (!record || record.loadError) return;

        selectedServiceId = String(serviceId);
        setText(
            "[data-hash-service-id]",
            `Service ID #${serviceId}`,
            hashDialog,
        );
        hashInput.value = "";
        hashStatus.hidden = true;
        hashStatus.textContent = "";
        hashStatus.className = "visitor-alert";
        if (typeof hashDialog.showModal === "function") hashDialog.showModal();
        hashInput.focus();
    }

    function setHashStatus(message, type) {
        hashStatus.textContent = message;
        hashStatus.className = `visitor-alert visitor-alert-${type}`;
        hashStatus.hidden = false;
    }

    async function readAndDisplayVehicle(vehicleId) {
        clearResults();
        try {
            setSearchStatus(
                "Menghubungkan ke jaringan blockchain dan mencari kendaraan…",
                "info",
            );
            const vehicle = await getVehicleFromBlockchain(vehicleId);
            currentVehicle = vehicle;

            let metadata = { owner: null, services: [] };
            try {
                metadata = await fetchOffChainMetadata(
                    vehicleId,
                    vehicle.owner,
                );
            } catch (error) {
                console.warn("Metadata owner/bengkel tidak tersedia:", error);
            }

            currentOwnerMetadata = metadata.owner ?? null;
            for (const item of metadata.services ?? []) {
                serviceMetadataById.set(String(item.serviceId), item);
            }

            renderVehicle(vehicle, metadata);
            resultContainer.hidden = false;
            setSearchStatus(
                "Kendaraan ditemukan di blockchain. Riwayat servis sedang dimuat…",
                "success",
            );

            const serviceIds =
                await getVehicleServiceHistoryFromBlockchain(vehicleId);
            const records = [];
            const batchSize = 8;

            for (let index = 0; index < serviceIds.length; index += batchSize) {
                const batch = serviceIds.slice(index, index + batchSize);
                const loaded = await Promise.all(
                    batch.map(async (serviceId) => {
                        try {
                            return await getServiceRecordFromBlockchain(
                                serviceId,
                            );
                        } catch (error) {
                            console.error(
                                `Gagal membaca Service ID ${serviceId}:`,
                                error,
                            );
                            return {
                                serviceId: String(serviceId),
                                loadError: true,
                            };
                        }
                    }),
                );
                records.push(...loaded);
            }

            records.sort((a, b) => {
                if (a.loadError || b.loadError) return 0;
                const dateA = BigInt(String(a.serviceDate));
                const dateB = BigInt(String(b.serviceDate));
                return dateA === dateB ? 0 : dateA > dateB ? -1 : 1;
            });

            renderServiceRows(records);
            const failed = records.filter((record) => record.loadError).length;
            const count = records.length;
            setSearchStatus(
                failed
                    ? `Kendaraan ditemukan dengan ${count} ID catatan; ${failed} catatan gagal dimuat. Coba muat ulang atau periksa koneksi blockchain.`
                    : `Verifikasi selesai. Ditemukan ${count} catatan servis di blockchain.`,
                failed ? "warning" : "success",
            );
        } catch (error) {
            console.error("Pencarian kendaraan gagal:", error);
            clearResults();
            setSearchStatus(
                "Kendaraan tidak ditemukan di blockchain atau jaringan blockchain sedang tidak dapat diakses. Periksa kembali Vehicle ID dan koneksi RPC.",
                "error",
            );
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const vehicleId = input.value.trim();

        if (!vehicleId) {
            setSearchStatus("Masukkan Vehicle ID terlebih dahulu.", "error");
            input.focus();
            return;
        }

        searchButton.disabled = true;
        searchButton.classList.add("is-loading");
        const originalButtonText =
            searchButton.dataset.originalText ||
            searchButton.textContent.trim();
        searchButton.dataset.originalText = originalButtonText;
        searchButton.textContent = "Mencari…";

        try {
            await readAndDisplayVehicle(vehicleId);
        } finally {
            searchButton.disabled = false;
            searchButton.classList.remove("is-loading");
            searchButton.textContent = originalButtonText;
        }
    });

    root.addEventListener("click", (event) => {
        const actionButton = event.target.closest("[data-service-action]");
        if (!actionButton || !root.contains(actionButton)) return;

        if (actionButton.dataset.serviceAction === "detail") {
            openServiceDetail(actionButton.dataset.serviceId);
        } else if (actionButton.dataset.serviceAction === "verify") {
            openHashVerification(actionButton.dataset.serviceId);
        }
    });

    root.querySelectorAll("[data-dialog-close]").forEach((button) => {
        button.addEventListener("click", () => {
            const dialog = button.closest("dialog");
            if (dialog?.open) dialog.close();
        });
    });

    hashForm.addEventListener("submit", (event) => {
        event.preventDefault();
        const record = recordsById.get(String(selectedServiceId));
        const suppliedHash = hashInput.value.trim();

        if (!record || record.loadError || !record.serviceHash) {
            setHashStatus(
                "Data hash dari blockchain belum tersedia. Silakan muat ulang catatan servis.",
                "error",
            );
            return;
        }

        if (!/^0x[a-fA-F0-9]{64}$/.test(suppliedHash)) {
            setHashStatus(
                "Format hash tidak valid. Masukkan hash lengkap dengan awalan 0x dan 64 karakter hexadecimal.",
                "error",
            );
            return;
        }

        if (
            suppliedHash.toLowerCase() ===
            String(record.serviceHash).toLowerCase()
        ) {
            if (record.cancelled) {
                setHashStatus(
                    "Hash cocok dengan hash yang tersimpan di blockchain, " +
                        "tetapi catatan servis ini berstatus DIBATALKAN. " +
                        "Kecocokan hash tidak berarti servis masih aktif.",
                    "warning",
                );
            } else {
                setHashStatus(
                    "Verifikasi berhasil. Hash cocok dengan hash yang tersimpan di blockchain.",
                    "success",
                );
            }
        } else {
            setHashStatus(
                "Verifikasi gagal, kemungkinan hash yang Anda masukkan salah " +
                    "atau riwayat servis memang sudah diubah dan menjadi tidak valid.",
                "error",
            );
        }
    });
})();
