<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import Swal from 'sweetalert2';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import FormModal from './FormModal.vue';
import MoveModal from './MoveModal.vue';

const props = defineProps({
    chillers: Object,
    filters: Object,
    customers: Array,
    chillerTypes: Array,
    orderBookers: Array
});

const isModalOpen = ref(false);
const isMoveModalOpen = ref(false);
const editingItem = ref(null);
const movingItem = ref(null);
const moveAction = ref('move'); // 'move' or 'return'
const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('chillers.index'), { search: value }, {
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

const openMoveModal = (item, action = 'move') => {
    movingItem.value = item;
    moveAction.value = action;
    isMoveModalOpen.value = true;
};

const closeMoveModal = () => {
    isMoveModalOpen.value = false;
    movingItem.value = null;
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
            router.delete(route('chillers.destroy', item.id));
        }
    });
};

const getStatusBadgeClass = (status) => {
    return status === 'active' 
        ? 'bg-green-100 text-green-800' 
        : 'bg-red-100 text-red-800';
};
</script>

<template>
    <Head title="Chillers" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Chillers</h1>
                    <p class="text-gray-500 mt-1">Manage chillers and assign them to customers.</p>
                </div>
                <div class="flex gap-2">
                    <Link 
                        :href="route('chillers.report')" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        View Report
                    </Link>
                    <button 
                        @click="openModal()" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        Add New Chiller
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
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Booker</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in chillers.data" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-indigo-600">{{ item.chiller_code || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">{{ item.chiller_type?.name || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="item.customer" class="text-sm text-blue-600 font-medium">
                                        {{ item.customer.shop_name }} ({{ item.customer.customer_code }})
                                    </div>
                                    <div v-else class="text-sm text-gray-400 italic">Unassigned</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">{{ item.order_booker?.name || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="getStatusBadgeClass(item.status)"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <!-- Move Button -->
                                    <button 
                                        v-if="item.customer_id"
                                        @click="openMoveModal(item, 'move')" 
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Move to another customer"
                                    >
                                        Move
                                    </button>
                                    <!-- Assign Button (when not assigned) -->
                                    <button 
                                        v-else
                                        @click="openMoveModal(item, 'move')" 
                                        class="text-green-600 hover:text-green-900"
                                        title="Assign to customer"
                                    >
                                        Assign
                                    </button>
                                    <!-- Return Button -->
                                    <button 
                                        v-if="item.customer_id"
                                        @click="openMoveModal(item, 'return')" 
                                        class="text-orange-600 hover:text-orange-900"
                                        title="Return chiller"
                                    >
                                        Return
                                    </button>
                                    <button @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="chillers.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No chillers found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="chillers.links.length > 3" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <Pagination :links="chillers.links" />
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <FormModal 
            :show="isModalOpen" 
            :item="editingItem" 
            :customers="customers"
            :chillerTypes="chillerTypes"
            :orderBookers="orderBookers"
            @close="closeModal" 
        />

        <!-- Move/Return Modal -->
        <MoveModal
            :show="isMoveModalOpen"
            :item="movingItem"
            :action="moveAction"
            :customers="customers"
            :orderBookers="orderBookers"
            @close="closeMoveModal"
        />
    </DashboardLayout>
</template>
