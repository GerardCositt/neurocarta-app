require('./bootstrap');
var flatpickrModule = require('flatpickr');
var flatpickr = flatpickrModule.default || flatpickrModule;
var Spanish = require('flatpickr/dist/l10n/es.js').Spanish;

/**
 * Cerrar listados expandidos (categorías / alérgenos / maridaje) al hacer clic fuera.
 * wire:click en capas transparentes no es fiable; Livewire.find(...).call(...) sí lo es.
 */
(function () {
    function closeExpandedListOnOutsideClick(e) {
        if (!window.Livewire) {
            return;
        }
        var roots = document.querySelectorAll('[data-lw-close-list-root][data-lw-expanded="1"]');
        if (!roots.length) {
            return;
        }
        if (e.target.closest('[data-lw-no-close-list]')) {
            return;
        }
        if (e.target.closest('.fixed.inset-0')) {
            return;
        }
        roots.forEach(function (root) {
            var wireId = root.getAttribute('wire:id');
            var method = root.getAttribute('data-lw-close-method');
            if (!wireId || !method) {
                return;
            }
            try {
                Livewire.find(wireId).call(method);
            } catch (err) {
                /* noop */
            }
        });
    }

    function registerCloseExpandedListListener() {
        document.addEventListener('click', closeExpandedListOnOutsideClick);
    }

    if (window.Livewire) {
        registerCloseExpandedListListener();
    } else {
        document.addEventListener('livewire:load', registerCloseExpandedListListener);
    }
})();

/**
 * Selectores modernos de fecha/hora para formularios Livewire (solo dentro del admin).
 */
