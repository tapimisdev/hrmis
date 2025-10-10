<template>
  <div class="timekeeping-summary">
    <SkeletonProfile v-if="loading" :lines="1" />

    <div v-else class="modern-card">
      <!-- Header Section -->
      <div class="card-header">
        <div class="profile-section">
          <div class="profile-avatar-wrapper">
            <img
              class="profile-avatar"
              :src="profile.picture"
              alt="Profile Picture"
            />
            <div class="status-indicator"></div>
          </div>
          
          <div class="profile-info">
            <h4 class="profile-name">{{ profile.name }}</h4>
            <div class="profile-meta">
              <span
                v-for="(info, index) in infoCards"
                :key="index"
                class="meta-badge"
              >
                {{ info.value }}
              </span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button class="action-btn action-btn-secondary" title="Print Report" @click="handlePrint">
            <i class="fa-solid fa-print"></i>
            <span class="btn-label">Print</span>
          </button>
          <button class="action-btn action-btn-primary" title="Download DTR" @click="handleDownload">
            <i class="fa-solid fa-download"></i>
            <span class="btn-label">Export</span>
          </button>
        </div>
      </div>

      <!-- Filter Section - Always Visible -->
      <div class="filter-section">
        <div class="filter-label">
          <i class="fa-solid fa-filter"></i>
          <span>Period</span>
        </div>
        <div class="filter-controls">
          <div class="select-wrapper">
            <select
              class="modern-select"
              @change="emitDate"
              v-model="localMonth"
            >
              <option
                v-for="(month, index) in months"
                :key="index"
                :value="index + 1"
              >
                {{ month }}
              </option>
            </select>
            <i class="fa-solid fa-chevron-down select-icon"></i>
          </div>

          <div class="select-wrapper">
            <select
              class="modern-select"
              @change="emitDate"
              v-model="localYear"
            >
              <option
                v-for="year in years"
                :key="year"
                :value="year"
              >
                {{ year }}
              </option>
            </select>
            <i class="fa-solid fa-chevron-down select-icon"></i>
          </div>
        </div>
      </div>

      <!-- Expandable Summary Section -->
      <div class="summary-toggle" @click="toggleSummary">
        <span class="toggle-text">
          <i class="fa-solid fa-chart-line"></i>
          Summary Statistics
        </span>
        <i class="fa-solid fa-chevron-down toggle-icon" :class="{ rotated: showSummary }"></i>
      </div>

      <transition name="expand">
        <div v-if="showSummary" class="summary-section">
          <div class="summary-grid">
            <div
              class="summary-card"
              v-for="(card, index) in summary"
              :key="index"
            >
              <div class="summary-icon">
                <i :class="getSummaryIcon(card.label)"></i>
              </div>
              <div class="summary-content">
                <span class="summary-label">{{ card.label }}</span>
                <span class="summary-value">{{ card.value }}</span>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import SkeletonProfile from "./SkeletonProfile.vue";

