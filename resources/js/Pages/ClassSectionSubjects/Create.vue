<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        
        <!-- Page Header -->
        <PageHeader 
          title="Assign Subjects to Class Section"
          description="Select branch → class → section → choose individual subjects or subject group"
          :show-back-button="true"
          back-route="class-section-subjects.index"
        />

        <!-- Flash Messages -->
        <FlashMessage 
          :show="showSuccessMessage"
          type="success"
          :message="$page.props.flash.success"
          @close="showSuccessMessage = false"
        />

        <FlashMessage 
          :show="showErrorMessage"
          type="error"
          :message="$page.props.flash.error"
          @close="showErrorMessage = false"
        />

        <!-- Form Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6">
          <form @submit.prevent="handleSubmit">
            
            <!-- Step 1: Branch Selection -->
            <div class="mb-6">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  1
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Branch</h3>
              </div>
              
              <div class="pl-11">
                <FormSelect
                  id="branch_id"
                  v-model="form.branch_id"
                  label="Branch"
                  :options="branches"
                  value-key="id"
                  label-key="branch_name"
                  placeholder="-- Select Branch --"
                  :required="true"
                  :error="form.errors.branch_id"
                  custom-width="md:w-2/3 lg:w-1/2"
                  @change="onBranchChange"
                />
              </div>
            </div>

            <!-- Step 2: Branch Class Selection -->
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
                <FormSelect
                  id="branch_class_id"
                  v-model="form.branch_class_id"
                  label="Class"
                  :options="availableClasses"
                  value-key="id"
                  label-key="class_name"
                  placeholder="-- Select Class --"
                  :required="true"
                  :disabled="!form.branch_id"
                  :loading="loadingClasses"
                  loading-text="Loading classes..."
                  :error="form.errors.branch_class_id"
                  hint="Please select a branch first"
                  custom-width="md:w-2/3 lg:w-1/2"
                  @change="onClassChange"
                />
              </div>
            </div>

            <!-- Step 3: Class Section Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm mr-3 font-semibold"
                     :class="form.branch_class_id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                  3
                </div>
                <h3 class="text-base font-medium" :class="form.branch_class_id ? 'text-gray-900' : 'text-gray-400'">
                  Select Class Section
                </h3>
              </div>
              
              <div class="pl-11">
                <FormSelect
                  id="class_section_id"
                  v-model="form.class_section_id"
                  label="Class Section"
                  :options="availableSections"
                  value-key="id"
                  label-key="display_name"
                  placeholder="-- Select Section --"
                  :required="true"
                  :disabled="!form.branch_class_id"
                  :loading="loadingSections"
                  loading-text="Loading sections..."
                  :error="form.errors.class_section_id"
                  hint="Please select a class first"
                  custom-width="md:w-2/3 lg:w-1/2"
                  @change="onSectionChange"
                />
              </div>
            </div>

            <!-- Step 4: Assignment Type Selection -->
            <div class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm mr-3 font-semibold"
                     :class="form.class_section_id ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                  4
                </div>
                <h3 class="text-base font-medium" :class="form.class_section_id ? 'text-gray-900' : 'text-gray-400'">
                  Assignment Type
                </h3>
              </div>
              
              <div class="pl-11">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Choose Assignment Type <span class="text-red-500">*</span>
                </label>
                
                <!-- Loading State -->
                <LoadingSpinner
                  v-if="loadingAssignments"
                  text="Checking existing assignments..."
                  size="sm"
                  container-class="py-8"
                />

                <!-- Assignment Type Cards -->
                <div v-else-if="form.class_section_id" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Individual Subjects -->
                  <label
                    class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="[
                      form.assignment_type === 'individual' 
                        ? 'border-indigo-500 bg-indigo-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white',
                      assignmentInfo.has_group ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  >
                    <input
                      type="radio"
                      name="assignment_type"
                      value="individual"
                      v-model="form.assignment_type"
                      :disabled="assignmentInfo.has_group"
                      class="sr-only"
                    />
                    <div class="flex items-start">
                      <div class="flex-shrink-0">
                        <div 
                          :class="[
                            'w-10 h-10 rounded-lg flex items-center justify-center',
                            form.assignment_type === 'individual' ? 'bg-indigo-100' : 'bg-gray-100'
                          ]"
                        >
                          <svg 
                            :class="[
                              'w-5 h-5',
                              form.assignment_type === 'individual' ? 'text-indigo-600' : 'text-gray-400'
                            ]"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                          </svg>
                        </div>
                      </div>
                      <div class="ml-3 flex-1">
                        <div 
                          :class="[
                            'text-sm font-medium',
                            form.assignment_type === 'individual' ? 'text-indigo-900' : 'text-gray-900'
                          ]"
                        >
                          Individual Subjects
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                          Assign multiple subjects at once
                        </div>
                        <div v-if="assignmentInfo.has_group" class="mt-2">
                          <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                            Group already assigned
                          </span>
                        </div>
                      </div>
                      <svg 
                        v-if="form.assignment_type === 'individual'" 
                        class="w-5 h-5 text-indigo-600 flex-shrink-0" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                  </label>

                  <!-- Subject Group -->
                  <label
                    class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 transition-all"
                    :class="[
                      form.assignment_type === 'group' 
                        ? 'border-purple-500 bg-purple-50' 
                        : 'border-gray-200 hover:border-gray-300 bg-white',
                      assignmentInfo.has_subjects ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  >
                    <input
                      type="radio"
                      name="assignment_type"
                      value="group"
                      v-model="form.assignment_type"
                      :disabled="assignmentInfo.has_subjects"
                      class="sr-only"
                    />
                    <div class="flex items-start">
                      <div class="flex-shrink-0">
                        <div 
                          :class="[
                            'w-10 h-10 rounded-lg flex items-center justify-center',
                            form.assignment_type === 'group' ? 'bg-purple-100' : 'bg-gray-100'
                          ]"
                        >
                          <svg 
                            :class="[
                              'w-5 h-5',
                              form.assignment_type === 'group' ? 'text-purple-600' : 'text-gray-400'
                            ]"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                          </svg>
                        </div>
                      </div>
                      <div class="ml-3 flex-1">
                        <div 
                          :class="[
                            'text-sm font-medium',
                            form.assignment_type === 'group' ? 'text-purple-900' : 'text-gray-900'
                          ]"
                        >
                          Subject Group
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                          Assign a complete group (Science, Arts, etc.)
                        </div>
                        <div v-if="assignmentInfo.has_subjects" class="mt-2">
                          <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                            Subjects already assigned
                          </span>
                        </div>
                      </div>
                      <svg 
                        v-if="form.assignment_type === 'group'" 
                        class="w-5 h-5 text-purple-600 flex-shrink-0" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                  </label>
                </div>

                <!-- Empty State -->
                <EmptyState
                  v-else
                  type="info"
                  icon="info"
                  title="Select a class section first"
                  description="Choose branch, class and section to proceed"
                  padding="py-8"
                />

                <p v-if="form.errors.assignment_type" class="mt-2 text-sm text-red-600">
                  {{ form.errors.assignment_type }}
                </p>
              </div>
            </div>

            <!-- Step 5a: Individual Subject Selection -->
            <div v-if="form.assignment_type === 'individual'" class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-sm mr-3">
                  5
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Subjects (Multiple)</h3>
              </div>
              
              <div class="pl-11">
                <!-- Loading State -->
                <LoadingSpinner
                  v-if="loadingSubjects"
                  text="Loading subjects..."
                  size="md"
                  container-class="py-12 border-2 border-dashed border-gray-300 rounded-lg"
                />

                <!-- Subjects List -->
                <div v-else>
                  <label class="block text-sm font-medium text-gray-700 mb-3">
                    Subjects <span class="text-red-500">*</span>
                    <span v-if="form.subject_ids.length > 0" class="ml-2 text-indigo-600">
                      ({{ form.subject_ids.length }} selected)
                    </span>
                  </label>

                  <!-- No Subjects Available -->
                  <EmptyState
                    v-if="availableSubjects.length === 0"
                    type="warning"
                    title="No subjects available"
                    description="All subjects may have been assigned already"
                  />

                  <!-- Subject Checkboxes -->
                  <div v-else class="space-y-4">
                    <!-- Compulsory Subjects -->
                    <div v-if="compulsorySubjects.length > 0">
                      <div class="flex items-center mb-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                          Compulsory Subjects
                        </span>
                      </div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <label
                          v-for="subject in compulsorySubjects"
                          :key="subject.id"
                          class="relative flex items-center p-3 cursor-pointer rounded-lg border-2 transition-all"
                          :class="[
                            form.subject_ids.includes(subject.id) 
                              ? 'border-indigo-500 bg-indigo-50' 
                              : 'border-gray-200 hover:border-gray-300 bg-white',
                            assignmentInfo.assigned_subject_ids.includes(subject.id) ? 'opacity-50' : ''
                          ]"
                        >
                          <input
                            type="checkbox"
                            :value="subject.id"
                            v-model="form.subject_ids"
                            :disabled="assignmentInfo.assigned_subject_ids.includes(subject.id)"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          />
                          <div class="ml-3 flex-1">
                            <div 
                              :class="[
                                'text-sm font-medium',
                                form.subject_ids.includes(subject.id) ? 'text-indigo-900' : 'text-gray-700'
                              ]"
                            >
                              {{ subject.subject_name }}
                            </div>
                            <div class="text-xs text-gray-500">{{ subject.subject_code }}</div>
                            <span 
                              v-if="assignmentInfo.assigned_subject_ids.includes(subject.id)" 
                              class="inline-block mt-1 text-xs text-amber-600"
                            >
                              Already assigned
                            </span>
                          </div>
                          <svg 
                            v-if="form.subject_ids.includes(subject.id)" 
                            class="w-4 h-4 text-indigo-600 flex-shrink-0" 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                          >
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                          </svg>
                        </label>
                      </div>
                    </div>

                    <!-- Elective Subjects -->
                    <div v-if="electiveSubjects.length > 0">
                      <div class="flex items-center mb-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">
                          Elective Subjects
                        </span>
                      </div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <label
                          v-for="subject in electiveSubjects"
                          :key="subject.id"
                          class="relative flex items-center p-3 cursor-pointer rounded-lg border-2 transition-all"
                          :class="[
                            form.subject_ids.includes(subject.id) 
                              ? 'border-amber-500 bg-amber-50' 
                              : 'border-gray-200 hover:border-gray-300 bg-white',
                            assignmentInfo.assigned_subject_ids.includes(subject.id) ? 'opacity-50' : ''
                          ]"
                        >
                          <input
                            type="checkbox"
                            :value="subject.id"
                            v-model="form.subject_ids"
                            :disabled="assignmentInfo.assigned_subject_ids.includes(subject.id)"
                            class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                          />
                          <div class="ml-3 flex-1">
                            <div 
                              :class="[
                                'text-sm font-medium',
                                form.subject_ids.includes(subject.id) ? 'text-amber-900' : 'text-gray-700'
                              ]"
                            >
                              {{ subject.subject_name }}
                            </div>
                            <div class="text-xs text-gray-500">{{ subject.subject_code }}</div>
                            <span 
                              v-if="assignmentInfo.assigned_subject_ids.includes(subject.id)" 
                              class="inline-block mt-1 text-xs text-amber-600"
                            >
                              Already assigned
                            </span>
                          </div>
                          <svg 
                            v-if="form.subject_ids.includes(subject.id)" 
                            class="w-4 h-4 text-amber-600 flex-shrink-0" 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                          >
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                          </svg>
                        </label>
                      </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center gap-3 pt-2">
                      <button
                        type="button"
                        @click="selectAllSubjects"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                      >
                        Select All Available
                      </button>
                      <span class="text-gray-300">|</span>
                      <button
                        type="button"
                        @click="form.subject_ids = []"
                        class="text-xs text-gray-600 hover:text-gray-800 font-medium"
                      >
                        Deselect All
                      </button>
                    </div>
                  </div>

                  <p v-if="form.errors.subject_ids" class="mt-2 text-sm text-red-600">
                    {{ form.errors.subject_ids }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Step 5b: Subject Group Selection -->
            <div v-if="form.assignment_type === 'group'" class="mb-6 pb-6 border-b border-gray-200">
              <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 text-purple-600 font-semibold text-sm mr-3">
                  5
                </div>
                <h3 class="text-base font-medium text-gray-900">Select Subject Group</h3>
              </div>
              
              <div class="pl-11">
                <!-- Loading State -->
                <LoadingSpinner
                  v-if="loadingGroups"
                  text="Loading subject groups..."
                  size="md"
                  container-class="py-12 border-2 border-dashed border-gray-300 rounded-lg"
                />

                <!-- Subject Groups List -->
                <div v-else>
                  <label class="block text-sm font-medium text-gray-700 mb-3">
                    Subject Group <span class="text-red-500">*</span>
                  </label>

                  <!-- No Groups Available -->
                  <EmptyState
                    v-if="availableGroups.length === 0"
                    type="warning"
                    title="No subject groups available"
                    description="All groups may have been assigned already"
                  />

                  <!-- Group Radio Buttons -->
                  <div v-else class="space-y-3">
                    <label
                      v-for="group in availableGroups"
                      :key="group.id"
                      class="relative flex items-start p-4 cursor-pointer rounded-lg border-2 transition-all"
                      :class="[
                        form.subject_group_id === group.id 
                          ? 'border-purple-500 bg-purple-50' 
                          : 'border-gray-200 hover:border-gray-300 bg-white',
                        assignmentInfo.assigned_group_ids.includes(group.id) ? 'opacity-50 cursor-not-allowed' : ''
                      ]"
                    >
                      <input
                        type="radio"
                        name="subject_group_id"
                        :value="group.id"
                        v-model="form.subject_group_id"
                        :disabled="assignmentInfo.assigned_group_ids.includes(group.id)"
                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 mt-1"
                      />
                      <div class="ml-3 flex-1">
                        <div 
                          :class="[
                            'text-sm font-medium',
                            form.subject_group_id === group.id ? 'text-purple-900' : 'text-gray-900'
                          ]"
                        >
                          {{ group.group_name }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                          {{ group.description || 'No description' }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                          <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                            {{ group.subject_count }} subjects
                          </span>
                          <button
                            type="button"
                            @click.prevent="showGroupDetails(group)"
                            class="text-xs text-purple-600 hover:text-purple-800 font-medium underline"
                          >
                            View subjects
                          </button>
                        </div>
                        <span 
                          v-if="assignmentInfo.assigned_group_ids.includes(group.id)" 
                          class="inline-block mt-2 text-xs text-red-600"
                        >
                          Already assigned
                        </span>
                      </div>
                      <svg 
                        v-if="form.subject_group_id === group.id" 
                        class="w-5 h-5 text-purple-600 flex-shrink-0" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </label>
                  </div>

                  <p v-if="form.errors.subject_group_id" class="mt-2 text-sm text-red-600">
                    {{ form.errors.subject_group_id }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 sm:gap-4">
              <button 
                type="button"
                @click="handleCancel"
                class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Cancel
              </button>
              <button 
                type="submit"
                :disabled="!canSubmit || form.processing"
                class="w-full sm:w-auto px-6 py-2.5 text-sm font-medium rounded-lg transition-all"
                :class="canSubmit && !form.processing
                  ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg hover:shadow-xl' 
                  : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
              >
                <span v-if="!form.processing">{{ getSubmitText }}</span>
                <span v-else class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Assigning...
                </span>
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- Group Details Modal -->
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
          <div v-for="subject in selectedGroup.subjects" :key="subject" 
               class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="font-medium text-sm">{{ subject }}</div>
          </div>
        </div>

        <template #footer>
          <button 
            @click="showGroupModal = false"
            class="w-full sm:w-auto px-6 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
          >
            Close
          </button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import PageHeader from '@/Components/Forms/PageHeader.vue'
import FlashMessage from '@/Components/Forms/FlashMessage.vue'
import FormSelect from '@/Components/Forms/FormSelect.vue'
import LoadingSpinner from '@/Components/Forms/LoadingSpinner.vue'
import EmptyState from '@/Components/Forms/EmptyState.vue'
import Modal from '@/Components/Common/Modal.vue'
import axios from 'axios'

// Props
const props = defineProps({
  branches: Array,
})

// State
const showSuccessMessage = ref(true)
const showErrorMessage = ref(true)
const availableClasses = ref([])
const loadingClasses = ref(false)
const availableSections = ref([])
const loadingSections = ref(false)
const availableSubjects = ref([])
const loadingSubjects = ref(false)
const availableGroups = ref([])
const loadingGroups = ref(false)
const loadingAssignments = ref(false)
const assignmentInfo = ref({
  assigned_subject_ids: [],
  assigned_group_ids: [],
  has_subjects: false,
  has_group: false
})
const showGroupModal = ref(false)
const selectedGroup = ref(null)

// Form
const form = useForm({
  branch_id: '',              // ← ADD THIS
  branch_class_id: '',        // ← ADD THIS
  class_section_id: '',
  assignment_type: '',
  subject_ids: [],
  subject_group_id: '',
})

// Computed
const compulsorySubjects = computed(() => {
  return availableSubjects.value.filter(s => s.is_compulsory)
})

const electiveSubjects = computed(() => {
  return availableSubjects.value.filter(s => !s.is_compulsory)
})

const canSubmit = computed(() => {
  if (!form.class_section_id || !form.assignment_type) return false
  if (form.assignment_type === 'individual' && form.subject_ids.length === 0) return false
  if (form.assignment_type === 'group' && !form.subject_group_id) return false
  return true
})

const getSubmitText = computed(() => {
  if (form.assignment_type === 'individual') {
    return `Assign ${form.subject_ids.length} Subject${form.subject_ids.length > 1 ? 's' : ''}`
  }
  return 'Assign Group'
})

// Watch for flash messages
const page = usePage()
watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success) {
    showSuccessMessage.value = true
    setTimeout(() => showSuccessMessage.value = false, 5000)
  }
  if (newFlash.error) {
    showErrorMessage.value = true
    setTimeout(() => showErrorMessage.value = false, 5000)
  }
}, { deep: true, immediate: true })

