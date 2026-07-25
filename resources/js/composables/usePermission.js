import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function usePermission() {
    const auth = useAuthStore();

    const permissions = computed(() => auth.user?.permissions ?? []);
    const roles = computed(() => auth.user?.roles ?? []);

    function can(permission) {
        if (roles.value.includes('owner')) return true;
        return permissions.value.includes(permission);
    }

    function canAny(perms) {
        if (roles.value.includes('owner')) return true;
        return perms.some((p) => permissions.value.includes(p));
    }

    function hasRole(role) {
        return roles.value.includes(role);
    }

    function hasAnyRole(roleList) {
        return roleList.some((r) => roles.value.includes(r));
    }

    function isOwner() {
        return roles.value.includes('owner');
    }

    return { permissions, roles, can, canAny, hasRole, hasAnyRole, isOwner };
}
