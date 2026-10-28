  @extends('template.template')

@section('custom_style')

@endsection


@section('content')

@include('../navbar')
 <section id="magicWheel" class="relative overflow-hidden lg:py-24 md:py-16 py-12">
        <div class="mx-auto w-full max-w-xl space-y-8 px-4 pt-24 pb-48">
          <div>
            <h1 class="mt-2 text-center text-3xl font-bold tracking-tight text-white">Kalkulator Magic Wheel Mobile Legends</h1>
            <p class="mt-2 text-center text-sm text-white">
              Digunakan untuk mengetahui total maksimal diamond yang dibutuhkan untuk mendapatkan skin Legends.
            </p>
          </div>
          
            <input type="hidden" name="remember" value="true">
            <div class="space-y-4 rounded-md shadow-sm">
              <div class="flex flex-col gap-y-2">
                <label for="range" class="block text-xs font-medium text-white">Geser sesuai dengan Titik Magic Wheel Anda</label>
                <input type="range" class="range-lg h-2 w-full cursor-pointer appearance-none rounded-lg bg-murky-200 accent-primary-500"  min="0" max="199" value="100" class="slider" id="jangkauanku" onchange="ButtonShow(this.value)">
                <div class="flex items-center justify-between pt-4">
                  <div class="font-semibold">Poin Bintang Kamu <span class="text-primary-500" id="poinBintang">0</span></div>
                  <div class="font-semibold">Membutuhkan Maksimal <span class="text-primary-500" id="JmlDiamond"></span> Diamond</div>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-x-4">
              <a class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 group relative flex w-full outline-none" href="/">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-primary-600 transition-colors group-hover:text-primary-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                  </svg>
                </span> Top Up Diamond Sekarang!
              </a>
            </div>
        </div>
      </section>
      <!-- Magic Wheel Section End -->
    <script>
    function updateUI() {
        const sliderValue = slideCol.value;
        poinBintang.innerHTML = sliderValue;

        let SpinKu = 200 - sliderValue;
        let yz = SpinKu < 196 ? Math.ceil(SpinKu / 5) * 270 : SpinKu * 60;

        document.getElementById("JmlDiamond").innerHTML = yz;
    }

    const slideCol = document.getElementById("jangkauanku");
    const poinBintang = document.getElementById("poinBintang");

    slideCol.addEventListener('input', updateUI);

    document.addEventListener('DOMContentLoaded', updateUI);
</script>
    


<section class="mx-auto w-full max-w-xl space-y-8 px-4 pb-20 text-white">
  <div class="space-y-3">
    <h2 class="text-xl font-bold">Tentang Kalkulator Magic Wheel Mobile Legends</h2>
    <p class="text-sm leading-relaxed text-murky-200">
      Magic Wheel adalah event gacha Mobile Legends untuk mendapatkan skin Legend. Kalkulator Magic Wheel ISTANATOPUP membantumu menghitung total diamond maksimal yang dibutuhkan untuk menyelesaikan event dan mendapatkan skin tersebut, supaya kamu bisa menyiapkan budget top up diamond dengan tepat.
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
        <h3 class="text-sm font-semibold">Apa itu Magic Wheel di Mobile Legends?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Magic Wheel adalah event gacha MLBB untuk mendapatkan skin Legend. Gunakan kalkulator di atas untuk menghitung total diamond maksimal yang dibutuhkan menyelesaikan event.</p>
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Berapa diamond yang dibutuhkan untuk menyelesaikan Magic Wheel?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Jumlah maksimal diamond bisa dihitung dengan kalkulator di atas. Masukkan data sesuai event, lalu klik Hitung untuk mengetahui estimasi diamond maksimal yang diperlukan.</p>
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Apakah Kalkulator Magic Wheel ini gratis?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Ya, Kalkulator Magic Wheel Mobile Legends dari ISTANATOPUP 100% gratis dan dapat digunakan kapan saja tanpa perlu login.</p>
      </div>
    </div>
  </div>
</section>

@include('../footer')
@push('custom_script')



@endpush




@endsection

