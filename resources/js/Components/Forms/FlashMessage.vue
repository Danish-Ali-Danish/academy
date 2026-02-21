<template>
  <transition name="fade">
    <div
      v-if="show && message"
      :class="[
        'mb-4 px-4 py-3 rounded-lg relative animate-fade-in',
        typeClasses[type].bg,
        typeClasses[type].border,
        typeClasses[type].text
      ]"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <!-- Icon -->
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path v-if="type === 'success'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            <path v-else-if="type === 'error'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            <path v-else fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          
          <span class="text-sm">{{ message }}</span>
        </div>
        
        <!-- Close Button -->
        <button 
          @click="$emit('close')" 
          :class="typeClasses[type].closeBtn"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

defineProps({
  show: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'success', // success, error, warning, info
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  }
})

defineEmits(['close'])

const typeClasses = {
  success: {
    bg: 'bg-green-100',
    border: 'border border-green-400',
    text: 'text-green-700',
    closeBtn: 'text-green-700 hover:text-green-900'
  },
  error: {
    bg: 'bg-red-100',
    border: 'border border-red-400',
    text: 'text-red-700',
    closeBtn: 'text-red-700 hover:text-red-900'
  },
  warning: {
    bg: 'bg-yellow-100',
    border: 'border border-yellow-400',
    text: 'text-yellow-700',
    closeBtn: 'text-yellow-700 hover:text-yellow-900'
  },
  info: {
    bg: 'bg-blue-100',
    border: 'border border-blue-400',
    text: 'text-blue-700',
    closeBtn: 'text-blue-700 hover:text-blue-900'
  }
}
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>