<template>
    <div>
        <SPageHeader title="Platform Dashboard" subtitle="System-wide metrics and health" />

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <SStatCard label="Total Tenants" :value="String(d.total_companies ?? 0)" :sub="`${d.active_companies ?? 0} active`" icon="&#127970;" iconBg="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600" />
            <SStatCard label="Total Users" :value="String(d.total_users ?? 0)" icon="&#128101;" iconBg="bg-blue-50 dark:bg-blue-900/30 text-blue-600" />
            <SStatCard label="MRR" :value="'AED ' + fmt(d.mrr)" icon="&#128176;" iconBg="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600" valueColor="text-emerald-600 dark:text-emerald-400" />
            <SStatCard label="New This Month" :value="String(d.new_companies_this_month ?? 0)" :sub="(d.growth_pct > 0 ? '+' : '') + d.growth_pct + '%'" icon="&#128200;" iconBg="bg-amber-50 dark:bg-amber-900/30 text-amber-600" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Growth trend -->
            <SCard title="Tenant Growth (6 months)">
                <div class="flex items-end gap-3 h-40">
                    <div v-for="m in d.growth_trend" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-indigo-100 dark:bg-indigo-900/30 rounded-t" :style="{ height: barHeight(m.count) + '%' }"></div>
                        <span class="text-[10px] text-slate-400">{{ m.month }}</span>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ m.count }}</span>
                    </div>
                </div>
            </SCard>

            <!-- Plan breakdown -->
            <SCard title="Subscriptions by Plan">
                <div v-if="d.plan_breakdown?.length" class="space-y-3">
                    <div v-for="p in d.plan_breakdown" :key="p.slug" class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <SBadge :color="planColor(p.slug)">{{ p.name }}</SBadge>
                        </div>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">{{ p.count }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400 text-center py-4">No active subscriptions</p>
            </SCard>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <SStatCard label="Total Invoices" :value="String(d.total_invoices ?? 0)" icon="&#128196;" iconBg="bg-slate-100 dark:bg-slate-700 text-slate-600" />
            <SStatCard label="Platform Revenue" :value="'AED ' + fmt(d.platform_revenue)" icon="&#128178;" iconBg="bg-green-50 dark:bg-green-900/30 text-green-600" />
            <SStatCard label="Total Collected" :value="'AED ' + fmt(d.total_collected)" icon="&#9989;" iconBg="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/utils/api';

const d = ref({});

function fmt(v) { return Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 }); }
function barHeight(count) { const max = Math.max(...(d.value.growth_trend || []).map(m => m.count), 1); return Math.max((count / max) * 100, 5); }
function planColor(slug) { return { free: 'default', starter: 'blue', professional: 'purple', enterprise: 'amber' }[slug] || 'default'; }

onMounted(async () => { try { const { data } = await api.get('/admin/dashboard'); d.value = data; } catch {} });
</script>
