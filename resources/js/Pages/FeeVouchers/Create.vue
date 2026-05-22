<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Single Student Fee Voucher</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Search a student, select fee type and month, then review the automatic calculation before creating.</p>
            </div>
            <Button @click="$inertia.visit(route('fee-vouchers.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Student Search <span class="text-red-500">*</span></label>
                <StudentRollSearch
                  v-model="selectedStudentId"
                  @student-selected="onStudentSelected"
                  @cleared="onStudentCleared"
                />
              </div>

              <div>
                <label for="student_enrollment_id" class="block text-sm font-medium text-gray-700 mb-2">Enrollment <span class="text-red-500">*</span></label>
                <select
                  id="student_enrollment_id"
                  v-model="form.student_enrollment_id"
                  @change="onEnrollmentChange"
                  :disabled="!selectedStudentId || loadingEnrollments"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                  :class="{ 'border-red-500': form.errors.student_enrollment_id }"
                  required
                >
                  <option value="">{{ loadingEnrollments ? 'Loading enrollments...' : 'Select Enrollment' }}</option>
                  <option v-for="enrollment in enrollmentOptions" :key="enrollment.id" :value="enrollment.id">
                    {{ enrollment.class_name }} - {{ enrollment.section_name }} | {{ enrollment.branch_name }} | {{ enrollment.academic_year }}
                  </option>
                </select>
                <p v-if="form.errors.student_enrollment_id" class="mt-1 text-sm text-red-600">{{ form.errors.student_enrollment_id }}</p>
              </div>

              <div>
                <label for="fee_type_id" class="block text-sm font-medium text-gray-700 mb-2">Fee Type <span class="text-red-500">*</span></label>
                <select
                  id="fee_type_id"
                  v-model="form.fee_type_id"
                  @change="calculateVoucher"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.fee_type_id }"
                  required
                >
                  <option value="">Select Fee Type</option>
                  <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">{{ feeType.fee_name }}</option>
                </select>
                <p v-if="form.errors.fee_type_id" class="mt-1 text-sm text-red-600">{{ form.errors.fee_type_id }}</p>
              </div>

              <div>
                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-2">Academic Year <span class="text-red-500">*</span></label>
                <select
                  id="academic_year_id"
                  v-model="form.academic_year_id"
                  @change="calculateVoucher"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  required
                >
                  <option value="">Select Year</option>
                  <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.year_name }}</option>
                </select>
                <p v-if="form.errors.academic_year_id" class="mt-1 text-sm text-red-600">{{ form.errors.academic_year_id }}</p>
              </div>

              <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-2">Voucher Month <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                  <select
                    id="month"
                    v-model="form.month"
                    @change="calculateVoucher"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    required
                  >
                    <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
                  </select>
                  <input
                    v-model.number="form.year"
                    @input="calculateVoucherDebounced"
                    type="number"
                    min="2000"
                    max="2100"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    required
                  />
                </div>
              </div>
            </div>

            <div class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/40 overflow-hidden">
              <div class="bg-white border-b border-indigo-100 px-4 sm:px-5 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                  <h3 class="text-sm font-bold text-indigo-950 uppercase tracking-wider">Auto Calculation Preview</h3>
                  <p class="text-xs text-gray-600 mt-1">Voucher no, original amount, concession, sibling discount, scholarship, fine, net and remaining amount are calculated by the system.</p>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" :class="calculationStatusClass">
                  {{ calculationStatusText }}
                </span>
              </div>

              <div v-if="calculationLoading" class="px-5 py-8 flex items-center justify-center text-sm text-indigo-700">
                <svg class="animate-spin h-5 w-5 mr-2 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Calculating voucher...
              </div>

              <div v-else-if="calculationError" class="m-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ calculationError }}
              </div>

              <div v-else-if="calculation" class="p-4 sm:p-5 space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
                      <p class="text-xs text-gray-700 font-semibold uppercase tracking-wide">Base Amount</p>
                      <p class="mt-1 text-xl font-bold text-gray-900">Rs {{ formatAmount(calculation.original_amount) }}</p>
                    </div>
                    <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                      <p class="text-xs text-green-700 font-semibold uppercase tracking-wide">Total Discount</p>
                      <p class="mt-1 text-xl font-bold text-green-800">Rs {{ formatAmount(calculation.discount_amount) }}</p>
                    </div>
                    <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                      <p class="text-xs text-red-700 font-semibold uppercase tracking-wide">Fine Amount</p>
                      <p class="mt-1 text-xl font-bold text-red-800">Rs {{ formatAmount(calculation.fine_amount) }}</p>
                    </div>
                    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                      <p class="text-xs text-yellow-700 font-semibold uppercase tracking-wide">Carry Forward</p>
                      <p class="mt-1 text-xl font-bold text-yellow-800">Rs {{ formatAmount(calculation.arrears_amount || 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-indigo-50 border border-indigo-200 p-4">
                      <p class="text-xs text-indigo-700 font-semibold uppercase tracking-wide">Net Amount</p>
                      <p class="mt-1 text-2xl font-black text-indigo-900">Rs {{ formatAmount(calculation.net_amount) }}</p>
                    </div>
                  </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div class="rounded-lg bg-white border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Fee Concession</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">Rs {{ formatAmount(calculation.concession_amount) }}</p>
                  </div>
                  <div class="rounded-lg bg-white border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Sibling Discount</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">Rs {{ formatAmount(calculation.sibling_discount_amount) }}</p>
                  </div>
                  <div class="rounded-lg bg-white border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Scholarship</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">Rs {{ formatAmount(calculation.scholarship_discount_amount) }}</p>
                  </div>
                </div>

                <div class="rounded-lg bg-white border border-gray-200 p-4">
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                    <div>
                      <p class="text-gray-500">Voucher No</p>
                      <p class="font-semibold text-gray-900">Auto on create</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Due Date</p>
                      <p class="font-semibold text-gray-900">{{ calculation.due_date }}</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Paid Amount</p>
                      <p class="font-semibold text-gray-900">Rs 0.00</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Remaining</p>
                      <p class="font-semibold text-gray-900">Rs {{ formatAmount(calculation.net_amount) }}</p>
                    </div>
                  </div>
                </div>

                <div v-if="calculation.breakdown?.length" class="rounded-lg bg-white border border-gray-200 overflow-hidden">
                  <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-600 uppercase tracking-wider">Discount Breakdown</div>
                  <div class="divide-y divide-gray-100">
                    <div v-for="item in calculation.breakdown" :key="`${item.source}-${item.source_id}`" class="px-4 py-3 flex items-center justify-between text-sm">
                      <div>
                        <p class="font-medium text-gray-900">{{ item.label }}</p>
                        <p class="text-xs text-gray-500">{{ item.discount_type }} {{ item.discount_value }}</p>
                      </div>
                      <p class="font-semibold text-green-700">Rs {{ formatAmount(item.amount) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="px-5 py-8 text-center">
                <p class="text-sm font-medium text-gray-700">Select student, enrollment, fee type and month to calculate voucher.</p>
                <p class="text-xs text-gray-500 mt-1">Manual amount entry is removed so voucher calculation stays consistent.</p>
              </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-vouchers.index'))" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing" :disabled="!canSubmit" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm disabled:opacity-50">
                <span v-if="!form.processing">Create Auto Voucher</span>
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
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import StudentRollSearch from '@/Components/Common/StudentRollSearch.vue'

const props = defineProps({
  feeTypes: { type: Array, default: () => [] },
  academicYears: { type: Array, default: () => [] },
})

const today = new Date()
const selectedStudentId = ref('')
const enrollmentOptions = ref([])
const loadingEnrollments = ref(false)
const calculation = ref(null)
const calculationError = ref('')
const calculationLoading = ref(false)
let calculationTimeout = null

const months = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]

const form = useForm({
  auto_calculate: true,
  student_enrollment_id: '',
  fee_type_id: '',
  academic_year_id: props.academicYears?.[0]?.id || '',
  month: today.getMonth() + 1,
  year: today.getFullYear(),
})

const canSubmit = computed(() => {
  return calculation.value && calculation.value.status === 'ready' && !calculationLoading.value
})

const calculationStatusText = computed(() => {
  if (calculationLoading.value) return 'Calculating'
  if (calculation.value?.status === 'ready') return 'Ready to create'
  if (calculation.value?.status === 'existing') return `Already exists: ${calculation.value.existing_voucher_no}`
  if (calculationError.value) return 'Needs attention'
  return 'Waiting for selection'
})

const calculationStatusClass = computed(() => {
  if (calculation.value?.status === 'ready') return 'bg-green-100 text-green-800'
  if (calculation.value?.status === 'existing') return 'bg-amber-100 text-amber-800'
  if (calculationError.value) return 'bg-red-100 text-red-800'
  return 'bg-gray-100 text-gray-700'
})

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const resetCalculation = () => {
  calculation.value = null
  calculationError.value = ''
}

const onStudentSelected = async () => {
  form.student_enrollment_id = ''
  enrollmentOptions.value = []
  resetCalculation()

  if (!selectedStudentId.value) return

  loadingEnrollments.value = true
  try {
    const response = await axios.get(route('fee-vouchers.enrollments-by-student', selectedStudentId.value))
    enrollmentOptions.value = response.data
    if (enrollmentOptions.value.length > 0) {
      form.student_enrollment_id = enrollmentOptions.value[0].id
      form.academic_year_id = enrollmentOptions.value[0].academic_year_id || form.academic_year_id
      calculateVoucher()
    }
  } catch (error) {
    calculationError.value = 'Student enrollments load nahi ho sake.'
  } finally {
    loadingEnrollments.value = false
  }
}

const onStudentCleared = () => {
  selectedStudentId.value = ''
  form.student_enrollment_id = ''
  enrollmentOptions.value = []
  resetCalculation()
}

const onEnrollmentChange = () => {
  const enrollment = enrollmentOptions.value.find((item) => item.id == form.student_enrollment_id)
  if (enrollment?.academic_year_id) {
    form.academic_year_id = enrollment.academic_year_id
  }
  calculateVoucher()
}

const calculateVoucherDebounced = () => {
  clearTimeout(calculationTimeout)
  calculationTimeout = setTimeout(calculateVoucher, 400)
}

const calculateVoucher = async () => {
  resetCalculation()

  if (!form.student_enrollment_id || !form.fee_type_id || !form.academic_year_id || !form.month || !form.year) {
    return
  }

  calculationLoading.value = true
  try {
    const response = await axios.post(route('fee-vouchers.generate-preview'), {
      student_enrollment_id: form.student_enrollment_id,
      fee_type_id: form.fee_type_id,
      academic_year_id: form.academic_year_id,
      month: form.month,
      year: form.year,
    })

    const rows = response.data.rows || []
    calculation.value = rows[0] || null

    if (!calculation.value) {
      calculationError.value = 'Is student ke liye selected fee type ki active fee structure nahi mili.'
    }
  } catch (error) {
    calculationError.value = error.response?.data?.message || 'Voucher calculate nahi ho saka.'
  } finally {
    calculationLoading.value = false
  }
}

const submit = () => {
  if (!canSubmit.value) return
  form.post(route('fee-vouchers.store'), { preserveScroll: true })
}
</script>
