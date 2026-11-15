import { ref } from 'vue';
import en from '@/lang/en.json';
import id from '@/lang/id.json';

const STORAGE_KEY = 'docpad_locale';
const MESSAGES = { en, id };

const getInitialLocale = () => {
    try {
        const saved = window.localStorage.getItem(STORAGE_KEY);
        return saved === 'id' || saved === 'en' ? saved : 'en';
    } catch {
        return 'en';
    }
};

const locale = ref(getInitialLocale());

const resolveKey = (dict, key) =>
    key.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : undefined), dict);

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

    const t = (key, params = {}) => {
        const primary = resolveKey(MESSAGES[locale.value], key);
        const fallback = resolveKey(MESSAGES.en, key);
        let text = primary ?? fallback ?? key;

        if (typeof text === 'string') {
            for (const [paramKey, paramValue] of Object.entries(params)) {
                text = text.replaceAll(`{${paramKey}}`, String(paramValue ?? ''));
            }
        }

        return text;
    };

    return {
        locale,
        setLocale,
        t,
    };
}
