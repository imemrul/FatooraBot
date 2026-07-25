<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape="$emit('close')">
                <div class="absolute inset-0 bg-black/50 dark:bg-black/70" @click="$emit('close')" />
                <div :class="widths[size]"
                    class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto w-full">
                    <div v-if="title" class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ title }}</h2>
                        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    show: Boolean,
    title: String,
    size: { type: String, default: 'md' },
});

defineEmits(['close']);

const widths = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};
</script>
