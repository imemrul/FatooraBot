<template>
    <div>
        <SPageHeader title="Products" subtitle="Manage your product catalog">
            <SButton v-can="'manage_inventory'" @click="openCreate">Add Product</SButton>
        </SPageHeader>

        <SCard noPad>
            <template #header>
                <input v-model="search" type="text" placeholder="Search by name or SKU..."
                    class="border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2 text-sm w-64 outline-none focus:ring-2 focus:ring-indigo-500" />
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ filtered.length }} products</p>
            </template>

            <STable :columns="cols" :empty="!filtered.length" emptyText="No products found.">
                <tr v-for="p in filtered" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ p.sku || '—' }}</td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900 dark:text-white">{{ p.name }}</p>
                        <p v-if="p.barcode" class="text-[11px] text-slate-400 dark:text-slate-500">{{ p.barcode }}</p>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-900 dark:text-white">{{ p.unit_price }}</td>
                    <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">{{ p.cost_price }}</td>
                    <td class="px-5 py-3 text-right">
                        <span :class="stockClass(p)" class="font-semibold">{{ p.total_stock ?? 0 }}</span>
                        <SBadge v-if="(p.total_stock ?? 0) <= 0" color="red" size="xs" class="ml-1">OUT</SBadge>
                        <SBadge v-else-if="(p.total_stock ?? 0) <= (p.low_stock_threshold ?? 10)" color="amber" size="xs" class="ml-1">LOW</SBadge>
                    </td>
                    <td class="px-5 py-3">
                        <SBadge :color="p.is_active ? 'green' : 'default'">{{ p.is_active ? 'Active' : 'Inactive' }}</SBadge>
                    </td>
                    <td class="px-5 py-3 text-right" v-can="'manage_inventory'">
                        <SButton size="xs" variant="ghost" @click="openEdit(p)">Edit</SButton>
                        <SButton size="xs" variant="ghost" class="text-red-600 dark:text-red-400" @click="remove(p.id)">Delete</SButton>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Modal -->
        <SModal :show="showModal" :title="editing ? 'Edit Product' : 'New Product'" @close="showModal = false">
            <SAlert v-if="formError" variant="error" class="mb-4">{{ formError }}</SAlert>
            <form @submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.sku" label="SKU" />
                    <SInput v-model="form.barcode" label="Barcode" />
                </div>
                <SInput v-model="form.name" label="Name" required />
                <div>
                    <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="2" class="w-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <SInput v-model="form.unit_price" label="Price" type="number" step="0.01" min="0" required />
                    <SInput v-model="form.cost_price" label="Cost" type="number" step="0.01" min="0" />
                    <SInput v-model="form.vat_rate" label="VAT %" type="number" step="0.01" min="0" max="100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <SInput v-model="form.unit" label="Unit" />
                    <SInput v-model="form.low_stock_threshold" label="Low Stock Alert" type="number" min="0" />
                </div>
            </form>
            <template #footer>
                <SButton variant="secondary" @click="showModal = false">Cancel</SButton>
                <SButton :loading="saving" @click="save">Save</SButton>
            </template>
        </SModal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import api from '@/utils/api';

const products = ref([]);
const search = ref('');
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const formError = ref('');

const cols = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Product' },
    { key: 'price', label: 'Price', align: 'right' },
    { key: 'cost', label: 'Cost', align: 'right' },
    { key: 'stock', label: 'Stock', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', align: 'right' },
];

const defaultForm = { sku: '', barcode: '', name: '', description: '', unit_price: '', cost_price: '0', vat_rate: '5', unit: 'unit', low_stock_threshold: 10 };
const form = reactive({ ...defaultForm });

const filtered = computed(() => {
    if (!search.value) return products.value;
    const q = search.value.toLowerCase();
    return products.value.filter(p => p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)));
});

function stockClass(p) {
    const s = p.total_stock ?? 0;
    if (s <= 0) return 'text-red-600 dark:text-red-400';
    if (s <= (p.low_stock_threshold ?? 10)) return 'text-amber-600 dark:text-amber-400';
    return 'text-slate-900 dark:text-white';
}

function openCreate() { Object.assign(form, defaultForm); editing.value = null; formError.value = ''; showModal.value = true; }
function openEdit(p) {
    Object.assign(form, { sku: p.sku || '', barcode: p.barcode || '', name: p.name, description: p.description || '', unit_price: p.unit_price, cost_price: p.cost_price, vat_rate: p.vat_rate, unit: p.unit, low_stock_threshold: p.low_stock_threshold });
    editing.value = p.id; formError.value = ''; showModal.value = true;
}

async function save() {
    saving.value = true; formError.value = '';
    try {
        if (editing.value) await api.put(`/products/${editing.value}`, form);
        else await api.post('/products', form);
        showModal.value = false; await load();
    } catch (e) { formError.value = e.response?.data?.message || 'Save failed.'; }
    finally { saving.value = false; }
}

async function remove(id) { if (!confirm('Delete this product?')) return; await api.delete(`/products/${id}`); await load(); }
async function load() { const { data } = await api.get('/products?per_page=100'); products.value = data.data; }

onMounted(load);
</script>
