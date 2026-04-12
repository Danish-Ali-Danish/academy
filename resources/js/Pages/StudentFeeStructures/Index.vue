<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Student Fee Assignments</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Har student ki fees — class-wise fee structure se automatically assign hoti hain
              </p>
            </div>
            <!-- Sync All Button -->
            <form :action="route('student-fee-structures.sync-all')" method="POST" @submit.prevent="syncAll">
              <input type="hidden" name="_token" :value="csrfToken" />
              <Button
                type="button"
                @click="syncAll"
                variant="primary"
                :loading="syncing"
                class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sync All Fee Structures
              </Button>
            </form>
          </div>
        </div>

        <!-- Info Banner -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex items-start gap-3">
          <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="text-sm text-blue-800">
            <p class="font-semibold mb-1">Kaise kaam karta hai?</p>
            <ul class="list-disc list-inside space-y-1 text-blue-700 text-xs">
              <li>Jab <strong>Fee Structure create/update</strong> hoti hai → us class ke saare enrolled students automatically link ho jaate hain</li>
              <li>Jab <strong>Student enroll</strong> hota hai → us class ki saari active fee structures automatically assign ho jaati hain</li>
              <li>Kisi student ka <strong>custom amount</strong> set kar sakte hain (baqi fee structure ka default amount use hoga)</li>
              <li><strong>Sync All</strong> button se manually sab kuch re-sync kar sakte hain</li>
            </ul>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Academic Year</label>
              <select v-model="filters.academic_year_id" @change="loadData"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All Years</option>
                <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.year_name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
              <select v-model="filters.branch_id" @change="loadData"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All Branches</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
              <select v-model="filters.class_id" @change="loadData"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All Classes</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.class_name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
              <select v-model="filters.is_active" @change="loadData"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
          <div class="mt-3 flex justify-end">
            <Button variant="secondary" @click="resetFilters" class="text-sm">Reset Filters</Button>
          </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-2 sm:gap-3">
              <span class="text-xs sm:text-sm text-gray-700">Show</span>
              <select v-model="perPage" @change="changePerPage"
                class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-blue-500">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-xs sm:text-sm text-gray-700">entries</span>
            </div>
            <div class="relative w-full sm:w-72">
              <input v-model="tableSearch" @input="tableSearchDebounced" type="text"
                placeholder="Student name, admission no..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-xs sm:text-sm focus:ring-2 focus:ring-blue-500"/>
              <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table id="student-fee-structures-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">#</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-left">Student</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Class</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Academic Year</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Fee Type</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Default Amount</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Custom Amount</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Payable</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Status</th>
                  <th class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100 text-center"></tbody>
            </table>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t bg-gray-50 gap-3">
            <div class="text-xs sm:text-sm text-gray-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-3">
          <div v-if="mobileLoading" class="flex justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
          </div>

          <div v-else-if="mobileItems.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">Koi assignment nahi mili</p>
            <p class="mt-1 text-xs text-gray-400">"Sync All" button dabayein</p>
          </div>

          <div v-else v-for="(item, idx) in mobileItems" :key="item.id"
            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ item.student_enrollment?.student?.student_name ?? '-' }}</p>
                  <p class="text-xs text-gray-500">{{ item.student_enrollment?.student?.admission_no ?? '' }}</p>
                </div>
                <span :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                  class="px-2 py-1 text-xs font-medium rounded-full">
                  {{ item.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="space-y-1.5 border-t pt-3 text-xs text-gray-600">
                <div class="flex justify-between">
                  <span class="font-medium">Class:</span>
                  <span>{{ item.student_enrollment?.class_section?.branch_class?.class?.class_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium">Fee Type:</span>
                  <span>{{ item.fee_structure?.fee_type?.fee_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium">Amount:</span>
                  <span class="font-bold text-indigo-700">Rs {{ item.custom_amount ?? item.fee_structure?.amount }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="mobileItems.length > 0 && mobileHasMore" class="flex justify-center pt-2 pb-4">
            <Button variant="secondary" @click="loadMoreMobile" :loading="mobileLoading" class="text-sm">
              Load More
            </Button>
          </div>
        </div>

      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editModal.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-gray-900">Edit Fee Assignment</h3>
          <button @click="editModal.show = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Custom Amount
              <span class="ml-1 text-xs text-gray-400 font-normal">(blank = default fee structure amount)</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">Rs.</span>
              <input v-model.number="editModal.custom_amount" type="number" step="0.01" min="0"
                class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                placeholder="Leave blank for default" />
            </div>
          </div>

          <div class="flex items-center gap-3">
            <label class="inline-flex items-center cursor-pointer">
              <input v-model="editModal.is_active" type="checkbox" class="sr-only peer" />
              <div class="relative w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
              <span class="ms-3 text-sm font-medium text-gray-700">Active</span>
            </label>
          </div>
        </div>

        <div class="mt-6 flex gap-3 justify-end">
          <Button variant="secondary" @click="editModal.show = false">Cancel</Button>
          <Button variant="primary" @click="saveEdit" :loading="editModal.saving">Save Changes</Button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div v-if="deleteModal.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.268 14c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900">Remove Assignment?</h3>
            <p class="text-sm text-gray-500">Is student ka fee assignment remove ho jaega.</p>
          </div>
        </div>
        <div class="flex gap-3 justify-end mt-4">
          <Button variant="secondary" @click="deleteModal.show = false">Cancel</Button>
          <Button variant="danger" @click="confirmDelete" :loading="deleteModal.deleting">Remove</Button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import axios from 'axios'
import $ from 'jquery'
import 'datatables.net'

const props = defineProps({
  academicYears: Array,
  branches: Array,
  classes: Array,
})

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content

// ─── Filters ─────────────────────────────────────────────────────────────────
const filters = reactive({ academic_year_id: '', branch_id: '', class_id: '', is_active: '' })
const perPage = ref(10)
const tableSearch = ref('')
let dtInstance = null
let searchTimer = null

// ─── Mobile ───────────────────────────────────────────────────────────────────
const mobileItems = ref([])
const mobileLoading = ref(false)
const mobileHasMore = ref(false)
const mobileOffset = ref(0)

// ─── Modals ───────────────────────────────────────────────────────────────────
const editModal = reactive({ show: false, id: null, custom_amount: null, is_active: true, saving: false })
const deleteModal = reactive({ show: false, id: null, deleting: false })
const syncing = ref(false)

// ─── DataTables init ─────────────────────────────────────────────────────────
const loadData = () => {
  if (window.innerWidth >= 768) {
    if (dtInstance) {
      dtInstance.ajax.reload()
    }
  } else {
    mobileOffset.value = 0
    mobileItems.value = []
    loadMobile()
  }
}

const initDataTable = () => {
  if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') return

  dtInstance = $('#student-fee-structures-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('student-fee-structures.index'),
      type: 'GET',
      data: (d) => {
        d.academic_year_id = filters.academic_year_id
        d.branch_id = filters.branch_id
        d.class_id = filters.class_id
        d.is_active = filters.is_active
      },
    },
    columns: [
      { data: 'DT_RowIndex', orderable: false },
      { data: 'student_name', className: 'text-left' },
      { data: 'class_name' },
      { data: 'academic_year' },
      { data: 'fee_type' },
      { data: 'base_amount' },
      { data: 'custom_amount' },
      { data: 'effective_amount', className: 'font-semibold text-indigo-700' },
      { data: 'is_active', orderable: false },
      { data: 'action', orderable: false },
    ],
    pageLength: perPage.value,
    dom: 'rt',
    language: { processing: '<div class="flex justify-center py-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div></div>' },
    drawCallback: (settings) => {
      const api = settings.oInstance.api()
      const info = api.page.info()
      const el = document.getElementById('table-info')
      if (el) el.textContent = `Showing ${info.start + 1} to ${info.end} of ${info.recordsDisplay} entries`
      renderPagination(api)
    },
  })
}

const renderPagination = (api) => {
  const info = api.page.info()
  const el = document.getElementById('table-pagination')
  if (!el) return
  if (info.pages <= 1) { el.innerHTML = ''; return }

  let html = '<div class="flex gap-1">'
  html += `<button onclick="changeDtPage(${info.page - 1})" ${info.page === 0 ? 'disabled' : ''} class="px-3 py-1 text-xs border rounded-lg ${info.page === 0 ? 'text-gray-300 cursor-not-allowed' : 'hover:bg-gray-100'}">Prev</button>`
  for (let i = 0; i < info.pages; i++) {
    if (i < 2 || i > info.pages - 3 || Math.abs(i - info.page) <= 1) {
      html += `<button onclick="changeDtPage(${i})" class="px-3 py-1 text-xs border rounded-lg ${i === info.page ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-100'}">${i + 1}</button>`
    } else if (Math.abs(i - info.page) === 2) {
      html += `<span class="px-2 py-1 text-xs">...</span>`
    }
  }
  html += `<button onclick="changeDtPage(${info.page + 1})" ${info.page === info.pages - 1 ? 'disabled' : ''} class="px-3 py-1 text-xs border rounded-lg ${info.page === info.pages - 1 ? 'text-gray-300 cursor-not-allowed' : 'hover:bg-gray-100'}">Next</button>`
  html += '</div>'
  el.innerHTML = html
}

window.changeDtPage = (page) => { if (dtInstance) dtInstance.page(page).draw('page') }

const changePerPage = () => { if (dtInstance) dtInstance.page.len(perPage.value).draw() }
const tableSearchDebounced = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { if (dtInstance) dtInstance.search(tableSearch.value).draw() }, 400)
}

const resetFilters = () => {
  filters.academic_year_id = ''
  filters.branch_id = ''
  filters.class_id = ''
  filters.is_active = ''
  tableSearch.value = ''
  loadData()
}

// ─── Mobile ───────────────────────────────────────────────────────────────────
const loadMobile = async () => {
  mobileLoading.value = true
  try {
    const res = await axios.get(route('student-fee-structures.index'), {
      params: {
        mobile: 1,
        page: Math.floor(mobileOffset.value / 10) + 1,
        per_page: 10,
        ...filters,
      },
    })
    const { data, current_page, last_page } = res.data
    mobileItems.value = [...mobileItems.value, ...data]
    mobileOffset.value += data.length
    mobileHasMore.value = current_page < last_page
  } finally {
    mobileLoading.value = false
  }
}
const loadMoreMobile = () => loadMobile()

// ─── Sync All ─────────────────────────────────────────────────────────────────
const syncAll = async () => {
  if (syncing.value) return
  syncing.value = true
  try {
    await axios.post(route('student-fee-structures.sync-all'), {}, {
      headers: { 'X-CSRF-TOKEN': csrfToken },
    })
    loadData()
    alert('Sync complete! Sab students ki fees assign ho gayi.')
  } catch (e) {
    alert('Sync failed: ' + (e.response?.data?.message || e.message))
  } finally {
    syncing.value = false
  }
}

// ─── Edit ─────────────────────────────────────────────────────────────────────
window.editStudentFee = (data) => {
  editModal.id = data.id
  editModal.custom_amount = data.custom_amount
  editModal.is_active = data.is_active
  editModal.show = true
}

const saveEdit = async () => {
  editModal.saving = true
  try {
    await axios.put(route('student-fee-structures.update', editModal.id), {
      custom_amount: editModal.custom_amount || null,
      is_active: editModal.is_active,
    }, { headers: { 'X-CSRF-TOKEN': csrfToken } })
    editModal.show = false
    loadData()
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message))
  } finally {
    editModal.saving = false
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────────
window.deleteStudentFee = (id) => {
  deleteModal.id = id
  deleteModal.show = true
}

const confirmDelete = async () => {
  deleteModal.deleting = true
  try {
    await axios.delete(route('student-fee-structures.destroy', deleteModal.id), {
      headers: { 'X-CSRF-TOKEN': csrfToken },
    })
    deleteModal.show = false
    loadData()
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message))
  } finally {
    deleteModal.deleting = false
  }
}

// ─── Mount ────────────────────────────────────────────────────────────────────
onMounted(() => {
  if (window.innerWidth >= 768) {
    initDataTable()
  } else {
    loadMobile()
  }
})
</script>
