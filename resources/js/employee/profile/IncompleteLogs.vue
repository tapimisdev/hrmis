<template>
    <div
        v-if="show"
        class="incomplete-logs-component"
        ref="wrapper"
        :class="{ dragging: isDragging }"
        :style="
            isMobile
                ? { top: '30%', left: '50%', transform: 'translateX(-50%)' }
                : { left: pos.x + 'px', top: pos.y + 'px', transform: 'none' }
        "
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

                <button class="btn btn-transparent" @click.stop="handleToggle">
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
                                <div v-if="loading" class="text-center text-muted py-2">
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
                                                    class="fw-bold px-2 py-2"
                                                    style="font-size: 11px"
                                                >
                                                    {{ formatDate(log.date) }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2"
                                                    style="font-size: 11px"
                                                >
                                                    {{ log.time_in || "" }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2"
                                                    style="font-size: 11px"
                                                >
                                                    {{ log.time_out || "" }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2 text-uppercase"
                                                    style="font-size: 11px"
                                                    :class="
                                                        log.remarks.includes(
                                                            'today'
                                                        )
                                                            ? 'text-success'
                                                            : 'text-danger'
                                                    "
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
import { watch } from "vue";
import { Collapse } from "bootstrap";

const POSITION_KEY = "incomplete-logs-position";
const ACCORDION_KEY = "incomplete-logs-accordion";
const HIDE_KEY = "hide_timelog_discrepancy";
const HIDE_DATE_KEY = "hide_timelog_discrepancy_date";

export default {
    data() {
        return {
            show: true,
            incompleteLogs: [],
            loading: false,
            isDragging: false,
            offset: { x: 0, y: 0 },
            pos: { x: window.innerWidth - 470, y: 200 },
            collapseInstance: null,
            windowWidth: window.innerWidth,
        };
    },

    computed: {
        isMobile() {
            return this.windowWidth < 768;
        },
        mobilePos() {
            return {
                x: (window.innerWidth - Math.min(window.innerWidth, 450)) / 2,
                y: window.innerHeight / 2,
            };
        },
    },

    mounted() {
        this.syncVisibility();
        this.loadPosition();
        this.fetchIncompleteLogs();

        this.$nextTick(() => {
            this.initAccordion();
            this.restoreAccordionState();
        });

        this.$watch(
            () => window.clockTriggers?.reload,
            () => {
                this.handleTriggerClocking();
                window.clockTriggers.reload = false;
            }
        );

        window.addEventListener("timelog-toggle", this.syncVisibility);
        window.addEventListener("resize", this.onResize);
        window.addEventListener("orientationchange", this.onResize);

        // Center on mobile initially if no saved position
        if (this.isMobile && !localStorage.getItem(POSITION_KEY)) {
            this.pos = { ...this.mobilePos };
        }
    },

    beforeUnmount() {
        this.destroyAccordion();
        window.removeEventListener("timelog-toggle", this.syncVisibility);
        window.removeEventListener("resize", this.onResize);
        window.removeEventListener("orientationchange", this.onResize);
    },

    methods: {
        onResize() {
            this.windowWidth = window.innerWidth;
            // On mobile, if user has not dragged, start centered
            if (this.isMobile && !localStorage.getItem(POSITION_KEY)) {
                this.pos = { ...this.mobilePos };
            }
        },

        syncVisibility() {
            const today = new Date().toDateString();
            const hidden = localStorage.getItem(HIDE_KEY);
            const hideDate = localStorage.getItem(HIDE_DATE_KEY);
            this.show = !(hidden === "true" && hideDate === today);
        },

        handleToggle() {
            this.show = false;
            localStorage.setItem(HIDE_KEY, "true");
            localStorage.setItem(HIDE_DATE_KEY, new Date().toDateString());
            window.dispatchEvent(new Event("timelog-toggle"));
        },

        handleTriggerClocking() {
            this.fetchIncompleteLogs();
        },

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
                this.incompleteLogs = Array.isArray(res.data) ? res.data : [];
            } catch (err) {
                console.error("Fetch incomplete logs failed:", err);
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

        loadPosition() {
            const saved = localStorage.getItem(POSITION_KEY);
            if (!saved) return;
            try {
                const { x, y } = JSON.parse(saved);
                this.pos = { x, y };
            } catch {
                localStorage.removeItem(POSITION_KEY);
            }
        },

        savePosition() {
            if (this.isMobile) return; // do not save on mobile
            localStorage.setItem(POSITION_KEY, JSON.stringify(this.pos));
        },

        initAccordion() {
            const el = this.$refs.collapse;
            if (!el) return;
            this.collapseInstance = new Collapse(el, { toggle: false });
            el.addEventListener("shown.bs.collapse", this.onAccordionOpen);
            el.addEventListener("hidden.bs.collapse", this.onAccordionClose);
        },

        restoreAccordionState() {
            if (localStorage.getItem(ACCORDION_KEY) === "open") {
                this.collapseInstance?.show();
            }
        },

        onAccordionOpen() {
            localStorage.setItem(ACCORDION_KEY, "open");
        },

        onAccordionClose() {
            localStorage.setItem(ACCORDION_KEY, "closed");
        },

        destroyAccordion() {
            if (!this.collapseInstance) return;
            this.collapseInstance.dispose();
            this.collapseInstance = null;
        },

        startDrag(e) {
            if (this.isMobile) return; // disable dragging on mobile
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
            const el = this.$refs.wrapper;
            if (!el) return;
            const maxX = window.innerWidth - el.offsetWidth;
            const maxY = window.innerHeight - el.offsetHeight;
            this.pos.x = Math.min(Math.max(0, e.clientX - this.offset.x), maxX);
            this.pos.y = Math.min(Math.max(0, e.clientY - this.offset.y), maxY);
        },

        stopDrag() {
            if (!this.isDragging) return;
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

/* MOBILE */
@media (max-width: 768px) {
    .incomplete-logs-component {
        position: fixed;
        top: 20% !important;
        width: 100%;
        max-width: 450px;
        padding: 0 10px;
    }
}
</style>
