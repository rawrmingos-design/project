/*!
 * jQuery v3.5.1
 */ function r(e, t) {
    for (var n = 0; n < t.length; n++) {
        var i = t[n];
        (i.enumerable = i.enumerable || !1), (i.configurable = !0), "value" in i && (i.writable = !0), Object.defineProperty(e, i.key, i);
    }
}
function Jt(e, t, n) {
    t && r(e.prototype, t), n && r(e, n), Object.defineProperty(e, "prototype", { writable: !1 });
}
!(function (e, t) {
    "use strict";
    "object" == typeof module && "object" == typeof module.exports
        ? (module.exports = e.document
              ? t(e, !0)
              : function (e) {
                    if (!e.document) throw Error("jQuery requires a window with a document");
                    return t(e);
                })
        : t(e);
})("undefined" != typeof window ? window : this, function (e, t) {
    "use strict";
    var n = [],
        i = Object.getPrototypeOf,
        a = n.slice,
        s = n.flat
            ? function (e) {
                  return n.flat.call(e);
              }
            : function (e) {
                  return n.concat.apply([], e);
              },
        o = n.push,
        l = n.indexOf,
        u = {},
        d = u.toString,
        c = u.hasOwnProperty,
        p = c.toString,
        f = p.call(Object),
        h = {},
        m = function (e) {
            return "function" == typeof e && "number" != typeof e.nodeType;
        },
        g = function (e) {
            return null != e && e === e.window;
        },
        v = e.document,
        $ = { type: !0, src: !0, nonce: !0, noModule: !0 };
    function y(e, t, n) {
        var i,
            a,
            s = (n = n || v).createElement("script");
        if (((s.text = e), t)) for (i in $) (a = t[i] || (t.getAttribute && t.getAttribute(i))) && s.setAttribute(i, a);
        n.head.appendChild(s).parentNode.removeChild(s);
    }
    function b(e) {
        return null == e ? e + "" : "object" == typeof e || "function" == typeof e ? u[d.call(e)] || "object" : typeof e;
    }
    var _ = "3.5.1",
        w = function (e, t) {
            return new w.fn.init(e, t);
        };
    function x(e) {
        var t = !!e && "length" in e && e.length,
            n = b(e);
        return !m(e) && !g(e) && ("array" === n || 0 === t || ("number" == typeof t && 0 < t && t - 1 in e));
    }
    (w.fn = w.prototype = {
        jquery: _,
        constructor: w,
        length: 0,
        toArray: function () {
            return a.call(this);
        },
        get: function (e) {
            return null == e ? a.call(this) : e < 0 ? this[e + this.length] : this[e];
        },
        pushStack: function (e) {
            var t = w.merge(this.constructor(), e);
            return (t.prevObject = this), t;
        },
        each: function (e) {
            return w.each(this, e);
        },
        map: function (e) {
            return this.pushStack(
                w.map(this, function (t, n) {
                    return e.call(t, n, t);
                })
            );
        },
        slice: function () {
            return this.pushStack(a.apply(this, arguments));
        },
        first: function () {
            return this.eq(0);
        },
        last: function () {
            return this.eq(-1);
        },
        even: function () {
            return this.pushStack(
                w.grep(this, function (e, t) {
                    return (t + 1) % 2;
                })
            );
        },
        odd: function () {
            return this.pushStack(
                w.grep(this, function (e, t) {
                    return t % 2;
                })
            );
        },
        eq: function (e) {
            var t = this.length,
                n = +e + (e < 0 ? t : 0);
            return this.pushStack(0 <= n && n < t ? [this[n]] : []);
        },
        end: function () {
            return this.prevObject || this.constructor();
        },
        push: o,
        sort: n.sort,
        splice: n.splice,
    }),
        (w.extend = w.fn.extend = function () {
            var e,
                t,
                n,
                i,
                a,
                s,
                o = arguments[0] || {},
                l = 1,
                u = arguments.length,
                d = !1;
            for ("boolean" == typeof o && ((d = o), (o = arguments[l] || {}), l++), "object" == typeof o || m(o) || (o = {}), l === u && ((o = this), l--); l < u; l++)
                if (null != (e = arguments[l]))
                    for (t in e)
                        (i = e[t]),
                            "__proto__" !== t &&
                                o !== i &&
                                (d && i && (w.isPlainObject(i) || (a = Array.isArray(i)))
                                    ? ((n = o[t]), (s = a && !Array.isArray(n) ? [] : a || w.isPlainObject(n) ? n : {}), (a = !1), (o[t] = w.extend(d, s, i)))
                                    : void 0 !== i && (o[t] = i));
            return o;
        }),
        w.extend({
            expando: "jQuery" + (_ + Math.random()).replace(/\D/g, ""),
            isReady: !0,
            error: function (e) {
                throw Error(e);
            },
            noop: function () {},
            isPlainObject: function (e) {
                var t, n;
                return !(!e || "[object Object]" !== d.call(e) || ((t = i(e)) && ("function" != typeof (n = c.call(t, "constructor") && t.constructor) || p.call(n) !== f)));
            },
            isEmptyObject: function (e) {
                var t;
                for (t in e) return !1;
                return !0;
            },
            globalEval: function (e, t, n) {
                y(e, { nonce: t && t.nonce }, n);
            },
            each: function (e, t) {
                var n,
                    i = 0;
                if (x(e)) for (n = e.length; i < n && !1 !== t.call(e[i], i, e[i]); i++);
                else for (i in e) if (!1 === t.call(e[i], i, e[i])) break;
                return e;
            },
            makeArray: function (e, t) {
                var n = t || [];
                return null != e && (x(Object(e)) ? w.merge(n, "string" == typeof e ? [e] : e) : o.call(n, e)), n;
            },
            inArray: function (e, t, n) {
                return null == t ? -1 : l.call(t, e, n);
            },
            merge: function (e, t) {
                for (var n = +t.length, i = 0, a = e.length; i < n; i++) e[a++] = t[i];
                return (e.length = a), e;
            },
            grep: function (e, t, n) {
                for (var i = [], a = 0, s = e.length, o = !n; a < s; a++) !t(e[a], a) !== o && i.push(e[a]);
                return i;
            },
            map: function (e, t, n) {
                var i,
                    a,
                    o = 0,
                    l = [];
                if (x(e)) for (i = e.length; o < i; o++) null != (a = t(e[o], o, n)) && l.push(a);
                else for (o in e) null != (a = t(e[o], o, n)) && l.push(a);
                return s(l);
            },
            guid: 1,
            support: h,
        }),
        "function" == typeof Symbol && (w.fn[Symbol.iterator] = n[Symbol.iterator]),
        w.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "), function (e, t) {
            u["[object " + t + "]"] = t.toLowerCase();
        });
    var S = (function (e) {
        var t,
            n,
            i,
            a,
            s,
            o,
            l,
            u,
            d,
            c,
            p,
            f,
            h,
            m,
            g,
            v,
            $,
            y,
            b,
            _ = "sizzle" + 1 * new Date(),
            w = e.document,
            x = 0,
            S = 0,
            T = ed(),
            C = ed(),
            E = ed(),
            D = ed(),
            k = function (e, t) {
                return e === t && (p = !0), 0;
            },
            L = {}.hasOwnProperty,
            A = [],
            I = A.pop,
            P = A.push,
            N = A.push,
            M = A.slice,
            O = function (e, t) {
                for (var n = 0, i = e.length; n < i; n++) if (e[n] === t) return n;
                return -1;
            },
            R = "checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",
            z = "[\\x20\\t\\r\\n\\f]",
            F = "(?:\\\\[\\da-fA-F]{1,6}" + z + "?|\\\\[^\\r\\n\\f]|[\\w-]|[^\0-\\x7f])+",
            j = "\\[" + z + "*(" + F + ")(?:" + z + "*([*^$|!~]?=)" + z + "*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|(" + F + "))|)" + z + "*\\]",
            H = ":(" + F + ")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|" + j + ")*)|.*)\\)|)",
            B = RegExp(z + "+", "g"),
            q = RegExp("^" + z + "+|((?:^|[^\\\\])(?:\\\\.)*)" + z + "+$", "g"),
            W = RegExp("^" + z + "*," + z + "*"),
            G = RegExp("^" + z + "*([>+~]|" + z + ")" + z + "*"),
            U = RegExp(z + "|>"),
            V = RegExp(H),
            X = RegExp("^" + F + "$"),
            Y = {
                ID: RegExp("^#(" + F + ")"),
                CLASS: RegExp("^\\.(" + F + ")"),
                TAG: RegExp("^(" + F + "|[*])"),
                ATTR: RegExp("^" + j),
                PSEUDO: RegExp("^" + H),
                CHILD: RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\(" + z + "*(even|odd|(([+-]|)(\\d*)n|)" + z + "*(?:([+-]|)" + z + "*(\\d+)|))" + z + "*\\)|)", "i"),
                bool: RegExp("^(?:" + R + ")$", "i"),
                needsContext: RegExp("^" + z + "*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\(" + z + "*((?:-\\d)?\\d*)" + z + "*\\)|)(?=[^-]|$)", "i"),
            },
            J = /HTML$/i,
            K = /^(?:input|select|textarea|button)$/i,
            Z = /^h\d$/i,
            Q = /^[^{]+\{\s*\[native \w/,
            ee = /^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,
            et = /[+~]/,
            en = RegExp("\\\\[\\da-fA-F]{1,6}" + z + "?|\\\\([^\\r\\n\\f])", "g"),
            ei = function (e, t) {
                var n = "0x" + e.slice(1) - 65536;
                return t || (n < 0 ? String.fromCharCode(n + 65536) : String.fromCharCode((n >> 10) | 55296, (1023 & n) | 56320));
            },
            er = /([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g,
            ea = function (e, t) {
                return t ? ("\0" === e ? "�" : e.slice(0, -1) + "\\" + e.charCodeAt(e.length - 1).toString(16) + " ") : "\\" + e;
            },
            es = function () {
                f();
            },
            eo = ew(
                function (e) {
                    return !0 === e.disabled && "fieldset" === e.nodeName.toLowerCase();
                },
                { dir: "parentNode", next: "legend" }
            );
        try {
            N.apply((A = M.call(w.childNodes)), w.childNodes), A[w.childNodes.length].nodeType;
        } catch (el) {
            N = {
                apply: A.length
                    ? function (e, t) {
                          P.apply(e, M.call(t));
                      }
                    : function (e, t) {
                          for (var n = e.length, i = 0; (e[n++] = t[i++]); );
                          e.length = n - 1;
                      },
            };
        }
        function eu(e, t, i, a) {
            var s,
                l,
                d,
                c,
                p,
                m,
                $,
                y = t && t.ownerDocument,
                w = t ? t.nodeType : 9;
            if (((i = i || []), "string" != typeof e || !e || (1 !== w && 9 !== w && 11 !== w))) return i;
            if (!a && (f(t), (t = t || h), g)) {
                if (11 !== w && (p = ee.exec(e))) {
                    if ((s = p[1])) {
                        if (9 === w) {
                            if (!(d = t.getElementById(s))) return i;
                            if (d.id === s) return i.push(d), i;
                        } else if (y && (d = y.getElementById(s)) && b(t, d) && d.id === s) return i.push(d), i;
                    } else {
                        if (p[2]) return N.apply(i, t.getElementsByTagName(e)), i;
                        if ((s = p[3]) && n.getElementsByClassName && t.getElementsByClassName) return N.apply(i, t.getElementsByClassName(s)), i;
                    }
                }
                if (n.qsa && !D[e + " "] && (!v || !v.test(e)) && (1 !== w || "object" !== t.nodeName.toLowerCase())) {
                    if ((($ = e), (y = t), 1 === w && (U.test(e) || G.test(e)))) {
                        for (((y = (et.test(e) && ey(t.parentNode)) || t) === t && n.scope) || ((c = t.getAttribute("id")) ? (c = c.replace(er, ea)) : t.setAttribute("id", (c = _))), l = (m = o(e)).length; l--; )
                            m[l] = (c ? "#" + c : ":scope") + " " + e_(m[l]);
                        $ = m.join(",");
                    }
                    try {
                        return N.apply(i, y.querySelectorAll($)), i;
                    } catch (x) {
                        D(e, !0);
                    } finally {
                        c === _ && t.removeAttribute("id");
                    }
                }
            }
            return u(e.replace(q, "$1"), t, i, a);
        }
        function ed() {
            var e = [];
            return function t(n, a) {
                return e.push(n + " ") > i.cacheLength && delete t[e.shift()], (t[n + " "] = a);
            };
        }
        function ec(e) {
            return (e[_] = !0), e;
        }
        function ep(e) {
            var t = h.createElement("fieldset");
            try {
                return !!e(t);
            } catch (n) {
                return !1;
            } finally {
                t.parentNode && t.parentNode.removeChild(t), (t = null);
            }
        }
        function ef(e, t) {
            for (var n = e.split("|"), a = n.length; a--; ) i.attrHandle[n[a]] = t;
        }
        function eh(e, t) {
            var n = t && e,
                i = n && 1 === e.nodeType && 1 === t.nodeType && e.sourceIndex - t.sourceIndex;
            if (i) return i;
            if (n) {
                for (; (n = n.nextSibling); ) if (n === t) return -1;
            }
            return e ? 1 : -1;
        }
        function em(e) {
            return function (t) {
                return "input" === t.nodeName.toLowerCase() && t.type === e;
            };
        }
        function eg(e) {
            return function (t) {
                var n = t.nodeName.toLowerCase();
                return ("input" === n || "button" === n) && t.type === e;
            };
        }
        function ev(e) {
            return function (t) {
                return "form" in t
                    ? t.parentNode && !1 === t.disabled
                        ? "label" in t
                            ? "label" in t.parentNode
                                ? t.parentNode.disabled === e
                                : t.disabled === e
                            : t.isDisabled === e || (!e !== t.isDisabled && eo(t) === e)
                        : t.disabled === e
                    : "label" in t && t.disabled === e;
            };
        }
        function e$(e) {
            return ec(function (t) {
                return (
                    (t = +t),
                    ec(function (n, i) {
                        for (var a, s = e([], n.length, t), o = s.length; o--; ) n[(a = s[o])] && (n[a] = !(i[a] = n[a]));
                    })
                );
            });
        }
        function ey(e) {
            return e && void 0 !== e.getElementsByTagName && e;
        }
        for (t in ((n = eu.support = {}),
        (s = eu.isXML = function (e) {
            var t = e.namespaceURI,
                n = (e.ownerDocument || e).documentElement;
            return !J.test(t || (n && n.nodeName) || "HTML");
        }),
        (f = eu.setDocument = function (e) {
            var t,
                a,
                o = e ? e.ownerDocument || e : w;
            return (
                o != h &&
                    9 === o.nodeType &&
                    o.documentElement &&
                    ((m = (h = o).documentElement),
                    (g = !s(h)),
                    w != h && (a = h.defaultView) && a.top !== a && (a.addEventListener ? a.addEventListener("unload", es, !1) : a.attachEvent && a.attachEvent("onunload", es)),
                    (n.scope = ep(function (e) {
                        return m.appendChild(e).appendChild(h.createElement("div")), void 0 !== e.querySelectorAll && !e.querySelectorAll(":scope fieldset div").length;
                    })),
                    (n.attributes = ep(function (e) {
                        return (e.className = "i"), !e.getAttribute("className");
                    })),
                    (n.getElementsByTagName = ep(function (e) {
                        return e.appendChild(h.createComment("")), !e.getElementsByTagName("*").length;
                    })),
                    (n.getElementsByClassName = Q.test(h.getElementsByClassName)),
                    (n.getById = ep(function (e) {
                        return (m.appendChild(e).id = _), !h.getElementsByName || !h.getElementsByName(_).length;
                    })),
                    n.getById
                        ? ((i.filter.ID = function (e) {
                              var t = e.replace(en, ei);
                              return function (e) {
                                  return e.getAttribute("id") === t;
                              };
                          }),
                          (i.find.ID = function (e, t) {
                              if (void 0 !== t.getElementById && g) {
                                  var n = t.getElementById(e);
                                  return n ? [n] : [];
                              }
                          }))
                        : ((i.filter.ID = function (e) {
                              var t = e.replace(en, ei);
                              return function (e) {
                                  var n = void 0 !== e.getAttributeNode && e.getAttributeNode("id");
                                  return n && n.value === t;
                              };
                          }),
                          (i.find.ID = function (e, t) {
                              if (void 0 !== t.getElementById && g) {
                                  var n,
                                      i,
                                      a,
                                      s = t.getElementById(e);
                                  if (s) {
                                      if ((n = s.getAttributeNode("id")) && n.value === e) return [s];
                                      for (a = t.getElementsByName(e), i = 0; (s = a[i++]); ) if ((n = s.getAttributeNode("id")) && n.value === e) return [s];
                                  }
                                  return [];
                              }
                          })),
                    (i.find.TAG = n.getElementsByTagName
                        ? function (e, t) {
                              return void 0 !== t.getElementsByTagName ? t.getElementsByTagName(e) : n.qsa ? t.querySelectorAll(e) : void 0;
                          }
                        : function (e, t) {
                              var n,
                                  i = [],
                                  a = 0,
                                  s = t.getElementsByTagName(e);
                              if ("*" === e) {
                                  for (; (n = s[a++]); ) 1 === n.nodeType && i.push(n);
                                  return i;
                              }
                              return s;
                          }),
                    (i.find.CLASS =
                        n.getElementsByClassName &&
                        function (e, t) {
                            if (void 0 !== t.getElementsByClassName && g) return t.getElementsByClassName(e);
                        }),
                    ($ = []),
                    (v = []),
                    (n.qsa = Q.test(h.querySelectorAll)) &&
                        (ep(function (e) {
                            var t;
                            (m.appendChild(e).innerHTML = "<a id='" + _ + "'></a><select id='" + _ + "-\r\\' msallowcapture=''><option selected=''></option></select>"),
                                e.querySelectorAll("[msallowcapture^='']").length && v.push("[*^$]=" + z + "*(?:''|\"\")"),
                                e.querySelectorAll("[selected]").length || v.push("\\[" + z + "*(?:value|" + R + ")"),
                                e.querySelectorAll("[id~=" + _ + "-]").length || v.push("~="),
                                (t = h.createElement("input")).setAttribute("name", ""),
                                e.appendChild(t),
                                e.querySelectorAll("[name='']").length || v.push("\\[" + z + "*name" + z + "*=" + z + "*(?:''|\"\")"),
                                e.querySelectorAll(":checked").length || v.push(":checked"),
                                e.querySelectorAll("a#" + _ + "+*").length || v.push(".#.+[+~]"),
                                e.querySelectorAll("\\\f"),
                                v.push("[\\r\\n\\f]");
                        }),
                        ep(function (e) {
                            e.innerHTML = "<a href='' disabled='disabled'></a><select disabled='disabled'><option/></select>";
                            var t = h.createElement("input");
                            t.setAttribute("type", "hidden"),
                                e.appendChild(t).setAttribute("name", "D"),
                                e.querySelectorAll("[name=d]").length && v.push("name" + z + "*[*^$|!~]?="),
                                2 !== e.querySelectorAll(":enabled").length && v.push(":enabled", ":disabled"),
                                (m.appendChild(e).disabled = !0),
                                2 !== e.querySelectorAll(":disabled").length && v.push(":enabled", ":disabled"),
                                e.querySelectorAll("*,:x"),
                                v.push(",.*:");
                        })),
                    (n.matchesSelector = Q.test((y = m.matches || m.webkitMatchesSelector || m.mozMatchesSelector || m.oMatchesSelector || m.msMatchesSelector))) &&
                        ep(function (e) {
                            (n.disconnectedMatch = y.call(e, "*")), y.call(e, "[s!='']:x"), $.push("!=", H);
                        }),
                    (v = v.length && RegExp(v.join("|"))),
                    ($ = $.length && RegExp($.join("|"))),
                    (b =
                        (t = Q.test(m.compareDocumentPosition)) || Q.test(m.contains)
                            ? function (e, t) {
                                  var n = 9 === e.nodeType ? e.documentElement : e,
                                      i = t && t.parentNode;
                                  return e === i || !(!i || 1 !== i.nodeType || !(n.contains ? n.contains(i) : e.compareDocumentPosition && 16 & e.compareDocumentPosition(i)));
                              }
                            : function (e, t) {
                                  if (t) {
                                      for (; (t = t.parentNode); ) if (t === e) return !0;
                                  }
                                  return !1;
                              }),
                    (k = t
                        ? function (e, t) {
                              if (e === t) return (p = !0), 0;
                              var i = !e.compareDocumentPosition - !t.compareDocumentPosition;
                              return (
                                  i ||
                                  (1 & (i = (e.ownerDocument || e) == (t.ownerDocument || t) ? e.compareDocumentPosition(t) : 1) || (!n.sortDetached && t.compareDocumentPosition(e) === i)
                                      ? e == h || (e.ownerDocument == w && b(w, e))
                                          ? -1
                                          : t == h || (t.ownerDocument == w && b(w, t))
                                          ? 1
                                          : c
                                          ? O(c, e) - O(c, t)
                                          : 0
                                      : 4 & i
                                      ? -1
                                      : 1)
                              );
                          }
                        : function (e, t) {
                              if (e === t) return (p = !0), 0;
                              var n,
                                  i = 0,
                                  a = e.parentNode,
                                  s = t.parentNode,
                                  o = [e],
                                  l = [t];
                              if (!a || !s) return e == h ? -1 : t == h ? 1 : a ? -1 : s ? 1 : c ? O(c, e) - O(c, t) : 0;
                              if (a === s) return eh(e, t);
                              for (n = e; (n = n.parentNode); ) o.unshift(n);
                              for (n = t; (n = n.parentNode); ) l.unshift(n);
                              for (; o[i] === l[i]; ) i++;
                              return i ? eh(o[i], l[i]) : o[i] == w ? -1 : l[i] == w ? 1 : 0;
                          })),
                h
            );
        }),
        (eu.matches = function (e, t) {
            return eu(e, null, null, t);
        }),
        (eu.matchesSelector = function (e, t) {
            if ((f(e), n.matchesSelector && g && !D[t + " "] && (!$ || !$.test(t)) && (!v || !v.test(t))))
                try {
                    var i = y.call(e, t);
                    if (i || n.disconnectedMatch || (e.document && 11 !== e.document.nodeType)) return i;
                } catch (a) {
                    D(t, !0);
                }
            return 0 < eu(t, h, null, [e]).length;
        }),
        (eu.contains = function (e, t) {
            return (e.ownerDocument || e) != h && f(e), b(e, t);
        }),
        (eu.attr = function (e, t) {
            (e.ownerDocument || e) != h && f(e);
            var a = i.attrHandle[t.toLowerCase()],
                s = a && L.call(i.attrHandle, t.toLowerCase()) ? a(e, t, !g) : void 0;
            return void 0 !== s ? s : n.attributes || !g ? e.getAttribute(t) : (s = e.getAttributeNode(t)) && s.specified ? s.value : null;
        }),
        (eu.escape = function (e) {
            return (e + "").replace(er, ea);
        }),
        (eu.error = function (e) {
            throw Error("Syntax error, unrecognized expression: " + e);
        }),
        (eu.uniqueSort = function (e) {
            var t,
                i = [],
                a = 0,
                s = 0;
            if (((p = !n.detectDuplicates), (c = !n.sortStable && e.slice(0)), e.sort(k), p)) {
                for (; (t = e[s++]); ) t === e[s] && (a = i.push(s));
                for (; a--; ) e.splice(i[a], 1);
            }
            return (c = null), e;
        }),
        (a = eu.getText = function (e) {
            var t,
                n = "",
                i = 0,
                s = e.nodeType;
            if (s) {
                if (1 === s || 9 === s || 11 === s) {
                    if ("string" == typeof e.textContent) return e.textContent;
                    for (e = e.firstChild; e; e = e.nextSibling) n += a(e);
                } else if (3 === s || 4 === s) return e.nodeValue;
            } else for (; (t = e[i++]); ) n += a(t);
            return n;
        }),
        ((i = eu.selectors = {
            cacheLength: 50,
            createPseudo: ec,
            match: Y,
            attrHandle: {},
            find: {},
            relative: { ">": { dir: "parentNode", first: !0 }, " ": { dir: "parentNode" }, "+": { dir: "previousSibling", first: !0 }, "~": { dir: "previousSibling" } },
            preFilter: {
                ATTR: function (e) {
                    return (e[1] = e[1].replace(en, ei)), (e[3] = (e[3] || e[4] || e[5] || "").replace(en, ei)), "~=" === e[2] && (e[3] = " " + e[3] + " "), e.slice(0, 4);
                },
                CHILD: function (e) {
                    return (
                        (e[1] = e[1].toLowerCase()),
                        "nth" === e[1].slice(0, 3) ? (e[3] || eu.error(e[0]), (e[4] = +(e[4] ? e[5] + (e[6] || 1) : 2 * ("even" === e[3] || "odd" === e[3]))), (e[5] = +(e[7] + e[8] || "odd" === e[3]))) : e[3] && eu.error(e[0]),
                        e
                    );
                },
                PSEUDO: function (e) {
                    var t,
                        n = !e[6] && e[2];
                    return Y.CHILD.test(e[0])
                        ? null
                        : (e[3] ? (e[2] = e[4] || e[5] || "") : n && V.test(n) && (t = o(n, !0)) && (t = n.indexOf(")", n.length - t) - n.length) && ((e[0] = e[0].slice(0, t)), (e[2] = n.slice(0, t))), e.slice(0, 3));
                },
            },
            filter: {
                TAG: function (e) {
                    var t = e.replace(en, ei).toLowerCase();
                    return "*" === e
                        ? function () {
                              return !0;
                          }
                        : function (e) {
                              return e.nodeName && e.nodeName.toLowerCase() === t;
                          };
                },
                CLASS: function (e) {
                    var t = T[e + " "];
                    return (
                        t ||
                        ((t = RegExp("(^|" + z + ")" + e + "(" + z + "|$)")),
                        T(e, function (e) {
                            return t.test(("string" == typeof e.className && e.className) || (void 0 !== e.getAttribute && e.getAttribute("class")) || "");
                        }))
                    );
                },
                ATTR: function (e, t, n) {
                    return function (i) {
                        var a = eu.attr(i, e);
                        return null == a
                            ? "!=" === t
                            : !t ||
                                  ((a += ""),
                                  "=" === t
                                      ? a === n
                                      : "!=" === t
                                      ? a !== n
                                      : "^=" === t
                                      ? n && 0 === a.indexOf(n)
                                      : "*=" === t
                                      ? n && -1 < a.indexOf(n)
                                      : "$=" === t
                                      ? n && a.slice(-n.length) === n
                                      : "~=" === t
                                      ? -1 < (" " + a.replace(B, " ") + " ").indexOf(n)
                                      : "|=" === t && (a === n || a.slice(0, n.length + 1) === n + "-"));
                    };
                },
                CHILD: function (e, t, n, i, a) {
                    var s = "nth" !== e.slice(0, 3),
                        o = "last" !== e.slice(-4),
                        l = "of-type" === t;
                    return 1 === i && 0 === a
                        ? function (e) {
                              return !!e.parentNode;
                          }
                        : function (t, n, u) {
                              var d,
                                  c,
                                  p,
                                  f,
                                  h,
                                  m,
                                  g = s !== o ? "nextSibling" : "previousSibling",
                                  v = t.parentNode,
                                  $ = l && t.nodeName.toLowerCase(),
                                  y = !u && !l,
                                  b = !1;
                              if (v) {
                                  if (s) {
                                      for (; g; ) {
                                          for (f = t; (f = f[g]); ) if (l ? f.nodeName.toLowerCase() === $ : 1 === f.nodeType) return !1;
                                          m = g = "only" === e && !m && "nextSibling";
                                      }
                                      return !0;
                                  }
                                  if (((m = [o ? v.firstChild : v.lastChild]), o && y)) {
                                      for (
                                          b = (h = (d = (c = (p = (f = v)[_] || (f[_] = {}))[f.uniqueID] || (p[f.uniqueID] = {}))[e] || [])[0] === x && d[1]) && d[2], f = h && v.childNodes[h];
                                          (f = (++h && f && f[g]) || (b = h = 0) || m.pop());

                                      )
                                          if (1 === f.nodeType && ++b && f === t) {
                                              c[e] = [x, h, b];
                                              break;
                                          }
                                  } else if ((y && (b = h = (d = (c = (p = (f = t)[_] || (f[_] = {}))[f.uniqueID] || (p[f.uniqueID] = {}))[e] || [])[0] === x && d[1]), !1 === b))
                                      for (
                                          ;
                                          (f = (++h && f && f[g]) || (b = h = 0) || m.pop()) &&
                                          ((l ? f.nodeName.toLowerCase() !== $ : 1 !== f.nodeType) || !++b || (y && ((c = (p = f[_] || (f[_] = {}))[f.uniqueID] || (p[f.uniqueID] = {}))[e] = [x, b]), f !== t));

                                      );
                                  return (b -= a) === i || (b % i == 0 && 0 <= b / i);
                              }
                          };
                },
                PSEUDO: function (e, t) {
                    var n,
                        a = i.pseudos[e] || i.setFilters[e.toLowerCase()] || eu.error("unsupported pseudo: " + e);
                    return a[_]
                        ? a(t)
                        : 1 < a.length
                        ? ((n = [e, e, "", t]),
                          i.setFilters.hasOwnProperty(e.toLowerCase())
                              ? ec(function (e, n) {
                                    for (var i, s = a(e, t), o = s.length; o--; ) e[(i = O(e, s[o]))] = !(n[i] = s[o]);
                                })
                              : function (e) {
                                    return a(e, 0, n);
                                })
                        : a;
                },
            },
            pseudos: {
                not: ec(function (e) {
                    var t = [],
                        n = [],
                        i = l(e.replace(q, "$1"));
                    return i[_]
                        ? ec(function (e, t, n, a) {
                              for (var s, o = i(e, null, a, []), l = e.length; l--; ) (s = o[l]) && (e[l] = !(t[l] = s));
                          })
                        : function (e, a, s) {
                              return (t[0] = e), i(t, null, s, n), (t[0] = null), !n.pop();
                          };
                }),
                has: ec(function (e) {
                    return function (t) {
                        return 0 < eu(e, t).length;
                    };
                }),
                contains: ec(function (e) {
                    return (
                        (e = e.replace(en, ei)),
                        function (t) {
                            return -1 < (t.textContent || a(t)).indexOf(e);
                        }
                    );
                }),
                lang: ec(function (e) {
                    return (
                        X.test(e || "") || eu.error("unsupported lang: " + e),
                        (e = e.replace(en, ei).toLowerCase()),
                        function (t) {
                            var n;
                            do if ((n = g ? t.lang : t.getAttribute("xml:lang") || t.getAttribute("lang"))) return (n = n.toLowerCase()) === e || 0 === n.indexOf(e + "-");
                            while ((t = t.parentNode) && 1 === t.nodeType);
                            return !1;
                        }
                    );
                }),
                target: function (t) {
                    var n = e.location && e.location.hash;
                    return n && n.slice(1) === t.id;
                },
                root: function (e) {
                    return e === m;
                },
                focus: function (e) {
                    return e === h.activeElement && (!h.hasFocus || h.hasFocus()) && !!(e.type || e.href || ~e.tabIndex);
                },
                enabled: ev(!1),
                disabled: ev(!0),
                checked: function (e) {
                    var t = e.nodeName.toLowerCase();
                    return ("input" === t && !!e.checked) || ("option" === t && !!e.selected);
                },
                selected: function (e) {
                    return e.parentNode && e.parentNode.selectedIndex, !0 === e.selected;
                },
                empty: function (e) {
                    for (e = e.firstChild; e; e = e.nextSibling) if (e.nodeType < 6) return !1;
                    return !0;
                },
                parent: function (e) {
                    return !i.pseudos.empty(e);
                },
                header: function (e) {
                    return Z.test(e.nodeName);
                },
                input: function (e) {
                    return K.test(e.nodeName);
                },
                button: function (e) {
                    var t = e.nodeName.toLowerCase();
                    return ("input" === t && "button" === e.type) || "button" === t;
                },
                text: function (e) {
                    var t;
                    return "input" === e.nodeName.toLowerCase() && "text" === e.type && (null == (t = e.getAttribute("type")) || "text" === t.toLowerCase());
                },
                first: e$(function () {
                    return [0];
                }),
                last: e$(function (e, t) {
                    return [t - 1];
                }),
                eq: e$(function (e, t, n) {
                    return [n < 0 ? n + t : n];
                }),
                even: e$(function (e, t) {
                    for (var n = 0; n < t; n += 2) e.push(n);
                    return e;
                }),
                odd: e$(function (e, t) {
                    for (var n = 1; n < t; n += 2) e.push(n);
                    return e;
                }),
                lt: e$(function (e, t, n) {
                    for (var i = n < 0 ? n + t : t < n ? t : n; 0 <= --i; ) e.push(i);
                    return e;
                }),
                gt: e$(function (e, t, n) {
                    for (var i = n < 0 ? n + t : n; ++i < t; ) e.push(i);
                    return e;
                }),
            },
        }).pseudos.nth = i.pseudos.eq),
        { radio: !0, checkbox: !0, file: !0, password: !0, image: !0 }))
            i.pseudos[t] = em(t);
        for (t in { submit: !0, reset: !0 }) i.pseudos[t] = eg(t);
        function eb() {}
        function e_(e) {
            for (var t = 0, n = e.length, i = ""; t < n; t++) i += e[t].value;
            return i;
        }
        function ew(e, t, n) {
            var i = t.dir,
                a = t.next,
                s = a || i,
                o = n && "parentNode" === s,
                l = S++;
            return t.first
                ? function (t, n, a) {
                      for (; (t = t[i]); ) if (1 === t.nodeType || o) return e(t, n, a);
                      return !1;
                  }
                : function (t, n, u) {
                      var d,
                          c,
                          p,
                          f = [x, l];
                      if (u) {
                          for (; (t = t[i]); ) if ((1 === t.nodeType || o) && e(t, n, u)) return !0;
                      } else
                          for (; (t = t[i]); )
                              if (1 === t.nodeType || o) {
                                  if (((c = (p = t[_] || (t[_] = {}))[t.uniqueID] || (p[t.uniqueID] = {})), a && a === t.nodeName.toLowerCase())) t = t[i] || t;
                                  else {
                                      if ((d = c[s]) && d[0] === x && d[1] === l) return (f[2] = d[2]);
                                      if (((c[s] = f)[2] = e(t, n, u))) return !0;
                                  }
                              }
                      return !1;
                  };
        }
        function ex(e) {
            return 1 < e.length
                ? function (t, n, i) {
                      for (var a = e.length; a--; ) if (!e[a](t, n, i)) return !1;
                      return !0;
                  }
                : e[0];
        }
        function eS(e, t, n, i, a) {
            for (var s, o = [], l = 0, u = e.length, d = null != t; l < u; l++) (s = e[l]) && ((n && !n(s, i, a)) || (o.push(s), d && t.push(l)));
            return o;
        }
        function eT(e, t, n, i, a, s) {
            return (
                i && !i[_] && (i = eT(i)),
                a && !a[_] && (a = eT(a, s)),
                ec(function (s, o, l, u) {
                    var d,
                        c,
                        p,
                        f = [],
                        h = [],
                        m = o.length,
                        g =
                            s ||
                            (function (e, t, n) {
                                for (var i = 0, a = t.length; i < a; i++) eu(e, t[i], n);
                                return n;
                            })(t || "*", l.nodeType ? [l] : l, []),
                        v = e && (s || !t) ? eS(g, f, e, l, u) : g,
                        $ = n ? (a || (s ? e : m || i) ? [] : o) : v;
                    if ((n && n(v, $, l, u), i)) for (d = eS($, h), i(d, [], l, u), c = d.length; c--; ) (p = d[c]) && ($[h[c]] = !(v[h[c]] = p));
                    if (s) {
                        if (a || e) {
                            if (a) {
                                for (d = [], c = $.length; c--; ) (p = $[c]) && d.push((v[c] = p));
                                a(null, ($ = []), d, u);
                            }
                            for (c = $.length; c--; ) (p = $[c]) && -1 < (d = a ? O(s, p) : f[c]) && (s[d] = !(o[d] = p));
                        }
                    } else ($ = eS($ === o ? $.splice(m, $.length) : $)), a ? a(null, o, $, u) : N.apply(o, $);
                })
            );
        }
        function eC(e) {
            for (
                var t,
                    n,
                    a,
                    s = e.length,
                    o = i.relative[e[0].type],
                    l = o || i.relative[" "],
                    u = o ? 1 : 0,
                    c = ew(
                        function (e) {
                            return e === t;
                        },
                        l,
                        !0
                    ),
                    p = ew(
                        function (e) {
                            return -1 < O(t, e);
                        },
                        l,
                        !0
                    ),
                    f = [
                        function (e, n, i) {
                            var a = (!o && (i || n !== d)) || ((t = n).nodeType ? c(e, n, i) : p(e, n, i));
                            return (t = null), a;
                        },
                    ];
                u < s;
                u++
            )
                if ((n = i.relative[e[u].type])) f = [ew(ex(f), n)];
                else {
                    if ((n = i.filter[e[u].type].apply(null, e[u].matches))[_]) {
                        for (a = ++u; a < s && !i.relative[e[a].type]; a++);
                        return eT(1 < u && ex(f), 1 < u && e_(e.slice(0, u - 1).concat({ value: " " === e[u - 2].type ? "*" : "" })).replace(q, "$1"), n, u < a && eC(e.slice(u, a)), a < s && eC((e = e.slice(a))), a < s && e_(e));
                    }
                    f.push(n);
                }
            return ex(f);
        }
        return (
            (eb.prototype = i.filters = i.pseudos),
            (i.setFilters = new eb()),
            (o = eu.tokenize = function (e, t) {
                var n,
                    a,
                    s,
                    o,
                    l,
                    u,
                    d,
                    c = C[e + " "];
                if (c) return t ? 0 : c.slice(0);
                for (l = e, u = [], d = i.preFilter; l; ) {
                    for (o in ((!n || (a = W.exec(l))) && (a && (l = l.slice(a[0].length) || l), u.push((s = []))),
                    (n = !1),
                    (a = G.exec(l)) && ((n = a.shift()), s.push({ value: n, type: a[0].replace(q, " ") }), (l = l.slice(n.length))),
                    i.filter))
                        (a = Y[o].exec(l)) && (!d[o] || (a = d[o](a))) && ((n = a.shift()), s.push({ value: n, type: o, matches: a }), (l = l.slice(n.length)));
                    if (!n) break;
                }
                return t ? l.length : l ? eu.error(e) : C(e, u).slice(0);
            }),
            (l = eu.compile = function (e, t) {
                var n,
                    a,
                    s,
                    l,
                    u,
                    c,
                    p = [],
                    m = [],
                    v = E[e + " "];
                if (!v) {
                    for (t || (t = o(e)), n = t.length; n--; ) (v = eC(t[n]))[_] ? p.push(v) : m.push(v);
                    (v = E(
                        e,
                        ((a = m),
                        (l = 0 < (s = p).length),
                        (u = 0 < a.length),
                        (c = function (e, t, n, o, c) {
                            var p,
                                m,
                                v,
                                $ = 0,
                                y = "0",
                                b = e && [],
                                _ = [],
                                w = d,
                                S = e || (u && i.find.TAG("*", c)),
                                T = (x += null == w ? 1 : Math.random() || 0.1),
                                C = S.length;
                            for (c && (d = t == h || t || c); y !== C && null != (p = S[y]); y++) {
                                if (u && p) {
                                    for (m = 0, t || p.ownerDocument == h || (f(p), (n = !g)); (v = a[m++]); )
                                        if (v(p, t || h, n)) {
                                            o.push(p);
                                            break;
                                        }
                                    c && (x = T);
                                }
                                l && ((p = !v && p) && $--, e && b.push(p));
                            }
                            if ((($ += y), l && y !== $)) {
                                for (m = 0; (v = s[m++]); ) v(b, _, t, n);
                                if (e) {
                                    if (0 < $) for (; y--; ) b[y] || _[y] || (_[y] = I.call(o));
                                    _ = eS(_);
                                }
                                N.apply(o, _), c && !e && 0 < _.length && 1 < $ + s.length && eu.uniqueSort(o);
                            }
                            return c && ((x = T), (d = w)), b;
                        }),
                        l ? ec(c) : c)
                    )).selector = e;
                }
                return v;
            }),
            (u = eu.select = function (e, t, n, a) {
                var s,
                    u,
                    d,
                    c,
                    p,
                    f = "function" == typeof e && e,
                    h = !a && o((e = f.selector || e));
                if (((n = n || []), 1 === h.length)) {
                    if (2 < (u = h[0] = h[0].slice(0)).length && "ID" === (d = u[0]).type && 9 === t.nodeType && g && i.relative[u[1].type]) {
                        if (!(t = (i.find.ID(d.matches[0].replace(en, ei), t) || [])[0])) return n;
                        f && (t = t.parentNode), (e = e.slice(u.shift().value.length));
                    }
                    for (s = Y.needsContext.test(e) ? 0 : u.length; s-- && ((d = u[s]), !i.relative[(c = d.type)]); )
                        if ((p = i.find[c]) && (a = p(d.matches[0].replace(en, ei), (et.test(u[0].type) && ey(t.parentNode)) || t))) {
                            if ((u.splice(s, 1), !(e = a.length && e_(u)))) return N.apply(n, a), n;
                            break;
                        }
                }
                return (f || l(e, h))(a, t, !g, n, !t || (et.test(e) && ey(t.parentNode)) || t), n;
            }),
            (n.sortStable = _.split("").sort(k).join("") === _),
            (n.detectDuplicates = !!p),
            f(),
            (n.sortDetached = ep(function (e) {
                return 1 & e.compareDocumentPosition(h.createElement("fieldset"));
            })),
            ep(function (e) {
                return (e.innerHTML = "<a href='#'></a>"), "#" === e.firstChild.getAttribute("href");
            }) ||
                ef("type|href|height|width", function (e, t, n) {
                    if (!n) return e.getAttribute(t, "type" === t.toLowerCase() ? 1 : 2);
                }),
            (n.attributes &&
                ep(function (e) {
                    return (e.innerHTML = "<input/>"), e.firstChild.setAttribute("value", ""), "" === e.firstChild.getAttribute("value");
                })) ||
                ef("value", function (e, t, n) {
                    if (!n && "input" === e.nodeName.toLowerCase()) return e.defaultValue;
                }),
            ep(function (e) {
                return null == e.getAttribute("disabled");
            }) ||
                ef(R, function (e, t, n) {
                    var i;
                    if (!n) return !0 === e[t] ? t.toLowerCase() : (i = e.getAttributeNode(t)) && i.specified ? i.value : null;
                }),
            eu
        );
    })(e);
    (w.find = S), (w.expr = S.selectors), (w.expr[":"] = w.expr.pseudos), (w.uniqueSort = w.unique = S.uniqueSort), (w.text = S.getText), (w.isXMLDoc = S.isXML), (w.contains = S.contains), (w.escapeSelector = S.escape);
    var T = function (e, t, n) {
            for (var i = [], a = void 0 !== n; (e = e[t]) && 9 !== e.nodeType; )
                if (1 === e.nodeType) {
                    if (a && w(e).is(n)) break;
                    i.push(e);
                }
            return i;
        },
        C = function (e, t) {
            for (var n = []; e; e = e.nextSibling) 1 === e.nodeType && e !== t && n.push(e);
            return n;
        },
        E = w.expr.match.needsContext;
    function D(e, t) {
        return e.nodeName && e.nodeName.toLowerCase() === t.toLowerCase();
    }
    var k = /^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i;
    function L(e, t, n) {
        return m(t)
            ? w.grep(e, function (e, i) {
                  return !!t.call(e, i, e) !== n;
              })
            : t.nodeType
            ? w.grep(e, function (e) {
                  return (e === t) !== n;
              })
            : "string" != typeof t
            ? w.grep(e, function (e) {
                  return -1 < l.call(t, e) !== n;
              })
            : w.filter(t, e, n);
    }
    (w.filter = function (e, t, n) {
        var i = t[0];
        return (
            n && (e = ":not(" + e + ")"),
            1 === t.length && 1 === i.nodeType
                ? w.find.matchesSelector(i, e)
                    ? [i]
                    : []
                : w.find.matches(
                      e,
                      w.grep(t, function (e) {
                          return 1 === e.nodeType;
                      })
                  )
        );
    }),
        w.fn.extend({
            find: function (e) {
                var t,
                    n,
                    i = this.length,
                    a = this;
                if ("string" != typeof e)
                    return this.pushStack(
                        w(e).filter(function () {
                            for (t = 0; t < i; t++) if (w.contains(a[t], this)) return !0;
                        })
                    );
                for (n = this.pushStack([]), t = 0; t < i; t++) w.find(e, a[t], n);
                return 1 < i ? w.uniqueSort(n) : n;
            },
            filter: function (e) {
                return this.pushStack(L(this, e || [], !1));
            },
            not: function (e) {
                return this.pushStack(L(this, e || [], !0));
            },
            is: function (e) {
                return !!L(this, "string" == typeof e && E.test(e) ? w(e) : e || [], !1).length;
            },
        });
    var A,
        I = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/;
    ((w.fn.init = function (e, t, n) {
        var i, a;
        if (!e) return this;
        if (((n = n || A), "string" == typeof e)) {
            if (!(i = "<" === e[0] && ">" === e[e.length - 1] && 3 <= e.length ? [null, e, null] : I.exec(e)) || (!i[1] && t)) return !t || t.jquery ? (t || n).find(e) : this.constructor(t).find(e);
            if (i[1]) {
                if (((t = t instanceof w ? t[0] : t), w.merge(this, w.parseHTML(i[1], t && t.nodeType ? t.ownerDocument || t : v, !0)), k.test(i[1]) && w.isPlainObject(t))) for (i in t) m(this[i]) ? this[i](t[i]) : this.attr(i, t[i]);
                return this;
            }
            return (a = v.getElementById(i[2])) && ((this[0] = a), (this.length = 1)), this;
        }
        return e.nodeType ? ((this[0] = e), (this.length = 1), this) : m(e) ? (void 0 !== n.ready ? n.ready(e) : e(w)) : w.makeArray(e, this);
    }).prototype = w.fn),
        (A = w(v));
    var P = /^(?:parents|prev(?:Until|All))/,
        N = { children: !0, contents: !0, next: !0, prev: !0 };
    function M(e, t) {
        for (; (e = e[t]) && 1 !== e.nodeType; );
        return e;
    }
    w.fn.extend({
        has: function (e) {
            var t = w(e, this),
                n = t.length;
            return this.filter(function () {
                for (var e = 0; e < n; e++) if (w.contains(this, t[e])) return !0;
            });
        },
        closest: function (e, t) {
            var n,
                i = 0,
                a = this.length,
                s = [],
                o = "string" != typeof e && w(e);
            if (!E.test(e)) {
                for (; i < a; i++)
                    for (n = this[i]; n && n !== t; n = n.parentNode)
                        if (n.nodeType < 11 && (o ? -1 < o.index(n) : 1 === n.nodeType && w.find.matchesSelector(n, e))) {
                            s.push(n);
                            break;
                        }
            }
            return this.pushStack(1 < s.length ? w.uniqueSort(s) : s);
        },
        index: function (e) {
            return e ? ("string" == typeof e ? l.call(w(e), this[0]) : l.call(this, e.jquery ? e[0] : e)) : this[0] && this[0].parentNode ? this.first().prevAll().length : -1;
        },
        add: function (e, t) {
            return this.pushStack(w.uniqueSort(w.merge(this.get(), w(e, t))));
        },
        addBack: function (e) {
            return this.add(null == e ? this.prevObject : this.prevObject.filter(e));
        },
    }),
        w.each(
            {
                parent: function (e) {
                    var t = e.parentNode;
                    return t && 11 !== t.nodeType ? t : null;
                },
                parents: function (e) {
                    return T(e, "parentNode");
                },
                parentsUntil: function (e, t, n) {
                    return T(e, "parentNode", n);
                },
                next: function (e) {
                    return M(e, "nextSibling");
                },
                prev: function (e) {
                    return M(e, "previousSibling");
                },
                nextAll: function (e) {
                    return T(e, "nextSibling");
                },
                prevAll: function (e) {
                    return T(e, "previousSibling");
                },
                nextUntil: function (e, t, n) {
                    return T(e, "nextSibling", n);
                },
                prevUntil: function (e, t, n) {
                    return T(e, "previousSibling", n);
                },
                siblings: function (e) {
                    return C((e.parentNode || {}).firstChild, e);
                },
                children: function (e) {
                    return C(e.firstChild);
                },
                contents: function (e) {
                    return null != e.contentDocument && i(e.contentDocument) ? e.contentDocument : (D(e, "template") && (e = e.content || e), w.merge([], e.childNodes));
                },
            },
            function (e, t) {
                w.fn[e] = function (n, i) {
                    var a = w.map(this, t, n);
                    return "Until" !== e.slice(-5) && (i = n), i && "string" == typeof i && (a = w.filter(i, a)), 1 < this.length && (N[e] || w.uniqueSort(a), P.test(e) && a.reverse()), this.pushStack(a);
                };
            }
        );
    var O = /[^\x20\t\r\n\f]+/g;
    function R(e) {
        return e;
    }
    function z(e) {
        throw e;
    }
    function F(e, t, n, i) {
        var a;
        try {
            e && m((a = e.promise)) ? a.call(e).done(t).fail(n) : e && m((a = e.then)) ? a.call(e, t, n) : t.apply(void 0, [e].slice(i));
        } catch (s) {
            n.apply(void 0, [s]);
        }
    }
    (w.Callbacks = function (e) {
        e =
            "string" == typeof e
                ? ((t = e),
                  (n = {}),
                  w.each(t.match(O) || [], function (e, t) {
                      n[t] = !0;
                  }),
                  n)
                : w.extend({}, e);
        var t,
            n,
            i,
            a,
            s,
            o,
            l = [],
            u = [],
            d = -1,
            c = function () {
                for (o = o || e.once, s = i = !0; u.length; d = -1) for (a = u.shift(); ++d < l.length; ) !1 === l[d].apply(a[0], a[1]) && e.stopOnFalse && ((d = l.length), (a = !1));
                e.memory || (a = !1), (i = !1), o && (l = a ? [] : "");
            },
            p = {
                add: function () {
                    return (
                        l &&
                            (a && !i && ((d = l.length - 1), u.push(a)),
                            (function t(n) {
                                w.each(n, function (n, i) {
                                    m(i) ? (e.unique && p.has(i)) || l.push(i) : i && i.length && "string" !== b(i) && t(i);
                                });
                            })(arguments),
                            a && !i && c()),
                        this
                    );
                },
                remove: function () {
                    return (
                        w.each(arguments, function (e, t) {
                            for (var n; -1 < (n = w.inArray(t, l, n)); ) l.splice(n, 1), n <= d && d--;
                        }),
                        this
                    );
                },
                has: function (e) {
                    return e ? -1 < w.inArray(e, l) : 0 < l.length;
                },
                empty: function () {
                    return l && (l = []), this;
                },
                disable: function () {
                    return (o = u = []), (l = a = ""), this;
                },
                disabled: function () {
                    return !l;
                },
                lock: function () {
                    return (o = u = []), a || i || (l = a = ""), this;
                },
                locked: function () {
                    return !!o;
                },
                fireWith: function (e, t) {
                    return o || ((t = [e, (t = t || []).slice ? t.slice() : t]), u.push(t), i || c()), this;
                },
                fire: function () {
                    return p.fireWith(this, arguments), this;
                },
                fired: function () {
                    return !!s;
                },
            };
        return p;
    }),
        w.extend({
            Deferred: function (t) {
                var n = [
                        ["notify", "progress", w.Callbacks("memory"), w.Callbacks("memory"), 2],
                        ["resolve", "done", w.Callbacks("once memory"), w.Callbacks("once memory"), 0, "resolved"],
                        ["reject", "fail", w.Callbacks("once memory"), w.Callbacks("once memory"), 1, "rejected"],
                    ],
                    i = "pending",
                    a = {
                        state: function () {
                            return i;
                        },
                        always: function () {
                            return s.done(arguments).fail(arguments), this;
                        },
                        catch: function (e) {
                            return a.then(null, e);
                        },
                        pipe: function () {
                            var e = arguments;
                            return w
                                .Deferred(function (t) {
                                    w.each(n, function (n, i) {
                                        var a = m(e[i[4]]) && e[i[4]];
                                        s[i[1]](function () {
                                            var e = a && a.apply(this, arguments);
                                            e && m(e.promise) ? e.promise().progress(t.notify).done(t.resolve).fail(t.reject) : t[i[0] + "With"](this, a ? [e] : arguments);
                                        });
                                    }),
                                        (e = null);
                                })
                                .promise();
                        },
                        then: function (t, i, a) {
                            var s = 0;
                            function o(t, n, i, a) {
                                return function () {
                                    var l = this,
                                        u = arguments,
                                        d = function () {
                                            var e, d;
                                            if (!(t < s)) {
                                                if ((e = i.apply(l, u)) === n.promise()) throw TypeError("Thenable self-resolution");
                                                m((d = e && ("object" == typeof e || "function" == typeof e) && e.then))
                                                    ? a
                                                        ? d.call(e, o(s, n, R, a), o(s, n, z, a))
                                                        : (s++, d.call(e, o(s, n, R, a), o(s, n, z, a), o(s, n, R, n.notifyWith)))
                                                    : (i !== R && ((l = void 0), (u = [e])), (a || n.resolveWith)(l, u));
                                            }
                                        },
                                        c = a
                                            ? d
                                            : function () {
                                                  try {
                                                      d();
                                                  } catch (e) {
                                                      w.Deferred.exceptionHook && w.Deferred.exceptionHook(e, c.stackTrace), s <= t + 1 && (i !== z && ((l = void 0), (u = [e])), n.rejectWith(l, u));
                                                  }
                                              };
                                    t ? c() : (w.Deferred.getStackHook && (c.stackTrace = w.Deferred.getStackHook()), e.setTimeout(c));
                                };
                            }
                            return w
                                .Deferred(function (e) {
                                    n[0][3].add(o(0, e, m(a) ? a : R, e.notifyWith)), n[1][3].add(o(0, e, m(t) ? t : R)), n[2][3].add(o(0, e, m(i) ? i : z));
                                })
                                .promise();
                        },
                        promise: function (e) {
                            return null != e ? w.extend(e, a) : a;
                        },
                    },
                    s = {};
                return (
                    w.each(n, function (e, t) {
                        var o = t[2],
                            l = t[5];
                        (a[t[1]] = o.add),
                            l &&
                                o.add(
                                    function () {
                                        i = l;
                                    },
                                    n[3 - e][2].disable,
                                    n[3 - e][3].disable,
                                    n[0][2].lock,
                                    n[0][3].lock
                                ),
                            o.add(t[3].fire),
                            (s[t[0]] = function () {
                                return s[t[0] + "With"](this === s ? void 0 : this, arguments), this;
                            }),
                            (s[t[0] + "With"] = o.fireWith);
                    }),
                    a.promise(s),
                    t && t.call(s, s),
                    s
                );
            },
            when: function (e) {
                var t = arguments.length,
                    n = t,
                    i = Array(n),
                    s = a.call(arguments),
                    o = w.Deferred(),
                    l = function (e) {
                        return function (n) {
                            (i[e] = this), (s[e] = 1 < arguments.length ? a.call(arguments) : n), --t || o.resolveWith(i, s);
                        };
                    };
                if (t <= 1 && (F(e, o.done(l(n)).resolve, o.reject, !t), "pending" === o.state() || m(s[n] && s[n].then))) return o.then();
                for (; n--; ) F(s[n], l(n), o.reject);
                return o.promise();
            },
        });
    var j = /^(Eval|Internal|Range|Reference|Syntax|Type|URI)Error$/;
    (w.Deferred.exceptionHook = function (t, n) {
        e.console && e.console.warn && t && j.test(t.name) && e.console.warn("jQuery.Deferred exception: " + t.message, t.stack, n);
    }),
        (w.readyException = function (t) {
            e.setTimeout(function () {
                throw t;
            });
        });
    var H = w.Deferred();
    function B() {
        v.removeEventListener("DOMContentLoaded", B), e.removeEventListener("load", B), w.ready();
    }
    (w.fn.ready = function (e) {
        return (
            H.then(e).catch(function (e) {
                w.readyException(e);
            }),
            this
        );
    }),
        w.extend({
            isReady: !1,
            readyWait: 1,
            ready: function (e) {
                (!0 === e ? --w.readyWait : w.isReady) || ((w.isReady = !0) !== e && 0 < --w.readyWait) || H.resolveWith(v, [w]);
            },
        }),
        (w.ready.then = H.then),
        "complete" !== v.readyState && ("loading" === v.readyState || v.documentElement.doScroll) ? (v.addEventListener("DOMContentLoaded", B), e.addEventListener("load", B)) : e.setTimeout(w.ready);
    var q = function (e, t, n, i, a, s, o) {
            var l = 0,
                u = e.length,
                d = null == n;
            if ("object" === b(n)) for (l in ((a = !0), n)) q(e, t, l, n[l], !0, s, o);
            else if (
                void 0 !== i &&
                ((a = !0),
                m(i) || (o = !0),
                d &&
                    (o
                        ? (t.call(e, i), (t = null))
                        : ((d = t),
                          (t = function (e, t, n) {
                              return d.call(w(e), n);
                          }))),
                t)
            )
                for (; l < u; l++) t(e[l], n, o ? i : i.call(e[l], l, t(e[l], n)));
            return a ? e : d ? t.call(e) : u ? t(e[0], n) : s;
        },
        W = /^-ms-/,
        G = /-([a-z])/g;
    function U(e, t) {
        return t.toUpperCase();
    }
    function V(e) {
        return e.replace(W, "ms-").replace(G, U);
    }
    var X = function (e) {
        return 1 === e.nodeType || 9 === e.nodeType || !+e.nodeType;
    };
    function Y() {
        this.expando = w.expando + Y.uid++;
    }
    (Y.uid = 1),
        (Y.prototype = {
            cache: function (e) {
                var t = e[this.expando];
                return t || ((t = {}), X(e) && (e.nodeType ? (e[this.expando] = t) : Object.defineProperty(e, this.expando, { value: t, configurable: !0 }))), t;
            },
            set: function (e, t, n) {
                var i,
                    a = this.cache(e);
                if ("string" == typeof t) a[V(t)] = n;
                else for (i in t) a[V(i)] = t[i];
                return a;
            },
            get: function (e, t) {
                return void 0 === t ? this.cache(e) : e[this.expando] && e[this.expando][V(t)];
            },
            access: function (e, t, n) {
                return void 0 === t || (t && "string" == typeof t && void 0 === n) ? this.get(e, t) : (this.set(e, t, n), void 0 !== n ? n : t);
            },
            remove: function (e, t) {
                var n,
                    i = e[this.expando];
                if (void 0 !== i) {
                    if (void 0 !== t) for (n = (t = Array.isArray(t) ? t.map(V) : ((t = V(t)) in i) ? [t] : t.match(O) || []).length; n--; ) delete i[t[n]];
                    (void 0 === t || w.isEmptyObject(i)) && (e.nodeType ? (e[this.expando] = void 0) : delete e[this.expando]);
                }
            },
            hasData: function (e) {
                var t = e[this.expando];
                return void 0 !== t && !w.isEmptyObject(t);
            },
        });
    var J = new Y(),
        K = new Y(),
        Z = /^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,
        Q = /[A-Z]/g;
    function ee(e, t, n) {
        var i, a;
        if (void 0 === n && 1 === e.nodeType) {
            if (((i = "data-" + t.replace(Q, "-$&").toLowerCase()), "string" == typeof (n = e.getAttribute(i)))) {
                try {
                    n = "true" === (a = n) || ("false" !== a && ("null" === a ? null : a === +a + "" ? +a : Z.test(a) ? JSON.parse(a) : a));
                } catch (s) {}
                K.set(e, t, n);
            } else n = void 0;
        }
        return n;
    }
    w.extend({
        hasData: function (e) {
            return K.hasData(e) || J.hasData(e);
        },
        data: function (e, t, n) {
            return K.access(e, t, n);
        },
        removeData: function (e, t) {
            K.remove(e, t);
        },
        _data: function (e, t, n) {
            return J.access(e, t, n);
        },
        _removeData: function (e, t) {
            J.remove(e, t);
        },
    }),
        w.fn.extend({
            data: function (e, t) {
                var n,
                    i,
                    a,
                    s = this[0],
                    o = s && s.attributes;
                if (void 0 === e) {
                    if (this.length && ((a = K.get(s)), 1 === s.nodeType && !J.get(s, "hasDataAttrs"))) {
                        for (n = o.length; n--; ) o[n] && 0 === (i = o[n].name).indexOf("data-") && ee(s, (i = V(i.slice(5))), a[i]);
                        J.set(s, "hasDataAttrs", !0);
                    }
                    return a;
                }
                return "object" == typeof e
                    ? this.each(function () {
                          K.set(this, e);
                      })
                    : q(
                          this,
                          function (t) {
                              var n;
                              if (s && void 0 === t) return void 0 !== (n = K.get(s, e)) || void 0 !== (n = ee(s, e)) ? n : void 0;
                              this.each(function () {
                                  K.set(this, e, t);
                              });
                          },
                          null,
                          t,
                          1 < arguments.length,
                          null,
                          !0
                      );
            },
            removeData: function (e) {
                return this.each(function () {
                    K.remove(this, e);
                });
            },
        }),
        w.extend({
            queue: function (e, t, n) {
                var i;
                if (e) return (t = (t || "fx") + "queue"), (i = J.get(e, t)), n && (!i || Array.isArray(n) ? (i = J.access(e, t, w.makeArray(n))) : i.push(n)), i || [];
            },
            dequeue: function (e, t) {
                t = t || "fx";
                var n = w.queue(e, t),
                    i = n.length,
                    a = n.shift(),
                    s = w._queueHooks(e, t);
                "inprogress" === a && ((a = n.shift()), i--),
                    a &&
                        ("fx" === t && n.unshift("inprogress"),
                        delete s.stop,
                        a.call(
                            e,
                            function () {
                                w.dequeue(e, t);
                            },
                            s
                        )),
                    !i && s && s.empty.fire();
            },
            _queueHooks: function (e, t) {
                var n = t + "queueHooks";
                return (
                    J.get(e, n) ||
                    J.access(e, n, {
                        empty: w.Callbacks("once memory").add(function () {
                            J.remove(e, [t + "queue", n]);
                        }),
                    })
                );
            },
        }),
        w.fn.extend({
            queue: function (e, t) {
                var n = 2;
                return (
                    "string" != typeof e && ((t = e), (e = "fx"), n--),
                    arguments.length < n
                        ? w.queue(this[0], e)
                        : void 0 === t
                        ? this
                        : this.each(function () {
                              var n = w.queue(this, e, t);
                              w._queueHooks(this, e), "fx" === e && "inprogress" !== n[0] && w.dequeue(this, e);
                          })
                );
            },
            dequeue: function (e) {
                return this.each(function () {
                    w.dequeue(this, e);
                });
            },
            clearQueue: function (e) {
                return this.queue(e || "fx", []);
            },
            promise: function (e, t) {
                var n,
                    i = 1,
                    a = w.Deferred(),
                    s = this,
                    o = this.length,
                    l = function () {
                        --i || a.resolveWith(s, [s]);
                    };
                for ("string" != typeof e && ((t = e), (e = void 0)), e = e || "fx"; o--; ) (n = J.get(s[o], e + "queueHooks")) && n.empty && (i++, n.empty.add(l));
                return l(), a.promise(t);
            },
        });
    var et = /[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,
        en = RegExp("^(?:([+-])=|)(" + et + ")([a-z%]*)$", "i"),
        ei = ["Top", "Right", "Bottom", "Left"],
        er = v.documentElement,
        ea = function (e) {
            return w.contains(e.ownerDocument, e);
        },
        es = { composed: !0 };
    er.getRootNode &&
        (ea = function (e) {
            return w.contains(e.ownerDocument, e) || e.getRootNode(es) === e.ownerDocument;
        });
    var eo = function (e, t) {
        return "none" === (e = t || e).style.display || ("" === e.style.display && ea(e) && "none" === w.css(e, "display"));
    };
    function el(e, t, n, i) {
        var a,
            s,
            o = 20,
            l = i
                ? function () {
                      return i.cur();
                  }
                : function () {
                      return w.css(e, t, "");
                  },
            u = l(),
            d = (n && n[3]) || (w.cssNumber[t] ? "" : "px"),
            c = e.nodeType && (w.cssNumber[t] || ("px" !== d && +u)) && en.exec(w.css(e, t));
        if (c && c[3] !== d) {
            for (u /= 2, d = d || c[3], c = +u || 1; o--; ) w.style(e, t, c + d), (1 - s) * (1 - (s = l() / u || 0.5)) <= 0 && (o = 0), (c /= s);
            (c *= 2), w.style(e, t, c + d), (n = n || []);
        }
        return n && ((c = +c || +u || 0), (a = n[1] ? c + (n[1] + 1) * n[2] : +n[2]), i && ((i.unit = d), (i.start = c), (i.end = a))), a;
    }
    var eu = {};
    function ed(e, t) {
        for (var n, i, a, s, o, l, u, d = [], c = 0, p = e.length; c < p; c++)
            (i = e[c]).style &&
                ((n = i.style.display),
                t
                    ? ("none" === n && ((d[c] = J.get(i, "display") || null), d[c] || (i.style.display = "")),
                      "" === i.style.display &&
                          eo(i) &&
                          (d[c] =
                              ((u = o = s = void 0),
                              (o = (a = i).ownerDocument),
                              (u = eu[(l = a.nodeName)]) || ((s = o.body.appendChild(o.createElement(l))), (u = w.css(s, "display")), s.parentNode.removeChild(s), "none" === u && (u = "block"), (eu[l] = u)))))
                    : "none" !== n && ((d[c] = "none"), J.set(i, "display", n)));
        for (c = 0; c < p; c++) null != d[c] && (e[c].style.display = d[c]);
        return e;
    }
    w.fn.extend({
        show: function () {
            return ed(this, !0);
        },
        hide: function () {
            return ed(this);
        },
        toggle: function (e) {
            return "boolean" == typeof e
                ? e
                    ? this.show()
                    : this.hide()
                : this.each(function () {
                      eo(this) ? w(this).show() : w(this).hide();
                  });
        },
    });
    var ec,
        ep,
        ef = /^(?:checkbox|radio)$/i,
        eh = /<([a-z][^\/\0>\x20\t\r\n\f]*)/i,
        em = /^$|^module$|\/(?:java|ecma)script/i;
    (ec = v.createDocumentFragment().appendChild(v.createElement("div"))),
        (ep = v.createElement("input")).setAttribute("type", "radio"),
        ep.setAttribute("checked", "checked"),
        ep.setAttribute("name", "t"),
        ec.appendChild(ep),
        (h.checkClone = ec.cloneNode(!0).cloneNode(!0).lastChild.checked),
        (ec.innerHTML = "<textarea>x</textarea>"),
        (h.noCloneChecked = !!ec.cloneNode(!0).lastChild.defaultValue),
        (ec.innerHTML = "<option></option>"),
        (h.option = !!ec.lastChild);
    var eg = { thead: [1, "<table>", "</table>"], col: [2, "<table><colgroup>", "</colgroup></table>"], tr: [2, "<table><tbody>", "</tbody></table>"], td: [3, "<table><tbody><tr>", "</tr></tbody></table>"], _default: [0, "", ""] };
    function ev(e, t) {
        var n;
        return (n = void 0 !== e.getElementsByTagName ? e.getElementsByTagName(t || "*") : void 0 !== e.querySelectorAll ? e.querySelectorAll(t || "*") : []), void 0 === t || (t && D(e, t)) ? w.merge([e], n) : n;
    }
    function e$(e, t) {
        for (var n = 0, i = e.length; n < i; n++) J.set(e[n], "globalEval", !t || J.get(t[n], "globalEval"));
    }
    (eg.tbody = eg.tfoot = eg.colgroup = eg.caption = eg.thead), (eg.th = eg.td), h.option || (eg.optgroup = eg.option = [1, "<select multiple='multiple'>", "</select>"]);
    var ey = /<|&#?\w+;/;
    function eb(e, t, n, i, a) {
        for (var s, o, l, u, d, c, p = t.createDocumentFragment(), f = [], h = 0, m = e.length; h < m; h++)
            if ((s = e[h]) || 0 === s) {
                if ("object" === b(s)) w.merge(f, s.nodeType ? [s] : s);
                else if (ey.test(s)) {
                    for (o = o || p.appendChild(t.createElement("div")), u = eg[(l = (eh.exec(s) || ["", ""])[1].toLowerCase())] || eg._default, o.innerHTML = u[1] + w.htmlPrefilter(s) + u[2], c = u[0]; c--; ) o = o.lastChild;
                    w.merge(f, o.childNodes), ((o = p.firstChild).textContent = "");
                } else f.push(t.createTextNode(s));
            }
        for (p.textContent = "", h = 0; (s = f[h++]); )
            if (i && -1 < w.inArray(s, i)) a && a.push(s);
            else if (((d = ea(s)), (o = ev(p.appendChild(s), "script")), d && e$(o), n)) for (c = 0; (s = o[c++]); ) em.test(s.type || "") && n.push(s);
        return p;
    }
    var e_ = /^key/,
        ew = /^(?:mouse|pointer|contextmenu|drag|drop)|click/,
        ex = /^([^.]*)(?:\.(.+)|)/;
    function eS() {
        return !0;
    }
    function eT() {
        return !1;
    }
    function eC(e, t) {
        return (
            (e ===
                (function () {
                    try {
                        return v.activeElement;
                    } catch (e) {}
                })()) ==
            ("focus" === t)
        );
    }
    function eE(e, t, n, i, a, s) {
        var o, l;
        if ("object" == typeof t) {
            for (l in ("string" != typeof n && ((i = i || n), (n = void 0)), t)) eE(e, l, n, i, t[l], s);
            return e;
        }
        if ((null == i && null == a ? ((a = n), (i = n = void 0)) : null == a && ("string" == typeof n ? ((a = i), (i = void 0)) : ((a = i), (i = n), (n = void 0))), !1 === a)) a = eT;
        else if (!a) return e;
        return (
            1 === s &&
                ((o = a),
                ((a = function (e) {
                    return w().off(e), o.apply(this, arguments);
                }).guid = o.guid || (o.guid = w.guid++))),
            e.each(function () {
                w.event.add(this, t, a, i, n);
            })
        );
    }
    function e8(e, t, n) {
        n
            ? (J.set(e, t, !1),
              w.event.add(e, t, {
                  namespace: !1,
                  handler: function (e) {
                      var i,
                          s,
                          o = J.get(this, t);
                      if (1 & e.isTrigger && this[t]) {
                          if (o.length) (w.event.special[t] || {}).delegateType && e.stopPropagation();
                          else if (((o = a.call(arguments)), J.set(this, t, o), (i = n(this, t)), this[t](), o !== (s = J.get(this, t)) || i ? J.set(this, t, !1) : (s = {}), o !== s))
                              return e.stopImmediatePropagation(), e.preventDefault(), s.value;
                      } else o.length && (J.set(this, t, { value: w.event.trigger(w.extend(o[0], w.Event.prototype), o.slice(1), this) }), e.stopImmediatePropagation());
                  },
              }))
            : void 0 === J.get(e, t) && w.event.add(e, t, eS);
    }
    (w.event = {
        global: {},
        add: function (e, t, n, i, a) {
            var s,
                o,
                l,
                u,
                d,
                c,
                p,
                f,
                h,
                m,
                g,
                v = J.get(e);
            if (X(e))
                for (
                    n.handler && ((n = (s = n).handler), (a = s.selector)),
                        a && w.find.matchesSelector(er, a),
                        n.guid || (n.guid = w.guid++),
                        (u = v.events) || (u = v.events = Object.create(null)),
                        (o = v.handle) ||
                            (o = v.handle = function (t) {
                                return void 0 !== w && w.event.triggered !== t.type ? w.event.dispatch.apply(e, arguments) : void 0;
                            }),
                        d = (t = (t || "").match(O) || [""]).length;
                    d--;

                )
                    (h = g = (l = ex.exec(t[d]) || [])[1]),
                        (m = (l[2] || "").split(".").sort()),
                        h &&
                            ((p = w.event.special[h] || {}),
                            (h = (a ? p.delegateType : p.bindType) || h),
                            (p = w.event.special[h] || {}),
                            (c = w.extend({ type: h, origType: g, data: i, handler: n, guid: n.guid, selector: a, needsContext: a && w.expr.match.needsContext.test(a), namespace: m.join(".") }, s)),
                            (f = u[h]) || (((f = u[h] = []).delegateCount = 0), (p.setup && !1 !== p.setup.call(e, i, m, o)) || (e.addEventListener && e.addEventListener(h, o))),
                            p.add && (p.add.call(e, c), c.handler.guid || (c.handler.guid = n.guid)),
                            a ? f.splice(f.delegateCount++, 0, c) : f.push(c),
                            (w.event.global[h] = !0));
        },
        remove: function (e, t, n, i, a) {
            var s,
                o,
                l,
                u,
                d,
                c,
                p,
                f,
                h,
                m,
                g,
                v = J.hasData(e) && J.get(e);
            if (v && (u = v.events)) {
                for (d = (t = (t || "").match(O) || [""]).length; d--; )
                    if (((h = g = (l = ex.exec(t[d]) || [])[1]), (m = (l[2] || "").split(".").sort()), h)) {
                        for (p = w.event.special[h] || {}, f = u[(h = (i ? p.delegateType : p.bindType) || h)] || [], l = l[2] && RegExp("(^|\\.)" + m.join("\\.(?:.*\\.|)") + "(\\.|$)"), o = s = f.length; s--; )
                            (c = f[s]),
                                (!a && g !== c.origType) ||
                                    (n && n.guid !== c.guid) ||
                                    (l && !l.test(c.namespace)) ||
                                    (i && i !== c.selector && ("**" !== i || !c.selector)) ||
                                    (f.splice(s, 1), c.selector && f.delegateCount--, p.remove && p.remove.call(e, c));
                        o && !f.length && ((p.teardown && !1 !== p.teardown.call(e, m, v.handle)) || w.removeEvent(e, h, v.handle), delete u[h]);
                    } else for (h in u) w.event.remove(e, h + t[d], n, i, !0);
                w.isEmptyObject(u) && J.remove(e, "handle events");
            }
        },
        dispatch: function (e) {
            var t,
                n,
                i,
                a,
                s,
                o,
                l = Array(arguments.length),
                u = w.event.fix(e),
                d = (J.get(this, "events") || Object.create(null))[u.type] || [],
                c = w.event.special[u.type] || {};
            for (l[0] = u, t = 1; t < arguments.length; t++) l[t] = arguments[t];
            if (((u.delegateTarget = this), !c.preDispatch || !1 !== c.preDispatch.call(this, u))) {
                for (o = w.event.handlers.call(this, u, d), t = 0; (a = o[t++]) && !u.isPropagationStopped(); )
                    for (u.currentTarget = a.elem, n = 0; (s = a.handlers[n++]) && !u.isImmediatePropagationStopped(); )
                        (u.rnamespace && !1 !== s.namespace && !u.rnamespace.test(s.namespace)) ||
                            ((u.handleObj = s), (u.data = s.data), void 0 !== (i = ((w.event.special[s.origType] || {}).handle || s.handler).apply(a.elem, l)) && !1 === (u.result = i) && (u.preventDefault(), u.stopPropagation()));
                return c.postDispatch && c.postDispatch.call(this, u), u.result;
            }
        },
        handlers: function (e, t) {
            var n,
                i,
                a,
                s,
                o,
                l = [],
                u = t.delegateCount,
                d = e.target;
            if (u && d.nodeType && !("click" === e.type && 1 <= e.button)) {
                for (; d !== this; d = d.parentNode || this)
                    if (1 === d.nodeType && ("click" !== e.type || !0 !== d.disabled)) {
                        for (s = [], o = {}, n = 0; n < u; n++) void 0 === o[(a = (i = t[n]).selector + " ")] && (o[a] = i.needsContext ? -1 < w(a, this).index(d) : w.find(a, this, null, [d]).length), o[a] && s.push(i);
                        s.length && l.push({ elem: d, handlers: s });
                    }
            }
            return (d = this), u < t.length && l.push({ elem: d, handlers: t.slice(u) }), l;
        },
        addProp: function (e, t) {
            Object.defineProperty(w.Event.prototype, e, {
                enumerable: !0,
                configurable: !0,
                get: m(t)
                    ? function () {
                          if (this.originalEvent) return t(this.originalEvent);
                      }
                    : function () {
                          if (this.originalEvent) return this.originalEvent[e];
                      },
                set: function (t) {
                    Object.defineProperty(this, e, { enumerable: !0, configurable: !0, writable: !0, value: t });
                },
            });
        },
        fix: function (e) {
            return e[w.expando] ? e : new w.Event(e);
        },
        special: {
            load: { noBubble: !0 },
            click: {
                setup: function (e) {
                    var t = this || e;
                    return ef.test(t.type) && t.click && D(t, "input") && e8(t, "click", eS), !1;
                },
                trigger: function (e) {
                    var t = this || e;
                    return ef.test(t.type) && t.click && D(t, "input") && e8(t, "click"), !0;
                },
                _default: function (e) {
                    var t = e.target;
                    return (ef.test(t.type) && t.click && D(t, "input") && J.get(t, "click")) || D(t, "a");
                },
            },
            beforeunload: {
                postDispatch: function (e) {
                    void 0 !== e.result && e.originalEvent && (e.originalEvent.returnValue = e.result);
                },
            },
        },
    }),
        (w.removeEvent = function (e, t, n) {
            e.removeEventListener && e.removeEventListener(t, n);
        }),
        (w.Event = function (e, t) {
            if (!(this instanceof w.Event)) return new w.Event(e, t);
            e && e.type
                ? ((this.originalEvent = e),
                  (this.type = e.type),
                  (this.isDefaultPrevented = e.defaultPrevented || (void 0 === e.defaultPrevented && !1 === e.returnValue) ? eS : eT),
                  (this.target = e.target && 3 === e.target.nodeType ? e.target.parentNode : e.target),
                  (this.currentTarget = e.currentTarget),
                  (this.relatedTarget = e.relatedTarget))
                : (this.type = e),
                t && w.extend(this, t),
                (this.timeStamp = (e && e.timeStamp) || Date.now()),
                (this[w.expando] = !0);
        }),
        (w.Event.prototype = {
            constructor: w.Event,
            isDefaultPrevented: eT,
            isPropagationStopped: eT,
            isImmediatePropagationStopped: eT,
            isSimulated: !1,
            preventDefault: function () {
                var e = this.originalEvent;
                (this.isDefaultPrevented = eS), e && !this.isSimulated && e.preventDefault();
            },
            stopPropagation: function () {
                var e = this.originalEvent;
                (this.isPropagationStopped = eS), e && !this.isSimulated && e.stopPropagation();
            },
            stopImmediatePropagation: function () {
                var e = this.originalEvent;
                (this.isImmediatePropagationStopped = eS), e && !this.isSimulated && e.stopImmediatePropagation(), this.stopPropagation();
            },
        }),
        w.each(
            {
                altKey: !0,
                bubbles: !0,
                cancelable: !0,
                changedTouches: !0,
                ctrlKey: !0,
                detail: !0,
                eventPhase: !0,
                metaKey: !0,
                pageX: !0,
                pageY: !0,
                shiftKey: !0,
                view: !0,
                char: !0,
                code: !0,
                charCode: !0,
                key: !0,
                keyCode: !0,
                button: !0,
                buttons: !0,
                clientX: !0,
                clientY: !0,
                offsetX: !0,
                offsetY: !0,
                pointerId: !0,
                pointerType: !0,
                screenX: !0,
                screenY: !0,
                targetTouches: !0,
                toElement: !0,
                touches: !0,
                which: function (e) {
                    var t = e.button;
                    return null == e.which && e_.test(e.type) ? (null != e.charCode ? e.charCode : e.keyCode) : !e.which && void 0 !== t && ew.test(e.type) ? (1 & t ? 1 : 2 & t ? 3 : 4 & t ? 2 : 0) : e.which;
                },
            },
            w.event.addProp
        ),
        w.each({ focus: "focusin", blur: "focusout" }, function (e, t) {
            w.event.special[e] = {
                setup: function () {
                    return e8(this, e, eC), !1;
                },
                trigger: function () {
                    return e8(this, e), !0;
                },
                delegateType: t,
            };
        }),
        w.each({ mouseenter: "mouseover", mouseleave: "mouseout", pointerenter: "pointerover", pointerleave: "pointerout" }, function (e, t) {
            w.event.special[e] = {
                delegateType: t,
                bindType: t,
                handle: function (e) {
                    var n,
                        i = e.relatedTarget,
                        a = e.handleObj;
                    return (i && (i === this || w.contains(this, i))) || ((e.type = a.origType), (n = a.handler.apply(this, arguments)), (e.type = t)), n;
                },
            };
        }),
        w.fn.extend({
            on: function (e, t, n, i) {
                return eE(this, e, t, n, i);
            },
            one: function (e, t, n, i) {
                return eE(this, e, t, n, i, 1);
            },
            off: function (e, t, n) {
                var i, a;
                if (e && e.preventDefault && e.handleObj) return (i = e.handleObj), w(e.delegateTarget).off(i.namespace ? i.origType + "." + i.namespace : i.origType, i.selector, i.handler), this;
                if ("object" == typeof e) {
                    for (a in e) this.off(a, t, e[a]);
                    return this;
                }
                return (
                    (!1 !== t && "function" != typeof t) || ((n = t), (t = void 0)),
                    !1 === n && (n = eT),
                    this.each(function () {
                        w.event.remove(this, e, n, t);
                    })
                );
            },
        });
    var eD = /<script|<style|<link/i,
        ek = /checked\s*(?:[^=]|=\s*.checked.)/i,
        eL = /^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;
    function eA(e, t) {
        return (D(e, "table") && D(11 !== t.nodeType ? t : t.firstChild, "tr") && w(e).children("tbody")[0]) || e;
    }
    function eI(e) {
        return (e.type = (null !== e.getAttribute("type")) + "/" + e.type), e;
    }
    function eP(e) {
        return "true/" === (e.type || "").slice(0, 5) ? (e.type = e.type.slice(5)) : e.removeAttribute("type"), e;
    }
    function e0(e, t) {
        var n, i, a, s, o, l;
        if (1 === t.nodeType) {
            if (J.hasData(e) && (l = J.get(e).events)) for (a in (J.remove(t, "handle events"), l)) for (n = 0, i = l[a].length; n < i; n++) w.event.add(t, a, l[a][n]);
            K.hasData(e) && ((s = K.access(e)), (o = w.extend({}, s)), K.set(t, o));
        }
    }
    function eN(e, t, n, i) {
        t = s(t);
        var a,
            o,
            l,
            u,
            d,
            c,
            p = 0,
            f = e.length,
            g = f - 1,
            v = t[0],
            $ = m(v);
        if ($ || (1 < f && "string" == typeof v && !h.checkClone && ek.test(v)))
            return e.each(function (a) {
                var s = e.eq(a);
                $ && (t[0] = v.call(this, a, s.html())), eN(s, t, n, i);
            });
        if (f && ((o = (a = eb(t, e[0].ownerDocument, !1, e, i)).firstChild), 1 === a.childNodes.length && (a = o), o || i)) {
            for (u = (l = w.map(ev(a, "script"), eI)).length; p < f; p++) (d = a), p !== g && ((d = w.clone(d, !0, !0)), u && w.merge(l, ev(d, "script"))), n.call(e[p], d, p);
            if (u)
                for (c = l[l.length - 1].ownerDocument, w.map(l, eP), p = 0; p < u; p++)
                    (d = l[p]),
                        em.test(d.type || "") &&
                            !J.access(d, "globalEval") &&
                            w.contains(c, d) &&
                            (d.src && "module" !== (d.type || "").toLowerCase() ? w._evalUrl && !d.noModule && w._evalUrl(d.src, { nonce: d.nonce || d.getAttribute("nonce") }, c) : y(d.textContent.replace(eL, ""), d, c));
        }
        return e;
    }
    function eM(e, t, n) {
        for (var i, a = t ? w.filter(t, e) : e, s = 0; null != (i = a[s]); s++) n || 1 !== i.nodeType || w.cleanData(ev(i)), i.parentNode && (n && ea(i) && e$(ev(i, "script")), i.parentNode.removeChild(i));
        return e;
    }
    w.extend({
        htmlPrefilter: function (e) {
            return e;
        },
        clone: function (e, t, n) {
            var i,
                a,
                s,
                o,
                l,
                u,
                d,
                c = e.cloneNode(!0),
                p = ea(e);
            if (!(h.noCloneChecked || (1 !== e.nodeType && 11 !== e.nodeType) || w.isXMLDoc(e)))
                for (o = ev(c), i = 0, a = (s = ev(e)).length; i < a; i++)
                    (l = s[i]), "input" === (d = (u = o[i]).nodeName.toLowerCase()) && ef.test(l.type) ? (u.checked = l.checked) : ("input" !== d && "textarea" !== d) || (u.defaultValue = l.defaultValue);
            if (t) {
                if (n) for (s = s || ev(e), o = o || ev(c), i = 0, a = s.length; i < a; i++) e0(s[i], o[i]);
                else e0(e, c);
            }
            return 0 < (o = ev(c, "script")).length && e$(o, !p && ev(e, "script")), c;
        },
        cleanData: function (e) {
            for (var t, n, i, a = w.event.special, s = 0; void 0 !== (n = e[s]); s++)
                if (X(n)) {
                    if ((t = n[J.expando])) {
                        if (t.events) for (i in t.events) a[i] ? w.event.remove(n, i) : w.removeEvent(n, i, t.handle);
                        n[J.expando] = void 0;
                    }
                    n[K.expando] && (n[K.expando] = void 0);
                }
        },
    }),
        w.fn.extend({
            detach: function (e) {
                return eM(this, e, !0);
            },
            remove: function (e) {
                return eM(this, e);
            },
            text: function (e) {
                return q(
                    this,
                    function (e) {
                        return void 0 === e
                            ? w.text(this)
                            : this.empty().each(function () {
                                  (1 !== this.nodeType && 11 !== this.nodeType && 9 !== this.nodeType) || (this.textContent = e);
                              });
                    },
                    null,
                    e,
                    arguments.length
                );
            },
            append: function () {
                return eN(this, arguments, function (e) {
                    (1 !== this.nodeType && 11 !== this.nodeType && 9 !== this.nodeType) || eA(this, e).appendChild(e);
                });
            },
            prepend: function () {
                return eN(this, arguments, function (e) {
                    if (1 === this.nodeType || 11 === this.nodeType || 9 === this.nodeType) {
                        var t = eA(this, e);
                        t.insertBefore(e, t.firstChild);
                    }
                });
            },
            before: function () {
                return eN(this, arguments, function (e) {
                    this.parentNode && this.parentNode.insertBefore(e, this);
                });
            },
            after: function () {
                return eN(this, arguments, function (e) {
                    this.parentNode && this.parentNode.insertBefore(e, this.nextSibling);
                });
            },
            empty: function () {
                for (var e, t = 0; null != (e = this[t]); t++) 1 === e.nodeType && (w.cleanData(ev(e, !1)), (e.textContent = ""));
                return this;
            },
            clone: function (e, t) {
                return (
                    (e = null != e && e),
                    (t = null == t ? e : t),
                    this.map(function () {
                        return w.clone(this, e, t);
                    })
                );
            },
            html: function (e) {
                return q(
                    this,
                    function (e) {
                        var t = this[0] || {},
                            n = 0,
                            i = this.length;
                        if (void 0 === e && 1 === t.nodeType) return t.innerHTML;
                        if ("string" == typeof e && !eD.test(e) && !eg[(eh.exec(e) || ["", ""])[1].toLowerCase()]) {
                            e = w.htmlPrefilter(e);
                            try {
                                for (; n < i; n++) 1 === (t = this[n] || {}).nodeType && (w.cleanData(ev(t, !1)), (t.innerHTML = e));
                                t = 0;
                            } catch (a) {}
                        }
                        t && this.empty().append(e);
                    },
                    null,
                    e,
                    arguments.length
                );
            },
            replaceWith: function () {
                var e = [];
                return eN(
                    this,
                    arguments,
                    function (t) {
                        var n = this.parentNode;
                        0 > w.inArray(this, e) && (w.cleanData(ev(this)), n && n.replaceChild(t, this));
                    },
                    e
                );
            },
        }),
        w.each({ appendTo: "append", prependTo: "prepend", insertBefore: "before", insertAfter: "after", replaceAll: "replaceWith" }, function (e, t) {
            w.fn[e] = function (e) {
                for (var n, i = [], a = w(e), s = a.length - 1, l = 0; l <= s; l++) (n = l === s ? this : this.clone(!0)), w(a[l])[t](n), o.apply(i, n.get());
                return this.pushStack(i);
            };
        });
    var eO = RegExp("^(" + et + ")(?!px)[a-z%]+$", "i"),
        eR = function (t) {
            var n = t.ownerDocument.defaultView;
            return (n && n.opener) || (n = e), n.getComputedStyle(t);
        },
        ez = function (e, t, n) {
            var i,
                a,
                s = {};
            for (a in t) (s[a] = e.style[a]), (e.style[a] = t[a]);
            for (a in ((i = n.call(e)), t)) e.style[a] = s[a];
            return i;
        },
        e9 = RegExp(ei.join("|"), "i");
    function eF(e, t, n) {
        var i,
            a,
            s,
            o,
            l = e.style;
        return (
            (n = n || eR(e)) &&
                ("" !== (o = n.getPropertyValue(t) || n[t]) || ea(e) || (o = w.style(e, t)),
                !h.pixelBoxStyles() && eO.test(o) && e9.test(t) && ((i = l.width), (a = l.minWidth), (s = l.maxWidth), (l.minWidth = l.maxWidth = l.width = o), (o = n.width), (l.width = i), (l.minWidth = a), (l.maxWidth = s))),
            void 0 !== o ? o + "" : o
        );
    }
    function ej(e, t) {
        return {
            get: function () {
                if (!e()) return (this.get = t).apply(this, arguments);
                delete this.get;
            },
        };
    }
    !(function () {
        function t() {
            if (c) {
                (d.style.cssText = "position:absolute;left:-11111px;width:60px;margin-top:1px;padding:0;border:0"),
                    (c.style.cssText = "position:relative;display:block;box-sizing:border-box;overflow:scroll;margin:auto;border:1px;padding:1px;width:60%;top:1%"),
                    er.appendChild(d).appendChild(c);
                var t = e.getComputedStyle(c);
                (i = "1%" !== t.top),
                    (u = 12 === n(t.marginLeft)),
                    (c.style.right = "60%"),
                    (o = 36 === n(t.right)),
                    (a = 36 === n(t.width)),
                    (c.style.position = "absolute"),
                    (s = 12 === n(c.offsetWidth / 3)),
                    er.removeChild(d),
                    (c = null);
            }
        }
        function n(e) {
            return Math.round(parseFloat(e));
        }
        var i,
            a,
            s,
            o,
            l,
            u,
            d = v.createElement("div"),
            c = v.createElement("div");
        c.style &&
            ((c.style.backgroundClip = "content-box"),
            (c.cloneNode(!0).style.backgroundClip = ""),
            (h.clearCloneStyle = "content-box" === c.style.backgroundClip),
            w.extend(h, {
                boxSizingReliable: function () {
                    return t(), a;
                },
                pixelBoxStyles: function () {
                    return t(), o;
                },
                pixelPosition: function () {
                    return t(), i;
                },
                reliableMarginLeft: function () {
                    return t(), u;
                },
                scrollboxSize: function () {
                    return t(), s;
                },
                reliableTrDimensions: function () {
                    var t, n, i, a;
                    return (
                        null == l &&
                            ((t = v.createElement("table")),
                            (n = v.createElement("tr")),
                            (i = v.createElement("div")),
                            (t.style.cssText = "position:absolute;left:-11111px"),
                            (n.style.height = "1px"),
                            (i.style.height = "9px"),
                            er.appendChild(t).appendChild(n).appendChild(i),
                            (l = 3 < parseInt((a = e.getComputedStyle(n)).height)),
                            er.removeChild(t)),
                        l
                    );
                },
            }));
    })();
    var eH = ["Webkit", "Moz", "ms"],
        e1 = v.createElement("div").style,
        eB = {};
    function eq(e) {
        return (
            w.cssProps[e] ||
            eB[e] ||
            (e in e1
                ? e
                : (eB[e] =
                      (function (e) {
                          for (var t = e[0].toUpperCase() + e.slice(1), n = eH.length; n--; ) if ((e = eH[n] + t) in e1) return e;
                      })(e) || e))
        );
    }
    var e3 = /^(none|table(?!-c[ea]).+)/,
        eW = /^--/,
        eG = { position: "absolute", visibility: "hidden", display: "block" },
        e2 = { letterSpacing: "0", fontWeight: "400" };
    function e4(e, t, n) {
        var i = en.exec(t);
        return i ? Math.max(0, i[2] - (n || 0)) + (i[3] || "px") : t;
    }
    function e6(e, t, n, i, a, s) {
        var o = "width" === t ? 1 : 0,
            l = 0,
            u = 0;
        if (n === (i ? "border" : "content")) return 0;
        for (; o < 4; o += 2)
            "margin" === n && (u += w.css(e, n + ei[o], !0, a)),
                i
                    ? ("content" === n && (u -= w.css(e, "padding" + ei[o], !0, a)), "margin" !== n && (u -= w.css(e, "border" + ei[o] + "Width", !0, a)))
                    : ((u += w.css(e, "padding" + ei[o], !0, a)), "padding" !== n ? (u += w.css(e, "border" + ei[o] + "Width", !0, a)) : (l += w.css(e, "border" + ei[o] + "Width", !0, a)));
        return !i && 0 <= s && (u += Math.max(0, Math.ceil(e["offset" + t[0].toUpperCase() + t.slice(1)] - s - u - l - 0.5)) || 0), u;
    }
    function eU(e, t, n) {
        var i = eR(e),
            a = (!h.boxSizingReliable() || n) && "border-box" === w.css(e, "boxSizing", !1, i),
            s = a,
            o = eF(e, t, i),
            l = "offset" + t[0].toUpperCase() + t.slice(1);
        if (eO.test(o)) {
            if (!n) return o;
            o = "auto";
        }
        return (
            ((!h.boxSizingReliable() && a) || (!h.reliableTrDimensions() && D(e, "tr")) || "auto" === o || (!parseFloat(o) && "inline" === w.css(e, "display", !1, i))) &&
                e.getClientRects().length &&
                ((a = "border-box" === w.css(e, "boxSizing", !1, i)), (s = l in e) && (o = e[l])),
            (o = parseFloat(o) || 0) + e6(e, t, n || (a ? "border" : "content"), s, i, o) + "px"
        );
    }
    function eV(e, t, n, i, a) {
        return new eV.prototype.init(e, t, n, i, a);
    }
    w.extend({
        cssHooks: {
            opacity: {
                get: function (e, t) {
                    if (t) {
                        var n = eF(e, "opacity");
                        return "" === n ? "1" : n;
                    }
                },
            },
        },
        cssNumber: {
            animationIterationCount: !0,
            columnCount: !0,
            fillOpacity: !0,
            flexGrow: !0,
            flexShrink: !0,
            fontWeight: !0,
            gridArea: !0,
            gridColumn: !0,
            gridColumnEnd: !0,
            gridColumnStart: !0,
            gridRow: !0,
            gridRowEnd: !0,
            gridRowStart: !0,
            lineHeight: !0,
            opacity: !0,
            order: !0,
            orphans: !0,
            widows: !0,
            zIndex: !0,
            zoom: !0,
        },
        cssProps: {},
        style: function (e, t, n, i) {
            if (e && 3 !== e.nodeType && 8 !== e.nodeType && e.style) {
                var a,
                    s,
                    o,
                    l = V(t),
                    u = eW.test(t),
                    d = e.style;
                if ((u || (t = eq(l)), (o = w.cssHooks[t] || w.cssHooks[l]), void 0 === n)) return o && "get" in o && void 0 !== (a = o.get(e, !1, i)) ? a : d[t];
                "string" == (s = typeof n) && (a = en.exec(n)) && a[1] && ((n = el(e, t, a)), (s = "number")),
                    null != n &&
                        n == n &&
                        ("number" !== s || u || (n += (a && a[3]) || (w.cssNumber[l] ? "" : "px")),
                        h.clearCloneStyle || "" !== n || 0 !== t.indexOf("background") || (d[t] = "inherit"),
                        (o && "set" in o && void 0 === (n = o.set(e, n, i))) || (u ? d.setProperty(t, n) : (d[t] = n)));
            }
        },
        css: function (e, t, n, i) {
            var a,
                s,
                o,
                l = V(t);
            return (
                eW.test(t) || (t = eq(l)),
                (o = w.cssHooks[t] || w.cssHooks[l]) && "get" in o && (a = o.get(e, !0, n)),
                void 0 === a && (a = eF(e, t, i)),
                "normal" === a && t in e2 && (a = e2[t]),
                "" === n || n ? ((s = parseFloat(a)), !0 === n || isFinite(s) ? s || 0 : a) : a
            );
        },
    }),
        w.each(["height", "width"], function (e, t) {
            w.cssHooks[t] = {
                get: function (e, n, i) {
                    if (n)
                        return !e3.test(w.css(e, "display")) || (e.getClientRects().length && e.getBoundingClientRect().width)
                            ? eU(e, t, i)
                            : ez(e, eG, function () {
                                  return eU(e, t, i);
                              });
                },
                set: function (e, n, i) {
                    var a,
                        s = eR(e),
                        o = !h.scrollboxSize() && "absolute" === s.position,
                        l = (o || i) && "border-box" === w.css(e, "boxSizing", !1, s),
                        u = i ? e6(e, t, i, l, s) : 0;
                    return (
                        l && o && (u -= Math.ceil(e["offset" + t[0].toUpperCase() + t.slice(1)] - parseFloat(s[t]) - e6(e, t, "border", !1, s) - 0.5)),
                        u && (a = en.exec(n)) && "px" !== (a[3] || "px") && ((e.style[t] = n), (n = w.css(e, t))),
                        e4(0, n, u)
                    );
                },
            };
        }),
        (w.cssHooks.marginLeft = ej(h.reliableMarginLeft, function (e, t) {
            if (t)
                return (
                    (parseFloat(eF(e, "marginLeft")) ||
                        e.getBoundingClientRect().left -
                            ez(e, { marginLeft: 0 }, function () {
                                return e.getBoundingClientRect().left;
                            })) + "px"
                );
        })),
        w.each({ margin: "", padding: "", border: "Width" }, function (e, t) {
            (w.cssHooks[e + t] = {
                expand: function (n) {
                    for (var i = 0, a = {}, s = "string" == typeof n ? n.split(" ") : [n]; i < 4; i++) a[e + ei[i] + t] = s[i] || s[i - 2] || s[0];
                    return a;
                },
            }),
                "margin" !== e && (w.cssHooks[e + t].set = e4);
        }),
        w.fn.extend({
            css: function (e, t) {
                return q(
                    this,
                    function (e, t, n) {
                        var i,
                            a,
                            s = {},
                            o = 0;
                        if (Array.isArray(t)) {
                            for (i = eR(e), a = t.length; o < a; o++) s[t[o]] = w.css(e, t[o], !1, i);
                            return s;
                        }
                        return void 0 !== n ? w.style(e, t, n) : w.css(e, t);
                    },
                    e,
                    t,
                    1 < arguments.length
                );
            },
        }),
        (((w.Tween = eV).prototype = {
            constructor: eV,
            init: function (e, t, n, i, a, s) {
                (this.elem = e), (this.prop = n), (this.easing = a || w.easing._default), (this.options = t), (this.start = this.now = this.cur()), (this.end = i), (this.unit = s || (w.cssNumber[n] ? "" : "px"));
            },
            cur: function () {
                var e = eV.propHooks[this.prop];
                return e && e.get ? e.get(this) : eV.propHooks._default.get(this);
            },
            run: function (e) {
                var t,
                    n = eV.propHooks[this.prop];
                return (
                    this.options.duration ? (this.pos = t = w.easing[this.easing](e, this.options.duration * e, 0, 1, this.options.duration)) : (this.pos = t = e),
                    (this.now = (this.end - this.start) * t + this.start),
                    this.options.step && this.options.step.call(this.elem, this.now, this),
                    n && n.set ? n.set(this) : eV.propHooks._default.set(this),
                    this
                );
            },
        }).init.prototype = eV.prototype),
        ((eV.propHooks = {
            _default: {
                get: function (e) {
                    var t;
                    return 1 !== e.elem.nodeType || (null != e.elem[e.prop] && null == e.elem.style[e.prop]) ? e.elem[e.prop] : (t = w.css(e.elem, e.prop, "")) && "auto" !== t ? t : 0;
                },
                set: function (e) {
                    w.fx.step[e.prop] ? w.fx.step[e.prop](e) : 1 === e.elem.nodeType && (w.cssHooks[e.prop] || null != e.elem.style[eq(e.prop)]) ? w.style(e.elem, e.prop, e.now + e.unit) : (e.elem[e.prop] = e.now);
                },
            },
        }).scrollTop = eV.propHooks.scrollLeft = {
            set: function (e) {
                e.elem.nodeType && e.elem.parentNode && (e.elem[e.prop] = e.now);
            },
        }),
        (w.easing = {
            linear: function (e) {
                return e;
            },
            swing: function (e) {
                return 0.5 - Math.cos(e * Math.PI) / 2;
            },
            _default: "swing",
        }),
        (w.fx = eV.prototype.init),
        (w.fx.step = {});
    var eX,
        e7,
        eY,
        e5,
        eJ = /^(?:toggle|show|hide)$/,
        eK = /queueHooks$/;
    function eZ() {
        return (
            e.setTimeout(function () {
                eX = void 0;
            }),
            (eX = Date.now())
        );
    }
    function eQ(e, t) {
        var n,
            i = 0,
            a = { height: e };
        for (t = t ? 1 : 0; i < 4; i += 2 - t) a["margin" + (n = ei[i])] = a["padding" + n] = e;
        return t && (a.opacity = a.width = e), a;
    }
    function te(e, t, n) {
        for (var i, a = (tt.tweeners[t] || []).concat(tt.tweeners["*"]), s = 0, o = a.length; s < o; s++) if ((i = a[s].call(n, t, e))) return i;
    }
    function tt(e, t, n) {
        var i,
            a,
            s = 0,
            o = tt.prefilters.length,
            l = w.Deferred().always(function () {
                delete u.elem;
            }),
            u = function () {
                if (a) return !1;
                for (var t = eX || eZ(), n = Math.max(0, d.startTime + d.duration - t), i = 1 - (n / d.duration || 0), s = 0, o = d.tweens.length; s < o; s++) d.tweens[s].run(i);
                return l.notifyWith(e, [d, i, n]), i < 1 && o ? n : (o || l.notifyWith(e, [d, 1, 0]), l.resolveWith(e, [d]), !1);
            },
            d = l.promise({
                elem: e,
                props: w.extend({}, t),
                opts: w.extend(!0, { specialEasing: {}, easing: w.easing._default }, n),
                originalProperties: t,
                originalOptions: n,
                startTime: eX || eZ(),
                duration: n.duration,
                tweens: [],
                createTween: function (t, n) {
                    var i = w.Tween(e, d.opts, t, n, d.opts.specialEasing[t] || d.opts.easing);
                    return d.tweens.push(i), i;
                },
                stop: function (t) {
                    var n = 0,
                        i = t ? d.tweens.length : 0;
                    if (a) return this;
                    for (a = !0; n < i; n++) d.tweens[n].run(1);
                    return t ? (l.notifyWith(e, [d, 1, 0]), l.resolveWith(e, [d, t])) : l.rejectWith(e, [d, t]), this;
                },
            }),
            c = d.props;
        for (
            (function (e, t) {
                var n, i, a, s, o;
                for (n in e)
                    if (((a = t[(i = V(n))]), Array.isArray((s = e[n])) && ((a = s[1]), (s = e[n] = s[0])), n !== i && ((e[i] = s), delete e[n]), (o = w.cssHooks[i]) && ("expand" in o)))
                        for (n in ((s = o.expand(s)), delete e[i], s)) (n in e) || ((e[n] = s[n]), (t[n] = a));
                    else t[i] = a;
            })(c, d.opts.specialEasing);
            s < o;
            s++
        )
            if ((i = tt.prefilters[s].call(d, e, c, d.opts))) return m(i.stop) && (w._queueHooks(d.elem, d.opts.queue).stop = i.stop.bind(i)), i;
        return (
            w.map(c, te, d),
            m(d.opts.start) && d.opts.start.call(e, d),
            d.progress(d.opts.progress).done(d.opts.done, d.opts.complete).fail(d.opts.fail).always(d.opts.always),
            w.fx.timer(w.extend(u, { elem: e, anim: d, queue: d.opts.queue })),
            d
        );
    }
    (w.Animation = w.extend(tt, {
        tweeners: {
            "*": [
                function (e, t) {
                    var n = this.createTween(e, t);
                    return el(n.elem, e, en.exec(t), n), n;
                },
            ],
        },
        tweener: function (e, t) {
            m(e) ? ((t = e), (e = ["*"])) : (e = e.match(O));
            for (var n, i = 0, a = e.length; i < a; i++) (n = e[i]), (tt.tweeners[n] = tt.tweeners[n] || []), tt.tweeners[n].unshift(t);
        },
        prefilters: [
            function (e, t, n) {
                var i,
                    a,
                    s,
                    o,
                    l,
                    u,
                    d,
                    c,
                    p = "width" in t || "height" in t,
                    f = this,
                    h = {},
                    m = e.style,
                    g = e.nodeType && eo(e),
                    v = J.get(e, "fxshow");
                for (i in (n.queue ||
                    (null == (o = w._queueHooks(e, "fx")).unqueued &&
                        ((o.unqueued = 0),
                        (l = o.empty.fire),
                        (o.empty.fire = function () {
                            o.unqueued || l();
                        })),
                    o.unqueued++,
                    f.always(function () {
                        f.always(function () {
                            o.unqueued--, w.queue(e, "fx").length || o.empty.fire();
                        });
                    })),
                t))
                    if (((a = t[i]), eJ.test(a))) {
                        if ((delete t[i], (s = s || "toggle" === a), a === (g ? "hide" : "show"))) {
                            if ("show" !== a || !v || void 0 === v[i]) continue;
                            g = !0;
                        }
                        h[i] = (v && v[i]) || w.style(e, i);
                    }
                if ((u = !w.isEmptyObject(t)) || !w.isEmptyObject(h))
                    for (i in (p &&
                        1 === e.nodeType &&
                        ((n.overflow = [m.overflow, m.overflowX, m.overflowY]),
                        null == (d = v && v.display) && (d = J.get(e, "display")),
                        "none" === (c = w.css(e, "display")) && (d ? (c = d) : (ed([e], !0), (d = e.style.display || d), (c = w.css(e, "display")), ed([e]))),
                        ("inline" === c || ("inline-block" === c && null != d)) &&
                            "none" === w.css(e, "float") &&
                            (u ||
                                (f.done(function () {
                                    m.display = d;
                                }),
                                null == d && (d = "none" === (c = m.display) ? "" : c)),
                            (m.display = "inline-block"))),
                    n.overflow &&
                        ((m.overflow = "hidden"),
                        f.always(function () {
                            (m.overflow = n.overflow[0]), (m.overflowX = n.overflow[1]), (m.overflowY = n.overflow[2]);
                        })),
                    (u = !1),
                    h))
                        u ||
                            (v ? "hidden" in v && (g = v.hidden) : (v = J.access(e, "fxshow", { display: d })),
                            s && (v.hidden = !g),
                            g && ed([e], !0),
                            f.done(function () {
                                for (i in (g || ed([e]), J.remove(e, "fxshow"), h)) w.style(e, i, h[i]);
                            })),
                            (u = te(g ? v[i] : 0, i, f)),
                            i in v || ((v[i] = u.start), g && ((u.end = u.start), (u.start = 0)));
            },
        ],
        prefilter: function (e, t) {
            t ? tt.prefilters.unshift(e) : tt.prefilters.push(e);
        },
    })),
        (w.speed = function (e, t, n) {
            var i = e && "object" == typeof e ? w.extend({}, e) : { complete: n || (!n && t) || (m(e) && e), duration: e, easing: (n && t) || (t && !m(t) && t) };
            return (
                w.fx.off ? (i.duration = 0) : "number" != typeof i.duration && (i.duration in w.fx.speeds ? (i.duration = w.fx.speeds[i.duration]) : (i.duration = w.fx.speeds._default)),
                (null != i.queue && !0 !== i.queue) || (i.queue = "fx"),
                (i.old = i.complete),
                (i.complete = function () {
                    m(i.old) && i.old.call(this), i.queue && w.dequeue(this, i.queue);
                }),
                i
            );
        }),
        w.fn.extend({
            fadeTo: function (e, t, n, i) {
                return this.filter(eo).css("opacity", 0).show().end().animate({ opacity: t }, e, n, i);
            },
            animate: function (e, t, n, i) {
                var a = w.isEmptyObject(e),
                    s = w.speed(t, n, i),
                    o = function () {
                        var t = tt(this, w.extend({}, e), s);
                        (a || J.get(this, "finish")) && t.stop(!0);
                    };
                return (o.finish = o), a || !1 === s.queue ? this.each(o) : this.queue(s.queue, o);
            },
            stop: function (e, t, n) {
                var i = function (e) {
                    var t = e.stop;
                    delete e.stop, t(n);
                };
                return (
                    "string" != typeof e && ((n = t), (t = e), (e = void 0)),
                    t && this.queue(e || "fx", []),
                    this.each(function () {
                        var t = !0,
                            a = null != e && e + "queueHooks",
                            s = w.timers,
                            o = J.get(this);
                        if (a) o[a] && o[a].stop && i(o[a]);
                        else for (a in o) o[a] && o[a].stop && eK.test(a) && i(o[a]);
                        for (a = s.length; a--; ) s[a].elem !== this || (null != e && s[a].queue !== e) || (s[a].anim.stop(n), (t = !1), s.splice(a, 1));
                        (!t && n) || w.dequeue(this, e);
                    })
                );
            },
            finish: function (e) {
                return (
                    !1 !== e && (e = e || "fx"),
                    this.each(function () {
                        var t,
                            n = J.get(this),
                            i = n[e + "queue"],
                            a = n[e + "queueHooks"],
                            s = w.timers,
                            o = i ? i.length : 0;
                        for (n.finish = !0, w.queue(this, e, []), a && a.stop && a.stop.call(this, !0), t = s.length; t--; ) s[t].elem === this && s[t].queue === e && (s[t].anim.stop(!0), s.splice(t, 1));
                        for (t = 0; t < o; t++) i[t] && i[t].finish && i[t].finish.call(this);
                        delete n.finish;
                    })
                );
            },
        }),
        w.each(["toggle", "show", "hide"], function (e, t) {
            var n = w.fn[t];
            w.fn[t] = function (e, i, a) {
                return null == e || "boolean" == typeof e ? n.apply(this, arguments) : this.animate(eQ(t, !0), e, i, a);
            };
        }),
        w.each({ slideDown: eQ("show"), slideUp: eQ("hide"), slideToggle: eQ("toggle"), fadeIn: { opacity: "show" }, fadeOut: { opacity: "hide" }, fadeToggle: { opacity: "toggle" } }, function (e, t) {
            w.fn[e] = function (e, n, i) {
                return this.animate(t, e, n, i);
            };
        }),
        (w.timers = []),
        (w.fx.tick = function () {
            var e,
                t = 0,
                n = w.timers;
            for (eX = Date.now(); t < n.length; t++) (e = n[t])() || n[t] !== e || n.splice(t--, 1);
            n.length || w.fx.stop(), (eX = void 0);
        }),
        (w.fx.timer = function (e) {
            w.timers.push(e), w.fx.start();
        }),
        (w.fx.interval = 13),
        (w.fx.start = function () {
            e7 ||
                ((e7 = !0),
                (function t() {
                    e7 && (!1 === v.hidden && e.requestAnimationFrame ? e.requestAnimationFrame(t) : e.setTimeout(t, w.fx.interval), w.fx.tick());
                })());
        }),
        (w.fx.stop = function () {
            e7 = null;
        }),
        (w.fx.speeds = { slow: 600, fast: 200, _default: 400 }),
        (w.fn.delay = function (t, n) {
            return (
                (t = (w.fx && w.fx.speeds[t]) || t),
                (n = n || "fx"),
                this.queue(n, function (n, i) {
                    var a = e.setTimeout(n, t);
                    i.stop = function () {
                        e.clearTimeout(a);
                    };
                })
            );
        }),
        (eY = v.createElement("input")),
        (e5 = v.createElement("select").appendChild(v.createElement("option"))),
        (eY.type = "checkbox"),
        (h.checkOn = "" !== eY.value),
        (h.optSelected = e5.selected),
        ((eY = v.createElement("input")).value = "t"),
        (eY.type = "radio"),
        (h.radioValue = "t" === eY.value);
    var tn,
        ti = w.expr.attrHandle;
    w.fn.extend({
        attr: function (e, t) {
            return q(this, w.attr, e, t, 1 < arguments.length);
        },
        removeAttr: function (e) {
            return this.each(function () {
                w.removeAttr(this, e);
            });
        },
    }),
        w.extend({
            attr: function (e, t, n) {
                var i,
                    a,
                    s = e.nodeType;
                if (3 !== s && 8 !== s && 2 !== s)
                    return void 0 === e.getAttribute
                        ? w.prop(e, t, n)
                        : ((1 === s && w.isXMLDoc(e)) || (a = w.attrHooks[t.toLowerCase()] || (w.expr.match.bool.test(t) ? tn : void 0)),
                          void 0 !== n
                              ? null === n
                                  ? void w.removeAttr(e, t)
                                  : a && "set" in a && void 0 !== (i = a.set(e, n, t))
                                  ? i
                                  : (e.setAttribute(t, n + ""), n)
                              : a && "get" in a && null !== (i = a.get(e, t))
                              ? i
                              : null == (i = w.find.attr(e, t))
                              ? void 0
                              : i);
            },
            attrHooks: {
                type: {
                    set: function (e, t) {
                        if (!h.radioValue && "radio" === t && D(e, "input")) {
                            var n = e.value;
                            return e.setAttribute("type", t), n && (e.value = n), t;
                        }
                    },
                },
            },
            removeAttr: function (e, t) {
                var n,
                    i = 0,
                    a = t && t.match(O);
                if (a && 1 === e.nodeType) for (; (n = a[i++]); ) e.removeAttribute(n);
            },
        }),
        (tn = {
            set: function (e, t, n) {
                return !1 === t ? w.removeAttr(e, n) : e.setAttribute(n, n), n;
            },
        }),
        w.each(w.expr.match.bool.source.match(/\w+/g), function (e, t) {
            var n = ti[t] || w.find.attr;
            ti[t] = function (e, t, i) {
                var a,
                    s,
                    o = t.toLowerCase();
                return i || ((s = ti[o]), (ti[o] = a), (a = null != n(e, t, i) ? o : null), (ti[o] = s)), a;
            };
        });
    var tr = /^(?:input|select|textarea|button)$/i,
        ta = /^(?:a|area)$/i;
    function ts(e) {
        return (e.match(O) || []).join(" ");
    }
    function to(e) {
        return (e.getAttribute && e.getAttribute("class")) || "";
    }
    function tl(e) {
        return Array.isArray(e) ? e : ("string" == typeof e && e.match(O)) || [];
    }
    w.fn.extend({
        prop: function (e, t) {
            return q(this, w.prop, e, t, 1 < arguments.length);
        },
        removeProp: function (e) {
            return this.each(function () {
                delete this[w.propFix[e] || e];
            });
        },
    }),
        w.extend({
            prop: function (e, t, n) {
                var i,
                    a,
                    s = e.nodeType;
                if (3 !== s && 8 !== s && 2 !== s)
                    return (
                        (1 === s && w.isXMLDoc(e)) || ((t = w.propFix[t] || t), (a = w.propHooks[t])),
                        void 0 !== n ? (a && "set" in a && void 0 !== (i = a.set(e, n, t)) ? i : (e[t] = n)) : a && "get" in a && null !== (i = a.get(e, t)) ? i : e[t]
                    );
            },
            propHooks: {
                tabIndex: {
                    get: function (e) {
                        var t = w.find.attr(e, "tabindex");
                        return t ? parseInt(t, 10) : tr.test(e.nodeName) || (ta.test(e.nodeName) && e.href) ? 0 : -1;
                    },
                },
            },
            propFix: { for: "htmlFor", class: "className" },
        }),
        h.optSelected ||
            (w.propHooks.selected = {
                get: function (e) {
                    var t = e.parentNode;
                    return t && t.parentNode && t.parentNode.selectedIndex, null;
                },
                set: function (e) {
                    var t = e.parentNode;
                    t && (t.selectedIndex, t.parentNode && t.parentNode.selectedIndex);
                },
            }),
        w.each(["tabIndex", "readOnly", "maxLength", "cellSpacing", "cellPadding", "rowSpan", "colSpan", "useMap", "frameBorder", "contentEditable"], function () {
            w.propFix[this.toLowerCase()] = this;
        }),
        w.fn.extend({
            addClass: function (e) {
                var t,
                    n,
                    i,
                    a,
                    s,
                    o,
                    l,
                    u = 0;
                if (m(e))
                    return this.each(function (t) {
                        w(this).addClass(e.call(this, t, to(this)));
                    });
                if ((t = tl(e)).length) {
                    for (; (n = this[u++]); )
                        if (((a = to(n)), (i = 1 === n.nodeType && " " + ts(a) + " "))) {
                            for (o = 0; (s = t[o++]); ) 0 > i.indexOf(" " + s + " ") && (i += s + " ");
                            a !== (l = ts(i)) && n.setAttribute("class", l);
                        }
                }
                return this;
            },
            removeClass: function (e) {
                var t,
                    n,
                    i,
                    a,
                    s,
                    o,
                    l,
                    u = 0;
                if (m(e))
                    return this.each(function (t) {
                        w(this).removeClass(e.call(this, t, to(this)));
                    });
                if (!arguments.length) return this.attr("class", "");
                if ((t = tl(e)).length) {
                    for (; (n = this[u++]); )
                        if (((a = to(n)), (i = 1 === n.nodeType && " " + ts(a) + " "))) {
                            for (o = 0; (s = t[o++]); ) for (; -1 < i.indexOf(" " + s + " "); ) i = i.replace(" " + s + " ", " ");
                            a !== (l = ts(i)) && n.setAttribute("class", l);
                        }
                }
                return this;
            },
            toggleClass: function (e, t) {
                var n = typeof e,
                    i = "string" === n || Array.isArray(e);
                return "boolean" == typeof t && i
                    ? t
                        ? this.addClass(e)
                        : this.removeClass(e)
                    : m(e)
                    ? this.each(function (n) {
                          w(this).toggleClass(e.call(this, n, to(this), t), t);
                      })
                    : this.each(function () {
                          var t, a, s, o;
                          if (i) for (a = 0, s = w(this), o = tl(e); (t = o[a++]); ) s.hasClass(t) ? s.removeClass(t) : s.addClass(t);
                          else (void 0 !== e && "boolean" !== n) || ((t = to(this)) && J.set(this, "__className__", t), this.setAttribute && this.setAttribute("class", t || !1 === e ? "" : J.get(this, "__className__") || ""));
                      });
            },
            hasClass: function (e) {
                var t,
                    n,
                    i = 0;
                for (t = " " + e + " "; (n = this[i++]); ) if (1 === n.nodeType && -1 < (" " + ts(to(n)) + " ").indexOf(t)) return !0;
                return !1;
            },
        });
    var tu = /\r/g;
    w.fn.extend({
        val: function (e) {
            var t,
                n,
                i,
                a = this[0];
            return arguments.length
                ? ((i = m(e)),
                  this.each(function (n) {
                      var a;
                      1 === this.nodeType &&
                          (null == (a = i ? e.call(this, n, w(this).val()) : e)
                              ? (a = "")
                              : "number" == typeof a
                              ? (a += "")
                              : Array.isArray(a) &&
                                (a = w.map(a, function (e) {
                                    return null == e ? "" : e + "";
                                })),
                          ((t = w.valHooks[this.type] || w.valHooks[this.nodeName.toLowerCase()]) && "set" in t && void 0 !== t.set(this, a, "value")) || (this.value = a));
                  }))
                : a
                ? (t = w.valHooks[a.type] || w.valHooks[a.nodeName.toLowerCase()]) && "get" in t && void 0 !== (n = t.get(a, "value"))
                    ? n
                    : "string" == typeof (n = a.value)
                    ? n.replace(tu, "")
                    : null == n
                    ? ""
                    : n
                : void 0;
        },
    }),
        w.extend({
            valHooks: {
                option: {
                    get: function (e) {
                        var t = w.find.attr(e, "value");
                        return null != t ? t : ts(w.text(e));
                    },
                },
                select: {
                    get: function (e) {
                        var t,
                            n,
                            i,
                            a = e.options,
                            s = e.selectedIndex,
                            o = "select-one" === e.type,
                            l = o ? null : [],
                            u = o ? s + 1 : a.length;
                        for (i = s < 0 ? u : o ? s : 0; i < u; i++)
                            if (((n = a[i]).selected || i === s) && !n.disabled && (!n.parentNode.disabled || !D(n.parentNode, "optgroup"))) {
                                if (((t = w(n).val()), o)) return t;
                                l.push(t);
                            }
                        return l;
                    },
                    set: function (e, t) {
                        for (var n, i, a = e.options, s = w.makeArray(t), o = a.length; o--; ) ((i = a[o]).selected = -1 < w.inArray(w.valHooks.option.get(i), s)) && (n = !0);
                        return n || (e.selectedIndex = -1), s;
                    },
                },
            },
        }),
        w.each(["radio", "checkbox"], function () {
            (w.valHooks[this] = {
                set: function (e, t) {
                    if (Array.isArray(t)) return (e.checked = -1 < w.inArray(w(e).val(), t));
                },
            }),
                h.checkOn ||
                    (w.valHooks[this].get = function (e) {
                        return null === e.getAttribute("value") ? "on" : e.value;
                    });
        }),
        (h.focusin = "onfocusin" in e);
    var td = /^(?:focusinfocus|focusoutblur)$/,
        tc = function (e) {
            e.stopPropagation();
        };
    w.extend(w.event, {
        trigger: function (t, n, i, a) {
            var s,
                o,
                l,
                u,
                d,
                p,
                f,
                h,
                $ = [i || v],
                y = c.call(t, "type") ? t.type : t,
                b = c.call(t, "namespace") ? t.namespace.split(".") : [];
            if (
                ((o = h = l = i = i || v),
                3 !== i.nodeType &&
                    8 !== i.nodeType &&
                    !td.test(y + w.event.triggered) &&
                    (-1 < y.indexOf(".") && ((y = (b = y.split(".")).shift()), b.sort()),
                    (d = 0 > y.indexOf(":") && "on" + y),
                    ((t = t[w.expando] ? t : new w.Event(y, "object" == typeof t && t)).isTrigger = a ? 2 : 3),
                    (t.namespace = b.join(".")),
                    (t.rnamespace = t.namespace ? RegExp("(^|\\.)" + b.join("\\.(?:.*\\.|)") + "(\\.|$)") : null),
                    (t.result = void 0),
                    t.target || (t.target = i),
                    (n = null == n ? [t] : w.makeArray(n, [t])),
                    (f = w.event.special[y] || {}),
                    a || !f.trigger || !1 !== f.trigger.apply(i, n)))
            ) {
                if (!a && !f.noBubble && !g(i)) {
                    for (u = f.delegateType || y, td.test(u + y) || (o = o.parentNode); o; o = o.parentNode) $.push(o), (l = o);
                    l === (i.ownerDocument || v) && $.push(l.defaultView || l.parentWindow || e);
                }
                for (s = 0; (o = $[s++]) && !t.isPropagationStopped(); )
                    (h = o),
                        (t.type = 1 < s ? u : f.bindType || y),
                        (p = (J.get(o, "events") || Object.create(null))[t.type] && J.get(o, "handle")) && p.apply(o, n),
                        (p = d && o[d]) && p.apply && X(o) && ((t.result = p.apply(o, n)), !1 === t.result && t.preventDefault());
                return (
                    (t.type = y),
                    a ||
                        t.isDefaultPrevented() ||
                        (f._default && !1 !== f._default.apply($.pop(), n)) ||
                        !X(i) ||
                        (d &&
                            m(i[y]) &&
                            !g(i) &&
                            ((l = i[d]) && (i[d] = null),
                            (w.event.triggered = y),
                            t.isPropagationStopped() && h.addEventListener(y, tc),
                            i[y](),
                            t.isPropagationStopped() && h.removeEventListener(y, tc),
                            (w.event.triggered = void 0),
                            l && (i[d] = l))),
                    t.result
                );
            }
        },
        simulate: function (e, t, n) {
            var i = w.extend(new w.Event(), n, { type: e, isSimulated: !0 });
            w.event.trigger(i, null, t);
        },
    }),
        w.fn.extend({
            trigger: function (e, t) {
                return this.each(function () {
                    w.event.trigger(e, t, this);
                });
            },
            triggerHandler: function (e, t) {
                var n = this[0];
                if (n) return w.event.trigger(e, t, n, !0);
            },
        }),
        h.focusin ||
            w.each({ focus: "focusin", blur: "focusout" }, function (e, t) {
                var n = function (e) {
                    w.event.simulate(t, e.target, w.event.fix(e));
                };
                w.event.special[t] = {
                    setup: function () {
                        var i = this.ownerDocument || this.document || this,
                            a = J.access(i, t);
                        a || i.addEventListener(e, n, !0), J.access(i, t, (a || 0) + 1);
                    },
                    teardown: function () {
                        var i = this.ownerDocument || this.document || this,
                            a = J.access(i, t) - 1;
                        a ? J.access(i, t, a) : (i.removeEventListener(e, n, !0), J.remove(i, t));
                    },
                };
            });
    var tp = e.location,
        tf = { guid: Date.now() },
        th = /\?/;
    w.parseXML = function (t) {
        var n;
        if (!t || "string" != typeof t) return null;
        try {
            n = new e.DOMParser().parseFromString(t, "text/xml");
        } catch (i) {
            n = void 0;
        }
        return (n && !n.getElementsByTagName("parsererror").length) || w.error("Invalid XML: " + t), n;
    };
    var tm = /\[\]$/,
        tg = /\r?\n/g,
        tv = /^(?:submit|button|image|reset|file)$/i,
        t$ = /^(?:input|select|textarea|keygen)/i;
    function ty(e, t, n, i) {
        var a;
        if (Array.isArray(t))
            w.each(t, function (t, a) {
                n || tm.test(e) ? i(e, a) : ty(e + "[" + ("object" == typeof a && null != a ? t : "") + "]", a, n, i);
            });
        else if (n || "object" !== b(t)) i(e, t);
        else for (a in t) ty(e + "[" + a + "]", t[a], n, i);
    }
    (w.param = function (e, t) {
        var n,
            i = [],
            a = function (e, t) {
                var n = m(t) ? t() : t;
                i[i.length] = encodeURIComponent(e) + "=" + encodeURIComponent(null == n ? "" : n);
            };
        if (null == e) return "";
        if (Array.isArray(e) || (e.jquery && !w.isPlainObject(e)))
            w.each(e, function () {
                a(this.name, this.value);
            });
        else for (n in e) ty(n, e[n], t, a);
        return i.join("&");
    }),
        w.fn.extend({
            serialize: function () {
                return w.param(this.serializeArray());
            },
            serializeArray: function () {
                return this.map(function () {
                    var e = w.prop(this, "elements");
                    return e ? w.makeArray(e) : this;
                })
                    .filter(function () {
                        var e = this.type;
                        return this.name && !w(this).is(":disabled") && t$.test(this.nodeName) && !tv.test(e) && (this.checked || !ef.test(e));
                    })
                    .map(function (e, t) {
                        var n = w(this).val();
                        return null == n
                            ? null
                            : Array.isArray(n)
                            ? w.map(n, function (e) {
                                  return { name: t.name, value: e.replace(tg, "\r\n") };
                              })
                            : { name: t.name, value: n.replace(tg, "\r\n") };
                    })
                    .get();
            },
        });
    var tb = /%20/g,
        t_ = /#.*$/,
        tw = /([?&])_=[^&]*/,
        tx = /^(.*?):[ \t]*([^\r\n]*)$/gm,
        tS = /^(?:GET|HEAD)$/,
        tT = /^\/\//,
        tC = {},
        tE = {},
        t8 = "*/".concat("*"),
        tD = v.createElement("a");
    function tk(e) {
        return function (t, n) {
            "string" != typeof t && ((n = t), (t = "*"));
            var i,
                a = 0,
                s = t.toLowerCase().match(O) || [];
            if (m(n)) for (; (i = s[a++]); ) "+" === i[0] ? (e[(i = i.slice(1) || "*")] = e[i] || []).unshift(n) : (e[i] = e[i] || []).push(n);
        };
    }
    function tL(e, t, n, i) {
        var a = {},
            s = e === tE;
        function o(l) {
            var u;
            return (
                (a[l] = !0),
                w.each(e[l] || [], function (e, l) {
                    var d = l(t, n, i);
                    return "string" != typeof d || s || a[d] ? (s ? !(u = d) : void 0) : (t.dataTypes.unshift(d), o(d), !1);
                }),
                u
            );
        }
        return o(t.dataTypes[0]) || (!a["*"] && o("*"));
    }
    function tA(e, t) {
        var n,
            i,
            a = w.ajaxSettings.flatOptions || {};
        for (n in t) void 0 !== t[n] && ((a[n] ? e : i || (i = {}))[n] = t[n]);
        return i && w.extend(!0, e, i), e;
    }
    (tD.href = tp.href),
        w.extend({
            active: 0,
            lastModified: {},
            etag: {},
            ajaxSettings: {
                url: tp.href,
                type: "GET",
                isLocal: /^(?:about|app|app-storage|.+-extension|file|res|widget):$/.test(tp.protocol),
                global: !0,
                processData: !0,
                async: !0,
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                accepts: { "*": t8, text: "text/plain", html: "text/html", xml: "application/xml, text/xml", json: "application/json, text/javascript" },
                contents: { xml: /\bxml\b/, html: /\bhtml/, json: /\bjson\b/ },
                responseFields: { xml: "responseXML", text: "responseText", json: "responseJSON" },
                converters: { "* text": String, "text html": !0, "text json": JSON.parse, "text xml": w.parseXML },
                flatOptions: { url: !0, context: !0 },
            },
            ajaxSetup: function (e, t) {
                return t ? tA(tA(e, w.ajaxSettings), t) : tA(w.ajaxSettings, e);
            },
            ajaxPrefilter: tk(tC),
            ajaxTransport: tk(tE),
            ajax: function (t, n) {
                "object" == typeof t && ((n = t), (t = void 0)), (n = n || {});
                var i,
                    a,
                    s,
                    o,
                    l,
                    u,
                    d,
                    c,
                    p,
                    f,
                    h = w.ajaxSetup({}, n),
                    m = h.context || h,
                    g = h.context && (m.nodeType || m.jquery) ? w(m) : w.event,
                    $ = w.Deferred(),
                    y = w.Callbacks("once memory"),
                    b = h.statusCode || {},
                    _ = {},
                    x = {},
                    S = "canceled",
                    T = {
                        readyState: 0,
                        getResponseHeader: function (e) {
                            var t;
                            if (d) {
                                if (!o) for (o = {}; (t = tx.exec(s)); ) o[t[1].toLowerCase() + " "] = (o[t[1].toLowerCase() + " "] || []).concat(t[2]);
                                t = o[e.toLowerCase() + " "];
                            }
                            return null == t ? null : t.join(", ");
                        },
                        getAllResponseHeaders: function () {
                            return d ? s : null;
                        },
                        setRequestHeader: function (e, t) {
                            return null == d && (_[(e = x[e.toLowerCase()] = x[e.toLowerCase()] || e)] = t), this;
                        },
                        overrideMimeType: function (e) {
                            return null == d && (h.mimeType = e), this;
                        },
                        statusCode: function (e) {
                            var t;
                            if (e) {
                                if (d) T.always(e[T.status]);
                                else for (t in e) b[t] = [b[t], e[t]];
                            }
                            return this;
                        },
                        abort: function (e) {
                            var t = e || S;
                            return i && i.abort(t), D(0, t), this;
                        },
                    };
                if (
                    ($.promise(T),
                    (h.url = ((t || h.url || tp.href) + "").replace(tT, tp.protocol + "//")),
                    (h.type = n.method || n.type || h.method || h.type),
                    (h.dataTypes = (h.dataType || "*").toLowerCase().match(O) || [""]),
                    null == h.crossDomain)
                ) {
                    u = v.createElement("a");
                    try {
                        (u.href = h.url), (u.href = u.href), (h.crossDomain = tD.protocol + "//" + tD.host != u.protocol + "//" + u.host);
                    } catch (C) {
                        h.crossDomain = !0;
                    }
                }
                if ((h.data && h.processData && "string" != typeof h.data && (h.data = w.param(h.data, h.traditional)), tL(tC, h, n, T), d)) return T;
                for (p in ((c = w.event && h.global) && 0 == w.active++ && w.event.trigger("ajaxStart"),
                (h.type = h.type.toUpperCase()),
                (h.hasContent = !tS.test(h.type)),
                (a = h.url.replace(t_, "")),
                h.hasContent
                    ? h.data && h.processData && 0 === (h.contentType || "").indexOf("application/x-www-form-urlencoded") && (h.data = h.data.replace(tb, "+"))
                    : ((f = h.url.slice(a.length)),
                      h.data && (h.processData || "string" == typeof h.data) && ((a += (th.test(a) ? "&" : "?") + h.data), delete h.data),
                      !1 === h.cache && ((a = a.replace(tw, "$1")), (f = (th.test(a) ? "&" : "?") + "_=" + tf.guid++ + f)),
                      (h.url = a + f)),
                h.ifModified && (w.lastModified[a] && T.setRequestHeader("If-Modified-Since", w.lastModified[a]), w.etag[a] && T.setRequestHeader("If-None-Match", w.etag[a])),
                ((h.data && h.hasContent && !1 !== h.contentType) || n.contentType) && T.setRequestHeader("Content-Type", h.contentType),
                T.setRequestHeader("Accept", h.dataTypes[0] && h.accepts[h.dataTypes[0]] ? h.accepts[h.dataTypes[0]] + ("*" !== h.dataTypes[0] ? ", " + t8 + "; q=0.01" : "") : h.accepts["*"]),
                h.headers))
                    T.setRequestHeader(p, h.headers[p]);
                if (h.beforeSend && (!1 === h.beforeSend.call(m, T, h) || d)) return T.abort();
                if (((S = "abort"), y.add(h.complete), T.done(h.success), T.fail(h.error), (i = tL(tE, h, n, T)))) {
                    if (((T.readyState = 1), c && g.trigger("ajaxSend", [T, h]), d)) return T;
                    h.async &&
                        0 < h.timeout &&
                        (l = e.setTimeout(function () {
                            T.abort("timeout");
                        }, h.timeout));
                    try {
                        (d = !1), i.send(_, D);
                    } catch (E) {
                        if (d) throw E;
                        D(-1, E);
                    }
                } else D(-1, "No Transport");
                function D(t, n, o, u) {
                    var p,
                        f,
                        v,
                        _,
                        x,
                        S = n;
                    d ||
                        ((d = !0),
                        l && e.clearTimeout(l),
                        (i = void 0),
                        (s = u || ""),
                        (T.readyState = 0 < t ? 4 : 0),
                        (p = (200 <= t && t < 300) || 304 === t),
                        o &&
                            (_ = (function (e, t, n) {
                                for (var i, a, s, o, l = e.contents, u = e.dataTypes; "*" === u[0]; ) u.shift(), void 0 === i && (i = e.mimeType || t.getResponseHeader("Content-Type"));
                                if (i) {
                                    for (a in l)
                                        if (l[a] && l[a].test(i)) {
                                            u.unshift(a);
                                            break;
                                        }
                                }
                                if (u[0] in n) s = u[0];
                                else {
                                    for (a in n) {
                                        if (!u[0] || e.converters[a + " " + u[0]]) {
                                            s = a;
                                            break;
                                        }
                                        o || (o = a);
                                    }
                                    s = s || o;
                                }
                                if (s) return s !== u[0] && u.unshift(s), n[s];
                            })(h, T, o)),
                        !p && -1 < w.inArray("script", h.dataTypes) && (h.converters["text script"] = function () {}),
                        (_ = (function (e, t, n, i) {
                            var a,
                                s,
                                o,
                                l,
                                u,
                                d = {},
                                c = e.dataTypes.slice();
                            if (c[1]) for (o in e.converters) d[o.toLowerCase()] = e.converters[o];
                            for (s = c.shift(); s; )
                                if ((e.responseFields[s] && (n[e.responseFields[s]] = t), !u && i && e.dataFilter && (t = e.dataFilter(t, e.dataType)), (u = s), (s = c.shift()))) {
                                    if ("*" === s) s = u;
                                    else if ("*" !== u && u !== s) {
                                        if (!(o = d[u + " " + s] || d["* " + s])) {
                                            for (a in d)
                                                if ((l = a.split(" "))[1] === s && (o = d[u + " " + l[0]] || d["* " + l[0]])) {
                                                    !0 === o ? (o = d[a]) : !0 !== d[a] && ((s = l[0]), c.unshift(l[1]));
                                                    break;
                                                }
                                        }
                                        if (!0 !== o) {
                                            if (o && e.throws) t = o(t);
                                            else
                                                try {
                                                    t = o(t);
                                                } catch (p) {
                                                    return { state: "parsererror", error: o ? p : "No conversion from " + u + " to " + s };
                                                }
                                        }
                                    }
                                }
                            return { state: "success", data: t };
                        })(h, _, T, p)),
                        p
                            ? (h.ifModified && ((x = T.getResponseHeader("Last-Modified")) && (w.lastModified[a] = x), (x = T.getResponseHeader("etag")) && (w.etag[a] = x)),
                              204 === t || "HEAD" === h.type ? (S = "nocontent") : 304 === t ? (S = "notmodified") : ((S = _.state), (f = _.data), (p = !(v = _.error))))
                            : ((v = S), (!t && S) || ((S = "error"), t < 0 && (t = 0))),
                        (T.status = t),
                        (T.statusText = (n || S) + ""),
                        p ? $.resolveWith(m, [f, S, T]) : $.rejectWith(m, [T, S, v]),
                        T.statusCode(b),
                        (b = void 0),
                        c && g.trigger(p ? "ajaxSuccess" : "ajaxError", [T, h, p ? f : v]),
                        y.fireWith(m, [T, S]),
                        c && (g.trigger("ajaxComplete", [T, h]), --w.active || w.event.trigger("ajaxStop")));
                }
                return T;
            },
            getJSON: function (e, t, n) {
                return w.get(e, t, n, "json");
            },
            getScript: function (e, t) {
                return w.get(e, void 0, t, "script");
            },
        }),
        w.each(["get", "post"], function (e, t) {
            w[t] = function (e, n, i, a) {
                return m(n) && ((a = a || i), (i = n), (n = void 0)), w.ajax(w.extend({ url: e, type: t, dataType: a, data: n, success: i }, w.isPlainObject(e) && e));
            };
        }),
        w.ajaxPrefilter(function (e) {
            var t;
            for (t in e.headers) "content-type" === t.toLowerCase() && (e.contentType = e.headers[t] || "");
        }),
        (w._evalUrl = function (e, t, n) {
            return w.ajax({
                url: e,
                type: "GET",
                dataType: "script",
                cache: !0,
                async: !1,
                global: !1,
                converters: { "text script": function () {} },
                dataFilter: function (e) {
                    w.globalEval(e, t, n);
                },
            });
        }),
        w.fn.extend({
            wrapAll: function (e) {
                var t;
                return (
                    this[0] &&
                        (m(e) && (e = e.call(this[0])),
                        (t = w(e, this[0].ownerDocument).eq(0).clone(!0)),
                        this[0].parentNode && t.insertBefore(this[0]),
                        t
                            .map(function () {
                                for (var e = this; e.firstElementChild; ) e = e.firstElementChild;
                                return e;
                            })
                            .append(this)),
                    this
                );
            },
            wrapInner: function (e) {
                return m(e)
                    ? this.each(function (t) {
                          w(this).wrapInner(e.call(this, t));
                      })
                    : this.each(function () {
                          var t = w(this),
                              n = t.contents();
                          n.length ? n.wrapAll(e) : t.append(e);
                      });
            },
            wrap: function (e) {
                var t = m(e);
                return this.each(function (n) {
                    w(this).wrapAll(t ? e.call(this, n) : e);
                });
            },
            unwrap: function (e) {
                return (
                    this.parent(e)
                        .not("body")
                        .each(function () {
                            w(this).replaceWith(this.childNodes);
                        }),
                    this
                );
            },
        }),
        (w.expr.pseudos.hidden = function (e) {
            return !w.expr.pseudos.visible(e);
        }),
        (w.expr.pseudos.visible = function (e) {
            return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
        }),
        (w.ajaxSettings.xhr = function () {
            try {
                return new e.XMLHttpRequest();
            } catch (t) {}
        });
    var tI = { 0: 200, 1223: 204 },
        tP = w.ajaxSettings.xhr();
    (h.cors = !!tP && "withCredentials" in tP),
        (h.ajax = tP = !!tP),
        w.ajaxTransport(function (t) {
            var n, i;
            if (h.cors || (tP && !t.crossDomain))
                return {
                    send: function (a, s) {
                        var o,
                            l = t.xhr();
                        if ((l.open(t.type, t.url, t.async, t.username, t.password), t.xhrFields)) for (o in t.xhrFields) l[o] = t.xhrFields[o];
                        for (o in (t.mimeType && l.overrideMimeType && l.overrideMimeType(t.mimeType), t.crossDomain || a["X-Requested-With"] || (a["X-Requested-With"] = "XMLHttpRequest"), a)) l.setRequestHeader(o, a[o]);
                        (n = function (e) {
                            return function () {
                                n &&
                                    ((n = i = l.onload = l.onerror = l.onabort = l.ontimeout = l.onreadystatechange = null),
                                    "abort" === e
                                        ? l.abort()
                                        : "error" === e
                                        ? "number" != typeof l.status
                                            ? s(0, "error")
                                            : s(l.status, l.statusText)
                                        : s(tI[l.status] || l.status, l.statusText, "text" !== (l.responseType || "text") || "string" != typeof l.responseText ? { binary: l.response } : { text: l.responseText }, l.getAllResponseHeaders()));
                            };
                        }),
                            (l.onload = n()),
                            (i = l.onerror = l.ontimeout = n("error")),
                            void 0 !== l.onabort
                                ? (l.onabort = i)
                                : (l.onreadystatechange = function () {
                                      4 === l.readyState &&
                                          e.setTimeout(function () {
                                              n && i();
                                          });
                                  }),
                            (n = n("abort"));
                        try {
                            l.send((t.hasContent && t.data) || null);
                        } catch (u) {
                            if (n) throw u;
                        }
                    },
                    abort: function () {
                        n && n();
                    },
                };
        }),
        w.ajaxPrefilter(function (e) {
            e.crossDomain && (e.contents.script = !1);
        }),
        w.ajaxSetup({
            accepts: { script: "text/javascript, application/javascript, application/ecmascript, application/x-ecmascript" },
            contents: { script: /\b(?:java|ecma)script\b/ },
            converters: {
                "text script": function (e) {
                    return w.globalEval(e), e;
                },
            },
        }),
        w.ajaxPrefilter("script", function (e) {
            void 0 === e.cache && (e.cache = !1), e.crossDomain && (e.type = "GET");
        }),
        w.ajaxTransport("script", function (e) {
            var t, n;
            if (e.crossDomain || e.scriptAttrs)
                return {
                    send: function (i, a) {
                        (t = w("<script>")
                            .attr(e.scriptAttrs || {})
                            .prop({ charset: e.scriptCharset, src: e.url })
                            .on(
                                "load error",
                                (n = function (e) {
                                    t.remove(), (n = null), e && a("error" === e.type ? 404 : 200, e.type);
                                })
                            )),
                            v.head.appendChild(t[0]);
                    },
                    abort: function () {
                        n && n();
                    },
                };
        });
    var t0,
        tN = [],
        tM = /(=)\?(?=&|$)|\?\?/;
    w.ajaxSetup({
        jsonp: "callback",
        jsonpCallback: function () {
            var e = tN.pop() || w.expando + "_" + tf.guid++;
            return (this[e] = !0), e;
        },
    }),
        w.ajaxPrefilter("json jsonp", function (t, n, i) {
            var a,
                s,
                o,
                l = !1 !== t.jsonp && (tM.test(t.url) ? "url" : "string" == typeof t.data && 0 === (t.contentType || "").indexOf("application/x-www-form-urlencoded") && tM.test(t.data) && "data");
            if (l || "jsonp" === t.dataTypes[0])
                return (
                    (a = t.jsonpCallback = m(t.jsonpCallback) ? t.jsonpCallback() : t.jsonpCallback),
                    l ? (t[l] = t[l].replace(tM, "$1" + a)) : !1 !== t.jsonp && (t.url += (th.test(t.url) ? "&" : "?") + t.jsonp + "=" + a),
                    (t.converters["script json"] = function () {
                        return o || w.error(a + " was not called"), o[0];
                    }),
                    (t.dataTypes[0] = "json"),
                    (s = e[a]),
                    (e[a] = function () {
                        o = arguments;
                    }),
                    i.always(function () {
                        void 0 === s ? w(e).removeProp(a) : (e[a] = s), t[a] && ((t.jsonpCallback = n.jsonpCallback), tN.push(a)), o && m(s) && s(o[0]), (o = s = void 0);
                    }),
                    "script"
                );
        }),
        (h.createHTMLDocument = (((t0 = v.implementation.createHTMLDocument("").body).innerHTML = "<form></form><form></form>"), 2 === t0.childNodes.length)),
        (w.parseHTML = function (e, t, n) {
            var i, a, s;
            return "string" != typeof e
                ? []
                : ("boolean" == typeof t && ((n = t), (t = !1)),
                  t || (h.createHTMLDocument ? (((i = (t = v.implementation.createHTMLDocument("")).createElement("base")).href = v.location.href), t.head.appendChild(i)) : (t = v)),
                  (s = !n && []),
                  (a = k.exec(e)) ? [t.createElement(a[1])] : ((a = eb([e], t, s)), s && s.length && w(s).remove(), w.merge([], a.childNodes)));
        }),
        (w.fn.load = function (e, t, n) {
            var i,
                a,
                s,
                o = this,
                l = e.indexOf(" ");
            return (
                -1 < l && ((i = ts(e.slice(l))), (e = e.slice(0, l))),
                m(t) ? ((n = t), (t = void 0)) : t && "object" == typeof t && (a = "POST"),
                0 < o.length &&
                    w
                        .ajax({ url: e, type: a || "GET", dataType: "html", data: t })
                        .done(function (e) {
                            (s = arguments), o.html(i ? w("<div>").append(w.parseHTML(e)).find(i) : e);
                        })
                        .always(
                            n &&
                                function (e, t) {
                                    o.each(function () {
                                        n.apply(this, s || [e.responseText, t, e]);
                                    });
                                }
                        ),
                this
            );
        }),
        (w.expr.pseudos.animated = function (e) {
            return w.grep(w.timers, function (t) {
                return e === t.elem;
            }).length;
        }),
        (w.offset = {
            setOffset: function (e, t, n) {
                var i,
                    a,
                    s,
                    o,
                    l,
                    u,
                    d = w.css(e, "position"),
                    c = w(e),
                    p = {};
                "static" === d && (e.style.position = "relative"),
                    (l = c.offset()),
                    (s = w.css(e, "top")),
                    (u = w.css(e, "left")),
                    ("absolute" === d || "fixed" === d) && -1 < (s + u).indexOf("auto") ? ((o = (i = c.position()).top), (a = i.left)) : ((o = parseFloat(s) || 0), (a = parseFloat(u) || 0)),
                    m(t) && (t = t.call(e, n, w.extend({}, l))),
                    null != t.top && (p.top = t.top - l.top + o),
                    null != t.left && (p.left = t.left - l.left + a),
                    "using" in t ? t.using.call(e, p) : ("number" == typeof p.top && (p.top += "px"), "number" == typeof p.left && (p.left += "px"), c.css(p));
            },
        }),
        w.fn.extend({
            offset: function (e) {
                if (arguments.length)
                    return void 0 === e
                        ? this
                        : this.each(function (t) {
                              w.offset.setOffset(this, e, t);
                          });
                var t,
                    n,
                    i = this[0];
                return i ? (i.getClientRects().length ? ((t = i.getBoundingClientRect()), (n = i.ownerDocument.defaultView), { top: t.top + n.pageYOffset, left: t.left + n.pageXOffset }) : { top: 0, left: 0 }) : void 0;
            },
            position: function () {
                if (this[0]) {
                    var e,
                        t,
                        n,
                        i = this[0],
                        a = { top: 0, left: 0 };
                    if ("fixed" === w.css(i, "position")) t = i.getBoundingClientRect();
                    else {
                        for (t = this.offset(), n = i.ownerDocument, e = i.offsetParent || n.documentElement; e && (e === n.body || e === n.documentElement) && "static" === w.css(e, "position"); ) e = e.parentNode;
                        e && e !== i && 1 === e.nodeType && (((a = w(e).offset()).top += w.css(e, "borderTopWidth", !0)), (a.left += w.css(e, "borderLeftWidth", !0)));
                    }
                    return { top: t.top - a.top - w.css(i, "marginTop", !0), left: t.left - a.left - w.css(i, "marginLeft", !0) };
                }
            },
            offsetParent: function () {
                return this.map(function () {
                    for (var e = this.offsetParent; e && "static" === w.css(e, "position"); ) e = e.offsetParent;
                    return e || er;
                });
            },
        }),
        w.each({ scrollLeft: "pageXOffset", scrollTop: "pageYOffset" }, function (e, t) {
            var n = "pageYOffset" === t;
            w.fn[e] = function (i) {
                return q(
                    this,
                    function (e, i, a) {
                        var s;
                        if ((g(e) ? (s = e) : 9 === e.nodeType && (s = e.defaultView), void 0 === a)) return s ? s[t] : e[i];
                        s ? s.scrollTo(n ? s.pageXOffset : a, n ? a : s.pageYOffset) : (e[i] = a);
                    },
                    e,
                    i,
                    arguments.length
                );
            };
        }),
        w.each(["top", "left"], function (e, t) {
            w.cssHooks[t] = ej(h.pixelPosition, function (e, n) {
                if (n) return (n = eF(e, t)), eO.test(n) ? w(e).position()[t] + "px" : n;
            });
        }),
        w.each({ Height: "height", Width: "width" }, function (e, t) {
            w.each({ padding: "inner" + e, content: t, "": "outer" + e }, function (n, i) {
                w.fn[i] = function (a, s) {
                    var o = arguments.length && (n || "boolean" != typeof a),
                        l = n || (!0 === a || !0 === s ? "margin" : "border");
                    return q(
                        this,
                        function (t, n, a) {
                            var s;
                            return g(t)
                                ? 0 === i.indexOf("outer")
                                    ? t["inner" + e]
                                    : t.document.documentElement["client" + e]
                                : 9 === t.nodeType
                                ? ((s = t.documentElement), Math.max(t.body["scroll" + e], s["scroll" + e], t.body["offset" + e], s["offset" + e], s["client" + e]))
                                : void 0 === a
                                ? w.css(t, n, l)
                                : w.style(t, n, a, l);
                        },
                        t,
                        o ? a : void 0,
                        o
                    );
                };
            });
        }),
        w.each(["ajaxStart", "ajaxStop", "ajaxComplete", "ajaxError", "ajaxSuccess", "ajaxSend"], function (e, t) {
            w.fn[t] = function (e) {
                return this.on(t, e);
            };
        }),
        w.fn.extend({
            bind: function (e, t, n) {
                return this.on(e, null, t, n);
            },
            unbind: function (e, t) {
                return this.off(e, null, t);
            },
            delegate: function (e, t, n, i) {
                return this.on(t, e, n, i);
            },
            undelegate: function (e, t, n) {
                return 1 === arguments.length ? this.off(e, "**") : this.off(t, e || "**", n);
            },
            hover: function (e, t) {
                return this.mouseenter(e).mouseleave(t || e);
            },
        }),
        w.each("blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu".split(" "), function (e, t) {
            w.fn[t] = function (e, n) {
                return 0 < arguments.length ? this.on(t, null, e, n) : this.trigger(t);
            };
        });
    var tO = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
    (w.proxy = function (e, t) {
        var n, i, s;
        if (("string" == typeof t && ((n = e[t]), (t = e), (e = n)), m(e)))
            return (
                (i = a.call(arguments, 2)),
                ((s = function () {
                    return e.apply(t || this, i.concat(a.call(arguments)));
                }).guid = e.guid = e.guid || w.guid++),
                s
            );
    }),
        (w.holdReady = function (e) {
            e ? w.readyWait++ : w.ready(!0);
        }),
        (w.isArray = Array.isArray),
        (w.parseJSON = JSON.parse),
        (w.nodeName = D),
        (w.isFunction = m),
        (w.isWindow = g),
        (w.camelCase = V),
        (w.type = b),
        (w.now = Date.now),
        (w.isNumeric = function (e) {
            var t = w.type(e);
            return ("number" === t || "string" === t) && !isNaN(e - parseFloat(e));
        }),
        (w.trim = function (e) {
            return null == e ? "" : (e + "").replace(tO, "");
        }),
        "function" == typeof define &&
            define.amd &&
            define("jquery", [], function () {
                return w;
            });
    var tR = e.jQuery,
        tz = e.$;
    return (
        (w.noConflict = function (t) {
            return e.$ === w && (e.$ = tz), t && e.jQuery === w && (e.jQuery = tR), w;
        }),
        void 0 === t && (e.jQuery = e.$ = w),
        w
    );
}),
    (function (e) {
        "use strict";
        var t;
        "function" == typeof define && define.amd
            ? define(["jquery"], function (t) {
                  return e(t, window, document);
              })
            : "object" == typeof exports
            ? ((t = require("jquery")),
              "undefined" != typeof window
                  ? (module.exports = function (n, i) {
                        return (n = n || window), e((i = i || t(n)), n, n.document);
                    })
                  : e(t, window, window.document))
            : (window.DataTable = e(jQuery, window, document));
    })(function (e, t, n, i) {
        "use strict";
        function a(e) {
            var t = parseInt(e, 10);
            return !isNaN(t) && isFinite(e) ? t : null;
        }
        function s(e, t, n) {
            var i = typeof e,
                a = "string" == i;
            return "number" == i || "bigint" == i || !!w(e) || (t && a && (e = x(e, t)), n && a && (e = e.replace(_, "")), !isNaN(parseFloat(e)) && isFinite(e));
        }
        function o(e, t, n) {
            var i;
            return !!w(e) || ((w((i = e)) || "string" == typeof i) && !!s(e.replace($, ""), t, n)) || null;
        }
        function l(e, t, n, a) {
            var s = [],
                o = 0,
                l = t.length;
            if (a !== i) for (; o < l; o++) e[t[o]][n] && s.push(e[t[o]][n][a]);
            else for (; o < l; o++) s.push(e[t[o]][n]);
            return s;
        }
        function u(e, t) {
            var n,
                a = [];
            t === i ? ((t = 0), (n = e)) : ((n = t), (t = e));
            for (var s = t; s < n; s++) a.push(s);
            return a;
        }
        function d(e) {
            for (var t = [], n = 0, i = e.length; n < i; n++) e[n] && t.push(e[n]);
            return t;
        }
        function c(e, t) {
            return -1 !== this.indexOf(e, (t = t === i ? 0 : t));
        }
        var p,
            f,
            h,
            m = function (t, n) {
                if (m.factory(t, n)) return m;
                if (this instanceof m) return e(t).DataTable(n);
                (n = t),
                    (this.$ = function (e, t) {
                        return this.api(!0).$(e, t);
                    }),
                    (this._ = function (e, t) {
                        return this.api(!0).rows(e, t).data();
                    }),
                    (this.api = function (e) {
                        return new tm(e ? eK(this[p.iApiIndex]) : this);
                    }),
                    (this.fnAddData = function (t, n) {
                        var a = this.api(!0);
                        return (t = (Array.isArray(t) && (Array.isArray(t[0]) || e.isPlainObject(t[0])) ? a.rows : a.row).add(t)), (n === i || n) && a.draw(), t.flatten().toArray();
                    }),
                    (this.fnAdjustColumnSizing = function (e) {
                        var t = this.api(!0).columns.adjust(),
                            n = t.settings()[0],
                            a = n.oScroll;
                        e === i || e ? t.draw(!1) : ("" === a.sX && "" === a.sY) || eF(n);
                    }),
                    (this.fnClearTable = function (e) {
                        var t = this.api(!0).clear();
                        (e === i || e) && t.draw();
                    }),
                    (this.fnClose = function (e) {
                        this.api(!0).row(e).child.hide();
                    }),
                    (this.fnDeleteRow = function (e, t, n) {
                        var a = this.api(!0),
                            s = (e = a.rows(e)).settings()[0],
                            o = s.aoData[e[0][0]];
                        return e.remove(), t && t.call(this, s, o), (n === i || n) && a.draw(), o;
                    }),
                    (this.fnDestroy = function (e) {
                        this.api(!0).destroy(e);
                    }),
                    (this.fnDraw = function (e) {
                        this.api(!0).draw(e);
                    }),
                    (this.fnFilter = function (e, t, n, a, s, o) {
                        var l = this.api(!0);
                        (null === t || t === i ? l : l.column(t)).search(e, n, a, o), l.draw();
                    }),
                    (this.fnGetData = function (e, t) {
                        var n,
                            a = this.api(!0);
                        return e !== i ? ((n = e.nodeName ? e.nodeName.toLowerCase() : ""), t !== i || "td" == n || "th" == n ? a.cell(e, t).data() : a.row(e).data() || null) : a.data().toArray();
                    }),
                    (this.fnGetNodes = function (e) {
                        var t = this.api(!0);
                        return e !== i ? t.row(e).node() : t.rows().nodes().flatten().toArray();
                    }),
                    (this.fnGetPosition = function (e) {
                        var t = this.api(!0),
                            n = e.nodeName.toUpperCase();
                        return "TR" == n ? t.row(e).index() : "TD" == n || "TH" == n ? [(n = t.cell(e).index()).row, n.columnVisible, n.column] : null;
                    }),
                    (this.fnIsOpen = function (e) {
                        return this.api(!0).row(e).child.isShown();
                    }),
                    (this.fnOpen = function (e, t, n) {
                        return this.api(!0).row(e).child(t, n).show().child()[0];
                    }),
                    (this.fnPageChange = function (e, t) {
                        (e = this.api(!0).page(e)), (t === i || t) && e.draw(!1);
                    }),
                    (this.fnSetColumnVis = function (e, t, n) {
                        (e = this.api(!0).column(e).visible(t)), (n === i || n) && e.columns.adjust().draw();
                    }),
                    (this.fnSettings = function () {
                        return eK(this[p.iApiIndex]);
                    }),
                    (this.fnSort = function (e) {
                        this.api(!0).order(e).draw();
                    }),
                    (this.fnSortListener = function (e, t, n) {
                        this.api(!0).order.listener(e, t, n);
                    }),
                    (this.fnUpdate = function (e, t, n, a, s) {
                        var o = this.api(!0);
                        return (n === i || null === n ? o.row(t) : o.cell(t, n)).data(e), (s === i || s) && o.columns.adjust(), (a === i || a) && o.draw(), 0;
                    }),
                    (this.fnVersionCheck = p.fnVersionCheck);
                var a,
                    s = this,
                    o = n === i,
                    l = this.length;
                for (a in (o && (n = {}), (this.oApi = this.internal = p.internal), m.ext.internal)) a && (this[a] = tN(a));
                return (
                    this.each(function () {
                        var t = 1 < l ? te({}, n, !0) : n,
                            a = 0,
                            u = this.getAttribute("id"),
                            d = !1,
                            c = m.defaults,
                            p = e(this);
                        if ("table" != this.nodeName.toLowerCase()) eZ(null, 0, "Non-table node initialisation (" + this.nodeName + ")", 2);
                        else {
                            A(c), I(c.column), D(c, c, !0), D(c.column, c.column, !0), D(c, e.extend(t, p.data()), !0);
                            for (var f = m.settings, h = ((a = 0), f.length); a < h; a++) {
                                var g = f[a];
                                if (g.nTable == this || (g.nTHead && g.nTHead.parentNode == this) || (g.nTFoot && g.nTFoot.parentNode == this)) {
                                    var v = (t.bRetrieve !== i ? t : c).bRetrieve,
                                        $ = (t.bDestroy !== i ? t : c).bDestroy;
                                    if (o || v) return g.oInstance;
                                    if ($) {
                                        g.oInstance.fnDestroy();
                                        break;
                                    }
                                    return void eZ(g, 0, "Cannot reinitialise DataTable", 3);
                                }
                                if (g.sTableId == this.id) {
                                    f.splice(a, 1);
                                    break;
                                }
                            }
                            (null !== u && "" !== u) || ((u = "DataTables_Table_" + m.ext._unique++), (this.id = u));
                            var y,
                                b,
                                _ = e.extend(!0, {}, m.models.oSettings, { sDestroyWidth: p[0].style.width, sInstance: u, sTableId: u }),
                                w =
                                    ((_.nTable = this),
                                    (_.oApi = s.internal),
                                    (_.oInit = t),
                                    f.push(_),
                                    (_.oInstance = 1 === s.length ? s : p.dataTable()),
                                    A(t),
                                    k(t.oLanguage),
                                    t.aLengthMenu && !t.iDisplayLength && (t.iDisplayLength = (Array.isArray(t.aLengthMenu[0]) ? t.aLengthMenu[0] : t.aLengthMenu)[0]),
                                    (t = te(e.extend(!0, {}, c), t)),
                                    eQ(_.oFeatures, t, ["bPaginate", "bLengthChange", "bFilter", "bSort", "bSortMulti", "bInfo", "bProcessing", "bAutoWidth", "bSortClasses", "bServerSide", "bDeferRender"]),
                                    eQ(_, t, [
                                        "asStripeClasses",
                                        "ajax",
                                        "fnServerData",
                                        "fnFormatNumber",
                                        "sServerMethod",
                                        "aaSorting",
                                        "aaSortingFixed",
                                        "aLengthMenu",
                                        "sPaginationType",
                                        "sAjaxSource",
                                        "sAjaxDataProp",
                                        "iStateDuration",
                                        "sDom",
                                        "bSortCellsTop",
                                        "iTabIndex",
                                        "fnStateLoadCallback",
                                        "fnStateSaveCallback",
                                        "renderer",
                                        "searchDelay",
                                        "rowId",
                                        ["iCookieDuration", "iStateDuration"],
                                        ["oSearch", "oPreviousSearch"],
                                        ["aoSearchCols", "aoPreSearchCols"],
                                        ["iDisplayLength", "_iDisplayLength"],
                                    ]),
                                    eQ(_.oScroll, t, [
                                        ["sScrollX", "sX"],
                                        ["sScrollXInner", "sXInner"],
                                        ["sScrollY", "sY"],
                                        ["bScrollCollapse", "bCollapse"],
                                    ]),
                                    eQ(_.oLanguage, t, "fnInfoCallback"),
                                    tn(_, "aoDrawCallback", t.fnDrawCallback, "user"),
                                    tn(_, "aoServerParams", t.fnServerParams, "user"),
                                    tn(_, "aoStateSaveParams", t.fnStateSaveParams, "user"),
                                    tn(_, "aoStateLoadParams", t.fnStateLoadParams, "user"),
                                    tn(_, "aoStateLoaded", t.fnStateLoaded, "user"),
                                    tn(_, "aoRowCallback", t.fnRowCallback, "user"),
                                    tn(_, "aoRowCreatedCallback", t.fnCreatedRow, "user"),
                                    tn(_, "aoHeaderCallback", t.fnHeaderCallback, "user"),
                                    tn(_, "aoFooterCallback", t.fnFooterCallback, "user"),
                                    tn(_, "aoInitComplete", t.fnInitComplete, "user"),
                                    tn(_, "aoPreDrawCallback", t.fnPreDrawCallback, "user"),
                                    (_.rowIdFn = K(t.rowId)),
                                    P(_),
                                    _.oClasses),
                                x =
                                    (e.extend(w, m.ext.classes, t.oClasses),
                                    p.addClass(w.sTable),
                                    _.iInitDisplayStart === i && ((_.iInitDisplayStart = t.iDisplayStart), (_._iDisplayStart = t.iDisplayStart)),
                                    null !== t.iDeferLoading &&
                                        ((_.bDeferLoading = !0), (u = Array.isArray(t.iDeferLoading)), (_._iRecordsDisplay = u ? t.iDeferLoading[0] : t.iDeferLoading), (_._iRecordsTotal = u ? t.iDeferLoading[1] : t.iDeferLoading)),
                                    _.oLanguage),
                                S =
                                    ((u =
                                        (e.extend(!0, x, t.oLanguage),
                                        x.sUrl
                                            ? (e.ajax({
                                                  dataType: "json",
                                                  url: x.sUrl,
                                                  success: function (t) {
                                                      D(c.oLanguage, t), k(t), e.extend(!0, x, t, _.oInit.oLanguage), ti(_, null, "i18n", [_]), eI(_);
                                                  },
                                                  error: function () {
                                                      eI(_);
                                                  },
                                              }),
                                              (d = !0))
                                            : ti(_, null, "i18n", [_]),
                                        null === t.asStripeClasses && (_.asStripeClasses = [w.sStripeOdd, w.sStripeEven]),
                                        _.asStripeClasses)),
                                    p.children("tbody").find("tr").eq(0)),
                                T =
                                    (-1 !==
                                        e.inArray(
                                            !0,
                                            e.map(u, function (e, t) {
                                                return S.hasClass(e);
                                            })
                                        ) && (e("tbody tr", this).removeClass(u.join(" ")), (_.asDestroyStripes = u.slice())),
                                    []);
                            if ((0 !== (u = this.getElementsByTagName("thead")).length && (ec(_.aoHeader, u[0]), (T = ep(_))), null === t.aoColumns)) for (y = [], a = 0, h = T.length; a < h; a++) y.push(null);
                            else y = t.aoColumns;
                            for (a = 0, h = y.length; a < h; a++) M(_, T ? T[a] : null);
                            q(_, t.aoColumnDefs, y, function (e, t) {
                                O(_, e, t);
                            }),
                                S.length &&
                                    ((b = function (e, t) {
                                        return null !== e.getAttribute("data-" + t) ? t : null;
                                    }),
                                    e(S[0])
                                        .children("th, td")
                                        .each(function (e, t) {
                                            var n,
                                                a = _.aoColumns[e];
                                            a || eZ(_, 0, "Incorrect column count", 18),
                                                a.mData === e &&
                                                    ((n = b(t, "sort") || b(t, "order")),
                                                    (t = b(t, "filter") || b(t, "search")),
                                                    (null === n && null === t) ||
                                                        ((a.mData = { _: e + ".display", sort: null !== n ? e + ".@data-" + n : i, type: null !== n ? e + ".@data-" + n : i, filter: null !== t ? e + ".@data-" + t : i }),
                                                        (a._isArrayHost = !0),
                                                        O(_, e)));
                                        }));
                            var C = _.oFeatures;
                            (u = function () {
                                if (t.aaSorting === i) {
                                    var n = _.aaSorting;
                                    for (a = 0, h = n.length; a < h; a++) n[a][1] = _.aoColumns[a].asSorting[0];
                                }
                                eX(_),
                                    C.bSort &&
                                        tn(_, "aoDrawCallback", function () {
                                            var t, n;
                                            _.bSorted &&
                                                ((t = e2(_)),
                                                (n = {}),
                                                e.each(t, function (e, t) {
                                                    n[t.src] = t.dir;
                                                }),
                                                ti(_, null, "order", [_, t, n]),
                                                e6(_));
                                        }),
                                    tn(
                                        _,
                                        "aoDrawCallback",
                                        function () {
                                            (_.bSorted || "ssp" === ts(_) || C.bDeferRender) && eX(_);
                                        },
                                        "sc"
                                    );
                                var s,
                                    o = p.children("caption").each(function () {
                                        this._captionSide = e(this).css("caption-side");
                                    }),
                                    l = (0 === (s = p.children("thead")).length && (s = e("<thead/>").appendTo(p)), (_.nTHead = s[0]), p.children("tbody"));
                                if (
                                    (0 ===
                                        (s =
                                            0 === (s = (0 === l.length && (l = e("<tbody/>").insertAfter(s)), (_.nTBody = l[0]), p.children("tfoot"))).length && 0 < o.length && ("" !== _.oScroll.sX || "" !== _.oScroll.sY)
                                                ? e("<tfoot/>").appendTo(p)
                                                : s).length || 0 === s.children().length
                                        ? p.addClass(w.sNoFooter)
                                        : 0 < s.length && ((_.nTFoot = s[0]), ec(_.aoFooter, _.nTFoot)),
                                    t.aaData)
                                )
                                    for (a = 0; a < t.aaData.length; a++) W(_, t.aaData[a]);
                                else (_.bDeferLoading || "dom" == ts(_)) && G(_, e(_.nTBody).children("tr"));
                                (_.aiDisplay = _.aiDisplayMaster.slice()), (_.bInitialised = !0), !1 === d && eI(_);
                            }),
                                tn(_, "aoDrawCallback", eY, "state_save"),
                                t.bStateSave ? ((C.bStateSave = !0), e5(_, 0, u)) : u();
                        }
                    }),
                    (s = null),
                    this
                );
            },
            g = {},
            v = /[\r\n\u2028]/g,
            $ = /<.*?>/g,
            y = /^\d{2,4}[\.\/\-]\d{1,2}[\.\/\-]\d{1,2}([T ]{1}\d{1,2}[:\.]\d{2}([\.:]\d{2})?)?$/,
            b = RegExp("(\\/|\\.|\\*|\\+|\\?|\\||\\(|\\)|\\[|\\]|\\{|\\}|\\\\|\\$|\\^|\\-)", "g"),
            _ = /['\u00A0,$£€¥%\u2009\u202F\u20BD\u20a9\u20BArfkɃΞ]/gi,
            w = function (e) {
                return !e || !0 === e || "-" === e;
            },
            x = function (e, t) {
                return g[t] || (g[t] = RegExp(eS(t), "g")), "string" == typeof e && "." !== t ? e.replace(/\./g, "").replace(g[t], ".") : e;
            },
            S = function (e, t, n) {
                var a = [],
                    s = 0,
                    o = e.length;
                if (n !== i) for (; s < o; s++) e[s] && e[s][t] && a.push(e[s][t][n]);
                else for (; s < o; s++) e[s] && a.push(e[s][t]);
                return a;
            },
            T = function (e) {
                if (
                    (function (e) {
                        if (!(e.length < 2))
                            for (var t = e.slice().sort(), n = t[0], i = 1, a = t.length; i < a; i++) {
                                if (t[i] === n) return !1;
                                n = t[i];
                            }
                        return !0;
                    })(e)
                )
                    return e.slice();
                var t,
                    n,
                    i,
                    a = [],
                    s = e.length,
                    o = 0;
                e: for (n = 0; n < s; n++) {
                    for (t = e[n], i = 0; i < o; i++) if (a[i] === t) continue e;
                    a.push(t), o++;
                }
                return a;
            },
            C = function (e, t) {
                if (Array.isArray(t)) for (var n = 0; n < t.length; n++) C(e, t[n]);
                else e.push(t);
                return e;
            };
        function E(t) {
            var n,
                i,
                a = {};
            e.each(t, function (e, s) {
                (n = e.match(/^([^A-Z]+?)([A-Z])/)) && -1 !== "a aa ai ao as b fn i m o s ".indexOf(n[1] + " ") && ((a[(i = e.replace(n[0], n[2].toLowerCase()))] = e), "o" === n[1]) && E(t[e]);
            }),
                (t._hungarianMap = a);
        }
        function D(t, n, a) {
            var s;
            t._hungarianMap || E(t),
                e.each(n, function (o, l) {
                    (s = t._hungarianMap[o]) !== i && (a || n[s] === i) && ("o" === s.charAt(0) ? (n[s] || (n[s] = {}), e.extend(!0, n[s], n[o]), D(t[s], n[s], a)) : (n[s] = n[o]));
                });
        }
        function k(e) {
            var t,
                n = m.defaults.oLanguage,
                i = n.sDecimal;
            i && tT(i),
                e &&
                    ((t = e.sZeroRecords),
                    !e.sEmptyTable && t && "No data available in table" === n.sEmptyTable && eQ(e, e, "sZeroRecords", "sEmptyTable"),
                    !e.sLoadingRecords && t && "Loading..." === n.sLoadingRecords && eQ(e, e, "sZeroRecords", "sLoadingRecords"),
                    e.sInfoThousands && (e.sThousands = e.sInfoThousands),
                    (t = e.sDecimal)) &&
                    i !== t &&
                    tT(t);
        }
        Array.isArray ||
            (Array.isArray = function (e) {
                return "[object Array]" === Object.prototype.toString.call(e);
            }),
            Array.prototype.includes || (Array.prototype.includes = c),
            String.prototype.trim ||
                (String.prototype.trim = function () {
                    return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, "");
                }),
            String.prototype.includes || (String.prototype.includes = c),
            (m.util = {
                throttle: function (e, t) {
                    var n,
                        a,
                        s = t !== i ? t : 200;
                    return function () {
                        var t = this,
                            o = +new Date(),
                            l = arguments;
                        n && o < n + s
                            ? (clearTimeout(a),
                              (a = setTimeout(function () {
                                  (n = i), e.apply(t, l);
                              }, s)))
                            : ((n = o), e.apply(t, l));
                    };
                },
                escapeRegex: function (e) {
                    return e.replace(b, "\\$1");
                },
                set: function (t) {
                    var n;
                    return e.isPlainObject(t)
                        ? m.util.set(t._)
                        : null === t
                        ? function () {}
                        : "function" == typeof t
                        ? function (e, n, i) {
                              t(e, "set", n, i);
                          }
                        : "string" != typeof t || (-1 === t.indexOf(".") && -1 === t.indexOf("[") && -1 === t.indexOf("("))
                        ? function (e, n) {
                              e[t] = n;
                          }
                        : ((n = function (e, t, a) {
                              for (var s, o, l, u, d = J(a), c = ((a = d[d.length - 1]), 0), p = d.length - 1; c < p; c++) {
                                  if ("__proto__" === d[c] || "constructor" === d[c]) throw Error("Cannot set prototype values");
                                  if (((s = d[c].match(X)), (o = d[c].match(Y)), s)) {
                                      if (((d[c] = d[c].replace(X, "")), (e[d[c]] = []), (s = d.slice()).splice(0, c + 1), (u = s.join(".")), Array.isArray(t)))
                                          for (var f = 0, h = t.length; f < h; f++) n((l = {}), t[f], u), e[d[c]].push(l);
                                      else e[d[c]] = t;
                                      return;
                                  }
                                  o && ((d[c] = d[c].replace(Y, "")), (e = e[d[c]](t))), (null !== e[d[c]] && e[d[c]] !== i) || (e[d[c]] = {}), (e = e[d[c]]);
                              }
                              a.match(Y) ? e[a.replace(Y, "")](t) : (e[a.replace(X, "")] = t);
                          }),
                          function (e, i) {
                              return n(e, i, t);
                          });
                },
                get: function (t) {
                    var n, a;
                    return e.isPlainObject(t)
                        ? ((n = {}),
                          e.each(t, function (e, t) {
                              t && (n[e] = m.util.get(t));
                          }),
                          function (e, t, a, s) {
                              var o = n[t] || n._;
                              return o !== i ? o(e, t, a, s) : e;
                          })
                        : null === t
                        ? function (e) {
                              return e;
                          }
                        : "function" == typeof t
                        ? function (e, n, i, a) {
                              return t(e, n, i, a);
                          }
                        : "string" != typeof t || (-1 === t.indexOf(".") && -1 === t.indexOf("[") && -1 === t.indexOf("("))
                        ? function (e, n) {
                              return e[t];
                          }
                        : ((a = function (e, t, n) {
                              var s, o, l;
                              if ("" !== n)
                                  for (var u = J(n), d = 0, c = u.length; d < c; d++) {
                                      if (((h = u[d].match(X)), (s = u[d].match(Y)), h)) {
                                          if (((u[d] = u[d].replace(X, "")), "" !== u[d] && (e = e[u[d]]), (o = []), u.splice(0, d + 1), (l = u.join(".")), Array.isArray(e))) for (var p = 0, f = e.length; p < f; p++) o.push(a(e[p], t, l));
                                          var h = h[0].substring(1, h[0].length - 1);
                                          e = "" === h ? o : o.join(h);
                                          break;
                                      }
                                      if (s) (u[d] = u[d].replace(Y, "")), (e = e[u[d]]());
                                      else {
                                          if (null === e || e[u[d]] === i) return i;
                                          e = e[u[d]];
                                      }
                                  }
                              return e;
                          }),
                          function (e, n) {
                              return a(e, n, t);
                          });
                },
            });
        var L = function (e, t, n) {
            e[t] !== i && (e[n] = e[t]);
        };
        function A(e) {
            L(e, "ordering", "bSort"),
                L(e, "orderMulti", "bSortMulti"),
                L(e, "orderClasses", "bSortClasses"),
                L(e, "orderCellsTop", "bSortCellsTop"),
                L(e, "order", "aaSorting"),
                L(e, "orderFixed", "aaSortingFixed"),
                L(e, "paging", "bPaginate"),
                L(e, "pagingType", "sPaginationType"),
                L(e, "pageLength", "iDisplayLength"),
                L(e, "searching", "bFilter"),
                "boolean" == typeof e.sScrollX && (e.sScrollX = e.sScrollX ? "100%" : ""),
                "boolean" == typeof e.scrollX && (e.scrollX = e.scrollX ? "100%" : "");
            var t = e.aoSearchCols;
            if (t) for (var n = 0, i = t.length; n < i; n++) t[n] && D(m.models.oSearch, t[n]);
        }
        function I(e) {
            L(e, "orderable", "bSortable"), L(e, "orderData", "aDataSort"), L(e, "orderSequence", "asSorting"), L(e, "orderDataType", "sortDataType");
            var t = e.aDataSort;
            "number" != typeof t || Array.isArray(t) || (e.aDataSort = [t]);
        }
        function P(n) {
            var i, a, s, o;
            m.__browser ||
                ((m.__browser = i = {}),
                (o = (s = (a = e("<div/>")
                    .css({ position: "fixed", top: 0, left: -1 * e(t).scrollLeft(), height: 1, width: 1, overflow: "hidden" })
                    .append(
                        e("<div/>")
                            .css({ position: "absolute", top: 1, left: 1, width: 100, overflow: "scroll" })
                            .append(e("<div/>").css({ width: "100%", height: 10 }))
                    )
                    .appendTo("body")).children()).children()),
                (i.barWidth = s[0].offsetWidth - s[0].clientWidth),
                (i.bScrollOversize = 100 === o[0].offsetWidth && 100 !== s[0].clientWidth),
                (i.bScrollbarLeft = 1 !== Math.round(o.offset().left)),
                (i.bBounding = !!a[0].getBoundingClientRect().width),
                a.remove()),
                e.extend(n.oBrowser, m.__browser),
                (n.oScroll.iBarWidth = m.__browser.barWidth);
        }
        function N(e, t, n, a, s, o) {
            var l,
                u = a,
                d = !1;
            for (n !== i && ((l = n), (d = !0)); u !== s; ) e.hasOwnProperty(u) && ((l = d ? t(l, e[u], u, e) : e[u]), (d = !0), (u += o));
            return l;
        }
        function M(t, i) {
            var a = m.defaults.column,
                s = t.aoColumns.length;
            (a = e.extend({}, m.models.oColumn, a, { nTh: i || n.createElement("th"), sTitle: a.sTitle || (i ? i.innerHTML : ""), aDataSort: a.aDataSort || [s], mData: a.mData || s, idx: s })),
                ((a = (t.aoColumns.push(a), t.aoPreSearchCols))[s] = e.extend({}, m.models.oSearch, a[s])),
                O(t, s, e(i).data());
        }
        function O(t, n, a) {
            function s(e) {
                return "string" == typeof e && -1 !== e.indexOf("@");
            }
            n = t.aoColumns[n];
            var o = t.oClasses,
                l = e(n.nTh),
                u =
                    (!n.sWidthOrig && ((n.sWidthOrig = l.attr("width") || null), (p = (l.attr("style") || "").match(/width:\s*(\d+[pxem%]+)/))) && (n.sWidthOrig = p[1]),
                    a !== i &&
                        null !== a &&
                        (I(a),
                        D(m.defaults.column, a, !0),
                        a.mDataProp === i || a.mData || (a.mData = a.mDataProp),
                        a.sType && (n._sManualType = a.sType),
                        a.className && !a.sClass && (a.sClass = a.className),
                        a.sClass && l.addClass(a.sClass),
                        (p = n.sClass),
                        e.extend(n, a),
                        eQ(n, a, "sWidth", "sWidthOrig"),
                        p !== n.sClass && (n.sClass = p + " " + n.sClass),
                        a.iDataSort !== i && (n.aDataSort = [a.iDataSort]),
                        eQ(n, a, "aDataSort")),
                    n.mData),
                d = K(u),
                c = n.mRender ? K(n.mRender) : null,
                p =
                    ((n._bAttrSrc = e.isPlainObject(u) && (s(u.sort) || s(u.type) || s(u.filter))),
                    (n._setter = null),
                    (n.fnGetData = function (e, t, n) {
                        var a = d(e, t, i, n);
                        return c && t ? c(a, t, e, n) : a;
                    }),
                    (n.fnSetData = function (e, t, n) {
                        return Z(u)(e, t, n);
                    }),
                    "number" == typeof u || n._isArrayHost || (t._rowReadObject = !0),
                    t.oFeatures.bSort || ((n.bSortable = !1), l.addClass(o.sSortableNone)),
                    -1 !== e.inArray("asc", n.asSorting));
            (a = -1 !== e.inArray("desc", n.asSorting)),
                n.bSortable && (p || a)
                    ? p && !a
                        ? ((n.sSortingClass = o.sSortableAsc), (n.sSortingClassJUI = o.sSortJUIAscAllowed))
                        : !p && a
                        ? ((n.sSortingClass = o.sSortableDesc), (n.sSortingClassJUI = o.sSortJUIDescAllowed))
                        : ((n.sSortingClass = o.sSortable), (n.sSortingClassJUI = o.sSortJUI))
                    : ((n.sSortingClass = o.sSortableNone), (n.sSortingClassJUI = ""));
        }
        function R(e) {
            if (!1 !== e.oFeatures.bAutoWidth) {
                var t = e.aoColumns;
                e1(e);
                for (var n = 0, i = t.length; n < i; n++) t[n].nTh.style.width = t[n].sWidth;
            }
            var a = e.oScroll;
            ("" === a.sY && "" === a.sX) || eF(e), ti(e, null, "column-sizing", [e]);
        }
        function z(e, t) {
            return "number" == typeof (e = H(e, "bVisible"))[t] ? e[t] : null;
        }
        function F(t, n) {
            return (t = H(t, "bVisible")), -1 !== (n = e.inArray(n, t)) ? n : null;
        }
        function j(t) {
            var n = 0;
            return (
                e.each(t.aoColumns, function (t, i) {
                    i.bVisible && "none" !== e(i.nTh).css("display") && n++;
                }),
                n
            );
        }
        function H(t, n) {
            var i = [];
            return (
                e.map(t.aoColumns, function (e, t) {
                    e[n] && i.push(t);
                }),
                i
            );
        }
        function B(e) {
            for (var t, n, a, s, o, l, u, d = e.aoColumns, c = e.aoData, p = m.ext.type.detect, f = 0, h = d.length; f < h; f++)
                if (((u = []), !(o = d[f]).sType && o._sManualType)) o.sType = o._sManualType;
                else if (!o.sType) {
                    for (t = 0, n = p.length; t < n; t++) {
                        for (a = 0, s = c.length; a < s && (u[a] === i && (u[a] = U(e, a, f, "type")), (l = p[t](u[a], e)) || t === p.length - 1) && ("html" !== l || w(u[a])); a++);
                        if (l) {
                            o.sType = l;
                            break;
                        }
                    }
                    o.sType || (o.sType = "string");
                }
        }
        function q(t, n, a, s) {
            var o,
                l,
                u,
                d,
                c = t.aoColumns;
            if (n) {
                for (o = n.length - 1; 0 <= o; o--)
                    for (var p, f = (p = n[o]).target !== i ? p.target : p.targets !== i ? p.targets : p.aTargets, h = 0, m = (f = Array.isArray(f) ? f : [f]).length; h < m; h++)
                        if ("number" == typeof f[h] && 0 <= f[h]) {
                            for (; c.length <= f[h]; ) M(t);
                            s(f[h], p);
                        } else if ("number" == typeof f[h] && f[h] < 0) s(c.length + f[h], p);
                        else if ("string" == typeof f[h]) for (u = 0, d = c.length; u < d; u++) ("_all" == f[h] || e(c[u].nTh).hasClass(f[h])) && s(u, p);
            }
            if (a) for (o = 0, l = a.length; o < l; o++) s(o, a[o]);
        }
        function W(t, n, a, s) {
            for (var o = t.aoData.length, l = e.extend(!0, {}, m.models.oRow, { src: a ? "dom" : "data", idx: o }), u = ((l._aData = n), t.aoData.push(l), t.aoColumns), d = 0, c = u.length; d < c; d++) u[d].sType = null;
            return t.aiDisplayMaster.push(o), (n = t.rowIdFn(n)) !== i && (t.aIds[n] = l), (!a && t.oFeatures.bDeferRender) || er(t, o, a, s), o;
        }
        function G(t, n) {
            var i;
            return (n = n instanceof e ? n : e(n)).map(function (e, n) {
                return (i = ei(t, n)), W(t, i.data, n, i.cells);
            });
        }
        function U(e, t, n, a) {
            "search" === a ? (a = "filter") : "order" === a && (a = "sort");
            var s = e.iDraw,
                o = e.aoColumns[n],
                l = e.aoData[t]._aData,
                u = o.sDefaultContent,
                d = o.fnGetData(l, a, { settings: e, row: t, col: n });
            if (d === i)
                return e.iDrawError != s && null === u && (eZ(e, 0, "Requested unknown parameter " + ("function" == typeof o.mData ? "{function}" : "'" + o.mData + "'") + " for row " + t + ", column " + n, 4), (e.iDrawError = s)), u;
            if ((d !== l && null !== d) || null === u || a === i) {
                if ("function" == typeof d) return d.call(l);
            } else d = u;
            return null === d && "display" === a ? "" : "filter" === a && (t = m.ext.type.search)[o.sType] ? t[o.sType](d) : d;
        }
        function V(e, t, n, i) {
            var a = e.aoColumns[n],
                s = e.aoData[t]._aData;
            a.fnSetData(s, i, { settings: e, row: t, col: n });
        }
        var X = /\[.*?\]$/,
            Y = /\(\)$/;
        function J(t) {
            return e.map(t.match(/(\\.|[^\.])+/g) || [""], function (e) {
                return e.replace(/\\\./g, ".");
            });
        }
        var K = m.util.get,
            Z = m.util.set;
        function Q(e) {
            return S(e.aoData, "_aData");
        }
        function ee(e) {
            (e.aoData.length = 0), (e.aiDisplayMaster.length = 0), (e.aiDisplay.length = 0), (e.aIds = {});
        }
        function et(e, t, n) {
            for (var a = -1, s = 0, o = e.length; s < o; s++) e[s] == t ? (a = s) : e[s] > t && e[s]--;
            -1 != a && n === i && e.splice(a, 1);
        }
        function en(e, t, n, a) {
            function s(n, i) {
                for (; n.childNodes.length; ) n.removeChild(n.firstChild);
                n.innerHTML = U(e, t, i, "display");
            }
            var o,
                l,
                u = e.aoData[t];
            if ("dom" !== n && ((n && "auto" !== n) || "dom" !== u.src)) {
                var d = u.anCells;
                if (d) {
                    if (a !== i) s(d[a], a);
                    else for (o = 0, l = d.length; o < l; o++) s(d[o], o);
                }
            } else u._aData = ei(e, u, a, a === i ? i : u._aData).data;
            (u._aSortData = null), (u._aFilterData = null);
            var c = e.aoColumns;
            if (a !== i) c[a].sType = null;
            else {
                for (o = 0, l = c.length; o < l; o++) c[o].sType = null;
                ea(e, u);
            }
        }
        function ei(e, t, n, a) {
            function s(e, t) {
                var n;
                "string" == typeof e && -1 !== (n = e.indexOf("@")) && ((n = e.substring(n + 1)), Z(e)(a, t.getAttribute(n)));
            }
            function o(e) {
                (n !== i && n !== f) ||
                    ((u = h[f]), (d = e.innerHTML.trim()), u && u._bAttrSrc ? (Z(u.mData._)(a, d), s(u.mData.sort, e), s(u.mData.type, e), s(u.mData.filter, e)) : m ? (u._setter || (u._setter = Z(u.mData)), u._setter(a, d)) : (a[f] = d)),
                    f++;
            }
            var l,
                u,
                d,
                c = [],
                p = t.firstChild,
                f = 0,
                h = e.aoColumns,
                m = e._rowReadObject;
            if (((a = a !== i ? a : m ? {} : []), p)) for (; p; ) ("TD" != (l = p.nodeName.toUpperCase()) && "TH" != l) || (o(p), c.push(p)), (p = p.nextSibling);
            else for (var g = 0, v = (c = t.anCells).length; g < v; g++) o(c[g]);
            return (t = t.firstChild ? t : t.nTr) && (t = t.getAttribute("id")) && Z(e.rowId)(a, t), { data: a, cells: c };
        }
        function er(t, i, a, s) {
            var o,
                l,
                u,
                d,
                c,
                p,
                f = t.aoData[i],
                h = f._aData,
                m = [];
            if (null === f.nTr) {
                for (o = a || n.createElement("tr"), f.nTr = o, f.anCells = m, o._DT_RowIndex = i, ea(t, f), d = 0, c = t.aoColumns.length; d < c; d++)
                    (u = t.aoColumns[d]),
                        (l = (p = !a) ? n.createElement(u.sCellType) : s[d]) || eZ(t, 0, "Incorrect column count", 18),
                        (l._DT_CellIndex = { row: i, column: d }),
                        m.push(l),
                        (!p && ((!u.mRender && u.mData === d) || (e.isPlainObject(u.mData) && u.mData._ === d + ".display"))) || (l.innerHTML = U(t, i, d, "display")),
                        u.sClass && (l.className += " " + u.sClass),
                        u.bVisible && !a ? o.appendChild(l) : !u.bVisible && a && l.parentNode.removeChild(l),
                        u.fnCreatedCell && u.fnCreatedCell.call(t.oInstance, l, U(t, i, d), h, i, d);
                ti(t, "aoRowCreatedCallback", null, [o, h, i, m]);
            }
        }
        function ea(t, n) {
            var i = n.nTr,
                a = n._aData;
            i &&
                ((t = t.rowIdFn(a)) && (i.id = t),
                a.DT_RowClass && ((t = a.DT_RowClass.split(" ")), (n.__rowc = n.__rowc ? T(n.__rowc.concat(t)) : t), e(i).removeClass(n.__rowc.join(" ")).addClass(a.DT_RowClass)),
                a.DT_RowAttr && e(i).attr(a.DT_RowAttr),
                a.DT_RowData) &&
                e(i).data(a.DT_RowData);
        }
        function es(t) {
            var n,
                i,
                a,
                s = t.nTHead,
                o = t.nTFoot,
                l = 0 === e("th, td", s).length,
                u = t.oClasses,
                d = t.aoColumns;
            for (l && (i = e("<tr/>").appendTo(s)), p = 0, f = d.length; p < f; p++)
                (n = e((a = d[p]).nTh).addClass(a.sClass)),
                    l && n.appendTo(i),
                    t.oFeatures.bSort && (n.addClass(a.sSortingClass), !1 !== a.bSortable) && (n.attr("tabindex", t.iTabIndex).attr("aria-controls", t.sTableId), eV(t, a.nTh, p)),
                    a.sTitle != n[0].innerHTML && n.html(a.sTitle),
                    ta(t, "header")(t, n, a, u);
            if ((l && ec(t.aoHeader, s), e(s).children("tr").children("th, td").addClass(u.sHeaderTH), e(o).children("tr").children("th, td").addClass(u.sFooterTH), null !== o))
                for (var c = t.aoFooter[0], p = 0, f = c.length; p < f; p++) (a = d[p]) ? ((a.nTf = c[p].cell), a.sClass && e(a.nTf).addClass(a.sClass)) : eZ(t, 0, "Incorrect column count", 18);
        }
        function eo(t, n, a) {
            var s,
                o,
                l,
                u,
                d,
                c,
                p,
                f,
                h,
                m = [],
                g = [],
                v = t.aoColumns.length;
            if (n) {
                for (a === i && (a = !1), s = 0, o = n.length; s < o; s++) {
                    for (m[s] = n[s].slice(), m[s].nTr = n[s].nTr, l = v - 1; 0 <= l; l--) t.aoColumns[l].bVisible || a || m[s].splice(l, 1);
                    g.push([]);
                }
                for (s = 0, o = m.length; s < o; s++) {
                    if ((p = m[s].nTr)) for (; (c = p.firstChild); ) p.removeChild(c);
                    for (l = 0, u = m[s].length; l < u; l++)
                        if (((h = f = 1), g[s][l] === i)) {
                            for (p.appendChild(m[s][l].cell), g[s][l] = 1; m[s + f] !== i && m[s][l].cell == m[s + f][l].cell; ) (g[s + f][l] = 1), f++;
                            for (; m[s][l + h] !== i && m[s][l].cell == m[s][l + h].cell; ) {
                                for (d = 0; d < f; d++) g[s + d][l + h] = 1;
                                h++;
                            }
                            e(m[s][l].cell).attr("rowspan", f).attr("colspan", h);
                        }
                }
            }
        }
        function el(t, n) {
            (a = "ssp" == ts((c = t))), (d = c.iInitDisplayStart) !== i && -1 !== d && ((c._iDisplayStart = !a && d >= c.fnRecordsDisplay() ? 0 : d), (c.iInitDisplayStart = -1));
            var a = ti(t, "aoPreDrawCallback", "preDraw", [t]);
            if (-1 !== e.inArray(!1, a)) ez(t, !1);
            else {
                var s = [],
                    o = 0,
                    l = t.asStripeClasses,
                    u = l.length,
                    d = t.oLanguage,
                    c = "ssp" == ts(t),
                    p = t.aiDisplay,
                    f = ((a = t._iDisplayStart), t.fnDisplayEnd());
                if (((t.bDrawing = !0), t.bDeferLoading)) (t.bDeferLoading = !1), t.iDraw++, ez(t, !1);
                else if (c) {
                    if (!t.bDestroying && !n) return void eh(t);
                } else t.iDraw++;
                if (0 !== p.length)
                    for (var h = c ? t.aoData.length : f, m = c ? 0 : a; m < h; m++) {
                        var g,
                            v = p[m],
                            $ = t.aoData[v],
                            y = (null === $.nTr && er(t, v), $.nTr);
                        0 !== u && ((g = l[o % u]), $._sRowStripe != g) && (e(y).removeClass($._sRowStripe).addClass(g), ($._sRowStripe = g)), ti(t, "aoRowCallback", null, [y, $._aData, o, m, v]), s.push(y), o++;
                    }
                else
                    (n = d.sZeroRecords),
                        1 == t.iDraw && "ajax" == ts(t) ? (n = d.sLoadingRecords) : d.sEmptyTable && 0 === t.fnRecordsTotal() && (n = d.sEmptyTable),
                        (s[0] = e("<tr/>", { class: u ? l[0] : "" }).append(e("<td />", { valign: "top", colSpan: j(t), class: t.oClasses.sRowEmpty }).html(n))[0]);
                ti(t, "aoHeaderCallback", "header", [e(t.nTHead).children("tr")[0], Q(t), a, f, p]),
                    ti(t, "aoFooterCallback", "footer", [e(t.nTFoot).children("tr")[0], Q(t), a, f, p]),
                    (c = e(t.nTBody)).children().detach(),
                    c.append(e(s)),
                    ti(t, "aoDrawCallback", "draw", [t]),
                    (t.bSorted = !1),
                    (t.bFiltered = !1),
                    (t.bDrawing = !1);
            }
        }
        function eu(e, t) {
            var n = (i = e.oFeatures).bSort,
                i = i.bFilter;
            n && e4(e), i ? ey(e, e.oPreviousSearch) : (e.aiDisplay = e.aiDisplayMaster.slice()), !0 !== t && (e._iDisplayStart = 0), (e._drawHold = t), el(e), (e._drawHold = !1);
        }
        function ed(t) {
            for (
                var n,
                    i,
                    a,
                    s,
                    o,
                    l,
                    u,
                    d = t.oClasses,
                    c = e(t.nTable),
                    p = ((c = e("<div/>").insertBefore(c)), t.oFeatures),
                    f = e("<div/>", { id: t.sTableId + "_wrapper", class: d.sWrapper + (t.nTFoot ? "" : " " + d.sNoFooter) }),
                    h = ((t.nHolding = c[0]), (t.nTableWrapper = f[0]), (t.nTableReinsertBefore = t.nTable.nextSibling), t.sDom.split("")),
                    g = 0;
                g < h.length;
                g++
            ) {
                if (((n = null), "<" == (i = h[g]))) {
                    if (((a = e("<div/>")[0]), "'" == (s = h[g + 1]) || '"' == s)) {
                        for (o = "", l = 2; h[g + l] != s; ) (o += h[g + l]), l++;
                        "H" == o ? (o = d.sJUIHeader) : "F" == o && (o = d.sJUIFooter),
                            -1 != o.indexOf(".") ? ((u = o.split(".")), (a.id = u[0].substr(1, u[0].length - 1)), (a.className = u[1])) : "#" == o.charAt(0) ? (a.id = o.substr(1, o.length - 1)) : (a.className = o),
                            (g += l);
                    }
                    f.append(a), (f = e(a));
                } else if (">" == i) f = f.parent();
                else if ("l" == i && p.bPaginate && p.bLengthChange) n = eN(t);
                else if ("f" == i && p.bFilter) n = e$(t);
                else if ("r" == i && p.bProcessing) n = eR(t);
                else if ("t" == i) n = e9(t);
                else if ("i" == i && p.bInfo) n = ek(t);
                else if ("p" == i && p.bPaginate) n = eM(t);
                else if (0 !== m.ext.feature.length) {
                    for (var v = m.ext.feature, $ = 0, y = v.length; $ < y; $++)
                        if (i == v[$].cFeature) {
                            n = v[$].fnInit(t);
                            break;
                        }
                }
                n && ((u = t.aanFeatures)[i] || (u[i] = []), u[i].push(n), f.append(n));
            }
            c.replaceWith(f), (t.nHolding = null);
        }
        function ec(t, n) {
            var i,
                a,
                s,
                o,
                l,
                u,
                d,
                c,
                p,
                f,
                h = e(n).children("tr");
            for (t.splice(0, t.length), s = 0, u = h.length; s < u; s++) t.push([]);
            for (s = 0, u = h.length; s < u; s++)
                for (a = (i = h[s]).firstChild; a; ) {
                    if ("TD" == a.nodeName.toUpperCase() || "TH" == a.nodeName.toUpperCase())
                        for (
                            c = (c = +a.getAttribute("colspan")) && 0 != c && 1 != c ? c : 1,
                                p = (p = +a.getAttribute("rowspan")) && 0 != p && 1 != p ? p : 1,
                                d = (function (e, t, n) {
                                    for (var i = e[t]; i[n]; ) n++;
                                    return n;
                                })(t, s, 0),
                                f = 1 == c,
                                l = 0;
                            l < c;
                            l++
                        )
                            for (o = 0; o < p; o++) (t[s + o][d + l] = { cell: a, unique: f }), (t[s + o].nTr = i);
                    a = a.nextSibling;
                }
        }
        function ep(e, t, n) {
            var i = [];
            n || ((n = e.aoHeader), t && ec((n = []), t));
            for (var a = 0, s = n.length; a < s; a++) for (var o = 0, l = n[a].length; o < l; o++) !n[a][o].unique || (i[o] && e.bSortCellsTop) || (i[o] = n[a][o].cell);
            return i;
        }
        function ef(t, n, i) {
            function a(e) {
                var n = t.jqXHR ? t.jqXHR.status : null;
                (null === e || ("number" == typeof n && 204 == n)) && ev(t, (e = {}), []), (n = e.error || e.sError) && eZ(t, 0, n), (t.json = e), ti(t, null, "xhr", [t, e, t.jqXHR]), i(e);
            }
            ti(t, "aoServerParams", "serverParams", [n]),
                n &&
                    Array.isArray(n) &&
                    ((s = {}),
                    (o = /(.*?)\[\]$/),
                    e.each(n, function (e, t) {
                        var n = t.name.match(o);
                        n ? (s[(n = n[0])] || (s[n] = []), s[n].push(t.value)) : (s[t.name] = t.value);
                    }),
                    (n = s));
            var s,
                o,
                l,
                u = t.ajax,
                d = t.oInstance,
                c =
                    (e.isPlainObject(u) && u.data && ((c = "function" == typeof (l = u.data) ? l(n, t) : l), (n = "function" == typeof l && c ? c : e.extend(!0, n, c)), delete u.data),
                    {
                        data: n,
                        success: a,
                        dataType: "json",
                        cache: !1,
                        type: t.sServerMethod,
                        error: function (n, i, a) {
                            var s = ti(t, null, "xhr", [t, null, t.jqXHR]);
                            -1 === e.inArray(!0, s) && ("parsererror" == i ? eZ(t, 0, "Invalid JSON response", 1) : 4 === n.readyState && eZ(t, 0, "Ajax error", 7)), ez(t, !1);
                        },
                    });
            (t.oAjaxData = n),
                ti(t, null, "preXhr", [t, n]),
                t.fnServerData
                    ? t.fnServerData.call(
                          d,
                          t.sAjaxSource,
                          e.map(n, function (e, t) {
                              return { name: t, value: e };
                          }),
                          a,
                          t
                      )
                    : t.sAjaxSource || "string" == typeof u
                    ? (t.jqXHR = e.ajax(e.extend(c, { url: u || t.sAjaxSource })))
                    : "function" == typeof u
                    ? (t.jqXHR = u.call(d, n, a, t))
                    : ((t.jqXHR = e.ajax(e.extend(c, u))), (u.data = l));
        }
        function eh(e) {
            e.iDraw++,
                ez(e, !0),
                ef(e, em(e), function (t) {
                    eg(e, t);
                });
        }
        function em(t) {
            for (
                var n,
                    i,
                    a,
                    s = t.aoColumns,
                    o = s.length,
                    l = t.oFeatures,
                    u = t.oPreviousSearch,
                    d = t.aoPreSearchCols,
                    c = [],
                    p = e2(t),
                    f = t._iDisplayStart,
                    h = !1 !== l.bPaginate ? t._iDisplayLength : -1,
                    g = function (e, t) {
                        c.push({ name: e, value: t });
                    },
                    v =
                        (g("sEcho", t.iDraw),
                        g("iColumns", o),
                        g("sColumns", S(s, "sName").join(",")),
                        g("iDisplayStart", f),
                        g("iDisplayLength", h),
                        { draw: t.iDraw, columns: [], order: [], start: f, length: h, search: { value: u.sSearch, regex: u.bRegex } }),
                    $ = 0;
                $ < o;
                $++
            )
                (i = s[$]),
                    (a = d[$]),
                    (n = "function" == typeof i.mData ? "function" : i.mData),
                    v.columns.push({ data: n, name: i.sName, searchable: i.bSearchable, orderable: i.bSortable, search: { value: a.sSearch, regex: a.bRegex } }),
                    g("mDataProp_" + $, n),
                    l.bFilter && (g("sSearch_" + $, a.sSearch), g("bRegex_" + $, a.bRegex), g("bSearchable_" + $, i.bSearchable)),
                    l.bSort && g("bSortable_" + $, i.bSortable);
            return (
                l.bFilter && (g("sSearch", u.sSearch), g("bRegex", u.bRegex)),
                l.bSort &&
                    (e.each(p, function (e, t) {
                        v.order.push({ column: t.col, dir: t.dir }), g("iSortCol_" + e, t.col), g("sSortDir_" + e, t.dir);
                    }),
                    g("iSortingCols", p.length)),
                null === (f = m.ext.legacy.ajax) ? (t.sAjaxSource ? c : v) : f ? c : v
            );
        }
        function eg(e, t) {
            function n(e, n) {
                return t[e] !== i ? t[e] : t[n];
            }
            var a = ev(e, t),
                s = n("sEcho", "draw"),
                o = n("iTotalRecords", "recordsTotal"),
                l = n("iTotalDisplayRecords", "recordsFiltered");
            if (s !== i) {
                if (+s < e.iDraw) return;
                e.iDraw = +s;
            }
            (a = a || []), ee(e), (e._iRecordsTotal = parseInt(o, 10)), (e._iRecordsDisplay = parseInt(l, 10));
            for (var u = 0, d = a.length; u < d; u++) W(e, a[u]);
            (e.aiDisplay = e.aiDisplayMaster.slice()), el(e, !0), e._bInitComplete || eP(e, t), ez(e, !1);
        }
        function ev(t, n, a) {
            if (((t = e.isPlainObject(t.ajax) && t.ajax.dataSrc !== i ? t.ajax.dataSrc : t.sAjaxDataProp), !a)) return "data" === t ? n.aaData || n[t] : "" !== t ? K(t)(n) : n;
            Z(t)(n, a);
        }
        function e$(t) {
            function i(e) {
                u.f;
                var n = this.value || "";
                (l.return && "Enter" !== e.key) || (n != l.sSearch && (ey(t, { sSearch: n, bRegex: l.bRegex, bSmart: l.bSmart, bCaseInsensitive: l.bCaseInsensitive, return: l.return }), (t._iDisplayStart = 0), el(t)));
            }
            var a = t.oClasses,
                s = t.sTableId,
                o = t.oLanguage,
                l = t.oPreviousSearch,
                u = t.aanFeatures,
                d = '<input type="search" class="' + a.sFilterInput + '"/>',
                c = (c = o.sSearch).match(/_INPUT_/) ? c.replace("_INPUT_", d) : c + d,
                p =
                    ((d = e("<div/>", { id: u.f ? null : s + "_filter", class: a.sFilter }).append(e("<label/>").append(c))),
                    (a = null !== t.searchDelay ? t.searchDelay : "ssp" === ts(t) ? 400 : 0),
                    e("input", d)
                        .val(l.sSearch)
                        .attr("placeholder", o.sSearchPlaceholder)
                        .on("keyup.DT search.DT input.DT paste.DT cut.DT", a ? eB(i, a) : i)
                        .on("mouseup", function (e) {
                            setTimeout(function () {
                                i.call(p[0], e);
                            }, 10);
                        })
                        .on("keypress.DT", function (e) {
                            if (13 == e.keyCode) return !1;
                        })
                        .attr("aria-controls", s));
            return (
                e(t.nTable).on("search.dt.DT", function (e, i) {
                    if (t === i)
                        try {
                            p[0] !== n.activeElement && p.val(l.sSearch);
                        } catch (a) {}
                }),
                d[0]
            );
        }
        function ey(e, t, n) {
            function a(e) {
                (o.sSearch = e.sSearch), (o.bRegex = e.bRegex), (o.bSmart = e.bSmart), (o.bCaseInsensitive = e.bCaseInsensitive), (o.return = e.return);
            }
            function s(e) {
                return e.bEscapeRegex !== i ? !e.bEscapeRegex : e.bRegex;
            }
            var o = e.oPreviousSearch,
                l = e.aoPreSearchCols;
            if ((B(e), "ssp" != ts(e))) {
                ew(e, t.sSearch, n, s(t), t.bSmart, t.bCaseInsensitive, t.return), a(t);
                for (var u = 0; u < l.length; u++) e_(e, l[u].sSearch, u, s(l[u]), l[u].bSmart, l[u].bCaseInsensitive);
                eb(e);
            } else a(t);
            (e.bFiltered = !0), ti(e, null, "search", [e]);
        }
        function eb(t) {
            for (var n, i, a = m.ext.search, s = t.aiDisplay, o = 0, l = a.length; o < l; o++) {
                for (var u = [], d = 0, c = s.length; d < c; d++) (i = s[d]), (n = t.aoData[i]), a[o](t, n._aFilterData, i, n._aData, d) && u.push(i);
                (s.length = 0), e.merge(s, u);
            }
        }
        function e_(e, t, n, i, a, s) {
            if ("" !== t) {
                for (var o, l = [], u = e.aiDisplay, d = ex(t, i, a, s), c = 0; c < u.length; c++) (o = e.aoData[u[c]]._aFilterData[n]), d.test(o) && l.push(u[c]);
                e.aiDisplay = l;
            }
        }
        function ew(e, t, n, i, a, s) {
            var o,
                l,
                u,
                d = ex(t, i, a, s),
                c = ((a = e.oPreviousSearch.sSearch), (s = e.aiDisplayMaster), []);
            if ((0 !== m.ext.search.length && (n = !0), (l = eE(e)), t.length <= 0)) e.aiDisplay = s.slice();
            else {
                for ((l || n || i || a.length > t.length || 0 !== t.indexOf(a) || e.bSorted) && (e.aiDisplay = s.slice()), o = e.aiDisplay, u = 0; u < o.length; u++) d.test(e.aoData[o[u]]._sFilterRow) && c.push(o[u]);
                e.aiDisplay = c;
            }
        }
        function ex(t, n, i, a) {
            return (
                (t = n ? t : eS(t)),
                i &&
                    (t =
                        "^(?=.*?" +
                        e
                            .map(t.match(/"[^"]+"|[^ ]+/g) || [""], function (e) {
                                var t;
                                return (e = '"' === e.charAt(0) && (t = e.match(/^"(.*)"$/)) ? t[1] : e).replace('"', "");
                            })
                            .join(")(?=.*?") +
                        ").*$"),
                RegExp(t, a ? "i" : "")
            );
        }
        var eS = m.util.escapeRegex,
            eT = e("<div>")[0],
            eC = eT.textContent !== i;
        function eE(e) {
            for (var t, n, i, a, s, o = e.aoColumns, l = !1, u = 0, d = e.aoData.length; u < d; u++)
                if (!(s = e.aoData[u])._aFilterData) {
                    for (i = [], t = 0, n = o.length; t < n; t++)
                        o[t].bSearchable ? "string" != typeof (a = null === (a = U(e, u, t, "filter")) ? "" : a) && a.toString && (a = a.toString()) : (a = ""),
                            a.indexOf && -1 !== a.indexOf("&") && ((eT.innerHTML = a), (a = eC ? eT.textContent : eT.innerText)),
                            a.replace && (a = a.replace(/[\r\n\u2028]/g, "")),
                            i.push(a);
                    (s._aFilterData = i), (s._sFilterRow = i.join("  ")), (l = !0);
                }
            return l;
        }
        function e8(e) {
            return { search: e.sSearch, smart: e.bSmart, regex: e.bRegex, caseInsensitive: e.bCaseInsensitive };
        }
        function eD(e) {
            return { sSearch: e.search, bSmart: e.smart, bRegex: e.regex, bCaseInsensitive: e.caseInsensitive };
        }
        function ek(t) {
            var n = t.sTableId,
                i = t.aanFeatures.i,
                a = e("<div/>", { class: t.oClasses.sInfo, id: i ? null : n + "_info" });
            return i || (t.aoDrawCallback.push({ fn: eL, sName: "information" }), a.attr("role", "status").attr("aria-live", "polite"), e(t.nTable).attr("aria-describedby", n + "_info")), a[0];
        }
        function eL(t) {
            var n,
                i,
                a,
                s,
                o,
                l,
                u = t.aanFeatures.i;
            0 !== u.length &&
                ((l = t.oLanguage),
                (n = t._iDisplayStart + 1),
                (i = t.fnDisplayEnd()),
                (a = t.fnRecordsTotal()),
                (o = (s = t.fnRecordsDisplay()) ? l.sInfo : l.sInfoEmpty),
                s !== a && (o += " " + l.sInfoFiltered),
                (o = eA(t, (o += l.sInfoPostFix))),
                null !== (l = l.fnInfoCallback) && (o = l.call(t.oInstance, t, n, i, a, s, o)),
                e(u).html(o));
        }
        function eA(e, t) {
            var n = e.fnFormatNumber,
                i = e._iDisplayStart + 1,
                a = e._iDisplayLength,
                s = e.fnRecordsDisplay(),
                o = -1 === a;
            return t
                .replace(/_START_/g, n.call(e, i))
                .replace(/_END_/g, n.call(e, e.fnDisplayEnd()))
                .replace(/_MAX_/g, n.call(e, e.fnRecordsTotal()))
                .replace(/_TOTAL_/g, n.call(e, s))
                .replace(/_PAGE_/g, n.call(e, o ? 1 : Math.ceil(i / a)))
                .replace(/_PAGES_/g, n.call(e, o ? 1 : Math.ceil(s / a)));
        }
        function eI(e) {
            var t,
                n,
                i,
                a = e.iInitDisplayStart,
                s = e.aoColumns,
                o = e.oFeatures,
                l = e.bDeferLoading;
            if (e.bInitialised) {
                for (ed(e), es(e), eo(e, e.aoHeader), eo(e, e.aoFooter), ez(e, !0), o.bAutoWidth && e1(e), t = 0, n = s.length; t < n; t++) (i = s[t]).sWidth && (i.nTh.style.width = eG(i.sWidth));
                ti(e, null, "preInit", [e]),
                    eu(e),
                    ("ssp" != (o = ts(e)) || l) &&
                        ("ajax" == o
                            ? ef(e, [], function (n) {
                                  var i = ev(e, n);
                                  for (t = 0; t < i.length; t++) W(e, i[t]);
                                  (e.iInitDisplayStart = a), eu(e), ez(e, !1), eP(e, n);
                              })
                            : (ez(e, !1), eP(e)));
            } else
                setTimeout(function () {
                    eI(e);
                }, 200);
        }
        function eP(e, t) {
            (e._bInitComplete = !0), (t || e.oInit.aaData) && R(e), ti(e, null, "plugin-init", [e, t]), ti(e, "aoInitComplete", "init", [e, t]);
        }
        function e0(e, t) {
            (t = parseInt(t, 10)), (e._iDisplayLength = t), tr(e), ti(e, null, "length", [e, t]);
        }
        function eN(t) {
            for (
                var n = t.oClasses,
                    i = t.sTableId,
                    a = t.aLengthMenu,
                    s = Array.isArray(a[0]),
                    o = s ? a[0] : a,
                    l = s ? a[1] : a,
                    u = e("<select/>", { name: i + "_length", "aria-controls": i, class: n.sLengthSelect }),
                    d = 0,
                    c = o.length;
                d < c;
                d++
            )
                u[0][d] = new Option("number" == typeof l[d] ? t.fnFormatNumber(l[d]) : l[d], o[d]);
            var p = e("<div><label/></div>").addClass(n.sLength);
            return (
                t.aanFeatures.l || (p[0].id = i + "_length"),
                p.children().append(t.oLanguage.sLengthMenu.replace("_MENU_", u[0].outerHTML)),
                e("select", p)
                    .val(t._iDisplayLength)
                    .on("change.DT", function (n) {
                        e0(t, e(this).val()), el(t);
                    }),
                e(t.nTable).on("length.dt.DT", function (n, i, a) {
                    t === i && e("select", p).val(a);
                }),
                p[0]
            );
        }
        function eM(t) {
            function n(e) {
                el(e);
            }
            var i = t.sPaginationType,
                a = m.ext.pager[i],
                s = "function" == typeof a,
                o = ((i = e("<div/>").addClass(t.oClasses.sPaging + i)[0]), t.aanFeatures);
            return (
                s || a.fnInit(t, i, n),
                o.p ||
                    ((i.id = t.sTableId + "_paginate"),
                    t.aoDrawCallback.push({
                        fn: function (e) {
                            if (s)
                                for (var t = e._iDisplayStart, i = e._iDisplayLength, l = e.fnRecordsDisplay(), u = -1 === i, d = u ? 0 : Math.ceil(t / i), c = u ? 1 : Math.ceil(l / i), p = a(d, c), f = 0, h = o.p.length; f < h; f++)
                                    ta(e, "pageButton")(e, o.p[f], f, p, d, c);
                            else a.fnUpdate(e, n);
                        },
                        sName: "pagination",
                    })),
                i
            );
        }
        function eO(e, t, n) {
            var i = e._iDisplayStart,
                a = e._iDisplayLength,
                s =
                    (0 === (s = e.fnRecordsDisplay()) || -1 === a
                        ? (i = 0)
                        : "number" == typeof t
                        ? s < (i = t * a) && (i = 0)
                        : "first" == t
                        ? (i = 0)
                        : "previous" == t
                        ? (i = 0 <= a ? i - a : 0) < 0 && (i = 0)
                        : "next" == t
                        ? i + a < s && (i += a)
                        : "last" == t
                        ? (i = Math.floor((s - 1) / a) * a)
                        : eZ(e, 0, "Unknown paging action: " + t, 5),
                    e._iDisplayStart !== i);
            return (e._iDisplayStart = i), s ? (ti(e, null, "page", [e]), n && el(e)) : ti(e, null, "page-nc", [e]), s;
        }
        function eR(t) {
            return e("<div/>", { id: t.aanFeatures.r ? null : t.sTableId + "_processing", class: t.oClasses.sProcessing, role: "status" })
                .html(t.oLanguage.sProcessing)
                .append("<div><div></div><div></div><div></div><div></div></div>")
                .insertBefore(t.nTable)[0];
        }
        function ez(t, n) {
            t.oFeatures.bProcessing && e(t.aanFeatures.r).css("display", n ? "block" : "none"), ti(t, null, "processing", [t, n]);
        }
        function e9(t) {
            var n,
                i,
                a,
                s,
                o,
                l,
                u,
                d,
                c,
                p,
                f,
                h,
                m = e(t.nTable),
                g = t.oScroll;
            return "" === g.sX && "" === g.sY
                ? t.nTable
                : ((n = g.sX),
                  (i = g.sY),
                  (a = t.oClasses),
                  (o = (s = m.children("caption")).length ? s[0]._captionSide : null),
                  (d = e(m[0].cloneNode(!1))),
                  (l = e(m[0].cloneNode(!1))),
                  (c = function (e) {
                      return e ? eG(e) : null;
                  }),
                  (u = m.children("tfoot")).length || (u = null),
                  (d = e((f = "<div/>"), { class: a.sScrollWrapper })
                      .append(
                          e(f, { class: a.sScrollHead })
                              .css({ overflow: "hidden", position: "relative", border: 0, width: n ? c(n) : "100%" })
                              .append(
                                  e(f, { class: a.sScrollHeadInner })
                                      .css({ "box-sizing": "content-box", width: g.sXInner || "100%" })
                                      .append(
                                          d
                                              .removeAttr("id")
                                              .css("margin-left", 0)
                                              .append("top" === o ? s : null)
                                              .append(m.children("thead"))
                                      )
                              )
                      )
                      .append(
                          e(f, { class: a.sScrollBody })
                              .css({ position: "relative", overflow: "auto", width: c(n) })
                              .append(m)
                      )),
                  u &&
                      d.append(
                          e(f, { class: a.sScrollFoot })
                              .css({ overflow: "hidden", border: 0, width: n ? c(n) : "100%" })
                              .append(
                                  e(f, { class: a.sScrollFootInner }).append(
                                      l
                                          .removeAttr("id")
                                          .css("margin-left", 0)
                                          .append("bottom" === o ? s : null)
                                          .append(m.children("tfoot"))
                                  )
                              )
                      ),
                  (p = (c = d.children())[0]),
                  (f = c[1]),
                  (h = u ? c[2] : null),
                  n &&
                      e(f).on("scroll.DT", function (e) {
                          var t = this.scrollLeft;
                          (p.scrollLeft = t), u && (h.scrollLeft = t);
                      }),
                  e(f).css("max-height", i),
                  g.bCollapse || e(f).css("height", i),
                  (t.nScrollHead = p),
                  (t.nScrollBody = f),
                  (t.nScrollFoot = h),
                  t.aoDrawCallback.push({ fn: eF, sName: "scrolling" }),
                  d[0]);
        }
        function eF(n) {
            function a(e) {
                ((e = e.style).paddingTop = "0"), (e.paddingBottom = "0"), (e.borderTopWidth = "0"), (e.borderBottomWidth = "0"), (e.height = 0);
            }
            var s,
                o,
                l,
                u,
                d,
                c = (h = n.oScroll).sX,
                p = h.sXInner,
                f = h.sY,
                h = h.iBarWidth,
                m = e(n.nScrollHead),
                g = m[0].style,
                v = ($ = m.children("div"))[0].style,
                $ = $.children("table"),
                y = n.nScrollBody,
                b = e(y),
                _ = y.style,
                w = e(n.nScrollFoot).children("div"),
                x = w.children("table"),
                T = e(n.nTHead),
                C = e(n.nTable),
                E = C[0],
                D = E.style,
                k = n.nTFoot ? e(n.nTFoot) : null,
                L = n.oBrowser,
                A = L.bScrollOversize,
                I = (S(n.aoColumns, "nTh"), []),
                P = [],
                N = [],
                M = [],
                O = y.scrollHeight > y.clientHeight;
            n.scrollBarVis !== O && n.scrollBarVis !== i
                ? ((n.scrollBarVis = O), R(n))
                : ((n.scrollBarVis = O),
                  C.children("thead, tfoot").remove(),
                  k && ((O = k.clone().prependTo(C)), (d = k.find("tr")), (o = O.find("tr")), O.find("[id]").removeAttr("id")),
                  (O = T.clone().prependTo(C)),
                  (T = T.find("tr")),
                  (s = O.find("tr")),
                  O.find("th, td").removeAttr("tabindex"),
                  O.find("[id]").removeAttr("id"),
                  c || ((_.width = "100%"), (m[0].style.width = "100%")),
                  e.each(ep(n, O), function (e, t) {
                      (l = z(n, e)), (t.style.width = n.aoColumns[l].sWidth);
                  }),
                  k &&
                      ej(function (e) {
                          e.style.width = "";
                      }, o),
                  (m = C.outerWidth()),
                  "" === c
                      ? ((D.width = "100%"), A && (C.find("tbody").height() > y.offsetHeight || "scroll" == b.css("overflow-y")) && (D.width = eG(C.outerWidth() - h)), (m = C.outerWidth()))
                      : "" !== p && ((D.width = eG(p)), (m = C.outerWidth())),
                  ej(a, s),
                  ej(function (n) {
                      var i = t.getComputedStyle ? t.getComputedStyle(n).width : eG(e(n).width());
                      N.push(n.innerHTML), I.push(i);
                  }, s),
                  ej(function (e, t) {
                      e.style.width = I[t];
                  }, T),
                  e(s).css("height", 0),
                  k &&
                      (ej(a, o),
                      ej(function (t) {
                          M.push(t.innerHTML), P.push(eG(e(t).css("width")));
                      }, o),
                      ej(function (e, t) {
                          e.style.width = P[t];
                      }, d),
                      e(o).height(0)),
                  ej(function (e, t) {
                      (e.innerHTML = '<div class="dataTables_sizing">' + N[t] + "</div>"), (e.childNodes[0].style.height = "0"), (e.childNodes[0].style.overflow = "hidden"), (e.style.width = I[t]);
                  }, s),
                  k &&
                      ej(function (e, t) {
                          (e.innerHTML = '<div class="dataTables_sizing">' + M[t] + "</div>"), (e.childNodes[0].style.height = "0"), (e.childNodes[0].style.overflow = "hidden"), (e.style.width = P[t]);
                      }, o),
                  Math.round(C.outerWidth()) < Math.round(m)
                      ? ((u = y.scrollHeight > y.offsetHeight || "scroll" == b.css("overflow-y") ? m + h : m),
                        A && (y.scrollHeight > y.offsetHeight || "scroll" == b.css("overflow-y")) && (D.width = eG(u - h)),
                        ("" !== c && "" === p) || eZ(n, 1, "Possible column misalignment", 6))
                      : (u = "100%"),
                  (_.width = eG(u)),
                  (g.width = eG(u)),
                  k && (n.nScrollFoot.style.width = eG(u)),
                  f || (A && (_.height = eG(E.offsetHeight + h))),
                  (O = C.outerWidth()),
                  ($[0].style.width = eG(O)),
                  (v.width = eG(O)),
                  (T = C.height() > y.clientHeight || "scroll" == b.css("overflow-y")),
                  (v[(d = "padding" + (L.bScrollbarLeft ? "Left" : "Right"))] = T ? h + "px" : "0px"),
                  k && ((x[0].style.width = eG(O)), (w[0].style.width = eG(O)), (w[0].style[d] = T ? h + "px" : "0px")),
                  C.children("colgroup").insertBefore(C.children("thead")),
                  b.trigger("scroll"),
                  (n.bSorted || n.bFiltered) && !n._drawHold && (y.scrollTop = 0));
        }
        function ej(e, t, n) {
            for (var i, a, s = 0, o = 0, l = t.length; o < l; ) {
                for (i = t[o].firstChild, a = n ? n[o].firstChild : null; i; ) 1 === i.nodeType && (n ? e(i, a, s) : e(i, s), s++), (i = i.nextSibling), (a = n ? a.nextSibling : null);
                o++;
            }
        }
        var eH = /<.*?>/g;
        function e1(n) {
            var i,
                a,
                s = n.nTable,
                o = n.aoColumns,
                l = (d = n.oScroll).sY,
                u = d.sX,
                d = d.sXInner,
                c = o.length,
                p = H(n, "bVisible"),
                f = e("th", n.nTHead),
                h = s.getAttribute("width"),
                m = s.parentNode,
                g = !1,
                v = n.oBrowser,
                $ = v.bScrollOversize;
            for ((y = s.style.width) && -1 !== y.indexOf("%") && (h = y), x = 0; x < p.length; x++) null !== (i = o[p[x]]).sWidth && ((i.sWidth = eq(i.sWidthOrig, m)), (g = !0));
            if (!$ && (g || u || l || c != j(n) || c != f.length)) {
                var y,
                    b = ((y = e(s).clone().css("visibility", "hidden").removeAttr("id")).find("tbody tr").remove(), e("<tr/>").appendTo(y.find("tbody")));
                for (y.find("thead, tfoot").remove(), y.append(e(n.nTHead).clone()).append(e(n.nTFoot).clone()), y.find("tfoot th, tfoot td").css("width", ""), f = ep(n, y.find("thead")[0]), x = 0; x < p.length; x++)
                    (i = o[p[x]]),
                        (f[x].style.width = null !== i.sWidthOrig && "" !== i.sWidthOrig ? eG(i.sWidthOrig) : ""),
                        i.sWidthOrig && u && e(f[x]).append(e("<div/>").css({ width: i.sWidthOrig, margin: 0, padding: 0, border: 0, height: 1 }));
                if (n.aoData.length) for (x = 0; x < p.length; x++) (i = o[(a = p[x])]), e(e3(n, a)).clone(!1).append(i.sContentPadding).appendTo(b);
                e("[name]", y).removeAttr("name");
                for (
                    var _ = e("<div/>")
                            .css(u || l ? { position: "absolute", top: 0, left: 0, height: 1, right: 0, overflow: "hidden" } : {})
                            .append(y)
                            .appendTo(m),
                        w = (u && d ? y.width(d) : u ? (y.css("width", "auto"), y.removeAttr("width"), y.width() < m.clientWidth && h && y.width(m.clientWidth)) : l ? y.width(m.clientWidth) : h && y.width(h), 0),
                        x = 0;
                    x < p.length;
                    x++
                ) {
                    var S,
                        T = (S = e(f[x])).outerWidth() - S.width();
                    (w += S = v.bBounding ? Math.ceil(f[x].getBoundingClientRect().width) : S.outerWidth()), (o[p[x]].sWidth = eG(S - T));
                }
                (s.style.width = eG(w)), _.remove();
            } else
                for (x = 0; x < c; x++) {
                    var C = z(n, x);
                    null !== C && (o[C].sWidth = eG(f.eq(x).width()));
                }
            h && (s.style.width = eG(h)),
                (h || u) &&
                    !n._reszEvt &&
                    ((d = function () {
                        e(t).on(
                            "resize.DT-" + n.sInstance,
                            eB(function () {
                                R(n);
                            })
                        );
                    }),
                    $ ? setTimeout(d, 1e3) : d(),
                    (n._reszEvt = !0));
        }
        var eB = m.util.throttle;
        function eq(t, i) {
            return t
                ? ((i = (t = e("<div/>")
                      .css("width", eG(t))
                      .appendTo(i || n.body))[0].offsetWidth),
                  t.remove(),
                  i)
                : 0;
        }
        function e3(t, n) {
            var i,
                a = eW(t, n);
            return a < 0 ? null : (i = t.aoData[a]).nTr ? i.anCells[n] : e("<td/>").html(U(t, a, n, "display"))[0];
        }
        function eW(e, t) {
            for (var n, i = -1, a = -1, s = 0, o = e.aoData.length; s < o; s++) (n = (n = (n = U(e, s, t, "display") + "").replace(eH, "")).replace(/&nbsp;/g, " ")).length > i && ((i = n.length), (a = s));
            return a;
        }
        function eG(e) {
            return null === e ? "0px" : "number" == typeof e ? (e < 0 ? "0px" : e + "px") : e.match(/\d$/) ? e + "px" : e;
        }
        function e2(t) {
            function n(t) {
                t.length && !Array.isArray(t[0]) ? v.push(t) : e.merge(v, t);
            }
            var a,
                s,
                o,
                l,
                u,
                d,
                c,
                p = [],
                f = t.aoColumns,
                h = t.aaSortingFixed,
                g = e.isPlainObject(h),
                v = [];
            for (Array.isArray(h) && n(h), g && h.pre && n(h.pre), n(t.aaSorting), g && h.post && n(h.post), a = 0; a < v.length; a++)
                for (o = (l = f[(c = v[a][(s = 0)])].aDataSort).length; s < o; s++)
                    (d = f[(u = l[s])].sType || "string"), v[a]._idx === i && (v[a]._idx = e.inArray(v[a][1], f[u].asSorting)), p.push({ src: c, col: u, dir: v[a][1], index: v[a]._idx, type: d, formatter: m.ext.type.order[d + "-pre"] });
            return p;
        }
        function e4(e) {
            var t,
                n,
                i,
                a,
                s,
                o = [],
                l = m.ext.type.order,
                u = e.aoData,
                d = (e.aoColumns, 0),
                c = e.aiDisplayMaster;
            for (B(e), t = 0, n = (s = e2(e)).length; t < n; t++) (a = s[t]).formatter && d++, e7(e, a.col);
            if ("ssp" != ts(e) && 0 !== s.length) {
                for (t = 0, i = c.length; t < i; t++) o[c[t]] = t;
                d === s.length
                    ? c.sort(function (e, t) {
                          for (var n, i, a, l, d = s.length, c = u[e]._aSortData, p = u[t]._aSortData, f = 0; f < d; f++) if (0 != (a = (n = c[(l = s[f]).col]) < (i = p[l.col]) ? -1 : i < n ? 1 : 0)) return "asc" === l.dir ? a : -a;
                          return (n = o[e]) < (i = o[t]) ? -1 : i < n ? 1 : 0;
                      })
                    : c.sort(function (e, t) {
                          for (var n, i, a, d = s.length, c = u[e]._aSortData, p = u[t]._aSortData, f = 0; f < d; f++)
                              if (((n = c[(a = s[f]).col]), (i = p[a.col]), 0 !== (a = (l[a.type + "-" + a.dir] || l["string-" + a.dir])(n, i)))) return a;
                          return (n = o[e]) < (i = o[t]) ? -1 : i < n ? 1 : 0;
                      });
            }
            e.bSorted = !0;
        }
        function e6(e) {
            for (var t = e.aoColumns, n = e2(e), i = e.oLanguage.oAria, a = 0, s = t.length; a < s; a++) {
                var o = t[a],
                    l = o.asSorting,
                    u = o.ariaTitle || o.sTitle.replace(/<.*?>/g, ""),
                    d = o.nTh;
                d.removeAttribute("aria-sort"),
                    (o = o.bSortable
                        ? u + ("asc" === ((0 < n.length && n[0].col == a && (d.setAttribute("aria-sort", "asc" == n[0].dir ? "ascending" : "descending"), l[n[0].index + 1])) || l[0]) ? i.sSortAscending : i.sSortDescending)
                        : u),
                    d.setAttribute("aria-label", o);
            }
        }
        function eU(t, n, a, s) {
            function o(t, n) {
                var a = t._idx;
                return (a = a === i ? e.inArray(t[1], c) : a) + 1 < c.length ? a + 1 : n ? null : 0;
            }
            var l,
                u = t.aoColumns[n],
                d = t.aaSorting,
                c = u.asSorting;
            "number" == typeof d[0] && (d = t.aaSorting = [d]),
                a && t.oFeatures.bSortMulti
                    ? -1 !== (u = e.inArray(n, S(d, "0")))
                        ? null === (l = null === (l = o(d[u], !0)) && 1 === d.length ? 0 : l)
                            ? d.splice(u, 1)
                            : ((d[u][1] = c[l]), (d[u]._idx = l))
                        : (d.push([n, c[0], 0]), (d[d.length - 1]._idx = 0))
                    : d.length && d[0][0] == n
                    ? ((l = o(d[0])), (d.length = 1), (d[0][1] = c[l]), (d[0]._idx = l))
                    : ((d.length = 0), d.push([n, c[0]]), (d[0]._idx = 0)),
                eu(t),
                "function" == typeof s && s(t);
        }
        function eV(e, t, n, i) {
            var a = e.aoColumns[n];
            tt(t, {}, function (t) {
                !1 !== a.bSortable &&
                    (e.oFeatures.bProcessing
                        ? (ez(e, !0),
                          setTimeout(function () {
                              eU(e, n, t.shiftKey, i), "ssp" !== ts(e) && ez(e, !1);
                          }, 0))
                        : eU(e, n, t.shiftKey, i));
            });
        }
        function eX(t) {
            var n,
                i,
                a,
                s = t.aLastSort,
                o = t.oClasses.sSortColumn,
                l = e2(t),
                u = t.oFeatures;
            if (u.bSort && u.bSortClasses) {
                for (n = 0, i = s.length; n < i; n++) (a = s[n].src), e(S(t.aoData, "anCells", a)).removeClass(o + (n < 2 ? n + 1 : 3));
                for (n = 0, i = l.length; n < i; n++) (a = l[n].src), e(S(t.aoData, "anCells", a)).addClass(o + (n < 2 ? n + 1 : 3));
            }
            t.aLastSort = l;
        }
        function e7(e, t) {
            for (var n, i, a, s = e.aoColumns[t], o = m.ext.order[s.sSortDataType], l = (o && (n = o.call(e.oInstance, e, t, F(e, t))), m.ext.type.order[s.sType + "-pre"]), u = 0, d = e.aoData.length; u < d; u++)
                (i = e.aoData[u])._aSortData || (i._aSortData = []), (i._aSortData[t] && !o) || ((a = o ? n[u] : U(e, u, t, "sort")), (i._aSortData[t] = l ? l(a) : a));
        }
        function eY(t) {
            var n;
            t._bLoadingState ||
                ((n = {
                    time: +new Date(),
                    start: t._iDisplayStart,
                    length: t._iDisplayLength,
                    order: e.extend(!0, [], t.aaSorting),
                    search: e8(t.oPreviousSearch),
                    columns: e.map(t.aoColumns, function (e, n) {
                        return { visible: e.bVisible, search: e8(t.aoPreSearchCols[n]) };
                    }),
                }),
                (t.oSavedState = n),
                ti(t, "aoStateSaveParams", "stateSaveParams", [t, n]),
                t.oFeatures.bStateSave && !t.bDestroying && t.fnStateSaveCallback.call(t.oInstance, t, n));
        }
        function e5(e, t, n) {
            var a;
            if (e.oFeatures.bStateSave)
                return (
                    (a = e.fnStateLoadCallback.call(e.oInstance, e, function (t) {
                        eJ(e, t, n);
                    })) !== i && eJ(e, a, n),
                    !0
                );
            n();
        }
        function eJ(t, n, a) {
            var s,
                o,
                l = t.aoColumns,
                u = ((t._bLoadingState = !0), t._bInitComplete ? new m.Api(t) : null);
            if (n && n.time) {
                var d = ti(t, "aoStateLoadParams", "stateLoadParams", [t, n]);
                if (-1 !== e.inArray(!1, d)) t._bLoadingState = !1;
                else if (0 < (d = t.iStateDuration) && n.time < +new Date() - 1e3 * d) t._bLoadingState = !1;
                else if (n.columns && l.length !== n.columns.length) t._bLoadingState = !1;
                else {
                    if (
                        ((t.oLoadedState = e.extend(!0, {}, n)),
                        n.length !== i && (u ? u.page.len(n.length) : (t._iDisplayLength = n.length)),
                        n.start !== i && (null === u ? ((t._iDisplayStart = n.start), (t.iInitDisplayStart = n.start)) : eO(t, n.start / t._iDisplayLength)),
                        n.order !== i &&
                            ((t.aaSorting = []),
                            e.each(n.order, function (e, n) {
                                t.aaSorting.push(n[0] >= l.length ? [0, n[1]] : n);
                            })),
                        n.search !== i && e.extend(t.oPreviousSearch, eD(n.search)),
                        n.columns)
                    ) {
                        for (s = 0, o = n.columns.length; s < o; s++) {
                            var c = n.columns[s];
                            c.visible !== i && (u ? u.column(s).visible(c.visible, !1) : (l[s].bVisible = c.visible)), c.search !== i && e.extend(t.aoPreSearchCols[s], eD(c.search));
                        }
                        u && u.columns.adjust();
                    }
                    (t._bLoadingState = !1), ti(t, "aoStateLoaded", "stateLoaded", [t, n]);
                }
            } else t._bLoadingState = !1;
            a();
        }
        function eK(t) {
            var n = m.settings;
            return -1 !== (t = e.inArray(t, S(n, "nTable"))) ? n[t] : null;
        }
        function eZ(e, n, i, a) {
            if (((i = "DataTables warning: " + (e ? "table id=" + e.sTableId + " - " : "") + i), a && (i += ". For more information about this error, please see http://datatables.net/tn/" + a), n))
                t.console && console.log && console.log(i);
            else if (((n = (n = m.ext).sErrMode || n.errMode), e && ti(e, null, "error", [e, a, i]), "alert" == n)) alert(i);
            else {
                if ("throw" == n) throw Error(i);
                "function" == typeof n && n(e, a, i);
            }
        }
        function eQ(t, n, a, s) {
            Array.isArray(a)
                ? e.each(a, function (e, i) {
                      Array.isArray(i) ? eQ(t, n, i[0], i[1]) : eQ(t, n, i);
                  })
                : (s === i && (s = a), n[a] !== i && (t[s] = n[a]));
        }
        function te(t, n, i) {
            var a, s;
            for (s in n) n.hasOwnProperty(s) && ((a = n[s]), e.isPlainObject(a) ? (e.isPlainObject(t[s]) || (t[s] = {}), e.extend(!0, t[s], a)) : i && "data" !== s && "aaData" !== s && Array.isArray(a) ? (t[s] = a.slice()) : (t[s] = a));
            return t;
        }
        function tt(t, n, i) {
            e(t)
                .on("click.DT", n, function (n) {
                    e(t).trigger("blur"), i(n);
                })
                .on("keypress.DT", n, function (e) {
                    13 === e.which && (e.preventDefault(), i(e));
                })
                .on("selectstart.DT", function () {
                    return !1;
                });
        }
        function tn(e, t, n, i) {
            n && e[t].push({ fn: n, sName: i });
        }
        function ti(t, n, i, a) {
            var s = [];
            return (
                n &&
                    (s = e.map(t[n].slice().reverse(), function (e, n) {
                        return e.fn.apply(t.oInstance, a);
                    })),
                null !== i && ((n = e.Event(i + ".dt")), (i = e(t.nTable)).trigger(n, a), 0 === i.parents("body").length && e("body").trigger(n, a), s.push(n.result)),
                s
            );
        }
        function tr(e) {
            var t = e._iDisplayStart,
                n = e.fnDisplayEnd(),
                i = e._iDisplayLength;
            n <= t && (t = n - i), (t -= t % i), (e._iDisplayStart = t = -1 === i || t < 0 ? 0 : t);
        }
        function ta(t, n) {
            t = t.renderer;
            var i = m.ext.renderer[n];
            return e.isPlainObject(t) && t[n] ? i[t[n]] || i._ : ("string" == typeof t && i[t]) || i._;
        }
        function ts(e) {
            return e.oFeatures.bServerSide ? "ssp" : e.ajax || e.sAjaxSource ? "ajax" : "dom";
        }
        function to(e, t, n) {
            var i, a;
            n &&
                (i = new tm(e)).one("draw", function () {
                    n(i.ajax.json());
                }),
                "ssp" == ts(e)
                    ? eu(e, t)
                    : (ez(e, !0),
                      (a = e.jqXHR) && 4 !== a.readyState && a.abort(),
                      ef(e, [], function (n) {
                          ee(e);
                          for (var i = ev(e, n), a = 0, s = i.length; a < s; a++) W(e, i[a]);
                          eu(e, t), ez(e, !1);
                      }));
        }
        function tl(e, t, n, a, s) {
            for (var o, l, u, d, c = [], f = typeof t, h = 0, m = (t = t && "string" != f && "function" != f && t.length !== i ? t : [t]).length; h < m; h++)
                for (u = 0, d = (l = t[h] && t[h].split && !t[h].match(/[\[\(:]/) ? t[h].split(",") : [t[h]]).length; u < d; u++) (o = n("string" == typeof l[u] ? l[u].trim() : l[u])) && o.length && (c = c.concat(o));
            var g = p.selector[e];
            if (g.length) for (h = 0, m = g.length; h < m; h++) c = g[h](a, s, c);
            return T(c);
        }
        function tu(t) {
            return (t = t || {}).filter && t.search === i && (t.search = t.filter), e.extend({ search: "none", order: "current", page: "all" }, t);
        }
        function td(e) {
            for (var t = 0, n = e.length; t < n; t++) if (0 < e[t].length) return (e[0] = e[t]), (e[0].length = 1), (e.length = 1), (e.context = [e.context[t]]), e;
            return (e.length = 0), e;
        }
        function tc(t, n) {
            var i = t.context;
            if (i.length && t.length && (o = i[0].aoData[t[0]])._details) {
                (o._detailsShow = n) ? (o._details.insertAfter(o.nTr), e(o.nTr).addClass("dt-hasChild")) : (o._details.detach(), e(o.nTr).removeClass("dt-hasChild")), ti(i[0], null, "childRow", [n, t.row(t[0])]);
                var a = i[0],
                    s = new tm(a),
                    o = ((n = "draw" + (o = ".dt.DT_details")), (t = "column-sizing" + o), "destroy" + o),
                    l = a.aoData;
                s.off(n + " " + t + " " + o),
                    S(l, "_details").length > 0 &&
                        (s.on(n, function (e, t) {
                            a === t &&
                                s
                                    .rows({ page: "current" })
                                    .eq(0)
                                    .each(function (e) {
                                        var t = l[e];
                                        t._detailsShow && t._details.insertAfter(t.nTr);
                                    });
                        }),
                        s.on(t, function (e, t, n, i) {
                            if (a === t) for (var s, o = j(t), u = 0, d = l.length; u < d; u++) (s = l[u])._details && s._details.children("td[colspan]").attr("colspan", o);
                        }),
                        s.on(o, function (e, t) {
                            if (a === t) for (var n = 0, i = l.length; n < i; n++) l[n]._details && t$(s, n);
                        })),
                    tv(i);
            }
        }
        function tp(e, t, n, i, a) {
            for (var s = [], o = 0, l = a.length; o < l; o++) s.push(U(e, a[o], t));
            return s;
        }
        var tf = [],
            th = Array.prototype,
            tm = function (t, n) {
                if (!(this instanceof tm)) return new tm(t, n);
                function i(t) {
                    var n, i, s, o;
                    (s = m.settings),
                        (o = e.map(s, function (e, t) {
                            return e.nTable;
                        })),
                        (t = t
                            ? t.nTable && t.oApi
                                ? [t]
                                : t.nodeName && "table" === t.nodeName.toLowerCase()
                                ? -1 !== (n = e.inArray(t, o))
                                    ? [s[n]]
                                    : null
                                : t && "function" == typeof t.settings
                                ? t.settings().toArray()
                                : ("string" == typeof t ? (i = e(t)) : t instanceof e && (i = t),
                                  i
                                      ? i
                                            .map(function (t) {
                                                return -1 !== (n = e.inArray(this, o)) ? s[n] : null;
                                            })
                                            .toArray()
                                      : void 0)
                            : []) && a.push.apply(a, t);
                }
                var a = [];
                if (Array.isArray(t)) for (var s = 0, o = t.length; s < o; s++) i(t[s]);
                else i(t);
                (this.context = T(a)), n && e.merge(this, n), (this.selector = { rows: null, cols: null, opts: null }), tm.extend(this, this, tf);
            },
            tg =
                ((m.Api = tm),
                e.extend(tm.prototype, {
                    any: function () {
                        return 0 !== this.count();
                    },
                    concat: th.concat,
                    context: [],
                    count: function () {
                        return this.flatten().length;
                    },
                    each: function (e) {
                        for (var t = 0, n = this.length; t < n; t++) e.call(this, this[t], t, this);
                        return this;
                    },
                    eq: function (e) {
                        var t = this.context;
                        return t.length > e ? new tm(t[e], this[e]) : null;
                    },
                    filter: function (e) {
                        var t = [];
                        if (th.filter) t = th.filter.call(this, e, this);
                        else for (var n = 0, i = this.length; n < i; n++) e.call(this, this[n], n, this) && t.push(this[n]);
                        return new tm(this.context, t);
                    },
                    flatten: function () {
                        var e = [];
                        return new tm(this.context, e.concat.apply(e, this.toArray()));
                    },
                    join: th.join,
                    indexOf:
                        th.indexOf ||
                        function (e, t) {
                            for (var n = t || 0, i = this.length; n < i; n++) if (this[n] === e) return n;
                            return -1;
                        },
                    iterator: function (e, t, n, a) {
                        var s,
                            o,
                            l,
                            u,
                            d,
                            c,
                            p,
                            f,
                            h = [],
                            m = this.context,
                            g = this.selector;
                        for ("string" == typeof e && ((a = n), (n = t), (t = e), (e = !1)), o = 0, l = m.length; o < l; o++) {
                            var v = new tm(m[o]);
                            if ("table" === t) (s = n.call(v, m[o], o)) !== i && h.push(s);
                            else if ("columns" === t || "rows" === t) (s = n.call(v, m[o], this[o], o)) !== i && h.push(s);
                            else if ("column" === t || "column-rows" === t || "row" === t || "cell" === t)
                                for (p = this[o], "column-rows" === t && (c = tg(m[o], g.opts)), u = 0, d = p.length; u < d; u++)
                                    (f = p[u]), (s = "cell" === t ? n.call(v, m[o], f.row, f.column, o, u) : n.call(v, m[o], f, o, u, c)) !== i && h.push(s);
                        }
                        return h.length || a ? (((e = (a = new tm(m, e ? h.concat.apply([], h) : h)).selector).rows = g.rows), (e.cols = g.cols), (e.opts = g.opts), a) : this;
                    },
                    lastIndexOf:
                        th.lastIndexOf ||
                        function (e, t) {
                            return this.indexOf.apply(this.toArray.reverse(), arguments);
                        },
                    length: 0,
                    map: function (e) {
                        var t = [];
                        if (th.map) t = th.map.call(this, e, this);
                        else for (var n = 0, i = this.length; n < i; n++) t.push(e.call(this, this[n], n));
                        return new tm(this.context, t);
                    },
                    pluck: function (e) {
                        var t = m.util.get(e);
                        return this.map(function (e) {
                            return t(e);
                        });
                    },
                    pop: th.pop,
                    push: th.push,
                    reduce:
                        th.reduce ||
                        function (e, t) {
                            return N(this, e, t, 0, this.length, 1);
                        },
                    reduceRight:
                        th.reduceRight ||
                        function (e, t) {
                            return N(this, e, t, this.length - 1, -1, -1);
                        },
                    reverse: th.reverse,
                    selector: null,
                    shift: th.shift,
                    slice: function () {
                        return new tm(this.context, this);
                    },
                    sort: th.sort,
                    splice: th.splice,
                    toArray: function () {
                        return th.slice.call(this);
                    },
                    to$: function () {
                        return e(this);
                    },
                    toJQuery: function () {
                        return e(this);
                    },
                    unique: function () {
                        return new tm(this.context, T(this));
                    },
                    unshift: th.unshift,
                }),
                (tm.extend = function (e, t, n) {
                    if (n.length && t && (t instanceof tm || t.__dt_wrapper))
                        for (var i, a = 0, s = n.length; a < s; a++)
                            (t[(i = n[a]).name] =
                                "function" === i.type
                                    ? (function (e, t, n) {
                                          return function () {
                                              var i = t.apply(e, arguments);
                                              return tm.extend(i, i, n.methodExt), i;
                                          };
                                      })(e, i.val, i)
                                    : "object" === i.type
                                    ? {}
                                    : i.val),
                                (t[i.name].__dt_wrapper = !0),
                                tm.extend(e, t[i.name], i.propExt);
                }),
                (tm.register = f = function (t, n) {
                    if (Array.isArray(t)) for (var i = 0, a = t.length; i < a; i++) tm.register(t[i], n);
                    else
                        for (var s = t.split("."), o = tf, l = 0, u = s.length; l < u; l++) {
                            var d,
                                c,
                                p = (function (e, t) {
                                    for (var n = 0, i = e.length; n < i; n++) if (e[n].name === t) return e[n];
                                    return null;
                                })(o, (c = (d = -1 !== s[l].indexOf("()")) ? s[l].replace("()", "") : s[l]));
                            p || o.push((p = { name: c, val: {}, methodExt: [], propExt: [], type: "object" })),
                                l === u - 1 ? ((p.val = n), (p.type = "function" == typeof n ? "function" : e.isPlainObject(n) ? "object" : "other")) : (o = d ? p.methodExt : p.propExt);
                        }
                }),
                (tm.registerPlural = h = function (e, t, n) {
                    tm.register(e, n),
                        tm.register(t, function () {
                            var e = n.apply(this, arguments);
                            return e === this ? this : e instanceof tm ? (e.length ? (Array.isArray(e[0]) ? new tm(e.context, e[0]) : e[0]) : i) : e;
                        });
                }),
                f("tables()", function (t) {
                    return t !== i && null !== t
                        ? new tm(
                              (function t(n, i) {
                                  var a;
                                  return Array.isArray(n)
                                      ? e.map(n, function (e) {
                                            return t(e, i);
                                        })
                                      : "number" == typeof n
                                      ? [i[n]]
                                      : ((a = e.map(i, function (e, t) {
                                            return e.nTable;
                                        })),
                                        e(a)
                                            .filter(n)
                                            .map(function (t) {
                                                return i[e.inArray(this, a)];
                                            })
                                            .toArray());
                              })(t, this.context)
                          )
                        : this;
                }),
                f("table()", function (e) {
                    var t = (e = this.tables(e)).context;
                    return t.length ? new tm(t[0]) : e;
                }),
                h("tables().nodes()", "table().node()", function () {
                    return this.iterator(
                        "table",
                        function (e) {
                            return e.nTable;
                        },
                        1
                    );
                }),
                h("tables().body()", "table().body()", function () {
                    return this.iterator(
                        "table",
                        function (e) {
                            return e.nTBody;
                        },
                        1
                    );
                }),
                h("tables().header()", "table().header()", function () {
                    return this.iterator(
                        "table",
                        function (e) {
                            return e.nTHead;
                        },
                        1
                    );
                }),
                h("tables().footer()", "table().footer()", function () {
                    return this.iterator(
                        "table",
                        function (e) {
                            return e.nTFoot;
                        },
                        1
                    );
                }),
                h("tables().containers()", "table().container()", function () {
                    return this.iterator(
                        "table",
                        function (e) {
                            return e.nTableWrapper;
                        },
                        1
                    );
                }),
                f("draw()", function (e) {
                    return this.iterator("table", function (t) {
                        "page" === e ? el(t) : eu(t, !1 === (e = "string" == typeof e ? "full-hold" !== e : e));
                    });
                }),
                f("page()", function (e) {
                    return e === i
                        ? this.page.info().page
                        : this.iterator("table", function (t) {
                              eO(t, e);
                          });
                }),
                f("page.info()", function (e) {
                    var t, n, a, s, o;
                    return 0 === this.context.length
                        ? i
                        : ((n = (t = this.context[0])._iDisplayStart),
                          (a = t.oFeatures.bPaginate ? t._iDisplayLength : -1),
                          (s = t.fnRecordsDisplay()),
                          { page: (o = -1 === a) ? 0 : Math.floor(n / a), pages: o ? 1 : Math.ceil(s / a), start: n, end: t.fnDisplayEnd(), length: a, recordsTotal: t.fnRecordsTotal(), recordsDisplay: s, serverSide: "ssp" === ts(t) });
                }),
                f("page.len()", function (e) {
                    return e === i
                        ? 0 !== this.context.length
                            ? this.context[0]._iDisplayLength
                            : i
                        : this.iterator("table", function (t) {
                              e0(t, e);
                          });
                }),
                f("ajax.json()", function () {
                    var e = this.context;
                    if (0 < e.length) return e[0].json;
                }),
                f("ajax.params()", function () {
                    var e = this.context;
                    if (0 < e.length) return e[0].oAjaxData;
                }),
                f("ajax.reload()", function (e, t) {
                    return this.iterator("table", function (n) {
                        to(n, !1 === t, e);
                    });
                }),
                f("ajax.url()", function (t) {
                    var n = this.context;
                    return t === i
                        ? 0 === n.length
                            ? i
                            : (n = n[0]).ajax
                            ? e.isPlainObject(n.ajax)
                                ? n.ajax.url
                                : n.ajax
                            : n.sAjaxSource
                        : this.iterator("table", function (n) {
                              e.isPlainObject(n.ajax) ? (n.ajax.url = t) : (n.ajax = t);
                          });
                }),
                f("ajax.url().load()", function (e, t) {
                    return this.iterator("table", function (n) {
                        to(n, !1 === t, e);
                    });
                }),
                function (t, n) {
                    var i,
                        a = [],
                        s = t.aiDisplay,
                        o = t.aiDisplayMaster,
                        l = n.search,
                        d = n.order;
                    if (((n = n.page), "ssp" == ts(t))) return "removed" === l ? [] : u(0, o.length);
                    if ("current" == n) for (p = t._iDisplayStart, f = t.fnDisplayEnd(); p < f; p++) a.push(s[p]);
                    else if ("current" == d || "applied" == d) {
                        if ("none" == l) a = o.slice();
                        else if ("applied" == l) a = s.slice();
                        else if ("removed" == l) {
                            for (var c = {}, p = 0, f = s.length; p < f; p++) c[s[p]] = null;
                            a = e.map(o, function (e) {
                                return c.hasOwnProperty(e) ? null : e;
                            });
                        }
                    } else if ("index" == d || "original" == d) for (p = 0, f = t.aoData.length; p < f; p++) ("none" == l || (-1 === (i = e.inArray(p, s)) && "removed" == l) || (0 <= i && "applied" == l)) && a.push(p);
                    return a;
                }),
            tv =
                (f("rows()", function (t, n) {
                    t === i ? (t = "") : e.isPlainObject(t) && ((n = t), (t = "")), (n = tu(n));
                    var s = this.iterator(
                        "table",
                        function (s) {
                            var o, u, c;
                            return tl(
                                "row",
                                t,
                                function (t) {
                                    var n = a(t),
                                        s = o.aoData;
                                    if ((null !== n && !u) || ((c = c || tg(o, u)), null !== n && -1 !== e.inArray(n, c))) return [n];
                                    if (null === t || t === i || "" === t) return c;
                                    if ("function" == typeof t)
                                        return e.map(c, function (e) {
                                            var n = s[e];
                                            return t(e, n._aData, n.nTr) ? e : null;
                                        });
                                    if (t.nodeName)
                                        return (
                                            (n = t._DT_RowIndex),
                                            (p = t._DT_CellIndex),
                                            n !== i ? (s[n] && s[n].nTr === t ? [n] : []) : p ? (s[p.row] && s[p.row].nTr === t.parentNode ? [p.row] : []) : (n = e(t).closest("*[data-dt-row]")).length ? [n.data("dt-row")] : []
                                        );
                                    if ("string" == typeof t && "#" === t.charAt(0)) {
                                        var p = o.aIds[t.replace(/^#/, "")];
                                        if (p !== i) return [p.idx];
                                    }
                                    return (
                                        (n = d(l(o.aoData, c, "nTr"))),
                                        e(n)
                                            .filter(t)
                                            .map(function () {
                                                return this._DT_RowIndex;
                                            })
                                            .toArray()
                                    );
                                },
                                (o = s),
                                (u = n)
                            );
                        },
                        1
                    );
                    return (s.selector.rows = t), (s.selector.opts = n), s;
                }),
                f("rows().nodes()", function () {
                    return this.iterator(
                        "row",
                        function (e, t) {
                            return e.aoData[t].nTr || i;
                        },
                        1
                    );
                }),
                f("rows().data()", function () {
                    return this.iterator(
                        !0,
                        "rows",
                        function (e, t) {
                            return l(e.aoData, t, "_aData");
                        },
                        1
                    );
                }),
                h("rows().cache()", "row().cache()", function (e) {
                    return this.iterator(
                        "row",
                        function (t, n) {
                            return (t = t.aoData[n]), "search" === e ? t._aFilterData : t._aSortData;
                        },
                        1
                    );
                }),
                h("rows().invalidate()", "row().invalidate()", function (e) {
                    return this.iterator("row", function (t, n) {
                        en(t, n, e);
                    });
                }),
                h("rows().indexes()", "row().index()", function () {
                    return this.iterator(
                        "row",
                        function (e, t) {
                            return t;
                        },
                        1
                    );
                }),
                h("rows().ids()", "row().id()", function (e) {
                    for (var t = [], n = this.context, i = 0, a = n.length; i < a; i++)
                        for (var s = 0, o = this[i].length; s < o; s++) {
                            var l = n[i].rowIdFn(n[i].aoData[this[i][s]]._aData);
                            t.push((!0 === e ? "#" : "") + l);
                        }
                    return new tm(n, t);
                }),
                h("rows().remove()", "row().remove()", function () {
                    var e = this;
                    return (
                        this.iterator("row", function (t, n, a) {
                            var s,
                                o,
                                l,
                                u,
                                d,
                                c,
                                p = t.aoData,
                                f = p[n];
                            for (p.splice(n, 1), s = 0, o = p.length; s < o; s++) if (((c = (d = p[s]).anCells), null !== d.nTr && (d.nTr._DT_RowIndex = s), null !== c)) for (l = 0, u = c.length; l < u; l++) c[l]._DT_CellIndex.row = s;
                            et(t.aiDisplayMaster, n), et(t.aiDisplay, n), et(e[a], n, !1), 0 < t._iRecordsDisplay && t._iRecordsDisplay--, tr(t), (a = t.rowIdFn(f._aData)) !== i && delete t.aIds[a];
                        }),
                        this.iterator("table", function (e) {
                            for (var t = 0, n = e.aoData.length; t < n; t++) e.aoData[t].idx = t;
                        }),
                        this
                    );
                }),
                f("rows.add()", function (t) {
                    var n = this.iterator(
                            "table",
                            function (e) {
                                for (var n, i = [], a = 0, s = t.length; a < s; a++) (n = t[a]).nodeName && "TR" === n.nodeName.toUpperCase() ? i.push(G(e, n)[0]) : i.push(W(e, n));
                                return i;
                            },
                            1
                        ),
                        i = this.rows(-1);
                    return i.pop(), e.merge(i, n), i;
                }),
                f("row()", function (e, t) {
                    return td(this.rows(e, t));
                }),
                f("row().data()", function (e) {
                    var t,
                        n = this.context;
                    return e === i
                        ? n.length && this.length
                            ? n[0].aoData[this[0]]._aData
                            : i
                        : (((t = n[0].aoData[this[0]])._aData = e), Array.isArray(e) && t.nTr && t.nTr.id && Z(n[0].rowId)(e, t.nTr.id), en(n[0], this[0], "data"), this);
                }),
                f("row().node()", function () {
                    var e = this.context;
                    return (e.length && this.length && e[0].aoData[this[0]].nTr) || null;
                }),
                f("row.add()", function (t) {
                    t instanceof e && t.length && (t = t[0]);
                    var n = this.iterator("table", function (e) {
                        return t.nodeName && "TR" === t.nodeName.toUpperCase() ? G(e, t)[0] : W(e, t);
                    });
                    return this.row(n[0]);
                }),
                e(n).on("plugin-init.dt", function (t, n) {
                    var i = new tm(n),
                        a = "stateSaveParams." + (o = "on-plugin-init"),
                        s = "destroy. " + o,
                        o =
                            (i.on(a, function (e, t, n) {
                                for (var i = t.rowIdFn, a = t.aoData, s = [], o = 0; o < a.length; o++) a[o]._detailsShow && s.push("#" + i(a[o]._aData));
                                n.childRows = s;
                            }),
                            i.on(s, function () {
                                i.off(a + " " + s);
                            }),
                            i.state.loaded());
                    o &&
                        o.childRows &&
                        i
                            .rows(
                                e.map(o.childRows, function (e) {
                                    return e.replace(/:/g, "\\:");
                                })
                            )
                            .every(function () {
                                ti(n, null, "requestChild", [this]);
                            });
                }),
                m.util.throttle(function (e) {
                    eY(e[0]);
                }, 500)),
            t$ = function (t, n) {
                var a = t.context;
                a.length && (n = a[0].aoData[n !== i ? n : t[0]]) && n._details && (n._details.remove(), (n._detailsShow = i), (n._details = i), e(n.nTr).removeClass("dt-hasChild"), tv(a));
            },
            ty = "row().child",
            tb = ty + "()",
            t_ =
                (f(tb, function (t, n) {
                    var a,
                        s,
                        o,
                        l,
                        u,
                        d = this.context;
                    return t === i
                        ? d.length && this.length
                            ? d[0].aoData[this[0]]._details
                            : i
                        : (!0 === t
                              ? this.child.show()
                              : !1 === t
                              ? t$(this)
                              : d.length &&
                                this.length &&
                                ((a = d[0]),
                                (s = d[0].aoData[this[0]]),
                                (o = t),
                                (u = []),
                                (function t(n, i) {
                                    var s;
                                    if (Array.isArray(n) || n instanceof e) for (var o = 0, l = n.length; o < l; o++) t(n[o], i);
                                    else n.nodeName && "tr" === n.nodeName.toLowerCase() ? u.push(n) : ((s = e("<tr><td></td></tr>").addClass(i)), (e("td", s).addClass(i).html(n)[0].colSpan = j(a)), u.push(s[0]));
                                })(o, (l = n)),
                                s._details && s._details.detach(),
                                (s._details = e(u)),
                                s._detailsShow && s._details.insertAfter(s.nTr)),
                          this);
                }),
                f([ty + ".show()", tb + ".show()"], function (e) {
                    return tc(this, !0), this;
                }),
                f([ty + ".hide()", tb + ".hide()"], function () {
                    return tc(this, !1), this;
                }),
                f([ty + ".remove()", tb + ".remove()"], function () {
                    return t$(this), this;
                }),
                f(ty + ".isShown()", function () {
                    var e = this.context;
                    return (e.length && this.length && e[0].aoData[this[0]]._detailsShow) || !1;
                }),
                /^([^:]+):(name|visIdx|visible)$/),
            tw =
                (f("columns()", function (t, n) {
                    t === i ? (t = "") : e.isPlainObject(t) && ((n = t), (t = "")), (n = tu(n));
                    var s = this.iterator(
                        "table",
                        function (i) {
                            var s, o, l, d, c, p;
                            return (
                                (o = t),
                                (l = n),
                                (c = S((d = (s = i).aoColumns), "sName")),
                                (p = S(d, "nTh")),
                                tl(
                                    "column",
                                    o,
                                    function (t) {
                                        var n,
                                            i = a(t);
                                        if ("" === t) return u(d.length);
                                        if (null !== i) return [0 <= i ? i : d.length + i];
                                        if ("function" == typeof t)
                                            return (
                                                (n = tg(s, l)),
                                                e.map(d, function (e, i) {
                                                    return t(i, tp(s, i, 0, 0, n), p[i]) ? i : null;
                                                })
                                            );
                                        var o = "string" == typeof t ? t.match(t_) : "";
                                        if (o)
                                            switch (o[2]) {
                                                case "visIdx":
                                                case "visible":
                                                    var f,
                                                        h = parseInt(o[1], 10);
                                                    return h < 0
                                                        ? [
                                                              (f = e.map(d, function (e, t) {
                                                                  return e.bVisible ? t : null;
                                                              }))[f.length + h],
                                                          ]
                                                        : [z(s, h)];
                                                case "name":
                                                    return e.map(c, function (e, t) {
                                                        return e === o[1] ? t : null;
                                                    });
                                                default:
                                                    return [];
                                            }
                                        return t.nodeName && t._DT_CellIndex
                                            ? [t._DT_CellIndex.column]
                                            : (i = e(p)
                                                  .filter(t)
                                                  .map(function () {
                                                      return e.inArray(this, p);
                                                  })
                                                  .toArray()).length || !t.nodeName
                                            ? i
                                            : (i = e(t).closest("*[data-dt-column]")).length
                                            ? [i.data("dt-column")]
                                            : [];
                                    },
                                    s,
                                    l
                                )
                            );
                        },
                        1
                    );
                    return (s.selector.cols = t), (s.selector.opts = n), s;
                }),
                h("columns().header()", "column().header()", function (e, t) {
                    return this.iterator(
                        "column",
                        function (e, t) {
                            return e.aoColumns[t].nTh;
                        },
                        1
                    );
                }),
                h("columns().footer()", "column().footer()", function (e, t) {
                    return this.iterator(
                        "column",
                        function (e, t) {
                            return e.aoColumns[t].nTf;
                        },
                        1
                    );
                }),
                h("columns().data()", "column().data()", function () {
                    return this.iterator("column-rows", tp, 1);
                }),
                h("columns().dataSrc()", "column().dataSrc()", function () {
                    return this.iterator(
                        "column",
                        function (e, t) {
                            return e.aoColumns[t].mData;
                        },
                        1
                    );
                }),
                h("columns().cache()", "column().cache()", function (e) {
                    return this.iterator(
                        "column-rows",
                        function (t, n, i, a, s) {
                            return l(t.aoData, s, "search" === e ? "_aFilterData" : "_aSortData", n);
                        },
                        1
                    );
                }),
                h("columns().nodes()", "column().nodes()", function () {
                    return this.iterator(
                        "column-rows",
                        function (e, t, n, i, a) {
                            return l(e.aoData, a, "anCells", t);
                        },
                        1
                    );
                }),
                h("columns().visible()", "column().visible()", function (t, n) {
                    var a = this,
                        s = this.iterator("column", function (n, a) {
                            if (t === i) return n.aoColumns[a].bVisible;
                            var s,
                                o,
                                l = a,
                                u = ((a = t), n.aoColumns),
                                d = u[l],
                                c = n.aoData;
                            if (a === i) d.bVisible;
                            else if (d.bVisible !== a) {
                                if (a) for (var p = e.inArray(!0, S(u, "bVisible"), l + 1), f = 0, h = c.length; f < h; f++) (o = c[f].nTr), (s = c[f].anCells), o && o.insertBefore(s[l], s[p] || null);
                                else e(S(n.aoData, "anCells", l)).detach();
                                d.bVisible = a;
                            }
                        });
                    return (
                        t !== i &&
                            this.iterator("table", function (s) {
                                eo(s, s.aoHeader),
                                    eo(s, s.aoFooter),
                                    s.aiDisplay.length || e(s.nTBody).find("td[colspan]").attr("colspan", j(s)),
                                    eY(s),
                                    a.iterator("column", function (e, i) {
                                        ti(e, null, "column-visibility", [e, i, t, n]);
                                    }),
                                    (n === i || n) && a.columns.adjust();
                            }),
                        s
                    );
                }),
                h("columns().indexes()", "column().index()", function (e) {
                    return this.iterator(
                        "column",
                        function (t, n) {
                            return "visible" === e ? F(t, n) : n;
                        },
                        1
                    );
                }),
                f("columns.adjust()", function () {
                    return this.iterator(
                        "table",
                        function (e) {
                            R(e);
                        },
                        1
                    );
                }),
                f("column.index()", function (e, t) {
                    var n;
                    if (0 !== this.context.length) return (n = this.context[0]), "fromVisible" === e || "toData" === e ? z(n, t) : "fromData" === e || "toVisible" === e ? F(n, t) : void 0;
                }),
                f("column()", function (e, t) {
                    return td(this.columns(e, t));
                }),
                f("cells()", function (t, n, a) {
                    var s, o, u, c, p, f, h;
                    return (
                        e.isPlainObject(t) && (t.row === i ? ((a = t), (t = null)) : ((a = n), (n = null))),
                        e.isPlainObject(n) && ((a = n), (n = null)),
                        null === n || n === i
                            ? this.iterator("table", function (n) {
                                  var s, o, u, c, p, f, h, m, g, v, $, y, b, _;
                                  return (
                                      (s = n),
                                      (n = t),
                                      (o = tu(a)),
                                      (v = s.aoData),
                                      ($ = tg(s, o)),
                                      (y = d(l(v, $, "anCells"))),
                                      (b = e(C([], y))),
                                      (_ = s.aoColumns.length),
                                      tl(
                                          "cell",
                                          n,
                                          function (t) {
                                              var n,
                                                  a = "function" == typeof t;
                                              if (null === t || t === i || a) {
                                                  for (c = [], p = 0, f = $.length; p < f; p++)
                                                      for (u = $[p], h = 0; h < _; h++) (m = { row: u, column: h }), (!a || ((g = v[u]), t(m, U(s, u, h), g.anCells ? g.anCells[h] : null))) && c.push(m);
                                                  return c;
                                              }
                                              return e.isPlainObject(t)
                                                  ? t.column !== i && t.row !== i && -1 !== e.inArray(t.row, $)
                                                      ? [t]
                                                      : []
                                                  : (n = b
                                                        .filter(t)
                                                        .map(function (e, t) {
                                                            return { row: t._DT_CellIndex.row, column: t._DT_CellIndex.column };
                                                        })
                                                        .toArray()).length || !t.nodeName
                                                  ? n
                                                  : (g = e(t).closest("*[data-dt-row]")).length
                                                  ? [{ row: g.data("dt-row"), column: g.data("dt-column") }]
                                                  : [];
                                          },
                                          s,
                                          o
                                      )
                                  );
                              })
                            : ((h = a ? { page: a.page, order: a.order, search: a.search } : {}),
                              (s = this.columns(n, h)),
                              (o = this.rows(t, h)),
                              (h = this.iterator(
                                  "table",
                                  function (e, t) {
                                      var n = [];
                                      for (u = 0, c = o[t].length; u < c; u++) for (p = 0, f = s[t].length; p < f; p++) n.push({ row: o[t][u], column: s[t][p] });
                                      return n;
                                  },
                                  1
                              )),
                              (h = a && a.selected ? this.cells(h, a) : h),
                              e.extend(h.selector, { cols: n, rows: t, opts: a }),
                              h)
                    );
                }),
                h("cells().nodes()", "cell().node()", function () {
                    return this.iterator(
                        "cell",
                        function (e, t, n) {
                            return (e = e.aoData[t]) && e.anCells ? e.anCells[n] : i;
                        },
                        1
                    );
                }),
                f("cells().data()", function () {
                    return this.iterator(
                        "cell",
                        function (e, t, n) {
                            return U(e, t, n);
                        },
                        1
                    );
                }),
                h("cells().cache()", "cell().cache()", function (e) {
                    return (
                        (e = "search" === e ? "_aFilterData" : "_aSortData"),
                        this.iterator(
                            "cell",
                            function (t, n, i) {
                                return t.aoData[n][e][i];
                            },
                            1
                        )
                    );
                }),
                h("cells().render()", "cell().render()", function (e) {
                    return this.iterator(
                        "cell",
                        function (t, n, i) {
                            return U(t, n, i, e);
                        },
                        1
                    );
                }),
                h("cells().indexes()", "cell().index()", function () {
                    return this.iterator(
                        "cell",
                        function (e, t, n) {
                            return { row: t, column: n, columnVisible: F(e, n) };
                        },
                        1
                    );
                }),
                h("cells().invalidate()", "cell().invalidate()", function (e) {
                    return this.iterator("cell", function (t, n, i) {
                        en(t, n, e, i);
                    });
                }),
                f("cell()", function (e, t, n) {
                    return td(this.cells(e, t, n));
                }),
                f("cell().data()", function (e) {
                    var t = this.context,
                        n = this[0];
                    return e === i ? (t.length && n.length ? U(t[0], n[0].row, n[0].column) : i) : (V(t[0], n[0].row, n[0].column, e), en(t[0], n[0].row, "data", n[0].column), this);
                }),
                f("order()", function (e, t) {
                    var n = this.context;
                    return e === i
                        ? 0 !== n.length
                            ? n[0].aaSorting
                            : i
                        : ("number" == typeof e ? (e = [[e, t]]) : e.length && !Array.isArray(e[0]) && (e = Array.prototype.slice.call(arguments)),
                          this.iterator("table", function (t) {
                              t.aaSorting = e.slice();
                          }));
                }),
                f("order.listener()", function (e, t, n) {
                    return this.iterator("table", function (i) {
                        eV(i, e, t, n);
                    });
                }),
                f("order.fixed()", function (t) {
                    var n;
                    return t
                        ? this.iterator("table", function (n) {
                              n.aaSortingFixed = e.extend(!0, {}, t);
                          })
                        : Array.isArray((n = (n = this.context).length ? n[0].aaSortingFixed : i))
                        ? { pre: n }
                        : n;
                }),
                f(["columns().order()", "column().order()"], function (t) {
                    var n = this;
                    return this.iterator("table", function (i, a) {
                        var s = [];
                        e.each(n[a], function (e, n) {
                            s.push([n, t]);
                        }),
                            (i.aaSorting = s);
                    });
                }),
                f("search()", function (t, n, a, s) {
                    var o = this.context;
                    return t === i
                        ? 0 !== o.length
                            ? o[0].oPreviousSearch.sSearch
                            : i
                        : this.iterator("table", function (i) {
                              i.oFeatures.bFilter && ey(i, e.extend({}, i.oPreviousSearch, { sSearch: t + "", bRegex: null !== n && n, bSmart: null === a || a, bCaseInsensitive: null === s || s }), 1);
                          });
                }),
                h("columns().search()", "column().search()", function (t, n, a, s) {
                    return this.iterator("column", function (o, l) {
                        var u = o.aoPreSearchCols;
                        if (t === i) return u[l].sSearch;
                        o.oFeatures.bFilter && (e.extend(u[l], { sSearch: t + "", bRegex: null !== n && n, bSmart: null === a || a, bCaseInsensitive: null === s || s }), ey(o, o.oPreviousSearch, 1));
                    });
                }),
                f("state()", function () {
                    return this.context.length ? this.context[0].oSavedState : null;
                }),
                f("state.clear()", function () {
                    return this.iterator("table", function (e) {
                        e.fnStateSaveCallback.call(e.oInstance, e, {});
                    });
                }),
                f("state.loaded()", function () {
                    return this.context.length ? this.context[0].oLoadedState : null;
                }),
                f("state.save()", function () {
                    return this.iterator("table", function (e) {
                        eY(e);
                    });
                }),
                (m.use = function (i, a) {
                    "lib" === a || i.fn ? (e = i) : ("win" == a || i.document) && (n = (t = i).document);
                }),
                (m.factory = function (i, a) {
                    var s = !1;
                    return i && i.document && (n = (t = i).document), a && a.fn && a.fn.jquery && ((e = a), (s = !0)), s;
                }),
                (m.versionCheck = m.fnVersionCheck = function (e) {
                    for (var t, n, i = m.version.split("."), a = e.split("."), s = 0, o = a.length; s < o; s++) if ((t = parseInt(i[s], 10) || 0) !== (n = parseInt(a[s], 10) || 0)) return n < t;
                    return !0;
                }),
                (m.isDataTable = m.fnIsDataTable = function (t) {
                    var n = e(t).get(0),
                        i = !1;
                    return (
                        t instanceof m.Api ||
                        (e.each(m.settings, function (t, a) {
                            var s = a.nScrollHead ? e("table", a.nScrollHead)[0] : null,
                                o = a.nScrollFoot ? e("table", a.nScrollFoot)[0] : null;
                            (a.nTable !== n && s !== n && o !== n) || (i = !0);
                        }),
                        i)
                    );
                }),
                (m.tables = m.fnTables = function (t) {
                    var n = !1,
                        i =
                            (e.isPlainObject(t) && ((n = t.api), (t = t.visible)),
                            e.map(m.settings, function (n) {
                                if (!t || e(n.nTable).is(":visible")) return n.nTable;
                            }));
                    return n ? new tm(i) : i;
                }),
                (m.camelToHungarian = D),
                f("$()", function (t, n) {
                    return (n = e((n = this.rows(n).nodes()))), e([].concat(n.filter(t).toArray(), n.find(t).toArray()));
                }),
                e.each(["on", "one", "off"], function (t, n) {
                    f(n + "()", function () {
                        var t = Array.prototype.slice.call(arguments),
                            i =
                                ((t[0] = e
                                    .map(t[0].split(/\s/), function (e) {
                                        return e.match(/\.dt\b/) ? e : e + ".dt";
                                    })
                                    .join(" ")),
                                e(this.tables().nodes()));
                        return i[n].apply(i, t), this;
                    });
                }),
                f("clear()", function () {
                    return this.iterator("table", function (e) {
                        ee(e);
                    });
                }),
                f("settings()", function () {
                    return new tm(this.context, this.context);
                }),
                f("init()", function () {
                    var e = this.context;
                    return e.length ? e[0].oInit : null;
                }),
                f("data()", function () {
                    return this.iterator("table", function (e) {
                        return S(e.aoData, "_aData");
                    }).flatten();
                }),
                f("destroy()", function (n) {
                    return (
                        (n = n || !1),
                        this.iterator("table", function (i) {
                            var a,
                                s = i.oClasses,
                                o = i.nTable,
                                l = i.nTBody,
                                u = i.nTHead,
                                d = i.nTFoot,
                                c = e(o),
                                p = ((l = e(l)), e(i.nTableWrapper)),
                                f = e.map(i.aoData, function (e) {
                                    return e.nTr;
                                });
                            (i.bDestroying = !0),
                                ti(i, "aoDestroyCallback", "destroy", [i]),
                                n || new tm(i).columns().visible(!0),
                                p.off(".DT").find(":not(tbody *)").off(".DT"),
                                e(t).off(".DT-" + i.sInstance),
                                o != u.parentNode && (c.children("thead").detach(), c.append(u)),
                                d && o != d.parentNode && (c.children("tfoot").detach(), c.append(d)),
                                (i.aaSorting = []),
                                (i.aaSortingFixed = []),
                                eX(i),
                                e(f).removeClass(i.asStripeClasses.join(" ")),
                                e("th, td", u).removeClass(s.sSortable + " " + s.sSortableAsc + " " + s.sSortableDesc + " " + s.sSortableNone),
                                l.children().detach(),
                                l.append(f),
                                (d = i.nTableWrapper.parentNode),
                                c[(u = n ? "remove" : "detach")](),
                                p[u](),
                                !n &&
                                    d &&
                                    (d.insertBefore(o, i.nTableReinsertBefore), c.css("width", i.sDestroyWidth).removeClass(s.sTable), (a = i.asDestroyStripes.length)) &&
                                    l.children().each(function (t) {
                                        e(this).addClass(i.asDestroyStripes[t % a]);
                                    }),
                                -1 !== (f = e.inArray(i, m.settings)) && m.settings.splice(f, 1);
                        })
                    );
                }),
                e.each(["column", "row", "cell"], function (e, t) {
                    f(t + "s().every()", function (e) {
                        var n = this.selector.opts,
                            a = this;
                        return this.iterator(t, function (s, o, l, u, d) {
                            e.call(a[t](o, "cell" === t ? l : n, "cell" === t ? n : i), o, l, u, d);
                        });
                    });
                }),
                f("i18n()", function (t, n, a) {
                    var s = this.context[0];
                    return (t = K(t)(s.oLanguage)) === i && (t = n), (t = a !== i && e.isPlainObject(t) ? (t[a] !== i ? t[a] : t._) : t).replace("%d", a);
                }),
                (m.version = "1.13.4"),
                (m.settings = []),
                (m.models = {}),
                (m.models.oSearch = { bCaseInsensitive: !0, sSearch: "", bRegex: !1, bSmart: !0, return: !1 }),
                (m.models.oRow = { nTr: null, anCells: null, _aData: [], _aSortData: null, _aFilterData: null, _sFilterRow: null, _sRowStripe: "", src: null, idx: -1 }),
                (m.models.oColumn = {
                    idx: null,
                    aDataSort: null,
                    asSorting: null,
                    bSearchable: null,
                    bSortable: null,
                    bVisible: null,
                    _sManualType: null,
                    _bAttrSrc: !1,
                    fnCreatedCell: null,
                    fnGetData: null,
                    fnSetData: null,
                    mData: null,
                    mRender: null,
                    nTh: null,
                    nTf: null,
                    sClass: null,
                    sContentPadding: null,
                    sDefaultContent: null,
                    sName: null,
                    sSortDataType: "std",
                    sSortingClass: null,
                    sSortingClassJUI: null,
                    sTitle: null,
                    sType: null,
                    sWidth: null,
                    sWidthOrig: null,
                }),
                (m.defaults = {
                    aaData: null,
                    aaSorting: [[0, "asc"]],
                    aaSortingFixed: [],
                    ajax: null,
                    aLengthMenu: [10, 25, 50, 100],
                    aoColumns: null,
                    aoColumnDefs: null,
                    aoSearchCols: [],
                    asStripeClasses: null,
                    bAutoWidth: !0,
                    bDeferRender: !1,
                    bDestroy: !1,
                    bFilter: !0,
                    bInfo: !0,
                    bLengthChange: !0,
                    bPaginate: !0,
                    bProcessing: !1,
                    bRetrieve: !1,
                    bScrollCollapse: !1,
                    bServerSide: !1,
                    bSort: !0,
                    bSortMulti: !0,
                    bSortCellsTop: !1,
                    bSortClasses: !0,
                    bStateSave: !1,
                    fnCreatedRow: null,
                    fnDrawCallback: null,
                    fnFooterCallback: null,
                    fnFormatNumber: function (e) {
                        return e.toString().replace(/\B(?=(\d{3})+(?!\d))/g, this.oLanguage.sThousands);
                    },
                    fnHeaderCallback: null,
                    fnInfoCallback: null,
                    fnInitComplete: null,
                    fnPreDrawCallback: null,
                    fnRowCallback: null,
                    fnServerData: null,
                    fnServerParams: null,
                    fnStateLoadCallback: function (e) {
                        try {
                            return JSON.parse((-1 === e.iStateDuration ? sessionStorage : localStorage).getItem("DataTables_" + e.sInstance + "_" + location.pathname));
                        } catch (t) {
                            return {};
                        }
                    },
                    fnStateLoadParams: null,
                    fnStateLoaded: null,
                    fnStateSaveCallback: function (e, t) {
                        try {
                            (-1 === e.iStateDuration ? sessionStorage : localStorage).setItem("DataTables_" + e.sInstance + "_" + location.pathname, JSON.stringify(t));
                        } catch (n) {}
                    },
                    fnStateSaveParams: null,
                    iStateDuration: 7200,
                    iDeferLoading: null,
                    iDisplayLength: 10,
                    iDisplayStart: 0,
                    iTabIndex: 0,
                    oClasses: {},
                    oLanguage: {
                        oAria: { sSortAscending: ": activate to sort column ascending", sSortDescending: ": activate to sort column descending" },
                        oPaginate: { sFirst: "First", sLast: "Last", sNext: "Next", sPrevious: "Previous" },
                        sEmptyTable: "No data available in table",
                        sInfo: "Showing _START_ to _END_ of _TOTAL_ entries",
                        sInfoEmpty: "Showing 0 to 0 of 0 entries",
                        sInfoFiltered: "(filtered from _MAX_ total entries)",
                        sInfoPostFix: "",
                        sDecimal: "",
                        sThousands: ",",
                        sLengthMenu: "Show _MENU_ entries",
                        sLoadingRecords: "Loading...",
                        sProcessing: "",
                        sSearch: "Search:",
                        sSearchPlaceholder: "",
                        sUrl: "",
                        sZeroRecords: "No matching records found",
                    },
                    oSearch: e.extend({}, m.models.oSearch),
                    sAjaxDataProp: "data",
                    sAjaxSource: null,
                    sDom: "lfrtip",
                    searchDelay: null,
                    sPaginationType: "simple_numbers",
                    sScrollX: "",
                    sScrollXInner: "",
                    sScrollY: "",
                    sServerMethod: "GET",
                    renderer: null,
                    rowId: "DT_RowId",
                }),
                E(m.defaults),
                (m.defaults.column = {
                    aDataSort: null,
                    iDataSort: -1,
                    asSorting: ["asc", "desc"],
                    bSearchable: !0,
                    bSortable: !0,
                    bVisible: !0,
                    fnCreatedCell: null,
                    mData: null,
                    mRender: null,
                    sCellType: "td",
                    sClass: "",
                    sContentPadding: "",
                    sDefaultContent: null,
                    sName: "",
                    sSortDataType: "std",
                    sTitle: null,
                    sType: null,
                    sWidth: null,
                }),
                E(m.defaults.column),
                (m.models.oSettings = {
                    oFeatures: {
                        bAutoWidth: null,
                        bDeferRender: null,
                        bFilter: null,
                        bInfo: null,
                        bLengthChange: null,
                        bPaginate: null,
                        bProcessing: null,
                        bServerSide: null,
                        bSort: null,
                        bSortMulti: null,
                        bSortClasses: null,
                        bStateSave: null,
                    },
                    oScroll: { bCollapse: null, iBarWidth: 0, sX: null, sXInner: null, sY: null },
                    oLanguage: { fnInfoCallback: null },
                    oBrowser: { bScrollOversize: !1, bScrollbarLeft: !1, bBounding: !1, barWidth: 0 },
                    ajax: null,
                    aanFeatures: [],
                    aoData: [],
                    aiDisplay: [],
                    aiDisplayMaster: [],
                    aIds: {},
                    aoColumns: [],
                    aoHeader: [],
                    aoFooter: [],
                    oPreviousSearch: {},
                    aoPreSearchCols: [],
                    aaSorting: null,
                    aaSortingFixed: [],
                    asStripeClasses: null,
                    asDestroyStripes: [],
                    sDestroyWidth: 0,
                    aoRowCallback: [],
                    aoHeaderCallback: [],
                    aoFooterCallback: [],
                    aoDrawCallback: [],
                    aoRowCreatedCallback: [],
                    aoPreDrawCallback: [],
                    aoInitComplete: [],
                    aoStateSaveParams: [],
                    aoStateLoadParams: [],
                    aoStateLoaded: [],
                    sTableId: "",
                    nTable: null,
                    nTHead: null,
                    nTFoot: null,
                    nTBody: null,
                    nTableWrapper: null,
                    bDeferLoading: !1,
                    bInitialised: !1,
                    aoOpenRows: [],
                    sDom: null,
                    searchDelay: null,
                    sPaginationType: "two_button",
                    iStateDuration: 0,
                    aoStateSave: [],
                    aoStateLoad: [],
                    oSavedState: null,
                    oLoadedState: null,
                    sAjaxSource: null,
                    sAjaxDataProp: null,
                    jqXHR: null,
                    json: i,
                    oAjaxData: i,
                    fnServerData: null,
                    aoServerParams: [],
                    sServerMethod: null,
                    fnFormatNumber: null,
                    aLengthMenu: null,
                    iDraw: 0,
                    bDrawing: !1,
                    iDrawError: -1,
                    _iDisplayLength: 10,
                    _iDisplayStart: 0,
                    _iRecordsTotal: 0,
                    _iRecordsDisplay: 0,
                    oClasses: {},
                    bFiltered: !1,
                    bSorted: !1,
                    bSortCellsTop: null,
                    oInit: null,
                    aoDestroyCallback: [],
                    fnRecordsTotal: function () {
                        return "ssp" == ts(this) ? +this._iRecordsTotal : this.aiDisplayMaster.length;
                    },
                    fnRecordsDisplay: function () {
                        return "ssp" == ts(this) ? +this._iRecordsDisplay : this.aiDisplay.length;
                    },
                    fnDisplayEnd: function () {
                        var e = this._iDisplayLength,
                            t = this._iDisplayStart,
                            n = t + e,
                            i = this.aiDisplay.length,
                            a = this.oFeatures,
                            s = a.bPaginate;
                        return a.bServerSide ? (!1 === s || -1 === e ? t + i : Math.min(t + e, this._iRecordsDisplay)) : !s || i < n || -1 === e ? i : n;
                    },
                    oInstance: null,
                    sInstance: null,
                    iTabIndex: 0,
                    nScrollHead: null,
                    nScrollFoot: null,
                    aLastSort: [],
                    oPlugins: {},
                    rowIdFn: null,
                    rowId: null,
                }),
                (m.ext = p = {
                    buttons: {},
                    classes: {},
                    builder: "-source-",
                    errMode: "alert",
                    feature: [],
                    search: [],
                    selector: { cell: [], column: [], row: [] },
                    internal: {},
                    legacy: { ajax: null },
                    pager: {},
                    renderer: { pageButton: {}, header: {} },
                    order: {},
                    type: { detect: [], search: {}, order: {} },
                    _unique: 0,
                    fnVersionCheck: m.fnVersionCheck,
                    iApiIndex: 0,
                    oJUIClasses: {},
                    sVersion: m.version,
                }),
                e.extend(p, { afnFiltering: p.search, aTypes: p.type.detect, ofnSearch: p.type.search, oSort: p.type.order, afnSortData: p.order, aoFeatures: p.feature, oApi: p.internal, oStdClasses: p.classes, oPagination: p.pager }),
                e.extend(m.ext.classes, {
                    sTable: "dataTable",
                    sNoFooter: "no-footer",
                    sPageButton: "paginate_button",
                    sPageButtonActive: "current",
                    sPageButtonDisabled: "disabled",
                    sStripeOdd: "odd",
                    sStripeEven: "even",
                    sRowEmpty: "dataTables_empty",
                    sWrapper: "dataTables_wrapper",
                    sFilter: "dataTables_filter",
                    sInfo: "dataTables_info",
                    sPaging: "dataTables_paginate paging_",
                    sLength: "dataTables_length",
                    sProcessing: "dataTables_processing",
                    sSortAsc: "sorting_asc",
                    sSortDesc: "sorting_desc",
                    sSortable: "sorting",
                    sSortableAsc: "sorting_desc_disabled",
                    sSortableDesc: "sorting_asc_disabled",
                    sSortableNone: "sorting_disabled",
                    sSortColumn: "sorting_",
                    sFilterInput: "",
                    sLengthSelect: "",
                    sScrollWrapper: "dataTables_scroll",
                    sScrollHead: "dataTables_scrollHead",
                    sScrollHeadInner: "dataTables_scrollHeadInner",
                    sScrollBody: "dataTables_scrollBody",
                    sScrollFoot: "dataTables_scrollFoot",
                    sScrollFootInner: "dataTables_scrollFootInner",
                    sHeaderTH: "",
                    sFooterTH: "",
                    sSortJUIAsc: "",
                    sSortJUIDesc: "",
                    sSortJUI: "",
                    sSortJUIAscAllowed: "",
                    sSortJUIDescAllowed: "",
                    sSortJUIWrapper: "",
                    sSortIcon: "",
                    sJUIHeader: "",
                    sJUIFooter: "",
                }),
                m.ext.pager);
        function tx(e, t) {
            var n = [],
                i = tw.numbers_length,
                a = Math.floor(i / 2);
            return (
                t <= i
                    ? (n = u(0, t))
                    : e <= a
                    ? ((n = u(0, i - 2)).push("ellipsis"), n.push(t - 1))
                    : ((t - 1 - a <= e ? (n = u(t - (i - 2), t)) : ((n = u(e - a + 2, e + a - 1)).push("ellipsis"), n.push(t - 1), n)).splice(0, 0, "ellipsis"), n.splice(0, 0, 0)),
                (n.DT_el = "span"),
                n
            );
        }
        function tS(e, t, n, i) {
            var a;
            return 0 === e || (e && "-" !== e) ? ("number" == (a = typeof e) || "bigint" == a ? e : +(e = (e = t ? x(e, t) : e).replace && (n && (e = e.replace(n, "")), i) ? e.replace(i, "") : e)) : -1 / 0;
        }
        function tT(t) {
            e.each(
                {
                    num: function (e) {
                        return tS(e, t);
                    },
                    "num-fmt": function (e) {
                        return tS(e, t, _);
                    },
                    "html-num": function (e) {
                        return tS(e, t, $);
                    },
                    "html-num-fmt": function (e) {
                        return tS(e, t, $, _);
                    },
                },
                function (e, n) {
                    (p.type.order[e + t + "-pre"] = n), e.match(/^html\-/) && (p.type.search[e + t] = p.type.search.html);
                }
            );
        }
        function tC(e) {
            return "string" == typeof (e = Array.isArray(e) ? e.join(",") : e) ? e.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") : e;
        }
        function tE(e, n, i, a, s) {
            return t.moment ? e[n](s) : t.luxon ? e[i](s) : a ? e[a](s) : e;
        }
        e.extend(tw, {
            simple: function (e, t) {
                return ["previous", "next"];
            },
            full: function (e, t) {
                return ["first", "previous", "next", "last"];
            },
            numbers: function (e, t) {
                return [tx(e, t)];
            },
            simple_numbers: function (e, t) {
                return ["previous", tx(e, t), "next"];
            },
            full_numbers: function (e, t) {
                return ["first", "previous", tx(e, t), "next", "last"];
            },
            first_last_numbers: function (e, t) {
                return ["first", tx(e, t), "last"];
            },
            _numbers: tx,
            numbers_length: 7,
        }),
            e.extend(!0, m.ext.renderer, {
                pageButton: {
                    _: function (t, a, s, o, l, u) {
                        var d,
                            c,
                            p,
                            f = t.oClasses,
                            h = t.oLanguage.oPaginate,
                            m = t.oLanguage.oAria.paginate || {};
                        try {
                            p = e(a).find(n.activeElement).data("dt-idx");
                        } catch (g) {}
                        (function n(i, a) {
                            for (
                                var o,
                                    p,
                                    g,
                                    v = f.sPageButtonDisabled,
                                    $ = function (e) {
                                        eO(t, e.data.action, !0);
                                    },
                                    y = 0,
                                    b = a.length;
                                y < b;
                                y++
                            )
                                if (Array.isArray((o = a[y]))) {
                                    var _ = e("<" + (o.DT_el || "div") + "/>").appendTo(i);
                                    n(_, o);
                                } else {
                                    switch (((d = null), (c = o), (p = t.iTabIndex), o)) {
                                        case "ellipsis":
                                            i.append('<span class="ellipsis">&#x2026;</span>');
                                            break;
                                        case "first":
                                            (d = h.sFirst), 0 === l && ((p = -1), (c += " " + v));
                                            break;
                                        case "previous":
                                            (d = h.sPrevious), 0 === l && ((p = -1), (c += " " + v));
                                            break;
                                        case "next":
                                            (d = h.sNext), (0 !== u && l !== u - 1) || ((p = -1), (c += " " + v));
                                            break;
                                        case "last":
                                            (d = h.sLast), (0 !== u && l !== u - 1) || ((p = -1), (c += " " + v));
                                            break;
                                        default:
                                            (d = t.fnFormatNumber(o + 1)), (c = l === o ? f.sPageButtonActive : "");
                                    }
                                    null !== d &&
                                        ((_ = t.oInit.pagingTag || "a"),
                                        (g = -1 !== c.indexOf(v)),
                                        tt(
                                            e("<" + _ + ">", {
                                                class: f.sPageButton + " " + c,
                                                "aria-controls": t.sTableId,
                                                "aria-disabled": g ? "true" : null,
                                                "aria-label": m[o],
                                                "aria-role": "link",
                                                "aria-current": c === f.sPageButtonActive ? "page" : null,
                                                "data-dt-idx": o,
                                                tabindex: p,
                                                id: 0 === s && "string" == typeof o ? t.sTableId + "_" + o : null,
                                            })
                                                .html(d)
                                                .appendTo(i),
                                            { action: o },
                                            $
                                        ));
                                }
                        })(e(a).empty(), o),
                            p !== i &&
                                e(a)
                                    .find("[data-dt-idx=" + p + "]")
                                    .trigger("focus");
                    },
                },
            }),
            e.extend(m.ext.type.detect, [
                function (e, t) {
                    return s(e, (t = t.oLanguage.sDecimal)) ? "num" + t : null;
                },
                function (e, t) {
                    var n;
                    return (!e || e instanceof Date || y.test(e)) && ((null !== (n = Date.parse(e)) && !isNaN(n)) || w(e)) ? "date" : null;
                },
                function (e, t) {
                    return s(e, (t = t.oLanguage.sDecimal), !0) ? "num-fmt" + t : null;
                },
                function (e, t) {
                    return o(e, (t = t.oLanguage.sDecimal)) ? "html-num" + t : null;
                },
                function (e, t) {
                    return o(e, (t = t.oLanguage.sDecimal), !0) ? "html-num-fmt" + t : null;
                },
                function (e, t) {
                    return w(e) || ("string" == typeof e && -1 !== e.indexOf("<")) ? "html" : null;
                },
            ]),
            e.extend(m.ext.type.search, {
                html: function (e) {
                    return w(e) ? e : "string" == typeof e ? e.replace(v, " ").replace($, "") : "";
                },
                string: function (e) {
                    return w(e) || "string" != typeof e ? e : e.replace(v, " ");
                },
            }),
            e.extend(p.type.order, {
                "date-pre": function (e) {
                    return isNaN((e = Date.parse(e))) ? -1 / 0 : e;
                },
                "html-pre": function (e) {
                    return w(e) ? "" : e.replace ? e.replace(/<.*?>/g, "").toLowerCase() : e + "";
                },
                "string-pre": function (e) {
                    return w(e) ? "" : "string" == typeof e ? e.toLowerCase() : e.toString ? e.toString() : "";
                },
                "string-asc": function (e, t) {
                    return e < t ? -1 : t < e ? 1 : 0;
                },
                "string-desc": function (e, t) {
                    return e < t ? 1 : t < e ? -1 : 0;
                },
            }),
            tT(""),
            e.extend(!0, m.ext.renderer, {
                header: {
                    _: function (t, n, i, a) {
                        e(t.nTable).on("order.dt.DT", function (e, s, o, l) {
                            t === s && ((s = i.idx), n.removeClass(a.sSortAsc + " " + a.sSortDesc).addClass("asc" == l[s] ? a.sSortAsc : "desc" == l[s] ? a.sSortDesc : i.sSortingClass));
                        });
                    },
                    jqueryui: function (t, n, i, a) {
                        e("<div/>")
                            .addClass(a.sSortJUIWrapper)
                            .append(n.contents())
                            .append(e("<span/>").addClass(a.sSortIcon + " " + i.sSortingClassJUI))
                            .appendTo(n),
                            e(t.nTable).on("order.dt.DT", function (e, s, o, l) {
                                t === s &&
                                    ((s = i.idx),
                                    n.removeClass(a.sSortAsc + " " + a.sSortDesc).addClass("asc" == l[s] ? a.sSortAsc : "desc" == l[s] ? a.sSortDesc : i.sSortingClass),
                                    n
                                        .find("span." + a.sSortIcon)
                                        .removeClass(a.sSortJUIAsc + " " + a.sSortJUIDesc + " " + a.sSortJUI + " " + a.sSortJUIAscAllowed + " " + a.sSortJUIDescAllowed)
                                        .addClass("asc" == l[s] ? a.sSortJUIAsc : "desc" == l[s] ? a.sSortJUIDesc : i.sSortingClassJUI));
                            });
                    },
                },
            });
        var t8 = !1;
        function tD(e, n, i) {
            var a;
            if (t.moment) {
                if (!(a = t.moment.utc(e, n, i, !0)).isValid()) return null;
            } else if (t.luxon) {
                if (!(a = n && "string" == typeof e ? t.luxon.DateTime.fromFormat(e, n) : t.luxon.DateTime.fromISO(e)).isValid) return null;
                a.setLocale(i);
            } else n ? (t8 || alert("DataTables warning: Formatted date without Moment.js or Luxon - https://datatables.net/tn/17"), (t8 = !0)) : (a = new Date(e));
            return a;
        }
        function tk(e) {
            return function (t, n, a, s) {
                0 === arguments.length ? ((a = "en"), (t = n = null)) : 1 === arguments.length ? ((a = "en"), (n = t), (t = null)) : 2 === arguments.length && ((a = n), (n = t), (t = null));
                var o = "datetime-" + n;
                return (
                    m.ext.type.order[o] ||
                        (m.ext.type.detect.unshift(function (e) {
                            return e === o && o;
                        }),
                        (m.ext.type.order[o + "-asc"] = function (e, t) {
                            return (e = e.valueOf()) === (t = t.valueOf()) ? 0 : e < t ? -1 : 1;
                        }),
                        (m.ext.type.order[o + "-desc"] = function (e, t) {
                            return (e = e.valueOf()) === (t = t.valueOf()) ? 0 : t < e ? -1 : 1;
                        })),
                    function (l, u) {
                        var d;
                        return (
                            (null !== l && l !== i) || (l = "--now" === s ? ((d = new Date()), new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds()))) : ""),
                            "type" === u
                                ? o
                                : "" === l
                                ? "sort" !== u
                                    ? ""
                                    : tD("0000-01-01 00:00:00", null, a)
                                : (null === n || t !== n || "sort" === u || "type" === u || l instanceof Date) && null !== (d = tD(l, t, a))
                                ? "sort" === u
                                    ? d
                                    : ((l = null === n ? tE(d, "toDate", "toJSDate", "")[e]() : tE(d, "format", "toFormat", "toISOString", n)), "display" === u ? tC(l) : l)
                                : l
                        );
                    }
                );
            };
        }
        var tL = ",",
            tA = ".";
        if (Intl)
            try {
                for (var tI = new Intl.NumberFormat().formatToParts(100000.1), tP = 0; tP < tI.length; tP++) "group" === tI[tP].type ? (tL = tI[tP].value) : "decimal" === tI[tP].type && (tA = tI[tP].value);
            } catch (t0) {}
        function tN(e) {
            return function () {
                var t = [eK(this[m.ext.iApiIndex])].concat(Array.prototype.slice.call(arguments));
                return m.ext.internal[e].apply(this, t);
            };
        }
        return (
            (m.datetime = function (e, t) {
                var n = "datetime-detect-" + e;
                (t = t || "en"),
                    m.ext.type.order[n] ||
                        (m.ext.type.detect.unshift(function (i) {
                            var a = tD(i, e, t);
                            return !("" !== i && !a) && n;
                        }),
                        (m.ext.type.order[n + "-pre"] = function (n) {
                            return tD(n, e, t) || 0;
                        }));
            }),
            (m.render = {
                date: tk("toLocaleDateString"),
                datetime: tk("toLocaleString"),
                time: tk("toLocaleTimeString"),
                number: function (e, t, n, a, s) {
                    return (
                        (null !== e && e !== i) || (e = tL),
                        (null !== t && t !== i) || (t = tA),
                        {
                            display: function (i) {
                                if (("number" != typeof i && "string" != typeof i) || "" === i || null === i) return i;
                                var o = i < 0 ? "-" : "",
                                    l = parseFloat(i);
                                return isNaN(l)
                                    ? tC(i)
                                    : ((i = Math.abs((l = l.toFixed(n)))),
                                      (l = parseInt(i, 10)),
                                      (i = n ? t + (i - l).toFixed(n).substring(2) : ""),
                                      (o = 0 === l && 0 === parseFloat(i) ? "" : o) + (a || "") + l.toString().replace(/\B(?=(\d{3})+(?!\d))/g, e) + i + (s || ""));
                            },
                        }
                    );
                },
                text: function () {
                    return { display: tC, filter: tC };
                },
            }),
            e.extend(m.ext.internal, {
                _fnExternApiFunc: tN,
                _fnBuildAjax: ef,
                _fnAjaxUpdate: eh,
                _fnAjaxParameters: em,
                _fnAjaxUpdateDraw: eg,
                _fnAjaxDataSrc: ev,
                _fnAddColumn: M,
                _fnColumnOptions: O,
                _fnAdjustColumnSizing: R,
                _fnVisibleToColumnIndex: z,
                _fnColumnIndexToVisible: F,
                _fnVisbleColumns: j,
                _fnGetColumns: H,
                _fnColumnTypes: B,
                _fnApplyColumnDefs: q,
                _fnHungarianMap: E,
                _fnCamelToHungarian: D,
                _fnLanguageCompat: k,
                _fnBrowserDetect: P,
                _fnAddData: W,
                _fnAddTr: G,
                _fnNodeToDataIndex: function (e, t) {
                    return t._DT_RowIndex !== i ? t._DT_RowIndex : null;
                },
                _fnNodeToColumnIndex: function (t, n, i) {
                    return e.inArray(i, t.aoData[n].anCells);
                },
                _fnGetCellData: U,
                _fnSetCellData: V,
                _fnSplitObjNotation: J,
                _fnGetObjectDataFn: K,
                _fnSetObjectDataFn: Z,
                _fnGetDataMaster: Q,
                _fnClearTable: ee,
                _fnDeleteIndex: et,
                _fnInvalidate: en,
                _fnGetRowElements: ei,
                _fnCreateTr: er,
                _fnBuildHead: es,
                _fnDrawHead: eo,
                _fnDraw: el,
                _fnReDraw: eu,
                _fnAddOptionsHtml: ed,
                _fnDetectHeader: ec,
                _fnGetUniqueThs: ep,
                _fnFeatureHtmlFilter: e$,
                _fnFilterComplete: ey,
                _fnFilterCustom: eb,
                _fnFilterColumn: e_,
                _fnFilter: ew,
                _fnFilterCreateSearch: ex,
                _fnEscapeRegex: eS,
                _fnFilterData: eE,
                _fnFeatureHtmlInfo: ek,
                _fnUpdateInfo: eL,
                _fnInfoMacros: eA,
                _fnInitialise: eI,
                _fnInitComplete: eP,
                _fnLengthChange: e0,
                _fnFeatureHtmlLength: eN,
                _fnFeatureHtmlPaginate: eM,
                _fnPageChange: eO,
                _fnFeatureHtmlProcessing: eR,
                _fnProcessingDisplay: ez,
                _fnFeatureHtmlTable: e9,
                _fnScrollDraw: eF,
                _fnApplyToChildren: ej,
                _fnCalculateColumnWidths: e1,
                _fnThrottle: eB,
                _fnConvertToWidth: eq,
                _fnGetWidestNode: e3,
                _fnGetMaxLenString: eW,
                _fnStringToCss: eG,
                _fnSortFlatten: e2,
                _fnSort: e4,
                _fnSortAria: e6,
                _fnSortListener: eU,
                _fnSortAttachListener: eV,
                _fnSortingClasses: eX,
                _fnSortData: e7,
                _fnSaveState: eY,
                _fnLoadState: e5,
                _fnImplementState: eJ,
                _fnSettingsFromNode: eK,
                _fnLog: eZ,
                _fnMap: eQ,
                _fnBindAction: tt,
                _fnCallbackReg: tn,
                _fnCallbackFire: ti,
                _fnLengthOverflow: tr,
                _fnRenderer: ta,
                _fnDataSource: ts,
                _fnRowAttributes: ea,
                _fnExtend: te,
                _fnCalculateEnd: function () {},
            }),
            (((e.fn.dataTable = m).$ = e).fn.dataTableSettings = m.settings),
            (e.fn.dataTableExt = m.ext),
            (e.fn.DataTable = function (t) {
                return e(this).dataTable(t).api();
            }),
            e.each(m, function (t, n) {
                e.fn.DataTable[t] = n;
            }),
            m
        );
    }),
    (function (e) {
        "use strict";
        "function" == typeof define && define.amd ? define(["jquery"], e) : "object" == typeof exports ? (module.exports = e(require("jquery"))) : e(jQuery);
    })(function (e) {
        "use strict";
        function t(e) {
            return e && (0 === e.offsetWidth || 0 === e.offsetHeight || !1 === e.open);
        }
        function n(e, n) {
            function i(e) {
                void 0 !== e.open && (e.open = !e.open);
            }
            var a = (function e(n) {
                    for (var i = [], a = n.parentNode; t(a); ) i.push(a), (a = a.parentNode);
                    return i;
                })(e),
                s = a.length,
                o = [],
                l = e[n];
            if (s) {
                for (var u = 0; u < s; u++)
                    (o[u] = a[u].style.cssText),
                        a[u].style.setProperty ? a[u].style.setProperty("display", "block", "important") : (a[u].style.cssText += ";display: block !important"),
                        (a[u].style.height = "0"),
                        (a[u].style.overflow = "hidden"),
                        (a[u].style.visibility = "hidden"),
                        i(a[u]);
                l = e[n];
                for (var d = 0; d < s; d++) (a[d].style.cssText = o[d]), i(a[d]);
            }
            return l;
        }
        function i(e, t) {
            var n = parseFloat(e);
            return Number.isNaN(n) ? t : n;
        }
        function a(e) {
            return e.charAt(0).toUpperCase() + e.substr(1);
        }
        function s(t, n) {
            if (
                ((this.$window = e(window)),
                (this.$document = e(document)),
                (this.$element = e(t)),
                (this.options = e.extend({}, c, n)),
                (this.polyfill = this.options.polyfill),
                (this.orientation = this.$element[0].getAttribute("data-orientation") || this.options.orientation),
                (this.onInit = this.options.onInit),
                (this.onSlide = this.options.onSlide),
                (this.onSlideEnd = this.options.onSlideEnd),
                (this.DIMENSION = p.orientation[this.orientation].dimension),
                (this.DIRECTION = p.orientation[this.orientation].direction),
                (this.DIRECTION_STYLE = p.orientation[this.orientation].directionStyle),
                (this.COORDINATE = p.orientation[this.orientation].coordinate),
                this.polyfill && d)
            )
                return !1;
            (this.identifier = "js-" + l + "-" + u++),
                (this.startEvent = this.options.startEvent.join("." + this.identifier + " ") + "." + this.identifier),
                (this.moveEvent = this.options.moveEvent.join("." + this.identifier + " ") + "." + this.identifier),
                (this.endEvent = this.options.endEvent.join("." + this.identifier + " ") + "." + this.identifier),
                (this.toFixed = (this.step + "").replace(".", "").length - 1),
                (this.$fill = e('<div class="' + this.options.fillClass + '" />')),
                (this.$handle = e('<div class="' + this.options.handleClass + '" />')),
                (this.$range = e('<div class="' + this.options.rangeClass + " " + this.options[this.orientation + "Class"] + '" id="' + this.identifier + '" />')
                    .insertAfter(this.$element)
                    .prepend(this.$fill, this.$handle)),
                this.$element.css({ position: "absolute", width: "1px", height: "1px", overflow: "hidden", opacity: "0" }),
                (this.handleDown = e.proxy(this.handleDown, this)),
                (this.handleMove = e.proxy(this.handleMove, this)),
                (this.handleEnd = e.proxy(this.handleEnd, this)),
                this.init();
            var i,
                a = this,
                s = 20;
            this.$window.on(
                "resize." + this.identifier,
                ((i = function () {
                    !(function (e, t) {
                        var n = Array.prototype.slice.call(arguments, 2);
                        setTimeout(function () {
                            return e.apply(null, n);
                        }, 300);
                    })(function () {
                        a.update(!1, !1);
                    }, 300);
                }),
                (s = s || 100),
                function () {
                    if (!i.debouncing) {
                        var e = Array.prototype.slice.apply(arguments);
                        (i.lastReturnVal = i.apply(window, e)), (i.debouncing = !0);
                    }
                    return (
                        clearTimeout(i.debounceTimeout),
                        (i.debounceTimeout = setTimeout(function () {
                            i.debouncing = !1;
                        }, s)),
                        i.lastReturnVal
                    );
                })
            ),
                this.$document.on(this.startEvent, "#" + this.identifier + ":not(." + this.options.disabledClass + ")", this.handleDown),
                this.$element.on("change." + this.identifier, function (e, t) {
                    if (!t || t.origin !== a.identifier) {
                        var n = e.target.value,
                            i = a.getPositionFromValue(n);
                        a.setPosition(i);
                    }
                });
        }
        Number.isNaN =
            Number.isNaN ||
            function (e) {
                return "number" == typeof e && e != e;
            };
        var o,
            l = "rangeslider",
            u = 0,
            d = ((o = document.createElement("input")).setAttribute("type", "range"), "text" !== o.type),
            c = {
                polyfill: !0,
                orientation: "horizontal",
                rangeClass: "rangeslider",
                disabledClass: "rangeslider--disabled",
                activeClass: "rangeslider--active",
                horizontalClass: "rangeslider--horizontal",
                verticalClass: "rangeslider--vertical",
                fillClass: "rangeslider__fill",
                handleClass: "rangeslider__handle",
                startEvent: ["mousedown", "touchstart", "pointerdown"],
                moveEvent: ["mousemove", "touchmove", "pointermove"],
                endEvent: ["mouseup", "touchend", "pointerup"],
            },
            p = { orientation: { horizontal: { dimension: "width", direction: "left", directionStyle: "left", coordinate: "x" }, vertical: { dimension: "height", direction: "top", directionStyle: "bottom", coordinate: "y" } } };
        return (
            (s.prototype.init = function () {
                this.update(!0, !1), this.onInit && "function" == typeof this.onInit && this.onInit();
            }),
            (s.prototype.update = function (e, t) {
                (e = e || !1) &&
                    ((this.min = i(this.$element[0].getAttribute("min"), 0)),
                    (this.max = i(this.$element[0].getAttribute("max"), 100)),
                    (this.value = i(this.$element[0].value, Math.round(this.min + (this.max - this.min) / 2))),
                    (this.step = i(this.$element[0].getAttribute("step"), 1))),
                    (this.handleDimension = n(this.$handle[0], "offset" + a(this.DIMENSION))),
                    (this.rangeDimension = n(this.$range[0], "offset" + a(this.DIMENSION))),
                    (this.maxHandlePos = this.rangeDimension - this.handleDimension),
                    (this.grabPos = this.handleDimension / 2),
                    (this.position = this.getPositionFromValue(this.value)),
                    this.$element[0].disabled ? this.$range.addClass(this.options.disabledClass) : this.$range.removeClass(this.options.disabledClass),
                    this.setPosition(this.position, t);
            }),
            (s.prototype.handleDown = function (e) {
                if (
                    (e.preventDefault(),
                    !(
                        (e.button && 0 !== e.button) ||
                        (this.$document.on(this.moveEvent, this.handleMove),
                        this.$document.on(this.endEvent, this.handleEnd),
                        this.$range.addClass(this.options.activeClass),
                        (" " + e.target.className + " ").replace(/[\n\t]/g, " ").indexOf(this.options.handleClass) > -1)
                    ))
                ) {
                    var t = this.getRelativePosition(e),
                        n = this.$range[0].getBoundingClientRect()[this.DIRECTION],
                        i = this.getPositionFromNode(this.$handle[0]) - n,
                        a = "vertical" === this.orientation ? this.maxHandlePos - (t - this.grabPos) : t - this.grabPos;
                    this.setPosition(a), t >= i && t < i + this.handleDimension && (this.grabPos = t - i);
                }
            }),
            (s.prototype.handleMove = function (e) {
                e.preventDefault();
                var t = this.getRelativePosition(e),
                    n = "vertical" === this.orientation ? this.maxHandlePos - (t - this.grabPos) : t - this.grabPos;
                this.setPosition(n);
            }),
            (s.prototype.handleEnd = function (e) {
                e.preventDefault(),
                    this.$document.off(this.moveEvent, this.handleMove),
                    this.$document.off(this.endEvent, this.handleEnd),
                    this.$range.removeClass(this.options.activeClass),
                    this.$element.trigger("change", { origin: this.identifier }),
                    this.onSlideEnd && "function" == typeof this.onSlideEnd && this.onSlideEnd(this.position, this.value);
            }),
            (s.prototype.cap = function (e, t, n) {
                return e < t ? t : e > n ? n : e;
            }),
            (s.prototype.setPosition = function (e, t) {
                var n, i;
                void 0 === t && (t = !0),
                    (n = this.getValueFromPosition(this.cap(e, 0, this.maxHandlePos))),
                    (i = this.getPositionFromValue(n)),
                    (this.$fill[0].style[this.DIMENSION] = i + this.grabPos + "px"),
                    (this.$handle[0].style[this.DIRECTION_STYLE] = i + "px"),
                    this.setValue(n),
                    (this.position = i),
                    (this.value = n),
                    t && this.onSlide && "function" == typeof this.onSlide && this.onSlide(i, n);
            }),
            (s.prototype.getPositionFromNode = function (e) {
                for (var t = 0; null !== e; ) (t += e.offsetLeft), (e = e.offsetParent);
                return t;
            }),
            (s.prototype.getRelativePosition = function (e) {
                var t = a(this.COORDINATE),
                    n = this.$range[0].getBoundingClientRect()[this.DIRECTION],
                    i = 0;
                return (
                    void 0 !== e.originalEvent["client" + t]
                        ? (i = e.originalEvent["client" + t])
                        : e.originalEvent.touches && e.originalEvent.touches[0] && void 0 !== e.originalEvent.touches[0]["client" + t]
                        ? (i = e.originalEvent.touches[0]["client" + t])
                        : e.currentPoint && void 0 !== e.currentPoint[this.COORDINATE] && (i = e.currentPoint[this.COORDINATE]),
                    i - n
                );
            }),
            (s.prototype.getPositionFromValue = function (e) {
                var t;
                return Number.isNaN((t = (e - this.min) / (this.max - this.min))) ? 0 : t * this.maxHandlePos;
            }),
            (s.prototype.getValueFromPosition = function (e) {
                var t, n;
                return (t = e / (this.maxHandlePos || 1)), Number((n = this.step * Math.round((t * (this.max - this.min)) / this.step) + this.min).toFixed(this.toFixed));
            }),
            (s.prototype.setValue = function (e) {
                (e === this.value && "" !== this.$element[0].value) || this.$element.val(e).trigger("input", { origin: this.identifier });
            }),
            (s.prototype.destroy = function () {
                this.$document.off("." + this.identifier),
                    this.$window.off("." + this.identifier),
                    this.$element
                        .off("." + this.identifier)
                        .removeAttr("style")
                        .removeData("plugin_" + l),
                    this.$range && this.$range.length && this.$range[0].parentNode.removeChild(this.$range[0]);
            }),
            (e.fn[l] = function (t) {
                var n = Array.prototype.slice.call(arguments, 1);
                return this.each(function () {
                    var i = e(this),
                        a = i.data("plugin_" + l);
                    a || i.data("plugin_" + l, (a = new s(this, t))), "string" == typeof t && a[t].apply(a, n);
                });
            }),
            "rangeslider.js is available in jQuery context e.g $(selector).rangeslider(options);"
        );
    });
var Swiper = (function () {
    "use strict";
    function e(e) {
        return null !== e && "object" == typeof e && "constructor" in e && e.constructor === Object;
    }
    function t(n, i) {
        void 0 === n && (n = {}),
            void 0 === i && (i = {}),
            Object.keys(i).forEach((a) => {
                void 0 === n[a] ? (n[a] = i[a]) : e(i[a]) && e(n[a]) && Object.keys(i[a]).length > 0 && t(n[a], i[a]);
            });
    }
    let n = {
        body: {},
        addEventListener() {},
        removeEventListener() {},
        activeElement: { blur() {}, nodeName: "" },
        querySelector: () => null,
        querySelectorAll: () => [],
        getElementById: () => null,
        createEvent: () => ({ initEvent() {} }),
        createElement: () => ({ children: [], childNodes: [], style: {}, setAttribute() {}, getElementsByTagName: () => [] }),
        createElementNS: () => ({}),
        importNode: () => null,
        location: { hash: "", host: "", hostname: "", href: "", origin: "", pathname: "", protocol: "", search: "" },
    };
    function i() {
        let e = "undefined" != typeof document ? document : {};
        return t(e, n), e;
    }
    let a = {
        document: n,
        navigator: { userAgent: "" },
        location: { hash: "", host: "", hostname: "", href: "", origin: "", pathname: "", protocol: "", search: "" },
        history: { replaceState() {}, pushState() {}, go() {}, back() {} },
        CustomEvent: function () {
            return this;
        },
        addEventListener() {},
        removeEventListener() {},
        getComputedStyle: () => ({ getPropertyValue: () => "" }),
        Image() {},
        Date() {},
        screen: {},
        setTimeout() {},
        clearTimeout() {},
        matchMedia: () => ({}),
        requestAnimationFrame: (e) => ("undefined" == typeof setTimeout ? (e(), null) : setTimeout(e, 0)),
        cancelAnimationFrame(e) {
            "undefined" != typeof setTimeout && clearTimeout(e);
        },
    };
    function s() {
        let e = "undefined" != typeof window ? window : {};
        return t(e, a), e;
    }
    function o(e, t) {
        return void 0 === t && (t = 0), setTimeout(e, t);
    }
    function l() {
        return Date.now();
    }
    function u(e, t) {
        void 0 === t && (t = "x");
        let n = s(),
            i,
            a,
            o,
            l = (function (e) {
                let t = s(),
                    n;
                return t.getComputedStyle && (n = t.getComputedStyle(e, null)), !n && e.currentStyle && (n = e.currentStyle), n || (n = e.style), n;
            })(e);
        return (
            n.WebKitCSSMatrix
                ? ((a = l.transform || l.webkitTransform).split(",").length > 6 &&
                      (a = a
                          .split(", ")
                          .map((e) => e.replace(",", "."))
                          .join(", ")),
                  (o = new n.WebKitCSSMatrix("none" === a ? "" : a)))
                : (i = (o = l.MozTransform || l.OTransform || l.MsTransform || l.msTransform || l.transform || l.getPropertyValue("transform").replace("translate(", "matrix(1, 0, 0, 1,")).toString().split(",")),
            "x" === t && (a = n.WebKitCSSMatrix ? o.m41 : 16 === i.length ? parseFloat(i[12]) : parseFloat(i[4])),
            "y" === t && (a = n.WebKitCSSMatrix ? o.m42 : 16 === i.length ? parseFloat(i[13]) : parseFloat(i[5])),
            a || 0
        );
    }
    function d(e) {
        return "object" == typeof e && null !== e && e.constructor && "Object" === Object.prototype.toString.call(e).slice(8, -1);
    }
    function c() {
        var e;
        let t = Object(arguments.length <= 0 ? void 0 : arguments[0]),
            n = ["__proto__", "constructor", "prototype"];
        for (let i = 1; i < arguments.length; i += 1) {
            let a = i < 0 || arguments.length <= i ? void 0 : arguments[i];
            if (null != a && ((e = a), !("undefined" != typeof window && void 0 !== window.HTMLElement ? e instanceof HTMLElement : e && (1 === e.nodeType || 11 === e.nodeType)))) {
                let s = Object.keys(Object(a)).filter((e) => 0 > n.indexOf(e));
                for (let o = 0, l = s.length; o < l; o += 1) {
                    let u = s[o],
                        p = Object.getOwnPropertyDescriptor(a, u);
                    void 0 !== p && p.enumerable && (d(t[u]) && d(a[u]) ? (a[u].__swiper__ ? (t[u] = a[u]) : c(t[u], a[u])) : !d(t[u]) && d(a[u]) ? ((t[u] = {}), a[u].__swiper__ ? (t[u] = a[u]) : c(t[u], a[u])) : (t[u] = a[u]));
                }
            }
        }
        return t;
    }
    function p(e, t, n) {
        e.style.setProperty(t, n);
    }
    function f(e) {
        let { swiper: t, targetPosition: n, side: i } = e,
            a = s(),
            o = -t.translate,
            l,
            u = null,
            d = t.params.speed;
        (t.wrapperEl.style.scrollSnapType = "none"), a.cancelAnimationFrame(t.cssModeFrameID);
        let c = n > o ? "next" : "prev",
            p = (e, t) => ("next" === c && e >= t) || ("prev" === c && e <= t),
            f = () => {
                (l = new Date().getTime()), null === u && (u = l);
                let e = Math.max(Math.min((l - u) / d, 1), 0),
                    s = o + (0.5 - Math.cos(e * Math.PI) / 2) * (n - o);
                if ((p(s, n) && (s = n), t.wrapperEl.scrollTo({ [i]: s }), p(s, n)))
                    return (
                        (t.wrapperEl.style.overflow = "hidden"),
                        (t.wrapperEl.style.scrollSnapType = ""),
                        setTimeout(() => {
                            (t.wrapperEl.style.overflow = ""), t.wrapperEl.scrollTo({ [i]: s });
                        }),
                        void a.cancelAnimationFrame(t.cssModeFrameID)
                    );
                t.cssModeFrameID = a.requestAnimationFrame(f);
            };
        f();
    }
    function h(e) {
        return e.querySelector(".swiper-slide-transform") || (e.shadowRoot && e.shadowRoot.querySelector(".swiper-slide-transform")) || e;
    }
    function m(e, t) {
        return void 0 === t && (t = ""), [...e.children].filter((e) => e.matches(t));
    }
    function g(e, t) {
        void 0 === t && (t = []);
        let n = document.createElement(e);
        return n.classList.add(...(Array.isArray(t) ? t : [t])), n;
    }
    function v(e) {
        let t = s(),
            n = i(),
            a = e.getBoundingClientRect(),
            o = n.body,
            l = e.clientTop || o.clientTop || 0,
            u = e.clientLeft || o.clientLeft || 0,
            d = e === t ? t.scrollY : e.scrollTop,
            c = e === t ? t.scrollX : e.scrollLeft;
        return { top: a.top + d - l, left: a.left + c - u };
    }
    function $(e, t) {
        return s().getComputedStyle(e, null).getPropertyValue(t);
    }
    function y(e) {
        let t,
            n = e;
        if (n) {
            for (t = 0; null !== (n = n.previousSibling); ) 1 === n.nodeType && (t += 1);
            return t;
        }
    }
    function b(e, t) {
        let n = [],
            i = e.parentElement;
        for (; i; ) t ? i.matches(t) && n.push(i) : n.push(i), (i = i.parentElement);
        return n;
    }
    function _(e, t) {
        t &&
            e.addEventListener("transitionend", function n(i) {
                i.target === e && (t.call(e, i), e.removeEventListener("transitionend", n));
            });
    }
    function w(e, t, n) {
        let i = s();
        return n
            ? e["width" === t ? "offsetWidth" : "offsetHeight"] +
                  parseFloat(i.getComputedStyle(e, null).getPropertyValue("width" === t ? "margin-right" : "margin-top")) +
                  parseFloat(i.getComputedStyle(e, null).getPropertyValue("width" === t ? "margin-left" : "margin-bottom"))
            : e.offsetWidth;
    }
    let x, S, T;
    function C() {
        return (
            x ||
                (x = (function () {
                    let e = s(),
                        t = i();
                    return { smoothScroll: t.documentElement && t.documentElement.style && "scrollBehavior" in t.documentElement.style, touch: !!("ontouchstart" in e || (e.DocumentTouch && t instanceof e.DocumentTouch)) };
                })()),
            x
        );
    }
    let E = (e, t) => {
            if (!e || e.destroyed || !e.params) return;
            let n = t.closest(e.isElement ? "swiper-slide" : `.${e.params.slideClass}`);
            if (n) {
                let i = n.querySelector(`.${e.params.lazyPreloaderClass}`);
                !i && e.isElement && (i = n.shadowRoot.querySelector(`.${e.params.lazyPreloaderClass}`)), i && i.remove();
            }
        },
        D = (e, t) => {
            if (!e.slides[t]) return;
            let n = e.slides[t].querySelector('[loading="lazy"]');
            n && n.removeAttribute("loading");
        },
        k = (e) => {
            if (!e || e.destroyed || !e.params) return;
            let t = e.params.lazyPreloadPrevNext,
                n = e.slides.length;
            if (!n || !t || t < 0) return;
            t = Math.min(t, n);
            let i = "auto" === e.params.slidesPerView ? e.slidesPerViewDynamic() : Math.ceil(e.params.slidesPerView),
                a = e.activeIndex;
            if (e.params.grid && e.params.grid.rows > 1) {
                let s = a,
                    o = [s - t];
                return (
                    o.push(...Array.from({ length: t }).map((e, t) => s + i + t)),
                    void e.slides.forEach((t, n) => {
                        o.includes(t.column) && D(e, n);
                    })
                );
            }
            let l = a + i - 1;
            if (e.params.rewind || e.params.loop)
                for (let u = a - t; u <= l + t; u += 1) {
                    let d = ((u % n) + n) % n;
                    (d < a || d > l) && D(e, d);
                }
            else for (let c = Math.max(a - t, 0); c <= Math.min(l + t, n - 1); c += 1) c !== a && (c > l || c < a) && D(e, c);
        };
    function L(e) {
        let { swiper: t, runCallbacks: n, direction: i, step: a } = e,
            { activeIndex: s, previousIndex: o } = t,
            l = i;
        if ((l || (l = s > o ? "next" : s < o ? "prev" : "reset"), t.emit(`transition${a}`), n && s !== o)) {
            if ("reset" === l) return void t.emit(`slideResetTransition${a}`);
            t.emit(`slideChangeTransition${a}`), "next" === l ? t.emit(`slideNextTransition${a}`) : t.emit(`slidePrevTransition${a}`);
        }
    }
    function A(e) {
        let t = this,
            n = i(),
            a = s(),
            o = t.touchEventsData;
        o.evCache.push(e);
        let { params: u, touches: d, enabled: c } = t;
        if (!c || (!u.simulateTouch && "mouse" === e.pointerType) || (t.animating && u.preventInteractionOnTransition)) return;
        !t.animating && u.cssMode && u.loop && t.loopFix();
        let p = e;
        p.originalEvent && (p = p.originalEvent);
        let f = p.target;
        if (("wrapper" === u.touchEventsTarget && !t.wrapperEl.contains(f)) || ("which" in p && 3 === p.which) || ("button" in p && p.button > 0) || (o.isTouched && o.isMoved)) return;
        let h = !!u.noSwipingClass && "" !== u.noSwipingClass,
            m = e.composedPath ? e.composedPath() : e.path;
        h && p.target && p.target.shadowRoot && m && (f = m[0]);
        let g = u.noSwipingSelector ? u.noSwipingSelector : `.${u.noSwipingClass}`,
            v = !(!p.target || !p.target.shadowRoot);
        if (
            u.noSwiping &&
            (v
                ? (function (e, t) {
                      return (
                          void 0 === t && (t = this),
                          (function t(n) {
                              if (!n || n === i() || n === s()) return null;
                              n.assignedSlot && (n = n.assignedSlot);
                              let a = n.closest(e);
                              return a || n.getRootNode ? a || t(n.getRootNode().host) : null;
                          })(t)
                      );
                  })(g, f)
                : f.closest(g))
        )
            return void (t.allowClick = !0);
        if (u.swipeHandler && !f.closest(u.swipeHandler)) return;
        (d.currentX = p.pageX), (d.currentY = p.pageY);
        let $ = d.currentX,
            y = d.currentY,
            b = u.edgeSwipeDetection || u.iOSEdgeSwipeDetection,
            _ = u.edgeSwipeThreshold || u.iOSEdgeSwipeThreshold;
        if (b && ($ <= _ || $ >= a.innerWidth - _)) {
            if ("prevent" !== b) return;
            e.preventDefault();
        }
        Object.assign(o, { isTouched: !0, isMoved: !1, allowTouchCallbacks: !0, isScrolling: void 0, startMoving: void 0 }),
            (d.startX = $),
            (d.startY = y),
            (o.touchStartTime = l()),
            (t.allowClick = !0),
            t.updateSize(),
            (t.swipeDirection = void 0),
            u.threshold > 0 && (o.allowThresholdMove = !1);
        let w = !0;
        f.matches(o.focusableElements) && ((w = !1), "SELECT" === f.nodeName && (o.isTouched = !1)), n.activeElement && n.activeElement.matches(o.focusableElements) && n.activeElement !== f && n.activeElement.blur();
        let x = w && t.allowTouchMove && u.touchStartPreventDefault;
        (u.touchStartForcePreventDefault || x) && !f.isContentEditable && p.preventDefault(), u.freeMode && u.freeMode.enabled && t.freeMode && t.animating && !u.cssMode && t.freeMode.onTouchStart(), t.emit("touchStart", p);
    }
    function I(e) {
        let t = i(),
            n = this,
            a = n.touchEventsData,
            { params: s, touches: o, rtlTranslate: u, enabled: d } = n;
        if (!d || (!s.simulateTouch && "mouse" === e.pointerType)) return;
        let c = e;
        if ((c.originalEvent && (c = c.originalEvent), !a.isTouched)) return void (a.startMoving && a.isScrolling && n.emit("touchMoveOpposite", c));
        let p = a.evCache.findIndex((e) => e.pointerId === c.pointerId);
        p >= 0 && (a.evCache[p] = c);
        let f = a.evCache.length > 1 ? a.evCache[0] : c,
            h = f.pageX,
            m = f.pageY;
        if (c.preventedByNestedSwiper) return (o.startX = h), void (o.startY = m);
        if (!n.allowTouchMove)
            return (
                c.target.matches(a.focusableElements) || (n.allowClick = !1),
                void (a.isTouched && (Object.assign(o, { startX: h, startY: m, prevX: n.touches.currentX, prevY: n.touches.currentY, currentX: h, currentY: m }), (a.touchStartTime = l())))
            );
        if (s.touchReleaseOnEdges && !s.loop) {
            if (n.isVertical()) {
                if ((m < o.startY && n.translate <= n.maxTranslate()) || (m > o.startY && n.translate >= n.minTranslate())) return (a.isTouched = !1), void (a.isMoved = !1);
            } else if ((h < o.startX && n.translate <= n.maxTranslate()) || (h > o.startX && n.translate >= n.minTranslate())) return;
        }
        if (t.activeElement && c.target === t.activeElement && c.target.matches(a.focusableElements)) return (a.isMoved = !0), void (n.allowClick = !1);
        if ((a.allowTouchCallbacks && n.emit("touchMove", c), c.targetTouches && c.targetTouches.length > 1)) return;
        (o.currentX = h), (o.currentY = m);
        let g = o.currentX - o.startX,
            v = o.currentY - o.startY;
        if (n.params.threshold && Math.sqrt(g ** 2 + v ** 2) < n.params.threshold) return;
        if (void 0 === a.isScrolling) {
            let $;
            (n.isHorizontal() && o.currentY === o.startY) || (n.isVertical() && o.currentX === o.startX)
                ? (a.isScrolling = !1)
                : g * g + v * v >= 25 && (($ = (180 * Math.atan2(Math.abs(v), Math.abs(g))) / Math.PI), (a.isScrolling = n.isHorizontal() ? $ > s.touchAngle : 90 - $ > s.touchAngle));
        }
        if (
            (a.isScrolling && n.emit("touchMoveOpposite", c),
            void 0 === a.startMoving && ((o.currentX === o.startX && o.currentY === o.startY) || (a.startMoving = !0)),
            a.isScrolling || (n.zoom && n.params.zoom && n.params.zoom.enabled && a.evCache.length > 1))
        )
            return void (a.isTouched = !1);
        if (!a.startMoving) return;
        (n.allowClick = !1), !s.cssMode && c.cancelable && c.preventDefault(), s.touchMoveStopPropagation && !s.nested && c.stopPropagation();
        let y = n.isHorizontal() ? g : v,
            b = n.isHorizontal() ? o.currentX - o.previousX : o.currentY - o.previousY;
        s.oneWayMovement && ((y = Math.abs(y) * (u ? 1 : -1)), (b = Math.abs(b) * (u ? 1 : -1))), (o.diff = y), (y *= s.touchRatio), u && ((y = -y), (b = -b));
        let _ = n.touchesDirection;
        (n.swipeDirection = y > 0 ? "prev" : "next"), (n.touchesDirection = b > 0 ? "prev" : "next");
        let w = n.params.loop && !s.cssMode;
        if (!a.isMoved) {
            if ((w && n.loopFix({ direction: n.swipeDirection }), (a.startTranslate = n.getTranslate()), n.setTransition(0), n.animating)) {
                let x = new window.CustomEvent("transitionend", { bubbles: !0, cancelable: !0 });
                n.wrapperEl.dispatchEvent(x);
            }
            (a.allowMomentumBounce = !1), s.grabCursor && (!0 === n.allowSlideNext || !0 === n.allowSlidePrev) && n.setGrabCursor(!0), n.emit("sliderFirstMove", c);
        }
        let S;
        a.isMoved && _ !== n.touchesDirection && w && Math.abs(y) >= 1 && (n.loopFix({ direction: n.swipeDirection, setTranslate: !0 }), (S = !0)), n.emit("sliderMove", c), (a.isMoved = !0), (a.currentTranslate = y + a.startTranslate);
        let T = !0,
            C = s.resistanceRatio;
        if (
            (s.touchReleaseOnEdges && (C = 0),
            y > 0
                ? (w && !S && a.currentTranslate > (s.centeredSlides ? n.minTranslate() - n.size / 2 : n.minTranslate()) && n.loopFix({ direction: "prev", setTranslate: !0, activeSlideIndex: 0 }),
                  a.currentTranslate > n.minTranslate() && ((T = !1), s.resistance && (a.currentTranslate = n.minTranslate() - 1 + (-n.minTranslate() + a.startTranslate + y) ** C)))
                : y < 0 &&
                  (w &&
                      !S &&
                      a.currentTranslate < (s.centeredSlides ? n.maxTranslate() + n.size / 2 : n.maxTranslate()) &&
                      n.loopFix({ direction: "next", setTranslate: !0, activeSlideIndex: n.slides.length - ("auto" === s.slidesPerView ? n.slidesPerViewDynamic() : Math.ceil(parseFloat(s.slidesPerView, 10))) }),
                  a.currentTranslate < n.maxTranslate() && ((T = !1), s.resistance && (a.currentTranslate = n.maxTranslate() + 1 - (n.maxTranslate() - a.startTranslate - y) ** C))),
            T && (c.preventedByNestedSwiper = !0),
            !n.allowSlideNext && "next" === n.swipeDirection && a.currentTranslate < a.startTranslate && (a.currentTranslate = a.startTranslate),
            !n.allowSlidePrev && "prev" === n.swipeDirection && a.currentTranslate > a.startTranslate && (a.currentTranslate = a.startTranslate),
            n.allowSlidePrev || n.allowSlideNext || (a.currentTranslate = a.startTranslate),
            s.threshold > 0)
        ) {
            if (!(Math.abs(y) > s.threshold || a.allowThresholdMove)) return void (a.currentTranslate = a.startTranslate);
            if (!a.allowThresholdMove)
                return (a.allowThresholdMove = !0), (o.startX = o.currentX), (o.startY = o.currentY), (a.currentTranslate = a.startTranslate), void (o.diff = n.isHorizontal() ? o.currentX - o.startX : o.currentY - o.startY);
        }
        s.followFinger &&
            !s.cssMode &&
            (((s.freeMode && s.freeMode.enabled && n.freeMode) || s.watchSlidesProgress) && (n.updateActiveIndex(), n.updateSlidesClasses()),
            s.freeMode && s.freeMode.enabled && n.freeMode && n.freeMode.onTouchMove(),
            n.updateProgress(a.currentTranslate),
            n.setTranslate(a.currentTranslate));
    }
    function P(e) {
        let t = this,
            n = t.touchEventsData,
            i = n.evCache.findIndex((t) => t.pointerId === e.pointerId);
        if ((i >= 0 && n.evCache.splice(i, 1), ["pointercancel", "pointerout", "pointerleave", "contextmenu"].includes(e.type) && (!["pointercancel", "contextmenu"].includes(e.type) || (!t.browser.isSafari && !t.browser.isWebView))))
            return;
        let { params: a, touches: s, rtlTranslate: u, slidesGrid: d, enabled: c } = t;
        if (!c || (!a.simulateTouch && "mouse" === e.pointerType)) return;
        let p = e;
        if ((p.originalEvent && (p = p.originalEvent), n.allowTouchCallbacks && t.emit("touchEnd", p), (n.allowTouchCallbacks = !1), !n.isTouched))
            return n.isMoved && a.grabCursor && t.setGrabCursor(!1), (n.isMoved = !1), void (n.startMoving = !1);
        a.grabCursor && n.isMoved && n.isTouched && (!0 === t.allowSlideNext || !0 === t.allowSlidePrev) && t.setGrabCursor(!1);
        let f = l(),
            h = f - n.touchStartTime;
        if (t.allowClick) {
            let m = p.path || (p.composedPath && p.composedPath());
            t.updateClickedSlide((m && m[0]) || p.target), t.emit("tap click", p), h < 300 && f - n.lastClickTime < 300 && t.emit("doubleTap doubleClick", p);
        }
        if (
            ((n.lastClickTime = l()),
            o(() => {
                t.destroyed || (t.allowClick = !0);
            }),
            !n.isTouched || !n.isMoved || !t.swipeDirection || 0 === s.diff || n.currentTranslate === n.startTranslate)
        )
            return (n.isTouched = !1), (n.isMoved = !1), void (n.startMoving = !1);
        let g;
        if (((n.isTouched = !1), (n.isMoved = !1), (n.startMoving = !1), (g = a.followFinger ? (u ? t.translate : -t.translate) : -n.currentTranslate), a.cssMode)) return;
        if (a.freeMode && a.freeMode.enabled) return void t.freeMode.onTouchEnd({ currentPos: g });
        let v = 0,
            $ = t.slidesSizesGrid[0];
        for (let y = 0; y < d.length; y += y < a.slidesPerGroupSkip ? 1 : a.slidesPerGroup) {
            let b = y < a.slidesPerGroupSkip - 1 ? 1 : a.slidesPerGroup;
            void 0 !== d[y + b] ? g >= d[y] && g < d[y + b] && ((v = y), ($ = d[y + b] - d[y])) : g >= d[y] && ((v = y), ($ = d[d.length - 1] - d[d.length - 2]));
        }
        let _ = null,
            w = null;
        a.rewind && (t.isBeginning ? (w = a.virtual && a.virtual.enabled && t.virtual ? t.virtual.slides.length - 1 : t.slides.length - 1) : t.isEnd && (_ = 0));
        let x = (g - d[v]) / $,
            S = v < a.slidesPerGroupSkip - 1 ? 1 : a.slidesPerGroup;
        if (h > a.longSwipesMs) {
            if (!a.longSwipes) return void t.slideTo(t.activeIndex);
            "next" === t.swipeDirection && (x >= a.longSwipesRatio ? t.slideTo(a.rewind && t.isEnd ? _ : v + S) : t.slideTo(v)),
                "prev" === t.swipeDirection && (x > 1 - a.longSwipesRatio ? t.slideTo(v + S) : null !== w && x < 0 && Math.abs(x) > a.longSwipesRatio ? t.slideTo(w) : t.slideTo(v));
        } else {
            if (!a.shortSwipes) return void t.slideTo(t.activeIndex);
            t.navigation && (p.target === t.navigation.nextEl || p.target === t.navigation.prevEl)
                ? p.target === t.navigation.nextEl
                    ? t.slideTo(v + S)
                    : t.slideTo(v)
                : ("next" === t.swipeDirection && t.slideTo(null !== _ ? _ : v + S), "prev" === t.swipeDirection && t.slideTo(null !== w ? w : v));
        }
    }
    function N() {
        let e = this,
            { params: t, el: n } = e;
        if (n && 0 === n.offsetWidth) return;
        t.breakpoints && e.setBreakpoint();
        let { allowSlideNext: i, allowSlidePrev: a, snapGrid: s } = e,
            o = e.virtual && e.params.virtual.enabled;
        (e.allowSlideNext = !0), (e.allowSlidePrev = !0), e.updateSize(), e.updateSlides(), e.updateSlidesClasses();
        let l = o && t.loop;
        !("auto" === t.slidesPerView || t.slidesPerView > 1) || !e.isEnd || e.isBeginning || e.params.centeredSlides || l
            ? e.params.loop && !o
                ? e.slideToLoop(e.realIndex, 0, !1, !0)
                : e.slideTo(e.activeIndex, 0, !1, !0)
            : e.slideTo(e.slides.length - 1, 0, !1, !0),
            e.autoplay &&
                e.autoplay.running &&
                e.autoplay.paused &&
                (clearTimeout(e.autoplay.resizeTimeout),
                (e.autoplay.resizeTimeout = setTimeout(() => {
                    e.autoplay && e.autoplay.running && e.autoplay.paused && e.autoplay.resume();
                }, 500))),
            (e.allowSlidePrev = a),
            (e.allowSlideNext = i),
            e.params.watchOverflow && s !== e.snapGrid && e.checkOverflow();
    }
    function M(e) {
        this.enabled && (this.allowClick || (this.params.preventClicks && e.preventDefault(), this.params.preventClicksPropagation && this.animating && (e.stopPropagation(), e.stopImmediatePropagation())));
    }
    function O() {
        let e = this,
            { wrapperEl: t, rtlTranslate: n, enabled: i } = e;
        if (!i) return;
        let a;
        (e.previousTranslate = e.translate), e.isHorizontal() ? (e.translate = -t.scrollLeft) : (e.translate = -t.scrollTop), 0 === e.translate && (e.translate = 0), e.updateActiveIndex(), e.updateSlidesClasses();
        let s = e.maxTranslate() - e.minTranslate();
        (a = 0 === s ? 0 : (e.translate - e.minTranslate()) / s) !== e.progress && e.updateProgress(n ? -e.translate : e.translate), e.emit("setTranslate", e.translate, !1);
    }
    function R(e) {
        E(this, e.target), this.params.cssMode || ("auto" !== this.params.slidesPerView && !this.params.autoHeight) || this.update();
    }
    let z = !1;
    function F() {}
    let j = (e, t) => {
            let n = i(),
                { params: a, el: s, wrapperEl: o, device: l } = e,
                u = !!a.nested,
                d = "on" === t ? "addEventListener" : "removeEventListener",
                c = t;
            s[d]("pointerdown", e.onTouchStart, { passive: !1 }),
                n[d]("pointermove", e.onTouchMove, { passive: !1, capture: u }),
                n[d]("pointerup", e.onTouchEnd, { passive: !0 }),
                n[d]("pointercancel", e.onTouchEnd, { passive: !0 }),
                n[d]("pointerout", e.onTouchEnd, { passive: !0 }),
                n[d]("pointerleave", e.onTouchEnd, { passive: !0 }),
                n[d]("contextmenu", e.onTouchEnd, { passive: !0 }),
                (a.preventClicks || a.preventClicksPropagation) && s[d]("click", e.onClick, !0),
                a.cssMode && o[d]("scroll", e.onScroll),
                a.updateOnWindowResize ? e[c](l.ios || l.android ? "resize orientationchange observerUpdate" : "resize observerUpdate", N, !0) : e[c]("observerUpdate", N, !0),
                s[d]("load", e.onLoad, { capture: !0 });
        },
        H = (e, t) => e.grid && t.grid && t.grid.rows > 1;
    var B = {
        init: !0,
        direction: "horizontal",
        oneWayMovement: !1,
        touchEventsTarget: "wrapper",
        initialSlide: 0,
        speed: 300,
        cssMode: !1,
        updateOnWindowResize: !0,
        resizeObserver: !0,
        nested: !1,
        createElements: !1,
        enabled: !0,
        focusableElements: "input, select, option, textarea, button, video, label",
        width: null,
        height: null,
        preventInteractionOnTransition: !1,
        userAgent: null,
        url: null,
        edgeSwipeDetection: !1,
        edgeSwipeThreshold: 20,
        autoHeight: !1,
        setWrapperSize: !1,
        virtualTranslate: !1,
        effect: "slide",
        breakpoints: void 0,
        breakpointsBase: "window",
        spaceBetween: 0,
        slidesPerView: 1,
        slidesPerGroup: 1,
        slidesPerGroupSkip: 0,
        slidesPerGroupAuto: !1,
        centeredSlides: !1,
        centeredSlidesBounds: !1,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 0,
        normalizeSlideIndex: !0,
        centerInsufficientSlides: !1,
        watchOverflow: !0,
        roundLengths: !1,
        touchRatio: 1,
        touchAngle: 45,
        simulateTouch: !0,
        shortSwipes: !0,
        longSwipes: !0,
        longSwipesRatio: 0.5,
        longSwipesMs: 300,
        followFinger: !0,
        allowTouchMove: !0,
        threshold: 5,
        touchMoveStopPropagation: !1,
        touchStartPreventDefault: !0,
        touchStartForcePreventDefault: !1,
        touchReleaseOnEdges: !1,
        uniqueNavElements: !0,
        resistance: !0,
        resistanceRatio: 0.85,
        watchSlidesProgress: !1,
        grabCursor: !1,
        preventClicks: !0,
        preventClicksPropagation: !0,
        slideToClickedSlide: !1,
        loop: !1,
        loopedSlides: null,
        loopPreventsSliding: !0,
        rewind: !1,
        allowSlidePrev: !0,
        allowSlideNext: !0,
        swipeHandler: null,
        noSwiping: !0,
        noSwipingClass: "swiper-no-swiping",
        noSwipingSelector: null,
        passiveListeners: !0,
        maxBackfaceHiddenSlides: 10,
        containerModifierClass: "swiper-",
        slideClass: "swiper-slide",
        slideActiveClass: "swiper-slide-active",
        slideVisibleClass: "swiper-slide-visible",
        slideNextClass: "swiper-slide-next",
        slidePrevClass: "swiper-slide-prev",
        wrapperClass: "swiper-wrapper",
        lazyPreloaderClass: "swiper-lazy-preloader",
        lazyPreloadPrevNext: 0,
        runCallbacksOnInit: !0,
        _emitClasses: !1,
    };
    let q = {
            eventsEmitter: {
                on(e, t, n) {
                    let i = this;
                    if (!i.eventsListeners || i.destroyed || "function" != typeof t) return i;
                    let a = n ? "unshift" : "push";
                    return (
                        e.split(" ").forEach((e) => {
                            i.eventsListeners[e] || (i.eventsListeners[e] = []), i.eventsListeners[e][a](t);
                        }),
                        i
                    );
                },
                once(e, t, n) {
                    let i = this;
                    if (!i.eventsListeners || i.destroyed || "function" != typeof t) return i;
                    function a() {
                        i.off(e, a), a.__emitterProxy && delete a.__emitterProxy;
                        for (var n = arguments.length, s = Array(n), o = 0; o < n; o++) s[o] = arguments[o];
                        t.apply(i, s);
                    }
                    return (a.__emitterProxy = t), i.on(e, a, n);
                },
                onAny(e, t) {
                    return !this.eventsListeners || this.destroyed || "function" != typeof e || (0 > this.eventsAnyListeners.indexOf(e) && this.eventsAnyListeners[t ? "unshift" : "push"](e)), this;
                },
                offAny(e) {
                    if (!this.eventsListeners || this.destroyed || !this.eventsAnyListeners) return this;
                    let t = this.eventsAnyListeners.indexOf(e);
                    return t >= 0 && this.eventsAnyListeners.splice(t, 1), this;
                },
                off(e, t) {
                    let n = this;
                    return (
                        !n.eventsListeners ||
                            n.destroyed ||
                            (n.eventsListeners &&
                                e.split(" ").forEach((e) => {
                                    void 0 === t
                                        ? (n.eventsListeners[e] = [])
                                        : n.eventsListeners[e] &&
                                          n.eventsListeners[e].forEach((i, a) => {
                                              (i === t || (i.__emitterProxy && i.__emitterProxy === t)) && n.eventsListeners[e].splice(a, 1);
                                          });
                                })),
                        n
                    );
                },
                emit() {
                    let e = this;
                    if (!e.eventsListeners || e.destroyed || !e.eventsListeners) return e;
                    let t, n, i;
                    for (var a = arguments.length, s = Array(a), o = 0; o < a; o++) s[o] = arguments[o];
                    return (
                        "string" == typeof s[0] || Array.isArray(s[0]) ? ((t = s[0]), (n = s.slice(1, s.length)), (i = e)) : ((t = s[0].events), (n = s[0].data), (i = s[0].context || e)),
                        n.unshift(i),
                        (Array.isArray(t) ? t : t.split(" ")).forEach((t) => {
                            e.eventsAnyListeners &&
                                e.eventsAnyListeners.length &&
                                e.eventsAnyListeners.forEach((e) => {
                                    e.apply(i, [t, ...n]);
                                }),
                                e.eventsListeners &&
                                    e.eventsListeners[t] &&
                                    e.eventsListeners[t].forEach((e) => {
                                        e.apply(i, n);
                                    });
                        }),
                        e
                    );
                },
            },
            update: {
                updateSize: function () {
                    let e,
                        t,
                        n = this.el;
                    (e = void 0 !== this.params.width && null !== this.params.width ? this.params.width : n.clientWidth),
                        (t = void 0 !== this.params.height && null !== this.params.height ? this.params.height : n.clientHeight),
                        (0 === e && this.isHorizontal()) ||
                            (0 === t && this.isVertical()) ||
                            ((e = e - parseInt($(n, "padding-left") || 0, 10) - parseInt($(n, "padding-right") || 0, 10)),
                            (t = t - parseInt($(n, "padding-top") || 0, 10) - parseInt($(n, "padding-bottom") || 0, 10)),
                            Number.isNaN(e) && (e = 0),
                            Number.isNaN(t) && (t = 0),
                            Object.assign(this, { width: e, height: t, size: this.isHorizontal() ? e : t }));
                },
                updateSlides: function () {
                    let e = this;
                    function t(t) {
                        return e.isHorizontal()
                            ? t
                            : {
                                  width: "height",
                                  "margin-top": "margin-left",
                                  "margin-bottom ": "margin-right",
                                  "margin-left": "margin-top",
                                  "margin-right": "margin-bottom",
                                  "padding-left": "padding-top",
                                  "padding-right": "padding-bottom",
                                  marginRight: "marginBottom",
                              }[t];
                    }
                    function n(e, n) {
                        return parseFloat(e.getPropertyValue(t(n)) || 0);
                    }
                    let i = e.params,
                        { wrapperEl: a, slidesEl: s, size: o, rtlTranslate: l, wrongRTL: u } = e,
                        d = e.virtual && i.virtual.enabled,
                        c = d ? e.virtual.slides.length : e.slides.length,
                        f = m(s, `.${e.params.slideClass}, swiper-slide`),
                        h = d ? e.virtual.slides.length : f.length,
                        g = [],
                        v = [],
                        y = [],
                        b = i.slidesOffsetBefore;
                    "function" == typeof b && (b = i.slidesOffsetBefore.call(e));
                    let _ = i.slidesOffsetAfter;
                    "function" == typeof _ && (_ = i.slidesOffsetAfter.call(e));
                    let x = e.snapGrid.length,
                        S = e.slidesGrid.length,
                        T = i.spaceBetween,
                        C = -b,
                        E = 0,
                        D = 0;
                    if (void 0 === o) return;
                    "string" == typeof T && T.indexOf("%") >= 0 ? (T = (parseFloat(T.replace("%", "")) / 100) * o) : "string" == typeof T && (T = parseFloat(T)),
                        (e.virtualSize = -T),
                        f.forEach((e) => {
                            l ? (e.style.marginLeft = "") : (e.style.marginRight = ""), (e.style.marginBottom = ""), (e.style.marginTop = "");
                        }),
                        i.centeredSlides && i.cssMode && (p(a, "--swiper-centered-offset-before", ""), p(a, "--swiper-centered-offset-after", ""));
                    let k = i.grid && i.grid.rows > 1 && e.grid,
                        L;
                    k && e.grid.initSlides(h);
                    let A = "auto" === i.slidesPerView && i.breakpoints && Object.keys(i.breakpoints).filter((e) => void 0 !== i.breakpoints[e].slidesPerView).length > 0;
                    for (let I = 0; I < h; I += 1) {
                        let P;
                        if (((L = 0), f[I] && (P = f[I]), k && e.grid.updateSlide(I, P, h, t), !f[I] || "none" !== $(P, "display"))) {
                            if ("auto" === i.slidesPerView) {
                                A && (f[I].style[t("width")] = "");
                                let N = getComputedStyle(P),
                                    M = P.style.transform,
                                    O = P.style.webkitTransform;
                                if ((M && (P.style.transform = "none"), O && (P.style.webkitTransform = "none"), i.roundLengths)) L = e.isHorizontal() ? w(P, "width", !0) : w(P, "height", !0);
                                else {
                                    let R = n(N, "width"),
                                        z = n(N, "padding-left"),
                                        F = n(N, "padding-right"),
                                        j = n(N, "margin-left"),
                                        H = n(N, "margin-right"),
                                        B = N.getPropertyValue("box-sizing");
                                    if (B && "border-box" === B) L = R + j + H;
                                    else {
                                        let { clientWidth: q, offsetWidth: W } = P;
                                        L = R + z + F + j + H + (W - q);
                                    }
                                }
                                M && (P.style.transform = M), O && (P.style.webkitTransform = O), i.roundLengths && (L = Math.floor(L));
                            } else (L = (o - (i.slidesPerView - 1) * T) / i.slidesPerView), i.roundLengths && (L = Math.floor(L)), f[I] && (f[I].style[t("width")] = `${L}px`);
                            f[I] && (f[I].swiperSlideSize = L),
                                y.push(L),
                                i.centeredSlides
                                    ? ((C = C + L / 2 + E / 2 + T),
                                      0 === E && 0 !== I && (C = C - o / 2 - T),
                                      0 === I && (C = C - o / 2 - T),
                                      0.001 > Math.abs(C) && (C = 0),
                                      i.roundLengths && (C = Math.floor(C)),
                                      D % i.slidesPerGroup == 0 && g.push(C),
                                      v.push(C))
                                    : (i.roundLengths && (C = Math.floor(C)), (D - Math.min(e.params.slidesPerGroupSkip, D)) % e.params.slidesPerGroup == 0 && g.push(C), v.push(C), (C = C + L + T)),
                                (e.virtualSize += L + T),
                                (E = L),
                                (D += 1);
                        }
                    }
                    if (
                        ((e.virtualSize = Math.max(e.virtualSize, o) + _),
                        l && u && ("slide" === i.effect || "coverflow" === i.effect) && (a.style.width = `${e.virtualSize + T}px`),
                        i.setWrapperSize && (a.style[t("width")] = `${e.virtualSize + T}px`),
                        k && e.grid.updateWrapperSize(L, g, t),
                        !i.centeredSlides)
                    ) {
                        let G = [];
                        for (let U = 0; U < g.length; U += 1) {
                            let V = g[U];
                            i.roundLengths && (V = Math.floor(V)), g[U] <= e.virtualSize - o && G.push(V);
                        }
                        (g = G), Math.floor(e.virtualSize - o) - Math.floor(g[g.length - 1]) > 1 && g.push(e.virtualSize - o);
                    }
                    if (d && i.loop) {
                        let X = y[0] + T;
                        if (i.slidesPerGroup > 1) {
                            let Y = Math.ceil((e.virtual.slidesBefore + e.virtual.slidesAfter) / i.slidesPerGroup),
                                J = X * i.slidesPerGroup;
                            for (let K = 0; K < Y; K += 1) g.push(g[g.length - 1] + J);
                        }
                        for (let Z = 0; Z < e.virtual.slidesBefore + e.virtual.slidesAfter; Z += 1) 1 === i.slidesPerGroup && g.push(g[g.length - 1] + X), v.push(v[v.length - 1] + X), (e.virtualSize += X);
                    }
                    if ((0 === g.length && (g = [0]), 0 !== T)) {
                        let Q = e.isHorizontal() && l ? "marginLeft" : t("marginRight");
                        f.filter((e, t) => !(i.cssMode && !i.loop) || t !== f.length - 1).forEach((e) => {
                            e.style[Q] = `${T}px`;
                        });
                    }
                    if (i.centeredSlides && i.centeredSlidesBounds) {
                        let ee = 0;
                        y.forEach((e) => {
                            ee += e + (T || 0);
                        }),
                            (ee -= T);
                        let et = ee - o;
                        g = g.map((e) => (e <= 0 ? -b : e > et ? et + _ : e));
                    }
                    if (i.centerInsufficientSlides) {
                        let en = 0;
                        if (
                            (y.forEach((e) => {
                                en += e + (T || 0);
                            }),
                            (en -= T) < o)
                        ) {
                            let ei = (o - en) / 2;
                            g.forEach((e, t) => {
                                g[t] = e - ei;
                            }),
                                v.forEach((e, t) => {
                                    v[t] = e + ei;
                                });
                        }
                    }
                    if ((Object.assign(e, { slides: f, snapGrid: g, slidesGrid: v, slidesSizesGrid: y }), i.centeredSlides && i.cssMode && !i.centeredSlidesBounds)) {
                        p(a, "--swiper-centered-offset-before", -g[0] + "px"), p(a, "--swiper-centered-offset-after", e.size / 2 - y[y.length - 1] / 2 + "px");
                        let er = -e.snapGrid[0],
                            ea = -e.slidesGrid[0];
                        (e.snapGrid = e.snapGrid.map((e) => e + er)), (e.slidesGrid = e.slidesGrid.map((e) => e + ea));
                    }
                    if (
                        (h !== c && e.emit("slidesLengthChange"),
                        g.length !== x && (e.params.watchOverflow && e.checkOverflow(), e.emit("snapGridLengthChange")),
                        v.length !== S && e.emit("slidesGridLengthChange"),
                        i.watchSlidesProgress && e.updateSlidesOffset(),
                        !(d || i.cssMode || ("slide" !== i.effect && "fade" !== i.effect)))
                    ) {
                        let es = `${i.containerModifierClass}backface-hidden`,
                            eo = e.el.classList.contains(es);
                        h <= i.maxBackfaceHiddenSlides ? eo || e.el.classList.add(es) : eo && e.el.classList.remove(es);
                    }
                },
                updateAutoHeight: function (e) {
                    let t = this,
                        n = [],
                        i = t.virtual && t.params.virtual.enabled,
                        a,
                        s = 0;
                    "number" == typeof e ? t.setTransition(e) : !0 === e && t.setTransition(t.params.speed);
                    let o = (e) => (i ? t.slides[t.getSlideIndexByData(e)] : t.slides[e]);
                    if ("auto" !== t.params.slidesPerView && t.params.slidesPerView > 1) {
                        if (t.params.centeredSlides)
                            (t.visibleSlides || []).forEach((e) => {
                                n.push(e);
                            });
                        else
                            for (a = 0; a < Math.ceil(t.params.slidesPerView); a += 1) {
                                let l = t.activeIndex + a;
                                if (l > t.slides.length && !i) break;
                                n.push(o(l));
                            }
                    } else n.push(o(t.activeIndex));
                    for (a = 0; a < n.length; a += 1)
                        if (void 0 !== n[a]) {
                            let u = n[a].offsetHeight;
                            s = u > s ? u : s;
                        }
                    (s || 0 === s) && (t.wrapperEl.style.height = `${s}px`);
                },
                updateSlidesOffset: function () {
                    let e = this.slides,
                        t = this.isElement ? (this.isHorizontal() ? this.wrapperEl.offsetLeft : this.wrapperEl.offsetTop) : 0;
                    for (let n = 0; n < e.length; n += 1) e[n].swiperSlideOffset = (this.isHorizontal() ? e[n].offsetLeft : e[n].offsetTop) - t - this.cssOverflowAdjustment();
                },
                updateSlidesProgress: function (e) {
                    void 0 === e && (e = (this && this.translate) || 0);
                    let t = this,
                        n = t.params,
                        { slides: i, rtlTranslate: a, snapGrid: s } = t;
                    if (0 === i.length) return;
                    void 0 === i[0].swiperSlideOffset && t.updateSlidesOffset();
                    let o = -e;
                    a && (o = e),
                        i.forEach((e) => {
                            e.classList.remove(n.slideVisibleClass);
                        }),
                        (t.visibleSlidesIndexes = []),
                        (t.visibleSlides = []);
                    let l = n.spaceBetween;
                    "string" == typeof l && l.indexOf("%") >= 0 ? (l = (parseFloat(l.replace("%", "")) / 100) * t.size) : "string" == typeof l && (l = parseFloat(l));
                    for (let u = 0; u < i.length; u += 1) {
                        let d = i[u],
                            c = d.swiperSlideOffset;
                        n.cssMode && n.centeredSlides && (c -= i[0].swiperSlideOffset);
                        let p = (o + (n.centeredSlides ? t.minTranslate() : 0) - c) / (d.swiperSlideSize + l),
                            f = (o - s[0] + (n.centeredSlides ? t.minTranslate() : 0) - c) / (d.swiperSlideSize + l),
                            h = -(o - c),
                            m = h + t.slidesSizesGrid[u];
                        ((h >= 0 && h < t.size - 1) || (m > 1 && m <= t.size) || (h <= 0 && m >= t.size)) && (t.visibleSlides.push(d), t.visibleSlidesIndexes.push(u), i[u].classList.add(n.slideVisibleClass)),
                            (d.progress = a ? -p : p),
                            (d.originalProgress = a ? -f : f);
                    }
                },
                updateProgress: function (e) {
                    if (void 0 === e) {
                        let t = this.rtlTranslate ? -1 : 1;
                        e = (this && this.translate && this.translate * t) || 0;
                    }
                    let n = this.params,
                        i = this.maxTranslate() - this.minTranslate(),
                        { progress: a, isBeginning: s, isEnd: o, progressLoop: l } = this,
                        u = s,
                        d = o;
                    if (0 === i) (a = 0), (s = !0), (o = !0);
                    else {
                        a = (e - this.minTranslate()) / i;
                        let c = 1 > Math.abs(e - this.minTranslate()),
                            p = 1 > Math.abs(e - this.maxTranslate());
                        (s = c || a <= 0), (o = p || a >= 1), c && (a = 0), p && (a = 1);
                    }
                    if (n.loop) {
                        let f = this.getSlideIndexByData(0),
                            h = this.getSlideIndexByData(this.slides.length - 1),
                            m = this.slidesGrid[f],
                            g = this.slidesGrid[h],
                            v = this.slidesGrid[this.slidesGrid.length - 1],
                            $ = Math.abs(e);
                        (l = $ >= m ? ($ - m) / v : ($ + v - g) / v) > 1 && (l -= 1);
                    }
                    Object.assign(this, { progress: a, progressLoop: l, isBeginning: s, isEnd: o }),
                        (n.watchSlidesProgress || (n.centeredSlides && n.autoHeight)) && this.updateSlidesProgress(e),
                        s && !u && this.emit("reachBeginning toEdge"),
                        o && !d && this.emit("reachEnd toEdge"),
                        ((u && !s) || (d && !o)) && this.emit("fromEdge"),
                        this.emit("progress", a);
                },
                updateSlidesClasses: function () {
                    let { slides: e, params: t, slidesEl: n, activeIndex: i } = this,
                        a = this.virtual && t.virtual.enabled,
                        s = (e) => m(n, `.${t.slideClass}${e}, swiper-slide${e}`)[0],
                        o;
                    if (
                        (e.forEach((e) => {
                            e.classList.remove(t.slideActiveClass, t.slideNextClass, t.slidePrevClass);
                        }),
                        a)
                    ) {
                        if (t.loop) {
                            let l = i - this.virtual.slidesBefore;
                            l < 0 && (l = this.virtual.slides.length + l), l >= this.virtual.slides.length && (l -= this.virtual.slides.length), (o = s(`[data-swiper-slide-index="${l}"]`));
                        } else o = s(`[data-swiper-slide-index="${i}"]`);
                    } else o = e[i];
                    if (o) {
                        o.classList.add(t.slideActiveClass);
                        let u = (function (e, t) {
                            let n = [];
                            for (; e.nextElementSibling; ) {
                                let i = e.nextElementSibling;
                                t ? i.matches(t) && n.push(i) : n.push(i), (e = i);
                            }
                            return n;
                        })(o, `.${t.slideClass}, swiper-slide`)[0];
                        t.loop && !u && (u = e[0]), u && u.classList.add(t.slideNextClass);
                        let d = (function (e, t) {
                            let n = [];
                            for (; e.previousElementSibling; ) {
                                let i = e.previousElementSibling;
                                t ? i.matches(t) && n.push(i) : n.push(i), (e = i);
                            }
                            return n;
                        })(o, `.${t.slideClass}, swiper-slide`)[0];
                        t.loop, d && d.classList.add(t.slidePrevClass);
                    }
                    this.emitSlidesClasses();
                },
                updateActiveIndex: function (e) {
                    let t = this,
                        n = t.rtlTranslate ? t.translate : -t.translate,
                        { snapGrid: i, params: a, activeIndex: s, realIndex: o, snapIndex: l } = t,
                        u,
                        d = e,
                        c = (e) => {
                            let n = e - t.virtual.slidesBefore;
                            return n < 0 && (n = t.virtual.slides.length + n), n >= t.virtual.slides.length && (n -= t.virtual.slides.length), n;
                        };
                    if (
                        (void 0 === d &&
                            (d = (function (e) {
                                let { slidesGrid: t, params: n } = e,
                                    i = e.rtlTranslate ? e.translate : -e.translate,
                                    a;
                                for (let s = 0; s < t.length; s += 1) void 0 !== t[s + 1] ? (i >= t[s] && i < t[s + 1] - (t[s + 1] - t[s]) / 2 ? (a = s) : i >= t[s] && i < t[s + 1] && (a = s + 1)) : i >= t[s] && (a = s);
                                return n.normalizeSlideIndex && (a < 0 || void 0 === a) && (a = 0), a;
                            })(t)),
                        i.indexOf(n) >= 0)
                    )
                        u = i.indexOf(n);
                    else {
                        let p = Math.min(a.slidesPerGroupSkip, d);
                        u = p + Math.floor((d - p) / a.slidesPerGroup);
                    }
                    if ((u >= i.length && (u = i.length - 1), d === s)) return u !== l && ((t.snapIndex = u), t.emit("snapIndexChange")), void (t.params.loop && t.virtual && t.params.virtual.enabled && (t.realIndex = c(d)));
                    let f;
                    (f = t.virtual && a.virtual.enabled && a.loop ? c(d) : t.slides[d] ? parseInt(t.slides[d].getAttribute("data-swiper-slide-index") || d, 10) : d),
                        Object.assign(t, { previousSnapIndex: l, snapIndex: u, previousRealIndex: o, realIndex: f, previousIndex: s, activeIndex: d }),
                        t.initialized && k(t),
                        t.emit("activeIndexChange"),
                        t.emit("snapIndexChange"),
                        o !== f && t.emit("realIndexChange"),
                        (t.initialized || t.params.runCallbacksOnInit) && t.emit("slideChange");
                },
                updateClickedSlide: function (e) {
                    let t = this,
                        n = t.params,
                        i = e.closest(`.${n.slideClass}, swiper-slide`),
                        a,
                        s = !1;
                    if (i) {
                        for (let o = 0; o < t.slides.length; o += 1)
                            if (t.slides[o] === i) {
                                (s = !0), (a = o);
                                break;
                            }
                    }
                    if (!i || !s) return (t.clickedSlide = void 0), void (t.clickedIndex = void 0);
                    (t.clickedSlide = i),
                        t.virtual && t.params.virtual.enabled ? (t.clickedIndex = parseInt(i.getAttribute("data-swiper-slide-index"), 10)) : (t.clickedIndex = a),
                        n.slideToClickedSlide && void 0 !== t.clickedIndex && t.clickedIndex !== t.activeIndex && t.slideToClickedSlide();
                },
            },
            translate: {
                getTranslate: function (e) {
                    void 0 === e && (e = this.isHorizontal() ? "x" : "y");
                    let { params: t, rtlTranslate: n, translate: i, wrapperEl: a } = this;
                    if (t.virtualTranslate) return n ? -i : i;
                    if (t.cssMode) return i;
                    let s = u(a, e);
                    return (s += this.cssOverflowAdjustment()), n && (s = -s), s || 0;
                },
                setTranslate: function (e, t) {
                    let n = this,
                        { rtlTranslate: i, params: a, wrapperEl: s, progress: o } = n,
                        l,
                        u = 0,
                        d = 0;
                    n.isHorizontal() ? (u = i ? -e : e) : (d = e),
                        a.roundLengths && ((u = Math.floor(u)), (d = Math.floor(d))),
                        (n.previousTranslate = n.translate),
                        (n.translate = n.isHorizontal() ? u : d),
                        a.cssMode
                            ? (s[n.isHorizontal() ? "scrollLeft" : "scrollTop"] = n.isHorizontal() ? -u : -d)
                            : a.virtualTranslate || (n.isHorizontal() ? (u -= n.cssOverflowAdjustment()) : (d -= n.cssOverflowAdjustment()), (s.style.transform = `translate3d(${u}px, ${d}px, 0px)`));
                    let c = n.maxTranslate() - n.minTranslate();
                    (l = 0 === c ? 0 : (e - n.minTranslate()) / c) !== o && n.updateProgress(e), n.emit("setTranslate", n.translate, t);
                },
                minTranslate: function () {
                    return -this.snapGrid[0];
                },
                maxTranslate: function () {
                    return -this.snapGrid[this.snapGrid.length - 1];
                },
                translateTo: function (e, t, n, i, a) {
                    void 0 === e && (e = 0), void 0 === t && (t = this.params.speed), void 0 === n && (n = !0), void 0 === i && (i = !0);
                    let s = this,
                        { params: o, wrapperEl: l } = s;
                    if (s.animating && o.preventInteractionOnTransition) return !1;
                    let u = s.minTranslate(),
                        d = s.maxTranslate(),
                        c;
                    if (((c = i && e > u ? u : i && e < d ? d : e), s.updateProgress(c), o.cssMode)) {
                        let p = s.isHorizontal();
                        if (0 === t) l[p ? "scrollLeft" : "scrollTop"] = -c;
                        else {
                            if (!s.support.smoothScroll) return f({ swiper: s, targetPosition: -c, side: p ? "left" : "top" }), !0;
                            l.scrollTo({ [p ? "left" : "top"]: -c, behavior: "smooth" });
                        }
                        return !0;
                    }
                    return (
                        0 === t
                            ? (s.setTransition(0), s.setTranslate(c), n && (s.emit("beforeTransitionStart", t, a), s.emit("transitionEnd")))
                            : (s.setTransition(t),
                              s.setTranslate(c),
                              n && (s.emit("beforeTransitionStart", t, a), s.emit("transitionStart")),
                              s.animating ||
                                  ((s.animating = !0),
                                  s.onTranslateToWrapperTransitionEnd ||
                                      (s.onTranslateToWrapperTransitionEnd = function (e) {
                                          s &&
                                              !s.destroyed &&
                                              e.target === this &&
                                              (s.wrapperEl.removeEventListener("transitionend", s.onTranslateToWrapperTransitionEnd),
                                              (s.onTranslateToWrapperTransitionEnd = null),
                                              delete s.onTranslateToWrapperTransitionEnd,
                                              n && s.emit("transitionEnd"));
                                      }),
                                  s.wrapperEl.addEventListener("transitionend", s.onTranslateToWrapperTransitionEnd))),
                        !0
                    );
                },
            },
            transition: {
                setTransition: function (e, t) {
                    let n = this;
                    n.params.cssMode || ((n.wrapperEl.style.transitionDuration = `${e}ms`), (n.wrapperEl.style.transitionDelay = 0 === e ? "0ms" : "")), n.emit("setTransition", e, t);
                },
                transitionStart: function (e, t) {
                    void 0 === e && (e = !0);
                    let { params: n } = this;
                    n.cssMode || (n.autoHeight && this.updateAutoHeight(), L({ swiper: this, runCallbacks: e, direction: t, step: "Start" }));
                },
                transitionEnd: function (e, t) {
                    void 0 === e && (e = !0);
                    let n = this,
                        { params: i } = n;
                    (n.animating = !1), i.cssMode || (n.setTransition(0), L({ swiper: n, runCallbacks: e, direction: t, step: "End" }));
                },
            },
            slide: {
                slideTo: function (e, t, n, i, a) {
                    void 0 === e && (e = 0), void 0 === t && (t = this.params.speed), void 0 === n && (n = !0), "string" == typeof e && (e = parseInt(e, 10));
                    let s = this,
                        o = e;
                    o < 0 && (o = 0);
                    let { params: l, snapGrid: u, slidesGrid: d, previousIndex: c, activeIndex: p, rtlTranslate: h, wrapperEl: m, enabled: g } = s;
                    if ((s.animating && l.preventInteractionOnTransition) || (!g && !i && !a)) return !1;
                    let v = Math.min(s.params.slidesPerGroupSkip, o),
                        $ = v + Math.floor((o - v) / s.params.slidesPerGroup);
                    $ >= u.length && ($ = u.length - 1);
                    let y = -u[$];
                    if (l.normalizeSlideIndex)
                        for (let b = 0; b < d.length; b += 1) {
                            let _ = -Math.floor(100 * y),
                                w = Math.floor(100 * d[b]),
                                x = Math.floor(100 * d[b + 1]);
                            void 0 !== d[b + 1] ? (_ >= w && _ < x - (x - w) / 2 ? (o = b) : _ >= w && _ < x && (o = b + 1)) : _ >= w && (o = b);
                        }
                    if (
                        s.initialized &&
                        o !== p &&
                        ((!s.allowSlideNext && (h ? y > s.translate && y > s.minTranslate() : y < s.translate && y < s.minTranslate())) || (!s.allowSlidePrev && y > s.translate && y > s.maxTranslate() && (p || 0) !== o))
                    )
                        return !1;
                    let S;
                    if ((o !== (c || 0) && n && s.emit("beforeSlideChangeStart"), s.updateProgress(y), (S = o > p ? "next" : o < p ? "prev" : "reset"), (h && -y === s.translate) || (!h && y === s.translate)))
                        return s.updateActiveIndex(o), l.autoHeight && s.updateAutoHeight(), s.updateSlidesClasses(), "slide" !== l.effect && s.setTranslate(y), "reset" !== S && (s.transitionStart(n, S), s.transitionEnd(n, S)), !1;
                    if (l.cssMode) {
                        let T = s.isHorizontal(),
                            C = h ? y : -y;
                        if (0 === t) {
                            let E = s.virtual && s.params.virtual.enabled;
                            E && ((s.wrapperEl.style.scrollSnapType = "none"), (s._immediateVirtual = !0)),
                                E && !s._cssModeVirtualInitialSet && s.params.initialSlide > 0
                                    ? ((s._cssModeVirtualInitialSet = !0),
                                      requestAnimationFrame(() => {
                                          m[T ? "scrollLeft" : "scrollTop"] = C;
                                      }))
                                    : (m[T ? "scrollLeft" : "scrollTop"] = C),
                                E &&
                                    requestAnimationFrame(() => {
                                        (s.wrapperEl.style.scrollSnapType = ""), (s._immediateVirtual = !1);
                                    });
                        } else {
                            if (!s.support.smoothScroll) return f({ swiper: s, targetPosition: C, side: T ? "left" : "top" }), !0;
                            m.scrollTo({ [T ? "left" : "top"]: C, behavior: "smooth" });
                        }
                        return !0;
                    }
                    return (
                        s.setTransition(t),
                        s.setTranslate(y),
                        s.updateActiveIndex(o),
                        s.updateSlidesClasses(),
                        s.emit("beforeTransitionStart", t, i),
                        s.transitionStart(n, S),
                        0 === t
                            ? s.transitionEnd(n, S)
                            : s.animating ||
                              ((s.animating = !0),
                              s.onSlideToWrapperTransitionEnd ||
                                  (s.onSlideToWrapperTransitionEnd = function (e) {
                                      s &&
                                          !s.destroyed &&
                                          e.target === this &&
                                          (s.wrapperEl.removeEventListener("transitionend", s.onSlideToWrapperTransitionEnd), (s.onSlideToWrapperTransitionEnd = null), delete s.onSlideToWrapperTransitionEnd, s.transitionEnd(n, S));
                                  }),
                              s.wrapperEl.addEventListener("transitionend", s.onSlideToWrapperTransitionEnd)),
                        !0
                    );
                },
                slideToLoop: function (e, t, n, i) {
                    void 0 === e && (e = 0), void 0 === t && (t = this.params.speed), void 0 === n && (n = !0), "string" == typeof e && (e = parseInt(e, 10));
                    let a = e;
                    return this.params.loop && (this.virtual && this.params.virtual.enabled ? (a += this.virtual.slidesBefore) : (a = this.getSlideIndexByData(a))), this.slideTo(a, t, n, i);
                },
                slideNext: function (e, t, n) {
                    void 0 === e && (e = this.params.speed), void 0 === t && (t = !0);
                    let i = this,
                        { enabled: a, params: s, animating: o } = i;
                    if (!a) return i;
                    let l = s.slidesPerGroup;
                    "auto" === s.slidesPerView && 1 === s.slidesPerGroup && s.slidesPerGroupAuto && (l = Math.max(i.slidesPerViewDynamic("current", !0), 1));
                    let u = i.activeIndex < s.slidesPerGroupSkip ? 1 : l,
                        d = i.virtual && s.virtual.enabled;
                    if (s.loop) {
                        if (o && !d && s.loopPreventsSliding) return !1;
                        i.loopFix({ direction: "next" }), (i._clientLeft = i.wrapperEl.clientLeft);
                    }
                    return s.rewind && i.isEnd ? i.slideTo(0, e, t, n) : i.slideTo(i.activeIndex + u, e, t, n);
                },
                slidePrev: function (e, t, n) {
                    void 0 === e && (e = this.params.speed), void 0 === t && (t = !0);
                    let i = this,
                        { params: a, snapGrid: s, slidesGrid: o, rtlTranslate: l, enabled: u, animating: d } = i;
                    if (!u) return i;
                    let c = i.virtual && a.virtual.enabled;
                    if (a.loop) {
                        if (d && !c && a.loopPreventsSliding) return !1;
                        i.loopFix({ direction: "prev" }), (i._clientLeft = i.wrapperEl.clientLeft);
                    }
                    function p(e) {
                        return e < 0 ? -Math.floor(Math.abs(e)) : Math.floor(e);
                    }
                    let f = p(l ? i.translate : -i.translate),
                        h = s.map((e) => p(e)),
                        m = s[h.indexOf(f) - 1];
                    if (void 0 === m && a.cssMode) {
                        let g;
                        s.forEach((e, t) => {
                            f >= e && (g = t);
                        }),
                            void 0 !== g && (m = s[g > 0 ? g - 1 : g]);
                    }
                    let v = 0;
                    if (
                        (void 0 !== m &&
                            ((v = o.indexOf(m)) < 0 && (v = i.activeIndex - 1), "auto" === a.slidesPerView && 1 === a.slidesPerGroup && a.slidesPerGroupAuto && (v = Math.max((v = v - i.slidesPerViewDynamic("previous", !0) + 1), 0))),
                        a.rewind && i.isBeginning)
                    ) {
                        let $ = i.params.virtual && i.params.virtual.enabled && i.virtual ? i.virtual.slides.length - 1 : i.slides.length - 1;
                        return i.slideTo($, e, t, n);
                    }
                    return i.slideTo(v, e, t, n);
                },
                slideReset: function (e, t, n) {
                    return void 0 === e && (e = this.params.speed), void 0 === t && (t = !0), this.slideTo(this.activeIndex, e, t, n);
                },
                slideToClosest: function (e, t, n, i) {
                    void 0 === e && (e = this.params.speed), void 0 === t && (t = !0), void 0 === i && (i = 0.5);
                    let a = this.activeIndex,
                        s = Math.min(this.params.slidesPerGroupSkip, a),
                        o = s + Math.floor((a - s) / this.params.slidesPerGroup),
                        l = this.rtlTranslate ? this.translate : -this.translate;
                    if (l >= this.snapGrid[o]) {
                        let u = this.snapGrid[o];
                        l - u > (this.snapGrid[o + 1] - u) * i && (a += this.params.slidesPerGroup);
                    } else {
                        let d = this.snapGrid[o - 1];
                        l - d <= (this.snapGrid[o] - d) * i && (a -= this.params.slidesPerGroup);
                    }
                    return (a = Math.min((a = Math.max(a, 0)), this.slidesGrid.length - 1)), this.slideTo(a, e, t, n);
                },
                slideToClickedSlide: function () {
                    let e = this,
                        { params: t, slidesEl: n } = e,
                        i = "auto" === t.slidesPerView ? e.slidesPerViewDynamic() : t.slidesPerView,
                        a,
                        s = e.clickedIndex,
                        l = e.isElement ? "swiper-slide" : `.${t.slideClass}`;
                    if (t.loop) {
                        if (e.animating) return;
                        (a = parseInt(e.clickedSlide.getAttribute("data-swiper-slide-index"), 10)),
                            t.centeredSlides
                                ? s < e.loopedSlides - i / 2 || s > e.slides.length - e.loopedSlides + i / 2
                                    ? (e.loopFix(),
                                      (s = e.getSlideIndex(m(n, `${l}[data-swiper-slide-index="${a}"]`)[0])),
                                      o(() => {
                                          e.slideTo(s);
                                      }))
                                    : e.slideTo(s)
                                : s > e.slides.length - i
                                ? (e.loopFix(),
                                  (s = e.getSlideIndex(m(n, `${l}[data-swiper-slide-index="${a}"]`)[0])),
                                  o(() => {
                                      e.slideTo(s);
                                  }))
                                : e.slideTo(s);
                    } else e.slideTo(s);
                },
            },
            loop: {
                loopCreate: function (e) {
                    let { params: t, slidesEl: n } = this;
                    !t.loop ||
                        (this.virtual && this.params.virtual.enabled) ||
                        (m(n, `.${t.slideClass}, swiper-slide`).forEach((e, t) => {
                            e.setAttribute("data-swiper-slide-index", t);
                        }),
                        this.loopFix({ slideRealIndex: e, direction: t.centeredSlides ? void 0 : "next" }));
                },
                loopFix: function (e) {
                    let { slideRealIndex: t, slideTo: n = !0, direction: i, setTranslate: a, activeSlideIndex: s, byController: o, byMousewheel: l } = void 0 === e ? {} : e,
                        u = this;
                    if (!u.params.loop) return;
                    u.emit("beforeLoopFix");
                    let { slides: d, allowSlidePrev: c, allowSlideNext: p, slidesEl: f, params: h } = u;
                    if (((u.allowSlidePrev = !0), (u.allowSlideNext = !0), u.virtual && h.virtual.enabled))
                        return (
                            n &&
                                (h.centeredSlides || 0 !== u.snapIndex
                                    ? h.centeredSlides && u.snapIndex < h.slidesPerView
                                        ? u.slideTo(u.virtual.slides.length + u.snapIndex, 0, !1, !0)
                                        : u.snapIndex === u.snapGrid.length - 1 && u.slideTo(u.virtual.slidesBefore, 0, !1, !0)
                                    : u.slideTo(u.virtual.slides.length, 0, !1, !0)),
                            (u.allowSlidePrev = c),
                            (u.allowSlideNext = p),
                            void u.emit("loopFix")
                        );
                    let m = "auto" === h.slidesPerView ? u.slidesPerViewDynamic() : Math.ceil(parseFloat(h.slidesPerView, 10)),
                        g = h.loopedSlides || m;
                    g % h.slidesPerGroup != 0 && (g += h.slidesPerGroup - (g % h.slidesPerGroup)), (u.loopedSlides = g);
                    let v = [],
                        $ = [],
                        y = u.activeIndex;
                    void 0 === s ? (s = u.getSlideIndex(u.slides.filter((e) => e.classList.contains(h.slideActiveClass))[0])) : (y = s);
                    let b = "next" === i || !i,
                        _ = "prev" === i || !i,
                        w = 0,
                        x = 0;
                    if (s < g) {
                        w = Math.max(g - s, h.slidesPerGroup);
                        for (let S = 0; S < g - s; S += 1) {
                            let T = S - Math.floor(S / d.length) * d.length;
                            v.push(d.length - T - 1);
                        }
                    } else if (s > u.slides.length - 2 * g) {
                        x = Math.max(s - (u.slides.length - 2 * g), h.slidesPerGroup);
                        for (let C = 0; C < x; C += 1) {
                            let E = C - Math.floor(C / d.length) * d.length;
                            $.push(E);
                        }
                    }
                    if (
                        (_ &&
                            v.forEach((e) => {
                                (u.slides[e].swiperLoopMoveDOM = !0), f.prepend(u.slides[e]), (u.slides[e].swiperLoopMoveDOM = !1);
                            }),
                        b &&
                            $.forEach((e) => {
                                (u.slides[e].swiperLoopMoveDOM = !0), f.append(u.slides[e]), (u.slides[e].swiperLoopMoveDOM = !1);
                            }),
                        u.recalcSlides(),
                        "auto" === h.slidesPerView && u.updateSlides(),
                        h.watchSlidesProgress && u.updateSlidesOffset(),
                        n)
                    ) {
                        if (v.length > 0 && _) {
                            if (void 0 === t) {
                                let D = u.slidesGrid[y],
                                    k = u.slidesGrid[y + w] - D;
                                l ? u.setTranslate(u.translate - k) : (u.slideTo(y + w, 0, !1, !0), a && ((u.touches[u.isHorizontal() ? "startX" : "startY"] += k), (u.touchEventsData.currentTranslate = u.translate)));
                            } else a && (u.slideToLoop(t, 0, !1, !0), (u.touchEventsData.currentTranslate = u.translate));
                        } else if ($.length > 0 && b) {
                            if (void 0 === t) {
                                let L = u.slidesGrid[y],
                                    A = u.slidesGrid[y - x] - L;
                                l ? u.setTranslate(u.translate - A) : (u.slideTo(y - x, 0, !1, !0), a && ((u.touches[u.isHorizontal() ? "startX" : "startY"] += A), (u.touchEventsData.currentTranslate = u.translate)));
                            } else u.slideToLoop(t, 0, !1, !0);
                        }
                    }
                    if (((u.allowSlidePrev = c), (u.allowSlideNext = p), u.controller && u.controller.control && !o)) {
                        let I = { slideRealIndex: t, direction: i, setTranslate: a, activeSlideIndex: s, byController: !0 };
                        Array.isArray(u.controller.control)
                            ? u.controller.control.forEach((e) => {
                                  !e.destroyed && e.params.loop && e.loopFix({ ...I, slideTo: e.params.slidesPerView === h.slidesPerView && n });
                              })
                            : u.controller.control instanceof u.constructor && u.controller.control.params.loop && u.controller.control.loopFix({ ...I, slideTo: u.controller.control.params.slidesPerView === h.slidesPerView && n });
                    }
                    u.emit("loopFix");
                },
                loopDestroy: function () {
                    let { params: e, slidesEl: t } = this;
                    if (!e.loop || (this.virtual && this.params.virtual.enabled)) return;
                    this.recalcSlides();
                    let n = [];
                    this.slides.forEach((e) => {
                        let t = void 0 === e.swiperSlideIndex ? 1 * e.getAttribute("data-swiper-slide-index") : e.swiperSlideIndex;
                        n[t] = e;
                    }),
                        this.slides.forEach((e) => {
                            e.removeAttribute("data-swiper-slide-index");
                        }),
                        n.forEach((e) => {
                            t.append(e);
                        }),
                        this.recalcSlides(),
                        this.slideTo(this.realIndex, 0);
                },
            },
            grabCursor: {
                setGrabCursor: function (e) {
                    let t = this;
                    if (!t.params.simulateTouch || (t.params.watchOverflow && t.isLocked) || t.params.cssMode) return;
                    let n = "container" === t.params.touchEventsTarget ? t.el : t.wrapperEl;
                    t.isElement && (t.__preventObserver__ = !0),
                        (n.style.cursor = "move"),
                        (n.style.cursor = e ? "grabbing" : "grab"),
                        t.isElement &&
                            requestAnimationFrame(() => {
                                t.__preventObserver__ = !1;
                            });
                },
                unsetGrabCursor: function () {
                    let e = this;
                    (e.params.watchOverflow && e.isLocked) ||
                        e.params.cssMode ||
                        (e.isElement && (e.__preventObserver__ = !0),
                        (e["container" === e.params.touchEventsTarget ? "el" : "wrapperEl"].style.cursor = ""),
                        e.isElement &&
                            requestAnimationFrame(() => {
                                e.__preventObserver__ = !1;
                            }));
                },
            },
            events: {
                attachEvents: function () {
                    let e = this,
                        t = i(),
                        { params: n } = e;
                    (e.onTouchStart = A.bind(e)),
                        (e.onTouchMove = I.bind(e)),
                        (e.onTouchEnd = P.bind(e)),
                        n.cssMode && (e.onScroll = O.bind(e)),
                        (e.onClick = M.bind(e)),
                        (e.onLoad = R.bind(e)),
                        z || (t.addEventListener("touchstart", F), (z = !0)),
                        j(e, "on");
                },
                detachEvents: function () {
                    j(this, "off");
                },
            },
            breakpoints: {
                setBreakpoint: function () {
                    let e = this,
                        { realIndex: t, initialized: n, params: i, el: a } = e,
                        s = i.breakpoints;
                    if (!s || (s && 0 === Object.keys(s).length)) return;
                    let o = e.getBreakpoint(s, e.params.breakpointsBase, e.el);
                    if (!o || e.currentBreakpoint === o) return;
                    let l = (o in s ? s[o] : void 0) || e.originalParams,
                        u = H(e, i),
                        d = H(e, l),
                        p = i.enabled;
                    u && !d
                        ? (a.classList.remove(`${i.containerModifierClass}grid`, `${i.containerModifierClass}grid-column`), e.emitContainerClasses())
                        : !u &&
                          d &&
                          (a.classList.add(`${i.containerModifierClass}grid`),
                          ((l.grid.fill && "column" === l.grid.fill) || (!l.grid.fill && "column" === i.grid.fill)) && a.classList.add(`${i.containerModifierClass}grid-column`),
                          e.emitContainerClasses()),
                        ["navigation", "pagination", "scrollbar"].forEach((t) => {
                            if (void 0 === l[t]) return;
                            let n = i[t] && i[t].enabled,
                                a = l[t] && l[t].enabled;
                            n && !a && e[t].disable(), !n && a && e[t].enable();
                        });
                    let f = l.direction && l.direction !== i.direction,
                        h = i.loop && (l.slidesPerView !== i.slidesPerView || f);
                    f && n && e.changeDirection(), c(e.params, l);
                    let m = e.params.enabled;
                    Object.assign(e, { allowTouchMove: e.params.allowTouchMove, allowSlideNext: e.params.allowSlideNext, allowSlidePrev: e.params.allowSlidePrev }),
                        p && !m ? e.disable() : !p && m && e.enable(),
                        (e.currentBreakpoint = o),
                        e.emit("_beforeBreakpoint", l),
                        h && n && (e.loopDestroy(), e.loopCreate(t), e.updateSlides()),
                        e.emit("breakpoint", l);
                },
                getBreakpoint: function (e, t, n) {
                    if ((void 0 === t && (t = "window"), !e || ("container" === t && !n))) return;
                    let i = !1,
                        a = s(),
                        o = "window" === t ? a.innerHeight : n.clientHeight,
                        l = Object.keys(e).map((e) => {
                            if ("string" == typeof e && 0 === e.indexOf("@")) {
                                let t = parseFloat(e.substr(1));
                                return { value: o * t, point: e };
                            }
                            return { value: e, point: e };
                        });
                    l.sort((e, t) => parseInt(e.value, 10) - parseInt(t.value, 10));
                    for (let u = 0; u < l.length; u += 1) {
                        let { point: d, value: c } = l[u];
                        "window" === t ? a.matchMedia(`(min-width: ${c}px)`).matches && (i = d) : c <= n.clientWidth && (i = d);
                    }
                    return i || "max";
                },
            },
            checkOverflow: {
                checkOverflow: function () {
                    let e = this,
                        { isLocked: t, params: n } = e,
                        { slidesOffsetBefore: i } = n;
                    if (i) {
                        let a = e.slides.length - 1,
                            s = e.slidesGrid[a] + e.slidesSizesGrid[a] + 2 * i;
                        e.isLocked = e.size > s;
                    } else e.isLocked = 1 === e.snapGrid.length;
                    !0 === n.allowSlideNext && (e.allowSlideNext = !e.isLocked),
                        !0 === n.allowSlidePrev && (e.allowSlidePrev = !e.isLocked),
                        t && t !== e.isLocked && (e.isEnd = !1),
                        t !== e.isLocked && e.emit(e.isLocked ? "lock" : "unlock");
                },
            },
            classes: {
                addClasses: function () {
                    let { classNames: e, params: t, rtl: n, el: i, device: a } = this,
                        s = (function (e, t) {
                            let n = [];
                            return (
                                e.forEach((e) => {
                                    "object" == typeof e
                                        ? Object.keys(e).forEach((i) => {
                                              e[i] && n.push(t + i);
                                          })
                                        : "string" == typeof e && n.push(t + e);
                                }),
                                n
                            );
                        })(
                            [
                                "initialized",
                                t.direction,
                                { "free-mode": this.params.freeMode && t.freeMode.enabled },
                                { autoheight: t.autoHeight },
                                { rtl: n },
                                { grid: t.grid && t.grid.rows > 1 },
                                { "grid-column": t.grid && t.grid.rows > 1 && "column" === t.grid.fill },
                                { android: a.android },
                                { ios: a.ios },
                                { "css-mode": t.cssMode },
                                { centered: t.cssMode && t.centeredSlides },
                                { "watch-progress": t.watchSlidesProgress },
                            ],
                            t.containerModifierClass
                        );
                    e.push(...s), i.classList.add(...e), this.emitContainerClasses();
                },
                removeClasses: function () {
                    let { el: e, classNames: t } = this;
                    e.classList.remove(...t), this.emitContainerClasses();
                },
            },
        },
        W = {};
    class G {
        constructor() {
            let e, t;
            for (var n, a = arguments.length, o = Array(a), l = 0; l < a; l++) o[l] = arguments[l];
            1 === o.length && o[0].constructor && "Object" === Object.prototype.toString.call(o[0]).slice(8, -1) ? (t = o[0]) : ([e, t] = o), t || (t = {}), (t = c({}, t)), e && !t.el && (t.el = e);
            let u = i();
            if (t.el && "string" == typeof t.el && u.querySelectorAll(t.el).length > 1) {
                let d = [];
                return (
                    u.querySelectorAll(t.el).forEach((e) => {
                        let n = c({}, t, { el: e });
                        d.push(new G(n));
                    }),
                    d
                );
            }
            let p = this;
            (p.__swiper__ = !0),
                (p.support = C()),
                (p.device =
                    ((n = { userAgent: t.userAgent }),
                    S ||
                        (S = (function (e) {
                            let { userAgent: t } = void 0 === e ? {} : e,
                                n = C(),
                                i = s(),
                                a = i.navigator.platform,
                                o = t || i.navigator.userAgent,
                                l = { ios: !1, android: !1 },
                                u = i.screen.width,
                                d = i.screen.height,
                                c = o.match(/(Android);?[\s\/]+([\d.]+)?/),
                                p = o.match(/(iPad).*OS\s([\d_]+)/),
                                f = o.match(/(iPod)(.*OS\s([\d_]+))?/),
                                h = !p && o.match(/(iPhone\sOS|iOS)\s([\d_]+)/),
                                m = "MacIntel" === a;
                            return (
                                !p &&
                                    m &&
                                    n.touch &&
                                    ["1024x1366", "1366x1024", "834x1194", "1194x834", "834x1112", "1112x834", "768x1024", "1024x768", "820x1180", "1180x820", "810x1080", "1080x810"].indexOf(`${u}x${d}`) >= 0 &&
                                    ((p = o.match(/(Version)\/([\d.]+)/)) || (p = [0, 1, "13_0_0"]), (m = !1)),
                                c && "Win32" !== a && ((l.os = "android"), (l.android = !0)),
                                (p || h || f) && ((l.os = "ios"), (l.ios = !0)),
                                l
                            );
                        })(n)),
                    S)),
                (p.browser =
                    (T ||
                        (T = (function () {
                            let e = s(),
                                t = !1;
                            function n() {
                                let t = e.navigator.userAgent.toLowerCase();
                                return t.indexOf("safari") >= 0 && 0 > t.indexOf("chrome") && 0 > t.indexOf("android");
                            }
                            if (n()) {
                                let i = String(e.navigator.userAgent);
                                if (i.includes("Version/")) {
                                    let [a, o] = i
                                        .split("Version/")[1]
                                        .split(" ")[0]
                                        .split(".")
                                        .map((e) => Number(e));
                                    t = a < 16 || (16 === a && o < 2);
                                }
                            }
                            return { isSafari: t || n(), needPerspectiveFix: t, isWebView: /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(e.navigator.userAgent) };
                        })()),
                    T)),
                (p.eventsListeners = {}),
                (p.eventsAnyListeners = []),
                (p.modules = [...p.__modules__]),
                t.modules && Array.isArray(t.modules) && p.modules.push(...t.modules);
            let f = {};
            p.modules.forEach((e) => {
                var n, i;
                e({
                    params: t,
                    swiper: p,
                    extendParams:
                        ((n = t),
                        (i = f),
                        function (e) {
                            void 0 === e && (e = {});
                            let t = Object.keys(e)[0],
                                a = e[t];
                            "object" == typeof a &&
                                null !== a &&
                                (["navigation", "pagination", "scrollbar"].indexOf(t) >= 0 && !0 === n[t] && (n[t] = { auto: !0 }),
                                t in n && "enabled" in a && (!0 === n[t] && (n[t] = { enabled: !0 }), "object" != typeof n[t] || "enabled" in n[t] || (n[t].enabled = !0), n[t] || (n[t] = { enabled: !1 }))),
                                c(i, e);
                        }),
                    on: p.on.bind(p),
                    once: p.once.bind(p),
                    off: p.off.bind(p),
                    emit: p.emit.bind(p),
                });
            });
            let h = c({}, B, f);
            return (
                (p.params = c({}, h, W, t)),
                (p.originalParams = c({}, p.params)),
                (p.passedParams = c({}, t)),
                p.params &&
                    p.params.on &&
                    Object.keys(p.params.on).forEach((e) => {
                        p.on(e, p.params.on[e]);
                    }),
                p.params && p.params.onAny && p.onAny(p.params.onAny),
                Object.assign(p, {
                    enabled: p.params.enabled,
                    el: e,
                    classNames: [],
                    slides: [],
                    slidesGrid: [],
                    snapGrid: [],
                    slidesSizesGrid: [],
                    isHorizontal: () => "horizontal" === p.params.direction,
                    isVertical: () => "vertical" === p.params.direction,
                    activeIndex: 0,
                    realIndex: 0,
                    isBeginning: !0,
                    isEnd: !1,
                    translate: 0,
                    previousTranslate: 0,
                    progress: 0,
                    velocity: 0,
                    animating: !1,
                    cssOverflowAdjustment() {
                        return 8388608 * Math.trunc(this.translate / 8388608);
                    },
                    allowSlideNext: p.params.allowSlideNext,
                    allowSlidePrev: p.params.allowSlidePrev,
                    touchEventsData: {
                        isTouched: void 0,
                        isMoved: void 0,
                        allowTouchCallbacks: void 0,
                        touchStartTime: void 0,
                        isScrolling: void 0,
                        currentTranslate: void 0,
                        startTranslate: void 0,
                        allowThresholdMove: void 0,
                        focusableElements: p.params.focusableElements,
                        lastClickTime: 0,
                        clickTimeout: void 0,
                        velocities: [],
                        allowMomentumBounce: void 0,
                        startMoving: void 0,
                        evCache: [],
                    },
                    allowClick: !0,
                    allowTouchMove: p.params.allowTouchMove,
                    touches: { startX: 0, startY: 0, currentX: 0, currentY: 0, diff: 0 },
                    imagesToLoad: [],
                    imagesLoaded: 0,
                }),
                p.emit("_swiper"),
                p.params.init && p.init(),
                p
            );
        }
        getSlideIndex(e) {
            let { slidesEl: t, params: n } = this,
                i = y(m(t, `.${n.slideClass}, swiper-slide`)[0]);
            return y(e) - i;
        }
        getSlideIndexByData(e) {
            return this.getSlideIndex(this.slides.filter((t) => 1 * t.getAttribute("data-swiper-slide-index") === e)[0]);
        }
        recalcSlides() {
            let { slidesEl: e, params: t } = this;
            this.slides = m(e, `.${t.slideClass}, swiper-slide`);
        }
        enable() {
            let e = this;
            e.enabled || ((e.enabled = !0), e.params.grabCursor && e.setGrabCursor(), e.emit("enable"));
        }
        disable() {
            let e = this;
            e.enabled && ((e.enabled = !1), e.params.grabCursor && e.unsetGrabCursor(), e.emit("disable"));
        }
        setProgress(e, t) {
            e = Math.min(Math.max(e, 0), 1);
            let n = this.minTranslate(),
                i = (this.maxTranslate() - n) * e + n;
            this.translateTo(i, void 0 === t ? 0 : t), this.updateActiveIndex(), this.updateSlidesClasses();
        }
        emitContainerClasses() {
            let e = this;
            if (!e.params._emitClasses || !e.el) return;
            let t = e.el.className.split(" ").filter((t) => 0 === t.indexOf("swiper") || 0 === t.indexOf(e.params.containerModifierClass));
            e.emit("_containerClasses", t.join(" "));
        }
        getSlideClasses(e) {
            let t = this;
            return t.destroyed
                ? ""
                : e.className
                      .split(" ")
                      .filter((e) => 0 === e.indexOf("swiper-slide") || 0 === e.indexOf(t.params.slideClass))
                      .join(" ");
        }
        emitSlidesClasses() {
            let e = this;
            if (!e.params._emitClasses || !e.el) return;
            let t = [];
            e.slides.forEach((n) => {
                let i = e.getSlideClasses(n);
                t.push({ slideEl: n, classNames: i }), e.emit("_slideClass", n, i);
            }),
                e.emit("_slideClasses", t);
        }
        slidesPerViewDynamic(e, t) {
            void 0 === e && (e = "current"), void 0 === t && (t = !1);
            let { params: n, slides: i, slidesGrid: a, slidesSizesGrid: s, size: o, activeIndex: l } = this,
                u = 1;
            if (n.centeredSlides) {
                let d,
                    c = i[l] ? i[l].swiperSlideSize : 0;
                for (let p = l + 1; p < i.length; p += 1) i[p] && !d && ((c += i[p].swiperSlideSize), (u += 1), c > o && (d = !0));
                for (let f = l - 1; f >= 0; f -= 1) i[f] && !d && ((c += i[f].swiperSlideSize), (u += 1), c > o && (d = !0));
            } else if ("current" === e) for (let h = l + 1; h < i.length; h += 1) (t ? a[h] + s[h] - a[l] < o : a[h] - a[l] < o) && (u += 1);
            else for (let m = l - 1; m >= 0; m -= 1) a[l] - a[m] < o && (u += 1);
            return u;
        }
        update() {
            let e = this;
            if (!e || e.destroyed) return;
            let { snapGrid: t, params: n } = e;
            function i() {
                let t = e.rtlTranslate ? -1 * e.translate : e.translate,
                    n = Math.min(Math.max(t, e.maxTranslate()), e.minTranslate());
                e.setTranslate(n), e.updateActiveIndex(), e.updateSlidesClasses();
            }
            let a;
            if (
                (n.breakpoints && e.setBreakpoint(),
                [...e.el.querySelectorAll('[loading="lazy"]')].forEach((t) => {
                    t.complete && E(e, t);
                }),
                e.updateSize(),
                e.updateSlides(),
                e.updateProgress(),
                e.updateSlidesClasses(),
                n.freeMode && n.freeMode.enabled && !n.cssMode)
            )
                i(), n.autoHeight && e.updateAutoHeight();
            else {
                if (("auto" === n.slidesPerView || n.slidesPerView > 1) && e.isEnd && !n.centeredSlides) {
                    let s = e.virtual && n.virtual.enabled ? e.virtual.slides : e.slides;
                    a = e.slideTo(s.length - 1, 0, !1, !0);
                } else a = e.slideTo(e.activeIndex, 0, !1, !0);
                a || i();
            }
            n.watchOverflow && t !== e.snapGrid && e.checkOverflow(), e.emit("update");
        }
        changeDirection(e, t) {
            void 0 === t && (t = !0);
            let n = this,
                i = n.params.direction;
            return (
                e || (e = "horizontal" === i ? "vertical" : "horizontal"),
                e === i ||
                    ("horizontal" !== e && "vertical" !== e) ||
                    (n.el.classList.remove(`${n.params.containerModifierClass}${i}`),
                    n.el.classList.add(`${n.params.containerModifierClass}${e}`),
                    n.emitContainerClasses(),
                    (n.params.direction = e),
                    n.slides.forEach((t) => {
                        "vertical" === e ? (t.style.width = "") : (t.style.height = "");
                    }),
                    n.emit("changeDirection"),
                    t && n.update()),
                n
            );
        }
        changeLanguageDirection(e) {
            let t = this;
            (t.rtl && "rtl" === e) ||
                (!t.rtl && "ltr" === e) ||
                ((t.rtl = "rtl" === e),
                (t.rtlTranslate = "horizontal" === t.params.direction && t.rtl),
                t.rtl ? (t.el.classList.add(`${t.params.containerModifierClass}rtl`), (t.el.dir = "rtl")) : (t.el.classList.remove(`${t.params.containerModifierClass}rtl`), (t.el.dir = "ltr")),
                t.update());
        }
        mount(e) {
            let t = this;
            if (t.mounted) return !0;
            let n = e || t.params.el;
            if (("string" == typeof n && (n = document.querySelector(n)), !n)) return !1;
            (n.swiper = t), n.parentNode && n.parentNode.host && "SWIPER-CONTAINER" === n.parentNode.host.nodeName && (t.isElement = !0);
            let i = () => `.${(t.params.wrapperClass || "").trim().split(" ").join(".")}`,
                a = n && n.shadowRoot && n.shadowRoot.querySelector ? n.shadowRoot.querySelector(i()) : m(n, i())[0];
            return (
                !a &&
                    t.params.createElements &&
                    ((a = g("div", t.params.wrapperClass)),
                    n.append(a),
                    m(n, `.${t.params.slideClass}`).forEach((e) => {
                        a.append(e);
                    })),
                Object.assign(t, {
                    el: n,
                    wrapperEl: a,
                    slidesEl: t.isElement && !n.parentNode.host.slideSlots ? n.parentNode.host : a,
                    hostEl: t.isElement ? n.parentNode.host : n,
                    mounted: !0,
                    rtl: "rtl" === n.dir.toLowerCase() || "rtl" === $(n, "direction"),
                    rtlTranslate: "horizontal" === t.params.direction && ("rtl" === n.dir.toLowerCase() || "rtl" === $(n, "direction")),
                    wrongRTL: "-webkit-box" === $(a, "display"),
                }),
                !0
            );
        }
        init(e) {
            let t = this;
            if (t.initialized || !1 === t.mount(e)) return t;
            t.emit("beforeInit"),
                t.params.breakpoints && t.setBreakpoint(),
                t.addClasses(),
                t.updateSize(),
                t.updateSlides(),
                t.params.watchOverflow && t.checkOverflow(),
                t.params.grabCursor && t.enabled && t.setGrabCursor(),
                t.params.loop && t.virtual && t.params.virtual.enabled
                    ? t.slideTo(t.params.initialSlide + t.virtual.slidesBefore, 0, t.params.runCallbacksOnInit, !1, !0)
                    : t.slideTo(t.params.initialSlide, 0, t.params.runCallbacksOnInit, !1, !0),
                t.params.loop && t.loopCreate(),
                t.attachEvents();
            let n = [...t.el.querySelectorAll('[loading="lazy"]')];
            return (
                t.isElement && n.push(...t.hostEl.querySelectorAll('[loading="lazy"]')),
                n.forEach((e) => {
                    e.complete
                        ? E(t, e)
                        : e.addEventListener("load", (e) => {
                              E(t, e.target);
                          });
                }),
                k(t),
                (t.initialized = !0),
                k(t),
                t.emit("init"),
                t.emit("afterInit"),
                t
            );
        }
        destroy(e, t) {
            void 0 === e && (e = !0), void 0 === t && (t = !0);
            let n = this,
                { params: i, el: a, wrapperEl: s, slides: o } = n;
            return (
                void 0 === n.params ||
                    n.destroyed ||
                    (n.emit("beforeDestroy"),
                    (n.initialized = !1),
                    n.detachEvents(),
                    i.loop && n.loopDestroy(),
                    t &&
                        (n.removeClasses(),
                        a.removeAttribute("style"),
                        s.removeAttribute("style"),
                        o &&
                            o.length &&
                            o.forEach((e) => {
                                e.classList.remove(i.slideVisibleClass, i.slideActiveClass, i.slideNextClass, i.slidePrevClass), e.removeAttribute("style"), e.removeAttribute("data-swiper-slide-index");
                            })),
                    n.emit("destroy"),
                    Object.keys(n.eventsListeners).forEach((e) => {
                        n.off(e);
                    }),
                    !1 !== e &&
                        ((n.el.swiper = null),
                        (function (e) {
                            let t = e;
                            Object.keys(t).forEach((e) => {
                                try {
                                    t[e] = null;
                                } catch (n) {}
                                try {
                                    delete t[e];
                                } catch (i) {}
                            });
                        })(n)),
                    (n.destroyed = !0)),
                null
            );
        }
        static extendDefaults(e) {
            c(W, e);
        }
        static get extendedDefaults() {
            return W;
        }
        static get defaults() {
            return B;
        }
        static installModule(e) {
            G.prototype.__modules__ || (G.prototype.__modules__ = []);
            let t = G.prototype.__modules__;
            "function" == typeof e && 0 > t.indexOf(e) && t.push(e);
        }
        static use(e) {
            return Array.isArray(e) ? (e.forEach((e) => G.installModule(e)), G) : (G.installModule(e), G);
        }
    }
    function U(e, t, n, i) {
        return (
            e.params.createElements &&
                Object.keys(i).forEach((a) => {
                    if (!n[a] && !0 === n.auto) {
                        let s = m(e.el, `.${i[a]}`)[0];
                        s || (((s = g("div", i[a])).className = i[a]), e.el.append(s)), (n[a] = s), (t[a] = s);
                    }
                }),
            n
        );
    }
    function V(e) {
        return (
            void 0 === e && (e = ""),
            `.${e
                .trim()
                .replace(/([\.:!+\/])/g, "\\$1")
                .replace(/ /g, ".")}`
        );
    }
    function X(e) {
        let { params: t, slidesEl: n } = this;
        t.loop && this.loopDestroy();
        let i = (e) => {
            if ("string" == typeof e) {
                let t = document.createElement("div");
                (t.innerHTML = e), n.append(t.children[0]), (t.innerHTML = "");
            } else n.append(e);
        };
        if ("object" == typeof e && "length" in e) for (let a = 0; a < e.length; a += 1) e[a] && i(e[a]);
        else i(e);
        this.recalcSlides(), t.loop && this.loopCreate(), (t.observer && !this.isElement) || this.update();
    }
    function Y(e) {
        let { params: t, activeIndex: n, slidesEl: i } = this;
        t.loop && this.loopDestroy();
        let a = n + 1,
            s = (e) => {
                if ("string" == typeof e) {
                    let t = document.createElement("div");
                    (t.innerHTML = e), i.prepend(t.children[0]), (t.innerHTML = "");
                } else i.prepend(e);
            };
        if ("object" == typeof e && "length" in e) {
            for (let o = 0; o < e.length; o += 1) e[o] && s(e[o]);
            a = n + e.length;
        } else s(e);
        this.recalcSlides(), t.loop && this.loopCreate(), (t.observer && !this.isElement) || this.update(), this.slideTo(a, 0, !1);
    }
    function J(e, t) {
        let { params: n, activeIndex: i, slidesEl: a } = this,
            s = i;
        n.loop && ((s -= this.loopedSlides), this.loopDestroy(), this.recalcSlides());
        let o = this.slides.length;
        if (e <= 0) return void this.prependSlide(t);
        if (e >= o) return void this.appendSlide(t);
        let l = s > e ? s + 1 : s,
            u = [];
        for (let d = o - 1; d >= e; d -= 1) {
            let c = this.slides[d];
            c.remove(), u.unshift(c);
        }
        if ("object" == typeof t && "length" in t) {
            for (let p = 0; p < t.length; p += 1) t[p] && a.append(t[p]);
            l = s > e ? s + t.length : s;
        } else a.append(t);
        for (let f = 0; f < u.length; f += 1) a.append(u[f]);
        this.recalcSlides(), n.loop && this.loopCreate(), (n.observer && !this.isElement) || this.update(), n.loop ? this.slideTo(l + this.loopedSlides, 0, !1) : this.slideTo(l, 0, !1);
    }
    function K(e) {
        let { params: t, activeIndex: n } = this,
            i = n;
        t.loop && ((i -= this.loopedSlides), this.loopDestroy());
        let a,
            s = i;
        if ("object" == typeof e && "length" in e) {
            for (let o = 0; o < e.length; o += 1) (a = e[o]), this.slides[a] && this.slides[a].remove(), a < s && (s -= 1);
            s = Math.max(s, 0);
        } else (a = e), this.slides[a] && this.slides[a].remove(), a < s && (s -= 1), (s = Math.max(s, 0));
        this.recalcSlides(), t.loop && this.loopCreate(), (t.observer && !this.isElement) || this.update(), t.loop ? this.slideTo(s + this.loopedSlides, 0, !1) : this.slideTo(s, 0, !1);
    }
    function Z() {
        let e = [];
        for (let t = 0; t < this.slides.length; t += 1) e.push(t);
        this.removeSlide(e);
    }
    function Q(e) {
        let { effect: t, swiper: n, on: i, setTranslate: a, setTransition: s, overwriteParams: o, perspective: l, recreateShadows: u, getEffectParams: d } = e,
            c;
        i("beforeInit", () => {
            if (n.params.effect !== t) return;
            n.classNames.push(`${n.params.containerModifierClass}${t}`), l && l() && n.classNames.push(`${n.params.containerModifierClass}3d`);
            let e = o ? o() : {};
            Object.assign(n.params, e), Object.assign(n.originalParams, e);
        }),
            i("setTranslate", () => {
                n.params.effect === t && a();
            }),
            i("setTransition", (e, i) => {
                n.params.effect === t && s(i);
            }),
            i("transitionEnd", () => {
                n.params.effect === t &&
                    u &&
                    d &&
                    d().slideShadows &&
                    (n.slides.forEach((e) => {
                        e.querySelectorAll(".swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left").forEach((e) => e.remove());
                    }),
                    u());
            }),
            i("virtualUpdate", () => {
                n.params.effect === t &&
                    (n.slides.length || (c = !0),
                    requestAnimationFrame(() => {
                        c && n.slides && n.slides.length && (a(), (c = !1));
                    }));
            });
    }
    function ee(e, t) {
        let n = h(t);
        return n !== t && ((n.style.backfaceVisibility = "hidden"), (n.style["-webkit-backface-visibility"] = "hidden")), n;
    }
    function et(e) {
        let { swiper: t, duration: n, transformElements: i, allSlides: a } = e,
            { activeIndex: s } = t;
        if (t.params.virtualTranslate && 0 !== n) {
            let o,
                l = !1;
            (o = a
                ? i
                : i.filter((e) => {
                      var n;
                      let i = e.classList.contains("swiper-slide-transform") ? ((n = e).parentElement ? n.parentElement : t.slides.filter((e) => e.shadowRoot && e.shadowRoot === n.parentNode)[0]) : e;
                      return t.getSlideIndex(i) === s;
                  })).forEach((e) => {
                _(e, () => {
                    if (l || !t || t.destroyed) return;
                    (l = !0), (t.animating = !1);
                    let e = new window.CustomEvent("transitionend", { bubbles: !0, cancelable: !0 });
                    t.wrapperEl.dispatchEvent(e);
                });
            });
        }
    }
    function en(e, t, n) {
        let i = `swiper-slide-shadow${n ? `-${n}` : ""}${e ? ` swiper-slide-shadow-${e}` : ""}`,
            a = h(t),
            s = a.querySelector(`.${i.split(" ").join(".")}`);
        return s || ((s = g("div", i.split(" "))), a.append(s)), s;
    }
    Object.keys(q).forEach((e) => {
        Object.keys(q[e]).forEach((t) => {
            G.prototype[t] = q[e][t];
        });
    }),
        G.use([
            function (e) {
                let { swiper: t, on: n, emit: i } = e,
                    a = s(),
                    o = null,
                    l = null,
                    u = () => {
                        t && !t.destroyed && t.initialized && (i("beforeResize"), i("resize"));
                    },
                    d = () => {
                        t && !t.destroyed && t.initialized && i("orientationchange");
                    };
                n("init", () => {
                    t.params.resizeObserver && void 0 !== a.ResizeObserver
                        ? t &&
                          !t.destroyed &&
                          t.initialized &&
                          (o = new ResizeObserver((e) => {
                              l = a.requestAnimationFrame(() => {
                                  let { width: n, height: i } = t,
                                      a = n,
                                      s = i;
                                  e.forEach((e) => {
                                      let { contentBoxSize: n, contentRect: i, target: o } = e;
                                      (o && o !== t.el) || ((a = i ? i.width : (n[0] || n).inlineSize), (s = i ? i.height : (n[0] || n).blockSize));
                                  }),
                                      (a === n && s === i) || u();
                              });
                          })).observe(t.el)
                        : (a.addEventListener("resize", u), a.addEventListener("orientationchange", d));
                }),
                    n("destroy", () => {
                        l && a.cancelAnimationFrame(l), o && o.unobserve && t.el && (o.unobserve(t.el), (o = null)), a.removeEventListener("resize", u), a.removeEventListener("orientationchange", d);
                    });
            },
            function (e) {
                let { swiper: t, extendParams: n, on: i, emit: a } = e,
                    o = [],
                    l = s(),
                    u = function (e, n) {
                        void 0 === n && (n = {});
                        let i = new (l.MutationObserver || l.WebkitMutationObserver)((e) => {
                            if (t.__preventObserver__) return;
                            if (1 === e.length) return void a("observerUpdate", e[0]);
                            let n = function () {
                                a("observerUpdate", e[0]);
                            };
                            l.requestAnimationFrame ? l.requestAnimationFrame(n) : l.setTimeout(n, 0);
                        });
                        i.observe(e, { attributes: void 0 === n.attributes || n.attributes, childList: void 0 === n.childList || n.childList, characterData: void 0 === n.characterData || n.characterData }), o.push(i);
                    };
                n({ observer: !1, observeParents: !1, observeSlideChildren: !1 }),
                    i("init", () => {
                        if (t.params.observer) {
                            if (t.params.observeParents) {
                                let e = b(t.hostEl);
                                for (let n = 0; n < e.length; n += 1) u(e[n]);
                            }
                            u(t.hostEl, { childList: t.params.observeSlideChildren }), u(t.wrapperEl, { attributes: !1 });
                        }
                    }),
                    i("destroy", () => {
                        o.forEach((e) => {
                            e.disconnect();
                        }),
                            o.splice(0, o.length);
                    });
            },
        ]);
    let ei = [
        function (e) {
            let t,
                { swiper: n, extendParams: a, on: s, emit: o } = e;
            a({ virtual: { enabled: !1, slides: [], cache: !0, renderSlide: null, renderExternal: null, renderExternalUpdate: !0, addSlidesBefore: 0, addSlidesAfter: 0 } });
            let l = i();
            n.virtual = { cache: {}, from: void 0, to: void 0, slides: [], offset: 0, slidesGrid: [] };
            let u = l.createElement("div");
            function d(e, t) {
                let i = n.params.virtual;
                if (i.cache && n.virtual.cache[t]) return n.virtual.cache[t];
                let a;
                return (
                    i.renderSlide ? "string" == typeof (a = i.renderSlide.call(n, e, t)) && ((u.innerHTML = a), (a = u.children[0])) : (a = n.isElement ? g("swiper-slide") : g("div", n.params.slideClass)),
                    a.setAttribute("data-swiper-slide-index", t),
                    i.renderSlide || (a.innerHTML = e),
                    i.cache && (n.virtual.cache[t] = a),
                    a
                );
            }
            function c(e) {
                let { slidesPerView: t, slidesPerGroup: i, centeredSlides: a, loop: s } = n.params,
                    { addSlidesBefore: l, addSlidesAfter: u } = n.params.virtual,
                    { from: c, to: p, slides: f, slidesGrid: h, offset: g } = n.virtual;
                n.params.cssMode || n.updateActiveIndex();
                let v = n.activeIndex || 0,
                    $,
                    y,
                    b;
                ($ = n.rtlTranslate ? "right" : n.isHorizontal() ? "left" : "top"), a ? ((y = Math.floor(t / 2) + i + u), (b = Math.floor(t / 2) + i + l)) : ((y = t + (i - 1) + u), (b = (s ? t : i) + l));
                let _ = v - b,
                    w = v + y;
                s || ((_ = Math.max(_, 0)), (w = Math.min(w, f.length - 1)));
                let x = (n.slidesGrid[_] || 0) - (n.slidesGrid[0] || 0);
                function S() {
                    n.updateSlides(), n.updateProgress(), n.updateSlidesClasses(), o("virtualUpdate");
                }
                if (
                    (s && v >= b ? ((_ -= b), a || (x += n.slidesGrid[0])) : s && v < b && ((_ = -b), a && (x += n.slidesGrid[0])),
                    Object.assign(n.virtual, { from: _, to: w, offset: x, slidesGrid: n.slidesGrid, slidesBefore: b, slidesAfter: y }),
                    c === _ && p === w && !e)
                )
                    return (
                        n.slidesGrid !== h &&
                            x !== g &&
                            n.slides.forEach((e) => {
                                e.style[$] = x - Math.abs(n.cssOverflowAdjustment()) + "px";
                            }),
                        n.updateProgress(),
                        void o("virtualUpdate")
                    );
                if (n.params.virtual.renderExternal)
                    return (
                        n.params.virtual.renderExternal.call(n, {
                            offset: x,
                            from: _,
                            to: w,
                            slides: (function () {
                                let e = [];
                                for (let t = _; t <= w; t += 1) e.push(f[t]);
                                return e;
                            })(),
                        }),
                        void (n.params.virtual.renderExternalUpdate ? S() : o("virtualUpdate"))
                    );
                let T = [],
                    C = [],
                    E = (e) => {
                        let t = e;
                        return e < 0 ? (t = f.length + e) : t >= f.length && (t -= f.length), t;
                    };
                if (e)
                    n.slidesEl.querySelectorAll(`.${n.params.slideClass}, swiper-slide`).forEach((e) => {
                        e.remove();
                    });
                else
                    for (let D = c; D <= p; D += 1)
                        if (D < _ || D > w) {
                            let k = E(D);
                            n.slidesEl.querySelectorAll(`.${n.params.slideClass}[data-swiper-slide-index="${k}"], swiper-slide[data-swiper-slide-index="${k}"]`).forEach((e) => {
                                e.remove();
                            });
                        }
                let L = s ? -f.length : 0,
                    A = s ? 2 * f.length : f.length;
                for (let I = L; I < A; I += 1)
                    if (I >= _ && I <= w) {
                        let P = E(I);
                        void 0 === p || e ? C.push(P) : (I > p && C.push(P), I < c && T.push(P));
                    }
                if (
                    (C.forEach((e) => {
                        n.slidesEl.append(d(f[e], e));
                    }),
                    s)
                )
                    for (let N = T.length - 1; N >= 0; N -= 1) {
                        let M = T[N];
                        n.slidesEl.prepend(d(f[M], M));
                    }
                else
                    T.sort((e, t) => t - e),
                        T.forEach((e) => {
                            n.slidesEl.prepend(d(f[e], e));
                        });
                m(n.slidesEl, ".swiper-slide, swiper-slide").forEach((e) => {
                    e.style[$] = x - Math.abs(n.cssOverflowAdjustment()) + "px";
                }),
                    S();
            }
            s("beforeInit", () => {
                if (!n.params.virtual.enabled) return;
                let e;
                if (void 0 === n.passedParams.virtual.slides) {
                    let t = [...n.slidesEl.children].filter((e) => e.matches(`.${n.params.slideClass}, swiper-slide`));
                    t &&
                        t.length &&
                        ((n.virtual.slides = [...t]),
                        (e = !0),
                        t.forEach((e, t) => {
                            e.setAttribute("data-swiper-slide-index", t), (n.virtual.cache[t] = e), e.remove();
                        }));
                }
                e || (n.virtual.slides = n.params.virtual.slides),
                    n.classNames.push(`${n.params.containerModifierClass}virtual`),
                    (n.params.watchSlidesProgress = !0),
                    (n.originalParams.watchSlidesProgress = !0),
                    n.params.initialSlide || c();
            }),
                s("setTranslate", () => {
                    n.params.virtual.enabled &&
                        (n.params.cssMode && !n._immediateVirtual
                            ? (clearTimeout(t),
                              (t = setTimeout(() => {
                                  c();
                              }, 100)))
                            : c());
                }),
                s("init update resize", () => {
                    n.params.virtual.enabled && n.params.cssMode && p(n.wrapperEl, "--swiper-virtual-size", `${n.virtualSize}px`);
                }),
                Object.assign(n.virtual, {
                    appendSlide: function (e) {
                        if ("object" == typeof e && "length" in e) for (let t = 0; t < e.length; t += 1) e[t] && n.virtual.slides.push(e[t]);
                        else n.virtual.slides.push(e);
                        c(!0);
                    },
                    prependSlide: function (e) {
                        let t = n.activeIndex,
                            i = t + 1,
                            a = 1;
                        if (Array.isArray(e)) {
                            for (let s = 0; s < e.length; s += 1) e[s] && n.virtual.slides.unshift(e[s]);
                            (i = t + e.length), (a = e.length);
                        } else n.virtual.slides.unshift(e);
                        if (n.params.virtual.cache) {
                            let o = n.virtual.cache,
                                l = {};
                            Object.keys(o).forEach((e) => {
                                let t = o[e],
                                    n = t.getAttribute("data-swiper-slide-index");
                                n && t.setAttribute("data-swiper-slide-index", parseInt(n, 10) + a), (l[parseInt(e, 10) + a] = t);
                            }),
                                (n.virtual.cache = l);
                        }
                        c(!0), n.slideTo(i, 0);
                    },
                    removeSlide: function (e) {
                        if (null == e) return;
                        let t = n.activeIndex;
                        if (Array.isArray(e)) for (let i = e.length - 1; i >= 0; i -= 1) n.virtual.slides.splice(e[i], 1), n.params.virtual.cache && delete n.virtual.cache[e[i]], e[i] < t && (t -= 1), (t = Math.max(t, 0));
                        else n.virtual.slides.splice(e, 1), n.params.virtual.cache && delete n.virtual.cache[e], e < t && (t -= 1), (t = Math.max(t, 0));
                        c(!0), n.slideTo(t, 0);
                    },
                    removeAllSlides: function () {
                        (n.virtual.slides = []), n.params.virtual.cache && (n.virtual.cache = {}), c(!0), n.slideTo(0, 0);
                    },
                    update: c,
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: a, emit: o } = e,
                l = i(),
                u = s();
            function d(e) {
                if (!t.enabled) return;
                let { rtlTranslate: n } = t,
                    i = e;
                i.originalEvent && (i = i.originalEvent);
                let a = i.keyCode || i.charCode,
                    s = t.params.keyboard.pageUpDown,
                    d = s && 33 === a,
                    c = s && 34 === a,
                    p = 37 === a,
                    f = 39 === a,
                    h = 38 === a,
                    m = 40 === a;
                if ((!t.allowSlideNext && ((t.isHorizontal() && f) || (t.isVertical() && m) || c)) || (!t.allowSlidePrev && ((t.isHorizontal() && p) || (t.isVertical() && h) || d))) return !1;
                if (!(i.shiftKey || i.altKey || i.ctrlKey || i.metaKey || (l.activeElement && l.activeElement.nodeName && ("input" === l.activeElement.nodeName.toLowerCase() || "textarea" === l.activeElement.nodeName.toLowerCase())))) {
                    if (t.params.keyboard.onlyInViewport && (d || c || p || f || h || m)) {
                        let g = !1;
                        if (b(t.el, `.${t.params.slideClass}, swiper-slide`).length > 0 && 0 === b(t.el, `.${t.params.slideActiveClass}`).length) return;
                        let $ = t.el,
                            y = $.clientWidth,
                            _ = $.clientHeight,
                            w = u.innerWidth,
                            x = u.innerHeight,
                            S = v($);
                        n && (S.left -= $.scrollLeft);
                        let T = [
                            [S.left, S.top],
                            [S.left + y, S.top],
                            [S.left, S.top + _],
                            [S.left + y, S.top + _],
                        ];
                        for (let C = 0; C < T.length; C += 1) {
                            let E = T[C];
                            if (E[0] >= 0 && E[0] <= w && E[1] >= 0 && E[1] <= x) {
                                if (0 === E[0] && 0 === E[1]) continue;
                                g = !0;
                            }
                        }
                        if (!g) return;
                    }
                    t.isHorizontal()
                        ? ((d || c || p || f) && (i.preventDefault ? i.preventDefault() : (i.returnValue = !1)), (((c || f) && !n) || ((d || p) && n)) && t.slideNext(), (((d || p) && !n) || ((c || f) && n)) && t.slidePrev())
                        : ((d || c || h || m) && (i.preventDefault ? i.preventDefault() : (i.returnValue = !1)), (c || m) && t.slideNext(), (d || h) && t.slidePrev()),
                        o("keyPress", a);
                }
            }
            function c() {
                t.keyboard.enabled || (l.addEventListener("keydown", d), (t.keyboard.enabled = !0));
            }
            function p() {
                t.keyboard.enabled && (l.removeEventListener("keydown", d), (t.keyboard.enabled = !1));
            }
            (t.keyboard = { enabled: !1 }),
                n({ keyboard: { enabled: !1, onlyInViewport: !0, pageUpDown: !0 } }),
                a("init", () => {
                    t.params.keyboard.enabled && c();
                }),
                a("destroy", () => {
                    t.keyboard.enabled && p();
                }),
                Object.assign(t.keyboard, { enable: c, disable: p });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i, emit: a } = e,
                u = s(),
                d;
            n({ mousewheel: { enabled: !1, releaseOnEdges: !1, invert: !1, forceToAxis: !1, sensitivity: 1, eventsTarget: "container", thresholdDelta: null, thresholdTime: null, noMousewheelClass: "swiper-no-mousewheel" } }),
                (t.mousewheel = { enabled: !1 });
            let c,
                p = l(),
                f = [];
            function h() {
                t.enabled && (t.mouseEntered = !0);
            }
            function m() {
                t.enabled && (t.mouseEntered = !1);
            }
            function g(e) {
                return !(
                    (t.params.mousewheel.thresholdDelta && e.delta < t.params.mousewheel.thresholdDelta) ||
                    (t.params.mousewheel.thresholdTime && l() - p < t.params.mousewheel.thresholdTime) ||
                    (!(e.delta >= 6 && l() - p < 60) &&
                        (e.direction < 0 ? (t.isEnd && !t.params.loop) || t.animating || (t.slideNext(), a("scroll", e.raw)) : (t.isBeginning && !t.params.loop) || t.animating || (t.slidePrev(), a("scroll", e.raw)),
                        (p = new u.Date().getTime()),
                        1))
                );
            }
            function v(e) {
                var n;
                let i = e,
                    s = !0;
                if (!t.enabled || e.target.closest(`.${t.params.mousewheel.noMousewheelClass}`)) return;
                let u = t.params.mousewheel;
                t.params.cssMode && i.preventDefault();
                let p = t.el;
                "container" !== t.params.mousewheel.eventsTarget && (p = document.querySelector(t.params.mousewheel.eventsTarget));
                let h = p && p.contains(i.target);
                if (!t.mouseEntered && !h && !u.releaseOnEdges) return !0;
                i.originalEvent && (i = i.originalEvent);
                let m = 0,
                    v,
                    $,
                    y,
                    b,
                    _ = t.rtlTranslate ? -1 : 1,
                    w =
                        ((n = i),
                        (v = 0),
                        ($ = 0),
                        (y = 0),
                        (b = 0),
                        "detail" in n && ($ = n.detail),
                        "wheelDelta" in n && ($ = -n.wheelDelta / 120),
                        "wheelDeltaY" in n && ($ = -n.wheelDeltaY / 120),
                        "wheelDeltaX" in n && (v = -n.wheelDeltaX / 120),
                        "axis" in n && n.axis === n.HORIZONTAL_AXIS && ((v = $), ($ = 0)),
                        (y = 10 * v),
                        (b = 10 * $),
                        "deltaY" in n && (b = n.deltaY),
                        "deltaX" in n && (y = n.deltaX),
                        n.shiftKey && !y && ((y = b), (b = 0)),
                        (y || b) && n.deltaMode && (1 === n.deltaMode ? ((y *= 40), (b *= 40)) : ((y *= 800), (b *= 800))),
                        y && !v && (v = y < 1 ? -1 : 1),
                        b && !$ && ($ = b < 1 ? -1 : 1),
                        { spinX: v, spinY: $, pixelX: y, pixelY: b });
                if (u.forceToAxis) {
                    if (t.isHorizontal()) {
                        if (!(Math.abs(w.pixelX) > Math.abs(w.pixelY))) return !0;
                        m = -w.pixelX * _;
                    } else {
                        if (!(Math.abs(w.pixelY) > Math.abs(w.pixelX))) return !0;
                        m = -w.pixelY;
                    }
                } else m = Math.abs(w.pixelX) > Math.abs(w.pixelY) ? -w.pixelX * _ : -w.pixelY;
                if (0 === m) return !0;
                u.invert && (m = -m);
                let x = t.getTranslate() + m * u.sensitivity;
                if (
                    (x >= t.minTranslate() && (x = t.minTranslate()),
                    x <= t.maxTranslate() && (x = t.maxTranslate()),
                    (s = !!t.params.loop || !(x === t.minTranslate() || x === t.maxTranslate())) && t.params.nested && i.stopPropagation(),
                    t.params.freeMode && t.params.freeMode.enabled)
                ) {
                    let S = { time: l(), delta: Math.abs(m), direction: Math.sign(m) },
                        T = c && S.time < c.time + 500 && S.delta <= c.delta && S.direction === c.direction;
                    if (!T) {
                        c = void 0;
                        let C = t.getTranslate() + m * u.sensitivity,
                            E = t.isBeginning,
                            D = t.isEnd;
                        if (
                            (C >= t.minTranslate() && (C = t.minTranslate()),
                            C <= t.maxTranslate() && (C = t.maxTranslate()),
                            t.setTransition(0),
                            t.setTranslate(C),
                            t.updateProgress(),
                            t.updateActiveIndex(),
                            t.updateSlidesClasses(),
                            ((!E && t.isBeginning) || (!D && t.isEnd)) && t.updateSlidesClasses(),
                            t.params.loop && t.loopFix({ direction: S.direction < 0 ? "next" : "prev", byMousewheel: !0 }),
                            t.params.freeMode.sticky)
                        ) {
                            clearTimeout(d), (d = void 0), f.length >= 15 && f.shift();
                            let k = f.length ? f[f.length - 1] : void 0,
                                L = f[0];
                            if ((f.push(S), k && (S.delta > k.delta || S.direction !== k.direction))) f.splice(0);
                            else if (f.length >= 15 && S.time - L.time < 500 && L.delta - S.delta >= 1 && S.delta <= 6) {
                                let A = m > 0 ? 0.8 : 0.2;
                                (c = S),
                                    f.splice(0),
                                    (d = o(() => {
                                        t.slideToClosest(t.params.speed, !0, void 0, A);
                                    }, 0));
                            }
                            d ||
                                (d = o(() => {
                                    (c = S), f.splice(0), t.slideToClosest(t.params.speed, !0, void 0, 0.5);
                                }, 500));
                        }
                        if ((T || a("scroll", i), t.params.autoplay && t.params.autoplayDisableOnInteraction && t.autoplay.stop(), u.releaseOnEdges && (C === t.minTranslate() || C === t.maxTranslate()))) return !0;
                    }
                } else {
                    let I = { time: l(), delta: Math.abs(m), direction: Math.sign(m), raw: e };
                    f.length >= 2 && f.shift();
                    let P = f.length ? f[f.length - 1] : void 0;
                    if (
                        (f.push(I),
                        P ? (I.direction !== P.direction || I.delta > P.delta || I.time > P.time + 150) && g(I) : g(I),
                        (function (e) {
                            let n = t.params.mousewheel;
                            if (e.direction < 0) {
                                if (t.isEnd && !t.params.loop && n.releaseOnEdges) return !0;
                            } else if (t.isBeginning && !t.params.loop && n.releaseOnEdges) return !0;
                            return !1;
                        })(I))
                    )
                        return !0;
                }
                return i.preventDefault ? i.preventDefault() : (i.returnValue = !1), !1;
            }
            function $(e) {
                let n = t.el;
                "container" !== t.params.mousewheel.eventsTarget && (n = document.querySelector(t.params.mousewheel.eventsTarget)), n[e]("mouseenter", h), n[e]("mouseleave", m), n[e]("wheel", v);
            }
            function y() {
                return t.params.cssMode ? (t.wrapperEl.removeEventListener("wheel", v), !0) : !t.mousewheel.enabled && ($("addEventListener"), (t.mousewheel.enabled = !0), !0);
            }
            function b() {
                return t.params.cssMode ? (t.wrapperEl.addEventListener(event, v), !0) : !!t.mousewheel.enabled && ($("removeEventListener"), (t.mousewheel.enabled = !1), !0);
            }
            i("init", () => {
                !t.params.mousewheel.enabled && t.params.cssMode && b(), t.params.mousewheel.enabled && y();
            }),
                i("destroy", () => {
                    t.params.cssMode && y(), t.mousewheel.enabled && b();
                }),
                Object.assign(t.mousewheel, { enable: y, disable: b });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i, emit: a } = e;
            n({
                navigation: {
                    nextEl: null,
                    prevEl: null,
                    hideOnClick: !1,
                    disabledClass: "swiper-button-disabled",
                    hiddenClass: "swiper-button-hidden",
                    lockClass: "swiper-button-lock",
                    navigationDisabledClass: "swiper-navigation-disabled",
                },
            }),
                (t.navigation = { nextEl: null, prevEl: null });
            let s = (e) => (Array.isArray(e) ? e : [e]).filter((e) => !!e);
            function o(e) {
                let n;
                return e && "string" == typeof e && t.isElement && (n = t.el.querySelector(e))
                    ? n
                    : (e && ("string" == typeof e && (n = [...document.querySelectorAll(e)]), t.params.uniqueNavElements && "string" == typeof e && n.length > 1 && 1 === t.el.querySelectorAll(e).length && (n = t.el.querySelector(e))),
                      e && !n ? e : n);
            }
            function l(e, n) {
                let i = t.params.navigation;
                (e = s(e)).forEach((e) => {
                    e && (e.classList[n ? "add" : "remove"](...i.disabledClass.split(" ")), "BUTTON" === e.tagName && (e.disabled = n), t.params.watchOverflow && t.enabled && e.classList[t.isLocked ? "add" : "remove"](i.lockClass));
                });
            }
            function u() {
                let { nextEl: e, prevEl: n } = t.navigation;
                if (t.params.loop) return l(n, !1), void l(e, !1);
                l(n, t.isBeginning && !t.params.rewind), l(e, t.isEnd && !t.params.rewind);
            }
            function d(e) {
                e.preventDefault(), (!t.isBeginning || t.params.loop || t.params.rewind) && (t.slidePrev(), a("navigationPrev"));
            }
            function c(e) {
                e.preventDefault(), (!t.isEnd || t.params.loop || t.params.rewind) && (t.slideNext(), a("navigationNext"));
            }
            function p() {
                let e = t.params.navigation;
                if (((t.params.navigation = U(t, t.originalParams.navigation, t.params.navigation, { nextEl: "swiper-button-next", prevEl: "swiper-button-prev" })), !e.nextEl && !e.prevEl)) return;
                let n = o(e.nextEl),
                    i = o(e.prevEl);
                Object.assign(t.navigation, { nextEl: n, prevEl: i }), (n = s(n)), (i = s(i));
                let a = (n, i) => {
                    n && n.addEventListener("click", "next" === i ? c : d), !t.enabled && n && n.classList.add(...e.lockClass.split(" "));
                };
                n.forEach((e) => a(e, "next")), i.forEach((e) => a(e, "prev"));
            }
            function f() {
                let { nextEl: e, prevEl: n } = t.navigation;
                (e = s(e)), (n = s(n));
                let i = (e, n) => {
                    e.removeEventListener("click", "next" === n ? c : d), e.classList.remove(...t.params.navigation.disabledClass.split(" "));
                };
                e.forEach((e) => i(e, "next")), n.forEach((e) => i(e, "prev"));
            }
            i("init", () => {
                !1 === t.params.navigation.enabled ? h() : (p(), u());
            }),
                i("toEdge fromEdge lock unlock", () => {
                    u();
                }),
                i("destroy", () => {
                    f();
                }),
                i("enable disable", () => {
                    let { nextEl: e, prevEl: n } = t.navigation;
                    [...(e = s(e)), ...(n = s(n))].filter((e) => !!e).forEach((e) => e.classList[t.enabled ? "remove" : "add"](t.params.navigation.lockClass));
                }),
                i("click", (e, n) => {
                    let { nextEl: i, prevEl: o } = t.navigation;
                    (i = s(i)), (o = s(o));
                    let l = n.target;
                    if (t.params.navigation.hideOnClick && !o.includes(l) && !i.includes(l)) {
                        if (t.pagination && t.params.pagination && t.params.pagination.clickable && (t.pagination.el === l || t.pagination.el.contains(l))) return;
                        let u;
                        i.length ? (u = i[0].classList.contains(t.params.navigation.hiddenClass)) : o.length && (u = o[0].classList.contains(t.params.navigation.hiddenClass)),
                            a(!0 === u ? "navigationShow" : "navigationHide"),
                            [...i, ...o].filter((e) => !!e).forEach((e) => e.classList.toggle(t.params.navigation.hiddenClass));
                    }
                });
            let h = () => {
                t.el.classList.add(...t.params.navigation.navigationDisabledClass.split(" ")), f();
            };
            Object.assign(t.navigation, {
                enable() {
                    t.el.classList.remove(...t.params.navigation.navigationDisabledClass.split(" ")), p(), u();
                },
                disable: h,
                update: u,
                init: p,
                destroy: f,
            });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i, emit: a } = e,
                s = "swiper-pagination",
                o;
            n({
                pagination: {
                    el: null,
                    bulletElement: "span",
                    clickable: !1,
                    hideOnClick: !1,
                    renderBullet: null,
                    renderProgressbar: null,
                    renderFraction: null,
                    renderCustom: null,
                    progressbarOpposite: !1,
                    type: "bullets",
                    dynamicBullets: !1,
                    dynamicMainBullets: 1,
                    formatFractionCurrent: (e) => e,
                    formatFractionTotal: (e) => e,
                    bulletClass: `${s}-bullet`,
                    bulletActiveClass: `${s}-bullet-active`,
                    modifierClass: `${s}-`,
                    currentClass: `${s}-current`,
                    totalClass: `${s}-total`,
                    hiddenClass: `${s}-hidden`,
                    progressbarFillClass: `${s}-progressbar-fill`,
                    progressbarOppositeClass: `${s}-progressbar-opposite`,
                    clickableClass: `${s}-clickable`,
                    lockClass: `${s}-lock`,
                    horizontalClass: `${s}-horizontal`,
                    verticalClass: `${s}-vertical`,
                    paginationDisabledClass: `${s}-disabled`,
                },
            }),
                (t.pagination = { el: null, bullets: [] });
            let l = 0,
                u = (e) => (Array.isArray(e) ? e : [e]).filter((e) => !!e);
            function d() {
                return !t.params.pagination.el || !t.pagination.el || (Array.isArray(t.pagination.el) && 0 === t.pagination.el.length);
            }
            function c(e, n) {
                let { bulletActiveClass: i } = t.params.pagination;
                e && (e = e[("prev" === n ? "previous" : "next") + "ElementSibling"]) && (e.classList.add(`${i}-${n}`), (e = e[("prev" === n ? "previous" : "next") + "ElementSibling"]) && e.classList.add(`${i}-${n}-${n}`));
            }
            function p(e) {
                let n = e.target.closest(V(t.params.pagination.bulletClass));
                if (!n) return;
                e.preventDefault();
                let i = y(n) * t.params.slidesPerGroup;
                if (t.params.loop) {
                    if (t.realIndex === i) return;
                    let a = t.realIndex,
                        s = t.getSlideIndexByData(i),
                        o = t.getSlideIndexByData(t.realIndex);
                    if (s > t.slides.length - t.loopedSlides) {
                        let l = t.activeIndex;
                        t.loopFix({ direction: s > o ? "next" : "prev", activeSlideIndex: s, slideTo: !1 }), l === t.activeIndex && t.slideToLoop(a, 0, !1, !0);
                    }
                    t.slideToLoop(i);
                } else t.slideTo(i);
            }
            function f() {
                let e = t.rtl,
                    n = t.params.pagination;
                if (d()) return;
                let i,
                    s,
                    p = t.pagination.el;
                p = u(p);
                let f = t.virtual && t.params.virtual.enabled ? t.virtual.slides.length : t.slides.length,
                    h = t.params.loop ? Math.ceil(f / t.params.slidesPerGroup) : t.snapGrid.length;
                if (
                    (t.params.loop
                        ? ((s = t.previousRealIndex || 0), (i = t.params.slidesPerGroup > 1 ? Math.floor(t.realIndex / t.params.slidesPerGroup) : t.realIndex))
                        : void 0 !== t.snapIndex
                        ? ((i = t.snapIndex), (s = t.previousSnapIndex))
                        : ((s = t.previousIndex || 0), (i = t.activeIndex || 0)),
                    "bullets" === n.type && t.pagination.bullets && t.pagination.bullets.length > 0)
                ) {
                    let m = t.pagination.bullets,
                        g,
                        v,
                        $;
                    if (
                        (n.dynamicBullets &&
                            ((o = w(m[0], t.isHorizontal() ? "width" : "height", !0)),
                            p.forEach((e) => {
                                e.style[t.isHorizontal() ? "width" : "height"] = o * (n.dynamicMainBullets + 4) + "px";
                            }),
                            n.dynamicMainBullets > 1 && void 0 !== s && ((l += i - (s || 0)) > n.dynamicMainBullets - 1 ? (l = n.dynamicMainBullets - 1) : l < 0 && (l = 0)),
                            ($ = ((v = (g = Math.max(i - l, 0)) + (Math.min(m.length, n.dynamicMainBullets) - 1)) + g) / 2)),
                        m.forEach((e) => {
                            let t = [...["", "-next", "-next-next", "-prev", "-prev-prev", "-main"].map((e) => `${n.bulletActiveClass}${e}`)].map((e) => ("string" == typeof e && e.includes(" ") ? e.split(" ") : e)).flat();
                            e.classList.remove(...t);
                        }),
                        p.length > 1)
                    )
                        m.forEach((e) => {
                            let a = y(e);
                            a === i ? e.classList.add(...n.bulletActiveClass.split(" ")) : t.isElement && e.setAttribute("part", "bullet"),
                                n.dynamicBullets && (a >= g && a <= v && e.classList.add(...`${n.bulletActiveClass}-main`.split(" ")), a === g && c(e, "prev"), a === v && c(e, "next"));
                        });
                    else {
                        let b = m[i];
                        if (
                            (b && b.classList.add(...n.bulletActiveClass.split(" ")),
                            t.isElement &&
                                m.forEach((e, t) => {
                                    e.setAttribute("part", t === i ? "bullet-active" : "bullet");
                                }),
                            n.dynamicBullets)
                        ) {
                            let _ = m[g],
                                x = m[v];
                            for (let S = g; S <= v; S += 1) m[S] && m[S].classList.add(...`${n.bulletActiveClass}-main`.split(" "));
                            c(_, "prev"), c(x, "next");
                        }
                    }
                    if (n.dynamicBullets) {
                        let T = Math.min(m.length, n.dynamicMainBullets + 4),
                            C = (o * T - o) / 2 - $ * o,
                            E = e ? "right" : "left";
                        m.forEach((e) => {
                            e.style[t.isHorizontal() ? E : "top"] = `${C}px`;
                        });
                    }
                }
                p.forEach((e, s) => {
                    if (
                        ("fraction" === n.type &&
                            (e.querySelectorAll(V(n.currentClass)).forEach((e) => {
                                e.textContent = n.formatFractionCurrent(i + 1);
                            }),
                            e.querySelectorAll(V(n.totalClass)).forEach((e) => {
                                e.textContent = n.formatFractionTotal(h);
                            })),
                        "progressbar" === n.type)
                    ) {
                        let o;
                        o = n.progressbarOpposite ? (t.isHorizontal() ? "vertical" : "horizontal") : t.isHorizontal() ? "horizontal" : "vertical";
                        let l = (i + 1) / h,
                            u = 1,
                            d = 1;
                        "horizontal" === o ? (u = l) : (d = l),
                            e.querySelectorAll(V(n.progressbarFillClass)).forEach((e) => {
                                (e.style.transform = `translate3d(0,0,0) scaleX(${u}) scaleY(${d})`), (e.style.transitionDuration = `${t.params.speed}ms`);
                            });
                    }
                    "custom" === n.type && n.renderCustom ? ((e.innerHTML = n.renderCustom(t, i + 1, h)), 0 === s && a("paginationRender", e)) : (0 === s && a("paginationRender", e), a("paginationUpdate", e)),
                        t.params.watchOverflow && t.enabled && e.classList[t.isLocked ? "add" : "remove"](n.lockClass);
                });
            }
            function h() {
                let e = t.params.pagination;
                if (d()) return;
                let n = t.virtual && t.params.virtual.enabled ? t.virtual.slides.length : t.slides.length,
                    i = t.pagination.el;
                i = u(i);
                let s = "";
                if ("bullets" === e.type) {
                    let o = t.params.loop ? Math.ceil(n / t.params.slidesPerGroup) : t.snapGrid.length;
                    t.params.freeMode && t.params.freeMode.enabled && o > n && (o = n);
                    for (let l = 0; l < o; l += 1) e.renderBullet ? (s += e.renderBullet.call(t, l, e.bulletClass)) : (s += `<${e.bulletElement} ${t.isElement ? 'part="bullet"' : ""} class="${e.bulletClass}"></${e.bulletElement}>`);
                }
                "fraction" === e.type && (s = e.renderFraction ? e.renderFraction.call(t, e.currentClass, e.totalClass) : `<span class="${e.currentClass}"></span> / <span class="${e.totalClass}"></span>`),
                    "progressbar" === e.type && (s = e.renderProgressbar ? e.renderProgressbar.call(t, e.progressbarFillClass) : `<span class="${e.progressbarFillClass}"></span>`),
                    (t.pagination.bullets = []),
                    i.forEach((n) => {
                        "custom" !== e.type && (n.innerHTML = s || ""), "bullets" === e.type && t.pagination.bullets.push(...n.querySelectorAll(V(e.bulletClass)));
                    }),
                    "custom" !== e.type && a("paginationRender", i[0]);
            }
            function m() {
                t.params.pagination = U(t, t.originalParams.pagination, t.params.pagination, { el: "swiper-pagination" });
                let e = t.params.pagination;
                if (!e.el) return;
                let n;
                "string" == typeof e.el && t.isElement && (n = t.el.querySelector(e.el)),
                    n || "string" != typeof e.el || (n = [...document.querySelectorAll(e.el)]),
                    n || (n = e.el),
                    n &&
                        0 !== n.length &&
                        (t.params.uniqueNavElements && "string" == typeof e.el && Array.isArray(n) && n.length > 1 && (n = [...t.el.querySelectorAll(e.el)]).length > 1 && (n = n.filter((e) => b(e, ".swiper")[0] === t.el)[0]),
                        Array.isArray(n) && 1 === n.length && (n = n[0]),
                        Object.assign(t.pagination, { el: n }),
                        (n = u(n)).forEach((n) => {
                            "bullets" === e.type && e.clickable && n.classList.add(...(e.clickableClass || "").split(" ")),
                                n.classList.add(e.modifierClass + e.type),
                                n.classList.add(t.isHorizontal() ? e.horizontalClass : e.verticalClass),
                                "bullets" === e.type && e.dynamicBullets && (n.classList.add(`${e.modifierClass}${e.type}-dynamic`), (l = 0), e.dynamicMainBullets < 1 && (e.dynamicMainBullets = 1)),
                                "progressbar" === e.type && e.progressbarOpposite && n.classList.add(e.progressbarOppositeClass),
                                e.clickable && n.addEventListener("click", p),
                                t.enabled || n.classList.add(e.lockClass);
                        }));
            }
            function g() {
                let e = t.params.pagination;
                if (d()) return;
                let n = t.pagination.el;
                n &&
                    (n = u(n)).forEach((n) => {
                        n.classList.remove(e.hiddenClass),
                            n.classList.remove(e.modifierClass + e.type),
                            n.classList.remove(t.isHorizontal() ? e.horizontalClass : e.verticalClass),
                            e.clickable && (n.classList.remove(...(e.clickableClass || "").split(" ")), n.removeEventListener("click", p));
                    }),
                    t.pagination.bullets && t.pagination.bullets.forEach((t) => t.classList.remove(...e.bulletActiveClass.split(" ")));
            }
            i("changeDirection", () => {
                if (!t.pagination || !t.pagination.el) return;
                let e = t.params.pagination,
                    { el: n } = t.pagination;
                (n = u(n)).forEach((n) => {
                    n.classList.remove(e.horizontalClass, e.verticalClass), n.classList.add(t.isHorizontal() ? e.horizontalClass : e.verticalClass);
                });
            }),
                i("init", () => {
                    !1 === t.params.pagination.enabled ? v() : (m(), h(), f());
                }),
                i("activeIndexChange", () => {
                    void 0 === t.snapIndex && f();
                }),
                i("snapIndexChange", () => {
                    f();
                }),
                i("snapGridLengthChange", () => {
                    h(), f();
                }),
                i("destroy", () => {
                    g();
                }),
                i("enable disable", () => {
                    let { el: e } = t.pagination;
                    e && (e = u(e)).forEach((e) => e.classList[t.enabled ? "remove" : "add"](t.params.pagination.lockClass));
                }),
                i("lock unlock", () => {
                    f();
                }),
                i("click", (e, n) => {
                    let i = n.target,
                        s = u(t.pagination.el);
                    if (t.params.pagination.el && t.params.pagination.hideOnClick && s && s.length > 0 && !i.classList.contains(t.params.pagination.bulletClass)) {
                        if (t.navigation && ((t.navigation.nextEl && i === t.navigation.nextEl) || (t.navigation.prevEl && i === t.navigation.prevEl))) return;
                        let o = s[0].classList.contains(t.params.pagination.hiddenClass);
                        a(!0 === o ? "paginationShow" : "paginationHide"), s.forEach((e) => e.classList.toggle(t.params.pagination.hiddenClass));
                    }
                });
            let v = () => {
                t.el.classList.add(t.params.pagination.paginationDisabledClass);
                let { el: e } = t.pagination;
                e && (e = u(e)).forEach((e) => e.classList.add(t.params.pagination.paginationDisabledClass)), g();
            };
            Object.assign(t.pagination, {
                enable() {
                    t.el.classList.remove(t.params.pagination.paginationDisabledClass);
                    let { el: e } = t.pagination;
                    e && (e = u(e)).forEach((e) => e.classList.remove(t.params.pagination.paginationDisabledClass)), m(), h(), f();
                },
                disable: v,
                render: h,
                update: f,
                init: m,
                destroy: g,
            });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: a, emit: s } = e,
                l = i(),
                u,
                d,
                c,
                p,
                f = !1,
                h = null,
                m = null;
            function $() {
                if (!t.params.scrollbar.el || !t.scrollbar.el) return;
                let { scrollbar: e, rtlTranslate: n } = t,
                    { dragEl: i, el: a } = e,
                    s = t.params.scrollbar,
                    o = t.params.loop ? t.progressLoop : t.progress,
                    l = d,
                    u = (c - d) * o;
                n ? ((u = -u) > 0 ? ((l = d - u), (u = 0)) : -u + d > c && (l = c + u)) : u < 0 ? ((l = d + u), (u = 0)) : u + d > c && (l = c - u),
                    t.isHorizontal() ? ((i.style.transform = `translate3d(${u}px, 0, 0)`), (i.style.width = `${l}px`)) : ((i.style.transform = `translate3d(0px, ${u}px, 0)`), (i.style.height = `${l}px`)),
                    s.hide &&
                        (clearTimeout(h),
                        (a.style.opacity = 1),
                        (h = setTimeout(() => {
                            (a.style.opacity = 0), (a.style.transitionDuration = "400ms");
                        }, 1e3)));
            }
            function y() {
                if (!t.params.scrollbar.el || !t.scrollbar.el) return;
                let { scrollbar: e } = t,
                    { dragEl: n, el: i } = e;
                (n.style.width = ""),
                    (n.style.height = ""),
                    (c = t.isHorizontal() ? i.offsetWidth : i.offsetHeight),
                    (p = t.size / (t.virtualSize + t.params.slidesOffsetBefore - (t.params.centeredSlides ? t.snapGrid[0] : 0))),
                    (d = "auto" === t.params.scrollbar.dragSize ? c * p : parseInt(t.params.scrollbar.dragSize, 10)),
                    t.isHorizontal() ? (n.style.width = `${d}px`) : (n.style.height = `${d}px`),
                    (i.style.display = p >= 1 ? "none" : ""),
                    t.params.scrollbar.hide && (i.style.opacity = 0),
                    t.params.watchOverflow && t.enabled && e.el.classList[t.isLocked ? "add" : "remove"](t.params.scrollbar.lockClass);
            }
            function b(e) {
                return t.isHorizontal() ? e.clientX : e.clientY;
            }
            function _(e) {
                let { scrollbar: n, rtlTranslate: i } = t,
                    { el: a } = n,
                    s;
                (s = Math.max(Math.min((s = (b(e) - v(a)[t.isHorizontal() ? "left" : "top"] - (null !== u ? u : d / 2)) / (c - d)), 1), 0)), i && (s = 1 - s);
                let o = t.minTranslate() + (t.maxTranslate() - t.minTranslate()) * s;
                t.updateProgress(o), t.setTranslate(o), t.updateActiveIndex(), t.updateSlidesClasses();
            }
            function w(e) {
                let n = t.params.scrollbar,
                    { scrollbar: i, wrapperEl: a } = t,
                    { el: o, dragEl: l } = i;
                (f = !0),
                    (u = e.target === l ? b(e) - e.target.getBoundingClientRect()[t.isHorizontal() ? "left" : "top"] : null),
                    e.preventDefault(),
                    e.stopPropagation(),
                    (a.style.transitionDuration = "100ms"),
                    (l.style.transitionDuration = "100ms"),
                    _(e),
                    clearTimeout(m),
                    (o.style.transitionDuration = "0ms"),
                    n.hide && (o.style.opacity = 1),
                    t.params.cssMode && (t.wrapperEl.style["scroll-snap-type"] = "none"),
                    s("scrollbarDragStart", e);
            }
            function x(e) {
                let { scrollbar: n, wrapperEl: i } = t,
                    { el: a, dragEl: o } = n;
                f && (e.preventDefault ? e.preventDefault() : (e.returnValue = !1), _(e), (i.style.transitionDuration = "0ms"), (a.style.transitionDuration = "0ms"), (o.style.transitionDuration = "0ms"), s("scrollbarDragMove", e));
            }
            function S(e) {
                let n = t.params.scrollbar,
                    { scrollbar: i, wrapperEl: a } = t,
                    { el: l } = i;
                f &&
                    ((f = !1),
                    t.params.cssMode && ((t.wrapperEl.style["scroll-snap-type"] = ""), (a.style.transitionDuration = "")),
                    n.hide &&
                        (clearTimeout(m),
                        (m = o(() => {
                            (l.style.opacity = 0), (l.style.transitionDuration = "400ms");
                        }, 1e3))),
                    s("scrollbarDragEnd", e),
                    n.snapOnRelease && t.slideToClosest());
            }
            function T(e) {
                let { scrollbar: n, params: i } = t,
                    a = n.el;
                if (!a) return;
                let s = a,
                    o = !!i.passiveListeners && { passive: !1, capture: !1 },
                    u = !!i.passiveListeners && { passive: !0, capture: !1 };
                if (!s) return;
                let d = "on" === e ? "addEventListener" : "removeEventListener";
                s[d]("pointerdown", w, o), l[d]("pointermove", x, o), l[d]("pointerup", S, u);
            }
            function C() {
                let { scrollbar: e, el: n } = t;
                t.params.scrollbar = U(t, t.originalParams.scrollbar, t.params.scrollbar, { el: "swiper-scrollbar" });
                let i = t.params.scrollbar;
                if (!i.el) return;
                let a, s;
                "string" == typeof i.el && t.isElement && (a = t.el.querySelector(i.el)),
                    a || "string" != typeof i.el ? a || (a = i.el) : (a = l.querySelectorAll(i.el)),
                    t.params.uniqueNavElements && "string" == typeof i.el && a.length > 1 && 1 === n.querySelectorAll(i.el).length && (a = n.querySelector(i.el)),
                    a.length > 0 && (a = a[0]),
                    a.classList.add(t.isHorizontal() ? i.horizontalClass : i.verticalClass),
                    a && ((s = a.querySelector(`.${t.params.scrollbar.dragClass}`)) || ((s = g("div", t.params.scrollbar.dragClass)), a.append(s))),
                    Object.assign(e, { el: a, dragEl: s }),
                    i.draggable && t.params.scrollbar.el && t.scrollbar.el && T("on"),
                    a && a.classList[t.enabled ? "remove" : "add"](t.params.scrollbar.lockClass);
            }
            function E() {
                let e = t.params.scrollbar,
                    n = t.scrollbar.el;
                n && n.classList.remove(t.isHorizontal() ? e.horizontalClass : e.verticalClass), t.params.scrollbar.el && t.scrollbar.el && T("off");
            }
            n({
                scrollbar: {
                    el: null,
                    dragSize: "auto",
                    hide: !1,
                    draggable: !1,
                    snapOnRelease: !0,
                    lockClass: "swiper-scrollbar-lock",
                    dragClass: "swiper-scrollbar-drag",
                    scrollbarDisabledClass: "swiper-scrollbar-disabled",
                    horizontalClass: "swiper-scrollbar-horizontal",
                    verticalClass: "swiper-scrollbar-vertical",
                },
            }),
                (t.scrollbar = { el: null, dragEl: null }),
                a("init", () => {
                    !1 === t.params.scrollbar.enabled ? D() : (C(), y(), $());
                }),
                a("update resize observerUpdate lock unlock", () => {
                    y();
                }),
                a("setTranslate", () => {
                    $();
                }),
                a("setTransition", (e, n) => {
                    var i;
                    (i = n), t.params.scrollbar.el && t.scrollbar.el && (t.scrollbar.dragEl.style.transitionDuration = `${i}ms`);
                }),
                a("enable disable", () => {
                    let { el: e } = t.scrollbar;
                    e && e.classList[t.enabled ? "remove" : "add"](t.params.scrollbar.lockClass);
                }),
                a("destroy", () => {
                    E();
                });
            let D = () => {
                t.el.classList.add(t.params.scrollbar.scrollbarDisabledClass), t.scrollbar.el && t.scrollbar.el.classList.add(t.params.scrollbar.scrollbarDisabledClass), E();
            };
            Object.assign(t.scrollbar, {
                enable() {
                    t.el.classList.remove(t.params.scrollbar.scrollbarDisabledClass), t.scrollbar.el && t.scrollbar.el.classList.remove(t.params.scrollbar.scrollbarDisabledClass), C(), y(), $();
                },
                disable: D,
                updateSize: y,
                setTranslate: $,
                init: C,
                destroy: E,
            });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ parallax: { enabled: !1 } });
            let a = "[data-swiper-parallax], [data-swiper-parallax-x], [data-swiper-parallax-y], [data-swiper-parallax-opacity], [data-swiper-parallax-scale]",
                s = (e, n) => {
                    let { rtl: i } = t,
                        a = i ? -1 : 1,
                        s = e.getAttribute("data-swiper-parallax") || "0",
                        o = e.getAttribute("data-swiper-parallax-x"),
                        l = e.getAttribute("data-swiper-parallax-y"),
                        u = e.getAttribute("data-swiper-parallax-scale"),
                        d = e.getAttribute("data-swiper-parallax-opacity"),
                        c = e.getAttribute("data-swiper-parallax-rotate");
                    o || l ? ((o = o || "0"), (l = l || "0")) : t.isHorizontal() ? ((o = s), (l = "0")) : ((l = s), (o = "0")),
                        (o = o.indexOf("%") >= 0 ? parseInt(o, 10) * n * a + "%" : o * n * a + "px"),
                        (l = l.indexOf("%") >= 0 ? parseInt(l, 10) * n + "%" : l * n + "px"),
                        null != d && (e.style.opacity = d - (d - 1) * (1 - Math.abs(n)));
                    let p = `translate3d(${o}, ${l}, 0px)`;
                    null != u && (p += ` scale(${u - (u - 1) * (1 - Math.abs(n))})`), c && null != c && (p += ` rotate(${-(c * n * 1)}deg)`), (e.style.transform = p);
                },
                o = () => {
                    let { el: e, slides: n, progress: i, snapGrid: o, isElement: l } = t,
                        u = m(e, a);
                    t.isElement && u.push(...m(t.hostEl, a)),
                        u.forEach((e) => {
                            s(e, i);
                        }),
                        n.forEach((e, n) => {
                            let l = e.progress;
                            t.params.slidesPerGroup > 1 && "auto" !== t.params.slidesPerView && (l += Math.ceil(n / 2) - i * (o.length - 1)),
                                (l = Math.min(Math.max(l, -1), 1)),
                                e.querySelectorAll(`${a}, [data-swiper-parallax-rotate]`).forEach((e) => {
                                    s(e, l);
                                });
                        });
                };
            i("beforeInit", () => {
                t.params.parallax.enabled && ((t.params.watchSlidesProgress = !0), (t.originalParams.watchSlidesProgress = !0));
            }),
                i("init", () => {
                    t.params.parallax.enabled && o();
                }),
                i("setTranslate", () => {
                    t.params.parallax.enabled && o();
                }),
                i("setTransition", (e, n) => {
                    t.params.parallax.enabled &&
                        (function (e) {
                            void 0 === e && (e = t.params.speed);
                            let { el: n, hostEl: i } = t,
                                s = [...n.querySelectorAll(a)];
                            t.isElement && s.push(...i.querySelectorAll(a)),
                                s.forEach((t) => {
                                    let n = parseInt(t.getAttribute("data-swiper-parallax-duration"), 10) || e;
                                    0 === e && (n = 0), (t.style.transitionDuration = `${n}ms`);
                                });
                        })(n);
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i, emit: a } = e,
                o = s();
            n({ zoom: { enabled: !1, maxRatio: 3, minRatio: 1, toggle: !0, containerClass: "swiper-zoom-container", zoomedSlideClass: "swiper-slide-zoomed" } }), (t.zoom = { enabled: !1 });
            let l,
                d,
                c = 1,
                p = !1,
                f = [],
                h = { originX: 0, originY: 0, slideEl: void 0, slideWidth: void 0, slideHeight: void 0, imageEl: void 0, imageWrapEl: void 0, maxRatio: 3 },
                g = {
                    isTouched: void 0,
                    isMoved: void 0,
                    currentX: void 0,
                    currentY: void 0,
                    minX: void 0,
                    minY: void 0,
                    maxX: void 0,
                    maxY: void 0,
                    width: void 0,
                    height: void 0,
                    startX: void 0,
                    startY: void 0,
                    touchesStart: {},
                    touchesCurrent: {},
                },
                $ = { x: void 0, y: void 0, prevPositionX: void 0, prevPositionY: void 0, prevTime: void 0 },
                y = 1;
            function _() {
                if (f.length < 2) return 1;
                let e = f[0].pageX,
                    t = f[0].pageY,
                    n = f[1].pageX,
                    i = f[1].pageY;
                return Math.sqrt((n - e) ** 2 + (i - t) ** 2);
            }
            function w(e) {
                let n = t.isElement ? "swiper-slide" : `.${t.params.slideClass}`;
                return !!e.target.matches(n) || t.slides.filter((t) => t.contains(e.target)).length > 0;
            }
            function x(e) {
                if (("mouse" === e.pointerType && f.splice(0, f.length), !w(e))) return;
                let n = t.params.zoom;
                if (((l = !1), (d = !1), f.push(e), !(f.length < 2))) {
                    if (((l = !0), (h.scaleStart = _()), !h.slideEl)) {
                        (h.slideEl = e.target.closest(`.${t.params.slideClass}, swiper-slide`)), h.slideEl || (h.slideEl = t.slides[t.activeIndex]);
                        let i = h.slideEl.querySelector(`.${n.containerClass}`);
                        if ((i && (i = i.querySelectorAll("picture, img, svg, canvas, .swiper-zoom-target")[0]), (h.imageEl = i), (h.imageWrapEl = i ? b(h.imageEl, `.${n.containerClass}`)[0] : void 0), !h.imageWrapEl))
                            return void (h.imageEl = void 0);
                        h.maxRatio = h.imageWrapEl.getAttribute("data-swiper-zoom") || n.maxRatio;
                    }
                    if (h.imageEl) {
                        let [a, s] = (function () {
                            if (f.length < 2) return { x: null, y: null };
                            let e = h.imageEl.getBoundingClientRect();
                            return [(f[0].pageX + (f[1].pageX - f[0].pageX) / 2 - e.x) / c, (f[0].pageY + (f[1].pageY - f[0].pageY) / 2 - e.y) / c];
                        })();
                        (h.originX = a), (h.originY = s), (h.imageEl.style.transitionDuration = "0ms");
                    }
                    p = !0;
                }
            }
            function S(e) {
                if (!w(e)) return;
                let n = t.params.zoom,
                    i = t.zoom,
                    a = f.findIndex((t) => t.pointerId === e.pointerId);
                a >= 0 && (f[a] = e),
                    f.length < 2 ||
                        ((d = !0),
                        (h.scaleMove = _()),
                        h.imageEl &&
                            ((i.scale = (h.scaleMove / h.scaleStart) * c),
                            i.scale > h.maxRatio && (i.scale = h.maxRatio - 1 + (i.scale - h.maxRatio + 1) ** 0.5),
                            i.scale < n.minRatio && (i.scale = n.minRatio + 1 - (n.minRatio - i.scale + 1) ** 0.5),
                            (h.imageEl.style.transform = `translate3d(0,0,0) scale(${i.scale})`)));
            }
            function T(e) {
                if (!w(e) || ("mouse" === e.pointerType && "pointerout" === e.type)) return;
                let n = t.params.zoom,
                    i = t.zoom,
                    a = f.findIndex((t) => t.pointerId === e.pointerId);
                a >= 0 && f.splice(a, 1),
                    l &&
                        d &&
                        ((l = !1),
                        (d = !1),
                        h.imageEl &&
                            ((i.scale = Math.max(Math.min(i.scale, h.maxRatio), n.minRatio)),
                            (h.imageEl.style.transitionDuration = `${t.params.speed}ms`),
                            (h.imageEl.style.transform = `translate3d(0,0,0) scale(${i.scale})`),
                            (c = i.scale),
                            (p = !1),
                            i.scale > 1 && h.slideEl ? h.slideEl.classList.add(`${n.zoomedSlideClass}`) : i.scale <= 1 && h.slideEl && h.slideEl.classList.remove(`${n.zoomedSlideClass}`),
                            1 === i.scale && ((h.originX = 0), (h.originY = 0), (h.slideEl = void 0))));
            }
            function C(e) {
                if (
                    !w(e) ||
                    !(function (e) {
                        let n = `.${t.params.zoom.containerClass}`;
                        return !!e.target.matches(n) || [...t.hostEl.querySelectorAll(n)].filter((t) => t.contains(e.target)).length > 0;
                    })(e)
                )
                    return;
                let n = t.zoom;
                if (!h.imageEl || !g.isTouched || !h.slideEl) return;
                g.isMoved ||
                    ((g.width = h.imageEl.offsetWidth),
                    (g.height = h.imageEl.offsetHeight),
                    (g.startX = u(h.imageWrapEl, "x") || 0),
                    (g.startY = u(h.imageWrapEl, "y") || 0),
                    (h.slideWidth = h.slideEl.offsetWidth),
                    (h.slideHeight = h.slideEl.offsetHeight),
                    (h.imageWrapEl.style.transitionDuration = "0ms"));
                let i = g.width * n.scale,
                    a = g.height * n.scale;
                if (i < h.slideWidth && a < h.slideHeight) return;
                if (
                    ((g.minX = Math.min(h.slideWidth / 2 - i / 2, 0)),
                    (g.maxX = -g.minX),
                    (g.minY = Math.min(h.slideHeight / 2 - a / 2, 0)),
                    (g.maxY = -g.minY),
                    (g.touchesCurrent.x = f.length > 0 ? f[0].pageX : e.pageX),
                    (g.touchesCurrent.y = f.length > 0 ? f[0].pageY : e.pageY),
                    Math.max(Math.abs(g.touchesCurrent.x - g.touchesStart.x), Math.abs(g.touchesCurrent.y - g.touchesStart.y)) > 5 && (t.allowClick = !1),
                    !g.isMoved &&
                        !p &&
                        ((t.isHorizontal() && ((Math.floor(g.minX) === Math.floor(g.startX) && g.touchesCurrent.x < g.touchesStart.x) || (Math.floor(g.maxX) === Math.floor(g.startX) && g.touchesCurrent.x > g.touchesStart.x))) ||
                            (!t.isHorizontal() && ((Math.floor(g.minY) === Math.floor(g.startY) && g.touchesCurrent.y < g.touchesStart.y) || (Math.floor(g.maxY) === Math.floor(g.startY) && g.touchesCurrent.y > g.touchesStart.y)))))
                )
                    return void (g.isTouched = !1);
                e.cancelable && e.preventDefault(), e.stopPropagation(), (g.isMoved = !0);
                let s = (n.scale - c) / (h.maxRatio - t.params.zoom.minRatio),
                    { originX: o, originY: l } = h;
                (g.currentX = g.touchesCurrent.x - g.touchesStart.x + g.startX + s * (g.width - 2 * o)),
                    (g.currentY = g.touchesCurrent.y - g.touchesStart.y + g.startY + s * (g.height - 2 * l)),
                    g.currentX < g.minX && (g.currentX = g.minX + 1 - (g.minX - g.currentX + 1) ** 0.8),
                    g.currentX > g.maxX && (g.currentX = g.maxX - 1 + (g.currentX - g.maxX + 1) ** 0.8),
                    g.currentY < g.minY && (g.currentY = g.minY + 1 - (g.minY - g.currentY + 1) ** 0.8),
                    g.currentY > g.maxY && (g.currentY = g.maxY - 1 + (g.currentY - g.maxY + 1) ** 0.8),
                    $.prevPositionX || ($.prevPositionX = g.touchesCurrent.x),
                    $.prevPositionY || ($.prevPositionY = g.touchesCurrent.y),
                    $.prevTime || ($.prevTime = Date.now()),
                    ($.x = (g.touchesCurrent.x - $.prevPositionX) / (Date.now() - $.prevTime) / 2),
                    ($.y = (g.touchesCurrent.y - $.prevPositionY) / (Date.now() - $.prevTime) / 2),
                    2 > Math.abs(g.touchesCurrent.x - $.prevPositionX) && ($.x = 0),
                    2 > Math.abs(g.touchesCurrent.y - $.prevPositionY) && ($.y = 0),
                    ($.prevPositionX = g.touchesCurrent.x),
                    ($.prevPositionY = g.touchesCurrent.y),
                    ($.prevTime = Date.now()),
                    (h.imageWrapEl.style.transform = `translate3d(${g.currentX}px, ${g.currentY}px,0)`);
            }
            function E() {
                let e = t.zoom;
                h.slideEl &&
                    t.activeIndex !== t.slides.indexOf(h.slideEl) &&
                    (h.imageEl && (h.imageEl.style.transform = "translate3d(0,0,0) scale(1)"),
                    h.imageWrapEl && (h.imageWrapEl.style.transform = "translate3d(0,0,0)"),
                    h.slideEl.classList.remove(`${t.params.zoom.zoomedSlideClass}`),
                    (e.scale = 1),
                    (c = 1),
                    (h.slideEl = void 0),
                    (h.imageEl = void 0),
                    (h.imageWrapEl = void 0),
                    (h.originX = 0),
                    (h.originY = 0));
            }
            function D(e) {
                let n = t.zoom,
                    i = t.params.zoom;
                if (!h.slideEl) {
                    e && e.target && (h.slideEl = e.target.closest(`.${t.params.slideClass}, swiper-slide`)),
                        h.slideEl || (t.params.virtual && t.params.virtual.enabled && t.virtual ? (h.slideEl = m(t.slidesEl, `.${t.params.slideActiveClass}`)[0]) : (h.slideEl = t.slides[t.activeIndex]));
                    let a = h.slideEl.querySelector(`.${i.containerClass}`);
                    a && (a = a.querySelectorAll("picture, img, svg, canvas, .swiper-zoom-target")[0]), (h.imageEl = a), (h.imageWrapEl = a ? b(h.imageEl, `.${i.containerClass}`)[0] : void 0);
                }
                if (!h.imageEl || !h.imageWrapEl) return;
                let s, l, u, d, p, f, $, y, _, w, x, S, T, C, E, D, k, L;
                t.params.cssMode && ((t.wrapperEl.style.overflow = "hidden"), (t.wrapperEl.style.touchAction = "none")),
                    h.slideEl.classList.add(`${i.zoomedSlideClass}`),
                    void 0 === g.touchesStart.x && e ? ((s = e.pageX), (l = e.pageY)) : ((s = g.touchesStart.x), (l = g.touchesStart.y));
                let A = "number" == typeof e ? e : null;
                1 === c && A && ((s = void 0), (l = void 0)),
                    (n.scale = A || h.imageWrapEl.getAttribute("data-swiper-zoom") || i.maxRatio),
                    (c = A || h.imageWrapEl.getAttribute("data-swiper-zoom") || i.maxRatio),
                    !e || (1 === c && A)
                        ? (($ = 0), (y = 0))
                        : ((k = h.slideEl.offsetWidth),
                          (L = h.slideEl.offsetHeight),
                          (u = v(h.slideEl).left + o.scrollX),
                          (d = v(h.slideEl).top + o.scrollY),
                          (p = u + k / 2 - s),
                          (f = d + L / 2 - l),
                          (_ = h.imageEl.offsetWidth),
                          (w = h.imageEl.offsetHeight),
                          (x = _ * n.scale),
                          (S = w * n.scale),
                          (T = Math.min(k / 2 - x / 2, 0)),
                          (C = Math.min(L / 2 - S / 2, 0)),
                          (E = -T),
                          (D = -C),
                          ($ = p * n.scale),
                          (y = f * n.scale),
                          $ < T && ($ = T),
                          $ > E && ($ = E),
                          y < C && (y = C),
                          y > D && (y = D)),
                    A && 1 === n.scale && ((h.originX = 0), (h.originY = 0)),
                    (h.imageWrapEl.style.transitionDuration = "300ms"),
                    (h.imageWrapEl.style.transform = `translate3d(${$}px, ${y}px,0)`),
                    (h.imageEl.style.transitionDuration = "300ms"),
                    (h.imageEl.style.transform = `translate3d(0,0,0) scale(${n.scale})`);
            }
            function k() {
                let e = t.zoom,
                    n = t.params.zoom;
                if (!h.slideEl) {
                    t.params.virtual && t.params.virtual.enabled && t.virtual ? (h.slideEl = m(t.slidesEl, `.${t.params.slideActiveClass}`)[0]) : (h.slideEl = t.slides[t.activeIndex]);
                    let i = h.slideEl.querySelector(`.${n.containerClass}`);
                    i && (i = i.querySelectorAll("picture, img, svg, canvas, .swiper-zoom-target")[0]), (h.imageEl = i), (h.imageWrapEl = i ? b(h.imageEl, `.${n.containerClass}`)[0] : void 0);
                }
                h.imageEl &&
                    h.imageWrapEl &&
                    (t.params.cssMode && ((t.wrapperEl.style.overflow = ""), (t.wrapperEl.style.touchAction = "")),
                    (e.scale = 1),
                    (c = 1),
                    (h.imageWrapEl.style.transitionDuration = "300ms"),
                    (h.imageWrapEl.style.transform = "translate3d(0,0,0)"),
                    (h.imageEl.style.transitionDuration = "300ms"),
                    (h.imageEl.style.transform = "translate3d(0,0,0) scale(1)"),
                    h.slideEl.classList.remove(`${n.zoomedSlideClass}`),
                    (h.slideEl = void 0),
                    (h.originX = 0),
                    (h.originY = 0));
            }
            function L(e) {
                let n = t.zoom;
                n.scale && 1 !== n.scale ? k() : D(e);
            }
            function A() {
                return { passiveListener: !!t.params.passiveListeners && { passive: !0, capture: !1 }, activeListenerWithCapture: !t.params.passiveListeners || { passive: !1, capture: !0 } };
            }
            function I() {
                let e = t.zoom;
                if (e.enabled) return;
                e.enabled = !0;
                let { passiveListener: n, activeListenerWithCapture: i } = A();
                t.wrapperEl.addEventListener("pointerdown", x, n),
                    t.wrapperEl.addEventListener("pointermove", S, i),
                    ["pointerup", "pointercancel", "pointerout"].forEach((e) => {
                        t.wrapperEl.addEventListener(e, T, n);
                    }),
                    t.wrapperEl.addEventListener("pointermove", C, i);
            }
            function P() {
                let e = t.zoom;
                if (!e.enabled) return;
                e.enabled = !1;
                let { passiveListener: n, activeListenerWithCapture: i } = A();
                t.wrapperEl.removeEventListener("pointerdown", x, n),
                    t.wrapperEl.removeEventListener("pointermove", S, i),
                    ["pointerup", "pointercancel", "pointerout"].forEach((e) => {
                        t.wrapperEl.removeEventListener(e, T, n);
                    }),
                    t.wrapperEl.removeEventListener("pointermove", C, i);
            }
            Object.defineProperty(t.zoom, "scale", {
                get: () => y,
                set(e) {
                    if (y !== e) {
                        let t = h.imageEl,
                            n = h.slideEl;
                        a("zoomChange", e, t, n);
                    }
                    y = e;
                },
            }),
                i("init", () => {
                    t.params.zoom.enabled && I();
                }),
                i("destroy", () => {
                    P();
                }),
                i("touchStart", (e, n) => {
                    t.zoom.enabled &&
                        (function (e) {
                            let n = t.device;
                            if (!h.imageEl || g.isTouched) return;
                            n.android && e.cancelable && e.preventDefault(), (g.isTouched = !0);
                            let i = f.length > 0 ? f[0] : e;
                            (g.touchesStart.x = i.pageX), (g.touchesStart.y = i.pageY);
                        })(n);
                }),
                i("touchEnd", (e, n) => {
                    t.zoom.enabled &&
                        (function () {
                            let e = t.zoom;
                            if (!h.imageEl) return;
                            if (!g.isTouched || !g.isMoved) return (g.isTouched = !1), void (g.isMoved = !1);
                            (g.isTouched = !1), (g.isMoved = !1);
                            let n = 300,
                                i = 300,
                                a = $.x * n,
                                s = g.currentX + a,
                                o = $.y * i,
                                l = g.currentY + o;
                            0 !== $.x && (n = Math.abs((s - g.currentX) / $.x)), 0 !== $.y && (i = Math.abs((l - g.currentY) / $.y));
                            let u = Math.max(n, i);
                            (g.currentX = s), (g.currentY = l);
                            let d = g.width * e.scale,
                                c = g.height * e.scale;
                            (g.minX = Math.min(h.slideWidth / 2 - d / 2, 0)),
                                (g.maxX = -g.minX),
                                (g.minY = Math.min(h.slideHeight / 2 - c / 2, 0)),
                                (g.maxY = -g.minY),
                                (g.currentX = Math.max(Math.min(g.currentX, g.maxX), g.minX)),
                                (g.currentY = Math.max(Math.min(g.currentY, g.maxY), g.minY)),
                                (h.imageWrapEl.style.transitionDuration = `${u}ms`),
                                (h.imageWrapEl.style.transform = `translate3d(${g.currentX}px, ${g.currentY}px,0)`);
                        })();
                }),
                i("doubleTap", (e, n) => {
                    !t.animating && t.params.zoom.enabled && t.zoom.enabled && t.params.zoom.toggle && L(n);
                }),
                i("transitionEnd", () => {
                    t.zoom.enabled && t.params.zoom.enabled && E();
                }),
                i("slideChange", () => {
                    t.zoom.enabled && t.params.zoom.enabled && t.params.cssMode && E();
                }),
                Object.assign(t.zoom, { enable: I, disable: P, in: D, out: k, toggle: L });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            function a(e, t) {
                let n,
                    i,
                    a,
                    s = (e, t) => {
                        for (i = -1, n = e.length; n - i > 1; ) e[(a = (n + i) >> 1)] <= t ? (i = a) : (n = a);
                        return n;
                    },
                    o,
                    l;
                return (
                    (this.x = e),
                    (this.y = t),
                    (this.lastIndex = e.length - 1),
                    (this.interpolate = function (e) {
                        return e ? ((o = (l = s(this.x, e)) - 1), ((e - this.x[o]) * (this.y[l] - this.y[o])) / (this.x[l] - this.x[o]) + this.y[o]) : 0;
                    }),
                    this
                );
            }
            function s() {
                t.controller.control && t.controller.spline && ((t.controller.spline = void 0), delete t.controller.spline);
            }
            n({ controller: { control: void 0, inverse: !1, by: "slide" } }),
                (t.controller = { control: void 0 }),
                i("beforeInit", () => {
                    if ("undefined" != typeof window && ("string" == typeof t.params.controller.control || t.params.controller.control instanceof HTMLElement)) {
                        let e = document.querySelector(t.params.controller.control);
                        if (e && e.swiper) t.controller.control = e.swiper;
                        else if (e) {
                            let n = (i) => {
                                (t.controller.control = i.detail[0]), t.update(), e.removeEventListener("init", n);
                            };
                            e.addEventListener("init", n);
                        }
                    } else t.controller.control = t.params.controller.control;
                }),
                i("update", () => {
                    s();
                }),
                i("resize", () => {
                    s();
                }),
                i("observerUpdate", () => {
                    s();
                }),
                i("setTranslate", (e, n, i) => {
                    t.controller.control && !t.controller.control.destroyed && t.controller.setTranslate(n, i);
                }),
                i("setTransition", (e, n, i) => {
                    t.controller.control && !t.controller.control.destroyed && t.controller.setTransition(n, i);
                }),
                Object.assign(t.controller, {
                    setTranslate: function (e, n) {
                        let i = t.controller.control,
                            s,
                            o,
                            l = t.constructor;
                        function u(e) {
                            var n;
                            if (e.destroyed) return;
                            let i = t.rtlTranslate ? -t.translate : t.translate;
                            "slide" === t.params.controller.by && ((n = e), (t.controller.spline = t.params.loop ? new a(t.slidesGrid, n.slidesGrid) : new a(t.snapGrid, n.snapGrid)), (o = -t.controller.spline.interpolate(-i))),
                                (o && "container" !== t.params.controller.by) ||
                                    ((!Number.isNaN((s = (e.maxTranslate() - e.minTranslate()) / (t.maxTranslate() - t.minTranslate()))) && Number.isFinite(s)) || (s = 1), (o = (i - t.minTranslate()) * s + e.minTranslate())),
                                t.params.controller.inverse && (o = e.maxTranslate() - o),
                                e.updateProgress(o),
                                e.setTranslate(o, t),
                                e.updateActiveIndex(),
                                e.updateSlidesClasses();
                        }
                        if (Array.isArray(i)) for (let d = 0; d < i.length; d += 1) i[d] !== n && i[d] instanceof l && u(i[d]);
                        else i instanceof l && n !== i && u(i);
                    },
                    setTransition: function (e, n) {
                        let i = t.constructor,
                            a = t.controller.control,
                            s;
                        function l(n) {
                            n.destroyed ||
                                (n.setTransition(e, t),
                                0 !== e &&
                                    (n.transitionStart(),
                                    n.params.autoHeight &&
                                        o(() => {
                                            n.updateAutoHeight();
                                        }),
                                    _(n.wrapperEl, () => {
                                        a && n.transitionEnd();
                                    })));
                        }
                        if (Array.isArray(a)) for (s = 0; s < a.length; s += 1) a[s] !== n && a[s] instanceof i && l(a[s]);
                        else a instanceof i && n !== a && l(a);
                    },
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({
                a11y: {
                    enabled: !0,
                    notificationClass: "swiper-notification",
                    prevSlideMessage: "Previous slide",
                    nextSlideMessage: "Next slide",
                    firstSlideMessage: "This is the first slide",
                    lastSlideMessage: "This is the last slide",
                    paginationBulletMessage: "Go to slide {{index}}",
                    slideLabelMessage: "{{index}} / {{slidesLength}}",
                    containerMessage: null,
                    containerRoleDescriptionMessage: null,
                    itemRoleDescriptionMessage: null,
                    slideRole: "group",
                    id: null,
                },
            }),
                (t.a11y = { clicked: !1 });
            let a = null;
            function s(e) {
                let t = a;
                0 !== t.length && ((t.innerHTML = ""), (t.innerHTML = e));
            }
            let o = (e) => (Array.isArray(e) ? e : [e]).filter((e) => !!e);
            function l(e) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("tabIndex", "0");
                });
            }
            function u(e) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("tabIndex", "-1");
                });
            }
            function d(e, t) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("role", t);
                });
            }
            function c(e, t) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("aria-roledescription", t);
                });
            }
            function p(e, t) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("aria-label", t);
                });
            }
            function f(e) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("aria-disabled", !0);
                });
            }
            function h(e) {
                (e = o(e)).forEach((e) => {
                    e.setAttribute("aria-disabled", !1);
                });
            }
            function m(e) {
                if (13 !== e.keyCode && 32 !== e.keyCode) return;
                let n = t.params.a11y,
                    i = e.target;
                (t.pagination && t.pagination.el && (i === t.pagination.el || t.pagination.el.contains(e.target)) && !e.target.matches(V(t.params.pagination.bulletClass))) ||
                    (t.navigation && t.navigation.nextEl && i === t.navigation.nextEl && ((t.isEnd && !t.params.loop) || t.slideNext(), t.isEnd ? s(n.lastSlideMessage) : s(n.nextSlideMessage)),
                    t.navigation && t.navigation.prevEl && i === t.navigation.prevEl && ((t.isBeginning && !t.params.loop) || t.slidePrev(), t.isBeginning ? s(n.firstSlideMessage) : s(n.prevSlideMessage)),
                    t.pagination && i.matches(V(t.params.pagination.bulletClass)) && i.click());
            }
            function v() {
                return t.pagination && t.pagination.bullets && t.pagination.bullets.length;
            }
            function $() {
                return v() && t.params.pagination.clickable;
            }
            let b = (e, t, n) => {
                    l(e),
                        "BUTTON" !== e.tagName && (d(e, "button"), e.addEventListener("keydown", m)),
                        p(e, n),
                        (function (e, t) {
                            (e = o(e)).forEach((e) => {
                                e.setAttribute("aria-controls", t);
                            });
                        })(e, t);
                },
                _ = () => {
                    t.a11y.clicked = !0;
                },
                w = () => {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            t.destroyed || (t.a11y.clicked = !1);
                        });
                    });
                },
                x = (e) => {
                    if (t.a11y.clicked) return;
                    let n = e.target.closest(`.${t.params.slideClass}, swiper-slide`);
                    if (!n || !t.slides.includes(n)) return;
                    let i = t.slides.indexOf(n) === t.activeIndex,
                        a = t.params.watchSlidesProgress && t.visibleSlides && t.visibleSlides.includes(n);
                    i || a || (e.sourceCapabilities && e.sourceCapabilities.firesTouchEvents) || (t.isHorizontal() ? (t.el.scrollLeft = 0) : (t.el.scrollTop = 0), t.slideTo(t.slides.indexOf(n), 0));
                },
                S = () => {
                    let e = t.params.a11y;
                    e.itemRoleDescriptionMessage && c(t.slides, e.itemRoleDescriptionMessage), e.slideRole && d(t.slides, e.slideRole);
                    let n = t.slides.length;
                    e.slideLabelMessage &&
                        t.slides.forEach((i, a) => {
                            let s = t.params.loop ? parseInt(i.getAttribute("data-swiper-slide-index"), 10) : a;
                            p(i, e.slideLabelMessage.replace(/\{\{index\}\}/, s + 1).replace(/\{\{slidesLength\}\}/, n));
                        });
                };
            i("beforeInit", () => {
                (a = g("span", t.params.a11y.notificationClass)).setAttribute("aria-live", "assertive"), a.setAttribute("aria-atomic", "true");
            }),
                i("afterInit", () => {
                    t.params.a11y.enabled &&
                        (() => {
                            var e, n;
                            let i = t.params.a11y;
                            t.el.append(a);
                            let s = t.el;
                            i.containerRoleDescriptionMessage && c(s, i.containerRoleDescriptionMessage), i.containerMessage && p(s, i.containerMessage);
                            let l = t.wrapperEl,
                                u = i.id || l.getAttribute("id") || `swiper-wrapper-${((e = 16), "x".repeat(e).replace(/x/g, () => Math.round(16 * Math.random()).toString(16)))}`,
                                d = t.params.autoplay && t.params.autoplay.enabled ? "off" : "polite";
                            (n = u),
                                o(l).forEach((e) => {
                                    e.setAttribute("id", n);
                                }),
                                (function (e, t) {
                                    (e = o(e)).forEach((e) => {
                                        e.setAttribute("aria-live", t);
                                    });
                                })(l, d),
                                S();
                            let { nextEl: f, prevEl: h } = t.navigation ? t.navigation : {};
                            (f = o(f)),
                                (h = o(h)),
                                f && f.forEach((e) => b(e, u, i.nextSlideMessage)),
                                h && h.forEach((e) => b(e, u, i.prevSlideMessage)),
                                $() &&
                                    (Array.isArray(t.pagination.el) ? t.pagination.el : [t.pagination.el]).forEach((e) => {
                                        e.addEventListener("keydown", m);
                                    }),
                                t.el.addEventListener("focus", x, !0),
                                t.el.addEventListener("pointerdown", _, !0),
                                t.el.addEventListener("pointerup", w, !0);
                        })();
                }),
                i("slidesLengthChange snapGridLengthChange slidesGridLengthChange", () => {
                    t.params.a11y.enabled && S();
                }),
                i("fromEdge toEdge afterInit lock unlock", () => {
                    t.params.a11y.enabled &&
                        (function () {
                            if (t.params.loop || t.params.rewind || !t.navigation) return;
                            let { nextEl: e, prevEl: n } = t.navigation;
                            n && (t.isBeginning ? (f(n), u(n)) : (h(n), l(n))), e && (t.isEnd ? (f(e), u(e)) : (h(e), l(e)));
                        })();
                }),
                i("paginationUpdate", () => {
                    t.params.a11y.enabled &&
                        (function () {
                            let e = t.params.a11y;
                            v() &&
                                t.pagination.bullets.forEach((n) => {
                                    t.params.pagination.clickable && (l(n), t.params.pagination.renderBullet || (d(n, "button"), p(n, e.paginationBulletMessage.replace(/\{\{index\}\}/, y(n) + 1)))),
                                        n.matches(V(t.params.pagination.bulletActiveClass)) ? n.setAttribute("aria-current", "true") : n.removeAttribute("aria-current");
                                });
                        })();
                }),
                i("destroy", () => {
                    t.params.a11y.enabled &&
                        (function () {
                            a && a.remove();
                            let { nextEl: e, prevEl: n } = t.navigation ? t.navigation : {};
                            (e = o(e)),
                                (n = o(n)),
                                e && e.forEach((e) => e.removeEventListener("keydown", m)),
                                n && n.forEach((e) => e.removeEventListener("keydown", m)),
                                $() &&
                                    (Array.isArray(t.pagination.el) ? t.pagination.el : [t.pagination.el]).forEach((e) => {
                                        e.removeEventListener("keydown", m);
                                    }),
                                t.el.removeEventListener("focus", x, !0),
                                t.el.removeEventListener("pointerdown", _, !0),
                                t.el.removeEventListener("pointerup", w, !0);
                        })();
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ history: { enabled: !1, root: "", replaceState: !1, key: "slides", keepQuery: !1 } });
            let a = !1,
                o = {},
                l = (e) =>
                    e
                        .toString()
                        .replace(/\s+/g, "-")
                        .replace(/[^\w-]+/g, "")
                        .replace(/--+/g, "-")
                        .replace(/^-+/, "")
                        .replace(/-+$/, ""),
                u = (e) => {
                    let t = s(),
                        n;
                    n = e ? new URL(e) : t.location;
                    let i = n.pathname
                            .slice(1)
                            .split("/")
                            .filter((e) => "" !== e),
                        a = i.length;
                    return { key: i[a - 2], value: i[a - 1] };
                },
                d = (e, n) => {
                    let i = s();
                    if (!a || !t.params.history.enabled) return;
                    let o;
                    o = t.params.url ? new URL(t.params.url) : i.location;
                    let u = t.slides[n],
                        d = l(u.getAttribute("data-history"));
                    if (t.params.history.root.length > 0) {
                        let c = t.params.history.root;
                        "/" === c[c.length - 1] && (c = c.slice(0, c.length - 1)), (d = `${c}/${e ? `${e}/` : ""}${d}`);
                    } else o.pathname.includes(e) || (d = `${e ? `${e}/` : ""}${d}`);
                    t.params.history.keepQuery && (d += o.search);
                    let p = i.history.state;
                    (p && p.value === d) || (t.params.history.replaceState ? i.history.replaceState({ value: d }, null, d) : i.history.pushState({ value: d }, null, d));
                },
                c = (e, n, i) => {
                    if (n)
                        for (let a = 0, s = t.slides.length; a < s; a += 1) {
                            let o = t.slides[a];
                            if (l(o.getAttribute("data-history")) === n) {
                                let u = t.getSlideIndex(o);
                                t.slideTo(u, e, i);
                            }
                        }
                    else t.slideTo(0, e, i);
                },
                p = () => {
                    (o = u(t.params.url)), c(t.params.speed, o.value, !1);
                };
            i("init", () => {
                t.params.history.enabled &&
                    (() => {
                        let e = s();
                        if (t.params.history) {
                            if (!e.history || !e.history.pushState) return (t.params.history.enabled = !1), void (t.params.hashNavigation.enabled = !0);
                            (a = !0), ((o = u(t.params.url)).key || o.value) && c(0, o.value, t.params.runCallbacksOnInit), t.params.history.replaceState || e.addEventListener("popstate", p);
                        }
                    })();
            }),
                i("destroy", () => {
                    t.params.history.enabled &&
                        (() => {
                            let e = s();
                            t.params.history.replaceState || e.removeEventListener("popstate", p);
                        })();
                }),
                i("transitionEnd _freeModeNoMomentumRelease", () => {
                    a && d(t.params.history.key, t.activeIndex);
                }),
                i("slideChange", () => {
                    a && t.params.cssMode && d(t.params.history.key, t.activeIndex);
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, emit: a, on: o } = e,
                l = !1,
                u = i(),
                d = s();
            n({
                hashNavigation: {
                    enabled: !1,
                    replaceState: !1,
                    watchState: !1,
                    getSlideIndex(e, n) {
                        if (t.virtual && t.params.virtual.enabled) {
                            let i = t.slides.filter((e) => e.getAttribute("data-hash") === n)[0];
                            return i ? parseInt(i.getAttribute("data-swiper-slide-index"), 10) : 0;
                        }
                        return t.getSlideIndex(m(t.slidesEl, `.${t.params.slideClass}[data-hash="${n}"], swiper-slide[data-hash="${n}"]`)[0]);
                    },
                },
            });
            let c = () => {
                    a("hashChange");
                    let e = u.location.hash.replace("#", ""),
                        n = t.virtual && t.params.virtual.enabled ? t.slidesEl.querySelector(`[data-swiper-slide-index="${t.activeIndex}"]`) : t.slides[t.activeIndex];
                    if (e !== (n ? n.getAttribute("data-hash") : "")) {
                        let i = t.params.hashNavigation.getSlideIndex(t, e);
                        if (void 0 === i || Number.isNaN(i)) return;
                        t.slideTo(i);
                    }
                },
                p = () => {
                    if (!l || !t.params.hashNavigation.enabled) return;
                    let e = t.virtual && t.params.virtual.enabled ? t.slidesEl.querySelector(`[data-swiper-slide-index="${t.activeIndex}"]`) : t.slides[t.activeIndex],
                        n = e ? e.getAttribute("data-hash") || e.getAttribute("data-history") : "";
                    t.params.hashNavigation.replaceState && d.history && d.history.replaceState ? (d.history.replaceState(null, null, `#${n}` || ""), a("hashSet")) : ((u.location.hash = n || ""), a("hashSet"));
                };
            o("init", () => {
                t.params.hashNavigation.enabled &&
                    (() => {
                        if (!t.params.hashNavigation.enabled || (t.params.history && t.params.history.enabled)) return;
                        l = !0;
                        let e = u.location.hash.replace("#", "");
                        if (e) {
                            let n = t.params.hashNavigation.getSlideIndex(t, e);
                            t.slideTo(n || 0, 0, t.params.runCallbacksOnInit, !0);
                        }
                        t.params.hashNavigation.watchState && d.addEventListener("hashchange", c);
                    })();
            }),
                o("destroy", () => {
                    t.params.hashNavigation.enabled && t.params.hashNavigation.watchState && d.removeEventListener("hashchange", c);
                }),
                o("transitionEnd _freeModeNoMomentumRelease", () => {
                    l && p();
                }),
                o("slideChange", () => {
                    l && t.params.cssMode && p();
                });
        },
        function (e) {
            let t,
                n,
                { swiper: a, extendParams: s, on: o, emit: l, params: u } = e;
            (a.autoplay = { running: !1, paused: !1, timeLeft: 0 }), s({ autoplay: { enabled: !1, delay: 3e3, waitForTransition: !0, disableOnInteraction: !0, stopOnLastSlide: !1, reverseDirection: !1, pauseOnMouseEnter: !1 } });
            let d,
                c,
                p,
                f,
                h,
                m,
                g,
                v = u && u.autoplay ? u.autoplay.delay : 3e3,
                $ = u && u.autoplay ? u.autoplay.delay : 3e3,
                y = new Date().getTime;
            function b(e) {
                a && !a.destroyed && a.wrapperEl && e.target === a.wrapperEl && (a.wrapperEl.removeEventListener("transitionend", b), C());
            }
            let _ = () => {
                    if (a.destroyed || !a.autoplay.running) return;
                    a.autoplay.paused ? (c = !0) : c && (($ = d), (c = !1));
                    let e = a.autoplay.paused ? d : y + $ - new Date().getTime();
                    (a.autoplay.timeLeft = e),
                        l("autoplayTimeLeft", e, e / v),
                        (n = requestAnimationFrame(() => {
                            _();
                        }));
                },
                w = (e) => {
                    if (a.destroyed || !a.autoplay.running) return;
                    cancelAnimationFrame(n), _();
                    let i = void 0 === e ? a.params.autoplay.delay : e;
                    (v = a.params.autoplay.delay), ($ = a.params.autoplay.delay);
                    let s = (() => {
                        let e;
                        if ((e = a.virtual && a.params.virtual.enabled ? a.slides.filter((e) => e.classList.contains("swiper-slide-active"))[0] : a.slides[a.activeIndex])) return parseInt(e.getAttribute("data-swiper-autoplay"), 10);
                    })();
                    !Number.isNaN(s) && s > 0 && void 0 === e && ((i = s), (v = s), ($ = s)), (d = i);
                    let o = a.params.speed,
                        u = () => {
                            a &&
                                !a.destroyed &&
                                (a.params.autoplay.reverseDirection
                                    ? !a.isBeginning || a.params.loop || a.params.rewind
                                        ? (a.slidePrev(o, !0, !0), l("autoplay"))
                                        : a.params.autoplay.stopOnLastSlide || (a.slideTo(a.slides.length - 1, o, !0, !0), l("autoplay"))
                                    : !a.isEnd || a.params.loop || a.params.rewind
                                    ? (a.slideNext(o, !0, !0), l("autoplay"))
                                    : a.params.autoplay.stopOnLastSlide || (a.slideTo(0, o, !0, !0), l("autoplay")),
                                a.params.cssMode &&
                                    ((y = new Date().getTime()),
                                    requestAnimationFrame(() => {
                                        w();
                                    })));
                        };
                    return (
                        i > 0
                            ? (clearTimeout(t),
                              (t = setTimeout(() => {
                                  u();
                              }, i)))
                            : requestAnimationFrame(() => {
                                  u();
                              }),
                        i
                    );
                },
                x = () => {
                    (a.autoplay.running = !0), w(), l("autoplayStart");
                },
                S = () => {
                    (a.autoplay.running = !1), clearTimeout(t), cancelAnimationFrame(n), l("autoplayStop");
                },
                T = (e, n) => {
                    if (a.destroyed || !a.autoplay.running) return;
                    clearTimeout(t), e || (g = !0);
                    let i = () => {
                        l("autoplayPause"), a.params.autoplay.waitForTransition ? a.wrapperEl.addEventListener("transitionend", b) : C();
                    };
                    if (((a.autoplay.paused = !0), n)) return m && (d = a.params.autoplay.delay), (m = !1), void i();
                    let s = d || a.params.autoplay.delay;
                    (d = s - (new Date().getTime() - y)), (a.isEnd && d < 0 && !a.params.loop) || (d < 0 && (d = 0), i());
                },
                C = () => {
                    (a.isEnd && d < 0 && !a.params.loop) || a.destroyed || !a.autoplay.running || ((y = new Date().getTime()), g ? ((g = !1), w(d)) : w(), (a.autoplay.paused = !1), l("autoplayResume"));
                },
                E = () => {
                    if (a.destroyed || !a.autoplay.running) return;
                    let e = i();
                    "hidden" === e.visibilityState && ((g = !0), T(!0)), "visible" === e.visibilityState && C();
                },
                D = (e) => {
                    "mouse" === e.pointerType && ((g = !0), a.animating || a.autoplay.paused || T(!0));
                },
                k = (e) => {
                    "mouse" === e.pointerType && a.autoplay.paused && C();
                };
            o("init", () => {
                a.params.autoplay.enabled &&
                    (a.params.autoplay.pauseOnMouseEnter && (a.el.addEventListener("pointerenter", D), a.el.addEventListener("pointerleave", k)), i().addEventListener("visibilitychange", E), (y = new Date().getTime()), x());
            }),
                o("destroy", () => {
                    a.el.removeEventListener("pointerenter", D), a.el.removeEventListener("pointerleave", k), i().removeEventListener("visibilitychange", E), a.autoplay.running && S();
                }),
                o("beforeTransitionStart", (e, t, n) => {
                    !a.destroyed && a.autoplay.running && (n || !a.params.autoplay.disableOnInteraction ? T(!0, !0) : S());
                }),
                o("sliderFirstMove", () => {
                    !a.destroyed &&
                        a.autoplay.running &&
                        (a.params.autoplay.disableOnInteraction
                            ? S()
                            : ((p = !0),
                              (f = !1),
                              (g = !1),
                              (h = setTimeout(() => {
                                  (g = !0), (f = !0), T(!0);
                              }, 200))));
                }),
                o("touchEnd", () => {
                    if (!a.destroyed && a.autoplay.running && p) {
                        if ((clearTimeout(h), clearTimeout(t), a.params.autoplay.disableOnInteraction)) return (f = !1), void (p = !1);
                        f && a.params.cssMode && C(), (f = !1), (p = !1);
                    }
                }),
                o("slideChange", () => {
                    !a.destroyed && a.autoplay.running && (m = !0);
                }),
                Object.assign(a.autoplay, { start: x, stop: S, pause: T, resume: C });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: a } = e;
            n({ thumbs: { swiper: null, multipleActiveThumbs: !0, autoScrollOffset: 0, slideThumbActiveClass: "swiper-slide-thumb-active", thumbsContainerClass: "swiper-thumbs" } });
            let s = !1,
                o = !1;
            function l() {
                let e = t.thumbs.swiper;
                if (!e || e.destroyed) return;
                let n = e.clickedIndex,
                    i = e.clickedSlide;
                if ((i && i.classList.contains(t.params.thumbs.slideThumbActiveClass)) || null == n) return;
                let a;
                (a = e.params.loop ? parseInt(e.clickedSlide.getAttribute("data-swiper-slide-index"), 10) : n), t.params.loop ? t.slideToLoop(a) : t.slideTo(a);
            }
            function u() {
                let { thumbs: e } = t.params;
                if (s) return !1;
                s = !0;
                let n = t.constructor;
                if (e.swiper instanceof n)
                    (t.thumbs.swiper = e.swiper),
                        Object.assign(t.thumbs.swiper.originalParams, { watchSlidesProgress: !0, slideToClickedSlide: !1 }),
                        Object.assign(t.thumbs.swiper.params, { watchSlidesProgress: !0, slideToClickedSlide: !1 }),
                        t.thumbs.swiper.update();
                else if (d(e.swiper)) {
                    let i = Object.assign({}, e.swiper);
                    Object.assign(i, { watchSlidesProgress: !0, slideToClickedSlide: !1 }), (t.thumbs.swiper = new n(i)), (o = !0);
                }
                return t.thumbs.swiper.el.classList.add(t.params.thumbs.thumbsContainerClass), t.thumbs.swiper.on("tap", l), !0;
            }
            function c(e) {
                let n = t.thumbs.swiper;
                if (!n || n.destroyed) return;
                let i = "auto" === n.params.slidesPerView ? n.slidesPerViewDynamic() : n.params.slidesPerView,
                    a = 1,
                    s = t.params.thumbs.slideThumbActiveClass;
                if (
                    (t.params.slidesPerView > 1 && !t.params.centeredSlides && (a = t.params.slidesPerView),
                    t.params.thumbs.multipleActiveThumbs || (a = 1),
                    (a = Math.floor(a)),
                    n.slides.forEach((e) => e.classList.remove(s)),
                    n.params.loop || (n.params.virtual && n.params.virtual.enabled))
                )
                    for (let o = 0; o < a; o += 1)
                        m(n.slidesEl, `[data-swiper-slide-index="${t.realIndex + o}"]`).forEach((e) => {
                            e.classList.add(s);
                        });
                else for (let l = 0; l < a; l += 1) n.slides[t.realIndex + l] && n.slides[t.realIndex + l].classList.add(s);
                let u = t.params.thumbs.autoScrollOffset,
                    d = u && !n.params.loop;
                if (t.realIndex !== n.realIndex || d) {
                    let c = n.activeIndex,
                        p,
                        f;
                    if (n.params.loop) {
                        let h = n.slides.filter((e) => e.getAttribute("data-swiper-slide-index") === `${t.realIndex}`)[0];
                        (p = n.slides.indexOf(h)), (f = t.activeIndex > t.previousIndex ? "next" : "prev");
                    } else f = (p = t.realIndex) > t.previousIndex ? "next" : "prev";
                    d && (p += "next" === f ? u : -1 * u),
                        n.visibleSlidesIndexes &&
                            0 > n.visibleSlidesIndexes.indexOf(p) &&
                            (n.params.centeredSlides ? (p = p > c ? p - Math.floor(i / 2) + 1 : p + Math.floor(i / 2) - 1) : p > c && n.params.slidesPerGroup, n.slideTo(p, e ? 0 : void 0));
                }
            }
            (t.thumbs = { swiper: null }),
                a("beforeInit", () => {
                    let { thumbs: e } = t.params;
                    if (e && e.swiper) {
                        if ("string" == typeof e.swiper || e.swiper instanceof HTMLElement) {
                            let n = i(),
                                a = () => {
                                    let i = "string" == typeof e.swiper ? n.querySelector(e.swiper) : e.swiper;
                                    if (i && i.swiper) (e.swiper = i.swiper), u(), c(!0);
                                    else if (i) {
                                        let a = (n) => {
                                            (e.swiper = n.detail[0]), i.removeEventListener("init", a), u(), c(!0), e.swiper.update(), t.update();
                                        };
                                        i.addEventListener("init", a);
                                    }
                                    return i;
                                },
                                s = () => {
                                    t.destroyed || a() || requestAnimationFrame(s);
                                };
                            requestAnimationFrame(s);
                        } else u(), c(!0);
                    }
                }),
                a("slideChange update resize observerUpdate", () => {
                    c();
                }),
                a("setTransition", (e, n) => {
                    let i = t.thumbs.swiper;
                    i && !i.destroyed && i.setTransition(n);
                }),
                a("beforeDestroy", () => {
                    let e = t.thumbs.swiper;
                    e && !e.destroyed && o && e.destroy();
                }),
                Object.assign(t.thumbs, { init: u, update: c });
        },
        function (e) {
            let { swiper: t, extendParams: n, emit: i, once: a } = e;
            n({ freeMode: { enabled: !1, momentum: !0, momentumRatio: 1, momentumBounce: !0, momentumBounceRatio: 1, momentumVelocityRatio: 1, sticky: !1, minimumVelocity: 0.02 } }),
                Object.assign(t, {
                    freeMode: {
                        onTouchStart: function () {
                            if (t.params.cssMode) return;
                            let e = t.getTranslate();
                            t.setTranslate(e), t.setTransition(0), (t.touchEventsData.velocities.length = 0), t.freeMode.onTouchEnd({ currentPos: t.rtl ? t.translate : -t.translate });
                        },
                        onTouchMove: function () {
                            if (t.params.cssMode) return;
                            let { touchEventsData: e, touches: n } = t;
                            0 === e.velocities.length && e.velocities.push({ position: n[t.isHorizontal() ? "startX" : "startY"], time: e.touchStartTime }),
                                e.velocities.push({ position: n[t.isHorizontal() ? "currentX" : "currentY"], time: l() });
                        },
                        onTouchEnd: function (e) {
                            let { currentPos: n } = e;
                            if (t.params.cssMode) return;
                            let { params: s, wrapperEl: o, rtlTranslate: u, snapGrid: d, touchEventsData: c } = t,
                                p = l() - c.touchStartTime;
                            if (n < -t.minTranslate()) t.slideTo(t.activeIndex);
                            else if (n > -t.maxTranslate()) t.slides.length < d.length ? t.slideTo(d.length - 1) : t.slideTo(t.slides.length - 1);
                            else {
                                if (s.freeMode.momentum) {
                                    if (c.velocities.length > 1) {
                                        let f = c.velocities.pop(),
                                            h = c.velocities.pop(),
                                            m = f.position - h.position,
                                            g = f.time - h.time;
                                        (t.velocity = m / g), (t.velocity /= 2), Math.abs(t.velocity) < s.freeMode.minimumVelocity && (t.velocity = 0), (g > 150 || l() - f.time > 300) && (t.velocity = 0);
                                    } else t.velocity = 0;
                                    (t.velocity *= s.freeMode.momentumVelocityRatio), (c.velocities.length = 0);
                                    let v = 1e3 * s.freeMode.momentumRatio,
                                        $ = t.velocity * v,
                                        y = t.translate + $;
                                    u && (y = -y);
                                    let b,
                                        w = !1,
                                        x = 20 * Math.abs(t.velocity) * s.freeMode.momentumBounceRatio,
                                        S;
                                    if (y < t.maxTranslate())
                                        s.freeMode.momentumBounce ? (y + t.maxTranslate() < -x && (y = t.maxTranslate() - x), (b = t.maxTranslate()), (w = !0), (c.allowMomentumBounce = !0)) : (y = t.maxTranslate()),
                                            s.loop && s.centeredSlides && (S = !0);
                                    else if (y > t.minTranslate())
                                        s.freeMode.momentumBounce ? (y - t.minTranslate() > x && (y = t.minTranslate() + x), (b = t.minTranslate()), (w = !0), (c.allowMomentumBounce = !0)) : (y = t.minTranslate()),
                                            s.loop && s.centeredSlides && (S = !0);
                                    else if (s.freeMode.sticky) {
                                        let T;
                                        for (let C = 0; C < d.length; C += 1)
                                            if (d[C] > -y) {
                                                T = C;
                                                break;
                                            }
                                        y = -(y = Math.abs(d[T] - y) < Math.abs(d[T - 1] - y) || "next" === t.swipeDirection ? d[T] : d[T - 1]);
                                    }
                                    if (
                                        (S &&
                                            a("transitionEnd", () => {
                                                t.loopFix();
                                            }),
                                        0 !== t.velocity)
                                    ) {
                                        if (((v = u ? Math.abs((-y - t.translate) / t.velocity) : Math.abs((y - t.translate) / t.velocity)), s.freeMode.sticky)) {
                                            let E = Math.abs((u ? -y : y) - t.translate),
                                                D = t.slidesSizesGrid[t.activeIndex];
                                            v = E < D ? s.speed : E < 2 * D ? 1.5 * s.speed : 2.5 * s.speed;
                                        }
                                    } else if (s.freeMode.sticky) return void t.slideToClosest();
                                    s.freeMode.momentumBounce && w
                                        ? (t.updateProgress(b),
                                          t.setTransition(v),
                                          t.setTranslate(y),
                                          t.transitionStart(!0, t.swipeDirection),
                                          (t.animating = !0),
                                          _(o, () => {
                                              t &&
                                                  !t.destroyed &&
                                                  c.allowMomentumBounce &&
                                                  (i("momentumBounce"),
                                                  t.setTransition(s.speed),
                                                  setTimeout(() => {
                                                      t.setTranslate(b),
                                                          _(o, () => {
                                                              t && !t.destroyed && t.transitionEnd();
                                                          });
                                                  }, 0));
                                          }))
                                        : t.velocity
                                        ? (i("_freeModeNoMomentumRelease"),
                                          t.updateProgress(y),
                                          t.setTransition(v),
                                          t.setTranslate(y),
                                          t.transitionStart(!0, t.swipeDirection),
                                          t.animating ||
                                              ((t.animating = !0),
                                              _(o, () => {
                                                  t && !t.destroyed && t.transitionEnd();
                                              })))
                                        : t.updateProgress(y),
                                        t.updateActiveIndex(),
                                        t.updateSlidesClasses();
                                } else {
                                    if (s.freeMode.sticky) return void t.slideToClosest();
                                    s.freeMode && i("_freeModeNoMomentumRelease");
                                }
                                (!s.freeMode.momentum || p >= s.longSwipesMs) && (t.updateProgress(), t.updateActiveIndex(), t.updateSlidesClasses());
                            }
                        },
                    },
                });
        },
        function (e) {
            let t,
                n,
                i,
                { swiper: a, extendParams: s } = e;
            s({ grid: { rows: 1, fill: "column" } });
            let o = () => {
                let e = a.params.spaceBetween;
                return "string" == typeof e && e.indexOf("%") >= 0 ? (e = (parseFloat(e.replace("%", "")) / 100) * a.size) : "string" == typeof e && (e = parseFloat(e)), e;
            };
            a.grid = {
                initSlides(e) {
                    let { slidesPerView: s } = a.params,
                        { rows: o, fill: l } = a.params.grid;
                    (i = Math.floor(e / o)), (t = Math.floor(e / o) === e / o ? e : Math.ceil(e / o) * o), "auto" !== s && "row" === l && (t = Math.max(t, s * o)), (n = t / o);
                },
                updateSlide(e, s, l, u) {
                    let { slidesPerGroup: d } = a.params,
                        c = o(),
                        { rows: p, fill: f } = a.params.grid,
                        h,
                        m,
                        g;
                    if ("row" === f && d > 1) {
                        let v = Math.floor(e / (d * p)),
                            $ = e - p * d * v,
                            y = 0 === v ? d : Math.min(Math.ceil((l - v * p * d) / p), d);
                        (g = Math.floor($ / y)), (h = (m = $ - g * y + v * d) + (g * t) / p), (s.style.order = h);
                    } else "column" === f ? ((m = Math.floor(e / p)), (g = e - m * p), (m > i || (m === i && g === p - 1)) && (g += 1) >= p && ((g = 0), (m += 1))) : ((g = Math.floor(e / n)), (m = e - g * n));
                    (s.row = g), (s.column = m), (s.style[u("margin-top")] = 0 !== g ? c && `${c}px` : "");
                },
                updateWrapperSize(e, n, i) {
                    let { centeredSlides: s, roundLengths: l } = a.params,
                        u = o(),
                        { rows: d } = a.params.grid;
                    if (((a.virtualSize = (e + u) * t), (a.virtualSize = Math.ceil(a.virtualSize / d) - u), (a.wrapperEl.style[i("width")] = `${a.virtualSize + u}px`), s)) {
                        let c = [];
                        for (let p = 0; p < n.length; p += 1) {
                            let f = n[p];
                            l && (f = Math.floor(f)), n[p] < a.virtualSize + n[0] && c.push(f);
                        }
                        n.splice(0, n.length), n.push(...c);
                    }
                },
            };
        },
        function (e) {
            let { swiper: t } = e;
            Object.assign(t, { appendSlide: X.bind(t), prependSlide: Y.bind(t), addSlide: J.bind(t), removeSlide: K.bind(t), removeAllSlides: Z.bind(t) });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ fadeEffect: { crossFade: !1 } }),
                Q({
                    effect: "fade",
                    swiper: t,
                    on: i,
                    setTranslate() {
                        let { slides: e } = t;
                        t.params.fadeEffect;
                        for (let n = 0; n < e.length; n += 1) {
                            let i = t.slides[n],
                                a = -i.swiperSlideOffset;
                            t.params.virtualTranslate || (a -= t.translate);
                            let s = 0;
                            t.isHorizontal() || ((s = a), (a = 0));
                            let o = t.params.fadeEffect.crossFade ? Math.max(1 - Math.abs(i.progress), 0) : 1 + Math.min(Math.max(i.progress, -1), 0),
                                l = ee(0, i);
                            (l.style.opacity = o), (l.style.transform = `translate3d(${a}px, ${s}px, 0px)`);
                        }
                    },
                    setTransition(e) {
                        let n = t.slides.map((e) => h(e));
                        n.forEach((t) => {
                            t.style.transitionDuration = `${e}ms`;
                        }),
                            et({ swiper: t, duration: e, transformElements: n, allSlides: !0 });
                    },
                    overwriteParams: () => ({ slidesPerView: 1, slidesPerGroup: 1, watchSlidesProgress: !0, spaceBetween: 0, virtualTranslate: !t.params.cssMode }),
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ cubeEffect: { slideShadows: !0, shadow: !0, shadowOffset: 20, shadowScale: 0.94 } });
            let a = (e, t, n) => {
                let i = n ? e.querySelector(".swiper-slide-shadow-left") : e.querySelector(".swiper-slide-shadow-top"),
                    a = n ? e.querySelector(".swiper-slide-shadow-right") : e.querySelector(".swiper-slide-shadow-bottom");
                i || ((i = g("div", ("swiper-slide-shadow-cube swiper-slide-shadow-" + (n ? "left" : "top")).split(" "))), e.append(i)),
                    a || ((a = g("div", ("swiper-slide-shadow-cube swiper-slide-shadow-" + (n ? "right" : "bottom")).split(" "))), e.append(a)),
                    i && (i.style.opacity = Math.max(-t, 0)),
                    a && (a.style.opacity = Math.max(t, 0));
            };
            Q({
                effect: "cube",
                swiper: t,
                on: i,
                setTranslate() {
                    let { el: e, wrapperEl: n, slides: i, width: s, height: o, rtlTranslate: l, size: u, browser: d } = t,
                        c = t.params.cubeEffect,
                        p = t.isHorizontal(),
                        f = t.virtual && t.params.virtual.enabled,
                        h,
                        m = 0;
                    c.shadow &&
                        (p
                            ? ((h = t.wrapperEl.querySelector(".swiper-cube-shadow")) || ((h = g("div", "swiper-cube-shadow")), t.wrapperEl.append(h)), (h.style.height = `${s}px`))
                            : (h = e.querySelector(".swiper-cube-shadow")) || ((h = g("div", "swiper-cube-shadow")), e.append(h)));
                    for (let v = 0; v < i.length; v += 1) {
                        let $ = i[v],
                            y = v;
                        f && (y = parseInt($.getAttribute("data-swiper-slide-index"), 10));
                        let b = 90 * y,
                            _ = Math.floor(b / 360);
                        l && (_ = Math.floor(-(b = -b) / 360));
                        let w = Math.max(Math.min($.progress, 1), -1),
                            x = 0,
                            S = 0,
                            T = 0;
                        y % 4 == 0 ? ((x = -(4 * _) * u), (T = 0)) : (y - 1) % 4 == 0 ? ((x = 0), (T = -(4 * _) * u)) : (y - 2) % 4 == 0 ? ((x = u + 4 * _ * u), (T = u)) : (y - 3) % 4 == 0 && ((x = -u), (T = 3 * u + 4 * u * _)),
                            l && (x = -x),
                            p || ((S = x), (x = 0));
                        let C = `rotateX(${p ? 0 : -b}deg) rotateY(${p ? b : 0}deg) translate3d(${x}px, ${S}px, ${T}px)`;
                        w <= 1 && w > -1 && ((m = 90 * y + 90 * w), l && (m = -(90 * y) - 90 * w)), ($.style.transform = C), c.slideShadows && a($, w, p);
                    }
                    if (((n.style.transformOrigin = `50% 50% -${u / 2}px`), (n.style["-webkit-transform-origin"] = `50% 50% -${u / 2}px`), c.shadow)) {
                        if (p) h.style.transform = `translate3d(0px, ${s / 2 + c.shadowOffset}px, ${-s / 2}px) rotateX(90deg) rotateZ(0deg) scale(${c.shadowScale})`;
                        else {
                            let E = Math.abs(m) - 90 * Math.floor(Math.abs(m) / 90),
                                D = c.shadowScale,
                                k = c.shadowScale / (1.5 - (Math.sin((2 * E * Math.PI) / 360) / 2 + Math.cos((2 * E * Math.PI) / 360) / 2)),
                                L = c.shadowOffset;
                            h.style.transform = `scale3d(${D}, 1, ${k}) translate3d(0px, ${o / 2 + L}px, ${-o / 2 / k}px) rotateX(-90deg)`;
                        }
                    }
                    let A = (d.isSafari || d.isWebView) && d.needPerspectiveFix ? -u / 2 : 0;
                    (n.style.transform = `translate3d(0px,0,${A}px) rotateX(${t.isHorizontal() ? 0 : m}deg) rotateY(${t.isHorizontal() ? -m : 0}deg)`), n.style.setProperty("--swiper-cube-translate-z", `${A}px`);
                },
                setTransition(e) {
                    let { el: n, slides: i } = t;
                    if (
                        (i.forEach((t) => {
                            (t.style.transitionDuration = `${e}ms`),
                                t.querySelectorAll(".swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left").forEach((t) => {
                                    t.style.transitionDuration = `${e}ms`;
                                });
                        }),
                        t.params.cubeEffect.shadow && !t.isHorizontal())
                    ) {
                        let a = n.querySelector(".swiper-cube-shadow");
                        a && (a.style.transitionDuration = `${e}ms`);
                    }
                },
                recreateShadows() {
                    let e = t.isHorizontal();
                    t.slides.forEach((t) => {
                        let n = Math.max(Math.min(t.progress, 1), -1);
                        a(t, n, e);
                    });
                },
                getEffectParams: () => t.params.cubeEffect,
                perspective: () => !0,
                overwriteParams: () => ({ slidesPerView: 1, slidesPerGroup: 1, watchSlidesProgress: !0, resistanceRatio: 0, spaceBetween: 0, centeredSlides: !1, virtualTranslate: !0 }),
            });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ flipEffect: { slideShadows: !0, limitRotation: !0 } });
            let a = (e, n) => {
                let i = t.isHorizontal() ? e.querySelector(".swiper-slide-shadow-left") : e.querySelector(".swiper-slide-shadow-top"),
                    a = t.isHorizontal() ? e.querySelector(".swiper-slide-shadow-right") : e.querySelector(".swiper-slide-shadow-bottom");
                i || (i = en("flip", e, t.isHorizontal() ? "left" : "top")), a || (a = en("flip", e, t.isHorizontal() ? "right" : "bottom")), i && (i.style.opacity = Math.max(-n, 0)), a && (a.style.opacity = Math.max(n, 0));
            };
            Q({
                effect: "flip",
                swiper: t,
                on: i,
                setTranslate() {
                    let { slides: e, rtlTranslate: n } = t,
                        i = t.params.flipEffect;
                    for (let s = 0; s < e.length; s += 1) {
                        let o = e[s],
                            l = o.progress;
                        t.params.flipEffect.limitRotation && (l = Math.max(Math.min(o.progress, 1), -1));
                        let u = o.swiperSlideOffset,
                            d = -180 * l,
                            c = 0,
                            p = t.params.cssMode ? -u - t.translate : -u,
                            f = 0;
                        t.isHorizontal() ? n && (d = -d) : ((f = p), (p = 0), (c = -d), (d = 0)), (o.style.zIndex = -Math.abs(Math.round(l)) + e.length), i.slideShadows && a(o, l);
                        let h = `translate3d(${p}px, ${f}px, 0px) rotateX(${c}deg) rotateY(${d}deg)`;
                        ee(0, o).style.transform = h;
                    }
                },
                setTransition(e) {
                    let n = t.slides.map((e) => h(e));
                    n.forEach((t) => {
                        (t.style.transitionDuration = `${e}ms`),
                            t.querySelectorAll(".swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left").forEach((t) => {
                                t.style.transitionDuration = `${e}ms`;
                            });
                    }),
                        et({ swiper: t, duration: e, transformElements: n });
                },
                recreateShadows() {
                    t.params.flipEffect,
                        t.slides.forEach((e) => {
                            let n = e.progress;
                            t.params.flipEffect.limitRotation && (n = Math.max(Math.min(e.progress, 1), -1)), a(e, n);
                        });
                },
                getEffectParams: () => t.params.flipEffect,
                perspective: () => !0,
                overwriteParams: () => ({ slidesPerView: 1, slidesPerGroup: 1, watchSlidesProgress: !0, spaceBetween: 0, virtualTranslate: !t.params.cssMode }),
            });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ coverflowEffect: { rotate: 50, stretch: 0, depth: 100, scale: 1, modifier: 1, slideShadows: !0 } }),
                Q({
                    effect: "coverflow",
                    swiper: t,
                    on: i,
                    setTranslate() {
                        let { width: e, height: n, slides: i, slidesSizesGrid: a } = t,
                            s = t.params.coverflowEffect,
                            o = t.isHorizontal(),
                            l = t.translate,
                            u = o ? e / 2 - l : n / 2 - l,
                            d = o ? s.rotate : -s.rotate,
                            c = s.depth;
                        for (let p = 0, f = i.length; p < f; p += 1) {
                            let h = i[p],
                                m = a[p],
                                g = (u - h.swiperSlideOffset - m / 2) / m,
                                v = "function" == typeof s.modifier ? s.modifier(g) : g * s.modifier,
                                $ = o ? d * v : 0,
                                y = o ? 0 : d * v,
                                b = -c * Math.abs(v),
                                _ = s.stretch;
                            "string" == typeof _ && -1 !== _.indexOf("%") && (_ = (parseFloat(s.stretch) / 100) * m);
                            let w = o ? 0 : _ * v,
                                x = o ? _ * v : 0,
                                S = 1 - (1 - s.scale) * Math.abs(v);
                            0.001 > Math.abs(x) && (x = 0), 0.001 > Math.abs(w) && (w = 0), 0.001 > Math.abs(b) && (b = 0), 0.001 > Math.abs($) && ($ = 0), 0.001 > Math.abs(y) && (y = 0), 0.001 > Math.abs(S) && (S = 0);
                            let T = `translate3d(${x}px,${w}px,${b}px)  rotateX(${y}deg) rotateY(${$}deg) scale(${S})`;
                            if (((ee(0, h).style.transform = T), (h.style.zIndex = 1 - Math.abs(Math.round(v))), s.slideShadows)) {
                                let C = o ? h.querySelector(".swiper-slide-shadow-left") : h.querySelector(".swiper-slide-shadow-top"),
                                    E = o ? h.querySelector(".swiper-slide-shadow-right") : h.querySelector(".swiper-slide-shadow-bottom");
                                C || (C = en("coverflow", h, o ? "left" : "top")), E || (E = en("coverflow", h, o ? "right" : "bottom")), C && (C.style.opacity = v > 0 ? v : 0), E && (E.style.opacity = -v > 0 ? -v : 0);
                            }
                        }
                    },
                    setTransition(e) {
                        t.slides
                            .map((e) => h(e))
                            .forEach((t) => {
                                (t.style.transitionDuration = `${e}ms`),
                                    t.querySelectorAll(".swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left").forEach((t) => {
                                        t.style.transitionDuration = `${e}ms`;
                                    });
                            });
                    },
                    perspective: () => !0,
                    overwriteParams: () => ({ watchSlidesProgress: !0 }),
                });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({
                creativeEffect: {
                    limitProgress: 1,
                    shadowPerProgress: !1,
                    progressMultiplier: 1,
                    perspective: !0,
                    prev: { translate: [0, 0, 0], rotate: [0, 0, 0], opacity: 1, scale: 1 },
                    next: { translate: [0, 0, 0], rotate: [0, 0, 0], opacity: 1, scale: 1 },
                },
            });
            let a = (e) => ("string" == typeof e ? e : `${e}px`);
            Q({
                effect: "creative",
                swiper: t,
                on: i,
                setTranslate() {
                    let { slides: e, wrapperEl: n, slidesSizesGrid: i } = t,
                        s = t.params.creativeEffect,
                        { progressMultiplier: o } = s,
                        l = t.params.centeredSlides;
                    if (l) {
                        let u = i[0] / 2 - t.params.slidesOffsetBefore || 0;
                        n.style.transform = `translateX(calc(50% - ${u}px))`;
                    }
                    for (let d = 0; d < e.length; d += 1) {
                        let c = e[d],
                            p = c.progress,
                            f = Math.min(Math.max(c.progress, -s.limitProgress), s.limitProgress),
                            h = f;
                        l || (h = Math.min(Math.max(c.originalProgress, -s.limitProgress), s.limitProgress));
                        let m = c.swiperSlideOffset,
                            g = [t.params.cssMode ? -m - t.translate : -m, 0, 0],
                            v = [0, 0, 0],
                            $ = !1;
                        t.isHorizontal() || ((g[1] = g[0]), (g[0] = 0));
                        let y = { translate: [0, 0, 0], rotate: [0, 0, 0], scale: 1, opacity: 1 };
                        f < 0 ? ((y = s.next), ($ = !0)) : f > 0 && ((y = s.prev), ($ = !0)),
                            g.forEach((e, t) => {
                                g[t] = `calc(${e}px + (${a(y.translate[t])} * ${Math.abs(f * o)}))`;
                            }),
                            v.forEach((e, t) => {
                                v[t] = y.rotate[t] * Math.abs(f * o);
                            }),
                            (c.style.zIndex = -Math.abs(Math.round(p)) + e.length);
                        let b = g.join(", "),
                            _ = `rotateX(${v[0]}deg) rotateY(${v[1]}deg) rotateZ(${v[2]}deg)`,
                            w = h < 0 ? `scale(${1 + (1 - y.scale) * h * o})` : `scale(${1 - (1 - y.scale) * h * o})`,
                            x = h < 0 ? 1 + (1 - y.opacity) * h * o : 1 - (1 - y.opacity) * h * o,
                            S = `translate3d(${b}) ${_} ${w}`;
                        if (($ && y.shadow) || !$) {
                            let T = c.querySelector(".swiper-slide-shadow");
                            if ((!T && y.shadow && (T = en("creative", c)), T)) {
                                let C = s.shadowPerProgress ? f * (1 / s.limitProgress) : f;
                                T.style.opacity = Math.min(Math.max(Math.abs(C), 0), 1);
                            }
                        }
                        let E = ee(0, c);
                        (E.style.transform = S), (E.style.opacity = x), y.origin && (E.style.transformOrigin = y.origin);
                    }
                },
                setTransition(e) {
                    let n = t.slides.map((e) => h(e));
                    n.forEach((t) => {
                        (t.style.transitionDuration = `${e}ms`),
                            t.querySelectorAll(".swiper-slide-shadow").forEach((t) => {
                                t.style.transitionDuration = `${e}ms`;
                            });
                    }),
                        et({ swiper: t, duration: e, transformElements: n, allSlides: !0 });
                },
                perspective: () => t.params.creativeEffect.perspective,
                overwriteParams: () => ({ watchSlidesProgress: !0, virtualTranslate: !t.params.cssMode }),
            });
        },
        function (e) {
            let { swiper: t, extendParams: n, on: i } = e;
            n({ cardsEffect: { slideShadows: !0, rotate: !0, perSlideRotate: 2, perSlideOffset: 8 } }),
                Q({
                    effect: "cards",
                    swiper: t,
                    on: i,
                    setTranslate() {
                        let { slides: e, activeIndex: n, rtlTranslate: i } = t,
                            a = t.params.cardsEffect,
                            { startTranslate: s, isTouched: o } = t.touchEventsData,
                            l = i ? -t.translate : t.translate;
                        for (let u = 0; u < e.length; u += 1) {
                            let d = e[u],
                                c = d.progress,
                                p = Math.min(Math.max(c, -4), 4),
                                f = d.swiperSlideOffset;
                            t.params.centeredSlides && !t.params.cssMode && (t.wrapperEl.style.transform = `translateX(${t.minTranslate()}px)`), t.params.centeredSlides && t.params.cssMode && (f -= e[0].swiperSlideOffset);
                            let h = t.params.cssMode ? -f - t.translate : -f,
                                m = 0,
                                g = -100 * Math.abs(p),
                                v = 1,
                                $ = -a.perSlideRotate * p,
                                y = a.perSlideOffset - 0.75 * Math.abs(p),
                                b = t.virtual && t.params.virtual.enabled ? t.virtual.from + u : u,
                                _ = (b === n || b === n - 1) && p > 0 && p < 1 && (o || t.params.cssMode) && l < s,
                                w = (b === n || b === n + 1) && p < 0 && p > -1 && (o || t.params.cssMode) && l > s;
                            if (_ || w) {
                                let x = (1 - Math.abs((Math.abs(p) - 0.5) / 0.5)) ** 0.5;
                                ($ += -28 * p * x), (v += -0.5 * x), (y += 96 * x), (m = -25 * x * Math.abs(p) + "%");
                            }
                            if (((h = p < 0 ? `calc(${h}px ${i ? "-" : "+"} (${y * Math.abs(p)}%))` : p > 0 ? `calc(${h}px ${i ? "-" : "+"} (-${y * Math.abs(p)}%))` : `${h}px`), !t.isHorizontal())) {
                                let S = m;
                                (m = h), (h = S);
                            }
                            let T = p < 0 ? "" + (1 + (1 - v) * p) : "" + (1 - (1 - v) * p),
                                C = `
        translate3d(${h}, ${m}, ${g}px)
        rotateZ(${a.rotate ? (i ? -$ : $) : 0}deg)
        scale(${T})
      `;
                            if (a.slideShadows) {
                                let E = d.querySelector(".swiper-slide-shadow");
                                E || (E = en("cards", d)), E && (E.style.opacity = Math.min(Math.max((Math.abs(p) - 0.5) / 0.5, 0), 1));
                            }
                            (d.style.zIndex = -Math.abs(Math.round(c)) + e.length), (ee(0, d).style.transform = C);
                        }
                    },
                    setTransition(e) {
                        let n = t.slides.map((e) => h(e));
                        n.forEach((t) => {
                            (t.style.transitionDuration = `${e}ms`),
                                t.querySelectorAll(".swiper-slide-shadow").forEach((t) => {
                                    t.style.transitionDuration = `${e}ms`;
                                });
                        }),
                            et({ swiper: t, duration: e, transformElements: n });
                    },
                    perspective: () => !0,
                    overwriteParams: () => ({ watchSlidesProgress: !0, virtualTranslate: !t.params.cssMode }),
                });
        },
    ];
    return G.use(ei), G;
})();
/*!
 * @pqina/flip v1.7.7
 */ !(function () {
    "use strict";
    var e, t, n, i, a, s;
    function o(t) {
        return "function" == typeof t || "[object Function]" === e.call(t);
    }
    Array.prototype.fill ||
        Object.defineProperty(Array.prototype, "fill", {
            value: function (e) {
                if (null == this) throw TypeError("this is null or not defined");
                for (
                    var t = Object(this), n = t.length >>> 0, i = arguments[1] >> 0, a = i < 0 ? Math.max(n + i, 0) : Math.min(i, n), s = arguments[2], o = void 0 === s ? n : s >> 0, l = o < 0 ? Math.max(n + o, 0) : Math.min(o, n);
                    a < l;

                )
                    (t[a] = e), a++;
                return t;
            },
        }),
        Array.prototype.find ||
            Object.defineProperty(Array.prototype, "find", {
                value: function (e) {
                    if (null == this) throw TypeError("Array.prototype.find called on null or undefined");
                    if ("function" != typeof e) throw TypeError("predicate must be a function");
                    for (var t = Object(this), n = t.length >>> 0, i = arguments[1], a = 0; a !== n; a++) if (e.call(i, this[a], a, t)) return this[a];
                },
            }),
        Array.from ||
            (Array.from =
                ((e = Object.prototype.toString),
                (t = 9007199254740991),
                function (e, n, i) {
                    var a = Object(e);
                    if (null == e) throw TypeError("Array.from requires an array-like object - not null or undefined");
                    var s,
                        l = 1 < arguments.length ? n : void 0;
                    if (void 0 !== l) {
                        if (!o(l)) throw TypeError("Array.from: when provided, the second argument must be a function");
                        2 < arguments.length && (s = i);
                    }
                    for (
                        var u, d, c, p = Math.min(Math.max(((d = Number((u = a.length))), isNaN(d) ? 0 : 0 !== d && isFinite(d) ? (0 < d ? 1 : -1) * Math.floor(Math.abs(d)) : d), 0), t), f = o(this) ? Object(new this(p)) : Array(p), h = 0;
                        h < p;

                    )
                        (c = a[h]), (f[h] = l ? (void 0 === s ? l(c, h) : l.call(s, c, h)) : c), (h += 1);
                    return (f.length = p), f;
                })),
        (Array.prototype.includes =
            Array.prototype.includes ||
            function (e, t) {
                if (!this) throw TypeError("Array.prototype.includes called on null or undefined");
                if (void 0 === t) {
                    for (var n = this.length; n--; ) if (this[n] === e) return !0;
                } else {
                    n = t;
                    for (var i = this.length; n++ !== i; ) if (this[n] === e) return !0;
                }
                return !1;
            }),
        "function" != typeof Object.assign &&
            (Object.assign = function (e, t) {
                if (null == e) throw TypeError("Cannot convert undefined or null to object");
                for (var n = Object(e), i = 1; i < arguments.length; i++) {
                    var a = arguments[i];
                    if (null != a) for (var s in a) Object.prototype.hasOwnProperty.call(a, s) && (n[s] = a[s]);
                }
                return n;
            }),
        Object.keys ||
            (Object.keys =
                ((n = Object.prototype.hasOwnProperty),
                (i = !{ toString: null }.propertyIsEnumerable("toString")),
                (s = (a = ["toString", "toLocaleString", "valueOf", "hasOwnProperty", "isPrototypeOf", "propertyIsEnumerable", "constructor"]).length),
                function (e) {
                    if ("object" != typeof e && ("function" != typeof e || null === e)) throw TypeError("Object.keys called on non-object");
                    var t,
                        o,
                        l = [];
                    for (t in e) n.call(e, t) && l.push(t);
                    if (i) for (o = 0; o < s; o++) n.call(e, a[o]) && l.push(a[o]);
                    return l;
                }));
})(),
    (function (e) {
        "use strict";
        e.Tick || (e.Tick = []),
            e.Tick.push([
                "view",
                "flip",
                (function () {
                    if (!e) var e = {};
                    function t(e) {
                        this.value = e;
                    }
                    function n(e) {
                        var n, i;
                        function a(n, i) {
                            try {
                                var o = e[n](i),
                                    l = o.value;
                                l instanceof t
                                    ? Promise.resolve(l.value).then(
                                          function (e) {
                                              a("next", e);
                                          },
                                          function (e) {
                                              a("throw", e);
                                          }
                                      )
                                    : s(o.done ? "return" : "normal", o.value);
                            } catch (u) {
                                s("throw", u);
                            }
                        }
                        function s(e, t) {
                            switch (e) {
                                case "return":
                                    n.resolve({ value: t, done: !0 });
                                    break;
                                case "throw":
                                    n.reject(t);
                                    break;
                                default:
                                    n.resolve({ value: t, done: !1 });
                            }
                            (n = n.next) ? a(n.key, n.arg) : (i = null);
                        }
                        (this._invoke = function (e, t) {
                            return new Promise(function (s, o) {
                                var l = { key: e, arg: t, resolve: s, reject: o, next: null };
                                i ? (i = i.next = l) : ((n = i = l), a(e, t));
                            });
                        }),
                            "function" != typeof e.return && (this.return = void 0);
                    }
                    function i(e, t) {
                        for (var n = 0; n < t.length; n++) {
                            var i = t[n];
                            (i.enumerable = i.enumerable || !1), (i.configurable = !0), "value" in i && (i.writable = !0), Object.defineProperty(e, i.key, i);
                        }
                    }
                    return (
                        "function" == typeof Symbol &&
                            Symbol.asyncIterator &&
                            (n.prototype[Symbol.asyncIterator] = function () {
                                return this;
                            }),
                        (n.prototype.next = function (e) {
                            return this._invoke("next", e);
                        }),
                        (n.prototype.throw = function (e) {
                            return this._invoke("throw", e);
                        }),
                        (n.prototype.return = function (e) {
                            return this._invoke("return", e);
                        }),
                        (e.exports = function (e) {
                            function t(e) {
                                if (e.isInitialValue()) {
                                    (e.root.textContent = ""), (e.spacer = o.create("span", "tick-flip-spacer")), e.root.appendChild(e.spacer);
                                    var t = o.create("span", "tick-flip-shadow-top tick-flip-shadow tick-flip-front"),
                                        n = o.create("span", "tick-flip-shadow-bottom tick-flip-shadow tick-flip-back");
                                    e.root.appendChild(t), e.root.appendChild(n), (e.shadowCard = o.create("span", "tick-flip-card-shadow")), e.root.appendChild(e.shadowCard);
                                }
                                if (((e.spacer.textContent = e.value), e.isInitialValue() || o.visible(e.root))) {
                                    var i = e.cards[e.cards.length - 1];
                                    if ((i && ((i.waiting = !1), (i.offset = u()), (i.back = e.value)), e.isInitialValue())) {
                                        var a = new $();
                                        (a.back = e.value), (a.offset = null), (a.progress = 1), e.root.insertBefore(a.root, e.root.firstChild), e.cards.push(a);
                                    }
                                    var s = new $();
                                    if (((s.offset = null), (s.progress = 0), (s.visual_progress = 0), (s.waiting = !0), (s.front = e.value), s.rotate(0), e.root.insertBefore(s.root, e.root.firstChild), e.cards.push(s), !e.animating)) {
                                        e.animating = !0;
                                        var d = l.getExtension(l.Type.EASING_FUNCTION, e.style.flipEasing);
                                        !(function t() {
                                            var n = e.cards.filter(function (e) {
                                                return !e.done && !e.waiting;
                                            });
                                            if (0 !== n.length) {
                                                n.forEach(function (t) {
                                                    null !== t.offset && (t.progress = (u() - t.offset) / e.style.flipDuration), 1 <= t.progress && ((t.progress = 1), (t.done = !0)), (t.visual_progress = d(t.progress));
                                                }),
                                                    n.reverse().forEach(function (e, t) {
                                                        var i = n[t - 1];
                                                        i && e.visual_progress <= i.visual_progress && (e.visual_progress = i.visual_progress + 0.01);
                                                    }),
                                                    n.reverse(),
                                                    e.cards.forEach(function (t, n) {
                                                        var i = 1 - 2 * Math.abs(t.visual_progress - 0.5),
                                                            a = 1 - (t.visual_progress - 0.5) / 0.5;
                                                        (t.shadowFront = i), (t.highlightBack = a);
                                                        var s = e.cards[n + 1];
                                                        s && 0.5 < t.visual_progress && 0 < t.visual_progress && (t.shadowBack = g(s.visual_progress));
                                                    }),
                                                    n.forEach(function (e, t) {
                                                        var n = e.visual_progress;
                                                        0.5 < n && !e.done ? (e.root.style.zIndex = 10 + t) : e.root.style.removeProperty("z-index"), e.rotate(-180 * n);
                                                    });
                                                var i = 0,
                                                    a = 1;
                                                n.forEach(function (e) {
                                                    var t = Math.abs(e.visual_progress - 0.5);
                                                    t < a && ((a = t), (i = e.visual_progress));
                                                });
                                                var s = v(i < 0.5 ? i / 0.5 : (1 - i) / 0.5);
                                                (e.shadowCard.style.opacity = s),
                                                    o.transform(e.shadowCard, "scaleY", s),
                                                    e.cards
                                                        .filter(function (e) {
                                                            return e.done;
                                                        })
                                                        .slice(0, -1)
                                                        .forEach(function (t) {
                                                            (e.cards = e.cards.filter(function (e) {
                                                                return e !== t;
                                                            })),
                                                                t.root.parentNode && e.root.removeChild(t.root);
                                                        }),
                                                    requestAnimationFrame(t);
                                            } else e.animating = !1;
                                        })();
                                    }
                                } else
                                    e.cards.forEach(function (t) {
                                        (t.back = e.value), (t.front = e.value);
                                    });
                            }
                            var n,
                                a,
                                s,
                                o = e.DOM,
                                l = (e.Animation.animate, e.Extension),
                                u = e.Date.performance,
                                d = e.View,
                                c = d.rooter,
                                p = d.destroyer,
                                f = d.drawer,
                                h = d.updater,
                                m = d.styler,
                                g = l.getExtension(l.Type.EASING_FUNCTION, "ease-out-cubic"),
                                v = l.getExtension(l.Type.EASING_FUNCTION, "ease-out-sine"),
                                $ =
                                    ((n = y),
                                    (a = [
                                        {
                                            key: "rotate",
                                            value: function (e) {
                                                (this._front.style.transform = "rotateX(" + e + "deg)"), (this._back.style.transform = "rotateX(" + (-180 + e) + "deg)");
                                            },
                                        },
                                        {
                                            key: "root",
                                            get: function () {
                                                return this._root;
                                            },
                                        },
                                        {
                                            key: "front",
                                            set: function (e) {
                                                (this._frontValue = e), (this._textFront.textContent = e);
                                            },
                                            get: function () {
                                                return this._frontValue;
                                            },
                                        },
                                        {
                                            key: "back",
                                            set: function (e) {
                                                (this._backValue = e), (this._textBack.textContent = e);
                                            },
                                            get: function () {
                                                return this._backValue;
                                            },
                                        },
                                        {
                                            key: "highlightBack",
                                            set: function (e) {
                                                this._highlightBack.style.opacity = e;
                                            },
                                        },
                                        {
                                            key: "shadowBack",
                                            set: function (e) {
                                                this._shadowBack.style.opacity = e;
                                            },
                                        },
                                        {
                                            key: "shadowFront",
                                            set: function (e) {
                                                this._shadowFront.style.opacity = e;
                                            },
                                        },
                                    ]),
                                    i(n.prototype, a),
                                    s && i(n, s),
                                    y);
                            function y() {
                                (function (e, t) {
                                    if (!(e instanceof t)) throw TypeError("Cannot call a class as a function");
                                })(this, y),
                                    (this._root = o.create("span", "tick-flip-card"));
                                var e = o.create("span", "tick-flip-panel-front tick-flip-front tick-flip-panel"),
                                    t = o.create("span", "tick-flip-panel-front-text"),
                                    n = o.create("span", "tick-flip-panel-text-wrapper");
                                t.appendChild(n);
                                var i = o.create("span", "tick-flip-panel-front-shadow");
                                e.appendChild(t), e.appendChild(i);
                                var a = o.create("span", "tick-flip-panel-back tick-flip-back tick-flip-panel"),
                                    s = o.create("span", "tick-flip-panel-back-text"),
                                    l = o.create("span", "tick-flip-panel-text-wrapper");
                                s.appendChild(l);
                                var u = o.create("span", "tick-flip-panel-back-highlight"),
                                    d = o.create("span", "tick-flip-panel-back-shadow");
                                a.appendChild(s),
                                    a.appendChild(u),
                                    a.appendChild(d),
                                    this._root.appendChild(e),
                                    this._root.appendChild(a),
                                    (this._front = e),
                                    (this._back = a),
                                    (this._shadowFront = i),
                                    (this._shadowBack = d),
                                    (this._highlightBack = u),
                                    (this._textBack = l),
                                    (this._textFront = n),
                                    (this._frontValue = null),
                                    (this._backValue = null);
                            }
                            return function (e) {
                                var n = { cards: [], lastCard: null, initialCard: null, shadowAbove: null, shadowBelow: null, shadowCard: null, currentValue: null, lastValue: null, front: null, back: null };
                                return Object.assign({}, c(n, e, "flip"), h(n), m(n, { flipDuration: 800, flipEasing: "ease-out-bounce" }), f(n, t), p(n));
                            };
                        }),
                        (e.exports.identifier = { name: "flip", type: "view" }),
                        e.exports
                    );
                })(),
            ]);
    })(window),
    (function (e, t, n) {
        "use strict";
        if (e && "MutationObserver" in e && "requestAnimationFrame" in e) {
            var i = (function () {
                if (!a)
                    var e,
                        t,
                        i,
                        a = {};
                var s = { FONT: "font", VIEW: "view", TRANSFORM: "transform", EASING_FUNCTION: "easing-function", TRANSITION: "transition" },
                    o = {};
                function l(e, t) {
                    if (o[e]) {
                        for (var n in t)
                            if (t.hasOwnProperty(n)) {
                                if (o[e][n]) return;
                                o[e][n] = t[n];
                            }
                    }
                }
                function u(e, t, n) {
                    if (!o[e]) throw "Can't add extension with type of \"" + e + '", "' + e + '" is not a valid extension type. The following types are valid: ' + tm(o);
                    if (!/^[-a-z]+$/.test(t)) throw "Can't add extension with name \"" + t + '", "' + t + '" is contains invalid characters. Only lowercase alphabetical characters and dashes are allowed.';
                    if (o[e][t]) throw "Can't add extension with name \"" + t + '", "' + t + '" is already added.';
                    o[e][t] = n;
                }
                function d(e, t) {
                    if (!o[e]) throw "Can't get extension with type of \"" + e + '", "' + e + '" is not a valid extension type. The following types are available: ' + tm(o);
                    if (!o[e][t]) throw "Can't get extension with name \"" + t + '", "' + t + '" is not available. The following extensions are available: ' + tm(o[e]);
                    return o[e][t];
                }
                (o[s.FONT] = {}), (o[s.VIEW] = {}), (o[s.TRANSFORM] = {}), (o[s.EASING_FUNCTION] = {}), (o[s.TRANSITION] = {});
                var c = { Week: 6048e5, Day: 864e5, Hour: 36e5, Minute: 6e4, Second: 1e3, Millisecond: 1, Month: 2628e6, Year: 31536e6 },
                    p = ["Januari", "Februari", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                for (var f in c)
                    if (c.hasOwnProperty(f)) {
                        var h = c[f];
                        1 === h ? ((c.mi = h), (c.ms = h)) : 2628e6 === h ? (c.M = h) : (c[f.charAt(0).toLowerCase()] = h), (c[f.toLowerCase()] = h), (c[f.toLowerCase() + "s"] = h);
                    }
                function m(e) {
                    return e instanceof Date;
                }
                function g(e, t) {
                    var n = t - e.getDay();
                    return e.setDate(e.getDate() + n), e;
                }
                function v(e, t) {
                    var n = N(e.getMonth() + 1, e.getFullYear());
                    return (t = "last" === t ? n : Math.max(1, Math.min(n, t))), e.setDate(t), e;
                }
                function $(e, t) {
                    return (
                        e.setMonth(
                            p
                                .map(function (e) {
                                    return e.toLowerCase();
                                })
                                .indexOf(t)
                        ),
                        e
                    );
                }
                function y(e) {
                    return new Date(Date.now() + e);
                }
                function b(e, t) {
                    return e.toDateString() === t.toDateString();
                }
                function _(e) {
                    return e.match(/(Z)|([+\-][0-9]{2}:?[0-9]*$)/g) ? new Date(e) : ((e += -1 !== e.indexOf("T") ? "Z" : ""), M(new Date(e)));
                }
                function w(e, t) {
                    return t.map(function (t) {
                        var n = c[t],
                            i = Math.max(0, Math.floor(e / n));
                        return (e %= n), i;
                    });
                }
                function x(e, t, n) {
                    var i = t - e,
                        a = !1;
                    if (i < 0) {
                        i = e - t;
                        var s = [t, e];
                        (e = s[0]), (t = s[1]), (a = !0);
                    }
                    var o = (n = n || ["d", "h", "m"]).indexOf("m");
                    0 <= o && ("y" === n[o - 1] || "d" === n[o + 1]) && (n[o].key = "M");
                    var l = void 0,
                        u = void 0,
                        d = void 0,
                        p = n.includes("y"),
                        f = n.includes("M");
                    (f || p) && ((l = new Date(e.valueOf() + i)), (u = z(l, e)), (d = f ? Math.floor(u) : 12 * Math.floor(u / 12)), (i = l.valueOf() - R(O(e), d).valueOf()));
                    var h = n.map(function (e) {
                        if ("y" === e || "M" === e) {
                            var t = Math.max(0, Math.floor(u / P[e]));
                            return (u -= t * P[e]), t;
                        }
                        var n = c[e],
                            a = Math.max(0, Math.floor(i / n));
                        return (i %= n), a;
                    });
                    return a
                        ? h.map(function (e) {
                              return 0 < e ? -e : e;
                          })
                        : h;
                }
                function S() {
                    for (var e = arguments.length, t = Array(e), i = 0; i < e; i++) t[i] = arguments[i];
                    if ("number" != typeof t[0] || "string" != typeof t[1]) return m(t[0]) ? x.apply(n, t) : "number" == typeof t[0] && Array.isArray(t[1]) ? w.apply(n, t) : null;
                    if (!c[t[1]]) throw '"' + t[1] + '" is not a valid amount.';
                    return t[0] * c[t[1]];
                }
                function T() {
                    return new Date();
                }
                function C(e) {
                    return {
                        destroy: function () {
                            (e.destroyed = !0),
                                e.frame && cancelAnimationFrame(e.frame),
                                e.styleObserver && e.styleObserver.disconnect(),
                                e.didResizeWindow && window.removeEventListener("resize", e.didResizeWindow),
                                e.root && e.root.parentNode && e.root.parentNode.removeChild(e.root);
                        },
                    };
                }
                function E(e) {
                    var t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : document.createElement("span"),
                        i = 2 < arguments.length && arguments[2] !== n ? arguments[2] : null;
                    return (
                        (e.root = t),
                        (e.aligned = null),
                        (e.destroyed = !1),
                        t && i && (e.root.classList.add("tick-" + i), e.root.setAttribute("data-view", i)),
                        t && t.dataset.layout && (e.align = (t.dataset.layout.match(/left|right|center/) || [])[0] || "left"),
                        {
                            appendTo: function (t, i) {
                                var a = 1 < arguments.length && i !== n ? i : "last";
                                !e.root ||
                                    (e.root && e.root.parentNode) ||
                                    ("last" !== a
                                        ? ("first" === a &&
                                              (0 === t.childNodes.length
                                                  ? t.appendChild(e.root)
                                                  : 0 === t.children.length && t.childNodes.length
                                                  ? t.insertBefore(e.root, t.childNodes[t.childNodes.length - 1])
                                                  : t.insertBefore(e.root, t.children[0])),
                                          "string" != typeof a && t.insertBefore(e.root, a))
                                        : t.childNodes.length && t.childNodes[t.childNodes.length - 1].nodeType === Node.TEXT_NODE
                                        ? t.insertBefore(e.root, t.childNodes[t.childNodes.length - 1])
                                        : t.appendChild(e.root));
                            },
                        }
                    );
                }
                function D(e, t) {
                    return (
                        (e.definition = t),
                        {
                            setDefinition: function (t) {
                                e.definition = t;
                            },
                        }
                    );
                }
                function k(e, t, n, i) {
                    return {
                        draw: function () {
                            return e.dirty ? (t(e, i), F(e), (e.dirty = !1), !0) : (n && n(e) && F(e), !1);
                        },
                    };
                }
                function L(e) {
                    return (
                        (e.dirty = !0),
                        (e.value = null),
                        (e.valueUpdateCount = 0),
                        (e.isInitialValue = function () {
                            return e.valueUpdateCount <= 1;
                        }),
                        {
                            reset: function () {
                                (e.dirty = !0), (e.value = null), (e.valueUpdateCount = 0);
                            },
                            update: function (t) {
                                tc(e.value, t) || ((e.value = t), e.valueUpdateCount++, (e.dirty = !0));
                            },
                        }
                    );
                }
                function A(e) {
                    (e.didResizeWindow = function () {
                        e.dirty = !0;
                    }),
                        window.addEventListener("resize", e.didResizeWindow);
                }
                var I = { Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6, Sunday: 0 },
                    P = { M: 1, y: 12 },
                    N = function (e, t) {
                        return new Date(t, e, 0).getDate();
                    },
                    M = function (e) {
                        return new Date(e.getTime() + 6e4 * e.getTimezoneOffset());
                    },
                    O = function (e) {
                        return new Date(e.valueOf());
                    },
                    R = function (e, t) {
                        return e.setMonth(e.getMonth() + t), e;
                    },
                    z = function (e, t) {
                        var n = 12 * (t.getFullYear() - e.getFullYear()) + (t.getMonth() - e.getMonth()),
                            i = R(O(e), n);
                        return -(n + (t - i < 0 ? (t - i) / (i - R(O(e), n - 1)) : (t - i) / (R(O(e), 1 + n) - i)));
                    },
                    F = function (e) {
                        if (!e.fit) {
                            if (!e.root || !(e.root.getAttribute("data-layout") || "").match(/fit/)) return void (e.fit = !1);
                            var t = window.getComputedStyle(e.root, null);
                            (e.fit = !0), (e.fitInfo = { currentFontSize: parseInt(t.getPropertyValue("font-size"), 10) });
                        }
                        (e.fitInfo.availableWidth = e.root.parentNode.clientWidth), (e.fitInfo.currentWidth = e.root.scrollWidth);
                        var n = Math.min(Math.max(4, (e.fitInfo.availableWidth / e.fitInfo.currentWidth) * e.fitInfo.currentFontSize), 1024);
                        1 >= Math.abs(n - e.fitInfo.currentFontSize) || ((e.fitInfo.currentFontSize = n), (e.root.style.fontSize = e.fitInfo.currentFontSize + "px"));
                    },
                    j =
                        "function" == typeof Symbol && "symbol" == typeof Symbol.iterator
                            ? function (e) {
                                  return typeof e;
                              }
                            : function (e) {
                                  return e && "function" == typeof Symbol && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e;
                              };
                function H(e) {
                    this.value = e;
                }
                function B(e) {
                    var t, i;
                    function a(t, n) {
                        try {
                            var i = e[t](n),
                                o = i.value;
                            o instanceof H
                                ? Promise.resolve(o.value).then(
                                      function (e) {
                                          a("next", e);
                                      },
                                      function (e) {
                                          a("throw", e);
                                      }
                                  )
                                : s(i.done ? "return" : "normal", i.value);
                        } catch (l) {
                            s("throw", l);
                        }
                    }
                    function s(e, n) {
                        switch (e) {
                            case "return":
                                t.resolve({ value: n, done: !0 });
                                break;
                            case "throw":
                                t.reject(n);
                                break;
                            default:
                                t.resolve({ value: n, done: !1 });
                        }
                        (t = t.next) ? a(t.key, t.arg) : (i = null);
                    }
                    (this._invoke = function (e, n) {
                        return new Promise(function (s, o) {
                            var l = { key: e, arg: n, resolve: s, reject: o, next: null };
                            i ? (i = i.next = l) : ((t = i = l), a(e, n));
                        });
                    }),
                        "function" != typeof e.return && (this.return = n);
                }
                function q(e, t) {
                    for (var n = 0; n < t.length; n++) {
                        var i = t[n];
                        (i.enumerable = i.enumerable || !1), (i.configurable = !0), "value" in i && (i.writable = !0), Object.defineProperty(e, i.key, i);
                    }
                }
                function W(e) {
                    if (Array.isArray(e)) {
                        for (var t = 0, n = Array(e.length); t < e.length; t++) n[t] = e[t];
                        return n;
                    }
                    return Array.from(e);
                }
                function G(e, t) {
                    var i = (e.definition || []).concat();
                    "right" === e.align && i.reverse();
                    var a = Array.isArray(e.value) ? e.value.concat() : "object" === j(e.value) ? to(e.value) : e.value;
                    i.forEach(function (n) {
                        !n.presenter && ((e.update = t(n)), n.presenter && n.presenter.appendTo(e.root));
                    }),
                        i
                            .filter(function (e) {
                                return e.presenter !== n;
                            })
                            .forEach(function (t) {
                                Array.isArray(a) && e.valueMapping ? e.update(t, "indexes" === e.valueMapping ? ("right" === e.align ? a.pop() : a.shift()) : a) : t.key && a[t.key] !== n ? e.update(t, a[t.key]) : e.update(t, a);
                            }),
                        (e.views = i),
                        el(e);
                }
                function U(e, t) {
                    var n = tl(Array.isArray(e.value) ? e.value : (e.value + "").split(""));
                    if (("right" === e.align && n.reverse(), e.definitions.length > n.length)) for (; e.definitions.length > n.length; ) e.definitions.pop().presenter.destroy();
                    n.forEach(function (n, i) {
                        var a = e.definitions[i];
                        a || ((a = e.definitions[i] = eU(e.definition)), (e.update = t(a)), a.presenter.appendTo(e.root, "right" === e.align ? "first" : "last"));
                    }),
                        n.forEach(function (t, n) {
                            return e.update(e.definitions[n], t);
                        }),
                        (e.views = n),
                        eu(e);
                }
                function V(e, t) {
                    var n = document.createElement(e);
                    return t && (n.className = t), n;
                }
                function X(e, t, n) {
                    var i = new MutationObserver(function (i) {
                        t.forEach(function (a) {
                            i.filter(function (e) {
                                return t.includes(e.attributeName);
                            }).length && n(e.getAttribute(a));
                        });
                    });
                    return i.observe(e, { attributes: !0 }), i;
                }
                function Y(e) {
                    return e instanceof HTMLElement;
                }
                function J(e, t, i, a) {
                    var s = 3 < arguments.length && a !== n ? a : "";
                    e.transforms || (e.transforms = []);
                    var o = e.transforms.find(function (e) {
                        return e.name === t;
                    });
                    o ? (o.value = i) : e.transforms.push({ name: t, value: i, unit: s }), ec(e, e.transforms);
                }
                function K(e) {
                    var t = e.getBoundingClientRect();
                    return !(t.bottom < 0 || t.top > window.scrollY + window.innerHeight);
                }
                function Z(e) {
                    return e.trim();
                }
                function Q(e, t) {
                    var n = t.toString();
                    return ep[n] || (ep[n] = {}), ep[n][e] || (ep[n][e] = t(e)), ep[n][e];
                }
                function ee(e) {
                    return e ? parseFloat(e) * (/ms$/.test(e) ? 1 : 1e3) : 0;
                }
                function et(e) {
                    return e.match(/[a-z]+(?:\(.*?\))?\s?(?:origin\(.*?\))?\s?(?:[a-z]+\(.*?\))?[ .a-z-0-9]*/g).map(ew);
                }
                function en(e) {
                    var t = e.match(/follow-gradient|horizontal-gradient|vertical-gradient/)[0];
                    return {
                        type: t,
                        colors: e
                            .substr(t.length)
                            .match(/(?:transparent|rgb\(.*?\)|hsl\(.*?\)|hsla\(.*?\)|rgba\(.*?\)|[a-z]+|#[abcdefABCDEF\d]+)\s?(?:[\d]{1,3}%?)?/g)
                            .map(eS),
                    };
                }
                function ei(e) {
                    return "string" != typeof e ? e : e.match(/([-.\d]+(?:%|ms|s|deg|cm|em|ch|ex|q|in|mm|pc|pt|px|vh|vw|vmin|vmax)?)|[%#A-Za-z0-9,.()]+/g);
                }
                function er(e) {
                    var t,
                        n,
                        i,
                        a,
                        s,
                        o = e.split(":").map(Z),
                        l = (t = o[0])
                            .trim()
                            .split("-")
                            .map(function (e, t) {
                                var n;
                                return 0 < t ? (n = e).charAt(0).toUpperCase() + n.slice(1) : e;
                            })
                            .join(""),
                        u =
                            ((n = o[1]),
                            (i = o[0]),
                            eh.test(n)
                                ? "string" == typeof (a = n)
                                    ? "true" === a
                                    : a
                                : ef.test(n)
                                ? parseInt(n, 10)
                                : em.test(n)
                                ? parseFloat(n)
                                : e_.test(n)
                                ? 1 ===
                                  (s = n.match(/url\((.*?)\)/g).map(function (e) {
                                      return e.substring(4, e.length - 1);
                                  })).length
                                    ? s[0]
                                    : s
                                : eg.test(i)
                                ? e$.test(n)
                                    ? Q(n, en)
                                    : Q(n, eE)
                                : ev.test(i)
                                ? Q(n, ei)
                                : eb.test(i) && "none" !== n
                                ? Q(n, et)
                                : n);
                    return l && null != u ? { property: l, value: u } : null;
                }
                function ea(e) {
                    return e;
                }
                function es(e) {
                    return e < 0.5 ? 2 * e * e : (4 - 2 * e) * e - 1;
                }
                "function" == typeof Symbol &&
                    Symbol.asyncIterator &&
                    (B.prototype[Symbol.asyncIterator] = function () {
                        return this;
                    }),
                    (B.prototype.next = function (e) {
                        return this._invoke("next", e);
                    }),
                    (B.prototype.throw = function (e) {
                        return this._invoke("throw", e);
                    }),
                    (B.prototype.return = function (e) {
                        return this._invoke("return", e);
                    });
                var eo =
                        Object.assign ||
                        function (e) {
                            for (var t = 1; t < arguments.length; t++) {
                                var n = arguments[t];
                                for (var i in n) Object.prototype.hasOwnProperty.call(n, i) && (e[i] = n[i]);
                            }
                            return e;
                        },
                    el = function (e) {
                        var t = !1;
                        return (
                            e.views
                                .filter(function (e) {
                                    return e.presenter !== n;
                                })
                                .forEach(function (e) {
                                    e.presenter.draw() && (t = !0);
                                }),
                            t
                        );
                    },
                    eu = function (e) {
                        var t = !1;
                        return (
                            e.views.forEach(function (n, i) {
                                e.definitions[i].presenter.draw() && (t = !0);
                            }),
                            t
                        );
                    },
                    ed =
                        "undefined" == typeof document
                            ? null
                            : (function () {
                                  for (var e = ["webkit", "Moz", "ms", "O"], t = 0, n = e.length, i = document.createElement("div").style; t < n; t++) if (e[t] + "Transform" in i) return e[t];
                                  return null;
                              })(),
                    ec = function (e, t) {
                        e.style.transform = t
                            .map(function (e) {
                                return e.name + "(" + e.value + e.unit + ")";
                            })
                            .join(" ");
                    },
                    ep = {},
                    ef = RegExp("^[0-9]+$"),
                    eh = RegExp("^(true|false)$"),
                    em = RegExp("^[0-9.]+$"),
                    eg = /color/,
                    ev = /shadow/,
                    e$ = RegExp("^(follow-gradient|horizontal-gradient|vertical-gradient)"),
                    ey = RegExp("^[.0-9]+(?:ms|s){1}$"),
                    eb = RegExp("^transition-?(?:in|out)?$"),
                    e_ = RegExp("^url\\("),
                    ew = function (e) {
                        var t = e.match(/([a-z]+(?:\(.*?\))?)\s?(?:origin\((.*?)\))?\s?([a-z]+(?:\(.*?\))?)?\s?(?:([.0-9ms]+)?\s?(?:(ease-[a-z-]+))?\s?([.0-9ms]+)?)?/),
                            i = e5(t[1]),
                            a = n,
                            s = n,
                            o = n,
                            l = n,
                            u = n;
                        return (
                            t
                                .slice(2)
                                .filter(function (e) {
                                    return void 0 !== e;
                                })
                                .forEach(function (e) {
                                    ey.test(e) ? (void 0 === s ? (s = ee(e)) : (l = ee(e))) : / /.test(e) ? (a = e) : /^ease-[a-z-]+$/.test(e) ? (o = e) : /^[a-z]+/.test(e) && (u = e5(e));
                                }),
                            u && (o = s = n),
                            { name: i.name, parameters: i.parameters, duration: s, ease: o, delay: l, origin: a, resolver: u }
                        );
                    },
                    ex = /\s([\d]{1,3})%?$/,
                    eS = function (e) {
                        var t = e.match(ex);
                        return { offset: t ? parseFloat(t[1]) / 100 : null, value: eE(e.replace(ex, "")) };
                    },
                    eT = [],
                    eC =
                        "undefined" == typeof document
                            ? function (e) {
                                  return 0;
                              }
                            : function (e) {
                                  var t,
                                      i,
                                      a,
                                      s,
                                      o = 1 < arguments.length && arguments[1] !== n ? arguments[1] : document.body,
                                      l = 2 < arguments.length && arguments[2] !== n ? arguments[2] : null;
                                  if (0 == e) return 0;
                                  if (l) {
                                      var u =
                                          ((s = o),
                                          (eT[(a = l)]
                                              ? eT[a].find(function (e) {
                                                    return e.node.parentNode === s;
                                                })
                                              : null) || {});
                                      return (
                                          u.node || ((u.node = document.createElement("span")), (u.node.style.cssText = "position:absolute;padding:0;visibility:hidden;"), o.appendChild(u.node)),
                                          (u.node.style.marginTop = e),
                                          u.style || (u.style = window.getComputedStyle(u.node)),
                                          (i = u),
                                          eT[(t = l)] || (eT[t] = []),
                                          eT[t].push(i),
                                          parseInt(u.style.marginTop, 10)
                                      );
                                  }
                                  var d = document.createElement("span");
                                  return (
                                      (d.style.cssText = "position:absolute;padding:0;visibility:hidden;margin-top:" + e),
                                      o.appendChild(d),
                                      requestAnimationFrame(function () {
                                          d.parentNode.removeChild(d);
                                      }),
                                      parseInt(window.getComputedStyle(d).marginTop, 10)
                                  );
                              },
                    eE =
                        "undefined" == typeof document
                            ? function (e) {
                                  return e;
                              }
                            : function (e) {
                                  if ("transparent" === e) return "rgba(0,0,0,0)";
                                  var t = document.createElement("span");
                                  return (
                                      (t.style.cssText = "position:absolute;visibility:hidden;color:" + e),
                                      document.body.appendChild(t),
                                      requestAnimationFrame(function () {
                                          t.parentNode.removeChild(t);
                                      }),
                                      window.getComputedStyle(t).getPropertyValue("color")
                                  );
                              };
                function e8(e, t, i, a, s) {
                    var o = 3 < arguments.length && a !== n ? a : ea;
                    return e0(
                        function (t) {
                            e(o(t));
                        },
                        t,
                        2 < arguments.length && i !== n ? i : 500,
                        4 < arguments.length && s !== n ? s : 0
                    );
                }
                function eD(e) {
                    for (var t = arguments.length, i = Array(1 < t ? t - 1 : 0), a = 1; a < t; a++) i[a - 1] = arguments[a];
                    var s,
                        o,
                        l =
                            ((o = { velocity: 0, origin: 0, position: 0, destination: 1 }),
                            {
                                getPosition: function () {
                                    return o.position;
                                },
                                cancel: u,
                                translate: function (e, t, n, i) {
                                    u(), null === n ? (o.destination = t) : ((o.position = t), (o.destination = n), (o.velocity = 0)), (o.origin = o.position);
                                    var a = null;
                                    !(function t(n) {
                                        s = requestAnimationFrame(t);
                                        var l = n - (a = a || n);
                                        l <= 1e3 / 24 || ((a = n - (l % (1e3 / 24))), i(o, u), e(o.position));
                                    })(tu());
                                },
                            });
                    function u() {
                        cancelAnimationFrame(s);
                    }
                    var d = { update: (s = null), cancel: l.cancel, getPosition: l.getPosition };
                    return "arrive" === e ? (d.update = eN.apply(n, [l.translate].concat(i))) : "spring" === e ? (d.update = eO.apply(n, [l.translate].concat(i))) : "step" === e && (d.update = eM.apply(n, [l.translate].concat(i))), d;
                }
                function ek(e) {
                    var t = e.map(function (e) {
                        return e9(ez(e.name, e.parameters, e.ease), e.origin, e.duration, e.delay);
                    });
                    return function (e, n, i) {
                        if (!Y(e)) return !1;
                        var a = t.length;
                        t.forEach(function (t) {
                            t(e, n, function () {
                                --a || !i || i(e);
                            });
                        });
                    };
                }
                function eL(e) {
                    var t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : {};
                    return (
                        (e.lastAppliedStyles = null),
                        eF(e, t, e.root.dataset.style),
                        (e.styleObserver = X(e.root, ["data-style"], function (n) {
                            eF(e, t, n);
                        })),
                        {
                            setStyle: function (n) {
                                eF(e, t, n);
                            },
                        }
                    );
                }
                function eA(e) {
                    return e[ed + "BackingStorePixelRatio"] || e.backingStorePixelRatio || 1;
                }
                function eI() {
                    return window.devicePixelRatio || 1;
                }
                function eP(e) {
                    e.getContext("2d").clearRect(0, 0, e.width, e.height);
                }
                l(s.EASING_FUNCTION, {
                    "ease-linear": ea,
                    "ease-in-sine": function (e) {
                        return -1 * Math.cos(e * (Math.PI / 2)) + 1;
                    },
                    "ease-out-sine": function (e) {
                        return Math.sin(e * (Math.PI / 2));
                    },
                    "ease-in-out-sine": function (e) {
                        return -0.5 * (Math.cos(Math.PI * e) - 1);
                    },
                    "ease-in-cubic": function (e) {
                        return e * e * e;
                    },
                    "ease-out-cubic": function (e) {
                        var t = e - 1;
                        return t * t * t + 1;
                    },
                    "ease-in-out-cubic": function (e) {
                        return e < 0.5 ? 4 * e * e * e : (e - 1) * (2 * e - 2) * (2 * e - 2) + 1;
                    },
                    "ease-in-circ": function (e) {
                        return -1 * (Math.sqrt(1 - +e * e) - 1);
                    },
                    "ease-out-circ": function (e) {
                        var t = e - 1;
                        return Math.sqrt(1 - t * t);
                    },
                    "ease-in-out-circ": function (e) {
                        var t = 2 * e,
                            n = t - 2;
                        return t < 1 ? -0.5 * (Math.sqrt(1 - t * t) - 1) : 0.5 * (Math.sqrt(1 - n * n) + 1);
                    },
                    "ease-in-quad": function (e) {
                        return e * e;
                    },
                    "ease-out-quad": function (e) {
                        return e * (2 - e);
                    },
                    "ease-in-out-quad": es,
                    "ease-in-quart": function (e) {
                        return e * e * e * e;
                    },
                    "ease-out-quart": function (e) {
                        return 1 - --e * e * e * e;
                    },
                    "ease-in-out-quart": function (e) {
                        return e < 0.5 ? 8 * e * e * e * e : 1 - 8 * --e * e * e * e;
                    },
                    "ease-in-expo": function (e) {
                        return 0 === e ? 0 : Math.pow(2, 10 * (e - 1));
                    },
                    "ease-out-expo": function (e) {
                        return 1 === e ? 1 : 1 - Math.pow(2, -10 * e);
                    },
                    "ease-in-out-expo": function (e) {
                        if (0 === e || 1 === e) return e;
                        var t = 2 * e,
                            n = t - 1;
                        return t < 1 ? 0.5 * Math.pow(2, 10 * n) : 0.5 * (2 - Math.pow(2, -10 * n));
                    },
                    "ease-in-back": function (e, t) {
                        var i = 1 < arguments.length && t !== n ? t : 1.70158,
                            a = +e;
                        return a * a * ((i + 1) * a - i);
                    },
                    "ease-out-back": function (e, t) {
                        var i = 1 < arguments.length && t !== n ? t : 1.70158,
                            a = e - 1;
                        return a * a * ((i + 1) * a + i) + 1;
                    },
                    "ease-in-out-back": function (e, t) {
                        var i = 2 * e,
                            a = i - 2,
                            s = 1.525 * (1 < arguments.length && t !== n ? t : 1.70158);
                        return i < 1 ? 0.5 * i * i * ((1 + s) * i - s) : 0.5 * (a * a * ((1 + s) * a + s) + 2);
                    },
                    "ease-out-elastic": function (e, t) {
                        var i = 1 - (1 < arguments.length && t !== n ? t : 0.7),
                            a = 2 * e;
                        return 0 === e || 1 === e ? e : Math.pow(2, -10 * a) * Math.sin(((a - (i / (2 * Math.PI)) * Math.asin(1)) * (2 * Math.PI)) / i) + 1;
                    },
                    "ease-out-bounce": function (e) {
                        var t = +e;
                        if (t < 1 / 2.75) return 7.5625 * t * t;
                        if (t < 2 / 2.75) {
                            var n = t - 1.5 / 2.75;
                            return 7.5625 * n * n + 0.75;
                        }
                        if (t < 2.5 / 2.75) {
                            var i = t - 2.25 / 2.75;
                            return 7.5625 * i * i + 0.9375;
                        }
                        var a = t - 2.625 / 2.75;
                        return 7.5625 * a * a + 0.984375;
                    },
                });
                var e0 = function (e, t, i, a) {
                        var s = 1 < arguments.length && t !== n ? t : null,
                            o = 2 < arguments.length && i !== n ? i : 500,
                            l = 3 < arguments.length && a !== n ? a : 0;
                        if (!e) return null;
                        var u = null,
                            d = void 0,
                            c = null;
                        return (
                            (function t(n) {
                                if ((null === u && (u = n), (d = n - u - l) < o)) return e(0 <= d ? d / o : 0), (c = requestAnimationFrame(t)), null;
                                e(1), s && s();
                            })(tu()),
                            function () {
                                cancelAnimationFrame(c);
                            }
                        );
                    },
                    eN = function (e, t, i) {
                        var a = 1 < arguments.length && t !== n ? t : 1,
                            s = 2 < arguments.length && i !== n ? i : 0.01;
                        return function (t) {
                            e(t, 1 < arguments.length && arguments[1] !== n ? arguments[1] : null, 2 < arguments.length && arguments[2] !== n ? arguments[2] : null, function (e, t) {
                                var n = e.destination - e.position,
                                    i = e.origin + 0.5 * (e.destination - e.origin);
                                (e.velocity += 2 * (-(i - e.origin) + n) * s),
                                    (e.position += e.velocity < 0 ? Math.max(e.velocity, -a) : Math.min(e.velocity, a)),
                                    ((e.origin < e.destination && e.position >= e.destination) || (e.origin >= e.destination && e.position <= e.destination)) && (t(), (e.velocity = 0), (e.position = e.destination));
                            });
                        };
                    },
                    eM = function (e, t) {
                        var i = 1 < arguments.length && t !== n ? t : 0.01;
                        return function (t) {
                            e(t, 1 < arguments.length && arguments[1] !== n ? arguments[1] : null, 2 < arguments.length && arguments[2] !== n ? arguments[2] : null, function (e, t) {
                                (e.velocity = i),
                                    (e.position += e.velocity),
                                    ((e.origin < e.destination && e.position >= e.destination) || (e.origin >= e.destination && e.position <= e.destination)) && (t(), (e.velocity = 0), (e.position = e.destination));
                            });
                        };
                    },
                    eO = function (e, t, i, a) {
                        var s = 1 < arguments.length && t !== n ? t : 0.5,
                            o = 2 < arguments.length && i !== n ? i : 0.75,
                            l = 3 < arguments.length && a !== n ? a : 10;
                        return function (t) {
                            e(t, 1 < arguments.length && arguments[1] !== n ? arguments[1] : null, 2 < arguments.length && arguments[2] !== n ? arguments[2] : null, function (e, t) {
                                var n = -(e.position - e.destination) * s;
                                (e.velocity += n / l), (e.position += e.velocity), (e.velocity *= o), eR(e.position, e.destination, e.velocity) && (t(), (e.position = e.destination), (e.velocity = 0));
                            });
                        };
                    },
                    eR = function (e, t, i, a) {
                        var s = 3 < arguments.length && a !== n ? a : 0.001;
                        return Math.abs(e - t) < s && Math.abs(i) < s;
                    },
                    ez = function (e, t, i) {
                        var a = i ? d(s.EASING_FUNCTION, i) : i,
                            o = d(s.TRANSITION, e);
                        return function (e, i, s) {
                            o.apply(n, [e, s, i, a].concat(W(t)));
                        };
                    },
                    e9 = function (e, t, i, a) {
                        var s = 1 < arguments.length && t !== n ? t : "50% 50% 0",
                            o = 2 < arguments.length && i !== n ? i : 500,
                            l = a;
                        return function (t) {
                            var i,
                                a = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 1,
                                u = arguments[2];
                            (i = s),
                                (t.style.transformOrigin = i),
                                e0(
                                    function (n) {
                                        e(t, a, n);
                                    },
                                    u,
                                    o,
                                    l
                                );
                        };
                    },
                    eF = function (e, t, i) {
                        if (e.lastAppliedStyles !== i) {
                            (e.lastAppliedStyles = i),
                                (e.style = i
                                    ? ta(
                                          t,
                                          i
                                              .split(";")
                                              .filter(function (e) {
                                                  return e.trim().length;
                                              })
                                              .map(er)
                                              .filter(function (e) {
                                                  return null !== e;
                                              })
                                              .reduce(function (e, t) {
                                                  return (e[t.property] = t.value), e;
                                              }, {})
                                      )
                                    : t);
                            var a = [],
                                o = [];
                            e.style.transitionIn && e.style.transitionIn.length
                                ? ((a = e.style.transitionIn), (o = e.style.transitionOut))
                                : e.style.transition &&
                                  "none" !== e.style.transition &&
                                  e.style.transition.forEach(function (e) {
                                      var t,
                                          i = ((t = e), d(s.TRANSITION, t.name).apply(n, W(t.parameters || [])));
                                      (a = a.concat(i.intro)), (o = o.concat(i.outro));
                                  }),
                                a && o && ((e.transitionIn = ek(a)), (e.transitionOut = ek(o)), (e.skipToTransitionInEnd = ek(a.map(ej))), (e.skipToTransitionOutEnd = ek(o.map(ej)))),
                                (e.dirty = !0);
                        }
                    },
                    ej = function (e) {
                        var t = to(e);
                        return (t.duration = 0), (t.delay = 0), t;
                    };
                function eH(e) {
                    return 0 !== e.trim().length;
                }
                function e1(e, t) {
                    return e.push(t.trim());
                }
                function eB(e, t) {
                    return eH(t) ? (e1(e, t), "") : t;
                }
                function eq(e, t) {
                    return e.length && t.push(1 < e.length ? e.concat() : e[0]), [];
                }
                function e3(e) {
                    for (var t = arguments.length, n = Array(1 < t ? t - 1 : 0), i = 1; i < t; i++) n[i - 1] = arguments[i];
                    return function (t, i) {
                        !(function t(a, s) {
                            n.length <= a ? i(s) : n[a](s, eX(t, [a + 1]), e);
                        })(0, t);
                    };
                }
                function eW(e) {
                    return e.substring(1, e.length - 1);
                }
                function eG(e) {
                    var t = (e + "").match(/(-?[.\d]+)(%|ms|s|deg|cm|em|ch|ex|q|in|mm|pc|pt|px|vh|vw|vmin|vmax)?/);
                    return { value: parseFloat(t[1]), units: t[2] };
                }
                function e2(e) {
                    var t = window,
                        n = e.split(".");
                    return (
                        n.forEach(function (e, i) {
                            t[n[i]] && (t = t[n[i]]);
                        }),
                        t !== window ? t : null
                    );
                }
                function e4(e) {
                    return /^(?:[\w]+\s?:\s?[\w.]+,\s?)+(?:[\w]+\s?:\s?[\w.]+)$/g.test(e)
                        ? e.match(/(?:(\w+)\s?:\s?([\w.]+))/g).reduce(function (e, t) {
                              var n = t.split(":");
                              return (e[n[0]] = tr(n[1])), e;
                          }, {})
                        : tr(e);
                }
                function e6(e) {
                    return parseInt(e, 10);
                }
                l(s.VIEW, {
                    text: function () {
                        return function (e) {
                            var t = {};
                            return Object.assign(
                                {},
                                E(t, e, "text"),
                                L(t),
                                k(t, function (e) {
                                    var t, n, i;
                                    e.root.setAttribute("data-value", e.value), (t = e.root), (n = e.value), (i = t.childNodes[0]) ? n !== i.nodeValue && (i.nodeValue = n) : ((i = document.createTextNode(n)), t.appendChild(i));
                                }),
                                C(t)
                            );
                        };
                    },
                });
                var eU = function e(t) {
                        var n = {};
                        for (var i in t) t.hasOwnProperty(i) && (n[i] = "root" !== i ? ("children" !== i ? ("repeat" !== i ? t[i] : null === t[i] ? null : e(t[i])) : null === t[i] ? null : t[i].map(e)) : t[i].cloneNode());
                        return (n.presenter = null), n;
                    },
                    eV = { root: null, key: null, view: null, overlay: null, presenter: null, transform: null, layout: null, style: null, repeat: null, children: null, className: null },
                    eX = function (e, t, i) {
                        var a = 1 < arguments.length && t !== n ? t : [],
                            s = i;
                        return function () {
                            var t = Array.from(a);
                            return Array.prototype.push.apply(t, arguments), e.apply(s, t);
                        };
                    },
                    e7 = function (e, t) {
                        if (!e)
                            return function (e, t) {
                                return t(e);
                            };
                        if ("function" == typeof e) return e;
                        var n,
                            i,
                            a =
                                ((n = "transform(" + (/^[a-z]+$/.test(e) ? e + "()" : e) + ")"),
                                (function e(t, n, i) {
                                    for (var a, s, o, l = "", u = [], d = null, c = !1; t < n.length; ) {
                                        var p = n[t];
                                        if ("(" === p) {
                                            c = !1;
                                            var f = [l.trim()];
                                            (p = n[(t = e(t + 1, n, f))]), u.push(f), (l = "");
                                        } else {
                                            if (")" === p) return c && l.trim().length && (u.push([l.trim()]), (l = ""), (c = !1)), eH(l) && e1(u, l), (u = eq(u, i)), t + 1;
                                            null !== d && p !== d
                                                ? (l += p)
                                                : p === d
                                                ? (u.push(l), (l = ""), (d = null))
                                                : "'" === (o = p) || '"' === o
                                                ? ((l = ""), (d = p))
                                                : "-" === (a = n)[(s = t)] && ">" === a[s + 1]
                                                ? ((c = !0), l.trim().length && (u.push([l.trim()]), (l = "")), (t += 2))
                                                : "," === p
                                                ? (c && l.trim().length && (u.push([l.trim()]), (l = ""), (c = !1)), (u = eq(u, i)), (l = eB(i, l)))
                                                : (l += p),
                                                t++;
                                        }
                                    }
                                    return ((c && l.trim().length) || (!c && l.trim().length && !u.length)) && (u.push([l.trim()]), (l = "")), eq(u, i), eB(i, l), t;
                                })(0, n, (i = [])),
                                i);
                        return eY(a, t);
                    },
                    eY = function e(t, i) {
                        var a = t.map(function (t) {
                            var a = t.shift(),
                                o =
                                    d(s.TRANSFORM, a) ||
                                    function (e, t, n) {
                                        t(e);
                                    },
                                l = t.map(function (t) {
                                    return Array.isArray(t) ? ("string" == typeof t[0] ? e([t], i) : e(t, i)) : tr(t);
                                });
                            return o.apply(n, W(l));
                        });
                        return e3.apply(n, [i].concat(W(a)));
                    },
                    e5 = function (e) {
                        var t = e.match(/[a-z]+/)[0];
                        return { name: t, parameters: eJ(e.substring(t.length)) };
                    },
                    eJ = function (e) {
                        return (e.match(/('.+?')|(".+?")|(\[.+?])|([.:\-\d\sa-zA-Z]+%?)/g) || [])
                            .map(ts)
                            .filter(function (e) {
                                return e.length;
                            })
                            .map(tr);
                    },
                    eK = /^([\d]{4}-[\d]{1,2}-[\d]{1,2})/,
                    eZ = /^(true|false)$/,
                    eQ = /^[\a-zA-Z]+$/,
                    te = /^0[\d]+/,
                    tt = /^('|")/,
                    tn = /^-?(?:\d+)?(?:\.|0\.)?[\d]+$/,
                    ti = /^(\[)/,
                    tr = function (e) {
                        return eZ.test(e) ? "true" === e : ti.test(e) ? eJ(eW(e)) : eK.test(e) ? _(e) : tt.test(e) ? eW(e) : eQ.test(e) || te.test(e) || !tn.test(e) ? e : parseFloat(e);
                    },
                    ta = function (e, t) {
                        var i = 1 < arguments.length && t !== n ? t : {},
                            a = void 0,
                            s = {};
                        for (a in e) e.hasOwnProperty(a) && (s[a] = void 0 === i[a] ? e[a] : i[a]);
                        return s;
                    },
                    ts = function (e) {
                        return e.trim();
                    },
                    to = function (e) {
                        return "object" === (void 0 === e ? "undefined" : j(e)) && null !== e ? JSON.parse(JSON.stringify(e)) : e;
                    },
                    tl = function (e) {
                        return e.slice();
                    },
                    tu = function () {
                        return window.performance.now();
                    },
                    td = function (e, t, n, i) {
                        var a = new XMLHttpRequest();
                        i && i(a),
                            a.open("GET", e, !0),
                            (a.onload = function () {
                                t(a.response);
                            }),
                            n &&
                                (a.onerror = function () {
                                    n(a, a.status);
                                }),
                            a.send();
                    },
                    tc = function (e, t) {
                        return tp(e) ? tf(e, t) : Array.isArray(e) ? th(e, t) : e === t;
                    },
                    tp = function (e) {
                        return e === Object(e);
                    },
                    tf = function (e, t) {
                        for (var n in e) if (!t.hasOwnProperty(n) || e[n] !== t[n]) return !1;
                        return !0;
                    },
                    th = function (e, t) {
                        return (
                            e.length == t.length &&
                            e.every(function (e, n) {
                                return e === t[n];
                            })
                        );
                    },
                    tm = function (e) {
                        return Object.keys(e)
                            .map(function (e) {
                                return '"' + e + '"';
                            })
                            .join(", ");
                    },
                    tg =
                        ((e = tv),
                        (t = [
                            {
                                key: "isRootElement",
                                value: function (e) {
                                    return this._element === e;
                                },
                            },
                            {
                                key: "setConstant",
                                value: function (e, t) {
                                    this._constants[e] = t;
                                },
                            },
                            {
                                key: "getConstants",
                                value: function () {
                                    return this._constants;
                                },
                            },
                            {
                                key: "getConstant",
                                value: function (e) {
                                    return this._constants[e];
                                },
                            },
                            {
                                key: "setPreset",
                                value: function (e, t) {
                                    this._presets[e] = t;
                                },
                            },
                            {
                                key: "getPreset",
                                value: function (e) {
                                    return this._presets[e];
                                },
                            },
                            {
                                key: "destroy",
                                value: function () {
                                    this._willDestroy(this), this._observer.disconnect(), this.baseDefinition.presenter.destroy(), this._didDestroy(this);
                                },
                            },
                            {
                                key: "redraw",
                                value: function () {
                                    this.baseDefinition && this.baseDefinition.presenter && (this.baseDefinition.presenter.reset(), this.baseDefinition.presenter.draw(), this._updater(this.baseDefinition, this._value));
                                },
                            },
                            {
                                key: "_init",
                                value: function () {
                                    var e = this;
                                    (this._viewDefinition = this._options.view),
                                        (this._willDestroy = this._options.willDestroy),
                                        (this._didDestroy = this._options.didDestroy),
                                        (this._didInit = this._options.didInit),
                                        (this._didUpdate = this._options.didUpdate),
                                        (this._value = this._options.value),
                                        (this._presets = this._options.presets),
                                        (this._constants = this._options.constants),
                                        this._element.classList.contains("tick") || this._element.classList.add("tick"),
                                        (this._observer = X(this._element, ["data-value"], function (t) {
                                            e.value = t;
                                        })),
                                        this._viewDefinition.root !== this._element &&
                                            (Array.from(this._viewDefinition.root.children).forEach(function (t) {
                                                e._element.appendChild(t);
                                            }),
                                            (this._viewDefinition.root = this._element)),
                                        this._viewDefinition.view || this._viewDefinition.children || (this._viewDefinition.view = "text"),
                                        (this._updater = (function e(t) {
                                            function n(e, n) {
                                                e.transform(
                                                    n,
                                                    function (t) {
                                                        e.presenter.update(t);
                                                    },
                                                    t
                                                ),
                                                    i || ((i = !0), a());
                                            }
                                            var i = !1,
                                                a = function e() {
                                                    t.baseDefinition.presenter.draw(), requestAnimationFrame(e);
                                                };
                                            return (function e(i) {
                                                var a, o, l, u, c, p, f, h, m, g, v, $, y, b, _, w, x, S;
                                                return (
                                                    (i.presenter =
                                                        ((o = e),
                                                        (u = void 0),
                                                        (a = i).repeat
                                                            ? (u = ((c = a.root), (p = a.repeat), (f = o), (h = c), (m = p), (g = f), Object.assign({}, E((v = { definitions: [] }), h), L(v), D(v, m), k(v, U, eu, g), C(v))))
                                                            : "string" == typeof a.view
                                                            ? (u =
                                                                  (($ = a.view),
                                                                  (y = a.root),
                                                                  (b = a.style),
                                                                  (_ = d(s.VIEW, $))
                                                                      ? _({
                                                                            Extension: { Type: s, getExtension: d },
                                                                            Utils: { toPixels: eC, toColor: eE },
                                                                            Canvas: { clear: eP, getDevicePixelRatio: eI, getBackingStoreRatio: eA },
                                                                            DOM: { visible: K, create: V, transform: J },
                                                                            Animation: { animate: e8 },
                                                                            Data: { request: td },
                                                                            Date: { performance: tu },
                                                                            View: { rooter: E, drawer: k, updater: L, styler: eL, grouper: D, resizer: A, destroyer: C },
                                                                        })(y, b)
                                                                      : null))
                                                            : (l = a).children &&
                                                              l.children.length &&
                                                              (u =
                                                                  ((w = a.root),
                                                                  (x = a.children),
                                                                  (function (e, t, n) {
                                                                      var i = { valueMapping: null };
                                                                      if (e && e.dataset.valueMapping) {
                                                                          var a = e.dataset.valueMapping;
                                                                          i.valueMapping = -1 !== ["none", "indexes"].indexOf(a) ? a : null;
                                                                      }
                                                                      return Object.assign({}, E(i, e), A(i), L(i), D(i, t), k(i, G, el, n), C(i));
                                                                  })(w, x, (S = o)))),
                                                        u)),
                                                    (i.transform = e7(i.transform, t)),
                                                    n
                                                );
                                            })(t.baseDefinition);
                                        })(this)),
                                        null !== this.value && this._update(this.value),
                                        (this._element.dataset.state = "initialised"),
                                        this._didInit(this, this.value);
                                },
                            },
                            {
                                key: "_update",
                                value: function (e) {
                                    this._updater(this.baseDefinition, e), this._didUpdate(this, e);
                                },
                            },
                            {
                                key: "baseDefinition",
                                get: function () {
                                    return this._viewDefinition;
                                },
                            },
                            {
                                key: "root",
                                get: function () {
                                    return this._element;
                                },
                            },
                            {
                                key: "value",
                                get: function () {
                                    return this._value;
                                },
                                set: function (e) {
                                    (this._value = "string" == typeof e ? e4(e) : e), this._update(e);
                                },
                            },
                        ]),
                        (i = [
                            {
                                key: "options",
                                value: function () {
                                    return { constants: tD(), presets: tk(), value: null, view: null, didInit: function () {}, didUpdate: function () {}, willDestroy: function () {}, didDestroy: function () {} };
                                },
                            },
                        ]),
                        t && q(e.prototype, t),
                        i && q(e, i),
                        tv);
                function tv() {
                    var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : {},
                        t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : document.createElement("div");
                    (function (e, t) {
                        if (!(e instanceof t)) throw TypeError("Cannot call a class as a function");
                    })(this, tv),
                        (this._options = ta(tv.options(), e)),
                        (this._element = t),
                        (this._value = null),
                        (this._observer = null),
                        (this._viewDefinition = null),
                        (this._constants = null),
                        (this._presets = null),
                        (this._updater = null),
                        (this._didInit = null),
                        (this._didDestroy = null),
                        (this._willDestroy = null),
                        (this._didUpdate = null),
                        this._init();
                }
                function t$(e, t, n, i) {
                    return { label: 1 === e ? t : n, progress: e / i, value: e };
                }
                function ty(e) {
                    var t = t8.filter(function (t) {
                        return t.isRootElement(e);
                    });
                    return t ? t[0] : null;
                }
                function tb(e) {
                    return function () {
                        setTimeout(e, 0);
                    };
                }
                function t_() {
                    return Date.now();
                }
                function tw(e, t, i) {
                    function a() {
                        return $;
                    }
                    function s() {
                        return null !== h;
                    }
                    function o() {
                        return document.hidden;
                    }
                    function l() {
                        var t = t_(),
                            n = f - t,
                            i = c + n;
                        (f = t + i), e(t - h - g + n), (y = setTimeout(l, i));
                    }
                    function u() {
                        $
                            ? b()
                            : s() ||
                              ((h = t_()),
                              setTimeout(function () {
                                  e(0);
                              }, 0),
                              T(),
                              o()
                                  ? x()
                                  : ((f = t_() + c),
                                    (y = setTimeout(function () {
                                        l();
                                    }, c))));
                    }
                    function d() {
                        clearTimeout(y), (g = 0), (v = m = f = h = y = null), ($ = !1), S();
                    }
                    var c = 1 < arguments.length && t !== n ? t : 1e3,
                        p = ta({ autostart: !0 }, 2 < arguments.length && i !== n ? i : {}),
                        f = null,
                        h = null,
                        m = null,
                        g = 0,
                        v = null,
                        $ = !1,
                        y = null,
                        b = function () {
                            $ && s() && !o() && (($ = !1), T(), w());
                        },
                        _ = function () {
                            clearTimeout(y), (m = t_()), (v = f - m);
                        },
                        w = function () {
                            (g += t_() - m),
                                (m = null),
                                (f = t_() + v),
                                (y = setTimeout(function () {
                                    l();
                                }, v));
                        },
                        x = function () {
                            _();
                        },
                        S = function () {
                            document.removeEventListener("visibilitychange", C);
                        },
                        T = function () {
                            document.addEventListener("visibilitychange", C);
                        },
                        C = function () {
                            o() ? x() : s() && w();
                        };
                    return (
                        p.autostart && u(),
                        {
                            start: u,
                            stop: tb(d),
                            reset: tb(function () {
                                d(), u();
                            }),
                            pause: tb(function () {
                                s() && !o() && (($ = !0), S(), _());
                            }),
                            resume: b,
                        }
                    );
                }
                function tx(e) {
                    /^[\d]+/.test(e) || (e = "1 " + e);
                    var t = e.split(" ");
                    return parseFloat(t[0]) * c[t[1].toLowerCase()];
                }
                function tS(e, t) {
                    var n, i;
                    return (n = e), (i = t.split(":").map(e6)), n.setHours(i[0] || 0, i[1] || 0, i[2] || 0, i[3] || 0), n;
                }
                function tT(e, t) {
                    var n, i, a;
                    if (!0 === e)
                        return (
                            (n = function (e) {
                                t(e.getTime() - T().getTime());
                            }),
                            (i = new XMLHttpRequest()),
                            (a = Date.now()),
                            i.open("HEAD", window.location + "?noCache=" + a),
                            i.setRequestHeader("Content-Type", "text/html"),
                            i.setRequestHeader("Cache-Control", "no-cache"),
                            (i.onload = function () {
                                var e = 0.5 * (a - Date.now()),
                                    t = new Date(i.getResponseHeader("Date"));
                                n(new Date(t.getTime() + e));
                            }),
                            void i.send()
                        );
                    "string" != typeof e
                        ? setTimeout(function () {
                              t(0);
                          }, 0)
                        : setTimeout(function () {
                              t(_(e).getTime() - T().getTime());
                          }, 0);
                }
                function tC(e) {
                    return eo({ complete: !1, offset: null, value: null, timer: null, onload: function () {}, onupdate: function () {} }, e);
                }
                function tE() {
                    var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : 0,
                        t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 100;
                    return function (n, i) {
                        return i((parseFloat(n) - e) / (t - e));
                    };
                }
                var t8 = [],
                    tD = function () {
                        return tL;
                    },
                    tk = function () {
                        return tA;
                    },
                    tL = {
                        YEAR_PLURAL: "Years",
                        YEAR_SINGULAR: "Year",
                        MONTH_PLURAL: "Months",
                        MONTH_SINGULAR: "Month",
                        WEEK_PLURAL: "Weeks",
                        WEEK_SINGULAR: "Week",
                        DAY_PLURAL: "Days",
                        DAY_SINGULAR: "Day",
                        HOUR_PLURAL: "Hours",
                        HOUR_SINGULAR: "Hour",
                        MINUTE_PLURAL: "Minutes",
                        MINUTE_SINGULAR: "Minute",
                        SECOND_PLURAL: "Seconds",
                        SECOND_SINGULAR: "Second",
                        MILLISECOND_PLURAL: "Milliseconds",
                        MILLISECOND_SINGULAR: "Millisecond",
                    },
                    tA = {
                        y: function (e, t) {
                            return t$(e, t.YEAR_SINGULAR, t.YEAR_PLURAL, 10);
                        },
                        M: function (e, t) {
                            return t$(e, t.MONTH_SINGULAR, t.MONTH_PLURAL, 12);
                        },
                        w: function (e, t) {
                            return t$(e, t.WEEK_SINGULAR, t.WEEK_PLURAL, 52);
                        },
                        d: function (e, t) {
                            return t$(e, t.DAY_SINGULAR, t.DAY_PLURAL, 365);
                        },
                        h: function (e, t) {
                            return t$(e, t.HOUR_SINGULAR, t.HOUR_PLURAL, 24);
                        },
                        m: function (e, t) {
                            return t$(e, t.MINUTE_SINGULAR, t.MINUTE_PLURAL, 60);
                        },
                        s: function (e, t) {
                            return t$(e, t.SECOND_SINGULAR, t.SECOND_PLURAL, 60);
                        },
                        mi: function (e, t) {
                            return t$(e, t.MILLISECOND_SINGULAR, t.MILLISECOND_PLURAL, 1e3);
                        },
                    },
                    tI = { value: e4, didInit: e2, didUpdate: e2, didDestroy: e2, willDestroy: e2 },
                    tP = function (e, t) {
                        var i = 0 < arguments.length && e !== n ? e : n,
                            a = 1 < arguments.length && t !== n ? t : n;
                        if ((i && !Y(i) && ((a = i), (i = n)), !i || !ty(i))) {
                            a &&
                                a.view &&
                                (a.view = (function e(t) {
                                    return t.map(function (t) {
                                        return (
                                            "string" == typeof (t = ta(eV, t)).root ? (t.root = document.createElement(t.root)) : (t.root = document.createElement("span")),
                                            t.transform && (t.root.dataset.transform = t.transform),
                                            t.className && (t.root.className = t.className),
                                            t.overlay && (t.root.dataset.overlay = t.overlay),
                                            t.view
                                                ? ((t.root.dataset.view = t.view), t.style && (t.root.dataset.style = t.style), (t.repeat = null))
                                                : (t.layout && (t.root.dataset.layout = t.layout),
                                                  t.repeat
                                                      ? ((t.root.dataset.repeat = !0), (t.repeat = e(t.children).pop()))
                                                      : t.children &&
                                                        ((t.children = e(t.children)),
                                                        t.children.forEach(function (e) {
                                                            t.root.appendChild(e.root);
                                                        }))),
                                            t
                                        );
                                    });
                                })([a.view])[0]),
                                !a &&
                                    i &&
                                    (a = (function (e, t, i) {
                                        var a = 1 < arguments.length && t !== n ? t : {},
                                            s = 2 < arguments.length && i !== n ? i : {},
                                            o = e.dataset,
                                            l = { meta: {} };
                                        for (var u in o)
                                            if (o.hasOwnProperty(u)) {
                                                var d = a[u],
                                                    c = o[u];
                                                d && ((c = null === (c = d(c)) ? to(s[u]) : c), (l[u] = c));
                                            }
                                        return l;
                                    })(i, tI, eo({}, tg.options(), { constants: eo({}, tL), presets: eo({}, tA) }))),
                                i &&
                                    ((a = a || {}).view ||
                                        (a.view = (function e(t) {
                                            return Array.from(t).map(function (t) {
                                                var n = ta(eV, { root: t });
                                                for (var i in t.dataset) t.dataset.hasOwnProperty(i) && void 0 !== n[i] && (n[i] = t.dataset[i]);
                                                return (
                                                    n.repeat
                                                        ? ((n.repeat = e(t.children).pop()),
                                                          Array.from(t.children).forEach(function (e) {
                                                              e.parentNode.removeChild(e);
                                                          }))
                                                        : t.children.length && (n.children = e(t.children)),
                                                    n
                                                );
                                            });
                                        })([i])[0]));
                            var s = new tg(a, i);
                            return t8.push(s), s;
                        }
                    },
                    t0 = function (e, t) {
                        var n = t.match(/((?:[\d]+\s)?(?:hours|hour|minutes|minute|seconds|second))|((?:from|till)\s[\d]+(?::[\d]+)?(?::[\d]+)?)|(wait\s[\d]+\s[a-z]+)/g).reduce(
                            function (e, t) {
                                return (
                                    /from/.test(t)
                                        ? (e.from = tS(e.from, t.split(" ")[1]))
                                        : /till/.test(t)
                                        ? (e.till = tS(e.till, t.split(" ")[1]))
                                        : /wait/.test(t)
                                        ? (e.idle = tx(t.substr(5)))
                                        : /hours|hour|minutes|minute|seconds|second/.test(t) && (e.interval = tx(t)),
                                    e
                                );
                            },
                            { idle: null, interval: null, date: null, dist: null, wait: !1, from: tS(O(e), "0"), till: tS(O(e), "23:59:59:999") }
                        );
                        if (e < n.from || e >= n.till || n.interval > n.till - n.from) return n;
                        var i = e - n.from,
                            a = n.interval - (i % n.interval);
                        return null !== n.idle && a >= n.interval - n.idle ? (n.wait = !0) : ((n.dist = a), (n.date = new Date(e.getTime() + n.dist))), n;
                    },
                    tN = { format: ["d", "h", "m", "s"], cascade: !0, server: null, interval: 1e3 };
                function tM(e, t, n) {
                    return e + (t - e) * n;
                }
                l(s.TRANSFORM, {
                    ascii: function () {
                        return function (e, t) {
                            return t((e + "").charCodeAt(0));
                        };
                    },
                    char: function (e) {
                        var t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : "",
                            i = e ? RegExp("[^" + e + "]", "g") : null;
                        return function (e, n) {
                            var a = String.fromCharCode(e);
                            i && (a = a.replace(i, t)), n(a);
                        };
                    },
                    tween: function (e) {
                        var t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : "ease-linear",
                            i = arguments[2];
                        e = ee(e);
                        var a = d(s.EASING_FUNCTION, t),
                            o = null,
                            l = null;
                        return function (t, n) {
                            if (((t = parseFloat(t)), o && o(), null !== l && t !== l)) {
                                var s = l,
                                    u = t - s;
                                (o = e8(
                                    function (e) {
                                        n(s + e * u);
                                    },
                                    function () {
                                        o = null;
                                    },
                                    e,
                                    a,
                                    i
                                )),
                                    (l = t);
                            } else n((l = t));
                        };
                    },
                    value: function (e) {
                        return function (t, n) {
                            return n(e);
                        };
                    },
                    input: function () {
                        return function (e, t) {
                            return t(e);
                        };
                    },
                    rotate: function () {
                        for (var e = arguments.length, t = Array(e), n = 0; n < e; n++) t[n] = arguments[n];
                        return function (e, n) {
                            var i = Array.isArray(e) ? e : [e],
                                a = [],
                                s = t.length;
                            i.forEach(function (e, o) {
                                t[o % s](e, function (e) {
                                    (a[o] = e), o === i.length - 1 && n(a);
                                });
                            });
                        };
                    },
                    map: function (e) {
                        return function (t, n) {
                            var i = [],
                                a = t;
                            a.forEach(function (t, s) {
                                e(t, function (e) {
                                    (i[s] = e), s === a.length - 1 && n(i.concat());
                                });
                            });
                        };
                    },
                    transform: function () {
                        for (var e = arguments.length, t = Array(e), n = 0; n < e; n++) t[n] = arguments[n];
                        return function (e, n) {
                            var i = [],
                                a = e;
                            t.forEach(function (e, s) {
                                e(a, function (e) {
                                    (i[s] = e), s === t.length - 1 && n(1 === i.length ? i[0] : i);
                                });
                            });
                        };
                    },
                    upper: function () {
                        return function (e, t) {
                            return t((e + "").toUpperCase());
                        };
                    },
                    lower: function () {
                        return function (e, t) {
                            return t((e + "").toLowerCase());
                        };
                    },
                    abs: function () {
                        return function (e, t) {
                            return t(Math.abs(e));
                        };
                    },
                    add: function (e) {
                        return function (t, n) {
                            return n(t + e);
                        };
                    },
                    subtract: function (e) {
                        return function (t, n) {
                            return n(t - e);
                        };
                    },
                    modulus: function (e) {
                        return function (t, n) {
                            return n(t % e);
                        };
                    },
                    pad: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : "",
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : "left";
                        return function (n, i) {
                            return i(e.length > ("" + n).length ? ("left" === t ? ("" + e + n).slice(-e.length) : ("" + n + e).substring(0, e.length)) : n);
                        };
                    },
                    number: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : ".",
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : ",",
                            i = 2 < arguments.length && arguments[2] !== n ? arguments[2] : 2;
                        return function (n, a) {
                            a(
                                (n < 0 ? "-" : "") +
                                    parseFloat(Math.abs(n))
                                        .toFixed(i)
                                        .replace(/./g, function (n, i, a) {
                                            return "." === n ? e : i && (a.length - i) % 3 == 0 ? t + n : n;
                                        })
                            );
                        };
                    },
                    replace: function (e, t) {
                        return function (n, i) {
                            return i((n + "").replace(RegExp("." === e ? "\\" + e : e, "g"), t));
                        };
                    },
                    round: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : 0;
                        return function (t, n) {
                            return n(e ? t.toFixed(e) : Math.round(t));
                        };
                    },
                    ceil: function () {
                        return function (e, t) {
                            return t(Math.ceil(e));
                        };
                    },
                    floor: function () {
                        return function (e, t) {
                            return t(Math.floor(e));
                        };
                    },
                    fraction: tE,
                    percentage: function () {
                        var e = tE(0 < arguments.length && arguments[0] !== n ? arguments[0] : 0, 1 < arguments.length && arguments[1] !== n ? arguments[1] : 100);
                        return function (t, n) {
                            e(t, function (e) {
                                n(100 * e);
                            });
                        };
                    },
                    multiply: function (e) {
                        return function (t, n) {
                            return n(t * e);
                        };
                    },
                    divide: function (e) {
                        return function (t, n) {
                            return n(t / e);
                        };
                    },
                    split: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : "";
                        return function (t, n) {
                            return n((t + "").split(e));
                        };
                    },
                    format: function (e) {
                        return function (t, n) {
                            return n(e.replace(/\$0/gi, t));
                        };
                    },
                    plural: function (e, t) {
                        return function (n, i) {
                            return i(1 === n ? e : t);
                        };
                    },
                    limit: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : 0,
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 1;
                        return function (n, i) {
                            return i(Math.min(Math.max(n, e), t));
                        };
                    },
                    reverse: function () {
                        return function (e, t) {
                            return t(Array.isArray(e) ? e.reverse() : (e + "").split("").reverse().join(""));
                        };
                    },
                    arrive: function (e, t) {
                        var i = 2 < arguments.length && arguments[2] !== n && arguments[2],
                            a = !(3 < arguments.length && arguments[3] !== n) || arguments[3],
                            s = null,
                            o = null,
                            l = null;
                        return function (n, u) {
                            if (((n = parseFloat(n)), null !== s)) {
                                if ((i && null !== o && s === n && (l.cancel(), (l = null)), a && null !== o && 1 < n - l.getPosition())) return l.cancel(), (o = l = null), void u((s = n));
                                l ? l.update(u, n) : (l = eD("arrive", e, t)).update(u, s, n), (o = n);
                            } else u((s = n));
                        };
                    },
                    spring: function (e, t, n) {
                        var i = null,
                            a = null;
                        return function (s, o) {
                            (s = parseFloat(s)), null !== i ? (a ? a.update(o, s) : (a = eD("spring", e, t, n)).update(o, i, s)) : o((i = s));
                        };
                    },
                    delay: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : "rtl",
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 50,
                            i = 2 < arguments.length && arguments[2] !== n ? arguments[2] : 50,
                            a = null;
                        return function (s, o) {
                            if (!a) return (a = tl(s)), void o(tl(a));
                            a = "rtl" === e ? a.slice(a.length - s.length, a.length) : a.slice(0, s.length);
                            var l = (function (e) {
                                for (var t = [], n = 0; n < e; n++) t.push(n);
                                return t;
                            })(s.length);
                            "random" === e &&
                                (function (e) {
                                    for (var t = e.length; t; t--) {
                                        var n = Math.floor(Math.random() * t),
                                            i = [e[n], e[t - 1]];
                                        (e[t - 1] = i[0]), (e[n] = i[1]);
                                    }
                                })(l),
                                "rtl" === e && l.reverse(),
                                (function e() {
                                    var u, d, c, p;
                                    (u = l.shift()),
                                        (d = a),
                                        (c = s),
                                        (p = o),
                                        (d[u] = c[u]),
                                        p(tl(d)),
                                        l.length &&
                                            setTimeout(
                                                e,
                                                (function (e, t) {
                                                    var i = 0 < arguments.length && e !== n ? e : 0,
                                                        a = 1 < arguments.length && t !== n ? t : 1;
                                                    return i + Math.random() * (a - i);
                                                })(t, i)
                                            );
                                })();
                        };
                    },
                    step: function (e) {
                        var t = null,
                            n = null,
                            i = null;
                        return function (a, s) {
                            (a = parseFloat(a)), null !== t ? (null !== n && t === a && (i.cancel(), (i = null)), i ? i.update(s, a) : (i = eD("step", e)).update(s, t, a), (n = a)) : s((t = a));
                        };
                    },
                    keys: function () {
                        for (var e = arguments.length, t = Array(e), n = 0; n < e; n++) t[n] = arguments[n];
                        return function (e, n) {
                            var i = {};
                            e.forEach(function (e, n) {
                                i[t[n]] = e;
                            }),
                                n(i);
                        };
                    },
                    duration: function () {
                        for (var e = arguments.length, t = Array(e), n = 0; n < e; n++) t[n] = arguments[n];
                        return function (e, n) {
                            return n(w(e, t));
                        };
                    },
                    substring: function (e, t) {
                        return function (n, i) {
                            return i((n + "").substring(e, t));
                        };
                    },
                    preset: function () {
                        for (var e = arguments.length, t = Array(e), n = 0; n < e; n++) t[n] = arguments[n];
                        return function (e, n, i) {
                            return n(
                                e.map(function (e, n) {
                                    return i.getPreset(t[n])(e, i.getConstants(), i);
                                })
                            );
                        };
                    },
                });
                var tO = { x: "translateX", y: "translateY", z: "translateZ" },
                    tR = { x: "rotateX", y: "rotateY", z: "rotateZ" },
                    tz = { both: "scale", x: "scaleX", y: "scaleY" };
                function t9(e) {
                    if (!s.hasOwnProperty(e)) return "continue";
                    tF.plugin[
                        ("add-" + s[e]).replace(/-./g, function (e) {
                            return e.charAt(1).toUpperCase();
                        })
                    ] = function (t, n) {
                        u(s[e], t, n);
                    };
                }
                l(s.TRANSITION, {
                    fade: function (e, t, i, a, s, o) {
                        var l,
                            u,
                            d,
                            c = 3 < arguments.length && a !== n ? a : es,
                            p = 4 < arguments.length && s !== n ? s : 0,
                            f = 5 < arguments.length && o !== n ? o : 1;
                        if (i < 0) {
                            var h = [f, p];
                            (p = h[0]), (f = h[1]);
                        }
                        e.style.opacity = ((l = p), (u = f), l + (u - l) * (d = c(t)));
                    },
                    move: function (e, t, i, a, s, o, l) {
                        var u = 3 < arguments.length && a !== n ? a : es,
                            d = 4 < arguments.length && s !== n ? s : "0",
                            c = 5 < arguments.length && o !== n ? o : "100%",
                            p = 6 < arguments.length && l !== n ? l : "y";
                        if (i < 0) {
                            var f = [c, d];
                            (d = f[0]), (c = f[1]);
                        }
                        var h,
                            m,
                            g,
                            v = Q(d, eG),
                            $ = Q(c, eG);
                        J(e, tO[p], ((h = v.value), (m = $.value), h + (m - h) * (g = u(t))), v.units || $.units);
                    },
                    rotate: function (e, t, i, a, s, o, l) {
                        var u = 3 < arguments.length && a !== n ? a : es,
                            d = 4 < arguments.length && s !== n ? s : "0",
                            c = 5 < arguments.length && o !== n ? o : "90deg",
                            p = 6 < arguments.length && l !== n ? l : "x";
                        if (i < 0) {
                            var f = [c, d];
                            (d = f[0]), (c = f[1]);
                        }
                        var h,
                            m,
                            g,
                            v = Q(d, eG),
                            $ = Q(c, eG);
                        J(e, tR[p], ((h = v.value), (m = $.value), h + (m - h) * (g = u(t))), v.units || $.units);
                    },
                    scale: function (e, t, i, a, s, o, l) {
                        var u,
                            d,
                            c,
                            p = 4 < arguments.length && s !== n ? s : 0,
                            f = 5 < arguments.length && o !== n ? o : 1;
                        if (i < 0) {
                            var h = [f, p];
                            (p = h[0]), (f = h[1]);
                        }
                        J(e, tz[6 < arguments.length && l !== n ? l : "both"], ((u = p), (d = f), (c = (3 < arguments.length && a !== n ? a : es)(t)), u + (d - u) * c));
                    },
                    crossfade: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : 1,
                            t = arguments[2];
                        return { intro: [{ name: "fade", parameters: [0, 1], duration: 1e3 * e, delay: ee(arguments[1]) }], outro: [{ name: "fade", parameters: [1, 0], duration: 1e3 * e, delay: ee(t) }] };
                    },
                    swap: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : "y",
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 1,
                            i = 2 < arguments.length && arguments[2] !== n ? arguments[2] : 1,
                            a = arguments[4];
                        return {
                            intro: [{ name: "move", parameters: ["" + -(100 * t), "0%", e], duration: 1e3 * i, delay: ee(arguments[3]) }],
                            outro: [{ name: "move", parameters: ["0%", "" + 100 * t, e], duration: 1e3 * i, delay: ee(a) }],
                        };
                    },
                    revolve: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : "y",
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 1,
                            i = 2 < arguments.length && arguments[2] !== n ? arguments[2] : 1,
                            a = arguments[4];
                        return {
                            intro: [{ name: "rotate", parameters: [-(90 * t) + "deg", "0deg", e], duration: 1e3 * i, delay: ee(arguments[3]) }],
                            outro: [{ name: "rotate", parameters: ["0deg", 90 * t + "deg", e], duration: 1e3 * i, delay: ee(a) }],
                        };
                    },
                    zoom: function () {
                        var e = 0 < arguments.length && arguments[0] !== n ? arguments[0] : 0,
                            t = 1 < arguments.length && arguments[1] !== n ? arguments[1] : 1,
                            i = arguments[3];
                        return { intro: [{ name: "scale", parameters: [e, 1], duration: 1e3 * t, delay: ee(arguments[2]) }], outro: [{ name: "scale", parameters: [1, e], duration: 1e3 * t, delay: ee(i) }] };
                    },
                });
                var tF = {
                    supported: (function () {
                        var e = window;
                        if (void 0 === e) return !1;
                        var t = e.CSS && e.CSS.supports,
                            n = !!e.MSInputMethodContext && !!document.documentMode,
                            i = t && CSS.supports("transform", "translateX(0)");
                        return (
                            n ||
                            (t &&
                                i &&
                                !!["MutationObserver", "requestAnimationFrame"].filter(function (t) {
                                    return t in e;
                                }).length)
                        );
                    })(),
                    options: {
                        setConstant: function (e, t) {
                            tL[e] = t;
                        },
                        setPreset: function (e, t) {
                            tA[e] = t;
                        },
                    },
                    helper: {
                        interval: tw,
                        date: function (e) {
                            return e ? _(e) : T();
                        },
                        duration: S,
                    },
                    data: {
                        request: td,
                        poll: function (e, t, i) {
                            return tw(
                                function () {
                                    td(e, t);
                                },
                                2 < arguments.length && i !== n ? i : 6e4
                            );
                        },
                    },
                    DOM: {
                        create: tP,
                        destroy: function (e) {
                            var t = (function (e, t) {
                                for (var n = 0, i = e.length; n < i; n++) if (e[n].isRootElement(t)) return n;
                                return -1;
                            })(t8, e);
                            return !(t < 0 || (t8[t].destroy(), t8.splice(t, 1), 0));
                        },
                        parse: function (e) {
                            var t,
                                n = void 0,
                                i = void 0,
                                a = [];
                            for (i = (t = e.querySelectorAll(".tick:not([data-state])")).length; i--; ) a.push(tP((n = t[i])));
                            return a;
                        },
                        find: ty,
                    },
                    count: {
                        down: function () {
                            for (var e = arguments.length, t = Array(e), i = 0; i < e; i++) t[i] = arguments[i];
                            if ("number" != typeof t[0] || "string" != typeof t[1])
                                return "string" == typeof t[0] || m(t[0])
                                    ? function (e, t) {
                                          var i = 1 < arguments.length && t !== n ? t : {};
                                          if (void 0 === e) throw 'Can\'t start counter, the "due" parameter is required';
                                          i = ta(tN, i);
                                          var a = m(e) ? e : _(e),
                                              s = tC({ due: O(a), onended: function () {} });
                                          return (
                                              tT(i.server, function (e) {
                                                  (s.offset = e),
                                                      (s.timer = tw(
                                                          function () {
                                                              var t = y(e);
                                                              if (a - t <= 0) return (s.value = Array(i.format.length).fill(0)), s.onupdate(s.value), s.timer.stop(), void s.onended();
                                                              (s.value = x(t, a, i.format, i.cascade)), s.onupdate(s.value);
                                                          },
                                                          i.interval,
                                                          { autostart: !1 }
                                                      )),
                                                      (s.complete = !0),
                                                      s.onload(),
                                                      s.timer.start();
                                              }),
                                              s
                                          );
                                      }.apply(n, t)
                                    : null;
                            var a = t[0],
                                s = t[1].toLowerCase();
                            return (
                                t.shift(),
                                (t[0] = S(a, s)),
                                (t[1] = t[1] || {}),
                                (t[1].units = s),
                                function (e, t) {
                                    var i = 1 < arguments.length && t !== n ? t : {};
                                    if ("number" != typeof e) throw 'Can\'t start counter, the "milliseconds" parameter is required';
                                    var a = (i = ta({ units: "seconds", target: 0, amount: 1e3, interval: 1e3 }, i)).target,
                                        s = e,
                                        o = tC({ target: a, onended: function () {} });
                                    return (
                                        setTimeout(function () {
                                            (o.timer = tw(
                                                function (t) {
                                                    if ((s = e - (t / i.interval) * i.amount) <= a) return (o.value = i.target), o.onupdate(o.value / c[i.units]), o.timer.stop(), void o.onended();
                                                    (o.value = s), o.onupdate(o.value / c[i.units]);
                                                },
                                                i.interval,
                                                { autostart: !1 }
                                            )),
                                                (o.complete = !0),
                                                o.onload(),
                                                o.timer.start();
                                        }, 0),
                                        o
                                    );
                                }.apply(n, t)
                            );
                        },
                        up: function (e, t) {
                            var i = 1 < arguments.length && t !== n ? t : {};
                            if (void 0 === e) throw 'Can\'t start counter, the "since" parameter is required';
                            i = ta(tN, i);
                            var a = m(e) ? e : _(e),
                                s = tC({ since: O(a) });
                            return (
                                tT(i.server, function (e) {
                                    (s.offset = e),
                                        (s.timer = tw(
                                            function () {
                                                var t = y(e);
                                                (s.value = x(a, t, i.format, i.cascade)), s.onupdate(s.value);
                                            },
                                            i.interval,
                                            { autostart: !1 }
                                        )),
                                        (s.complete = !0),
                                        s.onload(),
                                        s.timer.start();
                                }),
                                s
                            );
                        },
                        schedule: function (e, t) {
                            var i = 1 < arguments.length && t !== n ? t : {};
                            if ("string" != typeof e) throw 'Can\'t start scheduler, "schedule" is a required parameter';
                            var a = (i = ta(eo({}, tN, { timezone: null }), i)).timezone
                                    ? (function (e) {
                                          var t = 6e4 * new Date().getTimezoneOffset();
                                          if ("Z" === e) return t;
                                          var n = e.match(/\+|-|[\d]{2}|[\d]{2}/g);
                                          return ("-" === n.shift() ? -1 : 1) * (36e5 * parseInt(n[0], 10) + 6e4 * parseInt(n[1], 10)) + t;
                                      })(i.timezone)
                                    : null,
                                s = tC({ waiting: null, nextScheduledDate: null, previouslyScheduledDate: null, onrepeat: function () {}, onresume: function () {}, onwait: function () {} }),
                                o = n,
                                l = null;
                            return (
                                tT(i.server, function (t) {
                                    (s.offset = t),
                                        (s.timer = tw(
                                            function () {
                                                var u,
                                                    d,
                                                    p = y(t);
                                                if (
                                                    (null !== a && ((u = a), (p = new Date(p.getTime() + u))),
                                                    (l = (function (e, t) {
                                                        for (
                                                            var n = t
                                                                    .split(",")
                                                                    .map(ts)
                                                                    .map(function (t) {
                                                                        var n, i, a, s, o;
                                                                        return (
                                                                            (n = e),
                                                                            (i = t),
                                                                            /januari|februari|march|april|may|june|july|august|september|october|november|december/.test(i)
                                                                                ? (function (e, t) {
                                                                                      var n = t.match(
                                                                                          /januari|februari|march|april|may|june|july|august|september|october|november|december|[\d]+th|\dst|\dnd|first|last|at\s[\d]+(?::[\d]+)?(?::[\d]+)?/g
                                                                                      );
                                                                                      if (1 < n.length) {
                                                                                          var i = "";
                                                                                          n.forEach(function (e) {
                                                                                              i = t.split(e)[1] || "";
                                                                                          });
                                                                                          var a = i.trim().match(/wait\s[\d]+\s[a-z]+/);
                                                                                          a && n.push(a[0]);
                                                                                      }
                                                                                      var s = n.reduce(
                                                                                          function (t, n) {
                                                                                              return (
                                                                                                  /([\d]+th|\dst|\dnd|first|last)/.test(n) && (t.day = /^[\d]/.test(n) ? parseInt(n, 10) : "first" === n ? 1 : n),
                                                                                                  /^at/.test(n) ? (t.time = tS(O(e), n.substr(3))) : /wait/.test(n) ? (t.idle = tx(n.substr(5))) : /^[\a-zA-Z]+$/.test(n) && (t.month = n),
                                                                                                  t
                                                                                              );
                                                                                          },
                                                                                          { idle: null, day: null, month: null, time: null, date: null, dist: null, wait: !1 }
                                                                                      );
                                                                                      if (s.time) {
                                                                                          s.time.setDate(1), (s.time = $(s.time, s.month)), (s.time = v(s.time, s.day));
                                                                                          var o = s.time - e,
                                                                                              l = 0;
                                                                                          if ((o < 0 && ((l = o), s.time.setFullYear(s.time.getFullYear() + 1), (o = s.time - e)), null !== s.idle && 0 < l + s.idle)) return (s.wait = !0), s;
                                                                                          s.dist = o;
                                                                                      } else {
                                                                                          (s.time = O(e)), s.time.setDate(1), (s.time = $(s.time, s.month)), (s.time = v(s.time, s.day));
                                                                                          var u = t0(s.time, t);
                                                                                          if (u.wait) return s;
                                                                                          s.time = O(b(e, s.time) && u.date ? u.date : u.from);
                                                                                          var d = s.time - e;
                                                                                          d < 0 && ((s.time = O(u.from)), s.time.setFullYear(s.time.getFullYear() + 1), (d = s.time - e)), (s.dist = d);
                                                                                      }
                                                                                      return (s.date = O(s.time)), s;
                                                                                  })(n, i)
                                                                                : /month/.test(i)
                                                                                ? (function (e, t) {
                                                                                      var n = t.match(/[\d]+th|\dst|\dnd|first|last|at\s[\d]+(?::[\d]+)?(?::[\d]+)?/g);
                                                                                      if (1 < n.length) {
                                                                                          var i = "";
                                                                                          n.forEach(function (e) {
                                                                                              i = t.split(e)[1] || "";
                                                                                          });
                                                                                          var a = i.trim().match(/wait\s[\d]+\s[a-z]+/);
                                                                                          a && n.push(a[0]);
                                                                                      }
                                                                                      var s = n.reduce(
                                                                                          function (t, n) {
                                                                                              return (
                                                                                                  /([\d]+th|\dst|\dnd|first|last)/.test(n) && (t.day = /^[\d]/.test(n) ? parseInt(n, 10) : "first" === n ? 1 : n),
                                                                                                  /^at/.test(n) ? (t.time = tS(O(e), n.substr(3))) : /wait/.test(n) && (t.idle = tx(n.substr(5))),
                                                                                                  t
                                                                                              );
                                                                                          },
                                                                                          { idle: null, day: null, time: null, date: null, dist: null, wait: !1 }
                                                                                      );
                                                                                      if (s.time) {
                                                                                          s.time = v(s.time, s.day);
                                                                                          var o = s.time - e,
                                                                                              l = 0;
                                                                                          if (
                                                                                              (o < 0 && ((l = o), s.time.setDate(1), s.time.setMonth(s.time.getMonth() + 1), v(s.time, s.day), (o = s.time - e)),
                                                                                              null !== s.idle && 0 < l + s.idle)
                                                                                          )
                                                                                              return (s.wait = !0), s;
                                                                                          s.dist = o;
                                                                                      } else {
                                                                                          s.time = v(O(e), s.day);
                                                                                          var u = t0(s.time, t);
                                                                                          if (u.wait) return s;
                                                                                          s.time = O(b(e, s.time) && u.date ? u.date : u.from);
                                                                                          var d = s.time - e;
                                                                                          d < 0 && ((s.time = O(u.from)), s.time.setDate(1), s.time.setMonth(s.time.getMonth() + 1), v(s.time, s.day), (d = s.time - e)), (s.dist = d);
                                                                                      }
                                                                                      return (s.date = O(s.time)), s;
                                                                                  })(n, i)
                                                                                : /(?:mon|tues|wednes|thurs|fri|satur|sun)day/.test(i)
                                                                                ? (function (e, t) {
                                                                                      var n = t.match(/(?:mon|tues|wednes|thurs|fri|satur|sun)day|at\s[\d]+(?::[\d]+)?(?::[\d]+)?/g);
                                                                                      if (1 < n.length) {
                                                                                          var i = "";
                                                                                          n.forEach(function (e) {
                                                                                              i = t.split(e)[1] || "";
                                                                                          });
                                                                                          var a = i.trim().match(/wait\s[\d]+\s[a-z]+/);
                                                                                          a && n.push(a[0]);
                                                                                      }
                                                                                      var s = n.reduce(
                                                                                          function (t, n) {
                                                                                              var i;
                                                                                              return (
                                                                                                  /(?:mon|tues|wednes|thurs|fri|satur|sun)day/.test(n) && (t.day = I[(i = n).charAt(0).toUpperCase() + i.slice(1)]),
                                                                                                  /^at/.test(n) ? (t.time = tS(O(e), n.substr(3))) : /wait/.test(n) && (t.idle = tx(n.substr(5))),
                                                                                                  t
                                                                                              );
                                                                                          },
                                                                                          { idle: null, day: null, time: null, date: null, dist: null, wait: !1 }
                                                                                      );
                                                                                      if (s.time) {
                                                                                          s.time = g(s.time, s.day);
                                                                                          var o = s.time - e;
                                                                                          if ((o < 0 && (s.time.setDate(s.time.getDate() + 7), (o = s.time - e)), null !== s.idle && o >= c.Week - s.idle)) return (s.wait = !0), s;
                                                                                          s.dist = o;
                                                                                      } else {
                                                                                          s.time = g(O(e), s.day);
                                                                                          var l = t0(s.time, t);
                                                                                          if (l.wait) return s;
                                                                                          s.time = O(b(e, s.time) && l.date ? l.date : l.from);
                                                                                          var u = s.time - e;
                                                                                          u < 0 && s.time.setDate(s.time.getDate() + 7), (s.dist = u);
                                                                                      }
                                                                                      return (s.date = O(s.time)), s;
                                                                                  })(n, i)
                                                                                : /day at/.test(i) || /^at /.test(i)
                                                                                ? ((a = n),
                                                                                  (o =
                                                                                      (s = i.match(/([\d]+(?::[\d]+)?(?::[\d]+)?)|(wait\s[\d]+\s[a-z]+)/g).reduce(
                                                                                          function (e, t) {
                                                                                              return /^[\d]/.test(t) ? (e.time = tS(O(a), t)) : /wait/.test(t) && (e.idle = tx(t.substr(5))), e;
                                                                                          },
                                                                                          { idle: null, time: null, date: null, wait: !1, dist: null }
                                                                                      )).time - a) < 0 && (s.time.setDate(s.time.getDate() + 1), (o = s.time - a)),
                                                                                  null !== s.idle && o >= c.Day - s.idle ? (s.wait = !0) : ((s.dist = o), (s.date = O(s.time))),
                                                                                  s)
                                                                                : /hours|hour|minutes|minute|seconds|second/.test(i)
                                                                                ? t0(n, i)
                                                                                : null
                                                                        );
                                                                    }),
                                                                i = null,
                                                                a = 0;
                                                            a < n.length;
                                                            a++
                                                        ) {
                                                            var s = n[a];
                                                            if (null === i && s.wait) return null;
                                                            (null === i || (null === i.dist && null !== s.dist) || (null !== s.dist && s.dist < i.dist)) && (i = s);
                                                        }
                                                        return i.date;
                                                    })(p, e)),
                                                    (s.waiting = null === l),
                                                    s.waiting)
                                                )
                                                    return (
                                                        o === n && (o = null),
                                                        (s.value = Array(i.format.length).fill(0)),
                                                        s.nextScheduledDate && (s.previouslyScheduledDate = O(s.nextScheduledDate)),
                                                        (s.nextScheduledDate = null === l ? null : O(l)),
                                                        void s.onwait(s.previouslyScheduledDate ? O(s.previouslyScheduledDate) : null)
                                                    );
                                                (s.nextScheduledDate = O(l)),
                                                    null === o && s.onresume(O(l)),
                                                    (null === o || (o !== n && ((d = l), o.getTime() !== d.getTime()))) && (s.onrepeat(O(l), o ? O(o) : null), o && (s.previouslyScheduledDate = O(o))),
                                                    (o = O(l)),
                                                    (s.value = x(p, l, i.format, i.cascade)),
                                                    s.onupdate(s.value);
                                            },
                                            i.interval,
                                            { autostart: !1 }
                                        )),
                                        (s.complete = !0),
                                        s.onload(),
                                        s.timer.start();
                                }),
                                s
                            );
                        },
                    },
                    plugin: {
                        add: function (e, t, n) {
                            return "function" != typeof e ? u(e, t, n) : u(e.identifier.type, e.identifier.name, e);
                        },
                    },
                };
                for (var tj in s) t9(tj);
                return (a.exports = tF), a.exports;
            })();
            (i.push = a),
                t.forEach(a),
                (e.Tick = i),
                "loading" !== document.readyState
                    ? setTimeout(function () {
                          s();
                      }, 0)
                    : document.addEventListener("DOMContentLoaded", s);
        }
        function a(e) {
            i.plugin.add.apply(null, e);
        }
        function s() {
            i.DOM.parse(document);
        }
    })(window, window.Tick || []),
    (function (e) {
        "object" == typeof module && module.exports ? (module.exports = e()) : (window.intlTelInput = e());
    })(function (e) {
        return (function () {
            function t(e, t, n) {
                return (t = i(t)) in e ? Object.defineProperty(e, t, { value: n, enumerable: !0, configurable: !0, writable: !0 }) : (e[t] = n), e;
            }
            function n(e, t) {
                for (var n = 0; n < t.length; n++) {
                    var a = t[n];
                    (a.enumerable = a.enumerable || !1), (a.configurable = !0), "value" in a && (a.writable = !0), Object.defineProperty(e, i(a.key), a);
                }
            }
            function i(t) {
                var n = (function t(n, i) {
                    if ("object" != typeof n || null === n) return n;
                    var a = n[Symbol.toPrimitive];
                    if (a !== e) {
                        var s = a.call(n, i || "default");
                        if ("object" != typeof s) return s;
                        throw TypeError("@@toPrimitive must return a primitive value.");
                    }
                    return ("string" === i ? String : Number)(n);
                })(t, "string");
                return "symbol" == typeof n ? n : String(n);
            }
            for (
                var a = [
                        ["Afghanistan (‫افغانستان‬‎)", "af", "93"],
                        ["Albania (Shqip\xebri)", "al", "355"],
                        ["Algeria (‫الجزائر‬‎)", "dz", "213"],
                        ["American Samoa", "as", "1", 5, ["684"]],
                        ["Andorra", "ad", "376"],
                        ["Angola", "ao", "244"],
                        ["Anguilla", "ai", "1", 6, ["264"]],
                        ["Antigua and Barbuda", "ag", "1", 7, ["268"]],
                        ["Argentina", "ar", "54"],
                        ["Armenia (Հայաստան)", "am", "374"],
                        ["Aruba", "aw", "297"],
                        ["Ascension Island", "ac", "247"],
                        ["Australia", "au", "61", 0],
                        ["Austria (\xd6sterreich)", "at", "43"],
                        ["Azerbaijan (Azərbaycan)", "az", "994"],
                        ["Bahamas", "bs", "1", 8, ["242"]],
                        ["Bahrain (‫البحرين‬‎)", "bh", "973"],
                        ["Bangladesh (বাংলাদেশ)", "bd", "880"],
                        ["Barbados", "bb", "1", 9, ["246"]],
                        ["Belarus (Беларусь)", "by", "375"],
                        ["Belgium (Belgi\xeb)", "be", "32"],
                        ["Belize", "bz", "501"],
                        ["Benin (B\xe9nin)", "bj", "229"],
                        ["Bermuda", "bm", "1", 10, ["441"]],
                        ["Bhutan (འབྲུག)", "bt", "975"],
                        ["Bolivia", "bo", "591"],
                        ["Bosnia and Herzegovina (Босна и Херцеговина)", "ba", "387"],
                        ["Botswana", "bw", "267"],
                        ["Brazil (Brasil)", "br", "55"],
                        ["British Indian Ocean Territory", "io", "246"],
                        ["British Virgin Islands", "vg", "1", 11, ["284"]],
                        ["Brunei", "bn", "673"],
                        ["Bulgaria (България)", "bg", "359"],
                        ["Burkina Faso", "bf", "226"],
                        ["Burundi (Uburundi)", "bi", "257"],
                        ["Cambodia (កម្ពុជា)", "kh", "855"],
                        ["Cameroon (Cameroun)", "cm", "237"],
                        [
                            "Canada",
                            "ca",
                            "1",
                            1,
                            [
                                "204",
                                "226",
                                "236",
                                "249",
                                "250",
                                "263",
                                "289",
                                "306",
                                "343",
                                "354",
                                "365",
                                "367",
                                "368",
                                "382",
                                "387",
                                "403",
                                "416",
                                "418",
                                "428",
                                "431",
                                "437",
                                "438",
                                "450",
                                "584",
                                "468",
                                "474",
                                "506",
                                "514",
                                "519",
                                "548",
                                "579",
                                "581",
                                "584",
                                "587",
                                "604",
                                "613",
                                "639",
                                "647",
                                "672",
                                "683",
                                "705",
                                "709",
                                "742",
                                "753",
                                "778",
                                "780",
                                "782",
                                "807",
                                "819",
                                "825",
                                "867",
                                "873",
                                "902",
                                "905",
                            ],
                        ],
                        ["Cape Verde (Kabu Verdi)", "cv", "238"],
                        ["Caribbean Netherlands", "bq", "599", 1, ["3", "4", "7"]],
                        ["Cayman Islands", "ky", "1", 12, ["345"]],
                        ["Central African Republic (R\xe9publique centrafricaine)", "cf", "236"],
                        ["Chad (Tchad)", "td", "235"],
                        ["Chile", "cl", "56"],
                        ["China (中国)", "cn", "86"],
                        ["Christmas Island", "cx", "61", 2, ["89164"]],
                        ["Cocos (Keeling) Islands", "cc", "61", 1, ["89162"]],
                        ["Colombia", "co", "57"],
                        ["Comoros (‫جزر القمر‬‎)", "km", "269"],
                        ["Congo (DRC) (Jamhuri ya Kidemokrasia ya Kongo)", "cd", "243"],
                        ["Congo (Republic) (Congo-Brazzaville)", "cg", "242"],
                        ["Cook Islands", "ck", "682"],
                        ["Costa Rica", "cr", "506"],
                        ["C\xf4te d’Ivoire", "ci", "225"],
                        ["Croatia (Hrvatska)", "hr", "385"],
                        ["Cuba", "cu", "53"],
                        ["Cura\xe7ao", "cw", "599", 0],
                        ["Cyprus (Κύπρος)", "cy", "357"],
                        ["Czech Republic (Česk\xe1 republika)", "cz", "420"],
                        ["Denmark (Danmark)", "dk", "45"],
                        ["Djibouti", "dj", "253"],
                        ["Dominica", "dm", "1", 13, ["767"]],
                        ["Dominican Republic (Rep\xfablica Dominicana)", "do", "1", 2, ["809", "829", "849"]],
                        ["Ecuador", "ec", "593"],
                        ["Egypt (‫مصر‬‎)", "eg", "20"],
                        ["El Salvador", "sv", "503"],
                        ["Equatorial Guinea (Guinea Ecuatorial)", "gq", "240"],
                        ["Eritrea", "er", "291"],
                        ["Estonia (Eesti)", "ee", "372"],
                        ["Eswatini", "sz", "268"],
                        ["Ethiopia", "et", "251"],
                        ["Falkland Islands (Islas Malvinas)", "fk", "500"],
                        ["Faroe Islands (F\xf8royar)", "fo", "298"],
                        ["Fiji", "fj", "679"],
                        ["Finland (Suomi)", "fi", "358", 0],
                        ["France", "fr", "33"],
                        ["French Guiana (Guyane fran\xe7aise)", "gf", "594"],
                        ["French Polynesia (Polyn\xe9sie fran\xe7aise)", "pf", "689"],
                        ["Gabon", "ga", "241"],
                        ["Gambia", "gm", "220"],
                        ["Georgia (საქართველო)", "ge", "995"],
                        ["Germany (Deutschland)", "de", "49"],
                        ["Ghana (Gaana)", "gh", "233"],
                        ["Gibraltar", "gi", "350"],
                        ["Greece (Ελλάδα)", "gr", "30"],
                        ["Greenland (Kalaallit Nunaat)", "gl", "299"],
                        ["Grenada", "gd", "1", 14, ["473"]],
                        ["Guadeloupe", "gp", "590", 0],
                        ["Guam", "gu", "1", 15, ["671"]],
                        ["Guatemala", "gt", "502"],
                        ["Guernsey", "gg", "44", 1, ["1481", "7781", "7839", "7911"]],
                        ["Guinea (Guin\xe9e)", "gn", "224"],
                        ["Guinea-Bissau (Guin\xe9 Bissau)", "gw", "245"],
                        ["Guyana", "gy", "592"],
                        ["Haiti", "ht", "509"],
                        ["Honduras", "hn", "504"],
                        ["Hong Kong (香港)", "hk", "852"],
                        ["Hungary (Magyarorsz\xe1g)", "hu", "36"],
                        ["Iceland (\xcdsland)", "is", "354"],
                        ["India (भारत)", "in", "91"],
                        ["Indonesia", "id", "62"],
                        ["Iran (‫ایران‬‎)", "ir", "98"],
                        ["Iraq (‫العراق‬‎)", "iq", "964"],
                        ["Ireland", "ie", "353"],
                        ["Isle of Man", "im", "44", 2, ["1624", "74576", "7524", "7924", "7624"]],
                        ["Israel (‫ישראל‬‎)", "il", "972"],
                        ["Italy (Italia)", "it", "39", 0],
                        ["Jamaica", "jm", "1", 4, ["876", "658"]],
                        ["Japan (日本)", "jp", "81"],
                        ["Jersey", "je", "44", 3, ["1534", "7509", "7700", "7797", "7829", "7937"]],
                        ["Jordan (‫الأردن‬‎)", "jo", "962"],
                        ["Kazakhstan (Казахстан)", "kz", "7", 1, ["33", "7"]],
                        ["Kenya", "ke", "254"],
                        ["Kiribati", "ki", "686"],
                        ["Kosovo", "xk", "383"],
                        ["Kuwait (‫الكويت‬‎)", "kw", "965"],
                        ["Kyrgyzstan (Кыргызстан)", "kg", "996"],
                        ["Laos (ລາວ)", "la", "856"],
                        ["Latvia (Latvija)", "lv", "371"],
                        ["Lebanon (‫لبنان‬‎)", "lb", "961"],
                        ["Lesotho", "ls", "266"],
                        ["Liberia", "lr", "231"],
                        ["Libya (‫ليبيا‬‎)", "ly", "218"],
                        ["Liechtenstein", "li", "423"],
                        ["Lithuania (Lietuva)", "lt", "370"],
                        ["Luxembourg", "lu", "352"],
                        ["Macau (澳門)", "mo", "853"],
                        ["Madagascar (Madagasikara)", "mg", "261"],
                        ["Malawi", "mw", "265"],
                        ["Malaysia", "my", "60"],
                        ["Maldives", "mv", "960"],
                        ["Mali", "ml", "223"],
                        ["Malta", "mt", "356"],
                        ["Marshall Islands", "mh", "692"],
                        ["Martinique", "mq", "596"],
                        ["Mauritania (‫موريتانيا‬‎)", "mr", "222"],
                        ["Mauritius (Moris)", "mu", "230"],
                        ["Mayotte", "yt", "262", 1, ["269", "639"]],
                        ["Mexico (M\xe9xico)", "mx", "52"],
                        ["Micronesia", "fm", "691"],
                        ["Moldova (Republica Moldova)", "md", "373"],
                        ["Monaco", "mc", "377"],
                        ["Mongolia (Монгол)", "mn", "976"],
                        ["Montenegro (Crna Gora)", "me", "382"],
                        ["Montserrat", "ms", "1", 16, ["664"]],
                        ["Morocco (‫المغرب‬‎)", "ma", "212", 0],
                        ["Mozambique (Mo\xe7ambique)", "mz", "258"],
                        ["Myanmar (Burma) (မြန်မာ)", "mm", "95"],
                        ["Namibia (Namibi\xeb)", "na", "264"],
                        ["Nauru", "nr", "674"],
                        ["Nepal (नेपाल)", "np", "977"],
                        ["Netherlands (Nederland)", "nl", "31"],
                        ["New Caledonia (Nouvelle-Cal\xe9donie)", "nc", "687"],
                        ["New Zealand", "nz", "64"],
                        ["Nicaragua", "ni", "505"],
                        ["Niger (Nijar)", "ne", "227"],
                        ["Nigeria", "ng", "234"],
                        ["Niue", "nu", "683"],
                        ["Norfolk Island", "nf", "672"],
                        ["North Korea (조선 민주주의 인민 공화국)", "kp", "850"],
                        ["North Macedonia (Северна Македонија)", "mk", "389"],
                        ["Northern Mariana Islands", "mp", "1", 17, ["670"]],
                        ["Norway (Norge)", "no", "47", 0],
                        ["Oman (‫عُمان‬‎)", "om", "968"],
                        ["Pakistan (‫پاکستان‬‎)", "pk", "92"],
                        ["Palau", "pw", "680"],
                        ["Palestine (‫فلسطين‬‎)", "ps", "970"],
                        ["Panama (Panam\xe1)", "pa", "507"],
                        ["Papua New Guinea", "pg", "675"],
                        ["Paraguay", "py", "595"],
                        ["Peru (Per\xfa)", "pe", "51"],
                        ["Philippines", "ph", "63"],
                        ["Poland (Polska)", "pl", "48"],
                        ["Portugal", "pt", "351"],
                        ["Puerto Rico", "pr", "1", 3, ["787", "939"]],
                        ["Qatar (‫قطر‬‎)", "qa", "974"],
                        ["R\xe9union (La R\xe9union)", "re", "262", 0],
                        ["Romania (Rom\xe2nia)", "ro", "40"],
                        ["Russia (Россия)", "ru", "7", 0],
                        ["Rwanda", "rw", "250"],
                        ["Saint Barth\xe9lemy", "bl", "590", 1],
                        ["Saint Helena", "sh", "290"],
                        ["Saint Kitts and Nevis", "kn", "1", 18, ["869"]],
                        ["Saint Lucia", "lc", "1", 19, ["758"]],
                        ["Saint Martin (Saint-Martin (partie fran\xe7aise))", "mf", "590", 2],
                        ["Saint Pierre and Miquelon (Saint-Pierre-et-Miquelon)", "pm", "508"],
                        ["Saint Vincent and the Grenadines", "vc", "1", 20, ["784"]],
                        ["Samoa", "ws", "685"],
                        ["San Marino", "sm", "378"],
                        ["S\xe3o Tom\xe9 and Pr\xedncipe (S\xe3o Tom\xe9 e Pr\xedncipe)", "st", "239"],
                        ["Saudi Arabia (‫المملكة العربية السعودية‬‎)", "sa", "966"],
                        ["Senegal (S\xe9n\xe9gal)", "sn", "221"],
                        ["Serbia (Србија)", "rs", "381"],
                        ["Seychelles", "sc", "248"],
                        ["Sierra Leone", "sl", "232"],
                        ["Singapore", "sg", "65"],
                        ["Sint Maarten", "sx", "1", 21, ["721"]],
                        ["Slovakia (Slovensko)", "sk", "421"],
                        ["Slovenia (Slovenija)", "si", "386"],
                        ["Solomon Islands", "sb", "677"],
                        ["Somalia (Soomaaliya)", "so", "252"],
                        ["South Africa", "za", "27"],
                        ["South Korea (대한민국)", "kr", "82"],
                        ["South Sudan (‫جنوب السودان‬‎)", "ss", "211"],
                        ["Spain (Espa\xf1a)", "es", "34"],
                        ["Sri Lanka (ශ්‍රී ලංකාව)", "lk", "94"],
                        ["Sudan (‫السودان‬‎)", "sd", "249"],
                        ["Suriname", "sr", "597"],
                        ["Svalbard and Jan Mayen", "sj", "47", 1, ["79"]],
                        ["Sweden (Sverige)", "se", "46"],
                        ["Switzerland (Schweiz)", "ch", "41"],
                        ["Syria (‫سوريا‬‎)", "sy", "963"],
                        ["Taiwan (台灣)", "tw", "886"],
                        ["Tajikistan", "tj", "992"],
                        ["Tanzania", "tz", "255"],
                        ["Thailand (ไทย)", "th", "66"],
                        ["Timor-Leste", "tl", "670"],
                        ["Togo", "tg", "228"],
                        ["Tokelau", "tk", "690"],
                        ["Tonga", "to", "676"],
                        ["Trinidad and Tobago", "tt", "1", 22, ["868"]],
                        ["Tunisia (‫تونس‬‎)", "tn", "216"],
                        ["Turkey (T\xfcrkiye)", "tr", "90"],
                        ["Turkmenistan", "tm", "993"],
                        ["Turks and Caicos Islands", "tc", "1", 23, ["649"]],
                        ["Tuvalu", "tv", "688"],
                        ["U.S. Virgin Islands", "vi", "1", 24, ["340"]],
                        ["Uganda", "ug", "256"],
                        ["Ukraine (Україна)", "ua", "380"],
                        ["United Arab Emirates (‫الإمارات العربية المتحدة‬‎)", "ae", "971"],
                        ["United Kingdom", "gb", "44", 0],
                        ["United States", "us", "1", 0],
                        ["Uruguay", "uy", "598"],
                        ["Uzbekistan (Oʻzbekiston)", "uz", "998"],
                        ["Vanuatu", "vu", "678"],
                        ["Vatican City (Citt\xe0 del Vaticano)", "va", "39", 1, ["06698"]],
                        ["Venezuela", "ve", "58"],
                        ["Vietnam (Việt Nam)", "vn", "84"],
                        ["Wallis and Futuna (Wallis-et-Futuna)", "wf", "681"],
                        ["Western Sahara (‫الصحراء الغربية‬‎)", "eh", "212", 1, ["5288", "5289"]],
                        ["Yemen (‫اليمن‬‎)", "ye", "967"],
                        ["Zambia", "zm", "260"],
                        ["Zimbabwe", "zw", "263"],
                        ["\xc5land Islands", "ax", "358", 1, ["18"]],
                    ],
                    s = 0;
                s < a.length;
                s++
            ) {
                var o = a[s];
                a[s] = { name: o[0], iso2: o[1], dialCode: o[2], priority: o[3] || 0, areaCodes: o[4] || null };
            }
            var l = {
                getInstance: function (e) {
                    var t = e.getAttribute("data-intl-tel-input-id");
                    return window.intlTelInputGlobals.instances[t];
                },
                instances: {},
                documentReady: function () {
                    return "complete" === document.readyState;
                },
            };
            "object" == typeof window && (window.intlTelInputGlobals = l);
            var u = 0,
                d = {
                    allowDropdown: !0,
                    autoInsertDialCode: !1,
                    autoPlaceholder: "polite",
                    customContainer: "",
                    customPlaceholder: null,
                    dropdownContainer: null,
                    excludeCountries: [],
                    formatOnDisplay: !0,
                    geoIpLookup: null,
                    hiddenInput: "",
                    initialCountry: "",
                    localizedCountries: null,
                    nationalMode: !0,
                    onlyCountries: [],
                    placeholderNumberType: "MOBILE",
                    preferredCountries: ["us", "gb"],
                    separateDialCode: !1,
                    showFlags: !0,
                    utilsScript: "",
                },
                c = ["800", "822", "833", "844", "855", "866", "877", "880", "881", "882", "883", "884", "885", "886", "887", "888", "889"],
                p = function (e, t) {
                    for (var n = Object.keys(e), i = 0; i < n.length; i++) t(n[i], e[n[i]]);
                },
                f = function (e) {
                    p(window.intlTelInputGlobals.instances, function (t) {
                        window.intlTelInputGlobals.instances[t][e]();
                    });
                },
                h = (function () {
                    var i, s, o;
                    function l(e, t) {
                        var n = this;
                        (function (e, t) {
                            if (!(e instanceof t)) throw TypeError("Cannot call a class as a function");
                        })(this, l),
                            (this.id = u++),
                            (this.a = e),
                            (this.b = null),
                            (this.c = null);
                        var i = t || {};
                        (this.d = {}),
                            p(d, function (e, t) {
                                n.d[e] = i.hasOwnProperty(e) ? i[e] : t;
                            }),
                            (this.e = Boolean(e.getAttribute("placeholder")));
                    }
                    return (
                        (i = l),
                        (s = [
                            {
                                key: "_init",
                                value: function () {
                                    var e = this;
                                    this.d.nationalMode && (this.d.autoInsertDialCode = !1), this.d.separateDialCode && (this.d.autoInsertDialCode = !1);
                                    var t = this.d.allowDropdown && !this.d.separateDialCode;
                                    if (
                                        (!this.d.showFlags && t && (this.d.showFlags = !0),
                                        (this.g = /Android.+Mobile|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)),
                                        this.g && (document.body.classList.add("iti-mobile"), this.d.dropdownContainer || (this.d.dropdownContainer = document.body)),
                                        (this.isRTL = !!this.a.closest("[dir=rtl]")),
                                        "undefined" != typeof Promise)
                                    ) {
                                        var n = new Promise(function (t, n) {
                                                (e.h = t), (e.i = n);
                                            }),
                                            i = new Promise(function (t, n) {
                                                (e.i0 = t), (e.i1 = n);
                                            });
                                        this.promise = Promise.all([n, i]);
                                    } else (this.h = this.i = function () {}), (this.i0 = this.i1 = function () {});
                                    (this.s = {}), this._b(), this._f(), this._h(), this._i(), this._i3();
                                },
                            },
                            {
                                key: "_b",
                                value: function () {
                                    this._d(), this._d2(), this._e(), this.d.localizedCountries && this._d0(), (this.d.onlyCountries.length || this.d.localizedCountries) && this.p.sort(this._d1);
                                },
                            },
                            {
                                key: "_c",
                                value: function (t, n, i) {
                                    n.length > this.countryCodeMaxLen && (this.countryCodeMaxLen = n.length), this.q.hasOwnProperty(n) || (this.q[n] = []);
                                    for (var a = 0; a < this.q[n].length; a++) if (this.q[n][a] === t) return;
                                    var s = i !== e ? i : this.q[n].length;
                                    this.q[n][s] = t;
                                },
                            },
                            {
                                key: "_d",
                                value: function () {
                                    if (this.d.onlyCountries.length) {
                                        var e = this.d.onlyCountries.map(function (e) {
                                            return e.toLowerCase();
                                        });
                                        this.p = a.filter(function (t) {
                                            return e.indexOf(t.iso2) > -1;
                                        });
                                    } else if (this.d.excludeCountries.length) {
                                        var t = this.d.excludeCountries.map(function (e) {
                                            return e.toLowerCase();
                                        });
                                        this.p = a.filter(function (e) {
                                            return -1 === t.indexOf(e.iso2);
                                        });
                                    } else this.p = a;
                                },
                            },
                            {
                                key: "_d0",
                                value: function () {
                                    for (var e = 0; e < this.p.length; e++) {
                                        var t = this.p[e].iso2.toLowerCase();
                                        this.d.localizedCountries.hasOwnProperty(t) && (this.p[e].name = this.d.localizedCountries[t]);
                                    }
                                },
                            },
                            {
                                key: "_d1",
                                value: function (e, t) {
                                    return e.name < t.name ? -1 : e.name > t.name ? 1 : 0;
                                },
                            },
                            {
                                key: "_d2",
                                value: function () {
                                    (this.countryCodeMaxLen = 0), (this.dialCodes = {}), (this.q = {});
                                    for (var e = 0; e < this.p.length; e++) {
                                        var t = this.p[e];
                                        this.dialCodes[t.dialCode] || (this.dialCodes[t.dialCode] = !0), this._c(t.iso2, t.dialCode, t.priority);
                                    }
                                    for (var n = 0; n < this.p.length; n++) {
                                        var i = this.p[n];
                                        if (i.areaCodes)
                                            for (var a = this.q[i.dialCode][0], s = 0; s < i.areaCodes.length; s++) {
                                                for (var o = i.areaCodes[s], l = 1; l < o.length; l++) {
                                                    var u = i.dialCode + o.substr(0, l);
                                                    this._c(a, u), this._c(i.iso2, u);
                                                }
                                                this._c(i.iso2, i.dialCode + o);
                                            }
                                    }
                                },
                            },
                            {
                                key: "_e",
                                value: function () {
                                    this.preferredCountries = [];
                                    for (var e = 0; e < this.d.preferredCountries.length; e++) {
                                        var t = this.d.preferredCountries[e].toLowerCase(),
                                            n = this._y(t, !1, !0);
                                        n && this.preferredCountries.push(n);
                                    }
                                },
                            },
                            {
                                key: "_e2",
                                value: function (e, t, n) {
                                    var i = document.createElement(e);
                                    return (
                                        t &&
                                            p(t, function (e, t) {
                                                return i.setAttribute(e, t);
                                            }),
                                        n && n.appendChild(i),
                                        i
                                    );
                                },
                            },
                            {
                                key: "_f",
                                value: function () {
                                    this.a.hasAttribute("autocomplete") || (this.a.form && this.a.form.hasAttribute("autocomplete")) || this.a.setAttribute("autocomplete", "off");
                                    var e = this.d,
                                        n = e.allowDropdown,
                                        i = e.separateDialCode,
                                        a = e.showFlags,
                                        s = e.customContainer,
                                        o = e.hiddenInput,
                                        l = e.dropdownContainer,
                                        u = "iti";
                                    n && (u += " iti--allow-dropdown"), i && (u += " iti--separate-dial-code"), a && (u += " iti--show-flags"), s && (u += " ".concat(s));
                                    var d = this._e2("div", { class: u });
                                    this.a.parentNode.insertBefore(d, this.a);
                                    var c = n || a || i;
                                    if (
                                        (c && (this.k = this._e2("div", { class: "iti__flag-container" }, d)),
                                        d.appendChild(this.a),
                                        c &&
                                            (this.selectedFlag = this._e2(
                                                "div",
                                                (function e(n) {
                                                    for (var i = 1; i < arguments.length; i++) {
                                                        var a = null != arguments[i] ? Object(arguments[i]) : {},
                                                            s = Object.keys(a);
                                                        "function" == typeof Object.getOwnPropertySymbols &&
                                                            s.push.apply(
                                                                s,
                                                                Object.getOwnPropertySymbols(a).filter(function (e) {
                                                                    return Object.getOwnPropertyDescriptor(a, e).enumerable;
                                                                })
                                                            ),
                                                            s.forEach(function (e) {
                                                                t(n, e, a[e]);
                                                            });
                                                    }
                                                    return n;
                                                })(
                                                    { class: "iti__selected-flag" },
                                                    n && { role: "combobox", "aria-haspopup": "listbox", "aria-controls": "iti-".concat(this.id, "__country-listbox"), "aria-expanded": "false", "aria-label": "Telephone country code" }
                                                ),
                                                this.k
                                            )),
                                        a && (this.l = this._e2("div", { class: "iti__flag" }, this.selectedFlag)),
                                        this.selectedFlag && this.a.disabled && this.selectedFlag.setAttribute("aria-disabled", "true"),
                                        i && (this.t = this._e2("div", { class: "iti__selected-dial-code" }, this.selectedFlag)),
                                        n &&
                                            (this.a.disabled || this.selectedFlag.setAttribute("tabindex", "0"),
                                            (this.u = this._e2("div", { class: "iti__arrow" }, this.selectedFlag)),
                                            (this.m = this._e2("ul", { class: "iti__country-list iti__hide", id: "iti-".concat(this.id, "__country-listbox"), role: "listbox", "aria-label": "List of countries" })),
                                            this.preferredCountries.length && (this._g(this.preferredCountries, "iti__preferred", !0), this._e2("li", { class: "iti__divider", role: "separator", "aria-disabled": "true" }, this.m)),
                                            this._g(this.p, "iti__standard"),
                                            l ? ((this.dropdown = this._e2("div", { class: "iti iti--container" })), this.dropdown.appendChild(this.m)) : this.k.appendChild(this.m)),
                                        o)
                                    ) {
                                        var p = o,
                                            f = this.a.getAttribute("name");
                                        if (f) {
                                            var h = f.lastIndexOf("[");
                                            -1 !== h && (p = "".concat(f.substr(0, h), "[").concat(p, "]"));
                                        }
                                        (this.hiddenInput = this._e2("input", { type: "hidden", name: p })), d.appendChild(this.hiddenInput);
                                    }
                                },
                            },
                            {
                                key: "_g",
                                value: function (e, t, n) {
                                    for (var i = "", a = 0; a < e.length; a++) {
                                        var s = e[a],
                                            o = n ? "-preferred" : "";
                                        (i += "<li class='iti__country "
                                            .concat(t, "' tabIndex='-1' id='iti-")
                                            .concat(this.id, "__item-")
                                            .concat(s.iso2)
                                            .concat(o, "' role='option' data-dial-code='")
                                            .concat(s.dialCode, "' data-country-code='")
                                            .concat(s.iso2, "' aria-selected='false'>")),
                                            this.d.showFlags && (i += "<div class='iti__flag-box'><div class='iti__flag iti__".concat(s.iso2, "'></div></div>")),
                                            (i += "<span class='iti__country-name'>".concat(s.name, "</span>")),
                                            (i += "<span class='iti__dial-code'>+".concat(s.dialCode, "</span>")),
                                            (i += "</li>");
                                    }
                                    this.m.insertAdjacentHTML("beforeend", i);
                                },
                            },
                            {
                                key: "_h",
                                value: function () {
                                    var e = this.a.getAttribute("value"),
                                        t = this.a.value,
                                        n = e && "+" === e.charAt(0) && (!t || "+" !== t.charAt(0)) ? e : t,
                                        i = this._5(n),
                                        a = this._w(n),
                                        s = this.d,
                                        o = s.initialCountry,
                                        l = s.autoInsertDialCode;
                                    i && !a
                                        ? this._v(n)
                                        : "auto" !== o &&
                                          (o ? this._z(o.toLowerCase()) : i && a ? this._z("us") : ((this.j = this.preferredCountries.length ? this.preferredCountries[0].iso2 : this.p[0].iso2), n || this._z(this.j)),
                                          !n && l && (this.a.value = "+".concat(this.s.dialCode))),
                                        n && this._u(n);
                                },
                            },
                            {
                                key: "_i",
                                value: function () {
                                    this._j(), this.d.autoInsertDialCode && this._l(), this.d.allowDropdown && this._i2(), this.hiddenInput && this._i0();
                                },
                            },
                            {
                                key: "_i0",
                                value: function () {
                                    var e = this;
                                    (this._a14 = function () {
                                        e.hiddenInput.value = e.getNumber();
                                    }),
                                        this.a.form && this.a.form.addEventListener("submit", this._a14);
                                },
                            },
                            {
                                key: "_i1",
                                value: function () {
                                    for (var e = this.a; e && "LABEL" !== e.tagName; ) e = e.parentNode;
                                    return e;
                                },
                            },
                            {
                                key: "_i2",
                                value: function () {
                                    var e = this;
                                    this._a9 = function (t) {
                                        e.m.classList.contains("iti__hide") ? e.a.focus() : t.preventDefault();
                                    };
                                    var t = this._i1();
                                    t && t.addEventListener("click", this._a9),
                                        (this._a10 = function () {
                                            !e.m.classList.contains("iti__hide") || e.a.disabled || e.a.readOnly || e._n();
                                        }),
                                        this.selectedFlag.addEventListener("click", this._a10),
                                        (this._a11 = function (t) {
                                            e.m.classList.contains("iti__hide") && -1 !== ["ArrowUp", "Up", "ArrowDown", "Down", " ", "Enter"].indexOf(t.key) && (t.preventDefault(), t.stopPropagation(), e._n()), "Tab" === t.key && e._2();
                                        }),
                                        this.k.addEventListener("keydown", this._a11);
                                },
                            },
                            {
                                key: "_i3",
                                value: function () {
                                    var e = this;
                                    this.d.utilsScript && !window.intlTelInputUtils
                                        ? window.intlTelInputGlobals.documentReady()
                                            ? window.intlTelInputGlobals.loadUtils(this.d.utilsScript)
                                            : window.addEventListener("load", function () {
                                                  window.intlTelInputGlobals.loadUtils(e.d.utilsScript);
                                              })
                                        : this.i0(),
                                        "auto" === this.d.initialCountry ? this._i4() : this.h();
                                },
                            },
                            {
                                key: "_i4",
                                value: function () {
                                    window.intlTelInputGlobals.autoCountry
                                        ? this.handleAutoCountry()
                                        : window.intlTelInputGlobals.startedLoadingAutoCountry ||
                                          ((window.intlTelInputGlobals.startedLoadingAutoCountry = !0),
                                          "function" == typeof this.d.geoIpLookup &&
                                              this.d.geoIpLookup(
                                                  function (e) {
                                                      (window.intlTelInputGlobals.autoCountry = e.toLowerCase()),
                                                          setTimeout(function () {
                                                              return f("handleAutoCountry");
                                                          });
                                                  },
                                                  function () {
                                                      return f("rejectAutoCountryPromise");
                                                  }
                                              ));
                                },
                            },
                            {
                                key: "_j",
                                value: function () {
                                    var e = this;
                                    (this._a12 = function () {
                                        e._v(e.a.value) && e._m2CountryChange();
                                    }),
                                        this.a.addEventListener("keyup", this._a12),
                                        (this._a13 = function () {
                                            setTimeout(e._a12);
                                        }),
                                        this.a.addEventListener("cut", this._a13),
                                        this.a.addEventListener("paste", this._a13);
                                },
                            },
                            {
                                key: "_j2",
                                value: function (e) {
                                    var t = this.a.getAttribute("maxlength");
                                    return t && e.length > t ? e.substr(0, t) : e;
                                },
                            },
                            {
                                key: "_l",
                                value: function () {
                                    var e = this;
                                    (this._a8 = function () {
                                        e._l2();
                                    }),
                                        this.a.form && this.a.form.addEventListener("submit", this._a8),
                                        this.a.addEventListener("blur", this._a8);
                                },
                            },
                            {
                                key: "_l2",
                                value: function () {
                                    if ("+" === this.a.value.charAt(0)) {
                                        var e = this._m(this.a.value);
                                        (e && this.s.dialCode !== e) || (this.a.value = "");
                                    }
                                },
                            },
                            {
                                key: "_m",
                                value: function (e) {
                                    return e.replace(/\D/g, "");
                                },
                            },
                            {
                                key: "_m2",
                                value: function (e) {
                                    var t = document.createEvent("Event");
                                    t.initEvent(e, !0, !0), this.a.dispatchEvent(t);
                                },
                            },
                            {
                                key: "_n",
                                value: function () {
                                    this.m.classList.remove("iti__hide"),
                                        this.selectedFlag.setAttribute("aria-expanded", "true"),
                                        this._o(),
                                        this.b && (this._x(this.b, !1), this._3(this.b, !0)),
                                        this._p(),
                                        this.u.classList.add("iti__arrow--up"),
                                        this._m2("open:countrydropdown");
                                },
                            },
                            {
                                key: "_n2",
                                value: function (e, t, n) {
                                    n && !e.classList.contains(t) ? e.classList.add(t) : !n && e.classList.contains(t) && e.classList.remove(t);
                                },
                            },
                            {
                                key: "_o",
                                value: function () {
                                    var e = this;
                                    if ((this.d.dropdownContainer && this.d.dropdownContainer.appendChild(this.dropdown), !this.g)) {
                                        var t = this.a.getBoundingClientRect(),
                                            n = window.pageYOffset || document.documentElement.scrollTop,
                                            i = t.top + n,
                                            a = this.m.offsetHeight,
                                            s = i + this.a.offsetHeight + a < n + window.innerHeight,
                                            o = i - a > n;
                                        if ((this._n2(this.m, "iti__country-list--dropup", !s && o), this.d.dropdownContainer)) {
                                            var l = !s && o ? 0 : this.a.offsetHeight;
                                            (this.dropdown.style.top = "".concat(i + l, "px")),
                                                (this.dropdown.style.left = "".concat(t.left + document.body.scrollLeft, "px")),
                                                (this._a4 = function () {
                                                    return e._2();
                                                }),
                                                window.addEventListener("scroll", this._a4);
                                        }
                                    }
                                },
                            },
                            {
                                key: "_o2",
                                value: function (e) {
                                    for (var t = e; t && t !== this.m && !t.classList.contains("iti__country"); ) t = t.parentNode;
                                    return t === this.m ? null : t;
                                },
                            },
                            {
                                key: "_p",
                                value: function () {
                                    var e = this;
                                    (this._a0 = function (t) {
                                        var n = e._o2(t.target);
                                        n && e._x(n, !1);
                                    }),
                                        this.m.addEventListener("mouseover", this._a0),
                                        (this._a1 = function (t) {
                                            var n = e._o2(t.target);
                                            n && e._1(n);
                                        }),
                                        this.m.addEventListener("click", this._a1);
                                    var t = !0;
                                    (this._a2 = function () {
                                        t || e._2(), (t = !1);
                                    }),
                                        document.documentElement.addEventListener("click", this._a2);
                                    var n = "",
                                        i = null;
                                    (this._a3 = function (t) {
                                        t.preventDefault(),
                                            "ArrowUp" === t.key || "Up" === t.key || "ArrowDown" === t.key || "Down" === t.key
                                                ? e._q(t.key)
                                                : "Enter" === t.key
                                                ? e._r()
                                                : "Escape" === t.key
                                                ? e._2()
                                                : /^[a-zA-ZÀ-ÿа-яА-Я ]$/.test(t.key) &&
                                                  (i && clearTimeout(i),
                                                  (n += t.key.toLowerCase()),
                                                  e._s(n),
                                                  (i = setTimeout(function () {
                                                      n = "";
                                                  }, 1e3)));
                                    }),
                                        document.addEventListener("keydown", this._a3);
                                },
                            },
                            {
                                key: "_q",
                                value: function (e) {
                                    var t = "ArrowUp" === e || "Up" === e ? this.c.previousElementSibling : this.c.nextElementSibling;
                                    t && (t.classList.contains("iti__divider") && (t = "ArrowUp" === e || "Up" === e ? t.previousElementSibling : t.nextElementSibling), this._x(t, !0));
                                },
                            },
                            {
                                key: "_r",
                                value: function () {
                                    this.c && this._1(this.c);
                                },
                            },
                            {
                                key: "_s",
                                value: function (e) {
                                    for (var t = 0; t < this.p.length; t++)
                                        if (this._t(this.p[t].name, e)) {
                                            var n = this.m.querySelector("#iti-".concat(this.id, "__item-").concat(this.p[t].iso2));
                                            this._x(n, !1), this._3(n, !0);
                                            break;
                                        }
                                },
                            },
                            {
                                key: "_t",
                                value: function (e, t) {
                                    return e.substr(0, t.length).toLowerCase() === t;
                                },
                            },
                            {
                                key: "_u",
                                value: function (e) {
                                    var t = e;
                                    if (this.d.formatOnDisplay && window.intlTelInputUtils && this.s) {
                                        var n = this.d.nationalMode || ("+" !== t.charAt(0) && !this.d.separateDialCode),
                                            i = intlTelInputUtils.numberFormat,
                                            a = i.NATIONAL,
                                            s = i.INTERNATIONAL;
                                        t = intlTelInputUtils.formatNumber(t, this.s.iso2, n ? a : s);
                                    }
                                    (t = this._7(t)), (this.a.value = t);
                                },
                            },
                            {
                                key: "_v",
                                value: function (e) {
                                    var t = e,
                                        n = this.s.dialCode;
                                    t && "1" === n && "+" !== t.charAt(0) && ("1" !== t.charAt(0) && (t = "1".concat(t)), (t = "+".concat(t))), this.d.separateDialCode && n && "+" !== t.charAt(0) && (t = "+".concat(n).concat(t));
                                    var i = this._5(t, !0),
                                        a = this._m(t),
                                        s = null;
                                    if (i) {
                                        var o = this.q[this._m(i)],
                                            l = -1 !== o.indexOf(this.s.iso2) && a.length <= i.length - 1;
                                        if (!(("1" === n && this._w(a)) || l)) {
                                            for (var u = 0; u < o.length; u++)
                                                if (o[u]) {
                                                    s = o[u];
                                                    break;
                                                }
                                        }
                                    } else "+" === t.charAt(0) && a.length ? (s = "") : (t && "+" !== t) || (s = this.j);
                                    return null !== s && this._z(s);
                                },
                            },
                            {
                                key: "_w",
                                value: function (e) {
                                    var t = this._m(e);
                                    if ("1" === t.charAt(0)) {
                                        var n = t.substr(1, 3);
                                        return -1 !== c.indexOf(n);
                                    }
                                    return !1;
                                },
                            },
                            {
                                key: "_x",
                                value: function (e, t) {
                                    var n = this.c;
                                    n && n.classList.remove("iti__highlight"), (this.c = e), this.c.classList.add("iti__highlight"), this.selectedFlag.setAttribute("aria-activedescendant", e.getAttribute("id")), t && this.c.focus();
                                },
                            },
                            {
                                key: "_y",
                                value: function (e, t, n) {
                                    for (var i = t ? a : this.p, s = 0; s < i.length; s++) if (i[s].iso2 === e) return i[s];
                                    if (n) return null;
                                    throw Error("No country data for '".concat(e, "'"));
                                },
                            },
                            {
                                key: "_z",
                                value: function (e) {
                                    var t = this.d,
                                        n = t.allowDropdown,
                                        i = t.separateDialCode,
                                        a = t.showFlags,
                                        s = this.s.iso2 ? this.s : {};
                                    if (((this.s = e ? this._y(e, !1, !1) : {}), this.s.iso2 && (this.j = this.s.iso2), a && this.l.setAttribute("class", "iti__flag iti__".concat(e)), this._setSelectedCountryFlagTitleAttribute(e, i), i)) {
                                        var o = this.s.dialCode ? "+".concat(this.s.dialCode) : "";
                                        this.t.innerHTML = o;
                                        var l = this.selectedFlag.offsetWidth || this._z2();
                                        this.isRTL ? (this.a.style.paddingRight = "".concat(l + 6, "px")) : (this.a.style.paddingLeft = "".concat(l + 6, "px"));
                                    }
                                    if ((this._0(), n)) {
                                        var u = this.b;
                                        if ((u && (u.classList.remove("iti__active"), u.setAttribute("aria-selected", "false")), e)) {
                                            var d = this.m.querySelector("#iti-".concat(this.id, "__item-").concat(e, "-preferred")) || this.m.querySelector("#iti-".concat(this.id, "__item-").concat(e));
                                            d.setAttribute("aria-selected", "true"), d.classList.add("iti__active"), (this.b = d);
                                        }
                                    }
                                    return s.iso2 !== e;
                                },
                            },
                            {
                                key: "_setSelectedCountryFlagTitleAttribute",
                                value: function (e, t) {
                                    var n;
                                    this.selectedFlag && ((n = e && !t ? "".concat(this.s.name, ": +").concat(this.s.dialCode) : e ? this.s.name : "Unknown"), this.selectedFlag.setAttribute("title", n));
                                },
                            },
                            {
                                key: "_z2",
                                value: function () {
                                    var e = this.a.parentNode.cloneNode();
                                    (e.style.visibility = "hidden"), document.body.appendChild(e);
                                    var t = this.k.cloneNode();
                                    e.appendChild(t);
                                    var n = this.selectedFlag.cloneNode(!0);
                                    t.appendChild(n);
                                    var i = n.offsetWidth;
                                    return e.parentNode.removeChild(e), i;
                                },
                            },
                            {
                                key: "_0",
                                value: function () {
                                    var e = "aggressive" === this.d.autoPlaceholder || (!this.e && "polite" === this.d.autoPlaceholder);
                                    if (window.intlTelInputUtils && e) {
                                        var t = intlTelInputUtils.numberType[this.d.placeholderNumberType],
                                            n = this.s.iso2 ? intlTelInputUtils.getExampleNumber(this.s.iso2, this.d.nationalMode, t) : "";
                                        (n = this._7(n)), "function" == typeof this.d.customPlaceholder && (n = this.d.customPlaceholder(n, this.s)), this.a.setAttribute("placeholder", n);
                                    }
                                },
                            },
                            {
                                key: "_1",
                                value: function (e) {
                                    var t = this._z(e.getAttribute("data-country-code"));
                                    this._2(), this._4(e.getAttribute("data-dial-code")), this.a.focus();
                                    var n = this.a.value.length;
                                    this.a.setSelectionRange(n, n), t && this._m2CountryChange();
                                },
                            },
                            {
                                key: "_2",
                                value: function () {
                                    this.m.classList.add("iti__hide"),
                                        this.selectedFlag.setAttribute("aria-expanded", "false"),
                                        this.selectedFlag.removeAttribute("aria-activedescendant"),
                                        this.u.classList.remove("iti__arrow--up"),
                                        document.removeEventListener("keydown", this._a3),
                                        document.documentElement.removeEventListener("click", this._a2),
                                        this.m.removeEventListener("mouseover", this._a0),
                                        this.m.removeEventListener("click", this._a1),
                                        this.d.dropdownContainer && (this.g || window.removeEventListener("scroll", this._a4), this.dropdown.parentNode && this.dropdown.parentNode.removeChild(this.dropdown)),
                                        this._m2("close:countrydropdown");
                                },
                            },
                            {
                                key: "_3",
                                value: function (e, t) {
                                    var n = this.m,
                                        i = window.pageYOffset || document.documentElement.scrollTop,
                                        a = n.offsetHeight,
                                        s = n.getBoundingClientRect().top + i,
                                        o = e.offsetHeight,
                                        l = e.getBoundingClientRect().top + i,
                                        u = l - s + n.scrollTop,
                                        d = a / 2 - o / 2;
                                    l < s ? (t && (u -= d), (n.scrollTop = u)) : l + o > s + a && (t && (u += d), (n.scrollTop = u - (a - o)));
                                },
                            },
                            {
                                key: "_4",
                                value: function (e) {
                                    var t,
                                        n = this.a.value,
                                        i = "+".concat(e);
                                    if ("+" === n.charAt(0)) {
                                        var a = this._5(n);
                                        (t = a ? n.replace(a, i) : i), (this.a.value = t);
                                    } else this.d.autoInsertDialCode && ((t = n ? i + n : i), (this.a.value = t));
                                },
                            },
                            {
                                key: "_5",
                                value: function (e, t) {
                                    var n = "";
                                    if ("+" === e.charAt(0))
                                        for (var i = "", a = 0; a < e.length; a++) {
                                            var s = e.charAt(a);
                                            if (!isNaN(parseInt(s, 10))) {
                                                if (((i += s), t)) this.q[i] && (n = e.substr(0, a + 1));
                                                else if (this.dialCodes[i]) {
                                                    n = e.substr(0, a + 1);
                                                    break;
                                                }
                                                if (i.length === this.countryCodeMaxLen) break;
                                            }
                                        }
                                    return n;
                                },
                            },
                            {
                                key: "_6",
                                value: function () {
                                    var e = this.a.value.trim(),
                                        t = this.s.dialCode,
                                        n = this._m(e);
                                    return (this.d.separateDialCode && "+" !== e.charAt(0) && t && n ? "+".concat(t) : "") + e;
                                },
                            },
                            {
                                key: "_7",
                                value: function (e) {
                                    var t = e;
                                    if (this.d.separateDialCode) {
                                        var n = this._5(t);
                                        if (n) {
                                            var i = " " === t[(n = "+".concat(this.s.dialCode)).length] || "-" === t[n.length] ? n.length + 1 : n.length;
                                            t = t.substr(i);
                                        }
                                    }
                                    return this._j2(t);
                                },
                            },
                            {
                                key: "_m2CountryChange",
                                value: function () {
                                    this._m2("countrychange");
                                },
                            },
                            {
                                key: "handleAutoCountry",
                                value: function () {
                                    "auto" === this.d.initialCountry && ((this.j = window.intlTelInputGlobals.autoCountry), this.a.value || this.setCountry(this.j), this.h());
                                },
                            },
                            {
                                key: "handleUtils",
                                value: function () {
                                    window.intlTelInputUtils && (this.a.value && this._u(this.a.value), this._0()), this.i0();
                                },
                            },
                            {
                                key: "destroy",
                                value: function () {
                                    var e = this.a.form;
                                    if (this.d.allowDropdown) {
                                        this._2(), this.selectedFlag.removeEventListener("click", this._a10), this.k.removeEventListener("keydown", this._a11);
                                        var t = this._i1();
                                        t && t.removeEventListener("click", this._a9);
                                    }
                                    this.hiddenInput && e && e.removeEventListener("submit", this._a14),
                                        this.d.autoInsertDialCode && (e && e.removeEventListener("submit", this._a8), this.a.removeEventListener("blur", this._a8)),
                                        this.a.removeEventListener("keyup", this._a12),
                                        this.a.removeEventListener("cut", this._a13),
                                        this.a.removeEventListener("paste", this._a13),
                                        this.a.removeAttribute("data-intl-tel-input-id");
                                    var n = this.a.parentNode;
                                    n.parentNode.insertBefore(this.a, n), n.parentNode.removeChild(n), delete window.intlTelInputGlobals.instances[this.id];
                                },
                            },
                            {
                                key: "getExtension",
                                value: function () {
                                    return window.intlTelInputUtils ? intlTelInputUtils.getExtension(this._6(), this.s.iso2) : "";
                                },
                            },
                            {
                                key: "getNumber",
                                value: function (e) {
                                    if (window.intlTelInputUtils) {
                                        var t = this.s.iso2;
                                        return intlTelInputUtils.formatNumber(this._6(), t, e);
                                    }
                                    return "";
                                },
                            },
                            {
                                key: "getNumberType",
                                value: function () {
                                    return window.intlTelInputUtils ? intlTelInputUtils.getNumberType(this._6(), this.s.iso2) : -99;
                                },
                            },
                            {
                                key: "getSelectedCountryData",
                                value: function () {
                                    return this.s;
                                },
                            },
                            {
                                key: "getValidationError",
                                value: function () {
                                    if (window.intlTelInputUtils) {
                                        var e = this.s.iso2;
                                        return intlTelInputUtils.getValidationError(this._6(), e);
                                    }
                                    return -99;
                                },
                            },
                            {
                                key: "isValidNumber",
                                value: function () {
                                    var e = this._6().trim();
                                    return window.intlTelInputUtils ? intlTelInputUtils.isValidNumber(e, this.s.iso2) : null;
                                },
                            },
                            {
                                key: "isPossibleNumber",
                                value: function () {
                                    var e = this._6().trim();
                                    return window.intlTelInputUtils ? intlTelInputUtils.isPossibleNumber(e, this.s.iso2) : null;
                                },
                            },
                            {
                                key: "setCountry",
                                value: function (e) {
                                    var t = e.toLowerCase();
                                    this.s.iso2 !== t && (this._z(t), this._4(this.s.dialCode), this._m2CountryChange());
                                },
                            },
                            {
                                key: "setNumber",
                                value: function (e) {
                                    var t = this._v(e);
                                    this._u(e), t && this._m2CountryChange();
                                },
                            },
                            {
                                key: "setPlaceholderNumberType",
                                value: function (e) {
                                    (this.d.placeholderNumberType = e), this._0();
                                },
                            },
                        ]),
                        n(i.prototype, s),
                        o && n(i, o),
                        Object.defineProperty(i, "prototype", { writable: !1 }),
                        l
                    );
                })();
            l.getCountryData = function () {
                return a;
            };
            var m = function (e, t, n) {
                var i = document.createElement("script");
                (i.onload = function () {
                    f("handleUtils"), t && t();
                }),
                    (i.onerror = function () {
                        f("rejectUtilsScriptPromise"), n && n();
                    }),
                    (i.className = "iti-load-utils"),
                    (i.async = !0),
                    (i.src = e),
                    document.body.appendChild(i);
            };
            return (
                (l.loadUtils = function (e) {
                    if (!window.intlTelInputUtils && !window.intlTelInputGlobals.startedLoadingUtilsScript) {
                        if (((window.intlTelInputGlobals.startedLoadingUtilsScript = !0), "undefined" != typeof Promise))
                            return new Promise(function (t, n) {
                                return m(e, t, n);
                            });
                        m(e);
                    }
                    return null;
                }),
                (l.defaults = d),
                (l.version = "18.2.1"),
                function (e, t) {
                    var n = new h(e, t);
                    return n._init(), e.setAttribute("data-intl-tel-input-id", n.id), (window.intlTelInputGlobals.instances[n.id] = n), n;
                }
            );
        })();
    });
const currency = (e) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(e);
document.addEventListener("DOMContentLoaded", () => {
    let e = document.querySelector(".hero-swiper");
    if (e)
        try {
            new Swiper(e, { loop: !0, speed: 2500, autoplay: { delay: 100 }, slidesPerView: 1, spaceBetween: 64, navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" } });
        } catch (t) {
            console.error("An error occurred while initializing the Swiper:", t);
        }
}),
    document.addEventListener("DOMContentLoaded", () => {
        let e = document.querySelector(".info-swiper");
        if (e)
            try {
                new Swiper(e, { autoHeight: !0, navigation: { nextEl: ".custom-swiper-button-next", prevEl: ".custom-swiper-button-prev" } });
            } catch (t) {
                console.error("An error occurred while initializing the Swiper:", t);
            }
    });

function drawerMenu() {
    return {
        open: !1,
        usedKeyboard: !1,
        init() {
            this.$watch("open", (e) => {
                e && this.$refs.closeButton.focus(), this.toggleOverlay();
            }),
                this.toggleOverlay();
        },
        toggleOverlay() {
            document.body.classList[this.open ? "add" : "remove"]("h-screen", "overflow-hidden");
        },
    };
}
