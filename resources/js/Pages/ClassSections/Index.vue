<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Class Sections Management</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Manage class sections, capacity, and assignments
              </p>
            </div>
            <div>
              <Button 
                @click="router.visit(route('class-sections.create'))"
                variant="primary"
                class="px-4 sm:px-6 py-2 sm:py-2.5 text-sm font-medium"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Assign New Sections
              </Button>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="showSuccessMessage && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative animate-fade-in">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm">{{ $page.props.flash.success }}</span>
            </div>
            <button @click="showSuccessMessage = false" class="text-green-700 hover:text-green-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <div v-if="showErrorMessage && $page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative animate-fade-in">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm">{{ $page.props.flash.error }}</span>
            </div>
            <button @click="showErrorMessage = false" class="text-red-700 hover:text-red-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800">Filter Sections</h3>
            <Button 
              variant="secondary" 
              @click="resetFilters"
              class="text-xs px-3 py-1.5"
            >
              Reset All
            </Button>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Search -->
            <div>
              <Input
                v-model="filters.search"
                placeholder="Search sections..."
                @input="searchDebounced"
                class="w-full text-sm"
              />
            </div>
            
            <!-- Branch Filter -->
            <div>
              <select
                v-model="filters.branch_id"
                @change="onFilterBranchChange"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Branches</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.branch_name }}
                </option>
              </select>
            </div>

            <!-- Class Filter -->
            <div>
              <select
                v-model="filters.branch_class_id"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                :disabled="!filters.branch_id || loadingFilterClasses"
              >
                <option value="">All Classes</option>
                <option v-for="bc in filterClasses" :key="bc.id" :value="bc.id">
                  {{ bc.class_name }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <select
                v-model="filters.is_active"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Desktop/Tablet Table View -->
        <div class="hidden md:block bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
          <!-- Table Header -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
              <span class="text-xs sm:text-sm text-gray-700">Show</span>
              <select 
                v-model="perPage" 
                @change="changePerPage"
                class="px-3 sm:px-6 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm"
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
                  class="w-full pl-9 sm:pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm"
                />
                <svg class="absolute left-2.5 sm:left-3 top-2.5 h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table id="class-sections-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    #
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Branch & Class
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Section
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Capacity
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
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="mobileClassSections.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No class sections found</p>
            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
          </div>

          <!-- Class Section Cards -->
          <div v-else v-for="(classSection, index) in mobileClassSections" :key="classSection.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                  </div>
                  <h3 class="text-sm font-semibold text-gray-900">
                    {{ classSection.branch_class.branch.branch_name }}
                  </h3>
                  <p class="text-xs text-gray-600 mt-0.5">
                    {{ classSection.branch_class.class.class_name }}
                  </p>
                </div>
                <span :class="getStatusClass(classSection.is_active)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ classSection.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>

              <!-- Details -->
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <div class="flex items-center justify-between text-xs sm:text-sm">
                  <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-gray-600">Section:</span>
                  </div>
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                    {{ classSection.section.section_name }}
                  </span>
                </div>
                
                <div class="flex items-center justify-between text-xs sm:text-sm">
                  <div class="flex items-center">
                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-gray-600">Capacity:</span>
                  </div>
                  <span class="px-2 py-1 text-xs font-medium rounded-lg bg-blue-100 text-blue-800">
                    {{ classSection.capacity }} seats
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <button 
                  @click="router.visit(route('class-sections.edit', classSection.id))"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Edit
                </button>
                <button 
                  @click="() => { classSectionToDelete = classSection.id; showDeleteModal = true; }"
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
        <div v-if="mobileClassSections.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
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
              <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total sections</div>
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
            <span class="text-base sm:text-lg font-semibold text-gray-900">Delete Class Section</span>
          </div>
        </template>
        
        <p class="text-xs sm:text-sm text-gray-600 mt-2">
          Are you sure you want to delete this class section? This action cannot be undone.
        </p>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button 
              variant="secondary" 
              @click="showDeleteModal = false"
              class="w-full sm:w-auto px-4 sm:px-6 text-sm"
            >
              Cancel
            </Button>
            <Button 
              variant="danger" 
              @click="confirmDelete" 
              :loading="deleting"
              class="w-full sm:w-auto px-4 sm:px-6 text-sm"
            >
              <span v-if="!deleting">Delete Section</span>
              <span v-else>Deleting...</span>
            </Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

// Props
const props = defineProps({
  branches: Array,
})

// State
const showDeleteModal = ref(false)
const deleting = ref(false)
const classSectionToDelete = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobileClassSections = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)

// Filter cascading state
const filterClasses = ref([])
const loadingFilterClasses = ref(false)

let table = null
let tableSearchTimeout = null
let searchTimeout = null
let abortController = null

const filters = reactive({
  search: '',
  branch_id: '',
  branch_class_id: '',
  is_active: ''
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => {
      showSuccessMessage.value = false
    }, 5000)
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => {
      showErrorMessage.value = false
    }, 5000)
  }
}, { deep: true, immediate: true })

