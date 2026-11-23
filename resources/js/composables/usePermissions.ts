import { usePage } from '@inertiajs/vue3';

type PermissionState = {
    admin?: boolean;
    granted?: string[];
};

export function usePermissions() {
    const page = usePage();
    const can = (key: string): boolean => {
        const state = (page.props as unknown as { permissions?: PermissionState }).permissions;
        return Boolean(state?.admin || state?.granted?.includes(key));
    };
    return { can };
}
