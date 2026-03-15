<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    flash: Object,
    errors: Object,
});

const file = ref(null);
const importing = ref(false);
const fileInput = ref(null);

const pickFile = (e) => {
    file.value = e.target.files[0] || null;
};

const submitImport = () => {
    if (!file.value) return;
    importing.value = true;

    const form = new FormData();
    form.append('file', file.value);

    router.post(route('credit-management.entries.import'), form, {
        forceFormData: true,
        onFinish: () => { importing.value = false; },
    });
};

const downloadTemplate = () => {
    window.location.href = route('credit-management.entries.import.template');
};
</script>

<template>
    <Head title="Import Credit Entries" />

    <DashboardLayout>
        <div class="space-y-6 max-w-2xl mx-auto py-8">

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-6 rounded-2xl shadow-xl">
                <h1 class="text-2xl font-bold">Import Credit Entries</h1>
                <p class="text-blue-100 text-sm mt-1">
                    Upload your credit data Excel sheet to sync with Invoices and Recoveries.
                </p>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-3 transition-all">
                <div class="bg-green-100 p-1 rounded-full">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-sm font-medium">{{ flash.success }}</span>
            </div>
            
            <div v-if="flash?.error"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium">
                {{ flash.error }}
            </div>

            <!-- Expected Columns Info -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5">
                <h3 class="font-bold text-indigo-900 mb-3 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Expected Excel Columns
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <div v-for="col in ['Van', 'OrderBookers', 'Bill#', 'DATE', 'Customer Code', 'CUSTOMERS', 'ADDRESS', 'TYPE', 'BALANCE', 'Recovery']" 
                         :key="col"
                         class="bg-white px-2 py-1 rounded border border-indigo-200 text-[11px] font-mono text-indigo-700 shadow-sm">
                        {{ col }}
                    </div>
                </div>
                <p class="text-[10px] text-indigo-500 mt-4 leading-relaxed font-medium italic">
                    Note: Column order is flexible. Date should be in standard Excel format or YYYY-MM-DD.
                </p>
            </div>

            <!-- Upload Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8">
                    <div
                        class="relative group border-2 border-dashed border-gray-200 rounded-2xl p-10 hover:border-blue-400 hover:bg-blue-50/30 transition-all cursor-pointer text-center"
                        @click="fileInput?.click()"
                    >
                        <div class="space-y-4">
                            <div class="bg-blue-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto group-hover:scale-110 transition-transform shadow-inner">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-gray-900 font-semibold">
                                    {{ file ? file.name : 'Select Excel Document' }}
                                </h4>
                                <p class="text-gray-500 text-xs mt-1">
                                    Drag and drop or click to browse
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="pickFile"
                    />
                    
                    <p v-if="errors?.file" class="mt-3 text-xs text-red-600 font-medium flex items-center gap-1">
                         <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" /></svg>
                        {{ errors.file }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-center gap-4 mt-8 pt-4 border-t border-gray-50">
                        <PrimaryButton
                            @click="submitImport"
                            :disabled="!file || importing"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 disabled:opacity-50 shadow-lg shadow-blue-200"
                        >
                            <span v-if="importing" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                Importing Data...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                Start Import
                            </span>
                        </PrimaryButton>

                        <button
                            @click="downloadTemplate"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 transition-all"
                        >
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Download Template
                        </button>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a :href="route('credit-management.entries')" class="text-sm font-semibold text-gray-400 hover:text-blue-600 transition-colors">
                    ← Return to Credit Entries
                </a>
            </div>

        </div>
    </DashboardLayout>
</template>
