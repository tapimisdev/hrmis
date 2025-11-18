<template>
  <div class="timekeeping-summary">
    <SkeletonProfile v-if="loading" :lines="1" />

    <div v-else class="card">
      <!-- Header Section -->
      <div class="card-header">
        <div class="d-flex align-items-center gap-3 flex-grow-1 mt-2 mb-2">
          <div class="position-relative">
            <img
              class="rounded border"
              :src="profile.picture"
              alt="Profile Picture"
              width="56"
              height="56"
            />
            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" style="opacity: 0.7;"></span>
          </div>
          
          <div>
            <h5 class="mb-1 fw-bold text-body">{{ profile.name }}</h5>
            <div class="d-flex flex-wrap gap-2">
              <span
                v-for="(info, index) in infoCards"
                :key="index"
                class="badge text-body-secondary text-body border"
              >
                {{ info.value }}
              </span>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
          <button class="btn btn-outline-secondary btn-sm" @click="handlePrint">
            <i class="fa-solid fa-print"></i>
            <span class="d-none d-sm-inline ms-1">Print</span>
          </button>
          <button class="btn btn-outline-secondary btn-sm" @click="handleDownload">
            <i class="fa-solid fa-download"></i>
            <span class="d-none d-sm-inline ms-1">Export</span>
          </button>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="card-body bg-body-tertiary d-flex justify-content-between">
        <button 
          @click="toggleSummary" 
          class="btn btn-transparent d-flex gap-4 align-items-center" 
          :class="{ 'bg-body-secondary ': showSummary }">

          <span class="fw-semibold text-body">
            <i class="fa-solid fa-chart-line text-muted me-2"></i>
            Summary Statistics
          </span>
          <i 
            class="fa-solid fa-chevron-down transition-transform text-muted" 
            :class="{ 'rotate-180': showSummary }"
          ></i>
        </button>
        <div class="row g-2 align-items-center justify-content-end">
          <div class="col-auto">
            <label class="form-label mb-0 fw-semibold text-muted small text-uppercase">
              <i class="fa-solid fa-filter me-1"></i>
              Period
            </label>
          </div>
          <div class="col-auto">
            <select
              class="form-select form-select-sm"
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
          </div>
          <div class="col-auto">
            <select
              class="form-select form-select-sm"
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
          </div>
        </div>
      </div>
      <div v-if="showSummary" class="card-body">
        <div class="row g-3">
          <div
            class="col-md-6 col-lg-3"
            v-for="(card, index) in summary"
            :key="index"
          >
            <div class="card h-100 border">
              <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                  <div class="bg-body-secondary border text-muted rounded p-3">
                    <i :class="getSummaryIcon(card.label)" class="fs-4"></i>
                  </div>
                  <div>
                    <div class="text-muted text-uppercase small fw-semibold">
                      {{ card.label }}
                    </div>
                    <div class="fs-3 fw-bold text-body">{{ card.value }}</div>
                  </div>
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
.timekeeping-summary {
  margin-bottom: .8rem;

  .card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }

  .cursor-pointer {
    cursor: pointer;
    user-select: none;
    
    &:hover {
      background-color: var(--bs-secondary-bg) !important;
    }
  }

  .transition-transform {
    transition: transform 0.3s ease;
  }

  .rotate-180 {
    transform: rotate(180deg);
  }
}
</style>