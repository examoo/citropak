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
    categories: {
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
    if (sortField.value !== field) return '↕'; // Default neutral sort icon
    return sortDirection.value === 'asc' ? '↑' : '↓';
};


const form = useForm({
    dms_code: '',
    name: '',
    brand_id: '',
    category_id: '',
    type_id: '',
    packing_id: '',
    list_price_before_tax: 0,
    fed_tax_percent: 0,
    fed_sales_tax: 0,
    net_list_price: 0,
    distribution_margin: 0,
    distribution_manager_percent: 0,
    trade_price_before_tax: 0,
    fed_2: 0,
    sales_tax_3: 0,
    net_trade_price: 0,
    retailer_margin: 0,
    retailer_margin_4: 0,
    consumer_price_before_tax: 0,
    fed_5: 0,
    sales_tax_6: 0,
    net_consumer_price: 0,
    total_margin: 0,
    unit_price: 0,
    packing: '',
    packing_one: '',
    reorder_level: '',
    stock_quantity: 0,
});

// Helper to parse number safely
const num = (val) => parseFloat(val) || 0;

// Auto-calculate prices when fields change
const calculatePrices = () => {
    // Step 1: Calculate FED Sales Tax and Net List Price
    const listPrice = num(form.list_price_before_tax);
    const fedPercent = num(form.fed_tax_percent);
    form.fed_sales_tax = (listPrice * fedPercent / 100).toFixed(2);
    form.net_list_price = (listPrice + num(form.fed_sales_tax)).toFixed(2);

    // Step 2: Calculate Trade Price Before Tax (List Price - Distribution Margin)
    const distMargin = num(form.distribution_margin);
    form.trade_price_before_tax = (num(form.net_list_price) - distMargin).toFixed(2);

    // Step 3: Calculate Net Trade Price
    form.net_trade_price = (num(form.trade_price_before_tax) + num(form.fed_2) + num(form.sales_tax_3)).toFixed(2);

    // Step 4: Calculate Consumer Price Before Tax (Trade + Retailer Margin)
    const retailerMargin = num(form.retailer_margin);
    form.consumer_price_before_tax = (num(form.net_trade_price) + retailerMargin).toFixed(2);

    // Step 5: Calculate Net Consumer Price
    form.net_consumer_price = (num(form.consumer_price_before_tax) + num(form.fed_5) + num(form.sales_tax_6)).toFixed(2);

    // Step 6: Calculate Total Margin
    form.total_margin = (num(form.net_consumer_price) - listPrice).toFixed(2);

    // Step 7: Unit Price = Net Consumer Price
    form.unit_price = form.net_consumer_price;
};

// Watch for changes on input fields and recalculate
watch(
    () => [
        form.list_price_before_tax,
        form.fed_tax_percent,
        form.distribution_margin,
        form.distribution_manager_percent,
        form.fed_2,
        form.sales_tax_3,
        form.retailer_margin,
        form.retailer_margin_4,
        form.fed_5,
        form.sales_tax_6,
    ],
    () => {
        calculatePrices();
    },
    { deep: true }
);

// Quick-add modal states
const isBrandModalOpen = ref(false);
const isCategoryModalOpen = ref(false);
const isTypeModalOpen = ref(false);
const isPackingModalOpen = ref(false);

const brandForm = useForm({ name: '', status: 'active' });
const categoryForm = useForm({ name: '', status: 'active' });
const typeForm = useForm({ name: '' });

const submitBrand = () => {
    const newName = brandForm.name;
    brandForm.post(route('brands.store'), {
        preserveScroll: true,
        preserveState: true, // Keep Products modal open
        onSuccess: (page) => {
            isBrandModalOpen.value = false;
            brandForm.reset();
            // Find the newly created brand and auto-select it
            const newBrand = page.props.brands?.find(b => b.name === newName);
            if (newBrand) {
                form.brand_id = newBrand.id;
            }
            Swal.fire({ title: 'Success', text: 'Brand created!', icon: 'success', timer: 1500, showConfirmButton: false });
        }
    });
};

