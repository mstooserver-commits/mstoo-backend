(function ($) {
    "use strict";

    var body = $("body");
    if (localStorage.getItem("mstoo-aside-folded") === "1" && $(window).width() >= 992) {
        body.addClass("aside-folded");
    }

    $(document).on("click", ".aside-toggle", function () {
        if ($(window).width() >= 992) {
            localStorage.setItem("mstoo-aside-folded", body.hasClass("aside-folded") ? "1" : "0");
        }
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
    $(window).on("resize", foldedTooltips);
})(jQuery);
