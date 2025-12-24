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
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    stockOuts: Object,
    filters: Object,
    availableStocks: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] }, // Restored
    distributions: { type: Array, default: () => [] },
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    distribution_id: '',
    bilty_number: '',
    date: new Date().toISOString().split('T')[0],
    remarks: '',
    items: [],
});

// Item form for adding products
const newItem = ref({
    stock_id: '', // Added stock_id
    product_id: '',
    cartons: 0,
    pieces: 0,
    pieces_per_carton: 1,
    quantity: 0,
    batch_number: '',
    expiry_date: '',
    location: '',
    unit_cost: 0,
    // Pricing fields (optional for stock out display, but good to have)
    list_price_before_tax: 0, fed_tax_percent: 0, fed_sales_tax: 0, net_list_price: 0,
    distribution_margin: 0, trade_price_before_tax: 0, fed_2: 0, sales_tax_3: 0,
    net_trade_price: 0, retailer_margin: 0, consumer_price_before_tax: 0, fed_5: 0,
    sales_tax_6: 0, net_consumer_price: 0, unit_price: 0, total_margin: 0,
});

// Calculate total quantity from cartons and pieces
// Calculate total quantity from cartons and pieces
const calculateQuantity = () => {
    const cartons = Number(newItem.value.cartons) || 0;
    const pieces = Number(newItem.value.pieces) || 0;
    const packing = Number(newItem.value.pieces_per_carton) || 1;
    
    // Safety check ensuring we don't display NaN
    newItem.value.quantity = (cartons * packing) + pieces;
};

watch([() => newItem.value.cartons, () => newItem.value.pieces, () => newItem.value.pieces_per_carton], calculateQuantity);

// Get available batches/stocks for the selected product
const availableBatches = computed(() => {
    if (!newItem.value.product_id) return [];
    
    let stocks = props.availableStocks.filter(s => Number(s.product_id) === Number(newItem.value.product_id));

    // Filter by selected distribution to prevent cross-distribution stock usage
    const distId = form.distribution_id || currentDistribution.value?.id;
    if (distId) {
        stocks = stocks.filter(s => Number(s.distribution_id) === Number(distId));
    }

    return stocks.map(s => ({
            ...s,
            // Format label to show Batch, Location, and Qty
            label: `${s.batch_number ? 'Batch: ' + s.batch_number : 'No Batch'} ${s.location ? '(' + s.location + ')' : ''} - Qty: ${s.quantity}`,
        }));
});

// Auto-fill details when Stock (Batch) is selected
watch(() => newItem.value.stock_id, (newId) => {
    if (newId) {
        const stock = props.availableStocks.find(s => Number(s.id) === Number(newId));
        if (stock) {
            // Auto-fill quantity (User requested)
            newItem.value.quantity = stock.quantity;
            
            newItem.value.pieces_per_carton = stock.product.packing || 1;
            
            // Calculate cartons/pieces from total quantity
            if (newItem.value.pieces_per_carton > 0) {
                newItem.value.cartons = Math.floor(stock.quantity / newItem.value.pieces_per_carton);
                newItem.value.pieces = stock.quantity % newItem.value.pieces_per_carton;
            } else {
                newItem.value.cartons = 0;
                newItem.value.pieces = stock.quantity;
            }

            // Fill details
            newItem.value.batch_number = stock.batch_number || '';
            newItem.value.expiry_date = stock.expiry_date || '';
            newItem.value.location = stock.location || '';
            newItem.value.unit_cost = stock.unit_cost || stock.product.unit_price || 0;
            
            // Fill pricing
            const product = stock.product;
            newItem.value.list_price_before_tax = product.list_price_before_tax || 0;
            newItem.value.fed_tax_percent = product.fed_tax_percent || 0;
            newItem.value.fed_sales_tax = product.fed_sales_tax || 0;
            newItem.value.net_list_price = product.net_list_price || 0;
            newItem.value.distribution_margin = product.distribution_margin || 0;
            newItem.value.trade_price_before_tax = product.trade_price_before_tax || 0;
            newItem.value.fed_2 = product.fed_2 || 0;
            newItem.value.sales_tax_3 = product.sales_tax_3 || 0;
            newItem.value.net_trade_price = product.net_trade_price || 0;
            newItem.value.retailer_margin = product.retailer_margin || 0;
            newItem.value.consumer_price_before_tax = product.consumer_price_before_tax || 0;
            newItem.value.fed_5 = product.fed_5 || 0;
            newItem.value.sales_tax_6 = product.sales_tax_6 || 0;
            newItem.value.net_consumer_price = product.net_consumer_price || 0;
            newItem.value.unit_price = product.unit_price || 0;
            newItem.value.total_margin = product.total_margin || 0;
        }
    }
});

// Reset stock_id if product changes
watch(() => newItem.value.product_id, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        newItem.value.stock_id = '';
        // Clear dependent fields if needed, but they will update on stock selection
    }
});

