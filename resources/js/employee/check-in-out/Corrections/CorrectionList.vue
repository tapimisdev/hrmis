<template>
    <div
        class="modal fade"
        id="correctionModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content modern-modal">
                <div class="modal-header modern-header border-bottom">
                    <div class="header-content mb-0 d-flex align-items-center">
                        <div class="icon-wrapper me-2">
                            <i class="text-light fas fa-clock"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title">All Correction Requests</h5>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        @click="closeModal"
                    ></button>
                </div>

                <div class="modal-body">
                    <div v-if="loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                    <div v-else>
                        <div class="table-responsive">
                            <table
                                class="table table-striped table-bordered table-hover"
                            >
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Reference No.</th>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Break Out</th>
                                        <th>Break In</th>
                                        <th>Time Out</th>
                                        <th>Overtime In</th>
                                        <th>Overtime Out</th>
                                        <th>Status</th>
                                        <th>Attachment</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(request, index) in requests"
                                        :key="index"
                                    >
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ request.reference_no }}</td>
                                        <td>{{ formatDate(request.date) }}</td>
                                        <td>
                                            {{ formatTime(request.time_in) }}
                                        </td>
                                        <td>
                                            {{ formatTime(request.break_out) }}
                                        </td>
                                        <td>
                                            {{ formatTime(request.break_in) }}
                                        </td>
                                        <td>
                                            {{ formatTime(request.time_out) }}
                                        </td>
                                        <td>
                                            {{
                                                formatTime(request.overtime_in)
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatTime(request.overtime_out)
                                            }}
                                        </td>
                                        <td class="text-center">
                                            <span
                                                :class="{
                                                    'badge bg-warning text-dark':
                                                        request.status ===
                                                        'pending',
                                                    'badge bg-success':
                                                        request.status ===
                                                        'approved',
                                                    'badge bg-danger':
                                                        request.status ===
                                                        'rejected',
                                                }"
                                            >
                                                {{ request.status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a
                                                v-if="request.attachment"
                                                :href="request.attachment"
                                                target="_blank"
                                                class="btn btn-link"
                                            >
                                                View
                                            </a>
                                            <span v-else>-</span>
                                        </td>
                                        <td style="width: 300px">
                                            {{ request.action_remarks || "-" }}
                                        </td>
                                    </tr>
                                    <tr v-if="requests.length === 0">
                                        <td
                                            colspan="12"
                                            class="text-center text-muted text-uppercase"
                                        >
                                            No correction requests found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        style="font-size: 12px;"
                        class="btn py-2 px-4 btn-danger text-uppercase fw-medium"
                        data-bs-dismiss="modal"
                        @click="closeModal"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "CorrectionList",
    data() {
        return {
            loading: false,
            requests: [],
            month: null,
            year: null,
        };
    },
    methods: {
        open(month, year) {
            this.month = month;
            this.year = year;

            this.loadRequests();
            $("#correctionModal").modal("show");
        },
        async loadRequests() {
          this.loading = true;
          try {
              const token = localStorage.getItem("auth_token");

              const res = await axios.get("/api/view-correction", {
                  headers: {
                      Authorization: `Bearer ${token}`,
                  },
                  params: {
                      month: this.month,
                      year: this.year,
                  },
              });

              this.requests = res.data.data || [];

              // Initialize DataTable after Vue renders
              this.$nextTick(() => {
                  if ($.fn.dataTable.isDataTable('#correctionRequestsTable')) {
                      $('#correctionRequestsTable').DataTable().destroy();
                  }
                  $('#correctionRequestsTable').DataTable({
                      pageLength: 10,
                      lengthMenu: [5, 10, 25, 50],
                      order: [[2, 'desc']], // Default sorting by Date column
                  });
              });
          } catch (error) {
              console.error("Failed to load correction requests:", error);
          } finally {
              this.loading = false;
          }
      },
        formatDate(datetime) {
            return datetime ? new Date(datetime).toLocaleDateString() : "-";
        },
        formatTime(datetime) {
            return datetime
                ? new Date(datetime).toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                  })
                : "-";
        },
        storageUrl(path) {
            return path ? `${process.env.MIX_APP_URL}/storage/${path}` : "#";
        },
        closeModal() {
            const url = new URL(window.location.href);
            console.log(url);
            url.searchParams.delete("view-corrections");
            window.history.replaceState({}, document.title, url.toString());
        },
    },
};
</script>

<style scoped>
.badge {
    font-size: 10px;
    padding: 8px 20px;
    text-transform: uppercase;
}
.table {
    font-size: 0.9rem;
}

th {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: bold;
}

td {
    font-size: 12px;
    vertical-align: middle;

    a {
        width: 100%;
        text-align: center;
        font-size: 12px;
        text-transform: uppercase;
    }
}
</style>
