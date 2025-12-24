<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import Swal from 'sweetalert2';
import Pagination from '@/Components/Pagination.vue';
import { ref, computed } from 'vue';
import { debounce } from 'lodash';
import { watch } from 'vue';

const props = defineProps({
    stocks: Object,
    filters: Object,
    products: { type: Array, default: () => [] },
    distributions: { type: Array, default: () => [] },
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters?.search || '');
const lowStockFilter = ref(props.filters?.low_stock || false);

const form = useForm({
    product_id: '',
    distribution_id: '',
    quantity: 0,
    min_quantity: 0,
    max_quantity: '',
    unit_cost: 0,
    batch_number: '',
    expiry_date: '',
    location: '',
    notes: '',
    // Pricing fields
    list_price_before_tax: 0,
    fed_tax_percent: 0,
    fed_sales_tax: 0,
    net_list_price: 0,
    distribution_margin: 0,
    trade_price_before_tax: 0,
    fed_2: 0,
    sales_tax_3: 0,
    net_trade_price: 0,
    retailer_margin: 0,
    consumer_price_before_tax: 0,
    fed_5: 0,
    sales_tax_6: 0,
    net_consumer_price: 0,
    unit_price: 0,
    total_margin: 0,
});

// Get selected product details
const selectedProduct = computed(() => {
    if (!form.product_id) return null;
    return props.products.find(p => Number(p.id) === Number(form.product_id));
});

// Auto-fill pricing from product when selected
watch(() => form.product_id, (newId) => {
    if (newId && !isEditing.value) {
        const product = props.products.find(p => Number(p.id) === Number(newId));
        if (product) {
            // Populate all pricing fields from product
            form.unit_cost = product.unit_price || product.net_trade_price || 0;
            form.list_price_before_tax = product.list_price_before_tax || 0;
            form.fed_tax_percent = product.fed_tax_percent || 0;
            form.fed_sales_tax = product.fed_sales_tax || 0;
            form.net_list_price = product.net_list_price || 0;
            form.distribution_margin = product.distribution_margin || 0;
            form.trade_price_before_tax = product.trade_price_before_tax || 0;
            form.fed_2 = product.fed_2 || 0;
            form.sales_tax_3 = product.sales_tax_3 || 0;
            form.net_trade_price = product.net_trade_price || 0;
            form.retailer_margin = product.retailer_margin || 0;
            form.consumer_price_before_tax = product.consumer_price_before_tax || 0;
            form.fed_5 = product.fed_5 || 0;
            form.sales_tax_6 = product.sales_tax_6 || 0;
            form.net_consumer_price = product.net_consumer_price || 0;
            form.unit_price = product.unit_price || 0;
            form.total_margin = product.total_margin || 0;
        }
    }
});

// Debounced search
watch([search, lowStockFilter], debounce(() => {
    router.get(route('stocks.index'), { 
        search: search.value, 
        low_stock: lowStockFilter.value ? 1 : '' 
    }, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300));

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    if (item) {
        form.product_id = item.product_id;
        form.distribution_id = item.distribution_id;
        form.quantity = item.quantity;
        form.min_quantity = item.min_quantity;
        form.max_quantity = item.max_quantity;
        form.unit_cost = item.unit_cost;
        form.batch_number = item.batch_number;
        form.expiry_date = item.expiry_date?.split('T')[0] || item.expiry_date;
        form.location = item.location;
        form.notes = item.notes;
    } else {
        form.reset();
        form.quantity = 0;
        form.min_quantity = 0;
        form.unit_cost = 0;
        // Pre-select distribution if in specific distribution view
        if (currentDistribution.value?.id) {
            form.distribution_id = currentDistribution.value.id;
        }
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('stocks.update', editingId.value), { onSuccess: () => closeModal() });
    } else {
        form.post(route('stocks.store'), { onSuccess: () => closeModal() });
    }
};

const deleteItem = (item) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete stock for "${item.product?.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('stocks.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Deleted!', 'Stock deleted.', 'success')
            });
        }
    });
};

// Quick adjust stock
const adjustStock = (item, type) => {
    Swal.fire({
        title: type === 'add' ? 'Add Stock' : 'Subtract Stock',
        input: 'number',
        inputLabel: 'Quantity',
        inputValue: 1,
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value || value < 1) return 'Enter a valid quantity';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('stocks.adjust', item.id), {
                quantity: parseInt(result.value),
                type: type
            }, { preserveScroll: true });
        }
    });
};
</script>

