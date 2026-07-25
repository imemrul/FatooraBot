<template>
    <div>
        <SPageHeader title="Expenses" description="Track business expenses and view summaries">
            <div class="flex gap-2">
                <SButton variant="secondary" @click="showCategories = true">Categories</SButton>
                <SButton @click="openForm()">+ Add Expense</SButton>
            </div>
        </SPageHeader>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <SStatCard label="Total Expenses" :value="formatMoney(summary.total || 0)" icon="💰" />
            <SStatCard label="This Month" :value="formatMoney(monthlyTotal)" icon="📅" />
            <SStatCard label="Categories" :value="categories.length" icon="📂" />
        </div>

        <!-- Filters -->
        <SCard class="mb-4">
            <div class="flex flex-wrap gap-3">
                <SInput v-model="filters.search" placeholder="Search vendor/description..." class="flex-1 min-w-[200px]" @input="debouncedFetch" />
                <SSelect v-model="filters.category_id" :options="catOptions" placeholder="All Categories" class="w-48" @change="fetchExpenses" />
                <SInput v-model="filters.from" type="date" class="w-40" @change="fetchExpenses" />
                <SInput v-model="filters.to" type="date" class="w-40" @change="fetchExpenses" />
            </div>
        </SCard>

        <SCard>
            <STable :columns="['Date', 'Category', 'Vendor', 'Description', 'Amount', 'Actions']" :loading="loading">
                <tr v-for="e in expenses" :key="e.id">
                    <td class="px-4 py-3 text-sm">{{ new Date(e.expense_date).toLocaleDateString() }}</td>
                    <td class="px-4 py-3"><SBadge :style="{ backgroundColor: e.category?.color + '20', color: e.category?.color }">{{ e.category?.name || 'Uncategorized' }}</SBadge></td>
                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ e.vendor || '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 max-w-[200px] truncate">{{ e.description || '—' }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white">{{ formatMoney(e.amount) }} {{ e.currency }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button @click="openForm(e)" class="text-xs text-indigo-600 hover:underline">Edit</button>
                            <button @click="deleteExpense(e.id)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </div>
                    </td>
                </tr>
            </STable>
        </SCard>

        <!-- Expense Form Modal -->
        <SModal :show="showForm" :title="editing ? 'Edit Expense' : 'Add Expense'" @close="showForm = false">
            <form @submit.prevent="saveExpense" class="space-y-4">
                <SSelect v-model="form.expense_category_id" label="Category" :options="catOptions" />
                <SInput v-model="form.expense_date" label="Date" type="date" required />
                <SInput v-model="form.amount" label="Amount" type="number" step="0.01" required />
                <SInput v-model="form.vendor" label="Vendor" />
                <SInput v-model="form.reference" label="Reference" />
                <SInput v-model="form.description" label="Description" />
                <div class="flex justify-end gap-3 pt-2">
                    <SButton variant="secondary" @click="showForm = false">Cancel</SButton>
                    <SButton type="submit" :loading="saving">{{ editing ? 'Update' : 'Save' }}</SButton>
                </div>
            </form>
        </SModal>

        <!-- Categories Modal -->
        <SModal :show="showCategories" title="Expense Categories" @close="showCategories = false" size="lg">
            <div class="space-y-3">
                <div class="flex gap-2">
                    <SInput v-model="newCat.name" placeholder="Category name" class="flex-1" />
                    <input v-model="newCat.color" type="color" class="w-10 h-10 rounded cursor-pointer" />
                    <SButton @click="addCategory" size="sm">Add</SButton>
                </div>
                <div v-for="c in categories" :key="c.id" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: c.color }" />
                        <span class="text-sm font-medium dark:text-white">{{ c.name }}</span>
                        <span class="text-xs text-slate-400">{{ c.expenses_count || 0 }} expenses · {{ formatMoney(c.expenses_sum_amount || 0) }}</span>
                    </div>
                    <button @click="deleteCategory(c.id)" class="text-xs text-red-500 hover:underline">Delete</button>
                </div>
            </div>
        </SModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import api from '@/utils/api';

const expenses = ref([]);
const categories = ref([]);
const summary = ref({});
const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const showCategories = ref(false);
const editing = ref(null);
const filters = reactive({ search: '', category_id: '', from: '', to: '' });
const form = reactive({ expense_category_id: '', expense_date: new Date().toISOString().slice(0, 10), amount: '', vendor: '', reference: '', description: '' });
const newCat = reactive({ name: '', color: '#6366f1' });

const formatMoney = (v) => Number(v || 0).toLocaleString('en-AE', { minimumFractionDigits: 2 });
const catOptions = computed(() => [{ value: '', label: 'All Categories' }, ...categories.value.map(c => ({ value: c.id, label: c.name }))]);
const monthlyTotal = computed(() => {
    const now = new Date();
    return expenses.value.filter(e => { const d = new Date(e.expense_date); return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear(); }).reduce((s, e) => s + Number(e.amount), 0);
});

let debounceTimer;
const debouncedFetch = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchExpenses, 300); };

async function fetchExpenses() {
    loading.value = true;
    try {
        const params = {};
        if (filters.search) params.search = filters.search;
        if (filters.category_id) params.category_id = filters.category_id;
        if (filters.from) params.from = filters.from;
        if (filters.to) params.to = filters.to;
        const { data } = await api.get('/expenses', { params });
        expenses.value = data.data;
    } finally { loading.value = false; }
}

async function fetchCategories() {
    const { data } = await api.get('/expense-categories');
    categories.value = data.categories;
}

async function fetchSummary() {
    const { data } = await api.get('/expenses/summary');
    summary.value = data;
}

function openForm(expense = null) {
    editing.value = expense?.id || null;
    if (expense) {
        Object.assign(form, { expense_category_id: expense.expense_category_id || '', expense_date: expense.expense_date?.slice(0, 10), amount: expense.amount, vendor: expense.vendor || '', reference: expense.reference || '', description: expense.description || '' });
    } else {
        Object.assign(form, { expense_category_id: '', expense_date: new Date().toISOString().slice(0, 10), amount: '', vendor: '', reference: '', description: '' });
    }
    showForm.value = true;
}

async function saveExpense() {
    saving.value = true;
    try {
        if (editing.value) await api.put(`/expenses/${editing.value}`, form);
        else await api.post('/expenses', form);
        showForm.value = false;
        fetchExpenses();
        fetchSummary();
    } finally { saving.value = false; }
}

async function deleteExpense(id) {
    if (!confirm('Delete this expense?')) return;
    await api.delete(`/expenses/${id}`);
    fetchExpenses();
    fetchSummary();
}

async function addCategory() {
    if (!newCat.name) return;
    await api.post('/expense-categories', newCat);
    newCat.name = '';
    fetchCategories();
}

async function deleteCategory(id) {
    if (!confirm('Delete this category?')) return;
    await api.delete(`/expense-categories/${id}`);
    fetchCategories();
}

onMounted(() => { fetchExpenses(); fetchCategories(); fetchSummary(); });
</script>
