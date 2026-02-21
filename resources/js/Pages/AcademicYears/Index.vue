<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Academic Years Management</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Manage all academic year sessions</p>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="showSuccessMessage && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              {{ $page.props.flash.success }}
            </div>
            <button @click="showSuccessMessage = false" class="text-green-700 hover:text-green-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>
        <div v-if="showErrorMessage && $page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              {{ $page.props.flash.error }}
            </div>
            <button @click="showErrorMessage = false" class="text-red-700 hover:text-red-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Create/Edit Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="mb-4">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-800">
              {{ editMode ? 'Edit Academic Year' : 'Create New Academic Year' }}
            </h2>
          </div>

          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              <!-- Year Name -->
              <div class="col-span-2">
                <label for="year_name" class="block text-sm font-medium text-gray-700 mb-2">
                  Year Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="year_name"
                  v-model="form.year_name"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.year_name }"
                  placeholder="e.g., 2024-2025"
                  required
                />
                <p v-if="form.errors.year_name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.year_name }}
                </p>
              </div>

              <!-- Start Date -->
              <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                  Start Date <span class="text-red-500">*</span>
                </label>
                <input
                  id="start_date"
                  v-model="form.start_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.start_date }"
                  required
                />
                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                  {{ form.errors.start_date }}
                </p>
              </div>

              <!-- End Date -->
              <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                  End Date <span class="text-red-500">*</span>
                </label>
                <input
                  id="end_date"
                  v-model="form.end_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.end_date }"
                  :min="form.start_date"
                  required
                />
                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                  {{ form.errors.end_date }}
                </p>
              </div>

              <!-- Duration Display -->
              <div v-if="duration" class="col-span-2">
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                  <p class="text-sm text-blue-800">
                    <span class="font-semibold">Duration:</span> {{ duration }} days
                  </p>
                </div>
              </div>

              <!-- Is Active -->
              <div class="col-span-2">
                <div class="flex items-center">
                  <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Set as Active Academic Year
                  </label>
                </div>
                <p class="mt-1 text-sm text-gray-500">
                  Note: Setting this as active will automatically deactivate other academic years.
                </p>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-6 flex items-center justify-end gap-3 sm:gap-4">
              <Button 
                v-if="editMode"
                type="button"
                variant="secondary"
                @click="cancelEdit"
                class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm"
              >
                Cancel
              </Button>
              <Button 
                type="submit"
                variant="primary"
                :loading="form.processing"
                class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm"
              >
                <span v-if="!form.processing">{{ editMode ? 'Update Academic Year' : 'Create Academic Year' }}</span>
                <span v-else>{{ editMode ? 'Updating...' : 'Creating...' }}</span>
              </Button>
            </div>
          </form>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <div>
              <Input
                v-model="filters.search"
                placeholder="Search academic years..."
                @input="searchDebounced"
                class="w-full text-sm"
              />
            </div>
            
            <div>
              <select
                v-model="filters.is_active"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>

            <Button 
              variant="secondary" 
              @click="resetFilters"
              class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all duration-200 text-sm"
            >
              Reset Filters
            </Button>
          </div>
        </div>

        <!-- Desktop/Tablet Table View -->
        <div class="hidden md:block bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
          <!-- Table Header with Search -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
              <span class="text-xs sm:text-sm text-gray-700">Show</span>
              <select 
                v-model="perPage" 
                @change="changePerPage"
                class="px-3 sm:px-6 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-xs sm:text-sm text-gray-700">entries</span>
            </div>

            <div class="w-full sm:w-64">
              <div class="relative">
                <input
                  v-model="tableSearch"
                  @input="tableSearchDebounced"
                  type="text"
                  placeholder="Search in table..."
                  class="w-full pl-9 sm:pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                />
                <svg class="absolute left-2.5 sm:left-3 top-2.5 h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table id="academic-years-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    #
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Year Name
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Start Date
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    End Date
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Duration
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Status
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white text-center divide-y divide-gray-100">
                <!-- DataTables will populate this -->
              </tbody>
            </table>
          </div>

          <!-- Table Footer -->
          <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 bg-gray-50 gap-3 sm:gap-4">
            <div class="text-xs sm:text-sm text-gray-600" id="table-info">
              <!-- Info will be inserted here -->
            </div>
            <div id="table-pagination">
              <!-- Pagination will be inserted here -->
            </div>
          </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3 sm:space-y-4">
          <!-- Loading State -->
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="mobileYears.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No academic years found</p>
            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
          </div>

          <!-- Year Cards -->
          <div v-else v-for="(year, index) in mobileYears" :key="year.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                    <h3 class="text-base font-semibold text-gray-900">{{ year.year_name }}</h3>
                  </div>
                </div>
                <span :class="getStatusClass(year.is_active)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ year.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>

              <!-- Details -->
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <span class="text-gray-600">Start: {{ formatDate(year.start_date) }}</span>
                </div>
                
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <span class="text-gray-600">End: {{ formatDate(year.end_date) }}</span>
                </div>

                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-gray-600">Duration: {{ calculateDuration(year.start_date, year.end_date) }} days</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <button 
                  v-if="!year.is_active"
                  @click="activateYear(year.id)"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Activate
                </button>
                <button 
                  @click="editYearMobile(year)"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Edit
                </button>
                <button 
                  @click="() => { yearToDelete = year.id; showDeleteModal = true; }"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div v-if="mobileYears.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
          <div class="flex items-center justify-between">
            <button 
              @click="prevPage"
              :disabled="mobileCurrentPage === 1 || mobileLoading"
              class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed bg-white hover:bg-gray-50 transition-colors flex items-center gap-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
              Previous
            </button>
            
            <div class="text-center">
              <div class="text-sm font-medium text-gray-900">Page {{ mobileCurrentPage }} of {{ mobileTotalPages }}</div>
              <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total years</div>
            </div>
            
            <button 
              @click="nextPage"
              :disabled="mobileCurrentPage === mobileTotalPages || mobileLoading"
              class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed bg-white hover:bg-gray-50 transition-colors flex items-center gap-1"
            >
              Next
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>

      </div>

      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-red-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">Delete Academic Year</span>
          </div>
        </template>
        
        <p class="text-xs sm:text-sm text-gray-600 mt-2">
          Are you sure you want to delete this academic year? This action cannot be undone.
        </p>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button 
              variant="secondary" 
              @click="showDeleteModal = false"
              class="w-full sm:w-auto px-4 sm:px-6 shadow-sm hover:shadow-md transition-all text-sm"
            >
              Cancel
            </Button>
            <Button 
              variant="danger" 
              @click="confirmDelete" 
              :loading="deleting"
              class="w-full sm:w-auto px-4 sm:px-6 shadow-md hover:shadow-lg transition-all text-sm"
            >
              <span v-if="!deleting">Delete Year</span>
              <span v-else>Deleting...</span>
            </Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

