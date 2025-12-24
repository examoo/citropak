<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import Swal from 'sweetalert2';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    gins: Object,
    vans: Array,
    filters: Object
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const vanFilter = ref(props.filters.van_id || '');

// Format vans for dropdown
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution 
            ? `${van.code} (${van.distribution.name})` 
            : van.code
    }));
});

// Format amount with commas
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', { 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(amount || 0);
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Status badge classes
const getStatusClass = (status) => {
    switch(status) {
        case 'draft': return 'bg-yellow-100 text-yellow-800';
        case 'issued': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Search watcher
watch(search, debounce((value) => {
    applyFilters();
}, 300));

watch([statusFilter, vanFilter], () => {
    applyFilters();
});

const applyFilters = () => {
    router.get(route('good-issue-notes.index'), {
        search: search.value,
        status: statusFilter.value,
        van_id: vanFilter.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    vanFilter.value = '';
    router.get(route('good-issue-notes.index'));
};

const deleteGin = (gin) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete GIN "${gin.gin_number}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('good-issue-notes.destroy', gin.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'GIN has been deleted.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Good Issue Notes" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Good Issue Notes</h1>
                    <p class="text-gray-500 mt-1">Manage goods issued from warehouse to VANs.</p>
                </div>
                
                <Link 
                    :href="route('good-issue-notes.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create GIN
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-wrap gap-4 items-end">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Search by GIN number..." 
                                class="pl-10 pr-4 py-2.5 w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-sm"
                            >
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="statusFilter" class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="issued">Issued</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- VAN Filter -->
                    <div class="w-48">
                        <SearchableSelect 
                            v-model="vanFilter"
                            label="VAN"
                            :options="vanOptions"
                            option-value="id"
                            option-label="displayLabel"
                            placeholder="All VANs"
                        />
                    </div>

                    <!-- Clear Filters -->
                    <button 
                        v-if="search || statusFilter || vanFilter"
                        @click="clearFilters"
                        class="p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                        title="Clear filters"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-6 py-4">GIN Number</th>
                                <th class="px-6 py-4">VAN</th>
                                <th class="px-6 py-4">Issue Date</th>
                                <th class="px-6 py-4">Items</th>
                                <th class="px-6 py-4 text-right">Total</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="gin in gins.data" :key="gin.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <Link :href="route('good-issue-notes.show', gin.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ gin.gin_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 font-medium">{{ gin.van?.code }}</td>
                                <td class="px-6 py-4">{{ formatDate(gin.issue_date) }}</td>
                                <td class="px-6 py-4">{{ gin.items?.length || 0 }} items</td>
                                <td class="px-6 py-4 text-right font-semibold text-emerald-600">
                                    Rs. {{ formatAmount(gin.items?.reduce((sum, i) => sum + parseFloat(i.total_price || 0), 0)) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                        :class="getStatusClass(gin.status)"
                                    >
                                        {{ gin.status }}
                                    </span>
                                </td>
                                <td v-if="!currentDistribution?.id" class="px-6 py-4 text-gray-500">
                                    {{ gin.distribution?.name || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link 
                                            :href="route('good-issue-notes.show', gin.id)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="View"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <button 
                                            v-if="gin.status === 'draft'"
                                            @click="deleteGin(gin)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="gins.data.length === 0">
                                <td :colspan="!currentDistribution?.id ? 8 : 7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>No Good Issue Notes found.</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="gins.links" />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
