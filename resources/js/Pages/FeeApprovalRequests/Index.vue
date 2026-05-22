<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Fee Approval Requests</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Track approvals for waivers, fines, refunds, concessions, installments and voucher edits</p>
            </div>
            <Button @click="$inertia.visit(route('fee-approval-requests.create'))" variant="primary" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm">
              <PlusIcon class="h-4 w-4 mr-2" />
              New Request
            </Button>
          </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <Input v-model="filters.search" placeholder="Search request, voucher, student..." @input="searchDebounced" class="w-full text-sm" />
            <select v-model="filters.request_type" @change="loadData" class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">All Request Types</option>
              <option v-for="type in requestTypes" :key="type.id" :value="type.id">{{ type.label }}</option>
            </select>
            <select v-model="filters.status" @change="loadData" class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="processed">Processed</option>
              <option value="rejected">Rejected</option>
            </select>
            <Button variant="secondary" @click="resetFilters" class="w-full shadow-sm hover:shadow-md transition-all text-sm">Reset Filters</Button>
          </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
              <span class="text-xs sm:text-sm text-gray-700">Show</span>
              <select v-model="perPage" @change="changePerPage" class="px-3 sm:px-6 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-xs sm:text-sm text-gray-700">entries</span>
            </div>

            <div class="w-full sm:w-72">
              <div class="relative">
                <input v-model="tableSearch" @input="tableSearchDebounced" type="text" placeholder="Search in table..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm" />
                <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table id="approval-requests-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">#</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Type</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Student</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Voucher</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Amount</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Urgency</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Status</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Requested</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white text-center divide-y divide-gray-100"></tbody>
            </table>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 bg-gray-50 gap-3 sm:gap-4">
            <div class="text-xs sm:text-sm text-gray-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>
      </div>

      <Modal :show="showViewModal" max-width="4xl" @close="showViewModal = false">
        <template #title>Approval Request Details</template>
        <div v-if="selectedRequest" class="space-y-5 text-sm">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg bg-indigo-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Type</p>
              <p class="mt-1 font-bold text-gray-900">{{ selectedRequest.request_type }}</p>
              <p class="mt-2 text-xs text-gray-500">{{ selectedRequest.urgency }} urgency</p>
            </div>
            <div class="rounded-lg bg-green-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Requested</p>
              <p class="mt-1 text-lg font-bold text-green-700">Rs {{ selectedRequest.requested_amount }}</p>
              <p class="mt-2 text-xs text-gray-500">Current Rs {{ selectedRequest.current_amount }}</p>
            </div>
            <div class="rounded-lg bg-blue-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Status</p>
              <p class="mt-1 font-bold text-gray-900">{{ selectedRequest.status }}</p>
              <p class="mt-2 text-xs text-gray-500">{{ selectedRequest.requested_at }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><p class="text-xs text-gray-500">Student</p><p class="font-semibold">{{ selectedRequest.student_name }}</p><p class="text-xs text-gray-500">{{ selectedRequest.admission_no }}</p></div>
            <div><p class="text-xs text-gray-500">Voucher</p><p class="font-semibold">{{ selectedRequest.voucher_no }}</p><p class="text-xs text-gray-500">{{ selectedRequest.fee_type }}</p></div>
            <div><p class="text-xs text-gray-500">Requested By</p><p class="font-semibold">{{ selectedRequest.requested_by }}</p></div>
            <div><p class="text-xs text-gray-500">Reviewed By</p><p class="font-semibold">{{ selectedRequest.reviewed_by }}</p><p class="text-xs text-gray-500">{{ selectedRequest.reviewed_at }}</p></div>
          </div>

          <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reason</p>
            <p class="mt-2 text-gray-800 whitespace-pre-wrap">{{ selectedRequest.reason }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Supporting Notes</p><p class="mt-1 font-medium whitespace-pre-wrap">{{ selectedRequest.supporting_notes }}</p></div>
            <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Reviewer Remarks</p><p class="mt-1 font-medium whitespace-pre-wrap">{{ selectedRequest.reviewer_remarks }}</p></div>
          </div>
        </div>
        <template #footer>
          <div class="flex justify-end gap-3">
            <Button variant="secondary" @click="showViewModal = false">Close</Button>
            <Button v-if="selectedRequest?.id" variant="primary" @click="$inertia.visit(route('fee-approval-requests.show', selectedRequest.id))">Open Full Page</Button>
          </div>
        </template>
      </Modal>

      <Modal :show="showDecisionModal" @close="closeDecisionModal">
        <template #title>{{ decisionAction === 'approve' ? 'Approve Request' : 'Reject Request' }}</template>
        <div class="space-y-3">
          <p class="text-sm text-gray-600">
            {{ decisionAction === 'approve' ? 'Approval may apply the linked action, such as fee waiver or fine waiver.' : 'Please add a short rejection reason.' }}
          </p>
          <textarea v-model="decisionRemarks" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Remarks..." />
        </div>
        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-3">
            <Button variant="secondary" @click="closeDecisionModal" class="w-full sm:w-auto">Cancel</Button>
            <Button :variant="decisionAction === 'approve' ? 'success' : 'danger'" :loading="decisionProcessing" @click="submitDecision" class="w-full sm:w-auto">
              {{ decisionAction === 'approve' ? 'Approve' : 'Reject' }}
            </Button>
          </div>
        </template>
      </Modal>

      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>Delete Approval Request</template>
        <p class="text-sm text-gray-600">Only pending approval requests can be deleted. Are you sure?</p>
        <template #footer>
          <div class="flex justify-end gap-3">
            <Button variant="secondary" @click="showDeleteModal = false">Cancel</Button>
            <Button variant="danger" :loading="deleting" @click="confirmDelete">Delete</Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Modal from '@/Components/Common/Modal.vue'
import Input from '@/Components/Forms/Input.vue'
import $ from 'jquery'
import 'datatables.net'

const filters = reactive({ search: '', request_type: '', status: '' })
const tableSearch = ref('')
const perPage = ref(10)
const showViewModal = ref(false)
const selectedRequest = ref(null)
const showDecisionModal = ref(false)
const decisionAction = ref('approve')
const decisionRequestId = ref(null)
const decisionRemarks = ref('')
const decisionProcessing = ref(false)
const showDeleteModal = ref(false)
const deleteRequestId = ref(null)
const deleting = ref(false)
let table = null

const requestTypes = [
  { id: 'fee_waiver', label: 'Fee Waiver' },
  { id: 'fine_waiver', label: 'Fine Waiver' },
  { id: 'fee_refund', label: 'Fee Refund' },
  { id: 'fee_concession', label: 'Fee Concession' },
  { id: 'installment_plan', label: 'Installment Plan' },
  { id: 'fee_edit', label: 'Voucher Edit' },
]

onMounted(() => {
  table = $('#approval-requests-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('fee-approval-requests.index'),
      data: (d) => {
        d.search.value = filters.search || tableSearch.value
        d.status = filters.status
        d.request_type = filters.request_type
      },
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'request_type', name: 'request_type' },
      { data: 'student_html', name: 'student_enrollment_id', orderable: false },
      { data: 'voucher_html', name: 'voucher_id', orderable: false },
      { data: 'amount_html', name: 'requested_amount' },
      { data: 'urgency', name: 'urgency' },
      { data: 'status', name: 'status' },
      { data: 'requested_at', name: 'requested_at' },
      { data: 'action', orderable: false, searchable: false, className: 'text-center' },
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[0, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-16"><div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4"><svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg></div><p class="text-sm font-semibold text-gray-700">No approval requests found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div></div>',
      paginate: {
        first: '<span class="px-1"><<</span>',
        last: '<span class="px-1">>></span>',
        next: '<span class="px-1">></span>',
        previous: '<span class="px-1"><</span>',
      },
    },
    drawCallback: () => {
      $('#table-info').empty().append($('#approval-requests-table_info'))
      $('#table-pagination').empty().append($('#approval-requests-table_paginate'))
    },
  })
})

