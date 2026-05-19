@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')
<section class="public-dashboard-page public-settings-page">
    <div class="public-shell">
        <div class="public-dashboard public-settings-shell">
            @include('components.sidebar-dashboard')

            <main class="public-dashboard-main public-settings-main">
                <header class="public-settings-heading">
                    <h1>Pengaturan</h1>
                    <p>Kelola profil akun, keamanan kata sandi, dan Two Factor Authentication.</p>
                </header>

                <section class="public-settings-card">
                    <div class="space-y-6">
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-white">Profil</h3>
                                <p class="mt-1 max-w-2xl text-sm text-white">Informasi ini bersifat rahasia, jadi berhati-hatilah dengan apa yang Anda bagikan.</p>
                            </div>
                            @if ($errors->any())
                                <div class="public-settings-notice is-error">
                                    <ul class="list-disc space-y-1 pl-4">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if(session('success'))
                                <div class="public-settings-notice is-success">
                                    {{ session('success') }}
                                </div>
                            @endif
                            <form action="{{ route('saveEditProfile')}}" method="POST" class="grid grid-cols-2 gap-4">
					          @csrf 
                                <div class="col-span-2"></div>
                                <div>
                                    <label for="name" class="block text-xs font-medium text-white pb-2">Nama Anda</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            id="nama"
                                            autocomplete="name"
                                            placeholder="Nama Anda"
                                            value="{{Auth()->user()->name}}" name="name" required
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label for="username" class="block text-xs font-medium text-white pb-2">Username</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            autocomplete="username"
                                            placeholder="Username"
                                            value="{{Auth()->user()->username}}" name="username" required
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="apikey" class="block text-xs font-medium text-white pb-2">Api Key</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="apikey"
                                            id="apikey"
                                            autocomplete="apikey"
                                            placeholder="Api Key"
                                            name="apikey"
                                            disabled=""
                                            value="{{Auth()->user()->api_key}}"
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="email" class="block text-xs font-medium text-white pb-2">Alamat Email</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="email"
                                            id="email"
                                            autocomplete="email"
                                            placeholder="Alamat Email"
                                            name="email"
                                            disabled=""
                                            value="{{Auth()->user()->email}}"
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="no" class="block text-xs font-medium text-white pb-2">No. Handphone</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            id="no"
                                            autocomplete="no"
                                            name="no_wa"
                                            placeholder="No. Handphone"
                                            value="{{Auth()->user()->no_wa}}"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <label for="no" class="block text-xs font-medium text-white pb-2">Masukan Password Untuk Merubah</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                            type="text"
                                            name="password" autocomplete="off" placeholder="(Enter if want to changed)"
                                        />
                                    </div>
                                </div>
                                <div class="col-span-2"></div>
                                <div class="pt-4">
                                    <button
                                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75"
                                        type="submit"
                                    >
                                        Ubah Profil
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="space-y-5 pt-7 sm:space-y-6 sm:pt-8">
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-white">Two Factor Authentication</h3>
                                <p class="mt-1 max-w-2xl text-sm text-white">Aktifkan 2FA untuk menambah keamanan akun kamu saat login.</p>
                            </div>

                            <div
                                id="two-factor-box"
                                class="space-y-5 rounded-xl border border-murky-600 bg-murky-700/50 p-4 sm:p-5"
                                data-enabled="{{ filled(Auth()->user()->two_factor_secret) ? '1' : '0' }}"
                                data-setup-url="{{ route('settings.2fa.setup') }}"
                                data-enable-url="{{ route('settings.2fa.enable') }}"
                                data-disable-url="{{ route('settings.2fa.disable') }}"
                                data-csrf="{{ csrf_token() }}"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                                    <div>
                                        <p class="text-sm font-semibold text-white">Status 2FA:
                                            <span id="two-factor-status-badge" class="ml-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ filled(Auth()->user()->two_factor_secret) ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                                                {{ filled(Auth()->user()->two_factor_secret) ? 'Aktif' : 'Belum Aktif' }}
                                            </span>
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        id="two-factor-setup-btn"
                                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 {{ filled(Auth()->user()->two_factor_secret) ? 'hidden' : '' }}"
                                    >
                                        Generate QR 2FA
                                    </button>
                                </div>

                                <div id="two-factor-notice" class="hidden rounded-md px-3 py-2 text-sm leading-relaxed"></div>

                                <div id="two-factor-setup-panel" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-[240px_minmax(0,1fr)] sm:gap-5">
                                    <div class="rounded-lg border border-murky-600 bg-murky-800 p-3">
                                        <img id="two-factor-qr" alt="QR Code 2FA" class="h-[220px] w-[220px] rounded-md bg-white object-contain" />
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-xs text-murky-200">Secret Key</p>
                                            <code id="two-factor-secret" class="mt-1 inline-block rounded-md bg-murky-800 px-2 py-1 text-xs text-amber-300"></code>
                                        </div>
                                        <div>
                                            <label for="two-factor-code" class="block pb-2 text-xs font-medium text-white">Masukkan 6 digit kode dari Google Authenticator</label>
                                            <input
                                                id="two-factor-code"
                                                type="text"
                                                inputmode="numeric"
                                                maxlength="6"
                                                placeholder="123456"
                                                class="relative block w-full appearance-none rounded-md border border-murky-600 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:border-primary-500 focus:outline-none focus:ring-primary-500"
                                            />
                                        </div>
                                        <div class="flex flex-wrap gap-2 pt-1">
                                            <button type="button" id="two-factor-enable-btn" class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75">
                                                Aktifkan 2FA
                                            </button>
                                            <button type="button" id="two-factor-cancel-setup-btn" class="inline-flex items-center justify-center rounded-md border border-murky-600 bg-transparent px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-murky-700">
                                                Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div id="two-factor-recovery-panel" class="hidden space-y-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 sm:p-5">
                                    <p class="text-sm font-semibold text-emerald-300">Simpan Recovery Code ini di tempat aman:</p>
                                    <div id="two-factor-recovery-list" class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                                </div>

                                <div id="two-factor-disable-panel" class="space-y-3 border-t border-murky-600/80 pt-4 {{ filled(Auth()->user()->two_factor_secret) ? '' : 'hidden' }}">
                                    <p class="text-xs text-murky-200">Untuk menonaktifkan 2FA, isi kata sandi saat ini dan kode authenticator.</p>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="two-factor-disable-password" class="block pb-2 text-xs font-medium text-white">Kata Sandi Saat Ini</label>
                                            <input
                                                id="two-factor-disable-password"
                                                type="password"
                                                autocomplete="current-password"
                                                placeholder="Kata sandi"
                                                class="relative block w-full appearance-none rounded-md border border-murky-600 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:border-primary-500 focus:outline-none focus:ring-primary-500"
                                            />
                                        </div>
                                        <div>
                                            <label for="two-factor-disable-code" class="block pb-2 text-xs font-medium text-white">Kode Authenticator</label>
                                            <input
                                                id="two-factor-disable-code"
                                                type="text"
                                                inputmode="numeric"
                                                maxlength="6"
                                                placeholder="123456"
                                                class="relative block w-full appearance-none rounded-md border border-murky-600 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:border-primary-500 focus:outline-none focus:ring-primary-500"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" id="two-factor-disable-btn" class="inline-flex items-center justify-center rounded-md border border-rose-400/30 bg-rose-500/20 px-4 py-2 text-sm font-medium text-rose-200 duration-300 hover:bg-rose-500/30 disabled:cursor-not-allowed disabled:opacity-75">
                                            Nonaktifkan 2FA
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Semua tampilan riwayat deposit di profile disembunyikan sementara --}}
                        <!--<div class="space-y-6">-->
                        <!--    <div class="pt-8">-->
                        <!--        <h3 class="text-base font-semibold leading-6 text-white">Ubah Kata Sandi</h3>-->
                        <!--        <p class="mt-1 max-w-2xl text-sm text-murky-200">Pastikan Anda mengingat kata sandi baru Anda sebelum mengubahnya.</p>-->
                        <!--    </div>-->
                        <!--    <form class="grid grid-cols-2 gap-4" x-data="{ isCurrentPassword: true, isNewPassword: true, isConfirmNewPassword: true }" method="POST" action="/id/settings/change-password">-->
                        <!--        <input type="hidden" name="_token" value="vUHKTN3oWsPl8jZ3CEhNEVofdIK94BabLgf2wDil" />-->
                        <!--        <div class="col-span-2">-->
                        <!--            <label for="current-password" class="block text-xs font-medium text-white pb-2">Kata Sandi Saat Ini</label>-->
                        <!--            <div class="flex flex-col items-start">-->
                        <!--                <input-->
                        <!--                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
                        <!--                    type="password"-->
                        <!--                    type="password"-->
                        <!--                    id="password"-->
                        <!--                    name="password"-->
                        <!--                    autocomplete="current-password"-->
                        <!--                    placeholder="Kata Sandi Saat Ini"-->
                        <!--                />-->
                        <!--                <button type="button" class="absolute top-0 right-4 z-20 h-full text-murky-700" @click="isPassword = !isPassword"></button>-->
                        <!--            </div>-->
                        <!--            <span class="text-xs text-rose-500"></span>-->
                        <!--        </div>-->
                        <!--        <div>-->
                        <!--            <label for="new-password" class="block text-xs font-medium text-white pb-2">Kata Sandi Baru</label>-->
                        <!--            <div class="flex flex-col items-start">-->
                        <!--                <input-->
                        <!--                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
                        <!--                    type="new_password"-->
                        <!--                    id="new_password"-->
                        <!--                    name="new_password"-->
                        <!--                    placeholder="Kata Sandi Baru"-->
                        <!--                />-->
                        <!--            </div>-->
                        <!--            <span class="text-xs text-rose-500"></span>-->
                        <!--        </div>-->
                        <!--        <div>-->
                        <!--            <label for="confirm-new-password" class="block text-xs font-medium text-white pb-2">Konfirmasi Kata Sandi Baru</label>-->
                        <!--            <div class="flex flex-col items-start">-->
                        <!--                <input-->
                        <!--                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
                        <!--                    type="new_password2"-->
                        <!--                    id="new_password2"-->
                        <!--                    name="new_password2"-->
                        <!--                    placeholder="Konfirmasi Kata Sandi Baru"-->
                        <!--                />-->
                        <!--            </div>-->
                        <!--            <span class="text-xs text-rose-500"></span>-->
                        <!--        </div>-->
                        <!--        <div class="pt-4">-->
                        <!--            <button-->
                        <!--                class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75"-->
                        <!--                type="submit"-->
                        <!--            >-->
                        <!--                Ubah Kata Sandi-->
                        <!--            </button>-->
                        <!--        </div>-->
                        <!--    </form>-->
                        <!--</div>-->
                        

                    </div>
                </section>
            </main>
        </div>
    </div>
