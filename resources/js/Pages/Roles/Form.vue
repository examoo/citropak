<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

const props = defineProps({
    role: {
        type: Object,
        default: null
    },
    permissionsGrouped: {
        type: Object,
        required: true
    },
    distributions: {
        type: Array,
        default: () => []
    }
});

const isEditing = computed(() => !!props.role);

const form = useForm({
    name: props.role?.name || '',
    permissions: props.role?.permissions || [],
    distribution_id: props.role?.distribution_id || (page.props.currentDistribution?.id || null),
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('roles.update', props.role.id));
    } else {
        form.post(route('roles.store'));
    }
};

// Check if a permission is selected
const isPermissionSelected = (permName) => {
    return form.permissions.includes(permName);
};

// Toggle permission selection
const togglePermission = (permName) => {
    const index = form.permissions.indexOf(permName);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permName);
    }
};

// Select all permissions in a module
const selectAllInModule = (module) => {
    const modulePerms = props.permissionsGrouped[module].map(p => p.name);
    const allSelected = modulePerms.every(p => form.permissions.includes(p));
    
    if (allSelected) {
        // Deselect all
        modulePerms.forEach(p => {
            const idx = form.permissions.indexOf(p);
            if (idx > -1) form.permissions.splice(idx, 1);
        });
    } else {
        // Select all
        modulePerms.forEach(p => {
            if (!form.permissions.includes(p)) {
                form.permissions.push(p);
            }
        });
    }
};

// Check if all permissions in module are selected
const isModuleFullySelected = (module) => {
    const modulePerms = props.permissionsGrouped[module].map(p => p.name);
    return modulePerms.every(p => form.permissions.includes(p));
};

// Select all permissions
const selectAll = () => {
    const allPerms = Object.values(props.permissionsGrouped).flat().map(p => p.name);
    form.permissions = allPerms;
};

// Deselect all permissions
const deselectAll = () => {
    form.permissions = [];
};

// Format module name for display
const formatModuleName = (name) => {
    return name.charAt(0).toUpperCase() + name.slice(1);
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Role' : 'Create Role'" />

    <DashboardLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link 
                    :href="route('roles.index')"
                    class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
                >
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ isEditing ? 'Edit Role' : 'Create Role' }}
                    </h1>
                    <p class="text-gray-500 mt-0.5">
                        {{ isEditing ? `Editing "${role.name}" role` : 'Create a new role with permissions' }}
                    </p>
                </div>
            </div>



            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Role Name & Distribution -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Role Information</h3>
                    
                    <div class="space-y-4">
                        <!-- Distribution Select (Only if Global View) -->
                        <div v-if="!currentDistribution?.id">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target Distribution</label>
                            <select 
                                v-model="form.distribution_id"
                                :disabled="isEditing" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <option :value="null">Global (All Distributions)</option>
                                <option v-for="dist in distributions" :key="dist.id" :value="dist.id">
                                    {{ dist.name }} ({{ dist.code }})
                                </option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                Select "Global" to make this role available everywhere, or select a specific distribution.
                            </p>
                        </div>

                        <!-- Role Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role Name</label>
                            <input 
                                v-model="form.name"
                                type="text"
                                :disabled="role?.isSystemRole"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed"
                                placeholder="Enter role name"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            <p v-if="role?.isSystemRole" class="mt-1 text-sm text-amber-600">System role names cannot be changed.</p>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Permissions</h3>
                        <div class="flex gap-2">
                            <button 
                                type="button"
                                @click="selectAll"
                                class="px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                            >
                                Select All
                            </button>
                            <button 
                                type="button"
                                @click="deselectAll"
                                class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Permissions grouped by module -->
                    <div class="space-y-6">
                        <div 
                            v-for="(permissions, module) in permissionsGrouped" 
                            :key="module"
                            class="border border-gray-200 rounded-xl overflow-hidden"
                        >
                            <!-- Module Header -->
                            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-b border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ formatModuleName(module).charAt(0) }}</span>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ formatModuleName(module) }}</span>
                                </div>
                                <button 
                                    type="button"
                                    @click="selectAllInModule(module)"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                >
                                    {{ isModuleFullySelected(module) ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>
                            
                            <!-- Permissions Grid -->
                            <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label 
                                    v-for="permission in permissions" 
                                    :key="permission.id"
                                    class="flex items-center gap-2 cursor-pointer group"
                                >
                                    <input 
                                        type="checkbox"
                                        :checked="isPermissionSelected(permission.name)"
                                        @change="togglePermission(permission.name)"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-600 group-hover:text-gray-900 capitalize">
                                        {{ permission.action }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 justify-end">
                    <Link 
                        :href="route('roles.index')"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </Link>
                    <button 
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-200 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update Role' : 'Create Role') }}
                    </button>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
