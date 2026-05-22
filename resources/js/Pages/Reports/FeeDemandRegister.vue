<script setup>
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    summary: Object,
    classWiseData: Array,
    feeTypeData: Array,
    filters: Object,
    dropdowns: Object,
});

const month = ref(props.filters.month);
const year = ref(props.filters.year);
const branchId = ref(props.filters.branch_id || '');
const classId = ref(props.filters.class_id || '');
const feeTypeId = ref(props.filters.fee_type_id || '');
const academicYearId = ref(props.filters.academic_year_id || '');

const currentTab = ref('class'); // 'class' or 'feeType'
const showDrillDown = ref(false);
const drillDownLoading = ref(false);
const drillDownStudents = ref([]);
const selectedClassName = ref('');

const filterRecords = () => {
    router.get(route('reports.fee-demand-register'), {
        month: month.value,
        year: year.value,
        branch_id: branchId.value,
        class_id: classId.value,
        fee_type_id: feeTypeId.value,
        academic_year_id: academicYearId.value,
    }, { preserveState: true, replace: true });
};

watch([month, year, branchId, classId, feeTypeId, academicYearId], () => filterRecords());

const loadDrillDown = async (classRow) => {
    selectedClassName.value = classRow.class_name;
    showDrillDown.value = true;
    drillDownLoading.value = true;
    drillDownStudents.value = [];
    
    try {
        const url = new URL(route('api.reports.fee-demand-register.drill-down'), window.location.origin);
        url.searchParams.append('class_section_id', classRow.class_section_id);
        url.searchParams.append('month', month.value);
        url.searchParams.append('year', year.value);
        if(academicYearId.value) url.searchParams.append('academic_year_id', academicYearId.value);
        
        const response = await fetch(url);
        const data = await response.json();
        drillDownStudents.value = data;
    } catch (error) {
        console.error("Error loading drill down:", error);
    } finally {
        drillDownLoading.value = false;
    }
};

