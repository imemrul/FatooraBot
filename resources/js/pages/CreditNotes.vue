<template>
    <div>
        <SPageHeader title="Credit Notes" description="Issue refunds and credit adjustments against invoices">
            <SButton @click="openForm()">+ New Credit Note</SButton>
        </SPageHeader>

        <SCard>
            <STable :columns="['CN #', 'Invoice', 'Customer', 'Date', 'Total', 'Status', 'Actions']" :loading="loading">
                <tr v-for="cn in items" :key="cn.id">
                    <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ cn.credit_note_number }}</td>
                    <td class="px-4 py-3 text-sm">{{ cn.invoice?.invoice_number }}</td>
                    <td class="px-4 py-3 text-sm">{{ cn.client?.name }}</td>
                    <td class="px-4 py-3 text-sm">{{ cn.issue_date?.slice(0, 10) }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-red-600">-{{ fmt(cn.total) }}</td>
                    <td class="px-4 py-3"><SBadge :variant="statusV(cn.status)">{{ cn.status }}</SBadge></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 text-xs">
                            <button v-if="cn.status==='draft'" @click="action(cn.id,'issue')" class="text-blue-600">Issue</button>
                            <button v-if="cn.status==='issued'" @click="action(cn.id,'apply')" class="text-green-600 font-medium">Apply to Invoice</button>
                            <button v-if="['draft','issued'].includes(cn.status)" @click="action(cn.id,'cancel')" class="text-red-600">Cancel</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <SModal :show="showForm" title="New Credit Note" @close="showForm = false" size="lg">
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SSelect v-model="form.invoice_id" label="Against Invoice" :options="invoiceOpts" required @change="onInvoiceSelect" />
                    <SInput v-model="form.issue_date" label="Issue Date" type="date" required />
                </div>
                <SInput v-model="form.reason" label="Reason" />
                <div class="border-t dark:border-slate-700 pt-4">
                    <p class="text-sm font-medium mb-2 dark:text-white">Refund Items</p>
                    <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 mb-2 items-end">
                        <SInput v-model="item.description" placeholder="Description" class="flex-1" required />
                        <SInput v-model="item.quantity" placeholder="Qty" type="number" step="0.01" class="w-20" required />
                        <SInput v-model="item.unit_price" placeholder="Price" type="number" step="0.01" class="w-28" required />
                        <SInput v-model="item.vat_rate" placeholder="VAT%" type="number" class="w-20" />
                        <button type="button" @click="form.items.splice(i, 1)" class="text-red-500 text-lg pb-2">&times;</button>
                    </div>
                    <button type="button" @click="form.items.push({ description: '', quantity: 1, unit_price: 0, vat_rate: 5 })" class="text-xs text-indigo-600">+ Add Item</button>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SButton variant="secondary" @click="showForm = false">Cancel</SButton>
                    <SButton type="submit" :loading="saving">Create</SButton>
                </div>
            </form>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/api';

const items = ref([]);
const invoices = ref([]);
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const invoiceOpts = ref([]);
const form = reactive({ invoice_id: '', client_id: '', issue_date: new Date().toISOString().slice(0, 10), reason: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
const fmt = (v) => Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 });
const statusV = (s) => ({ draft: 'default', issued: 'info', applied: 'success', cancelled: 'error' }[s] || 'default');

async function fetchList() { loading.value = true; try { const { data } = await api.get('/credit-notes'); items.value = data.data; } finally { loading.value = false; } }
async function fetchInvoices() { const { data } = await api.get('/invoices/all'); const list = data.data; invoices.value = list; invoiceOpts.value = list.map(i => ({ value: i.id, label: `${i.invoice_number} - ${i.client?.name || ''}` })); }

function onInvoiceSelect() { const inv = invoices.value.find(i => i.id == form.invoice_id); if (inv) form.client_id = inv.client_id; }
function openForm() { Object.assign(form, { invoice_id: '', client_id: '', issue_date: new Date().toISOString().slice(0, 10), reason: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] }); showForm.value = true; }
async function save() { saving.value = true; try { await api.post('/credit-notes', form); showForm.value = false; fetchList(); } finally { saving.value = false; } }
async function action(id, act) { await api.post(`/credit-notes/${id}/${act}`); fetchList(); }

onMounted(() => { fetchList(); fetchInvoices(); });
</script>
