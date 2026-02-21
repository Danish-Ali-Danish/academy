<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Fee Payments</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Track and manage all fee payment transactions</p>
            </div>
            <Link :href="route('fee-payments.create')">
              <Button variant="primary" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all duration-200">
                <PlusIcon class="h-4 w-4 sm:h-5 sm:w-5 mr-2" />
                <span class="text-sm sm:text-base">Record Payment</span>
              </Button>
            </Link>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <div>
              <Input
                v-model="filters.search"
                placeholder="Search payments..."
                @input="searchDebounced"
                class="w-full text-sm"
              />
            </div>
            
            <div>
              <select
                v-model="filters.payment_method"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online">Online</option>
                <option value="card">Card</option>
              </select>
            </div>

            <div>
              <select
                v-model="filters.is_cancelled"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Payments</option>
                <option value="0">Active Only</option>
                <option value="1">Cancelled Only</option>
              </select>
            </div>

            <div>
              <select
                v-model="filters.branch_id"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Branches</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.name }}
                </option>
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
            <table id="payments-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">#</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Receipt No</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Student</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Admission No</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Fee Type</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Amount</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Method</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Collected By</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
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
          <div v-else-if="mobilePayments.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No payments found</p>
            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
          </div>

          <!-- Payment Cards -->
          <div v-else v-for="(payment, index) in mobilePayments" :key="payment.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                    <h3 class="text-base font-semibold text-gray-900">{{ payment.receipt_no }}</h3>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5">{{ payment.student ? payment.student.full_name : 'N/A' }}</p>
                </div>
                <span :class="payment.is_cancelled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ payment.is_cancelled ? 'Cancelled' : 'Active' }}
                </span>
              </div>

              <!-- Details -->
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <div class="flex items-center justify-between text-xs sm:text-sm">
                  <div class="flex items-center text-gray-600">
                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ formatDate(payment.payment_date) }}</span>
                  </div>
                  <span class="text-gray-900 font-semibold">Rs. {{ Number(payment.amount_paid).toLocaleString() }}</span>
                </div>
                
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                  </svg>
                  <span class="text-gray-600">{{ formatPaymentMethod(payment.payment_method) }}</span>
                </div>

                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                  </svg>
                  <span class="text-gray-600">{{ payment.fee && payment.fee.fee_type ? payment.fee.fee_type.name : 'N/A' }}</span>
                </div>

                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  <span class="text-gray-600">{{ payment.collected_by ? payment.collected_by.name : 'N/A' }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <Link :href="route('fee-payments.show', payment.id)" class="flex-1">
                  <button class="w-full px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View
                  </button>
                </Link>
                <button 
                  v-if="!payment.is_cancelled"
                  @click="() => { paymentToCancel = payment.id; showCancelModal = true; }"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div v-if="mobilePayments.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
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
              <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total payments</div>
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

      <!-- Cancel Payment Modal -->
      <Modal :show="showCancelModal" @close="showCancelModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-red-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">Cancel Payment</span>
          </div>
        </template>
        
        <div class="mt-2">
          <p class="text-xs sm:text-sm text-gray-600 mb-4">
            Are you sure you want to cancel this payment? This will reverse the payment and update the fee balance.
          </p>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Cancellation Reason <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="cancellationReason"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              placeholder="Enter reason for cancellation..."
              required
            ></textarea>
          </div>
        </div>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button 
              variant="secondary" 
              @click="showCancelModal = false"
              class="w-full sm:w-auto px-4 sm:px-6 shadow-sm hover:shadow-md transition-all text-sm"
            >
              Close
            </Button>
            <Button 
              variant="danger" 
              @click="confirmCancel" 
              :loading="cancelling"
              :disabled="!cancellationReason"
              class="w-full sm:w-auto px-4 sm:px-6 shadow-md hover:shadow-lg transition-all text-sm"
            >
              <span v-if="!cancelling">Cancel Payment</span>
              <span v-else>Cancelling...</span>
            </Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

const props = defineProps({
  branches: {
    type: Array,
    default: () => []
  }
})

const showCancelModal = ref(false)
const cancelling = ref(false)
const paymentToCancel = ref(null)
const cancellationReason = ref('')
const tableSearch = ref('')
const perPage = ref(10)
const mobilePayments = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
let table = null

const filters = reactive({
  search: '',
  payment_method: '',
  is_cancelled: '',
  branch_id: ''
})

// Helper functions
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatPaymentMethod = (method) => {
  const methods = {
    'cash': 'Cash',
    'cheque': 'Cheque',
    'bank_transfer': 'Bank Transfer',
    'online': 'Online',
    'card': 'Card'
  }
  return methods[method] || method
}

// Load mobile data using axios
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
    if (filters.payment_method) params.payment_method = filters.payment_method
    if (filters.is_cancelled !== '') params.is_cancelled = filters.is_cancelled
    if (filters.branch_id) params.branch_id = filters.branch_id
    
    const response = await axios.get(route('fee-payments.index'), {
      params,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    
    if (response.data) {
      if (response.data.data) {
        mobilePayments.value = response.data.data
        mobileCurrentPage.value = response.data.current_page || 1
        mobileTotalPages.value = response.data.last_page || 1
        mobileTotal.value = response.data.total || 0
        mobileOffset.value = response.data.from ? response.data.from - 1 : 0
      } else if (Array.isArray(response.data)) {
        mobilePayments.value = response.data
        mobileTotalPages.value = 1
        mobileTotal.value = response.data.length
        mobileOffset.value = 0
      }
    }
  } catch (error) {
    console.error('Error loading mobile data:', error)
    mobilePayments.value = []
    mobileTotal.value = 0
  } finally {
    mobileLoading.value = false
  }
}

// Initialize on mount
onMounted(() => {
  loadMobileData()

  table = $('#payments-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('fee-payments.index'),
      data: function(d) {
        d.search.value = filters.search || tableSearch.value
        d.payment_method = filters.payment_method
        d.is_cancelled = filters.is_cancelled
        d.branch_id = filters.branch_id
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'receipt_no', name: 'receipt_no' },
      { data: 'student_name', name: 'student_name' },
      { data: 'admission_no', name: 'admission_no' },
      { data: 'fee_type', name: 'fee_type' },
      { data: 'payment_date', name: 'payment_date' },
      { data: 'amount_paid', name: 'amount_paid' },
      { data: 'payment_method', name: 'payment_method' },
      { data: 'collected_by', name: 'collected_by' },
      { data: 'is_cancelled', name: 'is_cancelled', orderable: false },
      { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[5, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg><p class="mt-2 text-sm font-medium">No payments found</p></div>',
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
      const info = $('#payments-table_info')
      $('#table-info').empty().append(info)

      const paginate = $('#payments-table_paginate')
      $('#table-pagination').empty().append(paginate)
    }
  })
})

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
  filters.payment_method = ''
  filters.is_cancelled = ''
  filters.branch_id = ''
  tableSearch.value = ''
  loadData()
}

// Cancel payment
const confirmCancel = () => {
  if (!cancellationReason.value) return
  
  cancelling.value = true
  router.post(route('fee-payments.cancel', paymentToCancel.value), {
    cancellation_reason: cancellationReason.value,
    cancelled_by: 1 // You should get this from auth user
  }, {
    onSuccess: () => {
      showCancelModal.value = false
      cancelling.value = false
      cancellationReason.value = ''
      loadData()
    },
    onError: () => {
      cancelling.value = false
    }
  })
}

window.cancelPayment = (id) => {
  paymentToCancel.value = id
  showCancelModal.value = true
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

:deep(#payments-table_info),
:deep(#payments-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#payments-table tbody td) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

@media (min-width: 640px) {
  :deep(#payments-table tbody td) {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
  }
}

@media (max-width: 1024px) {
  :deep(#payments-table) {
    font-size: 0.813rem;
  }
  
  :deep(#payments-table th),
  :deep(#payments-table td) {
    padding: 0.5rem;
  }
}
</style>