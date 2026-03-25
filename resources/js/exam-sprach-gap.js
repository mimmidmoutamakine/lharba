window.sprachGapEngine = function sprachGapEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        questions: config.questions || [],
        choices: config.initialChoices || {},
        remainingSeconds: Number(config.remainingSeconds || 0),
        statusMessage: 'Bereit',
        selectedQuestionId: null,
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        optionLabelMap: {},
        draftKey: null,

        gapPickerOpen: false,
        activeQuestionId: null,
        lastPickedQuestionId: null,
        lastPickedAt: 0,

        init() {
            this.draftKey = `telc_attempt_${this.attemptId}_part_${this.partId}_draft`;
            this.questions.forEach((question) => {
                if (!Object.prototype.hasOwnProperty.call(this.choices, question.id)) {
                    this.choices[question.id] = null;
                }
                (question.options || []).forEach((option) => {
                    this.optionLabelMap[Number(option.id)] = option.option_text;
                });
            });
            this.restoreDraft();
            this.choices = this.normalizeChoices(this.choices);

            document.getElementById('manualSaveButton')?.addEventListener('click', () => this.save(true));
            document.getElementById('submitAttemptButton')?.addEventListener('click', () => this.submitAttempt(false));
            this.bindTabNavigation();

            this.updateTimerLabel();
            this.startTimer();
            this.updateCompletionIndicators();
            this.activeQuestionId = Number(this.questions?.[0]?.id || 0);
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

        selectGap(questionId) {
            this.selectedQuestionId = this.selectedQuestionId === Number(questionId) ? null : Number(questionId);
        },

        choose(questionId, optionId) {
            questionId = Number(questionId);
            optionId = Number(optionId);
            const question = this.questions.find((q) => Number(q.id) === questionId);
            const allowed = (question?.options || []).map((option) => Number(option.id));
            if (!allowed.includes(optionId)) {
                return;
            }

            this.choices[questionId] = optionId;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        selectedLabel(questionId) {
            const selectedId = Number(this.choices[questionId] || 0);
            if (!selectedId) {
                return '';
            }
            return this.optionLabelMap[selectedId] || '';
        },

        gapChipClass(questionId) {
            if (Number(this.selectedQuestionId) === Number(questionId)) {
                return 'border-emerald-500 bg-emerald-500';
            }
            if (this.choices[questionId]) {
                return 'border-[#3d5f8a] bg-[#3d5f8a]';
            }
            return 'border-[#3d5f8a] bg-[#3d5f8a]';
        },

        questionCardClass(questionId) {
            if (Number(this.selectedQuestionId) === Number(questionId)) {
                return 'ring-2 ring-emerald-500 bg-emerald-50';
            }
            return '';
        },

        queueAutosave() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.save(false), 700);
        },

        async save(manual = false) {
            this.choices = this.normalizeChoices(this.choices);
            const normalizedChoices = this.choices;
            const draftPayload = {
                gap_choices: normalizedChoices,
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
                            gap_choices: normalizedChoices,
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
                if (!parsed || typeof parsed !== 'object' || !parsed.gap_choices) {
                    return;
                }
                this.choices = this.normalizeChoices({ ...this.choices, ...parsed.gap_choices });
                this.statusMessage = 'Lokaler Entwurf wiederhergestellt';
            } catch (_) {
                // ignore bad local draft
            }
        },

        normalizeChoices(source) {
            const input = source && typeof source === 'object' ? source : {};
            const normalized = {};
            const validQuestionIds = new Set((this.questions || []).map((q) => Number(q.id)));
            const optionMap = {};

            (this.questions || []).forEach((question) => {
                const qid = Number(question.id);
                normalized[qid] = null;
                optionMap[qid] = new Set((question.options || []).map((option) => Number(option.id)));
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
                if (!Number.isInteger(optionId) || !optionMap[questionId]?.has(optionId)) {
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
                    throw new Error(payload.message || 'submit failed');
                }
                this.statusMessage = payload.message;
                window.location.href = payload.redirect_url || '/dashboard';
            } catch (error) {
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

        answeredCount() {
            return Object.values(this.choices).filter((v) => v !== null && v !== '').length;
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

        openGapPicker(questionId) {
            this.activeQuestionId = Number(questionId);
            this.selectedQuestionId = Number(questionId);
            this.gapPickerOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeGapPicker() {
            this.gapPickerOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        activeQuestion() {
            return this.questions.find((q) => Number(q.id) === Number(this.activeQuestionId)) || null;
        },

        activeGapNumber() {
            return this.activeQuestion()?.gap_number || '';
        },

        activeQuestionOptions() {
            return this.activeQuestion()?.options || [];
        },

        chooseFromPicker(optionId) {
            if (!this.activeQuestionId) return;

            const currentQuestionId = Number(this.activeQuestionId);
            this.choose(currentQuestionId, optionId);
            this.lastPickedQuestionId = currentQuestionId;
            this.lastPickedAt = Date.now();

            setTimeout(() => {
                this.closeGapPicker();
                this.goToNextUnansweredFrom(currentQuestionId);
            }, 160);
        },

        clearActiveGapChoice() {
            if (!this.activeQuestionId) return;

            this.choices[Number(this.activeQuestionId)] = null;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
            this.closeGapPicker();
        },

        wasJustPicked(questionId) {
            return Number(this.lastPickedQuestionId) === Number(questionId) && (Date.now() - this.lastPickedAt < 900);
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

        scrollToGap(questionId) {
            this.activeQuestionId = Number(questionId);
            const el = document.getElementById(`gap-${questionId}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        goToNextUnanswered() {
            const firstUnanswered = (this.questions || []).find((q) => !this.choices?.[q.id]);
            if (firstUnanswered) {
                this.scrollToGap(firstUnanswered.id);
                return;
            }

            if (this.questions?.length) {
                this.scrollToGap(this.questions[0].id);
            }
        },

        goToNextUnansweredFrom(questionId) {
            const ids = (this.questions || []).map((q) => Number(q.id));
            const currentIndex = ids.indexOf(Number(questionId));

            if (currentIndex === -1) return;

            for (let i = currentIndex + 1; i < ids.length; i++) {
                const id = ids[i];
                if (!this.choices?.[id]) {
                    this.scrollToGap(id);
                    return;
                }
            }

            for (let i = 0; i < ids.length; i++) {
                const id = ids[i];
                if (!this.choices?.[id]) {
                    this.scrollToGap(id);
                    return;
                }
            }
        },

        mobileGapChipClass(questionId) {
            if (this.wasJustPicked(questionId)) {
                return 'border-emerald-500 bg-emerald-500 ring-4 ring-emerald-300';
            }

            if (Number(this.activeQuestionId) === Number(questionId)) {
                return 'border-blue-500 bg-blue-600';
            }

            if (this.choices?.[questionId]) {
                return 'border-[#3d5f8a] bg-[#3d5f8a]';
            }

            return 'border-[#3d5f8a] bg-[#3d5f8a]';
        },

        mobileOptionButtonClass(optionId) {
            const current = Number(this.choices?.[this.activeQuestionId] || 0);

            if (current === Number(optionId)) {
                return 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-300';
            }

            return 'border-slate-300 bg-white';
        },
    };
};
