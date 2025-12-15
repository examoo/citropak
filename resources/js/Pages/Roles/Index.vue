<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const props = defineProps({
    roles: {
        type: Array,
        required: true
    }
});

const { can, isSuperAdmin } = usePermissions();

const deleteRole = (role) => {
    if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
        router.delete(route('roles.destroy', role.id));
    }
};
</script>

<template>
    <Head title="Roles Management" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Roles Management</h1>
                    <p class="text-gray-500 mt-1">Manage user roles and their permissions</p>
                </div>
                <Link 
                    v-if="can('roles.create')"
                    :href="route('roles.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-200 hover:-translate-y-0.5"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Role
                </Link>
            </div>

            <!-- Roles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="role in roles" 
                    :key="role.id"
                    class="group relative bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                >
                    <!-- System Role Badge -->
                    <div 
                        v-if="['superadmin', 'admin', 'customer'].includes(role.name)"
                        class="absolute top-4 right-4"
                    >
                        <span class="px-2.5 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                            System Role
                        </span>
                    </div>

                    <!-- Role Info -->
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ role.name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ role.permissions_count }} permissions
                            </p>
                        </div>
                    </div>

                    <!-- Permissions Preview -->
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <template v-for="(permission, index) in role.permissions.slice(0, 4)" :key="permission.id">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">
                                {{ permission.name }}
                            </span>
                        </template>
                        <span 
                            v-if="role.permissions.length > 4"
                            class="px-2 py-0.5 bg-indigo-100 text-indigo-600 rounded text-xs font-medium"
                        >
                            +{{ role.permissions.length - 4 }} more
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="mt-5 pt-4 border-t border-gray-100 flex gap-2">
                        <Link 
                            v-if="can('roles.edit')"
                            :href="route('roles.edit', role.id)"
                            class="flex-1 py-2 px-3 text-center text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                        >
                            Edit
                        </Link>
                        <button 
                            v-if="can('roles.delete') && !['superadmin', 'admin', 'customer'].includes(role.name)"
                            @click="deleteRole(role)"
                            class="flex-1 py-2 px-3 text-center text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="roles.length === 0" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No roles found</h3>
                <p class="text-gray-500 mt-1">Get started by creating a new role.</p>
            </div>
        </div>
    </DashboardLayout>
</template>
