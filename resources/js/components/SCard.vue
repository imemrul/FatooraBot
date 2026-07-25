<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700" :class="{ 'overflow-hidden': !noPad }">
        <div v-if="title || $slots.header" class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 v-if="title" class="text-sm font-semibold" :class="titleColor || 'text-slate-900 dark:text-white'">{{ title }}</h3>
            <slot name="header" />
        </div>
        <div :class="noPad ? '' : 'p-5'">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({ title: String, titleColor: String, noPad: Boolean });
</script>
