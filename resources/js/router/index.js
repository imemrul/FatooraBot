import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/ForgotPassword.vue'),
        meta: { guest: true },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('@/pages/ResetPassword.vue'),
        meta: { guest: true },
    },
    {
        path: '/onboarding',
        name: 'onboarding',
        component: () => import('@/pages/CompanySetup.vue'),
        meta: { auth: true, skipOnboardingCheck: true },
    },
    {
        path: '/unauthorized',
        name: 'unauthorized',
        component: () => import('@/pages/Unauthorized.vue'),
        meta: { auth: true, skipOnboardingCheck: true },
    },
    // ── Super Admin Panel ──
    {
        path: '/admin',
        component: () => import('@/layouts/SuperAdminLayout.vue'),
        meta: { auth: true, superAdmin: true, skipOnboardingCheck: true },
        children: [
            { path: '', name: 'admin-dashboard', component: () => import('@/pages/admin/Dashboard.vue') },
            { path: 'tenants', name: 'admin-tenants', component: () => import('@/pages/admin/Tenants.vue') },
            { path: 'users', name: 'admin-users', component: () => import('@/pages/admin/Users.vue') },
            { path: 'plans', name: 'admin-plans', component: () => import('@/pages/admin/Plans.vue') },
            { path: 'impersonation-logs', name: 'admin-impersonation-logs', component: () => import('@/pages/admin/ImpersonationLogs.vue') },
        ],
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('@/pages/Dashboard.vue'),
            },
            {
                path: 'invoices',
                name: 'invoices',
                component: () => import('@/pages/Invoices.vue'),
                meta: { permission: 'view_invoices' },
            },
            {
                path: 'whatsapp-order',
                name: 'whatsapp-order',
                component: () => import('@/pages/WhatsAppOrder.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'sales-orders',
                name: 'sales-orders',
                component: () => import('@/pages/SalesOrders.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'clients',
                name: 'clients',
                component: () => import('@/pages/Clients.vue'),
                meta: { permission: 'view_customers' },
            },
            {
                path: 'clients/:id',
                name: 'customer-detail',
                component: () => import('@/pages/CustomerDetail.vue'),
                meta: { permission: 'view_customers' },
            },
            {
                path: 'products',
                name: 'products',
                component: () => import('@/pages/Products.vue'),
                meta: { permission: 'view_inventory' },
            },
            {
                path: 'inventory',
                name: 'inventory',
                component: () => import('@/pages/Inventory.vue'),
                meta: { permission: 'view_inventory' },
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/pages/Settings.vue'),
            },
            {
                path: 'audit-log',
                name: 'audit-log',
                component: () => import('@/pages/AuditLog.vue'),
            },
            {
                path: 'help',
                name: 'help-center',
                component: () => import('@/pages/HelpCenter.vue'),
            },
            {
                path: 'team',
                name: 'team',
                component: () => import('@/pages/Team.vue'),
                meta: { permission: 'manage_users' },
            },
            {
                path: 'expenses',
                name: 'expenses',
                component: () => import('@/pages/Expenses.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'recurring-invoices',
                name: 'recurring-invoices',
                component: () => import('@/pages/RecurringInvoices.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'currencies',
                name: 'currencies',
                component: () => import('@/pages/Currencies.vue'),
            },
            {
                path: 'reports',
                name: 'reports',
                component: () => import('@/pages/Reports.vue'),
                meta: { permission: 'view_reports' },
            },
            {
                path: 'quotations',
                name: 'quotations',
                component: () => import('@/pages/Quotations.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'credit-notes',
                name: 'credit-notes',
                component: () => import('@/pages/CreditNotes.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'delivery-notes',
                name: 'delivery-notes',
                component: () => import('@/pages/DeliveryNotes.vue'),
                meta: { permission: 'manage_invoices' },
            },
            {
                path: 'purchase-orders',
                name: 'purchase-orders',
                component: () => import('@/pages/PurchaseOrders.vue'),
                meta: { permission: 'manage_inventory' },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // Guest-only routes
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    // Auth-required routes
    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    // Fetch user on first authenticated navigation
    if (to.meta.auth && auth.isAuthenticated && !auth.user) {
        try {
            await auth.fetchUser();
        } catch {
            auth.clearAuth();
            return { name: 'login' };
        }
    }

    // Onboarding guard — redirect to wizard if company not onboarded
    if (to.meta.auth && !to.meta.skipOnboardingCheck && auth.user && !auth.companyOnboarded) {
        return { name: 'onboarding' };
    }

    // Super admin guard
    if (to.meta.superAdmin && auth.user && !auth.user.is_super_admin) {
        return { name: 'unauthorized' };
    }

    // Permission guard
    if (to.meta.permission && auth.user) {
        const required = to.meta.permission;
        const allowed = Array.isArray(required)
            ? auth.canAny(required)
            : auth.can(required);

        if (!allowed) {
            return { name: 'unauthorized' };
        }
    }
});

export default router;
