<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Add Cheque Record</h1>
            <p class="mt-1 text-sm text-gray-500">Record a new cheque received from a student</p>
          </div>
          <Button @click="$inertia.visit(route('cheque-tracking.index'))" variant="secondary" class="w-full sm:w-auto text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
          </Button>
        </div>

        <div class="max-w-3xl space-y-4">

          <!-- STEP 1: Student Search -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
              <h2 class="font-semibold text-gray-900">Search Student</h2>
            </div>

            <div class="relative" ref="searchContainer">
              <div class="relative">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                  v-model="searchQuery"
                  @input="onSearchInput"
                  @focus="showDropdown = searchResults.length > 0"
                  type="text"
                  placeholder="Search by name, admission no, or roll no..."
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  :class="{ 'border-red-500': form.errors.student_enrollment_id }"
                />
                <div v-if="searchLoading" class="absolute right-3 top-2.5">
                  <svg class="animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                </div>
              </div>
              <p v-if="form.errors.student_enrollment_id" class="mt-1 text-xs text-red-600">{{ form.errors.student_enrollment_id }}</p>

              <!-- Search Results Dropdown -->
              <div v-if="showDropdown && searchResults.length > 0"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                <button
                  v-for="s in searchResults" :key="s.id"
                  @click="selectStudent(s)"
                  type="button"
                  class="w-full flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 transition-colors text-left border-b border-gray-100 last:border-0">
                  <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-indigo-700 font-semibold text-sm">{{ s.student_name?.charAt(0) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm">{{ s.student_name }}</p>
                    <p class="text-xs text-gray-500">{{ s.class_name }} · {{ s.branch_name }} · Adm: {{ s.admission_no }}</p>
                  </div>
                  <span class="text-xs text-gray-400 flex-shrink-0">{{ s.year_name }}</span>
                </button>
              </div>
              <div v-if="showDropdown && searchQuery.length >= 2 && !searchLoading && searchResults.length === 0"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow p-4 text-center text-sm text-gray-500">
                No student found
              </div>
            </div>

            <!-- Selected Student Card -->
            <div v-if="selectedStudent" class="mt-3 flex items-center gap-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
              <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold">{{ selectedStudent.student_name?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-indigo-900">{{ selectedStudent.student_name }}</p>
                <p class="text-xs text-indigo-600">{{ selectedStudent.class_name }} · {{ selectedStudent.branch_name }} · {{ selectedStudent.year_name }}</p>
              </div>
              <button @click="clearStudent" type="button" class="text-indigo-400 hover:text-indigo-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <!-- STEP 2: Cheque Details -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-5">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
              <h2 class="font-semibold text-gray-900">Cheque Details</h2>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Number <span class="text-red-500">*</span></label>
                  <input v-model="form.cheque_no" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.cheque_no}" placeholder="e.g. 123456" />
                  <p v-if="form.errors.cheque_no" class="mt-1 text-xs text-red-600">{{ form.errors.cheque_no }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Cheque Date <span class="text-red-500">*</span></label>
                  <input v-model="form.cheque_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.cheque_date}" />
                  <p v-if="form.errors.cheque_date" class="mt-1 text-xs text-red-600">{{ form.errors.cheque_date }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name <span class="text-red-500">*</span></label>
                  <input v-model="form.bank_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.bank_name}" placeholder="e.g. HBL" />
                  <p v-if="form.errors.bank_name" class="mt-1 text-xs text-red-600">{{ form.errors.bank_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                  <input v-model="form.branch_name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.branch_name}" placeholder="e.g. Main Branch" />
                  <p v-if="form.errors.branch_name" class="mt-1 text-xs text-red-600">{{ form.errors.branch_name }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Account Title</label>
                  <input v-model="form.account_title" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.account_title}" placeholder="e.g. John Doe" />
                  <p v-if="form.errors.account_title" class="mt-1 text-xs text-red-600">{{ form.errors.account_title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Amount (Rs.) <span class="text-red-500">*</span></label>
                  <input v-model="form.amount" type="number" step="0.01" min="0.01" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.amount}" placeholder="0.00" />
                  <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Received Date <span class="text-red-500">*</span></label>
                  <input v-model="form.received_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.received_date}" />
                  <p v-if="form.errors.received_date" class="mt-1 text-xs text-red-600">{{ form.errors.received_date }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Expected Clearance Date</label>
                  <input v-model="form.expected_clearance_date" type="date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.expected_clearance_date}" />
                  <p v-if="form.errors.expected_clearance_date" class="mt-1 text-xs text-red-600">{{ form.errors.expected_clearance_date }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                  <select v-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.status}">
                    <option value="Pending">Pending</option>
                    <option value="Cleared">Cleared</option>
                    <option value="Bounced">Bounced</option>
                  </select>
                  <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Remarks</label>
                <textarea v-model="form.notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" :class="{'border-red-500': form.errors.notes}" placeholder="Optional details..."></textarea>
                <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
              </div>

              <div class="pt-4 border-t border-gray-100 flex justify-end">
                <Button type="submit" variant="primary" :disabled="form.processing" class="w-full sm:w-auto px-6 py-2.5">
                  <span v-if="form.processing">Saving...</span>
                  <span v-else>Save Cheque Record</span>
                </Button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import axios from 'axios'
import debounce from 'lodash/debounce'

// Form
const form = useForm({
  student_enrollment_id: '',
  cheque_no: '',
  cheque_date: new Date().toISOString().split('T')[0],
  bank_name: '',
  branch_name: '',
  account_title: '',
  amount: '',
  received_date: new Date().toISOString().split('T')[0],
  expected_clearance_date: '',
  status: 'Pending',
  notes: ''
})

// Student Search State
const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const showDropdown = ref(false)
const selectedStudent = ref(null)
const searchContainer = ref(null)

const onSearchInput = debounce(async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    showDropdown.value = false
    return
  }
  
  searchLoading.value = true
  try {
    const res = await axios.get(route('cheque-tracking.students'), {
      params: { q: searchQuery.value }
    })
    searchResults.value = res.data
    showDropdown.value = true
  } catch (error) {
    console.error('Error fetching students:', error)
  } finally {
    searchLoading.value = false
  }
}, 400)

const selectStudent = (student) => {
  selectedStudent.value = student
  form.student_enrollment_id = student.id
  searchQuery.value = ''
  searchResults.value = []
  showDropdown.value = false
}

const clearStudent = () => {
  selectedStudent.value = null
  form.student_enrollment_id = ''
}

const handleClickOutside = (e) => {
  if (searchContainer.value && !searchContainer.value.contains(e.target)) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const submit = () => {
  if (!form.student_enrollment_id) {
    form.setError('student_enrollment_id', 'Please select a student first.')
    return
  }
  
  form.post(route('cheque-tracking.store'), {
    preserveScroll: true
  })
}
</script>
