<template>
    <div
        class="incomplete-logs-component"
        ref="wrapper"
        :class="{ dragging: isDragging }"
        :style="{ left: pos.x + 'px', top: pos.y + 'px' }"
    >
        <div class="card" style="overflow: hidden">
            <div
                class="card-header drag-header d-flex justify-content-between align-items-center gap: 10px;"
                @pointerdown="startDrag"
            >
                <div class="d-flex align-items-center">
                    <div style="width: 30px; height: 30px;" class="bg-warning rounded-2 me-3 d-flex justify-content-center align-items-center">
                        <i class="fa-solid fa-arrows-up-down-left-right"></i>
                    </div>
                    <div class="fw-bold text-warning text-uppercase">
                        Timelog Discrepancy!
                    </div>
                </div>
                <div>
                    <button class="btn btn-transparent">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="accordion">
                    <div class="accordion-item rounded-0">
                        <button
                            ref="accordionBtn"
                            class="accordion-button collapsed text-uppercase fw-bold py-2 rounded-0"
                            style="font-size: 10px"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseLogs"
                            aria-expanded="false"
                        >
                            Show Details
                        </button>

                        <div
                            id="collapseLogs"
                            ref="collapse"
                            class="accordion-collapse collapse"
                        >
                            <div class="accordion-body p-3">
                                <div v-if="loading">
                                    Loading incomplete logs...
                                </div>

                                <div
                                    v-else-if="incompleteLogs.length"
                                    class="table-responsive"
                                >
                                    <table
                                        class="table table-sm table-bordered align-middle mb-0"
                                        style="font-size: 12px"
                                    >
                                        <thead class="table-light">
                                            <tr>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                >
                                                    Date
                                                </th>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                >
                                                    In
                                                </th>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                >
                                                    Out
                                                </th>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                >
                                                    Remarks
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="log in incompleteLogs"
                                                :key="log.date + log.shift_id"
                                            >
                                                <td
                                                    style="font-size: 11px"
                                                    class="fw-bold px-2 py-2"
                                                >
                                                    {{ formatDate(log.date) }}
                                                </td>
                                                <td
                                                    style="font-size: 11px"
                                                    class="fw-bold px-2 py-2"
                                                >
                                                    {{ log.time_in || "" }}
                                                </td>
                                                <td
                                                    style="font-size: 11px"
                                                    class="fw-bold px-2 py-2"
                                                >
                                                    {{ log.time_out || "" }}
                                                </td>
                                                <td
                                                    style="font-size: 11px"
                                                    class="fw-bold px-2 py-2 text-uppercase text-danger"
                                                >
                                                    {{ log.remarks.join(", ") }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div v-else class="text-muted text-center py-2">
                                    No incomplete logs found.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import { Collapse } from "bootstrap";

const POSITION_KEY = "incomplete-logs-position";
const ACCORDION_KEY = "incomplete-logs-accordion";

export default {
    data() {
        return {
            incompleteLogs: [],
            loading: false,

            // Drag
            isDragging: false,
            start: { x: 0, y: 0 },
            offset: { x: 0, y: 0 },

            pos: { x: window.innerWidth - 470, y: 200 },

            collapseInstance: null,
        };
    },

    mounted() {
        this.loadPosition();
        this.fetchIncompleteLogs();

        this.$nextTick(() => {
            this.initAccordion();
            this.restoreAccordionState();
        });
    },

    beforeUnmount() {
        this.destroyAccordion();
    },

    methods: {
        /* =====================
           API
        ====================== */
        async fetchIncompleteLogs() {
            this.loading = true;
            try {
                const token = localStorage.getItem("auth_token");
                const res = await axios.get("/api/employee/incomplete-logs", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });
                this.incompleteLogs = res.data || [];
            } catch (err) {
                console.error(err);
                alert("Failed to load incomplete logs");
            } finally {
                this.loading = false;
            }
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString(undefined, {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        },

        /* =====================
           POSITION
        ====================== */
        loadPosition() {
            const saved = localStorage.getItem(POSITION_KEY);
            if (!saved) return;

            try {
                const { x, y } = JSON.parse(saved);

                const maxX = window.innerWidth - 450;
                const maxY = window.innerHeight - 300;

                this.pos.x = Math.min(Math.max(0, x), maxX);
                this.pos.y = Math.min(Math.max(0, y), maxY);
            } catch {
                console.warn("Invalid saved position");
            }
        },

        savePosition() {
            localStorage.setItem(POSITION_KEY, JSON.stringify(this.pos));
        },

        /* =====================
           ACCORDION (PROPER WAY)
        ====================== */
        initAccordion() {
            const el = this.$refs.collapse;
            if (!el) return;

            this.collapseInstance = new Collapse(el, {
                toggle: false,
            });

            el.addEventListener("shown.bs.collapse", () => {
                localStorage.setItem(ACCORDION_KEY, "open");
            });

            el.addEventListener("hidden.bs.collapse", () => {
                localStorage.setItem(ACCORDION_KEY, "closed");
            });
        },

        restoreAccordionState() {
            const state = localStorage.getItem(ACCORDION_KEY);
            if (state === "open" && this.collapseInstance) {
                this.collapseInstance.show();
            }
        },

        destroyAccordion() {
            if (this.collapseInstance) {
                this.collapseInstance.dispose();
                this.collapseInstance = null;
            }
        },

        /* =====================
           DRAGGING
        ====================== */
        startDrag(e) {
            e.preventDefault();

            const rect = this.$refs.wrapper.getBoundingClientRect();

            this.isDragging = true;
            this.start.x = e.clientX;
            this.start.y = e.clientY;

            this.offset.x = e.clientX - rect.left;
            this.offset.y = e.clientY - rect.top;

            window.addEventListener("pointermove", this.onDrag);
            window.addEventListener("pointerup", this.stopDrag);
        },

        onDrag(e) {
            if (!this.isDragging) return;

            const maxX = window.innerWidth - this.$refs.wrapper.offsetWidth;
            const maxY = window.innerHeight - this.$refs.wrapper.offsetHeight;

            this.pos.x = Math.min(Math.max(0, e.clientX - this.offset.x), maxX);
            this.pos.y = Math.min(Math.max(0, e.clientY - this.offset.y), maxY);
        },

        stopDrag() {
            this.isDragging = false;
            this.savePosition();

            window.removeEventListener("pointermove", this.onDrag);
            window.removeEventListener("pointerup", this.stopDrag);
        },
    },
};
</script>

<style scoped>
.incomplete-logs-component {
    position: fixed;
    width: 450px;
    z-index: 9999;
    user-select: none;
}

/* HEADER */
.drag-header {
    cursor: grab;
}
.drag-header:active {
    cursor: grabbing;
}

/* DRAG STATE */
.dragging {
    opacity: 0.9;
    cursor: grabbing;
}

/* Remove accordion focus outline */
.accordion-button {
    outline: none;
    box-shadow: none !important;
}
</style>
