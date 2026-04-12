<template>
  <div class="relative w-full">
    <!-- Search Input -->
    <div class="relative">
      <input
        ref="inputRef"
        type="text"
        v-model="searchQuery"
        @input="onSearchInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown.down.prevent="onArrowDown"
        @keydown.up.prevent="onArrowUp"
        @keydown.enter.prevent="onEnter"
        @keydown.escape="onEscape"
        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-10 bg-white disabled:bg-gray-50 disabled:text-gray-500 transition-colors"
        :class="{'border-red-500': error}"
        placeholder="Enter Roll No (e.g. MAT-12345) or Name..."
        autocomplete="off"
        :disabled="disabled || selectedStudent !== null"
      />
      <!-- Search Icon inside Input -->
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <!-- Clear Button (when student selected) -->
      <button 
        v-if="selectedStudent && !disabled" 
        @click.prevent="clearSelection"
        type="button" 
        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
        title="Clear Selection"
      >
        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <!-- Error msg -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>

    <!-- Search Dropdown Results -->
    <div 
      v-if="showDropdown && searchResults.length > 0 && !selectedStudent" 
      class="absolute z-20 mt-1 w-full bg-white shadow-xl max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm"
      ref="dropdownRef"
    >
      <!-- Suggestion header when no query -->
      <div v-if="!searchQuery.trim()" class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
        Recent Students
      </div>
      <ul role="listbox">
        <li 
          v-for="(student, index) in searchResults" 
          :key="student.id"
          :ref="el => { if (el) itemRefs[index] = el }"
          @mousedown.prevent="selectStudent(student)"
          @mouseenter="highlightedIndex = index"
          class="cursor-pointer select-none relative py-2.5 pl-3 pr-3 border-b border-gray-100 last:border-0 transition-colors"
          :class="index === highlightedIndex ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : 'hover:bg-gray-50'"
          role="option"
          :aria-selected="index === highlightedIndex"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-xs font-medium"
                :class="index === highlightedIndex ? 'bg-indigo-200 text-indigo-900' : 'bg-indigo-100 text-indigo-800'"
              >
                {{ student.roll_no || 'N/A' }}
              </span>
              <span class="font-medium" :class="index === highlightedIndex ? 'text-indigo-900' : 'text-gray-900'">{{ student.student_name }}</span>
            </div>
            <span class="text-xs sm:text-right" :class="index === highlightedIndex ? 'text-indigo-600' : 'text-gray-500'">s/o {{ student.father_name || 'N/A' }}</span>
          </div>
        </li>
      </ul>
    </div>

    <!-- Loading State -->
    <div v-if="showDropdown && loading && !selectedStudent" class="absolute z-20 mt-1 w-full bg-white shadow-lg rounded-md py-3 flex items-center justify-center gap-2 text-sm text-gray-500">
      <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
      Searching...
    </div>

    <!-- No Results State -->
    <div v-if="showDropdown && !loading && searchQuery.length >= 2 && searchResults.length === 0 && !selectedStudent" class="absolute z-20 mt-1 w-full bg-white shadow-lg rounded-md py-3 text-center text-sm text-gray-500">
      No student found matching "{{ searchQuery }}".
    </div>

    <!-- Selected Student Card (shows below input) -->
    <div v-if="selectedStudent" class="mt-3 bg-white border border-indigo-200 rounded-lg p-4 shadow-sm relative overflow-hidden ring-1 ring-inset ring-indigo-50 transition-all duration-300">
      <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
      
      <div class="flex gap-4">
        <!-- Icon -->
        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100">
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        
        <!-- Info Grid -->
        <div class="flex-1 min-w-0">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Student Name</p>
              <p class="text-sm font-bold text-gray-900 truncate">{{ selectedStudent.student_name }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Father / Guardian</p>
              <p class="text-sm font-medium text-gray-700 truncate">{{ selectedStudent.father_name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Roll No</p>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                {{ selectedStudent.roll_no || 'N/A' }}
              </span>
            </div>
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Admission No</p>
              <p class="text-sm text-gray-700">{{ selectedStudent.admission_no || 'N/A' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  initialStudent: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'student-selected', 'cleared'])

const searchQuery = ref('')
const searchResults = ref([])
const loading = ref(false)
const showDropdown = ref(false)
const selectedStudent = ref(null)
const highlightedIndex = ref(-1)
const inputRef = ref(null)
const dropdownRef = ref(null)
const itemRefs = ref({})

// Initialize if initialStudent is provided
onMounted(() => {
  if (props.initialStudent) {
    selectedStudent.value = props.initialStudent
    searchQuery.value = props.initialStudent.roll_no || props.initialStudent.student_name
  }
})

// Generic debounce
let debounceTimer = null
const debounce = (fn, delay) => {
  return (...args) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => fn(...args), delay)
  }
}

const performSearch = async (query) => {
  loading.value = true
  try {
    const params = query && query.trim().length >= 2 ? { q: query } : {}
    const res = await axios.get('/api/students/search', { params })
    searchResults.value = res.data
    highlightedIndex.value = -1
  } catch (err) {
    console.error('Error fetching students:', err)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(performSearch, 400)

const onSearchInput = () => {
  if (selectedStudent.value) return // Disable search typed if a student is already selected
  
  showDropdown.value = true
  highlightedIndex.value = -1

  if (searchQuery.value.trim().length >= 2) {
    debouncedSearch(searchQuery.value)
  } else if (searchQuery.value.trim().length === 0) {
    // Show suggestions when input is cleared
    performSearch('')
  } else {
    searchResults.value = []
  }
}

const selectStudent = (student) => {
  selectedStudent.value = student
  searchQuery.value = student.roll_no || student.student_name // lock input to roll no
  showDropdown.value = false
  highlightedIndex.value = -1
  emit('update:modelValue', student.id)
  emit('student-selected', student)
}

const clearSelection = () => {
  selectedStudent.value = null
  searchQuery.value = ''
  searchResults.value = []
  highlightedIndex.value = -1
  emit('update:modelValue', '')
  emit('cleared')
}

const onFocus = () => {
  if (!selectedStudent.value) {
    showDropdown.value = true
    // Load recent students as suggestions on focus
    if (searchResults.value.length === 0) {
      performSearch('')
    }
  }
}

const onBlur = () => {
  // Use timeout to allow mousedown on results to complete before dropdown hides
  setTimeout(() => {
    showDropdown.value = false
    highlightedIndex.value = -1
  }, 200)
}

// Keyboard navigation: Arrow Down
const onArrowDown = () => {
  if (!showDropdown.value || searchResults.value.length === 0) return
  
  if (highlightedIndex.value < searchResults.value.length - 1) {
    highlightedIndex.value++
  } else {
    highlightedIndex.value = 0 // wrap to top
  }
  scrollToHighlighted()
}

// Keyboard navigation: Arrow Up
const onArrowUp = () => {
  if (!showDropdown.value || searchResults.value.length === 0) return
  
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  } else {
    highlightedIndex.value = searchResults.value.length - 1 // wrap to bottom
  }
  scrollToHighlighted()
}

// Keyboard navigation: Enter to select
const onEnter = () => {
  if (highlightedIndex.value >= 0 && highlightedIndex.value < searchResults.value.length) {
    selectStudent(searchResults.value[highlightedIndex.value])
  }
}

// Escape to close dropdown
const onEscape = () => {
  showDropdown.value = false
  highlightedIndex.value = -1
}

// Scroll dropdown so highlighted item is visible
const scrollToHighlighted = () => {
  nextTick(() => {
    const el = itemRefs.value[highlightedIndex.value]
    if (el) {
      el.scrollIntoView({ block: 'nearest' })
    }
  })
}
</script>
