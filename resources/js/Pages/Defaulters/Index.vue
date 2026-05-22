<template>
  <AppLayout>
    <div class="min-h-screen px-4 py-8 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Defaulter Management</h1>
            <p class="mt-2 text-sm text-gray-600">Track overdue vouchers, reminders, blocking, and recovery reports.</p>
          </div>
          <div class="flex gap-2">
            <button @click="bulkReminder" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-lg hover:bg-indigo-700">Send Bulk Reminder</button>
            <a :href="exportUrl" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Export CSV</a>
            <button @click="printList" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Print Notices</button>
          </div>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
          <StatCard label="Defaulter Students" :value="summary.students" tone="gray" />
          <StatCard label="Total Outstanding" :value="money(summary.total_outstanding)" tone="red" />
          <StatCard label="Warning" :value="summary.warning" tone="yellow" />
          <StatCard label="Blocked" :value="summary.blocked" tone="slate" />
          <StatCard label="Chronic 3+ Months" :value="summary.chronic" tone="indigo" />
        </div>

        <div class="mb-6 rounded-xl bg-white p-5 shadow-md">
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <input v-model="filters.search" @input="loadDebounced" class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Search defaulters..." />
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
          <div class="mt-4 grid gap-4 md:grid-cols-5">
            <input v-model="filters.as_of" @change="loadData" type="date" class="rounded-lg border border-gray-300 px-4 py-2 text-sm" />
            <select v-model="filters.month" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Months</option>
              <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
            </select>
            <select v-model="filters.status" @change="loadData" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">
              <option value="">All Statuses</option>
              <option value="warning">Warning</option>
              <option value="defaulter">Defaulter</option>
              <option value="blocked">Blocked</option>
            </select>
            <input v-model="filters.min_amount" @input="loadDebounced" type="number" min="0" class="rounded-lg border border-gray-300 px-4 py-2 text-sm" placeholder="Min amount" />
            <input v-model="filters.max_amount" @input="loadDebounced" type="number" min="0" class="rounded-lg border border-gray-300 px-4 py-2 text-sm" placeholder="Max amount" />
          </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
          <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
            <p class="text-sm text-gray-600">Showing {{ rows.length }} students</p>
            <p class="text-sm text-gray-500">Grace {{ filters.grace_days }} days, block after {{ filters.block_days }} days</p>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-indigo-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Student</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Class / Section</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">Pending Months</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">Outstanding</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Last Payment</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Overdue</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Status</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-if="loading">
                  <td colspan="8" class="px-4 py-10 text-center text-gray-500">Loading defaulters...</td>
                </tr>
                <tr v-else-if="!rows.length">
                  <td colspan="8" class="px-4 py-10 text-center text-gray-500">No defaulters found</td>
                </tr>
                <tr v-for="row in rows" :key="row.student_enrollment_id" class="hover:bg-gray-50">
                  <td class="px-4 py-4 text-sm">
                    <p class="font-semibold text-gray-900">{{ row.student_name }}</p>
                    <p class="text-xs text-gray-500">{{ row.admission_no }} / {{ row.branch }}</p>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-700">{{ row.class_section }}</td>
                  <td class="max-w-xs px-4 py-4 text-sm text-gray-700">{{ row.pending_months }}</td>
                  <td class="px-4 py-4 text-right text-sm font-bold text-red-600">{{ money(row.outstanding) }}</td>
                  <td class="px-4 py-4 text-center text-sm text-gray-700">{{ row.last_payment_date }}</td>
                  <td class="px-4 py-4 text-center text-sm font-semibold text-gray-900">{{ row.days_overdue }} days</td>
                  <td class="px-4 py-4 text-center">
                    <span :class="statusClass(row.status_key)" class="rounded-full px-3 py-1 text-xs font-semibold">{{ row.status }}</span>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex flex-wrap justify-center gap-2">
                      <a :href="row.ledger_url" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">Ledger</a>
                      <button @click="sendReminder(row)" class="rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100">Reminder</button>
                      <button v-if="row.status_key !== 'blocked'" @click="blockStudent(row)" class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">Block</button>
                      <button v-else @click="unblockStudent(row)" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200">Unblock</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
          <ReportBox title="Class-wise Defaulter Summary" :items="reports.class_wise" item-label="class" />
          <ReportBox title="Month-wise Outstanding" :items="reports.month_wise" item-label="month" />
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
  defaultGraceDays: Number,
  defaultBlockDays: Number,
})

