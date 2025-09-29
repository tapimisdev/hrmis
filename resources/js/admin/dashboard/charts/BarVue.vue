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
  name: 'App',
  components: { Bar },
  data() {
    return {
      chartData: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [
          {
            label: 'On-Time',
            backgroundColor: '#0c8384',
            data: [95, 92, 98, 94, 96] 
          },
          {
            label: 'Late',
            backgroundColor: '#323535',
            data: [25, 32, 12, 11, 24]
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top' as const
          },
          title: {
            display: true,
            text: 'Daily Late vs On-Time Attendance'
          }
        }
      } as ChartOptions<'bar'>
    }
  }
}
</script>
