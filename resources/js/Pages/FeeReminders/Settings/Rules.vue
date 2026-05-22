<script setup>
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    rules: Array,
    branches: Array,
    feeTypes: Array,
});

const isModalOpen = ref(false);
const editingRule = ref(null);

const form = useForm({
    rule_name: '',
    trigger_type: 'on_due',
    days_offset: 0,
    channel: 'whatsapp',
    branch_id: '',
    fee_type_id: '',
    is_active: true,
});

const openModal = (rule = null) => {
    if (rule) {
        editingRule.value = rule;
        form.rule_name = rule.rule_name;
        form.trigger_type = rule.trigger_type;
        form.days_offset = rule.days_offset;
        form.channel = rule.channel;
        form.branch_id = rule.branch_id || '';
        form.fee_type_id = rule.fee_type_id || '';
        form.is_active = rule.is_active;
    } else {
        editingRule.value = null;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (editingRule.value) {
        form.put(route('fee-reminder-rules.update', editingRule.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('fee-reminder-rules.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteRule = (id) => {
    if (confirm('Are you sure you want to delete this rule?')) {
        form.delete(route('fee-reminder-rules.destroy', id));
    }
};
</script>

<template>
    <Head title="Reminder Rules" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-2xl font-bold text-gray-800">Fee Reminder Rules</h2>
                            <div class="flex gap-2">
                                <Link :href="route('fee-reminders.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-lg transition-colors border border-gray-300">
                                    Back to Logs
                                </Link>
                                <button @click="openModal()"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                    Create Rule
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rule Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trigger</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="rule in rules" :key="rule.id">
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ rule.rule_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ rule.trigger_type.replace('_', ' ') }}
                                            <span v-if="rule.days_offset > 0"> ({{ rule.days_offset }} days)</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ rule.channel }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 rounded text-xs">
                                                {{ rule.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button @click="openModal(rule)" class="text-blue-600 mr-3 hover:underline">Edit</button>
                                            <button @click="deleteRule(rule.id)" class="text-red-600 hover:underline">Delete</button>
                                        </td>
                                    </tr>
                                    <tr v-if="rules.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No rules configured.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
                <h3 class="text-lg font-bold mb-4">{{ editingRule ? 'Edit Rule' : 'Create Rule' }}</h3>
                
                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Rule Name</label>
                            <input v-model="form.rule_name" type="text" class="mt-1 block w-full rounded-md border-gray-300" required />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trigger Type</label>
                            <select v-model="form.trigger_type" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="before_due">Before Due Date</option>
                                <option value="on_due">On Due Date</option>
                                <option value="after_due">After Due Date</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Days Offset (0 for on due)</label>
                            <input v-model="form.days_offset" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300" required />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Channel</label>
                            <select v-model="form.channel" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="sms">SMS</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Specific Branch (Optional)</label>
                            <select v-model="form.branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All Branches</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Specific Fee Type (Optional)</label>
                            <select v-model="form.fee_type_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All Fee Types</option>
                                <option v-for="f in feeTypes" :key="f.id" :value="f.id">{{ f.fee_name }}</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center mt-6">
                            <input v-model="form.is_active" type="checkbox" id="is_active" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">Rule is Active</label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
