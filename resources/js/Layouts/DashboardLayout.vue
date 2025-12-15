<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';

const props = defineProps({
    sidebarOpen: {
        type: Boolean,
        default: true
    }
});

const page = usePage();
const isSidebarOpen = ref(true);
const currentTime = ref('');
const currentDate = ref('');

// User dropdown state
const isUserDropdownOpen = ref(false);
const showPasswordModal = ref(false);

// Password update form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const toggleUserDropdown = () => {
    isUserDropdownOpen.value = !isUserDropdownOpen.value;
};

const closeUserDropdown = () => {
    isUserDropdownOpen.value = false;
};

const openPasswordModal = () => {
    isUserDropdownOpen.value = false;
    showPasswordModal.value = true;
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    passwordForm.reset();
};

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordModal();
            passwordForm.reset();
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

// Navigation items
const navigation = [
    { name: 'Dashboard', href: 'dashboard', icon: 'dashboard', current: true },
    { 
        name: 'Admin', 
        icon: 'admin',
        children: [
            { name: 'Users', href: 'dashboard' },
            { name: 'Roles', href: 'dashboard' },
        ]
    },
    { 
        name: 'Order Management', 
        icon: 'orders',
        children: [
            { name: 'Orders', href: 'dashboard' },
            { name: 'New Order', href: 'dashboard' },
        ]
    },
    { 
        name: 'Stock Management', 
        icon: 'stock',
        children: [
            { name: 'Inventory', href: 'dashboard' },
            { name: 'Products', href: 'dashboard' },
        ]
    },
    { 
        name: 'Invoicing', 
        icon: 'invoice',
        children: [
            { name: 'Invoices', href: 'dashboard' },
            { name: 'Create Invoice', href: 'dashboard' },
        ]
    },
    { 
        name: 'Reports', 
        icon: 'reports',
        children: [
            { name: 'Sales Report', href: 'dashboard' },
            { name: 'Stock Report', href: 'dashboard' },
        ]
    },
];

const expandedMenus = ref([]);

const toggleMenu = (menuName) => {
    const index = expandedMenus.value.indexOf(menuName);
    if (index > -1) {
        expandedMenus.value.splice(index, 1);
    } else {
        expandedMenus.value.push(menuName);
    }
};

const isExpanded = (menuName) => expandedMenus.value.includes(menuName);

const updateDateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit',
        hour12: true 
    });
    currentDate.value = now.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

