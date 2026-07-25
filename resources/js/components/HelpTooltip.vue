<template>
    <div class="relative inline-flex">
        <button @click="open = !open" type="button"
            class="w-4 h-4 rounded-full bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-400 text-[10px] font-bold flex items-center justify-center hover:bg-indigo-100 hover:text-indigo-600 transition">
            ?
        </button>
        <div v-if="open" class="absolute z-50 bottom-full mb-2 left-1/2 -translate-x-1/2 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg p-3">
            <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">{{ text }}</p>
            <div class="absolute top-full left-1/2 -translate-x-1/2 w-2 h-2 bg-white dark:bg-slate-800 border-r border-b border-slate-200 dark:border-slate-700 rotate-45 -mt-1" />
        </div>
        <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
defineProps({ text: { type: String, required: true } });
const open = ref(false);
</script>
