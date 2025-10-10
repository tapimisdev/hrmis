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
        '#AFC4FF', // lighter 50%
        '#8DAAFF', // lighter 40%
        '#6B8FFF', // lighter 30%
        '#4975FF', // lighter 20%
        '#275AFF', // lighter 10%
        '#032985', // base
        '#021F69', // darker 10%
        '#02164D', // darker 20%
        '#010C31', // darker 30%
        '#000416', // darker 40%
        '#000109'  // darker 50%
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
