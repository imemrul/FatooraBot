<template>
    <div>
        <SPageHeader title="Recurring Invoices" description="Automate invoice generation on a schedule">
            <SButton @click="openForm()">+ New Recurring</SButton>
        </SPageHeader>

        <SCard>
            <STable :columns="['Customer', 'Frequency', 'Next Issue', 'Total', 'Generated', 'Status', 'Actions']" :loading="loading">
                <tr v-for="ri in items" :key="ri.id">
                    <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ ri.client?.name }}</td>
                    <td class="px-4 py-3"><SBadge>{{ ri.frequency }}</SBadge></td>
                    <td class="px-4 py-3 text-sm">{{ ri.next_issue_date?.slice(0, 10) }}</td>
                    <td class="px-4 py-3 text-sm font-semibold">{{ Number(ri.total).toLocaleString('en-AE', { minimumFractionDigits: 2 }) }} {{ ri.currency }}</td>
                    <td class="px-4 py-3 text-sm text-center">{{ ri.invoices_generated }}</td>
                    <td class="px-4 py-3">
                        <SBadge :variant="ri.is_active ? 'success' : 'warning'">{{ ri.is_active ? 'Active' : 'Paused' }}</SBadge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button @click="toggle(ri)" class="text-xs" :class="ri.is_active ? 'text-amber-600' : 'text-green-600'" >{{ ri.is_active ? 'Pause' : 'Resume' }}</button>
                            <button @click="openForm(ri)" class="text-xs text-indigo-600 hover:underline">Edit</button>
                            <button @click="remove(ri.id)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Form Modal -->
        <SModal :show="showForm" :title="editing ? 'Edit Recurring Invoice' : 'New Recurring Invoice'" @close="showForm = false" size="lg">
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SSelect v-model="form.client_id" label="Customer" :options="clientOptions" required />
                    <SSelect v-model="form.frequency" label="Frequency" :options="freqOptions" required />
                    <SInput v-model="form.start_date" label="Start Date" type="date" required />
                    <SInput v-model="form.end_date" label="End Date (optional)" type="date" />
                    <SInput v-model="form.payment_terms" label="Payment Terms (days)" type="number" />
                    <SInput v-model="form.discount" label="Discount" type="number" step="0.01" />
                </div>
                <SInput v-model="form.notes" label="Notes" />

                <div class="border-t dark:border-slate-700 pt-4">
                    <p class="text-sm font-medium mb-2 dark:text-white">Line Items</p>
                    <div v-for="(item, i) in form.items" :key="i" class="flex gap-2 mb-2 items-end">
                        <SInput v-model="item.description" placeholder="Description" class="flex-1" required />
                        <SInput v-model="item.quantity" placeholder="Qty" type="number" step="0.01" class="w-20" required />
                        <SInput v-model="item.unit_price" placeholder="Price" type="number" step="0.01" class="w-28" required />
                        <SInput v-model="item.vat_rate" placeholder="VAT%" type="number" class="w-20" />
                        <button type="button" @click="form.items.splice(i, 1)" class="text-red-500 text-lg pb-2">&times;</button>
                    </div>
                    <button type="button" @click="form.items.push({ description: '', quantity: 1, unit_price: 0, vat_rate: 5 })" class="text-xs text-indigo-600 hover:underline">+ Add Line</button>
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
const clients = ref([]);
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const editing = ref(null);

const freqOptions = [
    { value: 'weekly', label: 'Weekly' }, { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' }, { value: 'yearly', label: 'Yearly' },
];
const clientOptions = ref([]);

const form = reactive({
    client_id: '', frequency: 'monthly', start_date: '', end_date: '', payment_terms: 30,
    discount: 0, notes: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }],
});

async function fetchList() {
    loading.value = true;
    try {
        const { data } = await api.get('/recurring-invoices');
        items.value = data.data;
    } finally { loading.value = false; }
}

async function fetchClients() {
    const { data } = await api.get('/clients/all');
    clientOptions.value = data.data.map(c => ({ value: c.id, label: c.name }));
}

function openForm(ri = null) {
    editing.value = ri?.id || null;
    if (ri) {
        Object.assign(form, {
            client_id: ri.client_id, frequency: ri.frequency, start_date: ri.start_date?.slice(0, 10),
            end_date: ri.end_date?.slice(0, 10) || '', payment_terms: ri.payment_terms, discount: ri.discount, notes: ri.notes || '',
            items: ri.items?.length ? ri.items.map(it => ({ ...it })) : [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }],
        });
    } else {
        Object.assign(form, { client_id: '', frequency: 'monthly', start_date: new Date().toISOString().slice(0, 10), end_date: '', payment_terms: 30, discount: 0, notes: '', items: [{ description: '', quantity: 1, unit_price: 0, vat_rate: 5 }] });
    }
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) await api.put(`/recurring-invoices/${editing.value}`, form);
        else await api.post('/recurring-invoices', form);
        showForm.value = false;
        fetchList();
    } finally { saving.value = false; }
}

async function toggle(ri) {
    await api.post(`/recurring-invoices/${ri.id}/toggle`);
    fetchList();
}

async function remove(id) {
    if (!confirm('Delete this recurring invoice?')) return;
    await api.delete(`/recurring-invoices/${id}`);
    fetchList();
}

onMounted(() => { fetchList(); fetchClients(); });
</script>
