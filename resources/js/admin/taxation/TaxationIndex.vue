<template>
    <div class="container-fluid" style="padding: 16px 18px">
        <TaxationHeader
            :show_button="show_run_button"
            @delete="handleDelete"
            @taxation-data-updated="fetchTaxation"
        />
        <!-- show skeleton while processing -->
        <TaxationSkeleton
            v-if="is_processing || is_loading"
            :batch_data="batch_data"
        />
        <LoadingAccordion
            v-if="is_processing"
            class="accordion"
            :batch_data="batch_data"
        />

        <template v-else>
            <TaxationCard :cards="taxationData.cards" />
            <TaxationBody
                :body="taxationData.body"
                :disable_recon="show_run_button"
                @refresh-forecast="handleForecastRefresh"
            />
            <TaxSettings :settings="taxationData.settings" />
        </template>
    </div>
</template>

<script>
import axios from "axios";

import TaxationHeader from "./parts/TaxationHeader.vue";
import TaxationCard from "./parts/TaxationCard.vue";
import TaxationBody from "./parts/TaxationBody.vue";
import TaxSettings from "./parts/TaxSettings.vue";
import TaxationSkeleton from "./components/TaxationSkeleton.vue";
import LoadingAccordion from "./components/LoadingAccordion.vue";

import { TaxationSettingModel } from "./TaxationModel";

