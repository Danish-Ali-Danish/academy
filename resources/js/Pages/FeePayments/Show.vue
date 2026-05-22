<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print-wrapper">
      
      <!-- Page Header -->
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Payment Receipt</h1>
          <p class="mt-1 text-sm text-gray-600">View payment transaction details</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Button variant="secondary" @click="printReceipt" class="shadow-sm hover:shadow-md transition-shadow">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Receipt
          </Button>
          <Link :href="route('fee-payments.index')">
            <Button variant="secondary" class="shadow-sm hover:shadow-md transition-shadow w-full sm:w-auto">
              <ArrowLeftIcon class="h-5 w-5 mr-2" />
              Back
            </Button>
          </Link>
        </div>
      </div>

      <!-- Receipt Card -->
      <div id="receipt" class="bg-white sm:rounded-2xl shadow-xl border border-gray-100 overflow-hidden relative print-area">
        
        <!-- Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none z-0">
          <svg class="w-3/4 h-3/4 sm:w-96 sm:h-96" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 14l9-5-9-5-9 5 9 5z" />
            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
          </svg>
        </div>

        <div class="relative z-10">
          <!-- Print Academy Header (Shows only on print) -->
          <div class="hidden print-header text-center pt-6 pb-2 border-b-2 border-gray-800 mb-4">
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight uppercase">Academy Management</h1>
            <p class="text-xs text-gray-500 mt-1 font-medium">Official Payment Receipt</p>
          </div>

          <!-- Receipt Header -->
          <div class="bg-blue-600 p-5 sm:p-8 text-white receipt-header flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-center sm:text-left">
              <h2 class="text-xl sm:text-3xl font-bold tracking-wide">FEE RECEIPT</h2>
              <p class="text-blue-100 mt-1 font-mono text-sm tracking-wider">{{ payment.receipt_no }}</p>
            </div>
            <div class="text-center sm:text-right">
              <span :class="payment.is_advance ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-green-100 text-green-800 border-green-200'" 
                    class="px-4 py-1.5 text-xs sm:text-sm font-bold tracking-widest rounded-full border shadow-sm print:shadow-none inline-block uppercase print-status-badge">
                {{ payment.is_advance ? 'ADVANCE' : 'PAID' }}
              </span>
            </div>
          </div>

          <!-- Receipt Body -->
          <div class="p-5 sm:p-8 print:p-4 print-body-content">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:grid-cols-2 print:gap-4 mb-6 print:mb-4">
              <!-- Student Information -->
              <div>
                <div class="flex items-center mb-3">
                  <div class="h-5 w-1.5 bg-blue-600 rounded-full mr-2 print-color-exact"></div>
                  <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Student Details</h3>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 print:bg-transparent print:border-gray-300 print:p-3 h-full">
                  <div class="space-y-3">
                    <div>
                      <p class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</p>
                      <p class="text-sm sm:text-base font-bold text-gray-900">{{ payment.student_enrollment?.student?.student_name || 'N/A' }}</p>
                    </div>
                    <div class="flex justify-between">
                      <div>
                        <p class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No</p>
                        <p class="text-sm sm:text-base font-bold text-gray-900">{{ payment.student_enrollment?.student?.admission_no || 'N/A' }}</p>
                      </div>
                      <div class="text-right">
                        <p class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Branch</p>
                        <p class="text-sm sm:text-base font-bold text-gray-900">{{ payment.student_enrollment?.class_section?.branch_class?.branch?.branch_name || 'N/A' }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Fee Information -->
              <div>
                <div class="flex items-center mb-3">
                  <div class="h-5 w-1.5 bg-green-500 rounded-full mr-2 print-color-exact"></div>
                  <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Fee Details</h3>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 print:bg-transparent print:border-gray-300 print:p-3 h-full">
                  <div class="space-y-3">
                    <div>
                      <p class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</p>
                      <p class="text-sm sm:text-base font-bold text-gray-900">{{ payment.voucher?.fee_type?.fee_name || 'N/A' }}</p>
                    </div>
                    <div class="flex justify-between">
                      <div>
                        <p class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</p>
                        <p class="text-sm sm:text-base font-bold text-gray-900">{{ payment.voucher ? formatMonthYear(payment.voucher.month, payment.voucher.year) : 'N/A' }}</p>
                      </div>
                      <div class="text-right">
                        <p class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Fee</p>
                        <p class="text-sm sm:text-base font-bold text-gray-900">Rs. {{ payment.voucher ? Number(payment.voucher.net_amount || 0).toLocaleString() : '0' }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Details -->
            <div class="mb-4 print:mb-2">
              <div class="flex items-center mb-3">
                <div class="h-5 w-1.5 bg-purple-500 rounded-full mr-2 print-color-exact"></div>
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Transaction Info</h3>
              </div>
              
              <div class="border border-gray-200 rounded-lg overflow-hidden print:border-gray-400">
                <table class="min-w-full divide-y divide-gray-200 print:divide-gray-400 text-sm">
                  <tbody class="divide-y divide-gray-200 print:divide-gray-400 bg-white">
                    <tr>
                      <td class="px-4 py-2 font-semibold text-gray-500 w-1/3 bg-gray-50 print:bg-gray-100 print-color-exact">Payment Date</td>
                      <td class="px-4 py-2 font-bold text-gray-900">{{ formatDate(payment.payment_date) }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-semibold text-gray-500 bg-gray-50 print:bg-gray-100 print-color-exact">Payment Method</td>
                      <td class="px-4 py-2 font-bold text-gray-900">{{ formatPaymentMethod(payment.payment_method) }}</td>
                    </tr>
                    <tr v-if="payment.transaction_ref">
                      <td class="px-4 py-2 font-semibold text-gray-500 bg-gray-50 print:bg-gray-100 print-color-exact">Transaction ID</td>
                      <td class="px-4 py-2 font-bold text-gray-900">{{ payment.transaction_ref }}</td>
                    </tr>
                    <tr v-if="payment.bank_name">
                      <td class="px-4 py-2 font-semibold text-gray-500 bg-gray-50 print:bg-gray-100 print-color-exact">Bank Name</td>
                      <td class="px-4 py-2 font-bold text-gray-900">{{ payment.bank_name }}</td>
                    </tr>
                    <tr>
                      <td class="px-4 py-2 font-semibold text-gray-500 bg-gray-50 print:bg-gray-100 print-color-exact">Collected By</td>
                      <td class="px-4 py-2 font-bold text-gray-900">{{ payment.received_by ? payment.received_by.name : 'N/A' }}</td>
                    </tr>
                    
                    <!-- Financial Summary -->
                    <tr class="border-t-2 border-gray-300 print:border-gray-500">
                      <td class="px-4 py-3 font-bold text-gray-900 bg-gray-100 print-color-exact">AMOUNT PAID</td>
                      <td class="px-4 py-3 text-lg font-black text-green-600 bg-green-50 print-color-exact">Rs. {{ Number(payment.paid_amount || 0).toLocaleString() }}</td>
                    </tr>
                    <tr class="border-t border-gray-200">
                      <td class="px-4 py-2 font-bold text-gray-700 bg-gray-50 print-color-exact">REMAINING BALANCE</td>
                      <td class="px-4 py-2 text-base font-bold text-red-600 bg-red-50/50 print-color-exact">Rs. {{ payment.voucher ? Number(payment.voucher.remaining_amount || 0).toLocaleString() : '0' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="payment.notes" class="mt-3 bg-yellow-50 border border-yellow-100 rounded-md p-3 print:bg-transparent print:border-gray-300">
                <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1">Notes</p>
                <p class="text-xs font-medium text-gray-800">{{ payment.notes }}</p>
              </div>
            </div>
            
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 p-4 border-t border-gray-200 print:bg-transparent print:border-gray-400 print:mt-4">
            <div class="flex flex-col sm:flex-row items-center justify-between text-[10px] sm:text-xs text-gray-500 font-medium">
              <p>Generated on {{ formatDateTime(new Date()) }}</p>
              <p class="mt-1 sm:mt-0">This is a computer-generated document.</p>
            </div>
          </div>
        </div>

      </div>

      <!-- Actions -->
      <div class="mt-6 flex justify-end print:hidden">
        <Button 
          variant="danger" 
          @click="showDeleteModal = true"
          class="shadow-md hover:shadow-lg transition-all"
        >
          Delete Payment
        </Button>
      </div>

      <!-- Delete Payment Modal -->
      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-gray-900">Delete Payment</span>
          </div>
        </template>
        
        <div class="mt-2">
          <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to delete this payment? This will reverse the paid amount and update the voucher balance.
          </p>
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <Button 
              variant="secondary" 
              @click="showDeleteModal = false"
              class="px-6 shadow-sm hover:shadow-md transition-all text-sm"
            >
              Close
            </Button>
            <Button 
              variant="danger" 
              @click="deletePayment" 
              :loading="deleting"
              class="px-6 shadow-md hover:shadow-lg transition-all text-sm"
            >
              <span v-if="!deleting">Delete Payment</span>
              <span v-else>Deleting...</span>
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

const showDeleteModal = ref(false)
const deleting = ref(false)

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' })
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

const deletePayment = () => {
  deleting.value = true
  router.delete(route('fee-payments.destroy', props.payment.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      deleting.value = false
      router.visit(route('fee-payments.index'))
    },
    onError: () => {
      deleting.value = false
    }
  })
}
</script>

<style>
@media print {
  @page {
    margin: 5mm; /* Small margins to maximize space */
    size: auto;
  }
  
  body {
    background-color: white !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* Hide absolutely everything initially */
  body * {
    visibility: hidden;
  }
  
  /* Show only the receipt container and its children */
  #receipt, #receipt * {
    visibility: visible;
  }
  
  /* Extract receipt from document flow to ensure single page */
  #receipt {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    max-width: 100vw;
    margin: 0;
    padding: 0;
    border: none !important;
    box-shadow: none !important;
    page-break-inside: avoid; /* Prevent breaking across pages */
  }

  /* Force specific elements to look exact */
  .print-color-exact {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  
  .receipt-header {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    background-color: #2563eb !important;
    color: white !important;
    padding: 12px 16px !important;
  }

  .print-header {
    display: block !important;
  }

  .print-status-badge {
    border: 1px solid white !important;
    color: white !important;
    background: transparent !important;
  }
}
</style>
