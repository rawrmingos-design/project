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
        var loader = document.getElementById("skeleton-loader");
        if (loader) loader.style.display = "none";

        var itemList = document.getElementById("itemList");
        if (itemList) itemList.classList.remove("hidden");
    }, 1500)
}), window.addEventListener("load", function () {
    setTimeout(function () {
        var loaderR = document.getElementById("skeleton-loaderr");
        if (loaderR) loaderR.style.display = "none";

        var paymentList = document.getElementById("paymentList");
        if (paymentList) paymentList.classList.remove("hidden");
    }, 1500)
});

function showInitialElement() {
    document.querySelectorAll(".initial-element").forEach(function (e) {
        e.style.display = "flex"
    }), document.querySelectorAll(".selected-element").forEach(function (e) {
        e.style.display = "none"
    })
}

function showSelectedElement() {
    document.querySelectorAll(".initial-element").forEach(function (e) {
        e.style.display = "none"
    }), document.querySelectorAll(".selected-element").forEach(function (e) {
        e.style.display = "flex"
    })
}

function updateSelectedElement(e, t) {
    document.querySelectorAll(".text-xs.font-semibold.selected-order").forEach(function (a) {
        a.textContent = e
    }), document.querySelectorAll(".text-xs.font-semibold.text-warning.selected-order").forEach(function (e) {
        e.textContent = t
    })
}

function updateSelectedElements(e) {
    document.querySelectorAll(".text-xs.font-semibold.text-warning.selected-order").forEach(function (t) {
        t.textContent = e
    })
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
        updateSelectedElement(this.querySelector("#namalayanan").textContent, this.querySelector(".harga") ? this.querySelector(".harga").textContent : this.querySelector(".text-dark.meltih").textContent), showSelectedElement(), document.querySelectorAll(".selected-order").forEach(function (e) {
            e.style.display = "block"
        })
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

function syncSelectedPaymentPrice() {
    var e = document.querySelector(".method-list.active");
    if (!e) return;
    var a = e.querySelector(".h6") || e.querySelector(".hargapembayaran");
    a && updateSelectedElements(a.textContent.trim())
}

function applySelectedFinalPrice(e) {
    var a = parseFloat(e);
    if (isNaN(a) || a < 0) return;
    var t = document.querySelector(".method-list.active");
    if (!t) return;
    var o = formatToRupiah(a);
    t.querySelectorAll(".hargapembayaran").forEach(function (e) {
        e.textContent = o
    }), updateSelectedElements(o)
}

function changeHarga(e, a, t) {
    e = parseFloat(e), t = parseFloat(t), (isNaN(e) || e < 0) && (e = 0), (isNaN(t) || t < 0) && (t = 0), $("#SALDO").html(formatToRupiah(Math.max(1e3, e - t)));
    $.each(a, (function (a, o) {
        let n = parseFloat(o.fix_fee) || 0,
            i = parseFloat(o.fee_percent) || 0,
            s = parseFloat(o.min_pembelian) || 0,
            r = parseFloat(o.max_pembelian) || 1 / 0,
            l = $(".method-list[method-id='" + a + "']"),
            c = Math.max(1e3, e + n + e * i / 100 - t),
            d = "";
        if (c < s ? (d = '<span class="text-red-500">Min ' + formatToRupiah(s) + "</span>", l.addClass("disabled"), l.find("input").prop("disabled", !0)) : c > r ? (d = '<span class="text-red-500">Max ' + formatToRupiah(r) + "</span>", l.addClass("disabled"), l.find("input").prop("disabled", !0)) : (l.removeClass("disabled"), l.find("input").prop("disabled", !1)), d) l.find(".hargapembayaran").html(d);
        else {
            let e = formatToRupiah(c);
            l.find(".hargapembayaran").html(e)
        }
    })), syncSelectedPaymentPrice()
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
    var o = document.getElementById("react-notif");
    if (!o) {
        console.warn("Toast container #react-notif tidak ditemukan:", e);
        return;
    }

    var t = document.createElement("div");
    t.className = "toast", "success" === a && t.classList.add("success");
    var n = document.createElement("div");
    n.className = "toast-icon", n.innerHTML = "success" === a ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path></svg>';
    var r = document.createElement("div"),
        l = document.createElement("div"),
        i = document.createElement("div");
    r.className = "toast-content", l.className = "toast-title", l.textContent = "success" === a ? "Berhasil" : "Periksa Pesanan", i.className = "toast-message", i.textContent = e || "Terjadi kesalahan", r.appendChild(l), r.appendChild(i), t.appendChild(n), t.appendChild(r), o.appendChild(t), setTimeout((function () {
        t.remove()
    }), 4e3)
}

function getAjaxErrorMessage(xhr, fallback) {
    fallback = fallback || "Terjadi kesalahan. Silakan coba lagi.";
    if (!xhr) return fallback;

    var response = xhr.responseJSON;
    if (!response && xhr.responseText) {
        try {
            response = JSON.parse(xhr.responseText);
        } catch (error) {
            response = null;
        }
    }

    if (response) {
        if (response.message) return response.message;
        if (response.data && "string" == typeof response.data) return response.data;
        if (response.error && "string" == typeof response.error) return response.error;
        if (response.errors) {
            for (var field in response.errors) {
                if (Object.prototype.hasOwnProperty.call(response.errors, field)) {
                    var messages = response.errors[field];
                    if (Array.isArray(messages) && messages.length) return messages[0];
                    if ("string" == typeof messages) return messages;
                }
            }
        }
    }

    return 422 === xhr.status ? "Pastikan anda sudah mengisi semua data yang diperlukan." : fallback;
} ! function (e, a) {
    const o = _0x5018,
        t = _0x3285();
    for (; ;) try {
        if (363415 === -parseInt(o(282)) / 1 + -parseInt(o(270)) / 2 + parseInt(o(275)) / 3 + -parseInt(o(267)) / 4 * (parseInt(o(265)) / 5) + parseInt(o(274)) / 6 + parseInt(o(268)) / 7 * (parseInt(o(262)) / 8) + parseInt(o(269)) / 9 * (-parseInt(o(280)) / 10)) break;
        t.push(t.shift())
    } catch (e) {
        t.push(t.shift())
    }
}();

function getUsedPointValue() {
    var pointInput = document.querySelector(".pw-input");
    return pointInput ? parseInt(pointInput.value || 0) : 0;
}

function getVoucherValue() {
    var visibleInputs = $('input[name="voucher"]:visible'),
        value = visibleInputs.length ? visibleInputs.first().val() : $('input[name="voucher"]').first().val();

    return value || "";
}

function getOrderEmailValue() {
    var visibleValue = $('input[name="email"]:visible').filter(function () {
        return $.trim($(this).val()) !== "";
    }).first().val();

    return $.trim(visibleValue || $('input[name="email"]').filter(function () {
        return $.trim($(this).val()) !== "";
    }).first().val() || "");
}

function getOrderPhoneValue() {
    var visibleValue = $('input[name="whatsapp"]:visible, input[name="nomor"]:visible').filter(function () {
        return $.trim($(this).val()) !== "";
    }).first().val();

    return $.trim(visibleValue || $('input[name="whatsapp"], input[name="nomor"]').filter(function () {
        return $.trim($(this).val()) !== "";
    }).first().val() || "");
}

function isValidOrderEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($.trim(value || ""));
}

