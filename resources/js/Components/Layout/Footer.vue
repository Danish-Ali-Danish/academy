<template>
  <footer class="bg-white border-t border-gray-200 py-4">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-center text-sm text-gray-500">
        <p>
          &copy; {{ currentYear }} Academy Management System. All rights reserved.
        </p>
        <p class="mt-2 sm:mt-0">
          Version 1.0.0 | Powered by Laravel & Vue
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>