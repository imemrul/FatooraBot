<template>
    <div>
        <SPageHeader title="Currencies" description="Manage exchange rates for multi-currency invoicing">
            <div class="flex gap-2">
                <SButton variant="secondary" @click="seedDefaults" :loading="seeding">Seed Defaults</SButton>
                <SButton @click="openForm()">+ Add Currency</SButton>
            </div>
        </SPageHeader>

        <!-- Converter -->
        <SCard class="mb-6">
            <p class="text-sm font-medium mb-3 dark:text-white">Quick Convert</p>
            <div class="flex flex-wrap gap-3 items-end">
                <SInput v-model="conv.amount" label="Amount" type="number" step="0.01" class="w-36" />
                <SSelect v-model="conv.from" label="From" :options="codeOptions" class="w-32" />
                <span class="pb-2 text-slate-400">→</span>
                <SSelect v-model="conv.to" label="To" :options="codeOptions" class="w-32" />
                <SButton @click="convert" size="sm">Convert</SButton>
                <span v-if="conv.result !== null" class="pb-2 text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ Number(conv.result).toLocaleString('en-AE', { minimumFractionDigits: 2 }) }} {{ conv.to }}</span>
            </div>
        </SCard>

        <SCard>
            <STable :columns="['Code', 'Name', 'Symbol', 'Rate to AED', 'Status', 'Actions']" :loading="loading">
                <tr v-for="c in currencies" :key="c.id">
                    <td class="px-4 py-3 text-sm font-bold text-slate-900 dark:text-white">{{ c.code }}</td>
                    <td class="px-4 py-3 text-sm">{{ c.name }}</td>
                    <td class="px-4 py-3 text-sm text-center">{{ c.symbol }}</td>
                    <td class="px-4 py-3 text-sm font-mono">{{ Number(c.rate_to_base).toFixed(4) }}</td>
                    <td class="px-4 py-3"><SBadge :variant="c.is_active ? 'success' : 'warning'">{{ c.is_active ? 'Active' : 'Inactive' }}</SBadge></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button @click="openForm(c)" class="text-xs text-indigo-600 hover:underline">Edit</button>
                            <button @click="remove(c.id)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <SModal :show="showForm" :title="editing ? 'Edit Currency' : 'Add Currency'" @close="showForm = false">
            <form @submit.prevent="save" class="space-y-4">
                <SInput v-model="form.code" label="Code (e.g. USD)" maxlength="3" :disabled="!!editing" required />
                <SInput v-model="form.name" label="Name" required />
                <SInput v-model="form.symbol" label="Symbol" required />
                <SInput v-model="form.rate_to_base" label="Rate to AED (base)" type="number" step="0.000001" required />
                <div class="flex justify-end gap-3 pt-2">
                    <SButton variant="secondary" @click="showForm = false">Cancel</SButton>
                    <SButton type="submit" :loading="saving">{{ editing ? 'Update' : 'Create' }}</SButton>
                </div>
            </form>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import api from '@/utils/api';

const currencies = ref([]);
const loading = ref(false);
const saving = ref(false);
const seeding = ref(false);
const showForm = ref(false);
const editing = ref(null);
const form = reactive({ code: '', name: '', symbol: '', rate_to_base: 1 });
const conv = reactive({ amount: 100, from: 'USD', to: 'AED', result: null });
const codeOptions = computed(() => currencies.value.map(c => ({ value: c.code, label: c.code })));

async function fetchCurrencies() {
    loading.value = true;
    try { const { data } = await api.get('/currencies'); currencies.value = data.currencies; }
    finally { loading.value = false; }
}

function openForm(c = null) {
    editing.value = c?.id || null;
    if (c) Object.assign(form, { code: c.code, name: c.name, symbol: c.symbol, rate_to_base: c.rate_to_base });
    else Object.assign(form, { code: '', name: '', symbol: '', rate_to_base: 1 });
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) await api.put(`/currencies/${editing.value}`, form);
        else await api.post('/currencies', form);
        showForm.value = false;
        fetchCurrencies();
    } finally { saving.value = false; }
}

async function remove(id) {
    if (!confirm('Delete?')) return;
    await api.delete(`/currencies/${id}`);
    fetchCurrencies();
}

async function seedDefaults() {
    seeding.value = true;
    try { const { data } = await api.post('/currencies/seed-defaults'); currencies.value = data.currencies; }
    finally { seeding.value = false; }
}

async function convert() {
    const { data } = await api.post('/currencies/convert', conv);
    conv.result = data.result;
}

onMounted(fetchCurrencies);
</script>
