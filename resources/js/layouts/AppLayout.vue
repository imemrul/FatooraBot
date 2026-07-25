<template>
    <div class="min-h-screen flex bg-slate-50 dark:bg-slate-900 transition-colors">
        <!-- Sidebar overlay (mobile) -->
        <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', collapsed ? 'lg:w-[72px]' : 'lg:w-64']"
            class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col transition-all duration-200">

            <!-- Brand -->
            <div class="h-16 px-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-700 shrink-0">
                <router-link to="/" class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shrink-0">
                        <span class="text-white font-bold text-sm">F</span>
                    </div>
                    <span v-if="!collapsed" class="text-base font-bold text-slate-900 dark:text-white truncate">FatooraBot</span>
                </router-link>
                <button @click="collapsed = !collapsed" class="hidden lg:flex text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="collapsed ? 'M13 5l7 7-7 7' : 'M11 19l-7-7 7-7'" /></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto">
                <template v-for="(item, idx) in visibleNav" :key="idx">
                    <p v-if="item.section && !collapsed" class="px-3 pt-5 pb-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                        {{ item.section }}
                    </p>
                    <div v-else-if="item.section && collapsed" class="my-2 mx-2 border-t border-slate-100 dark:border-slate-700" />
                    <router-link
                        v-else
                        :to="item.to"
                        :title="collapsed ? item.label : ''"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] transition-colors"
                        :class="isActive(item.to)
                            ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <span class="w-5 h-5 flex items-center justify-center shrink-0" v-html="item.svg" />
                        <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                    </router-link>
                </template>
            </nav>

            <!-- User footer -->
            <div class="border-t border-slate-100 dark:border-slate-700 p-3 shrink-0">
                <div v-if="!collapsed" class="flex items-center gap-3 px-2 mb-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                        {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ auth.user?.name }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate capitalize">{{ displayRole }}</p>
                    </div>
                </div>
                <button @click="handleLogout"
                    :class="collapsed ? 'justify-center' : ''"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 rounded-lg transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span v-if="!collapsed">Sign out</span>
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="h-16 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-4 lg:px-8 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden text-slate-500 dark:text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <GlobalSearch class="hidden md:block" />
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-sm text-slate-400 dark:text-slate-500 hidden sm:block">{{ auth.company?.name }}</p>
                    <NotificationBell />
                    <!-- Dark mode toggle -->
                    <button @click="toggle" class="p-2 rounded-lg text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Toggle theme">
                        <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                </div>
            </header>

            <!-- Email verification banner -->
            <SAlert v-if="auth.user && !auth.emailVerified" variant="warning" class="mx-4 lg:mx-8 mt-4">
                Please verify your email address.
                <button @click="resendVerification" :disabled="resending" class="underline font-medium ml-1">
                    {{ resending ? 'Sending...' : 'Resend' }}
                </button>
            </SAlert>

            <!-- Page content -->
            <main class="flex-1 p-4 lg:p-8 overflow-y-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { usePermission } from '@/composables/usePermission';
import { useTheme } from '@/composables/useTheme';

import NotificationBell from '@/components/NotificationBell.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const { can } = usePermission();
const { isDark, toggle } = useTheme();
const resending = ref(false);
const sidebarOpen = ref(false);
const collapsed = ref(false);

const currentRoute = computed(() => route.name === 'dashboard' ? 'Dashboard' : (route.name || '').replace(/-/g, ' '));
const displayRole = computed(() => (auth.userRoles[0] || '').replace(/_/g, ' '));

function isActive(to) { return route.path === to || (to !== '/' && route.path.startsWith(to)); }

// SVG icons for each nav item
const svgIcons = {
    dashboard: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-2a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"/></svg>',
    invoices: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    salesOrders: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>',
    whatsapp: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
    customers: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    products: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    inventory: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>',
    settings: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    audit: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    team: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>',
    expenses: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    recurring: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
    currency: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    reports: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    quotation: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    creditNote: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>',
    delivery: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>',
    purchase: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>',
    help: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    admin: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
};

const navItems = [
    { section: 'Overview' },
    { to: '/', label: 'Dashboard', svg: svgIcons.dashboard, permission: null },

    { section: 'Sales & Finance' },
    { to: '/quotations', label: 'Quotations', svg: svgIcons.quotation, permission: 'manage_invoices' },
    { to: '/invoices', label: 'Invoices', svg: svgIcons.invoices, permission: 'view_invoices' },
    { to: '/credit-notes', label: 'Credit Notes', svg: svgIcons.creditNote, permission: 'manage_invoices' },
    { to: '/recurring-invoices', label: 'Recurring', svg: svgIcons.recurring, permission: 'manage_invoices' },
    { to: '/sales-orders', label: 'Sales Orders', svg: svgIcons.salesOrders, permission: 'manage_invoices' },
    { to: '/delivery-notes', label: 'Delivery Notes', svg: svgIcons.delivery, permission: 'manage_invoices' },
    { to: '/expenses', label: 'Expenses', svg: svgIcons.expenses, permission: 'manage_invoices' },
    { to: '/whatsapp-order', label: 'WhatsApp Order', svg: svgIcons.whatsapp, permission: 'manage_invoices' },
    { to: '/clients', label: 'Customers', svg: svgIcons.customers, permission: 'view_customers' },

    { section: 'Warehouse' },
    { to: '/products', label: 'Products', svg: svgIcons.products, permission: 'view_inventory' },
    { to: '/inventory', label: 'Inventory', svg: svgIcons.inventory, permission: 'view_inventory' },
    { to: '/purchase-orders', label: 'Purchase Orders', svg: svgIcons.purchase, permission: 'manage_inventory' },

    { section: 'Settings' },
    { to: '/currencies', label: 'Currencies', svg: svgIcons.currency, permission: null },
    { to: '/reports', label: 'Reports', svg: svgIcons.reports, permission: 'view_reports' },
    { to: '/team', label: 'Team', svg: svgIcons.team, permission: 'manage_users' },
    { to: '/help', label: 'Help Center', svg: svgIcons.help, permission: null },
    { to: '/settings', label: 'Company', svg: svgIcons.settings, permission: null, ownerOnly: true },
    { to: '/audit-log', label: 'Audit Log', svg: svgIcons.audit, permission: null, ownerOnly: true },
    { to: '/admin', label: 'Super Admin', svg: svgIcons.admin, permission: null, superAdminOnly: true },
];

const visibleNav = computed(() => {
    const result = [];
    let lastSection = null;
    let sectionHasItems = false;

    for (const item of navItems) {
        if (item.section) {
            if (lastSection && sectionHasItems) result.push(lastSection);
            lastSection = item;
            sectionHasItems = false;
            continue;
        }
        if (item.superAdminOnly && !auth.user?.is_super_admin) continue;
        if (item.ownerOnly && !auth.isOwner) continue;
        if (!item.permission || can(item.permission)) {
            if (lastSection && !sectionHasItems) { result.push(lastSection); sectionHasItems = true; }
            result.push(item);
        }
    }
    return result;
});

async function handleLogout() { await auth.logout(); router.push('/login'); }
async function resendVerification() {
    resending.value = true;
    try { await auth.resendVerification(); } catch {} finally { resending.value = false; }
}
</script>
