<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import Swal from 'sweetalert2';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    invoices: Object,
    orderBookers: Array,
    filters: Object
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const bookerFilter = ref(props.filters.booker_id || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Format amount
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

// Type badge classes
const getTypeClass = (type) => {
    switch(type) {
        case 'sale': return 'bg-green-100 text-green-800';
        case 'damage': return 'bg-red-100 text-red-800';
        case 'shelf_rent': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Tax badge classes
const getTaxClass = (type) => {
    return type === 'food' ? 'bg-orange-100 text-orange-800' : 'bg-purple-100 text-purple-800';
};

// Search watcher
watch(search, debounce(() => applyFilters(), 300));
watch([typeFilter, bookerFilter, dateFrom, dateTo], () => applyFilters());

const applyFilters = () => {
    router.get(route('invoices.index'), {
        search: search.value,
        type: typeFilter.value,
        booker_id: bookerFilter.value,
        date_from: dateFrom.value,
        date_to: dateTo.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const clearFilters = () => {
    search.value = '';
    typeFilter.value = '';
    bookerFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(route('invoices.index'));
};

const deleteInvoice = (invoice) => {
    Swal.fire({
        title: 'Delete Invoice?',
        text: `Delete invoice "${invoice.invoice_number}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('invoices.destroy', invoice.id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Deleted!', 'Invoice deleted.', 'success')
            });
        }
    });
};
</script>

<template>
    <Head title="Invoices" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
                    <p class="text-gray-500 mt-1">Manage sales, damage, and shelf rent invoices.</p>
                </div>
                
                <Link 
                    :href="route('invoices.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Invoice
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input v-model="search" type="text" placeholder="Invoice number..." class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select v-model="typeFilter" class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All</option>
                            <option value="sale">Sale</option>
                            <option value="damage">Damage</option>
                            <option value="shelf_rent">Shelf Rent</option>
                        </select>
                    </div>

                    <div class="w-40">
                        <SearchableSelect 
                            v-model="bookerFilter"
                            label="Order Booker"
                            :options="orderBookers"
                            option-value="id"
                            option-label="name"
                            placeholder="All"
                        />
                    </div>

                    <div class="w-36">
                        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                        <input v-model="dateFrom" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="w-36">
                        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input v-model="dateTo" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <button v-if="search || typeFilter || bookerFilter || dateFrom || dateTo" @click="clearFilters" class="p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl" title="Clear">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-4">Invoice #</th>
                                <th class="px-4 py-4">Date</th>
                                <th class="px-4 py-4">Customer</th>
                                <th class="px-4 py-4">Order Booker</th>
                                <th class="px-4 py-4 text-center">Type</th>
                                <th class="px-4 py-4 text-center">Tax</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th v-if="!currentDistribution?.id" class="px-4 py-4">Distribution</th>
                                <th class="px-4 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-4">
                                    <Link :href="route('invoices.show', invoice.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-4">{{ formatDate(invoice.invoice_date) }}</td>
                                <td class="px-4 py-4">
                                    <div class="font-medium">{{ invoice.customer?.shop_name }}</div>
                                    <div class="text-xs text-gray-500">{{ invoice.customer?.customer_code }}</div>
                                </td>
                                <td class="px-4 py-4">{{ invoice.order_booker?.name }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="getTypeClass(invoice.invoice_type)">
                                        {{ invoice.invoice_type.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="getTaxClass(invoice.tax_type)">
                                        {{ invoice.tax_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-semibold text-emerald-600">
                                    Rs. {{ formatAmount(invoice.total_amount) }}
                                </td>
                                <td v-if="!currentDistribution?.id" class="px-4 py-4 text-gray-500">
                                    {{ invoice.distribution?.name }}
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('invoices.show', invoice.id)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </Link>
                                        <Link :href="route('invoices.edit', invoice.id)" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </Link>
                                        <button @click="deleteInvoice(invoice)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="invoices.data.length === 0">
                                <td :colspan="!currentDistribution?.id ? 9 : 8" class="px-4 py-12 text-center text-gray-500">
                                    No invoices found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="invoices.links" />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
