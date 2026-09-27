document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => field.form?.submit());
    });

    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.dataset.originalText = submit.innerHTML;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving…';
            }
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const modalElement = document.getElementById('confirmActionModal');
            const bootstrapModal = window.bootstrap?.Modal;
            if (modalElement && bootstrapModal) {
                event.preventDefault();
                const form = button.form || button.closest('form');
                const title = button.dataset.confirmTitle || 'Are you sure?';
                const message = button.dataset.confirm || 'This action cannot be undone.';
                const titleNode = modalElement.querySelector('[data-confirm-title]');
                const messageNode = modalElement.querySelector('[data-confirm-message]');
                if (titleNode) titleNode.textContent = title;
                if (messageNode) messageNode.textContent = message;
                const confirmButton = modalElement.querySelector('[data-confirm-submit]');
                if (confirmButton) {
                    confirmButton.onclick = () => form?.submit();
                }
                bootstrapModal.getOrCreateInstance(modalElement).show();
                return;
            }
            if (!window.confirm(button.dataset.confirm || 'Are you sure?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('img[data-fallback]').forEach((image) => {
        const applyFallback = () => {
            if (image.dataset.fallbackApplied === '1' || !image.dataset.fallback) {
                return;
            }
            image.dataset.fallbackApplied = '1';
            image.src = image.dataset.fallback;
        };
        image.addEventListener('error', applyFallback);
        if (image.complete && image.naturalWidth === 0) {
            applyFallback();
        }
    });

    document.querySelectorAll('[data-favorite-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
            }
        });
    });

    const applicationForm = document.querySelector('[data-application-form]');
    if (applicationForm) {
        const panels = Array.from(applicationForm.querySelectorAll('[data-application-step]'));
        const indicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
        let currentStep = 0;
        const updateIndicators = () => {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('is-current', index === currentStep);
                indicator.classList.toggle('is-complete', index < currentStep);
            });
        };
        const showStep = (step) => {
            currentStep = Math.max(0, Math.min(step, panels.length - 1));
            panels.forEach((panel, index) => {
                panel.hidden = index !== currentStep;
            });
            updateIndicators();
            const heading = panels[currentStep]?.querySelector('h2');
            if (heading) heading.focus({ preventScroll: true });
            window.scrollTo({ top: applicationForm.offsetTop - 100, behavior: 'smooth' });
        };
        panels.forEach((panel, index) => {
            panel.hidden = index !== 0;
        });
        updateIndicators();
        applicationForm.querySelectorAll('[data-next-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const currentPanel = panels[currentStep];
                const required = Array.from(currentPanel.querySelectorAll('[required]'));
                const valid = required.every((field) => field.checkValidity());
                currentPanel.querySelectorAll('[required]').forEach((field) => field.classList.toggle('is-invalid', !field.checkValidity()));
                if (valid) showStep(currentStep + 1);
                else currentPanel.querySelector(':invalid')?.focus();
            });
        });
        applicationForm.querySelectorAll('[data-previous-step]').forEach((button) => {
            button.addEventListener('click', () => showStep(currentStep - 1));
        });
        applicationForm.addEventListener('submit', () => {
            const submit = applicationForm.querySelector('[data-submit-application]');
            if (submit) {
                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…';
            }
        });
        window.setPaworaApplicationStep = (step) => showStep(step);
    }

    document.querySelectorAll('[data-copy-reference]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copyReference || '';
            try {
                await navigator.clipboard.writeText(value);
                const original = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Copied';
                window.setTimeout(() => { button.innerHTML = original; }, 1600);
            } catch (error) {
                window.prompt('Copy this reference:', value);
            }
        });
    });

    document.querySelectorAll('[data-rating-text]').forEach((input) => {
        input.addEventListener('change', () => {
            const output = document.querySelector('[data-rating-output]');
            if (output) output.textContent = `${input.value} / 5`;
        });
    });

    document.querySelectorAll('[data-payment-method]').forEach((input) => {
        const form = input.form;
        if (!form) {
            return;
        }
        const cardFields = form.querySelector('[data-card-fields]');
        const sync = () => {
            if (!cardFields) {
                return;
            }
            const useCard = form.querySelector('[data-payment-method]:checked')?.value === 'demo_card';
            cardFields.hidden = !useCard;
            cardFields.querySelectorAll('input').forEach((field) => {
                field.disabled = !useCard;
                field.required = useCard;
            });
        };
        form.querySelectorAll('[data-payment-method]').forEach((radio) => radio.addEventListener('change', sync));
        sync();
    });

    document.querySelectorAll('[data-card-number]').forEach((input) => {
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D+/g, '').slice(0, 19);
            input.value = digits.replace(/(.{4})/g, '$1 ').trim();
        });
    });

    document.querySelectorAll('[data-card-expiry]').forEach((input) => {
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D+/g, '').slice(0, 4);
            input.value = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
        });
    });

    document.querySelectorAll('[data-print-page]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
});
