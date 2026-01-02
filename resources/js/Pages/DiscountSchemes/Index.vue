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
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

// Format date to "24 Dec 2024" format
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const day = date.getDate();
    const month = date.toLocaleString('en-US', { month: 'short' });
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
};

const props = defineProps({
    schemes: Object,
    filters: Object,
    distributions: { type: Array, default: () => [] },
    subDistributions: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters?.search || '');

// Product/Brand selection modal states
const isProductModalOpen = ref(false);
const isBrandModalOpen = ref(false);
const productSearch = ref('');
const brandSearch = ref('');

const form = useForm({
    name: '',
    distribution_id: '',
    sub_distribution_id: '',
    start_date: '',
    end_date: '',
    scheme_type: 'product',
    product_ids: [],
    brand_ids: [],
    from_qty: 1,
    to_qty: '',
    pieces: '',
    free_product_code: '',
    amount_less: 0,
    status: 'active',
});

// Filter sub distributions based on selected distribution
const filteredSubDistributions = computed(() => {
    if (!form.distribution_id) {
        return props.subDistributions;
    }
    return props.subDistributions.filter(sd => 
        !sd.distribution_id || sd.distribution_id == form.distribution_id
    );
});

// Filter products for modal search
const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products;
    const search = productSearch.value.toLowerCase();
    return props.products.filter(p => 
        p.name.toLowerCase().includes(search) || 
        (p.dms_code && p.dms_code.toLowerCase().includes(search))
    );
});

// Filter brands for modal search
const filteredBrands = computed(() => {
    if (!brandSearch.value) return props.brands;
    const search = brandSearch.value.toLowerCase();
    return props.brands.filter(b => b.name.toLowerCase().includes(search));
});

// Get selected product names for display
const selectedProductNames = computed(() => {
    return props.products
        .filter(p => form.product_ids.includes(p.id))
        .map(p => p.name);
});

// Get selected brand names for display
const selectedBrandNames = computed(() => {
    return props.brands
        .filter(b => form.brand_ids.includes(b.id))
        .map(b => b.name);
});

// Reset sub_distribution when distribution changes
watch(() => form.distribution_id, () => {
    form.sub_distribution_id = '';
});

// Clear selections when scheme type changes
watch(() => form.scheme_type, () => {
    form.product_ids = [];
    form.brand_ids = [];
});

watch(search, debounce((value) => {
    router.get(route('discount-schemes.index'), { search: value }, {
        preserveState: true, preserveScroll: true, replace: true
    });
}, 300));

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    if (item) {
        form.name = item.name;
        form.distribution_id = item.distribution_id;
        form.sub_distribution_id = item.sub_distribution_id || '';
        form.start_date = item.start_date?.split('T')[0] || item.start_date;
        form.end_date = item.end_date?.split('T')[0] || item.end_date;
        
        // Set product_ids and brand_ids BEFORE setting scheme_type to prevent watcher from clearing them
        const productIds = item.products ? item.products.map(p => p.id) : [];
        const brandIds = item.brands ? item.brands.map(b => b.id) : [];
        
        form.scheme_type = item.scheme_type;
        
        // Now set them after scheme_type is set (use nextTick workaround)
        setTimeout(() => {
            form.product_ids = productIds;
            form.brand_ids = brandIds;
        }, 0);
        
        form.from_qty = item.from_qty;
        form.to_qty = item.to_qty;
        form.pieces = item.pieces;
        form.free_product_code = item.free_product_code;
        form.amount_less = item.amount_less;
        form.status = item.status;
    } else {
        form.reset();
        form.scheme_type = 'product';
        form.product_ids = [];
        form.brand_ids = [];
        form.from_qty = 1;
        form.amount_less = 0;
        form.status = 'active';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const toggleProduct = (productId) => {
    const idx = form.product_ids.indexOf(productId);
    if (idx === -1) {
        form.product_ids.push(productId);
    } else {
        form.product_ids.splice(idx, 1);
    }
};

const toggleBrand = (brandId) => {
    const idx = form.brand_ids.indexOf(brandId);
    if (idx === -1) {
        form.brand_ids.push(brandId);
    } else {
        form.brand_ids.splice(idx, 1);
    }
};

const selectAllProducts = () => {
    form.product_ids = filteredProducts.value.map(p => p.id);
};

const clearAllProducts = () => {
    form.product_ids = [];
};

const selectAllBrands = () => {
    form.brand_ids = filteredBrands.value.map(b => b.id);
};

const clearAllBrands = () => {
    form.brand_ids = [];
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('discount-schemes.update', editingId.value), { onSuccess: () => closeModal() });
    } else {
        form.post(route('discount-schemes.store'), { onSuccess: () => closeModal() });
    }
};

const deleteItem = (item) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete scheme "${item.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('discount-schemes.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => Swal.fire('Deleted!', 'Scheme deleted.', 'success')
            });
        }
    });
};

