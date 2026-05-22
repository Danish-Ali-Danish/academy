<template>
    <AppLayout>
        <div class="min-h-screen flex flex-col">
            <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
                <div class="mb-4 sm:mb-6 lg:mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Online Payment Proofs</h1>
                            <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-gray-600">Track and verify submitted online fee proof records</p>
                        </div>
                        <Link :href="route('online-payment-proofs.create')">
                            <Button variant="primary" class="w-full sm:w-auto shadow-lg hover:shadow-xl transition-all duration-200">
                                <PlusIcon class="h-4 w-4 sm:h-5 sm:w-5 mr-2" />
                                <span class="text-sm sm:text-base">Add Proof</span>
                            </Button>
                        </Link>
                    </div>
                </div>

                <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                        <div>
                            <Input v-model="filters.search" placeholder="Search proofs..." @input="searchDebounced" class="w-full text-sm" />
                        </div>

                        <div>
                            <select
                                v-model="filters.verification_status"
                                @change="loadData"
                                class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="verified">Verified</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="proofs-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
                                <tr>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">#</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Voucher</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Student</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Account</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Method</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Amount</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Datetime</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Verified By</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-3 sm:px-6 py-3 sm:py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
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

                    <div v-else-if="mobilePayments.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z"/>
                        </svg>
                        <p class="mt-2 text-sm font-medium text-gray-500">No proofs found</p>
                        <p class="mt-1 text-xs text-gray-400">Try adjusting your filters</p>
                    </div>

                    <div v-else v-for="(proof, index) in mobilePayments" :key="proof.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-500">#{{ mobileOffset + index + 1 }}</span>
                                        <h3 class="text-base font-semibold text-gray-900">{{ proof.voucher_no }}</h3>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ studentName(proof) }}</p>
                                </div>
                                <span :class="statusBadgeClass(proof.verification_status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap ml-2">
                                    {{ statusLabel(proof.verification_status) }}
                                </span>
                            </div>

                            <div class="space-y-2 border-t border-gray-100 pt-3">
                                <div class="flex items-center justify-between text-xs sm:text-sm">
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>{{ formatDate(proof.payment_datetime) }}</span>
                                    </div>
                                    <span class="text-gray-900 font-semibold">Rs. {{ Number(proof.amount_sent || 0).toLocaleString() }}</span>
                                </div>

                                <div class="flex items-center text-xs sm:text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span class="text-gray-600">{{ formatPaymentMethod(proof.payment_method) }}</span>
                                </div>

                                <div class="flex items-center text-xs sm:text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                    <span class="text-gray-600">{{ proof.account }}</span>
                                </div>

                                <div class="flex items-center text-xs sm:text-sm">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="text-gray-600">{{ proof.verified_by }}</span>
                                </div>
                            </div>

                            <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                                <button @click="openViewModal(proof)" class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-1">
                                    View
                                </button>
                                <button @click="editProof(proof)" class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors flex items-center justify-center gap-1">
                                    Edit
                                </button>
                                <button @click="openDeleteModal(proof.id)" class="flex-1 px-3 py-2 text-xs sm:text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors flex items-center justify-center gap-1">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="mobilePayments.length > 0" class="md:hidden mt-4 bg-white rounded-lg shadow p-3">
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
                            <div class="text-xs text-gray-500 mt-0.5">{{ mobileTotal }} total proofs</div>
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

            <Modal :show="showViewModal" @close="showViewModal = false">
                <template #title>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-3 sm:mr-4">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <span class="text-base sm:text-lg font-semibold text-gray-900">Payment Proof Details</span>
                    </div>
                </template>

                <div v-if="selectedProof" class="space-y-4">
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Voucher</p>
                                <p class="mt-1 font-semibold text-gray-900">{{ voucherNo(selectedProof) }}</p>
                                <p class="text-xs text-gray-500">{{ studentName(selectedProof) }}</p>
                            </div>
                            <span :class="statusBadgeClass(selectedProof.verification_status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                {{ statusLabel(selectedProof.verification_status) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-xs text-gray-500">Account</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ accountName(selectedProof) }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-xs text-gray-500">Method</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatPaymentMethod(selectedProof.payment_method) }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-xs text-gray-500">Amount</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">Rs. {{ Number(selectedProof.amount_sent || 0).toLocaleString() }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-xs text-gray-500">Datetime</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatDateTime(selectedProof.payment_datetime) }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-xs text-gray-500">Transaction ID</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedProof.transaction_id || '-' }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-xs text-gray-500">Verified By</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ verifiedByName(selectedProof) }}</p>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="flex justify-end gap-2">
                        <Button variant="secondary" @click="showViewModal = false" class="px-5 text-sm">Close</Button>
                        <Button variant="primary" @click="editProof(selectedProof)" class="px-5 text-sm">Edit Proof</Button>
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
                        <span class="text-base sm:text-lg font-semibold text-gray-900">Delete Proof</span>
                    </div>
                </template>

                <div class="mt-2">
                    <p class="text-xs sm:text-sm text-gray-600 mb-4">Are you sure you want to delete this proof?</p>
                </div>

                <template #footer>
                    <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                        <Button variant="secondary" @click="showDeleteModal = false" class="w-full sm:w-auto px-4 sm:px-6 shadow-sm hover:shadow-md transition-all text-sm">Close</Button>
                        <Button variant="danger" @click="confirmDelete" :loading="deleting" class="w-full sm:w-auto px-4 sm:px-6 shadow-md hover:shadow-lg transition-all text-sm">
                            <span v-if="!deleting">Delete Proof</span>
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

