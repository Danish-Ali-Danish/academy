<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Apply Voucher Fine</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Select a pending voucher and let the system calculate the fine.</p>
            </div>
            <Button @click="$inertia.visit(route('fee-voucher-fines.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Voucher <span class="text-red-500">*</span></label>
                <div class="relative">
                  <div class="relative">
                    <input
                      ref="voucherInputRef"
                      v-model="voucherSearch"
                      type="text"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-10 pr-10"
                      :class="{ 'border-red-500': form.errors.voucher_id }"
                      placeholder="Search voucher no, student, roll no..."
                      autocomplete="off"
                      required
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
                    class="absolute z-30 mt-1 w-full bg-white shadow-xl max-h-72 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm"
                  >
                    <div v-if="!voucherSearch.trim()" class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                      Pending Vouchers
                    </div>
                    <button
                      v-for="(voucher, index) in filteredVouchers"
                      :key="voucher.id"
                      type="button"
                      class="w-full text-left px-3 py-2.5 border-b border-gray-100 last:border-0 transition-colors"
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
                            <span class="font-medium text-gray-900 truncate">{{ voucher.student_name }}</span>
                          </div>
                          <p class="mt-1 text-xs text-gray-500">
                            {{ voucher.roll_no || 'No roll no' }} · {{ voucher.fee_type }} · Due {{ formatDate(voucher.due_date) }}
                          </p>
                        </div>
                        <div class="text-xs sm:text-right">
                          <p class="font-semibold text-gray-900">Rs {{ formatAmount(voucher.remaining_amount) }}</p>
                          <p class="text-gray-500">{{ voucher.days_overdue }} days overdue</p>
                        </div>
                      </div>
                    </button>
                    <div v-if="filteredVouchers.length === 0" class="px-4 py-5 text-center text-sm text-gray-500">
                      No pending voucher found.
                    </div>
                  </div>
                </div>
                <p v-if="form.errors.voucher_id" class="mt-1 text-sm text-red-600">{{ form.errors.voucher_id }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fine Rule</label>
                <div class="relative">
                  <div class="relative">
                    <input
                      ref="ruleInputRef"
                      v-model="ruleSearch"
                      type="text"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-10 pr-10"
                      placeholder="Search fine rule..."
                      autocomplete="off"
                      @focus="focusRuleSearch"
                      @input="onRuleSearch"
                      @keydown.down.prevent="moveRuleHighlight(1)"
                      @keydown.up.prevent="moveRuleHighlight(-1)"
                      @keydown.enter.prevent="selectHighlightedRule"
                      @keydown.escape="showRuleDropdown = false"
                      @blur="hideRuleDropdown"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                    </div>
                    <button
                      v-if="form.fine_rule_id"
                      type="button"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors"
                      title="Clear rule"
                      @mousedown.prevent="selectRule(null)"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>

                  <div
                    v-if="showRuleDropdown"
                    class="absolute z-30 mt-1 w-full bg-white shadow-xl max-h-72 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm"
                  >
                    <button
                      type="button"
                      class="w-full text-left px-3 py-2.5 border-b border-gray-100 transition-colors"
                      :class="highlightedRuleIndex === 0 ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : 'hover:bg-gray-50'"
                      @mousedown.prevent="selectRule(null)"
                      @mouseenter="highlightedRuleIndex = 0"
                    >
                      <div class="font-medium text-gray-900">Manual Fine</div>
                      <p class="mt-1 text-xs text-gray-500">Enter fine type and value yourself</p>
                    </button>

                    <button
                      v-for="(rule, index) in filteredRules"
                      :key="rule.id"
                      type="button"
                      class="w-full text-left px-3 py-2.5 border-b border-gray-100 last:border-0 transition-colors"
                      :class="index + 1 === highlightedRuleIndex ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : 'hover:bg-gray-50'"
                      @mousedown.prevent="selectRule(rule)"
                      @mouseenter="highlightedRuleIndex = index + 1"
                    >
                      <div class="font-medium text-gray-900">{{ rule.description || ruleLabel(rule) }}</div>
                      <p class="mt-1 text-xs text-gray-500">
                        {{ fineTypeLabel(rule.fine_type) }} · {{ rule.fine_type?.includes('percentage') ? rule.fine_value + '%' : 'Rs ' + formatAmount(rule.fine_value) }} · after {{ rule.days_after_due }} days
                      </p>
                    </button>

                    <div v-if="filteredRules.length === 0 && ruleSearch.trim()" class="px-4 py-5 text-center text-sm text-gray-500">
                      No fine rule found.
                    </div>
                  </div>
                </div>
                <p v-if="form.errors.fine_rule_id" class="mt-1 text-sm text-red-600">{{ form.errors.fine_rule_id }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Days Overdue <span class="text-red-500">*</span></label>
                <input v-model.number="form.days_overdue" @input="calculateFine" type="number" min="0" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fine Type <span class="text-red-500">*</span></label>
                <select v-model="form.fine_type" @change="calculateFine" :disabled="!!form.fine_rule_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100">
                  <option value="fixed">Fixed</option>
                  <option value="percentage">Percentage</option>
                  <option value="daily_fixed">Daily Fixed</option>
                  <option value="daily_percentage">Daily Percentage</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fine Value <span class="text-red-500">*</span></label>
                <input v-model.number="form.fine_value" @input="calculateFine" :disabled="!!form.fine_rule_id" type="number" step="0.01" min="0" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Applied On</label>
                <input v-model="form.applied_on" type="date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
              </div>
            </div>

            <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 overflow-hidden">
              <div class="px-4 py-3 bg-white border-b border-indigo-100">
                <h3 class="text-sm font-bold text-indigo-950 uppercase tracking-wider">Fine Preview</h3>
              </div>
              <div v-if="selectedVoucher" class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-4">
                <div class="rounded-lg bg-white border border-gray-200 p-4">
                  <p class="text-xs text-gray-500">Remaining</p>
                  <p class="text-xl font-bold text-gray-900">Rs {{ formatAmount(selectedVoucher.remaining_amount) }}</p>
                </div>
                <div class="rounded-lg bg-white border border-gray-200 p-4">
                  <p class="text-xs text-gray-500">Existing Fine</p>
                  <p class="text-xl font-bold text-gray-900">Rs {{ formatAmount(selectedVoucher.fine_amount) }}</p>
                </div>
                <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                  <p class="text-xs text-red-700">New Fine</p>
                  <p class="text-xl font-bold text-red-800">Rs {{ formatAmount(calculatedAmount) }}</p>
                </div>
                <div class="rounded-lg bg-indigo-50 border border-indigo-200 p-4">
                  <p class="text-xs text-indigo-700">New Remaining</p>
                  <p class="text-xl font-bold text-indigo-800">Rs {{ formatAmount(Number(selectedVoucher.remaining_amount || 0) + calculatedAmount) }}</p>
                </div>
              </div>
              <div v-else class="px-5 py-8 text-center text-sm text-gray-600">Select a voucher to preview fine calculation.</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea v-model="form.notes" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Optional note..."></textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
              <input v-model="form.is_waived" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
              Mark as waived only, do not add amount to voucher
            </label>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-voucher-fines.index'))" class="w-full sm:w-auto">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing" :disabled="!canSubmit" class="w-full sm:w-auto disabled:opacity-50">
                Apply Fine
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

const props = defineProps({
  vouchers: { type: Array, default: () => [] },
  fineRules: { type: Array, default: () => [] },
})

const today = new Date().toISOString().slice(0, 10)
const calculatedAmount = ref(0)
const voucherSearch = ref('')
const showVoucherDropdown = ref(false)
const highlightedVoucherIndex = ref(-1)
const voucherInputRef = ref(null)
const ruleSearch = ref('Manual Fine')
const showRuleDropdown = ref(false)
const highlightedRuleIndex = ref(0)
const ruleInputRef = ref(null)

const form = useForm({
  voucher_id: '',
  fine_rule_id: '',
  days_overdue: 0,
  fine_type: 'fixed',
  fine_value: 0,
  calculated_amount: 0,
  applied_on: today,
  is_waived: false,
  notes: '',
})

const selectedVoucher = computed(() => props.vouchers.find((voucher) => voucher.id == form.voucher_id))
const selectedRule = computed(() => props.fineRules.find((rule) => rule.id == form.fine_rule_id))
const canSubmit = computed(() => form.voucher_id && Number(form.fine_value) >= 0 && calculatedAmount.value >= 0)
const filteredVouchers = computed(() => {
  const query = voucherSearch.value.trim().toLowerCase()

  if (!query) return props.vouchers.slice(0, 20)

  return props.vouchers.filter((voucher) => [
    voucher.voucher_no,
    voucher.student_name,
    voucher.roll_no,
    voucher.fee_type,
    voucher.status,
    voucher.remaining_amount,
  ].some((value) => String(value || '').toLowerCase().includes(query))).slice(0, 30)
})
const filteredRules = computed(() => {
  const query = ruleSearch.value.trim().toLowerCase()

  if (!query || query === 'manual fine') return props.fineRules.slice(0, 20)

  return props.fineRules.filter((rule) => [
    rule.description,
    rule.fine_type,
    fineTypeLabel(rule.fine_type),
    rule.fine_value,
    rule.days_after_due,
    rule.max_fine,
    ruleLabel(rule),
  ].some((value) => String(value || '').toLowerCase().includes(query))).slice(0, 30)
})

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (value) => value ? new Date(value).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const voucherLabel = (voucher) => voucher ? `${voucher.voucher_no || 'No voucher no'} - ${voucher.student_name} - Rs ${formatAmount(voucher.remaining_amount)}` : ''
const ruleLabel = (rule) => `${fineTypeLabel(rule.fine_type)} ${rule.fine_type?.includes('percentage') ? rule.fine_value + '%' : 'Rs ' + formatAmount(rule.fine_value)} after ${rule.days_after_due} days`
const fineTypeLabel = (type) => String(type || '').replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase())

const selectVoucher = (voucher) => {
  form.voucher_id = voucher.id
  voucherSearch.value = voucherLabel(voucher)
  showVoucherDropdown.value = false
  highlightedVoucherIndex.value = -1
  onVoucherChange()
}

const clearVoucher = () => {
  form.voucher_id = ''
  voucherSearch.value = ''
  highlightedVoucherIndex.value = -1
  calculatedAmount.value = 0
  form.calculated_amount = 0
  form.days_overdue = 0
  nextTick(() => voucherInputRef.value?.focus())
}

const onVoucherSearch = () => {
  if (form.voucher_id && voucherSearch.value !== voucherLabel(selectedVoucher.value)) {
    form.voucher_id = ''
    calculatedAmount.value = 0
    form.calculated_amount = 0
  }

  showVoucherDropdown.value = true
  highlightedVoucherIndex.value = filteredVouchers.value.length ? 0 : -1
}

const hideVoucherDropdown = () => {
  setTimeout(() => {
    showVoucherDropdown.value = false
  }, 180)
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

const onVoucherChange = () => {
  form.days_overdue = selectedVoucher.value?.days_overdue || 0
  calculateFine()
}

const onRuleChange = () => {
  if (selectedRule.value) {
    form.fine_type = selectedRule.value.fine_type
    form.fine_value = Number(selectedRule.value.fine_value || 0)
    form.days_overdue = Math.max(Number(form.days_overdue || 0), Number(selectedRule.value.days_after_due || 0))
  }
  calculateFine()
}

const selectRule = (rule) => {
  form.fine_rule_id = rule?.id || ''
  ruleSearch.value = rule ? (rule.description || ruleLabel(rule)) : 'Manual Fine'
  showRuleDropdown.value = false
  highlightedRuleIndex.value = 0
  onRuleChange()
}

const focusRuleSearch = () => {
  if (!form.fine_rule_id && ruleSearch.value === 'Manual Fine') {
    ruleSearch.value = ''
  }
  showRuleDropdown.value = true
}

const onRuleSearch = () => {
  if (form.fine_rule_id && selectedRule.value && ruleSearch.value !== (selectedRule.value.description || ruleLabel(selectedRule.value))) {
    form.fine_rule_id = ''
  }

  showRuleDropdown.value = true
  highlightedRuleIndex.value = 0
}

const hideRuleDropdown = () => {
  setTimeout(() => {
    showRuleDropdown.value = false
    if (!form.fine_rule_id && !ruleSearch.value.trim()) {
      ruleSearch.value = 'Manual Fine'
    }
  }, 180)
}

const moveRuleHighlight = (step) => {
  showRuleDropdown.value = true
  const count = filteredRules.value.length + 1
  if (!count) return

  const nextIndex = highlightedRuleIndex.value + step
  if (nextIndex < 0) highlightedRuleIndex.value = count - 1
  else if (nextIndex >= count) highlightedRuleIndex.value = 0
  else highlightedRuleIndex.value = nextIndex
}

const selectHighlightedRule = () => {
  if (highlightedRuleIndex.value === 0) {
    selectRule(null)
    return
  }

  const rule = filteredRules.value[highlightedRuleIndex.value - 1]
  if (rule) selectRule(rule)
}

const calculateFine = () => {
  const base = Number(selectedVoucher.value?.remaining_amount || 0)
  const value = Number(form.fine_value || 0)
  const days = Math.max(1, Number(form.days_overdue || 0))
  let amount = 0

  if (form.fine_type === 'percentage') amount = (base * value) / 100
  else if (form.fine_type === 'daily_fixed') amount = value * days
  else if (form.fine_type === 'daily_percentage') amount = ((base * value) / 100) * days
  else amount = value

  if (selectedRule.value?.max_fine) amount = Math.min(amount, Number(selectedRule.value.max_fine))

  calculatedAmount.value = Math.max(0, Math.round(amount * 100) / 100)
  form.calculated_amount = calculatedAmount.value
}

const submit = () => {
  calculateFine()
  form.post(route('fee-voucher-fines.store'), { preserveScroll: true })
}
</script>
