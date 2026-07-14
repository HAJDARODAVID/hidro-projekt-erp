const APPLICATION_MODAL_EVENT = 'open-application-modal';
const CALCULATOR_MODAL_KEY = 'calculator';

const dispatchCalculatorModalOpen = () => {
    window.dispatchEvent(new CustomEvent(APPLICATION_MODAL_EVENT, {
        detail: { modal: CALCULATOR_MODAL_KEY },
    }));
};

export function registerCalculatorModal() {
    window.calculatorModal = () => ({
        isOpen: false,

        init() {
            window.addEventListener(APPLICATION_MODAL_EVENT, (event) => {
                if (event && event.detail && event.detail.modal === CALCULATOR_MODAL_KEY) {
                    this.open();
                }
            });
        },

        open() {
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
        },
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('#calculator');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        dispatchCalculatorModalOpen();
    });
}