watch(search, debounce((value) => {
    router.get(route('stock-outs.index'), { search: value }, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300));

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    if (item) {
        form.distribution_id = item.distribution_id;
        form.bilty_number = item.bilty_number;
        form.date = item.date?.split('T')[0] || item.date;
        form.remarks = item.remarks;
        form.items = item.items?.map(i => ({
            product_id: i.product_id,
            product_name: i.product?.name,
            quantity: i.quantity,
            batch_number: i.batch_number,
            expiry_date: i.expiry_date?.split('T')[0] || i.expiry_date,
            location: i.location,
            unit_cost: i.unit_cost,
            // Carry over pricing fields
            ...i
        })) || [];
    } else {
        form.reset();
        form.date = new Date().toISOString().split('T')[0];
        form.items = [];
        if (currentDistribution.value?.id) {
            form.distribution_id = currentDistribution.value.id;
        }
    }
    resetNewItem();
    isModalOpen.value = true;
};

const resetNewItem = () => {
    newItem.value = {
        stock_id: '',
        product_id: '',
        cartons: 0,
        pieces: 0,
        pieces_per_carton: 1,
        quantity: 0,
        batch_number: '',
        expiry_date: '',
        location: '',
        unit_cost: 0,
        list_price_before_tax: 0, fed_tax_percent: 0, fed_sales_tax: 0, net_list_price: 0,
        distribution_margin: 0, trade_price_before_tax: 0, fed_2: 0, sales_tax_3: 0,
        net_trade_price: 0, retailer_margin: 0, consumer_price_before_tax: 0, fed_5: 0,
        sales_tax_6: 0, net_consumer_price: 0, unit_price: 0, total_margin: 0,
    };
};

const addItem = () => {
    if (!newItem.value.stock_id || !newItem.value.quantity) {
        Swal.fire('Error', 'Select a stock and quantity', 'error');
        return;
    }
    const stock = props.availableStocks.find(s => Number(s.id) === Number(newItem.value.stock_id));
    
    // Validate quantity
    if (newItem.value.quantity > stock.quantity) {
         Swal.fire('Error', `Only ${stock.quantity} available in this stock`, 'error');
         return;
    }

    form.items.push({
        ...newItem.value,
        product_name: stock?.product?.name || '',
        available_qty: stock?.quantity || 0, // Store available qty
    });
    resetNewItem();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (form.items.length === 0) {
        Swal.fire('Error', 'Add at least one item', 'error');
        return;
    }
    if (isEditing.value) {
        form.put(route('stock-outs.update', editingId.value), { onSuccess: () => closeModal() });
    } else {
        form.post(route('stock-outs.store'), { onSuccess: () => closeModal() });
    }
};

const postStockOut = (item) => {
    Swal.fire({
        title: 'Post Stock Out?',
        text: 'This will deduct items from inventory. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', 
        confirmButtonText: 'Yes, Post it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('stock-outs.post', item.id), {}, {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Posted!', 'Stock deducted from inventory.', 'success')
            });
        }
    });
};

const deleteItem = (item) => {
    if (item.status === 'posted') {
        Swal.fire('Error', 'Cannot delete posted stock out', 'error');
        return;
    }
    Swal.fire({
        title: 'Delete Stock Out?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('stock-outs.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Deleted!', 'Stock Out deleted.', 'success')
            });
        }
    });
};

const totalItems = computed(() => form.items.reduce((sum, i) => sum + Number(i.quantity), 0));
const totalValue = computed(() => form.items.reduce((sum, i) => sum + (Number(i.quantity) * Number(i.unit_cost)), 0));

// Format date helper
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    // Just default date string for now or use the helper if we want consistency
    return dateStr; 
};
</script>

