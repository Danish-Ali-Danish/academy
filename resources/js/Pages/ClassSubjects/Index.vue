<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Class Subjects Management</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Manage subject assignments for sections (individual & groups)
              </p>
            </div>
            <div>
              <Button 
                @click="router.visit(route('class-subjects.create'))"
                variant="primary"
                class="px-4 sm:px-6 py-2 sm:py-2.5 text-sm font-medium"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Assign Subjects
              </Button>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div v-if="showSuccessMessage && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative animate-fade-in">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm">{{ $page.props.flash.success }}</span>
            </div>
            <button @click="showSuccessMessage = false" class="text-green-700 hover:text-green-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <div v-if="showErrorMessage && $page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative animate-fade-in">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm">{{ $page.props.flash.error }}</span>
            </div>
            <button @click="showErrorMessage = false" class="text-red-700 hover:text-red-900">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800">Filter Sections</h3>
            <Button 
              variant="secondary" 
              @click="resetFilters"
              class="text-xs px-3 py-1.5"
            >
              Reset All
            </Button>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- Search -->
            <div>
              <input
                v-model="filters.search"
                @input="searchDebounced"
                type="text"
                placeholder="Search sections..."
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
            
            <!-- Branch Filter -->
            <div>
              <select
                v-model="filters.branch_id"
                @change="onFilterBranchChange"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Branches</option>
                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                  {{ branch.branch_name }}
                </option>
              </select>
            </div>

            <!-- Class Filter -->
            <div>
              <select
                v-model="filters.branch_class_id"
                @change="onFilterClassChange"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                :disabled="!filters.branch_id || loadingFilterClasses"
              >
                <option value="">
                  {{ !filters.branch_id ? 'Select Branch First' : loadingFilterClasses ? 'Loading...' : 'All Classes' }}
                </option>
                <option v-for="cls in filterClasses" :key="cls.id" :value="cls.id">
                  {{ cls.class_name }}
                </option>
              </select>
            </div>

            <!-- Section Filter -->
            <div>
              <select
                v-model="filters.class_section_id"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                :disabled="!filters.branch_class_id || loadingFilterSections"
              >
                <option value="">
                  {{ !filters.branch_class_id ? 'Select Class First' : loadingFilterSections ? 'Loading...' : 'All Sections' }}
                </option>
                <option v-for="section in filterSections" :key="section.id" :value="section.id">
                  {{ section.section_name }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <select
                v-model="filters.is_active"
                @change="loadData"
                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
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
                class="px-3 sm:px-6 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm"
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
                  class="w-full pl-9 sm:pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs sm:text-sm"
                />
                <svg class="absolute left-2.5 sm:left-3 top-2.5 h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table id="class-subjects-table" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    #
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-left">
                    Section Details
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Total Subjects
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Individual
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Groups
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Status
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider text-center">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <!-- DataTables will populate this -->
              </tbody>
            </table>
          </div>

          <!-- Table Footer -->
          <div class="flex flex-col sm:flex-row items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 bg-gray-50 gap-3 sm:gap-4">
            <div class="text-xs sm:text-sm text-gray-600" id="table-info">
              <!-- Info will be inserted here -->
            </div>
            <div id="table-pagination">
              <!-- Pagination will be inserted here -->
            </div>
          </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3 sm:space-y-4">
          <!-- Loading State -->
          <div v-if="mobileLoading" class="flex items-center justify-center py-12 bg-white rounded-lg shadow">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="mobileClassSections.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No sections with subjects found</p>
            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
          </div>

          <!-- Section Cards -->
          <div v-else v-for="(classSection, index) in mobileClassSections" :key="classSection.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-4">
              <!-- Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                  </div>
                  <h3 class="text-sm font-semibold text-gray-900">
                    {{ classSection.branchClass.branch.branch_name }}
                  </h3>
                  <p class="text-xs text-gray-600 mt-0.5">
                    {{ classSection.branchClass.class.class_name }} - {{ classSection.section.section_name }}
                  </p>
                </div>
                <span :class="getStatusClass(classSection.is_active)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                  {{ classSection.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>

              <!-- Subjects Count Badge -->
              <div class="mb-3">
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ classSection.class_subjects_count }} Assignment(s)
                </div>
              </div>

              <!-- Breakdown -->
              <div class="space-y-2 border-t border-gray-100 pt-3">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600">Individual Subjects:</span>
                  <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded font-medium">
                    {{ classSection.individual_subjects_count || 0 }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="text-gray-600">Subject Groups:</span>
                  <span class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded font-medium">
                    {{ classSection.subject_groups_count || 0 }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                <button 
                  @click="viewClassSubjects(classSection.id)"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  View Subjects
                </button>
                <button 
                  @click="router.visit(route('class-subjects.create', { class_section_id: classSection.id }))"
                  class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                  </svg>
                  Add More
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div v-if="mobileClassSections.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
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
              <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total sections</div>
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

      <!-- View Subjects Modal -->
      <Modal :show="showViewModal" @close="showViewModal = false" size="2xl">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
              </svg>
            </div>
            <div v-if="viewingSection.class_section">
              <span class="text-base sm:text-lg font-semibold text-gray-900">{{ viewingSection.class_section.branch_name }}</span>
              <p class="text-xs text-gray-500 mt-1">{{ viewingSection.class_section.class_name }} - {{ viewingSection.class_section.section_name }}</p>
            </div>
          </div>
        </template>
        
        <div v-if="loadingViewSubjects" class="py-8 text-center">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto"></div>
        </div>

        <div v-else class="space-y-4">
          <!-- Summary -->
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-blue-50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-blue-600">{{ viewingSection.total_assignments || 0 }}</div>
              <div class="text-xs text-gray-600 mt-1">Assignments</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-green-600">{{ viewingSection.total_unique_subjects || 0 }}</div>
              <div class="text-xs text-gray-600 mt-1">Total Subjects</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold text-purple-600">{{ viewingSection.subject_groups?.length || 0 }}</div>
              <div class="text-xs text-gray-600 mt-1">Groups</div>
            </div>
          </div>

          <!-- Individual Subjects -->
          <div v-if="viewingSection.individual_subjects?.length > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
              <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
              </svg>
              Individual Subjects ({{ viewingSection.individual_subjects.length }})
            </h4>
            <div class="space-y-2">
              <div
                v-for="subject in viewingSection.individual_subjects"
                :key="subject.id"
                class="flex items-center justify-between p-3 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-100 transition-colors"
              >
                <div class="flex-1">
                  <div class="font-medium text-gray-900">{{ subject.subject_name }}</div>
                  <div class="text-xs text-gray-500 mt-0.5">Code: {{ subject.subject_code }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <span v-if="subject.is_compulsory" class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    Compulsory
                  </span>
                  <span v-else class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                    Elective
                  </span>
                  <span :class="subject.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ subject.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Subject Groups -->
          <div v-if="viewingSection.subject_groups?.length > 0">
            <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
              <svg class="w-4 h-4 mr-1.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Subject Groups ({{ viewingSection.subject_groups.length }})
            </h4>
            <div class="space-y-3">
              <div
                v-for="group in viewingSection.subject_groups"
                :key="group.group_id"
                class="border border-purple-200 rounded-lg p-3 bg-purple-50"
              >
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <div class="font-medium text-purple-900">{{ group.group_name }}</div>
                    <div v-if="group.description" class="text-xs text-gray-600 mt-0.5">{{ group.description }}</div>
                  </div>
                  <span :class="group.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ group.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
                <div class="space-y-1 mt-2">
                  <div
                    v-for="subject in group.subjects"
                    :key="subject.id"
                    class="flex items-center justify-between p-2 bg-white rounded border border-purple-100"
                  >
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ subject.subject_name }}</div>
                      <div class="text-xs text-gray-500">{{ subject.subject_code }}</div>
                    </div>
                    <span v-if="subject.is_compulsory" class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                      Compulsory
                    </span>
                    <span v-else class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                      Elective
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!viewingSection.individual_subjects?.length && !viewingSection.subject_groups?.length" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500">No subjects assigned to this section</p>
            <Button 
              variant="primary" 
              @click="router.visit(route('class-subjects.create', { class_section_id: viewingSection.class_section.id }))"
              class="mt-4"
            >
              Assign Subjects
            </Button>
          </div>
        </div>

        <template #footer>
          <div class="flex items-center justify-between w-full">
            <Button 
              variant="secondary" 
              @click="router.visit(route('class-subjects.create', { class_section_id: viewingSection.class_section?.id }))"
              class="px-4 text-sm"
              v-if="viewingSection.class_section"
            >
              Add More Subjects
            </Button>
            <Button 
              variant="primary" 
              @click="showViewModal = false"
              class="px-6 text-sm ml-auto"
            >
              Close
            </Button>
          </div>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Modal from '@/Components/Common/Modal.vue'
import $ from 'jquery'
import 'datatables.net'
import axios from 'axios'

// Props
const props = defineProps({
  branches: Array,
})

// State
const showViewModal = ref(false)
const tableSearch = ref('')
const perPage = ref(10)
const mobileClassSections = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)
const viewingSection = ref({})
const loadingViewSubjects = ref(false)

