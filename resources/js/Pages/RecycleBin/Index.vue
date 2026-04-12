<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Page Header -->
        <div class="mb-6 sm:mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Recycle Bin</h1>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">View and restore deleted items</p>
            </div>
            <Button
              v-if="totalCount > 0"
              @click="confirmEmptyAll"
              variant="danger"
              class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all text-sm"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Empty All
            </Button>
          </div>
        </div>

        <!-- Type Tabs -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 mb-6">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="type in types"
              :key="type"
              @click="changeType(type)"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                currentType === type
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              {{ formatLabel(type) }}
              <span v-if="counts[type] > 0" class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-white/20">
                {{ counts[type] }}
              </span>
            </button>
          </div>
        </div>

        <!-- Items Table -->
        <div v-if="items.length > 0" class="bg-white rounded-lg sm:rounded-xl shadow-md overflow-hidden">
          <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Select All</span>
              </label>
            </div>
            <div class="flex gap-2">
              <Button
                v-if="selectedIds.length > 0"
                @click="confirmRestoreMultiple"
                variant="success"
                size="sm"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Restore ({{ selectedIds.length }})
              </Button>
              <Button
                v-if="selectedIds.length > 0"
                @click="confirmDeleteMultiple"
                variant="danger"
                size="sm"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete Permanently ({{ selectedIds.length }})
              </Button>
              <Button
                v-if="items.length > 0"
                @click="confirmEmptyType"
                variant="secondary"
                size="sm"
              >
                Empty {{ label }}
              </Button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                    <input
                      type="checkbox"
                      :checked="allSelected"
                      @change="toggleSelectAll"
                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ label }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deleted At
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <input
                      type="checkbox"
                      :checked="selectedIds.includes(item.id)"
                      @change="toggleSelect(item.id)"
                      class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.label }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(item.deleted_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end gap-2">
                      <button
                        @click="restoreItem(item.id)"
                        class="text-green-600 hover:text-green-900 flex items-center gap-1"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Restore
                      </button>
                      <button
                        @click="deleteItem(item.id)"
                        class="text-red-600 hover:text-red-900 flex items-center gap-1"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-lg sm:rounded-xl shadow-md p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900">No deleted items</h3>
          <p class="mt-2 text-sm text-gray-500">There are no {{ label.toLowerCase() }} in the recycle bin.</p>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeModal"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="sm:flex sm:items-start">
            <div :class="['mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10', modalType === 'danger' ? 'bg-red-100' : 'bg-green-100']">
              <svg v-if="modalType === 'danger'" class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              <svg v-else class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
              <h3 class="text-lg leading-6 font-medium text-gray-900">{{ modalTitle }}</h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">{{ modalMessage }}</p>
              </div>
            </div>
          </div>
          <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
            <button
              v-if="modalType === 'danger'"
              @click="executeDangerAction"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
            >
              Delete Permanently
            </button>
            <button
              v-else
              @click="executeRestoreAction"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
            >
              Restore
            </button>
            <button
              @click="closeModal"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  type: {
    type: String,
    default: 'students'
  },
  label: {
    type: String,
    default: 'Students'
  },
  counts: {
    type: Object,
    default: () => ({})
  },
  types: {
    type: Array,
    default: () => []
  }
})

const currentType = ref(props.type)
const selectedIds = ref([])
const showModal = ref(false)
const modalType = ref('danger')
const modalTitle = ref('')
const modalMessage = ref('')
const pendingAction = ref(null)
const pendingItemId = ref(null)

const totalCount = computed(() => {
  return Object.values(props.counts).reduce((sum, count) => sum + count, 0)
})

const allSelected = computed(() => {
  return props.items.length > 0 && selectedIds.value.length === props.items.length
})

function formatLabel(type) {
  return type
    .replace(/-/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function changeType(type) {
  currentType.value = type
  selectedIds.value = []
  router.get(route('recycle-bin.index'), { type }, { replace: true })
}

function toggleSelect(id) {
  const index = selectedIds.value.indexOf(id)
  if (index === -1) {
    selectedIds.value.push(id)
  } else {
    selectedIds.value.splice(index, 1)
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = props.items.map(item => item.id)
  }
}

function restoreItem(id) {
  router.post(route('recycle-bin.restore'), {
    type: currentType.value,
    id
  }, {
    onSuccess: () => {
      selectedIds.value = selectedIds.value.filter(i => i !== id)
    }
  })
}

function deleteItem(id) {
  router.delete(route('recycle-bin.destroy'), {
    data: {
      type: currentType.value,
      id
    }
  })
}

function confirmRestoreMultiple() {
  modalType.value = 'success'
  modalTitle.value = 'Restore Items'
  modalMessage.value = `Are you sure you want to restore ${selectedIds.value.length} selected item(s)?`
  pendingAction.value = 'restore-multiple'
  showModal.value = true
}

function confirmDeleteMultiple() {
  modalType.value = 'danger'
  modalTitle.value = 'Delete Permanently'
  modalMessage.value = `Are you sure you want to permanently delete ${selectedIds.value.length} selected item(s)? This action cannot be undone.`
  pendingAction.value = 'delete-multiple'
  showModal.value = true
}

function confirmEmptyType() {
  modalType.value = 'danger'
  modalTitle.value = `Empty ${props.label}`
  modalMessage.value = `Are you sure you want to permanently delete all ${props.label.toLowerCase()}? This action cannot be undone.`
  pendingAction.value = 'empty-type'
  showModal.value = true
}

function confirmEmptyAll() {
  modalType.value = 'danger'
  modalTitle.value = 'Empty Recycle Bin'
  modalMessage.value = `Are you sure you want to permanently delete all ${totalCount.value} items? This action cannot be undone.`
  pendingAction.value = 'empty-all'
  showModal.value = true
}

function executeRestoreAction() {
  if (pendingAction.value === 'restore-multiple') {
    router.post(route('recycle-bin.restore-multiple'), {
      type: currentType.value,
      ids: selectedIds.value
    }, {
      onSuccess: () => {
        selectedIds.value = []
      }
    })
  }
  closeModal()
}

function executeDangerAction() {
  if (pendingAction.value === 'delete-multiple') {
    router.delete(route('recycle-bin.destroy-multiple'), {
      data: {
        type: currentType.value,
        ids: selectedIds.value
      }
    }, {
      onSuccess: () => {
        selectedIds.value = []
      }
    })
  } else if (pendingAction.value === 'empty-type') {
    router.delete(route('recycle-bin.empty-type'), {
      data: {
        type: currentType.value
      }
    })
  } else if (pendingAction.value === 'empty-all') {
    router.delete(route('recycle-bin.empty-all'))
  }
  closeModal()
}

function closeModal() {
  showModal.value = false
  pendingAction.value = null
  pendingItemId.value = null
}
</script>