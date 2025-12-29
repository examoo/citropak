<template>
    <DashboardLayout title="FBR Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                FBR Configuration
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Distribution Selection (When Global) -->
                    <div v-if="!distribution" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Select Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Please select a distribution to configure FBR settings.</p>
                        <div class="mt-6 max-w-xs mx-auto">
                            <select @change="switchDistribution($event.target.value)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                <option value="" disabled selected>Select a Distribution</option>
                                <option v-for="dist in distributions" :key="dist.id" :value="dist.id">
                                    {{ dist.name }} ({{ dist.code }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Settings Form (When Distribution Selected) -->
                    <div v-else>
                        <!-- Distribution Header -->
                        <div class="mb-8 border-b pb-4 flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Settings for: <span class="text-emerald-600 font-bold">{{ distribution.name }}</span>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Configure Federal Board of Revenue (FBR) POS integration settings for this distribution.
                                </p>
                            </div>
                            <!-- Switcher -->
                            <div v-if="distributions && distributions.length > 0">
                                <select @change="switchDistribution($event.target.value)" class="mt-1 block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm rounded-md">
                                    <option v-for="dist in distributions" :key="dist.id" :value="dist.id" :selected="dist.id === distribution.id">
                                        {{ dist.code }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- FBR Enable Toggle -->
                            <div class="mb-6 bg-gray-50 p-4 rounded-lg flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">Enable FBR Integration</h4>
                                    <p class="text-sm text-gray-500">Turn on real-time invoice synchronization with FBR.</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.fbr_enabled" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Business Registration Details -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="col-span-1 md:col-span-2">
                                    <h4 class="text-md font-medium text-gray-900 border-b pb-2 mb-4">Business Registration</h4>
                                </div>

                                <!-- NTN -->
                                <div>
                                    <InputLabel for="ntn_number" value="NTN Number" />
                                    <TextInput id="ntn_number" v-model="form.ntn_number" type="text" class="mt-1 block w-full" placeholder="e.g. 1234567-8" />
                                    <InputError :message="form.errors.ntn_number" class="mt-2" />
                                </div>

                                <!-- STRN -->
                                <div>
                                    <InputLabel for="strn_number" value="STRN Number" />
                                    <TextInput id="strn_number" v-model="form.strn_number" type="text" class="mt-1 block w-full" placeholder="e.g. 1234567890123" />
                                    <InputError :message="form.errors.strn_number" class="mt-2" />
                                </div>

                                <!-- Business Name -->
                                <div class="col-span-1 md:col-span-2">
                                    <InputLabel for="business_name" value="Registered Business Name" />
                                    <TextInput id="business_name" v-model="form.business_name" type="text" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.business_name" class="mt-2" />
                                </div>

                                <!-- Business Address -->
                                <div class="col-span-1 md:col-span-2">
                                    <InputLabel for="business_address" value="Registered Address" />
                                    <textarea id="business_address" v-model="form.business_address" class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm" rows="2"></textarea>
                                    <InputError :message="form.errors.business_address" class="mt-2" />
                                </div>
                            </div>

                            <!-- API Configuration -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 bg-slate-50 p-6 rounded-lg border border-slate-200">
                                <div class="col-span-1 md:col-span-2 flex justify-between items-center border-b border-slate-200 pb-2 mb-4">
                                    <h4 class="text-md font-medium text-slate-800">API Configuration</h4>
                                    <span :class="{'bg-yellow-100 text-yellow-800': form.fbr_environment === 'sandbox', 'bg-green-100 text-green-800': form.fbr_environment === 'production'}" class="px-2 py-1 rounded text-xs font-bold uppercase">
                                        {{ form.fbr_environment }} Mode
                                    </span>
                                </div>

                                <!-- Environment -->
                                <div class="col-span-1 md:col-span-2">
                                    <InputLabel value="Environment" />
                                    <div class="flex space-x-4 mt-2">
                                        <label class="inline-flex items-center">
                                            <input type="radio" v-model="form.fbr_environment" value="sandbox" class="form-radio text-emerald-600">
                                            <span class="ml-2">Sandbox (Testing)</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" v-model="form.fbr_environment" value="production" class="form-radio text-emerald-600">
                                            <span class="ml-2">Production (Live)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- POS ID -->
                                <div>
                                    <InputLabel for="pos_id" value="POS ID" />
                                    <TextInput id="pos_id" v-model="form.pos_id" type="text" class="mt-1 block w-full" placeholder="Assigned by FBR" required />
                                    <InputError :message="form.errors.pos_id" class="mt-2" />
                                </div>
                                
                                <!-- Empty spacer -->
                                <div class="hidden md:block"></div>

                                <!-- Username -->
                                <div>
                                    <InputLabel for="fbr_username" value="API Username" />
                                    <TextInput id="fbr_username" v-model="form.fbr_username" type="text" class="mt-1 block w-full" :placeholder="fbrSettings?.fbr_username ? '••••••••' : 'Enter API Username'" />
                                    <p class="text-xs text-slate-500 mt-1" v-if="fbrSettings?.fbr_username">Leave blank to keep unchanged</p>
                                    <InputError :message="form.errors.fbr_username" class="mt-2" />
                                </div>

                                <!-- Password -->
                                <div>
                                    <InputLabel for="fbr_password" value="API Password" />
                                    <TextInput id="fbr_password" v-model="form.fbr_password" type="password" class="mt-1 block w-full" :placeholder="fbrSettings?.fbr_password ? '••••••••' : 'Enter API Password'" />
                                    <p class="text-xs text-slate-500 mt-1" v-if="fbrSettings?.fbr_password">Leave blank to keep unchanged</p>
                                    <InputError :message="form.errors.fbr_password" class="mt-2" />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between">
                                <span v-if="form.recentlySuccessful" class="text-sm text-gray-600 mr-3 transition ease-in-out duration-150">
                                    Saved.
                                </span>

                                <div class="flex space-x-3">
                                    <button type="button" @click="testConnection" :disabled="testing || !form.pos_id" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 active:bg-slate-900 focus:outline-none focus:border-slate-900 focus:ring focus:ring-slate-300 disabled:opacity-50 transition">
                                        <span v-if="testing">Testing...</span>
                                        <span v-else>Test Connection</span>
                                    </button>
                                    
                                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        Save Settings
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>

                        <!-- Test Result -->
                        <div v-if="testResult" :class="{'bg-green-50 border-green-200 text-green-800': testResult.success, 'bg-red-50 border-red-200 text-red-800': !testResult.success}" class="mt-6 p-4 rounded-md border">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg v-if="testResult.success" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium">{{ testResult.success ? 'Connection Successful' : 'Connection Failed' }}</h3>
                                    <div class="mt-2 text-sm">
                                        <p>{{ testResult.message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';

const props = defineProps({
    distribution: Object,
    distributions: Array,
    fbrSettings: Object,
    uomCodes: Object,
});

const form = useForm({
    distribution_id: props.distribution?.id,
    ntn_number: props.fbrSettings?.ntn_number ?? '',
    strn_number: props.fbrSettings?.strn_number ?? '',
    business_name: props.fbrSettings?.business_name ?? '',
    business_address: props.fbrSettings?.business_address ?? '',
    business_phone: props.fbrSettings?.business_phone ?? '',
    business_email: props.fbrSettings?.business_email ?? '',
    pos_id: props.fbrSettings?.pos_id ?? '',
    fbr_username: '', 
    fbr_password: '', 
    fbr_environment: props.fbrSettings?.fbr_environment || 'sandbox',
    fbr_enabled: props.fbrSettings?.fbr_enabled || false,
});

const testing = ref(false);
const testResult = ref(null);

const switchDistribution = (id) => {
    router.get(route('fbr-settings.show'), { distribution_id: id }, {
        preserveState: false, // Force component reload to reset form
    });
};

const submit = () => {
    form.put(route('fbr-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            testResult.value = null; 
            form.fbr_username = ''; 
            form.fbr_password = '';
        },
    });
};

const testConnection = async () => {
    testing.value = true;
    testResult.value = null;
    
    try {
        const response = await axios.post(route('fbr-settings.test'), {
            distribution_id: props.distribution?.id,
        });
        testResult.value = response.data;
    } catch (error) {
        testResult.value = {
            success: false,
            message: error.response?.data?.message || 'An error occurred while testing connection.',
        };
    } finally {
        testing.value = false;
    }
};
</script>
