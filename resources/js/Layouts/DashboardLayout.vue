<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions.js';

const isLoading = ref(false);

router.on('start', () => isLoading.value = true);
router.on('finish', () => isLoading.value = false);


const props = defineProps({
    sidebarOpen: {
        type: Boolean,
        default: true
    }
});

const page = usePage();
const { can, canAny, isSuperAdmin } = usePermissions();
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

// Distribution toggle state
const isDistributionDropdownOpen = ref(false);

// Use computed for reactive props from Inertia
const distributions = computed(() => page.props.distributions || []);
const currentDistribution = computed(() => page.props.currentDistribution); // null = All Distributions

const toggleDistributionDropdown = () => {
    isDistributionDropdownOpen.value = !isDistributionDropdownOpen.value;
};

const switchDistribution = (distribution) => {
    const distributionId = distribution ? distribution.id : 'all';
    router.post(route('distributions.switch', distributionId), {}, {
        preserveState: false,
        onSuccess: () => {
            isDistributionDropdownOpen.value = false;
        }
    });
};

// Navigation items with permissions
const navigation = [
    { 
        name: 'Dashboard', 
        href: 'dashboard', 
        icon: 'dashboard', 
        current: true,
        permission: 'view dashboard'
    },
    { 
        name: 'Admin', 
        icon: 'admin',
        children: [
            { name: 'Users', href: 'users.index', permission: 'users.view' },
            { name: 'Roles', href: 'roles.index', permission: 'roles.view' },
            { name: 'VAN', href: 'vans.index', icon: 'truck', permission: 'vans.view' },
        ]
    },
    { 
        name: 'Order Management', 
        icon: 'orders',
        permission: 'orders.view',
        children: [
            { name: 'Orders', href: 'dashboard', permission: 'orders.view' },
            { name: 'New Order', href: 'dashboard', permission: 'orders.create' },
            { name: 'Order Bookers', href: 'order-bookers.index', icon: 'users', permission: 'order_bookers.view' },
            { name: 'Set Targets', href: 'order-booker-targets.index', icon: 'target', permission: 'order_bookers.view' },
            { name: 'Target Sheets', href: 'target-sheets.index', icon: 'clipboard', permission: 'order_bookers.view' },
            { name: 'Customer Sheets', href: 'customer-sheets.index', permission: 'order_bookers.view' },
        ]
    },
    { 
        name: 'Stock Management', 
        icon: 'stock',
        permission: 'stock.view',
        children: [
            { name: 'Opening Stocks', href: 'opening-stocks.index', permission: 'stock.view', icon: 'archive' },
            { name: 'Closing Stocks', href: 'closing-stocks.index', permission: 'stock.view', icon: 'archive' },
            { name: 'Stock In', href: 'stock-ins.index', permission: 'stock.view', icon: 'plus' },
            { name: 'Stock Out', href: 'stock-outs.index', permission: 'stock.view', icon: 'minus' },
            { name: 'Stocks', href: 'stocks.index', permission: 'stock.view', icon: 'cube' },
            { name: 'Stock Report', href: 'stock-reports.index', permission: 'stock.view', icon: 'clipboard' },
            { name: 'Low Stock Report', href: 'low-stock-reports.index', permission: 'stock.view', icon: 'clipboard' },
        ]
    },
    { 
        name: 'Product Management', 
        icon: 'box', 
        permission: 'products.view',
        children: [
            { name: 'Products', href: 'products.index', permission: 'products.view', icon: 'cube' },
            { name: 'Brands', href: 'brands.index', permission: 'products.view', icon: 'tag' },
            { name: 'Categories', href: 'product-categories.index', permission: 'products.view', icon: 'folder' },
            { name: 'Types', href: 'product-types.index', permission: 'products.view', icon: 'tag' },
            { name: 'Packings', href: 'packings.index', permission: 'products.view', icon: 'cube' },
        ]
    },
    { 
        name: 'Discount Schemes', 
        icon: 'discount', 
        href: 'discount-schemes.index',
        permission: 'products.view',
    },
    { 
        name: 'Customer Management', 
        icon: 'customer', 
        permission: 'customers.view',
        children: [
            { name: 'Customers', href: 'customers.index', icon: 'users', permission: 'customers.view' },
            { name: 'Routes', href: 'routes.index', icon: 'map', permission: 'customers.view' },
            { name: 'Channels', href: 'channels.index', icon: 'globe', permission: 'customers.view' },
            { name: 'Categories', href: 'categories.index', icon: 'tag', permission: 'customers.view' },
            { name: 'Sub Addresses', href: 'sub-addresses.index', icon: 'location', permission: 'customers.view' },
            { name: 'Sub Distributions', href: 'sub-distributions.index', icon: 'truck', permission: 'customers.view' },
        ]
    },
    { 
        name: 'Invoicing', 
        icon: 'invoice',
        permission: 'invoices.view',
        children: [
            { name: 'Good Issue Notes', href: 'good-issue-notes.index', icon: 'truck', permission: 'invoices.view' },
            { name: 'Invoices', href: 'invoices.index', icon: 'invoice', permission: 'invoices.view' },
            { name: 'Create Invoice', href: 'invoices.create', icon: 'plus', permission: 'invoices.create' },
            { name: 'Schemes', href: 'schemes.index', icon: 'percent', permission: 'invoices.view' },
        ]
    },
    { 
        name: 'Reports', 
        icon: 'reports',
        permission: 'reports.view',
        children: [
            { name: 'Sales Report', href: 'dashboard', permission: 'reports.view' },
            { name: 'Stock Report', href: 'stock-reports.index', permission: 'reports.view' },
        ]
    },
    { 
        name: 'Distributions', 
        href: 'distributions.index', 
        icon: 'globe', 
        permission: 'users.view',
        // Only show if no distribution is selected (Global view)
        show: (props) => !props.currentDistribution?.id,
    },
    { 
        name: 'Holidays', 
        href: 'holidays.index', 
        icon: 'calendar', 
        permission: 'users.view'
    },
];

