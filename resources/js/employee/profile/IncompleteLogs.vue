<template>
    <div
        v-if="show"
        class="incomplete-logs-component"
        ref="wrapper"
        :class="{ dragging: isDragging }"
        :style="{ left: pos.x + 'px', top: pos.y + 'px' }"
    >
        <div class="card" style="overflow: hidden">
            <!-- HEADER -->
            <div
                class="card-header drag-header d-flex justify-content-between align-items-center"
                @pointerdown="startDrag"
            >
                <div class="d-flex align-items-center">
                    <div
                        style="width: 30px; height: 30px"
                        class="bg-warning rounded-2 me-3 d-flex justify-content-center align-items-center"
                    >
                        <i class="fa-solid fa-arrows-up-down-left-right"></i>
                    </div>
                    <div class="fw-bold text-warning text-uppercase">
                        Timelog Discrepancy!
                    </div>
                </div>

                <button class="btn btn-transparent" @click.stop="handleHide">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- BODY -->
            <div class="card-body p-0">
                <div class="accordion">
                    <div class="accordion-item rounded-0">
                        <button
                            class="accordion-button collapsed text-uppercase fw-bold py-2 rounded-0"
                            style="font-size: 10px"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseLogs"
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
                                                <th class="px-2 p-2 text-uppercase fw-bold" style="font-size: 10px">
                                                    Date
                                                </th>
                                                <th class="px-2 p-2 text-uppercase fw-bold" style="font-size: 10px">
                                                    In
                                                </th>
                                                <th class="px-2 p-2 text-uppercase fw-bold" style="font-size: 10px">
                                                    Out
                                                </th>
                                                <th class="px-2 p-2 text-uppercase fw-bold" style="font-size: 10px">
                                                    Remarks
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="log in incompleteLogs"
                                                :key="log.date + log.shift_id"
                                            >
                                                <td class="fw-bold px-2 py-2" style="font-size: 11px">
                                                    {{ formatDate(log.date) }}
                                                </td>
                                                <td class="fw-bold px-2 py-2" style="font-size: 11px">
                                                    {{ log.time_in || '' }}
                                                </td>
                                                <td class="fw-bold px-2 py-2" style="font-size: 11px">
                                                    {{ log.time_out || '' }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2 text-uppercase"
                                                    style="font-size: 11px"
                                                    :class="log.remarks.includes('today') ? 'text-primary' : 'text-danger'"
                                                >
                                                    {{ log.remarks.join(', ') }}
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

/* STORAGE KEYS */
const POSITION_KEY = "incomplete-logs-position";
const ACCORDION_KEY = "incomplete-logs-accordion";
const HIDE_KEY = "incomplete-logs-hidden";
const HIDE_DATE_KEY = "incomplete-logs-hide-date";

export default {
    data() {
        return {
            show: true,

            incompleteLogs: [],
            loading: false,

            // Dragging
            isDragging: false,
            offset: { x: 0, y: 0 },

            pos: { x: window.innerWidth - 470, y: 200 },
            collapseInstance: null,
        };
    },

    mounted() {
        this.restoreVisibility();
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
           VISIBILITY (DAILY RESET)
        ====================== */
        restoreVisibility() {
            const today = new Date().toDateString();
            const hidden = localStorage.getItem(HIDE_KEY);
            const hideDate = localStorage.getItem(HIDE_DATE_KEY);

            if (hidden === "true" && hideDate === today) {
                this.show = false;
            } else {
                localStorage.removeItem(HIDE_KEY);
                localStorage.removeItem(HIDE_DATE_KEY);
                this.show = true;
            }
        },

        handleHide() {
            this.show = false;
            localStorage.setItem(HIDE_KEY, "true");
            localStorage.setItem(HIDE_DATE_KEY, new Date().toDateString());
        },

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
                this.pos.x = x;
                this.pos.y = y;
            } catch {}
        },

        savePosition() {
            localStorage.setItem(POSITION_KEY, JSON.stringify(this.pos));
        },

        /* =====================
           ACCORDION
        ====================== */
        initAccordion() {
            const el = this.$refs.collapse;
            if (!el) return;

            this.collapseInstance = new Collapse(el, { toggle: false });

            el.addEventListener("shown.bs.collapse", () => {
                localStorage.setItem(ACCORDION_KEY, "open");
            });

            el.addEventListener("hidden.bs.collapse", () => {
                localStorage.setItem(ACCORDION_KEY, "closed");
            });
        },

        restoreAccordionState() {
            if (localStorage.getItem(ACCORDION_KEY) === "open") {
                this.collapseInstance?.show();
            }
        },

        destroyAccordion() {
            this.collapseInstance?.dispose();
            this.collapseInstance = null;
        },

        /* =====================
           DRAGGING
        ====================== */
        startDrag(e) {
            e.preventDefault();

            const rect = this.$refs.wrapper.getBoundingClientRect();
            this.isDragging = true;

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
    z-index: 999;
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