const submitCategory = () => {
    const newName = categoryForm.name;
    categoryForm.post(route('product-categories.store'), {
        preserveScroll: true,
        preserveState: true, // Keep Products modal open
        onSuccess: (page) => {
            isCategoryModalOpen.value = false;
            categoryForm.reset();
            // Find the newly created category and auto-select it
            const newCategory = page.props.categories?.find(c => c.name === newName);
            if (newCategory) {
                form.category_id = newCategory.id;
            }
            Swal.fire({ title: 'Success', text: 'Category created!', icon: 'success', timer: 1500, showConfirmButton: false });
        }
    });
};

const submitType = () => {
    const newName = typeForm.name;
    typeForm.post(route('product-types.store'), {
        preserveScroll: true,
        preserveState: true, // Keep Products modal open
        onSuccess: (page) => {
            isTypeModalOpen.value = false;
            typeForm.reset();
            // Find the newly created type and auto-select it
            const newType = page.props.types?.find(t => t.name === newName);
            if (newType) {
                form.type_id = newType.id;
            }
            Swal.fire({ title: 'Success', text: 'Type created!', icon: 'success', timer: 1500, showConfirmButton: false });
        }
    });
};

const handlePackingSaved = () => {
    // Rely on router reload updating props, then check flash or latest
    // Since we use FormModal with onSuccess->emit('saved'), the props should be updated (if we preserveState=false or if partial reload happened)
    // Actually, FormModal does router.post. That reloads the page.
    const page = usePage();
    const newId = page.props.flash?.created_packing_id;
    if (newId) {
        form.packing_id = newId;
        Swal.fire({ title: 'Success', text: 'Packing created!', icon: 'success', timer: 1500, showConfirmButton: false });
    }
};

