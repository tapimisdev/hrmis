<template>
    <div
        class="incomplete-logs-component"
        ref="wrapper"
        :class="{ dragging: isDragging }"
        :style="{ left: pos.x + 'px', top: pos.y + 'px' }"
    >
        <div class="card">
            <div class="card-header">
                <div class="drag-header" @pointerdown="startDrag">
                    <div class="btn btn-danger btn-sm me-2">
                        <i class="fa-solid fa-arrows-up-down-left-right"></i>
                    </div>
                    <span class="fw-bold text-danger text-uppercase">
                        Timelog Discrepancy!
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="accordion shadow" id="incompleteLogsAccordion">
                    <div class="accordion-item rounded-0">
                        <button
                            class="accordion-button collapsed text-uppercase fw-bold py-2" style="font-size: 10px"
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
                            class="accordion-collapse collapse"
                            data-bs-parent="#incompleteLogsAccordion"
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
                                                <th>Date</th>
                                                <th>In</th>
                                                <th>Out</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="log in incompleteLogs"
                                                :key="log.date + log.shift_id"
                                            >
                                                <td>
                                                    {{ formatDate(log.date) }}
                                                </td>
                                                <td>{{ log.time_in || "" }}</td>
                                                <td>
                                                    {{ log.time_out || "" }}
                                                </td>
                                                <td class="text-uppercase">
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

export default {
    data() {
        return {
            incompleteLogs: [],
            loading: false,

            // Drag state
            isDragging: false,
            start: { x: 0, y: 0 },
            offset: { x: 0, y: 0 },
            pos: { x: window.innerWidth - 470, y: 200 },
        };
    },

    mounted() {
        this.fetchIncompleteLogs();
    },

    methods: {
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
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-bottom: none;
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

.accordion-button {
  outline: none;
  box-shadow: none !important;
}
</style>
