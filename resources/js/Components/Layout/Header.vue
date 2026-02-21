<!-- ========================================
     HEADER.VUE
     File: resources/js/Components/Layout/Header.vue
     ======================================== -->
<template>
  <header class="sticky top-0 z-40 bg-white shadow-sm">
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
      
      <!-- Mobile menu button -->
      <button
        @click="$emit('toggle-sidebar')"
        class="lg:hidden -m-2.5 p-2.5 text-gray-700"
      >
        <Bars3Icon class="h-6 w-6" />
      </button>

      <!-- Breadcrumb / Page Title -->
      <div class="flex-1 lg:flex lg:items-center">
        <h2 class="text-lg font-semibold text-gray-900">
          {{ pageTitle }}
        </h2>
      </div>

      <!-- Right side items -->
      <div class="flex items-center gap-x-4">
        
        <!-- Notifications -->
        <button class="relative p-2 text-gray-400 hover:text-gray-500">
          <BellIcon class="h-6 w-6" />
          <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
        </button>

        <!-- User Menu Dropdown -->
        <Menu as="div" class="relative">
          <MenuButton class="flex items-center gap-x-2 rounded-full">
            <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold">
              {{ $page.props.auth.user.name.charAt(0) }}
            </div>
            <ChevronDownIcon class="h-4 w-4 text-gray-400" />
          </MenuButton>

          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
          >
            <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
              <MenuItem v-slot="{ active }">
                <Link
                  href="#"
                  :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                >
                  Your Profile
                </Link>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <Link
                  href="#"
                  :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                >
                  Settings
                </Link>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <Link
                  :href="route('logout')"
                  method="post"
                  as="button"
                  :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-4 py-2 text-sm text-gray-700']"
                >
                  Sign out
                </Link>
              </MenuItem>
            </MenuItems>
          </transition>
        </Menu>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Bars3Icon, BellIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

defineEmits(['toggle-sidebar'])

const page = usePage()

const pageTitle = computed(() => {
  const currentRoute = route().current()
  
  const titles = {
    'dashboard': 'Dashboard',
    'branches.index': 'Branches',
    'branches.create': 'Create Branch',
    'branches.edit': 'Edit Branch',
    'classes.index': 'Classes',
    'classes.create': 'Create Class',
    'sections.index': 'Sections',
    'students.index': 'Students',
  }
  
  return titles[currentRoute] || 'Academy Management'
})
</script>