// Filter navigation based on permissions
const filteredNavigation = computed(() => {
    return navigation.filter(item => {
        // Check if user has permission for this item
        if (!hasPermission(item.permission)) {
            return false;
        }

        // Check custom show logic
        if (item.show && !item.show(page.props)) {
            return false;
        }
        
        // Filter children if they exist
        if (item.children) {
            const filteredChildren = item.children.filter(child => 
                hasPermission(child.permission)
            );
            // Only show parent if at least one child is visible
            if (filteredChildren.length === 0) {
                return false;
            }
            // Replace children with filtered children
            item.children = filteredChildren;
        }
        
        return true;
    });
});

// Check permission helper
const hasPermission = (permission) => {
    if (!permission) return true;
    return can(permission);
};

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
                'fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out print:hidden',
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
                <template v-for="item in filteredNavigation" :key="item.name">
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
                        <!-- Globe Icon (Distributions) -->
                        <svg v-else-if="item.icon === 'globe'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <!-- Calendar Icon (Holidays) -->
                        <svg v-else-if="item.icon === 'calendar'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Discount Icon -->
                        <svg v-else-if="item.icon === 'discount'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M17 17h.01M7 17L17 7M10 7a3 3 0 11-6 0 3 3 0 016 0zM20 17a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span v-if="isSidebarOpen" class="text-sm font-medium whitespace-nowrap">{{ item.name }}</span>
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
                                <!-- Box Icon (Product Management) -->
                                <svg v-else-if="item.icon === 'box'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <!-- Settings Icon -->
                                <svg v-else-if="item.icon === 'settings'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <!-- Customer Icon -->
                                <svg v-else-if="item.icon === 'customer'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span v-if="isSidebarOpen" class="text-sm font-medium whitespace-nowrap">{{ item.name }}</span>
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
                                :href="route(child.href, child.params)"
                                class="flex items-center gap-3 px-3 py-2 text-sm text-slate-400 hover:text-white rounded-lg hover:bg-slate-700/30 transition-colors"
                            >
                                <!-- Cube Icon (Products) -->
                                <svg v-if="child.icon === 'cube'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <!-- Tag Icon (Types/Category) -->
                                <svg v-else-if="child.icon === 'tag'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <!-- Users Icon -->
                                <svg v-else-if="child.icon === 'users'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <!-- Document Icon -->
                                <svg v-else-if="child.icon === 'document'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <!-- Globe Icon (Channel) -->
                                <svg v-else-if="child.icon === 'globe'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                                <!-- Truck Icon (Distribution) -->
                                <svg v-else-if="child.icon === 'truck'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <!-- Map Icon (Routes) -->
                                <svg v-else-if="child.icon === 'map'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <!-- Folder Icon (Categories) -->
                                <svg v-else-if="child.icon === 'folder'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <!-- Percent Icon (Schemes) -->
                                <svg v-else-if="child.icon === 'percent'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M17 17h.01M7 17L17 7M10 7a3 3 0 11-6 0 3 3 0 016 0zM20 17a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <!-- Calendar Icon (Holidays) -->
                                <svg v-else-if="child.icon === 'calendar'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <!-- Target Icon (Set Targets) -->
                                <svg v-else-if="child.icon === 'target'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <!-- Default Dot for other children -->
                                <div v-else class="w-1.5 h-1.5 rounded-full bg-slate-600 group-hover:bg-slate-400"></div>

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
        <div :class="['transition-all duration-300 print:ml-0', isSidebarOpen ? 'ml-64' : 'ml-20']">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-gray-200/50 shadow-sm print:hidden">
                <div class="flex items-center justify-between h-16 px-6">
                    <!-- Logo/Brands + Distribution Toggle -->
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">C</span>
                            </div>
                            <span class="font-bold text-gray-800">CitroPak</span>
                        </div>
                        
                        <!-- Distribution Switcher -->
                        <div class="relative" v-if="distributions.length > 0">
                            <button 
                                @click="!page.props.auth.user.distribution_id ? toggleDistributionDropdown() : null"
                                :class="[
                                    'flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium shadow-md transition-all',
                                    currentDistribution && currentDistribution.id 
                                        ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white'
                                        : 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white',
                                    !page.props.auth.user.distribution_id ? 'hover:shadow-lg cursor-pointer' : 'cursor-default opacity-90'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>{{ currentDistribution && currentDistribution.code ? currentDistribution.code : 'ALL' }}</span>
                                <svg v-if="!page.props.auth.user.distribution_id" class="w-3 h-3" :class="{ 'rotate-180': isDistributionDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- Dropdown -->
                            <div 
                                v-if="isDistributionDropdownOpen"
                                class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50"
                            >
                                <div class="px-3 py-2 border-b border-gray-100">
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Switch Distribution</p>
                                </div>
                                <!-- All Distributions Option -->
                                <button
                                    @click="switchDistribution(null)"
                                    :class="[
                                        'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors',
                                        !currentDistribution || !currentDistribution.id
                                            ? 'bg-purple-50 text-purple-700' 
                                            : 'text-gray-700 hover:bg-gray-50'
                                    ]"
                                >
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-semibold">ALL</span>
                                    <span>All Distributions</span>
                                    <svg v-if="!currentDistribution || !currentDistribution.id" class="w-4 h-4 ml-auto text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="border-t border-gray-100 my-1"></div>
                                <!-- Distribution Options -->
                                <button
                                    v-for="dist in distributions"
                                    :key="dist.id"
                                    @click="switchDistribution(dist)"
                                    :class="[
                                        'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors',
                                        currentDistribution && currentDistribution.id === dist.id 
                                            ? 'bg-emerald-50 text-emerald-700' 
                                            : 'text-gray-700 hover:bg-gray-50'
                                    ]"
                                >
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-semibold">{{ dist.code }}</span>
                                    <span>{{ dist.name }}</span>
                                    <svg v-if="currentDistribution && currentDistribution.id === dist.id" class="w-4 h-4 ml-auto text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
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
            <main class="p-6 relative min-h-[calc(100vh-4rem)]">
                <!-- Global Page Loading Overlay (Spinner) -->
                <div v-if="isLoading" class="absolute inset-0 z-50 flex items-center justify-center bg-slate-50/50 backdrop-blur-sm transition-opacity duration-300">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-10 h-10 border-4 border-emerald-500/30 border-t-emerald-600 rounded-full animate-spin"></div>
                        <span class="text-sm font-medium text-emerald-600 animate-pulse">Loading...</span>
                    </div>
                </div>

                <Transition
                    enter-active-class="transition ease-out duration-300 transform"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200 transform"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-4"
                    mode="out-in"
                >
                    <div :key="$page.url">
                        <slot />
                    </div>
                </Transition>
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
