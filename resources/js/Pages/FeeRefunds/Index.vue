<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Fee Refunds Management</h1>
            <p class="mt-1 text-sm text-gray-600">Track refunded payments and keep voucher balances linked.</p>
          </div>
          <Button @click="$inertia.visit(route('fee-refunds.create'))" variant="primary" class="w-full sm:w-auto shadow-lg text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Refund
          </Button>
        </div>

        <div class="mb-6 rounded-xl bg-white p-4 shadow-md">
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <Input v-model="filters.search" placeholder="Search refund, receipt, voucher, student..." @input="searchDebounced" class="w-full text-sm" />
            <select v-model="filters.status" @change="loadData" class="w-full rounded-lg border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
            <Button variant="secondary" @click="resetFilters" class="w-full text-sm">Reset Filters</Button>
          </div>
        </div>

        <div class="hidden overflow-hidden rounded-xl bg-white shadow-lg md:block">
          <div class="flex flex-col items-start justify-between gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-700">Show</span>
              <select v-model="perPage" @change="changePerPage" class="rounded-lg border-gray-300 px-5 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-sm text-gray-700">entries</span>
            </div>
            <div class="relative w-full sm:w-72">
              <input v-model="tableSearch" @input="tableSearchDebounced" type="text" placeholder="Search in table..." class="w-full rounded-lg border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
              <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table id="fee-refunds-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-indigo-50">
                <tr>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">#</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Receipt No</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Voucher No</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Student</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Amount</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Date</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Method</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Status</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Refunded By</th>
                  <th class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white text-center"></tbody>
            </table>
          </div>

          <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row">
            <div id="table-info" class="text-sm text-gray-600"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <div class="space-y-4 md:hidden">
          <div v-if="mobileLoading" class="flex justify-center rounded-lg bg-white py-12 shadow">
            <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-indigo-600"></div>
          </div>
          <div v-else-if="mobileItems.length === 0" class="rounded-lg bg-white py-12 text-center shadow">
            <p class="text-sm font-medium text-gray-500">No refunds found</p>
          </div>
          <div v-else v-for="item in mobileItems" :key="item.id" class="rounded-lg bg-white p-4 shadow">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="font-semibold text-gray-900">{{ item.payment?.receipt_no ?? '-' }}</p>
                <p class="text-sm text-gray-500">{{ item.student_enrollment?.student?.student_name ?? '-' }}</p>
              </div>
              <span :class="getStatusClass(item.status)" class="rounded-full px-2 py-1 text-xs font-medium">{{ titleCase(item.status) }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 text-sm">
              <div><span class="text-gray-500">Amount</span><p class="font-semibold">Rs {{ formatAmount(item.refund_amount) }}</p></div>
              <div><span class="text-gray-500">Date</span><p class="font-semibold">{{ item.refund_date ?? '-' }}</p></div>
            </div>
            <div class="mt-4 flex gap-2">
              <button @click="openView(item)" class="flex-1 rounded-lg bg-green-50 px-3 py-2 text-sm font-medium text-green-700">View</button>
              <button @click="$inertia.visit(route('fee-refunds.edit', item.id))" class="flex-1 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700">Edit</button>
              <button @click="askDelete(item.id)" class="flex-1 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700">Delete</button>
            </div>
          </div>
        </div>
      </div>

      <Modal :show="showViewModal" max-width="4xl" @close="showViewModal = false">
        <template #title>
          <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </span>
            <span>Refund Tracking Details</span>
          </div>
        </template>

        <div v-if="selectedRefund" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 rounded-lg bg-indigo-50 p-4 md:grid-cols-3">
            <div><p class="text-xs text-indigo-600">Receipt</p><p class="font-semibold text-gray-900">{{ selectedRefund.receipt_no }}</p></div>
            <div><p class="text-xs text-indigo-600">Voucher</p><p class="font-semibold text-gray-900">{{ selectedRefund.voucher_no }}</p></div>
            <div><p class="text-xs text-indigo-600">Student</p><p class="font-semibold text-gray-900">{{ selectedRefund.student }}</p></div>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-lg border p-4"><p class="text-xs text-gray-500">Paid Amount</p><p class="mt-1 font-bold">Rs {{ selectedRefund.paid_amount }}</p></div>
            <div class="rounded-lg border p-4"><p class="text-xs text-gray-500">Refund Amount</p><p class="mt-1 font-bold text-indigo-700">Rs {{ selectedRefund.refund_amount }}</p></div>
            <div class="rounded-lg border p-4"><p class="text-xs text-gray-500">Method</p><p class="mt-1 font-bold">{{ selectedRefund.method }}</p></div>
            <div class="rounded-lg border p-4"><p class="text-xs text-gray-500">Status</p><p class="mt-1 font-bold">{{ selectedRefund.status }}</p></div>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Reason</p><p class="mt-1 text-sm text-gray-900">{{ selectedRefund.reason }}</p></div>
            <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Notes</p><p class="mt-1 text-sm text-gray-900">{{ selectedRefund.notes }}</p></div>
          </div>
          <div class="text-sm text-gray-600">
            Refunded by <span class="font-semibold text-gray-900">{{ selectedRefund.refunded_by }}</span> on <span class="font-semibold text-gray-900">{{ selectedRefund.refund_date }}</span>
          </div>
        </div>
      </Modal>

      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>Delete Refund</template>
        <p class="text-sm text-gray-600">Are you sure you want to delete this refund? Voucher balance will be recalculated automatically.</p>
        <template #footer>
          <div class="flex justify-end gap-3">
            <Button variant="secondary" @click="showDeleteModal = false" class="text-sm">Cancel</Button>
            <Button variant="danger" @click="confirmDelete" :loading="deleting" class="text-sm">Delete Refund</Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

const showDeleteModal = ref(false)
const showViewModal = ref(false)
const selectedRefund = ref(null)
const deleting = ref(false)
const itemToDelete = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobileItems = ref([])
const mobileLoading = ref(true)
const filters = reactive({ search: '', status: '' })
let table = null

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const titleCase = (value) => value ? String(value).charAt(0).toUpperCase() + String(value).slice(1) : '-'
const getStatusClass = (status) => ({ approved: 'bg-green-100 text-green-800', pending: 'bg-yellow-100 text-yellow-800', rejected: 'bg-red-100 text-red-800' }[String(status).toLowerCase()] || 'bg-gray-100 text-gray-800')

const openView = (refund) => {
  selectedRefund.value = refund.receipt_no ? refund : {
    student: refund.student_enrollment?.student?.student_name ?? '-',
    receipt_no: refund.payment?.receipt_no ?? '-',
    voucher_no: refund.payment?.voucher?.voucher_no ?? '-',
    fee_type: refund.payment?.voucher?.fee_type?.fee_name ?? '-',
    paid_amount: formatAmount(refund.payment?.paid_amount),
    refund_amount: formatAmount(refund.refund_amount),
    refund_date: refund.refund_date ?? '-',
    method: titleCase(refund.refund_method),
    status: titleCase(refund.status),
    reason: refund.reason ?? '-',
    notes: refund.notes ?? '-',
    refunded_by: refund.refunded_by?.name ?? '-',
  }
  showViewModal.value = true
}

const askDelete = (id) => {
  itemToDelete.value = id
  showDeleteModal.value = true
}

const loadMobileData = async () => {
  mobileLoading.value = true
  try {
    const params = { mobile: 1, per_page: perPage.value }
    if (filters.search || tableSearch.value) params.search = filters.search || tableSearch.value
    if (filters.status) params.status = filters.status
    const response = await axios.get(route('fee-refunds.index'), { params, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
    mobileItems.value = response.data?.data || []
  } catch {
    mobileItems.value = []
  } finally {
    mobileLoading.value = false
  }
}

const loadData = () => {
  if (table) table.ajax.reload()
  loadMobileData()
}

onMounted(() => {
  loadMobileData()
  table = $('#fee-refunds-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('fee-refunds.index'),
      data(d) {
        d.search.value = filters.search || tableSearch.value
        if (filters.status) d.status = filters.status
      },
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'payment_id' },
      { data: 'voucher_no' },
      { data: 'student_name' },
      { data: 'refund_amount' },
      { data: 'refund_date' },
      { data: 'refund_method', orderable: false },
      { data: 'status', orderable: false },
      { data: 'refunded_by', orderable: false, searchable: false },
      { data: 'action', orderable: false, searchable: false },
    ],
    pageLength: Number(perPage.value),
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[0, 'desc']],
    searching: true,
    info: true,
    dom: 'rt<"hidden"ip>',
    language: {
      emptyTable: '<div class="py-12 text-center text-gray-500">No refunds found</div>',
      processing: '<div class="flex justify-center py-8"><div class="h-10 w-10 animate-spin rounded-full border-b-2 border-indigo-600"></div></div>',
      paginate: {
        first: 'First',
        last: 'Last',
        next: 'Next',
        previous: 'Prev',
      },
    },
    drawCallback() {
      $('#table-info').empty().append($('#fee-refunds-table_info'))
      $('#table-pagination').empty().append($('#fee-refunds-table_paginate'))
    },
  })

  window.viewRefund = openView
  window.editRefund = (refund) => router.visit(route('fee-refunds.edit', refund.id))
  window.deleteRefund = askDelete
})

