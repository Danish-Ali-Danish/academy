<template>
  <div class="mb-4 sm:mb-6 lg:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
      <!-- Left side: Back button + Title/Description -->
      <div :class="showBackButton ? 'flex items-center gap-3' : ''">
        <!-- Back Button -->
        <button 
          v-if="showBackButton"
          @click="handleBack"
          class="p-2 hover:bg-gray-100 rounded-lg transition-colors flex-shrink-0"
          :title="`Back to ${backButtonText}`"
        >
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>

        <!-- Title & Description -->
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ title }}</h1>
          <p v-if="description" class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
            {{ description }}
          </p>
        </div>
      </div>

      <!-- Right side: Action Button (optional) -->
      <slot name="action">
        <button
          v-if="actionRoute"
          @click="handleAction"
          :class="[
            'w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm',
            actionVariant === 'primary' 
              ? 'bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-lg font-medium'
              : 'bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg font-medium border border-gray-300'
          ]"
        >
          <span class="flex items-center justify-center">
            <svg v-if="actionIcon === 'back'" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <svg v-else-if="actionIcon === 'plus'" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ actionText }}
          </span>
        </button>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  showBackButton: {
    type: Boolean,
    default: false
  },
  backRoute: {
    type: String,
    default: ''
  },
  backButtonText: {
    type: String,
    default: 'list'
  },
  actionRoute: {
    type: String,
    default: ''
  },
  actionText: {
    type: String,
    default: 'Back to List'
  },
  actionVariant: {
    type: String,
    default: 'secondary', // primary or secondary
    validator: (value) => ['primary', 'secondary'].includes(value)
  },
  actionIcon: {
    type: String,
    default: 'back', // back, plus, or none
    validator: (value) => ['back', 'plus', 'none'].includes(value)
  }
})

const emit = defineEmits(['back', 'action'])

const handleBack = () => {
  if (props.backRoute) {
    router.visit(route(props.backRoute))
  }
  emit('back')
}

const handleAction = () => {
  if (props.actionRoute) {
    router.visit(route(props.actionRoute))
  }
  emit('action')
}
</script>