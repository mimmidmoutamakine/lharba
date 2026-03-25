window.situationAdsEngine = function situationAdsEngine(config) {
    return {
        attemptId: config.attemptId,
        saveUrl: config.saveUrl,
        submitUrl: config.submitUrl,
        partId: config.partId,
        ads: config.ads || [],
        situations: config.situations || [],
        assignments: config.initialAssignments || {},
        selectedAdId: null,
        selectedSituationId: null,
        draggingAdId: null,
        remainingSeconds: Number(config.remainingSeconds || 0),
        statusMessage: 'Bereit',
        autosaveTimeout: null,
        timerInterval: null,
        isSubmitting: false,
        draftKey: null,

        adsSheetOpen: false,
        pickerOpen: false,
        activeSituationId: null,
        activeAdPreviewId: null,

        lastAssignedAdId: null,
        lastAssignedSituationId: null,
        justAssignedAt: 0,

        init() {
            this.draftKey = `telc_attempt_${this.attemptId}_part_${this.partId}_draft`;
            this.situations.forEach((s) => {
                if (!Object.prototype.hasOwnProperty.call(this.assignments, s.id)) {
                    this.assignments[s.id] = null;
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
            this.activeSituationId = Number(this.situations?.[0]?.id || 0);
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

        selectAd(adId) {
            if (this.isAdUsed(adId) && !this.isAdSelected(adId)) {
                return;
            }

            if (this.selectedSituationId) {
                this.assignAdToSituation(this.selectedSituationId, adId);
                return;
            }

            this.selectedAdId = this.selectedAdId === adId ? null : adId;
        },

        clearAdAssignment(adId) {
            const assignedSituation = this.situations.find((s) => Number(this.assignments[s.id]) === Number(adId));
            if (!assignedSituation) {
                return;
            }
            this.assignments[assignedSituation.id] = null;
            if (Number(this.selectedAdId) === Number(adId)) {
                this.selectedAdId = null;
            }
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        selectSituation(situationId) {
            if (this.selectedAdId) {
                this.assignAdToSituation(situationId, this.selectedAdId);
                return;
            }

            this.selectedSituationId = this.selectedSituationId === situationId ? null : situationId;
        },

        toggleX(situationId) {
            this.assignments[situationId] = this.assignments[situationId] === 'X' ? null : 'X';
            this.selectedAdId = null;
            this.selectedSituationId = null;
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        assignAdToSituation(situationId, adId) {
            Object.keys(this.assignments).forEach((sid) => {
                if (Number(this.assignments[sid]) === Number(adId)) {
                    this.assignments[sid] = null;
                }
            });

            this.assignments[situationId] = Number(adId);
            this.selectedAdId = null;
            this.selectedSituationId = null;
            this.draggingAdId = null;
            this.statusMessage = 'Autospeichern...';
            this.queueAutosave();
            this.updateCompletionIndicators();
        },

        dragStartAd(adId, event) {
            if (this.isAdUsed(adId)) {
                event.preventDefault();
                return;
            }
            this.draggingAdId = Number(adId);
            event.dataTransfer.effectAllowed = 'move';
        },

        dropOnSituation(situationId) {
            if (this.draggingAdId) {
                this.assignAdToSituation(situationId, this.draggingAdId);
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
                situation_assignments: normalizedAssignments,
            };
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        exam_part_id: this.partId,
                        manual,
                        answer_json: {
                            situation_assignments: normalizedAssignments,
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
                if (!parsed || typeof parsed !== 'object' || !parsed.situation_assignments) {
                    return;
                }
                this.assignments = this.normalizeAssignments({ ...this.assignments, ...parsed.situation_assignments });
                this.statusMessage = 'Lokaler Entwurf wiederhergestellt';
            } catch (_) {
                // ignore bad local draft
            }
        },

        normalizeAssignments(source) {
            const input = source && typeof source === 'object' ? source : {};
            const normalized = {};
            const validSituationIds = new Set((this.situations || []).map((s) => Number(s.id)));
            const validAdIds = new Set((this.ads || []).map((a) => Number(a.id)));

            (this.situations || []).forEach((situation) => {
                normalized[situation.id] = null;
            });

            Object.entries(input).forEach(([rawSituationId, rawValue]) => {
                const situationId = Number(rawSituationId);
                if (!Number.isInteger(situationId) || !validSituationIds.has(situationId)) {
                    return;
                }

                if (rawValue === null || rawValue === '') {
                    normalized[situationId] = null;
                    return;
                }

                if (String(rawValue).toUpperCase() === 'X') {
                    normalized[situationId] = 'X';
                    return;
                }

                const adId = Number(rawValue);
                if (!Number.isInteger(adId) || !validAdIds.has(adId)) {
                    return;
                }

                normalized[situationId] = adId;
            });

            return normalized;
        },

        async submitAttempt(fromTimer = false) {
            if (this.isSubmitting) return;
            if (!fromTimer) {
                const confirmed = await window.showExamSubmitDialog();
                if (!confirmed) return;
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
            if (target) target.innerText = `${minutes}:${seconds}`;
        },

        isAdUsed(adId) {
            return Object.values(this.assignments).some((value) => Number(value) === Number(adId));
        },

        isAdSelected(adId) {
            return Number(this.selectedAdId) === Number(adId);
        },

        adHeaderLabel(adId) {
            const assignedSituation = this.situations.find((s) => Number(this.assignments[s.id]) === Number(adId));
            return assignedSituation ? assignedSituation.text : '';
        },

        adCardClass(adId) {
            if (this.isAdSelected(adId)) return 'ring-2 ring-emerald-600 bg-emerald-50';
            return this.isAdUsed(adId) ? 'opacity-60' : 'hover:border-emerald-400';
        },

        adHeaderClass(adId) {
            if (this.isAdSelected(adId)) {
                return 'bg-emerald-500 text-slate-900';
            }
            return this.isAdUsed(adId) ? 'text-white' : '';
        },

        situationRowClass(situationId) {
            return Number(this.selectedSituationId) === Number(situationId) ? 'bg-emerald-200' : '';
        },

        xButtonClass(situationId) {
            return this.assignments[situationId] === 'X' ? 'border-slate-800 bg-slate-200 text-slate-900' : '';
        },

        xButtonText(situationId) {
            return this.assignments[situationId] === 'X' ? 'X' : '';
        },

        situationTextClass(situationId) {
            if (Number(this.selectedSituationId) === Number(situationId)) {
                return 'bg-emerald-500 text-slate-900';
            }
            const value = this.assignments[situationId];
            if (value === 'X') return 'text-white';
            if (value) return 'text-white';
            return '';
        },

        answeredCount() {
            return Object.values(this.assignments).filter((v) => v !== null && v !== '').length;
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

        lockBody() {
            document.body.classList.add('overflow-hidden');
        },

        unlockBodyIfNoSheet() {
            if (!this.adsSheetOpen && !this.pickerOpen) {
                document.body.classList.remove('overflow-hidden');
            }
        },
        openAdsSheet() {
            this.adsSheetOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeAdsSheet() {
            this.adsSheetOpen = false;
            if (!this.pickerOpen) {
                document.body.classList.remove('overflow-hidden');
            }
        },

        openPickerForSituation(situationId) {
            this.activeSituationId = Number(situationId);
            this.selectedSituationId = Number(situationId);
            this.pickerOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closePicker() {
            this.pickerOpen = false;
            this.selectedSituationId = null;
            if (!this.adsSheetOpen) {
                document.body.classList.remove('overflow-hidden');
            }
        },

        toggleXFromMobile(situationId) {
            this.activeSituationId = Number(situationId);
            this.toggleX(situationId);
            this.goToNextUnansweredFrom(situationId);
        },

        assignAdFromPicker(adId) {
            if (!this.activeSituationId) return;
            this.assignAdToSituation(this.activeSituationId, adId);
            this.closePicker();
            this.goToNextUnansweredFrom(this.activeSituationId);
        },

        assignXFromPicker() {
            if (!this.activeSituationId) return;
            this.assignments[this.activeSituationId] = 'X';
            this.selectedAdId = null;
            this.selectedSituationId = null;
            this.queueAutosave();
            this.updateCompletionIndicators();
            const currentId = this.activeSituationId;
            this.closePicker();
            this.goToNextUnansweredFrom(currentId);
        },

        clearSituationAssignment(situationId) {
            this.assignments[situationId] = null;
            this.selectedAdId = null;
            this.selectedSituationId = null;
            this.queueAutosave();
            this.updateCompletionIndicators();
            this.closePicker();
        },

        mobileAssignedAdLabel(situationId) {
            const value = this.assignments?.[situationId];
            if (!value || value === 'X') return '';
            const ad = this.ads.find((item) => Number(item.id) === Number(value));
            return ad ? ad.label : '';
        },

        navigatorButtonClass(situationId) {
            if (Number(this.activeSituationId) === Number(situationId)) {
                return 'border-blue-500 bg-blue-600 text-white';
            }

            if (this.assignments?.[situationId]) {
                return 'border-emerald-400 bg-emerald-100 text-emerald-700';
            }

            return 'border-slate-300 bg-white text-slate-700';
        },

        scrollToSituation(situationId) {
            this.activeSituationId = Number(situationId);
            const el = document.getElementById(`situation-${situationId}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        goToNextUnansweredFrom(situationId) {
            const ids = (this.situations || []).map((s) => Number(s.id));
            const currentIndex = ids.indexOf(Number(situationId));

            if (currentIndex === -1) return;

            for (let i = currentIndex + 1; i < ids.length; i++) {
                const id = ids[i];
                if (!this.assignments?.[id]) {
                    this.scrollToSituation(id);
                    return;
                }
            }

            for (let i = 0; i < ids.length; i++) {
                const id = ids[i];
                if (!this.assignments?.[id]) {
                    this.scrollToSituation(id);
                    return;
                }
            }
        },

        mobileSituationCardClass(situationId) {
            if (Number(this.activeSituationId) === Number(situationId)) {
                return 'border-blue-300 ring-2 ring-blue-200';
            }

            if (this.assignments?.[situationId]) {
                return 'border-emerald-300 ring-1 ring-emerald-200';
            }

            return 'border-slate-300';
        },

        mobileAnswerSlotClass(situationId) {
            const value = this.assignments?.[situationId];

            if (value === 'X') {
                return 'border-slate-400 bg-slate-100';
            }

            if (value) {
                return 'border-emerald-300 bg-emerald-50';
            }

            return 'border-slate-300 bg-white';
        },

        mobileXButtonClass(situationId) {
            return this.assignments?.[situationId] === 'X'
                ? 'border-slate-700 bg-slate-700 text-white'
                : 'border-slate-300 bg-white text-slate-700';
        },

        pickerAdButtonClass(adId) {
            if (!this.activeSituationId) {
                return 'border-slate-300 bg-white text-slate-700';
            }

            const currentValue = this.assignments?.[this.activeSituationId];

            if (Number(currentValue) === Number(adId)) {
                return 'border-emerald-400 bg-emerald-100 text-emerald-700 ring-2 ring-emerald-300';
            }

            if (this.isAdUsed(adId)) {
                return 'border-slate-200 bg-slate-100 text-slate-400 opacity-50';
            }

            return 'border-indigo-300 bg-[#b5b8ff] text-slate-900';
        },

        pickerXButtonClass() {
            if (!this.activeSituationId) {
                return 'border-slate-300 bg-white text-slate-700';
            }

            return this.assignments?.[this.activeSituationId] === 'X'
                ? 'border-slate-700 bg-slate-700 text-white'
                : 'border-slate-300 bg-white text-slate-700';
        },

        activeSituationNumber() {
            if (!this.activeSituationId) return '';
            const index = (this.situations || []).findIndex((s) => Number(s.id) === Number(this.activeSituationId));
            return index >= 0 ? index + 1 : '';
        },

        adNavigatorClass(adId) {
            if (Number(this.activeAdPreviewId) === Number(adId)) {
                return 'border-blue-500 bg-blue-600 text-white';
            }

            if (this.isAdUsed(adId)) {
                return 'border-emerald-400 bg-emerald-100 text-emerald-700';
            }

            return 'border-slate-300 bg-white text-slate-700';
        },

        scrollToAdCard(adId) {
            this.activeAdPreviewId = Number(adId);
            const el = document.getElementById(`ad-mobile-${adId}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        mobileAdCardClass(adId) {
            const currentValue = this.activeSituationId ? this.assignments?.[this.activeSituationId] : null;

            if (this.wasJustAssigned(adId)) {
                return 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-300 scale-[0.985]';
            }

            if (Number(currentValue) === Number(adId)) {
                return 'border-emerald-400 ring-2 ring-emerald-300 bg-emerald-50';
            }

            if (Number(this.activeAdPreviewId) === Number(adId)) {
                return 'border-blue-300 ring-2 ring-blue-200';
            }

            if (this.isAdUsed(adId)) {
                return 'border-slate-300 bg-slate-50 opacity-60';
            }

            if (this.activeSituationId) {
                return 'border-indigo-300 hover:ring-2 hover:ring-blue-200 active:scale-[0.99]';
            }

            return 'border-slate-300';
        },

        assignAdDirectlyFromAdsSheet(adId) {
            this.activeAdPreviewId = Number(adId);

            if (!this.activeSituationId) {
                return;
            }

            if (this.isAdUsed(adId) && Number(this.assignments?.[this.activeSituationId]) !== Number(adId)) {
                return;
            }

            const currentId = this.activeSituationId;
            this.lastAssignedAdId = Number(adId);
            this.lastAssignedSituationId = Number(currentId);
            this.justAssignedAt = Date.now();

            this.assignAdToSituation(currentId, adId);

            setTimeout(() => {
                this.closeAdsSheet();
                this.goToNextUnansweredFrom(currentId);
            }, 180);
        },

        openAdsForSituation(situationId) {
            this.activeSituationId = Number(situationId);
            this.selectedSituationId = Number(situationId);
            this.openAdsSheet();
        },

        wasJustAssigned(adId) {
            if (!this.lastAssignedAdId) return false;
            return Number(this.lastAssignedAdId) === Number(adId) && (Date.now() - this.justAssignedAt < 900);
        },
        
        clearSituationAssignmentOnly(situationId) {
            this.assignments[situationId] = null;
            this.selectedAdId = null;
            this.selectedSituationId = null;

            if (Number(this.activeSituationId) === Number(situationId)) {
                this.activeSituationId = Number(situationId);
            }

            this.queueAutosave();
            this.updateCompletionIndicators();
        },
    };
};
