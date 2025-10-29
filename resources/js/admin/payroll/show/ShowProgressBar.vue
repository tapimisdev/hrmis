<template>
  <div class="batch-progress">
    <div class="progress-container">
      <div class="left-section">
        <div class="img-container">
          <img src="./data_processing.png" alt="Processing">
        </div>
        
        <div class="progress-header">
          <h4>{{ title }}</h4>
          <span class="status-badge" :class="statusClass">
            {{ statusText }}
          </span>
        </div>

        <div class="progress-bar-container">
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: progress + '%' }"
              :class="{ 'complete': isFinished, 'error': hasFailed }"
            ></div>
          </div>
          <span class="progress-text">{{ progress }}%</span>
        </div>
      </div>

      <div class="right-section">
        <div class="stats-grid">
          <div class="stat-item" v-for="(value, key) in statList" :key="key">
            <div class="stat-label">{{ key }}</div>
            <div class="stat-value" :class="{ error: key === 'Failed' && failedJobs > 0 }">
              {{ value }}
            </div>
          </div>
        </div>

        <div v-if="!isFinished" class="actions">
          <button class="btn-cancel" :disabled="isCancelling" @click="cancelBatch">
            {{ isCancelling ? 'Canceling…' : 'Cancel' }}
          </button>
        </div>

        <div>
          <!-- When Batch is Finished -->
          <div v-if="status === 'finished'" class="completion-message text-success bg-opacity-25 bg-success">
            <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>Batch complete!</span>
          </div>

          <!--When Batch is Cancelled -->
          <div v-else-if="status === 'cancelled'" class="completion-message text-danger bg-opacity-25 bg-danger">
            <svg class="cancel-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>Batch cancelled</span>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "BatchProgress",
  props: {
    batchId: { type: String, required: true },
    endpoint: { type: String, required: true },
    cancelEndpoint: { type: String, default: "" },
    title: { type: String, default: "Batch Progress" },
    pollInterval: { type: Number, default: 2000 }
  },
  data() {
    return {
      progress: 0,
      processedJobs: 0,
      totalJobs: 0,
      pendingJobs: 0,
      failedJobs: 0,
      status: "processing",
      pollTimer: null,
      isCancelling: false,
      token: localStorage.getItem('auth_token')
    };
  },
  computed: {
    isFinished() {
      return ["finished", "cancelled"].includes(this.status);
    },
    hasFailed() {
      return this.failedJobs > 0;
    },
    statusClass() {
      if (this.status === "cancelled") return "warning";
      if (this.isFinished) return "finished";
      if (this.hasFailed) return "warning";
      return "processing";
    },
    statusText() {
      if (this.status === "cancelled") return "Cancelled";
      if (this.isFinished) return "Completed";
      if (this.hasFailed) return `Processing (${this.failedJobs} failed)`;
      return "Processing";
    },
    statList() {
      return {
        Processed: this.processedJobs,
        Pending: this.pendingJobs,
        Total: this.totalJobs,
        ...(this.failedJobs > 0 && { Failed: this.failedJobs })
      };
    }
  },
  mounted() {
    this.fetchProgress();
    this.startPolling();
  },
  beforeDestroy() {
    this.stopPolling();
  },
  methods: {
    async fetchProgress() {
      try {
        const res = await axios.get(`${this.endpoint}/${this.batchId}`, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${this.token}`
          }
        });

        const data = res.data;

        Object.assign(this, {
          progress: Math.round(data.progress),
          processedJobs: data.processedJobs,
          totalJobs: data.totalJobs,
          pendingJobs: data.pendingJobs,
          failedJobs: data.failedJobs,
          status: data.status
        });

        this.$emit("progress", data);

        if (this.isFinished) {
          this.stopPolling();
          this.$emit("completed", data);
        }
      } catch (err) {
        console.error("Error fetching progress:", err);
      }
    },
    startPolling() {
      this.pollTimer = setInterval(() => {
        if (!this.isFinished) this.fetchProgress();
      }, this.pollInterval);
    },
    stopPolling() {
      clearInterval(this.pollTimer);
      this.pollTimer = null;
    },
    async cancelBatch() {
      if (!this.cancelEndpoint) return;
      this.isCancelling = true;
      try {

        const res = await axios.post(
          `${this.cancelEndpoint}/${this.batchId}`,
          {}, // empty body
          {
            headers: {
              Accept: "application/json",
              Authorization: `Bearer ${this.token}`,
            },
          }
        );

        this.status = "cancelled";
        this.stopPolling();
        this.$emit("cancelled", res.data);
      } catch (err) {
        console.error("Cancel error:", err);
      } finally {
        this.isCancelling = false;
      }
    },
  }
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

// Design tokens
$max-width: 900px;
$border-radius: 12px;
$spacing-unit: 20px;
$text-muted: #6b7280;
$text-dark: #1f2937;
$error: #dc2626;

.batch-progress {
  width: 100%;
  max-width: $max-width;
  margin: 0 auto;
  padding: 0 $spacing-unit * 2;

  .progress-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: $spacing-unit * 2;
    align-items: start;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .left-section {
    display: flex;
    flex-direction: column;
    gap: $spacing-unit * 1.5;
    
    .img-container {
      display: flex;
      justify-content: center;
      padding: $spacing-unit 0;
      
      img {
        width: 280px;
        height: auto;
        filter: drop-shadow(2px 4px 8px rgba(0, 0, 0, 0.15));
      }
    }

    .progress-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: $spacing-unit;

      h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: $text-dark;
      }

      .status-badge {
        padding: 6px 16px;
        border-radius: 16px;
        font-size: 0.875rem;
        font-weight: 500;
        white-space: nowrap;

        &.processing { 
          background: rgba($primary, 0.15); 
          color: darken($primary, 10%); 
        }
        &.warning { 
          background: rgba($warning, 0.15); 
          color: darken($warning, 15%); 
        }
        &.finished { 
          background: rgba($success, 0.15); 
          color: darken($success, 10%); 
        }
      }
    }

    .progress-bar-container {
      display: flex;
      align-items: center;
      gap: $spacing-unit;

      .progress-bar {
        flex: 1;
        height: 16px;
        background: #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);

        .progress-fill {
          height: 100%;
          background: linear-gradient(90deg, lighten($primary, 15%), $secondary);
          transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
          border-radius: 8px;

          &.complete { 
            background: linear-gradient(90deg, lighten($success, 15%), $success); 
          }
          &.error { 
            background: linear-gradient(90deg, lighten($warning, 15%), $warning); 
          }
        }
      }

      .progress-text {
        font-weight: 700;
        color: $text-dark;
        font-size: 1.125rem;
        min-width: 55px;
        text-align: right;
      }
    }
  }

  .right-section {
    display: flex;
    flex-direction: column;
    gap: $spacing-unit * 1.5;
    justify-content: center;
    height: 100%;

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: $spacing-unit;
      padding: $spacing-unit * 1.5;
      background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
      border-radius: $border-radius;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      
      .stat-item {
        text-align: center;
        padding: $spacing-unit;
        background: white;
        border-radius: $border-radius - 2px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);

        .stat-label {
          color: $text-muted;
          font-size: 0.75rem;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 500;
          margin-bottom: 8px;
        }

        .stat-value {
          font-weight: 700;
          font-size: 2rem;
          color: $text-dark;
          line-height: 1;

          &.error { 
            color: $error; 
          }
        }
      }
    }

    .actions {
      .btn-cancel {
        width: 100%;
        background: $error;
        color: white;
        border: none;
        padding: 16px 32px;
        border-radius: $border-radius;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba($error, 0.3);

        &:hover:not(:disabled) { 
          background: darken($error, 8%);
          box-shadow: 0 6px 16px rgba($error, 0.4);
          transform: translateY(-2px);
        }

        &:active:not(:disabled) {
          transform: translateY(0);
          box-shadow: 0 4px 12px rgba($error, 0.3);
        }

        &:disabled { 
          opacity: 0.5; 
          cursor: not-allowed; 
        }
      }
    }

    .completion-message {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: $spacing-unit;
      padding: $spacing-unit * 1.5;
      border-radius: $border-radius;
      font-weight: 600;
      font-size: 1.125rem;
      
      .check-icon, .cancel-icon {
        width: 28px;
        height: 28px;
        stroke-width: 2.5;
      }
    }

  }
}
</style>