// Watch assignment type changes
watch(() => form.assignment_type, (newType) => {
  if (newType === 'individual') {
    form.subject_group_id = ''
    loadSubjects()
  } else if (newType === 'group') {
    form.subject_ids = []
    loadSubjectGroups()
  }
})

// Branch change handler
const onBranchChange = async () => {
  form.branch_class_id = ''
  form.class_section_id = ''
  form.assignment_type = ''
  form.subject_ids = []
  form.subject_group_id = ''
  availableClasses.value = []
  availableSections.value = []
  
  if (!form.branch_id) return
  
  loadingClasses.value = true
  
  try {
    const response = await axios.get(route('class-section-subjects.classes-by-branch'), {
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
const onClassChange = async () => {
  form.class_section_id = ''
  form.assignment_type = ''
  form.subject_ids = []
  form.subject_group_id = ''
  availableSections.value = []
  
  if (!form.branch_class_id) return
  
  loadingSections.value = true
  
  try {
    const response = await axios.get(route('class-section-subjects.sections-by-class'), {
      params: { branch_class_id: form.branch_class_id }
    })
    availableSections.value = response.data
  } catch (error) {
    console.error('Error loading sections:', error)
  } finally {
    loadingSections.value = false
  }
}

// Section change handler
const onSectionChange = async () => {
  form.assignment_type = ''
  form.subject_ids = []
  form.subject_group_id = ''
  
  if (!form.class_section_id) return
  
  loadingAssignments.value = true
  
  try {
    const response = await axios.get(route('class-section-subjects.assigned-subjects'), {
      params: { class_section_id: form.class_section_id }
    })
    assignmentInfo.value = response.data
  } catch (error) {
    console.error('Error loading assignments:', error)
  } finally {
    loadingAssignments.value = false
  }
}

// Load subjects
const loadSubjects = async () => {
  loadingSubjects.value = true
  
  try {
    const response = await axios.get(route('class-section-subjects.subjects'))
    availableSubjects.value = response.data
  } catch (error) {
    console.error('Error loading subjects:', error)
  } finally {
    loadingSubjects.value = false
  }
}

// Load subject groups
const loadSubjectGroups = async () => {
  loadingGroups.value = true
  
  try {
    const response = await axios.get(route('class-section-subjects.subject-groups'))
    availableGroups.value = response.data
  } catch (error) {
    console.error('Error loading groups:', error)
  } finally {
    loadingGroups.value = false
  }
}

// Select all available subjects
const selectAllSubjects = () => {
  const unassigned = availableSubjects.value
    .filter(s => !assignmentInfo.value.assigned_subject_ids.includes(s.id))
    .map(s => s.id)
  form.subject_ids = unassigned
}

// Show group details
const showGroupDetails = (group) => {
  selectedGroup.value = group
  showGroupModal.value = true
}

// Handle cancel
const handleCancel = () => {
  router.visit(route('class-section-subjects.index'))
}

// Handle submit
const handleSubmit = () => {
  if (!canSubmit.value) return
  
  form.post(route('class-section-subjects.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Will redirect automatically
    },
    onError: (errors) => {
      console.error('Validation errors:', errors)
      // Errors will be shown via form.errors automatically
    }
  })
}
</script>