(function () {
    var livewireHookRegistered = false;
    var observerRegistered = false;

    function getFlatpickrRoot() {
        return document.querySelector('[data-flatpickr-root]') || document.querySelector('main.admin-main') || document.body;
    }

    function syncLivewireValue(input) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function resolveModelInput(input) {
        var target = input.getAttribute('data-flatpickr-target');
        if (!target) {
            return input;
        }

        return document.querySelector('input[data-flatpickr-model="' + target + '"]');
    }

    function destroyFlatpickrIfNeeded(input) {
        if (input._flatpickr) {
            input._flatpickr.destroy();
        }
    }

    function initFlatpickrInput(input) {
        if (!input || input.dataset.flatpickrReady === '1') {
            return;
        }

        destroyFlatpickrIfNeeded(input);
        var modelInput = resolveModelInput(input);
        var initialValue = modelInput ? modelInput.value : input.value;
        var isDateTime = input.getAttribute('data-flatpickr-mode') === 'datetime';
        var fieldWrapper = input.closest('[data-flatpickr-field]');

        flatpickr(input, {
            locale: Spanish,
            enableTime: isDateTime,
            time_24hr: true,
            allowInput: true,
            clickOpens: true,
            dateFormat: isDateTime ? 'd/m/Y H:i' : 'd/m/Y',
            disableMobile: true,
            static: !!fieldWrapper,
            appendTo: fieldWrapper || undefined,
            position: 'above left',
            hourIncrement: 1,
            minuteIncrement: 1,
            defaultDate: initialValue ? flatpickr.parseDate(initialValue, isDateTime ? 'Y-m-d H:i' : 'Y-m-d') : null,
            prevArrow: '<span aria-hidden="true">‹</span>',
            nextArrow: '<span aria-hidden="true">›</span>',
            onReady: function (_, __, instance) {
                input.placeholder = input.getAttribute('data-flatpickr-placeholder') || '';
                if (instance.selectedDates && instance.selectedDates[0]) {
                    input.value = flatpickr.formatDate(instance.selectedDates[0], isDateTime ? 'd/m/Y H:i' : 'd/m/Y');
                }
                input.dataset.flatpickrReady = '1';

                if (isDateTime && instance.hourElement && instance.minuteElement) {
                    var lastWheelAt = 0;
                    var wheelCooldownMs = 320;
                    function onTimeWheel(e) {
                        if (e.ctrlKey) {
                            return;
                        }
                        e.preventDefault();
                        e.stopPropagation();
                        var now = Date.now();
                        if (now - lastWheelAt < wheelCooldownMs) {
                            return;
                        }
                        var dy = e.deltaY;
                        if (!dy) {
                            return;
                        }
                        lastWheelAt = now;
                        var dir = dy < 0 ? 1 : -1;
                        var ev = new CustomEvent('increment', { bubbles: true, cancelable: true });
                        ev.delta = dir;
                        e.currentTarget.dispatchEvent(ev);
                    }
                    instance.hourElement.addEventListener('wheel', onTimeWheel, { passive: false });
                    instance.minuteElement.addEventListener('wheel', onTimeWheel, { passive: false });
                }
            },
            onClose: function (selectedDates, dateStr) {
                var storageValue = selectedDates && selectedDates[0]
                    ? flatpickr.formatDate(selectedDates[0], isDateTime ? 'Y-m-d H:i' : 'Y-m-d')
                    : '';
                if (modelInput) {
                    modelInput.value = storageValue;
                    syncLivewireValue(modelInput);
                }
                input.value = dateStr || '';
            },
            onChange: function (selectedDates, dateStr) {
                var storageValue = selectedDates && selectedDates[0]
                    ? flatpickr.formatDate(selectedDates[0], isDateTime ? 'Y-m-d H:i' : 'Y-m-d')
                    : '';
                if (modelInput) {
                    modelInput.value = storageValue;
                    syncLivewireValue(modelInput);
                }
                input.value = dateStr || '';
            },
        });
    }

    function initFlatpickrInputs(root) {
        var scope = root || document;
        var inputs = scope.querySelectorAll('input[data-flatpickr]');
        if (!inputs.length) {
            return;
        }
        inputs.forEach(initFlatpickrInput);
    }

    function registerLivewireHook() {
        if (livewireHookRegistered || !window.Livewire || !window.Livewire.hook) {
            return;
        }

        livewireHookRegistered = true;
        window.Livewire.hook('message.processed', function (_, component) {
            var root = component && component.el ? component.el : getFlatpickrRoot();
            initFlatpickrInputs(root);
            getFlatpickrRoot().querySelectorAll('input[data-flatpickr]').forEach(function (input) {
                var modelInput = resolveModelInput(input);
                if (!modelInput || !input._flatpickr) {
                    return;
                }
                if ((modelInput.value || '') !== (input.value || '')) {
                    var isDateTime = input.getAttribute('data-flatpickr-mode') === 'datetime';
                    input._flatpickr.setDate(
                        modelInput.value || '',
                        false,
                        isDateTime ? 'Y-m-d H:i' : 'Y-m-d'
                    );
                    if (!modelInput.value) {
                        input.value = '';
                    }
                }
            });
        });
    }

    function registerObserver() {
        if (observerRegistered || !window.MutationObserver) {
            return;
        }

        observerRegistered = true;
        var observeRoot = getFlatpickrRoot();
        var observer = new MutationObserver(function (mutations) {
            var shouldInit = mutations.some(function (mutation) {
                return Array.prototype.some.call(mutation.addedNodes || [], function (node) {
                    return node.nodeType === 1 && (
                        (node.matches && node.matches('input[data-flatpickr]')) ||
                        (node.querySelector && node.querySelector('input[data-flatpickr]'))
                    );
                });
            });

            if (shouldInit) {
                initFlatpickrInputs(observeRoot);
            }
        });

        observer.observe(observeRoot, { childList: true, subtree: true });
    }

    function registerFlatpickr() {
        initFlatpickrInputs(getFlatpickrRoot());
        registerLivewireHook();
        registerObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerFlatpickr);
    } else {
        registerFlatpickr();
    }

    document.addEventListener('livewire:load', registerFlatpickr);
    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.matches('input[data-flatpickr]') && getFlatpickrRoot().contains(e.target)) {
            registerFlatpickr();
        }
    });
})();