// Filter cascading state
const filterClasses = ref([])
const filterSections = ref([])
const loadingFilterClasses = ref(false)
const loadingFilterSections = ref(false)

let table = null
let tableSearchTimeout = null
let searchTimeout = null
let abortController = null

const filters = reactive({
  search: '',
  branch_id: '',
  branch_class_id: '',
  class_section_id: '',
  is_active: ''
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => {
      showSuccessMessage.value = false
    }, 5000)
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => {
      showErrorMessage.value = false
    }, 5000)
  }
}, { deep: true, immediate: true })

// Helper functions
const getStatusClass = (isActive) => {
  return isActive 
    ? 'bg-green-100 text-green-800' 
    : 'bg-gray-100 text-gray-800'
}

// Filter branch change handler
const onFilterBranchChange = async () => {
  filters.branch_class_id = ''
  filters.class_section_id = ''
  filterClasses.value = []
  filterSections.value = []
  
  if (!filters.branch_id) {
    loadData()
    return
  }
  
  loadingFilterClasses.value = true
  
  try {
    const response = await axios.get(route('class-subjects.classes-by-branch'), {
      params: { branch_id: filters.branch_id }
    })
    filterClasses.value = response.data
  } catch (error) {
    console.error('Error loading filter classes:', error)
  } finally {
    loadingFilterClasses.value = false
  }
  
  loadData()
}

