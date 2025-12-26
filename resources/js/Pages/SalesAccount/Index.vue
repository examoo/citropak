<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

import ActionModal from './ActionModal.vue';

const props = defineProps({
    invoices: Array,
    filters: Object,
    vans: Array,
});

const form = ref({
    van_id: props.filters.van_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

// Modal State
const showModal = ref(false);
const modalType = ref(''); // 'Credit' or 'Recovery'
const selectedInvoice = ref(null);

const openModal = (invoice, type) => {
    selectedInvoice.value = invoice;
    modalType.value = type;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedInvoice.value = null;
    modalType.value = '';
    // Refresh to update data
    router.reload({ only: ['invoices'] });
};

const search = () => {
    router.get(route('sales-accounts.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const reset = () => {
    form.value = {
        van_id: '',
        date_from: new Date().toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
    };
    search();
};

// Calculate Totals
const totals = {
    gross_sale: props.invoices.reduce((sum, inv) => sum + Number(inv.gross_sale), 0),
    scheme_discount: props.invoices.reduce((sum, inv) => sum + Number(inv.scheme_discount), 0),
    percentage_discount: props.invoices.reduce((sum, inv) => sum + Number(inv.percentage_discount), 0),
    net_monetary_sale: props.invoices.reduce((sum, inv) => sum + Number(inv.net_monetary_sale), 0),
    net_pieces: props.invoices.reduce((sum, inv) => sum + Number(inv.net_pieces), 0),
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PK', { style: 'decimal', minimumFractionDigits: 2 }).format(value);
};

const print = () => {
    window.print();
};
</script>

<template>

    <Head title="Sales Account" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header & Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 no-print">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <InputLabel value="Select Van" />
                        <SearchableSelect v-model="form.van_id" :options="vans" option-value="id" option-label="name"
                            placeholder="All Vans" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Date From" />
                        <input type="date" v-model="form.date_from"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <InputLabel value="Date To" />
                        <input type="date" v-model="form.date_to"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex gap-2 pb-0.5">
                        <PrimaryButton @click="search">Search</PrimaryButton>
                        <SecondaryButton @click="print">Print</SecondaryButton>
                    </div>
                </div>
            </div>

            <!-- Report Header (Print Only) -->
            <div class="hidden print:block mb-6">
                <h1 class="text-2xl font-bold text-center">Sales Account Summary</h1>
                <p class="text-center text-gray-600">
                    {{ form.date_from }} to {{ form.date_to }}
                    <span v-if="form.van_id"> | Van: {{vans.find(v => v.id == form.van_id)?.name}}</span>
                </p>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 bg-purple-600 text-white flex justify-between items-center" v-if="form.van_id">
                    <h2 class="font-bold">
                        Summary for VAN: {{vans.find(v => v.id == form.van_id)?.name}}
                        ({{ form.date_from }} to {{ form.date_to }})
                    </h2>
                    <button @click="print"
                        class="bg-white text-purple-600 px-3 py-1 rounded text-sm font-bold hover:bg-gray-100 no-print">
                        Print
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Invoice No</th>
                                <th class="px-4 py-3">Customer Code</th>
                                <th class="px-4 py-3">Customer Name</th>
                                <th class="px-4 py-3 text-right">Gross Sale</th>
                                <th class="px-4 py-3 text-right">Scheme Dist</th>
                                <th class="px-4 py-3 text-right">Pct Dist</th>
                                <th class="px-4 py-3 text-right">Net Sale</th>
                                <th class="px-4 py-3 text-center">Net Pieces</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ inv.invoice_number }}</td>
                                <td class="px-4 py-3">{{ inv.customer_code }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ inv.customer_name }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(inv.gross_sale) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(inv.scheme_discount) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(inv.percentage_discount) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">{{
                                    formatCurrency(inv.net_monetary_sale) }}</td>
                                <td class="px-4 py-3 text-center">{{ inv.net_pieces }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <!-- Sale Badge (Static) -->
                                        <span v-if="inv.status.is_sale && !inv.status.is_credit"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-600 text-white cursor-default">Sale</span>
                                        <span v-if="inv.status.is_sale && inv.status.is_credit"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white cursor-default">Credit</span>

                                        <!-- Credit Button -->
                                        <button @click="openModal(inv, 'Credit')"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors"
                                            :class="inv.status.is_credit ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
                                            Credit
                                        </button>

                                        <!-- Recovery Button -->
                                        <button @click="openModal(inv, 'Recovery')"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#0089BA] text-white hover:bg-[#007da8] transition-colors">
                                            Recovery
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="invoices.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">No invoices found for the
                                    selected criteria.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold text-gray-900">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right">Total:</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totals.gross_sale) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totals.scheme_discount) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totals.percentage_discount) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(totals.net_monetary_sale) }}</td>
                                <td class="px-4 py-3 text-center">{{ totals.net_pieces }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Action Modal -->
            <ActionModal :show="showModal" :type="modalType" :invoice="selectedInvoice" @close="closeModal" />
        </div>
    </DashboardLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
