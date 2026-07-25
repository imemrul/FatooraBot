import { defineStore } from 'pinia';
import api from '@/utils/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token'),
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        company: (state) => state.user?.company,
        companyOnboarded: (state) => state.user?.company?.onboarded ?? false,
        emailVerified: (state) => state.user?.email_verified ?? false,
        userRoles: (state) => state.user?.roles ?? [],
        userPermissions: (state) => state.user?.permissions ?? [],
        hasRole: (state) => (role) => state.user?.roles?.includes(role) ?? false,
        isOwner: (state) => state.user?.roles?.includes('owner') ?? false,
        can: (state) => (permission) => {
            if (state.user?.roles?.includes('owner')) return true;
            return state.user?.permissions?.includes(permission) ?? false;
        },
        canAny: (state) => (permissions) => {
            if (state.user?.roles?.includes('owner')) return true;
            return permissions.some((p) => state.user?.permissions?.includes(p)) ?? false;
        },
    },

    actions: {
        async login(email, password) {
            const { data } = await api.post('/login', { email, password });
            this.setAuth(data);
        },

        async register(payload) {
            const { data } = await api.post('/register', payload);
            this.setAuth(data);
        },

        async fetchUser() {
            const { data } = await api.get('/me');
            this.user = data.user;
        },

        async logout() {
            try {
                await api.post('/logout');
            } finally {
                this.clearAuth();
            }
        },

        async forgotPassword(email) {
            const { data } = await api.post('/forgot-password', { email });
            return data.message;
        },

        async resetPassword(payload) {
            const { data } = await api.post('/reset-password', payload);
            return data.message;
        },

        async resendVerification() {
            const { data } = await api.post('/email/resend');
            return data.message;
        },

        async verifyEmail() {
            const { data } = await api.post('/email/verify');
            if (this.user) this.user.email_verified = true;
            return data.message;
        },

        async updateCompany(payload) {
            const { data } = await api.put('/company', payload);
            if (this.user) this.user.company = data.company;
            return data;
        },

        async uploadLogo(file) {
            const form = new FormData();
            form.append('logo', file);
            const { data } = await api.post('/company/logo', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (this.user) this.user.company = data.company;
            return data;
        },

        async deleteLogo() {
            const { data } = await api.delete('/company/logo');
            if (this.user) this.user.company = data.company;
            return data;
        },

        setAuth(data) {
            this.user = data.user;
            this.token = data.token;
            localStorage.setItem('token', data.token);
        },

        clearAuth() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('token');
        },
    },
});