let timeInterval;
onMounted(() => {
    updateDateTime();
    timeInterval = setInterval(updateDateTime, 1000);
    
    // Close dropdown when clicking outside
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    clearInterval(timeInterval);
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
    const dropdown = document.getElementById('user-dropdown');
    const trigger = document.getElementById('user-dropdown-trigger');
    if (dropdown && trigger && !dropdown.contains(event.target) && !trigger.contains(event.target)) {
        isUserDropdownOpen.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <!-- Sidebar -->
        <aside 
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out',
                isSidebarOpen ? 'w-64' : 'w-20'
            ]"
            class="bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 shadow-2xl"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <span class="text-white font-bold text-lg">C</span>
                    </div>
                    <div v-if="isSidebarOpen" class="overflow-hidden">
                        <h1 class="text-white font-bold text-lg leading-tight">CitroPak</h1>
                        <p class="text-emerald-400 text-xs font-medium">DMS</p>
                    </div>
                </div>
                <button 
                    @click="isSidebarOpen = !isSidebarOpen"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="item in navigation" :key="item.name">
                    <!-- Single Link -->
                    <Link
                        v-if="!item.children"
                        :href="route(item.href)"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group',
                            route().current(item.href)
                                ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30'
                                : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'
                        ]"
                    >
                        <!-- Dashboard Icon -->
                        <svg v-if="item.icon === 'dashboard'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span v-if="isSidebarOpen" class="text-sm font-medium">{{ item.name }}</span>
                    </Link>

                    <!-- Dropdown Menu -->
                    <div v-else>
                        <button
                            @click="toggleMenu(item.name)"
                            :class="[
                                'w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200',
                                isExpanded(item.name)
                                    ? 'bg-slate-700/50 text-white'
                                    : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Admin Icon -->
                                <svg v-if="item.icon === 'admin'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <!-- Orders Icon -->
                                <svg v-else-if="item.icon === 'orders'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <!-- Stock Icon -->
                                <svg v-else-if="item.icon === 'stock'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <!-- Invoice Icon -->
                                <svg v-else-if="item.icon === 'invoice'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <!-- Reports Icon -->
                                <svg v-else-if="item.icon === 'reports'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span v-if="isSidebarOpen" class="text-sm font-medium">{{ item.name }}</span>
                            </div>
                            <svg 
                                v-if="isSidebarOpen"
                                :class="['w-4 h-4 transition-transform duration-200', isExpanded(item.name) ? 'rotate-180' : '']"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <!-- Submenu -->
                        <div 
                            v-if="isSidebarOpen && isExpanded(item.name)"
                            class="mt-1 ml-4 pl-4 border-l border-slate-700 space-y-1"
                        >
                            <Link
                                v-for="child in item.children"
                                :key="child.name"
                                :href="route(child.href)"
                                class="block px-3 py-2 text-sm text-slate-400 hover:text-white rounded-lg hover:bg-slate-700/30 transition-colors"
                            >
                                {{ child.name }}
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Logout -->
            <div class="p-3 border-t border-slate-700/50">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-red-500/20 hover:text-red-400 transition-all duration-200"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span v-if="isSidebarOpen" class="text-sm font-medium">Logout</span>
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="['transition-all duration-300', isSidebarOpen ? 'ml-64' : 'ml-20']">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-gray-200/50 shadow-sm">
                <div class="flex items-center justify-between h-16 px-6">
                    <!-- Logo/Brands -->
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">C</span>
                            </div>
                            <span class="font-bold text-gray-800">CitroPak</span>
                            <span class="text-emerald-500 font-semibold text-sm">DMS</span>
                        </div>
                    </div>

                    <!-- Right Side -->
                    <div class="flex items-center gap-4">
                        <!-- Date/Time -->
                        <div class="hidden md:flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium">{{ currentTime }}</span>
                            <span class="text-gray-400">|</span>
                            <span>{{ currentDate }}</span>
                        </div>

                        <!-- User Dropdown -->
                        <div class="relative pl-4 border-l border-gray-200">
                            <button 
                                id="user-dropdown-trigger"
                                @click="toggleUserDropdown"
                                class="flex items-center gap-3 cursor-pointer hover:opacity-80 transition-opacity"
                            >
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium text-sm shadow-lg shadow-indigo-500/30">
                                    {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="text-sm font-medium text-gray-800">{{ page.props.auth.user.name }}</p>
                                    <p class="text-xs text-gray-500">{{ page.props.auth.user.email }}</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" :class="{ 'rotate-180': isUserDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div 
                                v-if="isUserDropdownOpen"
                                id="user-dropdown"
                                class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50"
                            >
                                <!-- User Info -->
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-indigo-500/30">
                                            {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ page.props.auth.user.name }}</p>
                                            <p class="text-sm text-gray-500">{{ page.props.auth.user.email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <button 
                                        @click="openPasswordModal"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        <span>Update Password</span>
                                    </button>
                                </div>

                                <!-- Logout -->
                                <div class="border-t border-gray-100 pt-2">
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>Logout</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notice Ticker -->
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 text-white text-sm py-2 px-6 overflow-hidden">
                    <div class="animate-marquee whitespace-nowrap">
                        <span class="mx-8">📢 <strong>Notice:</strong> No notices to display.</span>
                        <span class="mx-8">🌤️ <strong>Weather:</strong> 16.79°C, overcast clouds</span>
                        <span class="mx-8">📢 <strong>Notice:</strong> No notices to display.</span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <slot />
            </main>
        </div>

        <!-- Password Update Modal -->
        <Teleport to="body">
            <div v-if="showPasswordModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closePasswordModal"></div>
                
                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">Update Password</h3>
                            <button @click="closePasswordModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p class="text-sm text-gray-500 mb-6">
                            Ensure your account is using a strong password to stay secure.
                        </p>

                        <!-- Form -->
                        <form @submit.prevent="updatePassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input 
                                    v-model="passwordForm.current_password"
                                    type="password" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Enter current password"
                                />
                                <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.current_password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input 
                                    v-model="passwordForm.password"
                                    type="password" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Enter new password"
                                />
                                <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input 
                                    v-model="passwordForm.password_confirmation"
                                    type="password" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Confirm new password"
                                />
                                <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                                    {{ passwordForm.errors.password_confirmation }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-4">
                                <button 
                                    type="button"
                                    @click="closePasswordModal"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 transition-colors font-medium disabled:opacity-50"
                                >
                                    {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes marquee {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

.animate-marquee {
    animation: marquee 20s linear infinite;
}
</style>
