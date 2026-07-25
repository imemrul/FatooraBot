<template>
    <div>
        <SPageHeader title="Team Management" description="Invite and manage team members">
            <SButton @click="showInvite = true">+ Invite Member</SButton>
        </SPageHeader>

        <SCard>
            <div class="mb-4">
                <SInput v-model="search" placeholder="Search by name or email..." @input="debouncedFetch" />
            </div>

            <STable :columns="['Name', 'Email', 'Role', 'Status', 'Joined', 'Actions']" :loading="loading">
                <tr v-for="m in members" :key="m.id">
                    <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ m.name }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ m.email }}</td>
                    <td class="px-4 py-3">
                        <select v-if="m.id !== auth.user?.id" :value="m.roles?.[0]" @change="updateRole(m.id, $event.target.value)"
                            class="text-xs border rounded px-2 py-1 bg-white dark:bg-slate-700 dark:text-white dark:border-slate-600">
                            <option value="owner">Owner</option>
                            <option value="accountant">Accountant</option>
                            <option value="warehouse_manager">Warehouse Manager</option>
                            <option value="salesman">Salesman</option>
                        </select>
                        <SBadge v-else variant="info">{{ m.roles?.[0] }}</SBadge>
                    </td>
                    <td class="px-4 py-3">
                        <SBadge :variant="m.is_active ? 'success' : 'error'">{{ m.is_active ? 'Active' : 'Inactive' }}</SBadge>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ new Date(m.created_at).toLocaleDateString() }}</td>
                    <td class="px-4 py-3">
                        <div v-if="m.id !== auth.user?.id" class="flex gap-2">
                            <button @click="toggleStatus(m.id)" class="text-xs text-amber-600 hover:underline">
                                {{ m.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button @click="removeMember(m.id)" class="text-xs text-red-600 hover:underline">Remove</button>
                        </div>
                        <span v-else class="text-xs text-slate-400">You</span>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Invite Modal -->
        <SModal :show="showInvite" title="Invite Team Member" @close="showInvite = false">
            <form @submit.prevent="invite" class="space-y-4">
                <SInput v-model="form.name" label="Name" required />
                <SInput v-model="form.email" label="Email" type="email" required />
                <SInput v-model="form.phone" label="Phone" />
                <SInput v-model="form.password" label="Password" type="password" required />
                <SSelect v-model="form.role" label="Role" :options="roleOptions" required />
                <div class="flex justify-end gap-3 pt-2">
                    <SButton variant="secondary" @click="showInvite = false">Cancel</SButton>
                    <SButton type="submit" :loading="saving">Invite</SButton>
                </div>
            </form>
        </SModal>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/api';

const auth = useAuthStore();
const members = ref([]);
const loading = ref(false);
const saving = ref(false);
const showInvite = ref(false);
const search = ref('');
const form = reactive({ name: '', email: '', phone: '', password: '', role: 'salesman' });
const roleOptions = [
    { value: 'accountant', label: 'Accountant' },
    { value: 'warehouse_manager', label: 'Warehouse Manager' },
    { value: 'salesman', label: 'Salesman' },
];

let debounceTimer;
const debouncedFetch = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchMembers, 300); };

async function fetchMembers() {
    loading.value = true;
    try {
        const { data } = await api.get('/team', { params: { search: search.value || undefined } });
        members.value = data.members;
    } finally { loading.value = false; }
}

async function invite() {
    saving.value = true;
    try {
        await api.post('/team', form);
        showInvite.value = false;
        Object.assign(form, { name: '', email: '', phone: '', password: '', role: 'salesman' });
        fetchMembers();
    } finally { saving.value = false; }
}

async function updateRole(id, role) {
    await api.patch(`/team/${id}/role`, { role });
    fetchMembers();
}

async function toggleStatus(id) {
    await api.post(`/team/${id}/toggle-status`);
    fetchMembers();
}

async function removeMember(id) {
    if (!confirm('Remove this team member?')) return;
    await api.delete(`/team/${id}`);
    fetchMembers();
}

onMounted(fetchMembers);
</script>
