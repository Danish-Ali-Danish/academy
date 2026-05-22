<script setup>
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    pendingRequests: { type: Array, default: () => [] },
});

const search = ref('');
const loading = ref(false);

const columns = [
    { key: 'DT_RowIndex', label: 'S.No', width: '60px' },
    { key: 'fee_type', label: 'Fee Type', width: '120px' },
    { key: 'class_name', label: 'Class', width: '100px' },
    { key: 'old_amount', label: 'Old Amount', width: '100px' },
    { key: 'new_amount', label: 'New Amount', width: '100px' },
    { key: 'change', label: 'Change', width: '100px' },
    { key: 'changed_at', label: 'Changed At', width: '140px' },
    { key: 'action', label: 'Action', width: '100px' },
];

const logs = ref([]);
const totalRecords = ref(0);
const currentPage = ref(1);
const perPage = ref(10);

const fetchLogs = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            draw: '1',
            start: String((currentPage.value - 1) * perPage.value),
            length: String(perPage.value),
            'search[value]': search.value,
        });

        const response = await fetch(`/fee-structure-change-logs?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Server returned status ${response.status}`);
        }

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('Server returned a non-JSON response.');
        }

        const data = await response.json();
        logs.value = data.data;
        totalRecords.value = data.recordsFiltered;
    } catch (error) {
        console.error('Error fetching logs:', error);
    } finally {
        loading.value = false;
    }
};

const viewLog = (data) => {
    router.get(`/fee-structure-change-logs/${data.id}`);
};

const clearFilters = () => {
    search.value = '';
    fetchLogs();
};

const approveRequest = async (request) => {
    const result = await Swal.fire({
        title: `Approve ${request.request_code}?`,
        input: 'textarea',
        inputLabel: 'Approval remarks',
        inputPlaceholder: 'Enter approval remarks...',
        inputValidator: (value) => !value ? 'Remarks are required' : undefined,
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#4f46e5',
    });

    if (!result.isConfirmed) return;

    router.post(route('fee-structure-change-requests.approve', request.id), {
        remarks: result.value,
    }, { preserveScroll: true });
};

const rejectRequest = async (request) => {
    const result = await Swal.fire({
        title: `Reject ${request.request_code}?`,
        input: 'textarea',
        inputLabel: 'Rejection remarks',
        inputPlaceholder: 'Enter rejection reason...',
        inputValidator: (value) => !value ? 'Remarks are required' : undefined,
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#dc2626',
    });

    if (!result.isConfirmed) return;

    router.post(route('fee-structure-change-requests.reject', request.id), {
        remarks: result.value,
    }, { preserveScroll: true });
};

fetchLogs();
</script>

<template>
    <Head title="Fee Structure Change Logs" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-800">Fee Structure Change Logs</h2>
                        <p class="mt-1 text-sm text-gray-600">Audit trail of all fee structure changes</p>
                    </div>
                </div>

                <div v-if="props.pendingRequests.length" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Pending Approval Requests</h3>
                                <p class="mt-1 text-sm text-gray-600">Approve or reject fee structure version changes before they become active.</p>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-sm font-medium">
                                {{ props.pendingRequests.length }} pending
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-indigo-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Request</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Structure</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Impact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="request in props.pendingRequests" :key="request.id">
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-semibold text-indigo-700">{{ request.request_code }}</div>
                                        <div class="text-xs text-gray-500">{{ request.requested_by }} · {{ request.requested_at }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-medium text-gray-900">{{ request.fee_type }}</div>
                                        <div>{{ request.branch_name }} · {{ request.class_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="text-gray-500">Old Rs {{ request.old_amount }}</div>
                                        <div class="font-semibold text-green-700">New Rs {{ request.new_amount }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div>{{ request.affected_students_count }} students</div>
                                        <div>{{ request.unpaid_vouchers_count }} unpaid vouchers</div>
                                        <div class="font-medium">Rs {{ request.estimated_monthly_difference }} monthly diff</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 max-w-xs">
                                        {{ request.reason }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex gap-2">
                                            <button @click="approveRequest(request)" class="px-3 py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 font-medium">Approve</button>
                                            <button @click="rejectRequest(request)" class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 font-medium">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input v-model="search" @keyup.enter="fetchLogs" type="text"
                                    placeholder="Search by reason or fee type..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div class="flex items-end">
                                <button @click="clearFilters"
                                    class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors">
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="loading" class="text-center py-8">
                            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <p class="mt-2 text-gray-600">Loading change logs...</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th v-for="column in columns" :key="column.key" :width="column.width"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ column.label }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in logs" :key="log.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.DT_RowIndex }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.fee_type }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.class_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Rs. {{ log.old_amount }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Rs. {{ log.new_amount }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-html="log.change"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.changed_at }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button
                                                type="button"
                                                @click="viewLog(log)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                                            >
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="logs.length === 0">
                                        <td :colspan="columns.length" class="px-6 py-8 text-center text-gray-500">
                                            No change logs found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="logs.length > 0" class="mt-4 flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span> to
                                <span class="font-medium">{{ Math.min(currentPage * perPage, totalRecords) }}</span> of
                                <span class="font-medium">{{ totalRecords }}</span> results
                            </div>
                            <div class="flex gap-2">
                                <button @click="currentPage--" :disabled="currentPage === 1"
                                    class="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50">
                                    Previous
                                </button>
                                <button @click="currentPage++"
                                    :disabled="currentPage * perPage >= totalRecords"
                                    class="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
