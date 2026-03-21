import './bootstrap';
import './exam-matching';
import './exam-reading-mcq';
import './exam-situation-ads';
import './exam-sprach-gap';
import './exam-sprach-pool';
import './exam-hoeren-tf';
import './exam-writing';
import './exam-font-scale';

import Alpine from 'alpinejs';

window.showExamSubmitDialog = function showExamSubmitDialog() {
    return new Promise((resolve) => {
        const existing = document.getElementById('examSubmitDialogOverlay');
        if (existing) {
            existing.remove();
        }

        const overlay = document.createElement('div');
        overlay.id = 'examSubmitDialogOverlay';
        overlay.className = 'exam-submit-overlay';
        overlay.innerHTML = `
            <div class="exam-submit-dialog">
                <div class="exam-submit-title">
                    Sind Sie sicher, dass Sie die Prüfung versenden wollen?
                </div>
                <div class="exam-submit-actions">
                    <button type="button" data-submit-confirm class="exam-submit-btn">
                        Prüfung versenden
                    </button>
                    <button type="button" data-submit-cancel class="exam-submit-btn">
                        Abbrechen
                    </button>
                </div>
            </div>
        `;

        const cleanup = () => {
            document.removeEventListener('keydown', onKeyDown);
            overlay.remove();
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                cleanup();
                resolve(false);
            }
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                cleanup();
                resolve(false);
            }
        });

        overlay.querySelector('[data-submit-confirm]')?.addEventListener('click', () => {
            cleanup();
            resolve(true);
        });
        overlay.querySelector('[data-submit-cancel]')?.addEventListener('click', () => {
            cleanup();
            resolve(false);
        });

        document.addEventListener('keydown', onKeyDown);
        document.body.appendChild(overlay);
    });
};

window.trainingFilterBar = function ({ values, options }) {
    return {
        values: { ...values },
        options,
        openKey: null,
        loading: false,

        toggle(key) {
            this.openKey = this.openKey === key ? null : key;
        },

        isOpen(key) {
            return this.openKey === key;
        },

        closeAll() {
            this.openKey = null;
        },

        closeIfSame(key) {
            if (this.openKey === key) {
                this.openKey = null;
            }
        },

        selectedLabel(key) {
            const current = this.values[key] ?? '';
            const found = (this.options[key] || []).find(
                (option) => option.value === current
            );

            return found ? found.label : '';
        },

        async selectAndSubmit(key, value) {
            this.values[key] = value;
            this.closeAll();
            await this.refreshList();
        },

        async refreshList() {
            this.loading = true;

            const params = new URLSearchParams({
                section: this.values.section ?? '',
                difficulty: this.values.difficulty ?? '',
                status: this.values.status ?? '',
                sort: this.values.sort ?? '',
            });

            const url = `${window.location.pathname}?${params.toString()}`;

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                const container = document.getElementById('training-cards-container');
                if (container && data.html) {
                    container.innerHTML = data.html;
                }

                window.history.replaceState({}, '', url);
            } catch (error) {
                console.error('Filter refresh failed:', error);
            } finally {
                this.loading = false;
            }
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
