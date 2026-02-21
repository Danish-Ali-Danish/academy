<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/20">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
                Attendance Management
              </h1>
              <p class="mt-2 text-sm text-slate-600 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Track and manage student attendance records
              </p>
            </div>
            <Link :href="route('attendance.create')">
              <Button variant="primary" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-200">
                <PlusIcon class="h-5 w-5 mr-2 inline-block" />
                <span class="text-sm font-semibold">Mark Attendance</span>
              </Button>
            </Link>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-6 mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="relative">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              <Input
                v-model="filters.search"
                placeholder="Search students..."
                @input="searchDebounced"
                class="w-full pl-10 pr-4 py-2.5 text-sm border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              />
            </div>
            
            <div>
              <select
                v-model="filters.status"
                @change="loadData"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              >
                <option value="">All Status</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="leave">Leave</option>
              </select>
            </div>

            <div>
              <select
                v-model="filters.branch_id"
                @change="loadData"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              >
                <option value="">All Branches</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.name }}
                </option>
              </select>
            </div>

            <div>
              <select
                v-model="filters.class_id"
                @change="loadData"
                class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              >
                <option value="">All Classes</option>
                <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                  {{ cls.name }}
                </option>
              </select>
            </div>

            <Button 
              variant="secondary" 
              @click="resetFilters"
              class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium bg-slate-50 border border-slate-200 hover:bg-slate-100 rounded-xl transition-all flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Reset Filters
            </Button>
          </div>
        </div>

        <!-- Desktop/Tablet Table View -->
        <div class="hidden md:block bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
          <!-- Table Header with Search -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-transparent gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto">
              <span class="text-sm text-slate-600">Show</span>
              <select 
                v-model="perPage" 
                @change="changePerPage"
                class="px-4 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-sm text-slate-600">entries</span>
            </div>

            <div class="w-full sm:w-64">
              <div class="relative">
                <input
                  v-model="tableSearch"
                  @input="tableSearchDebounced"
                  type="text"
                  placeholder="Search in table..."
                  class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                />
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table id="attendance-table" class="min-w-full divide-y divide-slate-100">
              <thead class="bg-gradient-to-r from-slate-50 via-blue-50/30 to-indigo-50/20">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">#</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Student</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Roll No</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Class</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Check In</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Check Out</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-50 text-center"></tbody>
            </table>
          </div>

          <!-- Table Footer -->
          <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-slate-100 bg-gradient-to-r from-slate-50 to-transparent gap-4">
            <div class="text-sm text-slate-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-4">
          <!-- Loading State -->
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-slate-100">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="mobileData.length === 0" class="text-center py-12 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-slate-100">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-slate-500">No attendance records found</p>
            <p class="mt-1 text-xs text-slate-400">Try adjusting your filters</p>
          </div>

          <!-- Attendance Cards -->
          <div v-else v-for="(record, index) in mobileData" :key="record.id" class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 overflow-hidden hover:shadow-xl hover:shadow-slate-200/60 transition-all duration-300">
            <div class="p-5">
              <!-- Header -->
              <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold text-slate-400">#{{ mobileOffset + index + 1 }}</span>
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                      <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                    </div>
                  </div>
                  <h3 class="text-base font-bold text-slate-900">{{ record.student.first_name }} {{ record.student.last_name }}</h3>
                  <p class="text-xs text-slate-500 font-mono mt-0.5">Roll: {{ record.student.roll_number }}</p>
                </div>
                <span :class="getStatusClass(record.status)" class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap ml-2 border">
                  {{ record.status.charAt(0).toUpperCase() + record.status.slice(1) }}
                </span>
              </div>

              <!-- Details -->
              <div class="space-y-3 border-t border-slate-100 pt-4">
                <div class="flex items-center gap-3 text-sm">
                  <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <span class="text-slate-700 font-medium">{{ record.date }}</span>
                </div>
                
                <div class="flex items-center gap-3 text-sm">
                  <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                  </div>
                  <span class="text-slate-700">{{ record.class?.name }} - {{ record.section?.name }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div class="flex items-center gap-2 text-xs bg-slate-50 rounded-lg p-2">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <div>
                      <span class="text-slate-500 block">In</span>
                      <span class="text-slate-900 font-semibold">{{ record.check_in_time || '—' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 text-xs bg-slate-50 rounded-lg p-2">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <div>
                      <span class="text-slate-500 block">Out</span>
                      <span class="text-slate-900 font-semibold">{{ record.check_out_time || '—' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-3 mt-5 pt-4 border-t border-slate-100">
                <Link :href="route('attendance.edit', record.id)" class="flex-1">
                  <button class="w-full px-4 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-all duration-200 flex items-center justify-center gap-2 group">
                    <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                  </button>
                </Link>
                <button 
                  @click="() => { itemToDelete = record.id; showDeleteModal = true; }"
                  class="flex-1 px-4 py-2.5 text-sm font-semibold text-red-600 bg-red-50 rounded-xl hover:bg-red-100 transition-all duration-200 flex items-center justify-center gap-2 group"
                >
                  <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div v-if="mobileData.length > 0" class="md:hidden mt-4 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-4">
          <div class="flex items-center justify-between">
            <button 
              @click="prevPage"
              :disabled="mobileCurrentPage === 1 || mobileLoading"
              class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
              Previous
            </button>
            
            <div class="text-center">
              <div class="text-sm font-semibold text-slate-900">Page {{ mobileCurrentPage }} of {{ mobileTotalPages }}</div>
              <div class="text-xs text-slate-500 mt-0.5">{{ mobileTotal }} total records</div>
            </div>
            
            <button 
              @click="nextPage"
              :disabled="mobileCurrentPage === mobileTotalPages || mobileLoading"
              class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>

      </div>

      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-slate-900">Delete Attendance</span>
          </div>
        </template>
        
        <p class="text-sm text-slate-600 mt-2">
          Are you sure you want to delete this attendance record? This action cannot be undone.
        </p>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-3">
            <Button 
              variant="secondary" 
              @click="showDeleteModal = false"
              class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all"
            >
              Cancel
            </Button>
            <Button 
              variant="danger" 
              @click="confirmDelete" 
              :loading="deleting"
              class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-500 rounded-xl hover:from-red-700 hover:to-red-600 shadow-lg shadow-red-500/30 transition-all"
            >
              <span v-if="!deleting">Delete Record</span>
              <span v-else>Deleting...</span>
            </Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

const props = defineProps({
  branches: { type: Array, default: () => [] },
  classes: { type: Array, default: () => [] }
})

const showDeleteModal = ref(false)
const deleting = ref(false)
const itemToDelete = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobileData = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
let table = null

const filters = reactive({
  search: '',
  status: '',
  branch_id: '',
  class_id: ''
})

const getStatusClass = (status) => {
  const classes = {
    'present': 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'absent': 'bg-red-50 text-red-700 border-red-200',
    'late': 'bg-amber-50 text-amber-700 border-amber-200',
    'leave': 'bg-blue-50 text-blue-700 border-blue-200'
  }
  return classes[status] || 'bg-slate-50 text-slate-700 border-slate-200'
}

const loadMobileData = async () => {
  mobileLoading.value = true
  try {
    const params = {
      page: mobileCurrentPage.value,
      per_page: perPage.value,
      mobile: 1
    }
    if (filters.search) params.search = filters.search
    if (tableSearch.value) params.search = tableSearch.value
    if (filters.status) params.status = filters.status
    if (filters.branch_id) params.branch_id = filters.branch_id
    if (filters.class_id) params.class_id = filters.class_id
    
    const response = await axios.get(route('attendance.index'), {
      params,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    
    if (response.data?.data) {
      mobileData.value = response.data.data
      mobileCurrentPage.value = response.data.current_page || 1
      mobileTotalPages.value = response.data.last_page || 1
      mobileTotal.value = response.data.total || 0
      mobileOffset.value = response.data.from ? response.data.from - 1 : 0
    }
  } catch (error) {
    console.error('Error loading mobile data:', error)
    mobileData.value = []
  } finally {
    mobileLoading.value = false
  }
}

onMounted(() => {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    }
  })

  loadMobileData()

  table = $('#attendance-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('attendance.index'),
      data: function(d) {
        d.search.value = filters.search || tableSearch.value
        d.status = filters.status
        d.branch_id = filters.branch_id
        d.class_id = filters.class_id
      },
      error: function(xhr, error, thrown) {
        console.error('DataTables Ajax Error:', {
          status: xhr.status,
          error: error,
          thrown: thrown,
          response: xhr.responseText
        })
        alert('Error loading data. Please check console for details.')
      }
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'date', name: 'date' },
      { data: 'student', name: 'student' },
      { data: 'roll_number', name: 'roll_number' },
      { data: 'class', name: 'class' },
      { data: 'check_in', name: 'check_in' },
      { data: 'check_out', name: 'check_out' },
      { data: 'status', name: 'status' },
      { data: 'action', orderable: false, searchable: false }
    ],
    pageLength: 10,
    order: [[1, 'desc']],
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-slate-500"><svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="mt-2 text-sm font-medium">No records found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      infoFiltered: '(filtered from _MAX_ total entries)',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div></div>',
      paginate: {
        first: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
        last: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
        next: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
        previous: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
      }
    },
    drawCallback: function () {
      $('#table-info').empty().append($('#attendance-table_info'))
      $('#table-pagination').empty().append($('#attendance-table_paginate'))
    }
  })
})

