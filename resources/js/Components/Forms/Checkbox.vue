<template>
  <div class="flex items-center">
    <input
      :id="id"
      type="checkbox"
      :checked="modelValue"
      @change="$emit('update:modelValue', $event.target.checked)"
      :disabled="disabled"
      class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
    />
    <label v-if="label" :for="id" class="ml-2 block text-sm text-gray-700">
      {{ label }}
    </label>
  </div>
</template>

<script setup>
defineProps({
  id: String,
  label: String,
  modelValue: Boolean,
  disabled: Boolean
})

defineEmits(['update:modelValue'])
</script>