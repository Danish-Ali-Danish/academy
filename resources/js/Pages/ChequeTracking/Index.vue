<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Cheque Tracking</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Track and manage received cheques</p>
            </div>
            <Link :href="route('cheque-tracking.create')">
              <Button variant="primary" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all duration-200">
                <PlusIcon class="h-4 w-4 sm:h-5 sm:w-5 mr-2" />
                <span class="text-sm sm:text-base">Add Cheque Record</span>
              </Button>
            </Link>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div>
              <Input
                v-model="filters.search"
                placeholder="Search cheques..."
                @input="searchDebounced"
                class="w-full text-sm"
              />
            </div>
            
            <div>
              <select
                v-model="filters.status"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Cleared">Cleared</option>
                <option value="Bounced">Bounced</option>
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
            <table id="cheques-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">#</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Cheque No</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Student</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Bank</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Amount</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Received Date</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
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
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="mobileData.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No cheques found</p>
          </div>

          <!-- Cards -->
          <div v-else v-for="(cheque, index) in mobileData" :key="cheque.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                    <h3 class="text-base font-semibold text-gray-900">{{ cheque.cheque_no }}</h3>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5">{{ cheque.student_enrollment?.student?.student_name ?? 'N/A' }}</p>
                </div>
                <span :class="statusClass(cheque.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ cheque.status }}
                </span>
              </div>
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <div class="flex items-center justify-between text-xs sm:text-sm">
                  <div class="flex items-center text-gray-600">
                    <span>{{ formatDate(cheque.received_date) }}</span>
                  </div>
                  <span class="text-gray-900 font-semibold">Rs. {{ Number(cheque.amount || 0).toLocaleString() }}</span>
                </div>
              </div>
              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <Link :href="route('cheque-tracking.edit', cheque.id)" class="flex-1">
                  <button class="w-full px-3 py-2 text-xs sm:text-sm font-medium text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors flex items-center justify-center gap-1">
                    Edit
                  </button>
                </Link>
                <button 
                  @click="openDeleteModal(cheque.id)"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-1"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>

          <!-- Mobile Pagination -->
          <div v-if="mobilePagination && mobilePagination.last_page > 1" class="flex justify-center mt-6">
            <div class="flex items-center gap-3 bg-white rounded-lg shadow px-3 py-2">
              <button
                type="button"
                @click="loadMobilePage(mobilePagination.current_page - 1)"
                :disabled="mobilePagination.current_page <= 1 || mobileLoading"
                class="px-3 py-1.5 text-sm font-medium border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed bg-white hover:bg-gray-50"
              >
                Previous
              </button>
              <span class="text-sm text-gray-700">
                Page {{ mobilePagination.current_page }} of {{ mobilePagination.last_page }}
              </span>
              <button
                type="button"
                @click="loadMobilePage(mobilePagination.current_page + 1)"
                :disabled="mobilePagination.current_page >= mobilePagination.last_page || mobileLoading"
                class="px-3 py-1.5 text-sm font-medium border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed bg-white hover:bg-gray-50"
              >
                Next
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Delete Cheque Record</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete this cheque record? This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end">
          <Button variant="secondary" @click="closeDeleteModal">Cancel</Button>
          <Button variant="danger" class="ml-3" @click="deleteRecord" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
            Delete Record
          </Button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import debounce from 'lodash/debounce'
import axios from 'axios'

import $ from 'jquery'
import 'datatables.net'

const filters = ref({
  search: '',
  status: '',
})

const perPage = ref('10')
const tableSearch = ref('')
const mobileLoading = ref(false)
const mobileData = ref([])
const mobilePagination = ref(null)
const mobileOffset = ref(0)

const showDeleteModal = ref(false)
const deleteId = ref(null)
const form = useForm({})

let dataTable = null

