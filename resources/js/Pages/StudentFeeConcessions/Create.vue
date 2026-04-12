<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Assign Fee Concession</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Assign a fee concession to an enrolled student</p>
            </div>
            <Button @click="$inertia.visit(route('student-fee-concessions.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <!-- Create Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">

              <!-- 1. Student Search -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Student Search <span class="text-red-500">*</span></label>
                <StudentRollSearch 
                  v-model="selectedStudentId" 
                  @student-selected="onStudentChange"
                  @cleared="onStudentChange"
                />
              </div>

              <!-- 2. Enrollment -->
              <div>
                <label for="student_enrollment_id" class="block text-sm font-medium text-gray-700 mb-2">Enrollment <span class="text-red-500">*</span></label>
                <select
                  id="student_enrollment_id"
                  v-model="form.student_enrollment_id"
                  :disabled="!selectedStudentId || loadingEnrollments"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                  :class="{ 'border-red-500': form.errors.student_enrollment_id }"
                  required
                >
                  <option value="">{{ loadingEnrollments ? 'Loading enrollments...' : 'Select Enrollment' }}</option>
                  <option v-for="e in enrollmentOptions" :key="e.id" :value="e.id">
                    {{ e.class_name }} – {{ e.section_name }} ({{ e.academic_year }})
                  </option>
                </select>
                <p v-if="form.errors.student_enrollment_id" class="mt-1 text-sm text-red-600">{{ form.errors.student_enrollment_id }}</p>
              </div>

              <!-- Concession Type -->
              <div>
                <label for="concession_type_id" class="block text-sm font-medium text-gray-700 mb-2">Concession Type <span class="text-red-500">*</span></label>
                <select id="concession_type_id" v-model="form.concession_type_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :class="{ 'border-red-500': form.errors.concession_type_id }" required>
                  <option value="">Select Concession Type</option>
                  <option v-for="ct in concessionTypes" :key="ct.id" :value="ct.id">{{ ct.concession_name }}</option>
                </select>
                <p v-if="form.errors.concession_type_id" class="mt-1 text-sm text-red-600">{{ form.errors.concession_type_id }}</p>
              </div>

              <!-- Fee Type -->
              <div>
                <label for="fee_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Fee Type
                  <span class="ml-1 text-xs text-gray-400 font-normal">(leave blank to apply to all fees)</span>
                </label>
                <select id="fee_type_id" v-model="form.fee_type_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :class="{ 'border-red-500': form.errors.fee_type_id }">
                  <option value="">All Fee Types</option>
                  <option v-for="ft in feeTypes" :key="ft.id" :value="ft.id">{{ ft.fee_name }}</option>
                </select>
              </div>

              <!-- Discount Type -->
              <div>
                <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-2">Discount Type <span class="text-red-500">*</span></label>
                <select id="discount_type" v-model="form.discount_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                  <option value="percentage">Percentage (%)</option>
                  <option value="fixed">Fixed Amount</option>
                </select>
              </div>

              <!-- Discount Value -->
              <div>
                <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-2">Discount Value <span class="text-red-500">*</span></label>
                <div class="relative mt-1">
                  <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm pointer-events-none">
                    {{ form.discount_type === 'percentage' ? '%' : 'Rs.' }}
                  </span>
                  <input
                    id="discount_value"
                    v-model.number="form.discount_value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-8"
                    :class="{ 'pl-10': form.discount_type === 'fixed' }"
                    placeholder="0.00"
                    required
                  />
                </div>
              </div>

              <!-- Start Date -->
              <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input id="start_date" v-model="form.start_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
              </div>

              <!-- End Date -->
              <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date (optional)</label>
                <input id="end_date" v-model="form.end_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
              </div>

              <!-- Remarks -->
              <div class="col-span-1 md:col-span-2">
                <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <textarea id="remarks" v-model="form.remarks" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Additional notes..."></textarea>
              </div>

              <!-- Toggle -->
              <div class="flex items-center gap-3">
                <label class="inline-flex items-center cursor-pointer">
                  <input v-model="form.is_active" type="checkbox" class="sr-only peer" />
                  <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                  <span class="ms-3 text-sm font-medium text-gray-700">Active Concession</span>
                </label>
              </div>
            </div>

            <!-- ========== CALCULATION PREVIEW ========== -->
            <div class="mt-8">
              <div v-if="loadingFee" class="bg-gray-50 border border-gray-200 rounded-xl p-8 flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-indigo-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4m2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Calculating Fee Breakdown...</span>
              </div>

              <div v-else-if="breakdown.baseAmount > 0" class="bg-gradient-to-r from-indigo-50 via-white to-blue-50 border border-indigo-200 rounded-xl overflow-hidden shadow-md">
                <div class="px-4 py-3 bg-indigo-100/50 border-b border-indigo-200 flex items-center justify-between">
                  <h3 class="text-xs font-bold text-indigo-900 uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Fee Calculation Breakdown
                  </h3>
                  <span class="text-[10px] font-bold text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded italic">{{ form.fee_type_id ? 'Single Fee Type' : 'All Fee Types Sum' }}</span>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-center md:text-left">
                  <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 block">Base Class Fee</span>
                    <span class="text-2xl font-black text-gray-900 leading-none">Rs {{ breakdown.baseAmount.toLocaleString() }}</span>
                  </div>
                  <div class="md:border-x border-indigo-100 md:px-6">
                    <span class="text-[10px] font-bold text-green-500 uppercase tracking-wider mb-1 block">Concession (-)</span>
                    <span class="text-2xl font-black text-green-600 leading-none">Rs {{ breakdown.discountAmount.toLocaleString() }}</span>
                    <p class="text-[10px] text-green-500 mt-1 font-medium">{{ breakdown.label }}</p>
                  </div>
                  <div>
                    <span class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1 block">Final Payable Fee</span>
                    <span class="text-3xl font-black text-blue-700 leading-none underline decoration-blue-200 underline-offset-8">Rs {{ breakdown.netAmount.toLocaleString() }}</span>
                  </div>
                </div>
              </div>

              <div v-else-if="form.student_enrollment_id && !loadingFee" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3 text-amber-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.268 14c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="text-sm">
                  <p class="font-bold">No Fee Structure Found!</p>
                  <p class="text-xs">Umm... is student ki class ke liye koi active Fee Structure nahi mila. Pehle Fee Structure check karein.</p>
                </div>
              </div>
            </div>

            <div class="mt-8 flex items-center justify-end gap-4 border-t pt-6">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('student-fee-concessions.index'))">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing" :disabled="breakdown.baseAmount === 0 && !form.processing">
                Assign Concession
              </Button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import StudentRollSearch from '@/Components/Common/StudentRollSearch.vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
  feeTypes: Array,
  concessionTypes: Array,
  existingConcessions: {
    type: Array,
    default: () => [],
  },
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (flash) => {
  if (flash?.error) {
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: flash.error,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true,
    })
  }
}, { immediate: true, deep: true })

