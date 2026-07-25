<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 transition-colors p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-xl">F</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Reset password</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">We'll send you a reset link</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                <SAlert v-if="success" variant="success" class="mb-6">{{ success }}</SAlert>
                <SAlert v-if="error" variant="error" class="mb-6">{{ error }}</SAlert>

                <form @submit.prevent="handleSubmit" class="space-y-5">
                    <SInput v-model="email" label="Email address" type="email" required placeholder="you@company.ae" :error="errors.email?.[0]" />
                    <SButton type="submit" :loading="loading" class="w-full">Send reset link</SButton>
                </form>

                <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
                    <router-link to="/login" class="text-indigo-600 dark:text-indigo-400 font-medium">Back to sign in</router-link>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const email = ref('');
const loading = ref(false);
const error = ref('');
const errors = ref({});
const success = ref('');

async function handleSubmit() {
    loading.value = true; error.value = ''; errors.value = {}; success.value = '';
    try { success.value = await auth.forgotPassword(email.value); email.value = ''; }
    catch (e) {
        if (e.response?.status === 422) { errors.value = e.response.data.errors || {}; error.value = e.response.data.message || ''; }
        else error.value = 'Something went wrong.';
    } finally { loading.value = false; }
}
</script>
