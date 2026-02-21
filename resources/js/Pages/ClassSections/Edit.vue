<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex items-center gap-3 mb-3">
            <button 
              @click="router.visit(route('class-sections.index'))"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Class Section</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Update section details and capacity
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
                <p><strong>Branch:</strong> {{ classSection.branch_name }}</p>
                <p><strong>Class:</strong> {{ classSection.class_name }}</p>
                <p><strong>Section:</strong> {{ classSection.section_name }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <!-- Branch (Read-only) -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-400 font-semibold text-sm mr-3">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <h3 class="text-base font-medium text-gray-900">Branch (Locked)</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Branch
                </label>
                <div class="block w-full md:w-2/3 lg:w-1/2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                  {{ classSection.branch_name }}
                </div>
                <p class="mt-1 text-xs text-gray-500">
                  Branch cannot be changed in edit mode
                </p>
              </div>
            </div>

            <!-- Class (Read-only) -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-400 font-semibold text-sm mr-3">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <h3 class="text-base font-medium text-gray-900">Class (Locked)</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Class
                </label>
                <div class="block w-full md:w-2/3 lg:w-1/2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">
                  {{ classSection.class_name }}
                </div>
                <p class="mt-1 text-xs text-gray-500">
                  Class cannot be changed in edit mode
                </p>
              </div>
            </div>

            <!-- Section Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  1
                </div>
                <h3 class="text-base font-medium text-gray-900">Change Section</h3>
              </div>
              
              <div class="pl-11">
                <label for="section_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Section <span class="text-red-500">*</span>
                </label>
                <select
                  id="section_id"
                  v-model="form.section_id"
                  class="block w-full md:w-2/3 lg:w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.section_id }"
                  required
                >
                  <option value="">-- Select Section --</option>
                  <option v-for="section in sections" :key="section.id" :value="section.id">
                    {{ section.section_name }}
                  </option>
                </select>
                <p v-if="form.errors.section_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.section_id }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                  Change to a different section for this class
                </p>
              </div>
            </div>

            <!-- Capacity and Status -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  2
                </div>
                <h3 class="text-base font-medium text-gray-900">Update Capacity & Status</h3>
              </div>
              
              <div class="pl-11 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Capacity -->
                <div>
                  <label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">
                    Capacity (seats) <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="capacity"
                    v-model="form.capacity"
                    type="number"
                    min="1"
                    max="200"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :class="{ 'border-red-500': form.errors.capacity }"
                    placeholder="e.g., 30"
                    required
                  />
                  <p v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">
                    {{ form.errors.capacity }}
                  </p>
                  <p class="mt-1 text-xs text-gray-500">Maximum students allowed (1-200)</p>
                </div>

                <!-- Is Active -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status
                  </label>
                  <div class="flex items-center h-10">
                    <input
                      id="is_active"
                      v-model="form.is_active"
                      type="checkbox"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                      Active Section
                    </label>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">Inactive sections won't be available for enrollment</p>
                </div>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 sm:gap-4">
              <Button 
                type="button"
                variant="secondary"
                @click="router.visit(route('class-sections.index'))"
                class="px-6 py-2.5 text-sm font-medium"
              >
                Cancel
              </Button>
              <Button 
                type="submit"
                variant="primary"
                :loading="form.processing"
                class="px-6 py-2.5 text-sm font-medium"
              >
                <span v-if="!form.processing">Update Section</span>
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
import { ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

// Props
const props = defineProps({
  classSection: Object,
  branches: Array,
  branchClasses: Array,
  sections: Array,
})

// State
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)

// Form
const form = useForm({
  section_id: props.classSection.section_id,
  capacity: props.classSection.capacity,
  is_active: props.classSection.is_active,
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

// Form submit
const submit = () => {
  form.put(route('class-sections.update', props.classSection.id), {
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