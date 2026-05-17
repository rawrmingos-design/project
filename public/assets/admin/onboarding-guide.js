(function () {
    if (window.__adminOnboardingGuideBooted) {
        return;
    }

    window.__adminOnboardingGuideBooted = true;

    function initRoot(root) {
        if (!root || root.dataset.initialized === '1') {
            return;
        }

        root.dataset.initialized = '1';

        var welcome = root.querySelector('[data-onboarding-welcome]');
        var tour = root.querySelector('[data-onboarding-tour]');
        var spotlight = root.querySelector('[data-onboarding-spotlight]');
        var tooltip = root.querySelector('[data-onboarding-tooltip]');
        var title = root.querySelector('[data-onboarding-title]');
        var description = root.querySelector('[data-onboarding-description]');
        var stepLabel = root.querySelector('[data-onboarding-step-label]');
        var startButton = root.querySelector('[data-onboarding-start]');
        var dismissButton = root.querySelector('[data-onboarding-dismiss]');
        var closeButton = root.querySelector('[data-onboarding-close]');
        var prevButton = root.querySelector('[data-onboarding-prev]');
        var nextButton = root.querySelector('[data-onboarding-next]');
        var finishButton = root.querySelector('[data-onboarding-finish]');
        var reopenButtons = root.querySelectorAll('[data-onboarding-reopen]');

        var selectors = {};
        var steps = [];
        var targetsPayload = root.querySelector('[data-onboarding-targets-payload]');
        var stepsPayload = root.querySelector('[data-onboarding-steps-payload]');
        var scope = root.getAttribute('data-onboarding-scope') || window.location.pathname || 'global';
        var dismissStorageKey = 'admin-onboarding:dismissed:' + scope;
        var cookieName = root.getAttribute('data-onboarding-cookie') || ('admin_onboarding_dismissed_' + scope.replace(/[^a-zA-Z0-9_]/g, '_'));

        var state = {
            isWelcomeOpen: false,
            tourStarted: false,
            dismissedForPageLifecycle: false,
            currentStepIndex: 0,
            activeElement: null,
            activeInteractionCleanup: null,
            activeInteractionGroup: null,
            stepToken: 0,
        };

        try {
            selectors = JSON.parse(targetsPayload ? targetsPayload.textContent : '{}') || {};
        } catch (error) {
            selectors = {};
        }

        try {
            steps = JSON.parse(stepsPayload ? stepsPayload.textContent : '[]') || [];
        } catch (error) {
            steps = [];
        }

        function readCookie(name) {
            var cookie = document.cookie
                .split(';')
                .map(function (entry) { return entry.trim(); })
                .find(function (entry) { return entry.indexOf(name + '=') === 0; });

            if (!cookie) {
                return null;
            }

            return cookie.substring((name + '=').length);
        }

        function writeCookie(name, value, days) {
            var expires = '';
            if (typeof days === 'number') {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }

            document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
        }

        function readDismissedState() {
            try {
                var cookieDismissed = readCookie(cookieName) === '1';
                return window.localStorage.getItem(dismissStorageKey) === '1'
                    || window.sessionStorage.getItem(dismissStorageKey) === '1'
                    || cookieDismissed
                    || root.getAttribute('data-onboarding-initial-dismissed') === '1';
            } catch (error) {
                return readCookie(cookieName) === '1'
                    || root.getAttribute('data-onboarding-initial-dismissed') === '1';
            }
        }

        function writeDismissedState(value) {
            try {
                if (value) {
                    window.localStorage.setItem(dismissStorageKey, '1');
                    window.sessionStorage.setItem(dismissStorageKey, '1');
                    writeCookie(cookieName, '1', 365);
                } else {
                    window.localStorage.removeItem(dismissStorageKey);
                    window.sessionStorage.removeItem(dismissStorageKey);
                    writeCookie(cookieName, '0', -1);
                }
            } catch (error) {
                writeCookie(cookieName, value ? '1' : '0', value ? 365 : -1);
            }
        }

        state.dismissedForPageLifecycle = readDismissedState();

        function mountReopenButtons() {
            if (!reopenButtons.length) {
                return;
            }

            var headerActionsContainer = document.querySelector('.fi-page .fi-header-actions-ctn')
                || document.querySelector('.fi-header-actions-ctn');

            reopenButtons.forEach(function (button) {
                if (!button) {
                    return;
                }

                if (headerActionsContainer) {
                    button.classList.add('admin-onboarding-reopen-button--header');

                    if (!headerActionsContainer.contains(button)) {
                        headerActionsContainer.appendChild(button);
                    }

                    return;
                }

                button.classList.remove('admin-onboarding-reopen-button--header');
            });
        }

        function isVisible(element) {
            if (!element) {
                return false;
            }

            var styles = window.getComputedStyle(element);
            var rect = element.getBoundingClientRect();

            return styles.display !== 'none'
                && styles.visibility !== 'hidden'
                && rect.width > 0
                && rect.height > 0;
        }

        function findVisibleElement(selector) {
            if (!selector) {
                return null;
            }

            var elements = document.querySelectorAll(selector);

            for (var i = 0; i < elements.length; i++) {
                if (isVisible(elements[i])) {
                    return elements[i];
                }
            }

            return null;
        }

        function toggleDropdown(element) {
            if (!element) {
                return;
            }

            element.dispatchEvent(new MouseEvent('mousedown', {
                bubbles: true,
                cancelable: true,
                view: window,
            }));
        }

        function cleanupInteraction(force) {
            if (typeof state.activeInteractionCleanup === 'function') {
                state.activeInteractionCleanup(force === true);
            }

            state.activeInteractionCleanup = null;
            if (force) {
                state.activeInteractionGroup = null;
            }
        }

        function getDistanceBetweenRects(rectA, rectB) {
            var ax = rectA.left + (rectA.width / 2);
            var ay = rectA.top + (rectA.height / 2);
            var bx = rectB.left + (rectB.width / 2);
            var by = rectB.top + (rectB.height / 2);

            return Math.sqrt(Math.pow(ax - bx, 2) + Math.pow(ay - by, 2));
        }

        function findNearestVisibleDropdownPanel(trigger) {
            if (!trigger) {
                return null;
            }

            var panels = document.querySelectorAll('.fi-dropdown-panel');
            var triggerRect = trigger.getBoundingClientRect();
            var nearestPanel = null;
            var nearestDistance = Number.POSITIVE_INFINITY;

            for (var i = 0; i < panels.length; i++) {
                var panel = panels[i];

                if (!isVisible(panel)) {
                    continue;
                }

                var distance = getDistanceBetweenRects(triggerRect, panel.getBoundingClientRect());

                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestPanel = panel;
                }
            }

            return nearestPanel;
        }

        function hideWelcome() {
            if (welcome) {
                welcome.hidden = true;
                welcome.style.display = 'none';
            }

            state.isWelcomeOpen = false;
        }

        function showWelcome() {
            if (!welcome || state.dismissedForPageLifecycle) {
                return;
            }

            welcome.hidden = false;
            welcome.removeAttribute('hidden');
            welcome.style.display = 'block';
            state.isWelcomeOpen = true;
        }

        function clearHighlight() {
            if (state.activeElement) {
                state.activeElement.classList.remove('admin-onboarding-target-active');
                state.activeElement = null;
            }

            if (spotlight) {
                spotlight.hidden = true;
                spotlight.style.display = 'none';
            }
        }

        function endTour() {
            cleanupInteraction(true);
            clearHighlight();
            if (tour) {
                tour.hidden = true;
                tour.style.display = 'none';
                tour.style.visibility = 'hidden';
                tour.style.opacity = '0';
            }
            document.body.classList.remove('admin-onboarding-tour-active');
            state.tourStarted = false;
        }

        function dismissForPageLifecycle() {
            state.dismissedForPageLifecycle = true;
            writeDismissedState(true);
            hideWelcome();
            endTour();
        }

        function closeWelcomeForCurrentPage() {
            hideWelcome();
            endTour();
        }

        function reopenGuide() {
            state.dismissedForPageLifecycle = false;
            writeDismissedState(false);
            endTour();
            showWelcome();
        }

        function resolveElement(step) {
            var selector = selectors[step.targetKey];

            if (!selector) {
                return null;
            }

            return findVisibleElement(selector) || document.querySelector(selector);
        }

        function prepareStepElement(step, token, callback) {
            var baseElement = resolveElement(step);
            var interaction = step && step.interaction ? step.interaction : null;

            if (!interaction) {
                cleanupInteraction(true);
                callback(baseElement);
                return;
            }

            var interactionGroup = interaction.group || null;
            var shouldReuseInteraction = interactionGroup !== null && state.activeInteractionGroup === interactionGroup;

            var openElement = interaction.openTargetKey
                ? document.querySelector(selectors[interaction.openTargetKey] || '')
                : null;
            var focusSelector = interaction.focusSelector || null;
            var keepOpen = interaction.keepOpen === true;

            if (!shouldReuseInteraction) {
                cleanupInteraction(true);
            }

            var visibleFocusElement = null;

            var shouldOpenDropdown = false;

            if (openElement && keepOpen) {
                shouldOpenDropdown = !findNearestVisibleDropdownPanel(openElement);
            } else if (openElement && !shouldReuseInteraction) {
                shouldOpenDropdown = true;
            }

            if (openElement && shouldOpenDropdown) {
                toggleDropdown(openElement);
            }

            state.activeInteractionCleanup = function (forceClose) {
                if (keepOpen && !forceClose) {
                    state.activeInteractionGroup = null;
                    return;
                }

                if (openElement && (findNearestVisibleDropdownPanel(openElement) || (focusSelector && findVisibleElement(focusSelector)))) {
                    toggleDropdown(openElement);
                }

                state.activeInteractionGroup = null;
            };
            state.activeInteractionGroup = interactionGroup;

            window.setTimeout(function () {
                if (token !== state.stepToken) {
                    return;
                }

                if (openElement && focusSelector) {
                    visibleFocusElement = findNearestVisibleDropdownPanel(openElement);
                }

                var focusElement = visibleFocusElement || (
                    focusSelector
                        ? (findVisibleElement(focusSelector) || document.querySelector(focusSelector))
                        : null
                );

                callback(focusElement || baseElement || openElement);
            }, interaction.delay || 220);
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function positionTooltip(element, step) {
            if (!tooltip || !element) {
                return;
            }

            var rect = element.getBoundingClientRect();
            var tooltipRect = tooltip.getBoundingClientRect();
            var gap = 18;
            var viewportPadding = 16;
            var placement = (step && step.placement) || 'bottom';
            var top = rect.bottom + gap;
            var left = rect.left;
            var rightSpace = window.innerWidth - rect.right - viewportPadding;
            var leftSpace = rect.left - viewportPadding;

            if (placement === 'side') {
                placement = rightSpace >= tooltipRect.width || rightSpace >= leftSpace ? 'right' : 'left';

                if (placement === 'right' && rightSpace < tooltipRect.width && leftSpace < tooltipRect.width) {
                    placement = rect.top > tooltipRect.height + gap ? 'top' : 'bottom';
                }
            }

            if (placement === 'top') {
                top = rect.top - tooltipRect.height - gap;
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            } else if (placement === 'left') {
                top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.left - tooltipRect.width - gap;
            } else if (placement === 'right') {
                top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
                left = rect.right + gap;
            } else {
                top = rect.bottom + gap;
                left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            }

            if (placement === 'top' && top < viewportPadding) {
                top = rect.bottom + gap;
            }

            if (placement === 'bottom' && top + tooltipRect.height > window.innerHeight - viewportPadding) {
                top = rect.top - tooltipRect.height - gap;
            }

            if (placement === 'left' && left < viewportPadding) {
                left = rect.right + gap;
            }

            if (placement === 'right' && left + tooltipRect.width > window.innerWidth - viewportPadding) {
                left = rect.left - tooltipRect.width - gap;
            }

            top = clamp(top, viewportPadding, window.innerHeight - tooltipRect.height - viewportPadding);
            left = clamp(left, viewportPadding, window.innerWidth - tooltipRect.width - viewportPadding);

            tooltip.style.top = top + 'px';
            tooltip.style.left = left + 'px';
        }

        function positionSpotlight(element) {
            if (!spotlight || !element) {
                return;
            }

            var rect = element.getBoundingClientRect();
            var padding = 10;
            var top = Math.max(8, rect.top - padding);
            var left = Math.max(8, rect.left - padding);
            var width = Math.min(window.innerWidth - left - 8, rect.width + (padding * 2));
            var height = Math.min(window.innerHeight - top - 8, rect.height + (padding * 2));

            spotlight.hidden = false;
            spotlight.removeAttribute('hidden');
            spotlight.style.display = 'block';
            spotlight.style.top = top + 'px';
            spotlight.style.left = left + 'px';
            spotlight.style.width = width + 'px';
            spotlight.style.height = height + 'px';
        }

        function updateButtons() {
            if (!prevButton || !nextButton) {
                return;
            }

            prevButton.disabled = state.currentStepIndex === 0;
            nextButton.textContent = state.currentStepIndex === steps.length - 1 ? 'Selesai' : 'Berikutnya';
        }

        function showStep(index) {
            if (!steps.length) {
                endTour();
                return;
            }

            if (index < 0 || index >= steps.length) {
                endTour();
                return;
            }

            var step = steps[index];
            var token = ++state.stepToken;

            prepareStepElement(step, token, function (element) {
                if (token !== state.stepToken) {
                    return;
                }

                if (!element) {
                    showStep(index + 1);
                    return;
                }

                clearHighlight();
                state.currentStepIndex = index;
                state.activeElement = element;
                element.classList.add('admin-onboarding-target-active');
                element.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

                if (title) {
                    title.textContent = step.title || '';
                }

                if (description) {
                    description.textContent = step.description || '';
                }

                if (stepLabel) {
                    stepLabel.textContent = 'Langkah ' + (index + 1) + ' dari ' + steps.length;
                }

                if (tour) {
                    tour.hidden = false;
                    tour.removeAttribute('hidden');
                    tour.style.display = 'block';
                    tour.style.visibility = 'visible';
                    tour.style.opacity = '1';
                }

                document.body.classList.add('admin-onboarding-tour-active');

                window.requestAnimationFrame(function () {
                    positionSpotlight(element);
                    positionTooltip(element, step);
                });

                updateButtons();
            });
        }

        function startTour() {
            hideWelcome();
            state.tourStarted = true;
            state.currentStepIndex = 0;
            showStep(0);
        }

        if (startButton) {
            startButton.addEventListener('click', startTour);
        }

        if (dismissButton) {
            dismissButton.addEventListener('click', dismissForPageLifecycle);
        }

        if (closeButton) {
            closeButton.addEventListener('click', closeWelcomeForCurrentPage);
        }

        if (prevButton) {
            prevButton.addEventListener('click', function () {
                if (!state.tourStarted) {
                    return;
                }

                showStep(state.currentStepIndex - 1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function () {
                if (!state.tourStarted) {
                    return;
                }

                if (state.currentStepIndex >= steps.length - 1) {
                    endTour();
                    return;
                }

                showStep(state.currentStepIndex + 1);
            });
        }

        if (finishButton) {
            finishButton.addEventListener('click', endTour);
        }

        if (reopenButtons.length) {
            reopenButtons.forEach(function (button) {
                button.addEventListener('click', reopenGuide);
            });
        }

        mountReopenButtons();

        window.addEventListener('resize', function () {
            if (state.activeElement) {
                positionSpotlight(state.activeElement);
                positionTooltip(state.activeElement, steps[state.currentStepIndex]);
            }
        });

        window.addEventListener('scroll', function () {
            if (state.activeElement) {
                positionSpotlight(state.activeElement);
                positionTooltip(state.activeElement, steps[state.currentStepIndex]);
            }
        }, true);

        showWelcome();
    }

    function init() {
        var roots = document.querySelectorAll('[data-onboarding-guide]');

        if (!roots.length) {
            return;
        }

        roots.forEach(initRoot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }

    document.addEventListener('livewire:navigated', init);
})();
