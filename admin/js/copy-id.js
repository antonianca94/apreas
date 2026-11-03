(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.apreas-copy-id');

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const id = button.getAttribute('data-apreas-copy-id');

        if (!id) {
            return;
        }

        copyText(id)
            .then(function () {
                showCopyFeedback(button);
            })
            .catch(function () {
                showCopyError(button);
            });
    });

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).catch(function () {
                return copyTextWithFallback(text);
            });
        }

        return copyTextWithFallback(text);
    }

    function copyTextWithFallback(text) {
        const textarea = document.createElement('textarea');

        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';

        document.body.appendChild(textarea);
        textarea.select();

        let copied = false;

        try {
            copied = document.execCommand('copy');
        } finally {
            document.body.removeChild(textarea);
        }

        if (!copied) {
            return Promise.reject(new Error('Não foi possível copiar o ID.'));
        }

        return Promise.resolve();
    }

    function showCopyFeedback(button) {
        const icon = button.querySelector('.dashicons');

        button.classList.add('is-copied');
        button.setAttribute('title', 'Copiado!');
        button.setAttribute('aria-label', 'ID copiado');

        if (icon) {
            icon.classList.remove('dashicons-clipboard');
            icon.classList.add('dashicons-yes-alt');
        }

        window.setTimeout(function () {
            button.classList.remove('is-copied');
            button.setAttribute('title', 'Copiar ID');
            button.setAttribute('aria-label', 'Copiar ID ' + button.getAttribute('data-apreas-copy-id'));

            if (icon) {
                icon.classList.remove('dashicons-yes-alt');
                icon.classList.add('dashicons-clipboard');
            }
        }, 2000);
    }

    function showCopyError(button) {
        button.classList.add('has-error');
        button.setAttribute('title', 'Não foi possível copiar');

        window.setTimeout(function () {
            button.classList.remove('has-error');
            button.setAttribute('title', 'Copiar ID');
        }, 2000);
    }
})();
