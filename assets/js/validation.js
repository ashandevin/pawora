document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            button.innerHTML = `<i class="bi ${isPassword ? 'bi-eye-slash' : 'bi-eye'}" aria-hidden="true"></i>`;
        });
    });

    const formatBytes = (bytes) => {
        if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1).replace(/\.0$/, '')} MB`;
        if (bytes >= 1024) return `${(bytes / 1024).toFixed(0)} KB`;
        return `${bytes} bytes`;
    };

    document.querySelectorAll('input[type="file"][data-upload-input]').forEach((input) => {
        const form = input.closest('form');
        const feedback = form ? form.querySelector(`[data-upload-feedback="${input.name}"]`) : null;
        const preview = form ? form.querySelector(`[data-upload-preview="${input.name}"]`) : null;
        const limit = Number(input.dataset.maxSize || 0);
        const types = (input.dataset.allowedTypes || '').split(',').filter(Boolean);

        const reset = () => {
            if (preview) preview.textContent = '';
        };

        const fail = (message) => {
            if (feedback) {
                feedback.textContent = message;
                feedback.classList.remove('d-none');
            }
            input.setCustomValidity(message);
            input.classList.add('is-invalid');
        };

        const pass = (message) => {
            if (feedback) {
                feedback.textContent = message || '';
                feedback.classList.toggle('d-none', !message);
            }
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        };

        input.addEventListener('change', () => {
            reset();
            pass('');
            const file = input.files && input.files[0];
            if (!file) return;
            if (limit && file.size > limit) {
                fail(`That image is ${formatBytes(file.size)}. The maximum is ${formatBytes(limit)}.`);
                return;
            }
            if (file.size < 1) {
                fail('That file is empty. Please choose another image.');
                return;
            }
            if (types.length && types.indexOf(file.type) === -1) {
                fail('Only JPG, PNG or WEBP images are accepted. Save HEIC photos as JPG first.');
                return;
            }
            if (preview) preview.textContent = `${file.name} - ${formatBytes(file.size)}`;
        });

        if (form) {
            form.addEventListener('submit', (event) => {
                if (!input.files || !input.files[0]) return;
                if (!input.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        }
    });
});
