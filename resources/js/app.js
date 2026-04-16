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
        mobileSheetOpen: false,
        showSecondary: false,

        toggle(key) {
            this.openKey = this.openKey === key ? null : key;
        },

        isOpen(key) {
            return this.openKey === key;
        },

        closeAll() {
            this.openKey = null;
            this.mobileSheetOpen = false;
        },

        closeIfSame(key) {
            if (this.openKey === key) {
                this.openKey = null;
            }
        },

        activeFilterCount() {
            return [
                this.values.section !== '',
                this.values.difficulty !== '',
                this.values.status !== '',
                this.values.sort !== 'title',
            ].filter(Boolean).length;
        },

        // Only counts the filters shown in the advanced sheet (not section, which has its own tabs)
        advancedFilterCount() {
            return [
                this.values.difficulty !== '',
                this.values.status !== '',
                this.values.sort !== 'title',
            ].filter(Boolean).length;
        },

        async resetFilters() {
            this.values.difficulty = '';
            this.values.status = '';
            this.values.sort = 'title';
            await this.refreshList();
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

        // Returns the section group prefix ('lesen', 'sprach', 'hoeren', 'schreiben', or '')
        get activeGroup() {
            const s = this.values.section;
            if (!s) return '';
            const m = s.match(/^(lesen|sprach|hoeren|schreiben)/);
            return m ? m[1] : '';
        },

        // Click a group label: if already in the group keep sub-selection, else jump to defaultKey
        async selectGroup(group, defaultKey) {
            if (group === '') {
                await this.selectAndSubmit('section', '');
            } else if (this.activeGroup !== group) {
                await this.selectAndSubmit('section', defaultKey);
            }
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

// ── Session expired banner ──────────────────────────────────────────────────
window.showSessionExpiredBanner = function () {
    if (document.getElementById('_sessionExpiredBanner')) return;
    const el = document.createElement('div');
    el.id = '_sessionExpiredBanner';
    el.style.cssText = [
        'position:fixed', 'top:0', 'left:0', 'right:0', 'z-index:99999',
        'background:#991b1b', 'color:#fff', 'display:flex', 'align-items:center',
        'justify-content:center', 'gap:12px', 'padding:12px 16px',
        'font-size:14px', 'font-weight:500', 'direction:rtl',
        'box-shadow:0 2px 8px rgba(0,0,0,.35)',
    ].join(';');
    el.innerHTML = `
        <span>⚠️ انتهت الجلسة — إجاباتك لن تُحفظ. أعد تحميل الصفحة قبل الاستمرار.</span>
        <button onclick="window.location.reload()"
                style="background:#fff;color:#991b1b;border:none;padding:4px 14px;border-radius:5px;font-weight:700;cursor:pointer;flex-shrink:0;">
            إعادة تحميل
        </button>
    `;
    document.body.insertBefore(el, document.body.firstChild);
};

// ── Session keepalive — pings server every 4 min to prevent idle expiry ────
(function startSessionKeepalive() {
    setInterval(function () {
        fetch('/session-ping', { credentials: 'same-origin' }).catch(() => {});
    }, 4 * 60 * 1000);
})();

// Dark mode toggle
window.toggleDarkMode = function () {
    const html = document.getElementById('html-root');
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('telc_dark_mode', isDark ? 'dark' : 'light');
    // sync all toggle button icons
    document.querySelectorAll('[id^="darkIcon"]').forEach(function (el) { el.classList.toggle('hidden', isDark); });
    document.querySelectorAll('[id^="lightIcon"]').forEach(function (el) { el.classList.toggle('hidden', !isDark); });
};

// Sync toggle icons on load
document.addEventListener('DOMContentLoaded', function () {
    const htmlRoot = document.getElementById('html-root');
    if (!htmlRoot) return;
    const isDark = htmlRoot.classList.contains('dark');
    document.querySelectorAll('[id^="darkIcon"]').forEach(function (el) { el.classList.toggle('hidden', isDark); });
    document.querySelectorAll('[id^="lightIcon"]').forEach(function (el) { el.classList.toggle('hidden', !isDark); });
});

window.Alpine = Alpine;

Alpine.start();
