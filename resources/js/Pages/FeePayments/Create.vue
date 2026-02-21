<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Record Fee Payment</h1>
            <p class="mt-2 text-sm text-gray-600">Record a new fee payment transaction</p>
          </div>
          <Link :href="route('fee-payments.index')">
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
          
          <!-- Student & Fee Selection Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-blue-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Student & Fee Selection</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Student <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.student_id"
                  @change="loadStudentFees"
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

              <!-- Pending Fees List -->
              <div v-if="pendingFees.length > 0" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Select Fee to Pay <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                  <label 
                    v-for="fee in pendingFees" 
                    :key="fee.id"
                    class="flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                    :class="{ 'bg-blue-50 border-blue-300': form.fee_id === fee.id }"
                  >
                    <input
                      type="radio"
                      :value="fee.id"
                      v-model="form.fee_id"
                      @change="onFeeSelect"
                      class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500"
                    />
                    <div class="ml-3 flex-1">
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900">{{ fee.fee_type.name }}</p>
                        <span :class="getStatusClass(fee.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                          {{ formatStatus(fee.status) }}
                        </span>
                      </div>
                      <p class="text-xs text-gray-500 mt-1">{{ formatMonthYear(fee.month, fee.year) }}</p>
                      <div class="grid grid-cols-3 gap-2 mt-2 text-xs">
                        <div>
                          <span class="text-gray-500">Total:</span>
                          <span class="font-semibold text-gray-900 ml-1">Rs. {{ Number(fee.net_amount).toLocaleString() }}</span>
                        </div>
                        <div>
                          <span class="text-gray-500">Paid:</span>
                          <span class="font-semibold text-green-600 ml-1">Rs. {{ Number(fee.paid_amount).toLocaleString() }}</span>
                        </div>
                        <div>
                          <span class="text-gray-500">Balance:</span>
                          <span class="font-semibold text-red-600 ml-1">Rs. {{ Number(fee.balance_amount).toLocaleString() }}</span>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
                <p v-if="form.errors.fee_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.fee_id }}
                </p>
              </div>

              <div v-else-if="form.student_id && pendingFees.length === 0" class="md:col-span-2">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                  <svg class="mx-auto h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <p class="mt-2 text-sm font-medium text-green-900">All fees are paid!</p>
                  <p class="text-xs text-green-700 mt-1">This student has no pending fee payments.</p>
                </div>
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

          <!-- Payment Details Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Payment Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.payment_date"
                  type="date"
                  label="Payment Date"
                  required
                  :error="form.errors.payment_date"
                />
              </div>

              <div>
                <Input
                  v-model="form.amount_paid"
                  type="number"
                  label="Amount Paid"
                  placeholder="5000"
                  required
                  :error="form.errors.amount_paid"
                  step="0.01"
                  min="0"
                  :max="selectedFeeBalance"
                  :hint="selectedFeeBalance ? `Maximum: Rs. ${selectedFeeBalance.toLocaleString()}` : ''"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Payment Method <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.payment_method"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.payment_method }"
                  required
                >
                  <option value="" disabled>Select payment method</option>
                  <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                    {{ method.label }}
                  </option>
                </select>
                <p v-if="form.errors.payment_method" class="mt-1 text-sm text-red-600">
                  {{ form.errors.payment_method }}
                </p>
              </div>

              <div>
                <Input
                  v-model="form.receipt_no"
                  label="Receipt Number"
                  :error="form.errors.receipt_no"
                  :disabled="true"
                  hint="Auto-generated"
                />
              </div>

              <div>
                <Input
                  v-model="form.transaction_id"
                  label="Transaction ID"
                  placeholder="Enter transaction ID"
                  :error="form.errors.transaction_id"
                  :hint="form.payment_method === 'online' || form.payment_method === 'bank_transfer' ? 'Required for online/bank payments' : 'Optional'"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Collected By <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.collected_by"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.collected_by }"
                  required
                >
                  <option value="" disabled>Select collector</option>
                  <option value="1">Current User</option>
                  <!-- Add more users/staff if needed -->
                </select>
                <p v-if="form.errors.collected_by" class="mt-1 text-sm text-red-600">
                  {{ form.errors.collected_by }}
                </p>
              </div>
            </div>
          </div>

          <!-- Bank/Cheque Details Section (Conditional) -->
          <div v-if="form.payment_method === 'cheque' || form.payment_method === 'bank_transfer'" class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">
                {{ form.payment_method === 'cheque' ? 'Cheque Details' : 'Bank Details' }}
              </h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div v-if="form.payment_method === 'cheque'">
                <Input
                  v-model="form.cheque_number"
                  label="Cheque Number"
                  placeholder="Enter cheque number"
                  :error="form.errors.cheque_number"
                  :required="form.payment_method === 'cheque'"
                />
              </div>

              <div>
                <Input
                  v-model="form.bank_name"
                  label="Bank Name"
                  placeholder="Enter bank name"
                  :error="form.errors.bank_name"
                  :required="form.payment_method === 'cheque' || form.payment_method === 'bank_transfer'"
                />
              </div>
            </div>
          </div>

          <!-- Additional Notes Section -->
          <div class="p-8 bg-gray-50">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-orange-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Additional Information</h2>
            </div>
            
            <div class="grid grid-cols-1 gap-6">
              <div>
                <Textarea
                  v-model="form.notes"
                  label="Notes"
                  placeholder="Enter any additional notes or remarks about this payment"
                  :rows="3"
                  :error="form.errors.notes"
                />
              </div>

              <!-- Payment Summary -->
              <div v-if="form.fee_id && selectedFee" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-3">Payment Summary</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                  <div>
                    <p class="text-gray-600">Fee Total</p>
                    <p class="font-semibold text-gray-900">Rs. {{ Number(selectedFee.net_amount).toLocaleString() }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">Already Paid</p>
                    <p class="font-semibold text-green-600">Rs. {{ Number(selectedFee.paid_amount).toLocaleString() }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">Paying Now</p>
                    <p class="font-semibold text-blue-600">Rs. {{ Number(form.amount_paid || 0).toLocaleString() }}</p>
                  </div>
                  <div>
                    <p class="text-gray-600">Remaining</p>
                    <p class="font-semibold text-red-600">
                      Rs. {{ (Number(selectedFee.balance_amount) - Number(form.amount_paid || 0)).toLocaleString() }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-end gap-3">
              <Link :href="route('fee-payments.index')">
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
                :disabled="!form.fee_id || !form.amount_paid || pendingFees.length === 0"
                class="px-8 py-2.5 shadow-md hover:shadow-lg transition-all"
              >
                <span v-if="!form.processing">Record Payment</span>
                <span v-else>Recording...</span>
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
import { ref, computed } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Textarea from '@/Components/Forms/Textarea.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  branches: {
    type: Array,
    default: () => []
  },
  students: {
    type: Array,
    default: () => []
  }
})

