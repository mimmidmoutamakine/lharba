window.readingMcqEngine = function readingMcqEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        choices: config.initialChoices || {},
        questionIds: (config.questionIds || []).map((id) => Number(id)),
        optionMap: config.optionMap || {},
        remainingSeconds: Number(config.remainingSeconds || 0),
        statusMessage: 'Bereit',
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        draftKey: null,
        passageSheetOpen: false,
        activeQuestionId: null,

        openPassageSheet() {
            this.passageSheetOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closePassageSheet() {
            this.passageSheetOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        mobileQuestionCardClass(questionId) {
            if (this.choices?.[questionId]) {
                return 'border-emerald-300 ring-1 ring-emerald-200';
            }
            return 'border-slate-300';
        },

        mobileOptionClass(questionId, optionId) {
            const current = Number(this.choices?.[questionId] || 0);
            if (current === Number(optionId)) {
                return 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-300';
            }
            return 'border-slate-300 bg-white';
        },
        init() {
            this.draftKey = `telc_attempt_${this.attemptId}_part_${this.partId}_draft`;
            this.questionIds.forEach((id) => {
                if (!Object.prototype.hasOwnProperty.call(this.choices, id)) {
                    this.choices[id] = null;
                }
            });
            this.restoreDraft();
            this.choices = this.normalizeChoices(this.choices);

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

        choose(questionId, optionId) {
            questionId = Number(questionId);
            optionId = Number(optionId);
            const allowed = (this.optionMap[questionId] || []).map((id) => Number(id));
            if (!allowed.includes(optionId)) {
                return;
            }

            this.choices[questionId] = optionId;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        queueAutosave() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.save(false), 700);
        },

        async save(manual = false) {
            this.choices = this.normalizeChoices(this.choices);
            const normalizedChoices = this.choices;
            const draftPayload = {
                choices: normalizedChoices,
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
                            choices: normalizedChoices,
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
                if (!parsed || typeof parsed !== 'object' || !parsed.choices) {
                    return;
                }
                this.choices = this.normalizeChoices({ ...this.choices, ...parsed.choices });
                this.statusMessage = 'Lokaler Entwurf wiederhergestellt';
            } catch (_) {
                // ignore bad local draft
            }
        },

        normalizeChoices(source) {
            const input = source && typeof source === 'object' ? source : {};
            const normalized = {};
            const validQuestionIds = new Set(this.questionIds.map((id) => Number(id)));

            this.questionIds.forEach((questionId) => {
                normalized[questionId] = null;
            });

            Object.entries(input).forEach(([rawQuestionId, rawOptionId]) => {
                const questionId = Number(rawQuestionId);
                if (!Number.isInteger(questionId) || !validQuestionIds.has(questionId)) {
                    return;
                }

                if (rawOptionId === null || rawOptionId === '') {
                    normalized[questionId] = null;
                    return;
                }

                const optionId = Number(rawOptionId);
                const allowed = (this.optionMap[questionId] || []).map((id) => Number(id));
                if (!Number.isInteger(optionId) || !allowed.includes(optionId)) {
                    return;
                }

                normalized[questionId] = optionId;
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
            const label = `${minutes}:${seconds}`;
            const target = document.getElementById('remainingTimeLabel');
            if (target) {
                target.innerText = label;
            }
        },

        answeredCount() {
            return Object.values(this.choices).filter((id) => id !== null && id !== '').length;
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

        navigatorButtonClass(questionId) {
            if (Number(this.activeQuestionId) === Number(questionId)) {
                return 'border-blue-500 bg-blue-600 text-white';
            }

            if (this.choices?.[questionId]) {
                return 'border-emerald-400 bg-emerald-100 text-emerald-700';
            }

            return 'border-slate-300 bg-white text-slate-700';
        },

        scrollToQuestion(questionId) {
            this.activeQuestionId = questionId;

            const el = document.getElementById(`question-${questionId}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        chooseAndAdvance(questionId, optionId) {
            this.activeQuestionId = questionId;
            this.choose(questionId, optionId);

            setTimeout(() => {
                this.goToNextUnansweredFrom(questionId);
            }, 120);
        },

        goToNextUnansweredFrom(questionId) {
            const ids = this.questionIds.map(Number);
            const currentIndex = ids.indexOf(Number(questionId));

            if (currentIndex === -1) return;

            for (let i = currentIndex + 1; i < ids.length; i++) {
                const id = ids[i];
                if (!this.choices?.[id]) {
                    this.scrollToQuestion(id);
                    return;
                }
            }

            for (let i = 0; i < ids.length; i++) {
                const id = ids[i];
                if (!this.choices?.[id]) {
                    this.scrollToQuestion(id);
                    return;
                }
            }
        },

        mobileQuestionCardClass(questionId) {
            if (Number(this.activeQuestionId) === Number(questionId)) {
                return 'border-blue-300 ring-2 ring-blue-200';
            }

            if (this.choices?.[questionId]) {
                return 'border-emerald-300 ring-1 ring-emerald-200';
            }

            return 'border-slate-300';
        },

        mobileOptionClass(questionId, optionId) {
            const current = Number(this.choices?.[questionId] || 0);

            if (current === Number(optionId)) {
                return 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-300';
            }

            return 'border-slate-300 bg-white';
        },
    };
};
