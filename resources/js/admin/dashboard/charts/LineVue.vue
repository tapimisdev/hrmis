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
      default: () => [5, 10, 7, 12, 8, 15, 10] 
    },
    resignations: {
      type: Array,
      default: () => [2, 3, 1, 4, 3, 5, 2]
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
            borderColor: '#032985',
            backgroundColor: '#032985',
            tension: 0.3,
            fill: true,
            pointRadius: 5
          },
          {
            label: 'Resignations',
            data: this.resignations,
            borderColor: '#000000',
            backgroundColor: '#000000',
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
