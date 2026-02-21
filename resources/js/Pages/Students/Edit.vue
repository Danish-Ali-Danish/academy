<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Student</h1>
            <p class="mt-2 text-sm text-gray-600">Update student information and records</p>
          </div>
          <Link :href="route('students.index')">
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
          
          <!-- Basic Student Information Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-blue-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Student Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.admission_no"
                  label="Admission Number"
                  required
                  :error="form.errors.admission_no"
                  hint="Unique admission number"
                />
              </div>

              <div>
                <Input
                  v-model="form.roll_no"
                  label="Roll Number"
                  :error="form.errors.roll_no"
                />
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
                  v-model="form.blood_group"
                  label="Blood Group"
                  :error="form.errors.blood_group"
                />
              </div>

              <div>
                <Input
                  v-model="form.religion"
                  label="Religion"
                  :error="form.errors.religion"
                />
              </div>

              <div>
                <Input
                  v-model="form.nationality"
                  label="Nationality"
                  :error="form.errors.nationality"
                />
              </div>

              <div>
                <Input
                  v-model="form.admission_date"
                  type="date"
                  label="Admission Date"
                  required
                  :error="form.errors.admission_date"
                />
              </div>

              <div>
                <Input
                  v-model="form.leaving_date"
                  type="date"
                  label="Leaving Date"
                  :error="form.errors.leaving_date"
                />
              </div>

              <div>
                <Input
                  v-model="form.tc_number"
                  label="TC Number"
                  placeholder="Transfer Certificate Number"
                  :error="form.errors.tc_number"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Student Photo
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
                
                <!-- Display existing photo if available -->
                <div v-if="student.photo" class="mt-3">
                  <p class="text-xs text-gray-600 mb-2">Current Photo:</p>
                  <img 
                    :src="`/storage/${student.photo}`" 
                    alt="Student Photo" 
                    class="w-24 h-24 rounded-lg object-cover border border-gray-200"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Parent/Guardian Information Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-green-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Parent/Guardian Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Input
                  v-model="form.father_name"
                  label="Father's Name"
                  required
                  :error="form.errors.father_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_cnic"
                  label="Father's CNIC"
                  :error="form.errors.father_cnic"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_phone"
                  label="Father's Phone"
                  required
                  :error="form.errors.father_phone"
                />
              </div>

              <div>
                <Input
                  v-model="form.father_occupation"
                  label="Father's Occupation"
                  :error="form.errors.father_occupation"
                />
              </div>

              <div>
                <Input
                  v-model="form.mother_name"
                  label="Mother's Name"
                  :error="form.errors.mother_name"
                />
              </div>

              <div>
                <Input
                  v-model="form.mother_phone"
                  label="Mother's Phone"
                  :error="form.errors.mother_phone"
                />
              </div>
            </div>
          </div>

          <!-- Contact & Address Information Section -->
          <div class="p-8 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-purple-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Contact & Address</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <Textarea
                  v-model="form.address"
                  label="Complete Address"
                  required
                  :rows="3"
                  :error="form.errors.address"
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

              <div>
                <Input
                  v-model="form.state"
                  label="State/Province"
                  :error="form.errors.state"
                />
              </div>

              <div>
                <Input
                  v-model="form.postal_code"
                  label="Postal Code"
                  :error="form.errors.postal_code"
                />
              </div>

              <div>
                <Input
                  v-model="form.email"
                  type="email"
                  label="Email Address"
                  :error="form.errors.email"
                />
              </div>

              <div>
                <Input
                  v-model="form.emergency_contact"
                  label="Emergency Contact"
                  :error="form.errors.emergency_contact"
                  hint="Alternate contact number"
                />
              </div>
            </div>
          </div>

          <!-- Academic Information Section -->
          <div class="p-8 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-indigo-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Academic Information</h2>
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

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Class <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.class_id"
                  @change="loadSections"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.class_id }"
                  required
                >
                  <option value="" disabled>Select class</option>
                  <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                    {{ cls.name }}
                  </option>
                </select>
                <p v-if="form.errors.class_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.class_id }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Section
                </label>
                <select
                  v-model="form.section_id"
                  :disabled="!form.class_id || loadingSections"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 disabled:bg-gray-100 disabled:cursor-not-allowed"
                  :class="{ 'border-red-500 focus:ring-red-500': form.errors.section_id }"
                >
                  <option value="">Select section</option>
                  <option v-for="section in sections" :key="section.id" :value="section.id">
                    {{ section.name }}
                  </option>
                </select>
                <p v-if="form.errors.section_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.section_id }}
                </p>
              </div>

              <div>
                <Input
                  v-model="form.previous_school"
                  label="Previous School"
                  :error="form.errors.previous_school"
                />
              </div>
            </div>
          </div>

          <!-- Additional Details Section -->
          <div class="p-8">
            <div class="flex items-center mb-6">
              <div class="h-10 w-1 bg-orange-600 rounded-full mr-4"></div>
              <h2 class="text-xl font-semibold text-gray-900">Additional Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <Textarea
                  v-model="form.medical_info"
                  label="Medical Information"
                  :rows="3"
                  :error="form.errors.medical_info"
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
                  <option value="alumni">Alumni</option>
                  <option value="transferred">Transferred</option>
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
              <Link :href="route('students.index')">
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
                <span v-if="!form.processing">Update Student</span>
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
import { ref, onMounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Textarea from '@/Components/Forms/Textarea.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  student: {
    type: Object,
    required: true
  },
  branches: {
    type: Array,
    default: () => []
  },
  classes: {
    type: Array,
    default: () => []
  },
  sections: {
    type: Array,
    default: () => []
  }
})

const sections = ref(props.sections || [])
const loadingSections = ref(false)

const form = useForm({
  branch_id: props.student.branch_id,
  admission_no: props.student.admission_no,
  roll_no: props.student.roll_no,
  first_name: props.student.first_name,
  last_name: props.student.last_name,
  father_name: props.student.father_name,
  father_cnic: props.student.father_cnic,
  father_phone: props.student.father_phone,
  father_occupation: props.student.father_occupation,
  mother_name: props.student.mother_name,
  mother_phone: props.student.mother_phone,
  date_of_birth: props.student.date_of_birth,
  gender: props.student.gender,
  blood_group: props.student.blood_group,
  religion: props.student.religion,
  nationality: props.student.nationality,
  class_id: props.student.class_id,
  section_id: props.student.section_id,
  previous_school: props.student.previous_school,
  address: props.student.address,
  city: props.student.city,
  state: props.student.state,
  postal_code: props.student.postal_code,
  emergency_contact: props.student.emergency_contact,
  email: props.student.email,
  photo: null,
  medical_info: props.student.medical_info,
  admission_date: props.student.admission_date,
  leaving_date: props.student.leaving_date,
  tc_number: props.student.tc_number,
  status: props.student.status
})

const handlePhotoUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
  }
}

const loadSections = async () => {
  if (!form.class_id) {
    sections.value = []
    form.section_id = ''
    return
  }

  loadingSections.value = true
  try {
    const response = await axios.get(route('students.sections-by-class'), {
      params: { class_id: form.class_id }
    })
    sections.value = response.data
    
    // Only reset section_id if current section doesn't belong to new class
    const currentSectionExists = response.data.some(s => s.id === form.section_id)
    if (!currentSectionExists) {
      form.section_id = ''
    }
  } catch (error) {
    console.error('Error loading sections:', error)
    sections.value = []
  } finally {
    loadingSections.value = false
  }
}

const submit = () => {
  form.put(route('students.update', props.student.id), {
    preserveScroll: true
  })
}

</script>