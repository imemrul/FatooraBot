<template>
    <div v-if="!dismissed" :class="styles[variant]" class="rounded-lg px-4 py-3 text-sm flex items-start gap-3">
        <div class="flex-1">
            <slot />
        </div>
        <button v-if="dismissible" @click="dismissed = true" class="opacity-60 hover:opacity-100 transition shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    variant: { type: String, default: 'info' },
    dismissible: Boolean,
});

const dismissed = ref(false);

const styles = {
    info: 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
    success: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
    warning: 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
    error: 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800',
};
</script>
