<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :class="inputClasses"
    />
    
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: String,
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  modelValue: [String, Number],
  placeholder: String,
  error: String,
  hint: String,
  required: Boolean,
  disabled: Boolean
})

defineEmits(['update:modelValue'])

const inputClasses = computed(() => {
  const base = 'block w-full rounded-md shadow-sm sm:text-sm transition-colors'
  const normal = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
  const errorClass = 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
  const disabledClass = 'bg-gray-100 cursor-not-allowed'
  
  if (props.disabled) return `${base} ${disabledClass}`
  if (props.error) return `${base} ${errorClass}`
  return `${base} ${normal}`
})
</script>