<template>
    <div>
        <SPageHeader title="Reports & Exports" description="Download CSV exports and view profit/loss" />

        <!-- Profit & Loss -->
        <SCard class="mb-6">
            <h3 class="text-base font-semibold mb-4 dark:text-white">Profit & Loss</h3>
            <div class="flex flex-wrap gap-3 items-end mb-4">
                <SInput v-model="pl.from" label="From" type="date" class="w-40" />
                <SInput v-model="pl.to" label="To" type="date" class="w-40" />
                <SButton @click="fetchPL" :loading="plLoading">Calculate</SButton>
            </div>
            <div v-if="plData" class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <SStatCard label="Revenue" :value="fmt(plData.revenue)" icon="📈" />
                <SStatCard label="Collected" :value="fmt(plData.collected)" icon="💵" />
                <SStatCard label="Expenses" :value="fmt(plData.expenses)" icon="📉" />
                <SStatCard label="Gross Profit" :value="fmt(plData.gross_profit)" :icon="plData.gross_profit >= 0 ? '✅' : '❌'" />
                <SStatCard label="Net (Collected - Expenses)" :value="fmt(plData.net_collected)" :icon="plData.net_collected >= 0 ? '✅' : '❌'" />
            </div>
        </SCard>

        <!-- CSV Exports -->
        <SCard>
            <h3 class="text-base font-semibold mb-4 dark:text-white">CSV Exports</h3>
            <div class="flex flex-wrap gap-3 items-end mb-4">
                <SInput v-model="exportFilters.from" label="From" type="date" class="w-40" />
                <SInput v-model="exportFilters.to" label="To" type="date" class="w-40" />
                <SSelect v-model="exportFilters.status" label="Invoice Status" :options="statusOptions" class="w-40" />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <SButton variant="secondary" @click="download('invoices')">📄 Invoices</SButton>
                <SButton variant="secondary" @click="download('clients')">👥 Clients</SButton>
                <SButton variant="secondary" @click="download('products')">📦 Products</SButton>
                <SButton variant="secondary" @click="download('payments')">💳 Payments</SButton>
                <SButton variant="secondary" @click="download('expenses')">💰 Expenses</SButton>
            </div>
        </SCard>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import api from '@/utils/api';

const fmt = (v) => Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 }) + ' AED';
const now = new Date();
const pl = reactive({ from: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10), to: now.toISOString().slice(0, 10) });
const plData = ref(null);
const plLoading = ref(false);
const exportFilters = reactive({ from: '', to: '', status: '' });
const statusOptions = [{ value: '', label: 'All' }, { value: 'draft', label: 'Draft' }, { value: 'sent', label: 'Sent' }, { value: 'paid', label: 'Paid' }, { value: 'overdue', label: 'Overdue' }];

async function fetchPL() {
    plLoading.value = true;
    try { const { data } = await api.get('/reports/profit-loss', { params: pl }); plData.value = data; }
    finally { plLoading.value = false; }
}

async function download(type) {
    const params = new URLSearchParams();
    if (exportFilters.from) params.set('from', exportFilters.from);
    if (exportFilters.to) params.set('to', exportFilters.to);
    if (type === 'invoices' && exportFilters.status) params.set('status', exportFilters.status);

    const token = localStorage.getItem('token');
    const url = `/api/export/${type}?${params.toString()}`;

    const res = await fetch(url, { headers: { Authorization: `Bearer ${token}`, Accept: 'text/csv' } });
    const blob = await res.blob();
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${type}_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
}
</script>
