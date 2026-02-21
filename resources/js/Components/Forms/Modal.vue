<template>
  <teleport to="body">
    <transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Modal Container -->
        <div class="flex min-h-screen items-center justify-center p-4">
          <!-- Modal Content -->
          <div 
            :class="[
              'relative bg-white rounded-lg shadow-xl transform transition-all w-full',
              sizeClasses[size]
            ]"
            @click.stop
          >
            <!-- Header -->
            <div v-if="$slots.title || title" class="px-4 sm:px-6 py-4 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <slot name="title">
                    <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                  </slot>
                </div>
                <button
                  v-if="showClose"
                  @click="handleClose"
                  class="ml-4 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Body -->
            <div :class="['px-4 sm:px-6', bodyPadding]">
              <slot></slot>
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
              <div class="flex items-center justify-end gap-3">
                <slot name="footer"></slot>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { defineProps, defineEmits, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl, full
    validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value)
  },
  showClose: {
    type: Boolean,
    default: true
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  bodyPadding: {
    type: String,
    default: 'py-4 sm:py-6'
  }
})

const emit = defineEmits(['close', 'update:show'])

const sizeClasses = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  full: 'max-w-7xl'
}

const handleClose = () => {
  emit('close')
  emit('update:show', false)
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop) {
    handleClose()
  }
}

// Prevent body scroll when modal is open
watch(() => props.show, (isShown) => {
  if (isShown) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>