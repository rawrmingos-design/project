document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('[name="nominal"]').forEach(function (e) {
        e.addEventListener("click", function () {
            let e;
            (e = document.getElementById("section-payment-channel")) && e.scrollIntoView({
                behavior: "smooth"
            })
        })
    })
});
var listGroupItems = document.querySelectorAll(".method-list");
listGroupItems.forEach(function (e) {
    e.addEventListener("click", function () {
        var e = this.querySelector(".text-xs").textContent.trim();
        document.querySelector("#pesan").textContent = e, showSelectedElement()
    })
}), window.onload = function () {
    var e = document.getElementById("nomor"),
        t = localStorage.getItem("savedNumber");
    t && (e.value = t), e.addEventListener("input", function () {
        localStorage.setItem("savedNumber", e.value)
    })
}, document.addEventListener("DOMContentLoaded", function () {
    let e = document.getElementById("section-payment-channel"),
        t = document.getElementById("whatsappp");

    function n(e, t) {
        let n = e.getBoundingClientRect(),
            o = window.scrollY || window.pageYOffset,
            i = n.top + o - window.innerHeight / 2 + n.height / 2;
        window.scrollTo({
            top: i,
            behavior: "smooth"
        }), t && "function" == typeof t && setTimeout(t, 100)
    }
    document.querySelectorAll(".bg-product").forEach(function (t) {
        t.addEventListener("click", function () {
            n(e)
        })
    }), document.querySelectorAll(".method-list").forEach(function (o) {
        o.addEventListener("click", function () {
            n(e, function () {
                n(t)
            })
        })
    })
}), window.addEventListener("load", function () {
    setTimeout(function () {
        document.getElementById("skeleton-loader").style.display = "none", document.getElementById("itemList").classList.remove("hidden")
    }, 1500)
}), window.addEventListener("load", function () {
    setTimeout(function () {
        document.getElementById("skeleton-loaderr").style.display = "none", document.getElementById("paymentList").classList.remove("hidden")
    }, 1500)
});

function showInitialElement() {
    var e = document.querySelector(".initial-element"),
        t = document.querySelector(".selected-element");
    e.style.display = "flex", t.style.display = "none"
}

function showSelectedElement() {
    var e = document.querySelector(".initial-element"),
        t = document.querySelector(".selected-element");
    e.style.display = "none", t.style.display = "flex"
}

function updateSelectedElement(e, t) {
    var l = document.querySelector(".text-xs.font-semibold.selected-order"),
        n = document.querySelector(".text-xs.font-semibold.text-warning.selected-order");
    document.querySelector(".flex.w-full.items-center p.text-xs.italic"), l.textContent = e, n.textContent = t
}

function updateSelectedElements(e) {
    document.querySelector(".text-xs.font-semibold.text-warning.selected-order").textContent = e
}
var listGroupItems = document.querySelectorAll(".method-list");
listGroupItems.forEach(function (e) {
    e.addEventListener("click", function () {
        updateSelectedElements(this.querySelector(".h6") ? this.querySelector(".h6").textContent : this.querySelector(".hargapembayaran").textContent), showSelectedElement()
    })
});
var productListItems = document.querySelectorAll(".product-list");
productListItems.forEach(function (e) {
    e.addEventListener("click", function () {
        var e;
        updateSelectedElement(this.querySelector("#namalayanan").textContent, this.querySelector(".harga") ? this.querySelector(".harga").textContent : this.querySelector(".text-dark.meltih").textContent), showSelectedElement(), document.querySelector(".selected-order").style.display = "block"
    })
}), showInitialElement();



function _0x3285() {
    const e = ["4coIWde", "7zbjMNN", "27BIEiym", "855740aKAnWe", "html", "toLocaleString", "#SALDO", "3664584RzNZiZ", "1779630ZtmYLS", "each", '<span class="text-red-500">Max ', "find", ".method-list", "1804940MjlVqh", "id-ID", "74923CyckEr", "fee_percent", "closest", "removeClass", "input", "fix_fee", "2803064pyXcOZ", "addClass", "disabled", "733335CzHxwm", "prop"];
    return (_0x3285 = function () {
        return e
    })()
}

function _0x5018(e, a) {
    const o = _0x3285();
    return (_0x5018 = function (e, a) {
        return o[e -= 257]
    })(e, a)
}

function changeHarga(e, a) {
    const o = _0x5018;
    e = parseFloat(e), (isNaN(e) || e < 0) && (e = 0);
    let t = formatToRupiah(e);
    $(o(273))[o(271)](t), $[o(276)](a, (function (a, t) {
        const n = o;
        let i = parseFloat(t[n(261)]) || 0,
            s = parseFloat(t[n(257)]) || 0,
            r = parseFloat(t.min_pembelian) || 0,
            l = parseFloat(t.max_pembelian) || 1 / 0,
            c = $("#" + a)[n(258)](n(279)),
            d = "";
        if (e < r ? (d = '<span class="text-red-500">Min ' + formatToRupiah(r) + "</span>", c[n(263)](n(264)), c[n(278)](n(260))[n(266)](n(264), !0)) : e > l ? (d = n(277) + formatToRupiah(l) + "</span>", c[n(263)]("disabled"), c[n(278)](n(260))[n(266)](n(264), !0)) : (c[n(259)](n(264)), c[n(278)]("input")[n(266)](n(264), !1)), d) $("#" + a)[n(271)](d);
        else {
            let o = formatToRupiah(e + i + e * s / 100);
            $("#" + a)[n(271)](o)
        }
    }))
}

