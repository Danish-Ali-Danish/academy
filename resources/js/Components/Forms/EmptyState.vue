<template>
  <div 
    :class="[
      'flex items-center justify-center rounded-lg',
      padding,
      typeClasses[type].border,
      typeClasses[type].bg
    ]"
  >
    <div :class="['text-center', typeClasses[type].text]">
      <!-- Icon -->
      <div v-if="showIcon">
        <!-- Loading State -->
        <div v-if="type === 'loading'" class="mx-auto">
          <div 
            :class="[
              'animate-spin rounded-full border-b-2 mx-auto',
              iconSizeClasses[iconSize],
              'border-gray-600'
            ]"
          ></div>
        </div>

        <!-- Other States -->
        <svg 
          v-else
          :class="[
            'mx-auto',
            iconSizeClasses[iconSize],
            typeClasses[type].iconColor
          ]" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <!-- Empty/No Data Icon -->
          <path 
            v-if="icon === 'clipboard' || type === 'empty'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
          />
          
          <!-- Warning Icon -->
          <path 
            v-else-if="icon === 'warning' || type === 'warning'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
          
          <!-- Info Icon -->
          <path 
            v-else-if="icon === 'info' || type === 'info'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
          
          <!-- Search/Not Found Icon -->
          <path 
            v-else-if="icon === 'search'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
          
          <!-- Inbox Icon -->
          <path 
            v-else-if="icon === 'inbox'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
      </div>
      
      <!-- Title -->
      <p 
        v-if="title"
        :class="[
          'mt-2 font-medium',
          titleSizeClasses[titleSize]
        ]"
      >
        {{ title }}
      </p>
      
      <!-- Description -->
      <p 
        v-if="description"
        :class="[
          'mt-1',
          descriptionSizeClasses[descriptionSize],
          type === 'empty' || type === 'info' ? 'text-gray-500' : ''
        ]"
      >
        {{ description }}
      </p>
      
      <!-- Action Slot -->
      <div v-if="$slots.action" class="mt-4">
        <slot name="action"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  type: {
    type: String,
    default: 'empty', // empty, loading, warning, info
    validator: (value) => ['empty', 'loading', 'warning', 'info'].includes(value)
  },
  icon: {
    type: String,
    default: '', // clipboard, warning, info, search, inbox
  },
  title: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  iconSize: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  titleSize: {
    type: String,
    default: 'base', // sm, base, lg
    validator: (value) => ['sm', 'base', 'lg'].includes(value)
  },
  descriptionSize: {
    type: String,
    default: 'sm', // xs, sm, base
    validator: (value) => ['xs', 'sm', 'base'].includes(value)
  },
  padding: {
    type: String,
    default: 'py-12' // py-8, py-12, py-16
  }
})

const typeClasses = {
  empty: {
    border: 'border-2 border-dashed border-gray-300',
    bg: 'bg-gray-50',
    text: 'text-gray-500',
    iconColor: 'text-gray-400'
  },
  loading: {
    border: 'border-2 border-dashed border-gray-300',
    bg: 'bg-gray-50',
    text: 'text-gray-600',
    iconColor: 'text-gray-600'
  },
  warning: {
    border: 'border-2 border-dashed border-yellow-300',
    bg: 'bg-yellow-50',
    text: 'text-yellow-700',
    iconColor: 'text-yellow-500'
  },
  info: {
    border: 'border-2 border-dashed border-blue-300',
    bg: 'bg-blue-50',
    text: 'text-blue-700',
    iconColor: 'text-blue-500'
  }
}

const iconSizeClasses = {
  sm: 'h-8 w-8',
  md: 'h-12 w-12',
  lg: 'h-16 w-16'
}

const titleSizeClasses = {
  sm: 'text-sm',
  base: 'text-base',
  lg: 'text-lg'
}

const descriptionSizeClasses = {
  xs: 'text-xs',
  sm: 'text-sm',
  base: 'text-base'
}
</script>