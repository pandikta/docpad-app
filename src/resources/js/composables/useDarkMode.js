import { ref, onMounted } from 'vue';

const isDark = ref(false);

/**
 * Toggle dark mode (class `.dark-mode` di <html>) + simpan pilihan di localStorage.
 */
export function useDarkMode() {
    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
        document.documentElement.classList.toggle('dark-mode', isDark.value);
        localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    };

    onMounted(() => {
        isDark.value = document.documentElement.classList.contains('dark-mode');
    });

    return { isDark, toggleDarkMode };
}