const initDataTable = () => {
  if (dataTable) {
    dataTable.destroy()
  }

  dataTable = $('#cheques-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('cheque-tracking.index'),
      data: function (d) {
        d.search.value = tableSearch.value
        d.status = filters.value.status
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'cheque_no', name: 'cheque_no' },
      { data: 'student_name', name: 'student_name', orderable: false },
      { data: 'bank_name', name: 'bank_name' },
      { data: 'amount', name: 'amount' },
      { data: 'status', name: 'status' },
      { data: 'received_date', name: 'received_date' },
      { data: 'action', name: 'action', orderable: false, searchable: false }
    ],
    pageLength: parseInt(perPage.value),
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[0, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-16"><div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4"><svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div><p class="text-sm font-semibold text-gray-700">No cheques found</p><p class="text-xs text-gray-400 mt-1">Try adjusting your filters or add a cheque record</p></div>',
      zeroRecords: '<div class="text-center py-12"><p class="text-sm font-semibold text-gray-700">No matching cheques found</p><p class="text-xs text-gray-400 mt-1">Try another search term</p></div>',
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
    drawCallback: function() {
      const info = $('#cheques-table_info')
      $('#table-info').empty().append(info)

      const paginate = $('#cheques-table_paginate')
      $('#table-pagination').empty().append(paginate)
    }
  })
}

const loadMobilePage = async (page = 1) => {
  mobileLoading.value = true
  try {
    const response = await axios.get(route('cheque-tracking.index'), {
      params: {
        mobile: true,
        page: page,
        per_page: perPage.value,
        search: filters.value.search,
        status: filters.value.status
      }
    })
    mobileData.value = response.data.data
    mobilePagination.value = response.data
    mobileOffset.value = (response.data.current_page - 1) * response.data.per_page
  } catch (error) {
    console.error('Error loading mobile data:', error)
  } finally {
    mobileLoading.value = false
  }
}

const loadData = () => {
  if (window.innerWidth >= 768) {
    if (dataTable) {
      dataTable.ajax.reload()
    }
  } else {
    loadMobilePage(1)
  }
}

const searchDebounced = debounce(() => {
  tableSearch.value = filters.value.search
  loadData()
}, 300)

const tableSearchDebounced = debounce(() => {
  filters.value.search = tableSearch.value
  loadData()
}, 300)

const changePerPage = () => {
  if (dataTable) {
    dataTable.page.len(parseInt(perPage.value)).draw()
  }
  if (window.innerWidth < 768) {
    loadMobilePage(1)
  }
}

const resetFilters = () => {
  filters.value = { search: '', status: '' }
  tableSearch.value = ''
  loadData()
}

const statusClass = (status) => {
  const map = {
    'Pending': 'bg-yellow-100 text-yellow-800',
    'Cleared': 'bg-green-100 text-green-800',
    'Bounced': 'bg-red-100 text-red-800'
  }
  return map[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

window.editCheque = (cheque) => {
  router.visit(route('cheque-tracking.edit', cheque.id))
}

window.deleteCheque = (id) => {
  openDeleteModal(id)
}

const openDeleteModal = (id) => {
  deleteId.value = id
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  deleteId.value = null
}

const deleteRecord = () => {
  form.delete(route('cheque-tracking.destroy', deleteId.value), {
    preserveScroll: true,
    onSuccess: () => {
      closeDeleteModal()
      loadData()
    },
  })
}

let resizeTimer
const handleResize = () => {
  clearTimeout(resizeTimer)
  resizeTimer = setTimeout(() => {
    if (window.innerWidth >= 768 && !dataTable) {
      initDataTable()
    } else if (window.innerWidth < 768 && dataTable) {
      dataTable.destroy()
      dataTable = null
      loadMobilePage(1)
    }
  }, 250)
}

onMounted(() => {
  if (window.innerWidth >= 768) {
    initDataTable()
  } else {
    loadMobilePage(1)
  }
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  if (dataTable) {
    dataTable.destroy()
  }
  window.removeEventListener('resize', handleResize)
  delete window.editCheque
  delete window.deleteCheque
})
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

:deep(#cheques-table_info),
:deep(#cheques-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#cheques-table tbody td) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  vertical-align: middle;
}

@media (min-width: 640px) {
  :deep(#cheques-table tbody td) {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
  }
}

@media (max-width: 1024px) {
  :deep(#cheques-table) {
    font-size: 0.813rem;
  }

  :deep(#cheques-table th),
  :deep(#cheques-table td) {
    padding: 0.5rem;
  }
}
</style>
