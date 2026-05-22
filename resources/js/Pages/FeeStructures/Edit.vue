<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Fee Structure</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Create an approval request for a new fee structure version</p>
            </div>
            <Button @click="$inertia.visit(route('fee-structures.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">
          Current structure stays locked until this change is approved. Existing generated vouchers remain attached to their old fee structure version.
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">

              <div>
                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-2">Academic Year <span class="text-red-500">*</span></label>
                <select id="academic_year_id" v-model="form.academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                  <option value="">Select Academic Year</option>
                  <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.year_name }}</option>
                </select>
                <p v-if="form.errors.academic_year_id" class="mt-1 text-sm text-red-600">{{ form.errors.academic_year_id }}</p>
              </div>

              <div>
                <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">Branch <span class="text-red-500">*</span></label>
                <select id="branch_id" v-model="form.branch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                  <option value="">Select Branch</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                </select>
                <p v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">{{ form.errors.branch_id }}</p>
              </div>

              <div>
                <label for="class_id" class="block text-sm font-medium text-gray-700 mb-2">Class <span class="text-red-500">*</span></label>
                <select id="class_id" v-model="form.class_id" :disabled="!form.branch_id || loadingClasses" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" required>
                  <option value="">{{ loadingClasses ? 'Loading Classes...' : 'Select Class' }}</option>
                  <option v-for="c in availableClasses" :key="c.id" :value="c.id">{{ c.class_name }}</option>
                </select>
                <p v-if="form.errors.class_id" class="mt-1 text-sm text-red-600">{{ form.errors.class_id }}</p>
              </div>

              <div>
                <label for="fee_type_id" class="block text-sm font-medium text-gray-700 mb-2">Fee Type <span class="text-red-500">*</span></label>
                <select id="fee_type_id" v-model="form.fee_type_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                  <option value="">Select Fee Type</option>
                  <option v-for="f in feeTypes" :key="f.id" :value="f.id">{{ f.fee_name }}</option>
                </select>
                <p v-if="form.errors.fee_type_id" class="mt-1 text-sm text-red-600">{{ form.errors.fee_type_id }}</p>
              </div>

              <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (Rs.) <span class="text-red-500">*</span></label>
                <input id="amount" v-model="form.amount" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
              </div>

              <div>
                <label for="due_day" class="block text-sm font-medium text-gray-700 mb-2">Due Day of Month</label>
                <input id="due_day" v-model="form.due_day" type="number" min="1" max="28" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
              </div>

              <div>
                <label for="effective_from" class="block text-sm font-medium text-gray-700 mb-2">Effective From</label>
                <input id="effective_from" v-model="form.effective_from" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
              </div>

              <div>
                <label for="effective_to" class="block text-sm font-medium text-gray-700 mb-2">Effective To</label>
                <input id="effective_to" v-model="form.effective_to" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
              </div>

              <div class="flex items-center">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
              </div>

            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
              <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Current Version</p>
                <p class="mt-2 text-xl font-semibold text-gray-900">v{{ props.feeStructure.version_no || 1 }}</p>
                <p class="text-sm text-gray-600">{{ props.feeStructure.version_status || 'active' }}</p>
              </div>

              <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-green-700">Proposed Amount</p>
                <p class="mt-2 text-xl font-semibold text-green-900">Rs {{ money(form.amount) }}</p>
                <p class="text-sm text-green-700">Due day {{ form.due_day || '-' }}</p>
              </div>

              <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700">Approval Status</p>
                <p class="mt-2 text-xl font-semibold text-yellow-900">Pending after submit</p>
                <p class="text-sm text-yellow-700">Maker-checker enabled</p>
              </div>
            </div>

            <div class="mt-6">
              <label for="change_reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Change <span class="text-red-500">*</span></label>
              <textarea id="change_reason" v-model="form.change_reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Example: annual fee revision approved by management" required></textarea>
              <p v-if="form.errors.change_reason" class="mt-1 text-sm text-red-600">{{ form.errors.change_reason }}</p>
            </div>

            <div class="mt-6 rounded-lg border border-gray-200">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <div>
                  <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-900">Impact Analysis</h3>
                  <p class="text-xs text-gray-500">Preview affected students and unpaid vouchers before requesting approval.</p>
                </div>
                <Button type="button" variant="secondary" @click="previewImpact" :loading="impactLoading" class="w-full sm:w-auto text-sm">
                  Preview Impact
                </Button>
              </div>

              <div v-if="impact" class="grid grid-cols-1 md:grid-cols-4 gap-3 p-4">
                <div class="rounded-lg bg-blue-50 p-3">
                  <p class="text-xs text-blue-700">Students</p>
                  <p class="mt-1 text-xl font-semibold text-blue-900">{{ impact.affected_students_count }}</p>
                </div>
                <div class="rounded-lg bg-yellow-50 p-3">
                  <p class="text-xs text-yellow-700">Unpaid Vouchers</p>
                  <p class="mt-1 text-xl font-semibold text-yellow-900">{{ impact.unpaid_vouchers_count }}</p>
                </div>
                <div class="rounded-lg bg-purple-50 p-3">
                  <p class="text-xs text-purple-700">Future Vouchers</p>
                  <p class="mt-1 text-xl font-semibold text-purple-900">{{ impact.future_vouchers_count }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-3">
                  <p class="text-xs text-red-700">Monthly Difference</p>
                  <p class="mt-1 text-xl font-semibold text-red-900">Rs {{ money(impact.estimated_monthly_difference) }}</p>
                </div>
              </div>

              <div v-else class="p-4 text-sm text-gray-500">
                No preview yet. Click Preview Impact after changing the amount, due day, or effective dates.
              </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-structures.index'))" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm">
                <span v-if="!form.processing">Submit Change Request</span>
                <span v-else>Submitting...</span>
              </Button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Swal from 'sweetalert2'

const props = defineProps({
  feeStructure: { type: Object, required: true },
  academicYears: Array,
  branches: Array,
  feeTypes: Array,
  errors: Object,
})

const availableClasses = ref([])
const loadingClasses = ref(false)
const impact = ref(null)
const impactLoading = ref(false)

const form = useForm({
  academic_year_id: props.feeStructure.academic_year_id,
  branch_id: props.feeStructure.branch_id,
  class_id: props.feeStructure.class_id,
  fee_type_id: props.feeStructure.fee_type_id,
  amount: props.feeStructure.amount,
  due_day: props.feeStructure.due_day ?? '',
  effective_from: props.feeStructure.effective_from ?? '',
  effective_to: props.feeStructure.effective_to ?? '',
  is_active: props.feeStructure.is_active ?? true,
  change_reason: '',
})

const fetchClasses = async (branchId, isInit = false) => {
  if (!isInit) {
    form.class_id = '' // Reset class selection
  }
  availableClasses.value = []
  
  if (branchId) {
    loadingClasses.value = true
    try {
      const response = await axios.get(route('branches.get-classes', branchId))
      availableClasses.value = response.data
    } catch (error) {
      console.error('Failed to fetch classes:', error)
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: 'Failed to load classes for the selected branch.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
      })
    } finally {
      loadingClasses.value = false
    }
  }
}

