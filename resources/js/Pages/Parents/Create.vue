<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Create New Parent</h1>
            <p class="mt-2 text-sm text-gray-600">Add a new parent/guardian to the system</p>
          </div>
          <Link :href="route('parents.index')">
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
          
          <!-- Branch Information Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-blue-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Branch Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
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

          <!-- Father Information Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Father's Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.father_name"
                  label="Father's Name"
                  placeholder="Enter father's name"
                  required
                  :error="form.errors.father_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_cnic"
                  label="Father's CNIC"
                  placeholder="12345-1234567-1"
                  required
                  :error="form.errors.father_cnic"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_phone"
                  label="Father's Phone"
                  placeholder="0300-1234567"
                  required
                  :error="form.errors.father_phone"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_email"
                  type="email"
                  label="Father's Email"
                  placeholder="father@example.com"
                  :error="form.errors.father_email"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_occupation"
                  label="Father's Occupation"
                  placeholder="e.g., Business"
                  :error="form.errors.father_occupation"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_income"
                  type="number"
                  label="Father's Monthly Income"
                  placeholder="50000"
                  :error="form.errors.father_income"
                />
              </div>
            </div>
          </div>

          <!-- Mother Information Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Mother's Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.mother_name"
                  label="Mother's Name"
                  placeholder="Enter mother's name"
                  :error="form.errors.mother_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.mother_cnic"
                  label="Mother's CNIC"
                  placeholder="12345-1234567-1"
                  :error="form.errors.mother_cnic"
                />
              </div>

              <div>
                <Input
                  v-model="form.mother_phone"
                  label="Mother's Phone"
                  placeholder="0300-1234567"
                  :error="form.errors.mother_phone"
                />
              </div>

              <div>
                <Input
                  v-model="form.mother_email"
                  type="email"
                  label="Mother's Email"
                  placeholder="mother@example.com"
                  :error="form.errors.mother_email"
                />
              </div>

              <div>
                <Input
                  v-model="form.mother_occupation"
                  label="Mother's Occupation"
                  placeholder="e.g., Teacher"
                  :error="form.errors.mother_occupation"
                />
              </div>
            </div>
          </div>

          <!-- Guardian Information Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-orange-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Guardian Information (Optional)</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.guardian_name"
                  label="Guardian Name"
                  placeholder="Enter guardian name"
                  :error="form.errors.guardian_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.guardian_phone"
                  label="Guardian Phone"
                  placeholder="0300-1234567"
                  :error="form.errors.guardian_phone"
                />
              </div>

              <div>
                <Input
                  v-model="form.guardian_relation"
                  label="Guardian Relation"
                  placeholder="e.g., Uncle, Aunt"
                  :error="form.errors.guardian_relation"
                />
              </div>
            </div>
          </div>

          <!-- Contact & Address Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-indigo-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Contact & Address</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <Textarea
                  v-model="form.address"
                  label="Complete Address"
                  placeholder="Enter full residential address"
                  required
                  :rows="3"
                  :error="form.errors.address"
                />
              </div>

              <div>
                <Input
                  v-model="form.city"
                  label="City"
                  placeholder="e.g., Lahore"
                  required
                  :error="form.errors.city"
                />
              </div>

              <div>
                <Input
                  v-model="form.postal_code"
                  label="Postal Code"
                  placeholder="e.g., 54000"
                  :error="form.errors.postal_code"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Preferred Contact <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.preferred_contact"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.preferred_contact }"
                  required
                >
                  <option value="" disabled>Select preferred contact</option>
                  <option value="father">Father</option>
                  <option value="mother">Mother</option>
                  <option value="guardian">Guardian</option>
                </select>
                <p v-if="form.errors.preferred_contact" class="mt-1 text-sm text-red-600">
                  {{ form.errors.preferred_contact }}
                </p>
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
              <Link :href="route('parents.index')">
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
                <span v-if="!form.processing">Create Parent</span>
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
  },
  students: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  branch_id: '',
  father_name: '',
  father_phone: '',
  father_email: '',
  father_occupation: '',
  father_cnic: '',
  father_income: '',
  mother_name: '',
  mother_phone: '',
  mother_email: '',
  mother_occupation: '',
  mother_cnic: '',
  guardian_name: '',
  guardian_phone: '',
  guardian_relation: '',
  address: '',
  city: '',
  postal_code: '',
  preferred_contact: 'father',
  status: 'active'
})

const submit = () => {
  form.post(route('parents.store'), {
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