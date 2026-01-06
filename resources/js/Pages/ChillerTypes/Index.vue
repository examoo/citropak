<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    chillerTypes: Object,
    filters: Object
});

const isModalOpen = ref(false);
const editingItem = ref(null);
const search = ref(props.filters.search || '');

const form = useForm({
    name: '',
    status: 'active'
});

watch(search, debounce((value) => {
    router.get(route('chiller-types.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.name = item.name;
        form.status = item.status;
    } else {
        form.reset();
        form.status = 'active';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingItem.value = null;
    form.clearErrors();
};

const submit = () => {
    if (editingItem.value) {
        form.put(route('chiller-types.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('chiller-types.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('chiller-types.destroy', item.id));
        }
    });
};
</script>

<template>
    <Head title="Chiller Types" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Chiller Types</h1>
                    <p class="text-gray-500 mt-1">Manage different types of chillers.</p>
                </div>
                <button 
                    @click="openModal()" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    Add New Type
                </button>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Search -->
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <div class="max-w-md">
                        <TextInput 
                            v-model="search" 
                            type="text" 
                            class="block w-full" 
                            placeholder="Search types..." 
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in chillerTypes.data" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <button @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="chillerTypes.data.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    No chiller types found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="chillerTypes.links.length > 3" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <Pagination :links="chillerTypes.links" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingItem ? 'Edit Chiller Type' : 'Add New Chiller Type' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput 
                            v-model="form.name" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="e.g. Small, Medium, Large"
                            required
                        />
                        <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <InputLabel value="Status" />
                        <select 
                            v-model="form.status" 
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div v-if="form.errors.status" class="text-xs text-red-600 mt-1">{{ form.errors.status }}</div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
