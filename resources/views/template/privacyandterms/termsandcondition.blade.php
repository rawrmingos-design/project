@extends('template.template')

@section('content')
@include('../navbar')
<div class="container pt-4 md:mx-auto md:pt-16">
    <div class="rounded-md bg-murky-700 p-8 shadow-lg">
        <div class="flex flex-col gap-4">
            <div class="text-xl"><h1>Syarat &amp; Ketentuan {{ $config->judul_web }}</h1></div>
            <div class="flex flex-col gap-1">
                <p class="text-xs"><strong>Terakhir diperbarui:</strong> 28 Agustus 2026</p>
                <p class="text-xs">Selamat datang di {{ $config->judul_web }}. Syarat &amp; Ketentuan ini mengatur penggunaan aplikasi dan situs kami di <a class="text-primary-500" href="{{ url('/id') }}">{{ url('/id') }}</a> ("Layanan"). Dengan mendaftar, mengakses, atau melakukan transaksi melalui Layanan, Anda dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan di bawah ini. Jika Anda tidak setuju, mohon untuk tidak menggunakan Layanan.</p>

                <h2 class="mt-4 underline underline-offset-2">1. Definisi</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs"><strong>"Kami"</strong> berarti {{ $config->judul_web }} sebagai penyedia layanan.</li>
                    <li class="ml-4 text-xs"><strong>"Pengguna"</strong> berarti setiap orang yang mengakses atau bertransaksi melalui Layanan.</li>
                    <li class="ml-4 text-xs"><strong>"Produk"</strong> berarti produk digital yang kami jual, termasuk top up game, voucher, pulsa, paket data, token listrik, dan pembayaran tagihan (PPOB).</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">2. Akun Pengguna</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Pengguna bertanggung jawab menjaga kerahasiaan akun dan kata sandinya.</li>
                    <li class="ml-4 text-xs">Segala aktivitas yang terjadi melalui akun menjadi tanggung jawab pemilik akun.</li>
                    <li class="ml-4 text-xs">Data yang didaftarkan harus benar, akurat, dan milik Pengguna sendiri.</li>
                    <li class="ml-4 text-xs">Kami berhak menangguhkan atau menutup akun yang terindikasi melakukan penyalahgunaan atau penipuan.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">3. Produk dan Layanan</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Seluruh Produk yang kami jual bersifat digital dan diproses secara otomatis.</li>
                    <li class="ml-4 text-xs">Ketersediaan dan harga Produk dapat berubah sewaktu-waktu tanpa pemberitahuan terlebih dahulu.</li>
                    <li class="ml-4 text-xs">Kami berhak menolak atau membatalkan pesanan apabila terjadi kesalahan sistem, gangguan penyedia, kelangkaan stok, atau indikasi kecurangan.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">4. Proses Transaksi dan Pengiriman</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Pesanan akan diproses setelah pembayaran diterima dan terverifikasi.</li>
                    <li class="ml-4 text-xs">Produk dikirim secara otomatis ke tujuan yang Anda masukkan (misalnya ID game atau nomor tujuan).</li>
                    <li class="ml-4 text-xs">Waktu proses umumnya instan, namun dapat tertunda karena gangguan pada sistem kami maupun penyedia produk. Dalam hal tertunda, pesanan akan tetap diproses atau dikembalikan sesuai ketentuan di bawah.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">5. Tanggung Jawab Data Tujuan (PENTING)</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Pengguna <strong>wajib memastikan kebenaran</strong> data tujuan yang dimasukkan, seperti ID game, server, nomor telepon, atau nomor pelanggan, <strong>sebelum</strong> menyelesaikan pembayaran.</li>
                    <li class="ml-4 text-xs">Kesalahan input data tujuan yang dilakukan oleh Pengguna <strong>sepenuhnya menjadi tanggung jawab Pengguna</strong> dan <strong>tidak dapat dijadikan dasar pengembalian dana (refund)</strong>, karena Produk telah terkirim ke tujuan yang dimasukkan.</li>
                    <li class="ml-4 text-xs">Kami tidak bertanggung jawab atas kerugian akibat kesalahan input data oleh Pengguna.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">6. Pembayaran</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Pembayaran dilakukan melalui metode yang tersedia (QRIS, transfer bank, e-wallet, dan lainnya).</li>
                    <li class="ml-4 text-xs">Pembayaran diproses oleh penyedia payment gateway pihak ketiga; kami tidak menyimpan data kartu atau kredensial pembayaran Anda.</li>
                    <li class="ml-4 text-xs">Pesanan yang belum dibayar dalam batas waktu yang ditentukan akan otomatis dibatalkan.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">7. Pengembalian Dana dan Komplain</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Pengembalian dana hanya dapat dilakukan apabila kegagalan disebabkan oleh sistem kami, misalnya pembayaran berhasil namun Produk tidak terkirim, sedangkan data tujuan sudah benar.</li>
                    <li class="ml-4 text-xs">Pengembalian dana <strong>tidak berlaku</strong> untuk kesalahan input data tujuan oleh Pengguna.</li>
                    <li class="ml-4 text-xs">Komplain wajib disampaikan sesegera mungkin dengan menyertakan bukti transaksi (nomor invoice, tanggal, dan data tujuan) melalui kontak resmi kami.</li>
                    <li class="ml-4 text-xs">Pengembalian dana yang disetujui akan diproses ke metode/rekening asal atau saldo akun, sesuai kondisi.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">8. Larangan Penyalahgunaan</h2>
                <p class="text-xs">Pengguna dilarang menggunakan Layanan untuk aktivitas melanggar hukum, penipuan, pencucian uang, penggunaan metode pembayaran yang bukan haknya, atau upaya merusak sistem kami. Pelanggaran dapat berakibat penutupan akun dan/atau pelaporan kepada pihak berwenang.</p>
                <h2 class="mt-4 underline underline-offset-2">9. Hak Kekayaan Intelektual</h2>
                <p class="text-xs">Seluruh merek, logo, dan konten pada Layanan adalah milik {{ $config->judul_web }}. Pengguna tidak diperkenankan menyalin, memperbanyak, atau menggunakannya tanpa izin tertulis dari kami.</p>
                <h2 class="mt-4 underline underline-offset-2">10. Batasan Tanggung Jawab</h2>
                <p class="text-xs">Layanan disediakan "sebagaimana adanya". Sepanjang diizinkan oleh hukum yang berlaku, kami tidak bertanggung jawab atas kerugian tidak langsung yang timbul dari penggunaan Layanan, termasuk gangguan yang berada di luar kendali kami seperti gangguan penyedia produk, jaringan, atau sistem pembayaran.</p>
                <h2 class="mt-4 underline underline-offset-2">11. Perubahan Ketentuan</h2>
                <p class="text-xs">Kami dapat memperbarui Syarat &amp; Ketentuan ini sewaktu-waktu. Perubahan berlaku sejak dipublikasikan pada halaman ini. Penggunaan Layanan secara berkelanjutan dianggap sebagai persetujuan terhadap ketentuan yang diperbarui.</p>
                <h2 class="mt-4 underline underline-offset-2">12. Hukum yang Berlaku</h2>
                <p class="text-xs">Syarat &amp; Ketentuan ini diatur dan ditafsirkan berdasarkan hukum Republik Indonesia. Segala sengketa akan diupayakan diselesaikan secara musyawarah terlebih dahulu.</p>

                <h2 class="mt-4 underline underline-offset-2">13. Hubungi Kami</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">WhatsApp: <a class="text-primary-500" href="https://wa.me/6285123031674">+62 851-2303-1674</a></li>
                    <li class="ml-4 text-xs">Website: <a class="text-primary-500" href="{{ url('/id') }}">{{ url('/id') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@include('../footer')
@endsection
