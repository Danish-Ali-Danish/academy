<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex items-center gap-3 mb-3">
            <button 
              @click="router.visit(route('class-subjects.index'))"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Subject Assignment</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Update subject or group assignment for class
              </p>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="showSuccessMessage && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative animate-fade-in">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm">{{ $page.props.flash.success }}</span>
            </div>
            <button @click="showSuccessMessage = false" class="text-green-700 hover:text-green-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <div v-if="showErrorMessage && $page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative animate-fade-in">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm">{{ $page.props.flash.error }}</span>
            </div>
            <button @click="showErrorMessage = false" class="text-red-700 hover:text-red-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Current Assignment Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
              <h3 class="text-sm font-semibold text-blue-900 mb-1">Current Assignment</h3>
              <div class="text-xs sm:text-sm text-blue-800 space-y-1">
                <p><strong>Branch:</strong> {{ classSubject.branch_class.branch.branch_name }}</p>
                <p><strong>Class:</strong> {{ classSubject.branch_class.class.class_name }}</p>
                <p><strong>Type:</strong> {{ classSubject.assignment_type === 'individual' ? 'Individual Subject' : 'Subject Group' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <!-- Branch & Class (Read-only) -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-400 font-semibold text-sm mr-3">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <h3 class="text-base font-medium text-gray-900">Branch & Class (Locked)</h3>
              </div>
              
              <div class="pl-11 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                  <div class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                    {{ classSubject.branch_class.branch.branch_name }}
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                  <div class="block w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                    {{ classSubject.branch_class.class.class_name }}
                  </div>
                </div>
              </div>
              <p class="mt-2 pl-11 text-xs text-gray-500">
                Branch and class cannot be changed in edit mode
              </p>
            </div>

            <!-- Assignment Type Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  1
                </div>
                <h3 class="text-base font-medium text-gray-900">Change Assignment Type</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Assignment Type <span class="text-red-500">*</span>
                </label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Individual Subjects Option -->
                  <label
                    class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="form.assignment_type === 'individual' 
                      ? 'border-indigo-500 bg-indigo-50' 
                      : 'border-gray-200 hover:border-gray-300 bg-white'"
                  >
                    <input
                      type="radio"
                      value="individual"
                      v-model="form.assignment_type"
                      class="sr-only"
                    />
                    <div class="flex items-center mb-2">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                           :class="form.assignment_type === 'individual' ? 'bg-indigo-100' : 'bg-gray-100'">
                        <svg class="w-5 h-5" :class="form.assignment_type === 'individual' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                      </div>
                      <div class="ml-3 flex-1">
                        <span class="block text-sm font-semibold" :class="form.assignment_type === 'individual' ? 'text-indigo-900' : 'text-gray-900'">
                          Individual Subject
                        </span>
                      </div>
                      <svg v-if="form.assignment_type === 'individual'" class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <p class="text-xs text-gray-600 ml-13">
                      Assign a single subject
                    </p>
                  </label>

                  <!-- Subject Group Option -->
                  <label
                    class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="form.assignment_type === 'group' 
                      ? 'border-purple-500 bg-purple-50' 
                      : 'border-gray-200 hover:border-gray-300 bg-white'"
                  >
                    <input
                      type="radio"
                      value="group"
                      v-model="form.assignment_type"
                      class="sr-only"
                    />
                    <div class="flex items-center mb-2">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                           :class="form.assignment_type === 'group' ? 'bg-purple-100' : 'bg-gray-100'">
                        <svg class="w-5 h-5" :class="form.assignment_type === 'group' ? 'text-purple-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                      </div>
                      <div class="ml-3 flex-1">
                        <span class="block text-sm font-semibold" :class="form.assignment_type === 'group' ? 'text-purple-900' : 'text-gray-900'">
                          Subject Group
                        </span>
                      </div>
                      <svg v-if="form.assignment_type === 'group'" class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <p class="text-xs text-gray-600 ml-13">
                      Assign a subject group
                    </p>
                  </label>
                </div>
                
                <p v-if="form.errors.assignment_type" class="mt-2 text-sm text-red-600">
                  {{ form.errors.assignment_type }}
                </p>
              </div>
            </div>

            <!-- Individual Subject Selection (if individual type) -->
            <div v-if="form.assignment_type === 'individual'" class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  2
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Subject</h3>
              </div>
              
              <div class="pl-11">
                <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Subject <span class="text-red-500">*</span>
                </label>
                <select
                  id="subject_id"
                  v-model="form.subject_id"
                  class="block w-full md:w-2/3 lg:w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.subject_id }"
                >
                  <option value="">-- Select Subject --</option>
                  <optgroup label="Compulsory Subjects">
                    <option v-for="subject in compulsorySubjects" :key="subject.id" :value="subject.id">
                      {{ subject.display_name }}
                    </option>
                  </optgroup>
                  <optgroup label="Elective Subjects" v-if="electiveSubjects.length > 0">
                    <option v-for="subject in electiveSubjects" :key="subject.id" :value="subject.id">
                      {{ subject.display_name }}
                    </option>
                  </optgroup>
                </select>
                <p v-if="form.errors.subject_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.subject_id }}
                </p>
              </div>
            </div>

            <!-- Subject Group Selection (if group type) -->
            <div v-if="form.assignment_type === 'group'" class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 text-purple-600 font-semibold text-sm mr-3">
                  2
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Subject Group</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Subject Group <span class="text-red-500">*</span>
                </label>
                
                <div class="space-y-3">
                  <label
                    v-for="group in subjectGroups"
                    :key="group.id"
                    class="relative flex items-start p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="form.subject_group_id === group.id 
                      ? 'border-purple-500 bg-purple-50' 
                      : 'border-gray-200 hover:border-gray-300 bg-white'"
                  >
                    <input
                      type="radio"
                      :value="group.id"
                      v-model="form.subject_group_id"
                      class="sr-only"
                    />
                    <div class="flex-1">
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold" :class="form.subject_group_id === group.id ? 'text-purple-900' : 'text-gray-900'">
                          {{ group.group_name }}
                        </span>
                        <svg v-if="form.subject_group_id === group.id" class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                      </div>
                      <p v-if="group.description" class="text-xs text-gray-600 mb-2">
                        {{ group.description }}
                      </p>
                      <span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                        {{ group.subject_count }} subjects
                      </span>
                    </div>
                  </label>
                </div>
                
                <p v-if="form.errors.subject_group_id" class="mt-2 text-sm text-red-600">
                  {{ form.errors.subject_group_id }}
                </p>
              </div>
            </div>

            <!-- Status -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  3
                </div>
                <h3 class="text-base font-medium text-gray-900">Update Status</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Status
                </label>
                <div class="flex items-center">
                  <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Active Assignment
                  </label>
                </div>
                <p class="mt-1 text-xs text-gray-500">Inactive assignments won't be visible to students</p>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 sm:gap-4">
              <Button 
                type="button"
                variant="secondary"
                @click="router.visit(route('class-subjects.index'))"
                class="px-6 py-2.5 text-sm font-medium"
              >
                Cancel
              </Button>
              <Button 
                type="submit"
                variant="primary"
                :loading="form.processing"
                :disabled="!canSubmit"
                class="px-6 py-2.5 text-sm font-medium"
              >
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
import { ref, computed, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

// Props
const props = defineProps({
  classSubject: Object,
  branches: Array,
  branchClasses: Array,
  subjects: Array,
  subjectGroups: Array,
})

// State
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)

// Form
const form = useForm({
  branch_class_id: props.classSubject.branch_class_id,
  assignment_type: props.classSubject.assignment_type,
  subject_id: props.classSubject.subject_id || '',
  subject_group_id: props.classSubject.subject_group_id || '',
  is_active: props.classSubject.is_active,
})

// Computed
const compulsorySubjects = computed(() => {
  return props.subjects.filter(s => s.is_compulsory)
})

const electiveSubjects = computed(() => {
  return props.subjects.filter(s => !s.is_compulsory)
})

const canSubmit = computed(() => {
  if (!form.assignment_type) return false
  if (form.assignment_type === 'individual' && !form.subject_id) return false
  if (form.assignment_type === 'group' && !form.subject_group_id) return false
  return true
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => {
      showSuccessMessage.value = false
    }, 5000)
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => {
      showErrorMessage.value = false
    }, 5000)
  }
}, { deep: true, immediate: true })

// Watch assignment type changes
watch(() => form.assignment_type, (newType) => {
  if (newType === 'individual') {
    form.subject_group_id = ''
  } else if (newType === 'group') {
    form.subject_id = ''
  }
})

// Form submit
const submit = () => {
  form.put(route('class-subjects.update', props.classSubject.id), {
    preserveScroll: true,
  })
}
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}
</style>