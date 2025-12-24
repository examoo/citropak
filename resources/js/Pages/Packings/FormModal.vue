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
    item: Object, // If provided, we are in edit mode
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    conversion: '1',
    units: 'pcs',
    status: 'active'
});

const isEditing = ref(false);

watch(() => props.item, (newItem) => {
    if (newItem) {
        isEditing.value = true;
        form.name = newItem.name;
        form.conversion = newItem.conversion;
        form.units = newItem.units;
        form.status = newItem.status;
    } else {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
        form.conversion = '1';
        form.units = 'pcs';
    }
}, { immediate: true });

// Also watch 'show' to reset form when opening in create mode if item is null
watch(() => props.show, (newShow) => {
    if (newShow && !props.item) {
        isEditing.value = false;
        form.reset();
        form.status = 'active';
        form.conversion = '1';
        form.units = 'pcs';
    }
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('packings.update', props.item.id), {
            onSuccess: () => {
                emit('saved', props.item);
                emit('close');
            },
        });
    } else {
        form.post(route('packings.store'), {
            onSuccess: (page) => {
                // If the controller returns the new ID in flash or if we can infer it
                emit('saved', null); // Logic to find new item might be needed in parent
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
                {{ isEditing ? 'Edit Packing' : 'Add New Packing' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel value="Name" />
                    <TextInput 
                        v-model="form.name" 
                        type="text" 
                        class="mt-1 block w-full" 
                        placeholder="e.g. Dozen, Carton"
                        required
                    />
                    <div v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Conversion Rate" />
                        <TextInput 
                            v-model="form.conversion" 
                            type="number" 
                            step="any"
                            class="mt-1 block w-full" 
                            required
                        />
                        <p class="text-xs text-gray-500 mt-1">How many units in this packing?</p>
                        <div v-if="form.errors.conversion" class="text-xs text-red-600 mt-1">{{ form.errors.conversion }}</div>
                    </div>

                    <div>
                        <InputLabel value="Unit Name" />
                        <TextInput 
                            v-model="form.units" 
                            type="text" 
                            class="mt-1 block w-full" 
                            placeholder="e.g. pcs, kg"
                            required
                        />
                         <div v-if="form.errors.units" class="text-xs text-red-600 mt-1">{{ form.errors.units }}</div>
                    </div>
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