function formatToRupiah(e) {
    const a = _0x5018;
    return "number" != typeof e && (e = parseFloat(e)), isNaN(e) && (e = 0), "Rp " + e[a(272)](a(281), {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    })
}

function togglePaymentList() {
    const e = document.querySelectorAll(".melpa-sabled");
    document.querySelectorAll(".product-list").forEach((a => {
        a.addEventListener("click", (function () {
            e.forEach((e => {
                e.classList.remove("disabled")
            }))
        }))
    }))
}

function validateQtyInput(e) {
    e.value.includes("-") && (e.value = e.value.replace("-", "")), e.value < 1 ? e.value = 1 : e.value > 30 && (e.value = 30)
}

function scrollToElement(e) {
    $("html, body").animate({
        scrollTop: $("#" + e).offset().top
    }, 1e3)
}

function showToast(e, a = "error") {
    var o = document.getElementById("react-notif"),
        t = document.createElement("div");
    t.className = "toast", "success" === a && t.classList.add("success");
    var n = document.createElement("div");
    n.className = "toast-icon", n.innerHTML = "success" === a ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" width="16" color="rgba(34, 197, 94, 0.8)"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" width="16" color="rgba(244, 63, 94, 0.8)"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>';
    var i = document.createElement("div");
    i.className = "toast-message", i.textContent = e, t.appendChild(n), t.appendChild(i), o.appendChild(t), setTimeout((function () {
        t.remove()
    }), 3e3)
} ! function (e, a) {
    const o = _0x5018,
        t = _0x3285();
    for (; ;) try {
        if (363415 === -parseInt(o(282)) / 1 + -parseInt(o(270)) / 2 + parseInt(o(275)) / 3 + -parseInt(o(267)) / 4 * (parseInt(o(265)) / 5) + parseInt(o(274)) / 6 + parseInt(o(268)) / 7 * (parseInt(o(262)) / 8) + parseInt(o(269)) / 9 * (-parseInt(o(280)) / 10)) break;
        t.push(t.shift())
    } catch (e) {
        t.push(t.shift())
    }
}(), $(".product-list").off("click").on("click", (function () {
    if ("undefined" != typeof isLoading && isLoading) return;
    let e = $(this).attr("product-id");
    $(".product-list").removeClass("active"), $(this).addClass("active"), $("#nominal").val(e), isLoading = !0, $.ajax({
        url: window.routes.confirmationPrice,
        dataType: "json",
        type: "POST",
        data: {
            _token: window.csrfToken,
            nominal: e
        },
        success: function (e) {
            void 0 !== e.harga && !isNaN(e.harga) && parseFloat(e.harga) >= 0 ? changeHarga(e.harga, e.methods) : console.warn("Invalid price data received.")
        },
        error: function (e, a, o) {
            console.error("AJAX Error: " + a + " - " + o)
        },
        complete: function () {
            isLoading = !1
        }
    })
})), window.onload = togglePaymentList, $(".accordion-button").css("pointer-events", "none"), $(".accordion-header").addClass("hide-payment"), $(".accordion-header").click((function () {
    0 === $(".product-list.active").length && (showToast("Mohon untuk pilih item terlebih dahulu"), scrollToElement("section-nominal"))
})), $(".method-list").click((function () {
    let e = $(this).attr("method-id");
    $(".method-list").removeClass("active"), $(this).addClass("active"), $("#metode").val(e)
})), $("#order-check").on("click", (function () {
    var e = $("#user_id").val(),
        a = $("#zone").val(),
        o = $("#email_joki").val(),
        t = $("#password_joki").val(),
        n = $("#loginvia_joki").val(),
        i = $("#nickname_joki").val(),
        s = $("#request_joki").val(),
        r = $("#catatan_joki").val(),
        l = $("#tglmain_joki").val(),
        c = $("#jambooking_joki").val(),
        d = $("#nominal").val(),
        u = $("#qty").val(),
        m = $("#metode").val(),
        h = $("#nomor").val(),
        p = $("#voucher").val(),
        k = $("#ktg_tipe").val();
    if ("joki" === k || "vilogml" === k) {
        if (!(o && t && n && i)) return void showToast("Silahkan lengkapi semua data Informasi Joki / ML Vilog")
    } else if ("jokigendong" === k) {
        if (!(l && c && n && i)) return void showToast("Silahkan lengkapi semua data Informasi Joki Gendong")
    } else if (!e && !a) return void showToast("Mohon isi UID atau Zone");
    d && m && h ? h ? $.ajax({
        url: window.routes.confirmationUrl,
        dataType: "JSON",
        type: "POST",
        data: {
            _token: window.csrfToken,
            uid: e,
            zone: a,
            service: d,
            payment_method: m,
            nomor: h,
            email_joki: o,
            password_joki: t,
            loginvia_joki: n,
            nickname_joki: i,
            request_joki: s,
            catatan_joki: r,
            tglmain_joki: l,
            jambooking_joki: c,
            qty: u,
            ktg_tipe: k,
            voucher: p
        },
        beforeSend: function () {
            $(".load").addClass("show")
        },
        success: function (f) {
            $(".load").removeClass("show"), f.status ? Swal.fire({
                html: `${f.data}`,
                showCancelButton: !0,
                confirmButtonText: "Pesan Sekarang",
                cancelButtonText: "Batalkan",
                customClass: {
                    htmlContainer: "swal-text"
                }
            }).then((f => {
                if (f.isConfirmed) {
                    var v = $("#nick").text();
                    $.ajax({
                        url: window.routes.orderedUrl,
                        dataType: "JSON",
                        type: "POST",
                        data: {
                            _token: window.csrfToken,
                            nickname: v,
                            uid: e,
                            zone: a,
                            service: d,
                            payment_method: m,
                            nomor: h,
                            voucher: p,
                            email_joki: o,
                            password_joki: t,
                            loginvia_joki: n,
                            nickname_joki: i,
                            request_joki: s,
                            catatan_joki: r,
                            tglmain_joki: l,
                            jambooking_joki: c,
                            qty: u,
                            ktg_tipe: k
                        },
                        beforeSend: function () {
                            $(".load").addClass("show")
                        },
                        success: function (e) {
                            $(".load").removeClass("show"), e.status ? (showToast("Berhasil membuat pesanan!", "success"), window.location = `/id/invoices/${e.order_id}`) : showToast(e.data || e.message || "Terjadi kesalahan", "error")
                        },
                        error: function (e) {
                            $(".load").removeClass("show"), console.log(e)
                        }
                    })
                }
            })) : Swal.fire({
                title: "Oops...",
                text: f.data || "User ID tidak ditemukan.",
                icon: "error"
            })
        },
        error: function (e) {
            $(".load").removeClass("show");
            let msg = (e.responseJSON && e.responseJSON.message) ? e.responseJSON.message : (422 === e.status ? "Pastikan anda sudah mengisi semua data yang diperlukan." : "Terjadi kesalahan. Silakan coba lagi. Pastikan User ID Benar");
            Swal.fire({
                title: "Oops...",
                text: msg,
                icon: "error"
            });
        }
    }) : showToast("Silahkan lengkapi nomor WhatsApp") : showToast("Silahkan lengkapi semua data Informasi Pesanan")
})), $("#btn-check").on("click", (function () {
    var e = $("#voucher").val(),
        a = $("#nominal").val();

    function o() {
        $("#notification").remove()
    }
    $.ajax({
        url: window.routes.checkVoucher,
        dataType: "JSON",
        type: "POST",
        data: {
            _token: window.csrfToken,
            voucher: e,
            service: a
        },
        success: function (e) {
            setTimeout(o, 3e3), showToast("Voucher berhasil digunakan", "success")
        },
        error: function (e) {
            setTimeout(o, 4e3), showToast("Voucher tidak ditemukan", "error")
        }
    }), $(document).on("click", "#closeNotification", (function () {
        o()
    }))
}));

