<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <!-- Main Content -->
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <div class="mb-4 sm:mb-6 lg:mb-8">
          <div class="flex items-center gap-3 mb-3">
            <button 
              @click="router.visit(route('class-subjects.index'))"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Assign Subjects to Class</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">
                Select branch → class → choose individual subjects or subject group
              </p>
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

        <!-- Create Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="submit">
            <!-- Step 1: Branch Selection -->
            <div class="mb-6">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  1
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Branch</h3>
              </div>
              
              <div class="pl-11">
                <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Branch <span class="text-red-500">*</span>
                </label>
                <select
                  id="branch_id"
                  v-model="form.branch_id"
                  @change="onBranchChange"
                  class="block w-full md:w-2/3 lg:w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.branch_id }"
                  required
                >
                  <option value="">-- Select Branch --</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.branch_name }}{{ branch.location ? ` - ${branch.location}` : '' }}
                  </option>
                </select>
                <p v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.branch_id }}
                </p>
              </div>
            </div>

            <!-- Step 2: Class Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm mr-3 font-semibold"
                     :class="form.branch_id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                  2
                </div>
                <h3 class="text-base font-medium" :class="form.branch_id ? 'text-gray-900' : 'text-gray-400'">
                  Select Class
                </h3>
              </div>
              
              <div class="pl-11">
                <label for="branch_class_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Class <span class="text-red-500">*</span>
                </label>
                <select
                  id="branch_class_id"
                  v-model="form.branch_class_id"
                  @change="onClassChange"
                  class="block w-full md:w-2/3 lg:w-1/2 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-500': form.errors.branch_class_id }"
                  :disabled="!form.branch_id || loadingClasses"
                  required
                >
                  <option value="">
                    {{ !form.branch_id ? '-- Select Branch First --' : loadingClasses ? 'Loading classes...' : '-- Select Class --' }}
                  </option>
                  <option v-for="bc in availableClasses" :key="bc.id" :value="bc.id">
                    {{ bc.class_name }}
                  </option>
                </select>
                <p v-if="form.errors.branch_class_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.branch_class_id }}
                </p>
                <p v-if="!form.branch_id" class="mt-1 text-xs text-gray-500">
                  Please select a branch first
                </p>
              </div>
            </div>

            <!-- Step 3: Assignment Type Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm mr-3 font-semibold"
                     :class="form.branch_class_id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                  3
                </div>
                <h3 class="text-base font-medium" :class="form.branch_class_id ? 'text-gray-900' : 'text-gray-400'">
                  Assignment Type
                </h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Choose Assignment Type <span class="text-red-500">*</span>
                </label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Individual Subjects Option -->
                  <label
                    class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="[
                      form.assignment_type === 'individual' 
                        ? 'border-indigo-500 bg-indigo-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white',
                      !form.branch_class_id ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  >
                    <input
                      type="radio"
                      value="individual"
                      v-model="form.assignment_type"
                      :disabled="!form.branch_class_id"
                      class="sr-only"
                    />
                    <div class="flex items-center mb-2">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                           :class="form.assignment_type === 'individual' ? 'bg-indigo-100' : 'bg-gray-100'">
                        <svg class="w-5 h-5" :class="form.assignment_type === 'individual' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                      </div>
                      <div class="ml-3 flex-1">
                        <span class="block text-sm font-semibold" :class="form.assignment_type === 'individual' ? 'text-indigo-900' : 'text-gray-900'">
                          Individual Subjects
                        </span>
                      </div>
                      <svg v-if="form.assignment_type === 'individual'" class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <p class="text-xs text-gray-600 ml-13">
                      Assign multiple subjects at once (for classes 1-8)
                    </p>
                  </label>

                  <!-- Subject Group Option -->
                  <label
                    class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="[
                      form.assignment_type === 'group' 
                        ? 'border-purple-500 bg-purple-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white',
                      !form.branch_class_id ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  >
                    <input
                      type="radio"
                      value="group"
                      v-model="form.assignment_type"
                      :disabled="!form.branch_class_id"
                      class="sr-only"
                    />
                    <div class="flex items-center mb-2">
                      <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                           :class="form.assignment_type === 'group' ? 'bg-purple-100' : 'bg-gray-100'">
                        <svg class="w-5 h-5" :class="form.assignment_type === 'group' ? 'text-purple-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                      </div>
                      <div class="ml-3 flex-1">
                        <span class="block text-sm font-semibold" :class="form.assignment_type === 'group' ? 'text-purple-900' : 'text-gray-900'">
                          Subject Group
                        </span>
                      </div>
                      <svg v-if="form.assignment_type === 'group'" class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <p class="text-xs text-gray-600 ml-13">
                      Assign a group (Science, Arts, Commerce) for classes 9-12
                    </p>
                  </label>
                </div>
                
                <p v-if="form.errors.assignment_type" class="mt-2 text-sm text-red-600">
                  {{ form.errors.assignment_type }}
                </p>
                <p v-if="!form.branch_class_id" class="mt-2 text-xs text-gray-500">
                  Please select a class first
                </p>
              </div>
            </div>

            <!-- Step 4a: Individual Subject Selection (MULTI-SELECT) -->
            <div v-if="form.assignment_type === 'individual'" class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  4
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Subjects (Multiple)</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Subjects <span class="text-red-500">*</span>
                  <span class="text-xs text-gray-500 ml-2">({{ form.subject_ids.length }} selected)</span>
                </label>
                
                <!-- Compulsory Subjects -->
                <div v-if="compulsorySubjects.length > 0" class="mb-4">
                  <div class="flex items-center mb-2">
                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-700">Compulsory Subjects</span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label 
                      v-for="subject in compulsorySubjects" 
                      :key="subject.id"
                      class="relative flex items-center p-3 cursor-pointer rounded-lg border transition-all"
                      :class="form.subject_ids.includes(subject.id) 
                        ? 'border-indigo-500 bg-indigo-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white'"
                    >
                      <input
                        type="checkbox"
                        :value="subject.id"
                        v-model="form.subject_ids"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      />
                      <div class="ml-3 flex-1">
                        <span class="block text-sm font-medium text-gray-900">
                          {{ subject.subject_name }}
                        </span>
                        <span class="block text-xs text-gray-500">
                          {{ subject.subject_code }}
                        </span>
                      </div>
                      <svg v-if="form.subject_ids.includes(subject.id)" class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </label>
                  </div>
                </div>

                <!-- Elective Subjects -->
                <div v-if="electiveSubjects.length > 0">
                  <div class="flex items-center mb-2">
                    <svg class="w-4 h-4 text-amber-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-700">Elective Subjects</span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label 
                      v-for="subject in electiveSubjects" 
                      :key="subject.id"
                      class="relative flex items-center p-3 cursor-pointer rounded-lg border transition-all"
                      :class="form.subject_ids.includes(subject.id) 
                        ? 'border-amber-500 bg-amber-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white'"
                    >
                      <input
                        type="checkbox"
                        :value="subject.id"
                        v-model="form.subject_ids"
                        class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                      />
                      <div class="ml-3 flex-1">
                        <span class="block text-sm font-medium text-gray-900">
                          {{ subject.subject_name }}
                        </span>
                        <span class="block text-xs text-gray-500">
                          {{ subject.subject_code }}
                        </span>
                      </div>
                      <svg v-if="form.subject_ids.includes(subject.id)" class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </label>
                  </div>
                </div>

                <!-- Quick Actions -->
                <div class="flex gap-2 mt-4">
                  <button
                    type="button"
                    @click="selectAllSubjects"
                    class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                  >
                    Select All
                  </button>
                  <button
                    type="button"
                    @click="clearAllSubjects"
                    class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                  >
                    Clear All
                  </button>
                </div>

                <p v-if="form.errors.subject_ids" class="mt-2 text-sm text-red-600">
                  {{ form.errors.subject_ids }}
                </p>
                <p class="mt-2 text-xs text-gray-500">
                  Select one or more subjects to assign to this class
                </p>
              </div>
            </div>

            <!-- Step 4b: Subject Group Selection (if group type) -->
            <div v-if="form.assignment_type === 'group'" class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 text-purple-600 font-semibold text-sm mr-3">
                  4
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Subject Group</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Subject Group <span class="text-red-500">*</span>
                </label>
                
                <div class="space-y-3">
                  <label
                    v-for="group in subjectGroups"
                    :key="group.id"
                    class="relative flex items-start p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="form.subject_group_id === group.id 
                      ? 'border-purple-500 bg-purple-50' 
                      : 'border-gray-200 hover:border-gray-300 bg-white'"
                  >
                    <input
                      type="radio"
                      :value="group.id"
                      v-model="form.subject_group_id"
                      class="sr-only"
                    />
                    <div class="flex-1">
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold" :class="form.subject_group_id === group.id ? 'text-purple-900' : 'text-gray-900'">
                          {{ group.group_name }}
                        </span>
                        <svg v-if="form.subject_group_id === group.id" class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                      </div>
                      <p v-if="group.description" class="text-xs text-gray-600 mb-2">
                        {{ group.description }}
                      </p>
                      <div class="flex items-center gap-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                          {{ group.subject_count }} subjects
                        </span>
                        <button
                          type="button"
                          @click.prevent="showGroupSubjects(group)"
                          class="text-xs text-purple-600 hover:text-purple-800 font-medium"
                        >
                          View subjects →
                        </button>
                      </div>
                    </div>
                  </label>
                </div>
                
                <p v-if="form.errors.subject_group_id" class="mt-2 text-sm text-red-600">
                  {{ form.errors.subject_group_id }}
                </p>
              </div>
            </div>

            <!-- Step 5: Status -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  5
                </div>
                <h3 class="text-base font-medium text-gray-900">Set Status</h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Status
                </label>
                <div class="flex items-center">
                  <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Active Assignment
                  </label>
                </div>
                <p class="mt-1 text-xs text-gray-500">Inactive assignments won't be visible to students</p>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 sm:gap-4">
              <Button 
                type="button"
                variant="secondary"
                @click="router.visit(route('class-subjects.index'))"
                class="px-6 py-2.5 text-sm font-medium"
              >
                Cancel
              </Button>
              <Button 
                type="submit"
                variant="primary"
                :loading="form.processing"
                :disabled="!canSubmit"
                class="px-6 py-2.5 text-sm font-medium"
              >
                <span v-if="!form.processing">
                  Assign {{ form.assignment_type === 'individual' ? `Subject${form.subject_ids.length > 1 ? 's' : ''}` : 'Group' }}
                </span>
                <span v-else>Assigning...</span>
              </Button>
            </div>
          </form>
        </div>

      </div>

      <!-- Subject Group Details Modal -->
      <Modal :show="showGroupModal" @close="showGroupModal = false" size="lg">
        <template #title>
          <div class="flex items-center">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-purple-100 flex items-center justify-center mr-3 sm:mr-4">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
            </div>
            <span class="text-base sm:text-lg font-semibold text-gray-900">
              {{ selectedGroup?.group_name }} - Subjects
            </span>
          </div>
        </template>
        
        <div v-if="selectedGroup" class="space-y-3">
          <div v-for="subject in selectedGroup.subjects" :key="subject.id" 
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <div class="font-medium text-sm">{{ subject.subject_name }}</div>
              <div class="text-xs text-gray-500">{{ subject.subject_code }}</div>
            </div>
            <span v-if="subject.is_compulsory" class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
              Compulsory
            </span>
            <span v-else class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
              Elective
            </span>
          </div>
        </div>

        <template #footer>
          <Button 
            variant="primary" 
            @click="showGroupModal = false"
            class="w-full sm:w-auto px-6 text-sm"
          >
            Close
          </Button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import Modal from '@/Components/Common/Modal.vue'
