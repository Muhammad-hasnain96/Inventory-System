<template>
  <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
    <canvas ref="canvasRef" class="w-full h-72"></canvas>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler, Legend } from 'chart.js'

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler, Legend)

const props = defineProps({
  labels: {
    type: Array,
    default: () => []
  },
  data: {
    type: Array,
    default: () => []
  }
})

const canvasRef = ref(null)
let chartInstance = null

const createChart = () => {
  if (!canvasRef.value) return

  chartInstance = new Chart(canvasRef.value.getContext('2d'), {
    type: 'line',
    data: {
      labels: props.labels,
      datasets: [
        {
          label: 'Sales',
          data: props.data,
          tension: 0.35,
          borderColor: '#1D4ED8',
          backgroundColor: 'rgba(59, 130, 246, 0.18)',
          pointBackgroundColor: '#1D4ED8',
          fill: true,
          pointRadius: 4,
          borderWidth: 3,
        }
      ]
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: context => `$${context.formattedValue}`
          }
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: '#64748B'
          }
        },
        y: {
          grid: {
            color: 'rgba(148, 163, 184, 0.24)'
          },
          ticks: {
            color: '#64748B',
            callback: value => `$${value}`
          }
        }
      }
    }
  })
}

watch(
  () => [props.labels, props.data],
  () => {
    if (chartInstance) {
      chartInstance.data.labels = props.labels
      chartInstance.data.datasets[0].data = props.data
      chartInstance.update()
    }
  },
  { deep: true }
)

onMounted(() => {
  createChart()
})

onBeforeUnmount(() => {
  chartInstance?.destroy()
})
</script>

<style scoped>
canvas {
  display: block;
}
</style>
