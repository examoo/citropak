<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import FormModal from './FormModal.vue';

const props = defineProps({
    shelves: Object,
    filters: Object,
    customers: Array,
    orderBookers: Array
});

const isModalOpen = ref(false);
const editingItem = ref(null);
const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('shelves.index'), { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const openModal = (item = null) => {
    editingItem.value = item;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingItem.value = null;
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
            router.delete(route('shelves.destroy', item.id));
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK').format(value || 0);
};

const getStatusBadgeClass = (status) => {
    return status === 'active' 
        ? 'bg-green-100 text-green-800' 
        : 'bg-red-100 text-red-800';
};
</script>

<template>
    <Head title="Shelves" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Shelves</h1>
                    <p class="text-gray-500 mt-1">Manage shelf rentals and track monthly performance.</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('shelves.report')" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                    >
                        View Report
                    </Link>
                    <button 
                        @click="openModal()" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        Add New Shelf
                    </button>
                </div>
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
                            placeholder="Search by code, name or customer..." 
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rent/Month</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Months</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-4 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in shelves.data" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-mono font-medium text-indigo-600">{{ item.shelf_code || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ item.name }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div v-if="item.customer" class="text-sm text-blue-600 font-medium">
                                        {{ item.customer.shop_name }} ({{ item.customer.customer_code }})
                                    </div>
                                    <div v-else class="text-sm text-gray-400 italic">Unassigned</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-sm font-medium">Rs. {{ formatCurrency(item.rent_amount) }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <span class="text-sm">{{ item.contract_months || '-' }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="text-sm font-semibold text-green-600">
                                        Rs. {{ formatCurrency((item.rent_amount || 0) * (item.contract_months || 0)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span 
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="getStatusBadgeClass(item.status)"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="shelves.data.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                    No shelves found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="shelves.links.length > 3" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <Pagination :links="shelves.links" />
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <FormModal 
            :show="isModalOpen" 
            :item="editingItem" 
            :customers="customers"
            :orderBookers="orderBookers"
            @close="closeModal" 
        />
    </DashboardLayout>
</template>
