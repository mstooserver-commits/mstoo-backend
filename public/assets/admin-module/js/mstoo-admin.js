(function ($) {
    "use strict";

    var body = $("body");
    var STORAGE_KEY = "mstoo-aside-folded-v2";
    var DESKTOP_MIN = 1200;
    var lastDesktop = null;

    function isDesktop() {
        return window.innerWidth >= DESKTOP_MIN;
    }

    function overlay() {
        return $(".offcanvas-overlay");
    }

    function isExpanded() {
        if (isDesktop()) {
            return !body.hasClass("aside-folded");
        }
        return body.hasClass("aside-open");
    }

    function syncToggleUi() {
        var expanded = isExpanded();
        $(".aside-toggle").attr("aria-expanded", expanded ? "true" : "false");
        $(".aside-toggle .material-icons").each(function () {
            $(this).text(expanded ? "menu_open" : "menu");
        });
    }

    function applyStoredFold() {
        if (isDesktop()) {
            if (localStorage.getItem(STORAGE_KEY) === "1") {
                body.addClass("aside-folded");
            }
            body.removeClass("aside-open");
            overlay().removeClass("aside-active");
        } else {
            body.removeClass("aside-folded open-aside-folded");
        }
        lastDesktop = isDesktop();
        syncToggleUi();
    }

    applyStoredFold();

    $(document).on("click", ".aside-toggle, .offcanvas-overlay", function () {
        window.setTimeout(function () {
            if (isDesktop()) {
                localStorage.setItem(STORAGE_KEY, body.hasClass("aside-folded") ? "1" : "0");
            }
            syncToggleUi();
        }, 0);
    });

    $(document).on("keydown", function (event) {
        if (event.key === "Escape" && body.hasClass("aside-open")) {
            body.removeClass("aside-open");
            overlay().removeClass("aside-active");
            syncToggleUi();
        }
    });

    var resizeTimer;
    $(window).on("resize.mstooAside", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (isDesktop() !== lastDesktop) {
                applyStoredFold();
            } else {
                syncToggleUi();
            }
        }, 80);
    });

    function foldedTooltips() {
        var folded = body.hasClass("aside-folded") && !body.hasClass("open-aside-folded");
        $(".aside .aside-body a").each(function () {
            if (folded) {
                $(this).attr("data-bs-toggle", "tooltip");
                $(this).attr("data-bs-placement", "right");
                if (!$(this).attr("title")) {
                    $(this).attr("title", $.trim($(this).text()));
                }
            } else {
                $(this).removeAttr("data-bs-toggle");
            }
        });
    }

    foldedTooltips();
    $(window).on("resize.mstooAsideTips", foldedTooltips);
})(jQuery);
