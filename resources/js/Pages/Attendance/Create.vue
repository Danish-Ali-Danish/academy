<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20 px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="max-w-5xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
              Mark Attendance
            </h1>
            <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              Record student attendance for today
            </p>
          </div>
          <Link :href="route('attendance.index')">
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
            
            <!-- Student Information Section -->
            <div class="p-6 sm:p-8 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-blue-600 to-indigo-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Student Information</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    @change="loadSections"
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
                    Section <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.section_id"
                    @change="loadStudents"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.section_id }"
                    required
                  >
                    <option value="">Select Section</option>
                    <option v-for="section in sections" :key="section.id" :value="section.id">
                      {{ section.name }}
                    </option>
                  </select>
                  <p v-if="form.errors.section_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.section_id }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Student <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.student_id"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500/20': form.errors.student_id }"
                    required
                  >
                    <option value="">Select Student</option>
                    <option v-for="student in students" :key="student.id" :value="student.id">
                      {{ student.first_name }} {{ student.last_name }} - {{ student.roll_number }}
                    </option>
                  </select>
                  <p v-if="form.errors.student_id" class="mt-1 text-sm text-red-600">
                    {{ form.errors.student_id }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Attendance Details Section -->
            <div class="p-6 sm:p-8 bg-gradient-to-br from-slate-50/50 to-blue-50/30 border-b border-slate-100">
              <div class="flex items-center mb-6">
                <div class="h-10 w-1.5 bg-gradient-to-b from-green-600 to-emerald-600 rounded-full mr-4"></div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <h2 class="text-xl font-semibold text-slate-900">Attendance Details</h2>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Input
                    v-model="form.date"
                    type="date"
                    label="Attendance Date"
                    required
                    :error="form.errors.date"
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
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="leave">Leave</option>
                  </select>
                  <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                    {{ form.errors.status }}
                  </p>
                </div>

                <div>
                  <Input
                    v-model="form.check_in_time"
                    type="time"
                    label="Check In Time"
                    :error="form.errors.check_in_time"
                  />
                </div>

                <div>
                  <Input
                    v-model="form.check_out_time"
                    type="time"
                    label="Check Out Time"
                    :error="form.errors.check_out_time"
                  />
                </div>

                <div v-if="form.status === 'leave'">
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Leave Type
                  </label>
                  <select
                    v-model="form.leave_type"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                  >
                    <option value="">Select Leave Type</option>
                    <option value="sick">Sick Leave</option>
                    <option value="casual">Casual Leave</option>
                    <option value="emergency">Emergency</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div :class="form.status === 'leave' ? '' : 'md:col-span-2'">
                  <Textarea
                    v-model="form.remarks"
                    label="Remarks"
                    placeholder="Add any additional notes..."
                    :rows="3"
                    :error="form.errors.remarks"
                  />
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 sm:px-8 py-6 bg-gradient-to-r from-slate-50 to-transparent border-t border-slate-200">
              <div class="flex flex-col sm:flex-row justify-end gap-3">
                <Link :href="route('attendance.index')">
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
                    Mark Attendance
                  </span>
                  <span v-else>Processing...</span>
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
const sections = ref([])
const students = ref([])

const form = useForm({
  student_id: '',
  class_id: '',
  section_id: '',
  branch_id: '',
  date: new Date().toISOString().split('T')[0],
  check_in_time: '',
  check_out_time: '',
  status: '',
  leave_type: '',
  remarks: ''
})

const loadClasses = async () => {
  if (!form.branch_id) return
  try {
    const response = await axios.get(route('api.classes.by-branch', form.branch_id))
    classes.value = response.data
    form.class_id = ''
    form.section_id = ''
    form.student_id = ''
    sections.value = []
    students.value = []
  } catch (error) {
    console.error('Error loading classes:', error)
  }
}

const loadSections = async () => {
  if (!form.class_id) return
  try {
    const response = await axios.get(route('api.sections.by-class', form.class_id))
    sections.value = response.data
    form.section_id = ''
    form.student_id = ''
    students.value = []
  } catch (error) {
    console.error('Error loading sections:', error)
  }
}

const loadStudents = async () => {
  if (!form.section_id) return
  try {
    const response = await axios.get(route('api.students.by-section', form.section_id))
    students.value = response.data
    form.student_id = ''
  } catch (error) {
    console.error('Error loading students:', error)
  }
}

const submit = () => {
  form.post(route('attendance.store'), {
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