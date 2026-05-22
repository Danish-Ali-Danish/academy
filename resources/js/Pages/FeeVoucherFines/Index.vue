<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Voucher Fines Management</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Preview overdue vouchers, review matching fine rules, then apply fines safely.
              </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
              <Button @click="openOverdueModal" variant="primary" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm">
                Apply Overdue Rules
              </Button>
              <Button @click="$inertia.visit(route('fee-voucher-fines.create'))" variant="secondary" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Apply Manual Fine
              </Button>
            </div>
          </div>
        </div>

        <div v-if="notice" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ notice }}</div>
        <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <Input v-model="filters.search" placeholder="Search fines..." @input="searchDebounced" class="w-full text-sm" />
            <div></div>
            <Button variant="secondary" @click="resetFilters" class="w-full sm:w-auto shadow-sm hover:shadow-md transition-all duration-200 text-sm">
              Reset Filters
            </Button>
          </div>
        </div>

        <div class="hidden md:block bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50 gap-3">
            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
              <span class="text-xs sm:text-sm text-gray-700">Show</span>
              <select v-model="perPage" @change="changePerPage" class="px-3 sm:px-6 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <span class="text-xs sm:text-sm text-gray-700">entries</span>
            </div>
            <div class="w-full sm:w-64">
              <div class="relative">
                <input v-model="tableSearch" @input="tableSearchDebounced" type="text" placeholder="Search in table..." class="w-full pl-9 sm:pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs sm:text-sm" />
                <svg class="absolute left-2.5 sm:left-3 top-2.5 h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table id="fee-voucher-fines-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">#</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Voucher No</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Days Overdue</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Fine Type</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Fine Value</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Calculated</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Applied On</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Applied By</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Status</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white text-center divide-y divide-gray-100"></tbody>
            </table>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 bg-gray-50 gap-3 sm:gap-4">
            <div class="text-xs sm:text-sm text-gray-600" id="table-info"></div>
            <div id="table-pagination"></div>
          </div>
        </div>

        <div class="md:hidden space-y-3 sm:space-y-4">
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
          </div>
          <div v-else-if="mobileItems.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <p class="mt-2 text-sm font-medium text-gray-500">No voucher fines found</p>
          </div>
          <div v-else v-for="(item, index) in mobileItems" :key="item.id" class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                    <h3 class="text-base font-semibold text-gray-900">{{ item.voucher?.voucher_no ?? '-' }}</h3>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">{{ item.voucher?.student_enrollment?.student?.student_name ?? '-' }}</p>
                </div>
                <span :class="item.is_waived ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ item.is_waived ? 'Waived' : 'Applied' }}
                </span>
              </div>
              <div class="space-y-2 border-t border-gray-100 pt-3 text-xs sm:text-sm text-gray-600">
                <div>{{ item.days_overdue }} days overdue</div>
                <div>Rs {{ formatAmount(item.calculated_amount) }}</div>
              </div>
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <button @click="openFineModal(formatMobileFine(item))" class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100">View</button>
                <button @click="$inertia.visit(route('fee-voucher-fines.edit', item.id))" class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">Edit</button>
                <button @click="() => { itemToDelete = item.id; showDeleteModal = true }" class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Modal :show="showOverdueModal" @close="closeOverdueModal" max-width="7xl">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">Preview Overdue Voucher Fines</span>
          </div>
        </template>

        <div class="mt-4 space-y-5">
          <div class="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            One voucher can match more than one active fine rule. Preview shows every voucher-rule match before anything is saved.
          </div>

          <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">As Of Date</label>
              <input v-model="overdueForm.as_of_date" type="date" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Academic Year</label>
              <select v-model="overdueForm.academic_year_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Years</option>
                <option v-for="year in generatorOptions.academicYears" :key="year.id" :value="year.id">{{ year.year_name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Branch</label>
              <select v-model="overdueForm.branch_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Branches</option>
                <option v-for="branch in generatorOptions.branches" :key="branch.id" :value="branch.id">{{ branch.branch_name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Class</label>
              <select v-model="overdueForm.class_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Classes</option>
                <option v-for="classItem in generatorOptions.classes" :key="classItem.id" :value="classItem.id">{{ classItem.class_name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Fee Type</label>
              <select v-model="overdueForm.fee_type_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Fee Types</option>
                <option v-for="feeType in generatorOptions.feeTypes" :key="feeType.id" :value="feeType.id">{{ feeType.fee_name }}</option>
              </select>
            </div>
            <div class="md:col-span-5 flex flex-col sm:flex-row items-end gap-2">
              <Button type="button" variant="primary" :loading="previewLoading" @click="previewOverdueFines" class="w-full sm:w-auto">Preview Fines</Button>
              <Button type="button" variant="secondary" @click="resetOverduePreview" class="w-full sm:w-auto">Reset</Button>
            </div>
          </div>

          <div v-if="generatorError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ generatorError }}</div>
          <div v-if="generatorNotice" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ generatorNotice }}</div>

          <div v-if="previewSummary" class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3"><p class="text-xs text-gray-500">Rule Matches</p><p class="text-lg font-bold text-gray-900">{{ previewSummary.total_candidates }}</p></div>
            <div class="rounded-lg bg-green-50 border border-green-200 p-3"><p class="text-xs text-green-700">Ready</p><p class="text-lg font-bold text-green-800">{{ previewSummary.ready_count }}</p></div>
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3"><p class="text-xs text-amber-700">Already Applied</p><p class="text-lg font-bold text-amber-800">{{ previewSummary.already_applied_count }}</p></div>
            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3"><p class="text-xs text-blue-700">Vouchers</p><p class="text-lg font-bold text-blue-800">{{ previewSummary.voucher_count }}</p></div>
            <div class="rounded-lg bg-red-50 border border-red-200 p-3"><p class="text-xs text-red-700">Fine Amount</p><p class="text-lg font-bold text-red-800">Rs {{ formatAmount(previewSummary.fine_amount) }}</p></div>
          </div>

          <div v-if="previewRows.length" class="rounded-xl border border-gray-200 overflow-hidden">
            <div class="max-h-[430px] overflow-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Voucher</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Student</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Branch/Class</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Fee Type</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Overdue</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Fine Rule</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Remaining</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Fine</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Status</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                  <tr v-for="row in previewRows" :key="`${row.voucher_id}-${row.fine_rule_id}`" :class="row.status !== 'ready' ? 'bg-amber-50/50' : ''">
                    <td class="px-3 py-2 text-sm text-gray-900">
                      <div class="font-semibold">{{ row.voucher_no }}</div>
                      <div class="text-xs text-gray-500">Due {{ formatDate(row.due_date) }}</div>
                    </td>
                    <td class="px-3 py-2 text-sm text-gray-900">
                      <div class="font-medium">{{ row.student_name }}</div>
                      <div class="text-xs text-gray-500">{{ row.roll_no }}</div>
                    </td>
                    <td class="px-3 py-2 text-sm text-gray-600">
                      <div>{{ row.branch_name }}</div>
                      <div class="text-xs text-gray-500">{{ row.class_name }} - {{ row.section_name }} · {{ row.academic_year }}</div>
                    </td>
                    <td class="px-3 py-2 text-sm text-gray-600">{{ row.fee_type }}</td>
                    <td class="px-3 py-2 text-sm text-right font-medium">{{ row.days_overdue }} days</td>
                    <td class="px-3 py-2 text-sm text-gray-600">{{ row.rule_label }}</td>
                    <td class="px-3 py-2 text-sm text-right">Rs {{ formatAmount(row.remaining_amount) }}</td>
                    <td class="px-3 py-2 text-sm text-right font-bold text-red-700">Rs {{ formatAmount(row.calculated_amount) }}</td>
                    <td class="px-3 py-2 text-center">
                      <span v-if="row.status === 'ready'" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ready</span>
                      <span v-else class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Already Applied</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else-if="previewSummary" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">
            No overdue voucher fines found for these filters.
          </div>
        </div>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button variant="secondary" @click="closeOverdueModal" class="w-full sm:w-auto">Cancel</Button>
            <Button variant="primary" :loading="applyingOverdue" :disabled="!previewSummary || previewSummary.ready_count === 0" @click="applyOverdueFines" class="w-full sm:w-auto">
              Apply {{ previewSummary?.ready_count || 0 }} Ready Fines
            </Button>
          </div>
        </template>
      </Modal>

      <Modal :show="showFineModal" @close="closeFineModal" max-width="4xl">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0A9 9 0 113 12a9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <span class="text-base sm:text-lg font-semibold text-gray-900">Voucher Fine Details</span>
              <p class="mt-0.5 text-xs text-gray-500">{{ selectedFine?.voucher_no || '-' }}</p>
            </div>
          </div>
        </template>

        <div v-if="selectedFine" class="mt-4 space-y-4">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
              <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold uppercase tracking-wide text-indigo-900">Student Information</h3>
                <span :class="selectedFine.status === 'Waived' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'" class="px-2 py-1 text-xs font-semibold rounded-full">{{ selectedFine.status }}</span>
              </div>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-gray-500">Name</dt><dd class="font-semibold text-gray-900">{{ selectedFine.student_name }}</dd></div>
                <div><dt class="text-xs text-gray-500">Roll No</dt><dd class="font-semibold text-gray-900">{{ selectedFine.roll_no }}</dd></div>
                <div><dt class="text-xs text-gray-500">Admission No</dt><dd class="font-semibold text-gray-900">{{ selectedFine.admission_no }}</dd></div>
                <div><dt class="text-xs text-gray-500">Applied By</dt><dd class="font-semibold text-gray-900">{{ selectedFine.applied_by }}</dd></div>
              </dl>
            </div>

            <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
              <h3 class="text-sm font-bold uppercase tracking-wide text-emerald-900 mb-3">Class Information</h3>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-gray-500">Branch</dt><dd class="font-semibold text-gray-900">{{ selectedFine.branch_name }}</dd></div>
                <div><dt class="text-xs text-gray-500">Academic Year</dt><dd class="font-semibold text-gray-900">{{ selectedFine.academic_year }}</dd></div>
                <div><dt class="text-xs text-gray-500">Class</dt><dd class="font-semibold text-gray-900">{{ selectedFine.class_name }}</dd></div>
                <div><dt class="text-xs text-gray-500">Section</dt><dd class="font-semibold text-gray-900">{{ selectedFine.section_name }}</dd></div>
              </dl>
            </div>
          </div>

          <div class="rounded-xl border border-purple-100 bg-purple-50/60 p-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-purple-900 mb-3">Fine Rule</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
              <div class="md:col-span-2"><p class="text-xs text-gray-500">Rule</p><p class="font-semibold text-gray-900">{{ selectedFine.rule_label }}</p></div>
              <div><p class="text-xs text-gray-500">Fine Type</p><p class="font-semibold text-gray-900">{{ selectedFine.fine_type }}</p></div>
              <div><p class="text-xs text-gray-500">Fine Value</p><p class="font-semibold text-gray-900">{{ selectedFine.fine_value }}</p></div>
              <div><p class="text-xs text-gray-500">Days Overdue</p><p class="font-semibold text-gray-900">{{ selectedFine.days_overdue }} days</p></div>
              <div><p class="text-xs text-gray-500">Applied On</p><p class="font-semibold text-gray-900">{{ formatDate(selectedFine.applied_on) }}</p></div>
              <div><p class="text-xs text-gray-500">Due Date</p><p class="font-semibold text-gray-900">{{ formatDate(selectedFine.due_date) }}</p></div>
              <div><p class="text-xs text-gray-500">Fee Type</p><p class="font-semibold text-gray-900">{{ selectedFine.fee_type }}</p></div>
            </div>
          </div>

          <div class="rounded-xl border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
              <h3 class="text-sm font-bold uppercase tracking-wide text-gray-800">Voucher Amounts</h3>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-px bg-gray-200 text-sm">
              <div class="bg-white p-3"><p class="text-xs text-gray-500">Original</p><p class="font-bold text-gray-900">Rs {{ formatAmount(selectedFine.original_amount) }}</p></div>
              <div class="bg-white p-3"><p class="text-xs text-gray-500">Discount</p><p class="font-bold text-green-700">Rs {{ formatAmount(selectedFine.discount_amount) }}</p></div>
              <div class="bg-white p-3"><p class="text-xs text-gray-500">Fine Total</p><p class="font-bold text-red-700">Rs {{ formatAmount(selectedFine.voucher_fine_amount) }}</p></div>
              <div class="bg-white p-3"><p class="text-xs text-gray-500">Net</p><p class="font-bold text-indigo-700">Rs {{ formatAmount(selectedFine.net_amount) }}</p></div>
              <div class="bg-white p-3"><p class="text-xs text-gray-500">Paid</p><p class="font-bold text-blue-700">Rs {{ formatAmount(selectedFine.paid_amount) }}</p></div>
              <div class="bg-white p-3"><p class="text-xs text-gray-500">Remaining</p><p class="font-bold text-red-700">Rs {{ formatAmount(selectedFine.remaining_amount) }}</p></div>
            </div>
          </div>

          <div class="rounded-xl border border-red-200 bg-red-50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-red-700">This Fine Amount</p>
              <p class="text-2xl font-bold text-red-800">Rs {{ formatAmount(selectedFine.calculated_amount) }}</p>
            </div>
            <p v-if="selectedFine.notes" class="text-sm text-red-700 sm:text-right">{{ selectedFine.notes }}</p>
          </div>
        </div>

        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button variant="secondary" @click="closeFineModal" class="w-full sm:w-auto">Close</Button>
            <Button variant="primary" @click="editSelectedFine" class="w-full sm:w-auto">Edit Fine</Button>
          </div>
        </template>
      </Modal>

      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-red-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">Delete Voucher Fine</span>
          </div>
        </template>
        <p class="text-xs sm:text-sm text-gray-600 mt-2">Are you sure you want to delete this voucher fine? This will reverse the fine amount from the voucher.</p>
        <template #footer>
          <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
            <Button variant="secondary" @click="showDeleteModal = false" class="w-full sm:w-auto px-4 sm:px-6 text-sm">Cancel</Button>
            <Button variant="danger" @click="confirmDelete" :loading="deleting" class="w-full sm:w-auto px-4 sm:px-6 text-sm">Delete Fine</Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Input from '@/Components/Forms/Input.vue'
import Modal from '@/Components/Common/Modal.vue'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

const props = defineProps({
  generatorOptions: {
    type: Object,
    default: () => ({ branches: [], classes: [], feeTypes: [], academicYears: [] }),
  },
})

const generatorOptions = props.generatorOptions
const showDeleteModal = ref(false)
const showOverdueModal = ref(false)
const showFineModal = ref(false)
const deleting = ref(false)
const applyingOverdue = ref(false)
const previewLoading = ref(false)
const itemToDelete = ref(null)
const selectedFine = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const notice = ref('')
const error = ref('')
const generatorError = ref('')
const generatorNotice = ref('')
const previewRows = ref([])
const previewSummary = ref(null)
const mobileItems = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
let table = null

const filters = reactive({ search: '' })
const overdueForm = reactive({
  as_of_date: new Date().toISOString().slice(0, 10),
  academic_year_id: generatorOptions.academicYears?.[0]?.id || '',
  branch_id: '',
  class_id: '',
  fee_type_id: '',
})

const formatAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (value) => value ? new Date(value).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const fineTypeLabel = (type) => String(type || '-').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
const fineValueLabel = (type, value) => String(type || '').includes('percentage') ? `${formatAmount(value)}%` : `Rs ${formatAmount(value)}`
const overduePayload = () => ({
  as_of_date: overdueForm.as_of_date || null,
  academic_year_id: overdueForm.academic_year_id || null,
  branch_id: overdueForm.branch_id || null,
  class_id: overdueForm.class_id || null,
  fee_type_id: overdueForm.fee_type_id || null,
})

const openOverdueModal = () => {
  showOverdueModal.value = true
}

const closeOverdueModal = () => {
  showOverdueModal.value = false
}

const openFineModal = (fine) => {
  selectedFine.value = fine
  showFineModal.value = true
}

const closeFineModal = () => {
  showFineModal.value = false
  selectedFine.value = null
}

const editSelectedFine = () => {
  if (!selectedFine.value?.id) return
  router.visit(route('fee-voucher-fines.edit', selectedFine.value.id))
}

const formatMobileFine = (fine) => {
  const voucher = fine.voucher || {}
  const enrollment = voucher.student_enrollment || {}
  const student = enrollment.student || {}
  const classSection = enrollment.class_section || {}
  const branchClass = classSection.branch_class || {}
  const rule = fine.fine_rule || {}

  return {
    id: fine.id,
    voucher_no: voucher.voucher_no || '-',
    student_name: student.student_name || '-',
    roll_no: enrollment.roll_number || student.roll_no || '-',
    admission_no: student.admission_no || '-',
    branch_name: enrollment.branch?.branch_name || '-',
    class_name: branchClass.class?.class_name || '-',
    section_name: classSection.section?.section_name || '-',
    academic_year: voucher.academic_year?.year_name || '-',
    fee_type: voucher.fee_type?.fee_name || '-',
    voucher_status: voucher.status || '-',
    original_amount: voucher.original_amount || 0,
    discount_amount: voucher.discount_amount || 0,
    voucher_fine_amount: voucher.fine_amount || 0,
    net_amount: voucher.net_amount || 0,
    paid_amount: voucher.paid_amount || 0,
    remaining_amount: voucher.remaining_amount || 0,
    due_date: voucher.due_date,
    days_overdue: fine.days_overdue || 0,
    fine_type: fineTypeLabel(fine.fine_type),
    fine_value: fineValueLabel(fine.fine_type, fine.fine_value),
    calculated_amount: fine.calculated_amount || 0,
    applied_on: fine.applied_on,
    applied_by: fine.applied_by?.name || 'System',
    status: fine.is_waived ? 'Waived' : 'Applied',
    rule_label: rule.description || 'Manual Fine',
    notes: fine.notes,
  }
}

const resetOverduePreview = () => {
  overdueForm.as_of_date = new Date().toISOString().slice(0, 10)
  overdueForm.academic_year_id = generatorOptions.academicYears?.[0]?.id || ''
  overdueForm.branch_id = ''
  overdueForm.class_id = ''
  overdueForm.fee_type_id = ''
  previewRows.value = []
  previewSummary.value = null
  generatorError.value = ''
  generatorNotice.value = ''
}

const previewOverdueFines = async () => {
  generatorError.value = ''
  generatorNotice.value = ''
  previewRows.value = []
  previewSummary.value = null

  previewLoading.value = true
  try {
    const response = await axios.post(route('fee-voucher-fines.preview-overdue'), overduePayload(), { headers: { Accept: 'application/json' } })
    previewRows.value = response.data.rows || []
    previewSummary.value = response.data.summary || null
  } catch (e) {
    generatorError.value = e.response?.data?.message || 'Fine preview generate nahi ho saka.'
  } finally {
    previewLoading.value = false
  }
}

const applyOverdueFines = async () => {
  if (!previewSummary.value || previewSummary.value.ready_count === 0) return

  applyingOverdue.value = true
  notice.value = ''
  error.value = ''
  generatorError.value = ''
  generatorNotice.value = ''

  try {
    const response = await axios.post(route('fee-voucher-fines.apply-overdue'), overduePayload(), { headers: { Accept: 'application/json' } })
    generatorNotice.value = response.data?.message || 'Overdue fines applied.'
    notice.value = generatorNotice.value
    loadData()
    await previewOverdueFines()
  } catch (e) {
    generatorError.value = e.response?.data?.message || 'Overdue fines apply nahi ho sake.'
  } finally {
    applyingOverdue.value = false
  }
}

const loadMobileData = async () => {
  mobileLoading.value = true
  try {
    const params = { page: mobileCurrentPage.value, per_page: perPage.value, mobile: 1 }
    if (filters.search) params.search = filters.search
    if (tableSearch.value) params.search = tableSearch.value
    const response = await axios.get(route('fee-voucher-fines.index'), { params, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
    if (response.data?.data) {
      mobileItems.value = response.data.data
      mobileCurrentPage.value = response.data.current_page || 1
      mobileTotalPages.value = response.data.last_page || 1
      mobileTotal.value = response.data.total || 0
      mobileOffset.value = response.data.from ? response.data.from - 1 : 0
    }
  } catch (e) {
    mobileItems.value = []
    mobileTotal.value = 0
  } finally {
    mobileLoading.value = false
  }
}

onMounted(() => {
  loadMobileData()
  table = $('#fee-voucher-fines-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('fee-voucher-fines.index'),
      data: function (d) {
        d.search.value = filters.search || tableSearch.value
      },
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { data: 'voucher_no', name: 'voucher_id' },
      { data: 'days_overdue', name: 'days_overdue' },
      { data: 'fine_type', name: 'fine_type' },
      { data: 'fine_value', name: 'fine_value' },
      { data: 'calculated_amount', name: 'calculated_amount' },
      { data: 'applied_on', name: 'applied_on' },
      { data: 'applied_by', name: 'applied_by', orderable: false, searchable: false },
      { data: 'is_waived', name: 'is_waived', orderable: false },
      { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[1, 'desc']],
    searching: true,
    info: true,
    responsive: true,
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><p class="mt-2 text-sm font-medium">No voucher fines found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      infoFiltered: '(filtered from _MAX_ total entries)',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div></div>',
      paginate: {
        first: '«',
        last: '»',
        next: '›',
        previous: '‹',
      },
    },
    drawCallback: function () {
      $('#table-info').empty().append($('#fee-voucher-fines-table_info'))
      $('#table-pagination').empty().append($('#fee-voucher-fines-table_paginate'))
    },
  })
})

window.editFine = (fine) => {
  router.visit(route('fee-voucher-fines.edit', fine.id))
}

window.showFine = (fine) => {
  openFineModal(fine)
}

window.deleteFine = (id) => {
  itemToDelete.value = id
  showDeleteModal.value = true
}

const confirmDelete = () => {
  deleting.value = true
  router.delete(route('fee-voucher-fines.destroy', itemToDelete.value), {
    onSuccess: () => {
      showDeleteModal.value = false
      deleting.value = false
      loadData()
    },
    onError: () => {
      deleting.value = false
    },
  })
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

:deep(.paginate_button.current) {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}

:deep(#fee-voucher-fines-table_info),
:deep(#fee-voucher-fines-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#fee-voucher-fines-table tbody td) {
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
}
</style>
