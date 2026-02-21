<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <PageHeader 
          title="Class Section Subject Assignments"
          description="Manage subject assignments for class sections"
          action-route="class-section-subjects.create"
          action-text="Create New Assignment"
          action-variant="primary"
          action-icon="plus"
        />

        <!-- Flash Messages -->
        <FlashMessage 
          :show="showSuccessMessage"
          type="success"
          :message="$page.props.flash.success"
          @close="showSuccessMessage = false"
        />

        <FlashMessage 
          :show="showErrorMessage"
          type="error"
          :message="$page.props.flash.error"
          @close="showErrorMessage = false"
        />

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Branch Filter -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Branch</label>
              <select
                v-model="filters.branch_id"
                @change="onBranchFilterChange"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">All Branches</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.branch_name }}
                </option>
              </select>
            </div>

            <!-- Assignment Type Filter -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Assignment Type</label>
              <select
                v-model="filters.assignment_type"
                @change="loadData"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">All Types</option>
                <option value="individual">Individual Subjects</option>
                <option value="group">Subject Groups</option>
              </select>
            </div>

            <!-- Search -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
              <input
                v-model="filters.search"
                @input="searchDebounced"
                type="text"
                placeholder="Search..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <!-- Reset Button -->
            <div class="flex items-end">
              <button 
                @click="resetFilters"
                class="w-full px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Reset Filters
              </button>
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
            <table id="assignments-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    #
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Class Section
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Type
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Assignment
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Date
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
            <div class="text-xs sm:text-sm text-gray-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3 sm:space-y-4">
          <!-- Loading State -->
          <LoadingSpinner
            v-if="mobileLoading"
            text="Loading assignments..."
            size="lg"
            :full-height="false"
            container-class="py-12 bg-white rounded-lg shadow"
          />
          
          <!-- Empty State -->
          <EmptyState
            v-else-if="mobileAssignments.length === 0"
            type="empty"
            icon="clipboard"
            title="No assignments found"
            description="Try adjusting your filters or create a new assignment"
          />

          <!-- Assignment Cards -->
          <div v-else v-for="(assignment, index) in mobileAssignments" :key="assignment.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                  </div>
                  <h3 class="text-base font-semibold text-gray-900">
                    {{ assignment.class_section?.branch_class?.branch?.branch_name }}
                  </h3>
                  <p class="text-sm text-gray-600">
                    {{ assignment.class_section?.branch_class?.class?.class_name }} - 
                    {{ assignment.class_section?.section?.section_name }}
                  </p>
                </div>
                <span 
                  :class="assignment.subject_id ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                  class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2"
                >
                  {{ assignment.subject_id ? 'Individual' : 'Group' }}
                </span>
              </div>

              <!-- Details -->
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <!-- Assignment Details -->
                <div class="bg-gray-50 rounded-lg p-3">
                  <div v-if="assignment.subject_id" class="space-y-1">
                    <div class="font-medium text-sm text-gray-900">
                      {{ assignment.subject?.subject_name }}
                    </div>
                    <div class="text-xs text-gray-500">
                      Code: {{ assignment.subject?.subject_code }}
                    </div>
                  </div>
                  <div v-else class="space-y-1">
                    <div class="font-medium text-sm text-gray-900">
                      {{ assignment.subject_group?.group_name }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ getSubjectCount(assignment.subject_group?.subject_ids) }} subjects
                    </div>
                  </div>
                </div>

                <!-- Date -->
                <div class="flex items-center text-xs text-gray-500">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  {{ formatDate(assignment.created_at) }}
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <button 
                  @click="$inertia.visit(route('class-section-subjects.edit', assignment.id))"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Edit
                </button>
                <button 
                  @click="() => { assignmentToDelete = assignment.id; showDeleteModal = true; }"
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
        <div v-if="mobileAssignments.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
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
              <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total assignments</div>
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
            <span class="text-base sm:text-lg font-semibold text-gray-900">Delete Assignment</span>
          </div>
        </template>
        
        <p class="text-xs sm:text-sm text-gray-600 mt-2">
          Are you sure you want to delete this subject assignment? This action cannot be undone.
        </p>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <button 
              @click="showDeleteModal = false"
              class="w-full sm:w-auto px-4 sm:px-6 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
            >
              Cancel
            </button>
            <button 
              @click="confirmDelete" 
              :disabled="deleting"
              class="w-full sm:w-auto px-4 sm:px-6 py-2 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
            >
              <span v-if="!deleting">Delete Assignment</span>
              <span v-else>Deleting...</span>
            </button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import PageHeader from '@/Components/Forms/PageHeader.vue'
