<div>
@if($flashsale->count() > 0)
<div class="container">
  <div class="rounded-2xl bg-muted/50">
    <div class="px-4 pb-3 pt-4">
        <h3 class="flex items-center space-x-4 text-foreground">
             <div class="text-lg font-semibold uppercase leading-relaxed tracking-wider flex items-center">
            {{-- Lottie player disabled due to CDN 403 errors --}}
            {{-- <lottie-player 
                src="https://lottie.host/72527c22-6566-4eda-b453-dc61dd77ef2b/rt3d8phYjG.json" 
                speed="1" 
                style="width: 25px; height: 30px;" 
                loop 
                autoplay 
                direction="1" 
                mode="normal">
            </lottie-player> --}}
            <i class="fa fa-bolt text-primary-500 mr-2" style="font-size: 20px;"></i>
             FLASHSALE
        </div>
               <div class="flex items-center gap-1 text-sm capitalize">
                        <div class="fs-countdown ml-3">
                            <div class="time" id="hours"></div>
                            <div class="separator">:</div>
                            <div class="time" id="minutes"></div>
                            <div class="separator">:</div>
                            <div class="time" id="seconds"></div>
                  </div>
            </div>
        </h3>
        <p class="pl-6 text-xs text-foreground">Pesan sekarang! Persediaan terbatas.</p>
    </div>
        <div class="relative flex h-full w-full flex-col items-center justify-center overflow-hidden pb-2 pt-1">
            <div
                class="group flex overflow-hidden p-2 [--gap:1rem] [gap:var(--gap)] flex-row container [--duration:20s]">
                <div data-run-marquee="true" data-run-marquee-vertical="false" class="flex shrink-0 justify-around [gap:var(--gap)] data-[run-marquee-vertical=true]:animate-marquee-vertical data-[run-marquee=true]:animate-marquee data-[run-marquee]:flex-row data-[run-marquee-vertical=true]:flex-col group-hover:[animation-play-state:paused]">
                    <div class=" flex">
                        <div
                            class="assdafsdvsvasgdsgsdgwgreragwgwrgeargwrgergegsvdsDVSVcsdvdszvsbwtergerg43t34f34343ff34g34gG2">
                            <div id="special_deals">
                                <div class="list swiper-wrapper marquee-content">
                                    @for ($i = 0; $i < $flashsale->count(); $i++)
                                        @foreach ($flashsale as $fs)
                                            @php
                                                $discount = round(
                                                    (($fs->harga - $fs->harga_flash_sale) / $fs->harga) *
                                                        100,
                                                );
                                            @endphp
                                            <a class="swiper-slide-link"
                                                href="{{ url('/id') }}/{{ $fs->kode_game }}">
                                                <div class="item relative" data-item-theme="0722">
                                                    <div class="popular-tag-container">
                                                        <div class="popular-tag-content">
                                                            <div class="rate">{{ $fs->kategori->nama }}</div>
                                                        </div>
                                                        <div class="popular-tag-overlay"></div>
                                                    </div>
                                                    <img alt=""
                                                        class="flash-sale-img lazyloaded rounded-lg"
                                                        src="{{ asset($fs->gmr_thumb) }}" />
                                                    <div class="T truncatee">
                                                        <h2 class="sku text-white text-center">
                                                            <figcaption
                                                                class="text-sm font-medium text-foreground">
                                                                {{ $fs->judul_flash_sale }}</figcaption>
                                                        </h2>
                                                        @php
                                                            $total_stok = 100; // Asumsi total stok awal
                                                            $progress =
                                                                ($fs->sisa_stok / $total_stok) * 100;
                                                        @endphp
                                                        <div class="bar">
                                                            <div class="progress"
                                                                style="width: 100%; background-color: #e7e6e63f ">
                                                                <div class="progress-bar"
                                                                    style="width: {{ $progress }}%; background-color: var(--warna_3); height: 100%;">
                                                                </div>
                                                            </div>

                                                            <span class="progress-text">Tersisa:
                                                                {{ $fs->sisa_stok }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="promo">
                                                    <div class="rate">Rp.
                                                        {{ number_format($fs->harga_flash_sale, 0, '.', ',') }}
                                                    </div>
                                                    <div class="price">
                                                        <b><del class="red-line-through">Rp.
                                                                {{ number_format($fs->harga, 0, '.', ',') }}</del></b>
                                                        <figcaption class="text-sm font-bold">HEMAT Rp
                                                            {{ number_format($fs->harga - $fs->harga_flash_sale, 0, '.', ',') }}
                                                        </figcaption>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($flashsale->count() > 0)
@push('custom_script')
<script>
function updateTimer() {
@foreach($flashsale as $fs)
@php
$expiredFlashSale = new DateTime($fs->expired_flash_sale);
$formattedDate = $expiredFlashSale->format('Y-m-d H:i:s');
@endphp
var countDownDate=new Date("{{ $formattedDate }}").getTime(),x=setInterval(function(){var t=new Date().getTime(),e=countDownDate-t;e>0?(document.getElementById("hours").textContent=Math.floor(e%864e5/36e5).toString().padStart(2,"0"),document.getElementById("minutes").textContent=Math.floor(e%36e5/6e4).toString().padStart(2,"0"),document.getElementById("seconds").textContent=Math.floor(e%6e4/1e3).toString().padStart(2,"0")):(clearInterval(x),document.getElementById("hours").textContent="00",document.getElementById("minutes").textContent="00",document.getElementById("seconds").textContent="00",document.getElementById("expired_time_flash_sale").textContent="Waktu sudah habis!")},1e3);
@endforeach
}
document.addEventListener("DOMContentLoaded", function() {updateTimer();});
</script>
@endpush
@endif
</div>
