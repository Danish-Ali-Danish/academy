<script setup>
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    settings: Object,
    carryForwards: Object,
    filters: Object,
});

const form = useForm({
    is_enabled: props.settings.is_enabled ?? true,
    scope: props.settings.scope ?? 'full',
    max_months: props.settings.max_months ?? 3,
});

const saveSettings = () => {
    form.post(route('carry-forwards.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: show a toast
        }
    });
};

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const month = ref(props.filters.month ?? '');

const filterRecords = () => {
    router.get(route('carry-forwards.index'), {
        search: search.value,
        status: status.value,
        month: month.value,
    }, { preserveState: true, replace: true });
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => filterRecords(), 300);
});
watch([status, month], () => filterRecords());

const formatAmount = (val) => {
    return Number(val || 0).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="Carry Forwards" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Carry Forwards & Settings</h2>
                    <p class="mt-1 text-sm text-gray-500">Manage automatic fee carry forward rules and view history.</p>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Settings Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Automation Settings</h3>
                        <p class="text-sm text-gray-500">Configure how previous unpaid balances cascade into new vouchers.</p>
                    </div>
                </div>

                <form @submit.prevent="saveSettings" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Enable Auto Carry Forward</label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_enabled" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900">{{ form.is_enabled ? 'Active' : 'Disabled' }}</span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Scope of Arrears</label>
                            <select v-model="form.scope" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="full">Full Outstanding (Including Fines)</option>
                                <option value="fee_only">Fee Balance Only</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Months to Carry</label>
                            <input type="number" v-model="form.max_months" min="1" max="12" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            <p class="mt-1 text-xs text-gray-500">How many previous months to automatically fetch.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- History List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Carry Forward History</h3>
                        <p class="text-sm text-gray-500">Log of all automatically cascaded arrears.</p>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <input v-model="search" type="text" placeholder="Search student..." class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <select v-model="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="cleared">Cleared</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Month</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Carry Amount</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="cf in carryForwards.data" :key="cf.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ cf.student_enrollment.student.student_name }}</div>
                                    <div class="text-xs text-gray-500">{{ cf.student_enrollment.student.roll_no }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ cf.student_enrollment.class_section.branch_class.class.class_name }} - {{ cf.student_enrollment.class_section.section.section_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ cf.from_month_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-semibold">
                                    {{ cf.to_month_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                    Rs {{ formatAmount(cf.carry_amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="cf.status === 'cleared'" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Cleared</span>
                                    <span v-else class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Pending</span>
                                </td>
                            </tr>
                            <tr v-if="carryForwards.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No carry forward records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center" v-if="carryForwards.total > 0">
                    <span class="text-sm text-gray-500">Showing {{ carryForwards.from }} to {{ carryForwards.to }} of {{ carryForwards.total }}</span>
                    <!-- Simple pagination links can be added here using Inertia Link components -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
