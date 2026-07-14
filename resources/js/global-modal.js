const APPLICATION_MODAL_EVENT = 'open-application-modal';
const GLOBAL_MODAL_KEY = 'global';

export function registerGlobalModal() {
    window.globalModal = () => ({
        isOpen: false,

        close() {
            this.isOpen = false;
            // Tell PHP to clear componentName so the child is unmounted on re-render.
            Livewire.dispatch('clear-global-modal');
        },
    });

    // Quick-access shortcut: { type: 'modal', modal: 'global', component: '...', params: {...} }
    window.addEventListener(APPLICATION_MODAL_EVENT, (event) => {
        if (event?.detail?.modal !== GLOBAL_MODAL_KEY || !event?.detail?.component) {
            return;
        }
        Livewire.dispatch('open-global-modal', {
            component: event.detail.component,
            params: event.detail.params || {},
        });
    });

    // Button click:
    // <button id="global-modal"           data-component="livewire.comp.name">
    // <button data-trigger="global-modal" data-component="livewire.comp.name" data-params='{"id":1}'>
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[id="global-modal"], [data-trigger="global-modal"]');
        if (!trigger) {
            return;
        }

        const component = trigger.dataset.component;
        if (!component) {
            return;
        }

        let params = {};
        if (trigger.dataset.params) {
            try {
                params = JSON.parse(trigger.dataset.params);
            } catch {
                console.error('[global-modal] Invalid JSON in data-params:', trigger.dataset.params);
            }
        }

        event.preventDefault();
        Livewire.dispatch('open-global-modal', { component, params });
    });
}




