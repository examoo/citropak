<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    bookers: Array,
});

const showModal = ref(false);
const editingBooker = ref(null);

const form = useForm({
    name: '',
    code: '',
    phone: '',
    status: 'active',
});

const openCreateModal = () => {
    editingBooker.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (booker) => {
    editingBooker.value = booker;
    form.name = booker.name;
    form.code = booker.code === '-' ? '' : booker.code;
    form.phone = booker.phone === '-' ? '' : booker.phone;
    form.status = booker.status;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingBooker.value = null;
    form.reset();
};

const submit = () => {
    if (editingBooker.value) {
        form.put(route('credit-bookers.update', editingBooker.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('credit-bookers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteBooker = (id) => {
    if (confirm('Are you sure you want to delete this credit booker?')) {
        router.delete(route('credit-bookers.destroy', id));
    }
};
</script>

<template>

    <Head title="Credit Bookers" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white p-4 rounded-xl shadow-lg flex-1 mr-4">
                    <h1 class="text-xl font-bold">Credit Bookers</h1>
                    <p class="text-amber-100 text-sm">Manage staff responsible for credit collection</p>
                </div>
                <PrimaryButton @click="openCreateModal" class="bg-amber-600 hover:bg-amber-700">
                    + Add Credit Booker
                </PrimaryButton>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="booker in bookers" :key="booker.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ booker.code }}</td>
                            <td class="px-4 py-3">{{ booker.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ booker.phone }}</td>
                            <td class="px-4 py-3">
                                <span :class="booker.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    class="px-2 py-1 rounded text-xs font-medium">
                                    {{ booker.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="openEditModal(booker)"
                                    class="text-blue-600 hover:text-blue-800 mr-3">Edit</button>
                                <button @click="deleteBooker(booker.id)"
                                    class="text-red-600 hover:text-red-800">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="bookers.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                No credit bookers found. Click "Add Credit Booker" to create one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">
                            {{ editingBooker ? 'Edit Credit Booker' : 'Add Credit Booker' }}
                        </h3>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel value="Name *" />
                                <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <InputLabel value="Code" />
                                <TextInput v-model="form.code" type="text" class="mt-1 block w-full" />
                            </div>

                            <div>
                                <InputLabel value="Phone" />
                                <TextInput v-model="form.phone" type="text" class="mt-1 block w-full" />
                            </div>

                            <div>
                                <InputLabel value="Status" />
                                <select v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button type="button" @click="closeModal"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                    Cancel
                                </button>
                                <PrimaryButton type="submit" :disabled="form.processing"
                                    class="flex-1 bg-amber-600 hover:bg-amber-700">
                                    {{ form.processing ? 'Saving...' : (editingBooker ? 'Update' : 'Create') }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </DashboardLayout>
</template>
