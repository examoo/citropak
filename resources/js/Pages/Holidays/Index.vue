<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';
import Pagination from '@/Components/Pagination.vue';
import { ref } from 'vue';

const props = defineProps({ holidays: Object, filters: Object });

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({ date: '', description: '' });

const openModal = (item = null) => {
    isEditing.value = !!item;
    editingId.value = item?.id;
    if (item) {
        form.date = item.date;
        form.description = item.description || '';
    } else {
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => { isModalOpen.value = false; form.reset(); form.clearErrors(); };

const submit = () => {
    if (!form.date) { form.setError('date', 'Required.'); return; }
    if (isEditing.value) {
        form.put(route('holidays.update', editingId.value), { onSuccess: () => closeModal() });
    } else {
        form.post(route('holidays.store'), { onSuccess: () => closeModal() });
    }
};

const deleteItem = (item) => {
    Swal.fire({ title: 'Delete?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' })
        .then((r) => { if (r.isConfirmed) router.delete(route('holidays.destroy', item.id), { preserveScroll: true }); });
};
</script>

<template>
    <Head title="Manage Holidays" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div><h1 class="text-2xl font-bold text-gray-900">Holidays</h1><p class="text-gray-500 mt-1">Manage distribution holidays</p></div>
                <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Add Holiday
                </button>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500"><tr><th class="px-6 py-4">Date</th><th class="px-6 py-4">Description</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in holidays.data" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ new Date(item.date).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ item.description || '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openModal(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button @click="deleteItem(item)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="holidays.data.length === 0"><td colspan="3" class="px-6 py-12 text-center text-gray-500">No holidays found.</td></tr>
                    </tbody>
                </table>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50"><Pagination :links="holidays.links" /></div>
            </div>
        </div>
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">{{ isEditing ? 'Edit' : 'Add' }} Holiday</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div><InputLabel value="Date" /><TextInput v-model="form.date" type="date" class="mt-1 block w-full" :class="{ 'border-red-500': form.errors.date }" /><div v-if="form.errors.date" class="text-xs text-red-600 mt-1">{{ form.errors.date }}</div></div>
                    <div><InputLabel value="Description (Optional)" /><TextInput v-model="form.description" type="text" class="mt-1 block w-full" placeholder="e.g. Eid ul Fitr" /></div>
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t"><SecondaryButton @click="closeModal">Cancel</SecondaryButton><PrimaryButton :disabled="form.processing" class="bg-gradient-to-r from-emerald-600 to-teal-600 border-0">{{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}</PrimaryButton></div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
