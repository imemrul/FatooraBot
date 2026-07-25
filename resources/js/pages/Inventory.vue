<template>
    <div>
        <SPageHeader title="Inventory" subtitle="Stock levels and movements">
            <SButton v-can="'manage_inventory'" @click="showMoveModal = true">Stock Movement</SButton>
        </SPageHeader>

        <!-- Alerts -->
        <div v-if="alerts.low_stock?.length || alerts.out_of_stock?.length" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <SAlert v-if="alerts.out_of_stock?.length" variant="error">
                <strong>Out of Stock ({{ alerts.out_of_stock.length }})</strong>
                <ul class="mt-1 space-y-0.5">
                    <li v-for="p in alerts.out_of_stock.slice(0, 5)" :key="p.id">{{ p.name }} <span class="opacity-60">({{ p.sku || 'no SKU' }})</span></li>
                    <li v-if="alerts.out_of_stock.length > 5" class="opacity-60">+{{ alerts.out_of_stock.length - 5 }} more</li>
                </ul>
            </SAlert>
            <SAlert v-if="alerts.low_stock?.length" variant="warning">
                <strong>Low Stock ({{ alerts.low_stock.length }})</strong>
                <ul class="mt-1 space-y-0.5">
                    <li v-for="p in alerts.low_stock.slice(0, 5)" :key="p.id">{{ p.name }} — {{ p.total_stock }} left</li>
                    <li v-if="alerts.low_stock.length > 5" class="opacity-60">+{{ alerts.low_stock.length - 5 }} more</li>
                </ul>
            </SAlert>
        </div>

        <STabs :tabs="['Levels', 'Movements']" v-model="tab" class="mb-4" />

        <!-- Levels -->
        <SCard v-if="tab === 'Levels'" noPad>
            <STable :columns="[{key:'product',label:'Product'},{key:'warehouse',label:'Warehouse'},{key:'qty',label:'Quantity',align:'right'}]" :empty="!levels.length" emptyText="No inventory yet.">
                <tr v-for="l in levels" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">{{ l.product?.name }}</td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ l.warehouse?.name }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-slate-900 dark:text-white">{{ l.quantity }}</td>
                </tr>
            </STable>
        </SCard>

        <!-- Movements -->
        <SCard v-if="tab === 'Movements'" noPad>
            <STable :columns="[{key:'date',label:'Date'},{key:'type',label:'Type'},{key:'product',label:'Product'},{key:'from',label:'From'},{key:'to',label:'To'},{key:'qty',label:'Qty',align:'right'},{key:'ref',label:'Ref'}]" :empty="!movements.length" emptyText="No movements yet.">
                <tr v-for="m in movements" :key="m.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">{{ new Date(m.created_at).toLocaleDateString() }}</td>
                    <td class="px-5 py-3"><SBadge :color="typeColor(m.type)">{{ m.type.replace('_', ' ') }}</SBadge></td>
                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">{{ m.product?.name }}</td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ m.warehouse?.name }}</td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ m.to_warehouse?.name || '—' }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-slate-900 dark:text-white">{{ m.quantity }}</td>
                    <td class="px-5 py-3 text-xs text-slate-400 dark:text-slate-500">{{ m.reference || '—' }}</td>
                </tr>
            </STable>
        </SCard>

        <!-- Stock Movement Modal -->
        <SModal :show="showMoveModal" title="Stock Movement" @close="showMoveModal = false">
            <SAlert v-if="moveError" variant="error" class="mb-4">{{ moveError }}</SAlert>
            <form @submit.prevent="submitMove" class="space-y-4">
                <SSelect v-model="moveForm.type" label="Type" required>
                    <option value="stock_in">Stock In</option>
                    <option value="stock_out">Stock Out</option>
                    <option value="transfer">Transfer</option>
                </SSelect>
                <SSelect v-model="moveForm.product_id" label="Product" required>
                    <option value="">Select product</option>
                    <option v-for="p in productsList" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku || '—' }})</option>
                </SSelect>
                <SSelect v-model="moveForm.warehouse_id" :label="moveForm.type === 'transfer' ? 'From Warehouse' : 'Warehouse'" required>
                    <option value="">Select warehouse</option>
                    <option v-for="w in warehousesList" :key="w.id" :value="w.id">{{ w.name }}</option>
                </SSelect>
                <SSelect v-if="moveForm.type === 'transfer'" v-model="moveForm.to_warehouse_id" label="To Warehouse" required>
                    <option value="">Select destination</option>
                    <option v-for="w in warehousesList.filter(w => w.id != moveForm.warehouse_id)" :key="w.id" :value="w.id">{{ w.name }}</option>
                </SSelect>
                <SInput v-model="moveForm.quantity" label="Quantity" type="number" min="1" required />
                <SInput v-model="moveForm.reference" label="Reference" placeholder="PO-001, SO-123, etc." />
            </form>
            <template #footer>
                <SButton variant="secondary" @click="showMoveModal = false">Cancel</SButton>
                <SButton :loading="moveSaving" @click="submitMove">Submit</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/api';

const tab = ref('Levels');
const levels = ref([]);
const movements = ref([]);
const alerts = ref({});
const productsList = ref([]);
const warehousesList = ref([]);
const showMoveModal = ref(false);
const moveSaving = ref(false);
const moveError = ref('');
const moveForm = reactive({ type: 'stock_in', product_id: '', warehouse_id: '', to_warehouse_id: '', quantity: '', reference: '' });

function typeColor(t) { return { stock_in: 'green', stock_out: 'red', transfer: 'blue' }[t] || 'default'; }

async function submitMove() {
    moveSaving.value = true; moveError.value = '';
    try {
        await api.post('/inventory/move', moveForm);
        showMoveModal.value = false;
        Object.assign(moveForm, { type: 'stock_in', product_id: '', warehouse_id: '', to_warehouse_id: '', quantity: '', reference: '' });
        await loadAll();
    } catch (e) { moveError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat()[0] || 'Failed.'; }
    finally { moveSaving.value = false; }
}

async function loadAll() {
    const [lRes, mRes, aRes, pRes, wRes] = await Promise.all([
        api.get('/inventory/levels?per_page=100'), api.get('/inventory/movements?per_page=50'),
        api.get('/inventory/alerts'), api.get('/products?per_page=200'), api.get('/warehouses?per_page=50'),
    ]);
    levels.value = lRes.data.data; movements.value = mRes.data.data; alerts.value = aRes.data;
    productsList.value = pRes.data.data; warehousesList.value = wRes.data.data;
}

onMounted(loadAll);
</script>
