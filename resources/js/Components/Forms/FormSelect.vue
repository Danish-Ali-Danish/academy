<template>
  <div>
    <label 
      v-if="label" 
      :for="id" 
      class="block text-sm font-medium text-gray-700 mb-2"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <select
      :id="id"
      :value="modelValue"
      @change="handleChange"
      :required="required"
      :disabled="disabled || loading"
      :multiple="multiple"
      :size="multiple ? size : undefined"
      :class="[
        'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
        error ? 'border-red-500' : '',
        disabled || loading ? 'bg-gray-100 cursor-not-allowed' : '',
        customWidth || 'w-full'
      ]"
    >
      <option value="" v-if="!multiple && placeholder">
        {{ loading ? loadingText : placeholder }}
      </option>
      
      <option 
        v-for="option in options" 
        :key="getOptionValue(option)" 
        :value="getOptionValue(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>
    
    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
    
    <p v-if="hint && !error" class="mt-1 text-xs text-gray-500">
      {{ hint }}
    </p>
    
    <p v-if="multiple && multipleHint" class="mt-1 text-xs text-gray-500">
      {{ multipleHint }}
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
    type: [String, Number, Array],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  // For object arrays: specify which key to use as value
  valueKey: {
    type: String,
    default: 'id'
  },
  // For object arrays: specify which key to use as label
  labelKey: {
    type: String,
    default: 'name'
  },
  placeholder: {
    type: String,
    default: '-- Select --'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Loading...'
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  multiple: {
    type: Boolean,
    default: false
  },
  size: {
    type: Number,
    default: 10
  },
  multipleHint: {
    type: String,
    default: 'Hold Ctrl (Windows) or Command (Mac) to select multiple options'
  },
  customWidth: {
    type: String,
    default: '' // e.g., 'md:w-2/3 lg:w-1/2'
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const getOptionValue = (option) => {
  return typeof option === 'object' ? option[props.valueKey] : option
}

const getOptionLabel = (option) => {
  return typeof option === 'object' ? option[props.labelKey] : option
}

const handleChange = (event) => {
  if (props.multiple) {
    const selectedOptions = Array.from(event.target.selectedOptions).map(opt => opt.value)
    emit('update:modelValue', selectedOptions)
    emit('change', selectedOptions)
  } else {
    emit('update:modelValue', event.target.value)
    emit('change', event.target.value)
  }
}
</script>