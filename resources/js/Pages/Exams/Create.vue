<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20 px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="max-w-6xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
              Create New Exam
            </h1>
            <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              Schedule a new examination for students
            </p>
          </div>
          <Link :href="route('exams.index')">
            <Button variant="secondary" class="w-full sm:w-auto px-6 py-3 bg-white/80 backdrop-blur-sm border border-slate-200 hover:bg-slate-50 rounded-xl shadow-lg shadow-slate-200/50 transition-all duration-200">
              <ArrowLeftIcon class="h-5 w-5 mr-2" />
              Back to List
            </Button>
          </Link>
        </div>
      </div>

      <!-- Form Card -->
      <div class="max-w-6xl mx-auto">
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
                    label="Exam Name"
                    placeholder="e.g., Mid Term Examination"
                    required
                    :error="form.errors.name"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.exam_code"
                    label="Exam Code"
                    placeholder="e.g., MID-2024-01"
                    required
                    :error="form.errors.exam_code"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Branch <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.branch_id"
                    @change="loadClasses"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.branch_id }"
                    required
                  >
                    <option value="">Select Branch</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                      {{ branch.name }}
                    </option>
                  </select>
                  <p v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.branch_id }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Class <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.class_id"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.class_id }"
                    required
                  >
                    <option value="">Select Class</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                      {{ cls.name }}
                    </option>
                  </select>
                  <p v-if="form.errors.class_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.class_id }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Exam Type <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.exam_type"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.exam_type }"
                    required
                  >
                    <option value="">Select Exam Type</option>
                    <option value="mid_term">Mid Term</option>
                    <option value="final">Final</option>
                    <option value="quiz">Quiz</option>
                    <option value="monthly">Monthly Test</option>
                    <option value="annual">Annual</option>
                  </select>
                  <p v-if="form.errors.exam_type" class="mt-1 text-sm text-red-600">
                    {{ form.errors.exam_type }}
                  </p>
                </div>

                <div>
                  <Input
                    v-model="form.academic_year"
                    label="Academic Year"
                    placeholder="2024-2025"
                    required
                    :error="form.errors.academic_year"
                  />
                </div>

                <div class="md:col-span-2">
                  <Textarea
                    v-model="form.description"
                    label="Description"
                    placeholder="Enter exam description and instructions..."
                    :rows="3"
                    :error="form.errors.description"
                  />
                </div>
              </div>
            </div>

            <!-- Schedule Information -->
            <div class="p-6 sm:p-8 bg-gradient-to-br from-slate-50/50 to-purple-50/30 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-purple-600 to-pink-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Schedule Information</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <Input
                    v-model="form.start_date"
                    type="date"
                    label="Start Date"
                    required
                    :error="form.errors.start_date"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.end_date"
                    type="date"
                    label="End Date"
                    required
                    :error="form.errors.end_date"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.result_date"
                    type="date"
                    label="Result Date"
                    :error="form.errors.result_date"
                  />
                </div>
              </div>
            </div>

            <!-- Exam Configuration -->
            <div class="p-6 sm:p-8 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-emerald-600 to-green-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Exam Configuration</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Input
                    v-model="form.total_marks"
                    type="number"
                    label="Total Marks"
                    placeholder="500"
                    required
                    :error="form.errors.total_marks"
                  />
                </div>

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
                    <option value="scheduled">Scheduled</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                  <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                    {{ form.errors.status }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 sm:px-8 py-6 bg-gradient-to-r from-slate-50 to-transparent border-t border-slate-200">
              <div class="flex flex-col sm:flex-row justify-end gap-3">
                <Link :href="route('exams.index')">
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
                    Create Exam
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
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Textarea from '@/Components/Forms/Textarea.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  branches: { type: Array, default: () => [] }
})

const classes = ref([])

const form = useForm({
  branch_id: '',
  name: '',
  exam_code: '',
  exam_type: '',
  class_id: '',
  academic_year: '',
  start_date: '',
  end_date: '',
  result_date: '',
  total_marks: '',
  description: '',
  status: 'scheduled'
})

const loadClasses = async () => {
  if (!form.branch_id) return
  try {
    const response = await axios.get(route('api.classes.by-branch', form.branch_id))
    classes.value = response.data
    form.class_id = ''
  } catch (error) {
    console.error('Error loading classes:', error)
  }
}

const submit = () => {
  form.post(route('exams.store'), {
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