const formatAmount = (val) => {
    return Number(val || 0).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Fee Demand Register" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Fee Demand Register</h2>
                    <p class="mt-1 text-sm text-gray-500">Comprehensive report of fee demands vs collections.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="printReport" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                    <!-- Export options can be added here -->
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Month</label>
                        <select v-model="month" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option v-for="m in 12" :key="m" :value="m">{{ new Date(2000, m - 1).toLocaleString('default', { month: 'long' }) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                        <select v-model="year" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Branch</label>
                        <select v-model="branchId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Branches</option>
                            <option v-for="b in dropdowns.branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Class</label>
                        <select v-model="classId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Classes</option>
                            <option v-for="c in dropdowns.classes" :key="c.id" :value="c.id">{{ c.class_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Fee Type</label>
                        <select v-model="feeTypeId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Fee Types</option>
                            <option v-for="f in dropdowns.feeTypes" :key="f.id" :value="f.id">{{ f.fee_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Academic Year</label>
                        <select v-model="academicYearId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Years</option>
                            <option v-for="y in dropdowns.academicYears" :key="y.id" :value="y.id">{{ y.year_name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="rounded-xl bg-blue-50 border border-blue-200 p-5 shadow-sm">
                    <p class="text-sm text-blue-700 font-bold uppercase tracking-wider">Total Demand</p>
                    <p class="mt-2 text-3xl font-black text-blue-900">Rs {{ formatAmount(summary.demand) }}</p>
                </div>
                <div class="rounded-xl bg-green-50 border border-green-200 p-5 shadow-sm">
                    <p class="text-sm text-green-700 font-bold uppercase tracking-wider">Collected</p>
                    <p class="mt-2 text-3xl font-black text-green-900">Rs {{ formatAmount(summary.collected) }}</p>
                </div>
                <div class="rounded-xl bg-red-50 border border-red-200 p-5 shadow-sm">
                    <p class="text-sm text-red-700 font-bold uppercase tracking-wider">Outstanding</p>
                    <p class="mt-2 text-3xl font-black text-red-900">Rs {{ formatAmount(summary.outstanding) }}</p>
                </div>
                <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-5 shadow-sm">
                    <p class="text-sm text-indigo-700 font-bold uppercase tracking-wider">Collection %</p>
                    <p class="mt-2 text-3xl font-black text-indigo-900">{{ summary.percentage }}%</p>
                    <div class="w-full bg-indigo-200 rounded-full h-2 mt-3">
                        <div class="bg-indigo-600 h-2 rounded-full" :style="`width: ${summary.percentage}%`"></div>
                    </div>
                </div>
            </div>

            <!-- Tabs & Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex" aria-label="Tabs">
                        <button @click="currentTab = 'class'" :class="[currentTab === 'class' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm']">
                            Class-wise Breakdown
                        </button>
                        <button @click="currentTab = 'feeType'" :class="[currentTab === 'feeType' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm']">
                            Fee Type Breakdown
                        </button>
                    </nav>
                </div>

                <div class="p-0 overflow-x-auto">
                    <!-- Class-wise Table -->
                    <table v-if="currentTab === 'class'" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Section</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Students</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Demand</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Collected</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Outstanding</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">%</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="row in classWiseData" :key="row.class_section_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ row.class_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">{{ row.total_students }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">Rs {{ formatAmount(row.demand_amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">Rs {{ formatAmount(row.collected_amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-semibold">Rs {{ formatAmount(row.outstanding_amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span :class="[
                                        'px-2.5 py-1 text-xs font-bold rounded-full',
                                        row.collection_percentage >= 90 ? 'bg-green-100 text-green-800' :
                                        row.collection_percentage >= 50 ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-red-100 text-red-800'
                                    ]">
                                        {{ row.collection_percentage }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button @click="loadDrillDown(row)" class="text-indigo-600 hover:text-indigo-900">View</button>
                                </td>
                            </tr>
                            <tr v-if="classWiseData.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No data available for the selected filters.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-200">
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">TOTAL</td>
                                <td class="px-6 py-3 text-center text-sm text-gray-900">{{ classWiseData.reduce((acc, curr) => acc + curr.total_students, 0) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-900">Rs {{ formatAmount(summary.demand) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-green-700">Rs {{ formatAmount(summary.collected) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-red-700">Rs {{ formatAmount(summary.outstanding) }}</td>
                                <td class="px-6 py-3 text-center text-sm text-indigo-700">{{ summary.percentage }}%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Fee Type-wise Table -->
                    <table v-if="currentTab === 'feeType'" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee Type</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Demand</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Collected</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Outstanding</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">%</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="row in feeTypeData" :key="row.fee_type_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ row.fee_type_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">Rs {{ formatAmount(row.demand_amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">Rs {{ formatAmount(row.collected_amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-semibold">Rs {{ formatAmount(row.outstanding_amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <span :class="[
                                        'px-2.5 py-1 text-xs font-bold rounded-full',
                                        row.collection_percentage >= 90 ? 'bg-green-100 text-green-800' :
                                        row.collection_percentage >= 50 ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-red-100 text-red-800'
                                    ]">
                                        {{ row.collection_percentage }}%
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="feeTypeData.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">No data available for the selected filters.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold border-t-2 border-gray-200">
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">TOTAL</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-900">Rs {{ formatAmount(summary.demand) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-green-700">Rs {{ formatAmount(summary.collected) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-red-700">Rs {{ formatAmount(summary.outstanding) }}</td>
                                <td class="px-6 py-3 text-center text-sm text-indigo-700">{{ summary.percentage }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Drill Down Modal -->
        <div v-if="showDrillDown" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDrillDown = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Drill Down: {{ selectedClassName }}
                                    </h3>
                                    <button @click="showDrillDown = false" class="text-gray-400 hover:text-gray-500">
                                        <span class="sr-only">Close</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2 overflow-x-auto">
                                    <div v-if="drillDownLoading" class="flex justify-center py-8">
                                        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>
                                    <table v-else class="min-w-full divide-y divide-gray-200 border">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Student Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Roll No</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Demand</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Paid</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Outstanding</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(student, index) in drillDownStudents" :key="index">
                                                <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ student.student_name }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-500">{{ student.roll_no }}</td>
                                                <td class="px-4 py-2 text-sm text-right text-gray-900">Rs {{ formatAmount(student.demand) }}</td>
                                                <td class="px-4 py-2 text-sm text-right text-green-600">Rs {{ formatAmount(student.paid) }}</td>
                                                <td class="px-4 py-2 text-sm text-right font-bold text-red-600">Rs {{ formatAmount(student.outstanding) }}</td>
                                                <td class="px-4 py-2 text-sm text-center">
                                                    <span v-if="student.status === 'paid'" class="text-green-600 font-bold">✅ Paid</span>
                                                    <span v-else-if="student.status === 'partial'" class="text-yellow-600 font-bold">⚠️ Partial</span>
                                                    <span v-else class="text-red-600 font-bold">❌ Unpaid</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="showDrillDown = false">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
