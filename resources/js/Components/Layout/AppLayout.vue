<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar :open="sidebarOpen" :collapsed="sidebarCollapsed" @close="sidebarOpen = false" />

    <!-- Main Content Area -->
    <div :class="[sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'transition-all duration-300 ease-in-out min-h-screen flex flex-col']">
      <!-- Top Header -->
      <Header 
        :collapsed="sidebarCollapsed"
        @toggle-sidebar="sidebarOpen = !sidebarOpen" 
        @toggle-collapse="sidebarCollapsed = !sidebarCollapsed" 
      />

      <!-- Page Content -->
      <main class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
         

         

          <!-- Page Content Slot -->
          <slot />
        </div>
      </main>

      <!-- Footer -->
      <Footer />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Sidebar from './Sidebar.vue'
import Header from './Header.vue'
import Footer from './Footer.vue'
import Alert from '../Common/Alert.vue'

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const page = usePage()
</script>