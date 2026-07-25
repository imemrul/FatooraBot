import { ref, watch, onMounted } from 'vue';

const isDark = ref(false);

export function useTheme() {
    function apply() {
        document.documentElement.classList.toggle('dark', isDark.value);
    }

    function toggle() {
        isDark.value = !isDark.value;
        localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
        apply();
    }

    onMounted(() => {
        const stored = localStorage.getItem('theme');
        if (stored) {
            isDark.value = stored === 'dark';
        } else {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        apply();
    });

    return { isDark, toggle };
}
