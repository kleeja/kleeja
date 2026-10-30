/*
 * Bootstrap style: upload form, copy buttons and small page helpers.
 * Loaded at the end of the page, after bootstrap.bundle.min.js and the STYLE_LANG strings of footer.html
 */

/**
 * Load a new captcha image, called from the templates
 * @param {string} captcha_file
 * @param {string} input_id
 */
function update_kleeja_captcha(captcha_file, input_id) {
    document.getElementById(input_id).value = '';
    document.getElementById('kleeja_img_captcha').src = captcha_file + '&' + Math.random();
}

(function () {
    'use strict';

    var lang = window.STYLE_LANG || {};

    // a string missing from the language pack is printed as {lang.KEY}
    function text(key, fallback) {
        var value = lang[key];

        return value && value.indexOf('{lang.') !== 0 ? value : fallback;
    }

    // fill %s like placeholders, split and join so a $ in a file name is kept as is
    function fill(message, values) {
        Object.keys(values).forEach(function (key) {
            message = message.split(key).join(values[key]);
        });

        return message;
    }

    function hasFile(input) {
        return !!(input.files && input.files.length);
    }


    /*
     * Upload form of the index page: a file_N_ field for each file,
     * the next field shows up once a file is chosen in the one before it
     */
    function initUploader() {
        var form = document.getElementById('uploader');

        if (!form) {
            return;
        }

        var inputs = Array.prototype.slice.call(form.querySelectorAll('input[type="file"][name^="file_"]'));
        var errors = document.getElementById('upload-errors');
        var loadbox = document.getElementById('loadbox');
        var exts = window.UPLOAD_ALLOWED_EXTS;
        var sizes = window.UPLOAD_ALLOWED_SIZES || [];

        function showErrors(messages) {
            if (!errors) {
                return;
            }

            errors.textContent = '';

            messages.forEach(function (message) {
                var line = document.createElement('div');

                line.textContent = message;
                errors.appendChild(line);
            });

            errors.classList.toggle('d-none', messages.length === 0);
        }

        // same checks as the server, so a wrong file is not uploaded for nothing
        function checkFile(file) {
            if (!Array.isArray(exts)) {
                return '';
            }

            var dot = file.name.lastIndexOf('.');

            if (dot === -1) {
                return fill(text('wrongName', 'File name "%s" is not allowed.'), { '%s': file.name });
            }

            var ext = file.name.substring(dot + 1).toLowerCase();
            var index = exts.indexOf(ext);

            if (index === -1) {
                return fill(text('forbidExt', 'Extension "%s" is not allowed.'), { '%s': ext });
            }

            if (file.size > sizes[index]) {
                return fill(text('sizeTooBig', 'File size of "%1$s" must be smaller than %2$s.'), {
                    '%1$s': file.name,
                    '%2$s': (sizes[index] / 1048576).toFixed(2) + ' MB'
                });
            }

            return '';
        }

        inputs.forEach(function (input, index) {
            input.addEventListener('change', function () {
                if (!hasFile(input)) {
                    return;
                }

                var error = checkFile(input.files[0]);

                showErrors(error ? [error] : []);

                if (error) {
                    input.value = '';

                    return;
                }

                if (inputs[index + 1]) {
                    inputs[index + 1].closest('.upload-field').style.display = '';
                }
            });
        });

        form.addEventListener('submit', function (event) {
            if (!inputs.some(hasFile)) {
                event.preventDefault();
                showErrors([text('noFileSelected', 'No file selected!')]);

                return;
            }

            // hide the form, do not disable the button, its name tells the server to upload
            form.classList.add('d-none');

            if (loadbox) {
                loadbox.classList.remove('d-none');
            }
        });

        // coming back from the browser cache, show the form again
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                form.classList.remove('d-none');

                if (loadbox) {
                    loadbox.classList.add('d-none');
                }
            }
        });
    }


    /*
     * Copy buttons: a [data-copy] button copies the field in the same .input-group
     */
    function copyText(value) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(value);
        }

        // http sites have no clipboard api
        return new Promise(function (resolve, reject) {
            var area = document.createElement('textarea');
            var copied = false;

            area.value = value;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();

            try {
                copied = document.execCommand('copy');
            } catch (e) {}

            document.body.removeChild(area);

            if (copied) {
                resolve();
            } else {
                reject();
            }
        });
    }

    function showCopied(button) {
        var original = button.getAttribute('data-copy-html');
        var hasLabel = !!button.querySelector('span');
        var icon = document.createElement('i');

        if (original === null) {
            original = button.innerHTML;
            button.setAttribute('data-copy-html', original);
        }

        button.textContent = '';
        icon.className = 'fa-solid fa-check' + (hasLabel ? ' me-1' : '');
        button.appendChild(icon);

        if (hasLabel) {
            var label = document.createElement('span');

            label.textContent = text('copied', 'Copied');
            button.appendChild(label);
        }

        clearTimeout(button.copyTimer);
        button.copyTimer = setTimeout(function () {
            button.innerHTML = original;
        }, 1500);
    }

    function initCopyButtons() {
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            if (!button.hasAttribute('title')) {
                button.title = text('copy', 'Copy');
            }

            if (!button.textContent.trim() && !button.hasAttribute('aria-label')) {
                button.setAttribute('aria-label', button.title);
            }
        });

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-copy]');

            if (!button) {
                return;
            }

            var field = (button.closest('.input-group') || button.parentElement).querySelector('input, textarea');

            if (!field) {
                return;
            }

            copyText(field.value).then(
                function () {
                    showCopied(button);
                },
                function () {
                    field.focus();
                    field.select();
                }
            );
        });
    }


    /*
     * Small helpers
     */
    function initHelpers() {
        // [data-check-all] checks every .file-select of its form, or unchecks them when all are checked
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-check-all]');

            if (!button) {
                return;
            }

            var boxes = Array.prototype.slice.call((button.form || document).querySelectorAll('.file-select'));
            var allChecked = boxes.every(function (box) {
                return box.checked;
            });

            boxes.forEach(function (box) {
                box.checked = !allChecked;
            });
        });

        // form[data-confirm] asks before it is sent
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });

        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                window.bootstrap.Tooltip.getOrCreateInstance(element);
            });
        }
    }

    initUploader();
    initCopyButtons();
    initHelpers();
})();