import axios from 'axios'

// Props
const props = defineProps({
  branches: Array,
  subjects: Array,
  subjectGroups: Array,
})

// State
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)
const availableClasses = ref([])
const loadingClasses = ref(false)
const showGroupModal = ref(false)
const selectedGroup = ref(null)

// Form - ✅ Changed subject_id to subject_ids (array)
const form = useForm({
  branch_id: '',
  branch_class_id: '',
  assignment_type: '',
  subject_ids: [],  // ✅ Multi-select array
  subject_group_id: '',
  is_active: true,
})

// Computed
const compulsorySubjects = computed(() => {
  return props.subjects.filter(s => s.is_compulsory)
})

const electiveSubjects = computed(() => {
  return props.subjects.filter(s => !s.is_compulsory)
})

const canSubmit = computed(() => {
  if (!form.branch_class_id || !form.assignment_type) return false
  if (form.assignment_type === 'individual' && form.subject_ids.length === 0) return false
  if (form.assignment_type === 'group' && !form.subject_group_id) return false
  return true
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

// Watch assignment type changes
watch(() => form.assignment_type, (newType) => {
  if (newType === 'individual') {
    form.subject_group_id = ''
  } else if (newType === 'group') {
    form.subject_ids = []
  }
})

// Branch change handler
const onBranchChange = async () => {
  form.branch_class_id = ''
  form.assignment_type = ''
  form.subject_ids = []
  form.subject_group_id = ''
  availableClasses.value = []
  
  if (!form.branch_id) return
  
  loadingClasses.value = true
  
  try {
    const response = await axios.get(route('class-subjects.classes-by-branch'), {
      params: { branch_id: form.branch_id }
    })
    availableClasses.value = response.data
  } catch (error) {
    console.error('Error loading classes:', error)
  } finally {
    loadingClasses.value = false
  }
}

// Class change handler
const onClassChange = () => {
  form.assignment_type = ''
  form.subject_ids = []
  form.subject_group_id = ''
}

// ✅ Select all subjects
const selectAllSubjects = () => {
  form.subject_ids = props.subjects.map(s => s.id)
}

// ✅ Clear all subjects
const clearAllSubjects = () => {
  form.subject_ids = []
}

// Show group subjects
const showGroupSubjects = (group) => {
  selectedGroup.value = group
  showGroupModal.value = true
}

// Form submit - ✅ Updated to handle bulk assignment
const submit = () => {
  if (form.assignment_type === 'individual') {
    // Use bulk store for multiple subjects
    axios.post(route('class-subjects.bulk-store'), {
      branch_class_id: form.branch_class_id,
      subject_ids: form.subject_ids,
    })
    .then(() => {
      router.visit(route('class-subjects.index'), {
        onSuccess: () => {
          // Success message will be shown from flash
        }
      })
    })
    .catch(error => {
      console.error('Error:', error)
      if (error.response?.data?.errors) {
        form.errors = error.response.data.errors
      }
    })
  } else {
    // Use assign group for group assignment
    form.post(route('class-subjects.store'), {
      preserveScroll: true,
    })
  }
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
</style>