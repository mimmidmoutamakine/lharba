(() => {
    const STORAGE_KEY = 'telc_exam_font_scale';
    const MIN_SCALE = 0.8;
    const MAX_SCALE = 1.25;
    const STEP = 0.05;
    const DEFAULT_SCALE = 1;

    const parseScale = (value) => {
        const numeric = Number(value);
        if (Number.isNaN(numeric)) {
            return DEFAULT_SCALE;
        }
        return Math.min(MAX_SCALE, Math.max(MIN_SCALE, numeric));
    };

    const applyScale = (root, scale) => {
        const nodes = root.querySelectorAll('h1,h2,h3,h4,h5,h6,p,span,label,a,button,li,td,th,input,textarea');
        nodes.forEach((node) => {
            if (!node.dataset.baseFontSize) {
                const computed = window.getComputedStyle(node);
                node.dataset.baseFontSize = String(parseFloat(computed.fontSize || '16'));
                const lineHeight = computed.lineHeight || '';
                if (lineHeight.endsWith('px')) {
                    node.dataset.baseLineHeight = String(parseFloat(lineHeight));
                }
            }

            const base = Number(node.dataset.baseFontSize || '16');
            node.style.fontSize = `${Math.max(10, base * scale)}px`;

            const baseLineHeight = Number(node.dataset.baseLineHeight || '0');
            if (baseLineHeight > 0) {
                node.style.lineHeight = `${Math.max(12, baseLineHeight * scale)}px`;
            }
        });

        document.querySelectorAll('[data-font-scale-label]').forEach((label) => {
            label.textContent = `${Math.round(scale * 100)}%`;
        });
    };

    const init = () => {
        const root = document.querySelector('[data-exam-scale-root]');
        if (!root) {
            return;
        }

        let scale = parseScale(localStorage.getItem(STORAGE_KEY));
        applyScale(root, scale);

        document.querySelectorAll('[data-font-control="increase"]').forEach((button) => {
            button.addEventListener('click', () => {
                scale = parseScale((scale + STEP).toFixed(2));
                localStorage.setItem(STORAGE_KEY, String(scale));
                applyScale(root, scale);
            });
        });

        document.querySelectorAll('[data-font-control="decrease"]').forEach((button) => {
            button.addEventListener('click', () => {
                scale = parseScale((scale - STEP).toFixed(2));
                localStorage.setItem(STORAGE_KEY, String(scale));
                applyScale(root, scale);
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
