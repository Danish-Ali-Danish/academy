<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create Installment Assignment</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Assign an installment plan to an enrolled student</p>
            </div>
            <Button @click="$inertia.visit(route('student-installment-assignments.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <!-- Create Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">

              <!-- Student Search -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Student Search <span class="text-red-500">*</span></label>
                <StudentRollSearch 
                  v-model="selectedStudentId" 
                  @student-selected="onStudentSelected"
                  @cleared="onStudentCleared"
                />
              </div>

              <!-- Enrollment -->
              <div>
                <label for="student_enrollment_id" class="block text-sm font-medium text-gray-700 mb-2">Enrollment <span class="text-red-500">*</span></label>
                <select id="student_enrollment_id" v-model="form.student_enrollment_id" @change="onEnrollmentChange" :disabled="!selectedStudentId || loadingEnrollments" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" :class="{ 'border-red-500': form.errors.student_enrollment_id }" required>
                  <option value="">{{ loadingEnrollments ? 'Loading enrollments...' : 'Select Enrollment' }}</option>
                  <option v-for="e in enrollmentOptions" :key="e.id" :value="e.id">
                    {{ e.class_name }} – {{ e.section_name }} ({{ e.academic_year }})
                  </option>
                </select>
                <p v-if="form.errors.student_enrollment_id" class="mt-1 text-sm text-red-600">{{ form.errors.student_enrollment_id }}</p>
              </div>

              <!-- Installment Plan -->
              <div>
                <label for="installment_plan_id" class="block text-sm font-medium text-gray-700 mb-2">Installment Plan <span class="text-red-500">*</span></label>
                <select id="installment_plan_id" v-model="form.installment_plan_id" @change="onPlanChange" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :class="{ 'border-red-500': form.errors.installment_plan_id }" required>
                  <option value="">Select Plan</option>
                  <option v-for="p in installmentPlans" :key="p.id" :value="p.id">
                    {{ p.plan_name }} ({{ p.total_installments }} kistain) {{ p.fee_type ? '— ' + p.fee_type.fee_name : '' }}
                  </option>
                </select>
                <p v-if="form.errors.installment_plan_id" class="mt-1 text-sm text-red-600">{{ form.errors.installment_plan_id }}</p>
              </div>

              <!-- Fee Amount Info (auto-fetched with Concessions) -->
              <div class="md:col-span-2" v-if="feeInfo.loading || feeInfo.baseAmount > 0 || feeInfo.message">
                <div v-if="feeInfo.loading" class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 rounded-lg px-4 py-3">
                  <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  Calculating fee with concessions...
                </div>
                
                <div v-else-if="feeInfo.baseAmount > 0" class="bg-white border border-indigo-100 rounded-xl overflow-hidden shadow-sm">
                  <div class="bg-indigo-50 px-4 py-2 border-b border-indigo-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-indigo-800 uppercase tracking-widest">Fee Calculation Summary</span>
                    <span class="text-xs font-medium text-indigo-600">{{ feeInfo.feeTypeName }}</span>
                  </div>
                  <div class="p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-600">Standard Class Fee:</span>
                      <span class="font-medium text-gray-900">Rs {{ Number(feeInfo.baseAmount).toLocaleString() }}</span>
                    </div>
                    
                    <div v-if="feeInfo.concessionAmount > 0" class="flex justify-between text-sm items-center">
                      <div class="flex flex-col">
                        <span class="text-green-600 font-medium">Fee Concession:</span>
                        <span class="text-[10px] text-green-500">{{ feeInfo.concessionDetails }}</span>
                      </div>
                      <span class="font-medium text-green-600">- Rs {{ Number(feeInfo.concessionAmount).toLocaleString() }}</span>
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-2 flex justify-between items-center">
                      <span class="text-base font-bold text-indigo-900">Total Net Amount:</span>
                      <span class="text-lg font-black text-indigo-700 underline decoration-indigo-300 decoration-2 underline-offset-4">
                        Rs {{ Number(feeInfo.amount).toLocaleString() }}
                      </span>
                    </div>
                  </div>
                </div>

                <div v-else-if="feeInfo.message && feeInfo.message !== 'ok'" class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-700">
                  <strong>⚠️</strong> {{ feeInfo.message }} — Please enter amount manually below.
                </div>
              </div>

              <!-- Total Amount (auto-filled or manual) -->
              <div>
                <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount <span class="text-red-500">*</span></label>
                <input id="total_amount" v-model.number="totalAmount" @input="recalculateSchedule" type="number" step="0.01" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :class="{ 'border-red-500': form.errors.total_amount }" placeholder="0.00" required />
                <p v-if="form.errors.total_amount" class="mt-1 text-sm text-red-600">{{ form.errors.total_amount }}</p>
              </div>

              <!-- Status -->
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                  <option value="active">Active</option>
                  <option value="completed">Completed</option>
                  <option value="defaulted">Defaulted</option>
                </select>
              </div>

              <!-- Notes -->
              <div class="col-span-1 md:col-span-2">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea id="notes" v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Additional notes..."></textarea>
              </div>

            </div>

            <!-- ========== KIST PREVIEW TABLE ========== -->
            <div v-if="schedule.length > 0" class="mt-6">
              <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl overflow-hidden">
                <div class="px-4 sm:px-6 py-3 border-b border-indigo-200">
                  <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Installment Schedule Preview ({{ schedule.length }} Kists)
                  </h3>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full">
                    <thead>
                      <tr class="bg-indigo-100/60">
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Kist #</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Amount (Rs)</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Due Date</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100">
                      <tr v-for="kist in schedule" :key="kist.kist_number" class="hover:bg-indigo-50/50 transition-colors">
                        <td class="px-4 py-2.5 text-sm text-center">
                          <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-200 text-indigo-800 text-xs font-bold">{{ kist.kist_number }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-sm text-center font-semibold text-gray-900">{{ Number(kist.kist_amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                        <td class="px-4 py-2.5 text-sm text-center text-gray-700">
                          <input type="date" v-model="kist.due_date" class="bg-transparent border-0 text-center text-sm p-0 focus:ring-0 focus:outline-none" />
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="bg-indigo-100/80 font-bold">
                        <td class="px-4 py-2.5 text-sm text-center text-indigo-900">Total</td>
                        <td class="px-4 py-2.5 text-sm text-center text-indigo-900">Rs {{ Number(scheduleTotal).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
                        <td class="px-4 py-2.5 text-sm text-center text-indigo-700">—</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('student-installment-assignments.index'))" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing" :disabled="schedule.length === 0" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm disabled:opacity-50">
                <span v-if="!form.processing">
                  <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  Create Assignment ({{ schedule.length }} Kists)
                </span>
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
import { ref, reactive, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import StudentRollSearch from '@/Components/Common/StudentRollSearch.vue'
import axios from 'axios'

const props = defineProps({
  installmentPlans: Array,
})

// State
const selectedStudentId = ref('')
const enrollmentOptions = ref([])
const loadingEnrollments = ref(false)
const totalAmount = ref('')
const schedule = ref([])

const feeInfo = reactive({
  loading: false,
  amount: 0,
  baseAmount: 0,
  concessionAmount: 0,
  concessionDetails: null,
  feeTypeName: null,
  dueDay: 10,
  message: null,
})

const form = useForm({
  student_enrollment_id: '',
  installment_plan_id: '',
  total_amount: '',
  status: 'active',
  notes: '',
  schedule: [],
})

// Computed
const selectedPlan = computed(() => {
  return props.installmentPlans.find(p => p.id == form.installment_plan_id)
})

const scheduleTotal = computed(() => {
  return schedule.value.reduce((sum, k) => sum + Number(k.kist_amount || 0), 0)
})

// Student selected
const onStudentSelected = async () => {
  form.student_enrollment_id = ''
  enrollmentOptions.value = []
  resetFeeInfo()
  schedule.value = []
  if (!selectedStudentId.value) return

  loadingEnrollments.value = true
  try {
    const res = await axios.get(route('student-installment-assignments.enrollments-by-student', selectedStudentId.value))
    enrollmentOptions.value = res.data
    if (enrollmentOptions.value.length > 0) {
      form.student_enrollment_id = enrollmentOptions.value[0].id
      fetchFeeAmount()
    }
  } catch (e) { console.error('Error loading enrollments:', e) }
  finally { loadingEnrollments.value = false }
}

const onStudentCleared = () => {
  form.student_enrollment_id = ''
  form.installment_plan_id = ''
  enrollmentOptions.value = []
  totalAmount.value = ''
  schedule.value = []
  resetFeeInfo()
}

const onEnrollmentChange = () => {
  resetFeeInfo()
  schedule.value = []
  fetchFeeAmount()
}

const onPlanChange = () => {
  schedule.value = []
  fetchFeeAmount()
}

const resetFeeInfo = () => {
  feeInfo.loading = false
  feeInfo.amount = 0
  feeInfo.baseAmount = 0
  feeInfo.concessionAmount = 0
  feeInfo.concessionDetails = null
  feeInfo.feeTypeName = null
  feeInfo.dueDay = 10
  feeInfo.message = null
}

// Fetch fee structure amount
const fetchFeeAmount = async () => {
  if (!form.student_enrollment_id || !form.installment_plan_id) return

  feeInfo.loading = true
  try {
    const res = await axios.get(route('student-installment-assignments.fee-for-enrollment'), {
      params: {
        enrollment_id: form.student_enrollment_id,
        plan_id: form.installment_plan_id,
      }
    })
    feeInfo.amount = Number(res.data.amount) || 0
    feeInfo.baseAmount = Number(res.data.base_amount) || 0
    feeInfo.concessionAmount = Number(res.data.concession_amount) || 0
    feeInfo.concessionDetails = res.data.concession_details
    feeInfo.feeTypeName = res.data.fee_type_name
    feeInfo.dueDay = res.data.due_day || 10
    feeInfo.message = res.data.message

    if (feeInfo.amount > 0) {
      totalAmount.value = feeInfo.amount
    }
    recalculateSchedule()
  } catch (e) {
    console.error('Error fetching fee:', e)
    feeInfo.message = 'Error fetching fee structure'
  } finally {
    feeInfo.loading = false
  }
}

// Auto-divide into kists
const recalculateSchedule = () => {
  const plan = selectedPlan.value
  if (!plan || !totalAmount.value || totalAmount.value <= 0) {
    schedule.value = []
    return
  }

  const n = plan.total_installments
  const perKist = Math.floor((totalAmount.value / n) * 100) / 100
  const remainder = Math.round((totalAmount.value - perKist * n) * 100) / 100
  const today = new Date()
  const dueDay = feeInfo.dueDay || 10

  const kists = []
  for (let i = 0; i < n; i++) {
    const dueDate = new Date(today.getFullYear(), today.getMonth() + i, dueDay)
    const amount = i === n - 1 ? perKist + remainder : perKist

    kists.push({
      kist_number: i + 1,
      kist_amount: Math.round(amount * 100) / 100,
      due_date: dueDate.toISOString().split('T')[0],
    })
  }
  schedule.value = kists
}

// Submit form
const submit = () => {
  form.total_amount = totalAmount.value
  form.schedule = schedule.value
  form.post(route('student-installment-assignments.store'), { preserveScroll: true })
}
</script>