const prevPage = () => {
  if (mobileCurrentPage.value > 1 && !mobileLoading.value) {
    mobileCurrentPage.value--
    loadMobileData()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const nextPage = () => {
  if (mobileCurrentPage.value < mobileTotalPages.value && !mobileLoading.value) {
    mobileCurrentPage.value++
    loadMobileData()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

let tableSearchTimeout = null
const tableSearchDebounced = () => {
  clearTimeout(tableSearchTimeout)
  tableSearchTimeout = setTimeout(() => loadData(), 500)
}

let searchTimeout = null
const searchDebounced = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadData(), 500)
}

const changePerPage = () => {
  if (table) table.page.len(perPage.value).draw()
  mobileCurrentPage.value = 1
  loadMobileData()
}

const loadData = () => {
  if (table) table.ajax.reload()
  mobileCurrentPage.value = 1
  loadMobileData()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.branch_id = ''
  filters.class_id = ''
  tableSearch.value = ''
  loadData()
}

const confirmDelete = () => {
  deleting.value = true
  router.delete(route('attendance.destroy', itemToDelete.value), {
    onSuccess: () => {
      showDeleteModal.value = false
      deleting.value = false
      loadData()
    },
    onError: () => { deleting.value = false }
  })
}

window.deleteAttendance = (id) => {
  itemToDelete.value = id
  showDeleteModal.value = true
}
</script>

<style scoped>
:deep(.dataTables_info) {
  font-size: 0.875rem;
  color: #475569;
  font-weight: 500;
}

:deep(.dataTables_paginate) {
  display: flex;
  justify-content: flex-end;
  gap: 0.25rem;
  flex-wrap: wrap;
}

:deep(.paginate_button) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

:deep(.paginate_button:hover:not(.disabled)) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

:deep(.paginate_button.current) {
  background: linear-gradient(to right, #2563eb, #4f46e5);
  color: white;
  border-color: #2563eb;
  box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
}

:deep(.paginate_button.current:hover) {
  background: linear-gradient(to right, #1d4ed8, #4338ca);
  border-color: #1d4ed8;
}

:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}

:deep(#attendance-table_info),
:deep(#attendance-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#attendance-table tbody td) {
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
}

:deep(#attendance-table tbody tr:hover) {
  background-color: #f8fafc;
}
</style>