<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    vans: Array,
    products: Array,
    stocks: Array,
    distributions: Array
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

// Format vans for dropdown
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution
            ? `${van.code} (${van.distribution.name})`
            : van.code
    }));
});

// Format products for dropdown
const productOptions = computed(() => {
    return props.products.map(p => ({
        ...p,
        displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}`
    }));
});

// Get available stock for a product
const getStockForProduct = (productId) => {
    return props.stocks.filter(s => s.product_id === productId);
};

const form = useForm({
    van_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    notes: '',
    distribution_id: currentDistribution.value?.id || '',
    items: []
});



const isFetching = ref(false);

const fetchPendingItems = async () => {
    if (!form.van_id || !form.issue_date) return;

    isFetching.value = true;
    try {
        const response = await axios.get(route('good-issue-notes.pending-items'), {
            params: {
                van_id: form.van_id,
                date: form.issue_date
            }
        });

        // Clear existing items
        form.items = [];

        // Populate items
        const pendingItems = response.data;
        pendingItems.forEach(item => {
            const product = props.products.find(p => p.id === parseInt(item.product_id));
            if (!product) return;

            const isFreeItem = item.is_free === 1 || item.is_free === true;
            let remainingQty = parseInt(item.total_qty);
            const stocks = getStockForProduct(product.id);
            
            // Get unit price from invoice items (API provides avg)
            const invoiceUnitPrice = parseFloat(item.avg_unit_price) || 0;

            // Logic to auto-select batches
            for (const stock of stocks) {
                if (remainingQty <= 0) break;

                const takeQty = Math.min(remainingQty, stock.quantity);
                const unitPrice = isFreeItem ? 0 : invoiceUnitPrice;

                form.items.push({
                    product_id: product.id,
                    product_name: isFreeItem ? product.name + ' (FREE)' : product.name,
                    product_code: product.dms_code || product.sku,
                    stock_id: stock.id,
                    batch_number: stock.batch_number,
                    available_qty: stock.quantity,
                    quantity: takeQty,
                    unit_price: unitPrice,
                    total_price: takeQty * unitPrice,
                    is_free: isFreeItem
                });

                remainingQty -= takeQty;
            }

            // If demand exceeds stock, add a line with no stock (user can handle)
            if (remainingQty > 0) {
                const unitPrice = isFreeItem ? 0 : invoiceUnitPrice;
                form.items.push({
                    product_id: product.id,
                    product_name: isFreeItem ? product.name + ' (FREE)' : product.name,
                    product_code: product.dms_code || product.sku,
                    stock_id: null,
                    batch_number: 'N/A',
                    available_qty: 0,
                    quantity: remainingQty,
                    unit_price: unitPrice,
                    total_price: remainingQty * unitPrice,
                    is_free: isFreeItem
                });
            }
        });

    } catch (e) {
        console.error("Failed to fetch pending items", e);
    } finally {
        isFetching.value = false;
    }
};

// Watch for VAN and DATE changes to auto-fetch
watch([() => form.van_id, () => form.issue_date], () => {
    fetchPendingItems();
});

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Format amount
const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0);
};

// Calculate grand total
const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.total_price || 0), 0);
});

const submit = () => {
    form.post(route('good-issue-notes.store'));
};
</script>

<template>

    <Head title="Create Good Issue Note" />

    <DashboardLayout>
        <div class="space-y-6 max-w-6xl">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Good Issue Note</h1>
                    <p class="text-gray-500 mt-1">Issue goods from warehouse to a VAN.</p>
                </div>
                <Link :href="route('good-issue-notes.index')" class="text-gray-500 hover:text-gray-700">
                    ← Back to list
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Header Info -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">GIN Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Distribution (only when "All") -->
                        <div v-if="!currentDistribution?.id">
                            <SearchableSelect v-model="form.distribution_id" label="Distribution"
                                :options="distributions" option-value="id" option-label="name"
                                placeholder="Select distribution" :error="form.errors.distribution_id" required />
                        </div>

                        <!-- VAN -->
                        <div>
                            <SearchableSelect v-model="form.van_id" label="VAN" :options="vanOptions" option-value="id"
                                option-label="displayLabel" placeholder="Select VAN" :error="form.errors.van_id"
                                required />
                        </div>

                        <!-- Issue Date -->
                        <div>
                            <InputLabel value="Issue Date" />
                            <TextInput v-model="form.issue_date" type="date" class="mt-1 block w-full" required />
                            <div v-if="form.errors.issue_date" class="text-xs text-red-600 mt-1">{{
                                form.errors.issue_date }}</div>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-3">
                            <InputLabel value="Notes (Optional)" />
                            <textarea v-model="form.notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Any additional notes..."></textarea>
                        </div>
                    </div>
                </div>



                <!-- Items Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="text-lg font-medium text-gray-900">Items ({{ form.items.length }})</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Batch</th>
                                    <th class="px-4 py-3 text-right">Available</th>
                                    <th class="px-4 py-3 text-right">Quantity</th>
                                    <th class="px-4 py-3 text-right">Unit Price</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index" :class="{'bg-emerald-50/60': item.is_free}">
                                    <td class="px-4 py-3">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium" :class="{'text-emerald-700': item.is_free}">
                                            {{ item.product_name }}
                                            <span v-if="item.is_free" class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wide border border-emerald-200">
                                                FREE
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ item.batch_number || '-' }}</td>
                                    <td class="px-4 py-3 text-right">{{ item.available_qty }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span v-if="item.is_free" class="text-xs font-bold text-emerald-600">FREE</span>
                                        <span v-else>Rs. {{ formatAmount(item.unit_price) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">
                                        <span v-if="item.is_free">FREE</span>
                                        <span v-else>Rs. {{ formatAmount(item.total_price) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        No items added yet. Add products above.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 font-semibold">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-right">Grand Total:</td>
                                    <td class="px-4 py-3 text-right text-lg text-emerald-600">Rs. {{
                                        formatAmount(grandTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('good-issue-notes.index')">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing || form.items.length === 0"
                        class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0">
                        {{ form.processing ? 'Creating...' : 'Create GIN' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
