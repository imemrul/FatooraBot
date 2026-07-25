<template>
    <div>
        <SPageHeader title="Delivery Notes" description="Track deliveries and shipments">
            <SButton @click="openForm()">+ New Delivery Note</SButton>
        </SPageHeader>

        <STabs :tabs="tabs" v-model="activeTab" class="mb-4" @update:modelValue="fetchList" />

        <SCard>
            <STable :columns="['DN #', 'Customer', 'Date', 'Driver', 'Vehicle', 'Status', 'Actions']" :loading="loading">
                <tr v-for="dn in items" :key="dn.id">
                    <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ dn.delivery_number }}</td>
                    <td class="px-4 py-3 text-sm">{{ dn.client?.name }}</td>
                    <td class="px-4 py-3 text-sm">{{ dn.delivery_date?.slice(0, 10) }}</td>
                    <td class="px-4 py-3 text-sm">{{ dn.driver_name || '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ dn.vehicle_number || '—' }}</td>
                    <td class="px-4 py-3"><SBadge :variant="statusV(dn.status)">{{ dn.status?.replace('_', ' ') }}</SBadge></td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 text-xs">
                            <button v-if="dn.status==='pending'" @click="action(dn.id,'in-transit')" class="text-blue-600">Dispatch</button>
                            <button v-if="dn.status==='in_transit'" @click="action(dn.id,'delivered')" class="text-green-600">Delivered</button>
                            <button v-if="!['delivered','cancelled'].includes(dn.status)" @click="action(dn.id,'cancel')" class="text-red-600">Cancel</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <SModal :show="showForm" title="New Delivery Note" @close="showForm = false" size="lg">
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SSelect v-model="form.client_id" label="Customer" :options="clientOpts" required />
                    <SInput v-model="form.delivery_date" label="Delivery Date" type="date" required />
                    <SInput v-model="form.driver_name" label="Driver Name" />
                    <SInput v-model="form.vehicle_number" label="Vehicle #" />
                </div>
                <SInput v-model="form.delivery_address" label="Delivery Address" />
                <SInput v-model="form.notes" label="Notes" />
                <div class="border-t dark:border-slate-700 pt-4">
                    <p class="text-sm font-medium mb-2 dark:text-white">Items</p>
                    <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 mb-2 items-end">
                        <SInput v-model="item.description" placeholder="Description" class="flex-1" required />
                        <SInput v-model="item.quantity" placeholder="Qty" type="number" step="0.01" class="w-24" required />
                        <SInput v-model="item.unit" placeholder="Unit" class="w-20" />
                        <button type="button" @click="form.items.splice(i, 1)" class="text-red-500 text-lg pb-2">&times;</button>
                    </div>
                    <button type="button" @click="form.items.push({ description: '', quantity: 1, unit: 'pcs' })" class="text-xs text-indigo-600">+ Add Item</button>
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
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const activeTab = ref('');
const clientOpts = ref([]);
const tabs = [{ value: '', label: 'All' }, { value: 'pending', label: 'Pending' }, { value: 'in_transit', label: 'In Transit' }, { value: 'delivered', label: 'Delivered' }];
const form = reactive({ client_id: '', delivery_date: new Date().toISOString().slice(0, 10), driver_name: '', vehicle_number: '', delivery_address: '', notes: '', items: [{ description: '', quantity: 1, unit: 'pcs' }] });
const statusV = (s) => ({ pending: 'warning', in_transit: 'info', delivered: 'success', cancelled: 'error' }[s] || 'default');

async function fetchList() { loading.value = true; try { const { data } = await api.get('/delivery-notes', { params: { status: activeTab.value || undefined } }); items.value = data.data; } finally { loading.value = false; } }
async function fetchClients() { const { data } = await api.get('/clients/all'); clientOpts.value = data.data.map(c => ({ value: c.id, label: c.name })); }

function openForm() {
    Object.assign(form, { client_id: '', delivery_date: new Date().toISOString().slice(0, 10), driver_name: '', vehicle_number: '', delivery_address: '', notes: '', items: [{ description: '', quantity: 1, unit: 'pcs' }] });
    showForm.value = true;
}

async function save() { saving.value = true; try { await api.post('/delivery-notes', form); showForm.value = false; fetchList(); } finally { saving.value = false; } }
async function action(id, act) { await api.post(`/delivery-notes/${id}/${act}`); fetchList(); }

onMounted(() => { fetchList(); fetchClients(); });
</script>
