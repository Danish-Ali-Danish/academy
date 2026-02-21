<template>
  <div 
    :class="[
      'flex items-center justify-center',
      fullHeight ? 'min-h-screen' : '',
      containerClass
    ]"
  >
    <div class="text-center">
      <!-- Spinner -->
      <div 
        :class="[
          'animate-spin rounded-full border-b-2 mx-auto',
          sizeClasses[size],
          colorClasses[color]
        ]"
      ></div>
      
      <!-- Text -->
      <p 
        v-if="text" 
        :class="[
          'mt-3 text-gray-600',
          textSizeClasses[size]
        ]"
      >
        {{ text }}
      </p>
      
      <!-- Slot for custom content -->
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  size: {
    type: String,
    default: 'md', // xs, sm, md, lg, xl
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'indigo', // indigo, blue, green, red, gray
    validator: (value) => ['indigo', 'blue', 'green', 'red', 'gray'].includes(value)
  },
  text: {
    type: String,
    default: ''
  },
  fullHeight: {
    type: Boolean,
    default: false
  },
  containerClass: {
    type: String,
    default: ''
  }
})

const sizeClasses = {
  xs: 'h-4 w-4',
  sm: 'h-6 w-6',
  md: 'h-10 w-10',
  lg: 'h-16 w-16',
  xl: 'h-24 w-24'
}

const textSizeClasses = {
  xs: 'text-xs',
  sm: 'text-xs',
  md: 'text-sm',
  lg: 'text-base',
  xl: 'text-lg'
}

const colorClasses = {
  indigo: 'border-indigo-600',
  blue: 'border-blue-600',
  green: 'border-green-600',
  red: 'border-red-600',
  gray: 'border-gray-600'
}
</script>