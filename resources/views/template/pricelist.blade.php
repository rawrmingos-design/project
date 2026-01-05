@extends('template.template')

@section('custom_style')

    <style>
    .ring-green-500\/20 {
    --tw-ring-color: #22c55e33;
}
.ring-inset {
    --tw-ring-inset: inset;
}
.ring-1 {
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
}
.ring, .ring-1 {
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}
.text-green-400 {
    --tw-text-opacity: 1;
    color: rgb(74 222 128 / var(--tw-text-opacity));
}
.py-1 {
    padding-top: .25rem;
    padding-bottom: .25rem;
}
.px-2 {
    padding-left: .5rem;
    padding-right: .5rem;
}
.bg-green-500\/10 {
    background-color: #22c55e1a;
}
.rounded-md {
    border-radius: .375rem;
}
.items-center {
    align-items: center;
}
.inline-flex {
    display: inline-flex
;
}
*, ::backdrop, :after, :before {
    --tw-border-spacing-x: 0;
    --tw-border-spacing-y: 0;
    --tw-translate-x: 0;
    --tw-translate-y: 0;
    --tw-rotate: 0;
    --tw-skew-x: 0;
    --tw-skew-y: 0;
    --tw-scale-x: 1;
    --tw-scale-y: 1;
    --tw-pan-x: ;
    --tw-pan-y: ;
    --tw-pinch-zoom: ;
    --tw-scroll-snap-strictness: proximity;
    --tw-gradient-from-position: ;
    --tw-gradient-via-position: ;
    --tw-gradient-to-position: ;
    --tw-ordinal: ;
    --tw-slashed-zero: ;
    --tw-numeric-figure: ;
    --tw-numeric-spacing: ;
    --tw-numeric-fraction: ;
    --tw-ring-inset: ;
    --tw-ring-offset-width: 0px;
    --tw-ring-offset-color: #fff;
    --tw-ring-color: #3b82f680;
    --tw-ring-offset-shadow: 0 0 #0000;
    --tw-ring-shadow: 0 0 #0000;
    --tw-shadow: 0 0 #0000;
    --tw-shadow-colored: 0 0 #0000;
    --tw-blur: ;
    --tw-brightness: ;
    --tw-contrast: ;
    --tw-grayscale: ;
    --tw-hue-rotate: ;
    --tw-invert: ;
    --tw-saturate: ;
    --tw-sepia: ;
    --tw-drop-shadow: ;
    --tw-backdrop-blur: ;
    --tw-backdrop-brightness: ;
    --tw-backdrop-contrast: ;
    --tw-backdrop-grayscale: ;
    --tw-backdrop-hue-rotate: ;
    --tw-backdrop-invert: ;
    --tw-backdrop-opacity: ;
    --tw-backdrop-saturate: ;
    --tw-backdrop-sepia: ;
}
*, :after, :before {
    box-sizing: border-box;
    border: 0 solid #e5e7eb;
}
.text-left {
    text-align: left;
}
table {
    text-indent: 0;
    border-color: inherit;
    border-collapse: collapse;
}
.border-hitam-700 {
    --tw-border-opacity: 1;
    border-color: rgb(61 67 72 / var(--tw-border-opacity));
}
.truncate, .whitespace-nowrap {
    white-space: nowrap;
}
.space-y-24>:not([hidden])~:not([hidden]) {
    --tw-space-y-reverse: 0;
    margin-top: calc(6rem* calc(1 - var(--tw-space-y-reverse)));
    margin-bottom: calc(6rem* var(--tw-space-y-reverse));
}
        .accordion-button {
            box-shadow: none !important;
        }

        .product .box {
            margin-bottom: 40px;
        }
    </style>
@endsection