const showDeleteModal = ref(false)
const showViewModal = ref(false)
const deleting = ref(false)
const proofToDelete = ref(null)
const selectedProof = ref(null)
const tableSearch = ref('')
const perPage = ref(10)
const mobilePayments = ref([])
const mobileLoading = ref(true)
const mobileCurrentPage = ref(1)
const mobileTotalPages = ref(1)
const mobileTotal = ref(0)
const mobileOffset = ref(0)
let table = null

const filters = reactive({
    search: '',
    verification_status: ''
})

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (dateString) => {
    if (!dateString || dateString === '-') return '-'
    const date = new Date(dateString)
    if (Number.isNaN(date.getTime())) return dateString
    return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatPaymentMethod = (method) => {
    const methods = {
        jazzcash: 'JazzCash',
        easypaisa: 'Easypaisa',
        bank_transfer: 'Bank Transfer',
        raast: 'Raast'
    }
    return methods[method] || method || 'N/A'
}

const statusLabel = (status) => {
    const labels = {
        pending: 'Pending',
        verified: 'Verified',
        rejected: 'Rejected'
    }
    return labels[status] || 'Pending'
}

const statusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        verified: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const studentName = (proof) => {
    return proof?.student_name || proof?.student_enrollment?.student?.student_name || 'N/A'
}

const voucherNo = (proof) => {
    return proof?.voucher_no || proof?.voucher?.voucher_no || 'N/A'
}

const accountName = (proof) => {
    return proof?.account || proof?.academy_account?.account_title || 'N/A'
}

const verifiedByName = (proof) => {
    if (!proof?.verified_by) return 'N/A'
    return typeof proof.verified_by === 'string' ? proof.verified_by : proof.verified_by.name || 'N/A'
}

const loadData = () => {
    if (table) table.ajax.reload()
    loadMobileData()
}

const searchDebounced = () => {
    clearTimeout(window.__proofSearchTimeout)
    window.__proofSearchTimeout = setTimeout(() => loadData(), 500)
}

const tableSearchDebounced = () => {
    clearTimeout(window.__proofTableTimeout)
    window.__proofTableTimeout = setTimeout(() => loadData(), 500)
}

const changePerPage = () => {
    if (table) table.page.len(perPage.value).draw()
    mobileCurrentPage.value = 1
    loadMobileData()
}

const resetFilters = () => {
    filters.search = ''
    filters.verification_status = ''
    tableSearch.value = ''
    loadData()
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
        if (filters.verification_status) params.verification_status = filters.verification_status

        const response = await axios.get(route('online-payment-proofs.index'), {
            params,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })

        if (response.data) {
            if (response.data.data) {
                mobilePayments.value = response.data.data
                mobileCurrentPage.value = response.data.current_page || 1
                mobileTotalPages.value = response.data.last_page || 1
                mobileTotal.value = response.data.total || 0
                mobileOffset.value = response.data.from ? response.data.from - 1 : 0
            } else if (Array.isArray(response.data)) {
                mobilePayments.value = response.data
                mobileTotalPages.value = 1
                mobileTotal.value = response.data.length
                mobileOffset.value = 0
            }
        }
    } catch (error) {
        console.error('Error loading mobile proofs data:', error)
        mobilePayments.value = []
        mobileTotal.value = 0
    } finally {
        mobileLoading.value = false
    }
}

