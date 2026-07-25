<template>
    <div class="relative">
        <button @click="open = !open" class="relative p-2 rounded-lg text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span v-if="unread > 0" class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ unread > 9 ? '9+' : unread }}</span>
        </button>

        <!-- Dropdown -->
        <div v-if="open" class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg z-50 max-h-96 overflow-y-auto">
            <div class="flex items-center justify-between px-4 py-3 border-b dark:border-slate-700">
                <span class="text-sm font-semibold dark:text-white">Notifications</span>
                <button v-if="unread > 0" @click="markAllRead" class="text-xs text-indigo-600 hover:underline">Mark all read</button>
            </div>
            <div v-if="notifications.length === 0" class="p-6 text-center text-sm text-slate-400">No notifications</div>
            <div v-for="n in notifications" :key="n.id" @click="handleClick(n)"
                class="px-4 py-3 border-b dark:border-slate-700/50 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50 transition"
                :class="{ 'bg-indigo-50/50 dark:bg-indigo-900/10': !n.read_at }">
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ n.title }}</p>
                <p v-if="n.body" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ n.body }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ timeAgo(n.created_at) }}</p>
            </div>
        </div>

        <!-- Click outside -->
        <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';

const router = useRouter();
const open = ref(false);
const unread = ref(0);
const notifications = ref([]);
let pollInterval;

function timeAgo(date) {
    const s = Math.floor((Date.now() - new Date(date)) / 1000);
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s / 60) + 'm ago';
    if (s < 86400) return Math.floor(s / 3600) + 'h ago';
    return Math.floor(s / 86400) + 'd ago';
}

async function fetchUnread() {
    try { const { data } = await api.get('/notifications/unread-count'); unread.value = data.count; } catch {}
}

async function fetchNotifications() {
    try { const { data } = await api.get('/notifications'); notifications.value = data.data; } catch {}
}

async function markAllRead() {
    await api.post('/notifications/read-all');
    unread.value = 0;
    notifications.value.forEach(n => n.read_at = new Date().toISOString());
}

async function handleClick(n) {
    if (!n.read_at) {
        await api.post(`/notifications/${n.id}/read`);
        n.read_at = new Date().toISOString();
        unread.value = Math.max(0, unread.value - 1);
    }
    if (n.action_url) { open.value = false; router.push(n.action_url); }
}

onMounted(() => {
    fetchUnread();
    fetchNotifications();
    pollInterval = setInterval(fetchUnread, 30000);
});
onUnmounted(() => clearInterval(pollInterval));
</script>
