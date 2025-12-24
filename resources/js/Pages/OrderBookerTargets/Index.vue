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
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    targets: Object,
    orderBookers: Array,
    distributions: Array,
    filters: Object
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters.search || '');
// Default to current month if no filter provided
const getCurrentMonth = () => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
};
const monthFilter = ref(props.filters.month || getCurrentMonth());

const form = useForm({
    order_booker_id: '',
    month: '',
    target_amount: '',
    distribution_id: '',
});

// Format order bookers to show distribution name only if All Distributions is selected
const orderBookerOptions = computed(() => {
    return props.orderBookers.map(ob => ({
        ...ob,
        displayLabel: !currentDistribution.value?.id && ob.distribution 
            ? `${ob.name} (${ob.distribution.name})` 
            : `${ob.name} - ${ob.code}`
    }));
});

// Format amount with commas
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', { 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(amount);
};

// Format month for display (2025-01 -> January 2025)
const formatMonth = (month) => {
    if (!month) return '-';
    const [year, m] = month.split('-');
    const date = new Date(year, parseInt(m) - 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

// Search Watcher
watch(search, debounce((value) => {
    router.get(route('order-booker-targets.index'), { 
        search: value, 
        month: monthFilter.value 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

// Month Filter Watcher
watch(monthFilter, (value) => {
    router.get(route('order-booker-targets.index'), { 
        search: search.value, 
        month: value 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    
    if (item) {
        form.order_booker_id = item.order_booker_id;
        form.month = item.month;
        form.target_amount = item.target_amount;
        form.distribution_id = item.distribution_id;
    } else {
        form.reset();
        // Auto-select distribution if a specific distribution is selected
        if (currentDistribution.value && currentDistribution.value.id) {
            form.distribution_id = currentDistribution.value.id;
        } else {
            form.distribution_id = '';
        }
        // Default to current month
        const now = new Date();
        form.month = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
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

    if (!form.order_booker_id) {
        form.setError('order_booker_id', 'Please select an Order Booker.');
        isValid = false;
    }
    if (!form.month) {
        form.setError('month', 'Please select a month.');
        isValid = false;
    }
    if (!form.target_amount || form.target_amount <= 0) {
        form.setError('target_amount', 'Please enter a valid target amount.');
        isValid = false;
    }
    // Only validate distribution if the user is in "All Distributions" view
    if (!currentDistribution.value?.id && !form.distribution_id) {
        form.setError('distribution_id', 'Please select a distribution.');
        isValid = false;
    }

    return isValid;
};

const submit = () => {
    if (!validateForm()) return;

    if (isEditing.value) {
        form.put(route('order-booker-targets.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('order-booker-targets.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTarget = (item) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete target for "${item.order_booker?.name}" - ${formatMonth(item.month)}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('order-booker-targets.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Target has been deleted.', 'success');
                }
            });
        }
    })
};

const clearFilters = () => {
    search.value = '';
    monthFilter.value = '';
    router.get(route('order-booker-targets.index'));
};
</script>

<template>
    <Head title="Set Targets" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Set Targets</h1>
                    <p class="text-gray-500 mt-1">Manage monthly sales targets for Order Bookers.</p>
                </div>
                
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search booker..." 
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 w-48 shadow-sm"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Month Filter -->
                    <input 
                        v-model="monthFilter"
                        type="month" 
                        class="px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-sm"
                    >

                    <!-- Clear Filters -->
                    <button 
                        v-if="search || monthFilter"
                        @click="clearFilters"
                        class="p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                        title="Clear filters"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <button 
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Target
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">Order Booker</th>
                                <th class="px-6 py-4">Month</th>
                                <th class="px-6 py-4 text-right">Target Amount</th>
                                <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in targets.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ item.order_booker?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ item.order_booker?.code }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ formatMonth(item.month) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold text-emerald-600">Rs. {{ formatAmount(item.target_amount) }}</span>
                                </td>
                                <td v-if="!currentDistribution?.id" class="px-6 py-4 text-gray-500">
                                    {{ item.order_booker?.distribution?.name || 'N/A' }}
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
                                            @click="deleteTarget(item)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="targets.data.length === 0">
                                <td :colspan="!currentDistribution?.id ? 5 : 4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span>No targets found. Click "Add Target" to create one.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="targets.links" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                    {{ isEditing ? 'Edit' : 'Add New' }} Target
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

                    <!-- Order Booker Select -->
                    <div>
                        <SearchableSelect 
                            v-model="form.order_booker_id"
                            label="Order Booker"
                            :options="orderBookerOptions"
                            option-value="id"
                            option-label="displayLabel"
                            placeholder="Select an Order Booker"
                            :error="form.errors.order_booker_id"
                            required
                        />
                    </div>

                    <!-- Month Input -->
                    <div>
                        <InputLabel value="Target Month" />
                        <input 
                            v-model="form.month"
                            type="month" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.month }"
                        />
                        <div v-if="form.errors.month" class="text-xs text-red-600 mt-1">{{ form.errors.month }}</div>
                    </div>

                    <!-- Target Amount -->
                    <div>
                        <InputLabel value="Target Amount (Rs.)" />
                        <TextInput 
                            v-model="form.target_amount" 
                            type="number" 
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full" 
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.target_amount }"
                            placeholder="e.g., 50000.00"
                        />
                        <div v-if="form.errors.target_amount" class="text-xs text-red-600 mt-1">{{ form.errors.target_amount }}</div>
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
    </DashboardLayout>
</template>
