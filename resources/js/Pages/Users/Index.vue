<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions.js';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import Swal from 'sweetalert2';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';

const props = defineProps({
    users: {
        type: Object, // Changed to Object for Pagination
        required: true
    },
    roles: {
        type: Array,
        required: true
    },
    assignableDistributions: {
        type: Array,
        required: false,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: '', sort_field: 'created_at', sort_direction: 'desc' })
    }
});

const { can, isSuperAdmin } = usePermissions();
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

// Filter State
const search = ref(props.filters.search || '');
const filterRole = ref(props.filters.role || '');
const sortField = ref(props.filters.sort_field || 'created_at');
const sortDirection = ref(props.filters.sort_direction || 'desc');

// Debounced Search and Filter Watcher
watch([search, filterRole, sortField, sortDirection], debounce(() => {
    router.get(route('users.index'), {
        search: search.value,
        role: filterRole.value,
        sort_field: sortField.value,
        sort_direction: sortDirection.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

const handleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (field) => {
    if (sortField.value !== field) return '↕';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
    distribution_id: '',
});

const openModal = (user = null) => {
    isEditing.value = !!user;
    editingUserId.value = user?.id;
    
    if (user) {
        form.name = user.name;
        form.email = user.email;
        form.roles = user.roles.map(role => role.name);
        form.distribution_id = user.distribution_id || '';
        // Password fields are usually reset or left empty for edits unless changing
        form.password = '';
        form.password_confirmation = '';
    } else {
        form.reset();
    }
    
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('users.update', editingUserId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteUser = (user) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete "${user.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.destroy', user.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire(
                        'Deleted!',
                        'User has been deleted.',
                        'success'
                    )
                }
            });
        }
    })
};

const toggleRole = (roleName) => {
    const index = form.roles.indexOf(roleName);
    if (index > -1) {
        form.roles.splice(index, 1);
    } else {
        form.roles.push(roleName);
    }
};
</script>

<template>
    <Head title="Users Management" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Users</h1>
                    <p class="text-gray-500 mt-1">Manage system users and their roles</p>
                </div>
                <!-- Check explicit permissions -->
                <div class="flex items-center gap-3">
                    <!-- Search Bar -->
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search users..." 
                            class="pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 w-64 shadow-sm"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Role Filter -->
                    <select 
                        v-model="filterRole"
                        class="py-2.5 rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white shadow-sm"
                    >
                        <option value="">All Roles</option>
                        <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                    </select>

                    <button 
                        v-if="can('users.create')"
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Create User
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th @click="handleSort('name')" class="px-6 py-4 cursor-pointer hover:text-indigo-600 transition-colors">
                                    User {{ getSortIcon('name') }}
                                </th>
                                <th class="px-6 py-4">Roles</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-indigo-600 font-bold">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-gray-500 text-xs">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <span 
                                            v-for="role in user.roles" 
                                            :key="role.id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-purple-100 text-purple-800': role.name === 'superadmin',
                                                'bg-indigo-100 text-indigo-800': role.name === 'admin',
                                                'bg-emerald-100 text-emerald-800': role.name === 'manager',
                                                'bg-blue-100 text-blue-800': role.name === 'user',
                                                'bg-gray-100 text-gray-800': !['superadmin', 'admin', 'manager', 'user'].includes(role.name)
                                            }"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span v-if="user.roles.length === 0" class="text-gray-400 italic">No roles</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            v-if="can('users.edit')"
                                            @click="openModal(user)"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit User"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            v-if="can('users.delete')" 
                                            @click="deleteUser(user)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete User"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="font-medium">No users found</p>
                                        <p class="text-xs">Try adjusting your search or add a new user.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>

        <!-- User Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ isEditing ? 'Edit User' : 'Create New User' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- User Details -->
                    <div>
                        <InputLabel value="Full Name" />
                        <TextInput 
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g. John Doe"
                            required
                        />
                        <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <InputLabel value="Email Address" />
                        <TextInput 
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            placeholder="e.g. john@example.com"
                            required
                        />
                        <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}</div>
                    </div>

                    <!-- Distribution Select -->
                    <div>
                        <SearchableSelect 
                            v-model="form.distribution_id"
                            label="Distribution (Optional)"
                            :options="assignableDistributions"
                            option-value="id"
                            option-label="name"
                            placeholder="Select a distribution"
                            :error="form.errors.distribution_id"
                        />
                        <p class="text-xs text-gray-500 mt-1">Leave empty for a global user (if allowed).</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel :value="isEditing ? 'New Password (Optional)' : 'Password'" />
                            <TextInput 
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                :placeholder="isEditing ? 'Leave blank to keep' : 'Enter password'"
                                :required="!isEditing"
                            />
                            <div v-if="form.errors.password" class="text-sm text-red-600 mt-1">{{ form.errors.password }}</div>
                        </div>

                        <div>
                            <InputLabel value="Confirm Password" />
                            <TextInput 
                                v-model="form.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                placeholder="Confirm password"
                                :required="!isEditing"
                            />
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="border-t border-gray-100 pt-4 mt-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Assign Roles</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label 
                                v-for="role in roles" 
                                :key="role.id"
                                class="flex items-center p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/30 transition-all cursor-pointer group"
                                :class="{'border-indigo-500 bg-indigo-50/50': form.roles.includes(role.name)}"
                            >
                                <Checkbox 
                                    :checked="form.roles.includes(role.name)"
                                    @update:checked="toggleRole(role.name)"
                                />
                                <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-gray-900 capitalize select-none">
                                    {{ role.name }}
                                </span>
                            </label>
                        </div>
                        <div v-if="form.errors.roles" class="text-sm text-red-600 mt-1">{{ form.errors.roles }}</div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                        <PrimaryButton 
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-indigo-600 to-purple-600 border-0"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update User' : 'Create User') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardLayout>
</template>
