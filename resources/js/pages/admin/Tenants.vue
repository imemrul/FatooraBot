<template>
    <div>
        <SPageHeader title="Tenants" subtitle="Manage all companies on the platform" />

        <SCard noPad>
            <template #header>
                <input v-model="search" @input="debounceLoad" placeholder="Search companies..."
                    class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2 text-sm w-64 outline-none focus:ring-2 focus:ring-indigo-500" />
                <STabs :tabs="['all','active','inactive']" v-model="status" @update:modelValue="load" />
            </template>

            <STable :columns="cols" :empty="!tenants.length" emptyText="No tenants found.">
                <tr v-for="t in tenants" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900 dark:text-white">{{ t.name }}</p>
                        <p class="text-xs text-slate-400">{{ t.email }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <SBadge :color="planColor(t.subscription?.plan?.slug)">{{ t.subscription?.plan?.name || 'No Plan' }}</SBadge>
                    </td>
                    <td class="px-5 py-3 text-center text-slate-700 dark:text-slate-300">{{ t.users_count }}</td>
                    <td class="px-5 py-3 text-center text-slate-700 dark:text-slate-300">{{ t.invoices_count }}</td>
                    <td class="px-5 py-3">
                        <SBadge :color="t.is_active ? 'green' : 'red'">{{ t.is_active ? 'Active' : 'Inactive' }}</SBadge>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-400">{{ new Date(t.created_at).toLocaleDateString() }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <SButton size="xs" variant="ghost" @click="openPlanModal(t)">Plan</SButton>
                            <SButton size="xs" variant="ghost" @click="toggleStatus(t)">{{ t.is_active ? 'Deactivate' : 'Activate' }}</SButton>
                            <SButton size="xs" variant="ghost" class="text-purple-600 dark:text-purple-400" @click="impersonate(t)">Impersonate</SButton>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Assign Plan Modal -->
        <SModal :show="showPlanModal" title="Assign Plan" @close="showPlanModal = false">
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Assign a plan to <strong>{{ selectedTenant?.name }}</strong></p>
            <div class="space-y-3">
                <div v-for="p in plans" :key="p.id" @click="selectedPlan = p.id"
                    :class="selectedPlan === p.id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-slate-200 dark:border-slate-700'"
                    class="border-2 rounded-xl p-4 cursor-pointer transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white">{{ p.name }}</p>
                            <p class="text-xs text-slate-400">{{ p.description }}</p>
                        </div>
                        <p class="text-lg font-bold text-slate-900 dark:text-white">AED {{ p.price_monthly }}<span class="text-xs text-slate-400">/mo</span></p>
                    </div>
                    <div class="flex gap-4 mt-2 text-xs text-slate-500">
                        <span>{{ p.max_users }} users</span>
                        <span>{{ p.max_invoices_per_month }} inv/mo</span>
                        <span>{{ p.max_products }} products</span>
                    </div>
                </div>
            </div>
            <template #footer>
                <SButton variant="secondary" @click="showPlanModal = false">Cancel</SButton>
                <SButton :loading="assigning" @click="assignPlan">Assign Plan</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/api';

const router = useRouter();
const auth = useAuthStore();
const tenants = ref([]);
const plans = ref([]);
const search = ref('');
const status = ref('all');
const showPlanModal = ref(false);
const selectedTenant = ref(null);
const selectedPlan = ref(null);
const assigning = ref(false);

const cols = [
    { key: 'name', label: 'Company' }, { key: 'plan', label: 'Plan' },
    { key: 'users', label: 'Users', align: 'center' }, { key: 'invoices', label: 'Invoices', align: 'center' },
    { key: 'status', label: 'Status' }, { key: 'created', label: 'Created' }, { key: 'actions', label: '', align: 'right' },
];

function planColor(slug) { return { free: 'default', starter: 'blue', professional: 'purple', enterprise: 'amber' }[slug] || 'default'; }

let searchTimeout;
function debounceLoad() { clearTimeout(searchTimeout); searchTimeout = setTimeout(load, 300); }

async function load() {
    const params = { per_page: 50 };
    if (search.value) params.search = search.value;
    if (status.value !== 'all') params.status = status.value;
    const { data } = await api.get('/admin/tenants', { params });
    tenants.value = data.data;
}

async function toggleStatus(t) {
    await api.post(`/admin/tenants/${t.id}/toggle-status`);
    await load();
}

function openPlanModal(t) {
    selectedTenant.value = t;
    selectedPlan.value = t.subscription?.plan_id || null;
    showPlanModal.value = true;
}

async function assignPlan() {
    if (!selectedPlan.value) return;
    assigning.value = true;
    try {
        await api.post(`/admin/tenants/${selectedTenant.value.id}/assign-plan`, { plan_id: selectedPlan.value });
        showPlanModal.value = false;
        await load();
    } catch {} finally { assigning.value = false; }
}

async function impersonate(t) {
    try {
        const { data } = await api.post(`/admin/tenants/${t.id}/impersonate`);
        // Store super admin token for return
        localStorage.setItem('super_admin_token', localStorage.getItem('token'));
        // Switch to impersonated user
        auth.setAuth({ user: data.user, token: data.token });
        router.push('/');
    } catch (e) { alert(e.response?.data?.message || 'Impersonation failed.'); }
}

onMounted(async () => {
    await load();
    const { data } = await api.get('/admin/plans');
    plans.value = data.data;
});
</script>
