document.addEventListener("DOMContentLoaded", function() {
    var closePopupButtonId = window.closePopupButtonId || "closeWeeklyDiamondPopupButton";
    var popupStructureClass = window.popupStructureClass || "#weeklyDiamondPassPopup";
    var hideKey = "hideWeeklyDiamondPassPopup";

    var popupStructure = document.querySelector(popupStructureClass) || document.getElementById("weeklyDiamondPassPopup") || document.querySelector(".popup-structure");

    if (!popupStructure) {
        return;
    }

    function hidePopup(rememberDismiss) {
        popupStructure.classList.remove("show", "flex");
        popupStructure.classList.add("hidden");
        popupStructure.setAttribute("aria-hidden", "true");
        popupStructure.classList.remove("show");
        if (rememberDismiss) {
            localStorage.setItem(hideKey, "true");
        }
    }

    function showPopup() {
        popupStructure.classList.remove("hidden");
        popupStructure.classList.add("flex", "show");
        popupStructure.setAttribute("aria-hidden", "false");
        popupStructure.classList.add("show");
        localStorage.removeItem(hideKey);
    }

    var closePopupButton = document.getElementById(closePopupButtonId) || document.getElementById("closePopupButton");
    if (closePopupButton) {
        closePopupButton.addEventListener("click", function(event) {
            event.preventDefault();
            hidePopup(true);
        });
    }

    if ("true" === localStorage.getItem(hideKey)) {
        hidePopup(false);
    }

    document.addEventListener("click", function(event) {
        var closeTarget = event.target.closest("#" + closePopupButtonId + ", #closePopupButton");
        if (closeTarget) {
            event.preventDefault();
            hidePopup(true);
            return;
        }

        var target = event.target.closest(".product-list");
        if (!target) {
            return;
        }

        var layanan = String(target.getAttribute("data-layanan") || "").toLowerCase();
        if (layanan.includes("weekly diamond pass")) {
            showPopup();
        }
    });
});
