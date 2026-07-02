const normalizeCode = (value) => (value || '').trim().toUpperCase();

export function registerQuickAccessModal() {
    window.quickAccessModal = ({ shortcuts = [], actionEndpoint = '' } = {}) => ({
        isOpen: false,
        isExecuting: false,
        query: '',
        feedbackMessage: '',
        errorMessage: '',
        shortcuts: shortcuts.map((shortcut) => ({
            ...shortcut,
            code: normalizeCode(shortcut.code),
        })),
        actionEndpoint,

        get filteredShortcuts() {
            const normalizedQuery = normalizeCode(this.query);
            if (!normalizedQuery) {
                return this.shortcuts;
            }

            return this.shortcuts.filter((shortcut) => {
                return shortcut.code.includes(normalizedQuery)
                    || shortcut.label.toUpperCase().includes(normalizedQuery);
            });
        },

        open() {
            this.isOpen = true;
            this.query = '';
            this.errorMessage = '';
            this.feedbackMessage = '';
            this.$nextTick(() => {
                this.$refs.shortcutInput.focus();
            });
        },

        close() {
            this.isOpen = false;
            this.query = '';
            this.isExecuting = false;
        },

        handleGlobalKeydown(event) {
            if (event.ctrlKey && event.key.toLowerCase() === 'q') {
                event.preventDefault();
                this.open();
                return;
            }

            if (!this.isOpen) {
                return;
            }

            if (event.key === 'Escape') {
                this.close();
            }
        },

        handleInput() {
            const shortcut = this.findExactShortcut(this.query);
            if (shortcut) {
                this.executeShortcut(shortcut);
            }
        },

        executeCurrentInput() {
            const exactShortcut = this.findExactShortcut(this.query);
            if (exactShortcut) {
                this.executeShortcut(exactShortcut);
                return;
            }

            const [firstShortcut] = this.filteredShortcuts;
            if (firstShortcut) {
                this.executeShortcut(firstShortcut);
            }
        },

        findExactShortcut(value) {
            const normalizedQuery = normalizeCode(value);
            if (!normalizedQuery) {
                return null;
            }

            return this.shortcuts.find((shortcut) => shortcut.code === normalizedQuery) || null;
        },

        async executeShortcut(shortcut) {
            if (this.isExecuting) {
                return;
            }

            this.errorMessage = '';
            this.feedbackMessage = '';
            if (shortcut.type === 'route') {
                this.close();
                window.location.href = shortcut.url;
                return;
            }

            if (shortcut.type === 'action') {
                await this.executeAction(shortcut);
                return;
            }

            this.errorMessage = 'Unsupported shortcut type.';
        },

        async executeAction(shortcut) {
            this.isExecuting = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(this.actionEndpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        code: shortcut.code,
                    }),
                });

                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Action could not be completed.');
                }

                this.feedbackMessage = payload.message || 'Action completed.';
                this.close();
            } catch (error) {
                this.errorMessage = error.message || 'Action failed.';
            } finally {
                this.isExecuting = false;
            }
        },
    });
}
