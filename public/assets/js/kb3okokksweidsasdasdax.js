document.addEventListener("DOMContentLoaded", function () {
  let e = document.querySelectorAll('[name="nominal"]');
  e.forEach(function (e) {
      e.addEventListener("click", function () {
          !(function e() {
              let t = document.getElementById("section-payment-channel");
              t && t.scrollIntoView({ behavior: "smooth" });
          })();
      });
  });
});
var listGroupItems = document.querySelectorAll(".method-list");
listGroupItems.forEach(function (e) {
  e.addEventListener("click", function () {
      var e = this.querySelector(".text-xs").textContent.trim();
      (document.querySelector("#pesan").textContent = e),
          showSelectedElement();
  });
}),
  (window.onload = function () {
      var e = document.getElementById("nomor"),
          t = localStorage.getItem("savedNumber");
      t && (e.value = t),
          e.addEventListener("input", function () {
              localStorage.setItem("savedNumber", e.value);
          });
  }),
  document.addEventListener("DOMContentLoaded", function () {
      let e = document.getElementById("section-payment-channel"),
          t = document.getElementById("whatsappp");
      function n(e, t) {
          let n = e.getBoundingClientRect(),
              o = window.scrollY || window.pageYOffset,
              l = n.top + o - window.innerHeight / 2 + n.height / 2;
          window.scrollTo({ top: l, behavior: "smooth" }),
              t && "function" == typeof t && setTimeout(t, 100);
      }
      document.querySelectorAll(".bg-product").forEach(function (t) {
          t.addEventListener("click", function () {
              n(e);
          });
      }),
          document.querySelectorAll(".method-list").forEach(function (o) {
              o.addEventListener("click", function () {
                  n(e, function () {
                      n(t);
                  });
              });
          });
  });

window.addEventListener("load", function () {
  setTimeout(function () {
      document.getElementById("skeleton-loaderr").style.display = "none";
      document.getElementById("paymentList").classList.remove("hidden");
  }, 1500);
});

