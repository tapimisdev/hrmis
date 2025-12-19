<template>
  <!-- LOADING STATE -->
  <div v-if="loading" class="text-center d-flex align-items-center justify-content-center gap-2 py-4">
    <div class="spinner-border text-body text-opacity-25" role="status" style="height: 12px; width: 12px;">
      <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-2 fw-semibold text-body text-opacity-25">Loading ...</div>
  </div>
  <Bar v-else class="cardiness" :data="chartData" :options="chartOptions" />
</template>

<script lang="ts">
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ChartOptions
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

export default {
  name: 'BarChart',
  components: { Bar },
  props: {
    ontime: { 
      type: Array,
      default: () => []
    },
    lates: {
      type: Array,
      default: () => []
    },
    total_employees: {
      type: String,
      default: () => []
    },
    labels: {
      type: Array,
      default: () => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']
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
      const isDark = this.theme === 'dark'

      const onTimeColor = isDark ? '#6ea8fe' : '#032985'
      const lateColor = isDark ? '#f9d423' : '#facc15';

      return {
        labels: this.labels, // use labels prop
        datasets: [
          {
            label: 'On-Time',
            backgroundColor: onTimeColor,
            data: this.ontime // use prop
          },
          {
            label: 'Late',
            backgroundColor: lateColor,
            data: this.lates // use prop
          },
        ]
      }
    },
    chartOptions(): ChartOptions<'bar'> {
      const isDark = this.theme === 'dark'
      const textColor = isDark ? '#e9ecef' : '#212529'
      const gridColor = isDark ? '#495057' : '#dee2e6'
      const backgroundColor = isDark ? '#212529' : '#ffffff'

      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: textColor }
          },
          title: {
            display: true,
            text: 'Daily Late vs On-Time Attendance',
            color: textColor
          },
          tooltip: {
            bodyColor: textColor,
            titleColor: textColor,
            backgroundColor,
            borderColor: gridColor,
            borderWidth: 1
          }
        },
        scales: {
          x: { ticks: { color: textColor }, grid: { color: gridColor } },
          y: { 
            ticks: { color: textColor, precision: 0 },
            grid: { color: gridColor },
            beginAtZero: true,
            max: this.total_employees
          }
        }
      }
    }
  },
  mounted() {
    // Watch for Bootstrap theme changes
    const observer = new MutationObserver(() => {
      const newTheme = document.documentElement.getAttribute('data-bs-theme') || 'light'
      if (newTheme !== this.theme) this.theme = newTheme
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