export default {
    components: {
        TaxationHeader,
        TaxationCard,
        TaxationBody,
        TaxSettings,
        TaxationSkeleton,
        LoadingAccordion,
    },

    data() {
        return {
            taxationData: TaxationSettingModel(),

            is_processing: false,
            selectedYear: null,

            statusPollId: null,
            batch_id: null,

            progress: 0,

            finalized: false,

            show_run_button: false,
            taxation_id: null,
            is_loading: false,

            batch_data: [],
        };
    },

    methods: {
        fetchTaxation(year) {
            this.selectedYear = year;
            this.is_loading = true;
            this.finalized = false;
            this.batch_id = null;
            this.progress = 0;
            this.stopStatusPolling();

            axios
                .get("/admin/taxation", { params: { year: this.selectedYear } })
                .then((response) => {
                    const data = response.data || {};

                    if (data.id != null) {
                        this.show_run_button = false;
                        this.taxation_id = data.id;
                    } else {
                        this.show_run_button = true;
                        this.taxation_id = null;
                    }

                    if (data.status === "processing") {
                        this.batch_id = data.batch_id || null;

                        this.is_processing = !!this.batch_id;
                        return;
                    }

                    this.is_processing = false;
                    this.taxationData = TaxationSettingModel(data);
                })
                .catch((error) => {
                    console.error("Error fetching taxation data:", error);
                    this.is_processing = false;
                    this.batch_id = null;
                })
                .finally(() => {
                    this.is_loading = false;
                });
        },
        handleForecastRefresh() {
            const yearToRefresh = this.selectedYear || new Date().getFullYear();

            axios
                .get("/admin/taxation", { params: { year: yearToRefresh } })
                .then((response) => {
                    const data = response.data || {};

                    if (data.id != null) {
                        this.show_run_button = false;
                        this.taxation_id = data.id;
                    } else {
                        this.show_run_button = true;
                        this.taxation_id = null;
                    }

                    if (data.status === "processing") {
                        this.batch_id = data.batch_id || null;
                        this.is_processing = !!this.batch_id;
                        return;
                    }

                    this.is_processing = false;
                    this.taxationData = TaxationSettingModel(data);
                })
                .catch((error) => {
                    console.error("Error refreshing forecast data:", error);
                });
        },

        fetchBatchStatus() {
            if (!this.batch_id || this.finalized) return;

            axios
                .get("/admin/taxation/status", {
                    params: { batch_id: this.batch_id },
                })
                .then((response) => {
                    const data = response.data || {};

                    this.progress = Number(data.processed_percentage || 0);
                    this.batch_data = data;

                    if (data.is_finished) {
                        this.finalized = true;

                        this.is_processing = false;
                        this.stopStatusPolling();

                        this.fetchFinalTaxation();
                    }
                })
                .catch((error) => {
                    console.error("Error fetching taxation status:", error);
                    this.is_processing = false;
                    this.stopStatusPolling();
                });
        },

        fetchFinalTaxation() {
            axios
                .get("/admin/taxation", { params: { year: this.selectedYear } })
                .then((response) => {
                    const data = response.data || {};

                    if (data.status === "processing") {
                        return;
                    }

                    this.taxationData = TaxationSettingModel(data);
                    this.progress = 100;
                    this.batch_id = null;
                })
                .catch((error) => {
                    console.error("Error fetching final taxation data:", error);
                });
        },

        startStatusPolling() {
            if (!this.batch_id || this.finalized) return;

            this.stopStatusPolling();

            // poll every 3 seconds
            this.statusPollId = setInterval(() => {
                this.fetchBatchStatus();
            }, 3000);

            this.fetchBatchStatus();
        },

        stopStatusPolling() {
            if (this.statusPollId) {
                clearInterval(this.statusPollId);
                this.statusPollId = null;
            }
        },

        async handleDelete() {
            // Optional guard: block deleting while processing to avoid weird states
            if (this.is_processing) {
                Swal.fire({
                    title: "Still Processing",
                    text: "You can’t delete while the taxation is processing. Please wait for it to finish.",
                    icon: "info",
                });
                return;
            }

            if (!this.taxation_id) {
                Swal.fire({
                    title: "No Taxation Selected",
                    text: "There is no saved taxation record to delete for this year.",
                    icon: "info",
                });
                return;
            }

            const res = await Swal.fire({
                title: "Delete Taxation",
                html: `
            <div class="text-start">
                <div>This action cannot be undone.</div>
                <div class="mt-2">To confirm, type <b>DELETE</b> below.</div>
            </div>
        `,
                icon: "warning",
                input: "text",
                inputPlaceholder: "Type DELETE to confirm",
                inputAttributes: { autocapitalize: "off" },
                showCancelButton: true,
                confirmButtonText: "Delete",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#dc3545",
                reverseButtons: true,
                preConfirm: (value) => {
                    const v = String(value || "")
                        .trim()
                        .toUpperCase();
                    if (v !== "DELETE") {
                        Swal.showValidationMessage(
                            "Confirmation text must be DELETE",
                        );
                        return false;
                    }
                    return true;
                },
            });

            if (!res.isConfirmed) return;

            // UI lock + reset anything that could conflict
            this.is_loading = true;
            this.resetProcessingState(); // stop polling + clear batch/progress
            // (optional) also clear view instantly so user feels it changed
            this.taxationData = TaxationSettingModel();
            this.show_run_button = true;

            try {
                await axios.delete(
                    `/admin/taxation/${this.taxation_id}/delete`,
                );

                Swal.fire({
                    title: "Deleted!",
                    text: "The records have been deleted.",
                    icon: "success",
                });

                // clear the current id because it no longer exists
                this.taxation_id = null;

                // refresh based on selected year (this will set show_run_button properly)
                if (this.selectedYear) {
                    this.fetchTaxation(this.selectedYear);
                } else {
                    // fallback: keep blank state
                    this.is_loading = false;
                }
            } catch (err) {
                const msg =
                    err?.response?.data?.message ||
                    "Something went wrong while deleting.";

                Swal.fire({
                    title: "Error",
                    text: msg,
                    icon: "error",
                });
                this.is_loading = false;
            }
        },
        resetProcessingState() {
            this.stopStatusPolling();
            this.finalized = false;
            this.is_processing = false;
            this.batch_id = null;
            this.progress = 0;
            this.batch_data = [];
        },
    },

    watch: {
        is_processing(newVal) {
            if (newVal == true) this.startStatusPolling();
            else this.stopStatusPolling();
        },
    },

    beforeDestroy() {
        this.stopStatusPolling();
    },
};
</script>

<style lang="scss" scoped>
.accordion {
    position: fixed;
    bottom: 12px;
    right: 32px;
    width: 100%;
}
</style>
