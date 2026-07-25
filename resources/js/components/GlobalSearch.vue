<template>
    <div class="relative">
        <div class="flex items-center bg-slate-100 dark:bg-slate-700 rounded-lg px-3 py-1.5 gap-2 w-64">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input v-model="query" @input="debouncedSearch" @focus="open = true" placeholder="Search..." class="bg-transparent text-sm text-slate-700 dark:text-slate-200 outline-none w-full placeholder-slate-400" />
            <kbd v-if="!query" class="text-[10px] text-slate-400 border border-slate-300 dark:border-slate-600 rounded px-1">/</kbd>
        </div>

        <div v-if="open && (query.length >= 2)" class="absolute top-full mt-2 left-0 w-96 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-50 max-h-[28rem] overflow-y-auto">
            <div v-if="loading" class="p-4 text-center text-sm text-slate-400">Searching...</div>
            <template v-else>
                <div v-for="(items, section) in results" :key="section">
                    <template v-if="items.length">
                        <p class="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ sectionLabel(section) }}</p>
                        <router-link v-for="item in items" :key="item.id" :to="itemLink(section, item)" @click="open = false"
                            class="flex items-center justify-between px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition cursor-pointer">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ itemTitle(section, item) }}</p>
                                <p class="text-xs text-slate-400">{{ itemSub(section, item) }}</p>
                            </div>
                            <span v-if="item.status" class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500">{{ item.status }}</span>
                        </router-link>
                    </template>
                </div>
                <div v-if="noResults" class="p-6 text-center text-sm text-slate-400">No results found</div>
            </template>
        </div>

        <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import api from '@/utils/api';

const query = ref('');
const results = ref({});
const loading = ref(false);
const open = ref(false);

const noResults = computed(() => !loading.value && Object.values(results.value).every(v => !v.length));

let timer;
function debouncedSearch() {
    clearTimeout(timer);
    if (query.value.length < 2) { results.value = {}; return; }
    timer = setTimeout(search, 300);
}

async function search() {
    loading.value = true;
    open.value = true;
    try { const { data } = await api.get('/search', { params: { q: query.value } }); results.value = data; }
    finally { loading.value = false; }
}

function sectionLabel(s) { return { invoices: 'Invoices', clients: 'Customers', products: 'Products', quotations: 'Quotations', sales_orders: 'Sales Orders' }[s] || s; }

function itemTitle(s, item) {
    if (s === 'invoices') return item.invoice_number;
    if (s === 'clients') return item.name;
    if (s === 'products') return item.name;
    if (s === 'quotations') return item.quotation_number;
    if (s === 'sales_orders') return item.order_number;
    return '';
}

function itemSub(s, item) {
    if (s === 'invoices') return item.client?.name || '';
    if (s === 'clients') return item.email || item.phone || '';
    if (s === 'products') return `${item.sku || ''} · ${Number(item.unit_price).toFixed(2)} AED`;
    if (s === 'quotations') return item.client?.name || '';
    if (s === 'sales_orders') return item.client?.name || '';
    return '';
}

function itemLink(s, item) {
    if (s === 'invoices') return '/invoices';
    if (s === 'clients') return `/clients/${item.id}`;
    if (s === 'products') return '/products';
    if (s === 'quotations') return '/quotations';
    if (s === 'sales_orders') return '/sales-orders';
    return '/';
}
</script>
