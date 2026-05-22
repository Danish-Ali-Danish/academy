<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Approval Request</h1>
            <p class="mt-2 text-sm text-gray-600">Keep the request linked to the correct student voucher and review flow.</p>
          </div>
          <Button @click="$inertia.visit(route('fee-approval-requests.index'))" variant="secondary" class="w-full sm:w-auto">Back to List</Button>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-xl shadow-md p-4 sm:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Request Type <span class="text-red-500">*</span></label>
              <SearchablePicker v-model="form.request_type" :items="requestTypes" title="Request Types" placeholder="Search request type..." :error="form.errors.request_type" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Urgency <span class="text-red-500">*</span></label>
              <SearchablePicker v-model="form.urgency" :items="urgencies" title="Urgency" placeholder="Search urgency..." :error="form.errors.urgency" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Student Enrollment <span class="text-red-500">*</span></label>
              <SearchablePicker v-model="form.student_enrollment_id" :items="enrollments" title="Students" placeholder="Search student, admission, roll..." :error="form.errors.student_enrollment_id" @select="selectEnrollment" @clear="clearEnrollment" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Voucher</label>
              <SearchablePicker v-model="form.voucher_id" :items="filteredVouchers" title="Vouchers" placeholder="Search voucher..." :error="form.errors.voucher_id" @select="selectVoucher" @clear="clearVoucher" />
            </div>

            <div v-if="selectedVoucher" class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Voucher</p><p class="mt-1 font-semibold text-gray-900">{{ selectedVoucher.label }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Current Net</p><p class="mt-1 font-bold text-indigo-700">Rs {{ money(selectedVoucher.net_amount) }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Remaining</p><p class="mt-1 font-bold text-indigo-700">Rs {{ money(selectedVoucher.remaining_amount) }}</p></div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Current Amount <span class="text-red-500">*</span></label>
              <input v-model="form.current_amount" type="number" step="0.01" min="0" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
              <p v-if="form.errors.current_amount" class="mt-1 text-xs text-red-600">{{ form.errors.current_amount }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Requested Amount <span class="text-red-500">*</span></label>
              <input v-model="form.requested_amount" type="number" step="0.01" min="0" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
              <p v-if="form.errors.requested_amount" class="mt-1 text-xs text-red-600">{{ form.errors.requested_amount }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Requested Percent</label>
              <input v-model="form.requested_percent" type="number" step="0.01" min="0" max="100" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <SearchablePicker v-model="form.status" :items="statuses" title="Status" placeholder="Search status..." :error="form.errors.status" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Reason <span class="text-red-500">*</span></label>
              <textarea v-model="form.reason" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required placeholder="Reason for approval..." />
              <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Supporting Notes</label>
              <textarea v-model="form.supporting_notes" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Optional notes..." />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Reviewer Remarks</label>
              <textarea v-model="form.reviewer_remarks" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Reviewer notes..." />
            </div>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
            <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-approval-requests.index'))" class="w-full sm:w-auto">Cancel</Button>
            <Button type="submit" variant="primary" :loading="form.processing" class="w-full sm:w-auto">
              <span v-if="!form.processing">Update Request</span>
              <span v-else>Updating...</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import SearchablePicker from '@/Components/Finance/SearchablePicker.vue'

const props = defineProps({
  request: { type: Object, required: true },
  enrollments: { type: Array, default: () => [] },
  vouchers: { type: Array, default: () => [] },
})

const form = useForm({
  request_type: props.request.request_type || 'fee_waiver',
  student_enrollment_id: props.request.student_enrollment_id || '',
  voucher_id: props.request.voucher_id || '',
  requested_amount: props.request.requested_amount || '',
  requested_percent: props.request.requested_percent || '',
  current_amount: props.request.current_amount || '',
  reason: props.request.reason || '',
  supporting_notes: props.request.supporting_notes || '',
  urgency: props.request.urgency || 'medium',
  status: props.request.status || 'pending',
  reviewer_remarks: props.request.reviewer_remarks || '',
})

const requestTypes = [
  { id: 'fee_waiver', label: 'Fee Waiver', subtitle: 'Approved waiver will reduce voucher payable amount' },
  { id: 'fine_waiver', label: 'Fine Waiver', subtitle: 'Waive applied voucher fines after approval' },
  { id: 'fee_refund', label: 'Fee Refund', subtitle: 'Track refund approval before processing' },
  { id: 'fee_concession', label: 'Fee Concession', subtitle: 'Approval tracking for concessions' },
  { id: 'installment_plan', label: 'Installment Plan', subtitle: 'Approval tracking for installment requests' },
  { id: 'fee_edit', label: 'Voucher Edit', subtitle: 'Approval tracking for voucher changes' },
]
const urgencies = [
  { id: 'low', label: 'Low' },
  { id: 'medium', label: 'Medium' },
  { id: 'high', label: 'High' },
  { id: 'urgent', label: 'Urgent' },
]
const statuses = [
  { id: 'pending', label: 'Pending' },
  { id: 'approved', label: 'Approved' },
  { id: 'processed', label: 'Processed' },
  { id: 'rejected', label: 'Rejected' },
]

const selectedVoucher = computed(() => props.vouchers.find((voucher) => String(voucher.id) === String(form.voucher_id)))
const filteredVouchers = computed(() => {
  if (!form.student_enrollment_id) return props.vouchers
  return props.vouchers.filter((voucher) => String(voucher.student_enrollment_id) === String(form.student_enrollment_id))
})

const selectEnrollment = () => {
  if (selectedVoucher.value && String(selectedVoucher.value.student_enrollment_id) !== String(form.student_enrollment_id)) clearVoucher()
}
const clearEnrollment = () => {
  form.student_enrollment_id = ''
  clearVoucher()
}
const selectVoucher = (voucher) => {
  form.student_enrollment_id = voucher.student_enrollment_id
  form.current_amount = voucher.remaining_amount
  if (!form.requested_amount) form.requested_amount = voucher.remaining_amount
}
const clearVoucher = () => {
  form.voucher_id = ''
  form.current_amount = ''
}
const money = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const submit = () => form.put(route('fee-approval-requests.update', props.request.id), { preserveScroll: true })
</script>
