<template>
  <!-- LOADING STATE -->
  <div v-if="loading" class="text-center d-flex align-items-center justify-content-center gap-2 py-4">
    <div class="spinner-border text-body text-opacity-25" role="status" style="height: 12px; width: 12px;">
      <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-2 fw-semibold text-body text-opacity-25">Loading ...</div>
  </div>
  <Doughnut v-else class="cardiness" :data="chartData" :options="chartOptions" />
</template>

<script lang="ts">
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  ChartOptions
} from 'chart.js'
import { Doughnut } from 'vue-chartjs'

ChartJS.register(ArcElement, Title, Tooltip, Legend)

export default {
  name: 'DonutChart',
  components: { Doughnut },
  props: {
    labels: {
      type: Array as () => string[],
      required: true
    },
    dataset: {
      type: Array as () => number[],
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    colors: {
      type: Array as () => string[],
      default: () => [
        '#002265', // base color
        '#6895fd' // darkest
      ]
    },
    cutout: {
      type: String,
      default: '60%' // Donut hole size
    },
    loading: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      theme: document.documentElement.getAttribute('data-bs-theme') || 'light'
    }
  },
  computed: {
    chartData() {
      // Adjust brightness for dark mode slightly
      const adjustedColors =
        this.theme === 'dark'
          ? this.colors.map(color => this.lightenColor(color, 40))
          : this.colors

      return {
        labels: this.labels,
        datasets: [
          {
            label: this.title || 'Dataset',
            backgroundColor: adjustedColors,
            data: this.dataset,
            borderColor: this.theme === 'dark' ? '#212529' : '#fff',
            borderWidth: 2
          }
        ]
      }
    },
    chartOptions(): ChartOptions<'doughnut'> {
      const isDark = this.theme === 'dark'
      const textColor = isDark ? '#e9ecef' : '#212529'

      return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: this.cutout,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              color: textColor
            }
          },
          title: {
            display: !!this.title,
            text: this.title,
            color: textColor
          },
          tooltip: {
            bodyColor: textColor,
            titleColor: textColor,
            backgroundColor: isDark ? '#343a40' : '#f8f9fa',
            borderColor: isDark ? '#495057' : '#dee2e6',
            borderWidth: 1
          }
        }
      }
    }
  },
  methods: {
    lightenColor(hex: string, percent: number) {
      // Lighten color for dark mode adjustment
      const num = parseInt(hex.replace('#', ''), 16)
      const amt = Math.round(2.55 * percent)
      const R = (num >> 16) + amt
      const G = ((num >> 8) & 0x00ff) + amt
      const B = (num & 0x0000ff) + amt
      return (
        '#' +
        (
          0x1000000 +
          (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
          (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
          (B < 255 ? (B < 1 ? 0 : B) : 255)
        )
          .toString(16)
          .slice(1)
      )
    }
  },
  mounted() {
    // Observe theme changes and update chart
    const observer = new MutationObserver(() => {
      const newTheme = document.documentElement.getAttribute('data-bs-theme') || 'light'
      if (newTheme !== this.theme) {
        this.theme = newTheme
      }
    })

    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['data-bs-theme']
    })
  }
}
</script>

<style scoped>
.cardiness {
  height: 350px;
}
</style>