const selectedStudentId = ref('')
const enrollmentOptions = ref([])
const loadingEnrollments = ref(false)
const feeStructureAmount = ref(0)
const loadingFee = ref(false)

const form = useForm({
  student_enrollment_id: '',
  fee_type_id: '',
  concession_type_id: '',
  discount_type: 'percentage',
  discount_value: 0,
  start_date: '',
  end_date: '',
  remarks: '',
  is_active: true,
})

const breakdown = computed(() => {
  const base = Number(feeStructureAmount.value) || 0
  const val = Number(form.discount_value) || 0
  let discount = 0
  let label = ''

  if (base > 0 && val > 0) {
    if (form.discount_type === 'percentage') {
      discount = (base * val) / 100
      label = `${val}% of class fee`
    } else {
      discount = val
      label = `Fixed amount deduction`
    }
  }

  return {
    baseAmount: base,
    discountAmount: Math.round(discount),
    netAmount: Math.max(0, Math.round(base - discount)),
    label
  }
})

const onStudentChange = async () => {
  form.student_enrollment_id = ''
  enrollmentOptions.value = []
  feeStructureAmount.value = 0
  if (!selectedStudentId.value) return

  loadingEnrollments.value = true
  try {
    const res = await axios.get(route('student-fee-concessions.enrollments-by-student', selectedStudentId.value))
    enrollmentOptions.value = res.data
    if (enrollmentOptions.value.length > 0) {
      form.student_enrollment_id = enrollmentOptions.value[0].id
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingEnrollments.value = false
  }
}

const fetchFeeAmount = async () => {
  if (!form.student_enrollment_id) {
    feeStructureAmount.value = 0
    return
  }
  loadingFee.value = true
  try {
    const res = await axios.get(route('student-fee-concessions.fee-structure-amount'), {
      params: { 
        enrollment_id: form.student_enrollment_id, 
        fee_type_id: form.fee_type_id || null 
      }
    })
    feeStructureAmount.value = res.data.amount || 0
  } catch (e) {
    feeStructureAmount.value = 0
  } finally {
    loadingFee.value = false
  }
}

watch(() => form.student_enrollment_id, fetchFeeAmount)
watch(() => form.fee_type_id, fetchFeeAmount)

const submit = () => {
  // Client-side duplicate check
  const isDuplicate = props.existingConcessions?.some(
    c => c.student_enrollment_id == form.student_enrollment_id &&
         c.concession_type_id == form.concession_type_id &&
         (c.fee_type_id == form.fee_type_id || (!c.fee_type_id && !form.fee_type_id)) &&
         c.is_active == true
  )

  if (isDuplicate) {
    Swal.fire({
      icon: 'warning',
      title: 'Duplicate Concession!',
      html: 'An active fee concession already exists for this student with the same concession type.<br>Please edit the existing record or deactivate it first.',
      confirmButtonColor: '#3085d6',
    })
    return
  }

  form.post(route('student-fee-concessions.store'), { preserveScroll: true })
}
</script>