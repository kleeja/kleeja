/*
 * Damask colour mode: light, dark or auto (follows the system).
 * Loaded in <head> without defer so the right theme is set before the first paint.
 */
(function () {
    "use strict";

    var STORAGE_KEY = "kleeja-acp-color-mode";
    var MODES = ["light", "dark", "auto"];
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;

    function read() {
        try {
            var mode = window.localStorage.getItem(STORAGE_KEY);
            return MODES.indexOf(mode) !== -1 ? mode : "auto";
        } catch (e) {
            return "auto";
        }
    }

    function resolve(mode) {
        if (mode === "auto") {
            return media && media.matches ? "dark" : "light";
        }

        return mode;
    }

    function apply(mode) {
        var theme = resolve(mode);

        root.setAttribute("data-bs-theme", theme);
        root.setAttribute("data-kj-mode", mode);

        if (typeof window.CustomEvent === "function") {
            document.dispatchEvent(new CustomEvent("kj:colormode", { detail: { mode: mode, theme: theme } }));
        }
    }

    window.KleejaColorMode = {
        get: read,
        theme: function () {
            return resolve(read());
        },
        set: function (mode) {
            if (MODES.indexOf(mode) === -1) {
                return;
            }

            try {
                window.localStorage.setItem(STORAGE_KEY, mode);
            } catch (e) {}

            apply(mode);
        },
    };

    apply(read());

    if (media) {
        var onChange = function () {
            if (read() === "auto") {
                apply("auto");
            }
        };

        if (media.addEventListener) {
            media.addEventListener("change", onChange);
        } else if (media.addListener) {
            media.addListener(onChange);
        }
    }
})();