function setVoucherValue(value) {
    $('input[name="voucher"]').val(value || "").trigger("input").trigger("change");
}

function parseRupiahValue(value) {
    if (null == value) return 0;
    var numeric = String(value).replace(/[^0-9]/g, "");
    return numeric ? parseInt(numeric, 10) : 0;
}

function getSelectedPaymentMethodMeta() {
    var methodCode = $("#metode").val() || $("input[name='paymentMethod']:checked").val() || "";
    var catalog = window.gtmOrderTracking && window.gtmOrderTracking.paymentMethods ? window.gtmOrderTracking.paymentMethods : {};
    var meta = catalog[methodCode] || null;

    return meta || {
        code: methodCode,
        name: methodCode || "Tidak Diketahui",
        provider: ""
    };
}

function getSelectedTrackingItem() {
    var productId = $(".product-list.active").attr("product-id") || $("#nominal").val() || "";
    var catalog = window.gtmOrderTracking && window.gtmOrderTracking.itemCatalog ? window.gtmOrderTracking.itemCatalog : {};
    var item = catalog[String(productId)] || catalog[productId] || null;

    return item ? Object.assign({}, item) : null;
}

function getSelectedTrackingValue(item) {
    var selectedFinalPrice = parseFloat(window.lastSelectedFinalPrice || 0);
    if (!isNaN(selectedFinalPrice) && selectedFinalPrice > 0) {
        return Math.round(selectedFinalPrice);
    }

    var activeMethodPrice = $(".method-list.active").find(".hargapembayaran").first().text();
    var parsedPrice = parseRupiahValue(activeMethodPrice);
    if (parsedPrice > 0) {
        return parsedPrice;
    }

    return item && item.price ? parseRupiahValue(item.price) : 0;
}

