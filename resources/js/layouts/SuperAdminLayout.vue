<template>
    <div class="min-h-screen flex bg-slate-50 dark:bg-slate-900 transition-colors">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 dark:bg-slate-950 flex flex-col shrink-0">
            <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-800">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center shrink-0">
                    <span class="text-white font-bold text-sm">SA</span>
                </div>
                <span class="text-base font-bold text-white">Super Admin</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1">
                <router-link v-for="item in navItems" :key="item.to" :to="item.to"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="isActive(item.to) ? 'bg-red-600/20 text-red-400 font-medium' : 'text-slate-400 hover:bg-slate-800 hover:text-white'">
                    <span class="w-5 h-5 flex items-center justify-center" v-html="item.svg"></span>
                    {{ item.label }}
                </router-link>
            </nav>

            <div class="border-t border-slate-800 p-3">
                <router-link to="/" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-500 hover:text-indigo-400 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to App
                </router-link>
                <button @click="logout" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-500 hover:text-red-400 rounded-lg hover:bg-slate-800 transition mt-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-8 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white capitalize">{{ currentRoute }}</h2>
                <div class="flex items-center gap-3">
                    <SBadge color="red" size="md">SUPER ADMIN</SBadge>
                    <span class="text-sm text-slate-400">{{ auth.user?.name }}</span>
                </div>
            </header>
            <main class="flex-1 p-8 overflow-y-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const currentRoute = computed(() => (route.name || '').replace('admin-', '').replace(/-/g, ' '));

function isActive(to) { return route.path === to || (to !== '/admin' && route.path.startsWith(to)); }
async function logout() { await auth.logout(); router.push('/login'); }

const svgIcons = {
    dashboard: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-2a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"/></svg>',
    tenants: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
    users: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
    plans: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
    logs: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
};

const navItems = [
    { to: '/admin', label: 'Dashboard', svg: svgIcons.dashboard },
    { to: '/admin/tenants', label: 'Tenants', svg: svgIcons.tenants },
    { to: '/admin/users', label: 'Users', svg: svgIcons.users },
    { to: '/admin/plans', label: 'Plans', svg: svgIcons.plans },
    { to: '/admin/impersonation-logs', label: 'Impersonation Logs', svg: svgIcons.logs },
];
</script>
