<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';
import Pagination from '@/Components/Pagination.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import VanFormModal from '@/Components/VanFormModal.vue';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    bookers: Object,
    distributions: Array,
    vans: Array,
    filters: Object
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters.search || '');
const isVanModalOpen = ref(false);
const pendingVanCode = ref(null);

// Watch for vans list changes to auto-select newly added van
watch(() => props.vans, (newVans) => {
    if (pendingVanCode.value && newVans) {
        const newVan = newVans.find(v => v.code === pendingVanCode.value);
        if (newVan) {
            form.van_id = newVan.id;
            pendingVanCode.value = null;
        }
    }
}, { deep: true });

const form = useForm({
    name: '',
    code: '',
    van_id: '',
    distribution_id: '',
});

// Format vans to show distribution name only if All Distributions is selected
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution 
            ? `${van.code} (${van.distribution.name})` 
            : van.code
    }));
});

// Search Watcher
watch(search, debounce((value) => {
    router.get(route('order-bookers.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    
    if (item) {
        form.name = item.name;
        form.code = item.code;
        form.van_id = item.van_id || '';
        form.distribution_id = item.distribution_id;
    } else {
        form.reset();
        // Auto-select distribution if a specific distribution is selected
        if (currentDistribution.value && currentDistribution.value.id) {
            form.distribution_id = currentDistribution.value.id;
        } else {
            form.distribution_id = '';
        }
        form.van_id = '';
    }
    
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const validateForm = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.name) {
        form.setError('name', 'The name field is required.');
        isValid = false;
    }
    if (!form.code) {
        form.setError('code', 'The Code field is required.');
        isValid = false;
    }
    // Only validate distribution if the user is in "All Distributions" view
    if (!currentDistribution.value?.id && !form.distribution_id) {
        form.setError('distribution_id', 'The Distribution field is required.');
        isValid = false;
    }

    return isValid;
};

const submit = () => {
    if (!validateForm()) return;

    if (isEditing.value) {
        form.put(route('order-bookers.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('order-bookers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const handleVanSaved = (vanCode) => {
    // Store pending van code to auto-select after page refresh
    pendingVanCode.value = vanCode;
};

const deleteBooker = (item) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete order booker "${item.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('order-bookers.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Order Booker has been deleted.', 'success');
                }
            });
        }
    })
};
</script>

<template>
    <Head title="Order Bookers" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Order Bookers</h1>
                    <p class="text-gray-500 mt-1">Manage sales representatives (Order Bookers).</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search..." 
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 w-64 shadow-sm"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <button 
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Booker
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">OB Name</th>
                                <th class="px-6 py-4">OB Code</th>
                                <th class="px-6 py-4">VAN</th>
                                <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in bookers.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ item.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ item.code }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ item.van?.code || '-' }}</td>
                                <td v-if="!currentDistribution?.id" class="px-6 py-4 text-gray-500">
                                    {{ item.distribution?.name || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="openModal(item)"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button 
                                            @click="deleteBooker(item)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="bookers.data.length === 0">
                                <td :colspan="!currentDistribution?.id ? 5 : 4" class="px-6 py-12 text-center text-gray-500">No Order Bookers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="bookers.links" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                    {{ isEditing ? 'Edit' : 'Add New' }} Booker
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Distribution Select (Only when "All Distributions" is selected) -->
                    <div v-if="!currentDistribution?.id">
                        <SearchableSelect 
                            v-model="form.distribution_id"
                            label="Distribution"
                            :options="distributions"
                            option-value="id"
                            option-label="name"
                            placeholder="Select a distribution"
                            :error="form.errors.distribution_id"
                            required
                        />
                    </div>

                    <div>
                        <InputLabel value="OB Name" />
                        <TextInput 
                            v-model="form.name" 
                            type="text" 
                            class="mt-1 block w-full" 
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.name }"
                        />
                         <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <InputLabel value="OB Code" />
                        <TextInput 
                            v-model="form.code" 
                            type="text" 
                            class="mt-1 block w-full" 
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.code }"
                        />
                         <div v-if="form.errors.code" class="text-xs text-red-600 mt-1">{{ form.errors.code }}</div>
                    </div>

                    <!-- VAN Select -->
                    <div>
                        <InputLabel value="VAN" />
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <SearchableSelect 
                                    v-model="form.van_id"
                                    :options="vanOptions"
                                    option-value="id"
                                    option-label="displayLabel"
                                    placeholder="Select VAN"
                                    :error="form.errors.van_id"
                                />
                            </div>
                            <button type="button" @click="isVanModalOpen = true" class="p-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors" title="Add New VAN">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton 
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- VAN Form Modal -->
        <VanFormModal 
            :show="isVanModalOpen"
            :distributions="distributions"
            @close="isVanModalOpen = false"
            @saved="handleVanSaved"
        />
    </DashboardLayout>
</template>