// Get display text for products/brands column
const getItemsDisplay = (item) => {
    if (item.scheme_type === 'product') {
        if (item.products && item.products.length > 0) {
            return item.products.map(p => p.name).join(', ');
        }
        return item.product?.name || '-';
    } else {
        if (item.brands && item.brands.length > 0) {
            return item.brands.map(b => b.name).join(', ');
        }
        return item.brand?.name || '-';
    }
};
</script>

<template>
    <Head title="Discount Schemes" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Discount Schemes</h1>
                    <p class="text-gray-500 mt-1">Manage product and brand discount schemes</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input v-model="search" type="text" placeholder="Search..." class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-amber-500 focus:ring-amber-500 w-64 shadow-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add Scheme
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Name</th>
                            <th v-if="!currentDistribution?.id" class="px-6 py-4">Distribution</th>
                            <th class="px-6 py-4">Sub Distribution</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Products/Brands</th>
                            <th class="px-6 py-4">Dates</th>
                            <th class="px-6 py-4">Amount Less</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in schemes.data" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ item.name }}</td>
                            <td v-if="!currentDistribution?.id" class="px-6 py-4">
                                <span v-if="item.distribution" class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ item.distribution.code }}</span>
                                <span v-else class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">All</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="item.sub_distribution" class="px-2 py-1 bg-cyan-100 text-cyan-700 rounded-full text-xs">{{ item.sub_distribution.name }}</span>
                                <span v-else class="text-gray-400 text-xs">All</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', item.scheme_type === 'product' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700']">
                                    {{ item.scheme_type === 'product' ? 'Product' : 'Brand' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ getItemsDisplay(item) }}</td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ formatDate(item.start_date) }} to {{ formatDate(item.end_date) }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-amber-600">{{ item.amount_less }}</td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', item.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700']">
                                    {{ item.status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openModal(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button @click="deleteItem(item)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="schemes.data.length === 0"><td :colspan="!currentDistribution?.id ? 9 : 8" class="px-6 py-12 text-center text-gray-500">No discount schemes found.</td></tr>
                    </tbody>
                </table>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50"><Pagination :links="schemes.links" /></div>
            </div>
        </div>

        <!-- Discount Scheme Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="3xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">{{ isEditing ? 'Edit' : 'Add New' }} Discount Scheme</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Row 1: Name, Distribution, Sub Distribution, Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Scheme Name" />
                            <TextInput v-model="form.name" type="text" class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.name }" />
                            <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div v-if="!currentDistribution?.id">
                            <InputLabel value="Distribution" />
                            <SearchableSelect 
                                v-model="form.distribution_id"
                                :options="distributions"
                                option-value="id"
                                option-label="name"
                                placeholder="All Distributions"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel value="Sub Distribution" />
                            <select v-model="form.sub_distribution_id" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm">
                                <option value="">All Sub Distributions</option>
                                <option v-for="sd in filteredSubDistributions" :key="sd.id" :value="sd.id">{{ sd.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Start Date" />
                            <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                            <div v-if="form.errors.start_date" class="text-xs text-red-600 mt-1">{{ form.errors.start_date }}</div>
                        </div>
                        <div>
                            <InputLabel value="End Date" />
                            <TextInput v-model="form.end_date" type="date" class="mt-1 block w-full" />
                            <div v-if="form.errors.end_date" class="text-xs text-red-600 mt-1">{{ form.errors.end_date }}</div>
                        </div>
                    </div>

                    <!-- Row 2: Scheme Type -->
                    <div>
                        <InputLabel value="Scheme Type" />
                        <div class="flex gap-6 mt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.scheme_type" value="product" class="text-amber-600 focus:ring-amber-500">
                                <span>Apply to Products</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.scheme_type" value="brand" class="text-amber-600 focus:ring-amber-500">
                                <span>Apply to Brands</span>
                            </label>
                        </div>
                    </div>

                    <!-- Row 3: Product or Brand Selection Button -->
                    <div>
                        <div v-if="form.scheme_type === 'product'">
                            <InputLabel value="Products" />
                            <button type="button" @click="isProductModalOpen = true" 
                                class="mt-1 w-full flex items-center justify-between px-4 py-3 border border-gray-300 rounded-lg hover:border-amber-500 transition-colors text-left"
                                :class="{ 'border-red-500': form.errors.product_ids }">
                                <span v-if="form.product_ids.length === 0" class="text-gray-400">Click to select products...</span>
                                <span v-else class="text-gray-900">{{ form.product_ids.length }} Product(s) Selected</span>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                            <div v-if="selectedProductNames.length > 0 && selectedProductNames.length <= 5" class="text-xs text-gray-500 mt-1">
                                {{ selectedProductNames.join(', ') }}
                            </div>
                            <div v-if="form.errors.product_ids" class="text-xs text-red-600 mt-1">{{ form.errors.product_ids }}</div>
                        </div>
                        <div v-else>
                            <InputLabel value="Brands" />
                            <button type="button" @click="isBrandModalOpen = true"
                                class="mt-1 w-full flex items-center justify-between px-4 py-3 border border-gray-300 rounded-lg hover:border-amber-500 transition-colors text-left"
                                :class="{ 'border-red-500': form.errors.brand_ids }">
                                <span v-if="form.brand_ids.length === 0" class="text-gray-400">Click to select brands...</span>
                                <span v-else class="text-gray-900">{{ form.brand_ids.length }} Brand(s) Selected</span>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                            <div v-if="selectedBrandNames.length > 0 && selectedBrandNames.length <= 5" class="text-xs text-gray-500 mt-1">
                                {{ selectedBrandNames.join(', ') }}
                            </div>
                            <div v-if="form.errors.brand_ids" class="text-xs text-red-600 mt-1">{{ form.errors.brand_ids }}</div>
                        </div>
                    </div>

                    <!-- Row 4: Quantities -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="From Qty" />
                            <TextInput v-model="form.from_qty" type="number" class="mt-1 block w-full" min="1" />
                        </div>
                        <div>
                            <InputLabel value="To Qty" />
                            <TextInput v-model="form.to_qty" type="number" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Pieces" />
                            <TextInput v-model="form.pieces" type="number" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Free Product Code" />
                            <TextInput v-model="form.free_product_code" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <!-- Row 5: Amount & Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Amount Less" />
                            <TextInput v-model="form.amount_less" type="number" step="0.01" class="mt-1 block w-full" />
                            <div v-if="form.errors.amount_less" class="text-xs text-red-600 mt-1">{{ form.errors.amount_less }}</div>
                        </div>
                        <div>
                            <InputLabel value="Status" />
                            <select v-model="form.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing" class="bg-gradient-to-r from-amber-600 to-orange-600 border-0">{{ form.processing ? 'Saving...' : (isEditing ? 'Update Scheme' : 'Create Scheme') }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Product Selection Modal -->
        <Modal :show="isProductModalOpen" @close="isProductModalOpen = false" maxWidth="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-2">
                    <h2 class="text-lg font-medium text-gray-900">Select Products</h2>
                    <span class="text-sm text-gray-500">{{ form.product_ids.length }} selected</span>
                </div>
                
                <!-- Search -->
                <div class="relative mb-4">
                    <input v-model="productSearch" type="text" placeholder="Search products..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>

                <!-- Select All / Clear All -->
                <div class="flex gap-2 mb-3">
                    <button type="button" @click="selectAllProducts" class="text-xs text-amber-600 hover:underline">Select All</button>
                    <span class="text-gray-300">|</span>
                    <button type="button" @click="clearAllProducts" class="text-xs text-gray-600 hover:underline">Clear All</button>
                </div>

                <!-- Product List -->
                <div class="max-h-96 overflow-y-auto border rounded-lg divide-y">
                    <label v-for="product in filteredProducts" :key="product.id" 
                        class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" 
                            :checked="form.product_ids.includes(product.id)"
                            @change="toggleProduct(product.id)"
                            class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">{{ product.name }}</span>
                            <span v-if="product.dms_code" class="text-xs text-gray-500 ml-2">({{ product.dms_code }})</span>
                        </div>
                    </label>
                    <div v-if="filteredProducts.length === 0" class="px-4 py-8 text-center text-gray-500">
                        No products found.
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
                    <SecondaryButton @click="isProductModalOpen = false">Done</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Brand Selection Modal -->
        <Modal :show="isBrandModalOpen" @close="isBrandModalOpen = false" maxWidth="xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-2">
                    <h2 class="text-lg font-medium text-gray-900">Select Brands</h2>
                    <span class="text-sm text-gray-500">{{ form.brand_ids.length }} selected</span>
                </div>
                
                <!-- Search -->
                <div class="relative mb-4">
                    <input v-model="brandSearch" type="text" placeholder="Search brands..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>

                <!-- Select All / Clear All -->
                <div class="flex gap-2 mb-3">
                    <button type="button" @click="selectAllBrands" class="text-xs text-amber-600 hover:underline">Select All</button>
                    <span class="text-gray-300">|</span>
                    <button type="button" @click="clearAllBrands" class="text-xs text-gray-600 hover:underline">Clear All</button>
                </div>

                <!-- Brand List -->
                <div class="max-h-96 overflow-y-auto border rounded-lg divide-y">
                    <label v-for="brand in filteredBrands" :key="brand.id" 
                        class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" 
                            :checked="form.brand_ids.includes(brand.id)"
                            @change="toggleBrand(brand.id)"
                            class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="ml-3 text-sm font-medium text-gray-900">{{ brand.name }}</span>
                    </label>
                    <div v-if="filteredBrands.length === 0" class="px-4 py-8 text-center text-gray-500">
                        No brands found.
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
                    <SecondaryButton @click="isBrandModalOpen = false">Done</SecondaryButton>
                </div>
            </div>
        </Modal>
    </DashboardLayout>
</template>
