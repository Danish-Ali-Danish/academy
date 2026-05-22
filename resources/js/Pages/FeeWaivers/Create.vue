<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Fee Waiver</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Search a pending voucher and apply an approved waiver</p>
            </div>
            <Button @click="$inertia.visit(route('fee-waivers.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to List
            </Button>
          </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Voucher Search <span class="text-red-500">*</span>
                </label>
                <SearchablePicker
                  v-model="form.voucher_id"
                  :items="vouchers"
                  title="Pending Vouchers"
                  placeholder="Enter voucher no, student name, admission no..."
                  empty-text="No pending vouchers found"
                  :error="form.errors.voucher_id"
                  @select="selectVoucher"
                  @clear="clearVoucher"
                />
              </div>

              <div v-if="selectedVoucher" class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Voucher</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ selectedVoucher.label }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Fee Details</p>
                    <p class="mt-1 text-gray-700">{{ selectedVoucher.subtitle }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Net / Paid</p>
                    <p class="mt-1 text-gray-700">Rs {{ money(selectedVoucher.net_amount) }} / Rs {{ money(selectedVoucher.paid_amount) }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Remaining</p>
                    <p class="mt-1 font-bold text-indigo-700">Rs {{ money(selectedVoucher.remaining_amount) }}</p>
                  </div>
                </div>
              </div>

              <input v-model="form.student_enrollment_id" type="hidden" />

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Waived Amount <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.waived_amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  :max="selectedVoucher?.remaining_amount || undefined"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  required
                />
                <p v-if="form.errors.waived_amount" class="mt-1 text-xs text-red-600">{{ form.errors.waived_amount }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Approved On</label>
                <input
                  v-model="form.approved_on"
                  type="date"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select
                  v-model="form.status"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  <option value="approved">Approved</option>
                  <option value="reversed">Reversed</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Waiver Reason <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.waiver_reason"
                  rows="3"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="Reason for approving this waiver..."
                  required
                />
                <p v-if="form.errors.waiver_reason" class="mt-1 text-xs text-red-600">{{ form.errors.waiver_reason }}</p>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea
                  v-model="form.notes"
                  rows="2"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="Optional note..."
                />
              </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-waivers.index'))" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
                Cancel
              </Button>
              <Button type="submit" variant="primary" :loading="form.processing" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm">
                <span v-if="!form.processing">Create Waiver</span>
                <span v-else>Creating...</span>
              </Button>
            </div>
          </form>
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
import SearchablePicker from '@/Components/Finance/SearchablePicker.vue'

const props = defineProps({
  vouchers: { type: Array, default: () => [] },
})

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
  voucher_id: '',
  student_enrollment_id: '',
  waived_amount: '',
  waiver_reason: '',
  approved_on: today,
  status: 'approved',
  notes: '',
})

const selectedVoucher = computed(() =>
  props.vouchers.find((voucher) => String(voucher.id) === String(form.voucher_id))
)

const selectVoucher = (voucher) => {
  form.student_enrollment_id = voucher.student_enrollment_id
  form.waived_amount = voucher.remaining_amount || ''
}

const clearVoucher = () => {
  form.student_enrollment_id = ''
  form.waived_amount = ''
}

const money = (value) => Number(value || 0).toLocaleString(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const submit = () => {
  form.post(route('fee-waivers.store'), { preserveScroll: true })
}
</script>
