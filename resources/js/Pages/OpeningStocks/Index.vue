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

// Format date to "24 Dec 2024 03:15 PM" format
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const day = date.getDate();
    const month = date.toLocaleString('en-US', { month: 'short' });
    const year = date.getFullYear();
    const time = date.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    return `${day} ${month} ${year} ${time}`;
};

const props = defineProps({
    openingStocks: Object,
    filters: Object,
    availableStocks: { type: Array, default: () => [] },
    distributions: { type: Array, default: () => [] },
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const dateFilter = ref(props.filters?.date || '');

// NEW SIMPLIFIED FORM STRUCTURE
const form = useForm({
    stock_id: '',
    product_id: '',
    distribution_id: '',
    date: new Date().toISOString().split('T')[0],
    cartons: 0,
    pieces: 0,
    pieces_per_carton: 1,
    quantity: 0,
    batch_number: '',
    expiry_date: '',
    location: '',
    unit_cost: 0,
    notes: '',
    // New simplified pricing fields
    pieces_per_packing: 1,
    list_price_before_tax: 0,
    fed_sales_tax: 0,
    fed_percent: 0,
    retail_margin: 0,
    tp_rate: 0,
    distribution_margin: 0,
    invoice_price: 0,
    unit_price: 0,
});

// Get selected stock
const selectedStock = computed(() => {
    if (!form.stock_id) return null;
    return props.availableStocks.find(s => Number(s.id) === Number(form.stock_id));
});

// Get selected product from selected stock
const selectedProduct = computed(() => {
    return selectedStock.value?.product || null;
});

// Calculate total quantity from cartons and pieces
const calculateQuantity = () => {
    const cartonQty = Number(form.cartons) * Number(form.pieces_per_carton);
    form.quantity = cartonQty + Number(form.pieces);
};

// Watch cartons and pieces for auto-calculation
watch([() => form.cartons, () => form.pieces, () => form.pieces_per_carton], calculateQuantity);

// Auto-fill from selected stock - NEW SIMPLIFIED STRUCTURE
watch(() => form.stock_id, (newId) => {
    if (newId && !isEditing.value) {
        const stock = props.availableStocks.find(s => Number(s.id) === Number(newId));
        if (stock) {
            const product = stock.product;
            form.product_id = stock.product_id;
            form.distribution_id = stock.distribution_id;
            form.pieces_per_carton = product?.pieces_per_packing || 1;
            // Calculate cartons and pieces from quantity
            const piecesPerCarton = product?.pieces_per_packing || 1;
            form.cartons = Math.floor(stock.quantity / piecesPerCarton);
            form.pieces = stock.quantity % piecesPerCarton;
            form.quantity = stock.quantity;
            form.batch_number = stock.batch_number || '';
            form.expiry_date = stock.expiry_date || '';
            form.location = stock.location || '';
            form.unit_cost = stock.unit_cost || product?.unit_price || 0;
            // New simplified pricing fields
            form.pieces_per_packing = product?.pieces_per_packing || 1;
            form.list_price_before_tax = product?.list_price_before_tax || 0;
            form.fed_sales_tax = product?.fed_sales_tax || 0;
            form.fed_percent = product?.fed_percent || 0;
            form.retail_margin = product?.retail_margin || 0;
            form.tp_rate = product?.tp_rate || 0;
            form.distribution_margin = product?.distribution_margin || 0;
            form.invoice_price = product?.invoice_price || 0;
            form.unit_price = product?.unit_price || 0;
        }
    }
});

const applyFilters = debounce(() => {
    router.get(route('opening-stocks.index'), { 
        search: search.value,
        status: statusFilter.value,
        date: dateFilter.value
    }, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300);

watch([search, statusFilter, dateFilter], applyFilters);

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    if (item) {
        form.product_id = item.product_id;
        form.distribution_id = item.distribution_id;
        form.date = item.date?.split('T')[0] || item.date;
        form.cartons = item.cartons;
        form.pieces = item.pieces;
        form.pieces_per_carton = item.pieces_per_carton;
        form.quantity = item.quantity;
        form.batch_number = item.batch_number;
        form.expiry_date = item.expiry_date?.split('T')[0] || '';
        form.location = item.location;
        form.unit_cost = item.unit_cost;
        form.notes = item.notes;
        // New simplified fields
        form.pieces_per_packing = item.pieces_per_packing || 1;
        form.list_price_before_tax = item.list_price_before_tax || 0;
        form.fed_sales_tax = item.fed_sales_tax || 0;
        form.fed_percent = item.fed_percent || 0;
        form.retail_margin = item.retail_margin || 0;
        form.tp_rate = item.tp_rate || 0;
        form.distribution_margin = item.distribution_margin || 0;
        form.invoice_price = item.invoice_price || 0;
        form.unit_price = item.unit_price || 0;
    } else {
        form.reset();
        form.date = new Date().toISOString().split('T')[0];
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
    if (!form.product_id || form.quantity <= 0) {
        Swal.fire('Error', 'Select a product and enter quantity', 'error');
        return;
    }
    if (isEditing.value) {
        form.put(route('opening-stocks.update', editingId.value), { onSuccess: () => closeModal() });
    } else {
        form.post(route('opening-stocks.store'), { onSuccess: () => closeModal() });
    }
};

const postStock = (item) => {
    Swal.fire({
        title: 'Post Opening Stock?',
        text: 'This will mark as posted. This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Yes, Post it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('opening-stocks.post', item.id), {}, {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Posted!', 'Opening stock marked as posted.', 'success')
            });
        }
    });
};

