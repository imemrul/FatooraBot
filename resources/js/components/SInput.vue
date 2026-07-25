<template>
    <div>
        <label v-if="label" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :min="min"
            :max="max"
            :step="step"
            :maxlength="maxlength"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full border rounded-lg px-3.5 py-2.5 text-sm outline-none transition-colors"
            :class="[
                error
                    ? 'border-red-300 dark:border-red-600 focus:ring-2 focus:ring-red-500'
                    : 'border-slate-300 dark:border-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                'bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500',
                disabled ? 'bg-slate-50 dark:bg-slate-800 cursor-not-allowed' : '',
            ]"
        />
        <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
        <p v-if="hint && !error" class="text-slate-400 dark:text-slate-500 text-xs mt-1">{{ hint }}</p>
    </div>
</template>

<script setup>
defineProps({
    modelValue: [String, Number],
    label: String,
    type: { type: String, default: 'text' },
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
    hint: String,
    min: [String, Number],
    max: [String, Number],
    step: [String, Number],
    maxlength: [String, Number],
});

defineEmits(['update:modelValue']);
</script>