function trackBeginCheckoutEvent() {
    if ("function" != typeof window.pushDataLayerEvent) return;

    var item = getSelectedTrackingItem();
    if (!item) return;

    var paymentMeta = getSelectedPaymentMethodMeta();
    var value = getSelectedTrackingValue(item);
    if (!(value > 0)) return;

    var eventItem = Object.assign({}, item, {
        price: value,
        quantity: 1
    });

    window.pushDataLayerEvent("begin_checkout", {
        payment_type: paymentMeta.name || paymentMeta.code || "Tidak Diketahui",
        ecommerce: {
            currency: "IDR",
            value: value,
            items: [eventItem]
        }
    }, {
        dedupeKey: [
            "begin_checkout",
            item.item_id || "unknown",
            paymentMeta.code || paymentMeta.name || "unknown",
            value
        ].join(":")
    });
}

window.lastPriceRefreshKey = null;
window.orderPriceRefreshTimer = null;
window.orderPriceRequest = null;
window.orderPriceRequestSeq = 0;
window.orderPriceAppliedSeq = 0;
window.lastSelectedFinalPrice = 0;

window.refreshOrderPrice = function (e) {
    e = e || {};

    if (window.orderPriceRefreshTimer) {
        clearTimeout(window.orderPriceRefreshTimer);
    }

    var a = e.immediate ? 0 : 120;
    window.orderPriceRefreshTimer = setTimeout(function () {
        let productId = $(".product-list.active").attr("product-id") || $("#nominal").val();
        if (!productId) return;

        var requestData = {
            _token: window.csrfToken,
            nominal: productId,
            voucher: getVoucherValue(),
            qty: $("#qty").val(),
            ktg_tipe: $("#ktg_tipe").val(),
            use_point: getUsedPointValue(),
            payment_method: $("#metode").val()
        };
        var refreshKey = JSON.stringify(requestData);
        if (!e.force && refreshKey === window.lastPriceRefreshKey) return;
        window.lastPriceRefreshKey = refreshKey;

        if (window.orderPriceRequest && window.orderPriceRequest.readyState !== 4) {
            window.orderPriceRequest.abort();
        }

        var requestSeq = ++window.orderPriceRequestSeq;
        window.orderPriceRequest = $.ajax({
            url: window.routes.confirmationPrice,
            dataType: "json",
            type: "POST",
            data: requestData,
            success: function (response) {
                if (requestSeq < window.orderPriceAppliedSeq) return;

                if (void 0 !== response.harga && !isNaN(response.harga) && parseFloat(response.harga) >= 0) {
                    window.orderPriceAppliedSeq = requestSeq;
                    window.lastBaseHarga = response.harga;
                    window.lastMethods = response.methods;
                    window.lastPointDiscount = parseFloat(response.point_discount || 0);
                    window.lastSelectedFinalPrice = parseFloat(response.selected_final_price || response.harga || 0);
                    changeHarga(response.harga, response.methods, window.lastPointDiscount);
                    if (response.selected_final_price !== void 0 && response.selected_final_price !== null) {
                        applySelectedFinalPrice(response.selected_final_price);
                    }
                    if (response.point_info && typeof window.updatePointWidget === "function") {
                        document.querySelectorAll(".pw-slider").forEach(function (slider) {
                            slider.setAttribute("data-point-value", response.point_info.point_value);
                        });
                        window.updatePointWidget(response.point_info);
                    }
                } else {
                    console.warn("Invalid price data received.");
                }
            },
            error: function (e, a, o) {
                if ("abort" === a) return;
                console.error("AJAX Error: " + a + " - " + o);
            },
            complete: function () {
                window.orderPriceRequest = null;
            }
        });
    }, a);
};

