<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Installment Assignment</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Update installment assignment details</p>
            </div>
            <Button @click="$inertia.visit(route('student-installment-assignments.index'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">

              <!-- Student (read-only) -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Student</label>
                <StudentRollSearch 
                  v-model="selectedStudentId" 
                  :initial-student="selectedStudent"
                  disabled
                />
              </div>

              <!-- Enrollment (read-only) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Enrollment</label>
                <select disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm bg-gray-100 cursor-not-allowed">
                  <option v-for="e in initialEnrollments" :key="e.id" :value="e.id" :selected="e.id == assignment.student_enrollment_id">
                    {{ e.class_name }} – {{ e.section_name }} ({{ e.academic_year }})
                  </option>
                </select>
              </div>

              <!-- Installment Plan (read-only) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Installment Plan</label>
                <select disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm bg-gray-100 cursor-not-allowed">
                  <option v-for="p in installmentPlans" :key="p.id" :value="p.id" :selected="p.id == assignment.installment_plan_id">
                    {{ p.plan_name }} ({{ p.total_installments }} kistain) {{ p.fee_type ? '— ' + p.fee_type.fee_name : '' }}
                  </option>
                </select>
              </div>

              <!-- Amount Summary -->
              <div class="md:col-span-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div class="bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3 text-center">
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Total Amount</p>
                    <p class="text-xl font-bold text-indigo-900">Rs {{ Number(assignment.total_amount).toLocaleString() }}</p>
                  </div>
                  <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-center">
                    <p class="text-xs font-semibold text-green-600 uppercase tracking-wider">Paid</p>
                    <p class="text-xl font-bold text-green-900">Rs {{ Number(assignment.amount_paid || 0).toLocaleString() }}</p>
                  </div>
                  <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-center">
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wider">Remaining</p>
                    <p class="text-xl font-bold text-red-900">Rs {{ Number(assignment.remaining_amount || 0).toLocaleString() }}</p>
                  </div>
                </div>
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
              <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea id="notes" v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Additional notes..."></textarea>
              </div>

            </div>

            <!-- ========== EXISTING SCHEDULE TABLE ========== -->
            <div v-if="existingSchedule && existingSchedule.length > 0" class="mt-6">
              <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl overflow-hidden">
                <div class="px-4 sm:px-6 py-3 border-b border-indigo-200">
                  <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Installment Schedule ({{ existingSchedule.length }} Kists)
                  </h3>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full">
                    <thead>
                      <tr class="bg-indigo-100/60">
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Kist #</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Amount</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Due Date</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Paid</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Payment Date</th>
                        <th class="px-4 py-2.5 text-xs font-semibold text-indigo-800 text-center">Status</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100">
                      <tr v-for="kist in existingSchedule" :key="kist.kist_number" class="hover:bg-indigo-50/50 transition-colors">
                        <td class="px-4 py-2.5 text-sm text-center">
                          <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-200 text-indigo-800 text-xs font-bold">{{ kist.kist_number }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-sm text-center font-semibold text-gray-900">Rs {{ Number(kist.kist_amount).toLocaleString() }}</td>
                        <td class="px-4 py-2.5 text-sm text-center text-gray-700">{{ formatDate(kist.due_date) }}</td>
                        <td class="px-4 py-2.5 text-sm text-center" :class="kist.paid_amount > 0 ? 'font-semibold text-green-700' : 'text-gray-400'">
                          {{ kist.paid_amount ? 'Rs ' + Number(kist.paid_amount).toLocaleString() : '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-sm text-center text-gray-700">{{ kist.payment_date ? formatDate(kist.payment_date) : '—' }}</td>
                        <td class="px-4 py-2.5 text-sm text-center">
                          <span :class="getKistStatusClass(kist.status)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">{{ kist.status }}</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('student-installment-assignments.index'))" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm">
                <span v-if="!form.processing">Update Assignment</span>
                <span v-else>Updating...</span>
              </Button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import StudentRollSearch from '@/Components/Common/StudentRollSearch.vue'

const props = defineProps({
  assignment: { type: Object, required: true },
  installmentPlans: Array,
  initialEnrollments: { type: Array, default: () => [] },
  selectedStudent: { type: Object, default: null },
  existingSchedule: { type: Array, default: () => [] },
})

const selectedStudentId = ref(props.assignment.student_id ?? '')

const form = useForm({
  total_amount: props.assignment.total_amount,
  status: props.assignment.status,
  notes: props.assignment.notes || '',
})

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getKistStatusClass = (status) => {
  return {
    'pending': 'bg-amber-100 text-amber-800',
    'paid': 'bg-green-100 text-green-800',
    'overdue': 'bg-red-100 text-red-800',
    'partial': 'bg-blue-100 text-blue-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

const submit = () => {
  form.put(route('student-installment-assignments.update', props.assignment.id), { preserveScroll: true })
}
</script>
