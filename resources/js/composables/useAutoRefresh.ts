import type { ComputedRef, Ref } from 'vue';
import { onUnmounted, watch } from 'vue';

export function useAutoRefresh(
    refreshFn: () => void,
    isActive: Ref<boolean> | ComputedRef<boolean>,
    intervalMs: number = 1000,
) {
    let interval: number | null = null;

    function start() {
        stop();
        interval = window.setInterval(refreshFn, intervalMs);
    }

    function stop() {
        if (interval) {
            clearInterval(interval);
            interval = null;
        }
    }

    function onVisibilityChange() {
        if (document.hidden) {
            stop();
        } else if (isActive.value) {
            refreshFn();
            start();
        }
    }

    document.addEventListener('visibilitychange', onVisibilityChange);

    watch(
        isActive,
        (active) => {
            if (active) {
                start();
            } else {
                stop();
            }
        },
        { immediate: true },
    );

    onUnmounted(() => {
        stop();
        document.removeEventListener('visibilitychange', onVisibilityChange);
    });
}
