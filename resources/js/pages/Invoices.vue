<template>
    <div>
        <SPageHeader title="Invoices" subtitle="Manage invoices and payments">
            <SButton v-can="'manage_invoices'" @click="openCreate">New Invoice</SButton>
        </SPageHeader>

        <!-- Status filter -->
        <STabs :tabs="['all','draft','sent','paid','overdue','cancelled']" v-model="filterStatus" class="mb-4" @update:modelValue="load" />

        <SCard noPad>
            <STable :columns="cols" :empty="!store.invoices.length" emptyText="No invoices found.">
                <tr v-for="inv in store.invoices" :key="inv.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs font-medium text-slate-900 dark:text-white">{{ inv.invoice_number }}</td>
                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ inv.client?.name }}</td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ inv.issue_date }}</td>
                    <td class="px-5 py-3 text-right font-medium text-slate-900 dark:text-white">{{ inv.currency }} {{ fmt(inv.total) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span :class="inv.balance_due > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-emerald-600 dark:text-emerald-400'">{{ fmt(inv.balance_due) }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <SBadge :color="statusColor(inv.status)">{{ inv.status }}</SBadge>
                        <SBadge v-if="inv.is_overdue" color="red" size="xs" class="ml-1">overdue</SBadge>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <SButton size="xs" variant="ghost" @click="downloadPdf(inv)">PDF</SButton>
                            <template v-if="can('manage_invoices')">
                                <SButton v-if="inv.status === 'draft'" size="xs" variant="ghost" class="text-blue-600 dark:text-blue-400" @click="sendInvoice(inv)">Send</SButton>
                                <SButton v-if="inv.balance_due > 0 && inv.status !== 'draft' && inv.status !== 'cancelled'" size="xs" variant="ghost" class="text-emerald-600 dark:text-emerald-400" @click="openPayment(inv)">Pay</SButton>
                                <SButton v-if="inv.status === 'draft'" size="xs" variant="ghost" @click="openEdit(inv)">Edit</SButton>
                                <SButton v-if="inv.status !== 'cancelled' && inv.status !== 'paid'" size="xs" variant="ghost" class="text-red-600 dark:text-red-400" @click="cancelInvoice(inv)">Cancel</SButton>
                            </template>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Create/Edit Modal -->
        <SModal :show="showInvModal" :title="editingInv ? 'Edit Invoice' : 'New Invoice'" size="lg" @close="showInvModal = false">
            <SAlert v-if="invError" variant="error" class="mb-4">{{ invError }}</SAlert>
            <form @submit.prevent="saveInvoice" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <SSelect v-model="invForm.client_id" label="Customer" required>
                        <option value="">Select</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </SSelect>
                    <SInput v-model="invForm.issue_date" label="Issue Date" type="date" required />
                    <SInput v-model="invForm.due_date" label="Due Date" type="date" required />
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Line Items</label>
                        <button type="button" @click="addItem" class="text-xs text-indigo-600 dark:text-indigo-400">+ Add</button>
                    </div>
                    <div v-for="(item, idx) in invForm.items" :key="idx" class="grid grid-cols-12 gap-2 mb-2">
                        <input v-model="item.description" placeholder="Description" required class="col-span-5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                        <input v-model="item.quantity" type="number" step="0.01" min="0.01" placeholder="Qty" required class="col-span-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                        <input v-model="item.unit_price" type="number" step="0.01" min="0" placeholder="Price" required class="col-span-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                        <input v-model="item.vat_rate" type="number" step="0.01" placeholder="VAT%" class="col-span-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                        <button type="button" @click="invForm.items.splice(idx, 1)" v-if="invForm.items.length > 1" class="col-span-1 text-red-500 text-xs">✕</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="invForm.discount" label="Discount (AED)" type="number" step="0.01" min="0" />
                    <SInput v-model="invForm.notes" label="Notes" />
                </div>
            </form>
            <template #footer>
                <SButton variant="secondary" @click="showInvModal = false">Cancel</SButton>
                <SButton :loading="invSaving" @click="saveInvoice">Save Invoice</SButton>
            </template>
        </SModal>

        <!-- Payment Modal -->
        <SModal :show="showPayModal" title="Record Payment" @close="showPayModal = false">
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ payInvoice?.invoice_number }} — Balance:
                <span class="font-semibold text-amber-600 dark:text-amber-400">{{ payInvoice?.currency }} {{ fmt(payInvoice?.balance_due) }}</span>
            </p>
            <SAlert v-if="payError" variant="error" class="mb-4">{{ payError }}</SAlert>
            <form @submit.prevent="submitPayment" class="space-y-4">
                <SInput v-model="payForm.amount" label="Amount" type="number" step="0.01" min="0.01" :max="payInvoice?.balance_due" required />
                <div class="grid grid-cols-2 gap-4">
                    <SSelect v-model="payForm.method" label="Method" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="card">Card</option>
                    </SSelect>
                    <SInput v-model="payForm.payment_date" label="Date" type="date" required />
                </div>
                <SInput v-model="payForm.reference" label="Reference" placeholder="Cheque #, transfer ref..." />
            </form>
            <template #footer>
                <SButton variant="secondary" @click="showPayModal = false">Cancel</SButton>
                <SButton variant="success" :loading="paySaving" @click="submitPayment">Record Payment</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useInvoiceStore } from '@/stores/invoices';
