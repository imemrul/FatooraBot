import { defineStore } from 'pinia';
import api from '@/utils/api';

export const useInvoiceStore = defineStore('invoices', {
    state: () => ({
        invoices: [],
        current: null,
        pagination: null,
        loading: false,
    }),

    actions: {
        async fetchInvoices(page = 1, status = '') {
            this.loading = true;
            const params = { page };
            if (status) params.status = status;
            const { data } = await api.get('/invoices', { params });
            this.invoices = data.data;
            this.pagination = data.meta;
            this.loading = false;
        },

        async fetchInvoice(id) {
            this.loading = true;
            const { data } = await api.get(`/invoices/${id}`);
            this.current = data.data;
            this.loading = false;
            return data.data;
        },

        async createInvoice(payload) {
            const { data } = await api.post('/invoices', payload);
            return data.data;
        },

        async updateInvoice(id, payload) {
            const { data } = await api.put(`/invoices/${id}`, payload);
            return data.data;
        },

        async updateStatus(id, status) {
            const { data } = await api.patch(`/invoices/${id}/status`, { status });
            const idx = this.invoices.findIndex((i) => i.id === id);
            if (idx !== -1) this.invoices[idx] = data.data;
            return data.data;
        },

        async sendInvoice(id) {
            const { data } = await api.post(`/invoices/${id}/send`);
            const idx = this.invoices.findIndex((i) => i.id === id);
            if (idx !== -1) this.invoices[idx] = data.data;
            return data.data;
        },

        async recordPayment(id, payload) {
            const { data } = await api.post(`/invoices/${id}/payments`, payload);
            const idx = this.invoices.findIndex((i) => i.id === id);
            if (idx !== -1) this.invoices[idx] = data.invoice;
            return data;
        },

        async downloadPdf(id, invoiceNumber) {
            const response = await api.get(`/invoices/${id}/pdf`, { responseType: 'blob' });
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.download = `invoice-${invoiceNumber}.pdf`;
            link.click();
            window.URL.revokeObjectURL(url);
        },

        async deleteInvoice(id) {
            await api.delete(`/invoices/${id}`);
            this.invoices = this.invoices.filter((i) => i.id !== id);
        },
    },
});
