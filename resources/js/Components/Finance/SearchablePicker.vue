<template>
  <div class="relative">
    <div class="relative">
      <input
        v-model="query"
        type="text"
        autocomplete="off"
        :placeholder="placeholder"
        :class="[
          'block w-full rounded-lg border-gray-300 pl-10 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
          error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''
        ]"
        @focus="open = true"
        @input="open = true"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="chooseHighlighted"
        @keydown.escape="open = false"
        @blur="hide"
      />
      <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <button
        v-if="modelValue"
        type="button"
        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
        @mousedown.prevent="clear"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div
      v-if="open"
      class="absolute z-40 mt-1 max-h-72 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-xl"
    >
      <div class="border-b border-gray-100 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
        {{ title }}
      </div>
      <button
        v-for="(item, index) in filteredItems"
        :key="item.id"
        type="button"
        class="w-full border-b border-gray-50 px-4 py-3 text-left hover:bg-indigo-50"
        :class="index === highlighted ? 'bg-indigo-50' : ''"
        @mousedown.prevent="select(item)"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-900">{{ item.label }}</p>
            <p v-if="item.subtitle" class="mt-0.5 text-xs text-gray-500">{{ item.subtitle }}</p>
          </div>
          <div class="shrink-0 text-right">
            <p v-if="item.amount_label" class="text-sm font-semibold text-indigo-600">{{ item.amount_label }}</p>
            <p v-if="item.meta" class="mt-0.5 text-xs text-gray-500">{{ item.meta }}</p>
          </div>
        </div>
      </button>
      <div v-if="filteredItems.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
        {{ emptyText }}
      </div>
    </div>

    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  modelValue: { type: [String, Number], default: '' },
  placeholder: { type: String, default: 'Search...' },
  title: { type: String, default: 'Results' },
  emptyText: { type: String, default: 'No records found' },
  error: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'select', 'clear'])

const open = ref(false)
const query = ref('')
const highlighted = ref(0)

const selectedItem = computed(() =>
  props.items.find((item) => String(item.id) === String(props.modelValue))
)

watch(
  () => props.modelValue,
  () => {
    query.value = selectedItem.value?.label || ''
  },
  { immediate: true }
)

const filteredItems = computed(() => {
  const term = query.value.toLowerCase().trim()
  if (!term || selectedItem.value?.label === query.value) {
    return props.items.slice(0, 50)
  }

  return props.items
    .filter((item) =>
      [item.label, item.subtitle, item.meta, item.amount_label, item.search]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
        .includes(term)
    )
    .slice(0, 50)
})

const select = (item) => {
  emit('update:modelValue', item.id)
  emit('select', item)
  query.value = item.label
  open.value = false
}

const clear = () => {
  emit('update:modelValue', '')
  emit('clear')
  query.value = ''
  open.value = true
}

const move = (direction) => {
  if (!open.value) {
    open.value = true
    return
  }

  const max = filteredItems.value.length - 1
  if (max < 0) return
  highlighted.value = Math.min(max, Math.max(0, highlighted.value + direction))
}

const chooseHighlighted = () => {
  const item = filteredItems.value[highlighted.value]
  if (item) select(item)
}

const hide = () => {
  window.setTimeout(() => {
    open.value = false
    query.value = selectedItem.value?.label || ''
  }, 120)
}
</script>
