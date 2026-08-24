<template>
  <div
    class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-white to-slate-50 border border-slate-200/50 p-6 shadow-lg hover:shadow-2xl transition-all duration-300 group"
    :class="{
      'border-l-4 border-rose-500 from-rose-50 to-white': highlight,
      'hover:border-blue-300': color === 'blue',
      'hover:border-indigo-300': color === 'indigo',
      'hover:border-emerald-300': color === 'emerald',
      'hover:border-rose-300': color === 'rose',
    }"
  >
    <!-- Decorative gradient element -->
    <div
      class="absolute top-0 right-0 w-24 h-24 rounded-full opacity-5 group-hover:opacity-10 transition-opacity"
      :class="getGradientClass()"
    ></div>

    <div class="relative z-10 flex items-start justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-slate-600 uppercase tracking-wide mb-2">{{ title }}</p>
        <p class="text-4xl font-bold text-slate-900 mt-3 mb-2 group-hover:scale-105 transition-transform origin-left">{{ value }}</p>
        <p class="text-sm text-slate-500 font-medium">{{ subtitle }}</p>
      </div>
      <div class="text-5xl ml-3 opacity-80 group-hover:opacity-100 transition-all group-hover:scale-110">
        {{ icon }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  subtitle: String,
  highlight: {
    type: Boolean,
    default: false
  },
  color: {
    type: String,
    default: 'blue'
  }
})

const getGradientClass = () => {
  if (props.highlight) return 'bg-rose-500'
  switch (props.color) {
    case 'blue': return 'bg-blue-500'
    case 'indigo': return 'bg-indigo-500'
    case 'emerald': return 'bg-emerald-500'
    case 'rose': return 'bg-rose-500'
    default: return 'bg-blue-500'
  }
}
</script>

<style scoped></style>
