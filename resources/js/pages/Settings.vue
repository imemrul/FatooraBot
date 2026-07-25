<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Company Settings</h1>
            <p class="text-slate-500 mt-1">Manage your company profile and branding.</p>
        </div>

        <!-- Success message -->
        <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-6">
            {{ success }}
        </div>
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6">
            {{ error }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Logo section -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Company Logo</h3>
                <div class="flex flex-col items-center">
                    <div class="w-32 h-32 rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden bg-slate-50 mb-4">
                        <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain" />
                        <span v-else class="text-slate-400 text-xs text-center px-2">No logo</span>
                    </div>
                    <div class="flex gap-2">
                        <label class="px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer transition">
                            Upload
                            <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="handleLogoUpload" />
                        </label>
                        <button v-if="auth.company?.logo_path" @click="handleLogoDelete" :disabled="logoSaving"
                            class="px-3 py-1.5 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 transition">
                            Remove
                        </button>
                    </div>
                    <p v-if="logoError" class="text-red-600 text-xs mt-2">{{ logoError }}</p>
                    <p class="text-xs text-slate-400 mt-2">Max 2MB. PNG, JPG, SVG, WebP.</p>
                </div>
            </div>

            <!-- Profile form -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Business Information</h3>
                <form @submit.prevent="handleSave" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Company name *</label>
                            <input v-model="form.name" type="text" required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                            <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email *</label>
                            <input v-model="form.email" type="email" required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                            <p v-if="errors.email" class="text-red-600 text-xs mt-1">{{ errors.email[0] }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone *</label>
                            <input v-model="form.phone" type="tel" required
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                            <p v-if="errors.phone" class="text-red-600 text-xs mt-1">{{ errors.phone[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">City *</label>
                            <select v-model="form.city"
                                class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                <option value="">Select city</option>
                                <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                            </select>
                            <p v-if="errors.city" class="text-red-600 text-xs mt-1">{{ errors.city[0] }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Address *</label>
                        <textarea v-model="form.address" rows="2" required
                            class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"></textarea>
                        <p v-if="errors.address" class="text-red-600 text-xs mt-1">{{ errors.address[0] }}</p>
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <h4 class="text-sm font-semibold text-slate-700 mb-4">Legal & Tax</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Trade License</label>
                                <input v-model="form.trade_license_number" type="text"
                                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">TRN</label>
                                <input v-model="form.tax_registration_number" type="text" maxlength="15"
                                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="saving"
                            class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition">
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const saving = ref(false);
const logoSaving = ref(false);
const error = ref('');
const errors = ref({});
const success = ref('');
const logoError = ref('');

const cities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];

const logoPreview = computed(() => auth.company?.logo_url || null);

const form = reactive({
    name: '', email: '', phone: '', address: '', city: '',
    trade_license_number: '', tax_registration_number: '',
});

onMounted(() => {
    const c = auth.company;
    if (c) {
        form.name = c.name || '';
        form.email = c.email || '';
        form.phone = c.phone || '';
        form.address = c.address || '';
        form.city = c.city || '';
        form.trade_license_number = c.trade_license_number || '';
        form.tax_registration_number = c.tax_registration_number || '';
    }
});

async function handleSave() {
    saving.value = true;
    error.value = '';
    errors.value = {};
    success.value = '';
    try {
        const data = await auth.updateCompany(form);
        success.value = data.message;
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
            error.value = e.response.data.message || '';
        } else {
            error.value = 'Something went wrong.';
        }
    } finally {
        saving.value = false;
    }
}

async function handleLogoUpload(e) {
    logoError.value = '';
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        logoError.value = 'File must be 2MB or smaller.';
        return;
    }
    logoSaving.value = true;
    try {
        await auth.uploadLogo(file);
    } catch {
        logoError.value = 'Upload failed.';
    } finally {
        logoSaving.value = false;
    }
}

async function handleLogoDelete() {
    logoSaving.value = true;
    try {
        await auth.deleteLogo();
    } catch {
        logoError.value = 'Delete failed.';
    } finally {
        logoSaving.value = false;
    }
}
</script>
