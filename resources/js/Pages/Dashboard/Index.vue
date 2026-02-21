<template>
  <AppLayout>
    <div class="space-y-6">
      
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
          <p class="mt-1 text-sm text-gray-500">Welcome back, {{ $page.props.auth.user.name }}!</p>
        </div>
        <div class="text-sm text-gray-500">
          {{ currentDate }}
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        
        <!-- Total Branches -->
        <Card>
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                <BuildingOfficeIcon class="h-6 w-6" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Branches</dt>
                <dd class="flex items-baseline">
                  <div class="text-2xl font-semibold text-gray-900">{{ stats.total_branches }}</div>
                </dd>
              </dl>
            </div>
          </div>
        </Card>

        <!-- Total Students -->
        <Card>
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white">
                <UserGroupIcon class="h-6 w-6" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                <dd class="flex items-baseline">
                  <div class="text-2xl font-semibold text-gray-900">{{ stats.total_students }}</div>
                </dd>
              </dl>
            </div>
          </div>
        </Card>

        <!-- Total Teachers -->
        <Card>
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-yellow-500 text-white">
                <AcademicCapIcon class="h-6 w-6" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Teachers</dt>
                <dd class="flex items-baseline">
                  <div class="text-2xl font-semibold text-gray-900">{{ stats.total_teachers }}</div>
                </dd>
              </dl>
            </div>
          </div>
        </Card>

        <!-- Active Classes -->
        <Card>
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-500 text-white">
                <BookOpenIcon class="h-6 w-6" />
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Classes</dt>
                <dd class="flex items-baseline">
                  <div class="text-2xl font-semibold text-gray-900">{{ stats.active_classes }}</div>
                </dd>
              </dl>
            </div>
          </div>
        </Card>
      </div>

      <!-- Quick Actions & Recent Activity -->
      <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        
        <!-- Quick Actions -->
        <Card>
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
          </template>

          <div class="space-y-3">
            <a href="#" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
              <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-indigo-100 text-indigo-600 group-hover:bg-indigo-200">
                  <UserPlusIcon class="h-5 w-5" />
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Add New Student</p>
                <p class="text-xs text-gray-500">Register a new student</p>
              </div>
            </a>

            <Link :href="route('branches.create')" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
              <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-green-100 text-green-600 group-hover:bg-green-200">
                  <BuildingOfficeIcon class="h-5 w-5" />
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Add New Branch</p>
                <p class="text-xs text-gray-500">Create a new branch</p>
              </div>
            </Link>

            <Link :href="route('classes.create')" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors group">
              <div class="flex-shrink-0">
                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-yellow-100 text-yellow-600 group-hover:bg-yellow-200">
                  <AcademicCapIcon class="h-5 w-5" />
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Add New Class</p>
                <p class="text-xs text-gray-500">Setup a new class</p>
              </div>
            </Link>
          </div>
        </Card>

        <!-- Recent Activity / Announcements -->
        <Card>
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
          </template>

          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                  <CheckCircleIcon class="h-5 w-5 text-indigo-600" />
                </div>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">System Updated</p>
                <p class="text-sm text-gray-500">Version 1.0.0 is now live</p>
                <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                  <UserGroupIcon class="h-5 w-5 text-green-600" />
                </div>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">New Students Added</p>
                <p class="text-sm text-gray-500">5 students registered today</p>
                <p class="text-xs text-gray-400 mt-1">5 hours ago</p>
              </div>
            </div>

            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                  <BellIcon class="h-5 w-5 text-yellow-600" />
                </div>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Fee Payment Reminder</p>
                <p class="text-sm text-gray-500">Send reminders for pending fees</p>
                <p class="text-xs text-gray-400 mt-1">1 day ago</p>
              </div>
            </div>
          </div>
        </Card>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Card from '@/Components/Common/Card.vue'
import {
  BuildingOfficeIcon,
  UserGroupIcon,
  AcademicCapIcon,
  BookOpenIcon,
  UserPlusIcon,
  CheckCircleIcon,
  BellIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_branches: 0,
      total_students: 0,
      total_teachers: 0,
      active_classes: 0
    })
  }
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})
</script>