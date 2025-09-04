<template>
  <Line class="cardiness" :data="chartData" :options="chartOptions" />
</template>

<script lang="ts">
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  ChartOptions
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

export default {
  name: 'HrisLineChart',
  components: { Line },
  props: {
    labels: {
      type: Array,
      default: () => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
    },
    hires: {
      type: Array,
      default: () => [5, 10, 7, 12, 8, 15, 10] // new hires per month
    },
    resignations: {
      type: Array,
      default: () => [2, 3, 1, 4, 3, 5, 2] // resignations per month
    }
  },
  computed: {
    chartData() {
      return {
        labels: this.labels,
        datasets: [
          {
            label: 'New Hires',
            data: this.hires,
            borderColor: '#FFE433',
            backgroundColor: 'rgba(76, 175, 239, 0.2)',
            tension: 0.3,
            fill: true,
            pointRadius: 5
          },
          {
            label: 'Resignations',
            data: this.resignations,
            borderColor: '#1F2231',
            backgroundColor: 'rgba(239, 83, 80, 0.2)',
            tension: 0.3,
            fill: true,
            pointRadius: 5
          }
        ]
      }
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top' as const
          },
          title: {
            display: true,
            text: 'Employee Movement (Monthly)'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      } as ChartOptions<'line'>
    }
  }
}
</script>
