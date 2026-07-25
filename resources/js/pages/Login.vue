<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 transition-colors p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-xl">F</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Welcome back</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">Sign in to FatooraBot</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                <SAlert v-if="error" variant="error" class="mb-6">{{ error }}</SAlert>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <SInput v-model="form.email" label="Email address" type="email" required placeholder="you@company.ae" :error="errors.email?.[0]" />

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Password</label>
                            <router-link to="/forgot-password" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700">Forgot?</router-link>
                        </div>
                        <SInput v-model="form.password" type="password" required placeholder="••••••••" :error="errors.password?.[0]" />
                    </div>

                    <SButton type="submit" :loading="loading" class="w-full">Sign in</SButton>
                </form>

                <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
                    No account?
                    <router-link to="/register" class="text-indigo-600 dark:text-indigo-400 font-medium hover:text-indigo-700">Create one</router-link>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const error = ref('');
const errors = ref({});
const form = reactive({ email: '', password: '' });

async function handleLogin() {
    loading.value = true; error.value = ''; errors.value = {};
    try {
        await auth.login(form.email, form.password);
        router.push(auth.user?.is_super_admin ? '/admin' : '/');
    } catch (e) {
        if (e.response?.status === 422) { errors.value = e.response.data.errors || {}; error.value = e.response.data.message || ''; }
        else error.value = 'Something went wrong.';
    } finally { loading.value = false; }
}
</script>