import { usePermission } from '@/composables/usePermission';
import api from '@/utils/api';

const store = useInvoiceStore();
const { can } = usePermission();
const filterStatus = ref('all');
const clients = ref([]);

const cols = [
    { key: 'number', label: 'Invoice #' }, { key: 'customer', label: 'Customer' }, { key: 'date', label: 'Date' },
    { key: 'total', label: 'Total', align: 'right' }, { key: 'balance', label: 'Balance', align: 'right' },
    { key: 'status', label: 'Status' }, { key: 'actions', label: '', align: 'right' },
];

function statusColor(s) { return { draft: 'default', sent: 'blue', paid: 'green', overdue: 'red', cancelled: 'default' }[s] || 'default'; }

const showInvModal = ref(false);
const editingInv = ref(null);
const invSaving = ref(false);
const invError = ref('');
const defaultItem = () => ({ description: '', quantity: 1, unit_price: '', vat_rate: 5 });
const invForm = reactive({ client_id: '', issue_date: '', due_date: '', discount: 0, notes: '', items: [defaultItem()] });

const showPayModal = ref(false);
const payInvoice = ref(null);
const paySaving = ref(false);
const payError = ref('');
const payForm = reactive({ amount: '', method: 'bank_transfer', payment_date: new Date().toISOString().split('T')[0], reference: '' });

function fmt(v) { return Number(v || 0).toFixed(2); }
function addItem() { invForm.items.push(defaultItem()); }

function openCreate() {
    Object.assign(invForm, { client_id: '', issue_date: new Date().toISOString().split('T')[0], due_date: '', discount: 0, notes: '', items: [defaultItem()] });
    editingInv.value = null; invError.value = ''; showInvModal.value = true;
}
function openEdit(inv) {
    Object.assign(invForm, { client_id: inv.client?.id, issue_date: inv.issue_date, due_date: inv.due_date, discount: inv.discount, notes: inv.notes || '', items: inv.items?.map(i => ({ description: i.description, quantity: i.quantity, unit_price: i.unit_price, vat_rate: i.vat_rate, product_id: i.product_id })) || [defaultItem()] });
    editingInv.value = inv.id; invError.value = ''; showInvModal.value = true;
}
function openPayment(inv) {
    payInvoice.value = inv;
    Object.assign(payForm, { amount: inv.balance_due, method: 'bank_transfer', payment_date: new Date().toISOString().split('T')[0], reference: '' });
    payError.value = ''; showPayModal.value = true;
}

async function saveInvoice() {
    invSaving.value = true; invError.value = '';
    try { if (editingInv.value) await store.updateInvoice(editingInv.value, invForm); else await store.createInvoice(invForm); showInvModal.value = false; await load(); }
    catch (e) { invError.value = e.response?.data?.message || 'Save failed.'; } finally { invSaving.value = false; }
}
async function submitPayment() {
    paySaving.value = true; payError.value = '';
    try { await store.recordPayment(payInvoice.value.id, payForm); showPayModal.value = false; await load(); }
    catch (e) { payError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat()[0] || 'Failed.'; } finally { paySaving.value = false; }
}
async function sendInvoice(inv) { await store.sendInvoice(inv.id); await load(); }
async function cancelInvoice(inv) { if (confirm('Cancel this invoice?')) { await store.updateStatus(inv.id, 'cancelled'); await load(); } }
async function deleteInvoice(inv) { if (confirm('Delete this draft?')) { await store.deleteInvoice(inv.id); } }
async function downloadPdf(inv) { await store.downloadPdf(inv.id, inv.invoice_number); }
async function load() { await store.fetchInvoices(1, filterStatus.value === 'all' ? '' : filterStatus.value); }

onMounted(async () => { await load(); const { data } = await api.get('/clients/all'); clients.value = data.data; });
</script>
