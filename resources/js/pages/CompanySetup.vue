<template>
    <div class="min-h-screen bg-slate-50 flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4">
            <div class="max-w-2xl mx-auto flex items-center justify-between">
                <h1 class="text-lg font-bold text-indigo-600">FatooraBot</h1>
                <p class="text-sm text-slate-400">Company Setup</p>
            </div>
        </header>

        <div class="flex-1 flex items-start justify-center pt-12 px-4">
            <div class="w-full max-w-2xl">
                <!-- Step indicator -->
                <div class="flex items-center justify-center gap-2 mb-10">
                    <template v-for="(label, i) in stepLabels" :key="i">
                        <div class="flex items-center gap-2">
                            <div
                                :class="[
                                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition',
                                    step > i + 1 ? 'bg-indigo-600 text-white' :
                                    step === i + 1 ? 'bg-indigo-600 text-white' :
                                    'bg-slate-200 text-slate-500'
                                ]"
                            >
                                <span v-if="step > i + 1">&#10003;</span>
                                <span v-else>{{ i + 1 }}</span>
                            </div>
                            <span class="text-sm font-medium hidden sm:inline"
                                :class="step >= i + 1 ? 'text-slate-900' : 'text-slate-400'">
                                {{ label }}
                            </span>
                        </div>
                        <div v-if="i < stepLabels.length - 1"
                            class="w-12 h-px"
                            :class="step > i + 1 ? 'bg-indigo-600' : 'bg-slate-200'"></div>
                    </template>
                </div>

                <!-- Error banner -->
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6">
                    {{ error }}
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                    <!-- Step 1: Business Info -->
                    <div v-show="step === 1">
                        <h2 class="text-xl font-semibold text-slate-900 mb-1">Business Information</h2>
                        <p class="text-sm text-slate-500 mb-6">Tell us about your company.</p>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Company name *</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                                <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name[0] }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email *</label>
                                    <input v-model="form.email" type="email" required
                                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                                    <p v-if="errors.email" class="text-red-600 text-xs mt-1">{{ errors.email[0] }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone *</label>
                                    <input v-model="form.phone" type="tel" required
                                        class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                        placeholder="+971 50 123 4567" />
                                    <p v-if="errors.phone" class="text-red-600 text-xs mt-1">{{ errors.phone[0] }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Address *</label>
                                <textarea v-model="form.address" rows="2" required
                                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"></textarea>
                                <p v-if="errors.address" class="text-red-600 text-xs mt-1">{{ errors.address[0] }}</p>
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
                    </div>

                    <!-- Step 2: Legal & Tax -->
                    <div v-show="step === 2">
                        <h2 class="text-xl font-semibold text-slate-900 mb-1">Legal & Tax Details</h2>
                        <p class="text-sm text-slate-500 mb-6">Optional but recommended for invoicing compliance.</p>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Trade License Number</label>
                                <input v-model="form.trade_license_number" type="text"
                                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                    placeholder="e.g. TL-123456" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Tax Registration Number (TRN)</label>
                                <input v-model="form.tax_registration_number" type="text" maxlength="15"
                                    class="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                    placeholder="100000000000003" />
                                <p class="text-xs text-slate-400 mt-1">15-digit UAE TRN for VAT invoices</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Country</label>
                                    <input v-model="form.country" type="text" maxlength="2" disabled
                                        class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm bg-slate-50 text-slate-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Currency</label>
                                    <input v-model="form.currency" type="text" maxlength="3" disabled
                                        class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm bg-slate-50 text-slate-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Logo & Review -->
                    <div v-show="step === 3">
                        <h2 class="text-xl font-semibold text-slate-900 mb-1">Company Logo</h2>
                        <p class="text-sm text-slate-500 mb-6">Upload your logo. It will appear on invoices.</p>

                        <div class="flex items-start gap-6 mb-8">
                            <div class="w-24 h-24 rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0 bg-slate-50">
                                <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain" />
                                <span v-else class="text-slate-400 text-xs text-center px-2">No logo</span>
                            </div>
                            <div>
                                <label class="inline-block px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 cursor-pointer transition">
                                    Choose file
                                    <input type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="handleLogoSelect" />
                                </label>
                                <button v-if="logoPreview" @click="removeLogo" class="ml-2 text-sm text-red-600 hover:text-red-700">Remove</button>
                                <p class="text-xs text-slate-400 mt-2">PNG, JPG, SVG or WebP. Max 2MB.</p>
                                <p v-if="logoError" class="text-red-600 text-xs mt-1">{{ logoError }}</p>
                            </div>
                        </div>

                        <!-- Review summary -->
                        <div class="border-t border-slate-100 pt-6">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3">Review</h3>
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                                <dt class="text-slate-500">Company</dt><dd class="text-slate-900 font-medium">{{ form.name }}</dd>
                                <dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ form.email }}</dd>
                                <dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ form.phone }}</dd>
                                <dt class="text-slate-500">City</dt><dd class="text-slate-900">{{ form.city }}</dd>
                                <dt class="text-slate-500">Trade License</dt><dd class="text-slate-900">{{ form.trade_license_number || '—' }}</dd>
                                <dt class="text-slate-500">TRN</dt><dd class="text-slate-900">{{ form.tax_registration_number || '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <!-- Navigation buttons -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-slate-100">
                        <button v-if="step > 1" @click="step--"
                            class="px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                            Back
                        </button>
                        <div v-else></div>

                        <button v-if="step < 3" @click="nextStep"
                            class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                            Continue
                        </button>
                        <button v-else @click="submit" :disabled="saving"
                            class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition">
                            {{ saving ? 'Saving...' : 'Complete Setup' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const step = ref(1);
const saving = ref(false);
const error = ref('');
const errors = ref({});
const logoFile = ref(null);
const logoPreview = ref(null);
const logoError = ref('');

const stepLabels = ['Business Info', 'Legal & Tax', 'Logo & Review'];
const cities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];

const form = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    trade_license_number: '',
    tax_registration_number: '',
    country: 'AE',
    currency: 'AED',
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
        form.country = c.country || 'AE';
        form.currency = c.currency || 'AED';
        if (c.logo_url) logoPreview.value = c.logo_url;
    }
});

