<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <PageHeader 
          title="Assign New Sections"
          description="Select branch → class → assign multiple sections with capacity"
          :show-back-button="true"
          back-route="class-sections.index"
        />

        <!-- Flash Messages -->
        <FlashMessage 
          :show="showSuccessMessage"
          type="success"
          :message="$page.props.flash.success"
          @close="showSuccessMessage = false"
        />

        <FlashMessage 
          :show="showErrorMessage"
          type="error"
          :message="$page.props.flash.error"
          @close="showErrorMessage = false"
        />

        <!-- Create Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            
            <!-- Step 1: Branch Selection -->
            <div class="mb-6">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  1
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Branch</h3>
              </div>
              
              <div class="pl-11">
                <FormSelect
                  id="branch_id"
                  v-model="form.branch_id"
                  label="Branch"
                  :options="branches"
                  value-key="id"
                  label-key="branch_name"
                  placeholder="-- Select Branch --"
                  :required="true"
                  :error="form.errors.branch_id"
                  custom-width="md:w-2/3 lg:w-1/2"
                  @change="onBranchChange"
                />
              </div>
            </div>

            <!-- Step 2: Class Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm mr-3 font-semibold"
                     :class="form.branch_id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                  2
                </div>
                <h3 class="text-base font-medium" :class="form.branch_id ? 'text-gray-900' : 'text-gray-400'">
                  Select Class
                </h3>
              </div>
              
              <div class="pl-11">
                <FormSelect
                  id="branch_class_id"
                  v-model="form.branch_class_id"
                  label="Class"
                  :options="availableClasses"
                  value-key="id"
                  label-key="class_name"
                  placeholder="-- Select Class --"
                  :required="true"
                  :disabled="!form.branch_id"
                  :loading="loadingClasses"
                  loading-text="Loading classes..."
                  :error="form.errors.branch_class_id"
                  hint="Please select a branch first"
                  custom-width="md:w-2/3 lg:w-1/2"
                  @change="onClassChange"
                />
              </div>
            </div>

            <!-- Step 3: Section Selection -->
            <div class="mb-6">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm mr-3 font-semibold"
                     :class="form.branch_class_id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                  3
                </div>
                <h3 class="text-base font-medium" :class="form.branch_class_id ? 'text-gray-900' : 'text-gray-400'">
                  Select Sections
                </h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Sections <span class="text-red-500">*</span>
                  <span v-if="form.section_ids.length > 0" class="ml-2 text-indigo-600">
                    ({{ form.section_ids.length }} selected)
                  </span>
                </label>
                
                <!-- Loading State -->
                <LoadingSpinner
                  v-if="loadingSections"
                  text="Loading available sections..."
                  size="md"
                  container-class="py-12 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50"
                />

                <!-- No Class Selected -->
                <EmptyState
                  v-else-if="!form.branch_class_id"
                  type="info"
                  icon="clipboard"
                  title="Select a class first"
                  description="Choose a branch and class to see available sections"
                />

                <!-- No Available Sections -->
                <EmptyState
                  v-else-if="availableSections.length === 0"
                  type="warning"
                  title="No sections available"
                  description="All sections have already been assigned to this class"
                />

                <!-- Section Checkboxes Grid -->
                <div v-else>
                  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    <label
                      v-for="section in availableSections"
                      :key="section.id"
                      class="relative flex items-center p-3 cursor-pointer rounded-lg border-2 transition-all"
                      :class="form.section_ids.includes(section.id) 
                        ? 'border-indigo-500 bg-indigo-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white'"
                    >
                      <input
                        type="checkbox"
                        :value="section.id"
                        v-model="form.section_ids"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      />
                      <span class="ml-2 text-sm font-medium"
                            :class="form.section_ids.includes(section.id) ? 'text-indigo-900' : 'text-gray-700'">
                        {{ section.section_name }}
                      </span>
                      <svg v-if="form.section_ids.includes(section.id)" 
                           class="absolute top-1 right-1 h-4 w-4 text-indigo-600" 
                           fill="currentColor" 
                           viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </label>
                  </div>
                  
                  <p v-if="form.errors.section_ids" class="mt-2 text-sm text-red-600">
                    {{ form.errors.section_ids }}
                  </p>

                  <!-- Select All / Deselect All -->
                  <div class="mt-3 flex items-center gap-3">
                    <button
                      type="button"
                      @click="selectAllSections"
                      class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                      Select All
                    </button>
                    <span class="text-gray-300">|</span>
                    <button
                      type="button"
                      @click="form.section_ids = []"
                      class="text-xs text-gray-600 hover:text-gray-800 font-medium"
                    >
                      Deselect All
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Capacity and Status -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  4
                </div>
                <h3 class="text-base font-medium text-gray-900">Set Capacity & Status</h3>
              </div>
              
              <div class="pl-11 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Capacity -->
                <FormInput
                  id="capacity"
                  v-model="form.capacity"
                  type="number"
                  label="Capacity (seats)"
                  placeholder="e.g., 30"
                  :min="1"
                  :max="200"
                  :required="true"
                  :error="form.errors.capacity"
                  hint="Maximum students allowed (1-200)"
                />

                <!-- Is Active -->
                <FormCheckbox
                  id="is_active"
                  v-model="form.is_active"
                  label="Status"
                  checkbox-label="Active Section"
                  hint="Inactive sections won't be available for enrollment"
                />
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
                :disabled="form.section_ids.length === 0"
                class="px-6 py-2.5 text-sm font-medium"
              >
                <span v-if="!form.processing">
                  Assign {{ form.section_ids.length || 0 }} Section{{ form.section_ids.length !== 1 ? 's' : '' }}
                </span>
                <span v-else>Assigning...</span>
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
import PageHeader from '@/Components/Forms/PageHeader.vue'
import FlashMessage from '@/Components/Forms/FlashMessage.vue'
import FormSelect from '@/Components/Forms/FormSelect.vue'
import FormInput from '@/Components/Forms/FormInput.vue'
import FormCheckbox from '@/Components/Forms/FormCheckBox.vue'
import LoadingSpinner from '@/Components/Forms/LoadingSpinner.vue'
import EmptyState from '@/Components/Forms/EmptyState.vue'
import Button from '@/Components/Common/Button.vue'
import axios from 'axios'

