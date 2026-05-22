<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Payment</h1>
            <p class="mt-1 text-sm text-gray-500">Update payment details for <span class="font-medium text-gray-700">{{ payment.receipt_no }}</span></p>
          </div>
          <Button @click="$inertia.visit(route('fee-payments.index'))" variant="secondary" class="w-full sm:w-auto text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Payments
          </Button>
        </div>

        <div class="max-w-3xl space-y-4">

          <!-- Payment Info Card (read-only) -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payment Information</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <p class="text-xs text-gray-400 mb-0.5">Receipt No</p>
                <p class="text-sm font-bold text-indigo-600 font-mono">{{ payment.receipt_no }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400 mb-0.5">Student</p>
                <p class="text-sm font-semibold text-gray-900">{{ payment.student_name || '—' }}</p>
                <p class="text-xs text-gray-400">{{ payment.admission_no }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400 mb-0.5">Fee Type</p>
                <p class="text-sm font-medium text-gray-800">{{ payment.fee_type || '—' }}</p>
                <p class="text-xs text-gray-400 font-mono">{{ payment.voucher_no }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400 mb-0.5">Voucher Amount</p>
                <p class="text-sm font-bold text-gray-900">Rs. {{ Number(payment.net_amount || 0).toLocaleString() }}</p>
                <span :class="{
                  'bg-green-100 text-green-700': payment.voucher_status === 'paid',
                  'bg-yellow-100 text-yellow-700': payment.voucher_status === 'partial',
                  'bg-red-100 text-red-700': payment.voucher_status === 'pending',
                }" class="inline-block px-2 py-0.5 text-xs font-medium rounded-full capitalize mt-1">
                  {{ payment.voucher_status || '—' }}
                </span>
              </div>
            </div>
            <div v-if="payment.created_at_display || payment.received_by" class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-4 text-xs text-gray-400">
              <span v-if="payment.received_by">Collected by: <span class="text-gray-600 font-medium">{{ payment.received_by }}</span></span>
              <span v-if="payment.created_at_display">Recorded: <span class="text-gray-600">{{ payment.created_at_display }}</span></span>
            </div>
          </div>

          <!-- Edit Form -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Edit Details</h2>

            <!-- Error Alert -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <ul class="text-sm text-red-700 space-y-0.5">
                  <li v-for="(msg, field) in form.errors" :key="field">{{ Array.isArray(msg) ? msg[0] : msg }}</li>
                </ul>
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

              <!-- Amount + Date -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Paid Amount (Rs.) <span class="text-red-500">*</span></label>
                  <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-500 text-sm font-medium">Rs.</span>
                    <input
                      v-model="form.paid_amount"
                      type="number" step="1" min="0.01"
                      :class="{ 'border-red-500': form.errors.paid_amount }"
                      class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                    />
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date <span class="text-red-500">*</span></label>
                  <input
                    v-model="form.payment_date"
                    type="date"
                    :class="{ 'border-red-500': form.errors.payment_date }"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  />
                </div>
              </div>

              <!-- Payment Method -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                  <button
                    v-for="m in paymentMethods" :key="m.value"
                    @click="form.payment_method = m.value"
                    type="button"
                    :class="[
                      'flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all text-center',
                      form.payment_method === m.value
                        ? `border-${m.color}-500 bg-${m.color}-50`
                        : 'border-gray-200 hover:border-gray-300 bg-white'
                    ]">
                    <span class="text-lg mb-1">{{ m.icon }}</span>
                    <span class="text-xs font-medium" :class="form.payment_method === m.value ? `text-${m.color}-700` : 'text-gray-600'">{{ m.label }}</span>
                  </button>
                </div>
              </div>

              <!-- Bank / Transaction fields -->
              <div v-if="needsTransactionRef" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-if="form.payment_method === 'bank_transfer' || form.payment_method === 'cheque'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                  <input v-model="form.bank_name" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="e.g., HBL, MCB, Meezan" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ form.payment_method === 'cheque' ? 'Cheque No.' : 'Transaction ID' }}
                  </label>
                  <input v-model="form.transaction_ref" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                    :placeholder="form.payment_method === 'cheque' ? 'e.g., 001234' : 'Transaction reference'" />
                </div>
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                <input v-model="form.notes" type="text"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  placeholder="Additional notes..." />
              </div>

              <!-- Actions -->
              <div class="flex gap-3 justify-end pt-2 border-t border-gray-100">
                <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-payments.index'))" class="text-sm">Cancel</Button>
                <Button type="submit" variant="primary" :loading="form.processing" class="text-sm px-8">
                  <svg v-if="!form.processing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  <span v-if="!form.processing">Update Payment</span>
                  <span v-else>Updating...</span>
                </Button>
              </div>

            </form>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

const props = defineProps({
  payment: { type: Object, required: true },
})

const paymentMethods = [
  { value: 'cash',          label: 'Cash',      icon: '💵', color: 'green'   },
  { value: 'bank_transfer', label: 'Bank',       icon: '🏦', color: 'blue'    },
  { value: 'jazzcash',      label: 'JazzCash',   icon: '📱', color: 'red'     },
  { value: 'easypaisa',     label: 'Easypaisa',  icon: '📲', color: 'emerald' },
  { value: 'cheque',        label: 'Cheque',     icon: '📄', color: 'purple'  },
]

const form = useForm({
  paid_amount:    props.payment.paid_amount,
  payment_date:   props.payment.payment_date ?? '',
  payment_method: props.payment.payment_method ?? 'cash',
  bank_name:      props.payment.bank_name ?? '',
  transaction_ref: props.payment.transaction_ref ?? '',
  notes:          props.payment.notes ?? '',
})

const needsTransactionRef = computed(() =>
  ['bank_transfer', 'jazzcash', 'easypaisa', 'cheque', 'online'].includes(form.payment_method)
)

const submit = () => {
  form.put(route('fee-payments.update', props.payment.id), { preserveScroll: true })
}
</script>
