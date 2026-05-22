<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Add Online Payment Proof</h1>
            <p class="mt-1 text-sm text-gray-500">Select a voucher, attach proof details, and submit for verification.</p>
          </div>
          <Button @click="$inertia.visit(route('online-payment-proofs.index'))" variant="secondary" class="w-full sm:w-auto text-sm">Back</Button>
        </div>

        <div class="max-w-4xl space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Voucher <span class="text-red-500">*</span></label>
            <div class="relative">
              <div class="relative">
                <input
                  v-model="voucherSearch"
                  type="text"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm pl-10 pr-10"
                  :class="{ 'border-red-500': form.errors.voucher_id }"
                  placeholder="Search voucher no or student name..."
                  autocomplete="off"
                  @focus="showVoucherDropdown = true"
                  @input="onVoucherSearch"
                  @keydown.down.prevent="moveVoucherHighlight(1)"
                  @keydown.up.prevent="moveVoucherHighlight(-1)"
                  @keydown.enter.prevent="selectHighlightedVoucher"
                  @keydown.escape="showVoucherDropdown = false"
                  @blur="hideVoucherDropdown"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <button
                  v-if="form.voucher_id"
                  type="button"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors"
                  title="Clear voucher"
                  @mousedown.prevent="clearVoucher"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div
                v-if="showVoucherDropdown"
                class="absolute z-30 mt-1 w-full bg-white shadow-xl max-h-72 rounded-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm"
              >
                <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50">
                  Pending Vouchers
                </div>
                <button
                  v-for="(voucher, index) in filteredVouchers"
                  :key="voucher.id"
                  type="button"
                  class="w-full text-left px-4 py-3 border-b border-gray-100 last:border-0 transition-colors"
                  :class="index === highlightedVoucherIndex ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : 'hover:bg-gray-50'"
                  @mousedown.prevent="selectVoucher(voucher)"
                  @mouseenter="highlightedVoucherIndex = index"
                >
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">
                          {{ voucher.voucher_no || 'No voucher no' }}
                        </span>
                        <span class="font-medium text-gray-900 truncate">{{ voucherStudentName(voucher) }}</span>
                      </div>
                      <p class="mt-1 text-xs text-gray-500">
                        {{ voucher.status || 'pending' }} voucher
                      </p>
                    </div>
                    <div class="text-xs sm:text-right">
                      <p class="font-semibold text-gray-900">Rs. {{ formatAmount(voucher.remaining_amount) }}</p>
                      <p class="text-gray-500">remaining</p>
                    </div>
                  </div>
                </button>
                <div v-if="filteredVouchers.length === 0" class="px-4 py-5 text-center text-sm text-gray-500">
                  No pending voucher found.
                </div>
              </div>
            </div>
            <p v-if="form.errors.voucher_id" class="mt-1 text-xs text-red-600">{{ form.errors.voucher_id }}</p>

            <div v-if="selectedVoucher" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Student</div><div class="font-semibold text-indigo-900">{{ selectedVoucher.student_enrollment?.student?.student_name || '-' }}</div></div>
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Voucher</div><div class="font-semibold text-indigo-900">{{ selectedVoucher.voucher_no }}</div></div>
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Remaining</div><div class="font-semibold text-indigo-900">Rs. {{ Number(selectedVoucher.remaining_amount || 0).toLocaleString() }}</div></div>
            </div>
          </div>

          <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student Enrollment ID <span class="text-red-500">*</span></label>
                <input v-model="form.student_enrollment_id" type="text" readonly class="block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                <select v-model="form.payment_method" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                  <option value="jazzcash">JazzCash</option>
                  <option value="easypaisa">Easypaisa</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="raast">Raast</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Account <span class="text-red-500">*</span></label>
                <select v-model="form.academy_account_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                  <option value="">Select account</option>
                  <option v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.account_title }} ({{ account.payment_method }})
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount Sent <span class="text-red-500">*</span></label>
                <input v-model="form.amount_sent" type="number" step="0.01" min="0.01" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date & Time <span class="text-red-500">*</span></label>
                <input v-model="form.payment_datetime" type="datetime-local" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Screenshot Path / URL</label>
                <input v-model="form.screenshot_path" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="/storage/proofs/proof.jpg" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sender Name</label>
                <input v-model="form.sender_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sender Number</label>
                <input v-model="form.sender_number" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
                <input v-model="form.transaction_id" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input v-model="form.submission_notes" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('online-payment-proofs.index'))">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing">Submit Proof</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

