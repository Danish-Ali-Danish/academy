<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Installment Assignments</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Manage student installment plan assignments</p>
            </div>
            <Button
              @click="$inertia.visit(route('student-installment-assignments.create'))"
              variant="primary"
              class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              New Assignment
            </Button>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <div>
              <Input
                v-model="filters.search"
                placeholder="Search assignments..."
                @input="searchDebounced"
                class="w-full text-sm"
              />
            </div>

            <div>
              <select
                v-model="filters.status"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="defaulted">Defaulted</option>
              </select>
            </div>

            <Button
              variant="secondary"
              @click="resetFilters"
              class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all duration-200 text-sm"
            >
              Reset Filters
            </Button>
          </div>
        </div>

        <!-- Desktop/Tablet Table View -->
        <div class="hidden md:block bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
          <!-- Table Header -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
              <span class="text-xs sm:text-sm text-gray-700">Show</span>
              <select
                v-model="perPage"
                @change="changePerPage"
                class="px-3 sm:px-6 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
              >
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-xs sm:text-sm text-gray-700">entries</span>
            </div>

            <div class="w-full sm:w-64">
              <div class="relative">
                <input
                  v-model="tableSearch"
                  @input="tableSearchDebounced"
                  type="text"
                  placeholder="Search in table..."
                  class="w-full pl-9 sm:pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm"
                />
                <svg class="absolute left-2.5 sm:left-3 top-2.5 h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table id="assignments-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">#</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Student</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Plan</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Total Amount</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Paid</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Remaining</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Status</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white text-center divide-y divide-gray-100">
                <!-- DataTables will populate this -->
              </tbody>
            </table>
          </div>

          <!-- Table Footer -->
          <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 bg-gray-50 gap-3 sm:gap-4">
            <div class="text-xs sm:text-sm text-gray-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3 sm:space-y-4">
          <!-- Loading State -->
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
          </div>

          <!-- Empty State -->
          <div v-else-if="mobileAssignments.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No assignments found</p>
            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
          </div>

          <!-- Assignment Cards -->
          <div v-else v-for="(assignment, index) in mobileAssignments" :key="assignment.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                    <h3 class="text-base font-semibold text-gray-900">
                      {{ assignment.student_enrollment?.student?.student_name || 'N/A' }}
                    </h3>
                  </div>
                </div>
                <span :class="getStatusClass(assignment.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2 capitalize">
                  {{ assignment.status }}
                </span>
              </div>

              <!-- Details -->
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  <span class="text-gray-600">Plan: {{ assignment.installment_plan?.plan_name || '—' }}</span>
                </div>
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                  </svg>
                  <span class="text-gray-600">Total: Rs {{ formatAmount(assignment.total_amount) }}</span>
                </div>
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-green-600 font-semibold">Paid: Rs {{ formatAmount(assignment.amount_paid) }}</span>
                </div>
                <div class="flex items-center text-xs sm:text-sm">
                  <svg class="w-4 h-4 text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-red-600 font-semibold">Remaining: Rs {{ formatAmount(assignment.remaining_amount) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <button
                  @click="showAssignmentDetails(assignment.id)"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  View
                </button>
                <button
                  @click="$inertia.visit(route('student-installment-assignments.edit', assignment.id))"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                  Edit
                </button>
                <button
                  @click="() => { assignmentToDelete = assignment.id; showDeleteModal = true; }"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div v-if="mobileAssignments.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
          <div class="flex items-center justify-between">
            <button
              @click="prevPage"
              :disabled="mobileCurrentPage === 1 || mobileLoading"
              class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed bg-white hover:bg-gray-50 transition-colors flex items-center gap-1"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
              Previous
            </button>

            <div class="text-center">
              <div class="text-sm font-medium text-gray-900">Page {{ mobileCurrentPage }} of {{ mobileTotalPages }}</div>
              <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total assignments</div>
            </div>

            <button
              @click="nextPage"
              :disabled="mobileCurrentPage === mobileTotalPages || mobileLoading"
              class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed bg-white hover:bg-gray-50 transition-colors flex items-center gap-1"
            >
              Next
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>

      </div>

      <!-- Show Details Modal -->
      <Modal :show="showModal" @close="showModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">Installment Assignment Details</span>
          </div>
        </template>

        <div v-if="loadingDetails" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else-if="assignmentDetails" class="space-y-6">
          <!-- Student Information -->
          <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Student Information
            </h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span class="text-gray-500">Name:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.student.name }}</p>
              </div>
              <div>
                <span class="text-gray-500">Father's Name:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.student.father_name }}</p>
              </div>
              <div>
                <span class="text-gray-500">Roll No:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.student.roll_no }}</p>
              </div>
              <div>
                <span class="text-gray-500">Admission No:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.student.admission_no }}</p>
              </div>
            </div>
          </div>

          <!-- Class Information -->
          <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              Class Information
            </h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span class="text-gray-500">Class:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.class_info.class_name }}</p>
              </div>
              <div>
                <span class="text-gray-500">Section:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.class_info.section_name }}</p>
              </div>
              <div class="col-span-2">
                <span class="text-gray-500">Academic Year:</span>
                <p class="font-medium text-gray-900">{{ assignmentDetails.class_info.academic_year }}</p>
              </div>
            </div>
          </div>

          <!-- Plan Details -->
          <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              Plan Details
            </h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Plan Name:</span>
                <span class="font-medium text-gray-900">{{ assignmentDetails.plan.plan_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Fee Type:</span>
                <span class="font-medium text-gray-900">{{ assignmentDetails.plan.fee_type }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Total Installments:</span>
                <span class="font-medium text-gray-900">{{ assignmentDetails.plan.total_installments }}</span>
              </div>
            </div>
          </div>

          <!-- Fee Breakdown (concession info) -->
          <div v-if="assignmentDetails.fee_breakdown?.has_concession" class="bg-green-50 border border-green-200 rounded-lg p-3">
            <h3 class="text-xs font-semibold text-green-700 uppercase tracking-wider mb-2 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
              Fee Concession Applied
            </h3>
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
              <div>
                <p class="text-gray-500">Original Fee</p>
                <p class="font-bold text-gray-700 line-through">Rs {{ formatAmount(assignmentDetails.fee_breakdown.base_amount) }}</p>
              </div>
              <div>
                <p class="text-green-600">Concession (-)</p>
                <p class="font-bold text-green-700">Rs {{ formatAmount(assignmentDetails.fee_breakdown.concession_amount) }}</p>
                <p class="text-[10px] text-green-600 mt-0.5">{{ assignmentDetails.fee_breakdown.concession_detail }}</p>
              </div>
              <div>
                <p class="text-blue-600">Net Amount</p>
                <p class="font-bold text-blue-700 text-sm">Rs {{ formatAmount(assignmentDetails.total_amount) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 flex items-center gap-2 text-xs text-gray-500">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            No concession applied — Original fee structure amount used
          </div>

          <!-- Payment Summary -->
          <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              Payment Summary
            </h3>
            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="bg-white rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Total Amount</p>
                <p class="text-lg font-bold text-gray-900">Rs {{ formatAmount(assignmentDetails.total_amount) }}</p>
              </div>
              <div class="bg-green-100 rounded-lg p-3">
                <p class="text-xs text-green-600 mb-1">Paid</p>
                <p class="text-lg font-bold text-green-700">Rs {{ formatAmount(assignmentDetails.amount_paid) }}</p>
              </div>
              <div class="bg-red-100 rounded-lg p-3">
                <p class="text-xs text-red-600 mb-1">Remaining</p>
                <p class="text-lg font-bold text-red-700">Rs {{ formatAmount(assignmentDetails.remaining_amount) }}</p>
              </div>
            </div>
            <div class="mt-3 pt-3 border-t border-amber-200">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Status:</span>
                <span :class="getStatusClass(assignmentDetails.status)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                  {{ assignmentDetails.status }}
                </span>
              </div>
              <div class="flex justify-between text-sm mt-2">
                <span class="text-gray-600">Approved By:</span>
                <span class="font-medium text-gray-900">{{ assignmentDetails.approved_by }}</span>
              </div>
              <div class="flex justify-between text-sm mt-2">
                <span class="text-gray-600">Created At:</span>
                <span class="font-medium text-gray-900">{{ assignmentDetails.created_at }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="assignmentDetails.notes" class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Notes
            </h3>
            <p class="text-sm text-gray-700">{{ assignmentDetails.notes }}</p>
          </div>

          <!-- Installment Schedule Table -->
          <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              Installment Schedule (Kist Plan)
            </h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                  <tr>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">#</th>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">Amount</th>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">Due Date</th>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">Paid</th>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">Remaining</th>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">Payment Date</th>
                    <th class="px-3 py-3 text-xs font-semibold text-gray-700 uppercase text-center">Status</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                  <tr v-for="kist in assignmentDetails.schedule" :key="kist.kist_number" class="hover:bg-gray-50">
                    <td class="px-3 py-3 text-sm text-center font-medium text-gray-900">{{ kist.kist_number }}</td>
                    <td class="px-3 py-3 text-sm text-center font-medium text-gray-900">Rs {{ formatAmount(kist.kist_amount) }}</td>
                    <td class="px-3 py-3 text-sm text-center text-gray-600">{{ kist.due_date }}</td>
                    <td class="px-3 py-3 text-sm text-center font-medium text-green-600">Rs {{ formatAmount(kist.paid_amount) }}</td>
                    <td class="px-3 py-3 text-sm text-center font-medium text-red-600">Rs {{ formatAmount(kist.remaining) }}</td>
                    <td class="px-3 py-3 text-sm text-center text-gray-600">{{ kist.payment_date }}</td>
                    <td class="px-3 py-3 text-center">
                      <span :class="getKistStatusClass(kist.status)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                        {{ kist.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end">
            <Button variant="secondary" @click="showModal = false" class="shadow-sm hover:shadow-md transition-all text-sm">
              Close
            </Button>
          </div>
        </template>
      </Modal>

      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-red-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">Delete Assignment</span>
          </div>
        </template>

        <p class="text-xs sm:text-sm text-gray-600 mt-2">
          Are you sure you want to delete this installment assignment? This action cannot be undone.
        </p>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button
              variant="secondary"
              @click="showDeleteModal = false"
              class="w-full sm:w-auto px-4 sm:px-6 shadow-sm hover:shadow-md transition-all text-sm"
            >
              Cancel
            </Button>
            <Button
              variant="danger"
              @click="confirmDelete"
              :loading="deleting"
              class="w-full sm:w-auto px-4 sm:px-6 shadow-md hover:shadow-lg transition-all text-sm"
            >
              <span v-if="!deleting">Delete Assignment</span>
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
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import Swal from 'sweetalert2'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

// State
const showModal = ref(false)
const showDeleteModal = ref(false)
const deleting = ref(false)
const assignmentToDelete = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobileAssignments = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
const assignmentDetails = ref(null)
const loadingDetails = ref(false)
let table = null

const filters = reactive({
  search: '',
  status: ''
})

// Helper functions
const getStatusClass = (status) => {
  if (status === 'active') return 'bg-green-100 text-green-800'
  if (status === 'completed') return 'bg-blue-100 text-blue-800'
  if (status === 'defaulted') return 'bg-red-100 text-red-800'
  return 'bg-gray-100 text-gray-800'
}

const getKistStatusClass = (status) => {
  if (status === 'paid') return 'bg-green-100 text-green-800'
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800'
  if (status === 'overdue') return 'bg-red-100 text-red-800'
  if (status === 'partial') return 'bg-blue-100 text-blue-800'
  return 'bg-gray-100 text-gray-800'
}

const formatAmount = (value) => {
  if (value === null || value === undefined) return '0'
  return Number(value).toLocaleString()
}

// Show assignment details
const showAssignmentDetails = async (id) => {
  loadingDetails.value = true
  assignmentDetails.value = null
  showModal.value = true

  try {
    const response = await axios.get(route('student-installment-assignments.show', id), {
      headers: { 'Accept': 'application/json' }
    })
    assignmentDetails.value = response.data
  } catch (error) {
    console.error('Error loading assignment details:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Failed to load assignment details',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
  } finally {
    loadingDetails.value = false
  }
}

// Global handler
window.showAssignment = (assignment) => {
  showAssignmentDetails(assignment.id)
}

// Load mobile data
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

    const response = await axios.get(route('student-installment-assignments.index'), {
      params,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })

    if (response.data?.data) {
      mobileAssignments.value = response.data.data
      mobileCurrentPage.value = response.data.current_page || 1
      mobileTotalPages.value = response.data.last_page || 1
      mobileTotal.value = response.data.total || 0
      mobileOffset.value = response.data.from ? response.data.from - 1 : 0
    } else if (Array.isArray(response.data)) {
      mobileAssignments.value = response.data
      mobileTotalPages.value = 1
      mobileTotal.value = response.data.length
      mobileOffset.value = 0
    }
  } catch (error) {
    console.error('Error loading mobile data:', error)
    mobileAssignments.value = []
    mobileTotal.value = 0
  } finally {
    mobileLoading.value = false
  }
}

// Initialize
onMounted(() => {
  loadMobileData()

  table = $('#assignments-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('student-installment-assignments.index'),
      data: function (d) {
        d.search.value = filters.search || tableSearch.value
        if (filters.status) d.status = filters.status
      }
    },
    columns: [
      { data: 'DT_RowIndex',      name: 'DT_RowIndex',      orderable: false, searchable: false },
      { data: 'student_name',     name: 'student_name',     orderable: false },
      { data: 'plan_name',        name: 'plan_name',        orderable: false },
      { data: 'total_amount',     name: 'total_amount' },
      { data: 'amount_paid',      name: 'amount_paid' },
      { data: 'remaining_amount', name: 'remaining_amount' },
      { data: 'status',           name: 'status' },
      { data: 'action',           name: 'action',           orderable: false, searchable: false, className: 'text-center' }
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[0, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="mt-2 text-sm font-medium">No assignments found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      infoFiltered: '(filtered from _MAX_ total entries)',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div></div>',
      paginate: {
        first:    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
        last:     '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
        next:     '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
        previous: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
      }
    },
    drawCallback: function () {
      $('#table-info').empty().append($('#assignments-table_info'))
      $('#table-pagination').empty().append($('#assignments-table_paginate'))
    }
  })
})

// Global handlers for DataTables action buttons
window.editAssignment = (assignment) => {
  router.visit(route('student-installment-assignments.edit', assignment.id))
}
window.deleteAssignment = (id) => {
  assignmentToDelete.value = id
  showDeleteModal.value = true
}

// Delete
const confirmDelete = () => {
  deleting.value = true
  router.delete(route('student-installment-assignments.destroy', assignmentToDelete.value), {
    onSuccess: () => {
      showDeleteModal.value = false
      deleting.value = false
      loadData()
    },
    onError: () => { deleting.value = false }
  })
}

// Pagination
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

// Debounced search
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
  tableSearch.value = ''
  loadData()
}
</script>

<style scoped>
:deep(.dataTables_info) {
  font-size: 0.875rem;
  color: #4b5563;
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
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}
:deep(.paginate_button:hover:not(.disabled)) {
  background: #f3f4f6;
  border-color: #9ca3af;
}
:deep(.paginate_button.current) {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}
:deep(.paginate_button.current:hover) {
  background: #1d4ed8;
  border-color: #1d4ed8;
}
:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}
:deep(#assignments-table_info),
:deep(#assignments-table_paginate) {
  display: none;
}
#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}
:deep(#assignments-table tbody td) {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
@media (min-width: 640px) {
  :deep(#assignments-table tbody td) {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
  }
}
@media (max-width: 1024px) {
  :deep(#assignments-table) { font-size: 0.813rem; }
  :deep(#assignments-table th),
  :deep(#assignments-table td) { padding: 0.5rem; }
}
</style>