<template>
    <Head title="Stock Out" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Stock Out</h1>
                    <p class="text-gray-500 mt-1">Record outgoing stock (sales, damage, etc.)</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input v-model="search" type="text" placeholder="Search..." class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-rose-500 focus:ring-rose-500 w-64 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-xl font-medium shadow-lg shadow-rose-500/30 hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        New Stock Out
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Bilty #</th>
                            <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Items</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in stockOuts.data" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ item.bilty_number || `SO-${item.id}` }}</td>
                            <td v-if="!currentDistribution?.id" class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ item.distribution?.code }}</span>
                            </td>
                            <td class="px-6 py-4">{{ item.date }}</td>
                            <td class="px-6 py-4">{{ item.items?.length || 0 }} items</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', item.status === 'posted' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-700']">
                                    {{ item.status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button v-if="item.status === 'draft'" @click="postStockOut(item)" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg" title="Post">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button v-if="item.status === 'draft'" @click="openModal(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button v-if="item.status === 'draft'" @click="deleteItem(item)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="stockOuts.data.length === 0">
                            <td :colspan="!currentDistribution?.id ? 6 : 5" class="px-6 py-12 text-center text-gray-500">No stock out records found.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50"><Pagination :links="stockOuts.links" /></div>
            </div>
        </div>

        <!-- Stock Out Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="5xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">{{ isEditing ? 'Edit' : 'New' }} Stock Out</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Header Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Distribution" />
                            <SearchableSelect v-model="form.distribution_id" :options="distributions" option-value="id" option-label="name" placeholder="Select Distribution" class="mt-1" :disabled="currentDistribution?.id" />
                        </div>
                        <div>
                            <InputLabel value="Bilty Number" />
                            <TextInput v-model="form.bilty_number" type="text" class="mt-1 block w-full" placeholder="Enter bilty number" />
                        </div>
                        <div>
                            <InputLabel value="Date" />
                            <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Add Item Row -->
                    <div class="bg-rose-50 rounded-xl p-4 border border-rose-100">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Add Item</h4>
                        <div class="grid grid-cols-2 md:grid-cols-8 gap-3">
                            <div class="col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Product</label>
                                <SearchableSelect v-model="newItem.product_id" :options="products" option-value="id" option-label="name" placeholder="Select Product" />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Batch / Stock</label>
                                <SearchableSelect v-model="newItem.stock_id" :options="availableBatches" option-value="id" option-label="label" placeholder="Select Batch" :disabled="!newItem.product_id" />
                                <div v-if="newItem.product_id && availableBatches.length === 0" class="text-xs text-red-500 mt-1">
                                    No stock available
                                </div>
                            </div>
                            <!-- Adjusted remaining cols for spacing -->
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Cartons</label>
                                <TextInput v-model="newItem.cartons" type="number" min="0" class="block w-full" placeholder="0" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">× {{ newItem.pieces_per_carton }} pcs</label>
                                <TextInput v-model="newItem.pieces" type="number" min="0" class="block w-full" placeholder="0" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Total Qty</label>
                                <div class="px-3 py-2 bg-rose-100 text-rose-700 font-bold text-center rounded-md">{{ newItem.quantity }}</div>
                            </div>
                            <!-- Batch #, Unit Cost, Add Button moved below or adjusted. -->
                            <!-- Batch # is now selected via dropdown, but maybe user wants to see it? -->
                            <!-- Let's keep fields but make them readonly or auto-filled. -->
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                             <div>
                                <label class="text-xs text-gray-500 mb-1 block">Batch # (Auto)</label>
                                <TextInput v-model="newItem.batch_number" type="text" class="block w-full bg-gray-50" readonly placeholder="-" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Unit Cost (Auto)</label>
                                <TextInput v-model="newItem.unit_cost" type="number" step="0.01" class="block w-full bg-gray-50" readonly />
                            </div>
                             <div class="col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">&nbsp;</label>
                                <button type="button" @click="addItem" class="w-full px-4 py-2 bg-rose-600 text-white rounded-lg font-medium hover:bg-rose-700">+ Add</button>
                            </div>
                        </div>
                        
                        <!-- Simplified Pricing Preview for Stock Out -->
                        <div v-if="selectedNewProduct" class="mt-4 bg-white rounded-lg p-3 border text-xs text-gray-500 flex flex-wrap gap-4">
                           <span>Product: <strong>{{ selectedNewProduct.name }}</strong></span>
                           <span>DMS: <strong>{{ selectedNewProduct.dms_code }}</strong></span>
                           <span>Unit Price: <strong>{{ selectedNewProduct.unit_price }}</strong></span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="border rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Product</th>
                                    <th class="px-4 py-3">Cartons</th>
                                    <th class="px-4 py-3">Pieces</th>
                                    <th class="px-4 py-3">Avail Qty</th>
                                    <th class="px-4 py-3">Total Qty</th>
                                    <th class="px-4 py-3">Batch</th>
                                    <th class="px-4 py-3">Unit Cost</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td class="px-4 py-3">{{ item.product_name }}</td>
                                    <td class="px-4 py-3 text-center">{{ item.cartons }}<span class="text-gray-400 text-xs"> ×{{ item.pieces_per_carton }}</span></td>
                                    <td class="px-4 py-3 text-center">{{ item.pieces }}</td>
                                    <td class="px-4 py-3 text-center font-medium text-blue-600">{{ item.available_qty }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-rose-600">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-center font-mono text-xs">{{ item.batch_number || '-' }}</td>
                                    <td class="px-4 py-3 text-center">{{ item.unit_cost }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ (item.quantity * item.unit_cost).toFixed(2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No items added yet</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 font-semibold">
                                <tr>
                                    <td class="px-4 py-3">Total</td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3 text-center text-rose-600">{{ totalItems }}</td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3 text-center text-rose-600">{{ totalValue.toFixed(2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <InputLabel value="Remarks" />
                        <textarea v-model="form.remarks" rows="2" class="mt-1 block w-full border-gray-300 focus:border-rose-500 focus:ring-rose-500 rounded-md shadow-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-gradient-to-r from-rose-600 to-pink-600 border-0">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Save as Draft') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
