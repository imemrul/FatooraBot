<template>
    <div>
        <SPageHeader title="Impersonation Logs" subtitle="Audit trail of tenant impersonation sessions" />

        <SCard noPad>
            <STable :columns="cols" :empty="!logs.length" emptyText="No impersonation logs.">
                <tr v-for="l in logs" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                    <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400">{{ new Date(l.created_at).toLocaleString() }}</td>
                    <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">{{ l.super_admin?.name }}</td>
                    <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ l.company?.name }}</td>
                    <td class="px-5 py-3">
                        <SBadge :color="l.action === 'started' ? 'amber' : 'green'">{{ l.action }}</SBadge>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-400">{{ l.ip_address }}</td>
                </tr>
            </STable>
        </SCard>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/api';

const logs = ref([]);
const cols = [
    { key: 'time', label: 'Time' }, { key: 'admin', label: 'Super Admin' },
    { key: 'company', label: 'Company' }, { key: 'action', label: 'Action' }, { key: 'ip', label: 'IP' },
];

onMounted(async () => { const { data } = await api.get('/admin/impersonation-logs'); logs.value = data.data; });
</script>
