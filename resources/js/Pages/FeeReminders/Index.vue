<script setup>
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const search = ref('');
const status = ref('');
const channel = ref('');
const loading = ref(false);
const logs = ref([]);
const totalRecords = ref(0);
const currentPage = ref(1);
const perPage = ref(10);

const columns = [
    { key: 'DT_RowIndex', label: 'S.No', width: '60px' },
    { key: 'student_name', label: 'Student Name' },
    { key: 'voucher_no', label: 'Voucher No' },
    { key: 'channel', label: 'Channel', width: '100px' },
    { key: 'status', label: 'Status', width: '120px' },
    { key: 'sent_at', label: 'Sent At', width: '160px' },
    { key: 'action', label: 'Action', width: '100px' },
];

const fetchLogs = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/fee-reminders?draw=1&start=${(currentPage.value - 1) * perPage.value}&length=${perPage.value}&search[value]=${search.value}&status=${status.value}&channel=${channel.value}`);
        const data = await response.json();
        logs.value = data.data;
        totalRecords.value = data.recordsFiltered;
    } catch (error) {
        console.error('Error fetching logs:', error);
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    channel.value = '';
    currentPage.value = 1;
    fetchLogs();
};

window.viewLog = (id) => {
    // Basic implementation for now - in full system this opens a modal
    alert('Viewing log ID: ' + id);
};

fetchLogs();
</script>

<template>
    <Head title="Reminder Logs" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-2xl font-bold text-gray-800">Fee Reminder Logs</h2>
                            <div class="flex gap-2">
                                <Link :href="route('fee-reminder-rules.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-lg transition-colors border border-gray-300">
                                    Manage Rules
                                </Link>
                                <Link :href="route('fee-reminder-templates.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-lg transition-colors border border-gray-300">
                                    Manage Templates
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search Student/Voucher</label>
                                <input v-model="search" @keyup.enter="fetchLogs" type="text"
                                    placeholder="Search..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                                <select v-model="channel" @change="fetchLogs"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All Channels</option>
                                    <option value="sms">SMS</option>
                                    <option value="email">Email</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="status" @change="fetchLogs"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All Statuses</option>
                                    <option value="queued">Queued</option>
                                    <option value="sent">Sent</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button @click="clearFilters"
                                    class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition-colors border border-gray-300">
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
                            <p class="text-gray-600">Loading logs...</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th v-for="column in columns" :key="column.key" :width="column.width"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                            {{ column.label }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.DT_RowIndex }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ log.student_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.voucher_no }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                {{ log.channel }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'px-2 py-1 text-xs font-medium rounded-full': true,
                                                'bg-yellow-100 text-yellow-800': log.status === 'Queued',
                                                'bg-green-100 text-green-800': log.status === 'Sent' || log.status === 'Delivered',
                                                'bg-red-100 text-red-800': log.status === 'Failed'
                                            }">
                                                {{ log.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.sent_at }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div v-html="log.action"></div>
                                        </td>
                                    </tr>
                                    <tr v-if="logs.length === 0">
                                        <td :colspan="columns.length" class="px-6 py-8 text-center text-gray-500">
                                            No reminder logs found.
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
                                <button @click="currentPage--; fetchLogs()" :disabled="currentPage === 1"
                                    class="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50">
                                    Previous
                                </button>
                                <button @click="currentPage++; fetchLogs()"
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
