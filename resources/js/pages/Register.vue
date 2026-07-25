<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 transition-colors p-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-xl">F</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Create your account</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">Start managing your business</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                <SAlert v-if="error" variant="error" class="mb-6">{{ error }}</SAlert>

                <form @submit.prevent="handleRegister" class="space-y-5">
                    <SInput v-model="form.company_name" label="Company name" required placeholder="Your Trading LLC" :error="errors.company_name?.[0]" />
                    <SInput v-model="form.name" label="Full name" required :error="errors.name?.[0]" />
                    <SInput v-model="form.email" label="Email address" type="email" required placeholder="you@company.ae" :error="errors.email?.[0]" />
                    <SInput v-model="form.phone" label="Phone number" type="tel" placeholder="+971 50 123 4567" :error="errors.phone?.[0]" />
                    <SInput v-model="form.password" label="Password" type="password" required placeholder="Min. 8 characters" :error="errors.password?.[0]" />
                    <SInput v-model="form.password_confirmation" label="Confirm password" type="password" required />
                    <SButton type="submit" :loading="loading" class="w-full">Create account</SButton>
                </form>

                <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
                    Already have an account?
                    <router-link to="/login" class="text-indigo-600 dark:text-indigo-400 font-medium">Sign in</router-link>
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
const form = reactive({ company_name: '', name: '', email: '', phone: '', password: '', password_confirmation: '' });

async function handleRegister() {
    loading.value = true; error.value = ''; errors.value = {};
    try { await auth.register(form); router.push('/'); }
    catch (e) {
        if (e.response?.status === 422) { errors.value = e.response.data.errors || {}; error.value = e.response.data.message || ''; }
        else error.value = 'Something went wrong.';
    } finally { loading.value = false; }
}
</script>
