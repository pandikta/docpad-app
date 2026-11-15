import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Global frontend auth helper (mirror dari AuthHelper di backend).
 * Gunakan di komponen mana pun untuk mengecek status login, role superadmin, izin fungsi, dan akses menu.
 */
export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);
    const isSuperAdmin = computed(() => Boolean(user.value?.is_superadmin));
    const permissions = computed(() => page.props.auth?.permissions ?? {});
    const menus = computed(() => page.props.auth?.menus ?? {});
    const visibleMenus = computed(() => page.props.auth?.visibleMenus ?? { main: [], settings: [] });

    /**
     * Cek apakah user yang login berhak menjalankan fungsi tertentu (mis. 'documents.delete').
     */
    const can = (functionCode) => {
        if (isSuperAdmin.value) return true;
        return Boolean(permissions.value?.[functionCode]);
    };

    /**
     * Cek apakah user yang login berhak mengakses menu tertentu (mis. 'menu.settings.teams').
     */
    const canAccessMenu = (menuCode) => {
        if (isSuperAdmin.value) return true;
        if (!menuCode) return true;
        return Boolean(menus.value?.[menuCode]);
    };

    return {
        user,
        isSuperAdmin,
        permissions,
        menus,
        visibleMenus,
        can,
        canAccessMenu,
    };
}
