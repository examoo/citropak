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
    products: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    name: '',
    distribution_id: '',
    start_date: '',
    end_date: '',
    scheme_type: 'product',
    product_id: '',
    brand_id: '',
    from_qty: 1,
    to_qty: '',
    pieces: '',
    free_product_code: '',
    amount_less: 0,
    status: 'active',
});

// Get product name when product selected
const selectedProduct = computed(() => {
    return props.products.find(p => p.id === form.product_id);
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
        form.start_date = item.start_date?.split('T')[0] || item.start_date;
        form.end_date = item.end_date?.split('T')[0] || item.end_date;
        form.scheme_type = item.scheme_type;
        form.product_id = item.product_id;
        form.brand_id = item.brand_id;
        form.from_qty = item.from_qty;
        form.to_qty = item.to_qty;
        form.pieces = item.pieces;
        form.free_product_code = item.free_product_code;
        form.amount_less = item.amount_less;
        form.status = item.status;
    } else {
        form.reset();
        form.scheme_type = 'product';
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
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Product/Brand</th>
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
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', item.scheme_type === 'product' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700']">
                                    {{ item.scheme_type === 'product' ? 'Product' : 'Brand' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ item.scheme_type === 'product' ? (item.product?.name || '-') : (item.brand?.name || '-') }}
                            </td>
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
                        <tr v-if="schemes.data.length === 0"><td :colspan="!currentDistribution?.id ? 8 : 7" class="px-6 py-12 text-center text-gray-500">No discount schemes found.</td></tr>
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
                    <!-- Row 1: Name, Distribution, Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                <span>Apply to Product</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.scheme_type" value="brand" class="text-amber-600 focus:ring-amber-500">
                                <span>Apply to Brand</span>
                            </label>
                        </div>
                    </div>

                    <!-- Row 3: Product or Brand based on type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="form.scheme_type === 'product'">
                            <InputLabel value="Product" />
                            <SearchableSelect 
                                v-model="form.product_id"
                                :options="products"
                                option-value="id"
                                option-label="name"
                                placeholder="Select Product"
                                class="mt-1"
                                :error="form.errors.product_id"
                            />
                            <div v-if="selectedProduct" class="text-xs text-gray-500 mt-1">Code: {{ selectedProduct.dms_code }}</div>
                        </div>
                        <div v-else>
                            <InputLabel value="Brand" />
                            <SearchableSelect 
                                v-model="form.brand_id"
                                :options="brands"
                                option-value="id"
                                option-label="name"
                                placeholder="Select Brand"
                                class="mt-1"
                                :error="form.errors.brand_id"
                            />
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
    </DashboardLayout>
</template>
