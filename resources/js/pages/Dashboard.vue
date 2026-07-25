<template>
    <div>
        <SPageHeader title="Dashboard" :subtitle="today" />

        <!-- Stat cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <SStatCard label="Today's Sales" :value="'AED ' + fmt(d.stats?.daily_sales)" icon="&#128176;" iconBg="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400" />
            <SStatCard label="Monthly Revenue" :value="'AED ' + fmt(d.stats?.monthly_revenue)" icon="&#128200;" iconBg="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400" />
            <SStatCard label="Collected" :value="'AED ' + fmt(d.stats?.monthly_collected)" icon="&#9989;" iconBg="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400" />
            <SStatCard label="Outstanding" :value="'AED ' + fmt(d.stats?.total_outstanding)" icon="&#9888;" iconBg="bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400" valueColor="text-amber-600 dark:text-amber-400" />
            <SStatCard label="Overdue" :value="String(d.reminders?.overdue?.count ?? 0)" :sub="'AED ' + fmt(d.reminders?.overdue?.total)" icon="&#128308;" iconBg="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400" valueColor="text-red-600 dark:text-red-400" />
            <SStatCard label="Due Today" :value="String(d.reminders?.due_today?.count ?? 0)" :sub="'AED ' + fmt(d.reminders?.due_today?.total)" icon="&#128197;" iconBg="bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400" />
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <SCard title="Revenue Trend (12 months)">
                <div class="h-44">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full">
                        <line v-for="y in [20,40,60,80]" :key="y" x1="0" :y1="y" x2="100" :y2="y" class="stroke-slate-100 dark:stroke-slate-700" stroke-width="0.4" />
                        <polygon v-if="revPoints" :points="revPoints + ` ${revW * ((d.revenue_trend?.length||1)-1)},100 0,100`" fill="#4f46e5" opacity="0.07" />
                        <polyline v-if="revPoints" :points="revPoints" fill="none" stroke="#4f46e5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <circle v-for="(p,i) in revPts" :key="i" :cx="p.x" :cy="p.y" r="1.5" fill="#4f46e5" />
                    </svg>
                </div>
                <div class="flex justify-between mt-1">
                    <span v-for="l in revLabels" :key="l" class="text-[10px] text-slate-400 dark:text-slate-500">{{ l }}</span>
                </div>
            </SCard>
            <SCard title="Payment Collections (12 months)">
                <div class="h-44">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full">
                        <line v-for="y in [20,40,60,80]" :key="y" x1="0" :y1="y" x2="100" :y2="y" class="stroke-slate-100 dark:stroke-slate-700" stroke-width="0.4" />
                        <polygon v-if="colPoints" :points="colPoints + ` ${colW * ((d.collection_trend?.length||1)-1)},100 0,100`" fill="#16a34a" opacity="0.07" />
                        <polyline v-if="colPoints" :points="colPoints" fill="none" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <circle v-for="(p,i) in colPts" :key="i" :cx="p.x" :cy="p.y" r="1.5" fill="#16a34a" />
                    </svg>
                </div>
                <div class="flex justify-between mt-1">
                    <span v-for="l in colLabels" :key="l" class="text-[10px] text-slate-400 dark:text-slate-500">{{ l }}</span>
                </div>
            </SCard>
        </div>

        <!-- Middle row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <SCard title="Top Customers" noPad>
                <STable :columns="[{key:'name',label:'Customer'},{key:'inv',label:'Invoiced',align:'right'},{key:'out',label:'Outstanding',align:'right'}]" :empty="!d.top_customers?.length">
                    <tr v-for="c in d.top_customers" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-5 py-2.5 font-medium text-slate-900 dark:text-white">{{ c.name }}</td>
                        <td class="px-5 py-2.5 text-right text-slate-500 dark:text-slate-400">{{ fmt(c.total_invoiced) }}</td>
                        <td class="px-5 py-2.5 text-right"><span :class="c.outstanding > 0 ? 'text-amber-600 font-semibold' : 'text-emerald-600'">{{ fmt(c.outstanding) }}</span></td>
                    </tr>
                </STable>
            </SCard>

            <SCard title="Low Stock Alerts" noPad>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 max-h-72 overflow-y-auto">
                    <div v-for="p in d.low_stock" :key="p.id" class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ p.name }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ p.sku || 'No SKU' }} · threshold: {{ p.threshold }}</p>
                        </div>
                        <SBadge :color="p.status === 'out' ? 'red' : 'amber'">{{ p.status === 'out' ? 'OUT' : p.total_stock + ' left' }}</SBadge>
                    </div>
                    <div v-if="!d.low_stock?.length" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">All stock healthy ✓</div>
                </div>
            </SCard>
        </div>

        <!-- Bottom row: overdue + due today -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <SCard noPad>
                <template #header>
                    <h3 class="text-sm font-semibold text-red-700 dark:text-red-400">Overdue Invoices</h3>
                    <SBadge v-if="d.reminders?.overdue?.count" color="red">{{ d.reminders.overdue.count }}</SBadge>
                </template>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 max-h-72 overflow-y-auto">
                    <div v-for="inv in d.reminders?.overdue?.invoices" :key="inv.id" class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ inv.client_name }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ inv.invoice_number }} · {{ inv.days_overdue }}d overdue</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ inv.currency }} {{ fmt(inv.balance_due) }}</p>
                            <div class="flex gap-1 mt-1">
                                <SButton v-if="inv.client_email" size="xs" variant="ghost" @click="sendEmail(inv.id)" :disabled="sending[inv.id]">✉</SButton>
                                <SButton v-if="inv.client_phone" size="xs" variant="ghost" @click="openWhatsApp(inv.id)">💬</SButton>
                            </div>
                        </div>
                    </div>
                    <div v-if="!d.reminders?.overdue?.invoices?.length" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">No overdue invoices 🎉</div>
                </div>
            </SCard>

            <SCard noPad>
                <template #header>
                    <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-400">Due Today</h3>
                    <SBadge v-if="d.reminders?.due_today?.count" color="amber">{{ d.reminders.due_today.count }}</SBadge>
                </template>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 max-h-72 overflow-y-auto">
                    <div v-for="inv in d.reminders?.due_today?.invoices" :key="inv.id" class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ inv.client_name }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ inv.invoice_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ inv.currency }} {{ fmt(inv.balance_due) }}</p>
                            <div class="flex gap-1 mt-1">
                                <SButton v-if="inv.client_email" size="xs" variant="ghost" @click="sendEmail(inv.id)" :disabled="sending[inv.id]">✉</SButton>
                                <SButton v-if="inv.client_phone" size="xs" variant="ghost" @click="openWhatsApp(inv.id)">💬</SButton>
                            </div>
                        </div>
                    </div>
                    <div v-if="!d.reminders?.due_today?.invoices?.length" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">Nothing due today</div>
                </div>
            </SCard>
        </div>

        <!-- WhatsApp modal -->
        <SModal :show="waModal" title="WhatsApp Reminder" @close="waModal = false">
            <pre class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap mb-4 max-h-60 overflow-y-auto">{{ waMessage }}</pre>
            <template #footer>
                <a v-if="waUrl" :href="waUrl" target="_blank" @click="logWhatsApp"><SButton variant="success">Open WhatsApp</SButton></a>
                <SButton variant="secondary" @click="copyMsg">{{ copied ? 'Copied!' : 'Copy' }}</SButton>
                <SButton variant="ghost" @click="waModal = false">Close</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/utils/api';