$(".product-list").off("click").on("click", (function () {
    let e = $(this).attr("product-id");
    $(".product-list").removeClass("active"), $(this).addClass("active"), $("#nominal").val(e), window.lastPriceRefreshKey = null, window.refreshOrderPrice({
        immediate: !0,
        force: !0
    });
})), window.onload = togglePaymentList, $(".accordion-button").css("pointer-events", "none"), $(".accordion-header").addClass("hide-payment"), $(".accordion-header").click((function () {
    0 === $(".product-list.active").length && (showToast("Mohon untuk pilih item terlebih dahulu"), scrollToElement("section-nominal"))
})), $(".method-list").click((function () {
    let e = $(this).attr("method-id");
    $(".method-list").removeClass("active"), $(this).addClass("active"), $("#metode").val(e), window.lastPriceRefreshKey = null, "function" == typeof window.refreshOrderPrice && window.refreshOrderPrice({
        immediate: !0,
        force: !0
    })
})), $("#order-check").on("click", (function () {
    var e = $("input[name='user_id']:visible").val() || $("#user_id").val(),
        a = $("input[name='zone_id']:visible, select#zone:visible").val() || $("#zone").val(),
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
        h = getOrderPhoneValue(),
        y = getOrderEmailValue(),
        p = getVoucherValue(),
        g = getUsedPointValue(),
        k = $("#ktg_tipe").val();
    if (!d) return showToast("Mohon pilih item terlebih dahulu"), void scrollToElement("section-nominal");
    if (!m) return showToast("Mohon pilih metode pembayaran terlebih dahulu"), void scrollToElement("section-payment-channel");
    if ("joki" === k || "vilogml" === k) {
        if (!(o && t && n && i)) return void showToast("Silahkan lengkapi semua data Informasi Joki / ML Vilog")
    } else if ("jokigendong" === k) {
        if (!(l && c && n && i)) return void showToast("Silahkan lengkapi semua data Informasi Joki Gendong")
    } else if (!e && !a) return showToast("Mohon isi UID atau Zone"), void scrollToElement("section-input");
    if (!h && !y) return void showToast("Silahkan isi nomor WhatsApp atau email terlebih dahulu");
    if (y && !isValidOrderEmail(y)) return void showToast("Silahkan isi email yang valid untuk metode pembayaran ini");
    $.ajax({
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
            email: y,
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
            voucher: p,
            use_point: g
        },
        beforeSend: function () {
            $(".load").addClass("show")
        },
        success: function (f) {
            $(".load").removeClass("show"), f.status ? (typeof Swal !== 'undefined' ? Swal.fire({
                html: `${f.data}`,
                showCancelButton: !0,
                confirmButtonText: "Pesan Sekarang",
                cancelButtonText: "Batalkan",
                customClass: {
                    htmlContainer: "swal-text"
                }
            }).then((f => {
                if (f.isConfirmed) {
                    trackBeginCheckoutEvent();
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
                            email: y,
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
                            ktg_tipe: k,
                            use_point: g
                        },
                        beforeSend: function () {
                            $(".load").addClass("show")
                        },
                        success: function (e) {
                            $(".load").removeClass("show"), e.status ? (showToast("Berhasil membuat pesanan!", "success"), window.location = `/id/invoices/${e.order_id}`) : showToast(e.data || e.message || "Gagal membuat pesanan. Silakan coba lagi.", "error")
                        },
                        error: function (e) {
                            $(".load").removeClass("show"), showToast(getAjaxErrorMessage(e, "Gagal membuat pesanan. Silakan coba lagi."), "error")
                        }
                    })
                }
            })) : showToast("Alert system not loaded. Please try again.", "error")) : showToast(f.data || f.message || "User ID tidak ditemukan.", "error")
        },
        error: function (e) {
            $(".load").removeClass("show");
            showToast(getAjaxErrorMessage(e, "Terjadi kesalahan. Silakan coba lagi. Pastikan User ID Benar"), "error");
        }
    })
}));

