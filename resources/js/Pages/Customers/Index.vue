<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';
import Pagination from '@/Components/Pagination.vue';
import { debounce } from 'lodash';
import { watch, ref, computed } from 'vue';

const props = defineProps({
    customers: {
        type: Object,
        required: true
    },
    attributes: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '', sort_field: 'created_at', sort_direction: 'desc' })
    }
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingCustomerId = ref(null);

// Filter State
const search = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || '');
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

// Debounced Search and Filter Watcher
watch([search, filterStatus, sortField, sortDirection], debounce(() => {
    router.get(route('customers.index'), {
        search: search.value,
        status: filterStatus.value,
        sort_field: sortField.value,
        sort_direction: sortDirection.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const handleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (field) => {
    if (sortField.value !== field) return '↕'; 
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const form = useForm({
    customer_code: '',
    van: '',
    shop_name: '',
    address: '',
    sub_address: '',
    phone: '',
    category: '',
    channel: '',
    ntn_number: '',
    distribution: '',
    status: 'active',
    adv_tax_percent: '0.00'
});

const openModal = (customer = null) => {
    isEditing.value = !!customer;
    editingCustomerId.value = customer?.id;
    
    if (customer) {
        form.customer_code = customer.customer_code;
        form.van = customer.van;
        form.shop_name = customer.shop_name;
        form.address = customer.address;
        form.sub_address = customer.sub_address;
        form.phone = customer.phone;
        form.category = customer.category;
        form.channel = customer.channel;
        form.ntn_number = customer.ntn_number;
        form.distribution = customer.distribution;
        form.status = customer.status;
        form.adv_tax_percent = customer.adv_tax_percent;
    } else {
        form.reset();
        form.status = 'active';
        form.adv_tax_percent = '0.00';
    }
    
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('customers.update', editingCustomerId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCustomer = (customer) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete "${customer.shop_name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('customers.destroy', customer.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire(
                        'Deleted!',
                        'Customer has been deleted.',
                        'success'
                    )
                }
            });
        }
    })
};

// Quick Add Attribute Logic
const quickAddAttribute = async (type, title) => {
    // Check for open dialog to attach SweetAlert
    const openDialog = document.querySelector('dialog[open]');
    
    const { value: newValue } = await Swal.fire({
        title: `Add New ${title}`,
        input: 'text',
        inputLabel: `${title} Name`,
        inputPlaceholder: `Enter ${title}`,
        showCancelButton: true,
        target: openDialog || 'body',
        customClass: {
            container: 'z-[9999]' 
        },
        inputValidator: (value) => {
            if (!value) {
                return 'You need to write something!'
            }
        }
    });

    if (newValue) {
        router.post(route('customer-attributes.store'), {
            type: type,
            value: newValue
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                 Swal.fire({
                    title: 'Success', 
                    text: `${title} added successfully`, 
                    icon: 'success',
                    target: openDialog || 'body',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Auto Select
                form[type === 'sub_address' ? 'sub_address' : type] = newValue;
            },
            onError: () => {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to add attribute',
                    icon: 'error',
                    target: openDialog || 'body'
                });
            }
        });
    }
};

// Helpers to safely get attributes or empty array
const getAttributes = (type) => props.attributes[type] || [];

</script>

<template>
    <Head title="Customer Management" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                    <p class="text-gray-500 mt-1">Manage your customer database</p>
                </div>
                
                <div class="flex items-center gap-3">
                     <!-- Search Bar -->
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search customers..." 
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 w-64 shadow-sm"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <select 
                        v-model="filterStatus"
                        class="py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-white shadow-sm"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button 
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Customer
                    </button>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th @click="handleSort('customer_code')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Code {{ getSortIcon('customer_code') }}
                                </th>
                                <th @click="handleSort('shop_name')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Shop Name {{ getSortIcon('shop_name') }}
                                </th>
                                <th @click="handleSort('van')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    VAN {{ getSortIcon('van') }}
                                </th>
                                <th @click="handleSort('phone')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Telephone {{ getSortIcon('phone') }}
                                </th>
                                <th @click="handleSort('status')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Status {{ getSortIcon('status') }}
                                </th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ customer.customer_code || '-' }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ customer.shop_name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ customer.van || '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ customer.phone || 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        customer.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'
                                    ]">
                                        {{ customer.status ? customer.status.toUpperCase() : 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="openModal(customer)"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit Customer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="deleteCustomer(customer)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete Customer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="font-medium">No customers found</p>
                                        <p class="text-xs">Try adjusting your search or add a new customer.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <!-- Customer Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="4xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                    {{ isEditing ? 'Edit Customer' : 'Add New Customer' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Customer Code" />
                            <TextInput v-model="form.customer_code" type="text" class="mt-1 block w-full" placeholder="e.g. CPSSGD03739" />
                            <div v-if="form.errors.customer_code" class="text-xs text-red-600 mt-1">{{ form.errors.customer_code }}</div>
                        </div>
                        <div>
                            <InputLabel value="VAN" />
                            <div class="flex gap-2">
                                <select 
                                    v-model="form.van"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select VAN</option>
                                    <option v-for="attr in getAttributes('van')" :key="attr.id" :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="quickAddAttribute('van', 'VAN')" class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors" title="Add New VAN">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Shop Name" />
                            <TextInput v-model="form.shop_name" type="text" class="mt-1 block w-full" required />
                            <div v-if="form.errors.shop_name" class="text-xs text-red-600 mt-1">{{ form.errors.shop_name }}</div>
                        </div>
                         <div>
                            <InputLabel value="Address" />
                            <TextInput v-model="form.address" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                            <InputLabel value="Sub Address" />
                            <div class="flex gap-2">
                                <select 
                                    v-model="form.sub_address"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select Sub Address</option>
                                    <option v-for="attr in getAttributes('sub_address')" :key="attr.id" :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="quickAddAttribute('sub_address', 'Sub Address')" class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors" title="Add New Sub Address">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Telephone" />
                            <TextInput v-model="form.phone" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 4 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                            <InputLabel value="Categories" />
                            <div class="flex gap-2">
                                <select 
                                    v-model="form.category"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select Category</option>
                                    <option v-for="attr in getAttributes('category')" :key="attr.id" :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="quickAddAttribute('category', 'Category')" class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors" title="Add New Category">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                         <div>
                            <InputLabel value="Channel" />
                            <div class="flex gap-2">
                                <select 
                                    v-model="form.channel"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select Channel</option>
                                    <option v-for="attr in getAttributes('channel')" :key="attr.id" :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="quickAddAttribute('channel', 'Channel')" class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors" title="Add New Channel">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5 -->
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                            <InputLabel value="NTN Number" />
                            <TextInput v-model="form.ntn_number" type="text" class="mt-1 block w-full" />
                        </div>
                         <div>
                            <InputLabel value="Distribution" />
                            <div class="flex gap-2">
                                <select 
                                    v-model="form.distribution"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">Select Distribution</option>
                                    <option v-for="attr in getAttributes('distribution')" :key="attr.id" :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="quickAddAttribute('distribution', 'Distribution')" class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors" title="Add New Distribution">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Row 6 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Status" />
                            <select 
                                v-model="form.status" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Adv. Tax (%)" />
                            <TextInput v-model="form.adv_tax_percent" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t sticky bottom-0 bg-white">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton 
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Customer' : 'Create Customer') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