export default {
  components: { SkeletonProfile },
  props: {
    month: { type: Number, default: () => new Date().getMonth() + 1 },
    year: { type: Number, default: () => new Date().getFullYear() },
    employee_id: { type: String, required: true },
    summary: { type: Array, required: true },
  },
  data() {
    return {
      loading: false,
      showSummary: false,
      localMonth: this.month,
      localYear: this.year,
      profile: {},
      infoCards: [],
      months: [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December",
      ],
      years: Array.from({ length: 2 }, (_, i) => new Date().getFullYear() - i),
    };
  },
  mounted() {
    this.getEmployeeInformation();
  },
  methods: {
    toggleSummary() {
      this.showSummary = !this.showSummary;
    },
    handlePrint() {
      // Print functionality
    },
    handleDownload() {
      // Download functionality
    },
    getSummaryIcon(label) {
      const iconMap = {
        'total hours': 'fa-solid fa-clock',
        'days present': 'fa-solid fa-calendar-check',
        'days absent': 'fa-solid fa-calendar-xmark',
        'late': 'fa-solid fa-stopwatch',
        'overtime': 'fa-solid fa-hourglass-half',
        'leaves': 'fa-solid fa-plane-departure',
      };
      return iconMap[label.toLowerCase()] || 'fa-solid fa-chart-bar';
    },
    emitDate() {
      this.$emit("update-date", { month: this.localMonth, year: this.localYear });
    },
    async getEmployeeInformation() {
      this.loading = true;
      try {
        const response = await axios.get(
          `/admin/timekeeping/daily-time-record/${this.employee_id}/employee_information`
        );
        this.profile = response.data.profile;
        this.infoCards = response.data.infoCards;
      } catch (error) {
        console.error("Error fetching logs:", error);
      }
      this.loading = false;
    },
    updateUrlParams() {
      const params = new URLSearchParams(window.location.search);
      params.set('month', this.localMonth);
      params.set('year', this.localYear);

      const newUrl = `${window.location.pathname}?${params.toString()}`;
      window.history.replaceState({}, '', newUrl);
    },
  },
  watch: {
    month(newVal) {
      this.localMonth = newVal;
      this.updateUrlParams();
    },
    year(newVal) {
      this.localYear = newVal;
      this.updateUrlParams();
    },
  },
};
</script>

<style lang="scss" scoped>
@import './../../../../sass/variables';

.timekeeping-summary {
  .modern-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    margin-bottom: 1.5rem;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    background: linear-gradient(135deg, $primary 0%, $secondary 100%);
    color: white;
    gap: 1.5rem;
    flex-wrap: wrap;

    @media (max-width: 768px) {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  .profile-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
  }

  .profile-avatar-wrapper {
    position: relative;
  }

  .profile-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
    object-fit: cover;
  }

  .status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
  }

  .profile-info {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
  }

  .profile-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
  }

  .profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
  }

  .meta-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.8125rem;
    font-weight: 500;
  }

  .header-actions {
    display: flex;
    gap: 0.75rem;

    @media (max-width: 768px) {
      width: 100%;
    }
  }

  .action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    transition: all 0.3s ease;

    @media (max-width: 768px) {
      flex: 1;
      justify-content: center;
    }

    i {
      font-size: 1rem;
    }

    &.action-btn-secondary {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      backdrop-filter: blur(10px);

      &:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }
    }

    &.action-btn-primary {
      background: white;
      color: $primary;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      }
    }

    .btn-label {
      @media (max-width: 480px) {
        display: none;
      }
    }
  }

  .filter-section {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .filter-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #475569;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;

    i {
      color: $primary;
    }
  }

  .filter-controls {
    display: flex;
    gap: 0.75rem;
    flex: 1;
    flex-wrap: wrap;
  }

  .select-wrapper {
    position: relative;
    flex: 1;
    min-width: 140px;
  }

  .modern-select {
    width: 100%;
    padding: 0.6rem 2.5rem 0.6rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;

    &:hover {
      border-color: #cbd5e1;
    }

    &:focus {
      outline: none;
      border-color: $primary;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
  }

  .select-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.75rem;
    pointer-events: none;
  }

  .summary-toggle {
    padding: 0.75rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s ease;

    &:hover {
      background: #f8fafc;
    }
  }

  .toggle-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #334155;
    font-size: 0.875rem;

    i {
      color: $primary;
      font-size: 1rem;
    }
  }

  .toggle-icon {
    color: #94a3b8;
    transition: transform 0.3s ease;

    &.rotated {
      transform: rotate(180deg);
    }
  }

  .summary-section {
    padding: 1.5rem;
    background: white;
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.875rem;
  }

  .summary-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
      border-color: $primary;
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }
  }

  .summary-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, $primary 0%, $secondary 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    i {
      color: white;
      font-size: 1.25rem;
    }
  }

  .summary-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
  }

  .summary-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
  }

  // Transition animations
  .expand-enter-active,
  .expand-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
  }

  .expand-enter-from,
  .expand-leave-to {
    opacity: 0;
    max-height: 0;
  }

  .expand-enter-to,
  .expand-leave-from {
    opacity: 1;
    max-height: 1000px;
  }
}
</style>