function _0x2c6e() {
  var _0x18481f = [
      "36GXWZUW",
      "1423816lQSsyY",
      "querySelector",
      "querySelectorAll",
      "18DKcdpq",
      ".text-xs.font-semibold.selected-order",
      ".hargapembayaran",
      "none",
      "3006560NRupEh",
      ".text-xs.font-semibold.text-warning.selected-order",
      "addEventListener",
      "51612FBuyfA",
      ".flex.w-full.items-center\x20p.text-xs.italic",
      "4rcOZKA",
      "style",
      "#namalayanan",
      "display",
      "click",
      "8397620OkNCBu",
      "72898FWvdCT",
      "block",
      ".method-list",
      "162302xLFQgK",
      "textContent",
      ".selected-element",
      ".text-dark.meltih",
      "forEach",
      "flex",
      "343715UixQFe",
      "54jFWshW",
      ".initial-element",
  ];
  _0x2c6e = function () {
      return _0x18481f;
  };
  return _0x2c6e();
}
var _0x52679a = _0x3d6d;
(function (_0x254de3, _0x292f07) {
  var _0x35c3d8 = _0x3d6d,
      _0x3bdf9a = _0x254de3();
  while (!![]) {
      try {
          var _0x2078ae =
              -parseInt(_0x35c3d8(0x11b)) / 0x1 +
              (parseInt(_0x35c3d8(0x108)) / 0x2) *
                  (-parseInt(_0x35c3d8(0x113)) / 0x3) +
              (parseInt(_0x35c3d8(0x115)) / 0x4) *
                  (parseInt(_0x35c3d8(0x105)) / 0x5) +
              (-parseInt(_0x35c3d8(0x106)) / 0x6) *
                  (parseInt(_0x35c3d8(0x11e)) / 0x7) +
              (parseInt(_0x35c3d8(0x109)) / 0x8) *
                  (-parseInt(_0x35c3d8(0x10c)) / 0x9) +
              parseInt(_0x35c3d8(0x110)) / 0xa +
              parseInt(_0x35c3d8(0x11a)) / 0xb;
          if (_0x2078ae === _0x292f07) break;
          else _0x3bdf9a["push"](_0x3bdf9a["shift"]());
      } catch (_0x5cb1ce) {
          _0x3bdf9a["push"](_0x3bdf9a["shift"]());
      }
  }
})(_0x2c6e, 0x2d515);
function showInitialElement() {
  var _0x3c1ccf = _0x3d6d,
      _0x45c37e = document[_0x3c1ccf(0x10a)](_0x3c1ccf(0x107)),
      _0x202e6e = document[_0x3c1ccf(0x10a)](_0x3c1ccf(0x101));
  (_0x45c37e[_0x3c1ccf(0x116)][_0x3c1ccf(0x118)] = _0x3c1ccf(0x104)),
      (_0x202e6e["style"][_0x3c1ccf(0x118)] = _0x3c1ccf(0x10f));
}
function showSelectedElement() {
  var _0x66fae4 = _0x3d6d,
      _0x2a226c = document[_0x66fae4(0x10a)](".initial-element"),
      _0x450dd9 = document[_0x66fae4(0x10a)](_0x66fae4(0x101));
  (_0x2a226c[_0x66fae4(0x116)][_0x66fae4(0x118)] = _0x66fae4(0x10f)),
      (_0x450dd9["style"]["display"] = "flex");
}
function updateSelectedElement(_0xa1a39c, _0x56b4cb) {
  var _0x29eb93 = _0x3d6d,
      _0x5c7630 = document[_0x29eb93(0x10a)](_0x29eb93(0x10d)),
      _0xe083b0 = document[_0x29eb93(0x10a)](_0x29eb93(0x111));
  document[_0x29eb93(0x10a)](_0x29eb93(0x114)),
      (_0x5c7630[_0x29eb93(0x100)] = _0xa1a39c),
      (_0xe083b0[_0x29eb93(0x100)] = _0x56b4cb);
}
function updateSelectedElements(_0x34b672) {
  var _0x32a1de = _0x3d6d;
  document["querySelector"](_0x32a1de(0x111))[_0x32a1de(0x100)] = _0x34b672;
}
var listGroupItems = document["querySelectorAll"](_0x52679a(0x11d));
listGroupItems[_0x52679a(0x103)](function (_0x2bcf72) {
  var _0x1f6c8f = _0x52679a;
  _0x2bcf72[_0x1f6c8f(0x112)](_0x1f6c8f(0x119), function () {
      var _0x399ac6 = _0x1f6c8f;
      updateSelectedElements(
          this[_0x399ac6(0x10a)](".h6")
              ? this["querySelector"](".h6")["textContent"]
              : this[_0x399ac6(0x10a)](_0x399ac6(0x10e))["textContent"]
      ),
          showSelectedElement();
  });
});
function _0x3d6d(_0x19ccaf, _0x22bb9d) {
  var _0x2c6e2a = _0x2c6e();
  return (
      (_0x3d6d = function (_0x3d6df1, _0x20bb3d) {
          _0x3d6df1 = _0x3d6df1 - 0x100;
          var _0x14c2fc = _0x2c6e2a[_0x3d6df1];
          return _0x14c2fc;
      }),
      _0x3d6d(_0x19ccaf, _0x22bb9d)
  );
}
var productListItems = document[_0x52679a(0x10b)](".product-list");
productListItems[_0x52679a(0x103)](function (_0xdb87a) {
  var _0x2fc64c = _0x52679a;
  _0xdb87a[_0x2fc64c(0x112)]("click", function () {
      var _0x49b692 = _0x2fc64c,
          _0x35063a;
      updateSelectedElement(
          this["querySelector"](_0x49b692(0x117))[_0x49b692(0x100)],
          this["querySelector"](".harga")
              ? this[_0x49b692(0x10a)](".harga")[_0x49b692(0x100)]
              : this[_0x49b692(0x10a)](_0x49b692(0x102))[_0x49b692(0x100)]
      ),
          showSelectedElement(),
          (document["querySelector"](".selected-order")[_0x49b692(0x116)][
              _0x49b692(0x118)
          ] = _0x49b692(0x11c));
  });
}),
  showInitialElement();

function validateQtyInput(e) {
  e.value.includes("-") && (e.value = e.value.replace("-", ""));
  e.value < 1 ? (e.value = 1) : e.value > 30 && (e.value = 30);
}

