<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Refund</h1>
            <p class="mt-1 text-sm text-gray-600">Select a payment, review its voucher, and track the refund against the same fee flow.</p>
          </div>
          <Button @click="$inertia.visit(route('fee-refunds.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
          </Button>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div class="rounded-xl bg-white p-5 shadow-md">
            <div class="mb-4 flex items-center gap-3">
              <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">1</span>
              <div>
                <h2 class="text-base font-semibold text-gray-900">Payment Selection</h2>
                <p class="text-xs text-gray-500">Search receipt no, voucher no, student name, admission or fee type.</p>
              </div>
            </div>

            <label class="mb-2 block text-sm font-medium text-gray-700">Payment <span class="text-red-500">*</span></label>
            <SearchablePicker
              v-model="form.payment_id"
              :items="paymentOptions"
              placeholder="Search payment or voucher..."
              title="Payments"
              empty-text="No refundable payments found"
              :error="form.errors.payment_id"
              @select="selectPayment"
              @clear="clearPayment"
            />

            <div v-if="selectedPayment" class="mt-5 grid grid-cols-1 gap-3 rounded-lg border border-indigo-100 bg-indigo-50 p-4 md:grid-cols-4">
              <div>
                <p class="text-xs text-indigo-500">Receipt</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedPayment.label }}</p>
              </div>
              <div>
                <p class="text-xs text-indigo-500">Voucher</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedPayment.voucher_no }}</p>
              </div>
              <div>
                <p class="text-xs text-indigo-500">Fee Type</p>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedPayment.fee_type }}</p>
              </div>
              <div>
                <p class="text-xs text-indigo-500">Refundable</p>
                <p class="mt-1 text-sm font-semibold text-indigo-700">Rs {{ formatAmount(selectedPayment.refundable_amount) }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-xl bg-white p-5 shadow-md">
            <div class="mb-4 flex items-center gap-3">
              <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">2</span>
              <div>
                <h2 class="text-base font-semibold text-gray-900">Refund Details</h2>
                <p class="text-xs text-gray-500">Approved refunds reduce voucher paid amount and increase remaining balance automatically.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Refund Amount <span class="text-red-500">*</span></label>
                <input
                  v-model="form.refund_amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  :max="selectedPayment?.refundable_amount || undefined"
                  class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <p v-if="selectedPayment" class="mt-1 text-xs text-gray-500">Max Rs {{ formatAmount(selectedPayment.refundable_amount) }}</p>
                <p v-if="form.errors.refund_amount" class="mt-1 text-xs text-red-600">{{ form.errors.refund_amount }}</p>
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Refund Date <span class="text-red-500">*</span></label>
                <input v-model="form.refund_date" type="date" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                <p v-if="form.errors.refund_date" class="mt-1 text-xs text-red-600">{{ form.errors.refund_date }}</p>
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Refund Method</label>
                <SearchablePicker
                  v-model="form.refund_method"
                  :items="methodOptions"
                  placeholder="Search refund method..."
                  title="Refund Methods"
                  empty-text="No method found"
                  :error="form.errors.refund_method"
                />
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                <SearchablePicker
                  v-model="form.status"
                  :items="statusOptions"
                  placeholder="Search status..."
                  title="Statuses"
                  empty-text="No status found"
                  :error="form.errors.status"
                />
              </div>

              <div v-if="form.refund_method !== 'cash'" class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-700">Bank / Cheque Details</label>
                <input v-model="form.bank_details" type="text" placeholder="Bank name, account, cheque/reference no..." class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
              </div>

              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-700">Reason <span class="text-red-500">*</span></label>
                <textarea v-model="form.reason" rows="3" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></textarea>
                <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
              </div>

              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-700">Notes</label>
                <textarea v-model="form.notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
              </div>
            </div>
          </div>

          <div v-if="selectedPayment" class="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Refund Tracking Preview</p>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
              <div class="rounded-lg bg-white p-4">
                <p class="text-xs text-gray-500">Paid Amount</p>
                <p class="mt-1 text-lg font-bold text-gray-900">Rs {{ formatAmount(selectedPayment.paid_amount) }}</p>
              </div>
              <div class="rounded-lg bg-white p-4">
                <p class="text-xs text-gray-500">Already Refunded</p>
                <p class="mt-1 text-lg font-bold text-amber-600">Rs {{ formatAmount(selectedPayment.refunded_amount) }}</p>
              </div>
              <div class="rounded-lg bg-white p-4">
                <p class="text-xs text-gray-500">This Refund</p>
                <p class="mt-1 text-lg font-bold text-indigo-600">Rs {{ formatAmount(form.refund_amount || 0) }}</p>
              </div>
              <div class="rounded-lg bg-white p-4">
                <p class="text-xs text-gray-500">After Refund</p>
                <p class="mt-1 text-lg font-bold text-red-600">Rs {{ formatAmount(afterRefund) }}</p>
              </div>
            </div>
          </div>

          <div class="flex flex-col justify-end gap-3 sm:flex-row">
            <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-refunds.index'))" class="w-full sm:w-auto text-sm">Cancel</Button>
            <Button type="submit" variant="primary" :loading="form.processing" class="w-full sm:w-auto text-sm shadow-lg">
              <span v-if="!form.processing">Create Refund</span>
              <span v-else>Creating...</span>
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
  payments: { type: Array, default: () => [] },
})

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
  student_enrollment_id: '',
  payment_id: '',
  refund_amount: '',
  refund_date: today,
  reason: '',
  refund_method: 'cash',
  bank_details: '',
  status: 'pending',
  notes: '',
})

const paymentOptions = computed(() => props.payments || [])
const selectedPayment = computed(() => paymentOptions.value.find((item) => String(item.id) === String(form.payment_id)))

const methodOptions = [
  { id: 'cash', label: 'Cash', subtitle: 'Cash refund to student/parent' },
  { id: 'bank_transfer', label: 'Bank Transfer', subtitle: 'Refund through bank account' },
  { id: 'cheque', label: 'Cheque', subtitle: 'Refund through cheque' },
]

const statusOptions = [
  { id: 'pending', label: 'Pending', subtitle: 'Track request before final approval' },
  { id: 'approved', label: 'Approved', subtitle: 'Apply refund to voucher balance' },
  { id: 'rejected', label: 'Rejected', subtitle: 'Keep record without changing balance' },
]

const afterRefund = computed(() => {
  const refundable = Number(selectedPayment.value?.refundable_amount || 0)
  const amount = Number(form.refund_amount || 0)
  return Math.max(0, refundable - amount)
})

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const selectPayment = (payment) => {
  form.payment_id = payment.id
  form.student_enrollment_id = payment.student_enrollment_id
  form.refund_amount = payment.refundable_amount || ''
}

const clearPayment = () => {
  form.payment_id = ''
  form.student_enrollment_id = ''
  form.refund_amount = ''
}

const submit = () => {
  form.post(route('fee-refunds.store'), { preserveScroll: true })
}
</script>