function nextStep() {
    error.value = '';
    errors.value = {};

    if (step.value === 1) {
        const missing = [];
        if (!form.name.trim()) missing.push('name');
        if (!form.email.trim()) missing.push('email');
        if (!form.phone.trim()) missing.push('phone');
        if (!form.address.trim()) missing.push('address');
        if (!form.city) missing.push('city');
        if (missing.length) {
            const errs = {};
            missing.forEach((f) => errs[f] = ['This field is required.']);
            errors.value = errs;
            return;
        }
    }

    step.value++;
}

function handleLogoSelect(e) {
    logoError.value = '';
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        logoError.value = 'File must be 2MB or smaller.';
        return;
    }

    logoFile.value = file;
    logoPreview.value = URL.createObjectURL(file);
}

function removeLogo() {
    logoFile.value = null;
    logoPreview.value = null;
}

async function submit() {
    saving.value = true;
    error.value = '';
    errors.value = {};

    try {
        await auth.updateCompany(form);

        if (logoFile.value) {
            await auth.uploadLogo(logoFile.value);
        }

        await auth.fetchUser();
        router.push('/');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
            error.value = e.response.data.message || 'Please fix the errors below.';
            const errorFields = Object.keys(errors.value);
            if (errorFields.some((f) => ['name', 'email', 'phone', 'address', 'city'].includes(f))) {
                step.value = 1;
            }
        } else {
            error.value = 'Something went wrong. Please try again.';
        }
    } finally {
        saving.value = false;
    }
}
</script>