// Helper functions
const getStatusClass = (isActive) => {
  return isActive 
    ? 'bg-green-100 text-green-800' 
    : 'bg-gray-100 text-gray-800'
}

// Filter branch change handler
const onFilterBranchChange = async () => {
  filters.branch_class_id = ''
  filterClasses.value = []
  
  if (!filters.branch_id) {
    loadData()
    return
  }
  
  loadingFilterClasses.value = true
  
  try {
    const response = await axios.get(route('class-sections.classes-by-branch'), {
      params: { branch_id: filters.branch_id }
    })
    filterClasses.value = response.data
  } catch (error) {
    console.error('Error loading filter classes:', error)
  } finally {
    loadingFilterClasses.value = false
  }
  
  loadData()
}

// Load mobile data
const loadMobileData = async () => {
  // Cancel previous request if exists
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()
  
  mobileLoading.value = true
  
  try {
    const params = {
      page: mobileCurrentPage.value,
      per_page: perPage.value,
      mobile: 1
    }
    
    if (filters.search) params.search = filters.search
    if (tableSearch.value) params.search = tableSearch.value
    if (filters.branch_id) params.branch_id = filters.branch_id
    if (filters.branch_class_id) params.branch_class_id = filters.branch_class_id
    if (filters.is_active !== '') params.is_active = filters.is_active
    
    const response = await axios.get(route('class-sections.index'), {
      params,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      signal: abortController.signal
    })
    
    if (response.data) {
      if (response.data.data) {
        mobileClassSections.value = response.data.data
        mobileCurrentPage.value = response.data.current_page || 1
        mobileTotalPages.value = response.data.last_page || 1
        mobileTotal.value = response.data.total || 0
        mobileOffset.value = response.data.from ? response.data.from - 1 : 0
      } else if (Array.isArray(response.data)) {
        mobileClassSections.value = response.data
        mobileTotalPages.value = 1
        mobileTotal.value = response.data.length
        mobileOffset.value = 0
      }
    }
  } catch (error) {
    if (!axios.isCancel(error)) {
      console.error('Error loading mobile data:', error)
      mobileClassSections.value = []
      mobileTotal.value = 0
    }
  } finally {
    mobileLoading.value = false
    abortController = null
  }
}

// Initialize on mount
onMounted(() => {
  loadMobileData()

  // Initialize desktop table
  table = $('#class-sections-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('class-sections.index'),
      data: function(d) {
        d.search.value = filters.search || tableSearch.value
        if (filters.branch_id) d.branch_id = filters.branch_id
        if (filters.branch_class_id) d.branch_class_id = filters.branch_class_id
        if (filters.is_active !== '') d.is_active = filters.is_active
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'branch_class', name: 'branch' },
      { data: 'section', name: 'section' },
      { data: 'capacity', name: 'capacity' },
      { data: 'is_active', name: 'is_active', orderable: false },
      { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[1, 'asc']],
    searching: true,
    info: true,
    responsive: true,
    
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="mt-2 text-sm font-medium">No class sections found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      infoFiltered: '(filtered from _MAX_ total entries)',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div></div>',
      paginate: {
        first: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
        last: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
        next: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
        previous: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
      }
    },
    drawCallback: function () {
      const info = $('#class-sections-table_info')
      $('#table-info').empty().append(info)

      const paginate = $('#class-sections-table_paginate')
      $('#table-pagination').empty().append(paginate)
    }
  })
})

// Cleanup on unmount
onBeforeUnmount(() => {
  if (tableSearchTimeout) clearTimeout(tableSearchTimeout)
  if (searchTimeout) clearTimeout(searchTimeout)
  if (abortController) abortController.abort()
  if (table) {
    table.destroy()
    table = null
  }
})

// Delete class section
const confirmDelete = () => {
  deleting.value = true
  router.delete(route('class-sections.destroy', classSectionToDelete.value), {
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

window.deleteClassSection = (id) => {
  classSectionToDelete.value = id
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
const tableSearchDebounced = () => {
  clearTimeout(tableSearchTimeout)
  tableSearchTimeout = setTimeout(() => {
    loadData()
  }, 500)
}

// Filter search with debounce
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
  filters.branch_id = ''
  filters.branch_class_id = ''
  filters.is_active = ''
  tableSearch.value = ''
  filterClasses.value = []
  loadData()
}
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

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
  background: #4f46e5;
  color: white;
  border-color: #4f46e5;
}

:deep(.paginate_button.current:hover) {
  background: #4338ca;
  border-color: #4338ca;
}

:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}

:deep(#class-sections-table_info),
:deep(#class-sections-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#class-sections-table tbody td) {
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
  vertical-align: middle;
}

@media (max-width: 1024px) {
  :deep(#class-sections-table) {
    font-size: 0.813rem;
  }
  
  :deep(#class-sections-table th),
  :deep(#class-sections-table td) {
    padding: 0.5rem;
  }
}
</style>