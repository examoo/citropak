import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Composable for checking user permissions and roles
 */
export function usePermissions() {
    const page = usePage();

    /**
     * Get all user permissions
     */
    const permissions = computed(() => {
        return page.props.auth?.permissions || [];
    });

    /**
     * Get all user roles
     */
    const roles = computed(() => {
        return page.props.auth?.roles || [];
    });

    /**
     * Check if user has a specific permission
     * @param {string} permission - Permission name to check
     * @returns {boolean}
     */
    const can = (permission) => {
        // Superadmin has all permissions
        if (isSuperAdmin()) return true;
        return permissions.value.includes(permission);
    };

    /**
     * Check if user has any of the specified permissions
     * @param {string[]} permArr - Array of permission names
     * @returns {boolean}
     */
    const canAny = (permArr) => {
        if (isSuperAdmin()) return true;
        return permArr.some(p => permissions.value.includes(p));
    };

    /**
     * Check if user has all specified permissions
     * @param {string[]} permArr - Array of permission names
     * @returns {boolean}
     */
    const canAll = (permArr) => {
        if (isSuperAdmin()) return true;
        return permArr.every(p => permissions.value.includes(p));
    };

    /**
     * Check if user has a specific role
     * @param {string} role - Role name to check
     * @returns {boolean}
     */
    const hasRole = (role) => {
        return roles.value.includes(role);
    };

    /**
     * Check if user has any of the specified roles
     * @param {string[]} roleArr - Array of role names
     * @returns {boolean}
     */
    const hasAnyRole = (roleArr) => {
        return roleArr.some(r => roles.value.includes(r));
    };

    /**
     * Check if user is superadmin
     * @returns {boolean}
     */
    const isSuperAdmin = () => {
        return roles.value.includes('superadmin');
    };

    /**
     * Check if user is admin or superadmin
     * @returns {boolean}
     */
    const isAdmin = () => {
        return hasAnyRole(['superadmin', 'admin']);
    };

    return {
        permissions,
        roles,
        can,
        canAny,
        canAll,
        hasRole,
        hasAnyRole,
        isSuperAdmin,
        isAdmin,
    };
}
