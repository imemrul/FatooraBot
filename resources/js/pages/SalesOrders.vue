<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Sales Orders</h1>
            <button v-can="'manage_invoices'" @click="openCreate"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                New Order
            </button>
        </div>

        <!-- Status filter -->
        <div class="flex gap-1 mb-4 bg-slate-100 rounded-lg p-1 w-fit">
            <button v-for="s in ['all','draft','confirmed','delivered','cancelled']" :key="s" @click="filter = s; load()"
                :class="filter === s ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'"
                class="px-3 py-1.5 text-xs font-medium rounded-md transition capitalize">{{ s }}</button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-5 py-3">Order #</th>
                        <th class="text-left px-5 py-3">Customer</th>
                        <th class="text-left px-5 py-3">Date</th>
                        <th class="text-right px-5 py-3">Total</th>
                        <th class="text-left px-5 py-3">Status</th>
                        <th class="text-left px-5 py-3">Invoice</th>
                        <th class="text-right px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="o in orders" :key="o.id" class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-xs font-medium">{{ o.order_number }}</td>
                        <td class="px-5 py-3">{{ o.client?.name }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ o.order_date }}</td>
                        <td class="px-5 py-3 text-right font-medium">{{ o.currency }} {{ fmt(o.total) }}</td>
                        <td class="px-5 py-3"><span :class="stClass(o.status)" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ o.status }}</span></td>
                        <td class="px-5 py-3 text-xs">{{ o.invoice?.invoice_number || '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1 flex-wrap">
                                <button v-if="o.status === 'draft'" @click="openEdit(o)" class="text-indigo-600 text-xs">Edit</button>
                                <button v-if="o.status === 'draft'" @click="confirmOrder(o)" class="text-green-600 text-xs">Confirm</button>
                                <button v-if="o.status === 'confirmed'" @click="deliverOrder(o)" class="text-blue-600 text-xs">Deliver</button>
                                <button v-if="(o.status === 'confirmed' || o.status === 'delivered') && !o.invoice_id" @click="convertOrder(o)" class="text-purple-600 text-xs">→ Invoice</button>
                                <button v-if="o.status !== 'delivered' && o.status !== 'cancelled'" @click="cancelOrder(o)" class="text-red-600 text-xs">Cancel</button>
                                <button v-if="o.status === 'draft'" @click="deleteOrder(o)" class="text-red-600 text-xs">Del</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!orders.length"><td colspan="7" class="px-5 py-8 text-center text-slate-400">No sales orders found.</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ editing ? 'Edit Order' : 'New Sales Order' }}</h2>
                <div v-if="formError" class="bg-red-50 text-red-700 text-sm rounded-lg px-3 py-2 mb-4">{{ formError }}</div>
                <form @submit.prevent="saveOrder" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Customer *</label>
                            <select v-model="form.client_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select</option>
                                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Warehouse</label>
                            <select v-model="form.warehouse_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                <option :value="null">None (select before confirm)</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Order Date *</label>
                            <input v-model="form.order_date" type="date" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Delivery Date</label>
                            <input v-model="form.delivery_date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-medium text-slate-600">Items *</label>
                            <button type="button" @click="addItem" class="text-xs text-indigo-600">+ Add</button>
                        </div>
                        <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
                            <select v-model="item.product_id" @change="onProductSelect(idx)" class="col-span-4 border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none">
                                <option :value="null">Manual</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <input v-model="item.description" placeholder="Description" required class="col-span-3 border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none" />
                            <input v-model="item.quantity" type="number" step="0.01" min="0.01" placeholder="Qty" required class="col-span-2 border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none" />
                            <input v-model="item.unit_price" type="number" step="0.01" min="0" placeholder="Price" required class="col-span-2 border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none" />
                            <button type="button" @click="form.items.splice(idx, 1)" v-if="form.items.length > 1" class="col-span-1 text-red-500 text-xs">✕</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-slate-600">Cancel</button>
                        <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition">
                            {{ saving ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';

const router = useRouter();
const orders = ref([]);
const clients = ref([]);
const warehouses = ref([]);
const products = ref([]);
const filter = ref('all');
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const formError = ref('');

const newItem = () => ({ product_id: null, description: '', quantity: 1, unit_price: 0, vat_rate: 5 });
const form = reactive({ client_id: '', warehouse_id: null, order_date: '', delivery_date: '', notes: '', items: [newItem()] });

function fmt(v) { return Number(v || 0).toFixed(2); }
function stClass(s) {
    return { draft: 'bg-slate-100 text-slate-600', confirmed: 'bg-blue-100 text-blue-700', delivered: 'bg-green-100 text-green-700', cancelled: 'bg-red-100 text-red-600' }[s] || 'bg-slate-100 text-slate-600';
}
function addItem() { form.items.push(newItem()); }
function onProductSelect(idx) {
    const p = products.value.find(x => x.id === form.items[idx].product_id);
    if (p) { form.items[idx].description = p.name; form.items[idx].unit_price = p.unit_price; form.items[idx].vat_rate = p.vat_rate; }
}

function openCreate() {
    Object.assign(form, { client_id: '', warehouse_id: null, order_date: new Date().toISOString().split('T')[0], delivery_date: '', notes: '', items: [newItem()] });
    editing.value = null; formError.value = ''; showModal.value = true;
}
function openEdit(o) {
    Object.assign(form, {
        client_id: o.client?.id, warehouse_id: o.warehouse?.id || null, order_date: o.order_date, delivery_date: o.delivery_date || '', notes: o.notes || '',
        items: o.items?.map(i => ({ product_id: i.product_id, description: i.description, quantity: i.quantity, unit_price: i.unit_price, vat_rate: i.vat_rate })) || [newItem()],
    });
    editing.value = o.id; formError.value = ''; showModal.value = true;
}

async function saveOrder() {
    saving.value = true; formError.value = '';
    try {
        if (editing.value) await api.put(`/sales-orders/${editing.value}`, form);
        else await api.post('/sales-orders', form);
        showModal.value = false; await load();
    } catch (e) { formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat()[0] || 'Failed.'; }
    finally { saving.value = false; }
}

async function confirmOrder(o) { await api.post(`/sales-orders/${o.id}/confirm`); await load(); }
async function deliverOrder(o) { await api.post(`/sales-orders/${o.id}/deliver`); await load(); }
async function cancelOrder(o) { if (confirm('Cancel this order?')) { await api.post(`/sales-orders/${o.id}/cancel`); await load(); } }
async function deleteOrder(o) { if (confirm('Delete this draft?')) { await api.delete(`/sales-orders/${o.id}`); await load(); } }
async function convertOrder(o) {
    try {
        await api.post(`/sales-orders/${o.id}/convert-to-invoice`);
        router.push('/invoices');
    } catch (e) { alert(e.response?.data?.message || 'Conversion failed.'); }
}

async function load() {
    const params = filter.value !== 'all' ? { status: filter.value } : {};
    const { data } = await api.get('/sales-orders', { params });
    orders.value = data.data;
}

onMounted(async () => {
    await load();
    const [cRes, wRes, pRes] = await Promise.all([
        api.get('/clients/all'), api.get('/warehouses/all'), api.get('/products/all'),
    ]);
    clients.value = cRes.data.data; warehouses.value = wRes.data.data; products.value = pRes.data.data;
});
</script>