const openModal = (product = null) => {
    isEditing.value = !!product;
    editingProductId.value = product?.id;
    
    if (product) {
        form.dms_code = product.dms_code;
        form.name = product.name;
        form.brand_id = product.brand_id;
        form.category_id = product.category_id;
        form.type_id = product.type_id;
        form.list_price_before_tax = product.list_price_before_tax;
        form.fed_tax_percent = product.fed_tax_percent;
        form.fed_sales_tax = product.fed_sales_tax;
        form.net_list_price = product.net_list_price;
        form.distribution_margin = product.distribution_margin;
        form.distribution_manager_percent = product.distribution_manager_percent;
        form.trade_price_before_tax = product.trade_price_before_tax;
        form.fed_2 = product.fed_2;
        form.sales_tax_3 = product.sales_tax_3;
        form.net_trade_price = product.net_trade_price;
        form.retailer_margin = product.retailer_margin;
        form.retailer_margin_4 = product.retailer_margin_4;
        form.consumer_price_before_tax = product.consumer_price_before_tax;
        form.fed_5 = product.fed_5;
        form.sales_tax_6 = product.sales_tax_6;
        form.net_consumer_price = product.net_consumer_price;
        form.total_margin = product.total_margin;
        form.unit_price = product.unit_price;
        form.packing_id = product.packing_id;
        form.packing = product.packing;
        form.packing_one = product.packing_one;
        form.reorder_level = product.reorder_level;
        form.stock_quantity = product.stock_quantity;
    } else {
        form.reset();
        // Set defaults to 0.00 equivalent strings if needed, or let placeholder handle it
        form.stock_quantity = 0;
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
                <!-- Check explicit permissions -->
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
                                    Name {{ getSortIcon('name') }}
                                </th>
                                <th @click="handleSort('brand')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Brand {{ getSortIcon('brand') }}
                                </th>
                                <th class="px-6 py-4 text-gray-500 font-medium tracking-wider">
                                    Category
                                </th>
                                <th class="px-6 py-4 text-gray-500 font-medium tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-4 text-gray-500 font-medium tracking-wider">
                                    Packing
                                </th>
                                <th @click="handleSort('stock_quantity')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Stock {{ getSortIcon('stock_quantity') }}
                                </th>
                                <th @click="handleSort('net_consumer_price')" class="px-6 py-4 cursor-pointer hover:text-emerald-600 transition-colors">
                                    Price {{ getSortIcon('net_consumer_price') }}
                                </th>
                                <th class="px-6 py-4 text-gray-500 font-medium tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ product.dms_code }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ product.name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ product.brand?.name || product.brand || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">{{ product.category?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs">{{ product.product_type?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs">{{ product.packing?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        product.stock_quantity > 10 ? 'bg-emerald-100 text-emerald-700' : 
                                        product.stock_quantity > 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'
                                    ]">
                                        {{ product.stock_quantity || 0 }} units
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-semibold">
                                    {{ product.net_consumer_price }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[
                                        'px-2 py-1 rounded-full text-xs font-medium',
                                        product.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                    ]">
                                        {{ product.status }}
                                    </span>
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
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
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

                <form @submit.prevent="submit" class="space-y-4 h-[70vh] overflow-y-auto pr-2">
                    <!-- Row 1: Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="DMS Code" />
                            <TextInput v-model="form.dms_code" type="text" class="mt-1 block w-full" />
                            <div v-if="form.errors.dms_code" class="text-xs text-red-600 mt-1">{{ form.errors.dms_code }}</div>
                        </div>
                        <div>
                            <InputLabel value="Product Name" />
                            <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                        </div>
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
                    </div>

                    <!-- Row 2: Category & Type -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Category" />
                            <div class="flex gap-2 mt-1">
                                <div class="flex-1">
                                    <SearchableSelect 
                                        v-model="form.category_id"
                                        :options="categories"
                                        option-value="id"
                                        option-label="name"
                                        placeholder="Select Category"
                                        :error="form.errors.category_id"
                                    />
                                </div>
                                <button 
                                    type="button"
                                    @click="isCategoryModalOpen = true"
                                    class="p-2.5 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-lg transition-colors"
                                    title="Add New Category"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Type" />
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
                        <div>
                            <InputLabel value="Current Stock" />
                            <TextInput v-model="form.stock_quantity" type="number" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 3: Pricing -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="List Price (Before Tax)" />
                            <TextInput v-model="form.list_price_before_tax" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="FED %" />
                            <TextInput v-model="form.fed_tax_percent" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="FED Sales Tax" />
                            <TextInput v-model="form.fed_sales_tax" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 3 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Net List Price" />
                            <TextInput v-model="form.net_list_price" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Dist. Margin" />
                            <TextInput v-model="form.distribution_margin" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Dist. Manager %" />
                            <TextInput v-model="form.distribution_manager_percent" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 4 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                         <div>
                            <InputLabel value="Trade Price (Before Tax)" />
                            <TextInput v-model="form.trade_price_before_tax" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="FED2" />
                            <TextInput v-model="form.fed_2" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Sales Tax3" />
                            <TextInput v-model="form.sales_tax_3" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 5 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Net Trade Price" />
                            <TextInput v-model="form.net_trade_price" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Retailer Margin" />
                            <TextInput v-model="form.retailer_margin" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Retailer Margin4" />
                            <TextInput v-model="form.retailer_margin_4" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 6 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Consumer Price (Before Tax)" />
                            <TextInput v-model="form.consumer_price_before_tax" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="FED5" />
                            <TextInput v-model="form.fed_5" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Sales Tax6" />
                            <TextInput v-model="form.sales_tax_6" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 7 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Net Consumer Price" />
                            <TextInput v-model="form.net_consumer_price" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Total Margin" />
                            <TextInput v-model="form.total_margin" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Unit Price" />
                            <TextInput v-model="form.unit_price" type="number" step="0.01" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 8 -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            <InputLabel value="Packing (Legacy)" />
                            <TextInput v-model="form.packing" type="text" class="mt-1 block w-full" placeholder="Legacy string" />
                        </div>
                        <div>
                            <InputLabel value="Reorder Level" />
                            <TextInput v-model="form.reorder_level" type="number" class="mt-1 block w-full" />
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

        <!-- Quick-Add Category Modal -->
        <Modal :show="isCategoryModalOpen" @close="isCategoryModalOpen = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Add New Product Category</h2>
                <form @submit.prevent="submitCategory" class="space-y-4">
                    <div>
                        <InputLabel value="Category Name" />
                        <TextInput v-model="categoryForm.name" type="text" class="mt-1 block w-full" :class="{ 'border-red-500': categoryForm.errors.name }" />
                        <div v-if="categoryForm.errors.name" class="text-xs text-red-600 mt-1">{{ categoryForm.errors.name }}</div>
                    </div>
                    <div>
                        <InputLabel value="Status" />
                        <select v-model="categoryForm.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <SecondaryButton @click="isCategoryModalOpen = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="categoryForm.processing" class="bg-gradient-to-r from-orange-600 to-amber-600 border-0">{{ categoryForm.processing ? 'Saving...' : 'Create Category' }}</PrimaryButton>
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
