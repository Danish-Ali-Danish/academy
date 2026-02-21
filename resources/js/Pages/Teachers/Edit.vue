<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Teacher</h1>
            <p class="mt-2 text-sm text-gray-600">Update teacher information and records</p>
          </div>
          <Link :href="route('teachers.index')">
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
                  v-model="form.employee_id"
                  label="Employee ID"
                  required
                  :error="form.errors.employee_id"
                />
              </div>

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

              <div>
                <Input
                  v-model="form.first_name"
                  label="First Name"
                  required
                  :error="form.errors.first_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.last_name"
                  label="Last Name"
                  required
                  :error="form.errors.last_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_name"
                  label="Father's Name"
                  :error="form.errors.father_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.cnic"
                  label="CNIC"
                  required
                  :error="form.errors.cnic"
                />
              </div>

              <div>
                <Input
                  v-model="form.date_of_birth"
                  type="date"
                  label="Date of Birth"
                  required
                  :error="form.errors.date_of_birth"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Gender <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.gender"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.gender }"
                  required
                >
                  <option value="" disabled>Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
                <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600">
                  {{ form.errors.gender }}
                </p>
              </div>

              <div>
                <Input
                  v-model="form.phone"
                  label="Phone Number"
                  required
                  :error="form.errors.phone"
                />
              </div>

              <div>
                <Input
                  v-model="form.emergency_contact"
                  label="Emergency Contact"
                  :error="form.errors.emergency_contact"
                />
              </div>

              <div>
                <Input
                  v-model="form.email"
                  type="email"
                  label="Email Address"
                  required
                  :error="form.errors.email"
                />
              </div>

              <div>
                <Input
                  v-model="form.city"
                  label="City"
                  required
                  :error="form.errors.city"
                />
              </div>

              <div class="md:col-span-2">
                <Textarea
                  v-model="form.address"
                  label="Complete Address"
                  required
                  :rows="3"
                  :error="form.errors.address"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Teacher Photo
                </label>
                <input
                  type="file"
                  @change="handlePhotoUpload"
                  accept="image/*"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600">
                  {{ form.errors.photo }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Maximum file size: 2MB. Leave empty to keep existing photo.</p>
                
                <div v-if="teacher.photo" class="mt-3">
                  <p class="text-xs text-gray-600 mb-2">Current Photo:</p>
                  <img 
                    :src="`/storage/${teacher.photo}`" 
                    alt="Teacher Photo" 
                    class="w-24 h-24 rounded-lg object-cover border border-gray-200"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Academic Information Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Academic Qualifications</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.qualification"
                  label="Qualification"
                  required
                  :error="form.errors.qualification"
                />
              </div>

              <div>
                <Input
                  v-model="form.specialization"
                  label="Specialization"
                  :error="form.errors.specialization"
                />
              </div>

              <div>
                <Input
                  v-model="form.experience_years"
                  type="number"
                  label="Years of Experience"
                  :error="form.errors.experience_years"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Subjects <span class="text-gray-500 text-xs">(Optional)</span>
                </label>
                <select
                  v-model="form.subjects"
                  multiple
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  size="4"
                >
                  <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                    {{ subject.name }}
                  </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple subjects</p>
              </div>
            </div>
          </div>

          <!-- Employment Details Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Employment Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.joining_date"
                  type="date"
                  label="Joining Date"
                  required
                  :error="form.errors.joining_date"
                />
              </div>

              <div>
                <Input
                  v-model="form.resignation_date"
                  type="date"
                  label="Resignation Date"
                  :error="form.errors.resignation_date"
                />
              </div>

              <div>
                <Input
                  v-model="form.basic_salary"
                  type="number"
                  label="Basic Salary"
                  required
                  :error="form.errors.basic_salary"
                />
              </div>

              <div>
                <Input
                  v-model="form.allowances"
                  type="number"
                  label="Allowances"
                  :error="form.errors.allowances"
                />
              </div>

              <div>
                <Input
                  v-model="form.bank_account"
                  label="Bank Account"
                  :error="form.errors.bank_account"
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
                  <option value="on_leave">On Leave</option>
                  <option value="resigned">Resigned</option>
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
              <Link :href="route('teachers.index')">
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
                <span v-if="!form.processing">Update Teacher</span>
                <span v-else>Updating...</span>
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
  teacher: {
    type: Object,
    required: true
  },
  branches: {
    type: Array,
    default: () => []
  },
  subjects: {
    type: Array,
    default: () => []
  },
  teacherSubjects: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  branch_id: props.teacher.branch_id,
  employee_id: props.teacher.employee_id,
  first_name: props.teacher.first_name,
  last_name: props.teacher.last_name,
  father_name: props.teacher.father_name,
  cnic: props.teacher.cnic,
  date_of_birth: props.teacher.date_of_birth,
  gender: props.teacher.gender,
  phone: props.teacher.phone,
  emergency_contact: props.teacher.emergency_contact,
  email: props.teacher.email,
  address: props.teacher.address,
  city: props.teacher.city,
  qualification: props.teacher.qualification,
  experience_years: props.teacher.experience_years,
  specialization: props.teacher.specialization,
  joining_date: props.teacher.joining_date,
  resignation_date: props.teacher.resignation_date,
  basic_salary: props.teacher.basic_salary,
  allowances: props.teacher.allowances,
  bank_account: props.teacher.bank_account,
  photo: null,
  subjects: props.teacherSubjects,
  status: props.teacher.status
})

const handlePhotoUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
  }
}

const submit = () => {
  form.put(route('teachers.update', props.teacher.id), {
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