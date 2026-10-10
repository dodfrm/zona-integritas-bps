import { usePage } from '@inertiajs/react';

/**
 * UI-only permission check. The server re-checks permission and organization scope on every action.
 */
export function usePermissions() {
    const { auth } = usePage().props;
    const granted = new Set(auth.permissions);

    return {
        can: (...permissions: string[]) =>
            permissions.some((permission) => granted.has(permission)),
    };
}