window.viewRequest = (request) => {
  selectedRequest.value = request
  showViewModal.value = true
}
window.editRequest = (request) => router.visit(route('fee-approval-requests.edit', request.id))
window.approveRequest = (request) => openDecision('approve', request.id)
window.rejectRequest = (request) => openDecision('reject', request.id)
window.deleteRequest = (id) => {
  deleteRequestId.value = id
  showDeleteModal.value = true
}

const openDecision = (action, id) => {
  decisionAction.value = action
  decisionRequestId.value = id
  decisionRemarks.value = action === 'approve' ? 'Approved' : ''
  showDecisionModal.value = true
}
const closeDecisionModal = () => {
  showDecisionModal.value = false
  decisionProcessing.value = false
}
const submitDecision = () => {
  decisionProcessing.value = true
  const routeName = decisionAction.value === 'approve' ? 'fee-approval-requests.approve' : 'fee-approval-requests.reject'
  router.post(route(routeName, decisionRequestId.value), { reviewer_remarks: decisionRemarks.value }, {
    preserveScroll: true,
    onSuccess: () => { closeDecisionModal(); loadData() },
    onError: () => { decisionProcessing.value = false },
  })
}
const confirmDelete = () => {
  deleting.value = true
  router.delete(route('fee-approval-requests.destroy', deleteRequestId.value), {
    preserveScroll: true,
    onSuccess: () => { deleting.value = false; showDeleteModal.value = false; loadData() },
    onError: () => { deleting.value = false },
  })
}

let tableSearchTimeout = null
const tableSearchDebounced = () => { clearTimeout(tableSearchTimeout); tableSearchTimeout = setTimeout(loadData, 500) }
let searchTimeout = null
const searchDebounced = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(loadData, 500) }
const changePerPage = () => { if (table) table.page.len(perPage.value).draw() }
const loadData = () => { if (table) table.ajax.reload(null, false) }
const resetFilters = () => {
  filters.search = ''
  filters.request_type = ''
  filters.status = ''
  tableSearch.value = ''
  loadData()
}
</script>

<style scoped>
:deep(.dataTables_info) { font-size: 0.875rem; color: #4b5563; font-weight: 500; }
:deep(.dataTables_paginate) { display: flex; justify-content: flex-end; gap: 0.25rem; flex-wrap: wrap; }
:deep(.paginate_button) { padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white; color: #374151; cursor: pointer; transition: all 0.2s; }
:deep(.paginate_button:hover:not(.disabled)) { background: #f3f4f6; border-color: #9ca3af; }
:deep(.paginate_button.current) { background: #4f46e5; color: white; border-color: #4f46e5; }
:deep(.paginate_button.disabled) { opacity: 0.5; cursor: not-allowed; background: #f9fafb; }
:deep(#approval-requests-table_info), :deep(#approval-requests-table_paginate) { display: none; }
#table-info :deep(.dataTables_info), #table-pagination :deep(.dataTables_paginate) { display: block; }
:deep(#approval-requests-table tbody td) { padding: 0.75rem 1.5rem; font-size: 0.875rem; }
</style>
