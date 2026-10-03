/*
 * Kleeja default style: the upload form, copy buttons, the download wait and small page helpers.
 * Plain JavaScript, loaded at the end of <body> after the language strings of footer.html.
 */

/**
 * Load a new captcha image, global for the templates of plugins and older styles
 * @param {string} captcha_file
 * @param {string} input_id
 */
function update_kleeja_captcha(captcha_file, input_id) {
    var input = document.getElementById(input_id);
    var image = document.getElementById("kleeja_img_captcha");

    if (input) {
        input.value = "";
    }

    if (image) {
        image.src = captcha_file + (captcha_file.indexOf("?") === -1 ? "?" : "&") + Math.random();
    }
}

(function () {
    "use strict";

    var SVG_NS = "http://www.w3.org/2000/svg";
    var strings = readStrings();

    /*
     * Helpers
     */

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    // the strings are printed as text by footer.html, so quotes in a translation are safe
    function readStrings() {
        var result = {};
        var holder = document.getElementById("style-lang");

        if (holder) {
            toArray(holder.querySelectorAll("[data-key]")).forEach(function (item) {
                result[item.getAttribute("data-key")] = item.textContent.trim();
            });
        }

        return result;
    }

    // a string missing from the language pack is printed as {lang.KEY}
    function text(key, fallback) {
        var value = strings[key];

        return value && value.indexOf("{lang.") !== 0 ? value : fallback;
    }

    // fill %s like placeholders, split and join so a $ in a file name is kept as it is
    function fill(message, values) {
        Object.keys(values).forEach(function (key) {
            message = message.split(key).join(values[key]);
        });

        return message;
    }

    function formatSize(bytes) {
        var units = ["B", "KB", "MB", "GB", "TB"];
        var size = bytes;
        var unit = 0;

        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }

        return (unit === 0 ? size : size.toFixed(size < 10 ? 2 : 1)) + " " + units[unit];
    }

    function extension(name) {
        var dot = name.lastIndexOf(".");

        return dot === -1 ? "" : name.substring(dot + 1).toLowerCase();
    }

    function element(tag, className, content) {
        var node = document.createElement(tag);

        if (className) {
            node.className = className;
        }

        if (content) {
            node.textContent = content;
        }

        return node;
    }

    // an icon of the sprite in icons.html
    function icon(name) {
        var svg = document.createElementNS(SVG_NS, "svg");
        var use = document.createElementNS(SVG_NS, "use");

        svg.setAttribute("class", "icon");
        svg.setAttribute("aria-hidden", "true");
        use.setAttribute("href", "#i-" + name);
        svg.appendChild(use);

        return svg;
    }

    // the icon of a file type from images/filetypes, file.png for an unknown type
    function fileTypeIcon(base, ext) {
        var image = document.createElement("img");

        image.className = "upload-field-icon";
        image.alt = "";
        image.width = 24;
        image.height = 24;
        image.onerror = function () {
            image.onerror = null;
            image.src = base + "file.png";
        };
        image.src = base + (ext || "file") + ".png";

        return image;
    }

    function dispatch(target, name) {
        var event;

        try {
            event = new CustomEvent(name, { bubbles: true });
        } catch (e) {
            event = document.createEvent("CustomEvent");
            event.initCustomEvent(name, true, false, null);
        }

        target.dispatchEvent(event);
    }

    function prefersReducedMotion() {
        return !!window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }

    /*
     * Copy buttons: a [data-copy] button copies the field of its .input-group
     */
    function copyText(value) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(value);
        }

        // sites on http have no clipboard api
        return new Promise(function (resolve, reject) {
            var area = document.createElement("textarea");
            var copied = false;

            area.value = value;
            area.setAttribute("readonly", "");
            area.style.position = "fixed";
            area.style.top = "0";
            area.style.opacity = "0";
            document.body.appendChild(area);
            area.select();

            try {
                copied = document.execCommand("copy");
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
        var done = text("copied", "Copied");
        var hasLabel = !!button.querySelector("span");

        if (!button.hasAttribute("data-copy-html")) {
            button.setAttribute("data-copy-html", button.innerHTML);
            button.setAttribute("data-copy-label", button.getAttribute("aria-label") || "");
        }

        button.textContent = "";
        button.appendChild(icon("check"));

        if (hasLabel) {
            button.appendChild(element("span", "", done));
        } else {
            button.setAttribute("aria-label", done);
        }

        button.classList.add("is-copied");

        window.clearTimeout(button.copyTimer);
        button.copyTimer = window.setTimeout(function () {
            var label = button.getAttribute("data-copy-label");

            button.innerHTML = button.getAttribute("data-copy-html");
            button.classList.remove("is-copied");

            if (label) {
                button.setAttribute("aria-label", label);
            }
        }, 1600);
    }

    function initCopyButtons() {
        var label = text("copy", "Copy");

        function prepare(scope) {
            toArray(scope.querySelectorAll("[data-copy]")).forEach(function (button) {
                if (!button.hasAttribute("title")) {
                    button.title = label;
                }

                if (!button.textContent.trim() && !button.hasAttribute("aria-label")) {
                    button.setAttribute("aria-label", label);
                }
            });
        }

        prepare(document);

        // result boxes added by the upload form
        document.addEventListener("kleeja:content", function (event) {
            prepare(event.target);
        });

        document.addEventListener("click", function (event) {
            var button = event.target.closest("[data-copy]");

            if (!button) {
                return;
            }

            var group = button.closest(".input-group") || button.parentElement;
            var field = group ? group.querySelector("input, textarea") : null;

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
                },
            );
        });

        // a click in a link field selects all of it
        document.addEventListener("focusin", function (event) {
            var field = event.target;

            if (field.classList && field.classList.contains("copy-field")) {
                window.setTimeout(function () {
                    field.select();
                }, 0);
            }
        });
    }

    /*
     * Upload form of the index page
     *
     * The server reads the files from the fields file_1_, file_2_ ... one file each,
     * the next field shows up once a file is chosen. When the browser can move files between
     * the fields, files picked together or dropped on the page are placed one per field,
     * and the files after a removed one move up. The form is sent in the background with
     * a real progress, and index.php answers it with the messages as json (the "ajax" field).
     */
    function initUploader() {
        var form = document.getElementById("uploader");

        if (!form) {
            return;
        }

        var slots = toArray(form.querySelectorAll('input[type="file"][name^="file_"]'));

        if (!slots.length) {
            return;
        }

        var errorsBox = document.getElementById("upload-errors");
        var errorsList = errorsBox ? errorsBox.querySelector("[data-upload-errors]") : null;
        var loadbox = document.getElementById("loadbox");
        var progress = loadbox ? loadbox.querySelector("[data-progress]") : null;
        var bar = loadbox ? loadbox.querySelector("[data-progress-bar]") : null;
        var barValue = loadbox ? loadbox.querySelector("[data-progress-value]") : null;
        var results = document.getElementById("upload-results");
        var hint = form.querySelector("[data-upload-hint]");
        var submit = form.querySelector('[name="submitr"]');
        var fileTypes = form.getAttribute("data-filetypes") || "images/filetypes/";
        var exts = window.UPLOAD_ALLOWED_EXTS;
        var sizes = window.UPLOAD_ALLOWED_SIZES || [];
        var canMove = canAssignFiles();
        var nativeSubmit = false;

        // the fields the server shows at first, the others show up one by one
        var shownAtFirst = slots.map(function (slot) {
            return fieldOf(slot).style.display !== "none";
        });

        function fieldOf(slot) {
            return slot.closest(".upload-field") || slot.parentElement;
        }

        function canAssignFiles() {
            try {
                var transfer = new DataTransfer();

                return !!(transfer.items && transfer.items.add) && "files" in slots[0];
            } catch (e) {
                return false;
            }
        }

        function canSendInBackground() {
            return !!(window.FormData && window.XMLHttpRequest && "upload" in new XMLHttpRequest());
        }

        function hasFile(input) {
            return !!(input.files && input.files.length);
        }

        function showErrors(messages) {
            if (!errorsBox || !errorsList) {
                if (messages.length) {
                    window.alert(messages.join("\n"));
                }

                return;
            }

            errorsList.textContent = "";

            messages.forEach(function (message) {
                errorsList.appendChild(element("div", "", message));
            });

            errorsBox.hidden = messages.length === 0;
        }

        // the same checks as the server, so a wrong file is not uploaded for nothing
        function checkFile(file) {
            if (!Array.isArray(exts)) {
                return "";
            }

            var ext = extension(file.name);

            if (!ext) {
                return fill(text("wrongName", 'File name "%s" contains restricted characters.'), { "%s": file.name });
            }

            var index = exts.indexOf(ext);

            if (index === -1) {
                return fill(text("forbidExt", 'Extension "%s" not supported.'), { "%s": ext });
            }

            // zero is no limit
            var max = Number(sizes[index]) || 0;

            if (max > 0 && file.size >= max) {
                return fill(text("sizeTooBig", 'File size of "%1$s" must be smaller than %2$s .'), {
                    "%1$s": file.name,
                    "%2$s": formatSize(max),
                });
            }

            return "";
        }

        /* the fields */

        // shows the fields with a file, the ones the server shows, and the first empty one
        function layout() {
            var emptyShown = false;

            slots.forEach(function (slot, index) {
                var used = hasFile(slot);
                var show = used || shownAtFirst[index] || !emptyShown;

                if (show && !used) {
                    emptyShown = true;
                }

                fieldOf(slot).style.display = show ? "" : "none";
                describe(slot);
            });
        }

        // the type icon in place of the number, the size and a remove button beside a chosen file
        function describe(slot) {
            var field = fieldOf(slot);
            var file = hasFile(slot) ? slot.files[0] : null;

            if (field.shownFile === file) {
                return;
            }

            var number = field.querySelector(".upload-field-num");
            var meta = field.querySelector(".upload-field-meta");

            field.shownFile = file;
            field.classList.toggle("has-file", !!file);

            if (meta) {
                field.removeChild(meta);
            }

            if (number) {
                number.textContent = "";

                if (file) {
                    number.appendChild(fileTypeIcon(fileTypes, extension(file.name)));
                } else {
                    number.textContent = slot.getAttribute("data-number") || String(slots.indexOf(slot) + 1);
                }
            }

            if (!file) {
                return;
            }

            var removeLabel = text("remove", "Delete");
            var remove = element("button", "upload-field-remove");

            meta = element("span", "upload-field-meta");
            meta.appendChild(element("span", "upload-field-size", formatSize(file.size)));

            remove.type = "button";
            remove.title = removeLabel;
            remove.setAttribute("aria-label", removeLabel + ": " + file.name);
            remove.appendChild(icon("x"));
            remove.addEventListener("click", function () {
                removeFile(slot);
            });

            meta.appendChild(remove);
            field.appendChild(meta);
        }

        // the files move up, so there is no empty field between them
        function compact() {
            var files = slots.filter(hasFile).map(function (slot) {
                return slot.files[0];
            });

            slots.forEach(function (slot, index) {
                var transfer = new DataTransfer();

                if (files[index]) {
                    transfer.items.add(files[index]);
                }

                slot.files = transfer.files;
            });
        }

        function removeFile(slot) {
            slot.value = "";

            if (canMove) {
                compact();
            }

            showErrors([]);
            layout();

            // the field keeps the focus, or the first empty field when it was hidden
            var target =
                fieldOf(slot).style.display === "none"
                    ? slots.filter(function (input) {
                          return !hasFile(input) && fieldOf(input).style.display !== "none";
                      })[0]
                    : slot;

            if (target) {
                target.focus();
            }
        }

        function isQueued(file) {
            return slots.some(function (slot) {
                var queued = hasFile(slot) ? slot.files[0] : null;

                return (
                    !!queued &&
                    queued.name === file.name &&
                    queued.size === file.size &&
                    queued.lastModified === file.lastModified
                );
            });
        }

        // places the files in the empty fields, the first one in the field it was picked with
        function addFiles(files, target) {
            var messages = [];
            var limit = text("limit", "This is the final limit for input fields");

            toArray(files).forEach(function (file) {
                if (isQueued(file)) {
                    return;
                }

                var error = checkFile(file);

                if (error) {
                    messages.push(error);

                    return;
                }

                var slot =
                    target && !hasFile(target)
                        ? target
                        : slots.filter(function (input) {
                              return !hasFile(input);
                          })[0];

                if (!slot) {
                    if (messages.indexOf(limit) === -1) {
                        messages.push(limit);
                    }

                    return;
                }

                var transfer = new DataTransfer();

                transfer.items.add(file);
                slot.files = transfer.files;
            });

            compact();
            showErrors(messages);
            layout();
        }

        function onPick(slot) {
            if (canMove) {
                var picked = toArray(slot.files);

                // the field takes the first picked file again, the others go to the next fields
                slot.value = "";
                addFiles(picked, slot);

                return;
            }

            var error = hasFile(slot) ? checkFile(slot.files[0]) : "";

            showErrors(error ? [error] : []);

            if (error) {
                slot.value = "";
            }

            layout();
        }

        // the allowed extensions under the fields, the full list is on the guide page
        function showAllowedExtensions() {
            var box = form.querySelector("[data-upload-exts]");
            var max = 10;

            if (!box || !Array.isArray(exts)) {
                return;
            }

            var list = exts.filter(function (ext) {
                return !!ext;
            });

            if (!list.length) {
                return;
            }

            list.slice(0, max).forEach(function (ext) {
                box.appendChild(element("span", "ext-chip", ext));
            });

            if (list.length > max) {
                box.appendChild(element("span", "ext-chip ext-chip-more", "+" + (list.length - max)));
            }

            box.hidden = false;
        }

        // the whole page takes the dropped files, the browser never opens a file dropped by mistake
        function initDrop() {
            var depth = 0;

            function carriesFiles(event) {
                var types = event.dataTransfer && event.dataTransfer.types;

                return !!types && toArray(types).indexOf("Files") !== -1;
            }

            // no drops while the files are being sent
            function active() {
                return !form.hidden;
            }

            document.addEventListener("dragenter", function (event) {
                if (carriesFiles(event) && active()) {
                    depth++;
                    form.classList.add("is-dragover");
                }
            });

            document.addEventListener("dragleave", function (event) {
                if (carriesFiles(event)) {
                    depth = Math.max(0, depth - 1);

                    if (depth === 0) {
                        form.classList.remove("is-dragover");
                    }
                }
            });

            document.addEventListener("dragover", function (event) {
                if (carriesFiles(event)) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = active() ? "copy" : "none";
                }
            });

            document.addEventListener("drop", function (event) {
                if (!carriesFiles(event)) {
                    return;
                }

                event.preventDefault();
                depth = 0;
                form.classList.remove("is-dragover");

                // a file dropped on a field goes to that field, a full list says so, see addFiles()
                if (active()) {
                    addFiles(event.dataTransfer.files, slots.indexOf(event.target) !== -1 ? event.target : null);
                }
            });
        }

        /* sending */

        function setProgress(ratio) {
            var indeterminate = ratio === null;
            var percent = indeterminate ? 0 : Math.round(Math.min(1, ratio) * 100);

            // loading.gif while the progress is unknown, the striped bar with the percent after
            if (loadbox) {
                loadbox.classList.toggle("is-determinate", !indeterminate);
            }

            if (bar) {
                bar.style.width = indeterminate ? "" : percent + "%";
            }

            if (barValue) {
                barValue.textContent = indeterminate ? "" : percent + "%";
            }

            if (progress) {
                if (indeterminate) {
                    progress.removeAttribute("aria-valuenow");
                } else {
                    progress.setAttribute("aria-valuenow", String(percent));
                }
            }
        }

        function showLoading(determinate) {
            form.hidden = true;

            if (loadbox) {
                loadbox.hidden = false;
                setProgress(determinate ? 0 : null);
            }
        }

        function hideLoading() {
            form.hidden = false;

            if (loadbox) {
                loadbox.hidden = true;
                setProgress(null);
            }
        }

        function refreshCaptcha() {
            var trigger = form.querySelector("[data-captcha-refresh]");

            if (trigger) {
                update_kleeja_captcha(
                    trigger.getAttribute("data-captcha-refresh"),
                    trigger.getAttribute("data-captcha-input") || "kleeja_code_answer",
                );
            }
        }

        function reset() {
            slots.forEach(function (slot) {
                slot.value = "";
            });

            layout();
        }

        function tryJson(value) {
            try {
                return JSON.parse(value);
            } catch (e) {
                return null;
            }
        }

        // index.php answers with [{message_content, message_type}, ...], null for any other answer
        function parseMessages(body) {
            var trimmed = body.trim();

            if (trimmed === "") {
                return [];
            }

            var data = tryJson(trimmed);

            // notices printed before the json on development sites
            if (data === null) {
                var start = trimmed.indexOf("[{");
                var end = trimmed.lastIndexOf("}]");

                if (start !== -1 && end > start) {
                    data = tryJson(trimmed.substring(start, end + 2));
                }
            }

            if (!Array.isArray(data)) {
                return null;
            }

            return data.map(function (item) {
                var type = item.message_type || (item.t === "index_err" ? "error" : "info");

                return {
                    type: type === "error" ? "error" : "info",
                    html: String(item.message_content || item.i || ""),
                };
            });
        }

        function showResults(messages) {
            var success = document.getElementById("tpl-upload-result");
            var failure = document.getElementById("tpl-upload-error");
            var uploaded = false;

            if (!results) {
                return;
            }

            results.textContent = "";

            messages.forEach(function (message) {
                var template = message.type === "error" ? failure : success;

                if (!template || !template.content || !template.content.firstElementChild) {
                    return;
                }

                var node = template.content.firstElementChild.cloneNode(true);
                var body = node.querySelector("[data-slot]") || node;

                body.innerHTML = message.html;
                results.appendChild(node);
                uploaded = uploaded || message.type !== "error";
            });

            dispatch(results, "kleeja:content");

            // when nothing was uploaded the chosen files stay, to try again
            if (uploaded) {
                reset();
            }

            if (messages.length) {
                results.focus({ preventScroll: true });
                results.scrollIntoView({ behavior: prefersReducedMotion() ? "auto" : "smooth", block: "start" });
            }
        }

        // the classic way, for an answer that is a whole page, like a message for guests
        function submitNatively() {
            nativeSubmit = true;
            showLoading(false);

            if (form.requestSubmit && submit) {
                form.requestSubmit(submit);

                return;
            }

            var hidden = element("input");

            hidden.type = "hidden";
            hidden.name = submit ? submit.name : "submitr";
            hidden.value = submit ? submit.value : "1";
            form.appendChild(hidden);
            form.submit();
        }

        function send() {
            var data = new FormData(form);
            var request = new XMLHttpRequest();

            // a submit button is not a part of FormData, its name tells the server to upload
            data.append(submit ? submit.name : "submitr", submit ? submit.value : "1");
            data.append("ajax", "1");

            request.open("POST", form.action);
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");

            request.upload.addEventListener("progress", function (event) {
                if (event.lengthComputable) {
                    setProgress(event.loaded / event.total);
                }
            });

            request.addEventListener("load", function () {
                var ok = request.status >= 200 && request.status < 300;
                var messages = ok ? parseMessages(request.responseText || "") : null;

                if (messages === null) {
                    submitNatively();

                    return;
                }

                hideLoading();
                showResults(messages);
                refreshCaptcha();
            });

            request.addEventListener("error", function () {
                hideLoading();
                showErrors([text("tryAgain", "Error, try again.")]);
            });

            // the links of the last upload go, the new ones take their place
            if (results) {
                results.textContent = "";
            }

            showLoading(true);
            request.send(data);
        }

        form.addEventListener("submit", function (event) {
            if (nativeSubmit) {
                return;
            }

            if (!slots.some(hasFile)) {
                event.preventDefault();
                showErrors([text("noFileSelected", "No file selected!")]);

                return;
            }

            showErrors([]);

            if (!canSendInBackground()) {
                // sent the classic way, show that it is working
                showLoading(false);

                return;
            }

            event.preventDefault();
            send();
        });

        // coming back from the browser cache, show the form again
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) {
                nativeSubmit = false;
                hideLoading();
            }
        });

        slots.forEach(function (slot) {
            slot.addEventListener("change", function () {
                onPick(slot);
            });
        });

        if (canMove) {
            slots.forEach(function (slot) {
                slot.multiple = true;
            });

            initDrop();
            showAllowedExtensions();

            if (hint) {
                hint.hidden = false;
            }
        }

        layout();
    }

    /*
     * Waiting time of the download page
     */
    function initDownload() {
        var panel = document.querySelector("[data-download]");

        if (!panel) {
            return;
        }

        var wait = panel.querySelector("[data-download-wait]");
        var count = panel.querySelector("[data-download-count]");
        var ring = panel.querySelector("[data-download-ring]");
        var button = panel.querySelector("[data-download-button]");
        var total = parseInt(panel.getAttribute("data-seconds"), 10) || 0;
        var left = total;

        if (!button) {
            return;
        }

        function ready() {
            if (wait) {
                wait.hidden = true;
            }

            button.hidden = false;
        }

        function render() {
            if (count) {
                count.textContent = String(left);
            }

            if (ring) {
                ring.style.strokeDashoffset = String(100 - (left / total) * 100);
            }
        }

        if (total <= 0) {
            ready();

            return;
        }

        render();

        var timer = window.setInterval(function () {
            left -= 1;
            render();

            if (left <= 0) {
                window.clearInterval(timer);
                ready();
            }
        }, 1000);
    }

    /*
     * Files of the user folder: check all, shift+click for a range,
     * the delete button works when a file is selected
     */
    function initFileSelection() {
        var boxes = toArray(document.querySelectorAll(".file-select"));

        if (!boxes.length) {
            return;
        }

        var toggleAll = document.querySelector("[data-check-all]");
        var actions = toArray(document.querySelectorAll("[data-requires-selection]"));
        var counters = toArray(document.querySelectorAll("[data-selected-count]"));
        var last = null;

        function update() {
            var selected = boxes.filter(function (box) {
                return box.checked;
            }).length;

            actions.forEach(function (action) {
                action.disabled = selected === 0;
            });

            counters.forEach(function (counter) {
                counter.textContent = String(selected);
                counter.hidden = selected === 0;
            });

            if (toggleAll) {
                toggleAll.setAttribute("aria-pressed", selected === boxes.length ? "true" : "false");
            }
        }

        boxes.forEach(function (box, index) {
            box.addEventListener("click", function (event) {
                if (event.shiftKey && last !== null && last !== index) {
                    var from = Math.min(last, index);
                    var to = Math.max(last, index);

                    for (var i = from; i <= to; i++) {
                        boxes[i].checked = box.checked;
                    }
                }

                last = index;
                update();
            });
        });

        if (toggleAll) {
            toggleAll.addEventListener("click", function () {
                var all = boxes.every(function (box) {
                    return box.checked;
                });

                boxes.forEach(function (box) {
                    box.checked = !all;
                });

                update();
            });
        }

        update();
    }

    /*
     * Small helpers
     */
    function initHelpers() {
        // form[data-confirm] asks before it is sent
        document.addEventListener("submit", function (event) {
            var form = event.target;
            var message = form.getAttribute ? form.getAttribute("data-confirm") : null;

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });

        // [data-captcha-refresh="url"] loads a new captcha image
        document.addEventListener("click", function (event) {
            var trigger = event.target.closest("[data-captcha-refresh]");

            if (!trigger) {
                return;
            }

            var inputId = trigger.getAttribute("data-captcha-input") || "kleeja_code_answer";
            var input = document.getElementById(inputId);

            event.preventDefault();
            update_kleeja_captcha(trigger.getAttribute("data-captcha-refresh"), inputId);

            if (input) {
                input.focus();
            }
        });
    }

    initCopyButtons();
    initUploader();
    initDownload();
    initFileSelection();
    initHelpers();
})();