const props = defineProps({
  vouchers: { type: Array, default: () => [] },
  accounts: { type: Array, default: () => [] },
})

const form = useForm({
  voucher_id: '',
  student_enrollment_id: '',
  academy_account_id: '',
  payment_method: 'jazzcash',
  sender_name: '',
  sender_number: '',
  transaction_id: '',
  amount_sent: '',
  payment_datetime: new Date().toISOString().slice(0, 16),
  screenshot_path: '',
  submission_notes: '',
})

const selectedVoucher = computed(() => props.vouchers.find(voucher => String(voucher.id) === String(form.voucher_id)))
const voucherSearch = ref('')
const showVoucherDropdown = ref(false)
const highlightedVoucherIndex = ref(-1)

const voucherStudentName = (voucher) => voucher?.student_enrollment?.student?.student_name || 'Student'
const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const voucherLabel = (voucher) => voucher
  ? `${voucher.voucher_no || 'No voucher no'} - ${voucherStudentName(voucher)} - Rs. ${formatAmount(voucher.remaining_amount)} remaining`
  : ''

const filteredVouchers = computed(() => {
  const query = voucherSearch.value.trim().toLowerCase()

  if (!query) return props.vouchers.slice(0, 20)

  return props.vouchers.filter((voucher) => [
    voucher.voucher_no,
    voucherStudentName(voucher),
    voucher.status,
    voucher.remaining_amount,
  ].some((value) => String(value || '').toLowerCase().includes(query))).slice(0, 30)
})

const selectVoucher = (voucher) => {
  form.voucher_id = voucher.id
  voucherSearch.value = voucherLabel(voucher)
  showVoucherDropdown.value = false
  highlightedVoucherIndex.value = -1
}

const clearVoucher = () => {
  form.voucher_id = ''
  voucherSearch.value = ''
  highlightedVoucherIndex.value = -1
  form.student_enrollment_id = ''
  form.amount_sent = ''
}

const onVoucherSearch = () => {
  if (form.voucher_id && voucherSearch.value !== voucherLabel(selectedVoucher.value)) {
    form.voucher_id = ''
    form.student_enrollment_id = ''
    form.amount_sent = ''
  }

  showVoucherDropdown.value = true
  highlightedVoucherIndex.value = filteredVouchers.value.length ? 0 : -1
}

const hideVoucherDropdown = () => {
  setTimeout(() => {
    showVoucherDropdown.value = false
  }, 150)
}

const moveVoucherHighlight = (step) => {
  showVoucherDropdown.value = true
  if (!filteredVouchers.value.length) return

  const nextIndex = highlightedVoucherIndex.value + step
  if (nextIndex < 0) highlightedVoucherIndex.value = filteredVouchers.value.length - 1
  else if (nextIndex >= filteredVouchers.value.length) highlightedVoucherIndex.value = 0
  else highlightedVoucherIndex.value = nextIndex
}

const selectHighlightedVoucher = () => {
  const voucher = filteredVouchers.value[highlightedVoucherIndex.value]
  if (voucher) selectVoucher(voucher)
}

watch(selectedVoucher, (voucher) => {
  if (!voucher) {
    form.student_enrollment_id = ''
    return
  }

  voucherSearch.value = voucherLabel(voucher)
  form.student_enrollment_id = voucher.student_enrollment_id
  if (!form.amount_sent) {
    form.amount_sent = Number(voucher.remaining_amount || 0)
  }
}, { immediate: true })

const submit = () => {
  form.post(route('online-payment-proofs.store'))
}
</script>