// Filter class change handler
const onFilterClassChange = async () => {
  filters.class_section_id = ''
  filterSections.value = []
  
  if (!filters.branch_class_id) {
    loadData()
    return
  }
  
  loadingFilterSections.value = true
  
  try {
    const response = await axios.get(route('class-subjects.sections-by-class'), {
      params: { branch_class_id: filters.branch_class_id }
    })
    filterSections.value = response.data
  } catch (error) {
    console.error('Error loading filter sections:', error)
  } finally {
    loadingFilterSections.value = false
  }
  
  loadData()
}

// View class subjects
const viewClassSubjects = async (classSectionId) => {
  showViewModal.value = true
  loadingViewSubjects.value = true
  viewingSection.value = {}
  
  try {
    const response = await axios.get(route('class-subjects.show', classSectionId))
    viewingSection.value = response.data
  } catch (error) {
    console.error('Error loading class subjects:', error)
  } finally {
    loadingViewSubjects.value = false
  }
}

// Make it available globally for DataTables
window.viewClassSubjects = viewClassSubjects

// Load mobile data
const loadMobileData = async () => {
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()
  
  mobileLoading.value = true
  
  try {
    const params = {
      page: mobileCurrentPage.value,
      per_page: perPage.value,
      mobile: 1
    }
    
    if (filters.search) params.search = filters.search
    if (tableSearch.value) params.search = tableSearch.value
    if (filters.branch_id) params.branch_id = filters.branch_id
    if (filters.branch_class_id) params.branch_class_id = filters.branch_class_id
    if (filters.class_section_id) params.class_section_id = filters.class_section_id
    if (filters.is_active !== '') params.is_active = filters.is_active
    
    const response = await axios.get(route('class-subjects.index'), {
      params,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      signal: abortController.signal
    })
    
    if (response.data) {
      if (response.data.data) {
        mobileClassSections.value = response.data.data
        mobileCurrentPage.value = response.data.current_page || 1
        mobileTotalPages.value = response.data.last_page || 1
        mobileTotal.value = response.data.total || 0
        mobileOffset.value = response.data.from ? response.data.from - 1 : 0
      } else if (Array.isArray(response.data)) {
        mobileClassSections.value = response.data
        mobileTotalPages.value = 1
        mobileTotal.value = response.data.length
        mobileOffset.value = 0
      }
    }
  } catch (error) {
    if (!axios.isCancel(error)) {
      console.error('Error loading mobile data:', error)
      mobileClassSections.value = []
      mobileTotal.value = 0
    }
  } finally {
    mobileLoading.value = false
    abortController = null
  }
}

