<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Create New Fee Type</h1>
            <p class="mt-2 text-sm text-gray-600">Add a new fee category to your fee structure</p>
          </div>
          <Link :href="route('fee-types.index')">
            <Button variant="secondary" class="shadow-sm hover:shadow-md transition-shadow">
              <ArrowLeftIcon class="h-5 w-5 mr-2" />
              Back to List
            </Button>
          </Link>
        </div>
      </div>

      <!-- Form Card -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <form @submit.prevent="submit">
          
          <!-- Basic Information Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-blue-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Basic Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.name"
                  label="Fee Type Name"
                  placeholder="e.g., Tuition Fee"
                  required
                  :error="form.errors.name"
                  class="transition-all duration-200"
                />
              </div>

              <div>
                <Input
                  v-model="form.code"
                  label="Fee Code"
                  placeholder="e.g., TF-001"
                  required
                  :error="form.errors.code"
                  hint="Unique identifier for this fee type"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Branch <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.branch_id"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.branch_id }"
                  required
                >
                  <option value="" disabled>Select branch</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.name }}
                  </option>
                </select>
                <p v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.branch_id }}
                </p>
              </div>
            </div>
          </div>

          <!-- Fee Details Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Fee Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.amount"
                  type="number"
                  label="Fee Amount"
                  placeholder="5000"
                  required
                  :error="form.errors.amount"
                  step="0.01"
                  min="0"
                  hint="Standard fee amount"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Frequency <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.frequency"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.frequency }"
                  required
                >
                  <option value="" disabled>Select frequency</option>
                  <option v-for="freq in frequencyOptions" :key="freq.value" :value="freq.value">
                    {{ freq.label }}
                  </option>
                </select>
                <p v-if="form.errors.frequency" class="mt-1 text-sm text-red-600">
                  {{ form.errors.frequency }}
                </p>
              </div>

              <div class="md:col-span-2 flex items-center">
                <label class="flex items-center space-x-3 cursor-pointer group">
                  <input
                    v-model="form.is_mandatory"
                    type="checkbox"
                    class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-2 focus:ring-red-500 transition-all"
                  />
                  <div>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                      Mark as Mandatory Fee
                    </span>
                    <p class="text-xs text-gray-500 mt-0.5">This fee must be paid by all students</p>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Applicability Period Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Applicability Period</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.applicable_from"
                  type="date"
                  label="Applicable From"
                  :error="form.errors.applicable_from"
                  hint="Start date for this fee type"
                />
              </div>

              <div>
                <Input
                  v-model="form.applicable_to"
                  type="date"
                  label="Applicable To"
                  :error="form.errors.applicable_to"
                  hint="End date for this fee type (optional)"
                />
              </div>

              <div class="md:col-span-2">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-blue-900">Applicability Information</p>
                      <p class="text-xs text-blue-700 mt-1">
                        Leave the "Applicable To" date empty if this fee type should remain active indefinitely. 
                        The system will automatically check if the fee type is applicable when generating fee records.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Information Section -->
          <div class="p-8 bg-gray-50">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-orange-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Additional Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <Textarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Enter detailed description of this fee type"
                  :rows="4"
                  :error="form.errors.description"
                  hint="Provide information about what this fee covers"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Status <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.status"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.status }"
                  required
                >
                  <option value="" disabled>Select status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                  {{ form.errors.status }}
                </p>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-end gap-3">
              <Link :href="route('fee-types.index')">
                <Button 
                  type="button" 
                  variant="secondary" 
                  class="px-6 py-2.5 shadow-sm hover:shadow-md transition-all"
                >
                  Cancel
                </Button>
              </Link>
              <Button 
                type="submit" 
                variant="primary" 
                :loading="form.processing"
                class="px-8 py-2.5 shadow-md hover:shadow-lg transition-all"
              >
                <span v-if="!form.processing">Create Fee Type</span>
                <span v-else>Creating...</span>
              </Button>
            </div>
          </div>
        </form>
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
  branches: {
    type: Array,
    default: () => []
  }
})

const frequencyOptions = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'half_yearly', label: 'Half Yearly' },
  { value: 'yearly', label: 'Yearly' },
  { value: 'one_time', label: 'One Time' }
]

const form = useForm({
  branch_id: '',
  name: '',
  code: '',
  amount: '',
  description: '',
  frequency: 'monthly',
  is_mandatory: false,
  applicable_from: '',
  applicable_to: '',
  status: 'active'
})

const submit = () => {
  form.post(route('fee-types.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Handle success
    },
    onError: (errors) => {
      console.log('Validation errors:', errors)
    }
  })
}
</script>