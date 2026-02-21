<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <select
      :id="id"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :disabled="disabled"
      :required="required"
      :class="selectClasses"
    >
      <option value="" v-if="placeholder">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="optionValue ? option[optionValue] : option"
        :value="optionValue ? option[optionValue] : option"
      >
        {{ optionLabel ? option[optionLabel] : option }}
      </option>
    </select>
    
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: String,
  label: String,
  modelValue: [String, Number],
  options: {
    type: Array,
    default: () => []
  },
  optionValue: String,
  optionLabel: String,
  placeholder: String,
  error: String,
  hint: String,
  required: Boolean,
  disabled: Boolean
})

defineEmits(['update:modelValue'])

const selectClasses = computed(() => {
  const base = 'block w-full rounded-md shadow-sm sm:text-sm transition-colors'
  const normal = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
  const errorClass = 'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500'
  const disabledClass = 'bg-gray-100 cursor-not-allowed'
  
  if (props.disabled) return `${base} ${disabledClass}`
  if (props.error) return `${base} ${errorClass}`
  return `${base} ${normal}`
})
</script>