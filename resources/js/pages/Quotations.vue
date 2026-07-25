<template>
    <div>
        <SPageHeader title="Quotations" description="Create quotes and convert to invoices">
            <SButton @click="openForm()">+ New Quotation</SButton>
        </SPageHeader>

        <STabs :tabs="tabs" v-model="activeTab" class="mb-4" @update:modelValue="fetchList" />

        <SCard>
            <STable :columns="['Quote #', 'Customer', 'Date', 'Valid Until', 'Total', 'Status', 'Actions']" :loading="loading">
                <tr v-for="q in items" :key="q.id">
                    <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ q.quotation_number }}</td>
                    <td class="px-4 py-3 text-sm">{{ q.client?.name }}</td>
                    <td class="px-4 py-3 text-sm">{{ q.issue_date?.slice(0, 10) }}</td>
                    <td class="px-4 py-3 text-sm">{{ q.valid_until?.slice(0, 10) }}</td>
                    <td class="px-4 py-3 text-sm font-semibold">{{ fmt(q.total) }}</td>
                    <td class="px-4 py-3"><SBadge :variant="statusVariant(q.status)">{{ q.status }}</SBadge></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 text-xs">
                            <button v-if="q.status==='draft'" @click="action(q.id,'send')" class="text-blue-600">Send</button>
                            <button v-if="q.status==='sent'" @click="action(q.id,'approve')" class="text-green-600">Approve</button>
                            <button v-if="q.status==='sent'" @click="action(q.id,'reject')" class="text-red-600">Reject</button>
                            <button v-if="q.status==='approved'" @click="convert(q.id)" class="text-indigo-600 font-medium">→ Invoice</button>
                            <button v-if="['draft','sent'].includes(q.status)" @click="openForm(q)" class="text-indigo-600">Edit</button>
                            <button v-if="q.status==='draft'" @click="remove(q.id)" class="text-red-600">Delete</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <SModal :show="showForm" :title="editing ? 'Edit Quotation' : 'New Quotation'" @close="showForm = false" size="lg">
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SSelect v-model="form.client_id" label="Customer" :options="clientOpts" required />
                    <SInput v-model="form.issue_date" label="Issue Date" type="date" required />
                    <SInput v-model="form.valid_until" label="Valid Until" type="date" required />
                    <SInput v-model="form.discount" label="Discount" type="number" step="0.01" />
                </div>
                <SInput v-model="form.notes" label="Notes" />
                <SInput v-model="form.terms" label="Terms & Conditions" />
                <div class="border-t dark:border-slate-700 pt-4">
                    <p class="text-sm font-medium mb-2 dark:text-white">Line Items</p>
                    <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 mb-2 items-end">
                        <SInput v-model="item.description" placeholder="Description" class="flex-1" required />
                        <SInput v-model="item.quantity" placeholder="Qty" type="number" step="0.01" class="w-20" required />
                        <SInput v-model="item.unit_price" placeholder="Price" type="number" step="0.01" class="w-28" required />
                        <SInput v-model="item.vat_rate" placeholder="VAT%" type="number" class="w-20" />
                        <button type="button" @click="form.items.splice(i, 1)" class="text-red-500 text-lg pb-2">&times;</button>
                    </div>
                    <button type="button" @click="form.items.push({ description: '', quantity: 1, unit_price: 0, vat_rate: 5 })" class="text-xs text-indigo-600">+ Add Line</button>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SButton variant="secondary" @click="showForm = false">Cancel</SButton>
                    <SButton type="submit" :loading="saving">{{ editing ? 'Update' : 'Create' }}</SButton>
                </div>
            </form>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/api';

const items = ref([]);
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const editing = ref(null);
const activeTab = ref('');
const clientOpts = ref([]);
const tabs = [{ value: '', label: 'All' }, { value: 'draft', label: 'Draft' }, { value: 'sent', label: 'Sent' }, { value: 'approved', label: 'Approved' }, { value: 'converted', label: 'Converted' }];
const form = reactive({ client_id: '', issue_date: '', valid_until: '', discount: 0, notes: '', terms: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
const fmt = (v) => Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 });
const statusVariant = (s) => ({ draft: 'default', sent: 'info', approved: 'success', rejected: 'error', expired: 'warning', converted: 'success' }[s] || 'default');

async function fetchList() {
    loading.value = true;
    try { const { data } = await api.get('/quotations', { params: { status: activeTab.value || undefined } }); items.value = data.data; }
    finally { loading.value = false; }
}
async function fetchClients() { const { data } = await api.get('/clients/all'); clientOpts.value = data.data.map(c => ({ value: c.id, label: c.name })); }

function openForm(q = null) {
    editing.value = q?.id || null;
    if (q) Object.assign(form, { client_id: q.client_id, issue_date: q.issue_date?.slice(0, 10), valid_until: q.valid_until?.slice(0, 10), discount: q.discount, notes: q.notes || '', terms: q.terms || '', items: q.items?.length ? q.items.map(i => ({ ...i })) : [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
    else Object.assign(form, { client_id: '', issue_date: new Date().toISOString().slice(0, 10), valid_until: new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10), discount: 0, notes: '', terms: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try { if (editing.value) await api.put(`/quotations/${editing.value}`, form); else await api.post('/quotations', form); showForm.value = false; fetchList(); }
    finally { saving.value = false; }
}
async function action(id, act) { await api.post(`/quotations/${id}/${act}`); fetchList(); }
async function convert(id) { await api.post(`/quotations/${id}/convert-to-invoice`); fetchList(); }
async function remove(id) { if (!confirm('Delete?')) return; await api.delete(`/quotations/${id}`); fetchList(); }

onMounted(() => { fetchList(); fetchClients(); });
</script>
