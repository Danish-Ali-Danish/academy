<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>
    
    <div :class="inline ? 'flex items-center gap-4 flex-wrap' : 'space-y-2'">
      <!-- Single Checkbox -->
      <div v-if="!options || options.length === 0" class="flex items-center">
        <input
          :id="id"
          type="checkbox"
          :checked="modelValue"
          @change="$emit('update:modelValue', $event.target.checked)"
          :disabled="disabled"
          :class="[
            'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
        />
        <label 
          :for="id" 
          :class="[
            'ml-2 block text-sm text-gray-900',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
        >
          {{ checkboxLabel }}
        </label>
      </div>

      <!-- Multiple Checkboxes (Group) -->
      <div 
        v-else
        v-for="option in options" 
        :key="getOptionValue(option)"
        class="flex items-center"
      >
        <input
          :id="`${id}-${getOptionValue(option)}`"
          type="checkbox"
          :value="getOptionValue(option)"
          :checked="isChecked(option)"
          @change="handleChange($event, option)"
          :disabled="disabled"
          :class="[
            'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
        />
        <label 
          :for="`${id}-${getOptionValue(option)}`"
          :class="[
            'ml-2 block text-sm text-gray-900',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
        >
          {{ getOptionLabel(option) }}
        </label>
      </div>
    </div>
    
    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
    
    <p v-if="hint && !error" class="mt-1 text-xs text-gray-500">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  label: {
    type: String,
    default: ''
  },
  modelValue: {
    type: [Boolean, Array],
    default: false
  },
  checkboxLabel: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  // For checkbox groups
  options: {
    type: Array,
    default: () => []
  },
  valueKey: {
    type: String,
    default: 'id'
  },
  labelKey: {
    type: String,
    default: 'name'
  },
  inline: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const getOptionValue = (option) => {
  return typeof option === 'object' ? option[props.valueKey] : option
}

const getOptionLabel = (option) => {
  return typeof option === 'object' ? option[props.labelKey] : option
}

const isChecked = (option) => {
  if (!Array.isArray(props.modelValue)) return false
  const value = getOptionValue(option)
  return props.modelValue.includes(value)
}

const handleChange = (event, option) => {
  const value = getOptionValue(option)
  let newValue = [...(props.modelValue || [])]
  
  if (event.target.checked) {
    if (!newValue.includes(value)) {
      newValue.push(value)
    }
  } else {
    newValue = newValue.filter(v => v !== value)
  }
  
  emit('update:modelValue', newValue)
}
</script>