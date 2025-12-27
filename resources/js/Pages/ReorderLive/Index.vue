<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    reorderData: Array,
    vans: Array,
    filters: Object,
});

const form = ref({
    van: props.filters.van || '',
    days: props.filters.days || 30,
});

const search = () => {
    router.get(route('reorder-live.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const daysOptions = [
    { value: 7, label: '7 days' },
    { value: 14, label: '14 days' },
    { value: 30, label: '30 days' },
    { value: 60, label: '60 days' },
    { value: 90, label: '90 days' },
];
</script>

<template>

    <Head title="Reorder Live" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-cyan-600 to-teal-600 text-white p-4 rounded-xl shadow-lg">
                <h1 class="text-xl font-bold">🔄 Reorder Live</h1>
                <p class="text-cyan-100 text-sm">Track products that need reordering for customers</p>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <InputLabel value="Select VAN" />
                        <SearchableSelect v-model="form.van" :options="vans" option-value="code" option-label="name"
                            placeholder="Select a VAN..." class="mt-1" />
                    </div>
                    <div class="min-w-[150px]">
                        <InputLabel value="Days Since Last Order" />
                        <select v-model="form.days"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-cyan-500 focus:border-cyan-500">
                            <option v-for="opt in daysOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <PrimaryButton @click="search" class="bg-cyan-600 hover:bg-cyan-700">
                        Search
                    </PrimaryButton>
                </div>
            </div>

            <!-- Results -->
            <div v-if="filters.van" class="space-y-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-cyan-600 text-white px-4 py-3">
                        <h3 class="font-bold">Customers Needing Reorder ({{ filters.days }}+ days since last order)</h3>
                        <p class="text-cyan-100 text-sm">VAN: {{ filters.van }}</p>
                    </div>

                    <div v-if="reorderData.length === 0" class="p-8 text-center text-gray-400">
                        <div class="text-4xl mb-2">✅</div>
                        <p>All customers are up to date with their orders.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="customer in reorderData" :key="customer.id" class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="font-bold text-gray-900">{{ customer.customer_code }}</span>
                                    <span class="text-gray-600 ml-2">{{ customer.shop_name }}</span>
                                </div>
                                <span class="text-sm text-orange-600 font-medium">
                                    {{ customer.products.length }} product(s) need reorder
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="product in customer.products" :key="product.product_code"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">
                                    <span class="font-medium">{{ product.product_code }}</span>
                                    <span class="text-orange-500" v-if="product.days_since !== null">
                                        ({{ product.days_since }} days)
                                    </span>
                                    <span class="text-red-500" v-else>(Never ordered)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Van Selected -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <div class="text-6xl mb-4">🚐</div>
                <p>Select a VAN to view reorder suggestions.</p>
            </div>
        </div>
    </DashboardLayout>
</template>
