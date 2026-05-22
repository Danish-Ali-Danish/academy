<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Cheque Record</h1>
            <p class="mt-1 text-sm text-gray-500">Update cheque details and status</p>
          </div>
          <Button @click="$inertia.visit(route('cheque-tracking.index'))" variant="secondary" class="w-full sm:w-auto text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
          </Button>
        </div>

        <div class="max-w-3xl space-y-4">

          <!-- STEP 1: Student Search (Readonly/Prefilled) -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
              <h2 class="font-semibold text-gray-900">Student Enrollment</h2>
            </div>

            <!-- Pre-selected Student Card -->
            <div class="mt-3 flex items-center gap-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg opacity-80 cursor-not-allowed">
              <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold">{{ studentName.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-indigo-900">{{ studentName }}</p>
                <p class="text-xs text-indigo-600">Admission No: {{ admissionNo }}</p>
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-2 italic">Student cannot be changed once the cheque record is created.</p>
          </div>

          <!-- STEP 2: Cheque Details -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-5">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
              <h2 class="font-semibold text-gray-900">Cheque Details</h2>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Number <span class="text-red-500">*</span></label>
                  <input v-model="form.cheque_no" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.cheque_no}" />
                  <p v-if="form.errors.cheque_no" class="mt-1 text-xs text-red-600">{{ form.errors.cheque_no }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Date <span class="text-red-500">*</span></label>
                  <input v-model="form.cheque_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.cheque_date}" />
                  <p v-if="form.errors.cheque_date" class="mt-1 text-xs text-red-600">{{ form.errors.cheque_date }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name <span class="text-red-500">*</span></label>
                  <input v-model="form.bank_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.bank_name}" />
                  <p v-if="form.errors.bank_name" class="mt-1 text-xs text-red-600">{{ form.errors.bank_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                  <input v-model="form.branch_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.branch_name}" />
                  <p v-if="form.errors.branch_name" class="mt-1 text-xs text-red-600">{{ form.errors.branch_name }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Account Title</label>
                  <input v-model="form.account_title" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.account_title}" />
                  <p v-if="form.errors.account_title" class="mt-1 text-xs text-red-600">{{ form.errors.account_title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Amount (Rs.) <span class="text-red-500">*</span></label>
                  <input v-model="form.amount" type="number" step="0.01" min="0.01" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.amount}" />
                  <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Received Date <span class="text-red-500">*</span></label>
                  <input v-model="form.received_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.received_date}" />
                  <p v-if="form.errors.received_date" class="mt-1 text-xs text-red-600">{{ form.errors.received_date }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Expected Clearance Date</label>
                  <input v-model="form.expected_clearance_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.expected_clearance_date}" />
                  <p v-if="form.errors.expected_clearance_date" class="mt-1 text-xs text-red-600">{{ form.errors.expected_clearance_date }}</p>
                </div>
              </div>

              <!-- Status Selection -->
              <div class="pt-4 mt-2 border-t border-gray-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select v-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.status}">
                      <option value="Pending">Pending</option>
                      <option value="Cleared">Cleared</option>
                      <option value="Bounced">Bounced</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                  </div>
                </div>
              </div>

              <!-- Status Conditional Fields -->
              <div v-if="form.status === 'Cleared'" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-green-800 mb-1">Cleared On Date <span class="text-red-500">*</span></label>
                    <input v-model="form.cleared_on" type="date" class="block w-full rounded-lg border-green-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm" :class="{'border-red-500': form.errors.cleared_on}" />
                    <p v-if="form.errors.cleared_on" class="mt-1 text-xs text-red-600">{{ form.errors.cleared_on }}</p>
                  </div>
                </div>
              </div>

              <div v-if="form.status === 'Bounced'" class="p-4 bg-red-50 border border-red-200 rounded-lg space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-red-800 mb-1">Bounced On Date <span class="text-red-500">*</span></label>
                    <input v-model="form.bounced_on" type="date" class="block w-full rounded-lg border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" :class="{'border-red-500': form.errors.bounced_on}" />
                    <p v-if="form.errors.bounced_on" class="mt-1 text-xs text-red-600">{{ form.errors.bounced_on }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-red-800 mb-1">Bounce Reason <span class="text-red-500">*</span></label>
                    <select v-model="form.bounce_reason" class="block w-full rounded-lg border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" :class="{'border-red-500': form.errors.bounce_reason}">
                      <option value="">Select Reason</option>
                      <option value="Insufficient Funds">Insufficient Funds</option>
                      <option value="Signature Mismatch">Signature Mismatch</option>
                      <option value="Post Dated">Post Dated</option>
                      <option value="Stale Date">Stale Date</option>
                      <option value="Account Closed">Account Closed</option>
                      <option value="Other">Other</option>
                    </select>
                    <p v-if="form.errors.bounce_reason" class="mt-1 text-xs text-red-600">{{ form.errors.bounce_reason }}</p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-red-800 mb-1">Bounce Reason Detail</label>
                  <textarea v-model="form.bounce_reason_detail" rows="2" class="block w-full rounded-lg border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" :class="{'border-red-500': form.errors.bounce_reason_detail}" placeholder="Provide additional details if needed..."></textarea>
                  <p v-if="form.errors.bounce_reason_detail" class="mt-1 text-xs text-red-600">{{ form.errors.bounce_reason_detail }}</p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Remarks</label>
                <textarea v-model="form.notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.notes}"></textarea>
                <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
              </div>

              <div class="pt-4 border-t border-gray-100 flex justify-end">
                <Button type="submit" variant="primary" :disabled="form.processing" class="w-full sm:w-auto px-6 py-2.5">
                  <span v-if="form.processing">Updating...</span>
                  <span v-else>Update Cheque Record</span>
                </Button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

const props = defineProps({
  cheque: {
    type: Object,
    required: true
  }
})

// Extract Student Info
const studentName = computed(() => {
  return props.cheque.student_name ?? 'Unknown Student'
})
const admissionNo = computed(() => {
  return props.cheque.admission_no ?? 'N/A'
})

const form = useForm({
  student_enrollment_id: props.cheque.student_enrollment_id,
  cheque_no: props.cheque.cheque_no,
  cheque_date: props.cheque.cheque_date,
  bank_name: props.cheque.bank_name,
  branch_name: props.cheque.branch_name ?? '',
  account_title: props.cheque.account_title ?? '',
  amount: props.cheque.amount,
  received_date: props.cheque.received_date,
  expected_clearance_date: props.cheque.expected_clearance_date ?? '',
  status: props.cheque.status,
  cleared_on: props.cheque.cleared_on ?? '',
  bounced_on: props.cheque.bounced_on ?? '',
  bounce_reason: props.cheque.bounce_reason ?? '',
  bounce_reason_detail: props.cheque.bounce_reason_detail ?? '',
  notes: props.cheque.notes ?? ''
})

const submit = () => {
  form.put(route('cheque-tracking.update', props.cheque.id), {
    preserveScroll: true
  })
}
</script>