const today = new Date().toISOString().slice(0, 10)
const rows = ref([])
const loading = ref(false)
const reports = reactive({ class_wise: [], month_wise: [] })
const summary = reactive({ students: 0, total_outstanding: 0, warning: 0, defaulter: 0, blocked: 0, chronic: 0 })
let timer = null

const filters = reactive({
  search: '',
  academic_year_id: '',
  branch_id: '',
  class_id: '',
  fee_type_id: '',
  month: '',
  status: '',
  min_amount: '',
  max_amount: '',
  as_of: today,
  grace_days: props.defaultGraceDays || 5,
  block_days: props.defaultBlockDays || 30,
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

const exportUrl = computed(() => `${route('defaulters.export')}?${params.value}`)

const loadData = async () => {
  loading.value = true
  try {
    const { data } = await window.axios.get(`${route('defaulters.data')}?${params.value}`)
    rows.value = data.rows || []
    Object.assign(summary, data.summary || {})
    Object.assign(reports, data.reports || { class_wise: [], month_wise: [] })
  } finally {
    loading.value = false
  }
}

const loadDebounced = () => {
  clearTimeout(timer)
  timer = setTimeout(loadData, 300)
}

const resetFilters = () => {
  Object.assign(filters, { search: '', academic_year_id: '', branch_id: '', class_id: '', fee_type_id: '', month: '', status: '', min_amount: '', max_amount: '', as_of: today })
  loadData()
}

const statusClass = status => ({
  warning: 'bg-yellow-100 text-yellow-800',
  defaulter: 'bg-red-100 text-red-800',
  blocked: 'bg-gray-900 text-white',
}[status] || 'bg-gray-100 text-gray-700')

const sendReminder = async row => {
  await window.axios.post(route('defaulters.reminders'), { student_enrollment_id: row.student_enrollment_id, ...filters })
  loadData()
}

const bulkReminder = async () => {
  await window.axios.post(route('defaulters.reminders'), filters)
  loadData()
}

const blockStudent = async row => {
  await window.axios.post(route('defaulters.block'), { student_enrollment_id: row.student_enrollment_id })
  loadData()
}

const unblockStudent = async row => {
  await window.axios.post(route('defaulters.unblock'), { student_enrollment_id: row.student_enrollment_id })
  loadData()
}

const printList = () => window.print()

onMounted(loadData)
</script>

<script>
const statTones = {
  gray: 'border-gray-200 bg-white text-gray-900',
  red: 'border-red-200 bg-red-50 text-red-700',
  yellow: 'border-yellow-200 bg-yellow-50 text-yellow-700',
  slate: 'border-gray-300 bg-gray-900 text-white',
  indigo: 'border-indigo-200 bg-indigo-50 text-indigo-700',
}

export default {
  components: {
    StatCard: {
      props: ['label', 'value', 'tone'],
      computed: {
        classes() {
          return statTones[this.tone] || statTones.gray
        },
      },
      template: `<div :class="['rounded-xl border p-4 shadow-sm', classes]"><p class="text-xs font-semibold uppercase opacity-75">{{ label }}</p><p class="mt-2 text-2xl font-bold">{{ value }}</p></div>`,
    },
    ReportBox: {
      props: ['title', 'items', 'itemLabel'],
      methods: {
        money(value) {
          return `Rs ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        },
      },
      template: `<div class="rounded-xl bg-white p-5 shadow-md"><h2 class="text-base font-bold text-gray-900">{{ title }}</h2><div class="mt-4 space-y-3"><div v-if="!items.length" class="text-sm text-gray-500">No report data found</div><div v-for="item in items" :key="item[itemLabel]" class="flex items-center justify-between rounded-lg border border-gray-200 p-3 text-sm"><div><p class="font-semibold text-gray-900">{{ item[itemLabel] }}</p><p v-if="item.students !== undefined" class="text-xs text-gray-500">{{ item.students }} students</p></div><p class="font-bold text-red-600">{{ money(item.outstanding) }}</p></div></div></div>`,
    },
  },
}
</script>
