<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <PageHeader 
          title="Create New Branch"
          description="Add a new branch location to the system"
          action-route="branches.index"
          action-text="Back to List"
          action-variant="secondary"
          action-icon="back"
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              
              <!-- Branch Name -->
              <FormInput
                id="branch_name"
                v-model="form.branch_name"
                label="Branch Name"
                placeholder="e.g., Main Campus"
                :required="true"
                :error="form.errors.branch_name"
              />

              <!-- Location -->
              <FormInput
                id="location"
                v-model="form.location"
                label="Location"
                placeholder="e.g., Downtown Area"
                :required="true"
                :error="form.errors.location"
              />

              <!-- Phone -->
              <FormInput
                id="phone"
                v-model="form.phone"
                type="tel"
                label="Phone"
                placeholder="e.g., +92 300 1234567"
                :required="true"
                :error="form.errors.phone"
              />

              <!-- Status -->
              <FormCheckbox
                id="is_active"
                v-model="form.is_active"
                label="Status"
                checkbox-label="Active"
              />

              <!-- Classes Multi-Select -->
              <div class="md:col-span-2">
                <FormSelect
                  id="class_ids"
                  v-model="form.class_ids"
                  label="Assign Classes"
                  :options="classes"
                  value-key="id"
                  label-key="class_name"
                  :multiple="true"
                  :size="10"
                  :error="form.errors.class_ids"
                />

                <!-- Selected Classes Display -->
                <div v-if="form.class_ids && form.class_ids.length > 0" class="mt-3">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Selected Classes ({{ form.class_ids.length }})
                  </label>
                  <div class="flex flex-wrap gap-2">
                    <span 
                      v-for="classId in form.class_ids" 
                      :key="classId"
                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
                    >
                      {{ getClassName(classId) }}
                      <button 
                        type="button"
                        @click="removeClass(classId)"
                        class="ml-2 inline-flex items-center justify-center w-4 h-4 text-blue-600 hover:text-blue-800"
                      >
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                      </button>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <Button 
                type="button"
                variant="secondary"
                @click="$inertia.visit(route('branches.index'))"
                class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm"
              >
                Cancel
              </Button>
              <Button 
                type="submit"
                variant="primary"
                :loading="form.processing"
                class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm"
              >
                <span v-if="!form.processing">Create Branch</span>
                <span v-else>Creating...</span>
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
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import PageHeader from '@/Components/Forms/PageHeader.vue'
import FlashMessage from '@/Components/Forms/FlashMessage.vue'
import FormInput from '@/Components/Forms/FormInput.vue'
import FormSelect from '@/Components/Forms/FormSelect.vue'
import FormCheckbox from '@/Components/Forms/FormCheckBox.vue'
import Button from '@/Components/Common/Button.vue'

// Props
const props = defineProps({
  classes: {
    type: Array,
    default: () => []
  }
})

// State
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)

// Form
const form = useForm({
  branch_name: '',
  location: '',
  phone: '',
  is_active: true,
  class_ids: []
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => showSuccessMessage.value = false, 5000)
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => showErrorMessage.value = false, 5000)
  }
}, { deep: true, immediate: true })

// Helper functions
const getClassName = (classId) => {
  const classItem = props.classes.find(c => c.id === classId)
  return classItem ? classItem.class_name : ''
}

const removeClass = (classId) => {
  form.class_ids = form.class_ids.filter(id => id !== classId)
}

// Form submit
const submit = () => {
  form.post(route('branches.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Redirect will be handled by the controller
    }
  })
}
</script>