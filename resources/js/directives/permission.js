import { useAuthStore } from '@/stores/auth';

function checkPermission(el, binding) {
    const auth = useAuthStore();
    const permissions = auth.user?.permissions ?? [];
    const roles = auth.user?.roles ?? [];

    if (roles.includes('owner')) return;

    const required = binding.value;
    let hasAccess = false;

    if (Array.isArray(required)) {
        hasAccess = required.some((p) => permissions.includes(p));
    } else {
        hasAccess = permissions.includes(required);
    }

    if (!hasAccess) {
        el.style.display = 'none';
    } else {
        el.style.display = '';
    }
}

export const vCan = {
    mounted: checkPermission,
    updated: checkPermission,
};

export const vRole = {
    mounted(el, binding) {
        const auth = useAuthStore();
        const roles = auth.user?.roles ?? [];
        const required = Array.isArray(binding.value) ? binding.value : [binding.value];

        if (!required.some((r) => roles.includes(r))) {
            el.style.display = 'none';
        }
    },
    updated(el, binding) {
        const auth = useAuthStore();
        const roles = auth.user?.roles ?? [];
        const required = Array.isArray(binding.value) ? binding.value : [binding.value];

        el.style.display = required.some((r) => roles.includes(r)) ? '' : 'none';
    },
};