// Watch for branch changes to fetch corresponding classes
watch(() => form.branch_id, (newBranchId) => {
  fetchClasses(newBranchId)
})

onMounted(() => {
  if (form.branch_id) {
    fetchClasses(form.branch_id, true)
  }
})

// Watch for flash messages and validation errors
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
  if (flash?.success) {
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: flash.success,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    })
  }
}, { immediate: true, deep: true })

// Watch for validation errors and show SweetAlert
watch(() => form.errors, (errors) => {
  if (Object.keys(errors).length > 0) {
    const errorMessages = Object.values(errors).join('<br>')
    Swal.fire({
      icon: 'error',
      title: 'Validation Error!',
      html: errorMessages,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true,
    })
  }
}, { deep: true })

const submit = () => {
  form.put(route('fee-structures.update', props.feeStructure.id), {
    preserveScroll: true,
  })
}

const previewImpact = async () => {
  impactLoading.value = true
  try {
    const response = await axios.post(route('fee-structures.impact-preview', props.feeStructure.id), {
      academic_year_id: form.academic_year_id,
      branch_id: form.branch_id,
      class_id: form.class_id,
      fee_type_id: form.fee_type_id,
      amount: form.amount,
      due_day: form.due_day,
      effective_from: form.effective_from,
      effective_to: form.effective_to,
      is_active: form.is_active,
    })
    impact.value = response.data
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Impact preview failed',
      text: error.response?.data?.message || 'Please check the form fields and try again.',
    })
  } finally {
    impactLoading.value = false
  }
}

const money = (value) => {
  const amount = Number(value || 0)
  return amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
