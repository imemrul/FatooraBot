<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 flex items-start gap-4">
        <div v-if="icon" :class="iconBg" class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-lg">
            <span v-html="icon" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 truncate">{{ label }}</p>
            <p class="text-xl font-bold mt-0.5" :class="valueColor || 'text-slate-900 dark:text-white'">{{ value }}</p>
            <p v-if="sub" class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ sub }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    value: [String, Number],
    sub: String,
    icon: String,
    iconBg: { type: String, default: 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' },
    valueColor: String,
});
</script>
