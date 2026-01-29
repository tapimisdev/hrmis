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
                        <i class="fa-solid fa-arrows-up-down-left-right" aria-hidden="true"></i>
                    </div>
                    <div class="fw-bold text-warning text-uppercase">
                        Timelog Discrepancy!
                    </div>
                </div>

                <button
                    class="btn btn-transparent"
                    @click.stop="handleToggle"
                    aria-label="Close timelog discrepancy"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
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
                            aria-expanded="false"
                            aria-controls="collapseLogs"
                        >
                            Show Details
                        </button>

                        <div
                            id="collapseLogs"
                            ref="collapse"
                            class="accordion-collapse collapse"
                        >
                            <div class="accordion-body p-3">
                                <div
                                    v-if="loading"
                                    class="text-center text-muted py-2"
                                >
                                    Loading incomplete logs...
                                </div>

                                <div
                                    v-else-if="incompleteLogs.length"
                                    class="table-responsive"
                                >
                                    <table
                                        class="table table-sm table-bordered align-middle mb-0"
                                        style="font-size: 12px"
                                        role="table"
                                        aria-label="Incomplete logs table"
                                    >
                                        <thead class="table-light">
                                            <tr>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                    scope="col"
                                                >
                                                    Date
                                                </th>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                    scope="col"
                                                >
                                                    In
                                                </th>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                    scope="col"
                                                >
                                                    Out
                                                </th>
                                                <th
                                                    class="px-2 p-2 text-uppercase fw-bold"
                                                    style="font-size: 10px"
                                                    scope="col"
                                                >
                                                    Remarks
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="log in incompleteLogs"
                                                :key="log.date + log.shift_id"
                                                role="row"
                                            >
                                                <td
                                                    class="fw-bold px-2 py-2"
                                                    style="font-size: 11px"
                                                    role="cell"
                                                >
                                                    {{ formatDate(log.date) }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2"
                                                    style="font-size: 11px"
                                                    role="cell"
                                                >
                                                    {{ log.time_in || "" }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2"
                                                    style="font-size: 11px"
                                                    role="cell"
                                                >
                                                    {{ log.time_out || "" }}
                                                </td>
                                                <td
                                                    class="fw-bold px-2 py-2 text-uppercase"
                                                    style="font-size: 11px"
                                                    :class="
                                                        log.remarks.includes('today')
                                                            ? 'text-success'
                                                            : 'text-danger'
                                                    "
                                                    role="cell"
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

// Local storage keys
const POSITION_KEY = "incomplete-logs-position";
const ACCORDION_KEY = "incomplete-logs-accordion";
const INCOMPLETE_KEY = "hide_timelog_discrepancy";
const INCOMPLETE_DATE_KEY = "hide_timelog_discrepancy_date";

export default {
    name: "IncompleteLogs",
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
            // Internal triggers to replace global window.clockTriggers
            clockTriggers: { reload: false },
        };
    },

    computed: {
        isMobile() {
            return this.windowWidth < 768;
        },
        mobilePos() {
            return {
                x: (window.innerWidth - Math.min(window.innerWidth, 450)) / 2,
                y: window.innerHeight * 0.3, // 30% from top for centering
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

        // Watch internal clock triggers (replaces global window.clockTriggers)
        this.$watch(
            () => this.clockTriggers.reload,
            (newVal) => {
                if (newVal) {
                    this.handleTriggerClocking();
                    this.clockTriggers.reload = false;
                }
            },
            { immediate: false }
        );

        // Event listeners
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
        if (typeof window !== "undefined") {
            window.removeEventListener("timelog-toggle", this.syncVisibility);
            window.removeEventListener("resize", this.onResize);
            window.removeEventListener("orientationchange", this.onResize);
        }
    },

    methods: {
        // Handle window resize/orientation change
        onResize() {
            this.windowWidth = window.innerWidth;
            if (this.isMobile && !localStorage.getItem(POSITION_KEY)) {
                this.pos = { ...this.mobilePos };
            }
        },

        // Sync visibility based on localStorage (for hiding discrepancy)
        syncVisibility() {
            const today = new Date().toDateString();
            const hidden = localStorage.getItem(INCOMPLETE_KEY);
            const hideDate = localStorage.getItem(INCOMPLETE_DATE_KEY);
            this.show = !(hidden === "true" && hideDate === today);
        },

        // Handle closing the discrepancy (fixes the switch in widgets)
        handleToggle() {
            this.show = false;
            try {
                localStorage.setItem(INCOMPLETE_KEY, "true");
                localStorage.setItem(INCOMPLETE_DATE_KEY, new Date().toDateString());
                console.log("Timelog discrepancy closed: Switch in widgets should now be off."); // Debug log
            } catch (error) {
                console.error("Failed to save hide state to localStorage:", error);
            }
            this.$emit("close"); // Emit to parent if used as child
            // Dispatch event to notify WidgetComponent to update the toggle
            if (typeof window !== "undefined") {
                window.dispatchEvent(new Event("timelog-toggle"));
            }
        },

        // Handle clock trigger (reload logs)
        handleTriggerClocking() {
            this.fetchIncompleteLogs();
        },

        // Fetch incomplete logs from API
        async fetchIncompleteLogs() {
            if (this.loading) return;
            this.loading = true;
            try {
                const token = localStorage.getItem("auth_token");
                if (!token) throw new Error("No auth token found");
                const res = await axios.get("/api/employee/incomplete-logs", {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });
                this.incompleteLogs = Array.isArray(res.data) ? res.data : [];
            } catch (err) {
                console.error("Failed to fetch incomplete logs:", err);
                this.incompleteLogs = [];
            } finally {
                this.loading = false;
            }
        },

        // Format date for display
        formatDate(date) {
            try {
                return new Date(date).toLocaleDateString(undefined, {
                    year: "numeric",
                    month: "short",
                    day: "numeric",
                });
            } catch (error) {
                console.error("Invalid date format:", date, error);
                return date; // Fallback to raw date
            }
        },

        // Load saved position from localStorage
        loadPosition() {
            const saved = localStorage.getItem(POSITION_KEY);
            if (!saved) return;
            try {
                const { x, y } = JSON.parse(saved);
                this.pos = { x, y };
            } catch (error) {
                console.error("Failed to parse saved position:", error);
                localStorage.removeItem(POSITION_KEY);
            }
        },

        // Save position to localStorage (only on desktop)
        savePosition() {
            if (this.isMobile) return;
            try {
                localStorage.setItem(POSITION_KEY, JSON.stringify(this.pos));
            } catch (error) {
                console.error("Failed to save position:", error);
            }
        },

        // Initialize Bootstrap accordion
        initAccordion() {
            const el = this.$refs.collapse;
            if (!el) return;
            this.collapseInstance = new Collapse(el, { toggle: false });
            el.addEventListener("shown.bs.collapse", this.onAccordionOpen);
            el.addEventListener("hidden.bs.collapse", this.onAccordionClose);
        },

        // Restore accordion state from localStorage
        restoreAccordionState() {
            if (localStorage.getItem(ACCORDION_KEY) === "open") {
                this.collapseInstance?.show();
            }
        },

        // Handle accordion open
        onAccordionOpen() {
            localStorage.setItem(ACCORDION_KEY, "open");
        },

        // Handle accordion close
        onAccordionClose() {
            localStorage.setItem(ACCORDION_KEY, "closed");
        },

        // Destroy accordion instance
        destroyAccordion() {
            if (!this.collapseInstance) return;
            this.collapseInstance.dispose();
            this.collapseInstance = null;
        },

        // Start dragging (desktop only)
        startDrag(e) {
            if (this.isMobile) return;
            e.preventDefault();
            const rect = this.$refs.wrapper.getBoundingClientRect();
            this.isDragging = true;
            this.offset.x = e.clientX - rect.left;
            this.offset.y = e.clientY - rect.top;
            window.addEventListener("pointermove", this.onDrag);
            window.addEventListener("pointerup", this.stopDrag);
        },

        // Handle drag movement
        onDrag(e) {
            if (!this.isDragging) return;
            const el = this.$refs.wrapper;
            if (!el) return;
            const maxX = window.innerWidth - el.offsetWidth;
            const maxY = window.innerHeight - el.offsetHeight;
            this.pos.x = Math.min(Math.max(0, e.clientX - this.offset.x), maxX);
            this.pos.y = Math.min(Math.max(0, e.clientY - this.offset.y), maxY);
        },

        // Stop dragging
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