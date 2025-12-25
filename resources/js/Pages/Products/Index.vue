<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions.js';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import FormModal from '@/Pages/Packings/FormModal.vue';
import Pagination from '@/Components/Pagination.vue';
import Swal from 'sweetalert2';
import { debounce } from 'lodash';

const props = defineProps({
    products: {
        type: Object,
        required: true
    },
    brands: {
        type: Array,
        default: () => []
    },
    packings: {
        type: Array,
        default: () => []
    },
    types: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', sort_field: 'created_at', sort_direction: 'desc' })
    }
});

const { can } = usePermissions();
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingProductId = ref(null);

// Filter State
const search = ref(props.filters.search || '');
const filterType = ref(props.filters.type || '');
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

// Debounced Search and Filter Watcher
watch([search, filterType, sortField, sortDirection], debounce(() => {
    router.get(route('products.index'), {
        search: search.value,
        type: filterType.value,
        sort_field: sortField.value,
        sort_direction: sortDirection.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const handleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (field) => {
    if (sortField.value !== field) return '↕';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const form = useForm({
    dms_code: '',
    name: '',
    brand_id: '',
    type_id: '',
    packing_id: '',
    sku: '',
    list_price_before_tax: 0,  // Exclusive Value (Base Price)
    retail_margin: 0,          // Retail Margin %
    tp_rate: 0,                // T.P Rate (calculated)
    distribution_margin: 0,    // Distribution Margin %
    invoice_price: 0,          // Invoice Price (calculated)
    fed_sales_tax: 0,          // Sale Tax %
    fed_percent: 0,            // FED %
    unit_price: 0,             // Unit Price (Final)
    reorder_level: 0,
    pieces_per_packing: 1,
});

// Auto-fill pieces_per_packing when packing is selected (uses conversion from packing)
watch(() => form.packing_id, (newPackingId) => {
    if (newPackingId) {
        const selectedPacking = props.packings.find(p => p.id === newPackingId);
        if (selectedPacking && selectedPacking.conversion) {
            form.pieces_per_packing = selectedPacking.conversion;
        }
    }
});

// Helper to parse number safely with high precision
const num = (val) => parseFloat(val) || 0;

// Flag to prevent infinite loops during calculations
let isCalculating = false;

/**
 * ERP/DMS Pricing Formula (Excel Style)
 * 
 * Forward Calculation (when Exclusive Value is entered):
 *   TP Rate = Exclusive Value × (1 + RetailMargin)
 *   Invoice Price = TP Rate ÷ (1 + DistributionMargin)
 *   Unit Price = Invoice Price × (1 + SalesTax + FED)
 * 
 * Reverse Calculation (when Unit Price is entered):
 *   Invoice Price = Unit Price ÷ (1 + SalesTax + FED)
 *   TP Rate = Invoice Price × (1 + DistributionMargin)
 *   Exclusive Value = TP Rate ÷ (1 + RetailMargin)
 */

// Forward calculation: Exclusive Value (Base Price) → Unit Price
const calculateForward = () => {
    if (isCalculating) return;
    isCalculating = true;
    
    const basePrice = num(form.list_price_before_tax);
    const retailerMargin = num(form.retail_margin) / 100;
    const distMargin = num(form.distribution_margin) / 100;
    const salesTax = num(form.fed_sales_tax) / 100;
    const fed = num(form.fed_percent) / 100;
    
    // Step 1: Base Price + FED %
    const priceAfterFed = basePrice * (1 + fed);
    
    // Step 2: (Base Price + FED) + Sale Tax % = Unit Price
    const unitPrice = priceAfterFed * (1 + salesTax);

    // Derived Fields Logic (Markup Logic from Unit Price)
    // "TP Rate on Retail and Unit Price" -> Unit Price is TP + Margin
    // So TP Rate = Unit Price / (1 + Retail Margin)
    // Verification: 100 / 1.118971 = ~89.36 (Matches user request)
    const tpRate = unitPrice / (1 + retailerMargin);
    
    // Invoice Price (Distributor Price)
    // Invoice Price is TP Rate / (1 + Dist Margin)
    const invoicePrice = tpRate / (1 + distMargin);

    // Update all calculated fields
    form.tp_rate = tpRate.toFixed(4);
    form.invoice_price = invoicePrice.toFixed(4);
    form.unit_price = unitPrice.toFixed(2);
    
    isCalculating = false;
};

// Reverse calculation: Unit Price → Exclusive Value (Base Price)
const calculateReverse = () => {
    if (isCalculating) return;
    isCalculating = true;
    
    const unitPrice = num(form.unit_price);
    const retailerMargin = num(form.retail_margin) / 100;
    const distMargin = num(form.distribution_margin) / 100;
    const salesTax = num(form.fed_sales_tax) / 100;
    const fed = num(form.fed_percent) / 100;
    
    // Step 1: Remove Sale Tax
    const priceAfterFed = unitPrice / (1 + salesTax);
    
    // Step 2: Remove FED
    const basePrice = priceAfterFed / (1 + fed);
    
    // Derived Fields Logic
    // Same as above
    const tpRate = unitPrice / (1 + retailerMargin);
    const invoicePrice = tpRate / (1 + distMargin);
    
    // Update all calculated fields
    form.list_price_before_tax = basePrice.toFixed(8); 
    form.tp_rate = tpRate.toFixed(4);
    form.invoice_price = invoicePrice.toFixed(4);
    
    isCalculating = false;
};

// Track which field was last edited for calculation direction
const lastEditedField = ref('exclusive'); // 'exclusive' or 'unit'

// Watch Exclusive Value (List Price) changes - Forward calculation
watch(
    () => form.list_price_before_tax,
    () => {
        if (!isCalculating) {
            lastEditedField.value = 'exclusive';
            calculateForward();
        }
    }
);

// Watch Unit Price changes - Reverse calculation
watch(
    () => form.unit_price,
    () => {
        if (!isCalculating && lastEditedField.value === 'unit') {
            calculateReverse();
        }
    }
);

// Watch margin/tax changes - recalculate based on last edited field
watch(
    () => [
        form.retail_margin,
        form.distribution_margin,
        form.fed_sales_tax,
        form.fed_percent,
    ],
    () => {
        if (!isCalculating) {
            if (lastEditedField.value === 'unit') {
                calculateReverse();
            } else {
                calculateForward();
            }
        }
    },
    { deep: true }
);

// Handler for Unit Price manual input
const onUnitPriceInput = () => {
    lastEditedField.value = 'unit';
};

// Quick-add modal states
const isBrandModalOpen = ref(false);
const isTypeModalOpen = ref(false);
const isPackingModalOpen = ref(false);

const brandForm = useForm({ name: '', status: 'active' });
const typeForm = useForm({ name: '' });

const submitBrand = () => {
    const newName = brandForm.name;
    brandForm.post(route('brands.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            isBrandModalOpen.value = false;
            brandForm.reset();
            const newBrand = page.props.brands?.find(b => b.name === newName);
            if (newBrand) {
                form.brand_id = newBrand.id;
            }
            Swal.fire({ title: 'Success', text: 'Brand created!', icon: 'success', timer: 1500, showConfirmButton: false });
        }
    });
};

const submitType = () => {
    const newName = typeForm.name;
    typeForm.post(route('product-types.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            isTypeModalOpen.value = false;
            typeForm.reset();
            const newType = page.props.types?.find(t => t.name === newName);
            if (newType) {
                form.type_id = newType.id;
            }
            Swal.fire({ title: 'Success', text: 'Type created!', icon: 'success', timer: 1500, showConfirmButton: false });
        }
    });
};

const handlePackingSaved = () => {
    const page = usePage();
    const newId = page.props.flash?.created_packing_id;
    if (newId) {
        form.packing_id = newId;
        Swal.fire({ title: 'Success', text: 'Packing created!', icon: 'success', timer: 1500, showConfirmButton: false });
    }
};



const isImportModalOpen = ref(false);
const importProcessing = ref(false);

const openImportModal = () => {
    isImportModalOpen.value = true;
};

const closeImportModal = () => {
    isImportModalOpen.value = false;
};

const handleImport = () => {
    const fileInput = document.getElementById('import-file');
    const file = fileInput?.files[0];
    
    if (!file) {
        Swal.fire({ title: 'Error', text: 'Please select a file to import.', icon: 'error' });
        return;
    }

    importProcessing.value = true;
    const formData = new FormData();
    formData.append('file', file);
    
    router.post(route('products.import'), formData, {
        onSuccess: () => {
             importProcessing.value = false;
             closeImportModal();
             Swal.fire({ title: 'Success', text: 'Products imported successfully!', icon: 'success', timer: 1500, showConfirmButton: false });
             if(fileInput) fileInput.value = '';
        },
        onError: () => {
             importProcessing.value = false;
             Swal.fire({ title: 'Error', text: 'Import failed. Please check the file format.', icon: 'error' });
        }
    });
};

const openModal = (product = null) => {
    isEditing.value = !!product;
    editingProductId.value = product?.id;
    
    if (product) {
        form.dms_code = product.dms_code || '';
        form.name = product.name || '';
        form.brand_id = product.brand_id || '';
        form.type_id = product.type_id || '';
        form.packing_id = product.packing_id || '';
        form.sku = product.sku || '';
        form.list_price_before_tax = product.list_price_before_tax || 0;
        form.retail_margin = product.retail_margin || 0;
        form.tp_rate = product.tp_rate || 0;
        form.distribution_margin = product.distribution_margin || 0;
        form.invoice_price = product.invoice_price || 0;
        form.fed_sales_tax = product.fed_sales_tax || 0;
        form.fed_percent = product.fed_percent || 0;
        form.unit_price = product.unit_price || 0;
        form.reorder_level = product.reorder_level || 0;
        form.pieces_per_packing = product.pieces_per_packing || 1;
    } else {
        form.reset();
        form.pieces_per_packing = 1;
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
        form.put(route('products.update', editingProductId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('products.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteProduct = (product) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete "${product.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('products.destroy', product.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire(
                        'Deleted!',
                        'Product has been deleted.',
                        'success'
                    )
                }
            });
        }
    })
};
</script>

<template>
    <Head title="Product Management" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Products</h1>
                    <p class="text-gray-500 mt-1">Manage your product inventory</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Search Bar -->
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search products..." 
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 w-64 shadow-sm"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Type Filter -->
                    <select 
                        v-model="filterType"
                        class="py-2.5 rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-white shadow-sm"
                    >
                        <option value="">All Types</option>
                        <option v-for="type in types" :key="type.id" :value="type.name">{{ type.name }}</option>
                    </select>

                    <button 
                        @click="openImportModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-700 rounded-xl font-medium border border-gray-200 hover:bg-gray-50 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import
                    </button>
                    <button 
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Product
                    </button>
                </div>
            </div>

            <!-- Import Modal -->
            <Modal :show="isImportModalOpen" @close="closeImportModal" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        Import Products
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Please use the reference template for importing products.
                                    </p>
                                    <a :href="route('products.template')" class="mt-2 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
                                        Download Template
                                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Excel File</label>
                            <input 
                                type="file" 
                                id="import-file"
                                accept=".xlsx,.csv"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                            >
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton @click="closeImportModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton @click="handleImport" :disabled="importProcessing" :class="{ 'opacity-25': importProcessing }">
                            {{ importProcessing ? 'Importing...' : 'Import Products' }}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            <!-- Products Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th @click="handleSort('dms_code')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Code {{ getSortIcon('dms_code') }}
                                </th>
                                <th @click="handleSort('name')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Product Name {{ getSortIcon('name') }}
                                </th>
                                <th class="px-6 py-4">Brand</th>
                                <th @click="handleSort('list_price_before_tax')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Exclusive Value {{ getSortIcon('list_price_before_tax') }}
                                </th>
                                <th class="px-6 py-4">SKU</th>
                                <th class="px-6 py-4">Retail %</th>
                                <th @click="handleSort('tp_rate')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    T.P Rate {{ getSortIcon('tp_rate') }}
                                </th>
                                <th class="px-6 py-4">Dist. %</th>
                                <th class="px-6 py-4">Invoice Price</th>
                                <th class="px-6 py-4">Sale Tax %</th>
                                <th class="px-6 py-4">FED %</th>
                                <th class="px-6 py-4">Types</th>
                                <th class="px-6 py-4">Reorder Level</th>
                                <th @click="handleSort('unit_price')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Unit Price {{ getSortIcon('unit_price') }}
                                </th>
                                <th class="px-6 py-4">Packing</th>
                                <th class="px-6 py-4">Pcs/Packing</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ product.dms_code || '-' }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ product.name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ product.brand?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-semibold">
                                    {{ product.list_price_before_tax || 0 }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.sku || '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.retail_margin || 0 }}%
                                </td>
                                <td class="px-6 py-4 text-gray-700 font-medium">
                                    {{ product.tp_rate || 0 }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.distribution_margin || 0 }}%
                                </td>
                                <td class="px-6 py-4 text-gray-700 font-medium">
                                    {{ product.invoice_price || 0 }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.fed_sales_tax || 0 }}%
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.fed_percent || 0 }}%
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs">{{ product.product_type?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.reorder_level || 0 }}
                                </td>
                                <td class="px-6 py-4 text-emerald-700 font-bold">
                                    {{ product.unit_price || 0 }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs">{{ product.packing?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ product.pieces_per_packing || 1 }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="openModal(product)"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit Product"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="deleteProduct(product)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete Product"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="14" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <p class="font-medium">No products found</p>
                                        <p class="text-xs">Try adjusting your search or add a new product.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="products.links" />
                </div>
            </div>
        </div>

        <!-- Product Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="4xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">
                    {{ isEditing ? 'Edit Product' : 'Add New Product' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                    <!-- Row 1: Code, Product Name, SKU -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Code" />
                            <TextInput v-model="form.dms_code" type="text" class="mt-1 block w-full" />
                            <div v-if="form.errors.dms_code" class="text-xs text-red-600 mt-1">{{ form.errors.dms_code }}</div>
                        </div>
                        <div>
                            <InputLabel value="Product Name" />
                            <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div>
                            <InputLabel value="SKU" />
                            <TextInput v-model="form.sku" type="text" class="mt-1 block w-full" />
                            <div v-if="form.errors.sku" class="text-xs text-red-600 mt-1">{{ form.errors.sku }}</div>
                        </div>
                    </div>

                    <!-- Row 2: Brand, Types -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Brand" />
                            <div class="flex gap-2 mt-1">
                                <div class="flex-1">
                                    <SearchableSelect 
                                        v-model="form.brand_id"
                                        :options="brands"
                                        option-value="id"
                                        option-label="name"
                                        placeholder="Select Brand"
                                        :error="form.errors.brand_id"
                                    />
                                </div>
                                <button 
                                    type="button"
                                    @click="isBrandModalOpen = true"
                                    class="p-2.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-lg transition-colors"
                                    title="Add New Brand"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Types" />
                            <div class="flex gap-2 mt-1">
                                <div class="flex-1">
                                    <SearchableSelect 
                                        v-model="form.type_id"
                                        :options="types"
                                        option-value="id"
                                        option-label="name"
                                        placeholder="Select Type"
                                        :error="form.errors.type_id"
                                    />
                                </div>
                                <button 
                                    type="button"
                                    @click="isTypeModalOpen = true"
                                    class="p-2.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg transition-colors"
                                    title="Add New Type"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Exclusive Value, Retail Margin, T.P Rate -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Exclusive Value (Base Price)" />
                            <TextInput v-model="form.list_price_before_tax" type="number" step="0.0001" class="mt-1 block w-full" required />
                            <div v-if="form.errors.list_price_before_tax" class="text-xs text-red-600 mt-1">{{ form.errors.list_price_before_tax }}</div>
                        </div>
                        <div>
                            <InputLabel value="Retail Margin %" />
                            <TextInput v-model="form.retail_margin" type="number" step="0.001" class="mt-1 block w-full" placeholder="e.g. 11.897" />
                            <div v-if="form.errors.retail_margin" class="text-xs text-red-600 mt-1">{{ form.errors.retail_margin }}</div>
                        </div>
                        <div>
                            <InputLabel value="T.P Rate (Calculated)" />
                            <TextInput v-model="form.tp_rate" type="number" step="0.0001" class="mt-1 block w-full bg-gray-50" readonly />
                        </div>
                    </div>

                    <!-- Row 4: Dist Margin, Invoice Price -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Distribution Margin %" />
                            <TextInput v-model="form.distribution_margin" type="number" step="0.001" class="mt-1 block w-full" placeholder="e.g. 5.1" />
                            <div v-if="form.errors.distribution_margin" class="text-xs text-red-600 mt-1">{{ form.errors.distribution_margin }}</div>
                        </div>
                        <div>
                            <InputLabel value="Invoice Price (Calculated)" />
                            <TextInput v-model="form.invoice_price" type="number" step="0.0001" class="mt-1 block w-full bg-gray-50" readonly />
                        </div>
                    </div>

                    <!-- Row 5: Sale Tax, FED, Unit Price -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Sale Tax %" />
                            <TextInput v-model="form.fed_sales_tax" type="number" step="0.01" class="mt-1 block w-full" placeholder="e.g. 18" />
                            <div v-if="form.errors.fed_sales_tax" class="text-xs text-red-600 mt-1">{{ form.errors.fed_sales_tax }}</div>
                        </div>
                        <div>
                            <InputLabel value="FED %" />
                            <TextInput v-model="form.fed_percent" type="number" step="0.01" class="mt-1 block w-full" placeholder="e.g. 20" />
                            <div v-if="form.errors.fed_percent" class="text-xs text-red-600 mt-1">{{ form.errors.fed_percent }}</div>
                        </div>
                        <div>
                            <InputLabel value="Unit Price (Final)" />
                            <TextInput 
                                v-model="form.unit_price" 
                                type="number" 
                                step="0.01" 
                                class="mt-1 block w-full bg-emerald-50 font-bold text-emerald-700" 
                                @input="onUnitPriceInput"
                                placeholder="Auto-calculated or enter manually"
                            />
                            <div v-if="form.errors.unit_price" class="text-xs text-red-600 mt-1">{{ form.errors.unit_price }}</div>
                        </div>
                    </div>

                    <!-- Row 5: Reorder Level -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Reorder Level" />
                            <TextInput v-model="form.reorder_level" type="number" class="mt-1 block w-full" />
                            <div v-if="form.errors.reorder_level" class="text-xs text-red-600 mt-1">{{ form.errors.reorder_level }}</div>
                        </div>
                    </div>

                    <!-- Row 5: Packing, Pieces per Packing -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Packing" />
                            <div class="flex gap-2 mt-1">
                                <div class="flex-1">
                                    <SearchableSelect 
                                        v-model="form.packing_id"
                                        :options="packings"
                                        option-value="id"
                                        option-label="name"
                                        placeholder="Select Packing"
                                        :error="form.errors.packing_id"
                                    />
                                </div>
                                <button 
                                    type="button"
                                    @click="isPackingModalOpen = true"
                                    class="p-2.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors"
                                    title="Add New Packing"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Per Packing Piece" />
                            <TextInput v-model="form.pieces_per_packing" type="number" min="1" class="mt-1 block w-full" />
                            <div v-if="form.errors.pieces_per_packing" class="text-xs text-red-600 mt-1">{{ form.errors.pieces_per_packing }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t sticky bottom-0 bg-white">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton 
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Product' : 'Create Product') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Quick-Add Brand Modal -->
        <Modal :show="isBrandModalOpen" @close="isBrandModalOpen = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Add New Brand</h2>
                <form @submit.prevent="submitBrand" class="space-y-4">
                    <div>
                        <InputLabel value="Brand Name" />
                        <TextInput v-model="brandForm.name" type="text" class="mt-1 block w-full" :class="{ 'border-red-500': brandForm.errors.name }" />
                        <div v-if="brandForm.errors.name" class="text-xs text-red-600 mt-1">{{ brandForm.errors.name }}</div>
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="brandForm.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <SecondaryButton @click="isBrandModalOpen = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="brandForm.processing" class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0">{{ brandForm.processing ? 'Saving...' : 'Create Brand' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Quick-Add Type Modal -->
        <Modal :show="isTypeModalOpen" @close="isTypeModalOpen = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Add New Product Type</h2>
                <form @submit.prevent="submitType" class="space-y-4">
                    <div>
                        <InputLabel value="Type Name" />
                        <TextInput v-model="typeForm.name" type="text" class="mt-1 block w-full" :class="{ 'border-red-500': typeForm.errors.name }" />
                        <div v-if="typeForm.errors.name" class="text-xs text-red-600 mt-1">{{ typeForm.errors.name }}</div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <SecondaryButton @click="isTypeModalOpen = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="typeForm.processing" class="bg-gradient-to-r from-purple-600 to-indigo-600 border-0">{{ typeForm.processing ? 'Saving...' : 'Create Type' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Packing Modal -->
        <FormModal 
            :show="isPackingModalOpen" 
            @close="isPackingModalOpen = false" 
            @saved="handlePackingSaved"
        />
    </DashboardLayout>
</template>
