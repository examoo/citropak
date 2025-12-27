<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    notices: Array,
});

const showModal = ref(false);
const editingNotice = ref(null);

const form = useForm({
    title: '',
    content: '',
    type: 'info',
    is_active: true,
});

const typeColors = {
    info: 'bg-blue-100 border-blue-500 text-blue-800',
    warning: 'bg-yellow-100 border-yellow-500 text-yellow-800',
    success: 'bg-green-100 border-green-500 text-green-800',
    danger: 'bg-red-100 border-red-500 text-red-800',
};

const openCreateModal = () => {
    editingNotice.value = null;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (notice) => {
    editingNotice.value = notice;
    form.title = notice.title;
    form.content = notice.content;
    form.type = notice.type;
    form.is_active = notice.is_active;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingNotice.value = null;
    form.reset();
};

const submit = () => {
    if (editingNotice.value) {
        form.put(route('notice-board.update', editingNotice.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('notice-board.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteNotice = (id) => {
    if (confirm('Are you sure you want to delete this notice?')) {
        router.delete(route('notice-board.destroy', id));
    }
};
</script>

<template>

    <Head title="Notice Board" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4 rounded-xl shadow-lg flex-1 mr-4">
                    <h1 class="text-xl font-bold">📢 Notice Board</h1>
                    <p class="text-indigo-100 text-sm">Create and manage notices for your team</p>
                </div>
                <PrimaryButton @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700">
                    + Add Notice
                </PrimaryButton>
            </div>

            <!-- Notices List -->
            <div class="space-y-4">
                <div v-for="notice in notices" :key="notice.id"
                    :class="[typeColors[notice.type], 'border-l-4 p-4 rounded-lg shadow-sm']">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-lg">{{ notice.title }}</h3>
                                <span v-if="!notice.is_active"
                                    class="px-2 py-0.5 bg-gray-200 text-gray-600 rounded text-xs">Inactive</span>
                            </div>
                            <p class="mt-2 whitespace-pre-line">{{ notice.content }}</p>
                            <p class="mt-2 text-xs opacity-60">{{ notice.created_at }}</p>
                        </div>
                        <div class="flex gap-2 ml-4">
                            <button @click="openEditModal(notice)"
                                class="text-sm px-3 py-1 bg-white/50 rounded hover:bg-white transition">Edit</button>
                            <button @click="deleteNotice(notice.id)"
                                class="text-sm px-3 py-1 bg-white/50 rounded hover:bg-red-100 text-red-600 transition">Delete</button>
                        </div>
                    </div>
                </div>

                <div v-if="notices.length === 0"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                    <div class="text-6xl mb-4">📭</div>
                    <p>No notices yet. Click "Add Notice" to create one.</p>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6">
                            {{ editingNotice ? 'Edit Notice' : 'Add Notice' }}
                        </h3>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel value="Title *" />
                                <TextInput v-model="form.title" type="text" class="mt-1 block w-full" required />
                            </div>

                            <div>
                                <InputLabel value="Content *" />
                                <textarea v-model="form.content" rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    required></textarea>
                            </div>

                            <div>
                                <InputLabel value="Type" />
                                <select v-model="form.type"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="info">Info (Blue)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="danger">Danger (Red)</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.is_active" id="is_active"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="is_active" class="text-sm text-gray-700">Active</label>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button type="button" @click="closeModal"
                                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                    Cancel
                                </button>
                                <PrimaryButton type="submit" :disabled="form.processing"
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700">
                                    {{ form.processing ? 'Saving...' : (editingNotice ? 'Update' : 'Create') }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </DashboardLayout>
</template>