import FlashMessage from '@/Components/Forms/FlashMessage.vue'
import LoadingSpinner from '@/Components/Forms/LoadingSpinner.vue'
import EmptyState from '@/Components/Forms/EmptyState.vue'
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
const assignmentToDelete = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobileAssignments = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)
let table = null

const filters = reactive({
  search: '',
  branch_id: '',
  assignment_type: ''
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => showSuccessMessage.value = false, 5000)
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => showErrorMessage.value = false, 5000)
  }
}, { deep: true, immediate: true })

// Helper functions
const getSubjectCount = (subjectIds) => {
  if (!subjectIds) return 0
  const ids = subjectIds.split(',')
  return ids.length
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Branch filter change
const onBranchFilterChange = () => {
  loadData()
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
    if (filters.branch_id) params.branch_id = filters.branch_id
    if (filters.assignment_type) params.assignment_type = filters.assignment_type
    
    const response = await axios.get(route('class-section-subjects.index'), {
      params,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    
    if (response.data) {
      if (response.data.data) {
        mobileAssignments.value = response.data.data
        mobileCurrentPage.value = response.data.current_page || 1
        mobileTotalPages.value = response.data.last_page || 1
        mobileTotal.value = response.data.total || 0
        mobileOffset.value = response.data.from ? response.data.from - 1 : 0
      } else if (Array.isArray(response.data)) {
        mobileAssignments.value = response.data
        mobileTotalPages.value = 1
        mobileTotal.value = response.data.length
        mobileOffset.value = 0
      }
    }
  } catch (error) {
    console.error('Error loading mobile data:', error)
    mobileAssignments.value = []
    mobileTotal.value = 0
  } finally {
    mobileLoading.value = false
  }
}

// Initialize on mount
onMounted(() => {
  loadMobileData()

  // Initialize desktop table
  table = $('#assignments-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('class-section-subjects.index'),
      data: function(d) {
        d.search.value = filters.search || tableSearch.value
        if (filters.branch_id) d.branch_id = filters.branch_id
        if (filters.assignment_type) d.assignment_type = filters.assignment_type
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'class_section', name: 'class_section' },
      { data: 'assignment_type', name: 'assignment_type' },
      { data: 'assignment_details', name: 'assignment_details' },
      { data: 'created_at', name: 'created_at' },
      { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[4, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="mt-2 text-sm font-medium">No assignments found</p></div>',
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
      const info = $('#assignments-table_info')
      $('#table-info').empty().append(info)

      const paginate = $('#assignments-table_paginate')
      $('#table-pagination').empty().append(paginate)
    }
  })
})

// Delete assignment
const confirmDelete = () => {
  deleting.value = true
  router.delete(route('class-section-subjects.destroy', assignmentToDelete.value), {
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

// Make delete function available globally for DataTables
window.deleteClassSectionSubject = (id) => {
  assignmentToDelete.value = id
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
  filters.branch_id = ''
  filters.assignment_type = ''
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

:deep(#assignments-table_info),
:deep(#assignments-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#assignments-table tbody td) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

@media (min-width: 640px) {
  :deep(#assignments-table tbody td) {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
  }
}

@media (max-width: 1024px) {
  :deep(#assignments-table) {
    font-size: 0.813rem;
  }
  
  :deep(#assignments-table th),
  :deep(#assignments-table td) {
    padding: 0.5rem;
  }
}
</style>