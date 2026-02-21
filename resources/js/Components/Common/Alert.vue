<!-- ========================================
     ALERT.VUE
     File: resources/js/Components/Common/Alert.vue
     ======================================== -->
<template>
  <div :class="alertClasses" class="rounded-md p-4">
    <div class="flex">
      <div class="shrink-0">
        <component :is="icon" class="h-5 w-5" :class="iconColor" />
      </div>
      <div class="ml-3 flex-1">
        <p class="text-sm font-medium" :class="textColor">
          <slot />
        </p>
      </div>
      <div v-if="dismissible" class="ml-auto pl-3">
        <button @click="$emit('close')" class="-mx-1.5 -my-1.5 rounded-md p-1.5 hover:bg-opacity-20">
          <XMarkIcon class="h-5 w-5" :class="iconColor" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  dismissible: {
    type: Boolean,
    default: false
  }
})

defineEmits(['close'])

const alertClasses = computed(() => {
  const classes = {
    success: 'bg-green-50 border border-green-200',
    error: 'bg-red-50 border border-red-200',
    warning: 'bg-yellow-50 border border-yellow-200',
    info: 'bg-blue-50 border border-blue-200',
  }
  return classes[props.type]
})

const iconColor = computed(() => {
  const colors = {
    success: 'text-green-400',
    error: 'text-red-400',
    warning: 'text-yellow-400',
    info: 'text-blue-400',
  }
  return colors[props.type]
})

const textColor = computed(() => {
  const colors = {
    success: 'text-green-800',
    error: 'text-red-800',
    warning: 'text-yellow-800',
    info: 'text-blue-800',
  }
  return colors[props.type]
})

const icon = computed(() => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  }
  return icons[props.type]
})
</script>