<template>
    <Head title="Stock Management" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Stocks</h1>
                    <p class="text-gray-500 mt-1">Manage product inventory by distribution</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input v-model="search" type="text" placeholder="Search products..." class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 w-64 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input v-model="lowStockFilter" type="checkbox" class="rounded text-red-600 focus:ring-red-500">
                        Low Stock Only
                    </label>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add Stock
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Product</th>
                            <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                            <th class="px-6 py-4">Batch #</th>
                            <th class="px-6 py-4">Quantity</th>
                            <th class="px-6 py-4">Min Qty</th>
                            <th class="px-6 py-4">Unit Cost</th>
                            <th class="px-6 py-4">Location</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in stocks.data" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ item.product?.name }}</div>
                                <div class="text-xs text-gray-500">{{ item.product?.dms_code }}</div>
                            </td>
                            <td v-if="!currentDistribution?.id" class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ item.distribution?.code }}</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">{{ item.batch_number || '-' }}</td>
                            <td class="px-6 py-4 font-semibold" :class="item.quantity <= item.min_quantity ? 'text-red-600' : 'text-gray-900'">
                                {{ item.quantity }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ item.min_quantity }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ item.unit_cost }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ item.location || '-' }}</td>
                            <td class="px-6 py-4">
                                <span v-if="item.quantity <= item.min_quantity" class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Low Stock</span>
                                <span v-else class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">In Stock</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="adjustStock(item, 'add')" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Add Stock">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    </button>
                                    <button @click="adjustStock(item, 'subtract')" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg" title="Subtract Stock">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                    </button>
                                    <button @click="openModal(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button @click="deleteItem(item)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="stocks.data.length === 0">
                            <td :colspan="!currentDistribution?.id ? 9 : 8" class="px-6 py-12 text-center text-gray-500">No stocks found.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50"><Pagination :links="stocks.links" /></div>
            </div>
        </div>

        <!-- Stock Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="2xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">{{ isEditing ? 'Edit' : 'Add' }} Stock</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Product" />
                            <SearchableSelect 
                                v-model="form.product_id"
                                :options="products"
                                option-value="id"
                                option-label="name"
                                placeholder="Select Product"
                                class="mt-1"
                                :error="form.errors.product_id"
                                :disabled="isEditing"
                            />
                        </div>
                        <div>
                            <InputLabel value="Distribution" />
                            <SearchableSelect 
                                v-model="form.distribution_id"
                                :options="distributions"
                                option-value="id"
                                option-label="name"
                                placeholder="Select Distribution"
                                class="mt-1"
                                :error="form.errors.distribution_id"
                                :disabled="isEditing || currentDistribution?.id"
                            />
                        </div>
                    </div>
                    
                    <!-- Product Pricing Info Panel -->
                    <div v-if="selectedProduct" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            Product Pricing Info
                        </h4>
                        <div class="grid grid-cols-3 md:grid-cols-6 gap-3 text-xs">
                            <!-- Row 1: Basic Info -->
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">DMS Code</div>
                                <div class="font-semibold text-gray-900">{{ selectedProduct.dms_code || '-' }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">List Price</div>
                                <div class="font-semibold">{{ selectedProduct.list_price_before_tax || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">FED Tax %</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.fed_tax_percent || 0 }}%</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">FED Sales Tax</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.fed_sales_tax || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Net List Price</div>
                                <div class="font-semibold">{{ selectedProduct.net_list_price || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Dist. Margin</div>
                                <div class="font-semibold">{{ selectedProduct.distribution_margin || 0 }}</div>
                            </div>
                            <!-- Row 2: Trade Info -->
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Trade Price</div>
                                <div class="font-semibold">{{ selectedProduct.trade_price_before_tax || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">FED 2</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.fed_2 || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Sales Tax 3</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.sales_tax_3 || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Net Trade</div>
                                <div class="font-semibold text-emerald-600">{{ selectedProduct.net_trade_price || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Retailer Margin</div>
                                <div class="font-semibold">{{ selectedProduct.retailer_margin || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Consumer Price</div>
                                <div class="font-semibold">{{ selectedProduct.consumer_price_before_tax || 0 }}</div>
                            </div>
                            <!-- Row 3: Consumer & Final -->
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">FED 5</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.fed_5 || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Sales Tax 6</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.sales_tax_6 || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Net Consumer</div>
                                <div class="font-semibold text-blue-600">{{ selectedProduct.net_consumer_price || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Unit Price</div>
                                <div class="font-semibold text-blue-600">{{ selectedProduct.unit_price || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Total Margin</div>
                                <div class="font-semibold text-amber-600">{{ selectedProduct.total_margin || 0 }}</div>
                            </div>
                            <div class="bg-white rounded-lg p-2 shadow-sm">
                                <div class="text-gray-500">Packing</div>
                                <div class="font-semibold">{{ selectedProduct.packing || '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Quantity" />
                            <TextInput v-model="form.quantity" type="number" class="mt-1 block w-full" min="0" />
                        </div>
                        <div>
                            <InputLabel value="Min Quantity" />
                            <TextInput v-model="form.min_quantity" type="number" class="mt-1 block w-full" min="0" />
                        </div>
                        <div>
                            <InputLabel value="Max Quantity" />
                            <TextInput v-model="form.max_quantity" type="number" class="mt-1 block w-full" min="0" />
                        </div>
                        <div>
                            <InputLabel value="Unit Cost" />
                            <TextInput v-model="form.unit_cost" type="number" step="0.01" class="mt-1 block w-full" min="0" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Batch Number" />
                            <TextInput v-model="form.batch_number" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Expiry Date" />
                            <TextInput v-model="form.expiry_date" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Location" />
                            <TextInput v-model="form.location" type="text" class="mt-1 block w-full" placeholder="Warehouse A, Shelf 1" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Notes" />
                        <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-gradient-to-r from-blue-600 to-indigo-600 border-0">{{ form.processing ? 'Saving...' : (isEditing ? 'Update Stock' : 'Add Stock') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
