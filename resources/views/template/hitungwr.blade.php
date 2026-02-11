@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')
 <section id="winrate" class="relative overflow-hidden lg:py-24 md:py-16 py-12">
        <div class="mx-auto w-full max-w-xl space-y-8 px-4 pt-10 pb-48">
          <div class="flex flex-col gap-y-2">
            <h2 class="mt-2 text-center text-3xl font-bold tracking-tight text-white">Kalkulator Win Rate</h2>
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
    


@include('../footer')
@push('custom_script')



@endpush




@endsection