const openDeleteModal = (id) => {
    proofToDelete.value = id
    showDeleteModal.value = true
}

const openViewModal = (proof) => {
    selectedProof.value = proof
    showViewModal.value = true
}

const confirmDelete = () => {
    if (!proofToDelete.value) return

    deleting.value = true
    router.delete(route('online-payment-proofs.destroy', proofToDelete.value), {
        onSuccess: () => {
            showDeleteModal.value = false
            deleting.value = false
            proofToDelete.value = null
            loadData()
        },
        onError: () => {
            deleting.value = false
        }
    })
}

window.editProof = (proof) => {
    router.visit(route('online-payment-proofs.edit', proof.id))
}

window.viewProof = (proof) => {
    openViewModal(proof)
}

window.deleteProof = (id) => {
    openDeleteModal(id)
}

onMounted(() => {
    loadMobileData()

    table = $('#proofs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: route('online-payment-proofs.index'),
            data: function (d) {
                d.search.value = filters.search || tableSearch.value
                d.verification_status = filters.verification_status
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'voucher_no', name: 'voucher_no' },
            { data: 'student_name', name: 'student_name' },
            { data: 'account', name: 'account', orderable: false },
            { data: 'payment_method', name: 'payment_method', orderable: false },
            { data: 'amount_sent', name: 'amount_sent' },
            { data: 'payment_datetime', name: 'payment_datetime' },
            { data: 'verified_by', name: 'verified_by', orderable: false },
            { data: 'verification_status', name: 'verification_status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[6, 'desc']],
        searching: true,
        info: true,
        responsive: true,
        dom: '<"flex items-center justify-between border-b border-gray-200"<"ml-auto"i>>rt<"flex items-center justify-between px-6 py-4 border-t border-gray-200"<"text-sm text-gray-600"i>p>',
        language: {
            emptyTable: '<div class="text-center py-16"><div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4"><svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7z"/></svg></div><p class="text-sm font-semibold text-gray-700">No payment proofs found</p><p class="text-xs text-gray-400 mt-1">Try adjusting filters or add a new proof</p></div>',
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
            const info = $('#proofs-table_info')
            $('#table-info').empty().append(info)
            const paginate = $('#proofs-table_paginate')
            $('#table-pagination').empty().append(paginate)
        }
    })
})

const editProof = (proof) => {
    router.visit(route('online-payment-proofs.edit', proof.id))
}

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

:deep(#proofs-table_info),
:deep(#proofs-table_paginate) {
    display: none;
}

#table-info :deep(.dataTables_info),
#table-pagination :deep(.dataTables_paginate) {
    display: block;
}

:deep(#proofs-table tbody td) {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

@media (min-width: 640px) {
    :deep(#proofs-table tbody td) {
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 1024px) {
    :deep(#proofs-table) {
        font-size: 0.813rem;
    }

    :deep(#proofs-table th),
    :deep(#proofs-table td) {
        padding: 0.5rem;
    }
}
</style>
