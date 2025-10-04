@extends('template.template')
@section('custom_style')


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  .text-yellow-400 {
    --tw-text-opacity: 1;
    color: rgb(250 204 21 / var(--tw-text-opacity));
}
</style>

@include('../navbar')

    <div class="relative flex items-center bg-murky-700 py-8 shadow-2xl md:py-12">
        <div class="absolute h-full w-full">
            <div class="area">
                <ul class="circles">
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
            <h2 class="max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">Testimonials</h2>
            <p class="mt-6 max-w-3xl">Terima kasih untuk semua pelanggan yang. <br> memberi kami ulasan dan peringkat.</p>
        </div>
    </div>

    <div class="py-4 sm:py-3 ">
        <div class="container">
            <div class="mx-auto mt-3 flow-root max-w-2xl sm:mt-20 lg:mx-0 lg:max-w-none">
    <div class="sm:-mx-4 sm:grid sm:grid-cols-3 lg:grid-cols-3">
        @foreach ($ratings as $rating)
        <div class="pt-8 sm:px-4">
            <figure class="w-full rounded-2xl bg-murky-700 p-6 text-sm leading-6">
                <h3 class="font-bold">{{ $rating->kategori_nama }}</h3>
                <blockquote class="mt-3 italic text-white"><p>“{{ $rating->comment }}”</p></blockquote>
                <figcaption class="mt-3 flex w-full flex-col items-center justify-center gap-x-4">
                    <div class="flex w-full items-center justify-between">
                        @php
                            $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                            $usernameLength = strlen($username);
                            
                            if ($usernameLength <= 5) {
                                $sensorLength = 2;
                                $start = floor(($usernameLength - $sensorLength) / 2);
                                $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                            } else {
                                $sensorLength = 4;
                                $start = floor(($usernameLength - $sensorLength) / 2);
                                $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                            }
                        @endphp
                        <div class="text-murky-300">{{ $censoredUsername }}</div>
                        <div class="flex items-center">
                            <div class="star-rating">
                                <td>
                                    @for($i=1; $i<=5; $i++)
                                        @if($i <= $rating->bintang)
                                            <i class="fas fa-star text-yellow-400"></i>
                                        @else
                                            <i class="far fa-star text-yellow-400"></i>
                                        @endif
                                    @endfor
                                </td>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 flex w-full items-center justify-between">
                        <div class="text-xs text-murky-300">{{ $rating->layanan }}</div>
                        <div class="flex items-center text-xs">{{$rating->created_at}}</div>
                    </div>
                </figcaption>
            </figure>
        </div>
        @endforeach
    </div>
</div>

            <div class="flex items-center justify-center pt-12">
               
            </div>
        </div>
    </div>




@include('../footer')
@endsection