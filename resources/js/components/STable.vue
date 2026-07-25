<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div v-if="$slots.toolbar" class="px-5 py-3 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-4">
            <slot name="toolbar" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 dark:bg-slate-700/50">
                    <tr>
                        <th v-for="col in columns" :key="col.key"
                            :class="[col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left', col.class]"
                            class="px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <slot />
                </tbody>
            </table>
        </div>
        <div v-if="empty" class="px-5 py-12 text-center">
            <p class="text-slate-400 dark:text-slate-500 text-sm">{{ emptyText || 'No data found.' }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    columns: { type: Array, default: () => [] },
    empty: Boolean,
    emptyText: String,
});
</script>
