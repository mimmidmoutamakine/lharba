window.sprachPoolEngine = function sprachPoolEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        gapIds: (config.gapIds || []).map((id) => Number(id)),
        options: config.options || [],
        optionMap: {},
        assignments: config.initialAssignments || {},
        selectedGapId: null,
        selectedOptionId: null,
        draggingOptionId: null,
        remainingSeconds: Number(config.remainingSeconds || 0),
        statusMessage: 'Bereit',
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        draftKey: null,

        init() {
            this.draftKey = `telc_attempt_${this.attemptId}_part_${this.partId}_draft`;
            this.options.forEach((option) => {
                this.optionMap[Number(option.id)] = option.label;
            });

            this.gapIds.forEach((gapId) => {
                if (!Object.prototype.hasOwnProperty.call(this.assignments, gapId)) {
                    this.assignments[gapId] = null;
                }
            });
            this.restoreDraft();
            this.assignments = this.normalizeAssignments(this.assignments);

            document.getElementById('manualSaveButton')?.addEventListener('click', () => this.save(true));
            document.getElementById('submitAttemptButton')?.addEventListener('click', () => this.submitAttempt(false));
            this.bindTabNavigation();

            this.updateTimerLabel();
            this.startTimer();
            this.updateCompletionIndicators();
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

        handleOptionClick(optionId) {
            optionId = Number(optionId);
            if (this.isOptionAssigned(optionId) && this.selectedOptionId !== optionId) {
                return;
            }

            if (this.selectedGapId) {
                this.assignToGap(this.selectedGapId, optionId);
                return;
            }

            this.selectedOptionId = this.selectedOptionId === optionId ? null : optionId;
        },

        handleGapClick(gapId) {
            gapId = Number(gapId);
            if (this.selectedOptionId) {
                this.assignToGap(gapId, this.selectedOptionId);
                return;
            }

            this.selectedGapId = this.selectedGapId === gapId ? null : gapId;
        },

        assignToGap(gapId, optionId) {
            gapId = Number(gapId);
            optionId = Number(optionId);

            Object.keys(this.assignments).forEach((existingGapId) => {
                if (Number(this.assignments[existingGapId]) === optionId) {
                    this.assignments[existingGapId] = null;
                }
            });

            this.assignments[gapId] = optionId;
            this.selectedGapId = null;
            this.selectedOptionId = null;
            this.draggingOptionId = null;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        removeAssignment(gapId) {
            this.assignments[Number(gapId)] = null;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        dragStart(optionId, event) {
            if (this.isOptionAssigned(optionId)) {
                event.preventDefault();
                return;
            }
            this.draggingOptionId = Number(optionId);
            event.dataTransfer.effectAllowed = 'move';
        },

        dropOnGap(gapId) {
            if (this.draggingOptionId) {
                this.assignToGap(gapId, this.draggingOptionId);
            }
        },

        queueAutosave() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.save(false), 700);
        },

        async save(manual = false) {
            this.assignments = this.normalizeAssignments(this.assignments);
            const normalizedAssignments = this.assignments;
            const draftPayload = {
                pool_assignments: normalizedAssignments,
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
                            pool_assignments: normalizedAssignments,
                        },
                    }),
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'save failed');
                }

                this.remainingSeconds = Number(payload.remaining_seconds ?? this.remainingSeconds);
                this.updateTimerLabel();
                this.statusMessage = manual ? 'Gespeichert' : 'Automatisch gespeichert';
                localStorage.removeItem(this.draftKey);
                return true;
            } catch (_) {
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
                if (!parsed || typeof parsed !== 'object' || !parsed.pool_assignments) {
                    return;
                }
                this.assignments = this.normalizeAssignments({ ...this.assignments, ...parsed.pool_assignments });
                this.statusMessage = 'Lokaler Entwurf wiederhergestellt';
            } catch (_) {
                // ignore bad local draft
            }
        },

        normalizeAssignments(source) {
            const input = source && typeof source === 'object' ? source : {};
            const normalized = {};
            const validGapIds = new Set(this.gapIds.map((id) => Number(id)));
            const validOptionIds = new Set((this.options || []).map((option) => Number(option.id)));

            this.gapIds.forEach((gapId) => {
                normalized[gapId] = null;
            });

            Object.entries(input).forEach(([rawGapId, rawOptionId]) => {
                const gapId = Number(rawGapId);
                if (!Number.isInteger(gapId) || !validGapIds.has(gapId)) {
                    return;
                }
                if (rawOptionId === null || rawOptionId === '') {
                    normalized[gapId] = null;
                    return;
                }
                const optionId = Number(rawOptionId);
                if (!Number.isInteger(optionId) || !validOptionIds.has(optionId)) {
                    return;
                }
                normalized[gapId] = optionId;
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const payload = await response.json();
                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'submit failed');
                }
                this.statusMessage = payload.message;
                window.location.href = payload.redirect_url || '/dashboard';
            } catch (_) {
                this.statusMessage = 'Abgabe fehlgeschlagen';
                this.isSubmitting = false;
                this.startTimer();
            }
        },

        startTimer() {
            clearInterval(this.timerInterval);
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
            const safeSeconds = Math.max(0, Math.floor(this.remainingSeconds));
            const minutes = Math.floor(safeSeconds / 60).toString().padStart(2, '0');
            const seconds = (safeSeconds % 60).toString().padStart(2, '0');
            const target = document.getElementById('remainingTimeLabel');
            if (target) {
                target.innerText = `${minutes}:${seconds}`;
            }
        },

        optionLabel(optionId) {
            return this.optionMap[Number(optionId)] || '';
        },

        isOptionAssigned(optionId) {
            optionId = Number(optionId);
            return Object.values(this.assignments).some((id) => Number(id) === optionId);
        },

        gapChipClass(gapId) {
            if (Number(this.selectedGapId) === Number(gapId)) {
                return 'border-emerald-600 bg-lime-400';
            }
            return 'border-indigo-300 bg-[#b5b8ff]';
        },

        optionCardClass(optionId) {
            if (Number(this.selectedOptionId) === Number(optionId)) {
                return 'bg-emerald-500 text-slate-900 ring-2 ring-emerald-600';
            }
            if (this.isOptionAssigned(optionId)) {
                return 'bg-[#b5b8ff] text-white';
            }
            return 'hover:bg-[#a7abff]';
        },

        answeredCount() {
            return Object.values(this.assignments).filter((id) => id !== null && id !== '').length;
        },

        updateCompletionIndicators() {
            document.querySelectorAll('[data-part-complete]').forEach((node) => {
                const partId = Number(node.dataset.partComplete);
                const requiredCount = Number(node.dataset.requiredCount || 0);
                const initiallyComplete = node.dataset.initialComplete === '1';
                const draftCount = this.getDraftAnsweredCount(partId);
                const draftComplete = requiredCount > 0 && draftCount >= requiredCount;
                const complete = partId === Number(this.partId)
                    ? this.answeredCount() >= requiredCount
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
