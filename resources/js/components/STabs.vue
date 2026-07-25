<template>
    <div class="flex gap-1 bg-slate-100 dark:bg-slate-700/50 rounded-lg p-1 w-fit">
        <button
            v-for="tab in tabs"
            :key="tabValue(tab)"
            @click="$emit('update:modelValue', tabValue(tab))"
            :class="modelValue === tabValue(tab)
                ? 'bg-white dark:bg-slate-600 shadow-sm text-slate-900 dark:text-white'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
            class="px-4 py-1.5 text-sm font-medium rounded-md transition"
        >
            {{ tabLabel(tab) }}
        </button>
    </div>
</template>

<script setup>
defineProps({
    tabs: { type: Array, required: true },
    modelValue: [String, Number],
});

defineEmits(['update:modelValue']);

function tabValue(tab) { return typeof tab === 'object' ? tab.value : tab; }
function tabLabel(tab) { return typeof tab === 'object' ? tab.label : tab; }
</script>
