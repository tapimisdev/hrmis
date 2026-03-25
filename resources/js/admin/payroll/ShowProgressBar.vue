<template>
  <div class="batch-progress">
    <div class="bp-card" :class="{ finished: isFinished, failed: hasFailed }">
      <!-- Ambient glow -->
      <div class="bp-glow" aria-hidden="true"></div>

      <div class="bp-grid">
        <!-- LEFT -->
        <section class="bp-left">
          <div class="bp-media">
            <div class="bp-media-ring" aria-hidden="true"></div>
            <div class="bp-media-img">
              <img src="./img/data_processing.png" alt="Processing" />
            </div>
          </div>

          <header class="bp-header">
            <div class="bp-title">
              <h4 class="bp-h">{{ title }}</h4>
              <p class="bp-sub">Live batch tracking · updates every {{ pollInterval / 1000 }}s</p>
            </div>

            <span class="bp-pill" :class="statusClass">
              <span class="bp-dot" aria-hidden="true"></span>
              {{ statusText }}
            </span>
          </header>

          <div class="bp-progress">
            <div class="bp-progress-top">
              <div class="bp-percent">
                <span class="bp-percent-num">{{ progress }}</span>
                <span class="bp-percent-sym">%</span>
              </div>

              <div class="bp-meta">
                <div class="bp-meta-row">
                  <span class="bp-meta-label">Processed</span>
                  <span class="bp-meta-val">{{ processedJobs }}</span>
                </div>
                <div class="bp-meta-row">
                  <span class="bp-meta-label">Pending</span>
                  <span class="bp-meta-val">{{ pendingJobs }}</span>
                </div>
              </div>
            </div>

            <div class="bp-bar" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
              <div
                class="bp-fill"
                :style="{ width: progress + '%' }"
                :class="{ complete: isFinished, error: hasFailed }"
              >
                <span class="bp-shine" aria-hidden="true"></span>
              </div>
            </div>

            <div class="bp-hint" v-if="hasFailed && !isFinished">
              <span class="bp-hint-ico">⚠️</span>
              Some jobs failed — you can still let the batch finish or cancel.
            </div>
          </div>
        </section>

        <!-- RIGHT -->
        <section class="bp-right">
          <div class="bp-stats">
            <div
              class="bp-stat"
              v-for="(value, key) in statList"
              :key="key"
              :class="{ danger: key === 'Failed' && failedJobs > 0 }"
            >
              <div class="bp-stat-top">
                <div class="bp-stat-label">{{ key }}</div>
                <div class="bp-stat-chip" :class="chipClass(key)" aria-hidden="true"></div>
              </div>
              <div class="bp-stat-value">{{ value }}</div>
            </div>
          </div>

          <div class="bp-actions" v-if="!isFinished">
            <button class="bp-btn bp-btn-ghost" type="button" @click="$emit('refresh')">
              <i class="fa-solid fa-rotate"></i>
              Refresh
            </button>

            <button class="bp-btn bp-btn-danger" :disabled="isCancelling" @click="cancelBatch" type="button">
              <span v-if="!isCancelling"><i class="fa-solid fa-ban"></i> Cancel batch</span>
              <span v-else class="bp-loading">
                <span class="bp-spinner" aria-hidden="true"></span>
                Canceling…
              </span>
            </button>
          </div>

          <!-- Finished states -->
          <div class="bp-done" v-if="status === 'finished'">
            <div class="bp-done-icon ok">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div class="bp-done-text">
              <div class="bp-done-title">Batch complete</div>
              <div class="bp-done-sub">All results are ready 🎉</div>
            </div>
          </div>

          <div class="bp-done" v-else-if="status === 'cancelled'">
            <div class="bp-done-icon stop">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <div class="bp-done-text">
              <div class="bp-done-title">Batch cancelled</div>
              <div class="bp-done-sub">Stopped by user action</div>
            </div>
          </div>
        </section>
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
    pollInterval: { type: Number, default: 2000 },
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
      token: localStorage.getItem("auth_token"),
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
        ...(this.failedJobs > 0 && { Failed: this.failedJobs }),
      };
    },
  },
  mounted() {
    this.fetchProgress();
    this.startPolling();
  },
  beforeDestroy() {
    this.stopPolling();
  },
  methods: {
    chipClass(key) {
      if (key === "Failed") return "danger";
      if (key === "Processed") return "ok";
      if (key === "Pending") return "warn";
      return "info";
    },
    async fetchProgress() {
      try {
        const res = await axios.get(`${this.endpoint}/${this.batchId}`, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${this.token}`,
          },
        });

        const data = res.data;

        Object.assign(this, {
          progress: Math.round(data.progress),
          processedJobs: data.processedJobs,
          totalJobs: data.totalJobs,
          pendingJobs: data.pendingJobs,
          failedJobs: data.failedJobs,
          status: data.status,
        });

        this.$emit("progress", data);

        if (this.isFinished) {
          this.stopPolling();
          this.$emit("completed", data);
        }
      } catch (err) {
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
          {},
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
      } finally {
        this.isCancelling = false;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import '../../../sass/variables';

$radius: 18px;
$radius-lg: 22px;

$shadow-soft: 0 18px 50px rgba(0, 0, 0, 0.12);
$shadow-lift: 0 22px 60px rgba(0, 0, 0, 0.18);

$text-muted: rgba(var(--bs-body-color-rgb), 0.65);
$text-strong: rgba(var(--bs-body-color-rgb), 0.92);

$border: rgba(var(--bs-body-color-rgb), 0.12);
$glass: rgba(255, 255, 255, 0.55);
$glass-dark: rgba(20, 22, 26, 0.55);

.batch-progress {
  width: 100%;
  max-width: 980px;
  margin: 0 auto;
  padding: 14px 18px;
}


@keyframes bpIn {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.99);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}



.bp-grid {
  position: relative;
  display: grid;
  grid-template-columns: 1.25fr 1fr;
  gap: 18px;
  padding: 18px;

  @media (max-width: 860px) {
    grid-template-columns: 1fr;
  }
}

/* LEFT */
.bp-left {
  display: grid;
  gap: 14px;
}

.bp-media {
  position: relative;
  display: grid;
  place-items: center;
  padding: 10px 0 0;
}

.bp-media-ring {
  position: absolute;
  width: 260px;
  height: 260px;
  border-radius: 999px;
  background:
    radial-gradient(circle at 30% 30%, rgba(var(--bs-primary-rgb), 0.18), transparent 55%),
    radial-gradient(circle at 70% 70%, rgba(var(--bs-info-rgb), 0.12), transparent 55%);
  filter: blur(0px);
  animation: floaty 4.2s ease-in-out infinite;
}

@keyframes floaty {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.bp-media-img {
  width: 300px;
  max-width: 92%;
  background: rgba(var(--bs-body-bg-rgb), 0.65);
  border: 1px solid $border;
  border-radius: $radius-lg;
  padding: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.10);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);

  img {
    width: 100%;
    height: auto;
    display: block;
    filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.14));
  }
}

.bp-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.bp-title {
  min-width: 0;
}

.bp-h {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.2px;
  color: $text-strong;
}

.bp-sub {
  margin: 6px 0 0;
  font-size: 0.85rem;
  color: $text-muted;
}

.bp-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid $border;
  background: rgba(var(--bs-body-bg-rgb), 0.65);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  font-weight: 700;
  font-size: 0.82rem;
  white-space: nowrap;

  .bp-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 1);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.18);
  }

  &.processing {
    color: rgba(var(--bs-primary-rgb), 1);

    .bp-dot {
      background: rgba(var(--bs-primary-rgb), 1);
      animation: pulse 1.2s ease-in-out infinite;
    }
  }

  &.warning {
    color: rgba(var(--bs-warning-rgb), 1);

    .bp-dot {
      background: rgba(var(--bs-warning-rgb), 1);
      box-shadow: 0 0 0 4px rgba(var(--bs-warning-rgb), 0.18);
      animation: pulse 1.2s ease-in-out infinite;
    }
  }

  &.finished {
    color: rgba(var(--bs-success-rgb), 1);

    .bp-dot {
      background: rgba(var(--bs-success-rgb), 1);
      box-shadow: 0 0 0 4px rgba(var(--bs-success-rgb), 0.18);
      animation: none;
    }
  }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.12); opacity: 0.85; }
}

.bp-progress {
  border-radius: $radius-lg;
  border: 1px solid $border;
  background: rgba(var(--bs-body-bg-rgb), 0.55);
  padding: 14px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.bp-progress-top {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 12px;
}

.bp-percent {
  display: flex;
  align-items: baseline;
  gap: 4px;

  .bp-percent-num {
    font-size: 2.6rem;
    font-weight: 900;
    letter-spacing: -1px;
    line-height: 1;
    background: linear-gradient(
      90deg,
      rgba(var(--bs-primary-rgb), 1),
      rgba(var(--bs-info-rgb), 1)
    );
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }

  .bp-percent-sym {
    font-weight: 800;
    color: $text-muted;
  }
}

.bp-meta {
  display: grid;
  gap: 6px;
  min-width: 160px;

  .bp-meta-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 0.86rem;
    color: $text-muted;
  }

  .bp-meta-val {
    color: $text-strong;
    font-weight: 800;
  }
}

.bp-bar {
  height: 16px;
  border-radius: 999px;
  background: rgba(var(--bs-body-color-rgb), 0.10);
  overflow: hidden;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.18);
}

.bp-fill {
  height: 100%;
  border-radius: 999px;
  position: relative;
  background: linear-gradient(
    90deg,
    rgba(var(--bs-primary-rgb), 1),
    rgba(var(--bs-info-rgb), 1)
  );
  transition: width 420ms cubic-bezier(0.2, 0.9, 0.2, 1);

  &.complete {
    background: linear-gradient(
      90deg,
      rgba(var(--bs-success-rgb), 1),
      rgba(var(--bs-success-rgb), 1)
    );
  }

  &.error {
    background: linear-gradient(
      90deg,
      rgba(var(--bs-warning-rgb), 1),
      rgba(var(--bs-warning-rgb), 1)
    );
  }
}

.bp-shine {
  position: absolute;
  inset: 0;
  background: linear-gradient(110deg, transparent 30%, rgba(255, 255, 255, 0.38) 50%, transparent 70%);
  transform: translateX(-60%);
  animation: shine 1.6s ease-in-out infinite;
  mix-blend-mode: overlay;
}

@keyframes shine {
  0% { transform: translateX(-60%); opacity: 0.65; }
  60% { transform: translateX(120%); opacity: 0.85; }
  100% { transform: translateX(120%); opacity: 0; }
}

.bp-hint {
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px dashed rgba(var(--bs-warning-rgb), 0.35);
  background: rgba(var(--bs-warning-rgb), 0.10);
  color: rgba(var(--bs-warning-rgb), 0.95);
  font-weight: 650;
  font-size: 0.86rem;
  display: flex;
  align-items: center;
  gap: 8px;

  .bp-hint-ico {
    font-size: 1.05rem;
  }
}

/* RIGHT */
.bp-right {
  display: grid;
  gap: 14px;
  align-content: start;
}

.bp-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;

  @media (max-width: 520px) {
    grid-template-columns: 1fr;
  }
}

.bp-stat {
  border-radius: $radius;
  border: 1px solid $border;
  background: rgba(var(--bs-body-bg-rgb), 0.55);
  padding: 12px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;

  &:hover {
    transform: translateY(-3px);
    box-shadow: $shadow-lift;
    border-color: rgba(var(--bs-primary-rgb), 0.18);
  }

  &.danger {
    border-color: rgba(var(--bs-danger-rgb), 0.25);
  }
}

.bp-stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.bp-stat-label {
  font-size: 0.78rem;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  color: $text-muted;
  font-weight: 800;
}

.bp-stat-chip {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: rgba(var(--bs-secondary-rgb), 1);
  box-shadow: 0 0 0 4px rgba(var(--bs-secondary-rgb), 0.15);

  &.ok {
    background: rgba(var(--bs-success-rgb), 1);
    box-shadow: 0 0 0 4px rgba(var(--bs-success-rgb), 0.15);
  }
  &.warn {
    background: rgba(var(--bs-warning-rgb), 1);
    box-shadow: 0 0 0 4px rgba(var(--bs-warning-rgb), 0.15);
  }
  &.danger {
    background: rgba(var(--bs-danger-rgb), 1);
    box-shadow: 0 0 0 4px rgba(var(--bs-danger-rgb), 0.15);
  }
  &.info {
    background: rgba(var(--bs-info-rgb), 1);
    box-shadow: 0 0 0 4px rgba(var(--bs-info-rgb), 0.15);
  }
}

.bp-stat-value {
  font-size: 2rem;
  font-weight: 900;
  line-height: 1;
  color: $text-strong;
}

.bp-actions {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 10px;

  @media (max-width: 520px) {
    grid-template-columns: 1fr;
  }
}

.bp-btn {
  border: 1px solid $border;
  border-radius: 16px;
  padding: 12px 14px;
  font-weight: 800;
  cursor: pointer;
  transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, border-color 160ms ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;

  &:active {
    transform: translateY(1px);
  }
}

.bp-btn-ghost {
  background: rgba(var(--bs-body-bg-rgb), 0.55);
  color: $text-strong;

  &:hover {
    box-shadow: $shadow-soft;
    border-color: rgba(var(--bs-primary-rgb), 0.22);
  }
}

.bp-btn-danger {
  background: rgba(var(--bs-danger-rgb), 0.92);
  color: white;
  border-color: rgba(var(--bs-danger-rgb), 0.35);
  box-shadow: 0 10px 22px rgba(var(--bs-danger-rgb), 0.20);

  &:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 16px 30px rgba(var(--bs-danger-rgb), 0.26);
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.bp-loading {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.bp-spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: rgba(255, 255, 255, 1);
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.bp-done {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border-radius: $radius-lg;
  border: 1px solid $border;
  background: rgba(var(--bs-body-bg-rgb), 0.55);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.bp-done-icon {
  width: 44px;
  height: 44px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  color: white;

  svg {
    width: 22px;
    height: 22px;
    stroke-width: 2.6;
  }

  &.ok {
    background: rgba(var(--bs-success-rgb), 0.95);
    box-shadow: 0 12px 22px rgba(var(--bs-success-rgb), 0.18);
  }

  &.stop {
    background: rgba(var(--bs-danger-rgb), 0.90);
    box-shadow: 0 12px 22px rgba(var(--bs-danger-rgb), 0.18);
  }
}

.bp-done-title {
  font-weight: 900;
  color: $text-strong;
  font-size: 1.05rem;
}

.bp-done-sub {
  margin-top: 2px;
  color: $text-muted;
  font-weight: 650;
  font-size: 0.9rem;
}

/* Dark theme tuning */
[data-bs-theme="dark"] .bp-card {
  
}

[data-bs-theme="dark"] .bp-media-img,
[data-bs-theme="dark"] .bp-pill,
[data-bs-theme="dark"] .bp-progress,
[data-bs-theme="dark"] .bp-stat,
[data-bs-theme="dark"] .bp-done {
  background: rgba(18, 20, 24, 0.55);
}
</style>
