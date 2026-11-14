import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Global frontend auth helper (mirror dari AuthHelper di backend).
 * Gunakan di komponen mana pun untuk mengecek status login, role superadmin, dan izin fungsi.
 */
export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);
    const isSuperAdmin = computed(() => Boolean(user.value?.is_superadmin));
    const permissions = computed(() => page.props.auth?.permissions ?? {});

    /**
     * Cek apakah user yang login berhak menjalankan fungsi tertentu (mis. 'documents.delete').
     */
    const can = (functionCode) => {
        if (isSuperAdmin.value) return true;
        return Boolean(permissions.value?.[functionCode]);
    };

    return {
        user,
        isSuperAdmin,
        permissions,
        can,
    };
}
