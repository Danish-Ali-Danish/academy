<template>
  <AppLayout>
    <div class="min-h-screen px-4 py-8 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Student Ledger</h1>
            <p class="mt-2 text-sm text-gray-600">Complete bank-statement style financial history for every student.</p>
          </div>
          <div class="flex gap-2">
            <button @click="printStatement" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Print</button>
            <button @click="shareStatement" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700">WhatsApp Share</button>
          </div>
        </div>

        <div v-if="student" class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm">
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <p class="text-xs font-semibold uppercase text-indigo-600">Student</p>
              <p class="mt-1 font-semibold text-gray-900">{{ student.name }}</p>
              <p class="text-sm text-gray-600">{{ student.admission_no }} / {{ student.roll_no }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase text-indigo-600">Class</p>
              <p class="mt-1 font-semibold text-gray-900">{{ student.class }} - {{ student.section }}</p>
              <p class="text-sm text-gray-600">{{ student.branch }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase text-indigo-600">Guardian</p>
              <p class="mt-1 font-semibold text-gray-900">{{ student.father_name }}</p>
              <p class="text-sm text-gray-600">{{ student.contact }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase text-indigo-600">Academic Year</p>
              <p class="mt-1 font-semibold text-gray-900">{{ student.academic_year }}</p>
            </div>
          </div>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <SummaryCard label="Opening Balance" :value="summary.opening_balance" tone="gray" />
          <SummaryCard label="Total Due" :value="summary.total_due" tone="red" />
          <SummaryCard label="Total Credits" :value="summary.total_paid" tone="green" />
          <SummaryCard label="Outstanding" :value="summary.current_outstanding" tone="indigo" />
        </div>

        <div class="mb-6 rounded-xl bg-white p-5 shadow-md">
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input v-model="filters.search" @input="loadDebounced" class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Search student, voucher, entry..." />
            <select v-model="filters.academic_year_id" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Years</option>
              <option v-for="item in academicYears" :key="item.id" :value="item.id">{{ item.year_name }}</option>
            </select>
            <select v-model="filters.branch_id" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Branches</option>
              <option v-for="item in branches" :key="item.id" :value="item.id">{{ item.branch_name }}</option>
            </select>
            <select v-model="filters.class_id" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Classes</option>
              <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.class_name }}</option>
            </select>
            <select v-model="filters.fee_type_id" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Fee Types</option>
              <option v-for="item in feeTypes" :key="item.id" :value="item.id">{{ item.fee_name }}</option>
            </select>
            <button @click="resetFilters" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-50">Reset Filters</button>
          </div>
          <div class="mt-4 grid gap-4 md:grid-cols-4">
            <input v-model="filters.date_from" @change="loadData" type="date" class="rounded-lg border border-gray-300 px-4 py-2 text-sm" />
            <input v-model="filters.date_to" @change="loadData" type="date" class="rounded-lg border border-gray-300 px-4 py-2 text-sm" />
            <select v-model="filters.month" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Months</option>
              <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
            </select>
            <select v-model="filters.status" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Vouchers</option>
              <option value="pending">Pending Only</option>
              <option value="paid">Paid Only</option>
            </select>
          </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
          <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
            <p class="text-sm text-gray-600">Showing {{ rows.length }} ledger entries</p>
            <button :disabled="!filters.student_enrollment_id" @click="manualOpen = true" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">Add Manual Entry</button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-indigo-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Student</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Description</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">Debit</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">Credit</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">Balance</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Reference</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-if="loading">
                  <td colspan="7" class="px-4 py-10 text-center text-gray-500">Loading ledger...</td>
                </tr>
                <tr v-else-if="!rows.length">
                  <td colspan="7" class="px-4 py-10 text-center text-gray-500">No ledger entries found</td>
                </tr>
                <tr v-for="row in rows" :key="`${row.reference_type}-${row.reference_id}-${row.date}-${row.description}`" class="hover:bg-gray-50">
                  <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ row.date }}</td>
                  <td class="px-4 py-4 text-sm">
                    <p class="font-medium text-gray-900">{{ row.student_name }}</p>
                    <p class="text-xs text-gray-500">{{ row.student_code }}</p>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-700">{{ row.description }}</td>
                  <td class="px-4 py-4 text-right text-sm font-semibold text-red-600">{{ row.debit ? money(row.debit) : '-' }}</td>
                  <td class="px-4 py-4 text-right text-sm font-semibold text-green-700">{{ row.credit ? money(row.credit) : '-' }}</td>
                  <td class="px-4 py-4 text-right text-sm font-bold text-gray-900">{{ money(row.balance) }}</td>
                  <td class="px-4 py-4 text-center text-sm">
                    <a v-if="row.url" :href="row.url" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">{{ row.reference_label }}</a>
                    <span v-else class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">{{ row.reference_label }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div v-if="manualOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
          <h2 class="text-lg font-bold text-gray-900">Add Manual Ledger Entry</h2>
          <div class="mt-4 space-y-4">
            <select v-model="manual.transaction_type" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="debit">Debit - charge student</option>
              <option value="credit">Credit - relief/payment</option>
            </select>
            <input v-model.number="manual.amount" type="number" min="1" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm" placeholder="Amount" />
            <input v-model="manual.description" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm" placeholder="Reason / correction note" />
          </div>
          <div class="mt-6 flex justify-end gap-2">
            <button @click="manualOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
            <button @click="saveManualEntry" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Save Entry</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { computed, onMounted, reactive, ref } from 'vue'

