/*
 * Damask, the Kleeja control panel theme.
 * Plain JavaScript on top of the Bootstrap 5 bundle; every block is opt-in through
 * data-kj-* attributes, so a page only runs what its markup asks for.
 */
(function () {
    "use strict";

    var body = document.body;

    function $(selector, context) {
        return (context || document).querySelector(selector);
    }

    function $$(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    // translated strings are handed over as data attributes on <body>
    function msg(key, fallback) {
        return (body && body.dataset[key]) || fallback;
    }

    function decode(url) {
        return String(url || "").replace(/&amp;/g, "&");
    }

    function splitOnce(text, separator) {
        text = String(text || "");
        var index = text.indexOf(separator);

        return index === -1 ? [text, ""] : [text.slice(0, index), text.slice(index + separator.length)];
    }

    function emit(name, detail) {
        document.dispatchEvent(new CustomEvent(name, { detail: detail }));
    }

    // admin ajax answers are {code, content, menu}, even when the status is an error
    function fetchJSON(url) {
        return fetch(decode(url), {
            credentials: "same-origin",
            cache: "no-store",
            headers: { "X-Requested-With": "XMLHttpRequest" },
        }).then(function (response) {
            return response.text().then(function (text) {
                var data = null;

                try {
                    data = JSON.parse(text);
                } catch (e) {}

                if (!response.ok || !data || !data.content) {
                    var error = new Error("Request failed");
                    error.data = data;
                    error.status = response.status;
                    throw error;
                }

                return data;
            });
        });
    }

    function errorText(error) {
        return (error && error.data && error.data.content) || msg("kjMsgError", "Error, try again.");
    }

    function spinner() {
        var span = document.createElement("span");
        span.className = "spinner-border spinner-border-sm";
        span.setAttribute("aria-hidden", "true");

        return span;
    }

    function icon(classes) {
        var i = document.createElement("i");
        i.className = classes;
        i.setAttribute("aria-hidden", "true");

        return i;
    }

    /* --- Global helpers, kept for templates and plugins that call them ----------------- */

    window.confirm_form = function (message) {
        return window.confirm(message || msg("kjMsgConfirm", "Are you sure?"));
    };

    window.get_kleeja_link = function (link, target, options) {
        if (options && options.confirm && !window.confirm_form()) {
            return false;
        }

        window.location.href = decode(link);

        return false;
    };

    window.update_kleeja_captcha = function (captchaFile, inputId) {
        var input = document.getElementById(inputId);
        var image = document.getElementById("kleeja_img_captcha");

        if (input) {
            input.value = "";
            input.focus();
        }

        if (image) {
            image.src = captchaFile + (captchaFile.indexOf("?") === -1 ? "?" : "&") + Math.random();
        }
    };

    window.change_color = function (checkbox, id, onClass, offClass) {
        var element = document.getElementById(id);

        if (!element) {
            return;
        }

        if (onClass || offClass) {
            element.className = checkbox.checked ? onClass || "" : offClass || "";
        } else {
            element.classList.toggle(element.tagName === "TR" ? "table-active" : "is-selected", checkbox.checked);
        }
    };

    function relBoxes(form, rel) {
        return Array.prototype.filter.call(form.elements, function (element) {
            return element.getAttribute("rel") === rel;
        });
    }

    window.checkAll = function (form, rel, prefix, onClass, offClass) {
        var boxes = relBoxes(form, rel);
        var check = !boxes.some(function (box) {
            return box.checked;
        });

        boxes.forEach(function (box) {
            box.checked = check;
            window.change_color(box, prefix + "[" + box.value + "]", onClass, offClass);
        });
    };

    window.uncheckAll = function (form, rel, prefix, onClass, offClass) {
        relBoxes(form, rel).forEach(function (box) {
            box.checked = false;
            window.change_color(box, prefix + "[" + box.value + "]", onClass, offClass);
        });
    };

    /* --- Bootstrap 4 data attributes on plugin pages -------------------------------------- */

    function upgradeLegacyDataApi() {
        var map = {
            toggle: "bsToggle",
            target: "bsTarget",
            dismiss: "bsDismiss",
            placement: "bsPlacement",
            parent: "bsParent",
            html: "bsHtml",
            trigger: "bsTrigger",
        };

        $$("[data-toggle], [data-dismiss]").forEach(function (element) {
            Object.keys(map).forEach(function (legacy) {
                if (element.dataset[legacy] !== undefined && element.dataset[map[legacy]] === undefined) {
                    element.dataset[map[legacy]] = element.dataset[legacy];
                }
            });
        });
    }

    function initBootstrapParts() {
        if (!window.bootstrap) {
            return;
        }

        $$('[data-bs-toggle="tooltip"]').forEach(function (element) {
            window.bootstrap.Tooltip.getOrCreateInstance(element);
        });

        $$('[data-bs-toggle="popover"]').forEach(function (element) {
            window.bootstrap.Popover.getOrCreateInstance(element);
        });

        $$("[data-kj-autohide]").forEach(function (element) {
            window.setTimeout(function () {
                window.bootstrap.Alert.getOrCreateInstance(element).close();
            }, 6000);
        });
    }

    /* --- Colour mode switcher -------------------------------------------------------------- */

    function initColorMode() {
        var colorMode = window.KleejaColorMode;
        var icons = { light: "fa-sun", dark: "fa-moon", auto: "fa-circle-half-stroke" };

        if (!colorMode) {
            return;
        }

        function sync() {
            var mode = colorMode.get();

            $$("[data-kj-color-mode]").forEach(function (button) {
                var active = button.dataset.kjColorMode === mode;
                button.classList.toggle("active", active);
                button.setAttribute("aria-pressed", active ? "true" : "false");
            });

            $$("[data-kj-color-icon]").forEach(function (element) {
                element.classList.remove("fa-sun", "fa-moon", "fa-circle-half-stroke");
                element.classList.add(icons[mode]);
            });
        }

        document.addEventListener("click", function (event) {
            var button = event.target.closest("[data-kj-color-mode]");

            if (button) {
                colorMode.set(button.dataset.kjColorMode);
            }
        });

        document.addEventListener("kj:colormode", sync);
        sync();
    }

    /* --- Confirmation on links and buttons --------------------------------------------------- */

    function initConfirm() {
        document.addEventListener("click", function (event) {
            var element = event.target.closest("[data-kj-confirm]");

            if (element && !window.confirm_form(element.dataset.kjConfirm || "")) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        });
    }

    /* --- Unread messages and reports ------------------------------------------------------- */

    function setCounter(name, value) {
        var count = parseInt(value, 10) || 0;

        $$(".bubble_" + name).forEach(function (element) {
            element.textContent = count;
            element.classList.toggle("d-none", count === 0);
        });
    }

    function syncBell() {
        var dots = $$("[data-kj-bell-dot]");
        var unread = $$(".kj-topbar .dropdown-menu .kj-badge").some(function (badge) {
            return !badge.classList.contains("d-none") && (parseInt(badge.textContent, 10) || 0) > 0;
        });

        dots.forEach(function (dot) {
            dot.classList.toggle("d-none", !unread);
        });
    }

    function refreshCounters() {
        return fetch("./?check_msgs=1", { credentials: "same-origin", cache: "no-store" })
            .then(function (response) {
                if (response.status === 401) {
                    var error = new Error("Session ended");
                    error.sessionEnded = true;
                    throw error;
                }

                return response.text();
            })
            .then(function (text) {
                if (text.indexOf("::") !== -1) {
                    var numbers = text.split("::");
                    setCounter("calls", numbers[0]);
                    setCounter("reports", numbers[1]);
                    syncBell();
                }
            });
    }

    // the calls and reports pages call this after deleting items
    window.check_msg_and_reports = function () {
        return refreshCounters().catch(function () {});
    };

    function initCounters() {
        if (!$("[data-kj-bell-dot]")) {
            return;
        }

        function poll() {
            refreshCounters()
                .then(function () {
                    window.setTimeout(poll, 240000);
                })
                .catch(function (error) {
                    if (error && error.sessionEnded) {
                        if (window.confirm(msg("kjMsgSession", "Session has ended, do you want to login again?"))) {
                            window.location.reload();
                        } else {
                            window.location.href = msg("kjSiteUrl", "../");
                        }

                        return;
                    }

                    window.setTimeout(poll, 240000);
                });
        }

        syncBell();
        window.setTimeout(poll, 240000);
    }

    /* --- Bulk selection: [data-kj-check], [data-kj-check-all], [data-kj-bulk] ------------------ */

    function initBulkSelection() {
        var groups = {};

        $$("[data-kj-check]").forEach(function (box) {
            groups[box.dataset.kjCheck] = true;
        });

        function update(group) {
            var boxes = $$('[data-kj-check="' + group + '"]');
            var checked = boxes.filter(function (box) {
                return box.checked;
            });

            boxes.forEach(function (box) {
                var row = box.closest("[data-kj-row]");

                if (row) {
                    row.classList.toggle(row.tagName === "TR" ? "table-active" : "is-selected", box.checked);
                }
            });

            $$('[data-kj-check-all="' + group + '"]').forEach(function (master) {
                master.checked = checked.length > 0 && checked.length === boxes.length;
                master.indeterminate = checked.length > 0 && checked.length < boxes.length;
            });

            $$('[data-kj-bulk="' + group + '"]').forEach(function (button) {
                var counter = $("[data-kj-count]", button);
                button.disabled = checked.length === 0;

                if (counter) {
                    counter.textContent = checked.length ? "(" + checked.length + ")" : "";
                }
            });

            emit("kj:selection", { group: group, checked: checked });
        }

        document.addEventListener("change", function (event) {
            var target = event.target;

            if (target.dataset.kjCheckAll) {
                $$('[data-kj-check="' + target.dataset.kjCheckAll + '"]').forEach(function (box) {
                    box.checked = target.checked;
                });
                update(target.dataset.kjCheckAll);
            } else if (target.dataset.kjCheck) {
                update(target.dataset.kjCheck);
            }
        });

        Object.keys(groups).forEach(update);
    }

    /* --- Forms ---------------------------------------------------------------------------- */

    function initForms() {
        // at least one field must be filled, e.g. the search forms
        document.addEventListener(
            "submit",
            function (event) {
                var form = event.target;

                if (!form.matches || !form.matches("[data-kj-require-one]")) {
                    return;
                }

                var filled = $$('input[type="text"], input[type="email"], input[type="search"]', form).some(
                    function (input) {
                        return input.value.trim() !== "";
                    },
                );

                if (!filled) {
                    event.preventDefault();
                    window.alert(form.dataset.kjRequireOne);
                }
            },
            true,
        );

        // copy a read-only value, e.g. the cron link
        document.addEventListener("click", function (event) {
            var button = event.target.closest("[data-kj-copy]");

            if (!button) {
                return;
            }

            var input = $(button.dataset.kjCopy);
            var label = $("[data-kj-copy-label]", button);

            if (!input) {
                return;
            }

            var done = function () {
                if (!label) {
                    return;
                }

                var original = label.textContent;
                label.textContent = button.dataset.kjCopied || original;
                window.setTimeout(function () {
                    label.textContent = original;
                }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(input.value).then(done);
            } else {
                input.select();
                document.execCommand("copy");
                done();
            }
        });

        // go to a link built from a select, e.g. "files uploaded since"
        document.addEventListener("change", function (event) {
            var select = event.target.closest("select[data-kj-since]");

            if (select && select.value) {
                window.location.href =
                    decode(select.dataset.kjSince) + (Math.round(Date.now() / 1000) - parseInt(select.value, 10));
            }
        });

        // byte converter: typing in one unit fills the others
        document.addEventListener("input", function (event) {
            var input = event.target.closest("[data-kj-bytes]");

            if (!input) {
                return;
            }

            var bytes = (parseFloat(input.value) || 0) * parseFloat(input.dataset.kjBytes);

            $$("[data-kj-bytes]", input.closest("[data-kj-converter]") || document).forEach(function (other) {
                if (other !== input) {
                    other.value = Math.round((bytes / parseFloat(other.dataset.kjBytes)) * 100000) / 100000;
                }
            });
        });
    }

    /* --- Dashboard -------------------------------------------------------------------------- */

    function initStartBoxes() {
        var container = $("[data-kj-start-boxes]");

        if (!container) {
            return;
        }

        var status = $("[data-kj-status]", container);

        function show(text, type) {
            if (!status) {
                return;
            }

            status.className = "alert alert-" + type + " small py-2 mb-3";
            status.innerHTML = text;
        }

        container.addEventListener("change", function (event) {
            var toggle = event.target.closest("[data-kj-start-box]");

            if (!toggle) {
                return;
            }

            var name = toggle.dataset.kjStartBox;
            var hide = toggle.checked ? 0 : 1;

            toggle.disabled = true;

            fetchJSON(
                "./?cp=r_repair&case=toggle_start_box&toggle=" +
                    hide +
                    "&_ajax_=1&name=" +
                    encodeURIComponent(name) +
                    "&" +
                    decode(container.dataset.kjFormKey),
            )
                .then(function (data) {
                    var box = document.getElementById(name);

                    if (box) {
                        box.classList.toggle("d-none", hide === 1);
                    }

                    show(data.content, "success");
                    emit("kj:layout");
                })
                .catch(function (error) {
                    toggle.checked = !toggle.checked;
                    show(errorText(error), "danger");
                })
                .then(function () {
                    toggle.disabled = false;
                });
        });
    }

    function initQuickLanguage() {
        document.addEventListener("click", function (event) {
            var button = event.target.closest("[data-kj-lang-apply]");

            if (!button) {
                return;
            }

            var language = $("#lang_change");
            var group = $("#groups_list");

            if (language && group) {
                window.location.href =
                    decode(button.dataset.kjLangApply) +
                    encodeURIComponent(language.value) +
                    "&qg=" +
                    encodeURIComponent(group.value);
            }
        });
    }

    // brand chart palette (kleeja.net/branding): coral first, then navy, lighter navy on dark surfaces
    function chartColors() {
        var dark = document.documentElement.getAttribute("data-bs-theme") === "dark";

        return dark
            ? {
                  series: ["#F45B69", "#BBC0C8"],
                  grid: "#3C4C61",
                  axis: "#BBC0C8",
                  text: "#FFFFFF",
                  surface: "#23354E",
                  pointer: "rgba(255, 255, 255, 0.06)",
                  shadow: "box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5); border-radius: 8px;",
              }
            : {
                  series: ["#F45B69", "#0B1F3A"],
                  grid: "#EDEEF0",
                  axis: "#546275",
                  text: "#0B1F3A",
                  surface: "#FFFFFF",
                  pointer: "rgba(11, 31, 58, 0.05)",
                  shadow: "box-shadow: 0 8px 24px rgba(11, 31, 58, 0.12); border-radius: 8px;",
              };
    }

    function initStatsChart() {
        var element = document.getElementById("kjStatsChart");
        var raw = window.arrayOfDataMulti;

        if (!element || !window.echarts || !Array.isArray(raw) || !raw.length) {
            return;
        }

        // the server lists the newest day first
        var rows = raw.slice().reverse();
        var labels = rows.map(function (row) {
            return String(row[1]);
        });
        var files = rows.map(function (row) {
            return Number(row[0][0]) || 0;
        });
        var images = rows.map(function (row) {
            return Number(row[0][1]) || 0;
        });

        var chart = window.echarts.init(element, null, { renderer: "svg" });

        function render() {
            var colors = chartColors();
            var rtl = document.documentElement.getAttribute("dir") === "rtl";
            var bar = function (name, data, color) {
                return {
                    name: name,
                    type: "bar",
                    data: data,
                    barMaxWidth: 18,
                    barGap: "20%",
                    itemStyle: { color: color, borderRadius: [4, 4, 0, 0] },
                    emphasis: { focus: "series" },
                };
            };

            chart.setOption(
                {
                    aria: { enabled: true },
                    color: colors.series,
                    animationDuration: 400,
                    textStyle: { fontFamily: window.getComputedStyle(body).fontFamily },
                    grid: { left: 8, right: 8, top: 12, bottom: 4, containLabel: true },
                    tooltip: {
                        trigger: "axis",
                        axisPointer: { type: "shadow", shadowStyle: { color: colors.pointer } },
                        backgroundColor: colors.surface,
                        borderColor: colors.grid,
                        borderWidth: 1,
                        padding: [8, 12],
                        textStyle: { color: colors.text, fontSize: 13 },
                        extraCssText: colors.shadow,
                    },
                    xAxis: {
                        type: "category",
                        data: labels,
                        inverse: rtl,
                        axisTick: { show: false },
                        axisLine: { lineStyle: { color: colors.grid } },
                        axisLabel: { color: colors.axis, fontSize: 12, hideOverlap: true },
                    },
                    yAxis: {
                        type: "value",
                        minInterval: 1,
                        position: rtl ? "right" : "left",
                        splitLine: { lineStyle: { color: colors.grid } },
                        axisLabel: { color: colors.axis, fontSize: 12 },
                    },
                    series: [
                        bar(element.dataset.kjLabelFiles, files, colors.series[0]),
                        bar(element.dataset.kjLabelImages, images, colors.series[1]),
                    ],
                },
                true,
            );
        }

        render();
        document.addEventListener("kj:colormode", render);
        document.addEventListener("kj:layout", function () {
            chart.resize();
        });

        if (window.ResizeObserver) {
            new ResizeObserver(function () {
                chart.resize();
            }).observe(element);
        } else {
            window.addEventListener("resize", function () {
                chart.resize();
            });
        }
    }

    /* --- Kleeja team ------------------------------------------------------------------------ */

    // numbers in the panel's language, with Latin digits like the rest of the panel
    function numberFormat(options) {
        try {
            var format = new Intl.NumberFormat((document.documentElement.lang || "en") + "-u-nu-latn", options);

            return format.format;
        } catch (error) {
            return String;
        }
    }

    // a GitHub avatar at twice its rendered size, so it stays sharp on dense screens
    function avatarUrl(value, size) {
        var url = webUrl(value);

        if (!url) {
            return "";
        }

        url = new URL(url);
        url.searchParams.set("s", String(size * 2));

        return url.href;
    }

    // the rank, share and bar only exist on the leading contributors' cards
    function teamItem(template, person, rank, share, format) {
        var item = template.content.firstElementChild.cloneNode(true);
        var avatar = $("[data-kj-team-avatar]", item);
        var rankBadge = $("[data-kj-team-rank]", item);
        var shareText = $("[data-kj-team-share]", item);
        var bar = $("[data-kj-team-bar]", item);

        $("[data-kj-team-link]", item).href =
            webUrl(person.html_url) || "https://github.com/" + encodeURIComponent(person.login);
        $("[data-kj-team-login]", item).textContent = person.login;
        $("[data-kj-team-count]", item).textContent = format.count(person.contributions);
        avatar.src = avatarUrl(person.avatar_url, +avatar.getAttribute("width"));

        if (rankBadge) {
            rankBadge.textContent = "#" + rank;
            $("[data-kj-team-link]", item).classList.toggle("is-top", rank === 1);
        }

        if (shareText) {
            shareText.textContent = format.percent(share);
        }

        if (bar) {
            bar.style.width = (share * 100).toFixed(2) + "%";
        }

        return item;
    }

    // contributors are read from GitHub and written with textContent only; the three with the
    // most commits lead the page, everyone else follows in a compact grid
    function initTeam() {
        var root = $("[data-kj-team]");
        var leadTemplate = $("#kjTeamLead");
        var itemTemplate = $("#kjTeamItem");

        if (!root || !leadTemplate || !itemTemplate || !window.fetch) {
            return;
        }

        var leads = $("[data-kj-team-leads]", root);
        var list = $("[data-kj-team-list]", root);

        fetch(root.dataset.kjTeam, {
            cache: "force-cache",
            credentials: "omit",
            referrerPolicy: "no-referrer",
            headers: { Accept: "application/vnd.github+json" },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("GitHub");
                }

                return response.json();
            })
            .then(function (people) {
                // automated accounts such as dependabot are not part of the team
                people = (Array.isArray(people) ? people : [])
                    .filter(function (person) {
                        return person && person.login && person.type !== "Bot";
                    })
                    .map(function (person) {
                        return {
                            login: String(person.login),
                            html_url: person.html_url,
                            avatar_url: person.avatar_url,
                            contributions: Math.max(0, parseInt(person.contributions, 10) || 0),
                        };
                    })
                    .sort(function (a, b) {
                        return b.contributions - a.contributions;
                    });

                if (!people.length) {
                    throw new Error("GitHub");
                }

                var total = people.reduce(function (sum, person) {
                    return sum + person.contributions;
                }, 0);
                var others = people.slice(3);
                var format = {
                    count: numberFormat(),
                    percent: numberFormat({ style: "percent", maximumFractionDigits: 1 }),
                };

                $("[data-kj-team-people]").textContent = format.count(people.length);
                $("[data-kj-team-commits]").textContent = format.count(total);

                leads.innerHTML = "";
                list.innerHTML = "";

                var rank = 0;

                people.forEach(function (person, index) {
                    var share = total ? person.contributions / total : 0;

                    // contributors with the same number of commits share a rank
                    if (!index || person.contributions !== people[index - 1].contributions) {
                        rank = index + 1;
                    }

                    if (index < 3) {
                        leads.appendChild(teamItem(leadTemplate, person, rank, share, format));
                    } else {
                        list.appendChild(teamItem(itemTemplate, person, rank, share, format));
                    }
                });

                $("[data-kj-team-community-count]", root).textContent = format.count(others.length);
                $("[data-kj-team-community]", root).classList.toggle("d-none", !others.length);
            })
            .catch(function () {
                $$("[data-kj-team-section]", root).forEach(function (section) {
                    section.remove();
                });
                $("[data-kj-team-error]", root).classList.remove("d-none");
            })
            .then(function () {
                $("[data-kj-team-loading]", root).remove();
                root.setAttribute("aria-busy", "false");
            });
    }

    /* --- Kleeja blog ------------------------------------------------------------------------ */

    // only web links from the feed may become an href or an image source
    function webUrl(value) {
        try {
            var url = new URL(String(value || ""), window.location.href);

            return url.protocol === "https:" || url.protocol === "http:" ? url.href : "";
        } catch (error) {
            return "";
        }
    }

    // the admin's language when the feed has posts in it, English otherwise
    function blogPosts(feed, language) {
        var own = feed && Array.isArray(feed[language]) ? feed[language] : [];

        if (own.length) {
            return { language: language, posts: own };
        }

        return { language: "en", posts: feed && Array.isArray(feed.en) ? feed.en : [] };
    }

    // "2026-09-26" in the posts' language, with Latin digits like the rest of the panel
    function blogDate(element, value, language) {
        var match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ""));

        if (!match) {
            element.remove();
            return;
        }

        element.dateTime = match[0];
        element.textContent = match[0];

        try {
            element.textContent = new Intl.DateTimeFormat(language + "-u-nu-latn", {
                dateStyle: "long",
                timeZone: "UTC",
            }).format(new Date(Date.UTC(+match[1], match[2] - 1, +match[3])));
        } catch (error) {
            // keep the ISO date
        }
    }

    function blogNotice(list, text) {
        var column = document.createElement("div");
        var alert = document.createElement("div");

        column.className = "col-12";
        alert.className = "alert alert-info mb-0";
        alert.textContent = text;
        column.appendChild(alert);
        list.appendChild(column);
    }

    function blogItem(template, post, language, rtl) {
        var item = template.content.firstElementChild.cloneNode(true);
        var card = $(".card", item);
        var link = $("[data-kj-blog-link]", item);
        var image = webUrl(post.image);
        var author = post.author || {};
        var authorLink = $("[data-kj-blog-author-link]", item);
        var avatar = webUrl(author.avatar);

        // English posts in an Arabic panel still read left to right
        card.lang = language;
        card.dir = rtl ? "rtl" : "ltr";

        link.textContent = String(post.title || "");
        link.href = webUrl(post.url) || "#";
        $("[data-kj-blog-desc]", item).textContent = String(post.description || "");
        blogDate($("[data-kj-blog-date]", item), post.date, language);

        if (image) {
            $("[data-kj-blog-image]", item).src = image;
            $("[data-kj-blog-image]", item).addEventListener("error", function () {
                $("[data-kj-blog-media]", item).remove();
            });
        } else {
            $("[data-kj-blog-media]", item).remove();
        }

        if (author.name) {
            authorLink.textContent = String(author.name);

            if (webUrl(author.url)) {
                authorLink.href = webUrl(author.url);
            } else {
                authorLink.removeAttribute("href");
            }

            if (avatar) {
                $("[data-kj-blog-avatar]", item).src = avatar;
            } else {
                $("[data-kj-blog-avatar]", item).remove();
            }
        } else {
            $("[data-kj-blog-author]", item).remove();
        }

        return item;
    }

    // posts are read from kleeja.net by the browser and written with textContent only
    function initBlog() {
        var list = $("[data-kj-blog]");
        var template = $("#kjBlogItem");

        if (!list || !template || !window.fetch) {
            return;
        }

        var language = (list.dataset.kjBlogLang || "en").toLowerCase();

        fetch(list.dataset.kjBlog, {
            credentials: "omit",
            referrerPolicy: "no-referrer",
            headers: { Accept: "application/json" },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("blog");
                }

                return response.json();
            })
            .then(function (feed) {
                var found = blogPosts(feed, language);
                var rtl = found.language === language && document.documentElement.dir === "rtl";
                var posts = found.posts.filter(function (post) {
                    return post && post.title;
                });

                list.innerHTML = "";

                if (!posts.length) {
                    blogNotice(list, list.dataset.kjBlogEmpty);
                    return;
                }

                // newest first, whatever order the feed uses
                posts
                    .sort(function (a, b) {
                        return String(b.date || "").localeCompare(String(a.date || ""));
                    })
                    .forEach(function (post) {
                        list.appendChild(blogItem(template, post, found.language, rtl));
                    });
            })
            .catch(function () {
                list.innerHTML = "";
                blogNotice(list, list.dataset.kjBlogError || msg("kjMsgError", "Error, try again."));
            })
            .then(function () {
                list.setAttribute("aria-busy", "false");
            });
    }

    /* --- Image control ---------------------------------------------------------------------- */

    function initImagePreview() {
        var modal = document.getElementById("kjImagePreview");

        if (!modal) {
            return;
        }

        modal.addEventListener("show.bs.modal", function (event) {
            var trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            var card = trigger.closest("[data-kj-row]");
            var meta = card ? $("[data-kj-meta]", card) : null;
            var target = $("[data-kj-preview-meta]", modal);

            $("[data-kj-preview-img]", modal).src = trigger.getAttribute("href");
            $("[data-kj-preview-open]", modal).href = trigger.getAttribute("href");
            $("[data-kj-preview-title]", modal).textContent = trigger.dataset.kjPreviewTitle || "";

            if (target) {
                target.innerHTML = meta ? meta.innerHTML : "";
            }
        });

        modal.addEventListener("hidden.bs.modal", function () {
            $("[data-kj-preview-img]", modal).removeAttribute("src");
        });
    }

    function initSearchOne() {
        var wrap = $("[data-kj-search-one]");

        if (!wrap) {
            return;
        }

        var select = $("select", wrap);

        document.addEventListener("kj:selection", function (event) {
            var single = event.detail.checked.length === 1;

            wrap.classList.toggle("d-none", !single);

            if (!single) {
                select.selectedIndex = 0;
            }
        });

        select.addEventListener("change", function () {
            var checked = $('[data-kj-check="del"]:checked');

            if (!checked || !select.value || select.value === "0") {
                return;
            }

            var source = document.getElementById((select.value === "1" ? "ip_" : "user_") + checked.value);

            window.open(
                decode(wrap.dataset.kjSearchOne) +
                    "&s_input=" +
                    select.value +
                    "&s_value=" +
                    encodeURIComponent(source ? source.textContent.trim() : ""),
                "_blank",
            );

            select.selectedIndex = 0;
        });
    }

    /* --- Kleeja update ------------------------------------------------------------------------ */

    function initUpdater() {
        var root = $("[data-kj-updater]");

        if (!root) {
            return;
        }

        var tile = $("[data-kj-update-icon]", root);
        var title = $("[data-kj-update-title]", root);
        var start = $("[data-kj-update-start]", root);
        var notesWrap = $("[data-kj-update-notes-wrap]", root);
        var steps = $("[data-kj-update-steps]");

        function setTile(state, iconClass) {
            tile.className = "kj-icon-tile is-" + state;
            tile.innerHTML = "";
            tile.appendChild(icon("fa-solid " + iconClass));
        }

        fetchJSON("./?cp=p_check_update&smt=check&_ajax_=1")
            .then(function (data) {
                var parts = splitOnce(data.content, ":::");
                var code = parts[0];

                if (code === "2") {
                    // text ::--x--:: release notes ::--x--:: date
                    var info = parts[1].split("::--x--::");
                    setTile("warning", "fa-arrow-up-from-bracket");
                    title.innerHTML = info[0];
                    $("[data-kj-update-notes]", root).innerHTML = info[1] || "";
                    $("[data-kj-update-date]", root).textContent = (info[2] || "").replace("T", " ").replace("Z", "");
                    notesWrap.classList.remove("d-none");
                    start.classList.remove("d-none");
                } else if (code === "0") {
                    setTile("success", "fa-circle-check");
                    title.innerHTML = parts[1];
                } else {
                    setTile("warning", "fa-triangle-exclamation");
                    title.innerHTML = parts[1];
                }
            })
            .catch(function (error) {
                setTile("warning", "fa-triangle-exclamation");
                title.textContent = root.dataset.kjCheckError || errorText(error);
            })
            .then(function () {
                if (root.dataset.kjAfterCheck) {
                    window.location.href = root.dataset.kjAfterCheck;
                }
            });

        function runStep(step) {
            var item = $('[data-kj-step="' + step + '"]', steps);

            if (!item) {
                return;
            }

            var state = $("[data-kj-step-state]", item);
            var message = $("[data-kj-step-message]", item);

            item.className = "list-group-item is-running";
            state.innerHTML = "";
            state.appendChild(spinner());

            fetchJSON("./?cp=p_check_update&smt=update" + step + "&" + decode(root.dataset.kjFormKey) + "&_ajax_=1")
                .then(function (data) {
                    var parts = splitOnce(data.content, ":::");
                    var ok = parts[0] === "1";

                    item.className = "list-group-item " + (ok ? "is-done" : "is-failed");
                    state.innerHTML = "";
                    state.appendChild(
                        icon(ok ? "fa-solid fa-circle-check text-success" : "fa-solid fa-circle-xmark text-danger"),
                    );

                    if (parts[1]) {
                        message.className = "alert small py-2 mt-2 mb-0 " + (ok ? "alert-success" : "alert-danger");
                        message.textContent = parts[1];
                    }

                    if (ok) {
                        window.setTimeout(function () {
                            runStep(step + 1);
                        }, 500);
                    }
                })
                .catch(function (error) {
                    item.className = "list-group-item is-failed";
                    state.innerHTML = "";
                    state.appendChild(icon("fa-solid fa-circle-xmark text-danger"));
                    message.className = "alert alert-danger small py-2 mt-2 mb-0";
                    message.innerHTML = errorText(error);
                });
        }

        start.addEventListener("click", function () {
            start.classList.add("d-none");
            notesWrap.classList.add("d-none");
            steps.classList.remove("d-none");
            runStep(1);
        });
    }

    /* --- Store: install or update plugins and styles ---------------------------------------- */

    function initStore() {
        var buttons = $$("[data-kj-download]");

        if (!buttons.length) {
            return;
        }

        function download(button) {
            var name = button.dataset.kjDownload;
            var status = $('[data-kj-download-status="' + name + '"]');
            // a list row shows the answer as a badge; a store card is narrow, so it asks for an alert
            var asAlert = status.hasAttribute("data-kj-download-alert");

            function look(tone) {
                return asAlert ? "alert alert-" + tone + " small py-2 px-3 mt-3 mb-0" : "kj-status kj-badge is-" + tone;
            }

            button.disabled = true;
            status.className = asAlert
                ? look("secondary") + " d-flex align-items-center gap-2"
                : "kj-status text-body-secondary d-inline-flex align-items-center gap-2";
            status.innerHTML = "";
            status.appendChild(spinner());
            status.appendChild(document.createTextNode(" " + (button.dataset.kjLoading || "")));

            return fetchJSON(button.dataset.kjDownloadUrl + encodeURIComponent(name) + "&_ajax_=1")
                .then(function (data) {
                    var parts = splitOnce(data.content, ":::");
                    status.className = look(parts[0] === "1" ? "success" : "warning");
                    status.innerHTML = parts[1];
                })
                .catch(function (error) {
                    button.disabled = false;
                    status.className = look("danger");
                    status.innerHTML = errorText(error);
                });
        }

        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                download(button);
            });
        });

        $$("[data-kj-download-all]").forEach(function (all) {
            all.addEventListener("click", function () {
                var queue = buttons.filter(function (button) {
                    return !button.disabled;
                });

                all.disabled = true;
                queue.forEach(function (button) {
                    button.disabled = true;
                });

                (function next() {
                    var button = queue.shift();

                    if (button) {
                        download(button).then(function () {
                            window.setTimeout(next, 500);
                        });
                    }
                })();
            });
        });
    }

    /* --- Help ------------------------------------------------------------------------------ */

    // lower case, without Arabic diacritics and with one form of each letter, so "إضافة" finds "اضافه"
    function helpNormalize(text) {
        return String(text || "")
            .toLowerCase()
            .replace(/[ً-ٰٟـ]/g, "")
            .replace(/[أإآٱ]/g, "ا")
            .replace(/ى/g, "ي")
            .replace(/ة/g, "ه")
            .replace(/\s+/g, " ")
            .trim();
    }

    function initHelp() {
        var root = $("[data-kj-help]");

        if (!root) {
            return;
        }

        var input = $("[data-kj-help-search]", root);
        var status = $("[data-kj-help-status]", root);
        var empty = $("[data-kj-help-empty]", root);
        var toc = $("[data-kj-help-toc]", root);
        var narrow = window.matchMedia("(max-width: 991.98px)");
        var guides = $$("[data-kj-help-guide]", root).map(function (element) {
            return {
                element: element,
                text: helpNormalize(element.textContent),
                link: $('[data-kj-help-link="' + element.id.replace(/^help-/, "") + '"]', root),
            };
        });

        // on small screens the contents start folded above the guides
        if (toc && narrow.matches) {
            toc.open = false;
        }

        function search() {
            var words = helpNormalize(input.value).split(" ").filter(Boolean);
            var shown = 0;

            guides.forEach(function (guide) {
                var match = words.every(function (word) {
                    return guide.text.indexOf(word) !== -1;
                });

                guide.element.hidden = !match;

                if (guide.link) {
                    guide.link.parentNode.hidden = !match;
                }

                if (match) {
                    shown++;

                    // open the answers that hold the words
                    if (words.length) {
                        $$("details", guide.element).forEach(function (details) {
                            var text = helpNormalize(details.textContent);

                            details.open = words.every(function (word) {
                                return text.indexOf(word) !== -1;
                            });
                        });
                    }
                }
            });

            // a group with nothing left to show hides its title too
            $$("[data-kj-help-group]", root).forEach(function (group) {
                group.hidden = !$("[data-kj-help-guide]:not([hidden])", group);
            });

            $$("[data-kj-help-toc-group]", root).forEach(function (group) {
                group.hidden = !$(":scope > ul > li:not([hidden])", group);
            });

            empty.hidden = shown > 0;
            status.textContent = words.length ? (status.dataset.kjHelpCount || "%d").replace("%d", shown) : "";
        }

        input.addEventListener("input", search);

        input.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && input.value) {
                event.preventDefault();
                input.value = "";
                search();
            }
        });

        $("[data-kj-help-clear]", root).addEventListener("click", function () {
            input.value = "";
            search();
            input.focus();
        });

        // "/" jumps to the search box, unless the reader is typing somewhere
        document.addEventListener("keydown", function (event) {
            var target = event.target;

            if (
                event.key !== "/" ||
                event.ctrlKey ||
                event.metaKey ||
                event.altKey ||
                /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName) ||
                target.isContentEditable
            ) {
                return;
            }

            event.preventDefault();
            input.focus();
        });

        // a link in the contents folds them on small screens, the guide takes the whole width
        if (toc) {
            toc.addEventListener("click", function (event) {
                if (event.target.closest("a") && narrow.matches) {
                    toc.open = false;
                }
            });
        }

        // the contents mark the guide being read
        if ("IntersectionObserver" in window) {
            var current = null;
            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        var guide = guides.filter(function (item) {
                            return item.element === entry.target;
                        })[0];

                        if (!guide || !guide.link || guide.link === current) {
                            return;
                        }

                        if (current) {
                            current.classList.remove("active");
                            current.removeAttribute("aria-current");
                        }

                        current = guide.link;
                        current.classList.add("active");
                        current.setAttribute("aria-current", "true");

                        // keep it in view inside the scrolling contents
                        if (!narrow.matches && toc) {
                            var box = toc.getBoundingClientRect();
                            var link = current.getBoundingClientRect();

                            if (link.top < box.top || link.bottom > box.bottom) {
                                toc.scrollTop += link.top - box.top - box.height / 2;
                            }
                        }
                    });
                },
                { rootMargin: "-15% 0px -70% 0px" },
            );

            guides.forEach(function (guide) {
                observer.observe(guide.element);
            });
        }

        // the help button of a page opens here, on the guide of that page
        var focus = root.dataset.kjHelpFocus && document.getElementById("help-" + root.dataset.kjHelpFocus);

        if (focus && !window.location.hash) {
            // jump like an anchor would, Bootstrap makes scrolling smooth
            document.documentElement.style.scrollBehavior = "auto";
            focus.scrollIntoView({ block: "start" });
            document.documentElement.style.scrollBehavior = "";
            focus.focus({ preventScroll: true });
            focus.classList.add("is-focused");
            focus.addEventListener(
                "animationend",
                function () {
                    focus.classList.remove("is-focused");
                },
                { once: true },
            );
        }
    }

    /* --- Boot --------------------------------------------------------------------------------- */

    upgradeLegacyDataApi();
    initBootstrapParts();
    initColorMode();
    initConfirm();
    initCounters();
    initBulkSelection();
    initForms();
    initStartBoxes();
    initQuickLanguage();
    initStatsChart();
    initTeam();
    initBlog();
    initImagePreview();
    initSearchOne();
    initUpdater();
    initStore();
    initHelp();
})();
