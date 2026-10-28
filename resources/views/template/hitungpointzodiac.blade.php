@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')
 <section id="zodiac" class="relative overflow-hidden lg:py-24 md:py-16 py-12">
        <div class="mx-auto w-full max-w-xl space-y-8 px-4 pt-24 pb-48">
          <div>
            <h1 class="mt-2 text-center text-3xl font-bold tracking-tight text-white">Kalkulator Zodiac Mobile Legends</h1>
            <p class="mt-2 text-center text-sm text-white">
              Digunakan untuk mengetahui total maksimal diamond yang dibutuhkan untuk mendapatkan skin Zodiac.
            </p>
          </div>
          <form class="mt-8 space-y-6" action="#" method="POST">
            <input type="hidden" name="remember" value="true">
            <div class="space-y-4 rounded-md shadow-sm">
              <div class="flex flex-col gap-y-2">
                <label for="range" class="block text-xs font-medium text-white">Geser sesuai dengan Titik Zodiac Anda</label>
                <input type="range" class="range-lg h-2 w-full cursor-pointer appearance-none rounded-lg bg-murky-200 accent-primary-500" type="range" min="0" max="99" value="50" class="slider" id="jangkauanku" onchange="ButtonShow(this.value)">
                <div class="flex items-center justify-between pt-4">
                  <div class="font-semibold">Poin Bintang Kamu <span class="text-primary-500" id="poinBintang"></span></div>
                  <div class="font-semibold">Membutuhkan Maksimal <span class="text-primary-500" id="JmlDiamond"></span> Diamond</div>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-x-4">
              <a class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 group relative flex w-full outline-none" href="https://egymarket.id/id/mobile-legends">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-primary-600 transition-colors group-hover:text-primary-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                  </svg>
                </span> Top Up Diamond Sekarang!
              </a>
            </div>
          </form>
        </div>
      </section>
      <!-- Zodiac Section End -->
    <script>
    function updateValues() {
        const rangeSlider = document.getElementById("jangkauanku");
        const poinBintang = document.getElementById("poinBintang");
        const jmlDiamond = document.getElementById("JmlDiamond");

        const point = rangeSlider.value;
        poinBintang.innerHTML = point;

        let result;
        if (point < 90) {
            result = Math.ceil((2000 - point * 20) * 850 / 1000);
        } else {
            result = Math.ceil((2000 - point * 20));
        }

        jmlDiamond.innerHTML = result;
    }
    document.getElementById("jangkauanku").oninput = updateValues;
    document.addEventListener("DOMContentLoaded", updateValues);
</script>
    

<section class="mx-auto w-full max-w-xl space-y-8 px-4 pb-20 text-white">
  <div class="space-y-3">
    <h2 class="text-xl font-bold">Tentang Kalkulator Zodiac Mobile Legends</h2>
    <p class="text-sm leading-relaxed text-murky-200">
      Skin Zodiac adalah skin spesial Mobile Legends berdasarkan rasi bintang yang diperoleh lewat event gacha diamond. Kalkulator Zodiac ISTANATOPUP membantumu menghitung total diamond maksimal yang dibutuhkan untuk mendapatkan skin Zodiac, supaya kamu bisa menyiapkan budget top up diamond dengan tepat.
    </p>
  </div>

  <div class="space-y-2 rounded-md border border-primary-500 bg-murky-700 p-4 text-center">
    <p class="text-sm text-murky-200">Sudah tahu target diamond-mu? Top up diamond Mobile Legends dengan harga murah &amp; proses otomatis 24 jam.</p>
    <a href="/id/mobile-legends" class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400">Top Up Diamond Mobile Legends</a>
  </div>

  <div class="space-y-4">
    <h2 class="text-xl font-bold">Pertanyaan Umum (FAQ)</h2>
    <div class="space-y-4">
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Apa itu skin Zodiac di Mobile Legends?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Skin Zodiac adalah skin spesial MLBB berdasarkan rasi bintang yang diperoleh lewat event gacha diamond. Gunakan kalkulator di atas untuk menghitung total diamond maksimal yang dibutuhkan.</p>
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Berapa diamond yang dibutuhkan untuk mendapatkan skin Zodiac?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Total diamond maksimal dapat dihitung dengan kalkulator di atas. Masukkan data sesuai event, lalu klik Hitung untuk mengetahui estimasi diamond maksimal yang diperlukan.</p>
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Apakah Kalkulator Zodiac ini gratis?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Ya, Kalkulator Zodiac Mobile Legends dari ISTANATOPUP 100% gratis dan dapat digunakan kapan saja tanpa perlu login.</p>
      </div>
    </div>
  </div>
</section>

@include('../footer')
@push('custom_script')



@endpush




@endsection

