<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50/50 pb-12">
      <!-- Header Section -->
      <div class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:flex lg:items-center lg:justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate flex items-center gap-3">
              <div class="p-2 bg-indigo-100 rounded-xl">
                <ClipboardDocumentListIcon class="h-6 w-6 text-indigo-600" />
              </div>
              Voucher Edit History
            </h1>
            <p class="mt-2 text-sm text-gray-500">
              Track and audit every manual modification made to fee vouchers to ensure absolute financial integrity.
            </p>
          </div>
          <div class="mt-4 flex flex-col sm:flex-row sm:mt-0 sm:ml-4 gap-3">
            <div class="relative rounded-xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input 
                v-model="filters.search" 
                @input="searchDebounced"
                type="text" 
                placeholder="Search voucher, student, reason..." 
                class="pl-10 block w-full sm:w-80 text-sm border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-white" 
              />
            </div>
            <button 
              @click="resetFilters" 
              class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors gap-2"
            >
              <ArrowPathIcon class="h-4 w-4 text-gray-500" />
              <span>Reset</span>
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        
        <!-- Table Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-gray-500">Show</span>
              <select 
                v-model="perPage" 
                @change="changePerPage" 
                class="block w-full py-1.5 pl-3 pr-8 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-medium text-gray-700 shadow-sm"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
              <span class="text-sm font-medium text-gray-500">entries</span>
            </div>
            
            <div class="relative w-full sm:w-64 hidden">
              <!-- Keeping secondary search bound if needed by datatable, but main search is in header -->
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
              </div>
              <input 
                v-model="tableSearch" 
                @input="tableSearchDebounced" 
                type="text" 
                placeholder="Quick search..." 
                class="pl-9 block w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" 
              />
            </div>
          </div>

          <div class="overflow-x-auto">
            <table id="fee-voucher-edit-history-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Voucher</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                  <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Changes</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Edited By</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Edited At</th>
                  <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Approval</th>
                  <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100 text-sm">
                <!-- Datatable content -->
              </tbody>
            </table>
          </div>

          <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div id="table-info" class="text-sm font-medium text-gray-500"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

      </div>

      <!-- View Audit Modal -->
      <Modal :show="showViewModal" @close="showViewModal = false" maxWidth="2xl">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
              <DocumentMagnifyingGlassIcon class="h-6 w-6 text-indigo-600" />
              Voucher Edit Audit Trail
            </h3>
            <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-500 focus:outline-none transition-colors">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
          
          <div v-if="selectedHistory" class="p-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
              <div class="rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100/50 p-4 border border-indigo-100/50">
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Voucher No</p>
                <p class="mt-1 font-semibold text-gray-900 text-lg">{{ selectedHistory.voucher_no }}</p>
              </div>
              <div class="rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-4 border border-emerald-100/50">
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Student Name</p>
                <p class="mt-1 font-semibold text-gray-900 truncate">{{ selectedHistory.student_name }}</p>
              </div>
              <div class="rounded-xl bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 border border-purple-100/50">
                <p class="text-xs font-bold text-purple-600 uppercase tracking-wider">Edited By</p>
                <p class="mt-1 font-semibold text-gray-900 truncate">{{ selectedHistory.edited_by }}</p>
              </div>
            </div>

            <div class="mb-6">
              <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <ChatBubbleBottomCenterTextIcon class="h-5 w-5 text-gray-400 mt-0.5" />
                <div>
                  <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Reason for Modification</h4>
                  <p class="mt-1 text-sm text-gray-700 italic">"{{ selectedHistory.reason }}"</p>
                </div>
              </div>
            </div>

            <div class="rounded-xl border border-gray-200 overflow-hidden shadow-sm">
              <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <h4 class="text-sm font-semibold text-gray-900">Modified Fields</h4>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                  {{ selectedHistory.changes?.length || 0 }} changes
                </span>
              </div>
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/3">Field Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/3">Old Value</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/3">New Value</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                  <tr v-for="change in selectedHistory.changes" :key="change.field" class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ change.field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 line-through decoration-red-400">
                      {{ change.old ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-emerald-600 bg-emerald-50/30">
                      {{ change.new ?? '-' }}
                    </td>
                  </tr>
                  <tr v-if="!selectedHistory.changes || selectedHistory.changes.length === 0">
                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                      No specific field changes recorded.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
          
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button @click="showViewModal = false" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
              Close Audit Log
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Modal from '@/Components/Common/Modal.vue'
import $ from 'jquery'
import 'datatables.net'
import { 
  ClipboardDocumentListIcon, 
  MagnifyingGlassIcon, 
  ArrowPathIcon,
  DocumentMagnifyingGlassIcon,
  XMarkIcon,
  ChatBubbleBottomCenterTextIcon
} from '@heroicons/vue/24/outline'

const filters = reactive({ search: '' })
const tableSearch = ref('')
const perPage = ref(10)
const showViewModal = ref(false)
const selectedHistory = ref(null)
let table = null

onMounted(() => {
  table = $('#fee-voucher-edit-history-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('fee-voucher-edit-history.index'),
      data: (d) => { d.search.value = filters.search || tableSearch.value },
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-gray-500' },
      { data: 'voucher', className: 'font-semibold text-indigo-600' },
      { data: 'student', className: 'text-gray-900 font-medium' },
      { data: 'change_count', orderable: false, searchable: false, className: 'text-center' },
      { data: 'reason', className: 'text-gray-500 italic max-w-xs truncate' },
      { data: 'edited_by', className: 'text-gray-700' },
      { data: 'edited_at', className: 'text-gray-500' },
      { data: 'approval', orderable: false, searchable: false, className: 'text-center' },
      { data: 'action', orderable: false, searchable: false, className: 'text-center' },
    ],
    pageLength: Number(perPage.value),
    order: [[0, 'desc']],
    dom: 'rt<"hidden"ip>',
    language: {
      emptyTable: '<div class="py-12 text-center text-gray-500 flex flex-col items-center justify-center"><svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="text-sm">No voucher edits found</span></div>',
      processing: '<div class="py-8 text-indigo-600 flex justify-center items-center gap-2"><svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Loading...</span></div>',
    },
    drawCallback: function () {
      $('#table-info').empty().append($('#fee-voucher-edit-history-table_info'))
      $('#table-pagination').empty().append($('#fee-voucher-edit-history-table_paginate'))
    },
  })
})

