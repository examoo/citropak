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
import VanFormModal from '@/Components/VanFormModal.vue';
import ChannelFormModal from '@/Components/ChannelFormModal.vue';
import SubAddressFormModal from '@/Components/SubAddressFormModal.vue';
import SubDistributionFormModal from '@/Components/SubDistributionFormModal.vue';
import CategoryFormModal from '@/Components/CategoryFormModal.vue';
import RouteFormModal from '@/Components/RouteFormModal.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { debounce } from 'lodash';
import { watch, ref, computed, nextTick } from 'vue';

// Multi-select state
const selectedCustomers = ref([]);
const selectAll = ref(false);

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedCustomers.value = props.customers.data.map(c => c.id);
    } else {
        selectedCustomers.value = [];
    }
};

watch(selectedCustomers, (newVal) => {
    selectAll.value = newVal.length === props.customers.data.length && props.customers.data.length > 0;
});

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

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingCustomerId = ref(null);
const isVanModalOpen = ref(false);
const isChannelModalOpen = ref(false);
const isSubAddressModalOpen = ref(false);
const isSubDistributionModalOpen = ref(false);
const isCategoryModalOpen = ref(false);
const isRouteModalOpen = ref(false);

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
    distribution_id: '',
    sub_distribution: '',
    day: '',
    status: 'active',
    atl: 'active',
    adv_tax_percent: '0.00',
    percentage: '0.00',
    cnic: '',
    sales_tax_number: '',
    sales_tax_status: 'active',
    route: ''
});

const selectedChannelId = ref('');

// Helpers to safely get attributes or empty array
const getAttributes = (type) => props.attributes[type] || [];

// Filtered options based on selected distribution (form.distribution_id or currentDistribution)
const activeDistributionId = computed(() => {
    return form.distribution_id || currentDistribution.value?.id || null;
});

// Filter attributes by distribution - show items matching selected distribution or global (no distribution_id)
const filterByDistribution = (items) => {
    if (!activeDistributionId.value) return items;
    return items.filter(item =>
        !item.distribution_id || item.distribution_id == activeDistributionId.value
    );
};

const filteredVanOptions = computed(() => filterByDistribution(getAttributes('van')));
const filteredCategoryOptions = computed(() => filterByDistribution(getAttributes('category')));
const filteredSubAddressOptions = computed(() => filterByDistribution(getAttributes('sub_address')));
const filteredSubDistributionOptions = computed(() => filterByDistribution(getAttributes('sub_distribution')));
const filteredChannelOptions = computed(() => filterByDistribution(getAttributes('channel')));
const filteredRouteOptions = computed(() => filterByDistribution(getAttributes('route')));

const channelOptions = computed(() => filteredChannelOptions.value);

// Update form fields when detailed channel selection changes (by ID)
watch(selectedChannelId, (newId) => {
    if (!newId) {
        // If cleared manually (though dropdown usually forces selection), don't necessarily clear form unless creating?
        // Let's keep form sync if needed. But usually selection drives form.
        return;
    }

    // Find the full channel object by unique ID
    const channel = channelOptions.value.find(ch => ch.id === newId);

    if (channel) {
        // Sync the form data
        form.channel = channel.value; // The name
        form.adv_tax_percent = parseFloat(channel.adv_tax_percent || 0).toFixed(2);
        form.atl = channel.atl || 'active';
    }
});

const openModal = (customer = null) => {
    isEditing.value = !!customer;
    editingCustomerId.value = customer?.id;

    // Reset selection ID first
    selectedChannelId.value = '';

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
        form.day = customer.day;
        form.status = customer.status;
        form.atl = customer.atl || 'active';
        form.adv_tax_percent = customer.adv_tax_percent;
        form.percentage = customer.percentage;
        form.cnic = customer.cnic;
        form.sales_tax_number = customer.sales_tax_number;
        form.sales_tax_status = customer.sales_tax_status || 'active';
        form.route = customer.route || '';

        // Smartly find the correct channel ID to select
        // Match by Name AND ATL status to distinguish duplicates
        if (customer.channel) {
            const match = channelOptions.value.find(ch =>
                ch.value === customer.channel &&
                ch.atl === (customer.atl || 'active')
            );

            // Fallback to name only if exact match fails
            if (match) {
                selectedChannelId.value = match.id;
            } else {
                const nameMatch = channelOptions.value.find(ch => ch.value === customer.channel);
                if (nameMatch) selectedChannelId.value = nameMatch.id;
            }
        }

    } else {
        form.reset();
        form.status = 'active';
        form.sales_tax_status = 'active';
        form.atl = 'active';
        form.adv_tax_percent = '0.00';
        form.percentage = '0.00';
        form.route = '';
        selectedChannelId.value = '';

        // Auto-select distribution if scoped (DMS mode)
        if (currentDistribution.value?.id) {
            form.distribution_id = currentDistribution.value.id;
        }
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

    if (!form.shop_name) {
        form.setError('shop_name', 'The shop name field is required.');
        isValid = false;
    }

    if (!form.status) {
        form.setError('status', 'The status field is required.');
        isValid = false;
    }

    return isValid;
};

