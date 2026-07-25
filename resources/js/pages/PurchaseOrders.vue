<template>
    <div>
        <SPageHeader title="Purchase Orders" description="Manage supplier orders and receive goods">
            <div class="flex gap-2">
                <SButton variant="secondary" @click="showSuppliers = true">Suppliers</SButton>
                <SButton @click="openForm()">+ New PO</SButton>
            </div>
        </SPageHeader>

        <STabs :tabs="tabs" v-model="activeTab" class="mb-4" @update:modelValue="fetchList" />

        <SCard>
            <STable :columns="['PO #', 'Supplier', 'Date', 'Expected', 'Total', 'Status', 'Actions']" :loading="loading">
                <tr v-for="po in items" :key="po.id">
                    <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ po.po_number }}</td>
                    <td class="px-4 py-3 text-sm">{{ po.supplier?.name }}</td>
                    <td class="px-4 py-3 text-sm">{{ po.order_date?.slice(0, 10) }}</td>
                    <td class="px-4 py-3 text-sm">{{ po.expected_date?.slice(0, 10) || '—' }}</td>
                    <td class="px-4 py-3 text-sm font-semibold">{{ fmt(po.total) }}</td>
                    <td class="px-4 py-3"><SBadge :variant="statusV(po.status)">{{ po.status }}</SBadge></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 text-xs">
                            <button v-if="po.status==='draft'" @click="sendPO(po.id)" class="text-blue-600">Send</button>
                            <button v-if="['sent','partial'].includes(po.status)" @click="openReceive(po)" class="text-green-600 font-medium">Receive</button>
                            <button v-if="po.status==='draft'" @click="openForm(po)" class="text-indigo-600">Edit</button>
                            <button v-if="!['received','cancelled'].includes(po.status)" @click="cancelPO(po.id)" class="text-red-600">Cancel</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- PO Form Modal -->
        <SModal :show="showForm" :title="editing ? 'Edit PO' : 'New Purchase Order'" @close="showForm = false" size="lg">
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SSelect v-model="form.supplier_id" label="Supplier" :options="supplierOpts" required />
                    <SSelect v-model="form.warehouse_id" label="Receive to Warehouse" :options="warehouseOpts" />
                    <SInput v-model="form.order_date" label="Order Date" type="date" required />
                    <SInput v-model="form.expected_date" label="Expected Date" type="date" />
                </div>
                <SInput v-model="form.notes" label="Notes" />
                <div class="border-t dark:border-slate-700 pt-4">
                    <p class="text-sm font-medium mb-2 dark:text-white">Line Items</p>
                    <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 mb-2 items-end">
                        <SInput v-model="item.description" placeholder="Description" class="flex-1" required />
                        <SInput v-model="item.quantity" placeholder="Qty" type="number" step="0.01" class="w-20" required />
                        <SInput v-model="item.unit_price" placeholder="Price" type="number" step="0.01" class="w-28" required />
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

        <!-- Receive Modal -->
        <SModal :show="showReceive" title="Receive Goods" @close="showReceive = false">
            <div class="space-y-3">
                <div v-for="item in receiveItems" :key="item.item_id" class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium dark:text-white">{{ item.description }}</p>
                        <p class="text-xs text-slate-400">Ordered: {{ item.ordered }} | Received: {{ item.received }}</p>
                    </div>
                    <SInput v-model="item.quantity" type="number" step="0.01" :max="item.ordered - item.received" class="w-24" placeholder="Qty" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SButton variant="secondary" @click="showReceive = false">Cancel</SButton>
                    <SButton @click="receiveGoods" :loading="receiving">Receive & Stock In</SButton>
                </div>
            </div>
        </SModal>

        <!-- Suppliers Modal -->
        <SModal :show="showSuppliers" title="Suppliers" @close="showSuppliers = false" size="lg">
            <div class="space-y-3">
                <div class="flex gap-2">
                    <SInput v-model="newSupplier.name" placeholder="Supplier name" class="flex-1" />
                    <SInput v-model="newSupplier.email" placeholder="Email" class="flex-1" />
                    <SInput v-model="newSupplier.phone" placeholder="Phone" class="w-36" />
                    <SButton @click="addSupplier" size="sm">Add</SButton>
                </div>
                <div v-for="s in suppliers" :key="s.id" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <div>
                        <span class="text-sm font-medium dark:text-white">{{ s.name }}</span>
                        <span class="text-xs text-slate-400 ml-2">{{ s.email }} · {{ s.purchase_orders_count || 0 }} POs</span>
                    </div>
                    <button @click="deleteSupplier(s.id)" class="text-xs text-red-500">Delete</button>
                </div>
            </div>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/api';

