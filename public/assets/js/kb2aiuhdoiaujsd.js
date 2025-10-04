document.addEventListener("DOMContentLoaded", function() {
    let closePopupButton = document.getElementById("closePopupButton");
    let popupStructure = document.querySelector(".popup-structure");

    if (closePopupButton) {
        closePopupButton.addEventListener("click", function() {
            popupStructure.style.display = "none";
            localStorage.setItem("hidePopup", "true");
        });
    }

    if ("true" === localStorage.getItem("hidePopup")) {
        popupStructure.style.display = "none";
    }

    document.getElementById("specialList").addEventListener("click", function(event) {
        let target = event.target.closest(".product-list");
        if (target) {
            if (target.getAttribute("data-layanan").toLowerCase().includes("weekly diamond pass")) {
                popupStructure.style.display = "block";
            }
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {
    let popupSlides = document.querySelectorAll(".popup-slide");
    let isOutsideClick = false;

    if (popupSlides.length > 0) {
        popupSlides[0].classList.add("show");
        isOutsideClick = true;
    }

    document.addEventListener("click", function(event) {
        if (!Array.from(popupSlides).some(slide => slide.contains(event.target))) {
            isOutsideClick = true;
        }
    });
});