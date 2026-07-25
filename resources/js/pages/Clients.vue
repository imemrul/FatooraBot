<template>
    <div>
        <SPageHeader title="Customers" subtitle="Manage your customer relationships">
            <SButton v-can="'manage_customers'" @click="openCreate">Add Customer</SButton>
        </SPageHeader>

        <SCard noPad>
            <template #header>
                <input v-model="search" type="text" placeholder="Search by name, email, or phone..."
                    class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2 text-sm w-64 outline-none focus:ring-2 focus:ring-indigo-500" />
            </template>

            <STable :columns="cols" :empty="!clients.length" emptyText="No customers found.">
                <tr v-for="c in clients" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-3">
                        <router-link :to="`/clients/${c.id}`" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ c.name }}</router-link>
                        <p v-if="c.contact_person" class="text-xs text-slate-400 dark:text-slate-500">{{ c.contact_person }}</p>
                    </td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                        <div>{{ c.phone || '—' }}</div>
                        <div class="text-xs">{{ c.email || '' }}</div>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <span :class="c.outstanding_balance > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-slate-400 dark:text-slate-500'">{{ formatMoney(c.outstanding_balance) }}</span>
                        <SBadge v-if="c.over_credit_limit" color="red" size="xs" class="ml-1">OVER LIMIT</SBadge>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <span v-if="c.overdue_amount > 0" class="text-red-600 dark:text-red-400 font-semibold">{{ formatMoney(c.overdue_amount) }}</span>
                        <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                    </td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ c.payment_terms }}d</td>
                    <td class="px-5 py-3 text-right" v-can="'manage_customers'">
                        <SButton size="xs" variant="ghost" @click="openEdit(c)">Edit</SButton>
                        <SButton size="xs" variant="ghost" class="text-red-600 dark:text-red-400" @click="remove(c)">Delete</SButton>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Modal -->
        <SModal :show="showModal" :title="editing ? 'Edit Customer' : 'New Customer'" @close="showModal = false">
            <SAlert v-if="formError" variant="error" class="mb-4">{{ formError }}</SAlert>
            <form @submit.prevent="save" class="space-y-4">
                <SInput v-model="form.name" label="Company / Customer Name" required />
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.contact_person" label="Contact Person" />
                    <SInput v-model="form.phone" label="Phone" type="tel" />
                </div>
                <SInput v-model="form.email" label="Email" type="email" />
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.tax_registration_number" label="TRN" maxlength="15" />
                    <SInput v-model="form.city" label="City" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Address</label>
                    <textarea v-model="form.address" rows="2" class="w-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.credit_limit" label="Credit Limit (AED)" type="number" min="0" step="0.01" />
                    <SInput v-model="form.payment_terms" label="Payment Terms (days)" type="number" min="0" max="365" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="w-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
            </form>
            <template #footer>
                <SButton variant="secondary" @click="showModal = false">Cancel</SButton>
                <SButton :loading="saving" @click="save">Save</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import api from '@/utils/api';

const clients = ref([]);
const search = ref('');
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const formError = ref('');

const cols = [
    { key: 'name', label: 'Customer' }, { key: 'contact', label: 'Contact' },
    { key: 'outstanding', label: 'Outstanding', align: 'right' }, { key: 'overdue', label: 'Overdue', align: 'right' },
    { key: 'terms', label: 'Terms' }, { key: 'actions', label: '', align: 'right' },
];

const defaultForm = { name: '', contact_person: '', email: '', phone: '', tax_registration_number: '', credit_limit: 0, payment_terms: 30, address: '', city: '', country: 'AE', notes: '' };
const form = reactive({ ...defaultForm });

function formatMoney(v) { return Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 }); }

function openCreate() { Object.assign(form, defaultForm); editing.value = null; formError.value = ''; showModal.value = true; }
function openEdit(c) {
    Object.assign(form, { name: c.name, contact_person: c.contact_person || '', email: c.email || '', phone: c.phone || '', tax_registration_number: c.tax_registration_number || '', credit_limit: c.credit_limit, payment_terms: c.payment_terms, address: c.address || '', city: c.city || '', country: c.country || 'AE', notes: c.notes || '' });
    editing.value = c.id; formError.value = ''; showModal.value = true;
}

async function save() {
    saving.value = true; formError.value = '';
    try { if (editing.value) await api.put(`/clients/${editing.value}`, form); else await api.post('/clients', form); showModal.value = false; await load(); }
    catch (e) { formError.value = e.response?.data?.message || 'Save failed.'; } finally { saving.value = false; }
}

async function remove(c) {
    if (!confirm(`Delete ${c.name}?`)) return;
    try { await api.delete(`/clients/${c.id}`); await load(); } catch (e) { alert(e.response?.data?.message || 'Delete failed.'); }
}

let searchTimeout;
watch(search, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(load, 300); });

async function load() {
    const params = search.value ? { search: search.value, per_page: 100 } : { per_page: 100 };
    const { data } = await api.get('/clients', { params });
    clients.value = data.data;
}

onMounted(load);
</script>
