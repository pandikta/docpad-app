import { ref } from 'vue';

const STORAGE_KEY = 'docpad_locale';

const getInitialLocale = () => {
    try {
        const saved = window.localStorage.getItem(STORAGE_KEY);
        return saved === 'id' || saved === 'en' ? saved : 'en';
    } catch {
        return 'en';
    }
};

const locale = ref(getInitialLocale());

export function useLocale() {
    const setLocale = (nextLocale) => {
        if ((nextLocale !== 'en' && nextLocale !== 'id') || nextLocale === locale.value) return;
        locale.value = nextLocale;
        try {
            window.localStorage.setItem(STORAGE_KEY, nextLocale);
        } catch {
            // ignore storage errors
        }
        window.location.reload();
    };

    return {
        locale,
        setLocale,
    };
}