const paymentMethods = [
  { value: 'cash', label: 'Cash' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'online', label: 'Online Payment' },
  { value: 'card', label: 'Card Payment' }
]

const pendingFees = ref([])
const selectedFee = ref(null)

const form = useForm({
  fee_id: '',
  student_id: '',
  branch_id: '',
  payment_date: new Date().toISOString().split('T')[0],
  amount_paid: '',
  payment_method: 'cash',
  transaction_id: '',
  cheque_number: '',
  bank_name: '',
  receipt_no: 'AUTO-GENERATED',
  collected_by: '1',
  notes: ''
})

const selectedFeeBalance = computed(() => {
  return selectedFee.value ? Number(selectedFee.value.balance_amount) : 0
})

const loadStudentFees = async () => {
  if (!form.student_id) return
  
  try {
    const response = await axios.get(route('fees.dropdown'), {
      params: {
        student_id: form.student_id,
        status: 'pending,partial'
      }
    })
    
    pendingFees.value = response.data || []
    
    // Auto-select student's branch if available
    const student = props.students.find(s => s.id === form.student_id)
    if (student && student.branch_id) {
      form.branch_id = student.branch_id
    }
  } catch (error) {
    console.error('Error loading student fees:', error)
    pendingFees.value = []
  }
}

const onFeeSelect = () => {
  selectedFee.value = pendingFees.value.find(f => f.id === form.fee_id)
  if (selectedFee.value) {
    // Auto-fill with remaining balance
    form.amount_paid = selectedFee.value.balance_amount
  }
}

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'partial': 'bg-blue-100 text-blue-800',
    'paid': 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatMonthYear = (month, year) => {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${months[month - 1]} ${year}`
}

const submit = () => {
  form.post(route('fee-payments.store'), {
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