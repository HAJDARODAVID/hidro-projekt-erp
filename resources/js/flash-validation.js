const FLASH_ATTR = 'data-flash-validation';
const FLASH_CLASSES = ['is-valid', 'is-invalid'];
const FLASH_DELAY_MS = 3000;

const timers = new WeakMap();

function scheduleFlash(el) {
    if (!el || !el.hasAttribute || !el.hasAttribute(FLASH_ATTR)) {
        return;
    }
    if (!FLASH_CLASSES.some((cls) => el.classList.contains(cls))) {
        return;
    }

    clearTimeout(timers.get(el));
    timers.set(
        el,
        setTimeout(() => {
            el.classList.remove(...FLASH_CLASSES);
            el.removeAttribute(FLASH_ATTR);
            timers.delete(el);
        }, FLASH_DELAY_MS)
    );
}

/**
 * The "saved" indicator (is-valid/is-invalid) on the ui-input/ui-select components fades
 * out on its own instead of staying until something else re-renders the field.
 */
export function registerFlashValidation() {
    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('morph.added', ({ el }) => scheduleFlash(el));
        window.Livewire.hook('morph.updated', ({ el }) => scheduleFlash(el));
    });
}
