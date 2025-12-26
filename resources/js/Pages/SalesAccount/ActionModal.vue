<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    type: String, // 'Credit' or 'Recovery'
    invoice: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    amount: '',
    type: '', // Will be set on open
});

// Reset and setup form when modal opens or invoice changes
const title = computed(() => props.type === 'Credit' ? 'Credit Entry' : 'Recovery Entry');

const submit = () => {
    if (props.type === 'Credit') {
        form.post(route('invoices.mark-credit', props.invoice.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.transform((data) => ({
            ...data,
            invoice_id: props.invoice.id,
            recovery_date: data.date,
        })).post(route('recoveries.store'), {
            onSuccess: () => emit('close'),
        });
    }
};

// Initialize form values when invoice changes
import { watch } from 'vue';
watch(() => props.invoice, (newInv) => {
    if (newInv) {
        // For Recovery, start with empty amount; for Credit, use net sale
        form.amount = props.type === 'Recovery' ? '' : newInv.net_monetary_sale;
        form.type = props.type;
    }
}, { immediate: true });

watch(() => props.type, (newType) => {
    form.type = newType;
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900">{{ title }}</h2>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <span class="text-xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">

                <!-- RECOVERY MODE FIELDS -->
                <div v-if="props.type === 'Recovery'" class="space-y-4">
                    <!-- Customer Name -->
                    <div>
                        <InputLabel value="Customer Name" />
                        <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-700">
                            {{ invoice?.customer_name || '-' }}
                        </div>
                    </div>

                    <!-- Select Invoice (Readonly dropdown look-alike for now since we select from row) -->
                    <!-- TBD: If we want to allow selecting OTHER invoices, we need a list of invoices for this customer.
                         For now, adhering to row-click context, we show the current invoice. -->
                    <div>
                        <InputLabel value="Select Invoice" />
                        <div class="relative">
                            <select disabled
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md bg-gray-50 text-gray-700">
                                <option>Invoice No: {{ invoice?.invoice_number }} (Balance: {{ invoice?.balance }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Outstanding Amount -->
                    <div>
                        <InputLabel value="Outstanding Amount for Selected Invoice" />
                        <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-700">
                            {{ invoice?.balance }}
                        </div>
                    </div>

                    <!-- Recovery Amount -->
                    <div>
                        <InputLabel value="Recovery Amount" />
                        <TextInput type="number" step="0.01" v-model="form.amount" class="mt-1 block w-full"
                            placeholder="Enter amount" />
                    </div>

                    <!-- Recovery Date -->
                    <div>
                        <InputLabel value="Recovery Date" />
                        <TextInput type="date" v-model="form.date" class="mt-1 block w-full" />
                    </div>
                </div>

                <!-- CREDIT MODE FIELDS (Original) -->
                <div v-else class="space-y-4">
                    <!-- Date -->
                    <div>
                        <InputLabel value="Date" />
                        <TextInput type="date" v-model="form.date" class="mt-1 block w-full" />
                    </div>

                    <!-- Customer Code -->
                    <div>
                        <InputLabel value="Customer Code" />
                        <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-700">
                            {{ invoice?.customer_code || '-' }}
                        </div>
                    </div>

                    <!-- Customer Name -->
                    <div>
                        <InputLabel value="Customer Name" />
                        <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-700">
                            {{ invoice?.customer_name || '-' }}
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <InputLabel value="Address" />
                        <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-700">
                            {{ invoice?.customer_address || '-' }}
                        </div>
                    </div>

                    <!-- Amount -->
                    <div>
                        <InputLabel value="Amount" />
                        <TextInput type="number" step="0.01" v-model="form.amount" class="mt-1 block w-full" />
                    </div>

                    <!-- Type -->
                    <div>
                        <InputLabel value="Type" />
                        <select v-model="form.type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <SecondaryButton @click="emit('close')">Close</SecondaryButton>
                    <PrimaryButton :disabled="form.processing"
                        :class="props.type === 'Recovery' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-blue-600 hover:bg-blue-700'">
                        {{ props.type === 'Recovery' ? 'Save Recovery' : 'Save changes' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