// Props
const props = defineProps({
  branches: Array,
})

// State
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)
const availableClasses = ref([])
const loadingClasses = ref(false)
const availableSections = ref([])
const loadingSections = ref(false)

// Form
const form = useForm({
  branch_id: '',
  branch_class_id: '',
  section_ids: [],
  capacity: 30,
  is_active: true,
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

// Branch change handler
const onBranchChange = async () => {
  // Reset dependent fields
  form.branch_class_id = ''
  form.section_ids = []
  availableClasses.value = []
  availableSections.value = []
  
  if (!form.branch_id) return
  
  loadingClasses.value = true
  
  try {
    const response = await axios.get(route('class-sections.classes-by-branch'), {
      params: { branch_id: form.branch_id }
    })
    availableClasses.value = response.data
  } catch (error) {
    console.error('Error loading classes:', error)
  } finally {
    loadingClasses.value = false
  }
}

// Class change handler
const onClassChange = async () => {
  // Reset sections
  form.section_ids = []
  availableSections.value = []
  
  if (!form.branch_class_id) return
  
  loadingSections.value = true
  
  try {
    const response = await axios.get(route('class-sections.available-sections'), {
      params: { branch_class_id: form.branch_class_id }
    })
    availableSections.value = response.data.available
  } catch (error) {
    console.error('Error loading sections:', error)
  } finally {
    loadingSections.value = false
  }
}

// Select all sections
const selectAllSections = () => {
  form.section_ids = availableSections.value.map(s => s.id)
}

// Form submit
const submit = () => {
  form.post(route('class-sections.store'), {
    preserveScroll: true,
  })
}
</script>