const props = defineProps({
  academicYears: Array,
  branches: Array,
  classes: Array,
  feeTypes: Array,
  initialEnrollmentId: [Number, String, null],
})

const loading = ref(false)
const rows = ref([])
const student = ref(null)
const summary = reactive({ opening_balance: 0, total_due: 0, total_paid: 0, current_outstanding: 0, total_entries: 0 })
const manualOpen = ref(false)
const manual = reactive({ transaction_type: 'debit', amount: null, description: '' })
let timer = null

const filters = reactive({
  student_enrollment_id: props.initialEnrollmentId || '',
  search: '',
  academic_year_id: '',
  branch_id: '',
  class_id: '',
  fee_type_id: '',
  date_from: '',
  date_to: '',
  month: '',
  status: '',
})

const months = [
  ['1', 'January'], ['2', 'February'], ['3', 'March'], ['4', 'April'], ['5', 'May'], ['6', 'June'],
  ['7', 'July'], ['8', 'August'], ['9', 'September'], ['10', 'October'], ['11', 'November'], ['12', 'December'],
].map(([value, label]) => ({ value, label }))

const money = value => `Rs ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const params = computed(() => {
  const query = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) query.append(key, value)
  })
  return query.toString()
})

const loadData = async () => {
  loading.value = true
  try {
    const { data } = await window.axios.get(`${route('student-ledgers.data')}?${params.value}`)
    rows.value = data.rows || []
    student.value = data.student || null
    Object.assign(summary, data.summary || {})
  } finally {
    loading.value = false
  }
}

const loadDebounced = () => {
  clearTimeout(timer)
  timer = setTimeout(loadData, 300)
}

const resetFilters = () => {
  Object.assign(filters, { student_enrollment_id: '', search: '', academic_year_id: '', branch_id: '', class_id: '', fee_type_id: '', date_from: '', date_to: '', month: '', status: '' })
  loadData()
}

const printStatement = () => window.print()

const shareStatement = () => {
  const text = student.value
    ? `${student.value.name} ledger outstanding: ${money(summary.current_outstanding)}`
    : `Student ledger outstanding: ${money(summary.current_outstanding)}`
  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
}

const saveManualEntry = async () => {
  await window.axios.post(route('student-ledgers.manual-entry'), {
    student_enrollment_id: filters.student_enrollment_id,
    ...manual,
  })
  manualOpen.value = false
  Object.assign(manual, { transaction_type: 'debit', amount: null, description: '' })
  loadData()
}

onMounted(loadData)
</script>

<script>
const tones = {
  gray: 'border-gray-200 bg-white text-gray-900',
  red: 'border-red-200 bg-red-50 text-red-700',
  green: 'border-green-200 bg-green-50 text-green-700',
  indigo: 'border-indigo-200 bg-indigo-50 text-indigo-700',
}

export default {
  components: {
    SummaryCard: {
      props: ['label', 'value', 'tone'],
      computed: {
        classes() {
          return tones[this.tone] || tones.gray
        },
        formatted() {
          return `Rs ${Number(this.value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        },
      },
      template: `<div :class="['rounded-xl border p-4 shadow-sm', classes]"><p class="text-xs font-semibold uppercase opacity-75">{{ label }}</p><p class="mt-2 text-xl font-bold">{{ formatted }}</p></div>`,
    },
  },
}
</script>
