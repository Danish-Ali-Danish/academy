<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Payment Receipt</h1>
            <p class="mt-2 text-sm text-gray-600">View payment transaction details</p>
          </div>
          <div class="flex gap-3">
            <Button variant="secondary" @click="printReceipt" class="shadow-sm hover:shadow-md transition-shadow">
              <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              Print Receipt
            </Button>
            <Link :href="route('fee-payments.index')">
              <Button variant="secondary" class="shadow-sm hover:shadow-md transition-shadow">
                <ArrowLeftIcon class="h-5 w-5 mr-2" />
                Back to List
              </Button>
            </Link>
          </div>
        </div>
      </div>

      <!-- Receipt Card -->
      <div id="receipt" class="bg-white rounded-xl shadow-lg overflow-hidden">
        
        <!-- Receipt Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-8 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold">Fee Payment Receipt</h2>
              <p class="text-blue-100 mt-1">{{ payment.receipt_no }}</p>
            </div>
            <div class="text-right">
              <span :class="payment.is_cancelled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'" 
                    class="px-4 py-2 text-sm font-semibold rounded-full">
                {{ payment.is_cancelled ? 'CANCELLED' : 'PAID' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Student Information -->
        <div class="p-8 border-b border-gray-100">
          <div class="flex items-center mb-6">
            <div class="h-10 w-1 bg-blue-600 rounded-full mr-4"></div>
            <h3 class="text-xl font-semibold text-gray-900">Student Information</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <p class="text-sm text-gray-600">Student Name</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.student ? payment.student.full_name : 'N/A' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Admission No</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.student ? payment.student.admission_no : 'N/A' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Branch</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.branch ? payment.branch.name : 'N/A' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Fee Information -->
        <div class="p-8 bg-gray-50 border-b border-gray-100">
          <div class="flex items-center mb-6">
            <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
            <h3 class="text-xl font-semibold text-gray-900">Fee Information</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <p class="text-sm text-gray-600">Fee Type</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.fee && payment.fee.fee_type ? payment.fee.fee_type.name : 'N/A' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Period</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.fee ? formatMonthYear(payment.fee.month, payment.fee.year) : 'N/A' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Total Fee Amount</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                Rs. {{ payment.fee ? Number(payment.fee.net_amount).toLocaleString() : '0' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Payment Details -->
        <div class="p-8 border-b border-gray-100">
          <div class="flex items-center mb-6">
            <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
            <h3 class="text-xl font-semibold text-gray-900">Payment Details</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <p class="text-sm text-gray-600">Payment Date</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ formatDate(payment.payment_date) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Payment Method</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ formatPaymentMethod(payment.payment_method) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">Amount Paid</p>
              <p class="text-2xl font-bold text-green-600 mt-1">
                Rs. {{ Number(payment.amount_paid).toLocaleString() }}
              </p>
            </div>

            <div v-if="payment.transaction_id">
              <p class="text-sm text-gray-600">Transaction ID</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.transaction_id }}
              </p>
            </div>

            <div v-if="payment.cheque_number">
              <p class="text-sm text-gray-600">Cheque Number</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.cheque_number }}
              </p>
            </div>

            <div v-if="payment.bank_name">
              <p class="text-sm text-gray-600">Bank Name</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.bank_name }}
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-600">Collected By</p>
              <p class="text-base font-semibold text-gray-900 mt-1">
                {{ payment.collected_by ? payment.collected_by.name : 'N/A' }}
              </p>
            </div>
          </div>

          <div v-if="payment.notes" class="mt-6">
            <p class="text-sm text-gray-600">Notes</p>
            <p class="text-base text-gray-900 mt-1">{{ payment.notes }}</p>
          </div>
        </div>

        <!-- Cancellation Details (if cancelled) -->
        <div v-if="payment.is_cancelled" class="p-8 bg-red-50 border-b border-red-200">
          <div class="flex items-center mb-6">
            <div class="h-10 w-1 bg-red-600 rounded-full mr-4"></div>
            <h3 class="text-xl font-semibold text-red-900">Cancellation Details</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <p class="text-sm text-red-700">Cancelled Date</p>
              <p class="text-base font-semibold text-red-900 mt-1">
                {{ formatDate(payment.cancelled_at) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-red-700">Cancelled By</p>
              <p class="text-base font-semibold text-red-900 mt-1">
                {{ payment.cancelled_by ? payment.cancelled_by.name : 'N/A' }}
              </p>
            </div>
            <div class="md:col-span-1">
              <p class="text-sm text-red-700">Reason</p>
              <p class="text-base font-semibold text-red-900 mt-1">
                {{ payment.cancellation_reason || 'N/A' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-8 bg-gray-50">
          <div class="flex items-center justify-between text-sm text-gray-600">
            <p>Generated on {{ formatDate(new Date()) }}</p>
            <p>This is a computer-generated receipt</p>
          </div>
        </div>

      </div>

      <!-- Actions (only if not cancelled) -->
      <div v-if="!payment.is_cancelled" class="mt-6 flex justify-end">
        <Button 
          variant="danger" 
          @click="showCancelModal = true"
          class="shadow-md hover:shadow-lg transition-all"
        >
          Cancel Payment
        </Button>
      </div>

      <!-- Cancel Payment Modal -->
      <Modal :show="showCancelModal" @close="showCancelModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-gray-900">Cancel Payment</span>
          </div>
        </template>
        
        <div class="mt-2">
          <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to cancel this payment? This will reverse the payment and update the fee balance.
          </p>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Cancellation Reason <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="cancellationReason"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
              placeholder="Enter reason for cancellation..."
              required
            ></textarea>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <Button 
              variant="secondary" 
              @click="showCancelModal = false"
              class="px-6 shadow-sm hover:shadow-md transition-all text-sm"
            >
              Close
            </Button>
            <Button 
              variant="danger" 
              @click="cancelPayment" 
              :loading="cancelling"
              :disabled="!cancellationReason"
              class="px-6 shadow-md hover:shadow-lg transition-all text-sm"
            >
              <span v-if="!cancelling">Cancel Payment</span>
              <span v-else>Cancelling...</span>
            </Button>
          </div>
        </template>
      </Modal>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Modal from '@/Components/Common/Modal.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  payment: {
    type: Object,
    required: true
  }
})

const showCancelModal = ref(false)
const cancelling = ref(false)
const cancellationReason = ref('')

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatPaymentMethod = (method) => {
  const methods = {
    'cash': 'Cash',
    'cheque': 'Cheque',
    'bank_transfer': 'Bank Transfer',
    'online': 'Online Payment',
    'card': 'Card Payment'
  }
  return methods[method] || method
}

const formatMonthYear = (month, year) => {
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  return `${months[month - 1]} ${year}`
}

const printReceipt = () => {
  window.print()
}

const cancelPayment = () => {
  if (!cancellationReason.value) return
  
  cancelling.value = true
  router.post(route('fee-payments.cancel', props.payment.id), {
    cancellation_reason: cancellationReason.value,
    cancelled_by: 1 // Should be current user ID
  }, {
    onSuccess: () => {
      showCancelModal.value = false
      cancelling.value = false
      cancellationReason.value = ''
    },
    onError: () => {
      cancelling.value = false
    }
  })
}
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #receipt, #receipt * {
    visibility: visible;
  }
  #receipt {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>