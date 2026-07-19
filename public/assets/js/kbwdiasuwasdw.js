
     


    document.addEventListener("DOMContentLoaded", function() {
        let e = document.getElementById("dontshowinfoo"),
            t = document.querySelector(".popup-structuree"),
            o = document.querySelectorAll(".close-popup-buttonn");

        function n() {
            t.style.display = "none", e.checked && localStorage.setItem("hidePopupp", "true")
        }
        o.forEach(e => {
            e.addEventListener("click", n)
        }), "true" === localStorage.getItem("hidePopupp") ? t.style.display = "none" : t.style.display = "block"
    }), document.addEventListener("DOMContentLoaded", function() {
        let e = document.querySelectorAll(".popup-slidee"),
            t = !1;
        0 < e.length && (e[0].classList.add("show"), t = !0), document.addEventListener("click", function(o) {
            t && (Array.from(e).some(e => e.contains(o.target)) || (e.forEach(e => {
                e.classList.remove("show")
            }), t = !1))
        })
    });
    
    document.addEventListener("DOMContentLoaded", function() {
        let t = document.querySelectorAll("[data-tabs-toggle]"),
            e = document.querySelectorAll("[data-tabs-content]"),
            a = t[0],
            l = document.querySelector(a.getAttribute("data-tabs-toggle"));
        a.classList.add("active"), a.style.backgroundColor = "var(--warna_3)", l.style.display = "block", t.forEach(function(a) {
            a.addEventListener("click", function() {
                e.forEach(function(t) {
                    t.style.display = "none"
                }), t.forEach(function(t) {
                    t.classList.remove("active"), t.style.backgroundColor = "var(--warna_0)"
                });
                let a = this.getAttribute("data-tabs-toggle"),
                    l = document.querySelector(a);
                l.style.display = "block", this.classList.add("active"), this.style.backgroundColor = "var(--warna_3)"
            })
        })
    });
    
    // Scroll buttons - only on home page
    const scrollLeft = document.getElementById('scrollLeft');
    const scrollRight = document.getElementById('scrollRight');
    const tabsContainer = document.querySelector('.tabs-container');

    if (scrollLeft && tabsContainer) {
        scrollLeft.addEventListener('click', function() {
            tabsContainer.scrollBy({
                left: -100,
                behavior: 'smooth'
            });
        });
    }

    if (scrollRight && tabsContainer) {
        scrollRight.addEventListener('click', function() {
            tabsContainer.scrollBy({
                left: 100,
                behavior: 'smooth'
            });
        });
    }
    

    //loading hero - only on home page
    const heroElement = document.getElementById("hero");
    const herooElement = document.getElementById("heroo");
    const fsElement = document.getElementById("fs");

    if (heroElement) {
        heroElement.classList.remove("hidden");
    }

    if (herooElement) {
        herooElement.classList.add("swiper-wrapper");
        herooElement.classList.add("patih");
    }

    if (fsElement) {
        fsElement.classList.remove("hidden");
    }

//tampilkan lainnya - only on home page
 document.addEventListener("DOMContentLoaded", () => {
        const panelTopup = document.getElementById("game");
        if (!panelTopup) return; // Exit if element doesn't exist

        const items = panelTopup.querySelectorAll(".category-item");
        const showAllButton = document.getElementById("showAllButton2");
        const buttonContainer = document.getElementById("buttonContainer2");

        if (!showAllButton || !buttonContainer) return; // Exit if buttons don't exist

        let visibleItems = 6;

        function updateVisibility() {
            items.forEach((item, index) => {
                item.style.display = index < visibleItems ? "block" : "none";
            });

            if (items.length <= 6) {
                buttonContainer.style.display = "none";
            } else {
                buttonContainer.style.display = visibleItems >= items.length ? "none" : "block";
            }
        }

        updateVisibility();

        showAllButton.addEventListener("click", () => {
            visibleItems += 6;
            updateVisibility();
        });
    });