onBeforeUnmount(() => {
  if (table) table.destroy()
  delete window.viewRefund
  delete window.editRefund
  delete window.deleteRefund
})

const confirmDelete = () => {
  deleting.value = true
  router.delete(route('fee-refunds.destroy', itemToDelete.value), {
    onSuccess: () => {
      showDeleteModal.value = false
      deleting.value = false
      loadData()
    },
    onError: () => { deleting.value = false },
  })
}

let searchTimeout = null
const searchDebounced = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadData, 400)
}

let tableSearchTimeout = null
const tableSearchDebounced = () => {
  clearTimeout(tableSearchTimeout)
  tableSearchTimeout = setTimeout(loadData, 400)
}

const changePerPage = () => {
  if (table) table.page.len(Number(perPage.value)).draw()
  loadMobileData()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  tableSearch.value = ''
  loadData()
}
</script>

<style scoped>
:deep(.dataTables_info) {
  color: #4b5563;
  font-size: 0.875rem;
}

:deep(.dataTables_paginate) {
  display: flex;
  gap: 0.25rem;
}

:deep(.paginate_button) {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  color: #374151;
  cursor: pointer;
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
}

:deep(.paginate_button.current) {
  background: #4f46e5;
  border-color: #4f46e5;
  color: white;
}

:deep(.paginate_button.disabled) {
  cursor: not-allowed;
  opacity: 0.5;
}

:deep(#fee-refunds-table tbody td) {
  font-size: 0.875rem;
  padding: 0.85rem 1rem;
  vertical-align: middle;
}
</style>
