@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')
 <section id="winrate" class="relative overflow-hidden lg:py-24 md:py-16 py-12">
        <div class="mx-auto w-full max-w-xl space-y-8 px-4 pt-10 pb-48">
          <div class="flex flex-col gap-y-2">
            <h1 class="mt-2 text-center text-3xl font-bold tracking-tight text-white">Kalkulator Win Rate Mobile Legends</h1>
            <p class="mt-2 text-center text-sm text-white">
              Digunakan untuk menghitung total jumlah pertandingan yang harus diambil untuk mencapai target tingkat kemenangan yang diinginkan.
            </p>
          </div>
          <form class="mt-8 space-y-6">
            <div class="space-y-6 rounded-md shadow-sm">
              <div class="flex flex-col gap-y-2">
                <label for="total-match" class="block text-xs font-medium text-white">Total Pertandingan Anda Saat Ini</label>
                <div class="flex flex-col items-start">
                  <input class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md" type="text" id="tMatch" placeholder="Contoh: 223" name="total-match">
                </div>
              </div>
              <div class="flex flex-col gap-y-2">
                <label for="total-winrate" class="block text-xs font-medium text-white">Total Win Rate Anda Saat Ini</label>
                <div class="flex flex-col items-start">
                  <input class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md" type="text" id="tWr" placeholder="Contoh: 54" name="total-winrate">
                </div>
              </div>
              <div class="flex flex-col gap-y-2">
                <label for="winrate-request" class="block text-xs font-medium text-white">Win Rate Total yang Anda Inginkan</label>
                <div class="flex flex-col items-start">
                  <input class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md" type="text" id="wrReq" placeholder="Contoh: 70" name="winrate-request">
                </div>
              </div>
            </div>
            <div class="flex items-center gap-x-4">
              <button class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 group relative flex w-full" type="button" id="hasil">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-primary-600 transition-colors group-hover:text-primary-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"></path>
                  </svg>
                </span>Hitung
              </button>
              <a class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 group relative flex w-full outline-none" href="/">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-primary-600 transition-colors group-hover:text-primary-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                  </svg>
                </span>Pesan Joki
              </a>
            </div>
          </form>
         <div class="rounded-md border border-transparent bg-murky-700 p-4 text-center text-sm font-semibold uppercase ring-2 ring-primary-500 resultDiv">
     <!--Dynamic content will be inserted here -->
</div>
</div>
      </section>
      
      
      
    <script type="text/javascript">
    // Variables
    const tMatch = document.querySelector("#tMatch");
    const tWr = document.querySelector("#tWr");
    const wrReq = document.querySelector("#wrReq");
    const hasil = document.querySelector("#hasil");
    const resultDiv = document.querySelector(".resultDiv");

    // Hide the resultDiv initially
    resultDiv.style.display = 'none';

    // Functions
    function res() {
        const resultNum = rumus(tMatch.value, tWr.value, wrReq.value);
        const text = `You need about <strong class="text-primary-500">${resultNum} Win without Lose</strong> to get a <strong class="text-primary-500">${wrReq.value}% Win Rate</strong>.`;
        resultDiv.innerHTML = text;

        // Show the resultDiv
        resultDiv.style.display = 'block';
    }

    function rumus(tMatch, tWr, wrReq) {
        let tWin = tMatch * (tWr / 100);
        let tLose = tMatch - tWin;
        let sisaWr = 100 - wrReq;
        let wrResult = 100 / sisaWr;
        let seratusPersen = tLose * wrResult;
        let final = seratusPersen - tMatch;
        return Math.round(final);
    }

    // Main
    window.addEventListener("load", init);

    function init() {
        load();
        eventListener();
    }

    function load() {}

    function eventListener() {
        hasil.addEventListener("click", res);
    }
</script>
    


<section class="mx-auto w-full max-w-xl space-y-8 px-4 pb-20 text-white">
  <div class="space-y-3">
    <h2 class="text-xl font-bold">Cara Menghitung Win Rate Mobile Legends</h2>
    <p class="text-sm leading-relaxed text-murky-200">
      Win rate (tingkat kemenangan) di Mobile Legends dihitung dari jumlah match yang kamu menangkan dibagi total match dikali 100%. Untuk menaikkan win rate, kamu perlu menang beruntun tanpa kalah. Kalkulator Win Rate ISTANATOPUP membantumu mengetahui berapa kemenangan beruntun yang dibutuhkan untuk mencapai target win rate &mdash; cukup masukkan total pertandingan, win rate saat ini, dan target win rate, lalu klik <strong>Hitung</strong>.
    </p>
  </div>

  <div class="space-y-3 rounded-md bg-murky-700 p-4">
    <h2 class="text-base font-bold">Contoh Perhitungan Win Rate</h2>
    <p class="text-sm leading-relaxed text-murky-200">
      Misalnya kamu sudah bermain <strong>200 match</strong> dengan win rate <strong>55%</strong> (110 menang, 90 kalah), dan ingin naik ke <strong>60%</strong>. Dengan kalkulator di atas, hasilnya kamu perlu sekitar <strong class="text-primary-500">25 kemenangan beruntun tanpa kalah</strong>. Semakin tinggi target dan semakin banyak total match, semakin besar kemenangan beruntun yang dibutuhkan &mdash; jadi menjaga win rate sejak awal jauh lebih mudah daripada memperbaikinya di kemudian hari.
    </p>
  </div>

  <div class="space-y-3">
    <h2 class="text-xl font-bold">Tips Menaikkan Win Rate di Mobile Legends</h2>
    <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-murky-200">
      <li>Fokus pada 2&ndash;3 hero yang benar-benar kamu kuasai agar permainan lebih konsisten.</li>
      <li>Hindari lanjut push rank saat sedang <em>tilt</em> atau kalah beruntun.</li>
      <li>Prioritaskan objektif tim (Lord, Turtle, turret) daripada sekadar mengejar kill.</li>
      <li>Main bersama party untuk koordinasi yang lebih baik.</li>
    </ul>
  </div>

  <div class="space-y-2 rounded-md border border-primary-500 bg-murky-700 p-4 text-center">
    <p class="text-sm text-murky-200">Siap naikkan rank? Isi diamond-mu dulu dengan harga murah &amp; proses otomatis 24 jam.</p>
    <a href="/id/mobile-legends" class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400">Top Up Diamond Mobile Legends</a>
  </div>

  <div class="space-y-4">
    <h2 class="text-xl font-bold">Pertanyaan Umum (FAQ)</h2>
    <div class="space-y-4">
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Bagaimana cara menghitung win rate Mobile Legends?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Win rate dihitung dari jumlah kemenangan dibagi total pertandingan dikali 100%. Gunakan kalkulator di atas untuk mengetahui berapa kemenangan beruntun tanpa kalah yang dibutuhkan agar mencapai target win rate yang kamu inginkan.</p>
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Berapa match yang dibutuhkan untuk menaikkan win rate di MLBB?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Tergantung total pertandingan dan win rate saat ini. Masukkan total match, win rate sekarang, dan target win rate ke kalkulator, lalu klik Hitung untuk melihat estimasi kemenangan beruntun yang diperlukan.</p>
      </div>
      <div class="space-y-1">
        <h3 class="text-sm font-semibold">Apakah Kalkulator Win Rate ini gratis?</h3>
        <p class="text-sm leading-relaxed text-murky-200">Ya, Kalkulator Win Rate Mobile Legends dari ISTANATOPUP 100% gratis dan dapat digunakan kapan saja tanpa perlu login.</p>
      </div>
    </div>
  </div>
</section>

@include('../footer')
@push('custom_script')



@endpush




@endsection

