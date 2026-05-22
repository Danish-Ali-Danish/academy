<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Advance Adjustment</h1>
            <p class="mt-2 text-sm text-gray-600">Changing this will refresh the linked payment and voucher balance.</p>
          </div>
          <Button @click="$inertia.visit(route('fee-advance-adjustments.index'))" variant="secondary" class="w-full sm:w-auto">Back to List</Button>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-xl shadow-md p-4 sm:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Advance Payment <span class="text-red-500">*</span></label>
              <SearchablePicker v-model="form.from_payment_id" :items="advancePayments" title="Available Advance Payments" placeholder="Search receipt, student, admission..." :error="form.errors.from_payment_id" @select="selectAdvance" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Target Voucher <span class="text-red-500">*</span></label>
              <SearchablePicker v-model="form.to_voucher_id" :items="filteredVouchers" title="Pending Vouchers" placeholder="Search voucher, student, fee type..." :error="form.errors.to_voucher_id" @select="autoAmount" />
            </div>

            <div class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Available Advance</p>
                  <p class="mt-1 text-lg font-bold text-indigo-700">Rs {{ money(selectedAdvance?.available_amount) }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Voucher Remaining</p>
                  <p class="mt-1 text-lg font-bold text-indigo-700">Rs {{ money(selectedVoucher?.remaining_amount) }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Current Adjustment</p>
                  <p class="mt-1 font-semibold text-gray-900">Rs {{ money(adjustment.adjusted_amount) }}</p>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Adjusted Amount <span class="text-red-500">*</span></label>
              <input v-model="form.adjusted_amount" type="number" step="0.01" min="0.01" :max="maxAdjustable || undefined" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
              <p v-if="form.errors.adjusted_amount" class="mt-1 text-xs text-red-600">{{ form.errors.adjusted_amount }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Adjusted At</label>
              <input v-model="form.adjusted_at" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea v-model="form.notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3">
            <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-advance-adjustments.index'))" class="w-full sm:w-auto">Cancel</Button>
            <Button type="submit" variant="primary" :loading="form.processing" class="w-full sm:w-auto">
              <span v-if="!form.processing">Update Adjustment</span>
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
  adjustment: { type: Object, required: true },
  advancePayments: { type: Array, default: () => [] },
  vouchers: { type: Array, default: () => [] },
})

const form = useForm({
  from_payment_id: props.adjustment.from_payment_id,
  to_voucher_id: props.adjustment.to_voucher_id,
  adjusted_amount: props.adjustment.adjusted_amount,
  adjusted_at: props.adjustment.adjusted_at ?? '',
  notes: props.adjustment.notes ?? '',
})

const selectedAdvance = computed(() => props.advancePayments.find((item) => String(item.id) === String(form.from_payment_id)))
const selectedVoucher = computed(() => props.vouchers.find((item) => String(item.id) === String(form.to_voucher_id)))
const filteredVouchers = computed(() => {
  if (!selectedAdvance.value) return props.vouchers
  return props.vouchers.filter((voucher) => String(voucher.student_enrollment_id) === String(selectedAdvance.value.student_enrollment_id))
})
const maxAdjustable = computed(() => {
  const voucherRoom = Number(selectedVoucher.value?.remaining_amount || 0) + (String(selectedVoucher.value?.id) === String(props.adjustment.to_voucher_id) ? Number(props.adjustment.adjusted_amount || 0) : 0)
  return Math.min(Number(selectedAdvance.value?.available_amount || 0), voucherRoom)
})

const selectAdvance = () => {
  if (selectedVoucher.value && String(selectedVoucher.value.student_enrollment_id) !== String(selectedAdvance.value?.student_enrollment_id)) {
    form.to_voucher_id = ''
    form.adjusted_amount = ''
  }
  autoAmount()
}
const autoAmount = () => {
  if (maxAdjustable.value > 0) form.adjusted_amount = maxAdjustable.value
}
const money = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const submit = () => form.put(route('fee-advance-adjustments.update', props.adjustment.id), { preserveScroll: true })
</script>
