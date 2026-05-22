<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Voucher Discount Breakdowns</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Review concessions, sibling discounts, scholarships, waivers and fines applied to vouchers.
              </p>
            </div>
            <Button @click="$inertia.visit(route('fee-vouchers.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to Vouchers
            </Button>
          </div>
        </div>

        <div v-if="selectedVoucher" class="mb-5 rounded-xl border border-indigo-100 bg-white shadow-md overflow-hidden">
          <div class="px-5 py-4 border-b border-indigo-100 bg-indigo-50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Selected Voucher</p>
                <h2 class="text-lg font-bold text-gray-900 mt-1">{{ selectedVoucher.voucher_no }}</h2>
                <p class="text-sm text-gray-600">{{ selectedVoucher.student_name }} · {{ selectedVoucher.fee_type }}</p>
              </div>
              <button @click="clearVoucherFilter" type="button" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">
                Show all breakdowns
              </button>
            </div>
          </div>
          <div class="grid grid-cols-2 xl:grid-cols-5 gap-3 p-4">
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
              <p class="text-xs text-gray-500">Original</p>
              <p class="text-lg font-bold text-gray-900">Rs {{ formatAmount(selectedVoucher.original_amount) }}</p>
            </div>
            <div class="rounded-lg bg-green-50 border border-green-200 p-3">
              <p class="text-xs text-green-700">Discount</p>
              <p class="text-lg font-bold text-green-800">Rs {{ formatAmount(selectedVoucher.discount_amount) }}</p>
            </div>
            <div class="rounded-lg bg-sky-50 border border-sky-200 p-3">
              <p class="text-xs text-sky-700">Fee Waiver</p>
              <p class="text-lg font-bold text-sky-800">Rs {{ formatAmount(selectedVoucher.waiver_amount) }}</p>
            </div>
            <div class="rounded-lg bg-red-50 border border-red-200 p-3">
              <p class="text-xs text-red-700">Fine</p>
              <p class="text-lg font-bold text-red-800">Rs {{ formatAmount(selectedVoucher.fine_amount) }}</p>
            </div>
            <div class="rounded-lg bg-indigo-50 border border-indigo-200 p-3">
              <p class="text-xs text-indigo-700">Net Payable</p>
              <p class="text-lg font-bold text-indigo-800">Rs {{ formatAmount(selectedVoucher.net_amount) }}</p>
            </div>
          </div>
          <div class="px-4 pb-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
              <span class="font-semibold text-gray-900">Calculation:</span>
              Original Rs {{ formatAmount(selectedVoucher.original_amount) }}
              - Discount Rs {{ formatAmount(selectedVoucher.discount_amount) }}
              - Waiver Rs {{ formatAmount(selectedVoucher.waiver_amount) }}
              + Fine Rs {{ formatAmount(selectedVoucher.fine_amount) }}
              = <span class="font-bold text-indigo-700">Net Rs {{ formatAmount(selectedVoucher.net_amount) }}</span>
            </div>
          </div>
          <div class="border-t border-gray-100 px-4 py-4">
            <div class="flex items-center justify-between gap-3 mb-3">
              <div>
                <p class="text-sm font-semibold text-gray-900">Fee Waiver Details</p>
                <p class="text-xs text-gray-500">Approved waivers reduce the payable amount after discounts.</p>
              </div>
              <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                {{ selectedVoucher.waivers?.length || 0 }} record{{ (selectedVoucher.waivers?.length || 0) === 1 ? '' : 's' }}
              </span>
            </div>
            <div v-if="selectedVoucher.waivers?.length" class="overflow-x-auto rounded-lg border border-gray-200">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Approved By</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Approved On</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="waiver in selectedVoucher.waivers" :key="waiver.id">
                    <td class="px-4 py-3 text-sm font-semibold text-sky-700">Rs {{ formatAmount(waiver.waived_amount) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                      <div class="max-w-md">
                        <p>{{ waiver.waiver_reason || '-' }}</p>
                        <p v-if="waiver.status === 'reversed' && waiver.reversal_reason" class="mt-1 text-xs text-red-600">
                          Reversed: {{ waiver.reversal_reason }}
                        </p>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ waiver.approved_by || '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ waiver.approved_on || '-' }}</td>
                    <td class="px-4 py-3">
                      <span :class="waiverStatusClass(waiver.status)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                        {{ sourceName(waiver.status) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">
              No fee waiver is attached to this voucher.
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-5">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <Input v-model="filters.search" placeholder="Search voucher, student, source..." @input="searchDebounced" class="w-full text-sm" />
            <select v-model="filters.source" @change="loadData" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">All Discount Sources</option>
              <option value="student_fee_concession">Fee Concession</option>
              <option value="sibling_discount_rule">Sibling Discount</option>
              <option value="student_scholarship">Scholarship</option>
            </select>
            <Button variant="secondary" @click="resetFilters" class="w-full shadow-sm text-sm">Reset Filters</Button>
          </div>
        </div>

        <div class="hidden md:block bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-700">Show</span>
              <select v-model="perPage" @change="changePerPage" class="px-5 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-sm text-gray-700">entries</span>
            </div>
            <div class="w-72">
              <div class="relative">
                <input v-model="tableSearch" @input="tableSearchDebounced" type="text" placeholder="Search in table..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table id="voucher-breakdowns-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">#</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Voucher No</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Student</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Fee Type</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Source</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Type</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Value</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Amount</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Applied By</th>
                  <th class="px-4 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white text-center divide-y divide-gray-100"></tbody>
            </table>
          </div>
          <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="text-sm text-gray-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <div class="md:hidden space-y-3">
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
          </div>
          <div v-else-if="mobileItems.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <p class="text-sm font-medium text-gray-500">No discount breakdowns found</p>
          </div>
          <div v-else v-for="item in mobileItems" :key="item.id" class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="font-semibold text-gray-900">{{ item.voucher?.voucher_no || '-' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ item.voucher?.student_enrollment?.student?.student_name || '-' }}</p>
              </div>
              <span class="text-sm font-bold text-green-700">Rs {{ formatAmount(item.calculated_amount) }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div class="rounded-lg bg-gray-50 p-2">
                <p class="text-gray-500">Source</p>
                <p class="font-medium text-gray-900">{{ item.source_label || sourceName(item.discount_source) }}</p>
              </div>
              <div class="rounded-lg bg-gray-50 p-2">
                <p class="text-gray-500">Value</p>
                <p class="font-medium text-gray-900">{{ discountValue(item) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

const props = defineProps({
  voucherId: { type: [String, Number, null], default: null },
  selectedVoucher: { type: Object, default: null },
})

const filters = reactive({ search: '', source: '' })
const selectedVoucher = props.selectedVoucher
const tableSearch = ref('')
const perPage = ref(10)
const mobileItems = ref([])
const mobileLoading = ref(false)
let table = null
let searchTimeout = null
let tableSearchTimeout = null

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const sourceName = (source) => source ? source.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : '-'
const discountValue = (item) => item.discount_type === 'percentage' ? `${item.discount_value || 0}%` : `Rs ${formatAmount(item.discount_value)}`
const waiverStatusClass = (status) => ({
  approved: 'bg-green-100 text-green-800',
  reversed: 'bg-red-100 text-red-800',
  pending: 'bg-yellow-100 text-yellow-800',
}[status] || 'bg-gray-100 text-gray-700')

const ajaxParams = (d = {}) => {
  d.search = d.search || {}
  d.search.value = filters.search || tableSearch.value
  if (props.voucherId) d.voucher_id = props.voucherId
  if (filters.source) d.source = filters.source
  return d
}

const loadMobileData = async () => {
  mobileLoading.value = true
  try {
    const params = { mobile: 1, per_page: perPage.value }
    if (props.voucherId) params.voucher_id = props.voucherId
    if (filters.search || tableSearch.value) params.search = filters.search || tableSearch.value
    if (filters.source) params.source = filters.source
    const response = await axios.get(route('voucher-discount-breakdowns.index'), {
      params,
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    })
    mobileItems.value = response.data?.data || []
  } catch (error) {
    mobileItems.value = []
  } finally {
    mobileLoading.value = false
  }
}

const loadData = () => {
  if (table) table.ajax.reload()
  loadMobileData()
}

const resetFilters = () => {
  filters.search = ''
  filters.source = ''
  tableSearch.value = ''
  loadData()
}

const searchDebounced = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadData, 450)
}

const tableSearchDebounced = () => {
  clearTimeout(tableSearchTimeout)
  tableSearchTimeout = setTimeout(loadData, 450)
}

const changePerPage = () => {
  if (table) table.page.len(perPage.value).draw()
  loadMobileData()
}

const clearVoucherFilter = () => {
  router.visit(route('voucher-discount-breakdowns.index'))
}

window.viewBreakdown = (item) => {
  router.visit(route('voucher-discount-breakdowns.show', item.id))
}

onMounted(() => {
  loadMobileData()
  table = $('#voucher-breakdowns-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('voucher-discount-breakdowns.index'),
      data: ajaxParams,
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'voucher_no', name: 'voucher_id' },
      { data: 'student', name: 'student' },
      { data: 'fee_type', name: 'fee_type' },
      { data: 'discount_source', name: 'discount_source', orderable: false },
      { data: 'discount_type', name: 'discount_type' },
      { data: 'discount_value', name: 'discount_value' },
      { data: 'discount_amount', name: 'calculated_amount' },
      { data: 'applied_by', name: 'applied_by', orderable: false },
      { data: 'action', orderable: false, searchable: false },
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[0, 'desc']],
    searching: true,
    info: true,
    dom: '<"hidden"i>rt<"hidden"p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><p class="mt-2 text-sm font-medium">No discount breakdowns found</p></div>',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
    },
    drawCallback: function () {
      $('#table-info').empty().append($('#voucher-breakdowns-table_info'))
      $('#table-pagination').empty().append($('#voucher-breakdowns-table_paginate'))
    },
  })
})
</script>

<style scoped>
:deep(.dataTables_info) { font-size: 0.875rem; color: #4b5563; font-weight: 500; }
:deep(.dataTables_paginate) { display: flex; justify-content: flex-end; gap: 0.25rem; flex-wrap: wrap; }
:deep(.paginate_button) { padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white; color: #374151; cursor: pointer; transition: all 0.2s; }
:deep(.paginate_button:hover:not(.disabled)) { background: #f3f4f6; border-color: #9ca3af; }
:deep(.paginate_button.current) { background: #4f46e5; color: white; border-color: #4f46e5; }
:deep(.paginate_button.disabled) { opacity: 0.5; cursor: not-allowed; background: #f9fafb; }
:deep(#voucher-breakdowns-table_info), :deep(#voucher-breakdowns-table_paginate) { display: none; }
#table-info :deep(.dataTables_info), #table-pagination :deep(.dataTables_paginate) { display: block; }
:deep(#voucher-breakdowns-table tbody td) { padding: 0.75rem 1rem; font-size: 0.875rem; vertical-align: middle; }
</style>