// State
const showDeleteModal = ref(false)
const deleting = ref(false)
const yearToDelete = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobileYears = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
const editMode = ref(false)
const editingId = ref(null)
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)
let table = null

const filters = reactive({
  search: '',
  is_active: ''
})

// Form
const form = useForm({
  year_name: '',
  start_date: '',
  end_date: '',
  is_active: false,
})

// Watch for flash messages and auto-dismiss after 10 seconds
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => {
      showSuccessMessage.value = false
    }, 10000) // 10 seconds
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => {
      showErrorMessage.value = false
    }, 10000) // 10 seconds
  }
}, { deep: true, immediate: true })

// Calculate duration
const duration = computed(() => {
  if (!form.start_date || !form.end_date) return null
  
  const start = new Date(form.start_date)
  const end = new Date(form.end_date)
  const diffTime = Math.abs(end - start)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  return diffDays
})

// Helper functions
const getStatusClass = (isActive) => {
  return isActive 
    ? 'bg-green-100 text-green-800' 
    : 'bg-red-100 text-red-800'
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const calculateDuration = (startDate, endDate) => {
  if (!startDate || !endDate) return 0
  const start = new Date(startDate)
  const end = new Date(endDate)
  const diffTime = Math.abs(end - start)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

// Load mobile data
const loadMobileData = async () => {
  mobileLoading.value = true
  
  try {
    const params = {
      page: mobileCurrentPage.value,
      per_page: perPage.value,
      mobile: 1
    }
    
    if (filters.search) params.search = filters.search
    if (tableSearch.value) params.search = tableSearch.value
    if (filters.is_active !== '') params.is_active = filters.is_active
    
    const response = await axios.get(route('academic-years.index'), {
      params,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    
    if (response.data) {
      if (response.data.data) {
        mobileYears.value = response.data.data
        mobileCurrentPage.value = response.data.current_page || 1
        mobileTotalPages.value = response.data.last_page || 1
        mobileTotal.value = response.data.total || 0
        mobileOffset.value = response.data.from ? response.data.from - 1 : 0
      } else if (Array.isArray(response.data)) {
        mobileYears.value = response.data
        mobileTotalPages.value = 1
        mobileTotal.value = response.data.length
        mobileOffset.value = 0
      }
    }
  } catch (error) {
    console.error('Error loading mobile data:', error)
    mobileYears.value = []
    mobileTotal.value = 0
  } finally {
    mobileLoading.value = false
  }
}

// Initialize on mount
onMounted(() => {
  loadMobileData()

  // Initialize desktop table
  table = $('#academic-years-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('academic-years.index'),
      data: function(d) {
        d.search.value = filters.search || tableSearch.value
        if (filters.is_active !== '') {
          d.is_active = filters.is_active
        }
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'year_name', name: 'year_name' },
      { data: 'start_date', name: 'start_date' },
      { data: 'end_date', name: 'end_date' },
      { data: 'duration', name: 'duration', orderable: false, searchable: false },
      { data: 'is_active', name: 'is_active', orderable: false },
      { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[1, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><p class="mt-2 text-sm font-medium">No academic years found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      infoFiltered: '(filtered from _MAX_ total entries)',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div></div>',
      paginate: {
        first: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
        last: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
        next: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
        previous: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
      }
    },
    drawCallback: function () {
      const info = $('#academic-years-table_info')
      $('#table-info').empty().append(info)

      const paginate = $('#academic-years-table_paginate')
      $('#table-pagination').empty().append(paginate)
    }
  })
})

// Form submit
const submit = () => {
  if (editMode.value) {
    form.put(route('academic-years.update', editingId.value), {
      preserveScroll: true,
      onSuccess: () => {
        resetForm()
        loadData()
      }
    })
  } else {
    form.post(route('academic-years.store'), {
      preserveScroll: true,
      onSuccess: () => {
        resetForm()
        loadData()
      }
    })
  }
}

// Reset form
const resetForm = () => {
  form.reset()
  form.clearErrors()
  editMode.value = false
  editingId.value = null
}

// Cancel edit
const cancelEdit = () => {
  resetForm()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Edit year (called from table)
window.editYear = (year) => {
  editMode.value = true
  editingId.value = year.id
  
  form.year_name = year.year_name
  form.start_date = year.start_date_raw
  form.end_date = year.end_date_raw
  form.is_active = year.is_active
  
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Edit year mobile
const editYearMobile = (year) => {
  editMode.value = true
  editingId.value = year.id
  
  form.year_name = year.year_name
  form.start_date = year.start_date
  form.end_date = year.end_date
  form.is_active = year.is_active
  
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Activate year
const activateYear = (id) => {
  if (confirm('Are you sure you want to activate this academic year? This will deactivate all other years.')) {
    router.post(route('academic-years.activate', id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        loadData()
      }
    })
  }
}

window.activateYear = activateYear

// Delete year
const confirmDelete = () => {
  deleting.value = true
  router.delete(route('academic-years.destroy', yearToDelete.value), {
    onSuccess: () => {
      showDeleteModal.value = false
      deleting.value = false
      loadData()
    },
    onError: () => {
      deleting.value = false
    }
  })
}

window.deleteYear = (id) => {
  yearToDelete.value = id
  showDeleteModal.value = true
}

// Mobile pagination
const prevPage = () => {
  if (mobileCurrentPage.value > 1 && !mobileLoading.value) {
    mobileCurrentPage.value--
    loadMobileData()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const nextPage = () => {
  if (mobileCurrentPage.value < mobileTotalPages.value && !mobileLoading.value) {
    mobileCurrentPage.value++
    loadMobileData()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Table search with debounce
let tableSearchTimeout = null
const tableSearchDebounced = () => {
  clearTimeout(tableSearchTimeout)
  tableSearchTimeout = setTimeout(() => {
    loadData()
  }, 500)
}

// Filter search with debounce
let searchTimeout = null
const searchDebounced = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadData()
  }, 500)
}

// Change per page
const changePerPage = () => {
  if (table) {
    table.page.len(perPage.value).draw()
  }
  mobileCurrentPage.value = 1
  loadMobileData()
}

// Reload table
const loadData = () => {
  if (table) {
    table.ajax.reload()
  }
  mobileCurrentPage.value = 1
  loadMobileData()
}

// Reset filters
const resetFilters = () => {
  filters.search = ''
  filters.is_active = ''
  tableSearch.value = ''
  loadData()
}
</script>

<style scoped>
:deep(.dataTables_info) {
  font-size: 0.875rem;
  color: #4b5563;
  font-weight: 500;
}

:deep(.dataTables_paginate) {
  display: flex;
  justify-content: flex-end;
  gap: 0.25rem;
  flex-wrap: wrap;
}

:deep(.paginate_button) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

:deep(.paginate_button:hover:not(.disabled)) {
  background: #f3f4f6;
  border-color: #9ca3af;
}

:deep(.paginate_button.current) {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

:deep(.paginate_button.current:hover) {
  background: #1d4ed8;
  border-color: #1d4ed8;
}

:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}

:deep(#academic-years-table_info),
:deep(#academic-years-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#academic-years-table tbody td) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

@media (min-width: 640px) {
  :deep(#academic-years-table tbody td) {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
  }
}

@media (max-width: 1024px) {
  :deep(#academic-years-table) {
    font-size: 0.813rem;
  }
  
  :deep(#academic-years-table th),
  :deep(#academic-years-table td) {
    padding: 0.5rem;
  }
}
</style>