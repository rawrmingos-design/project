@extends('template.template')

@section('content')
@include('../navbar')
<div class="container pt-4 md:mx-auto md:pt-16">
    <div class="rounded-md bg-murky-700 p-8 shadow-lg">
        <div class="flex flex-col gap-4">
            <div class="text-xl">
                <h1>Kebijakan Privasi {{ $config->judul_web }}</h1>
            </div>
            <div class="flex flex-col gap-1">
                <p class="text-xs"><strong>Terakhir diperbarui:</strong> 28 Agustus 2026</p>
                <p class="text-xs">
                    Kebijakan Privasi ini menjelaskan bagaimana {{ $config->judul_web }} ("kami") mengumpulkan, menggunakan, dan melindungi informasi Anda saat Anda menggunakan aplikasi dan situs kami di <a class="text-primary-500" href="{{ url('/id') }}">{{ url('/id') }}</a> (secara bersama disebut "Layanan"). Dengan menggunakan Layanan, Anda menyetujui praktik dalam kebijakan ini.
                </p>

                <h2 class="mt-4 underline underline-offset-2">1. Informasi yang Kami Kumpulkan</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs"><strong>Informasi akun:</strong> nama, alamat email, dan nomor telepon yang Anda berikan saat mendaftar atau bertransaksi.</li>
                    <li class="ml-4 text-xs"><strong>Data transaksi:</strong> riwayat pesanan, jenis produk, nominal, serta data tujuan seperti ID game atau nomor tujuan top up.</li>
                    <li class="ml-4 text-xs"><strong>Informasi pembayaran:</strong> pembayaran diproses oleh penyedia payment gateway pihak ketiga. Kami <strong>tidak menyimpan</strong> nomor kartu atau kredensial pembayaran Anda di server kami.</li>
                    <li class="ml-4 text-xs"><strong>Data perangkat dan log:</strong> alamat IP, jenis perangkat/browser, dan aktivitas penggunaan untuk keamanan serta pencegahan penipuan.</li>
                    <li class="ml-4 text-xs"><strong>Data analitik:</strong> kami menggunakan Google Analytics dan Google Tag Manager untuk memahami cara Layanan digunakan. Layanan pihak ketiga ini dapat mengumpulkan data melalui cookie sesuai kebijakan privasi masing-masing.</li>
                    <li class="ml-4 text-xs"><strong>Cookie:</strong> digunakan untuk menjaga sesi login dan meningkatkan pengalaman penggunaan Layanan.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">2. Cara Kami Menggunakan Informasi</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Memproses dan menyelesaikan pesanan serta transaksi Anda.</li>
                    <li class="ml-4 text-xs">Menyediakan layanan pelanggan dan menanggapi pertanyaan.</li>
                    <li class="ml-4 text-xs">Menjaga keamanan akun dan mencegah aktivitas penipuan.</li>
                    <li class="ml-4 text-xs">Mengirim notifikasi terkait transaksi dan pembaruan layanan.</li>
                    <li class="ml-4 text-xs">Menganalisis dan meningkatkan Layanan kami.</li>
                    <li class="ml-4 text-xs">Mematuhi kewajiban hukum yang berlaku.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">3. Pembagian Informasi</h2>
                <p class="text-xs">Kami tidak menjual data pribadi Anda. Kami hanya membagikan informasi seperlunya kepada:</p>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Penyedia payment gateway untuk memproses pembayaran Anda.</li>
                    <li class="ml-4 text-xs">Penyedia atau distributor produk untuk memenuhi pesanan top up atau tagihan Anda.</li>
                    <li class="ml-4 text-xs">Penyedia analitik seperti Google, sesuai dengan <a class="text-primary-500" href="https://policies.google.com/privacy">kebijakan privasi Google</a>.</li>
                    <li class="ml-4 text-xs">Pihak berwenang apabila diwajibkan oleh hukum yang berlaku.</li>
                </ul>

                <h2 class="mt-4 underline underline-offset-2">4. Keamanan Data</h2>
                <p class="text-xs">Kami menerapkan langkah keamanan yang wajar untuk melindungi data Anda, termasuk enkripsi pada jalur transmisi. Namun, tidak ada metode penyimpanan atau transmisi elektronik yang sepenuhnya aman.</p>

                <h2 class="mt-4 underline underline-offset-2">5. Hak Anda</h2>
                <p class="text-xs">Anda berhak mengakses, memperbarui, atau meminta penghapusan data pribadi dan akun Anda. Untuk menggunakan hak tersebut, silakan hubungi kami melalui kontak yang tersedia di bawah.</p>

                <h2 class="mt-4 underline underline-offset-2">6. Anak-anak</h2>
                <p class="text-xs">Layanan ini ditujukan untuk pengguna berusia 13 tahun ke atas dan tidak diperuntukkan bagi anak-anak. Kami tidak dengan sengaja mengumpulkan data dari anak di bawah umur.</p>

                <h2 class="mt-4 underline underline-offset-2">7. Perubahan Kebijakan</h2>
                <p class="text-xs">Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan dipublikasikan di halaman ini dengan mencantumkan tanggal pembaruan terbaru.</p>

                <h2 class="mt-4 underline underline-offset-2">8. Hubungi Kami</h2>
                <p class="text-xs">Jika ada pertanyaan mengenai Kebijakan Privasi ini, hubungi kami melalui:</p>
                <ul class="list-disc">
                    <li class="ml-4 text-xs"><strong>WhatsApp:</strong> <a class="text-primary-500" href="https://wa.me/6285123031674">+62 851-2303-1674</a></li>
                    <li class="ml-4 text-xs"><strong>Email:</strong> support@istanatopup.com</li>
                    <li class="ml-4 text-xs"><strong>Website:</strong> <a class="text-primary-500" href="{{ url('/id') }}">{{ url('/id') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@include('../footer')
@endsection