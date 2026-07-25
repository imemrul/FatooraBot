<template>
    <div>
        <SPageHeader title="Users" subtitle="All users across all tenants" />

        <SCard noPad>
            <template #header>
                <input v-model="search" @input="debounceLoad" placeholder="Search users..."
                    class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2 text-sm w-64 outline-none focus:ring-2 focus:ring-indigo-500" />
            </template>

            <STable :columns="cols" :empty="!users.length" emptyText="No users found.">
                <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900 dark:text-white">{{ u.name }}</p>
                        <p class="text-xs text-slate-400">{{ u.email }}</p>
                    </td>
                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ u.company?.name || '—' }}</td>
                    <td class="px-5 py-3">
                        <SBadge :color="u.is_super_admin ? 'red' : u.is_active ? 'green' : 'default'">
                            {{ u.is_super_admin ? 'Super Admin' : u.is_active ? 'Active' : 'Inactive' }}
                        </SBadge>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-400">{{ new Date(u.created_at).toLocaleDateString() }}</td>
                    <td class="px-5 py-3 text-right">
                        <SButton v-if="!u.is_super_admin" size="xs" variant="ghost" @click="toggleStatus(u)">{{ u.is_active ? 'Deactivate' : 'Activate' }}</SButton>
                        <SButton v-if="!u.is_super_admin" size="xs" variant="ghost" @click="openResetModal(u)">Reset PW</SButton>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Reset Password Modal -->
        <SModal :show="showResetModal" title="Reset Password" @close="showResetModal = false">
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Reset password for <strong>{{ resetUser?.name }}</strong> ({{ resetUser?.email }})</p>
            <SInput v-model="newPassword" label="New Password" type="password" required placeholder="Min. 8 characters" />
            <template #footer>
                <SButton variant="secondary" @click="showResetModal = false">Cancel</SButton>
                <SButton variant="danger" :loading="resetting" @click="resetPassword">Reset Password</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/api';

const users = ref([]);
const search = ref('');
const showResetModal = ref(false);
const resetUser = ref(null);
const newPassword = ref('');
const resetting = ref(false);

const cols = [
    { key: 'name', label: 'User' }, { key: 'company', label: 'Company' },
    { key: 'status', label: 'Status' }, { key: 'created', label: 'Created' }, { key: 'actions', label: '', align: 'right' },
];

let searchTimeout;
function debounceLoad() { clearTimeout(searchTimeout); searchTimeout = setTimeout(load, 300); }

async function load() {
    const params = { per_page: 50 };
    if (search.value) params.search = search.value;
    const { data } = await api.get('/admin/users', { params });
    users.value = data.data;
}

async function toggleStatus(u) { await api.post(`/admin/users/${u.id}/toggle-status`); await load(); }

function openResetModal(u) { resetUser.value = u; newPassword.value = ''; showResetModal.value = true; }

async function resetPassword() {
    if (newPassword.value.length < 8) return;
    resetting.value = true;
    try {
        await api.post(`/admin/users/${resetUser.value.id}/reset-password`, { password: newPassword.value });
        showResetModal.value = false;
    } catch {} finally { resetting.value = false; }
}

onMounted(load);
</script>
