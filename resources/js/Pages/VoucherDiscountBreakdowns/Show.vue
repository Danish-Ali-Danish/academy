<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Discount Breakdown Detail</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Complete audit of one automatic discount applied on a fee voucher.</p>
            </div>
            <Button @click="$inertia.visit(backUrl)" variant="secondary" class="w-full sm:w-auto shadow-sm text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to Breakdowns
            </Button>
          </div>
        </div>

        <div class="max-w-5xl mx-auto space-y-5">
          <div class="rounded-xl border border-indigo-100 bg-white shadow-lg overflow-hidden">
            <div class="px-5 py-4 bg-indigo-50 border-b border-indigo-100">
              <p class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Voucher</p>
              <div class="mt-1 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                <div>
                  <h2 class="text-xl font-bold text-gray-900">{{ voucher.voucher_no || '-' }}</h2>
                  <p class="text-sm text-gray-600">{{ student.student_name || '-' }} - {{ voucher.fee_type?.fee_name || '-' }}</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700 border border-indigo-200">
                  {{ voucher.month }}/{{ voucher.year }}
                </span>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 p-4" :class="hasWaivers ? 'lg:grid-cols-5' : 'lg:grid-cols-4'">
              <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
                <p class="text-xs text-gray-500">Original Amount</p>
                <p class="text-xl font-bold text-gray-900">Rs {{ formatAmount(voucher.original_amount) }}</p>
              </div>
              <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                <p class="text-xs text-green-700">This Discount</p>
                <p class="text-xl font-bold text-green-800">Rs {{ formatAmount(breakdown.calculated_amount) }}</p>
              </div>
              <div v-if="hasWaivers" class="rounded-lg bg-sky-50 border border-sky-200 p-4">
                <p class="text-xs text-sky-700">Fee Waiver</p>
                <p class="text-xl font-bold text-sky-800">Rs {{ formatAmount(waiverAmount) }}</p>
              </div>
              <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                <p class="text-xs text-red-700">Fine</p>
                <p class="text-xl font-bold text-red-800">Rs {{ formatAmount(voucher.fine_amount) }}</p>
              </div>
              <div class="rounded-lg bg-indigo-50 border border-indigo-200 p-4">
                <p class="text-xs text-indigo-700">Net Payable</p>
                <p class="text-xl font-bold text-indigo-800">Rs {{ formatAmount(voucher.net_amount) }}</p>
              </div>
            </div>
          </div>

          <div v-if="hasWaivers" class="rounded-xl bg-white shadow-md border border-sky-100 p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Fee Waiver Details</h3>
                <p class="mt-1 text-xs text-gray-500">Waiver records attached with this voucher.</p>
              </div>
              <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                {{ waivers.length }} {{ waivers.length === 1 ? 'record' : 'records' }}
              </span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
              <table class="min-w-full divide-y divide-gray-200 text-sm">
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
                  <tr v-for="waiver in waivers" :key="waiver.id">
                    <td class="px-4 py-3 font-semibold text-sky-700">Rs {{ formatAmount(waiver.waived_amount) }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ waiver.waiver_reason || '-' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ waiver.approved_by || '-' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ waiver.approved_on || '-' }}</td>
                    <td class="px-4 py-3">
                      <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="waiverStatusClass(waiver.status)">
                        {{ sourceName(waiver.status) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="rounded-xl bg-white shadow-md border border-gray-100 p-5">
              <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Discount Source</h3>
              <dl class="space-y-4">
                <div>
                  <dt class="text-xs text-gray-500">Source</dt>
                  <dd class="mt-1">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="sourceClass">
                      {{ breakdown.source_label || sourceName(breakdown.discount_source) }}
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Discount Type</dt>
                  <dd class="mt-1 font-semibold text-gray-900">{{ sourceName(breakdown.discount_type) }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Discount Value</dt>
                  <dd class="mt-1 font-semibold text-gray-900">{{ discountValue }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Calculated Amount</dt>
                  <dd class="mt-1 text-2xl font-bold text-green-700">Rs {{ formatAmount(breakdown.calculated_amount) }}</dd>
                </div>
              </dl>
            </div>

            <div class="rounded-xl bg-white shadow-md border border-gray-100 p-5">
              <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Student & Audit</h3>
              <dl class="space-y-4">
                <div>
                  <dt class="text-xs text-gray-500">Student</dt>
                  <dd class="mt-1 font-semibold text-gray-900">{{ student.student_name || '-' }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Roll / Admission</dt>
                  <dd class="mt-1 font-semibold text-gray-900">{{ student.roll_no || '-' }} - {{ student.admission_no || '-' }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Applied By</dt>
                  <dd class="mt-1 font-semibold text-gray-900">{{ breakdown.applied_by?.name || 'System' }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Applied At</dt>
                  <dd class="mt-1 font-semibold text-gray-900">{{ formatDate(breakdown.created_at) }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

const props = defineProps({
  breakdown: { type: Object, required: true },
  waivers: { type: Array, default: () => [] },
  waiverAmount: { type: [Number, String], default: 0 },
})

const breakdown = props.breakdown
const voucher = breakdown.voucher || {}
const student = voucher.student_enrollment?.student || {}
const waivers = props.waivers || []
const waiverAmount = Number(props.waiverAmount || 0)
const backUrl = route('voucher-discount-breakdowns.index', voucher.id ? { voucher_id: voucher.id } : {})

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const sourceName = (value) => value ? value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : '-'
const formatDate = (value) => value ? new Date(value).toLocaleString() : '-'
const hasWaivers = computed(() => waivers.length > 0)

const discountValue = computed(() => {
  if (breakdown.discount_type === 'percentage') return `${breakdown.discount_value || 0}%`
  return `Rs ${formatAmount(breakdown.discount_value)}`
})

const sourceClass = computed(() => {
  if (breakdown.discount_source === 'student_fee_concession') return 'bg-green-100 text-green-800'
  if (breakdown.discount_source === 'sibling_discount_rule') return 'bg-blue-100 text-blue-800'
  if (breakdown.discount_source === 'student_scholarship') return 'bg-purple-100 text-purple-800'
  return 'bg-gray-100 text-gray-800'
})

const waiverStatusClass = (status) => {
  if (status === 'approved') return 'bg-green-100 text-green-800'
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800'
  if (status === 'rejected' || status === 'reversed') return 'bg-red-100 text-red-800'
  return 'bg-gray-100 text-gray-800'
}
</script>
