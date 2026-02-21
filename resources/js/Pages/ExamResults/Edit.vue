<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20 px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="max-w-5xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
              Edit Exam Result
            </h1>
            <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Update student examination results
            </p>
          </div>
          <Link :href="route('exam-results.index')">
            <Button variant="secondary" class="w-full sm:w-auto px-6 py-3 bg-white/80 backdrop-blur-sm border border-slate-200 hover:bg-slate-50 rounded-xl shadow-lg shadow-slate-200/50 transition-all duration-200">
              <ArrowLeftIcon class="h-5 w-5 mr-2" />
              Back to List
            </Button>
          </Link>
        </div>
      </div>

      <!-- Form Card -->
      <div class="max-w-5xl mx-auto">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
          <form @submit.prevent="submit">
            
            <!-- Exam & Student Information (Read-only) -->
            <div class="p-6 sm:p-8 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-blue-600 to-indigo-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Exam & Student Information</h2>
                </div>
              </div>
              
              <div class="bg-gradient-to-br from-blue-50/50 to-indigo-50/30 rounded-xl p-6 border border-blue-200/50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-xs text-slate-600 font-medium mb-1">Exam</p>
                    <p class="text-base font-bold text-slate-900">{{ result.exam.name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-600 font-medium mb-1">Student</p>
                    <p class="text-base font-bold text-slate-900">{{ result.student.first_name }} {{ result.student.last_name }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-600 font-medium mb-1">Roll Number</p>
                    <p class="text-base font-bold text-slate-900">{{ result.student.roll_number }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-slate-600 font-medium mb-1">Subject</p>
                    <p class="text-base font-bold text-slate-900">{{ result.subject.name }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Marks Information -->
            <div class="p-6 sm:p-8 bg-gradient-to-br from-slate-50/50 to-purple-50/30 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-purple-600 to-pink-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Marks Information</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Input
                    v-model="form.total_marks"
                    type="number"
                    label="Total Marks"
                    placeholder="100"
                    required
                    :error="form.errors.total_marks"
                    @input="calculatePercentage"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.obtained_marks"
                    type="number"
                    step="0.01"
                    label="Obtained Marks"
                    placeholder="85"
                    required
                    :error="form.errors.obtained_marks"
                    @input="calculatePercentage"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.theory_marks"
                    type="number"
                    step="0.01"
                    label="Theory Marks"
                    placeholder="60"
                    :error="form.errors.theory_marks"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.practical_marks"
                    type="number"
                    step="0.01"
                    label="Practical Marks"
                    placeholder="25"
                    :error="form.errors.practical_marks"
                  />
                </div>

                <!-- Calculated Fields Display -->
                <div class="md:col-span-2">
                  <div class="grid grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-xl p-4 border border-blue-200/50">
                      <p class="text-xs text-blue-600 font-medium mb-1">Percentage</p>
                      <p class="text-2xl font-bold text-blue-700">{{ calculatedPercentage }}%</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 rounded-xl p-4 border border-purple-200/50">
                      <p class="text-xs text-purple-600 font-medium mb-1">Grade</p>
                      <p class="text-2xl font-bold text-purple-700">{{ calculatedGrade || '—' }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-xl p-4 border border-emerald-200/50">
                      <p class="text-xs text-emerald-600 font-medium mb-1">Status</p>
                      <p class="text-2xl font-bold" :class="isPass ? 'text-emerald-700' : 'text-red-700'">
                        {{ isPass ? 'Pass' : 'Fail' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Information -->
            <div class="p-6 sm:p-8 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-emerald-600 to-green-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Additional Information</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 gap-6">
                <div class="flex items-center">
                  <label class="flex items-center space-x-3 cursor-pointer group">
                    <input
                      v-model="form.is_absent"
                      type="checkbox"
                      class="w-5 h-5 text-red-600 border-slate-300 rounded focus:ring-2 focus:ring-red-500/20 transition-all"
                    />
                    <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">
                      Mark as Absent
                    </span>
                  </label>
                </div>

                <div>
                  <Textarea
                    v-model="form.remarks"
                    label="Remarks"
                    placeholder="Add any additional notes or comments..."
                    :rows="4"
                    :error="form.errors.remarks"
                  />
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 sm:px-8 py-6 bg-gradient-to-r from-slate-50 to-transparent border-t border-slate-200">
              <div class="flex flex-col sm:flex-row justify-end gap-3">
                <Link :href="route('exam-results.index')">
                  <Button 
                    type="button" 
                    variant="secondary" 
                    class="w-full sm:w-auto px-6 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl shadow-sm transition-all"
                  >
                    Cancel
                  </Button>
                </Link>
                <Button 
                  type="submit" 
                  variant="primary" 
                  :loading="form.processing"
                  class="w-full sm:w-auto px-8 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all"
                >
                  <span v-if="!form.processing" class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Update Result
                  </span>
                  <span v-else>Updating...</span>
                </Button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Textarea from '@/Components/Forms/Textarea.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  result: { type: Object, required: true }
})

const form = useForm({
  theory_marks: props.result.theory_marks || '',
  practical_marks: props.result.practical_marks || '',
  obtained_marks: props.result.obtained_marks,
  total_marks: props.result.total_marks,
  is_absent: props.result.is_absent || false,
  remarks: props.result.remarks || ''
})

const calculatedPercentage = computed(() => {
  if (form.total_marks && form.obtained_marks) {
    return ((parseFloat(form.obtained_marks) / parseFloat(form.total_marks)) * 100).toFixed(2)
  }
  return '0.00'
})

const calculatedGrade = computed(() => {
  const percentage = parseFloat(calculatedPercentage.value)
  if (percentage >= 90) return 'A+'
  if (percentage >= 80) return 'A'
  if (percentage >= 70) return 'B+'
  if (percentage >= 60) return 'B'
  if (percentage >= 50) return 'C+'
  if (percentage >= 40) return 'C'
  if (percentage >= 33) return 'D'
  return 'F'
})

const isPass = computed(() => {
  return parseFloat(calculatedPercentage.value) >= 33
})

const calculatePercentage = () => {
  // Trigger reactivity for computed properties
}

const submit = () => {
  form.put(route('exam-results.update', props.result.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled by Inertia
    },
    onError: (errors) => {
      console.log('Validation errors:', errors)
    }
  })
}
</script>