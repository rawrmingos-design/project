@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')
 <section id="zodiac" class="relative overflow-hidden">
        <div class="mx-auto w-full max-w-xl space-y-8 px-4 pt-24 pb-48">
          <div>
            <a href="/" style="outline:none;">
              <img
                src="{{url('')}}{{ !$config ? '' : $config->logo_header }}"
                width="300"
                height="300"
                class="mx-auto h-32 w-auto"
                style="color: transparent;"
                alt="Logo"
              />
            </a>
            <h2 class="mt-2 text-center text-3xl font-bold tracking-tight text-white">Kalkulator Zodiac</h2>
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
    

@include('../footer')
@push('custom_script')



@endpush




@endsection

