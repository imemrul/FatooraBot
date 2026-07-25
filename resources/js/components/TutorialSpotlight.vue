<template>
    <div v-if="active && currentStep" class="fixed inset-0 z-[100]">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/60" @click="dismiss" />

        <!-- Tooltip -->
        <div ref="tooltip" class="absolute z-[101] bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 p-5 max-w-sm"
            :style="tooltipStyle">
            <!-- Progress -->
            <div class="flex items-center gap-2 mb-3">
                <div class="flex gap-1">
                    <div v-for="i in totalSteps" :key="i" class="w-2 h-2 rounded-full transition-colors"
                        :class="i <= step ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-600'" />
                </div>
                <span class="text-[10px] text-slate-400 ml-auto">{{ step }}/{{ totalSteps }}</span>
            </div>

            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ currentStep.title }}</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ currentStep.content }}</p>

            <div class="flex items-center justify-between mt-4">
                <button @click="dismiss" class="text-xs text-slate-400 hover:text-slate-600">Skip tour</button>
                <div class="flex gap-2">
                    <button v-if="step > 1" @click="prev" class="px-3 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Back</button>
                    <button @click="next" class="px-4 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                        {{ step >= totalSteps ? 'Finish' : 'Next' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
    steps: { type: Array, default: () => [] },
    active: { type: Boolean, default: false },
});

const emit = defineEmits(['complete', 'dismiss', 'step-change']);

const step = ref(1);
const tooltip = ref(null);
const tooltipStyle = ref({});

const totalSteps = computed(() => props.steps.length);
const currentStep = computed(() => props.steps[step.value - 1]);

function next() {
    if (step.value >= totalSteps.value) {
        emit('complete');
        return;
    }
    step.value++;
    emit('step-change', step.value);
    positionTooltip();
}

function prev() {
    if (step.value > 1) {
        step.value--;
        emit('step-change', step.value);
        positionTooltip();
    }
}

function dismiss() {
    emit('dismiss');
}

function positionTooltip() {
    nextTick(() => {
        const s = currentStep.value;
        if (!s?.target) {
            tooltipStyle.value = { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };
            return;
        }

        const el = document.querySelector(s.target);
        if (!el) {
            tooltipStyle.value = { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };
            return;
        }

        const rect = el.getBoundingClientRect();
        const pos = s.position || 'bottom';

        el.style.position = 'relative';
        el.style.zIndex = '101';
        el.style.boxShadow = '0 0 0 4px rgba(99, 102, 241, 0.3)';
        el.style.borderRadius = '8px';

        const styles = {
            bottom: { top: `${rect.bottom + 12}px`, left: `${rect.left}px` },
            top: { top: `${rect.top - 12}px`, left: `${rect.left}px`, transform: 'translateY(-100%)' },
            right: { top: `${rect.top}px`, left: `${rect.right + 12}px` },
            left: { top: `${rect.top}px`, left: `${rect.left - 12}px`, transform: 'translateX(-100%)' },
        };

        tooltipStyle.value = styles[pos] || styles.bottom;
    });
}

watch(() => props.active, (val) => {
    if (val) { step.value = 1; positionTooltip(); }
});
</script>
