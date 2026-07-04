<x-filament-panels::page>
    <style>
        .push-broadcast {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .push-broadcast__hero,
        .push-broadcast__card,
        .push-broadcast__form {
            border: 1px solid rgba(71, 85, 105, .5);
            background: linear-gradient(145deg, rgba(15, 23, 42, .92), rgba(30, 41, 59, .72));
            border-radius: 18px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05), 0 18px 40px rgba(2, 6, 23, .18);
        }

        .push-broadcast__hero {
            position: relative;
            overflow: hidden;
            padding: 1.35rem 1.5rem;
        }

        .push-broadcast__hero::before {
            content: '';
            position: absolute;
            inset: -45% auto auto -8%;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(59, 130, 246, .28), rgba(59, 130, 246, 0) 68%);
            pointer-events: none;
        }

        .push-broadcast__hero-content {
            position: relative;
            z-index: 1;
            max-width: 78ch;
        }

        .push-broadcast__badge {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            width: fit-content;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, .18);
            border: 1px solid rgba(96, 165, 250, .38);
            color: #bfdbfe;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .push-broadcast__title {
            margin: .8rem 0 0;
            color: #f8fafc;
            font-size: clamp(1.35rem, 2.2vw, 2rem);
            line-height: 1.15;
            font-weight: 800;
        }

        .push-broadcast__subtitle {
            margin: .65rem 0 0;
            color: rgba(226, 232, 240, .86);
            font-size: .95rem;
            line-height: 1.65;
        }

        .push-broadcast__grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: .85rem;
        }

        @media (min-width: 900px) {
            .push-broadcast__grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .push-broadcast__card {
            padding: 1rem;
        }

        .push-broadcast__card-title {
            margin: 0;
            color: #f8fafc;
            font-size: .95rem;
            font-weight: 750;
        }

        .push-broadcast__card-text {
            margin: .35rem 0 0;
            color: rgba(226, 232, 240, .72);
            font-size: .83rem;
            line-height: 1.55;
        }

        .push-broadcast__form {
            padding: 1.15rem;
        }

        .push-broadcast__form-head {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            margin-bottom: 1rem;
        }

        .push-broadcast__form-title {
            margin: 0;
            color: #f8fafc;
            font-size: 1rem;
            font-weight: 760;
        }

        .push-broadcast__form-note {
            color: rgba(226, 232, 240, .72);
            font-size: .84rem;
            line-height: 1.5;
        }

        .push-broadcast__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem;
            margin-top: 1.1rem;
        }

        .push-broadcast__safety {
            color: rgba(251, 191, 36, .88);
            font-size: .8rem;
            line-height: 1.5;
        }
    </style>

    <div class="push-broadcast">
        <section class="push-broadcast__hero">
            <div class="push-broadcast__hero-content">
                <span class="push-broadcast__badge">Notifikasi PWA</span>
                <h2 class="push-broadcast__title">Kirim notifikasi ke user yang sudah aktifkan PWA.</h2>
                <p class="push-broadcast__subtitle">
                    Gunakan halaman ini untuk mengirim info penting, promo, atau pengumuman ke pengguna yang sudah
                    mengizinkan notifikasi. Kamu bisa kirim sekarang atau menjadwalkan pengiriman untuk nanti.
                </p>
            </div>
        </section>

        <div class="push-broadcast__grid">
            <section class="push-broadcast__card">
                <h3 class="push-broadcast__card-title">Siapa yang menerima?</h3>
                <p class="push-broadcast__card-text">User yang sudah mengizinkan notifikasi dari web atau PWA di device mereka.</p>
            </section>
            <section class="push-broadcast__card">
                <h3 class="push-broadcast__card-title">Kapan dikirim?</h3>
                <p class="push-broadcast__card-text">Pilih kirim sekarang untuk masuk antrian langsung, atau jadwalkan tanggal dan jam pengiriman.</p>
            </section>
            <section class="push-broadcast__card">
                <h3 class="push-broadcast__card-title">Anti spam</h3>
                <p class="push-broadcast__card-text">Sistem membatasi maksimal 2 pengiriman dalam rentang 2 jam agar user tidak terganggu.</p>
            </section>
        </div>

        <form wire:submit="send" class="push-broadcast__form">
            <div class="push-broadcast__form-head">
                <h3 class="push-broadcast__form-title">Tulis Notifikasi</h3>
                <p class="push-broadcast__form-note">Pastikan pesan, halaman tujuan, dan waktu pengiriman sudah benar sebelum disimpan.</p>
            </div>

            {{ $this->form }}

            <div class="push-broadcast__actions">
                <x-filament::button type="submit" color="primary" size="lg" icon="heroicon-m-paper-airplane">
                    Simpan Pengiriman
                </x-filament::button>
                <span class="push-broadcast__safety">
                    Maksimal 2 notifikasi dalam 2 jam agar user tidak terganggu.
                </span>
            </div>
        </form>
    </div>
</x-filament-panels::page>
