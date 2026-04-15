import Sortable from 'sortablejs';

window.matchingEngine = function matchingEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        textIds: config.textIds.map((id) => Number(id)),
        options: config.options,
        optionMap: {},
        optionIds: [],
        assignments: config.initialAssignments || {},
        selectedTextId: null,
        selectedOptionId: null,
        draggingOptionId: null,
        remainingSeconds: Number(config.remainingSeconds || 0),
        textCount: Number(config.textCount || 0),
        respectTime: window._examRespectTime !== false,
        timeModeUrl: window._examTimeModeUrl || '',
        statusMessage: 'Bereit',
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        draftKey: null,
        mobilePickerOpen: false,
        activeTextId: null,

        answeredCount() {
            return Object.values(this.assignments || {}).filter(Boolean).length;
        },

        openMobilePicker(textId) {
            this.activeTextId = textId;

            // نخليو نفس منطق desktop خدام
            if (typeof this.handleTextClick === 'function') {
                this.handleTextClick(textId);
            }

            this.mobilePickerOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeMobilePicker() {
            this.mobilePickerOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        chooseMobileOption(optionId) {
            if (!this.activeTextId) return;

            // نستعمل نفس logic ديال desktop
            if (typeof this.handleOptionClick === 'function') {
                this.handleOptionClick(optionId);
            }

            this.closeMobilePicker();
        },

        clearActiveTextAssignment() {
            if (!this.activeTextId) return;

            if (typeof this.removeAssignment === 'function') {
                this.removeAssignment(this.activeTextId);
            }

            this.closeMobilePicker();
        },

        activeTextLabel() {
            if (!this.activeTextId) return '';
            const found = this.textIds.find((id) => Number(id) === Number(this.activeTextId));
            if (!found) return '';
            const index = this.textIds.findIndex((id) => Number(id) === Number(this.activeTextId));
            return index >= 0 ? index + 1 : '';
        },

        isOptionAssignedToAnotherText(optionId) {
            const entries = Object.entries(this.assignments || {});
            for (const [textId, assignedOptionId] of entries) {
                if (Number(assignedOptionId) === Number(optionId) && Number(textId) !== Number(this.activeTextId)) {
                    return true;
                }
            }
            return false;
        },

        isOptionAssignedToActiveText(optionId) {
            if (!this.activeTextId) return false;
            return Number(this.assignments?.[this.activeTextId]) === Number(optionId);
        },

        mobileTextCardClass(textId) {
            if (Number(this.activeTextId) === Number(textId) && this.mobilePickerOpen) {
                return 'ring-2 ring-blue-500 border-blue-300';
            }

            if (this.assignments?.[textId]) {
                return 'border-emerald-300 ring-1 ring-emerald-200';
            }

            return 'border-slate-300';
        },

        mobileAssignedHeaderClass(textId) {
            if (this.assignments?.[textId]) {
                return 'border-emerald-300 bg-emerald-50';
            }

            return 'border-slate-300 bg-slate-50';
        },

        mobileOptionButtonClass(optionId) {
            if (this.isOptionAssignedToActiveText(optionId)) {
                return 'border-emerald-400 bg-emerald-100 ring-2 ring-emerald-300';
            }

            if (this.isOptionAssignedToAnotherText(optionId)) {
                return 'border-slate-200 bg-slate-100 opacity-45';
            }

            return 'border-indigo-300 bg-[#b5b8ff] hover:bg-[#a9adff]';
        },
        init() {
            this.draftKey = `telc_attempt_${this.attemptId}_part_${this.partId}_draft`;
            this.options.forEach((option) => {
                this.optionMap[Number(option.id)] = option.label;
                this.optionIds.push(Number(option.id));
            });

            this.textIds.forEach((textId) => {
                if (!Object.prototype.hasOwnProperty.call(this.assignments, textId)) {
                    this.assignments[textId] = null;
                }
            });
            this.restoreDraft();
            this.assignments = this.normalizeAssignments(this.assignments);

            document.getElementById('manualSaveButton')?.addEventListener('click', () => this.save(true));
            document.getElementById('submitAttemptButton')?.addEventListener('click', () => this.submitAttempt(false));
            this.bindTabNavigation();

            this.initDragTargets();
            this.updateTimerLabel();
            this.startTimer();
            this.updateCompletionIndicators();

            // In-exam time-mode toggle (dispatched from the header button)
            window.addEventListener('exam-time-toggle', () => this.toggleRespectTime());
        },

        bindTabNavigation() {
            document.querySelectorAll('[data-part-tab-link]').forEach((link) => {
                link.addEventListener('click', async (event) => {
                    if (this.isSubmitting) {
                        return;
                    }
                    event.preventDefault();
                    clearTimeout(this.autosaveTimeout);
                    await this.save(false);
                    window.location.href = link.href;
                });
            });
        },

        initDragTargets() {
            Sortable.create(document.getElementById('optionPool'), {
                animation: 120,
                ghostClass: 'opacity-70',
                draggable: '.option-item',
                sort: false,
            });

            document.querySelectorAll('.text-drop-zone').forEach((zone) => {
                zone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                });

                zone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    const textId = Number(zone.dataset.textId);
                    if (this.draggingOptionId) {
                        this.assignToText(textId, this.draggingOptionId);
                    }
                });
            });
        },

        dragStart(optionId, event) {
            if (this.isOptionAssigned(optionId)) {
                event.preventDefault();
                return;
            }
            this.draggingOptionId = Number(optionId);
            event.dataTransfer.effectAllowed = 'move';
        },

        handleOptionClick(optionId) {
            optionId = Number(optionId);
            if (this.isOptionAssigned(optionId) && this.selectedOptionId !== optionId) {
                return;
            }

            if (this.selectedTextId) {
                this.assignToText(this.selectedTextId, optionId);
                return;
            }

            this.selectedOptionId = this.selectedOptionId === optionId ? null : optionId;
        },

        handleTextClick(textId) {
            textId = Number(textId);
            if (this.selectedOptionId) {
                this.assignToText(textId, this.selectedOptionId);
                return;
            }

            this.selectedTextId = this.selectedTextId === textId ? null : textId;
        },

        assignToText(textId, optionId) {
            textId = Number(textId);
            optionId = Number(optionId);

            Object.keys(this.assignments).forEach((existingTextId) => {
                if (Number(this.assignments[existingTextId]) === optionId) {
                    this.assignments[existingTextId] = null;
                }
            });

            this.assignments[textId] = optionId;
            this.selectedTextId = null;
            this.selectedOptionId = null;
            this.draggingOptionId = null;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        removeAssignment(textId) {
            this.assignments[Number(textId)] = null;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        resetPart() {
            this.textIds.forEach((id) => {
                this.assignments[id] = null;
            });
            this.selectedTextId = null;
            this.selectedOptionId = null;
            this.statusMessage = 'Teil zuruckgesetzt - Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        queueAutosave() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.save(false), 700);
        },

        async save(manual = false) {
            this.assignments = this.normalizeAssignments(this.assignments);
            const normalizedAssignments = this.assignments;
            const draftPayload = {
                assignments: normalizedAssignments,
            };
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        exam_part_id: this.partId,
                        manual,
                        answer_json: {
                            assignments: normalizedAssignments,
                        },
                    }),
                });

                if (response.status === 419 || response.status === 401) {
                    window.showSessionExpiredBanner?.();
                }
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Save failed');
                }

                this.remainingSeconds = Number(payload.remaining_seconds ?? this.remainingSeconds);
                this.updateTimerLabel();
                this.statusMessage = manual ? 'Gespeichert' : 'Automatisch gespeichert';
                localStorage.removeItem(this.draftKey);
                return true;
            } catch (error) {
                localStorage.setItem(this.draftKey, JSON.stringify(draftPayload));
                this.statusMessage = 'Speichern fehlgeschlagen';
                return false;
            }
        },

        restoreDraft() {
            try {
                const raw = localStorage.getItem(this.draftKey);
                if (!raw) {
                    return;
                }
                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object' || !parsed.assignments) {
                    return;
                }
                this.assignments = this.normalizeAssignments({ ...this.assignments, ...parsed.assignments });
                this.statusMessage = 'Lokaler Entwurf wiederhergestellt';
            } catch (_) {
                // ignore bad local draft
            }
        },

        normalizeAssignments(source) {
            const input = source && typeof source === 'object' ? source : {};
            const normalized = {};
            const validTextIds = new Set(this.textIds.map((id) => Number(id)));
            const validOptionIds = new Set(this.optionIds.map((id) => Number(id)));

            this.textIds.forEach((textId) => {
                normalized[textId] = null;
            });

            Object.entries(input).forEach(([rawTextId, rawOptionId]) => {
                const textId = Number(rawTextId);
                if (!Number.isInteger(textId) || !validTextIds.has(textId)) {
                    return;
                }
                if (rawOptionId === null || rawOptionId === '') {
                    normalized[textId] = null;
                    return;
                }
                const optionId = Number(rawOptionId);
                if (!Number.isInteger(optionId) || !validOptionIds.has(optionId)) {
                    return;
                }
                normalized[textId] = optionId;
            });

            return normalized;
        },

        async submitAttempt(fromTimer = false) {
            if (this.isSubmitting) {
                return;
            }

            if (!fromTimer) {
                const confirmed = await window.showExamSubmitDialog();
                if (!confirmed) {
                    return;
                }
            }

            this.isSubmitting = true;
            clearInterval(this.timerInterval);

            try {
                await this.save(false);
                const response = await fetch(this.submitUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const payload = await response.json();
                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Submit failed');
                }

                this.statusMessage = payload.message;
                window.location.href = payload.redirect_url || '/dashboard';
            } catch (error) {
                this.isSubmitting = false;
                this.statusMessage = 'Abgabe fehlgeschlagen';
                this.startTimer();
            }
        },

        startTimer() {
            clearInterval(this.timerInterval);
            if (!this.respectTime) {
                this.updateTimerLabel();
                return;
            }
            this.timerInterval = setInterval(() => {
                if (this.remainingSeconds <= 0) {
                    clearInterval(this.timerInterval);
                    this.submitAttempt(true);
                    return;
                }
                this.remainingSeconds -= 1;
                this.updateTimerLabel();
            }, 1000);
        },

        updateTimerLabel() {
            const targets = document.querySelectorAll('.remainingTimeLabel');
            if (!this.respectTime) {
                targets.forEach(t => { t.innerText = '∞'; });
                return;
            }
            const safeSeconds = Math.max(0, Math.floor(this.remainingSeconds));
            const minutes = Math.floor(safeSeconds / 60).toString().padStart(2, '0');
            const seconds = (safeSeconds % 60).toString().padStart(2, '0');
            targets.forEach(t => { t.innerText = `${minutes}:${seconds}`; });
        },

        async toggleRespectTime() {
            this.respectTime = !this.respectTime;
            this.startTimer(); // restarts or stops countdown
            if (this.timeModeUrl) {
                const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
                await fetch(this.timeModeUrl, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
                });
            }
            // Update the toggle button visual
            const btn = document.getElementById('timeModeToggleBtn');
            if (btn) btn.classList.toggle('time-mode-off', !this.respectTime);
        },

        optionLabel(optionId) {
            return this.optionMap[Number(optionId)] || '';
        },

        isOptionAssigned(optionId) {
            optionId = Number(optionId);
            return Object.values(this.assignments).some((id) => Number(id) === optionId);
        },

        textCardClass(textId) {
            if (Number(this.selectedTextId) === Number(textId)) {
                return 'ring-2 ring-emerald-500 bg-emerald-50';
            }
            return 'hover:border-emerald-300';
        },

        assignedHeaderClass(textId) {
            if (Number(this.selectedTextId) === Number(textId)) {
                return 'border-emerald-600 bg-lime-400';
            }
            if (this.assignments[textId]) {
                return 'border-indigo-300 bg-[#b5b8ff]';
            }
            return 'border-indigo-300 bg-[#b5b8ff]';
        },

        optionCardClass(optionId) {
            if (Number(this.selectedOptionId) === Number(optionId)) {
                return 'bg-emerald-500 text-white ring-2 ring-emerald-600';
            }
            if (this.isOptionAssigned(optionId)) {
                return 'bg-[#b5b8ff] text-white';
            }
            return 'hover:bg-[#a7abff]';
        },

        answeredCount() {
            return Object.values(this.assignments).filter((id) => id !== null && id !== '').length;
        },

        isPartComplete(partId, requiredCount) {
            if (Number(partId) !== Number(this.partId)) {
                return false;
            }
            return this.answeredCount() >= Number(requiredCount);
        },

        updateCompletionIndicators() {
            document.querySelectorAll('[data-part-complete]').forEach((node) => {
                const partId = Number(node.dataset.partComplete);
                const requiredCount = Number(node.dataset.requiredCount || 0);
                const initiallyComplete = node.dataset.initialComplete === '1';
                const draftCount = this.getDraftAnsweredCount(partId);
                const draftComplete = requiredCount > 0 && draftCount >= requiredCount;
                const complete = partId === Number(this.partId)
                    ? this.isPartComplete(partId, requiredCount)
                    : (initiallyComplete || draftComplete);
                node.style.display = complete ? 'inline-flex' : 'none';
            });
        },

        getDraftAnsweredCount(partId) {
            const key = `telc_attempt_${this.attemptId}_part_${partId}_draft`;
            try {
                const raw = localStorage.getItem(key);
                if (!raw) {
                    return 0;
                }
                const parsed = JSON.parse(raw);
                if (parsed.assignments) {
                    return Object.values(parsed.assignments).filter((v) => v !== null && v !== '').length;
                }
                if (parsed.choices) {
                    return Object.values(parsed.choices).filter((v) => v !== null && v !== '').length;
                }
                if (parsed.situation_assignments) {
                    return Object.values(parsed.situation_assignments).filter((v) => v !== null && v !== '').length;
                }
                if (parsed.gap_choices) {
                    return Object.values(parsed.gap_choices).filter((v) => v !== null && v !== '').length;
                }
                if (parsed.pool_assignments) {
                    return Object.values(parsed.pool_assignments).filter((v) => v !== null && v !== '').length;
                }
                if (parsed.tf_choices) {
                    return Object.values(parsed.tf_choices).filter((v) => v === 'true' || v === 'false').length;
                }
                return 0;
            } catch (_) {
                return 0;
            }
        },
    };
};