function togglePaymentList() {
  const paymentLists = document.querySelectorAll(".melpa-sabled");
  const productLists = document.querySelectorAll(".product-list");

  productLists.forEach((productList) => {
      productList.addEventListener("click", function () {
          paymentLists.forEach((paymentList) => {
              paymentList.classList.remove("disabled");
          });
      });
  });
}

window.onload = togglePaymentList;

let isLoading = false;

function changeHarga(basePrice, methods) {
  basePrice = parseFloat(basePrice);
  if (isNaN(basePrice) || basePrice < 0) {
      basePrice = 0;
  }

  let saldoFormatted = formatToRupiah(basePrice);

  $.each(methods, function (code, method) {
      let fixedFee = parseFloat(method.fixed_fee) || 0;
      let feePercentage = parseFloat(method.fee_percentage) || 0;
      let minPembelian = parseFloat(method.min_pembelian) || 0;
      let maxPembelian = parseFloat(method.max_pembelian) || Infinity;
      let methodElement = $("#" + code).closest(".method-list");
      let message = "";

      if (basePrice < minPembelian) {
          message = `<span class="text-red-500">Min ${formatToRupiah(
              minPembelian
          )}</span>`;
          methodElement.addClass("disabled");
          methodElement.find("input").prop("disabled", true);
      } else if (basePrice > maxPembelian) {
          message = `<span class="text-red-500">Max ${formatToRupiah(
              maxPembelian
          )}</span>`;
          methodElement.addClass("disabled");
          methodElement.find("input").prop("disabled", true);
      } else {
          methodElement.removeClass("disabled");
          methodElement.find("input").prop("disabled", false);
      }

      if (message) {
          $("#" + code).html(message);
      } else {
          let total =
              basePrice + fixedFee + (basePrice * feePercentage) / 100;
          let formattedTotal = formatToRupiah(total);
          $("#" + code).html(formattedTotal);
      }
  });
}

function formatToRupiah(value) {
  if (typeof value !== "number") {
      value = parseFloat(value);
  }
  if (isNaN(value)) {
      value = 0;
  }
  return (
      "Rp " +
      value.toLocaleString("id-ID", {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
      })
  );
}

$(".product-list")
  .off("click")
  .on("click", function () {
      if (isLoading) return;

      let productId = $(this).attr("product-id");
      $(".product-list").removeClass("active");
      $(this).addClass("active");
      $("#nominal").val(productId);

      isLoading = true;

      $.ajax({
          url: window.routes.confirmationPrice,
          dataType: "json",
          type: "POST",
          data: { _token: window.csrfToken, nominal: productId },
          success: function (response) {
              if (
                  response.harga !== undefined &&
                  !isNaN(response.harga) &&
                  parseFloat(response.harga) >= 0
              ) {
                  changeHarga(response.harga, response.methods);
              }
          },
          error: function () {
              console.error("Error in AJAX request.");
          },
          complete: function () {
              isLoading = false;
          },
      });
  });

function scrollToElement(o) {
  $("html, body").animate({ scrollTop: $("#" + o).offset().top }, 1000);
}

$(".accordion-header").click(function () {
  if ($(".product-list.active").length === 0) {
      showToast("Mohon untuk pilih item terlebih dahulu");
      scrollToElement("section-nominal");
  }
});

$(".method-list").click(function () {
  let t = $(this).attr("method-id");
  $(".method-list").removeClass("active");
  $(this).addClass("active");
  $("#metode").val(t);
});

