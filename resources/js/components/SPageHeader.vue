<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-3">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({ title: String, subtitle: String });
</script>
