<template>
    <div>
        <SPageHeader title="Plans" subtitle="Manage subscription plans">
            <SButton @click="openCreate">Create Plan</SButton>
        </SPageHeader>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="p in plans" :key="p.id"
                class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <SBadge :color="planColor(p.slug)" size="md">{{ p.name }}</SBadge>
                    <SButton size="xs" variant="ghost" @click="openEdit(p)">Edit</SButton>
                </div>
                <p class="text-3xl font-bold text-slate-900 dark:text-white">AED {{ p.price_monthly }}<span class="text-sm text-slate-400 font-normal">/mo</span></p>
                <p class="text-xs text-slate-400 mb-4">AED {{ p.price_yearly }}/yr</p>
                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400 flex-1">
                    <p>&#128101; {{ p.max_users }} users</p>
                    <p>&#128196; {{ p.max_invoices_per_month }} invoices/mo</p>
                    <p>&#128230; {{ p.max_products }} products</p>
                    <p>&#127970; {{ p.max_warehouses }} warehouses</p>
                    <p>&#128273; {{ p.max_api_tokens }} API tokens</p>
                </div>
                <div class="flex flex-wrap gap-1 mt-4">
                    <SBadge v-if="p.feature_pdf_invoices" color="green" size="xs">PDF</SBadge>
                    <SBadge v-if="p.feature_whatsapp_parser" color="green" size="xs">WhatsApp</SBadge>
                    <SBadge v-if="p.feature_api_access" color="green" size="xs">API</SBadge>
                    <SBadge v-if="p.feature_webhooks" color="green" size="xs">Webhooks</SBadge>
                    <SBadge v-if="p.feature_audit_log" color="green" size="xs">Audit</SBadge>
                    <SBadge v-if="p.feature_payment_reminders" color="green" size="xs">Reminders</SBadge>
                </div>
                <p class="text-xs text-slate-400 mt-3">{{ p.subscriptions_count ?? 0 }} active subscriptions</p>
            </div>
        </div>

        <!-- Plan Modal -->
        <SModal :show="showModal" :title="editing ? 'Edit Plan' : 'Create Plan'" size="lg" @close="showModal = false">
            <SAlert v-if="formError" variant="error" class="mb-4">{{ formError }}</SAlert>
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.name" label="Name" required />
                    <SInput v-model="form.slug" label="Slug" required :disabled="!!editing" />
                </div>
                <SInput v-model="form.description" label="Description" />
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.price_monthly" label="Monthly Price (AED)" type="number" step="0.01" min="0" required />
                    <SInput v-model="form.price_yearly" label="Yearly Price (AED)" type="number" step="0.01" min="0" required />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <SInput v-model="form.max_users" label="Max Users" type="number" min="1" required />
                    <SInput v-model="form.max_invoices_per_month" label="Max Invoices/mo" type="number" min="1" required />
                    <SInput v-model="form.max_products" label="Max Products" type="number" min="1" required />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <SInput v-model="form.max_warehouses" label="Max Warehouses" type="number" min="1" required />
                    <SInput v-model="form.max_api_tokens" label="Max API Tokens" type="number" min="0" />
                    <SInput v-model="form.sort_order" label="Sort Order" type="number" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">Features</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label v-for="f in features" :key="f.key" class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" v-model="form[f.key]" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            {{ f.label }}
                        </label>
                    </div>
                </div>
            </form>
            <template #footer>
                <SButton variant="secondary" @click="showModal = false">Cancel</SButton>
                <SButton :loading="saving" @click="save">{{ editing ? 'Update' : 'Create' }}</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/utils/api';

const plans = ref([]);
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const formError = ref('');

const features = [
    { key: 'feature_pdf_invoices', label: 'PDF Invoices' },
    { key: 'feature_whatsapp_parser', label: 'WhatsApp Parser' },
    { key: 'feature_api_access', label: 'API Access' },
    { key: 'feature_webhooks', label: 'Webhooks' },
    { key: 'feature_audit_log', label: 'Audit Log' },
    { key: 'feature_payment_reminders', label: 'Payment Reminders' },
];

const defaultForm = { name: '', slug: '', description: '', price_monthly: 0, price_yearly: 0, max_users: 1, max_invoices_per_month: 10, max_products: 50, max_warehouses: 1, max_api_tokens: 0, sort_order: 0, feature_pdf_invoices: true, feature_whatsapp_parser: false, feature_api_access: false, feature_webhooks: false, feature_audit_log: false, feature_payment_reminders: false };
const form = reactive({ ...defaultForm });

function planColor(slug) { return { free: 'default', starter: 'blue', professional: 'purple', enterprise: 'amber' }[slug] || 'default'; }

function openCreate() { Object.assign(form, defaultForm); editing.value = null; formError.value = ''; showModal.value = true; }
function openEdit(p) {
    Object.assign(form, { ...p });
    editing.value = p.id; formError.value = ''; showModal.value = true;
}

async function save() {
    saving.value = true; formError.value = '';
    try {
        if (editing.value) await api.put(`/admin/plans/${editing.value}`, form);
        else await api.post('/admin/plans', form);
        showModal.value = false; await load();
    } catch (e) { formError.value = e.response?.data?.message || 'Save failed.'; }
    finally { saving.value = false; }
}

async function load() { const { data } = await api.get('/admin/plans'); plans.value = data.data; }

onMounted(load);
</script>