const deleteItem = (item) => {
    if (item.status === 'posted') {
        Swal.fire('Error', 'Cannot delete posted opening stock', 'error');
        return;
    }
    Swal.fire({
        title: 'Delete Opening Stock?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('opening-stocks.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Deleted!', 'Opening stock deleted.', 'success')
            });
        }
    });
};

const convertFromStocks = () => {
    Swal.fire({
        title: 'Convert Available Stocks?',
        text: 'This will create draft opening stocks from all available stocks. You can review and post them individually.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        confirmButtonText: 'Yes, Convert!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('opening-stocks.convert'), {}, {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Converted!', 'Available stocks converted to opening stocks.', 'success')
            });
        }
    });
};
</script>

<template>
    <Head title="Opening Stocks" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Opening Stocks</h1>
                    <p class="text-gray-500 mt-1">Record initial stock balances</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="relative">
                        <input v-model="search" type="text" placeholder="Search products..." class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500 w-52 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <select v-model="statusFilter" class="py-2.5 px-4 rounded-xl border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-sm">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                    </select>
                    <input v-model="dateFilter" type="date" class="py-2.5 px-4 rounded-xl border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500 shadow-sm" placeholder="Filter by date">
                    <button @click="convertFromStocks" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Convert Available Stocks
                    </button>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-medium shadow-lg shadow-purple-500/30 hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add Opening Stock
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Product</th>
                            <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                            <th class="px-6 py-4">Opening Date</th>
                            <th class="px-6 py-4">Opening Qty</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in openingStocks.data" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ item.product?.name }}</div>
                                <div class="text-xs text-gray-500">{{ item.product?.dms_code }}</div>
                            </td>
                            <td v-if="!currentDistribution?.id" class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ item.distribution?.code }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs">{{ formatDate(item.date) }}</td>
                            <td class="px-6 py-4 font-bold text-purple-600">{{ item.quantity }}</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', item.status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                    {{ item.status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button v-if="item.status === 'draft'" @click="postStock(item)" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Post">
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
                        <tr v-if="openingStocks.data.length === 0">
                            <td :colspan="!currentDistribution?.id ? 7 : 6" class="px-6 py-12 text-center text-gray-500">No opening stocks found.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50"><Pagination :links="openingStocks.links" /></div>
            </div>
        </div>

        <!-- Opening Stock Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="4xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">{{ isEditing ? 'Edit' : 'New' }} Opening Stock</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Stock Selection & Date -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="!isEditing">
                            <InputLabel value="Select Stock" />
                            <SearchableSelect v-model="form.stock_id" :options="availableStocks" option-value="id" option-label="name" placeholder="Select from available stocks..." class="mt-1" />
                            <p v-if="availableStocks.length === 0" class="text-xs text-amber-600 mt-1">No stocks available. All stocks already have opening stock entries.</p>
                        </div>
                        <div v-if="isEditing">
                            <InputLabel value="Product" />
                            <div class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-gray-700">{{ selectedProduct?.name || 'N/A' }}</div>
                        </div>
                        <div>
                            <InputLabel value="Opening Date" />
                            <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Quantity Section -->
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Quantity</h4>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Cartons</label>
                                <TextInput v-model="form.cartons" type="number" min="0" class="block w-full" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">× {{ form.pieces_per_carton }} pcs</label>
                                <TextInput v-model="form.pieces" type="number" min="0" class="block w-full" placeholder="Extra pieces" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Total Qty</label>
                                <div class="px-3 py-2 bg-purple-100 text-purple-700 font-bold text-center rounded-md text-lg">{{ form.quantity }}</div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Batch #</label>
                                <TextInput v-model="form.batch_number" type="text" class="block w-full" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Expiry Date</label>
                                <TextInput v-model="form.expiry_date" type="date" class="block w-full" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Unit Cost</label>
                                <TextInput v-model="form.unit_cost" type="number" step="0.01" class="block w-full" />
                            </div>
                        </div>
                    </div>

                    <!-- Product Pricing Preview - NEW SIMPLIFIED -->
                    <div v-if="selectedProduct" class="bg-white rounded-lg p-4 border">
                        <h5 class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wide">Product Pricing Info</h5>
                        <div class="grid grid-cols-3 md:grid-cols-5 gap-3 text-xs">
                            <div class="p-2 bg-gray-50 rounded">
                                <div class="text-gray-500">DMS Code</div>
                                <div class="font-semibold">{{ selectedProduct.dms_code || '-' }}</div>
                            </div>
                            <div class="p-2 bg-gray-50 rounded">
                                <div class="text-gray-500">List Price</div>
                                <div class="font-semibold">{{ selectedProduct.list_price_before_tax || 0 }}</div>
                            </div>
                            <div class="p-2 bg-orange-50 rounded">
                                <div class="text-gray-500">FED %</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.fed_percent || 0 }}%</div>
                            </div>
                            <div class="p-2 bg-orange-50 rounded">
                                <div class="text-gray-500">Sales Tax %</div>
                                <div class="font-semibold text-orange-600">{{ selectedProduct.fed_sales_tax || 0 }}%</div>
                            </div>
                            <div class="p-2 bg-gray-50 rounded">
                                <div class="text-gray-500">Retail Margin %</div>
                                <div class="font-semibold">{{ selectedProduct.retail_margin || 0 }}%</div>
                            </div>
                            <div class="p-2 bg-emerald-50 rounded">
                                <div class="text-gray-500">T.P Rate</div>
                                <div class="font-semibold text-emerald-600">{{ selectedProduct.tp_rate || 0 }}</div>
                            </div>
                            <div class="p-2 bg-gray-50 rounded">
                                <div class="text-gray-500">Dist. Margin %</div>
                                <div class="font-semibold">{{ selectedProduct.distribution_margin || 0 }}%</div>
                            </div>
                            <div class="p-2 bg-blue-50 rounded">
                                <div class="text-gray-500">Invoice Price</div>
                                <div class="font-semibold text-blue-600">{{ selectedProduct.invoice_price || 0 }}</div>
                            </div>
                            <div class="p-2 bg-blue-50 rounded">
                                <div class="text-gray-500">Unit Price</div>
                                <div class="font-semibold text-blue-600">{{ selectedProduct.unit_price || 0 }}</div>
                            </div>
                            <div class="p-2 bg-purple-50 rounded">
                                <div class="text-gray-500">Total Value</div>
                                <div class="font-semibold text-purple-600">{{ (form.quantity * form.unit_cost).toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <InputLabel value="Notes" />
                        <textarea v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm" placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-gradient-to-r from-purple-600 to-indigo-600 border-0">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Save as Draft') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