</section>

      







@include('../footer')
@push('custom_script')
<script>
    (function () {
        const root = document.getElementById('two-factor-box');
        if (!root) return;

        const setupUrl = root.dataset.setupUrl;
        const enableUrl = root.dataset.enableUrl;
        const disableUrl = root.dataset.disableUrl;
        const csrf = root.dataset.csrf || '';

        const setupBtn = document.getElementById('two-factor-setup-btn');
        const statusBadge = document.getElementById('two-factor-status-badge');
        const notice = document.getElementById('two-factor-notice');
        const setupPanel = document.getElementById('two-factor-setup-panel');
        const qrImage = document.getElementById('two-factor-qr');
        const secretText = document.getElementById('two-factor-secret');
        const codeInput = document.getElementById('two-factor-code');
        const enableBtn = document.getElementById('two-factor-enable-btn');
        const cancelSetupBtn = document.getElementById('two-factor-cancel-setup-btn');
        const recoveryPanel = document.getElementById('two-factor-recovery-panel');
        const recoveryList = document.getElementById('two-factor-recovery-list');
        const disablePanel = document.getElementById('two-factor-disable-panel');
        const disablePasswordInput = document.getElementById('two-factor-disable-password');
        const disableCodeInput = document.getElementById('two-factor-disable-code');
        const disableBtn = document.getElementById('two-factor-disable-btn');

        let hasPendingSecret = false;

        const setNotice = (type, message) => {
            if (!notice) return;
            notice.classList.remove('hidden', 'bg-emerald-500/20', 'text-emerald-200', 'bg-rose-500/20', 'text-rose-200', 'bg-amber-500/20', 'text-amber-200');

            if (!message) {
                notice.classList.add('hidden');
                notice.textContent = '';
                return;
            }

            const classes = type === 'success'
                ? ['bg-emerald-500/20', 'text-emerald-200']
                : type === 'warn'
                    ? ['bg-amber-500/20', 'text-amber-200']
                    : ['bg-rose-500/20', 'text-rose-200'];

            notice.classList.add(...classes);
            notice.textContent = message;
        };

        const setLoading = (button, loadingText, active) => {
            if (!button) return;
            if (active) {
                if (!button.dataset.originalText) button.dataset.originalText = button.textContent;
                button.textContent = loadingText;
                button.disabled = true;
                return;
            }
            button.textContent = button.dataset.originalText || button.textContent;
            button.disabled = false;
        };

        const setEnabledState = (enabled) => {
            if (statusBadge) {
                statusBadge.textContent = enabled ? 'Aktif' : 'Belum Aktif';
                statusBadge.className = `ml-2 inline-flex rounded-full px-2 py-1 text-xs font-semibold ${enabled ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'}`;
            }
            if (setupBtn) {
                setupBtn.classList.toggle('hidden', enabled);
            }
            if (disablePanel) {
                disablePanel.classList.toggle('hidden', !enabled);
            }
        };

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        };

        setupBtn?.addEventListener('click', async () => {
            setNotice();
            setLoading(setupBtn, 'Menyiapkan...', true);
            recoveryPanel?.classList.add('hidden');
            if (recoveryList) recoveryList.innerHTML = '';

            try {
                const response = await fetch(setupUrl, {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({}),
                });

                const payload = await response.json();
                if (!response.ok || payload?.status !== 'success') {
                    throw new Error(payload?.message || 'Gagal menyiapkan 2FA.');
                }

                hasPendingSecret = true;
                setupPanel?.classList.remove('hidden');
                if (qrImage) qrImage.src = payload?.data?.qr_image_url || '';
                if (secretText) secretText.textContent = payload?.data?.secret || '-';
                if (codeInput) codeInput.value = '';
                setNotice('success', payload?.message || 'Secret 2FA berhasil dibuat.');
                codeInput?.focus();
            } catch (error) {
                setNotice('error', error.message || 'Gagal menyiapkan 2FA.');
            } finally {
                setLoading(setupBtn, 'Menyiapkan...', false);
            }
        });

        cancelSetupBtn?.addEventListener('click', () => {
            setupPanel?.classList.add('hidden');
            hasPendingSecret = false;
            if (codeInput) codeInput.value = '';
            setNotice('warn', 'Setup 2FA dibatalkan.');
        });

        enableBtn?.addEventListener('click', async () => {
            const code = (codeInput?.value || '').replace(/\D+/g, '').trim();
            if (code.length !== 6) {
                setNotice('error', 'Kode autentikator harus 6 digit angka.');
                return;
            }
            if (!hasPendingSecret) {
                setNotice('warn', 'Silakan klik "Generate QR 2FA" terlebih dahulu.');
                return;
            }

            setNotice();
            setLoading(enableBtn, 'Memverifikasi...', true);

            try {
                const response = await fetch(enableUrl, {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({ code }),
                });

                const payload = await response.json();
                if (!response.ok || payload?.status !== 'success') {
                    throw new Error(payload?.message || 'Gagal mengaktifkan 2FA.');
                }

                setEnabledState(true);
                hasPendingSecret = false;
                setupPanel?.classList.add('hidden');
                if (codeInput) codeInput.value = '';

                const codes = Array.isArray(payload?.data?.recovery_codes) ? payload.data.recovery_codes : [];
                if (codes.length && recoveryList) {
                    recoveryList.innerHTML = codes.map((item) => `<code class="rounded-md bg-murky-800 px-2 py-1 text-xs text-emerald-200">${item}</code>`).join('');
                    recoveryPanel?.classList.remove('hidden');
                }

                setNotice('success', payload?.message || 'Two Factor Authentication berhasil diaktifkan.');
            } catch (error) {
                setNotice('error', error.message || 'Gagal mengaktifkan 2FA.');
            } finally {
                setLoading(enableBtn, 'Memverifikasi...', false);
            }
        });

        disableBtn?.addEventListener('click', async () => {
            const currentPassword = (disablePasswordInput?.value || '').trim();
            const code = (disableCodeInput?.value || '').replace(/\D+/g, '').trim();

            if (!currentPassword) {
                setNotice('error', 'Kata sandi saat ini wajib diisi.');
                return;
            }
            if (code.length !== 6) {
                setNotice('error', 'Kode autentikator harus 6 digit angka.');
                return;
            }

            setNotice();
            setLoading(disableBtn, 'Menonaktifkan...', true);

            try {
                const response = await fetch(disableUrl, {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({
                        current_password: currentPassword,
                        code,
                    }),
                });

                const payload = await response.json();
                if (!response.ok || payload?.status !== 'success') {
                    throw new Error(payload?.message || 'Gagal menonaktifkan 2FA.');
                }

                setEnabledState(false);
                if (disablePasswordInput) disablePasswordInput.value = '';
                if (disableCodeInput) disableCodeInput.value = '';
                recoveryPanel?.classList.add('hidden');
                if (recoveryList) recoveryList.innerHTML = '';
                setNotice('success', payload?.message || 'Two Factor Authentication berhasil dinonaktifkan.');
            } catch (error) {
                setNotice('error', error.message || 'Gagal menonaktifkan 2FA.');
            } finally {
                setLoading(disableBtn, 'Menonaktifkan...', false);
            }
        });
    })();
</script>
@endpush




@endsection
