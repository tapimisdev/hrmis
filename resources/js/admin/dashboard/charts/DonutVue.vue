<template>
  <Doughnut class="cardiness" :data="chartData" :options="chartOptions" />
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
        '#1F2231', '#FFE433', 
      ]
    },
    cutout: {
      type: String,
      default: '60%' // Donut hole size
    }
  },
  computed: {
    chartData() {
      return {
        labels: this.labels,
        datasets: [
          {
            label: this.title || 'Dataset',
            backgroundColor: this.colors,
            data: this.dataset
          }
        ]
      }
    },
    chartOptions(): ChartOptions<'doughnut'> {
      return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: this.cutout,
        plugins: {
          legend: {
            position: 'top' as const
          },
          title: {
            display: !!this.title,
            text: this.title
          }
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