const submit = () => {
    if (!validateForm()) {
        return;
    }

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

const deleteSelected = () => {
    if (selectedCustomers.value.length === 0) return;

    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete ${selectedCustomers.value.length} selected customers?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete them!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('customers.bulk-destroy'), {
                ids: selectedCustomers.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedCustomers.value = [];
                    selectAll.value = false;
                    Swal.fire(
                        'Deleted!',
                        'Selected customers have been deleted.',
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

    // Special handling for Channel - needs ATL and Advance Tax fields
    if (type === 'channel') {
        const { value: formValues } = await Swal.fire({
            title: 'Add New Channel',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Channel Name</label>
                        <input id="swal-channel-name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Enter channel name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ATL</label>
                        <select id="swal-channel-atl" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Advance Tax (%)</label>
                        <input id="swal-channel-adv-tax" type="number" step="0.01" min="0" max="100" value="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="0.00">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Add Channel',
            confirmButtonColor: '#059669',
            target: openDialog || 'body',
            customClass: {
                container: 'z-[9999]'
            },
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById('swal-channel-name').value;
                const atl = document.getElementById('swal-channel-atl').value;
                const advTax = document.getElementById('swal-channel-adv-tax').value;

                if (!name) {
                    Swal.showValidationMessage('Channel name is required');
                    return false;
                }

                return { name, atl, advTax };
            }
        });

        if (formValues) {
            router.post(route('customer-attributes.store'), {
                type: 'channel',
                value: formValues.name,
                atl: formValues.atl,
                adv_tax_percent: formValues.advTax || 0
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success',
                        text: 'Channel added successfully',
                        icon: 'success',
                        target: openDialog || 'body',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Find the newly added channel ID (matching name and ATL) and select it
                    const newChannel = props.attributes.channel.find(ch =>
                        ch.value === formValues.name &&
                        ch.atl === formValues.atl // Exact match
                    );

                    if (newChannel) {
                        selectedChannelId.value = newChannel.id;
                    } else {
                        // Fallback
                        form.channel = formValues.name;
                        form.atl = formValues.atl;
                        form.adv_tax_percent = formValues.advTax || 0;
                    }
                },
                onError: () => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to add channel',
                        icon: 'error',
                        target: openDialog || 'body'
                    });
                }
            });
        }
        return;
    }

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
        // Special handling for VAN module - now uses modal instead
        if (type === 'van') {
            // This branch is no longer used - Van uses VanFormModal instead
            return;
        }

        // Default logic for other attributes
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



const isImportModalOpen = ref(false);
const importFile = ref(null);
const importDistributionId = ref('');

const openImportModal = () => {
    isImportModalOpen.value = true;
    importFile.value = null;
    importDistributionId.value = '';
};

const closeImportModal = () => {
    isImportModalOpen.value = false;
    importFile.value = null;
    importDistributionId.value = '';
};

const handleImportFileChange = (event) => {
    importFile.value = event.target.files[0];
};

