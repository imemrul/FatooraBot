<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 transition-colors p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-xl">F</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">New password</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">Set your new password</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                <SAlert v-if="success" variant="success" class="mb-6">
                    {{ success }}
                    <router-link to="/login" class="block mt-2 font-medium underline">Sign in now</router-link>
                </SAlert>
                <SAlert v-if="error" variant="error" class="mb-6">{{ error }}</SAlert>

                <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-5">
                    <SInput v-model="form.email" label="Email address" type="email" required :error="errors.email?.[0]" />
                    <SInput v-model="form.password" label="New password" type="password" required placeholder="Min. 8 characters" :error="errors.password?.[0]" />
                    <SInput v-model="form.password_confirmation" label="Confirm password" type="password" required />
                    <SButton type="submit" :loading="loading" class="w-full">Reset password</SButton>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const auth = useAuthStore();
const loading = ref(false);
const error = ref('');
const errors = ref({});
const success = ref('');
const form = reactive({ email: '', password: '', password_confirmation: '', token: '' });

onMounted(() => { form.token = route.query.token || ''; form.email = route.query.email || ''; });

async function handleSubmit() {
    loading.value = true; error.value = ''; errors.value = ''; success.value = '';
    try { success.value = await auth.resetPassword(form); }
    catch (e) {
        if (e.response?.status === 422) { errors.value = e.response.data.errors || {}; error.value = e.response.data.message || ''; }
        else error.value = 'Something went wrong.';
    } finally { loading.value = false; }
}
</script>
