<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar Reseller Topup - TopupEngine</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute left-[-8rem] top-[-8rem] h-80 w-80 rounded-full bg-purple-500/20 blur-3xl"></div>
        <div class="absolute right-[-10rem] top-32 h-96 w-96 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute bottom-[-12rem] left-1/3 h-96 w-96 rounded-full bg-fuchsia-500/10 blur-3xl"></div>
    </div>

    <main class="relative mx-auto grid min-h-screen max-w-7xl gap-10 px-4 py-8 lg:grid-cols-[1fr_520px] lg:px-8 lg:py-12">
        <section class="flex flex-col justify-between gap-10">
            <nav class="flex items-center justify-between">
                <a href="/id" class="inline-flex items-center gap-3 font-black tracking-tight">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-purple-500 to-cyan-400 text-slate-950">T</span>
                    <span>TopupEngine</span>
                </a>
                <a href="/id/reseller" class="rounded-full border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10">Kemitraan</a>
            </nav>

            <div class="max-w-3xl">
                <div class="inline-flex rounded-full border border-purple-400/30 bg-purple-400/10 px-4 py-2 text-sm font-bold text-purple-100">
                    Website siap pakai untuk Reseller Topup
                </div>
                <h1 class="mt-6 text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    Buka website topup branded dalam hitungan menit.
                </h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">
                    Daftar, pilih subdomain, bayar paket pertama, lalu toko kamu aktif otomatis. Payment gateway dan supply produk tetap ditangani platform pusat.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-2xl font-black">0</p>
                        <p class="mt-1 text-sm text-slate-400">integrasi gateway terpisah</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-2xl font-black">Gold</p>
                        <p class="mt-1 text-sm text-slate-400">modal harga Gold</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-2xl font-black">Instan</p>
                        <p class="mt-1 text-sm text-slate-400">subdomain wildcard</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                @foreach ($tiers as $key => $tier)
                    <article class="relative rounded-3xl border {{ ($tier['badge'] ?? null) ? 'border-purple-400/60 bg-purple-500/10' : 'border-white/10 bg-white/[0.04]' }} p-5">
                        @if (($tier['badge'] ?? null))
                            <span class="absolute right-4 top-4 rounded-full bg-purple-400 px-3 py-1 text-xs font-black text-slate-950">{{ $tier['badge'] }}</span>
                        @endif
                        <h2 class="text-xl font-black">{{ $tier['name'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $tier['description'] }}</p>
                        <p class="mt-4 text-2xl font-black">
                            @if ((int) $tier['price'] > 0)
                                Rp {{ number_format((int) $tier['price'], 0, ',', '.') }}<span class="text-sm font-semibold text-slate-400">/bulan</span>
                            @else
                                Custom
                            @endif
                        </p>
                        <ul class="mt-4 space-y-2 text-sm text-slate-300">
                            @foreach ($tier['features'] as $feature)
                                <li class="flex gap-2"><span class="text-emerald-300">✓</span><span>{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="self-start rounded-[2rem] border border-white/10 bg-white/[0.06] p-5 shadow-2xl shadow-purple-950/30 backdrop-blur md:p-7">
            <div id="registerPanel">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-purple-200">Daftar Reseller Topup</p>
                <h2 class="mt-2 text-3xl font-black">Buat website topup sendiri</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">Isi data owner dan nama toko. Subdomain aktif setelah invoice langganan dibayar.</p>

                <div id="formAlert" class="mt-5 hidden rounded-2xl border border-red-400/40 bg-red-500/10 p-4 text-sm text-red-100"></div>

                <form id="tenantRegisterForm" class="mt-6 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-200">Nama owner</span>
                            <input name="name" autocomplete="name" required class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-purple-300" placeholder="Raka Reseller">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-200">WhatsApp</span>
                            <input name="no_wa" autocomplete="tel" required class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-purple-300" placeholder="081234567890">
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-200">Email login</span>
                        <input name="email" type="email" autocomplete="email" required class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-purple-300" placeholder="owner@brand.com">
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-200">Password</span>
                        <input name="password" type="password" autocomplete="new-password" required minlength="8" class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-purple-300" placeholder="Minimal 8 karakter">
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-200">Nama toko</span>
                        <input name="store_name" required class="mt-2 w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-purple-300" placeholder="Raka Topup">
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-200">Subdomain toko</span>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-white/10 bg-slate-950/80 focus-within:border-purple-300">
                            <input id="subdomainInput" name="subdomain" required class="min-w-0 flex-1 bg-transparent px-4 py-3 text-white outline-none" placeholder="raka-topup">
                            <span class="border-l border-white/10 px-4 py-3 text-sm text-slate-400">.{{ $baseHost }}</span>
                        </div>
                        <p id="subdomainStatus" class="mt-2 text-xs text-slate-400">Gunakan huruf, angka, dan strip. Minimal 3 karakter.</p>
                    </label>

                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-200">Pilih paket</legend>
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            @foreach ($tiers as $key => $tier)
                                <label class="{{ ($tier['self_service'] ?? true) ? 'cursor-pointer has-[:checked]:border-purple-300 has-[:checked]:bg-purple-500/15' : 'cursor-not-allowed opacity-60' }} rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                                    @if ($tier['self_service'] ?? true)
                                        <input type="radio" name="tier" value="{{ $key }}" class="sr-only" @checked($key === 'starter')>
                                    @endif
                                    <span class="block font-black">{{ $tier['name'] }}</span>
                                    <span class="mt-1 block text-xs text-slate-400">
                                        {{ (int) $tier['price'] > 0 ? 'Rp ' . number_format((int) $tier['price'], 0, ',', '.') : 'Hubungi admin' }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <label class="flex items-start gap-3 text-sm leading-6 text-slate-300">
                        <input type="checkbox" name="terms_accepted" value="1" required class="mt-1 rounded border-white/20 bg-slate-950 text-purple-500">
                        <span>Saya paham dana customer masuk ke payment gateway pusat, lalu margin toko dicatat sebagai komisi.</span>
                    </label>

                    <button id="submitButton" type="submit" class="w-full rounded-2xl bg-gradient-to-r from-purple-500 to-cyan-400 px-5 py-4 font-black text-slate-950 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                        Daftar & Buat Invoice
                    </button>
                </form>
            </div>

            <div id="successPanel" class="hidden">
                <div class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-sm font-bold text-emerald-100">Registrasi berhasil</div>
                <h2 class="mt-5 text-3xl font-black">Invoice langganan dibuat.</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">Selesaikan pembayaran invoice. Setelah webhook paid diterima, toko otomatis aktif.</p>
                <div class="mt-6 space-y-3 rounded-3xl border border-white/10 bg-slate-950/70 p-5 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Toko</span><strong id="successStore"></strong></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Subdomain</span><strong id="successSubdomain"></strong></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Status</span><strong id="successStatus"></strong></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Gateway</span><strong id="successGateway"></strong></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Invoice</span><strong id="successInvoice"></strong></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Amount</span><strong id="successAmount"></strong></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-400">Gateway ref</span><strong id="successGatewayRef" class="text-right"></strong></div>
                    <div id="successVaRow" class="hidden justify-between gap-4"><span class="text-slate-400">VA / QR</span><strong id="successVaNumber" class="text-right break-all"></strong></div>
                </div>
                <a id="successPaymentUrl" href="#" target="_blank" rel="noopener" class="mt-5 hidden w-full rounded-2xl bg-gradient-to-r from-purple-500 to-cyan-400 px-5 py-4 text-center font-black text-slate-950 transition hover:opacity-90">Bayar Sekarang</a>
                <p class="mt-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm leading-6 text-emerald-100">
                    Pembayaran diproses via Duitku. Toko akan aktif otomatis setelah callback pembayaran diterima.
                </p>
                <a href="/id" class="mt-5 inline-flex rounded-2xl border border-white/10 px-5 py-3 font-bold text-slate-100 hover:bg-white/10">Kembali ke landing</a>
            </div>
        </section>
    </main>

    <script>
        (function () {
            const form = document.getElementById('tenantRegisterForm');
            const subdomainInput = document.getElementById('subdomainInput');
            const subdomainStatus = document.getElementById('subdomainStatus');
            const submitButton = document.getElementById('submitButton');
            const formAlert = document.getElementById('formAlert');
            let subdomainTimer = 0;
            let subdomainAvailable = false;

            function rupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
            }

            function setAlert(message) {
                formAlert.textContent = message;
                formAlert.classList.toggle('hidden', !message);
            }

            function setSubdomainStatus(message, tone) {
                subdomainStatus.textContent = message;
                subdomainStatus.className = 'mt-2 text-xs ' + (tone === 'ok' ? 'text-emerald-300' : tone === 'bad' ? 'text-red-300' : 'text-slate-400');
            }

            async function checkSubdomain() {
                const raw = (subdomainInput.value || '').trim();
                subdomainAvailable = false;

                if (raw.length < 3) {
                    setSubdomainStatus('Gunakan huruf, angka, dan strip. Minimal 3 karakter.', 'muted');
                    return;
                }

                setSubdomainStatus('Mengecek ketersediaan...', 'muted');

                try {
                    const response = await fetch('/api/subdomain/check?name=' + encodeURIComponent(raw), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    subdomainInput.value = data.subdomain || raw;
                    subdomainAvailable = Boolean(data.available);
                    setSubdomainStatus(
                        subdomainAvailable ? (data.subdomain + '.{{ $baseHost }} tersedia.') : 'Subdomain tidak tersedia. Coba nama lain.',
                        subdomainAvailable ? 'ok' : 'bad'
                    );
                } catch (error) {
                    setSubdomainStatus('Belum bisa cek subdomain. Coba lagi.', 'bad');
                }
            }

            subdomainInput.addEventListener('input', function () {
                window.clearTimeout(subdomainTimer);
                subdomainTimer = window.setTimeout(checkSubdomain, 350);
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                setAlert('');

                if (!subdomainAvailable) {
                    await checkSubdomain();
                    if (!subdomainAvailable) {
                        return;
                    }
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Memproses...';

                const payload = Object.fromEntries(new FormData(form).entries());

                try {
                    const response = await fetch('/api/tenant/register', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Registrasi belum berhasil.');
                        throw new Error(errors);
                    }

                    document.getElementById('registerPanel').classList.add('hidden');
                    document.getElementById('successPanel').classList.remove('hidden');
                    document.getElementById('successStore').textContent = data.tenant.name;
                    document.getElementById('successSubdomain').textContent = data.tenant.subdomain + '.{{ $baseHost }}';
                    document.getElementById('successStatus').textContent = data.tenant.status;
                    document.getElementById('successGateway').textContent = data.invoice.gateway || '-';
                    document.getElementById('successInvoice').textContent = '#' + data.invoice.id;
                    document.getElementById('successAmount').textContent = rupiah(data.invoice.amount);
                    document.getElementById('successGatewayRef').textContent = data.invoice.gateway_ref;

                    const paymentUrl = document.getElementById('successPaymentUrl');
                    if (data.invoice.payment_url) {
                        paymentUrl.href = data.invoice.payment_url;
                        paymentUrl.classList.remove('hidden');
                    }

                    const va = data.invoice.va_number || data.invoice.qr_string || '';
                    if (va) {
                        document.getElementById('successVaNumber').textContent = va;
                        document.getElementById('successVaRow').classList.remove('hidden');
                        document.getElementById('successVaRow').classList.add('flex');
                    }
                } catch (error) {
                    setAlert(error.message || 'Registrasi belum berhasil.');
                    submitButton.disabled = false;
                    submitButton.textContent = 'Daftar & Buat Invoice';
                }
            });
        })();
    </script>
</body>
</html>
