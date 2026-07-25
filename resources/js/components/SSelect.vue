<template>
    <div class="relative" ref="wrapper">
        <label v-if="label" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- If options provided: custom searchable select -->
        <template v-if="options">
            <button type="button" @click="toggle" :disabled="disabled"
                class="w-full border rounded-lg px-3.5 py-2.5 text-sm text-left outline-none transition-colors bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 flex items-center justify-between"
                :class="[disabled ? 'bg-slate-50 dark:bg-slate-800 cursor-not-allowed' : 'cursor-pointer', modelValue ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500']">
                <span class="truncate">{{ selectedLabel || placeholder || 'Select...' }}</span>
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div v-if="open" class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg max-h-60 overflow-hidden">
                <div class="p-2 border-b dark:border-slate-700" v-if="options.length > 5">
                    <input ref="searchInput" v-model="search" placeholder="Search..." autofocus
                        class="w-full px-3 py-1.5 text-sm bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded outline-none text-slate-900 dark:text-white placeholder-slate-400" />
                </div>
                <div class="overflow-y-auto max-h-48">
                    <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-slate-400 text-center">No results</div>
                    <button v-for="opt in filteredOptions" :key="optValue(opt)" type="button"
                        @click="select(opt)"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition"
                        :class="optValue(opt) == modelValue ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium' : 'text-slate-700 dark:text-slate-300'">
                        {{ optLabel(opt) }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Fallback: native select with slot -->
        <template v-else>
            <select :value="modelValue" :required="required" :disabled="disabled"
                @change="$emit('update:modelValue', $event.target.value)"
                class="w-full border rounded-lg px-3.5 py-2.5 text-sm outline-none transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                :class="disabled ? 'bg-slate-50 dark:bg-slate-800 cursor-not-allowed' : ''">
                <slot />
            </select>
        </template>

        <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    label: String,
    options: Array,
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const search = ref('');
const wrapper = ref(null);
const searchInput = ref(null);

function optValue(opt) { return typeof opt === 'object' ? opt.value : opt; }
function optLabel(opt) { return typeof opt === 'object' ? opt.label : opt; }

const selectedLabel = computed(() => {
    if (!props.options || props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) return '';
    const found = props.options.find(o => String(optValue(o)) === String(props.modelValue));
    return found ? optLabel(found) : '';
});

const filteredOptions = computed(() => {
    if (!props.options) return [];
    if (!search.value) return props.options;
    const q = search.value.toLowerCase();
    return props.options.filter(o => optLabel(o).toLowerCase().includes(q));
});

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        search.value = '';
        nextTick(() => searchInput.value?.focus());
    }
}

function select(opt) {
    emit('update:modelValue', optValue(opt));
    emit('change', optValue(opt));
    open.value = false;
    search.value = '';
}

function onClickOutside(e) {
    if (wrapper.value && !wrapper.value.contains(e.target)) open.value = false;
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>