window.viewHistory = (history) => {
  selectedHistory.value = history
  showViewModal.value = true
}

let timer = null
const loadData = () => table?.ajax.reload()
const searchDebounced = () => { clearTimeout(timer); timer = setTimeout(loadData, 400) }
const tableSearchDebounced = searchDebounced
const changePerPage = () => table?.page.len(Number(perPage.value)).draw()
const resetFilters = () => { filters.search = ''; tableSearch.value = ''; loadData() }
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.font-sans {
  font-family: 'Inter', sans-serif;
}

/* DataTables Overrides */
:deep(table.dataTable) {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}
:deep(table.dataTable tbody tr td) {
  vertical-align: middle;
  padding: 1rem 1.5rem !important;
}
:deep(.dataTables_info) { 
  font-size: 0.875rem; 
  color: #6b7280; 
  padding-top: 0 !important;
}
:deep(.dataTables_paginate) { 
  display: flex; 
  gap: 0.375rem; 
  margin: 0 !important;
}
:deep(.paginate_button) { 
  padding: 0.375rem 0.75rem !important; 
  border: 1px solid #e5e7eb !important; 
  border-radius: 0.5rem !important; 
  cursor: pointer;
  background: white !important;
  color: #374151 !important;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
}
:deep(.paginate_button:hover) {
  background: #f9fafb !important;
  color: #111827 !important;
  border-color: #d1d5db !important;
}
:deep(.paginate_button.current) { 
  background: #4f46e5 !important; 
  color: #fff !important; 
  border-color: #4f46e5 !important; 
}
:deep(.paginate_button.current:hover) {
  background: #4338ca !important;
  color: #fff !important;
}
:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
