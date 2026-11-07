/* landing-app.js — hash 2440f351 */
/* ===== tema awal (anti-flicker) ===== */
(function(){
    try{
      var t = localStorage.getItem("topupin.theme");
      if(t !== "dark" && t !== "light"){
        t = (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) ? "dark" : "light";
      }
      if(t === "dark") document.documentElement.setAttribute("data-theme","dark");
    }catch(e){}
  })();

/* ===== scroll halus + tema ===== */
(function(){
  if(!window.gsap||!window.ScrollToPlugin) return;
  document.documentElement.style.scrollBehavior = "auto";
  if(window.matchMedia && matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  gsap.registerPlugin(ScrollToPlugin);
  function offset(){ var n=document.querySelector(".nav"); return (n?n.offsetHeight:0)+12; }
  document.addEventListener("click",function(e){
    var a=e.target&&e.target.closest?e.target.closest('a[href^="#"]'):null;
    if(!a) return;
    var href=a.getAttribute("href");
    if(!href||href==="#") return;
    var t=document.querySelector(href);
    if(!t) return;
    e.preventDefault();
    var y=t.getBoundingClientRect().top+window.pageYOffset-offset();
    if(y<0) y=0;
    gsap.to(window,{duration:1.05,ease:"power3.out",scrollTo:{y:y,autoKill:true}});
    if(window.history&&history.pushState) history.pushState(null,"",href);
  });
})();

/* ===== halaman: harga, tab langkah, FAQ, pembayaran, marquee ===== */
(function(){
  "use strict";

  var PAY = [
    { n: "QRIS",       s: "assets/landing/payments/qris.svg" },
    { n: "GoPay",      s: "assets/landing/payments/gopay.svg" },
    { n: "OVO",        s: "assets/landing/payments/ovo.svg" },
    { n: "DANA",       s: "assets/landing/payments/dana.svg" },
    { n: "ShopeePay",  s: "assets/landing/payments/shopeepay.jpg" },
    { n: "SeaBank",    s: "assets/landing/payments/seabank.svg" },
    { n: "BCA Virtual Account", s: "assets/landing/payments/bca.svg" },
    { n: "Mandiri",    s: "assets/landing/payments/mandiri.svg" },
    { n: "Visa",       s: "assets/landing/payments/visa.svg" },
    { n: "Mastercard", s: "assets/landing/payments/mastercard.svg" }
  ];
  var state = { period: "3bulan", tab: "ringkasan" };

  function $(id){ return document.getElementById(id); }
  function store(k, v){ try{ localStorage.setItem(k, v); }catch(e){} }
  function load(k){ try{ return localStorage.getItem(k); }catch(e){ return null; } }

  /* Theme: terang / gelap */
  function applyTheme(theme, persist){
    var dark = theme === "dark";
    if (dark) document.documentElement.setAttribute("data-theme", "dark");
    else document.documentElement.removeAttribute("data-theme");
    if (persist) store("topupin.theme", theme);
    var btn = $("themeToggle");
    if (btn){
      btn.setAttribute("aria-pressed", String(dark));
      btn.setAttribute("aria-label", dark ? "Ganti ke tema terang" : "Ganti ke tema gelap");
    }
  }
  var themeBtn = $("themeToggle");
  if (themeBtn){
    applyTheme(document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light", false);
    themeBtn.addEventListener("click", function(){
      var isDark = document.documentElement.getAttribute("data-theme") === "dark";
      applyTheme(isDark ? "light" : "dark", true);
    });
  }

  /* Hero: efek mengetik memakai Typed.js (github.com/mattboldt/typed.js) */
  var typedEl = $("typed");
  if (typedEl){
    var PHRASES = [
      "Tanpa Ribet, Mulai Dari Nol",
      "Tanpa Coding Yang Rumit",
      "Dengan Transaksi Otomatis",
      "Pakai Brand Kamu Sendiri",
      "Siap Jualan 24/7",
      "Tanpa Membangun Sistem Dari Nol"
    ];
    if (window.Typed){
      new window.Typed(typedEl, {
        strings: PHRASES,
        typeSpeed: 62,
        backSpeed: 32,
        backDelay: 1900,
        startDelay: 300,
        loop: true,
        smartBackspace: false,
        showCursor: false
      });
    } else {
      (function (){
        var p = 0, c = 0, deleting = false;
        function tick(){
          var word = PHRASES[p];
          typedEl.textContent = word.slice(0, c);
          var delay = deleting ? 45 : 92;
          if (!deleting && c === word.length){ deleting = true; delay = 1700; }
          else if (deleting && c === 0){ deleting = false; p = (p + 1) % PHRASES.length; delay = 420; }
          else { c += deleting ? -1 : 1; }
          setTimeout(tick, delay);
        }
        tick();
      })();
    }
  }

  /* Payments strip */
  var payRow = $("payRow");
  if (payRow){
    payRow.innerHTML = PAY.map(function(p){
      var id = "pay-" + p.n.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
      return '<span class="paychip" data-od-id="' + id + '"><img src="' + p.s + '" alt="' + p.n + '" height="20" loading="lazy" decoding="async"></span>';
    }).join("");
  }

  /* Pricing toggle */
  function applyPeriod(period){
    state.period = period;
    var isYear = period === "1tahun";
    $("billQuarter").setAttribute("aria-pressed", String(!isYear));
    $("billYearly").setAttribute("aria-pressed", String(isYear));
    var key = isYear ? "y" : "q";
    Array.prototype.forEach.call(document.querySelectorAll(".plan .was"), function(el){
      var v = el.getAttribute("data-" + key);
      if (v) el.textContent = v;
    });
    Array.prototype.forEach.call(document.querySelectorAll(".plan .amt"), function(el){
      var v = el.getAttribute("data-" + key);
      if (v) el.textContent = v;
    });
    Array.prototype.forEach.call(document.querySelectorAll(".plan .per"), function(el){
      var v = el.getAttribute("data-" + key);
      if (v) el.textContent = v;
    });
    Array.prototype.forEach.call(document.querySelectorAll(".plan .bill"), function(el){
      var v = el.getAttribute("data-" + key);
      if (v) el.textContent = v;
    });
    store("topupin.period", period);
  }

  ["billQuarter","billYearly"].forEach(function(id){
    var b = $(id);
    if (b) b.addEventListener("click", function(){ applyPeriod(b.getAttribute("data-period")); });
  });

  /* Dashboard tabs */
  function selectTab(tab){
    state.tab = tab;
    Array.prototype.forEach.call(document.querySelectorAll(".atab"), function(b){
      var on = b.getAttribute("data-tab") === tab;
      b.setAttribute("aria-selected", String(on));
      var panel = $(b.getAttribute("aria-controls"));
      if (panel) panel.hidden = !on;
    });
    store("topupin.tab", tab);
  }
  Array.prototype.forEach.call(document.querySelectorAll(".atab"), function(b){
    b.addEventListener("click", function(){ selectTab(b.getAttribute("data-tab")); });
  });

  /* Restore persisted state */
  var savedPeriod = load("topupin.period");
  applyPeriod(savedPeriod === "1tahun" ? "1tahun" : "3bulan");
  var savedTab = load("topupin.tab");
  if (savedTab === "pesanan" || savedTab === "katalog" || savedTab === "ringkasan") selectTab(savedTab);

  /* Hero: elemen game melayang — Motion (motion.dev) + parallax kursor */
  (function(){
    var layer = document.querySelector(".hero-floats");
    if (!layer) return;
    var tiles = Array.prototype.slice.call(layer.querySelectorAll(".float"));
    var M = window.Motion;
    if (!M || typeof M.animate !== "function"){ layer.classList.add("no-motion"); return; }
    try { if (M.MotionGlobalConfig) M.MotionGlobalConfig.skipAnimations = false; } catch(e){}
    var easeOut = (typeof M.easeOut === "function") ? M.easeOut : "ease-out";
    tiles.forEach(function(el, i){
      var inner = el.querySelector(".float-in");
      if (!inner) return;
      var amp = 9 + (i % 3) * 3;
      var dur = 4.4 + (i % 4) * 0.8;
      var dir = (i % 2) ? 1 : -1;
      M.animate(inner, { opacity: [0, 1], scale: [0.5, 1] },
        { duration: 0.55, delay: 0.12 + i * 0.05, easing: easeOut });
      M.animate(inner, { y: [0, -amp, 0], rotate: [0, dir * 3, 0] },
        { duration: dur, repeat: Infinity, easing: "ease-in-out", delay: i * 0.14 });
    });
    var hero = document.querySelector(".hero");
    if (hero){
      var raf = 0, px = 0, py = 0;
      var apply = function(){
        raf = 0;
        tiles.forEach(function(el){
          var d = parseFloat(el.getAttribute("data-depth")) || 1;
          el.style.transform = "translate3d(" + (px * 9 * d).toFixed(2) + "px," + (py * 7 * d).toFixed(2) + "px,0)";
        });
      };
      hero.addEventListener("pointermove", function(e){
        var r = hero.getBoundingClientRect();
        px = ((e.clientX - r.left) / r.width - 0.5) * 2;
        py = ((e.clientY - r.top) / r.height - 0.5) * 2;
        if (!raf) raf = requestAnimationFrame(apply);
      });
      hero.addEventListener("pointerleave", function(){
        px = 0; py = 0; if (!raf) raf = requestAnimationFrame(apply);
      });
    }
  })();

  /* Cara kerja: timeline 3D yang tersinkron dengan scroll + parallax kursor */
  (function(){
    var section = $("cara");
    if (!section) return;
    var items = Array.prototype.slice.call(section.querySelectorAll(".tl-item"));
    var objs = Array.prototype.slice.call(section.querySelectorAll(".obj"));
    var fill = section.querySelector(".tl-rail-fill");
    if (!items.length || !objs.length) return;
    var current = -1;

    function setActive(i){
      if (i === current) return;
      current = i;
      items.forEach(function(it, k){
        it.classList.toggle("is-active", k === i);
        it.classList.toggle("is-done", k < i);
      });
      objs.forEach(function(o, k){ o.classList.toggle("is-active", k === i); });
      if (fill) fill.style.height = (i / (items.length - 1) * 100).toFixed(2) + "%";
    }

    function compute(){
      var line = window.innerHeight * 0.42;
      var best = 0;
      for (var i = 0; i < items.length; i++){
        if (items[i].getBoundingClientRect().top <= line) best = i;
      }
      setActive(best);
    }

    var ticking = false;
    function onScroll(){
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function(){ ticking = false; compute(); });
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);

    items.forEach(function(it, i){
      var btn = it.querySelector(".tl-node");
      if (btn) btn.addEventListener("click", function(){ setActive(i); });
      it.addEventListener("click", function(e){ if (e.target === it) setActive(i); });
    });

    var scene = section.querySelector(".scene");
    var tilt = section.querySelector(".scene-tilt");
    if (scene && tilt){
      scene.addEventListener("pointermove", function(e){
        var r = scene.getBoundingClientRect();
        var nx = (e.clientX - r.left) / r.width - 0.5;
        var ny = (e.clientY - r.top) / r.height - 0.5;
        tilt.style.setProperty("--ry", (nx * 14).toFixed(2) + "deg");
        tilt.style.setProperty("--rx", (-ny * 10).toFixed(2) + "deg");
      });
      scene.addEventListener("pointerleave", function(){
        tilt.style.setProperty("--ry", "-6deg");
        tilt.style.setProperty("--rx", "0deg");
      });
    }

    if ("IntersectionObserver" in window){
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){ if (en.isIntersecting) compute(); });
      }, { threshold: [0, 0.2, 0.45, 0.7, 1] });
      io.observe(section);
    }
    compute();
  })();
})();