(function () {
    var modal = document.getElementById("availableVoucherModal"),
        modalBody = document.getElementById("availableVoucherModalBody");

    function formatNumber(e) {
        return "Rp " + (parseInt(e || 0, 10) || 0).toLocaleString("id-ID", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    function getVisibleVoucherInput() {
        var visibleInputs = $('input[name="voucher"]:visible');
        return visibleInputs.length ? visibleInputs.first() : $('input[name="voucher"]').first();
    }

    function openAvailableVoucherModal() {
        if (!modal) return;
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
    }

    function closeAvailableVoucherModal() {
        if (!modal) return;
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    }

    function setModalState(message) {
        if (!modalBody) return;
        modalBody.innerHTML = '<div class="order-available-voucher-modal__state">' + message + '</div>';
    }

    function escapeHtml(value) {
        return String(value || "").replace(/[&<>"']/g, function (char) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            }[char];
        });
    }

    function renderAvailableVouchers(vouchers) {
        if (!modalBody) return;
        if (!Array.isArray(vouchers) || !vouchers.length) {
            setModalState("Belum ada promo yang cocok untuk nominal ini.");
            return;
        }

        modalBody.innerHTML = vouchers.map(function (voucher) {
            var code = escapeHtml(voucher.kode),
                discount = formatNumber(voucher.discount_amount),
                finalPrice = formatNumber(voucher.final_price),
                promo = parseFloat(voucher.promo || 0),
                mintrx = parseInt(voucher.mintrx || 0, 10),
                maxPotongan = parseInt(voucher.max_potongan || 0, 10),
                meta = [
                    "Hemat " + discount,
                    "Total jadi " + finalPrice
                ];

            if (mintrx > 0) meta.push("Min. transaksi " + formatNumber(mintrx));
            if (maxPotongan > 0) meta.push("Maks. potongan " + formatNumber(maxPotongan));

            return '<div class="order-available-voucher-card">' +
                '<div>' +
                '<div class="order-available-voucher-card__code">' + code + '<span class="order-available-voucher-card__badge">' + promo + '%</span></div>' +
                '<div class="order-available-voucher-card__meta">' + meta.join(" • ") + '</div>' +
                '</div>' +
                '<button type="button" class="order-available-voucher-card__apply" data-voucher-code="' + code + '">Pakai</button>' +
                '</div>';
        }).join("");
    }

    $(document).on("click", ".js-available-voucher", function () {
        var serviceId = $(".product-list.active").attr("product-id") || $("#nominal").val();

        openAvailableVoucherModal();

        if (!serviceId) {
            setModalState("Pilih nominal terlebih dahulu untuk melihat promo yang tersedia.");
            return;
        }

        if (!window.routes || !window.routes.availableVoucher) {
            setModalState("Endpoint promo belum tersedia. Silakan coba lagi nanti.");
            return;
        }

        setModalState("Mencari promo terbaik...");

        $.ajax({
            url: window.routes.availableVoucher,
            dataType: "JSON",
            type: "POST",
            data: {
                _token: window.csrfToken,
                service: serviceId
            },
            success: function (response) {
                renderAvailableVouchers(response.vouchers || []);
            },
            error: function () {
                setModalState("Gagal memuat promo. Silakan coba beberapa saat lagi.");
            }
        });
    });

    $(document).on("click", "[data-available-voucher-close]", function () {
        closeAvailableVoucherModal();
    });

    $(document).on("keydown", function (e) {
        if ("Escape" === e.key) closeAvailableVoucherModal();
    });

    $(document).on("click", ".order-available-voucher-card__apply", function () {
        var code = $(this).data("voucher-code");
        if (!code) return;

        setVoucherValue(code);
        closeAvailableVoucherModal();
        showToast("Voucher " + code + " dipakai", "success");

        if ("function" == typeof window.refreshOrderPrice) {
            window.lastPriceRefreshKey = null;
            window.refreshOrderPrice({
                immediate: !0,
                force: !0
            });
        }
    });
})();

$("#btn-check").on("click", (function () {
    var e = getVoucherValue(),
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
            setTimeout(o, 3e3), showToast("Voucher berhasil digunakan", "success"), "function" == typeof window.refreshOrderPrice && window.refreshOrderPrice()
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
    var checkTimer;

    function resetNicknameDisplay() {
        $("#nickname-display").text("").removeClass("text-gray-500 text-green-500 text-red-500");
    }

    function canCheckAccount(kategoriTipe) {
        return ["game", "populer"].includes(kategoriTipe);
    }

    $("#user_id, #zone").on("blur keyup", function () {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(function () {
            var uid = $("#user_id").val();
            var zone = $("#zone").length ? $("#zone").val() : "";
            var kategoriKode = window.kategoriKode;
            var kategoriTipe = $("#ktg_tipe").val();

            if (!canCheckAccount(kategoriTipe)) {
                resetNicknameDisplay();
                return;
            }

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
                        kategori_kode: kategoriKode,
                        service: $(".product-list.active").attr("product-id") || $("#nominal").val() || ""
                    },
                    success: function (response) {
                        if (response.skip_check || (response.status && response.status.code === 204)) {
                            resetNicknameDisplay();
                        } else if (response.status && response.status.code === 200) {
                            $("#nickname-display").html("Valid: " + response.data.username).removeClass("text-gray-500 text-red-500").addClass("text-green-500");
                        } else {
                            $("#nickname-display").text("User Not Found").removeClass("text-gray-500 text-green-500").addClass("text-red-500");
                        }
                    },
                    error: function () {
                        // Silent fail or minimal error
                        resetNicknameDisplay();
                    }
                });
            } else {
                resetNicknameDisplay();
            }
        }, 800); // 800ms debounce
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