$("#order-check").on("click", function () {
  var t = $("#user_id").val(),
      a = $("#zone").val(),
      e = $("#email_joki").val(),
      o = $("#password_joki").val(),
      i = $("#loginvia_joki").val(),
      n = $("#nickname_joki").val(),
      s = $("#request_joki").val(),
      r = $("#catatan_joki").val(),
      b = $("#tglmain_joki").val(),
      f = $("#jambooking_joki").val(),
      l = $("#nominal").val(),
      q = $("#qty").val(),
      m = $("#metode").val(),
      h = $("#nomor").val(),
      c = $("#voucher").val(),
      d = $("#ktg_tipe").val();

  if (d === "joki" || d === "vilogml") {
      if (!e || !o || !i || !n) {
          showToast("Silahkan isi data akun terlebih dahulu.");
          return;
      }
  } else if (d === "jokigendong") {
      if (!b || !f || !i || !n) {
          showToast("Silahkan isi data akun terlebih dahulu.");
          return;
      }
  } else {
      if (!t && !a) {
          showToast("Mohon isi UID atau Zone");
          return;
      }
  }

  if (!l || !m || !h) {
      showToast("Silahkan isi data akun terlebih dahulu.");
      return;
  }

  if (!h) {
      showToast("Silahkan lengkapi nomor WhatsApp");
      return;
  }

  $("#order-check").prop("disabled", true);

  $.ajax({
      url: window.routes.confirmationUrl,
      dataType: "JSON",
      type: "POST",
      data: {
          _token: window.csrfToken,
          uid: t,
          zone: a,
          service: l,
          payment_method: m,
          nomor: h,
          email_joki: e,
          password_joki: o,
          loginvia_joki: i,
          nickname_joki: n,
          request_joki: s,
          catatan_joki: r,
          tglmain_joki: b,
          jambooking_joki: f,
          qty: q,
          ktg_tipe: d,
          voucher: c,
      },
      beforeSend: function () {
          $(".load").addClass("show");
      },
      success: function (u) {
          $(".load").removeClass("show");
          $("#order-check").prop("disabled", false);

          if (u.status) {
              Swal.fire({
                  html: `${u.data}`,
                  showCancelButton: true,
                  confirmButtonText: "Pesan Sekarang",
                  cancelButtonText: "Batalkan",
              }).then((u) => {
                  if (u.isConfirmed) {
                      var k = $("#nick").text();
                      $.ajax({
                          url: window.routes.orderedUrl,
                          dataType: "JSON",
                          type: "POST",
                          data: {
                              _token: window.csrfToken,
                              nickname: k,
                              uid: t,
                              zone: a,
                              service: l,
                              payment_method: m,
                              nomor: h,
                              voucher: c,
                              email_joki: e,
                              password_joki: o,
                              loginvia_joki: i,
                              nickname_joki: n,
                              request_joki: s,
                              catatan_joki: r,
                              tglmain_joki: b,
                              jambooking_joki: f,
                              qty: q,
                              ktg_tipe: d,
                          },
                          beforeSend: function () {
                              $(".load").addClass("show");
                          },
                          success: function (t) {
                              $(".load").removeClass("show");
                              if (t.status) {
                                  showToast(
                                      "Berhasil membuat pesanan!",
                                      "success"
                                  );
                                  window.location = `/id/invoices/${t.order_id}`;
                              } else {
                                  showToast("Sedang gangguan!", "error");
                              }
                          },
                          error: function (t) {
                              $(".load").removeClass("show");
                              console.log(t);
                          },
                      });
                  }
              });
          } else {
              Swal.fire({
                  title: "Oops...",
                  text: u.data || "User ID tidak ditemukan.",
                  icon: "error",
              });
          }
      },
      error: function (t) {
          $(".load").removeClass("show");
          $("#order-check").prop("disabled", false);

          Swal.fire({
              title: "Oops...",
              text: "Terjadi kesalahan. Silakan coba lagi.",
              icon: "error",
          });
      },
  });
});

function showToast(t, type = "error") {
  var a = document.getElementById("react-notif"),
      e = document.createElement("div");
  e.className = "toast";
  if (type === "success") {
      e.classList.add("success");
  }
  var o = document.createElement("div");
  o.className = "toast-icon";
  if (type === "success") {
      o.innerHTML =
          '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" width="16" color="rgba(34, 197, 94, 0.8)"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>';
  } else {
      o.innerHTML = '<div class="alertmelpaa"></div>';
  }
  var i = document.createElement("div");
  i.className = "toast-message";
  i.textContent = t;
  e.appendChild(o);
  e.appendChild(i);
  a.appendChild(e);
  setTimeout(function () {
      e.remove();
  }, 3e3);
}

$("#btn-check").on("click", function () {
  var t = $("#voucher").val(),
      o = $("#nominal").val();

  $.ajax({
      url: window.routes.checkVoucher,
      dataType: "JSON",
      type: "POST",
      data: { _token: window.csrfToken, voucher: t, service: o },
      success: function () {
          showToast("Promo berhasil digunakan", "success");
      },
      error: function () {
          showToast("Promo tidak tersedia", "error");
      },
  });
});