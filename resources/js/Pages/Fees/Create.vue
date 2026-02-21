<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Create Fee Record</h1>
            <p class="mt-2 text-sm text-gray-600">Generate a new fee record for a student</p>
          </div>
          <Link :href="route('fees.index')">
            <Button variant="secondary" class="shadow-sm hover:shadow-md transition-shadow">
              <ArrowLeftIcon class="h-5 w-5 mr-2" />
              Back to List
            </Button>
          </Link>
        </div>
      </div>

      <!-- Form Card -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <form @submit.prevent="submit">
          
          <!-- Student & Fee Type Information Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-blue-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Student & Fee Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Student <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.student_id"
                  @change="onStudentChange"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.student_id }"
                  required
                >
                  <option value="" disabled>Select student</option>
                  <option v-for="student in students" :key="student.id" :value="student.id">
                    {{ student.admission_no }} - {{ student.first_name }} {{ student.last_name }}
                  </option>
                </select>
                <p v-if="form.errors.student_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.student_id }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Fee Type <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.fee_type_id"
                  @change="onFeeTypeChange"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.fee_type_id }"
                  required
                >
                  <option value="" disabled>Select fee type</option>
                  <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                    {{ feeType.name }} - Rs. {{ Number(feeType.amount).toLocaleString() }}
                  </option>
                </select>
                <p v-if="form.errors.fee_type_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.fee_type_id }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Class <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.class_id"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.class_id }"
                  required
                >
                  <option value="" disabled>Select class</option>
                  <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">
                    {{ classItem.name }}
                  </option>
                </select>
                <p v-if="form.errors.class_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.class_id }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Branch <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.branch_id"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.branch_id }"
                  required
                >
                  <option value="" disabled>Select branch</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.name }}
                  </option>
                </select>
                <p v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.branch_id }}
                </p>
              </div>
            </div>
          </div>

          <!-- Period & Academic Information Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Period & Academic Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Month <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.month"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.month }"
                  required
                >
                  <option value="" disabled>Select month</option>
                  <option v-for="(month, index) in months" :key="index + 1" :value="index + 1">
                    {{ month }}
                  </option>
                </select>
                <p v-if="form.errors.month" class="mt-1 text-sm text-red-600">
                  {{ form.errors.month }}
                </p>
              </div>

              <div>
                <Input
                  v-model="form.year"
                  type="number"
                  label="Year"
                  placeholder="2024"
                  required
                  :error="form.errors.year"
                  min="2000"
                  :max="new Date().getFullYear() + 1"
                />
              </div>

              <div>
                <Input
                  v-model="form.academic_year"
                  label="Academic Year"
                  placeholder="e.g., 2024-2025"
                  :error="form.errors.academic_year"
                  hint="Optional academic year identifier"
                />
              </div>

              <div>
                <Input
                  v-model="form.due_date"
                  type="date"
                  label="Due Date"
                  required
                  :error="form.errors.due_date"
                />
              </div>
            </div>
          </div>

          <!-- Amount & Discount Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Amount & Discount Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.total_amount"
                  type="number"
                  label="Total Amount"
                  placeholder="5000"
                  required
                  :error="form.errors.total_amount"
                  step="0.01"
                  min="0"
                />
              </div>

              <div>
                <Input
                  v-model="form.fine_amount"
                  type="number"
                  label="Fine Amount"
                  placeholder="0"
                  :error="form.errors.fine_amount"
                  step="0.01"
                  min="0"
                  hint="Late payment fine"
                />
              </div>

              <div>
                <Input
                  v-model="form.discount_percent"
                  type="number"
                  label="Discount Percentage"
                  placeholder="10"
                  :error="form.errors.discount_percent"
                  step="0.01"
                  min="0"
                  max="100"
                  @input="calculateDiscount"
                />
              </div>

              <div>
                <Input
                  v-model="form.discount_amount"
                  type="number"
                  label="Discount Amount"
                  placeholder="500"
                  :error="form.errors.discount_amount"
                  step="0.01"
                  min="0"
                  hint="Auto-calculated from percentage"
                />
              </div>

              <div class="md:col-span-2">
                <Textarea
                  v-model="form.discount_reason"
                  label="Discount Reason"
                  placeholder="Enter reason for discount if applicable"
                  :rows="2"
                  :error="form.errors.discount_reason"
                />
              </div>

              <!-- Net Amount Display -->
              <div class="md:col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-gray-600">Net Payable Amount</p>
                    <p class="text-xs text-gray-500 mt-1">Total + Fine - Discount</p>
                  </div>
                  <p class="text-2xl font-bold text-blue-600">
                    Rs. {{ calculateNetAmount().toLocaleString() }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Status & Additional Information Section -->
          <div class="p-8 bg-gray-50">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-orange-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Status & Additional Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Status <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.status"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.status }"
                  required
                >
                  <option value="" disabled>Select status</option>
                  <option value="pending">Pending</option>
                  <option value="partial">Partial</option>
                  <option value="paid">Paid</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                  {{ form.errors.status }}
                </p>
              </div>

              <div class="md:col-span-2">
                <Textarea
                  v-model="form.remarks"
                  label="Remarks"
                  placeholder="Enter any additional notes or remarks"
                  :rows="3"
                  :error="form.errors.remarks"
                />
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-end gap-3">
              <Link :href="route('fees.index')">
                <Button 
                  type="button" 
                  variant="secondary" 
                  class="px-6 py-2.5 shadow-sm hover:shadow-md transition-all"
                >
                  Cancel
                </Button>
              </Link>
              <Button 
                type="submit" 
                variant="primary" 
                :loading="form.processing"
                class="px-8 py-2.5 shadow-md hover:shadow-lg transition-all"
              >
                <span v-if="!form.processing">Create Fee Record</span>
                <span v-else>Creating...</span>
              </Button>
            </div>
          </div>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Textarea from '@/Components/Forms/Textarea.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  branches: {
    type: Array,
    default: () => []
  },
  classes: {
    type: Array,
    default: () => []
  },
  feeTypes: {
    type: Array,
    default: () => []
  },
  students: {
    type: Array,
    default: () => []
  }
})

const months = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
]

const form = useForm({
  student_id: '',
  fee_type_id: '',
  class_id: '',
  branch_id: '',
  month: '',
  year: new Date().getFullYear(),
  academic_year: '',
  total_amount: '',
  discount_percent: '',
  discount_amount: '',
  discount_reason: '',
  fine_amount: '',
  due_date: '',
  status: 'pending',
  remarks: ''
})

const onStudentChange = () => {
  const student = props.students.find(s => s.id === form.student_id)
  if (student) {
    form.class_id = student.class_id || ''
    form.branch_id = student.branch_id || ''
  }
}

const onFeeTypeChange = () => {
  const feeType = props.feeTypes.find(ft => ft.id === form.fee_type_id)
  if (feeType) {
    form.total_amount = feeType.amount || ''
  }
}

const calculateDiscount = () => {
  if (form.discount_percent && form.total_amount) {
    form.discount_amount = (parseFloat(form.total_amount) * parseFloat(form.discount_percent)) / 100
  }
}

const calculateNetAmount = () => {
  const total = parseFloat(form.total_amount) || 0
  const discount = parseFloat(form.discount_amount) || 0
  const fine = parseFloat(form.fine_amount) || 0
  return total + fine - discount
}

const submit = () => {
  form.post(route('fees.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Handle success
    },
    onError: (errors) => {
      console.log('Validation errors:', errors)
    }
  })
}
</script>