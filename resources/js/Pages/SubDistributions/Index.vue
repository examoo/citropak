<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Pagination from '@/Components/Pagination.vue';
import SubDistributionFormModal from '@/Components/SubDistributionFormModal.vue';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    subDistributions: Object,
    filters: Object,
    distributions: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const editingItem = ref(null);
const search = ref(props.filters.search || '');

// Search Watcher
watch(search, debounce((value) => {
    router.get(route('sub-distributions.index'), { search: value }, {
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
        text: `Delete sub distribution "${item.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('sub-distributions.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Sub Distribution has been deleted.', 'success');
                }
            });
        }
    })
};
</script>

<template>
    <Head title="Manage Sub Distributions" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manage Sub Distributions</h1>
                    <p class="text-gray-500 mt-1">Add, edit, or remove sub distributions.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search..." 
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-cyan-500 focus:ring-cyan-500 w-64 shadow-sm"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <button 
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl font-medium shadow-lg shadow-cyan-500/30 hover:shadow-xl hover:shadow-cyan-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Sub Distribution
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                                <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">FBR Type</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in subDistributions.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td v-if="!currentDistribution?.id" class="px-6 py-4">
                                    <span v-if="item.distribution" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ item.distribution.code }}
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Global
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ item.name }}</td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        item.is_fbr ? 'bg-cyan-100 text-cyan-700' : 'bg-gray-100 text-gray-700'
                                    ]">
                                        {{ item.is_fbr ? 'FBR' : 'Non-FBR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        item.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'
                                    ]">
                                        {{ item.status.toUpperCase() }}
                                    </span>
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
                                            @click="deleteItem(item)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="subDistributions.data.length === 0">
                                <td :colspan="!currentDistribution?.id ? 5 : 4" class="px-6 py-12 text-center text-gray-500">No Sub Distributions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="subDistributions.links" />
                </div>
            </div>
        </div>

        <!-- Shared Sub Distribution Form Modal -->
        <SubDistributionFormModal 
            :show="isModalOpen"
            :subDistribution="editingItem"
            :distributions="distributions"
            @close="closeModal"
            @saved="closeModal"
        />
    </DashboardLayout>
</template>
