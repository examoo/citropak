<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { watch, ref } from 'vue';

const props = defineProps({
    show: Boolean,
    item: Object,
    customers: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    status: 'active',
    customer_id: ''
});

const isEditing = ref(false);

watch(() => props.item, (newItem) => {
    if (newItem) {
        isEditing.value = true;
        form.name = newItem.name;
        form.status = newItem.status;
        form.customer_id = newItem.customer_id || '';
    } else {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
        form.customer_id = '';
    }
}, { immediate: true });

watch(() => props.show, (newShow) => {
    if (newShow && !props.item) {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
        form.customer_id = '';
    }
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('shelves.update', props.item.id), {
            onSuccess: () => {
                emit('saved', props.item);
                emit('close');
            },
        });
    } else {
        form.post(route('shelves.store'), {
            onSuccess: () => {
                emit('saved', null);
                emit('close');
            },
        });
    }
};

const close = () => {
    emit('close');
    form.clearErrors();
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ isEditing ? 'Edit Shelf' : 'Add New Shelf' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel value="Name" />
                    <TextInput 
                        v-model="form.name" 
                        type="text" 
                        class="mt-1 block w-full" 
                        placeholder="e.g. Shelf A-1"
                        required
                    />
                    <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <InputLabel value="Assign to Customer" />
                    <select 
                        v-model="form.customer_id" 
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">None (Unassigned)</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }} ({{ customer.code }})
                        </option>
                    </select>
                    <div v-if="form.errors.customer_id" class="text-xs text-red-600 mt-1">{{ form.errors.customer_id }}</div>
                </div>

                <div>
                    <InputLabel value="Status" />
                    <select 
                        v-model="form.status" 
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <div v-if="form.errors.status" class="text-xs text-red-600 mt-1">{{ form.errors.status }}</div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="close">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
