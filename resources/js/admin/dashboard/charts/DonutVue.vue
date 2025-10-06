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
        '#269899', // lighter 10%
        '#40ADAE', // lighter 20%
        '#5AC2C3', // lighter 30%
        '#74D7D8', // lighter 40%
        '#8EECEC', // lighter 50%
        '#0C8384', // base
        '#0A6E6F', // darker 10%
        '#085A5B', // darker 20%
        '#064647', // darker 30%
        '#043233', // darker 40%
        '#021E1F'  // darker 50%
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
