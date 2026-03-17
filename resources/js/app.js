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

window.trainingFilterBar = function trainingFilterBar(config) {
    return {
        openKey: null,
        values: {
            section: config.values?.section ?? '',
            difficulty: config.values?.difficulty ?? '',
            status: config.values?.status ?? '',
            sort: config.values?.sort ?? 'title',
        },
        options: config.options ?? {},
        toggle(key) {
            this.openKey = this.openKey === key ? null : key;
        },
        isOpen(key) {
            return this.openKey === key;
        },
        closeAll() {
            this.openKey = null;
        },
        select(key, value) {
            this.values[key] = value;
            this.closeAll();
        },
        selectedLabel(key) {
            const list = this.options[key] ?? [];
            const selected = list.find((option) => option.value === this.values[key]);
            return selected?.label ?? list[0]?.label ?? '';
        },
    };
};

window.Alpine = Alpine;

Alpine.start();
