<template>
    <div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1">WhatsApp Order</h1>
        <p class="text-slate-500 text-sm mb-6">Paste a WhatsApp message to create a draft invoice automatically.</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Input -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Paste Message</h3>
                <textarea
                    v-model="message"
                    rows="8"
                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none font-mono"
                    placeholder="Ahmed need 5 chargers and 10 covers tomorrow&#10;&#10;Or paste any WhatsApp order message..."
                ></textarea>
                <div class="flex items-center justify-between mt-3">
                    <p class="text-xs text-slate-400">{{ message.length }} chars</p>
                    <button
                        @click="parseMessage"
                        :disabled="parsing || message.length < 5"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
                    >
                        {{ parsing ? 'Parsing...' : 'Parse Order' }}
                    </button>
                </div>
                <div v-if="parseError" class="bg-red-50 text-red-700 text-sm rounded-lg px-3 py-2 mt-3">{{ parseError }}</div>

                <!-- Examples -->
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="text-xs font-medium text-slate-500 mb-2">Try these examples:</p>
                    <div class="space-y-1.5">
                        <button v-for="ex in examples" :key="ex" @click="message = ex"
                            class="block w-full text-left text-xs text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-lg px-3 py-2 transition font-mono">
                            {{ ex }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div v-if="parsed" class="space-y-4">
                <!-- Warnings -->
                <div v-if="parsed.warnings.length" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <h4 class="text-sm font-semibold text-amber-800 mb-1">Warnings</h4>
                    <ul class="text-sm text-amber-700 space-y-0.5">
                        <li v-for="(w, i) in parsed.warnings" :key="i">• {{ w }}</li>
                    </ul>
                </div>

                <!-- Customer -->
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-slate-900">Customer</h4>
                        <span :class="confClass(parsed.customer_confidence)" class="text-[10px] px-2 py-0.5 rounded-full font-medium">
                            {{ Math.round(parsed.customer_confidence * 100) }}% match
                        </span>
                    </div>
                    <select v-model="form.client_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select customer</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p v-if="parsed.customer_name && !parsed.client_id" class="text-xs text-amber-600 mt-1">
                        Detected: "{{ parsed.customer_name }}" — not matched. Select manually.
                    </p>
                </div>

                <!-- Items -->
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <h4 class="text-sm font-semibold text-slate-900 mb-3">Items ({{ form.items.length }})</h4>
                    <div v-for="(item, idx) in form.items" :key="idx"
                        class="flex items-start gap-2 mb-3 pb-3 border-b border-slate-100 last:border-0 last:mb-0 last:pb-0">
                        <div class="flex-1 space-y-2">
                            <div class="flex gap-2">
                                <select v-model="item.product_id" @change="onProductSelect(idx)"
                                    class="flex-1 border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option :value="null">Manual entry</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku || '—' }})</option>
                                </select>
                                <span :class="confClass(item.confidence)" class="text-[10px] px-2 py-1 rounded-full font-medium shrink-0">
                                    {{ Math.round(item.confidence * 100) }}%
                                </span>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <input v-model="item.description" placeholder="Description" class="col-span-2 border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                                <input v-model="item.quantity" type="number" min="1" placeholder="Qty" class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                                <input v-model="item.unit_price" type="number" step="0.01" min="0" placeholder="Price" class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs outline-none focus:ring-1 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <button @click="form.items.splice(idx, 1)" v-if="form.items.length > 1"
                            class="text-red-400 hover:text-red-600 text-sm mt-1">✕</button>
                    </div>
                    <button @click="addItem" class="text-xs text-indigo-600 hover:text-indigo-800 mt-2">+ Add item</button>
                </div>

                <!-- Delivery & Confirm -->
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Delivery / Due Date</label>
                            <input v-model="form.delivery_date" type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500" />
                            <p v-if="parsed.delivery_raw" class="text-xs text-slate-400 mt-1">Detected: "{{ parsed.delivery_raw }}"</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Estimated Total</label>
                            <p class="text-xl font-bold text-slate-900 mt-1">AED {{ estimatedTotal }}</p>
                        </div>
                    </div>
                    <div v-if="confirmError" class="bg-red-50 text-red-700 text-sm rounded-lg px-3 py-2 mb-3">{{ confirmError }}</div>
                    <button
                        @click="confirmOrder"
                        :disabled="confirming || !form.client_id || !form.items.length"
                        class="w-full px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 transition"
                    >
                        {{ confirming ? 'Creating...' : 'Create Draft Invoice' }}
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-white rounded-xl border border-slate-200 p-12 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-4xl mb-3">💬</p>
                    <p class="text-slate-400 text-sm">Paste a WhatsApp message and click Parse to get started.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/utils/api';

const router = useRouter();
const message = ref('');
const parsing = ref(false);
const parseError = ref('');
const parsed = ref(null);
const confirming = ref(false);
const confirmError = ref('');
const clients = ref([]);
const products = ref([]);

const form = reactive({
    client_id: '',
    delivery_date: '',
    items: [],
});

const examples = [
    'Ahmed need 5 chargers and 10 covers tomorrow',
    'Hi, order for Dubai Trading: 3 cables, 20 adapters, delivery next week',
    'Fatima wants 100 screen protectors and 50 cases by 15 Jan',
];

const estimatedTotal = computed(() => {
    let total = 0;
    for (const item of form.items) {
        const line = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
        const vat = line * ((Number(item.vat_rate) || 5) / 100);
        total += line + vat;
    }
    return total.toFixed(2);
});

function confClass(c) {
    if (c >= 0.7) return 'bg-green-100 text-green-700';
    if (c >= 0.4) return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-700';
}

function addItem() {
    form.items.push({ product_id: null, description: '', quantity: 1, unit_price: 0, vat_rate: 5, confidence: 0 });
}

function onProductSelect(idx) {
    const item = form.items[idx];
    const product = products.value.find(p => p.id === item.product_id);
    if (product) {
        item.description = product.name;
        item.unit_price = product.unit_price;
        item.vat_rate = product.vat_rate;
    }
}

async function parseMessage() {
    parsing.value = true;
    parseError.value = '';
    parsed.value = null;
    try {
        const { data } = await api.post('/orders/parse', { message: message.value });
        parsed.value = data;

        form.client_id = data.client_id || '';
        form.delivery_date = data.delivery_date || new Date().toISOString().split('T')[0];
        form.items = data.items.map(i => ({
            product_id: i.product_id,
            description: i.product_name,
            quantity: i.quantity,
            unit_price: i.unit_price || 0,
            vat_rate: i.vat_rate ?? 5,
            confidence: i.confidence,
        }));

        if (!form.items.length) {
            form.items = [{ product_id: null, description: '', quantity: 1, unit_price: 0, vat_rate: 5, confidence: 0 }];
        }
    } catch (e) {
        parseError.value = e.response?.data?.message || 'Parse failed.';
    } finally {
        parsing.value = false;
    }
}

async function confirmOrder() {
    confirming.value = true;
    confirmError.value = '';
    try {
        const payload = {
            client_id: form.client_id,
            delivery_date: form.delivery_date,
            notes: `Parsed from WhatsApp: ${message.value.substring(0, 200)}`,
            items: form.items.map(i => ({
                product_id: i.product_id,
                description: i.description,
                quantity: i.quantity,
                unit_price: i.unit_price,
                vat_rate: i.vat_rate,
            })),
        };
        await api.post('/orders/confirm', payload);
        router.push('/invoices');
    } catch (e) {
        confirmError.value = e.response?.data?.message || 'Failed to create invoice.';
    } finally {
        confirming.value = false;
    }
}

onMounted(async () => {
    const [cRes, pRes] = await Promise.all([
        api.get('/clients?per_page=200'),
        api.get('/products?per_page=200'),
    ]);
    clients.value = cRes.data.data;
    products.value = pRes.data.data;
});
</script>