const auth = useAuthStore();
const d = ref({});
const sending = reactive({});
const waModal = ref(false);
const waMessage = ref('');
const waUrl = ref('');
const waInvId = ref(null);
const copied = ref(false);

const today = computed(() => new Date().toLocaleDateString('en-AE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));

function fmt(v) { return Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 }); }

// Chart helpers
function chartData(arr, key) {
    if (!arr?.length) return { points: '', pts: [], labels: [], w: 0 };
    const vals = arr.map(d => d[key] || 0);
    const max = Math.max(...vals, 1);
    const w = 100 / Math.max(arr.length - 1, 1);
    const pts = vals.map((v, i) => ({ x: i * w, y: 100 - (v / max) * 80 }));
    const points = pts.map(p => `${p.x},${p.y}`).join(' ');
    const labels = arr.filter((_, i) => i % Math.ceil(arr.length / 6) === 0 || i === arr.length - 1).map(d => d.label);
    return { points, pts, labels, w };
}

const revChart = computed(() => chartData(d.value.revenue_trend, 'revenue'));
const colChart = computed(() => chartData(d.value.collection_trend, 'collected'));
const revPoints = computed(() => revChart.value.points);
const revPts = computed(() => revChart.value.pts);
const revLabels = computed(() => revChart.value.labels);
const revW = computed(() => revChart.value.w);
const colPoints = computed(() => colChart.value.points);
const colPts = computed(() => colChart.value.pts);
const colLabels = computed(() => colChart.value.labels);
const colW = computed(() => colChart.value.w);

async function load() { try { const { data } = await api.get('/dashboard'); d.value = data; } catch {} }
async function sendEmail(id) { sending[id] = true; try { await api.post(`/invoices/${id}/remind-email`); await load(); } catch {} finally { sending[id] = false; } }
async function openWhatsApp(id) { try { const { data } = await api.get(`/invoices/${id}/whatsapp-reminder`); waMessage.value = data.message; waUrl.value = data.whatsapp_url; waInvId.value = id; copied.value = false; waModal.value = true; } catch {} }
async function logWhatsApp() { if (waInvId.value) { await api.post(`/invoices/${waInvId.value}/remind-whatsapp`); await load(); } }
async function copyMsg() { await navigator.clipboard.writeText(waMessage.value); copied.value = true; setTimeout(() => copied.value = false, 2000); }

onMounted(load);
</script>
