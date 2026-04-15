window.hoerenTrueFalseEngine = function hoerenTrueFalseEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        choices: config.initialChoices || {},
        questionIds: (config.questionIds || []).map((id) => Number(id)),
        audioDurationSeconds: Number(config.audioDurationSeconds || 0),
        remainingSeconds: Number(config.remainingSeconds || 0),
        statusMessage: 'Bereit',
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        draftKey: null,
        audioTimerInterval: null,

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
            this.initAudioAutoplay();

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

        initAudioAutoplay() {
            const audio = document.getElementById('hoerenAudioElement');
            if (!audio) {
                return;
            }

            audio.controls = false;
            audio.loop = false;
            audio.autoplay = true;

            const updateAudioLabel = () => {
                const current = Math.floor(audio.currentTime || 0);
                const duration = Number.isFinite(audio.duration) && audio.duration > 0
                    ? Math.floor(audio.duration)
                    : Math.floor(this.audioDurationSeconds || 0);
                const fmt = (sec) => `${Math.floor(sec / 60)}:${String(sec % 60).padStart(2, '0')}`;
                const label = document.getElementById('hoerenAudioTimeLabel');
                if (label) {
                    label.innerText = `${fmt(current)} / ${duration > 0 ? fmt(duration) : '--:--'}`;
                }
            };

            const tryPlay = () => {
                audio.play().then(() => {
                    window.removeEventListener('click', tryPlay);
                    window.removeEventListener('keydown', tryPlay);
                }).catch(() => {
                    // wait for user gesture fallback
                });
            };

            audio.addEventListener('loadedmetadata', updateAudioLabel);
            audio.addEventListener('timeupdate', updateAudioLabel);
            clearInterval(this.audioTimerInterval);
            this.audioTimerInterval = setInterval(updateAudioLabel, 500);
            tryPlay();
            window.addEventListener('click', tryPlay, { once: true });
            window.addEventListener('keydown', tryPlay, { once: true });
        },

        choose(questionId, value) {
            if (!['true', 'false'].includes(value)) {
                return;
            }
            this.choices[Number(questionId)] = value;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        rowClass(questionId) {
            return this.choices[questionId] ? 'bg-[#b5b8ff]' : '';
        },

        queueAutosave() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.save(false), 700);
        },

        async save(manual = false) {
            this.choices = this.normalizeChoices(this.choices);
            const normalizedChoices = this.choices;
            const draftPayload = {
                tf_choices: normalizedChoices,
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
                            tf_choices: normalizedChoices,
                        },
                    }),
                });

                if (response.status === 419 || response.status === 401) {
                    window.showSessionExpiredBanner?.();
                }
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
                if (!parsed || typeof parsed !== 'object' || !parsed.tf_choices) {
                    return;
                }
                this.choices = this.normalizeChoices({ ...this.choices, ...parsed.tf_choices });
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

            Object.entries(input).forEach(([rawQuestionId, rawValue]) => {
                const questionId = Number(rawQuestionId);
                if (!Number.isInteger(questionId) || !validQuestionIds.has(questionId)) {
                    return;
                }
                if (rawValue === 'true' || rawValue === 'false') {
                    normalized[questionId] = rawValue;
                }
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
            clearInterval(this.audioTimerInterval);

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

        answeredCount() {
            return Object.values(this.choices).filter((v) => v === 'true' || v === 'false').length;
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
