<script setup>
import AuthenticatedLayout from '@/Components/Layout/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    templates: Array,
    rules: Array,
    branches: Array,
});

const isModalOpen = ref(false);
const editingTemplate = ref(null);

const form = useForm({
    rule_id: '',
    channel: 'whatsapp',
    template_body: '',
    language: 'en',
    branch_id: '',
});

const openModal = (template = null) => {
    if (template) {
        editingTemplate.value = template;
        form.rule_id = template.rule_id || '';
        form.channel = template.channel;
        form.template_body = template.template_body;
        form.language = template.language;
        form.branch_id = template.branch_id || '';
    } else {
        editingTemplate.value = null;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (editingTemplate.value) {
        form.put(route('fee-reminder-templates.update', editingTemplate.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('fee-reminder-templates.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTemplate = (id) => {
    if (confirm('Are you sure you want to delete this template?')) {
        form.delete(route('fee-reminder-templates.destroy', id));
    }
};
</script>

<template>
    <Head title="Reminder Templates" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-2xl font-bold text-gray-800">Fee Reminder Templates</h2>
                            <div class="flex gap-2">
                                <Link :href="route('fee-reminders.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold rounded-lg transition-colors border border-gray-300">
                                    Back to Logs
                                </Link>
                                <button @click="openModal()"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                    Create Template
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rule</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Language</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Body Preview</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="template in templates" :key="template.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ template.rule ? template.rule.rule_name : 'No specific rule' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ template.channel }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ template.language }}</td>
                                        <td class="px-6 py-4 text-sm">{{ template.template_body.substring(0, 50) }}...</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button @click="openModal(template)" class="text-blue-600 mr-3 hover:underline">Edit</button>
                                            <button @click="deleteTemplate(template.id)" class="text-red-600 hover:underline">Delete</button>
                                        </td>
                                    </tr>
                                    <tr v-if="templates.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No templates configured.</td>
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
                <h3 class="text-lg font-bold mb-4">{{ editingTemplate ? 'Edit Template' : 'Create Template' }}</h3>
                
                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Link to Rule (Optional)</label>
                            <select v-model="form.rule_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">General Template</option>
                                <option v-for="r in rules" :key="r.id" :value="r.id">{{ r.rule_name }}</option>
                            </select>
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
                            <label class="block text-sm font-medium text-gray-700">Language</label>
                            <select v-model="form.language" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="en">English</option>
                                <option value="ur">Urdu</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Specific Branch (Optional)</label>
                            <select v-model="form.branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All Branches</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
                            </select>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Template Body</label>
                            <p class="text-xs text-gray-500 mb-1">Available placeholders: {student_name}, {voucher_no}, {net_amount}, {due_date}, {remaining_amount}</p>
                            <textarea v-model="form.template_body" rows="4" class="mt-1 block w-full rounded-md border-gray-300" required></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