// Real-time Account Check Logic
$(document).ready(function () {
    $("#user_id, #zone").on("blur", function () {
        var uid = $("#user_id").val();
        var zone = $("#zone").val();
        var kategoriKode = window.kategoriKode;

        // Ensure UID is present. Zone is optional depending on game, but we send it anyway.
        if (uid && kategoriKode) {
            $("#nickname-display").text("Checking ID...").removeClass("text-green-500 text-red-500").addClass("text-gray-500");

            $.ajax({
                url: window.routes.checkAccount,
                type: "POST",
                dataType: "JSON",
                data: {
                    _token: window.csrfToken,
                    uid: uid,
                    zone: zone,
                    kategori_kode: kategoriKode
                },
                success: function (response) {
                    if (response.status && response.status.code === 200) {
                        $("#nickname-display").html("Valid: " + response.data.username).removeClass("text-gray-500 text-red-500").addClass("text-green-500");
                    } else {
                        $("#nickname-display").text("User Not Found").removeClass("text-gray-500 text-green-500").addClass("text-red-500");
                    }
                },
                error: function () {
                    // Silent fail or minimal error
                    $("#nickname-display").text("").removeClass("text-gray-500");
                }
            });
        }
    });
});

// Smart Paste for Mobile Legends ID (Zone)
$("#user_id").on("paste", function (e) {
    var pastedData = (e.originalEvent || e).clipboardData.getData('text');

    // Regex to match "12345678 (1234)" or "12345678(1234)"
    // Captures ID in group 1 and Zone in group 2
    var match = pastedData.match(/^(\w+)\s*\((\w+)\)$/);

    if (match && match.length === 3) {
        e.preventDefault(); // Stop default paste

        var uid = match[1];
        var zone = match[2];

        $("#user_id").val(uid);
        $("#zone").val(zone);

        // Trigger blur to run validation immediately
        $("#user_id").trigger("blur");
    }
});