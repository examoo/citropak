<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed } from 'vue';

const props = defineProps({
    gin: Object
});

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
        month: 'long',
        day: 'numeric'
    });
};

// Status badge classes
const getStatusClass = (status) => {
    switch(status) {
        case 'draft': return 'bg-yellow-100 text-yellow-800 border-yellow-300';
        case 'issued': return 'bg-green-100 text-green-800 border-green-300';
        case 'cancelled': return 'bg-red-100 text-red-800 border-red-300';
        default: return 'bg-gray-100 text-gray-800 border-gray-300';
    }
};

// Calculate total
const totalAmount = computed(() => {
    return props.gin.items?.reduce((sum, item) => sum + parseFloat(item.total_price || 0), 0) || 0;
});

const issueGin = () => {
    Swal.fire({
        title: 'Issue GIN?',
        text: 'This will deduct stock from warehouse. This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Issue it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('good-issue-notes.issue', props.gin.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Issued!', 'GIN has been issued and stock deducted.', 'success');
                }
            });
        }
    });
};

const cancelGin = () => {
    Swal.fire({
        title: 'Cancel GIN?',
        text: 'This will mark the GIN as cancelled.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Cancel it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('good-issue-notes.cancel', props.gin.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Cancelled!', 'GIN has been cancelled.', 'success');
                }
            });
        }
    });
};

const printGin = () => {
    window.print();
};
</script>

<template>
    <Head :title="`GIN: ${gin.gin_number}`" />

    <DashboardLayout>
        <div class="space-y-6 max-w-5xl">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ gin.gin_number }}</h1>
                    <p class="text-gray-500 mt-1">Good Issue Note Details</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('good-issue-notes.index')"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ← Back to list
                    </Link>
                    
                    <button 
                        @click="printGin"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Print
                    </button>

                    <button 
                        v-if="gin.status === 'draft'"
                        @click="issueGin"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Issue GIN
                    </button>

                    <button 
                        v-if="gin.status === 'draft'"
                        @click="cancelGin"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Cancel
                    </button>
                </div>
            </div>

            <!-- GIN Document -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 print:shadow-none print:border-0 print:p-4">
                <!-- Document Header -->
                <div class="text-center mb-8 border-b pb-6">
                    <h1 class="text-3xl font-bold text-gray-900 uppercase">GOOD ISSUE NOTE</h1>
                    <h2 class="text-xl font-semibold text-emerald-600 mt-2">{{ gin.gin_number }}</h2>
                    <span 
                        class="inline-block mt-3 px-4 py-1 border rounded-full text-sm font-medium uppercase"
                        :class="getStatusClass(gin.status)"
                    >
                        {{ gin.status }}
                    </span>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div>
                        <div class="text-sm text-gray-500">Distribution</div>
                        <div class="font-semibold">{{ gin.distribution?.name || 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">VAN</div>
                        <div class="font-semibold">{{ gin.van?.code }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Issue Date</div>
                        <div class="font-semibold">{{ formatDate(gin.issue_date) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Issued By</div>
                        <div class="font-semibold">{{ gin.issued_by?.name || 'N/A' }}</div>
                    </div>
                </div>

                <div v-if="gin.notes" class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="text-sm text-gray-500 mb-1">Notes</div>
                    <div>{{ gin.notes }}</div>
                </div>

                <!-- Items Table -->
                <table class="w-full text-left text-sm border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-200 px-3 py-2">#</th>
                            <th class="border border-gray-200 px-3 py-2">Product</th>
                            <th class="border border-gray-200 px-3 py-2">Batch</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">Quantity</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">Unit Price</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in gin.items" :key="item.id">
                            <td class="border border-gray-200 px-3 py-2">{{ index + 1 }}</td>
                            <td class="border border-gray-200 px-3 py-2">
                                <div class="font-medium">{{ item.product?.name }}</div>
                                <div class="text-xs text-gray-500">{{ item.product?.dms_code || item.product?.sku }}</div>
                            </td>
                            <td class="border border-gray-200 px-3 py-2">{{ item.stock?.batch_number || '-' }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right font-medium">{{ item.quantity }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right">Rs. {{ formatAmount(item.unit_price) }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right font-semibold">Rs. {{ formatAmount(item.total_price) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="5" class="border border-gray-200 px-3 py-3 text-right">GRAND TOTAL:</td>
                            <td class="border border-gray-200 px-3 py-3 text-right text-lg text-emerald-600">Rs. {{ formatAmount(totalAmount) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Signatures (Print) -->
                <div class="grid grid-cols-3 gap-8 mt-12 pt-8 border-t">
                    <div class="text-center">
                        <div class="border-t border-gray-400 pt-2 mt-12">Prepared By</div>
                    </div>
                    <div class="text-center">
                        <div class="border-t border-gray-400 pt-2 mt-12">Checked By</div>
                    </div>
                    <div class="text-center">
                        <div class="border-t border-gray-400 pt-2 mt-12">Received By (VAN)</div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-xs text-gray-400 text-right print:mt-4">
                    Printed on: {{ new Date().toLocaleString() }}
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    :deep(aside), :deep(nav), :deep(header) {
        display: none !important;
    }
    
    :deep(main) {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table, th, td {
        border-color: #000 !important;
    }
}
</style>