// Initialize on mount
onMounted(() => {
  loadMobileData()

  // Initialize desktop table
  table = $('#class-subjects-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: route('class-subjects.index'),
      data: function(d) {
        d.search.value = filters.search || tableSearch.value
        if (filters.branch_id) d.branch_id = filters.branch_id
        if (filters.branch_class_id) d.branch_class_id = filters.branch_class_id
        if (filters.class_section_id) d.class_section_id = filters.class_section_id
        if (filters.is_active !== '') d.is_active = filters.is_active
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      { 
        data: 'section_info',
        name: 'section_info',
        render: function(data, type, row) {
          return `<div class="text-left">
                    <div class="font-medium text-gray-900">${data.branch}</div>
                    <div class="text-sm text-gray-500">${data.class} - ${data.section}</div>
                  </div>`;
        }
      },
      { 
        data: 'subjects_count',
        name: 'subjects_count',
        orderable: false,
        render: function(data) {
          return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${data}</span>`;
        }
      },
      { 
        data: 'individual_count',
        name: 'individual_count',
        orderable: false,
        render: function(data) {
          return `<span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium">${data}</span>`;
        }
      },
      { 
        data: 'group_count',
        name: 'group_count',
        orderable: false,
        render: function(data) {
          return `<span class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded text-xs font-medium">${data}</span>`;
        }
      },
      { 
        data: 'is_active',
        name: 'is_active',
        orderable: false,
        render: function(data) {
          return data 
            ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>'
            : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>';
        }
      },
      { 
        data: 'id',
        name: 'action',
        orderable: false,
        searchable: false,
        className: 'text-center',
        render: function(data, type, row) {
          return `
            <div class="flex items-center justify-center gap-2">
              <button onclick="viewClassSubjects(${data})" class="text-green-600 hover:text-green-800" title="View Subjects">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
              <a href="/class-subjects/create?class_section_id=${data}" class="text-blue-600 hover:text-blue-800" title="Add More Subjects">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
              </a>
            </div>
          `;
        }
      }
    ],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[1, 'asc']],
    searching: true,
    info: true,
    responsive: true,
    
    dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
    language: {
      emptyTable: '<div class="text-center py-12 text-gray-500"><svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><p class="mt-2 text-sm font-medium">No sections with subjects found</p></div>',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'Showing 0 to 0 of 0 entries',
      infoFiltered: '(filtered from _MAX_ total entries)',
      processing: '<div class="flex items-center justify-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div></div>',
      paginate: {
        first: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>',
        last: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>',
        next: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
        previous: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
      }
    },
    drawCallback: function () {
      const info = $('#class-subjects-table_info')
      $('#table-info').empty().append(info)

      const paginate = $('#class-subjects-table_paginate')
      $('#table-pagination').empty().append(paginate)
    }
  })
})

// Cleanup on unmount
onBeforeUnmount(() => {
  if (tableSearchTimeout) clearTimeout(tableSearchTimeout)
  if (searchTimeout) clearTimeout(searchTimeout)
  if (abortController) abortController.abort()
  if (table) {
    table.destroy()
    table = null
  }
})

// Mobile pagination
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

// Table search with debounce
const tableSearchDebounced = () => {
  clearTimeout(tableSearchTimeout)
  tableSearchTimeout = setTimeout(() => {
    loadData()
  }, 500)
}

// Filter search with debounce
const searchDebounced = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadData()
  }, 500)
}

// Change per page
const changePerPage = () => {
  if (table) {
    table.page.len(perPage.value).draw()
  }
  mobileCurrentPage.value = 1
  loadMobileData()
}

// Reload table
const loadData = () => {
  if (table) {
    table.ajax.reload()
  }
  mobileCurrentPage.value = 1
  loadMobileData()
}

// Reset filters
const resetFilters = () => {
  filters.search = ''
  filters.branch_id = ''
  filters.branch_class_id = ''
  filters.class_section_id = ''
  filters.is_active = ''
  tableSearch.value = ''
  filterClasses.value = []
  filterSections.value = []
  loadData()
}
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

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
  background: #4f46e5;
  color: white;
  border-color: #4f46e5;
}

:deep(.paginate_button.current:hover) {
  background: #4338ca;
  border-color: #4338ca;
}

:deep(.paginate_button.disabled) {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f9fafb;
}

:deep(#class-subjects-table_info),
:deep(#class-subjects-table_paginate) {
  display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
  display: block;
}

:deep(#class-subjects-table tbody td) {
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
  vertical-align: middle;
}

@media (max-width: 1024px) {
  :deep(#class-subjects-table) {
    font-size: 0.813rem;
  }
  
  :deep(#class-subjects-table th),
  :deep(#class-subjects-table td) {
    padding: 0.5rem;
  }
}
</style>