const submitImport = () => {
    if (!importFile.value) {
        Swal.fire('Error', 'Please select a file to import', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('file', importFile.value);
    if (importDistributionId.value) {
        formData.append('distribution_id', importDistributionId.value);
    }

    router.post(route('customers.import'), formData, {
        onSuccess: () => {
            closeImportModal();
            Swal.fire({
                title: 'Success',
                text: 'Customers imported successfully',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        },
        onError: () => {
            closeImportModal(); // Close modal on error too? Or keep open? User preference usually keep open. But keeping simple.
            Swal.fire({
                title: 'Error',
                text: 'Failed to import customers',
                icon: 'error'
            });
        }
    });
};
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
                        <input v-model="search" type="text" placeholder="Search customers..."
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 w-64 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select v-model="filterStatus"
                        class="py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-white shadow-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button @click="openImportModal"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-5 h-5 mr-2 -ml-1 text-green-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Import Excel
                    </button>

                    <button @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Customer
                    </button>
                    
                    <button v-if="selectedCustomers.length > 0" @click="deleteSelected"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl font-medium shadow-lg shadow-red-500/30 hover:bg-red-700 hover:shadow-xl hover:shadow-red-500/40 transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Selected ({{ selectedCustomers.length }})
                    </button>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4 w-4">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                        class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                                </th>
                                <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                                <th @click="handleSort('customer_code')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Code {{ getSortIcon('customer_code') }}
                                </th>
                                <th @click="handleSort('shop_name')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Shop Name {{ getSortIcon('shop_name') }}
                                </th>
                                <th @click="handleSort('van')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    VAN {{ getSortIcon('van') }}
                                </th>
                                <th @click="handleSort('channel')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Channel {{ getSortIcon('channel') }}
                                </th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Sub Distribution</th>
                                <th class="px-6 py-4">Day</th>
                                <th class="px-6 py-4">NTN/STRN</th>
                                <th class="px-6 py-4">CNIC</th>
                                <th class="px-6 py-4">Percentage</th>
                                <th @click="handleSort('phone')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Telephone {{ getSortIcon('phone') }}
                                </th>
                                <th @click="handleSort('status')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Status {{ getSortIcon('status') }}
                                </th>
                                <th @click="handleSort('sales_tax_status')"
                                    class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    ST Status {{ getSortIcon('sales_tax_status') }}
                                </th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="customer in customers.data" :key="customer.id"
                                class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <input type="checkbox" :value="customer.id" v-model="selectedCustomers"
                                        class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                                </td>
                                <td v-if="!currentDistribution?.id" class="px-6 py-4">
                                    <span v-if="customer.distribution"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ customer.distribution.name }}
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Global
                                    </span>
                                </td>
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
                                    {{ customer.channel || '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ customer.category || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="customer.sub_distribution"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                        {{ customer.sub_distribution }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ customer.day || '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    <div v-if="customer.ntn_number">NTN: {{ customer.ntn_number }}</div>
                                    <div v-if="customer.sales_tax_number">STRN: {{ customer.sales_tax_number }}</div>
                                    <div v-if="!customer.ntn_number && !customer.sales_tax_number">-</div>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ customer.cnic || '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ customer.percentage || '0.00' }}%
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
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        customer.sales_tax_status === 'active' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'
                                    ]">
                                        {{ customer.sales_tax_status ? customer.sales_tax_status.toUpperCase() : 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openModal(customer)"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit Customer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteCustomer(customer)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete Customer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
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
                    <!-- Distribution Select (Only if Global View) -->
                    <div v-if="!currentDistribution?.id && !isEditing">
                        <SearchableSelect v-model="form.distribution_id" label="Distribution"
                            :options="getAttributes('distribution')" option-value="id" option-label="value"
                            placeholder="Select a distribution" :error="form.errors.distribution_id" required />
                        <p class="text-xs text-gray-500 mt-1">Select the distribution this customer belongs to.</p>
                    </div>

                    <!-- Row 1 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Customer Code" />
                            <TextInput v-model="form.customer_code" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.customer_code }"
                                placeholder="e.g. CPSSGD03739" />
                            <div v-if="form.errors.customer_code" class="text-xs text-red-600 mt-1">{{
                                form.errors.customer_code }}</div>
                        </div>
                        <div>
                            <InputLabel value="VAN" />
                            <div class="flex gap-2">
                                <select v-model="form.van"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select VAN</option>
                                    <option v-for="attr in filteredVanOptions" :key="attr.id" :value="attr.value">{{
                                        attr.value }}</option>
                                </select>
                                <button type="button" @click="isVanModalOpen = true"
                                    class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                    title="Add New VAN">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.van" class="text-xs text-red-600 mt-1">{{ form.errors.van }}</div>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Shop Name" />
                            <TextInput v-model="form.shop_name" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.shop_name }" />
                            <div v-if="form.errors.shop_name" class="text-xs text-red-600 mt-1">{{ form.errors.shop_name
                            }}</div>
                        </div>
                        <div>
                            <TextInput v-model="form.address" type="text" class="mt-1 block w-full" />
                            <div v-if="form.errors.address" class="text-xs text-red-600 mt-1">{{ form.errors.address }}</div>
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Sub Address" />
                            <div class="flex gap-2">
                                <select v-model="form.sub_address"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Sub Address</option>
                                    <option v-for="attr in filteredSubAddressOptions" :key="attr.id"
                                        :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="isSubAddressModalOpen = true"
                                    class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                    title="Add New Sub Address">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.sub_address" class="text-xs text-red-600 mt-1">{{ form.errors.sub_address }}</div>
                        </div>
                        <div>
                            <InputLabel value="Route" />
                            <div class="flex gap-2">
                                <select v-model="form.route"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Route</option>
                                    <option v-for="attr in filteredRouteOptions" :key="attr.id" :value="attr.value">{{
                                        attr.value }}</option>
                                </select>
                                <button type="button" @click="isRouteModalOpen = true"
                                    class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                    title="Add New Route">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.route" class="text-xs text-red-600 mt-1">{{ form.errors.route }}</div>
                        </div>
                        <div>
                            <InputLabel value="Telephone" />
                            <TextInput v-model="form.phone" type="text" class="mt-1 block w-full" />
                            <div v-if="form.errors.phone" class="text-xs text-red-600 mt-1">{{ form.errors.phone }}</div>
                        </div>
                    </div>

                    <!-- Row 4 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Categories" />
                            <div class="flex gap-2">
                                <select v-model="form.category"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Category</option>
                                    <option v-for="attr in filteredCategoryOptions" :key="attr.id" :value="attr.value">
                                        {{ attr.value }}</option>
                                </select>
                                <button type="button" @click="isCategoryModalOpen = true"
                                    class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                    title="Add New Category">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.category" class="text-xs text-red-600 mt-1">{{ form.errors.category }}</div>
                        </div>
                        <div>
                            <InputLabel value="Channel" />
                            <div class="flex gap-2">
                                <select v-model="selectedChannelId"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Channel</option>
                                    <option v-for="attr in channelOptions" :key="attr.id" :value="attr.id">
                                        {{ attr.value }} ({{ attr.atl ? 'ATL' : 'Non-ATL' }})
                                    </option>
                                </select>
                                <button type="button" @click="isChannelModalOpen = true"
                                    class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                    title="Add New Channel">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.channel" class="text-xs text-red-600 mt-1">{{ form.errors.channel }}</div>
                        </div>
                    </div>



                    <!-- Row 5b - Sub Distribution and Day (always visible) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Sub Distribution" />
                            <div class="flex gap-2">
                                <select v-model="form.sub_distribution"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Sub Distribution</option>
                                    <option v-for="attr in filteredSubDistributionOptions" :key="attr.id"
                                        :value="attr.value">{{ attr.value }}</option>
                                </select>
                                <button type="button" @click="isSubDistributionModalOpen = true"
                                    class="mt-1 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                    title="Add New Sub Distribution">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="form.errors.sub_distribution" class="text-xs text-red-600 mt-1">{{ form.errors.sub_distribution }}</div>
                        </div>
                        <div>
                            <InputLabel value="Day" />
                            <select v-model="form.day"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.day }">
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                            <div v-if="form.errors.day" class="text-xs text-red-600 mt-1">{{ form.errors.day }}</div>
                        </div>
                    </div>

                    <!-- Row 6 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="NTN Number" />
                            <TextInput v-model="form.ntn_number" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.ntn_number }" />
                            <div v-if="form.errors.ntn_number" class="text-xs text-red-600 mt-1">{{
                                form.errors.ntn_number }}</div>
                        </div>
                        <div>
                            <InputLabel value="CNIC" />
                            <TextInput v-model="form.cnic" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.cnic }" />
                            <div v-if="form.errors.cnic" class="text-xs text-red-600 mt-1">{{ form.errors.cnic }}</div>
                        </div>
                    </div>

                    <!-- Row 7 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Sale Tax Number" />
                            <TextInput v-model="form.sales_tax_number" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.sales_tax_number }" />
                            <div v-if="form.errors.sales_tax_number" class="text-xs text-red-600 mt-1">{{
                                form.errors.sales_tax_number }}</div>
                        </div>
                        <div>
                            <InputLabel value="Adv. Tax (%)" />
                            <TextInput v-model="form.adv_tax_percent" type="number" step="0.01"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.adv_tax_percent }" />
                            <div v-if="form.errors.adv_tax_percent" class="text-xs text-red-600 mt-1">{{
                                form.errors.adv_tax_percent }}</div>
                        </div>
                    </div>

                    <!-- Row 8 -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Percentage" />
                            <TextInput v-model="form.percentage" type="number" step="0.01" class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.percentage }" />
                            <div v-if="form.errors.percentage" class="text-xs text-red-600 mt-1">{{
                                form.errors.percentage }}</div>
                        </div>
                        <div>
                            <InputLabel value="ATL" />
                            <select v-model="form.atl"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div v-if="form.errors.atl" class="text-xs text-red-600 mt-1">{{ form.errors.atl }}</div>
                        </div>
                        <div>
                            <InputLabel value="Sales Tax Status" />
                            <select v-model="form.sales_tax_status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div v-if="form.errors.sales_tax_status" class="text-xs text-red-600 mt-1">{{ form.errors.sales_tax_status }}</div>
                        </div>
                        <div>
                            <InputLabel value="Status" />
                            <select v-model="form.status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div v-if="form.errors.status" class="text-xs text-red-600 mt-1">{{ form.errors.status }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t sticky bottom-0 bg-white">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing"
                            class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Customer' : 'Create Customer') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
    <!-- Import Modal -->
    <Modal :show="isImportModalOpen" @close="closeImportModal" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Import Customers</h2>

            <div class="space-y-4">
                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <span class="text-sm text-gray-600">Download sample format</span>
                    <a :href="route('customers.template')"
                        class="text-sm text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                        Download Format
                    </a>
                </div>

                <div>
                    <!-- Distribution Selection for Import (Global View) -->
                    <div v-if="!currentDistribution?.id" class="mb-4">
                        <SearchableSelect v-model="importDistributionId" label="Target Distribution (Optional)"
                            :options="getAttributes('distribution')" option-value="id" option-label="value"
                            placeholder="Select target distribution" />
                        <p class="text-xs text-gray-500 mt-1">Select a distribution to import all customers into. If
                            empty, uses 'Distribution' column from Excel.</p>
                    </div>

                    <InputLabel value="Select Excel File" class="mb-2" />
                    <input type="file" accept=".xlsx,.csv" @change="handleImportFileChange"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="closeImportModal">
                    Cancel
                </SecondaryButton>
                <PrimaryButton @click="submitImport" :disabled="!importFile">
                    Import Customers
                </PrimaryButton>
            </div>
        </div>
    </Modal>

    <!-- Van Form Modal (Shared Component) -->
    <VanFormModal :show="isVanModalOpen" @close="isVanModalOpen = false" @saved="(vanCode) => { form.van = vanCode; }"
        :distributions="getAttributes('distribution')" :parentDistributionId="form.distribution_id" />

    <!-- Channel Form Modal (Shared Component) -->
    <ChannelFormModal :show="isChannelModalOpen" @close="isChannelModalOpen = false"
        @saved="(channelName) => { form.channel = channelName; }" :distributions="getAttributes('distribution')"
        :parentDistributionId="form.distribution_id" />

    <!-- Sub Address Form Modal (Shared Component) -->
    <SubAddressFormModal :show="isSubAddressModalOpen" @close="isSubAddressModalOpen = false"
        @saved="(subAddressName) => { form.sub_address = subAddressName; }"
        :distributions="getAttributes('distribution')" :parentDistributionId="form.distribution_id" />

    <!-- Sub Distribution Form Modal (Shared Component) -->
    <SubDistributionFormModal :show="isSubDistributionModalOpen" @close="isSubDistributionModalOpen = false"
        @saved="(subDistName) => { form.sub_distribution = subDistName; }"
        :distributions="getAttributes('distribution')" :parentDistributionId="form.distribution_id" />

    <!-- Category Form Modal (Shared Component) -->
    <CategoryFormModal :show="isCategoryModalOpen" @close="isCategoryModalOpen = false"
        @saved="(categoryName) => { form.category = categoryName; }" :distributions="getAttributes('distribution')"
        :parentDistributionId="form.distribution_id" />

    <!-- Route Form Modal (Shared Component) -->
    <RouteFormModal :show="isRouteModalOpen" @close="isRouteModalOpen = false"
        @saved="(routeName) => { form.route = routeName; }" :distributions="getAttributes('distribution')"
        :parentDistributionId="form.distribution_id" />
</template>
