@extends('template.template')

@section('content')
@include('../navbar')
<div class="container pt-4 md:mx-auto md:pt-16">
    <div class="rounded-md bg-murky-700 p-8 shadow-lg">
        <div class="flex flex-col gap-4">
            <div class="text-xl"><h1>Penghapusan Akun {{ $config->judul_web }}</h1></div>
            <div class="flex flex-col gap-1">
                <p class="text-xs"><strong>Terakhir diperbarui:</strong> 28 Agustus 2026</p>
                <p class="text-xs">Halaman ini menjelaskan cara pengguna {{ $config->judul_web }} meminta penghapusan akun beserta data pribadi yang terkait. Kami menghormati hak Anda untuk menghapus data Anda dari Layanan kami di <a class="text-primary-500" href="https://istanatopup.com">istanatopup.com</a>.</p>

                <h2 class="mt-4 underline underline-offset-2">Cara Meminta Penghapusan Akun</h2>
                <p class="text-xs">Untuk mengajukan penghapusan akun, ikuti langkah berikut:</p>
                <ol class="list-decimal">
                    <li class="ml-4 text-xs">Hubungi kami melalui WhatsApp resmi di nomor <a class="text-primary-500" href="https://wa.me/6285123031674">+62 851-2303-1674</a>.</li>
                    <li class="ml-4 text-xs">Sampaikan permintaan penghapusan akun dengan menyertakan <strong>alamat email atau nomor telepon</strong> yang terdaftar pada akun Anda sebagai verifikasi kepemilikan.</li>
                    <li class="ml-4 text-xs">Tim kami akan memverifikasi identitas Anda dan memproses permintaan.</li>
                </ol>
                <p class="text-xs"><a class="public-legal-action inline-block rounded-md bg-primary-500 px-4 py-2 text-white no-underline" href="https://wa.me/6285123031674">Ajukan lewat WhatsApp</a></p>

                <h2 class="mt-4 underline underline-offset-2">Data yang Dihapus dan Disimpan</h2>
                <p class="text-xs">Setelah permintaan diverifikasi, kami akan menghapus data pribadi berikut:</p>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">Informasi akun: nama, alamat email, dan nomor telepon.</li>
                    <li class="ml-4 text-xs">Data profil dan preferensi akun Anda.</li>
                </ul>
                <p class="text-xs">Sebagian data mungkin <strong>tetap disimpan untuk jangka waktu terbatas</strong> sesuai kewajiban hukum, akuntansi, dan pencegahan penipuan:</p>
                <table class="w-full text-left text-xs"><thead><tr><th class="border p-2">Jenis data</th><th class="border p-2">Keterangan</th></tr></thead><tbody><tr><td class="border p-2">Catatan transaksi</td><td class="border p-2">Disimpan sesuai ketentuan hukum yang berlaku, lalu dihapus atau dianonimkan setelah periode tersebut berakhir.</td></tr></tbody></table>

                <h2 class="mt-4 underline underline-offset-2">Waktu Pemrosesan</h2>
                <p class="text-xs">Permintaan penghapusan akun umumnya diproses dalam beberapa hari kerja setelah identitas Anda terverifikasi.</p>

                <h2 class="mt-4 underline underline-offset-2">Hubungi Kami</h2>
                <ul class="list-disc">
                    <li class="ml-4 text-xs">WhatsApp: <a class="text-primary-500" href="https://wa.me/6285123031674">+62 851-2303-1674</a></li>
                    <li class="ml-4 text-xs">Website: <a class="text-primary-500" href="https://istanatopup.com">istanatopup.com</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@include('../footer')
@endsection
