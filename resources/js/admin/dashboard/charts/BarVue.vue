<template>
  <Bar class="cardiness" :data="chartData" :options="chartOptions" />
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
  data() {
    return {
      theme: document.documentElement.getAttribute('data-bs-theme') || 'light'
    }
  },
  computed: {
    chartData() {
      const isDark = this.theme === 'dark'
      const onTimeColor = isDark ? '#6ea8fe' : '#032985'
      const lateColor = isDark ? '#f67280' : '#000000'

      return {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [
          {
            label: 'On-Time',
            backgroundColor: onTimeColor,
            data: [95, 92, 98, 94, 96]
          },
          {
            label: 'Late',
            backgroundColor: lateColor,
            data: [25, 32, 12, 11, 24]
          }
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
            labels: {
              color: textColor
            }
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
          x: {
            ticks: { color: textColor },
            grid: { color: gridColor }
          },
          y: {
            ticks: {
              color: textColor,
              precision: 0
            },
            grid: { color: gridColor },
            beginAtZero: true
          }
        }
      }
    }
  },
  mounted() {
    // Watch for Bootstrap theme changes
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