const items = ref([]);
const suppliers = ref([]);
const loading = ref(false);
const saving = ref(false);
const receiving = ref(false);
const showForm = ref(false);
const showReceive = ref(false);
const showSuppliers = ref(false);
const editing = ref(null);
const activeTab = ref('');
const receiveItems = ref([]);
const receivePOId = ref(null);
const supplierOpts = ref([]);
const warehouseOpts = ref([]);
const tabs = [{ value: '', label: 'All' }, { value: 'draft', label: 'Draft' }, { value: 'sent', label: 'Sent' }, { value: 'partial', label: 'Partial' }, { value: 'received', label: 'Received' }];
const form = reactive({ supplier_id: '', warehouse_id: '', order_date: '', expected_date: '', notes: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
const newSupplier = reactive({ name: '', email: '', phone: '' });
const fmt = (v) => Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 });
const statusV = (s) => ({ draft: 'default', sent: 'info', partial: 'warning', received: 'success', cancelled: 'error' }[s] || 'default');

async function fetchList() { loading.value = true; try { const { data } = await api.get('/purchase-orders', { params: { status: activeTab.value || undefined } }); items.value = data.data; } finally { loading.value = false; } }
async function fetchSuppliers() { const { data } = await api.get('/suppliers?per_page=200'); suppliers.value = data.data || data; supplierOpts.value = suppliers.value.map(s => ({ value: s.id, label: s.name })); }
async function fetchWarehouses() { const { data } = await api.get('/warehouses/all'); warehouseOpts.value = data.data.map(w => ({ value: w.id, label: w.name })); }

function openForm(po = null) {
    editing.value = po?.id || null;
    if (po) Object.assign(form, { supplier_id: po.supplier_id, warehouse_id: po.warehouse_id || '', order_date: po.order_date?.slice(0, 10), expected_date: po.expected_date?.slice(0, 10) || '', notes: po.notes || '', items: po.items?.map(i => ({ ...i })) || [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
    else Object.assign(form, { supplier_id: '', warehouse_id: '', order_date: new Date().toISOString().slice(0, 10), expected_date: '', notes: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
    showForm.value = true;
}

function openReceive(po) {
    receivePOId.value = po.id;
    receiveItems.value = (po.items || []).filter(i => Number(i.received_quantity) < Number(i.quantity)).map(i => ({ item_id: i.id, description: i.description, ordered: Number(i.quantity), received: Number(i.received_quantity), quantity: 0 }));
    showReceive.value = true;
}

async function save() { saving.value = true; try { if (editing.value) await api.put(`/purchase-orders/${editing.value}`, form); else await api.post('/purchase-orders', form); showForm.value = false; fetchList(); } finally { saving.value = false; } }
async function sendPO(id) { await api.post(`/purchase-orders/${id}/send`); fetchList(); }
async function cancelPO(id) { if (!confirm('Cancel?')) return; await api.post(`/purchase-orders/${id}/cancel`); fetchList(); }
async function receiveGoods() { receiving.value = true; try { const payload = receiveItems.value.filter(i => i.quantity > 0); await api.post(`/purchase-orders/${receivePOId.value}/receive`, { items: payload }); showReceive.value = false; fetchList(); } finally { receiving.value = false; } }
async function addSupplier() { if (!newSupplier.name) return; await api.post('/suppliers', newSupplier); Object.assign(newSupplier, { name: '', email: '', phone: '' }); fetchSuppliers(); }
async function deleteSupplier(id) { if (!confirm('Delete?')) return; await api.delete(`/suppliers/${id}`); fetchSuppliers(); }

onMounted(() => { fetchList(); fetchSuppliers(); fetchWarehouses(); });
</script>
