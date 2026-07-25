<template>
    <div v-if="client">
        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <router-link to="/clients" class="text-sm text-indigo-600 hover:text-indigo-800 mb-1 inline-block">&larr; Back to Customers</router-link>
                <h1 class="text-2xl font-bold text-slate-900">{{ client.name }}</h1>
                <p v-if="client.contact_person" class="text-slate-500">{{ client.contact_person }}</p>
            </div>
            <div class="flex gap-2">
                <span v-if="client.over_credit_limit" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">Over Credit Limit</span>
                <span v-if="client.overdue_invoice_count > 0" class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">{{ client.overdue_invoice_count }} Overdue</span>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">Total Invoiced</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ fmt(client.total_invoiced) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">Total Paid</p>
                <p class="text-xl font-bold text-green-600 mt-1">{{ fmt(client.total_paid) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">Outstanding</p>
                <p class="text-xl font-bold mt-1" :class="client.outstanding_balance > 0 ? 'text-amber-600' : 'text-slate-400'">{{ fmt(client.outstanding_balance) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">Overdue</p>
                <p class="text-xl font-bold mt-1" :class="client.overdue_amount > 0 ? 'text-red-600' : 'text-slate-400'">{{ fmt(client.overdue_amount) }}</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 mb-4 bg-slate-100 rounded-lg p-1 w-fit">
            <button v-for="t in ['Overview', 'Ledger', 'Invoices']" :key="t" @click="tab = t"
                :class="tab === t ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-1.5 text-sm font-medium rounded-md transition">{{ t }}</button>
        </div>

        <!-- Overview tab -->
        <div v-if="tab === 'Overview'" class="bg-white rounded-xl border border-slate-200 p-6">
            <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.phone || '—' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.email || '—' }}</dd></div>
                <div><dt class="text-slate-500">TRN</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.tax_registration_number || '—' }}</dd></div>
                <div><dt class="text-slate-500">Credit Limit</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.credit_limit > 0 ? fmt(client.credit_limit) : 'No limit' }}</dd></div>
                <div><dt class="text-slate-500">Payment Terms</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.payment_terms }} days</dd></div>
                <div><dt class="text-slate-500">City</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.city || '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500">Address</dt><dd class="font-medium text-slate-900 mt-0.5">{{ client.address || '—' }}</dd></div>
                <div v-if="client.notes" class="col-span-2"><dt class="text-slate-500">Notes</dt><dd class="font-medium text-slate-900 mt-0.5 whitespace-pre-line">{{ client.notes }}</dd></div>
            </dl>
        </div>

        <!-- Ledger tab -->
        <div v-if="tab === 'Ledger'" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-5 py-3">Date</th>
                        <th class="text-left px-5 py-3">Type</th>
                        <th class="text-left px-5 py-3">Reference</th>
                        <th class="text-right px-5 py-3">Debit</th>
                        <th class="text-right px-5 py-3">Credit</th>
                        <th class="text-right px-5 py-3">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(e, i) in statement.entries" :key="i" class="border-t border-slate-100">
                        <td class="px-5 py-3 text-slate-500">{{ e.date }}</td>
                        <td class="px-5 py-3">
                            <span :class="e.type === 'invoice' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'"
                                class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">{{ e.type }}</span>
                        </td>
                        <td class="px-5 py-3 font-mono text-xs">{{ e.reference }}</td>
                        <td class="px-5 py-3 text-right">{{ e.debit > 0 ? fmt(e.debit) : '' }}</td>
                        <td class="px-5 py-3 text-right text-green-600">{{ e.credit > 0 ? fmt(e.credit) : '' }}</td>
                        <td class="px-5 py-3 text-right font-semibold">{{ fmt(e.balance) }}</td>
                    </tr>
                    <tr v-if="!statement.entries?.length">
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">No transactions yet.</td>
                    </tr>
                </tbody>
                <tfoot v-if="statement.entries?.length" class="bg-slate-50 font-semibold text-sm">
                    <tr>
                        <td colspan="3" class="px-5 py-3">Totals</td>
                        <td class="px-5 py-3 text-right">{{ fmt(statement.total_invoiced) }}</td>
                        <td class="px-5 py-3 text-right text-green-600">{{ fmt(statement.total_paid) }}</td>
                        <td class="px-5 py-3 text-right">{{ fmt(statement.outstanding_balance) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Invoices tab -->
        <div v-if="tab === 'Invoices'" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-5 py-3">Invoice #</th>
                        <th class="text-left px-5 py-3">Date</th>
                        <th class="text-left px-5 py-3">Due</th>
                        <th class="text-right px-5 py-3">Total</th>
                        <th class="text-right px-5 py-3">Paid</th>
                        <th class="text-right px-5 py-3">Balance</th>
                        <th class="text-left px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="inv in invoices" :key="inv.id" class="border-t border-slate-100">
                        <td class="px-5 py-3 font-mono text-xs font-medium">{{ inv.invoice_number }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ inv.issue_date }}</td>
                        <td class="px-5 py-3" :class="inv.is_overdue ? 'text-red-600 font-medium' : 'text-slate-500'">{{ inv.due_date }}</td>
                        <td class="px-5 py-3 text-right">{{ fmt(inv.total) }}</td>
                        <td class="px-5 py-3 text-right text-green-600">{{ fmt(inv.paid_amount) }}</td>
                        <td class="px-5 py-3 text-right font-semibold" :class="inv.balance_due > 0 ? 'text-amber-600' : 'text-slate-400'">{{ fmt(inv.balance_due) }}</td>
                        <td class="px-5 py-3">
                            <span :class="statusClass(inv)" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ inv.status }}</span>
                        </td>
                    </tr>
                    <tr v-if="!invoices.length">
                        <td colspan="7" class="px-5 py-8 text-center text-slate-400">No invoices yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div v-else class="flex items-center justify-center h-64 text-slate-400">Loading...</div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/utils/api';

const route = useRoute();
const client = ref(null);
const tab = ref('Overview');
const statement = ref({ entries: [], total_invoiced: 0, total_paid: 0, outstanding_balance: 0 });
const invoices = ref([]);

function fmt(v) { return Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 }); }
function statusClass(inv) {
    return { draft: 'bg-slate-100 text-slate-600', sent: 'bg-blue-100 text-blue-700', paid: 'bg-green-100 text-green-700', overdue: 'bg-red-100 text-red-700', cancelled: 'bg-slate-100 text-slate-400' }[inv.status] || 'bg-slate-100 text-slate-600';
}

async function loadClient() {
    const { data } = await api.get(`/clients/${route.params.id}`);
    client.value = data.data;
}

watch(tab, async (t) => {
    if (t === 'Ledger' && !statement.value.entries.length) {
        const { data } = await api.get(`/clients/${route.params.id}/statement`);
        statement.value = data;
    }
    if (t === 'Invoices' && !invoices.length) {
        const { data } = await api.get(`/clients/${route.params.id}/ledger?per_page=100`);
        invoices.value = data.data;
    }
});

onMounted(loadClient);
</script>
