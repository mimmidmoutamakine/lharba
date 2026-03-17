window.writingEngine = function writingEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        remainingSeconds: Number(config.remainingSeconds || 0),
        isB2: Boolean(config.isB2),
        tasks: config.tasks || [],
        selectedTaskKey: config.initialTaskKey || '',
        text: String((config.initialResponse || {}).text || ''),
        statusMessage: 'Bereit',
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        draftKey: null,
        specialChars: ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü', 'ẞ'],

        init() {
            this.draftKey = `telc_attempt_${this.attemptId}_part_${this.partId}_draft`;

            if (!this.selectedTaskKey && this.tasks.length > 0) {
                this.selectedTaskKey = this.isB2 ? '' : String(this.tasks[0].key || 'A');
            }

            this.restoreDraft();

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

        selectTask(taskKey) {
            this.selectedTaskKey = String(taskKey || '');
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        selectedTask() {
            return this.tasks.find((task) => String(task.key) === String(this.selectedTaskKey)) || null;
        },

        clearTaskChoice() {
            if (!this.isB2) {
                return;
            }
            this.selectedTaskKey = '';
            this.text = '';
            this.statusMessage = 'Aufgabe zuruckgesetzt';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        onInput() {
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        insertChar(char) {
            const editor = document.getElementById('writingEditor');
            if (!editor) {
                this.text += char;
                this.onInput();
                return;
            }

            const start = editor.selectionStart ?? this.text.length;
            const end = editor.selectionEnd ?? this.text.length;
            const before = this.text.slice(0, start);
            const after = this.text.slice(end);
            this.text = `${before}${char}${after}`;

            this.$nextTick(() => {
                editor.focus();
                const cursor = start + char.length;
                editor.setSelectionRange(cursor, cursor);
            });

            this.onInput();
        },

        queueAutosave() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.save(false), 800);
        },

        buildPayload() {
            return {
                writing_response: {
                    selected_task_key: this.isB2 ? (this.selectedTaskKey || null) : (this.selectedTaskKey || String((this.tasks[0] || {}).key || 'A')),
                    text: this.text || '',
                },
            };
        },

        async save(manual = false) {
            const draftPayload = this.buildPayload();
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
                        answer_json: draftPayload,
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
                const response = parsed?.writing_response;
                if (!response || typeof response !== 'object') {
                    return;
                }
                if (typeof response.text === 'string') {
                    this.text = response.text;
                }
                if (typeof response.selected_task_key === 'string' && response.selected_task_key !== '') {
                    this.selectedTaskKey = response.selected_task_key;
                }
                this.statusMessage = 'Lokaler Entwurf wiederhergestellt';
            } catch (_) {
                // ignore malformed draft
            }
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
                        Accept: 'application/json',
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
            const hasText = this.text.trim().length > 0;
            const hasTask = !this.isB2 || (this.selectedTaskKey || '').trim() !== '';
            return hasText && hasTask ? 1 : 0;
        },

        updateCompletionIndicators() {
            document.querySelectorAll('[data-part-complete]').forEach((node) => {
                const partId = Number(node.dataset.partComplete);
                const requiredCount = Number(node.dataset.requiredCount || 0);
                const initiallyComplete = node.dataset.initialComplete === '1';
                const complete = partId === Number(this.partId)
                    ? this.answeredCount() >= requiredCount
                    : initiallyComplete;
                node.style.display = complete ? 'inline-flex' : 'none';
            });
        },
    };
};
