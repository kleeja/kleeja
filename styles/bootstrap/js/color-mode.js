/*
 * Color mode: light, dark or auto (follows the system).
 * Loaded in <head> without defer so the theme is set before the first paint,
 * the switcher in header.html is wired once the page is ready.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'color-mode';
    var MODES = ['light', 'dark', 'auto'];
    var ICONS = { light: 'fa-sun', dark: 'fa-moon', auto: 'fa-circle-half-stroke' };
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function read() {
        try {
            var mode = window.localStorage.getItem(STORAGE_KEY);

            return MODES.indexOf(mode) !== -1 ? mode : 'auto';
        } catch (e) {
            return 'auto';
        }
    }

    function resolve(mode) {
        if (mode === 'auto') {
            return media && media.matches ? 'dark' : 'light';
        }

        return mode;
    }

    function showActive(mode) {
        var icon = document.querySelector('[data-color-mode-icon]');

        if (icon) {
            icon.classList.remove(ICONS.light, ICONS.dark, ICONS.auto);
            icon.classList.add(ICONS[mode]);
        }

        document.querySelectorAll('[data-color-mode-value]').forEach(function (button) {
            var active = button.getAttribute('data-color-mode-value') === mode;

            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function apply(mode) {
        root.setAttribute('data-bs-theme', resolve(mode));
    }

    function set(mode) {
        if (MODES.indexOf(mode) === -1) {
            return;
        }

        try {
            window.localStorage.setItem(STORAGE_KEY, mode);
        } catch (e) {}

        apply(mode);
        showActive(mode);
    }

    apply(read());

    if (media) {
        var onSystemChange = function () {
            if (read() === 'auto') {
                apply('auto');
            }
        };

        if (media.addEventListener) {
            media.addEventListener('change', onSystemChange);
        } else if (media.addListener) {
            media.addListener(onSystemChange);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        showActive(read());

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-color-mode-value]');

            if (button) {
                set(button.getAttribute('data-color-mode-value'));
            }
        });
    });
})();
