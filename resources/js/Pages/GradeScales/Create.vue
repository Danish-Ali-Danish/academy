<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20 px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="max-w-5xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
              Create Grade Scale
            </h1>
            <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
              Add a new grading scale to your system
            </p>
          </div>
          <Link :href="route('grade-scales.index')">
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
            
            <!-- Basic Information -->
            <div class="p-6 sm:p-8 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-blue-600 to-indigo-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Basic Information</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Input
                    v-model="form.name"
                    label="Scale Name"
                    placeholder="e.g., A+ Grade"
                    required
                    :error="form.errors.name"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Branch
                  </label>
                  <select
                    v-model="form.branch_id"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.branch_id }"
                  >
                    <option value="">All Branches</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                      {{ branch.name }}
                    </option>
                  </select>
                  <p v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.branch_id }}
                  </p>
                  <p class="mt-1 text-xs text-slate-500">Leave empty to apply to all branches</p>
                </div>
              </div>
            </div>

            <!-- Grade Details -->
            <div class="p-6 sm:p-8 bg-gradient-to-br from-slate-50/50 to-purple-50/30 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-purple-600 to-pink-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Grade Details</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Input
                    v-model="form.min_percentage"
                    type="number"
                    step="0.01"
                    label="Minimum Percentage"
                    placeholder="90.00"
                    required
                    :error="form.errors.min_percentage"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.max_percentage"
                    type="number"
                    step="0.01"
                    label="Maximum Percentage"
                    placeholder="100.00"
                    required
                    :error="form.errors.max_percentage"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.grade"
                    label="Grade"
                    placeholder="A+"
                    required
                    :error="form.errors.grade"
                    maxlength="3"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.grade_point"
                    type="number"
                    step="0.01"
                    label="Grade Point"
                    placeholder="4.00"
                    required
                    :error="form.errors.grade_point"
                  />
                </div>

                <!-- Preview Card -->
                <div class="md:col-span-2">
                  <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200/50">
                    <p class="text-xs text-slate-600 font-medium mb-3 uppercase tracking-wide">Preview</p>
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-4">
                        <div :class="getGradeColorClass(form.grade)" class="px-6 py-3 rounded-xl shadow-md">
                          <div class="text-3xl font-bold">{{ form.grade || '—' }}</div>
                          <div class="text-xs mt-1 opacity-90">Point: {{ form.grade_point || '—' }}</div>
                        </div>
                        <div class="text-left">
                          <p class="text-sm text-slate-600 mb-1">Percentage Range</p>
                          <p class="text-2xl font-bold text-slate-900">
                            {{ form.min_percentage || '0' }}% - {{ form.max_percentage || '0' }}%
                          </p>
                        </div>
                      </div>
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Additional Information</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Status <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.status"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.status }"
                    required
                  >
                    <option value="">Select Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                  <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                    {{ form.errors.status }}
                  </p>
                </div>

                <div class="md:col-span-2">
                  <Textarea
                    v-model="form.remarks"
                    label="Remarks"
                    placeholder="Add any additional notes about this grade scale..."
                    :rows="4"
                    :error="form.errors.remarks"
                  />
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 sm:px-8 py-6 bg-gradient-to-r from-slate-50 to-transparent border-t border-slate-200">
              <div class="flex flex-col sm:flex-row justify-end gap-3">
                <Link :href="route('grade-scales.index')">
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
                    Create Grade Scale
                  </span>
                  <span v-else>Creating...</span>
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
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Textarea from '@/Components/Forms/Textarea.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  branches: { type: Array, default: () => [] }
})

const form = useForm({
  branch_id: '',
  name: '',
  min_percentage: '',
  max_percentage: '',
  grade: '',
  grade_point: '',
  remarks: '',
  status: 'active'
})

const getGradeColorClass = (grade) => {
  const classes = {
    'A+': 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white',
    'A': 'bg-gradient-to-br from-green-500 to-green-600 text-white',
    'B+': 'bg-gradient-to-br from-blue-500 to-blue-600 text-white',
    'B': 'bg-gradient-to-br from-cyan-500 to-cyan-600 text-white',
    'C+': 'bg-gradient-to-br from-yellow-500 to-yellow-600 text-white',
    'C': 'bg-gradient-to-br from-orange-500 to-orange-600 text-white',
    'D': 'bg-gradient-to-br from-red-400 to-red-500 text-white',
    'F': 'bg-gradient-to-br from-red-600 to-red-700 text-white'
  }
  return classes[grade] || 'bg-gradient-to-br from-slate-500 to-slate-600 text-white'
}

const submit = () => {
  form.post(route('grade-scales.store'), {
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