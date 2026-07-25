<template>
    <div>
        <SPageHeader title="Audit Log" subtitle="Track all system changes" />

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <SStatCard label="Today" :value="String(stats.today_count ?? 0)" icon="&#128197;" iconBg="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400" />
            <SStatCard label="Created" :value="String(stats.by_action?.created ?? 0)" icon="&#10133;" iconBg="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400" valueColor="text-emerald-600 dark:text-emerald-400" />
            <SStatCard label="Updated" :value="String(stats.by_action?.updated ?? 0)" icon="&#9998;" iconBg="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400" valueColor="text-blue-600 dark:text-blue-400" />
            <SStatCard label="Deleted" :value="String(stats.by_action?.deleted ?? 0)" icon="&#128465;" iconBg="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400" valueColor="text-red-600 dark:text-red-400" />
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-4">
            <input v-model="filters.search" placeholder="Search label or user..." @input="debounceLoad"
                class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3 py-1.5 text-sm w-56 outline-none focus:ring-2 focus:ring-indigo-500" />
            <select v-model="filters.model" @change="load"
                class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Models</option>
                <option v-for="m in models" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select v-model="filters.action" @change="load"
                class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
            </select>
            <input v-model="filters.from" type="date" @change="load"
                class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
            <input v-model="filters.to" type="date" @change="load"
                class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <SCard noPad>
            <STable :columns="[{key:'x',label:''},{key:'time',label:'Time'},{key:'user',label:'User'},{key:'action',label:'Action'},{key:'model',label:'Model'},{key:'record',label:'Record'},{key:'changes',label:'Changes'}]" :empty="!logs.length" emptyText="No audit logs found.">
                <template v-for="log in logs" :key="log.id">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 cursor-pointer transition-colors" @click="toggle(log.id)">
                        <td class="px-5 py-3 text-slate-400 dark:text-slate-500 w-8">{{ expanded === log.id ? '▾' : '▸' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">{{ fmtTime(log.created_at) }}</td>
                        <td class="px-5 py-3 text-slate-900 dark:text-white">{{ log.user_name }}</td>
                        <td class="px-5 py-3"><SBadge :color="actionColor(log.action)">{{ log.action }}</SBadge></td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ log.model }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-slate-700 dark:text-slate-300">{{ log.label }}</td>
                        <td class="px-5 py-3 text-xs text-slate-400 dark:text-slate-500">{{ log.changed_fields?.join(', ') || '—' }}</td>
                    </tr>
                    <tr v-if="expanded === log.id" class="bg-slate-50 dark:bg-slate-700/30">
                        <td colspan="7" class="px-5 py-4">
                            <div class="grid grid-cols-2 gap-4 max-w-3xl">
                                <div v-if="log.old_values">
                                    <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1">Old Values</p>
                                    <pre class="text-xs bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 rounded-lg p-3 overflow-x-auto">{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                                </div>
                                <div v-if="log.new_values">
                                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-1">New Values</p>
                                    <pre class="text-xs bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 rounded-lg p-3 overflow-x-auto">{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2">IP: {{ log.ip_address || '—' }}</p>
                        </td>
                    </tr>
                </template>
            </STable>
        </SCard>

        <div v-if="pagination" class="flex items-center justify-between mt-4">
            <p class="text-xs text-slate-400 dark:text-slate-500">{{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}</p>
            <div class="flex gap-1">
                <button v-for="p in Math.min(pagination.last_page, 10)" :key="p" @click="page = p; load()"
                    :class="p === pagination.current_page ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-600'"
                    class="px-3 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-600">{{ p }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/api';

const logs = ref([]);
const stats = ref({});
const pagination = ref(null);
const expanded = ref(null);
const page = ref(1);
const filters = reactive({ search: '', model: '', action: '', from: '', to: '' });

const models = [
    { value: 'invoice', label: 'Invoice' }, { value: 'product', label: 'Product' },
    { value: 'client', label: 'Customer' }, { value: 'payment', label: 'Payment' },
    { value: 'stock_movement', label: 'Stock Movement' }, { value: 'sales_order', label: 'Sales Order' },
];

function actionColor(a) { return { created: 'green', updated: 'blue', deleted: 'red' }[a] || 'default'; }
function fmtTime(iso) { return new Date(iso).toLocaleString('en-AE', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); }
function toggle(id) { expanded.value = expanded.value === id ? null : id; }

let searchTimeout;
function debounceLoad() { clearTimeout(searchTimeout); searchTimeout = setTimeout(load, 300); }

async function load() {
    const params = { page: page.value, per_page: 30 };
    if (filters.search) params.search = filters.search;
    if (filters.model) params.model = filters.model;
    if (filters.action) params.action = filters.action;
    if (filters.from) params.from = filters.from;
    if (filters.to) params.to = filters.to;
    const { data } = await api.get('/audit-logs', { params });
    logs.value = data.data;
    pagination.value = { from: data.from, to: data.to, total: data.total, current_page: data.current_page, last_page: data.last_page };
}

async function loadStats() { const { data } = await api.get('/audit-logs/stats'); stats.value = data; }

onMounted(() => { load(); loadStats(); });
</script>