@section('content')
    @include('../navbar')
    <section id="price-list" class="relative space-y-12 pb-24">
        <div class="relative flex items-center bg-murky-700 py-8 shadow-2xl md:py-12">
            <div class="absolute h-full w-full">
                <div class="area">
                    <ul class="rectangle">
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                    </ul>
                </div>
            </div>
            <div class="container relative z-20">
                <h2 class="max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">Daftar Harga</h2>
                <p class="mt-6 max-w-3xl">Semua daftar harga dari produk kami. <br> Pilih produk untuk melihat daftar
                    harga </p>
            </div>
        </div>
        <div class="container">
            <div class="border-l-4 border-cyan-400 bg-cyan-100 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-cyan-500">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="max-w-5xl text-sm text-cyan-900">Koneksi tersedia melalui metode POST (API) dan metode
                            GET (H2H). Silahkan baca <a href="/id/docs"
                                class="font-semibold underline decoration-primary-500 underline-offset-2" target="_blank"
                                rel="noopener noreferrer" style="outline: none;">Dokumentasi</a> untuk
                            memulai integrasi dengan {{ $config->judul_web }}. </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                    <div class="col-span-2">
                        <div class="flex flex-col items-start">
                            <input
                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"
                                type="text" id="search" placeholder="#code @item">
                        </div>
                    </div>
                    <div class="flex justify-start md:col-start-4 md:justify-end">
                        <button id="refresh" type="button"
                            class="h-full  rounded-md bg-murky-600 px-4 text-xs duration-300 ease-in-out hover:bg-murky-500">Segarkan</button>
                    </div>
                    <div>
                        <select id="filter"
                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md">
                            <option value="">Pilih Produk</option>
                            @foreach ($kategoris as $kategori)
                                <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-x-4">
                    <button type="button" id="downloadcsv"
                        class="inline-flex w-full items-center justify-center rounded-md border border-murky-500 bg-murky-600 px-4 py-2 text-xs hover:bg-murky-700 disabled:cursor-not-allowed disabled:opacity-75 md:w-auto">Download
                        CSV</button>
                    <button type="button" id="downloadxlsx"
                        class="inline-flex w-full items-center justify-center rounded-md border border-murky-500 bg-murky-600 px-4 py-2 text-xs hover:bg-murky-700 disabled:cursor-not-allowed disabled:opacity-75 md:w-auto">Download
                        XLSX</button>
                    <select id="entries"
                        class="inline-flex w-full cursor-pointer items-center justify-center rounded-md border border-murky-500 bg-murky-600 py-2 text-xs ring-inset hover:bg-murky-700 focus:ring-2 focus:ring-primary-500 md:w-auto">
                        <option value="5">5<!-- --> Entries</option>
                        <option value="10" selected>10<!-- --> Entries</option>
                        <option value="25">25<!-- --> Entries</option>
                        <option value="50">50<!-- --> Entries</option>
                        <option value="100">100<!-- --> Entries</option>
                    </select>
                </div>
                <div class="-mx-4 overflow-x-auto ring-1 ring-murky-600 sm:mx-0 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-murky-600">
                        <thead>
                            <tr>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">
                                        Kategori</div>
                                </th>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Item Produk</div>
                                </th>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">
                                        Public</div>
                                </th>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">
                                        Member</div>
                                </th>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">
                                        Platinum</div>
                                </th>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">
                                        Gold</div>
                                </th>
                                <th scope="col" colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                    <div
                                        class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">
                                        Status</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="result_filter">
                            <?php $no = 1; ?>
                            @foreach ($datas as $d)
                                @php
                                    if ($d->status == 'AVAILABLE') {
                                        $label_pesanan = 'success';
                                    } else {
                                        $label_pesanan = 'danger';
                                    }
                                @endphp
                                <!--<tr class="even:bg-murky-700/50">-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap">{{ $d->nama_kategori }} </div>-->
                                <!--    </td>-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap"> {{ $d->layanan }}</div>-->
                                <!--    </td>-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap"> Rp.{{ number_format($d->harga, 0, ',', '.') }}</div>-->
                                <!--    </td>-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap">-->
                                <!--            Rp.{{ number_format($d->harga_member, 0, ',', '.') }}</div>-->
                                <!--    </td>-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap">-->
                                <!--            Rp.{{ number_format($d->harga_platinum, 0, ',', '.') }}</div>-->
                                <!--    </td>-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap"> Rp.{{ number_format($d->harga_gold, 0, ',', '.') }}-->
                                <!--        </div>-->
                                <!--    </td>-->
                                <!--    <td-->
                                <!--        class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">-->
                                <!--        <div class="whitespace-nowrap"><span-->
                                <!--                class="inline-flex items-center rounded-md bg-green-500/10 px-2 py-1 text-xs font-medium text-green-400 ring-1 ring-inset ring-green-500/20">AVAILABLE</span>-->
                                <!--        </div>-->
                                <!--    </td>-->
                                <!--</tr>-->

                                <?php $no++; ?>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @include('../footer')
    @push('custom_script')
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const tableBody = document.getElementById('result_filter');
        const selectEntries = document.getElementById('entries');
        const filterDropdown = document.getElementById('filter');
        const refreshButton = document.getElementById('refresh');
        const downloadCSVButton = document.getElementById('downloadcsv');
        const downloadXLSXButton = document.getElementById('downloadxlsx');
        
        // Mengambil data produk dari server yang sudah dikirim di view
        const dataProducts = @json($datas); // Data produk dari controller

        // Menampilkan hanya entri yang dipilih
        function updateVisibleRows(count) {
            const rows = document.querySelectorAll("#result_filter tr"); // Update rows after populating
            rows.forEach((row, index) => row.style.display = index < count ? '' : 'none');
        }

        // Menambahkan data ke tabel
        function populateTable(data) {
            tableBody.innerHTML = ''; // Kosongkan tabel
            data.forEach(product => {
                const row = tableBody.insertRow();
                row.innerHTML = `
                    <td class="table-cell px-3 py-3.5 text-xs text-white">${product.nama_kategori}</td>
                    <td class="table-cell px-3 py-3.5 text-xs text-white">${product.layanan}</td>
                    <td class="table-cell px-3 py-3.5 text-xs text-white">Rp.${new Intl.NumberFormat('id-ID').format(product.harga)}</td>
                    <td class="table-cell px-3 py-3.5 text-xs text-white">Rp.${new Intl.NumberFormat('id-ID').format(product.harga_member)}</td>
                    <td class="table-cell px-3 py-3.5 text-xs text-white">Rp.${new Intl.NumberFormat('id-ID').format(product.harga_platinum)}</td>
                    <td class="table-cell px-3 py-3.5 text-xs text-white">Rp.${new Intl.NumberFormat('id-ID').format(product.harga_gold)}</td>
                    <td class="table-cell px-3 py-3.5 text-xs text-white">
                        <span class="inline-flex items-center rounded-md bg-green-500/10 px-2 py-1 text-xs font-medium text-green-400 ring-1 ring-inset ring-green-500/20 uppercase">${product.status}</span>
                    </td>
                `;
            });

            // Update visible rows after populating the table
            updateVisibleRows(parseInt(selectEntries.value));
        }

        // Filter berdasarkan kategori
        filterDropdown.addEventListener('change', function() {
            const selectedId = this.value;
            const filteredProducts = selectedId ? dataProducts.filter(p => p.kategori_id == selectedId) : dataProducts;
            populateTable(filteredProducts);
        });

        // Reset tabel dan dropdown filter
        refreshButton.addEventListener('click', function() {
            filterDropdown.selectedIndex = 0;
            selectEntries.selectedIndex = 0; // Reset the entries dropdown
            populateTable(dataProducts);
        });

        // Fungsi untuk mendownload CSV
        function downloadCSV() {
            const csvContent = Array.from(tableBody.rows).map(row =>
                Array.from(row.cells).map(cell => cell.textContent).join(',')
            ).join('\n');
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'data.csv';
            a.click();
        }

        // Fungsi untuk mendownload XLSX
        function downloadXLSX() {
            const worksheetData = Array.from(tableBody.rows).map(row =>
                Array.from(row.cells).map(cell => cell.textContent)
            );
            const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Data');
            XLSX.writeFile(workbook, 'data.xlsx');
        }

        downloadCSVButton.addEventListener('click', downloadCSV);
        downloadXLSXButton.addEventListener('click', downloadXLSX);

        // Update jumlah entri yang tampil
        selectEntries.addEventListener('change', function() {
            updateVisibleRows(parseInt(this.value));
        });

        // Inisialisasi dengan entri 10 pertama
        populateTable(dataProducts);
    });
</script>

    @endpush
@endsection
