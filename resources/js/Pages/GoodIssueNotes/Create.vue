<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch } from 'vue';

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

// Item form for adding new items
const newItem = ref({
    product_id: '',
    stock_id: '',
    quantity: 1,
    unit_price: 0
});

// Available stock for selected product in new item
const availableStock = computed(() => {
    if (!newItem.value.product_id) return [];
    return getStockForProduct(parseInt(newItem.value.product_id));
});

// Watch for product change to set default stock and price
watch(() => newItem.value.product_id, (productId) => {
    if (productId) {
        const stocks = getStockForProduct(parseInt(productId));
        if (stocks.length > 0) {
            newItem.value.stock_id = stocks[0].id;
            newItem.value.unit_price = stocks[0].unit_price || stocks[0].net_trade_price || 0;
        } else {
            const product = props.products.find(p => p.id === parseInt(productId));
            newItem.value.unit_price = product?.net_trade_price || product?.price || 0;
        }
    }
});

// Watch for stock change to update price
watch(() => newItem.value.stock_id, (stockId) => {
    if (stockId) {
        const stock = props.stocks.find(s => s.id === parseInt(stockId));
        if (stock) {
            newItem.value.unit_price = stock.unit_price || stock.net_trade_price || 0;
        }
    }
});

const addItem = () => {
    if (!newItem.value.product_id || !newItem.value.quantity) return;
    
    const product = props.products.find(p => p.id === parseInt(newItem.value.product_id));
    const stock = props.stocks.find(s => s.id === parseInt(newItem.value.stock_id));
    
    form.items.push({
        product_id: parseInt(newItem.value.product_id),
        product_name: product?.name,
        product_code: product?.dms_code || product?.sku,
        stock_id: newItem.value.stock_id ? parseInt(newItem.value.stock_id) : null,
        batch_number: stock?.batch_number,
        available_qty: stock?.quantity || 0,
        quantity: parseInt(newItem.value.quantity),
        unit_price: parseFloat(newItem.value.unit_price),
        total_price: parseInt(newItem.value.quantity) * parseFloat(newItem.value.unit_price)
    });
    
    // Reset
    newItem.value = {
        product_id: '',
        stock_id: '',
        quantity: 1,
        unit_price: 0
    };
};

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
                <Link 
                    :href="route('good-issue-notes.index')"
                    class="text-gray-500 hover:text-gray-700"
                >
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
                            <SearchableSelect 
                                v-model="form.distribution_id"
                                label="Distribution"
                                :options="distributions"
                                option-value="id"
                                option-label="name"
                                placeholder="Select distribution"
                                :error="form.errors.distribution_id"
                                required
                            />
                        </div>

                        <!-- VAN -->
                        <div>
                            <SearchableSelect 
                                v-model="form.van_id"
                                label="VAN"
                                :options="vanOptions"
                                option-value="id"
                                option-label="displayLabel"
                                placeholder="Select VAN"
                                :error="form.errors.van_id"
                                required
                            />
                        </div>

                        <!-- Issue Date -->
                        <div>
                            <InputLabel value="Issue Date" />
                            <TextInput 
                                v-model="form.issue_date" 
                                type="date" 
                                class="mt-1 block w-full" 
                                required
                            />
                            <div v-if="form.errors.issue_date" class="text-xs text-red-600 mt-1">{{ form.errors.issue_date }}</div>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-3">
                            <InputLabel value="Notes (Optional)" />
                            <textarea 
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Any additional notes..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Add Items -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Items</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                        <!-- Product -->
                        <div class="md:col-span-2">
                            <SearchableSelect 
                                v-model="newItem.product_id"
                                label="Product"
                                :options="productOptions"
                                option-value="id"
                                option-label="displayLabel"
                                placeholder="Search product..."
                            />
                        </div>

                        <!-- Stock/Batch -->
                        <div>
                            <InputLabel value="Stock Batch" />
                            <select 
                                v-model="newItem.stock_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">No batch</option>
                                <option v-for="stock in availableStock" :key="stock.id" :value="stock.id">
                                    {{ stock.batch_number || 'N/A' }} (Qty: {{ stock.quantity }})
                                </option>
                            </select>
                        </div>

                        <!-- Quantity -->
                        <div>
                            <InputLabel value="Quantity" />
                            <TextInput 
                                v-model="newItem.quantity" 
                                type="number" 
                                min="1"
                                class="mt-1 block w-full" 
                            />
                        </div>

                        <!-- Unit Price -->
                        <div>
                            <InputLabel value="Unit Price" />
                            <TextInput 
                                v-model="newItem.unit_price" 
                                type="number" 
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full" 
                            />
                        </div>

                        <!-- Add Button -->
                        <div>
                            <button 
                                type="button"
                                @click="addItem"
                                :disabled="!newItem.product_id"
                                class="w-full px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                            >
                                + Add
                            </button>
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
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td class="px-4 py-3">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ item.product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ item.batch_number || '-' }}</td>
                                    <td class="px-4 py-3 text-right">{{ item.available_qty }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right">Rs. {{ formatAmount(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600">Rs. {{ formatAmount(item.total_price) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button 
                                            type="button"
                                            @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-800"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
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
                                    <td class="px-4 py-3 text-right text-lg text-emerald-600">Rs. {{ formatAmount(grandTotal) }}</td>
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
                    <PrimaryButton 
                        :disabled="form.processing || form.items.length === 0"
                        class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0"
                    >
                        {{ form.processing ? 'Creating...' : 'Create GIN' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
