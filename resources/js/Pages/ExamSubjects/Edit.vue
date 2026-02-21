<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20 px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="max-w-6xl mx-auto mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
              Edit Exam Schedule
            </h1>
            <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Update subject schedule {{ exam ? 'for ' + exam.name : '' }}
            </p>
          </div>
          <Link :href="route('exams.index')">
            <Button variant="secondary" class="w-full sm:w-auto px-6 py-3 bg-white/80 backdrop-blur-sm border border-slate-200 hover:bg-slate-50 rounded-xl shadow-lg shadow-slate-200/50 transition-all duration-200">
              <ArrowLeftIcon class="h-5 w-5 mr-2" />
              Back to Exams
            </Button>
          </Link>
        </div>
      </div>

      <!-- Exam Info Card -->
      <div v-if="exam" class="max-w-6xl mx-auto mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-xl shadow-blue-500/30 border border-blue-400 p-6 text-white">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-2xl font-bold">{{ exam.name }}</h2>
                <p class="text-blue-100 mt-1">{{ exam.exam_code }} • {{ exam.exam_type }}</p>
              </div>
            </div>
            <div class="flex gap-4 text-sm">
              <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
                <p class="text-blue-100 text-xs">Duration</p>
                <p class="font-semibold">{{ formatDate(exam.start_date) }} - {{ formatDate(exam.end_date) }}</p>
              </div>
              <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
                <p class="text-blue-100 text-xs">Total Marks</p>
                <p class="font-semibold text-xl">{{ exam.total_marks }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Subjects Schedule Form -->
      <div class="max-w-6xl mx-auto">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
          
          <!-- Header -->
          <div class="p-6 sm:p-8 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                  <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                </div>
                <h2 class="text-xl font-semibold text-slate-900">Scheduled Subjects ({{ subjects.length }})</h2>
              </div>
              <Button 
                @click="addSubject" 
                variant="primary"
                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-lg shadow-blue-500/30 transition-all text-sm"
              >
                <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Subject
              </Button>
            </div>
          </div>

          <!-- Subjects List -->
          <div class="p-6 sm:p-8">
            <div v-if="subjects.length === 0" class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
              </svg>
              <p class="mt-2 text-sm font-medium text-slate-500">No subjects scheduled yet</p>
              <p class="mt-1 text-xs text-slate-400">Click "Add Subject" to schedule exam subjects</p>
            </div>

            <div v-else class="space-y-4">
              <div 
                v-for="(subject, index) in subjects" 
                :key="subject.id || index"
                class="bg-gradient-to-br from-slate-50/50 to-blue-50/30 rounded-2xl border border-slate-200 p-6 hover:shadow-lg transition-all"
              >
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center font-bold text-blue-600">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="font-semibold text-slate-900">
                        {{ getSubjectName(subject.subject_id) || 'Subject ' + (index + 1) }}
                      </h3>
                      <p class="text-xs text-slate-500">Configure subject details and schedule</p>
                    </div>
                  </div>
                  <button 
                    @click="removeSubject(index)"
                    type="button"
                    class="text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg p-2 transition-all"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div class="lg:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                      Subject <span class="text-red-500">*</span>
                    </label>
                    <select
                      v-model="subject.subject_id"
                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                      required
                    >
                      <option value="">Select Subject</option>
                      <option v-for="subj in availableSubjects" :key="subj.id" :value="subj.id">
                        {{ subj.name }} - {{ subj.code }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <Input
                      v-model="subject.exam_date"
                      type="date"
                      label="Exam Date"
                      required
                    />
                  </div>

                  <div>
                    <Input
                      v-model="subject.exam_time"
                      type="time"
                      label="Exam Time"
                      required
                    />
                  </div>

                  <div>
                    <Input
                      v-model="subject.duration_minutes"
                      type="number"
                      label="Duration (minutes)"
                      placeholder="180"
                      required
                    />
                  </div>

                  <div>
                    <Input
                      v-model="subject.total_marks"
                      type="number"
                      label="Total Marks"
                      placeholder="100"
                      required
                    />
                  </div>

                  <div>
                    <Input
                      v-model="subject.theory_marks"
                      type="number"
                      label="Theory Marks"
                      placeholder="70"
                    />
                  </div>

                  <div>
                    <Input
                      v-model="subject.practical_marks"
                      type="number"
                      label="Practical Marks"
                      placeholder="30"
                    />
                  </div>

                  <div>
                    <Input
                      v-model="subject.pass_marks"
                      type="number"
                      label="Pass Marks"
                      placeholder="33"
                      required
                    />
                  </div>
                </div>
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
                @click="submit" 
                variant="primary" 
                :loading="processing"
                :disabled="subjects.length === 0"
                class="w-full sm:w-auto px-8 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="!processing" class="flex items-center justify-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  Update Schedule
                </span>
                <span v-else>Updating...</span>
              </Button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  exam: { type: Object, default: null },
  examSubjects: { type: Array, default: () => [] },
  availableSubjects: { type: Array, default: () => [] }
})

const subjects = ref([])
const processing = ref(false)

// Initialize subjects from existing exam subjects
if (props.examSubjects && props.examSubjects.length > 0) {
  subjects.value = props.examSubjects.map(es => ({
    id: es.id,
    subject_id: es.subject_id,
    exam_date: es.exam_date,
    exam_time: es.exam_time ? es.exam_time.substring(0, 5) : '', // Convert to HH:MM format
    duration_minutes: es.duration_minutes,
    total_marks: es.total_marks,
    theory_marks: es.theory_marks || '',
    practical_marks: es.practical_marks || '',
    pass_marks: es.pass_marks
  }))
}

const addSubject = () => {
  subjects.value.push({
    id: null,
    subject_id: '',
    exam_date: '',
    exam_time: '',
    duration_minutes: '',
    total_marks: '',
    theory_marks: '',
    practical_marks: '',
    pass_marks: ''
  })
}

const removeSubject = (index) => {
  subjects.value.splice(index, 1)
}

const getSubjectName = (subjectId) => {
  const subject = props.availableSubjects.find(s => s.id === parseInt(subjectId))
  return subject ? subject.name : ''
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const submit = () => {
  if (!props.exam || !props.exam.id) {
    console.error('Exam data is missing')
    return
  }
  
  processing.value = true
  router.put(route('exam-subjects.update', props.exam.id), {
    subjects: subjects.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      processing.value = false
    },
    onError: (errors) => {
      processing.value = false
      console.log('Validation errors:', errors)
    }
  })
}
</script>