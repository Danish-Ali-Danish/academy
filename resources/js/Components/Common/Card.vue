<template>
  <div class="bg-white shadow rounded-lg overflow-hidden">
    <div v-if="$slots.header" class="px-6 py-4 border-b border-gray-200 bg-gray-50">
      <slot name="header" />
    </div>
    
    <div class="p-6">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <slot name="footer" />
    </div>
  </div>
</template>
