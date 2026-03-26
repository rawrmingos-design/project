@extends('template.template')

@section('custom_style')


<style>
    .disabled {
        pointer-events: none;
        opacity: 0.5;
        color: gray;
    }

    .scroll-container {
        display: flex;
        overflow-x: auto;
        padding: 1rem 0;
        white-space: nowrap;
        scrollbar-width: thin;
    }

    .scroll-container::-webkit-scrollbar {
        height: 8px;
    }

    .scroll-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .scroll-container::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .button-3d {
        background: linear-gradient(145deg, var(--warna_3), var(--warna_1));
        border-radius: 1rem;
        color: #f2efef;
        font-weight: bold;
        margin: 0 5px;
        padding: 5px 20px;
        cursor: pointer;
    }

    .button-3d:active {
        transform: translateY(4px);
    }

    .rate {
        background-color: var(--warna_1);
        box-shadow: 0 0 6px 1px var(--warna_1);
        color: #fffbfb;
        padding: 0 .5em;
        font-weight: 800;
        text-align: center;
        border-radius: 1em;
    }

    @keyframes gradientAnimation {
        0% {
            background-position: 0% 50%;
        }
        40% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    .asdasdwe_2353_Sdfsdccxxx_Xx3979b {
        position: absolute;
        top: -4px;
        right: 0;
        background-color: #FF3956;
        color: #1f1f1f;
        background: linear-gradient(45deg, #92918f, #b6b6b6, #e4e4e4, #8c8c8c, #f8f8f8, #b3b3b3, #636363, rgba(255, 255, 255, 0.9) 80%, #dcdbd6, #b6b6b5, #9e9e9e, #d0d0d0, #c8c8c8, #a3a3a2, #bebebe);
        background-size: 700% 200%;
        animation: gradientAnimation 2.5s linear infinite;
    }

    .bg-black\/80 {
        background-color: rgba(0, 0, 0, .8);
    }

    .shadow-xl {
        --tw-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1);
        --tw-shadow-colored: 0 20px 25px -5px var(--tw-shadow-color), 0 8px 10px -6px var(--tw-shadow-color);
        box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
    }

    .opacity-100 {
        opacity: 1;
    }

    .bg-popover {
        background-color: var(--warna_4);
    }

    .relative {
        position: relative;
    }

    .font-medium {
        font-weight: 500;
    }

    .text-xs {
        font-size: .75rem;
        line-height: 1rem;
    }

    .truncate,
    .whitespace-nowrap {
        white-space: nowrap;
    }

    .justify-center {
        justify-content: center;
    }

    .items-center {
        align-items: center;
    }

    .h-8 {
        height: 2rem;
    }

    .bg-destructive {
        background-color: #dc2626;
    }

    /* Highlight required account inputs so users notice they must be filled */
    #section-input input:not([type="hidden"]),
    #section-input select,
    #section-input textarea {
        border: 2px solid rgba(255, 255, 255, 0.9) !important;
        box-shadow: none !important;
        outline: none !important;
    }

    #section-input input:not([type="hidden"]):focus,
    #section-input select:focus,
    #section-input textarea:focus {
        border-color: #ffffff !important;
        box-shadow: none !important;
        outline: none !important;
    }

    input[name="voucher"],
    input[name="whatsapp"] {
        border: 2px solid rgba(255, 255, 255, 0.9) !important;
        box-shadow: none !important;
        outline: none !important;
    }

    input[name="voucher"]:focus,
    input[name="whatsapp"]:focus {
        border-color: #ffffff !important;
        box-shadow: none !important;
        outline: none !important;
    }
</style>

@endsection


@section('content')

@include('../navbar')

      
    <div class="load"></div>
    <div class="relative h-56 w-full bg-murky-800 lg:h-[340px]">
    <img src="{{ asset($kategori->banner) }}" class="object-cover object-center"
        style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;"/>
      <ul class="circles">
     
     <section class="relative flex items-center overflow-hidden bg-secondary/50 px-4 py-m lg:min-h-[521.96px]">
   @php
    $positions = [
        ['left' => 1130, 'delay' => 0.686975, 'duration' => 8],
        ['left' => -350, 'delay' => 0.670151, 'duration' => 8],
        ['left' => 563, 'delay' => 0.632454, 'duration' => 9],
        ['left' => -969, 'delay' => 0.524996, 'duration' => 5],
        ['left' => -1153, 'delay' => 0.460272, 'duration' => 8],
        ['left' => -560, 'delay' => 0.223791, 'duration' => 6],
        ['left' => -1287, 'delay' => 0.406558, 'duration' => 4],
        ['left' => 211, 'delay' => 0.475533, 'duration' => 6],
        ['left' => -63, 'delay' => 0.394929, 'duration' => 5],
        ['left' => -112, 'delay' => 0.78249, 'duration' => 2],
        ['left' => 946, 'delay' => 0.353787, 'duration' => 5],
        ['left' => 275, 'delay' => 0.309607, 'duration' => 5],
        ['left' => 1216, 'delay' => 0.35162, 'duration' => 8],
        ['left' => -210, 'delay' => 0.413144, 'duration' => 7],
        ['left' => -842, 'delay' => 0.395388, 'duration' => 6],
        ['left' => -323, 'delay' => 0.582248, 'duration' => 4],
        ['left' => 278, 'delay' => 0.710367, 'duration' => 4],
        ['left' => -736, 'delay' => 0.564896, 'duration' => 6],
        ['left' => -800, 'delay' => 0.206357, 'duration' => 7],
        ['left' => -1118, 'delay' => 0.628613, 'duration' => 9],
        ['left' => 1361, 'delay' => 0.529785, 'duration' => 7],
        ['left' => -11, 'delay' => 0.64863, 'duration' => 6],
        ['left' => -678, 'delay' => 0.701722, 'duration' => 3],
        ['left' => -170, 'delay' => 0.366231, 'duration' => 5],
        ['left' => 946, 'delay' => 0.521904, 'duration' => 7],
        ['left' => 1364, 'delay' => 0.484818, 'duration' => 9],
        ['left' => 943, 'delay' => 0.502043, 'duration' => 3],
        ['left' => 1296, 'delay' => 0.577243, 'duration' => 7],
        ['left' => 1273, 'delay' => 0.273317, 'duration' => 5],
        ['left' => -1306, 'delay' => 0.556245, 'duration' => 7],
        ['left' => -360, 'delay' => 0.344508, 'duration' => 5],
        ['left' => 306, 'delay' => 0.332693, 'duration' => 6],
        ['left' => 312, 'delay' => 0.250245, 'duration' => 9],
        ['left' => 649, 'delay' => 0.607517, 'duration' => 2],
        ['left' => 13, 'delay' => 0.379304, 'duration' => 6],
        ['left' => 1269, 'delay' => 0.586079, 'duration' => 5],
        ['left' => -798, 'delay' => 0.675148, 'duration' => 4],
        ['left' => 1199, 'delay' => 0.515393, 'duration' => 6],
        ['left' => 304, 'delay' => 0.799655, 'duration' => 8],
            ];
        @endphp
        
        @for ($i = 0; $i < count($positions); $i++)
            @php
                $left = $positions[$i]['left'];
                $delay = $positions[$i]['delay'];
                $duration = $positions[$i]['duration'];
            @endphp
            <span class="absolute left-1/2 top-1/2 h-1 w-1 rotate-[215deg] animate-meteor-effect rounded-[9999px] bg-white shadow-[0_0_0_1px_#ffffff10] before:absolute before:top-1/2 before:h-[1px] before:w-[80px] before:-translate-y-[0%] before:transform before:bg-gradient-to-r before:from-white before:to-transparent before:content-['']"
                style="top: -20px; left: {{ $left }}px; animation-delay: {{ $delay }}s; animation-duration: {{ $duration }}s;"></span>
        @endfor

               </section>
 </ul>
    <div class="container relative top-10 z-20 flex h-full w-full flex-col justify-end gap-4 py-4 md:top-[5rem] lg:py-8">
                <article class="flex items-start gap-4">
                    <div class="product-thumbnail-container"><img src="{{ asset($kategori->thumbnail) }}" width="100" height="100" class="z-20 -mb-14 aspect-square w-32 rounded-2xl object-cover shadow-2xl md:-mb-20 md:w-60" style="color: transparent;" alt="{{ $kategori->nama }}"
                        /></div>
                </article>
            </div>
</div>
   
    <div class="bg-title-product dynamic-bg min-h-[120px] shadow-2xl md:min-h-[140px]">
            <div class="container">
                <div class="ml-[8.5rem] pt-4 md:ml-[15.5rem] md:pt-5">
                    <div>
                        <h2 class="truncate text-base font-semibold text-white md:text-2xl">{{ $kategori->nama }}</h2>
                        <p class="truncate text-xs text-white md:text-base">{{ $kategori->sub_nama }}</p>
                    </div>
                    <div class="flex items-center space-x-8 pt-4">
                        <div class="inline-flex items-center space-x-2 text-xs md:text-sm">
                            <div class="rounded-full bg-emerald-500 p-1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-3.5 w-3.5 text-white"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"></path></svg></div>
                            <span
                                class="font-semibold">Terverifikasi</span>
                        </div>
                        <div class="inline-flex items-center space-x-2 text-xs md:text-sm">
                            <div class="rounded-full bg-murky-500 p-1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-3.5 w-3.5 text-yellow-300"><path d="M10 1a6 6 0 00-3.815 10.631C7.237 12.5 8 13.443 8 14.456v.644a.75.75 0 00.572.729 6.016 6.016 0 002.856 0A.75.75 0 0012 15.1v-.644c0-1.013.762-1.957 1.815-2.825A6 6 0 0010 1zM8.863 17.414a.75.75 0 00-.226 1.483 9.066 9.066 0 002.726 0 .75.75 0 00-.226-1.483 7.553 7.553 0 01-2.274 0z" ></path></svg></div>
                            <span
                                class="font-semibold">Instant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <div class="container grid grid-cols-3 gap-8 pb-8 pt-8">
      <div class="col-span-3 md:col-span-1">
        <div class="sticky top-24 flex flex-col space-y-8">
          <div class="rounded-xl bg-murky-800 shadow-2xl">
            <div class="prose prose-sm px-4 py-2 pb-8 text-xs text-white sm:px-6">
              <div>

                        {!! htmlspecialchars_decode($kategori->deskripsi_game) !!}
              </div>
              <div class="mt-2 flex flex-col border-t border-dashed  text-card-foreground">
            <p> Note:&nbsp;<br> Jika Mengalami Kendala Silahkan hub CS IG <a href="{{ !$config ? '' : $config->url_ig }}" target="_blank">Instagram</a> dan CS WhatsApp <a href="{{ !$config ? '' : $config->url_wa }}" target="_blank">WhatsApp</a></p>
              </div>

            </div>
          </div>
          
          
            <div class="mt-4 hidden rounded-xl bg-murky-800 shadow-2xl md:block">
    <div class="flex border-b border-murky-600">
                            <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                                <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" ></path></svg></div>
                                <h3
                                    class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Ulasan</h3>
                            </div>
                        </div>
    <div class="flow-root p-6">

        @php
        $ratings = DB::table('ratings')->where('kategori_id', $kategori->id)->get();
        $totalReviews = $ratings->count();
        $totalStars = $ratings->sum('bintang');
        $positiveReviews = $ratings->where('bintang', '>=', 4)->count();
        $averageRating = $totalReviews > 0 ? $totalStars / $totalReviews : 0;
        $satisfactionPercentage = $totalReviews > 0 ? ($positiveReviews / $totalReviews) * 100 : 0;
        @endphp

        <div class="flex flex-col overflow-hidden ">
            <div class="mx-6 flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-8 w-8 flex-shrink-0 text-yellow-400">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                </svg>
                <div><span class="text-5xl text-besar">{{ number_format($averageRating, 1) }}</span> / 5.0</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="mx-6 flex items-center justify-center text-xs font-bold">{{ number_format($satisfactionPercentage, 0) }}% pembeli merasa puas dengan produk ini.</div>
                <div class="mx-6 flex items-center justify-center gap-2 text-xs">Dari {{ $totalReviews }} Ulasan.</div>
            </div>
        </div>

        @php
        $totalRatings = $ratings->groupBy('bintang')->map->count();
        @endphp

        <style>
            .progress {
                transition: width 0.5s ease-in-out;
            }
        </style>

        <div class="flex flex-col overflow-hidden pt-6">
            @foreach(range(5, 1) as $rating)
            @php
            $count = $totalRatings->get($rating, 0);
            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
            @endphp
             <ul class="rating-list" style="list-style-type: none; padding-left: 0;">
                                <li class="rating-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                    <div class="rating-value" style="width: 30px; text-align: right; margin-right: 10px;">
                                        {{ $rating }}
                                    </div>
                                    <div class="star-rating" style="display: flex; align-items: center; margin-right: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="height: 20px; width: 20px; color: #ffc107;">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="bar" style="flex-grow: 1; height: 10px; background-color: #ddd; border-radius: 5px; overflow: hidden;">
                                        <div class="progress" style="height: 100%; background-color: #ffc107; border-radius: 5px; width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="count" style="width: 50px; margin-left: 0px; text-align: right;">{{ $count }}</div>
                                </li>
                            </ul>
            @endforeach
        </div>

        @if($ratings->isEmpty())
        <div class="py-4">
            <div class="rounded-md border-l-4 border-yellow-400 bg-yellow-100 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-yellow-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                        </svg>
                    </div>
                    <div class="ml-3"><p class="text-sm text-yellow-700">Belum ada ulasan dan penilaian.</p></div>
                </div>
            </div>
        </div>
        @else
        <div class="mt-6">
            <p class="text-sm text-secondary-foreground">Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya tentang pengalamanmu.</p></div>
            <hr>
        <div class="flow-root pt-5">
            <div class="-my-6 divide-y">
                @foreach($ratings->reverse()->take(5) as $rating)
                <div class="py-3">
                    <div class="flex items-center">
                        <div class="w-full">
                            <div class="flex items-start justify-between">
                                @php
                                $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                                if(!$username && isset($rating->no_pembeli)) {
                                    $username = $rating->no_pembeli;
                                }
                                $usernameLength = strlen($username);
                                $sensorLength = $usernameLength <= 5 ? 2 : 4;
                                $start = floor(($usernameLength - $sensorLength) / 2);
                                $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                                @endphp
                                <h4 class="mt-0.5 text-xs font-bold text-white">{{ $censoredUsername }}</h4>
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $i <= $rating->bintang ? 'currentColor' : 'white' }}" aria-hidden="true" class="text-yellow-400 h-4 w-4 flex-shrink-0">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                            <p class="sr-only">{{ $rating->bintang }} dari 5 bintang</p>
                        </div>
                    </div>
                    <div class="flex w-full justify-between pt-1 text-xxs">
                        <span>{{ $rating->layanan }}</span>
                        <span>{{ $rating->created_at }}</span>
                    </div>
                    <div class="text-murky-20 mt-1 space-y-6 text-xs italic">“{{ $rating->comment }}”</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <div class="flex justify-end pt-5 mt-5">
            <a
                class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent/75 hover:text-accent-foreground h-8 rounded-md px-4 bg-secondary/50 pr-3 flex items-center gap-2"
                type="button"
                href="/id/reviews"
                style="outline: none;"
            >
                <span>Lihat semua ulasan</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

            </div>

      </div>
     
        @if(in_array($kategori->tipe, ['joki', 'jokigendong', 'giftskin' , 'vilogml']))
        @if($kategori->tipe === 'joki')
            
      <ul class="col-span-3 flex flex-col space-y-8 md:col-span-2">
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-input">
                         <input type="hidden" id="nominal">
                    <input type="hidden" id="metode">
                    <input type="hidden" id="ktg_tipe" value="{{ $kategori->tipe }}">
                
                  
   <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">1</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Masukkan Data Akun Kamu</h3>
                    </div>
                </div>
              
                    @php
                        if($kategori->field_2 !== null){
                            $field2Values = explode(',', (string) ($kategori->field_2 ?? ''));
                            $selectValue = isset($field2Values[2]) ? trim($field2Values[2]) : null;
                        }
                        
                            $fieldSelectTitle = explode(',', (string) ($kategori->field_select_title ?? ''));
                            $fieldSelect = explode(',', (string) ($kategori->field_select ?? ''));
                            $field1Values = explode(',', (string) ($kategori->field_1 ?? ''));
                        @endphp
                   @if($kategori->field_2 !== null)
                             <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                @if($kategori->require_user_id ?? true)
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input 
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" 
                                            type="{{ $field1Values[2] }}" 
                                            id="user_id" name="user_id" 
                                            placeholder="{{ $field1Values[1] }}"/> 
                                    </div>
                                </div>
                                @endif
                                @if($selectValue == "select")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2"> {{ $field2Values[0] }}</label>
                                        <select class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" id="zone">
                                            <option value="">{{ $field2Values[1] }}</option>
                                            @foreach($fieldSelectTitle as $key => $fst)
                                                <option value="{{ $fieldSelect[$key] }}">{{ $fst }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($selectValue == "text" || $selectValue == "number" || $selectValue == "password")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2">{{ $field2Values[0] }}</label>
                                        <div class="flex flex-col items-start">
                                            <input
                                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                                type="{{ $field2Values[2] }}"
                                                name="zone_id" id="zone"
                                                placeholder="{{ $field2Values[1] }}"/>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                        @elseif(in_array($kategori->tipe,['joki']))
                       <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                        <div>
                            <label for="email_joki" class="block text-xs font-medium text-white pb-2">Email/No. Hp</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="email"
                                    id="email_joki"
                                    name="email_joki"
                                    placeholder="Ketikan Email/No. Hp"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="password_joki" class="block text-xs font-medium text-white pb-2">Password</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="password"
                                    id="password_joki"
                                    name="password_joki"
                                    placeholder="Ketikan Password"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="loginvia_joki" class="block text-xs font-medium text-white pb-2">Login Via</label>
                            <select
                                id="loginvia_joki"
                                name="loginvia_joki"
                                class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                required
                            >
                                <option value="" disabled selected>Login Via</option>
                                <option value="moonton">Moonton (Rekomendasi)</option>
                                <option value="vk">VK</option>
                                <option value="tiktok">Tiktok</option>
                                <option value="facebook">Facebook</option>
                            </select>
                        </div>
                        <div>
                            <label for="nickname_joki" class="block text-xs font-medium text-white pb-2">Nickname</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="text"
                                    id="nickname_joki"
                                    name="nickname_joki"
                                    placeholder="Ketikan Nickname"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="request_joki" class="block text-xs font-medium text-white pb-2">Request Hero</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="text"
                                    id="request_joki"
                                    name="request_joki"
                                    placeholder="Min Request 3 Hero (Diusahakan)"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="catatan_joki" class="block text-xs font-medium text-white pb-2">Catatan untuk Penjoki</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="text"
                                    id="catatan_joki"
                                    name="catatan_joki"
                                    placeholder="Catatan untuk Penjoki"
                                    required
                                />
                            </div>
                        </div>
                    </div>
    
                        
                        @else
                            @if($kategori->require_user_id ?? true)
                            <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                            type="{{ $field1Values[2] }}"
                                            id="user_id" name="user_id"
                                            placeholder="{{ $field1Values[1] }}"/> 
                                            <div id="nickname-display" class="text-xs text-green-500 mt-1 font-bold"></div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endif

					     <div class="px-4 pb-4 text-[10px] sm:px-6 sm:pb-6">
                            <div>
                            <p><em>@safeHtml($kategori->deskripsi_field)</em></p>
                        </div>
                        
                            </div>
                </div>
                    <!--end section input-->
     
            
                @if(in_array($kategori->tipe,['joki']))
<div class="popup-structureeee popup-slide flex min-h-full items-center justify-center p-4 text-center sm:p-0" id="popupdppp">
  <div class="fixed inset-0 z-10 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
      <div class="popup-content relative w-full transform overflow-hidden rounded-lg bg-murky-900 text-left shadow-xl transition-all sm:my-8 sm:max-w-3xl !rounded-2xl opacity-100 sm:scale-100" id="headlessui-dialog-panel-weekly-diamond-pass" data-headlessui-state="open">
        <div class="absolute right-0 top-0 block pr-4 pt-4"></div>
        <div class="w-full pb-4 flex flex-col items-center justify-center">
          <h2 class="max-w-xl pt-1 text-center text-sm font-semibold text-white mt-4">Informasi Sebelum Order Jasa Joki MLBB</h2>
          <div class="prose prose-sm px-2 text-xs text-foreground">
            <div>
              <p><strong>
                  <em class="text-white">Mohon luangkan waktu untuk membaca catatan Informasi sebelum melakukan pemesanan.<br /><br /></em>
              </strong>Waktu Pengecekan Orderan :<br />Orderan yang sudah dibayarkan akan kami cek setiap hari mulai pukul 10.00 - 22.00 WIB.<br />Untuk orderan yang melewati batas waktu pengecekan, akan kami proses pada jam kerja di hari berikutnya.<br /><br />Berikut Syarat Dan Ketentuan Sebelum Order Jasa Joki :</p>
              <p class="selectable-text copyable-text iq0m558w g0rxnol2" dir="ltr">
                <span class="selectable-text copyable-text">1. Data Akun : Lengkapi data dengan benar, termasuk kapitalisasi huruf.<br /></span>2. Pilihan Hero : Minimal tiga pilihan hero, sebagai alternatif jika hero sedang di pick/ban.<br />
                <span class="selectable-text copyable-text">3. Verifikasi Akun : Nonaktifkan Untuk Mempermudah Login.<br /></span>
                <span class="selectable-text copyable-text">4. Tipe Akun : Utamakan Akun yang dijoki adalah akun utama, bukan akun beli atau bekas GB untuk menghindari BAN.<br /></span>
                <span class="selectable-text copyable-text">5. Login Tanpa izin : Berakibat pembatalan joki dan hangusnya pembayaran.<br /></span>
                <span class="selectable-text copyable-text">6. Kesabaran: Tunggu sesuai estimasi dan jangan spam chat admin.<br /></span>
                <span class="selectable-text copyable-text">7. Masalah Login : Admin/Bot akan menghubungi jika ada kendala.<br /></span>
                <span class="selectable-text copyable-text">8. Keterlambatan Proses : Hubungi kami jika belum diproses dalam 1-3 jam.<br /></span>
                <span class="selectable-text copyable-text">9. Setelah Joki Selesai : Tetapi belum menerima laporan dari Admin/BOT, jangan di login terlebih dahulu karena ada benefit bonus.<br /></span>
                <span class="selectable-text copyable-text">10. Tanggung Jawab Pasca-Joki : Tanggung jawab atas akun berakhir setelah joki selesai.<br /></span>
                <span class="selectable-text copyable-text">11. Konfirmasi Selesai : Akan dihubungi oleh Admin/BOT dan Customer Bisa Cek Malalui (Cek Transaksi)<br /><br /></span>
                Jika Butuh Bantuan Harap Hubungi Admin {{ $config->judul_web }}<br />Terimakasih
              </p>
            </div>
          </div>
          <div class="flex justify-center bg-secondary px-4 py-2 rounded-xl">
            <button class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-8" type="button" name="popup" id="closePopupButton">
              OK, Saya Mengerti
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let popup = document.getElementById("popupdppp");
    let closeButton = document.getElementById("closePopupButton");

    if (popup) {
        popup.classList.add("show");
    }

    if (closeButton) {
        closeButton.addEventListener("click", function() {
            if (popup) {
                popup.classList.remove("show");
            }
        });
    }
});
</script>
<style>
    .popup-structureeee {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(128, 128, 128, 0.7); 
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.popup-content {
  background: #212121;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.5);
  transform: translateY(100%);
  transition: transform 0.3s ease;
}

.popup-structureeee.show .popup-content {
  transform: translateY(0);
}

.popup-structureeee.show {
  display: flex;
  opacity: 1;
}

</style>
@endif

<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('skeleton-loader').style.display = 'none';
            document.getElementById('itemList').classList.remove('hidden');
        }, 1500);
    });
</script>
              <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-nominal">
                 <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 2 </div>
                   <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Nominal </h3>
                 </div>
                 <div id="skeleton-loader" class="skeleton-loader grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 px-4 mt-4 py-4">
                @for ($i = 0; $i < 12; $i++)
                    <div class="ph-item melpaaaaaa">  
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            
                 <style>
        .scroll-container {
            display: flex;
            overflow-x: auto;
            padding: 1rem 0;
            white-space: nowrap;
            scrollbar-width: thin;
        }

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

       .button-3d {
    background: linear-gradient(145deg, var(--warna_2), var(--warna_3));
    border-radius: 12px;
    color: #f2efef;
    font-weight: bold;
    margin: 0 5px;
    padding: 7px 20px;
    transition: transform 0.3s;
    display: inline-block;
    cursor: pointer;
}

        .button-3d:active {
            transform: translateY(4px);
            
        }
        
       .rate {
    background-color: var(--warna_1);
    box-shadow: 0 0 6px 1px var(--warna_1);
    color: #fffbfb;
    padding: 0 .5em;
    font-weight: 800;
    text-align: center;
    border-radius: 1em;
}
@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    40% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.asdasdwe_2353_Sdfsdccxxx_Xx3979b {
    position: absolute;
    top: -4px;
    right: 0;
    background-color: #FF3956;
    color: #1f1f1f;
    background: linear-gradient(45deg, #92918f, #b6b6b6, #e4e4e4, #8c8c8c, #f8f8f8, #b3b3b3, #636363, rgba(255, 255, 255, 0.9) 80%, #dcdbd6, #b6b6b5, #9e9e9e, #d0d0d0, #c8c8c8, #a3a3a2, #bebebe);
    background-size: 700% 200%;
    animation: gradientAnimation 2.5s linear infinite;
}

    </style>
              <div id="paketList" x-data="{ selectedPaket: 'all', selectedProduct: '' }" class="p-4 sm:p-6">
                  
                  <h3 class="font-semibold mt-4">📦 Pilih Paket</h3>
        <div class="scroll-container">
            <button @click="selectedPaket = 'all'" class="button-3d">🎮 Semua</button>
            @foreach($pakets as $paket)
            <button @click="selectedPaket = {{ $loop->index }}" class="button-3d">{{ $paket['nama'] }}</button>
            @endforeach
        </div>
        <div id="itemList" class="flex flex-col space-y-4 sm:p-1">
            <div x-show="selectedPaket === 'all'">
                @foreach($pakets as $paket) 
                <section">
                    <h3 class="font-semibold  mt-4">{{ $paket['nama'] }}</h3>
                    <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                        <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                            @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                            <div x-bind:class="{ 'ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                                <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                                @php
                                $currentDateTime = now();
                            @endphp
                            
                            <span class="flex flex-1">
                                <span class="flex flex-col justify-between">
                                    <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
                                    <div>
                                        @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                                            <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </span>
                            
                                @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                    <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
                                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                        <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                                            {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
                                        </div>
                                    </div>
                                @endif
                            </span>

                                @if($nom['product_logo'])
                                <div class="flex aspect-square w-8 items-center">
                                    <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                                </div>
                                @endif
                                <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endforeach
            </div>
            @foreach($pakets as $paket) 
            <section x-show="selectedPaket === {{ $loop->index }}" x-transition>
                <h3 class="font-semibold">{{ $paket['nama'] }}</h3>
                <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                    <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                        @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                        <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                            <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                          @php
    $currentDateTime = now();
@endphp

<span class="flex flex-1">
    <span class="flex flex-col justify-between">
        <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
        <div>
            @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @else
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @endif
        </div>
    </span>

    @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
        <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
            <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
            </div>
        </div>
    @endif
</span>

                            @if($nom['product_logo'])
                            <div class="flex aspect-square w-8 items-center">
                                <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                            </div>
                            @endif
                            <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endforeach
        </div>
    </div>
               </div>
               
               
          

                      @if(in_array($kategori->tipe,['joki']))
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="quantity">
  
   <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 3 </div>
                <h3 class="flex w-full items-center justify-between text-sm/6 rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Jumlah Pembelian </h3>
              </div>
              
  <div class="p-4 sm:px-6 sm:pb-6">
    <div class="flex items-center gap-x-4">
      <div class="flex-1">
        <div class="flex flex-col items-start">
         <input
                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent disabled:cursor-not-allowed disabled:opacity-75"
                type="number" name="qty" id="qty" value="1" min="1" max="30" disabled required
                oninput="validateQtyInput(this)"
            />
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" id="incrementBtn" class="flex items-center justify-center rounded-md bg-murky-200 p-1.5 text-murky-800 disabled:cursor-not-allowed disabled:opacity-75">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
          </svg>
        </button>
        <button type="button" id="decrementBtn" class="flex items-center justify-center rounded-md bg-murky-200 p-1.5 text-murky-800 disabled:cursor-not-allowed disabled:opacity-75">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>
                @endif
           
            <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-payment-channel">
              <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 4 </div>
                <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Metode Pembayaran </h3>
              </div>
              
                 <div id="skeleton-loaderr" class="skeleton-loader grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 px-4 mt-4 py-4">
                @for ($i = 0; $i < 4; $i++)
                    <div class="ph-item melpaaaaaa">
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
              <dl id="paymentList" class="flex w-full flex-col space-y-4 p-4 sm:p-6 hidden" x-data="{ selected: null, paymentSelected: '' }">
                  
      <!--saldo1-->
            @if(Auth::check())
                @foreach($pay_method as $p) 
                    @if($p->tipe == 'SALDO')
                        <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" class="relative flex cursor-pointer method-list rounded-xl border border-transparent bg-murky-200 p-3 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" role="radio" aria-checked="false" method-id="{{$p->code}}" name="paymentMethod" @click="paymentSelected = '{{$p->code}}'">
                            <div class="flex items-center gap-2 max-w-xs">
                                <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                                <label for="method_{{$p->id}}"></label>
                                <img src="{{ ENV('COIN_STORE') }}" class="object-cover rounded-full" alt="Coin" width="45" height="40" />
                                <div>
                                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{ $config->judul_web }} COIN</span>
                                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{$p->code}}">Rp 0</p>
                                </div>
                            </div>
                            <div class="max-w-xs">
                                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
                                </div>
                            </div>
                            <div class="flex aspect-square w-8 items-center">
                                <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                    <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                                </div>
                            </div>
                        </div>
                    @endif 
                @endforeach
            @else
                <div class="relative flex cursor-pointer rounded-xl border border-transparent bg-murky-200 p-3 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out">
                    <div class="flex items-center gap-2 max-w-xs">
                        <img src="{{ ENV('COIN_STORE') }}" class="rounded-full" alt="Coin" width="45" height="40" />
                        @foreach($pay_method as $p) 
                            @if($p->tipe == 'SALDO')
                                <div>
                                    <span class="block  text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{ $config->judul_web }} COIN</span>
                                    <p class="block text-xxs text-murky-800 sm:text-xs" id="{{$p->code}}">Rp 0</p>
                                </div>
                            @endif 
                        @endforeach
                    </div>
                    <div class="max-w-xs">
                        <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
                            <!--<span class="text-xs text-rose-800">Saldo Account tidak mencukupi</span>-->
                        </div>
                    </div>
                    <div class="flex aspect-square w-8 items-center">
                        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                            <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                            <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                        </div>
                    </div>
                </div>
            @endif
                
                  <!--QRIS-->
                @foreach($pay_method as $p) 
                    @if($p->isType('qris'))
                        <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" class="relative flex cursor-pointer method-list rounded-xl border border-transparent bg-murky-200 p-4 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" role="radio" aria-checked="false" method-id="{{$p->code}}" name="paymentMethod" @click="paymentSelected = '{{$p->code}}'">
                            <div class="flex items-center gap-2 max-w-xs">
                                <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                                <label for="method_{{$p->id}}"></label>
                                <img src="{{ $p->image_url }}" alt="qris" width="55" height="40" />
                                <div>
                                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{$p->name}}</span>
                                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{$p->code}}">Rp 0</p>
                                </div>
                            </div>
                            <div class="max-w-xs">
                                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
                                </div>
                            </div>
                            <div class="flex aspect-square w-8 items-center">
                                <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                    <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                                </div>
                            </div>
                        </div>
                    @endif 
                @endforeach
                
                <!--end QRIS-->
                
                
                <!-- E-Wallet -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-1" type="button" @click="selected !== 3 ? selected = 3 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-1">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>E-Wallet</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 3 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700 " x-ref="container1" x-bind:style="selected == 3 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out " id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{ $p->image_url }}" x-bind:class="{ 'grayscale-0': paymentSelected === '{{$p->code}}', 'grayscale': paymentSelected !== '{{$p->code}}' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 3 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 3 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{ $p->image_url }}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                
              
                <!-- Virtual Account -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-2" type="button" @click="selected !== 5 ? selected = 5 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-2">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Virtual Account</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 5 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container2" x-bind:style="selected == 5 ? 'max-height: ' + $refs.container2.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-2">
                        <div id="radiogroup-2" role="radiogroup" aria-labelledby="label-2">
                          <div id="virtualAccountList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo5" x-bind:style="selected == 5 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 5 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                <!-- Convenience Store -->
                  
                 <!-- Convenience Store -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-3" type="button" @click="selected !== 4 ? selected = 4 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-3">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Convenience Store</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 4 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container3" x-bind:style="selected == 4 ? 'max-height: ' + $refs.container3.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-3">
                        <div id="radiogroup-3" role="radiogroup" aria-labelledby="label-3">
                          <div id="convenienceStoreList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh" id="">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo4" x-bind:style="selected == 4 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 4 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{ $p->image_url }}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
              </dl>
            </div>

                 <div class="rounded-xl bg-murky-800 shadow-2xl" id="promooo">
                 
                     <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 5 </div>
                <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Kode Promo </h3>
              </div>
                  <div class="p-4 sm:px-6 sm:pb-6">
                    <label for="voucher" class="block text-xs font-medium text-white pb-2">Kode Promo</label>
                    <div class="flex items-center space-x-2">
                      <div class="grow">
                        <div class="flex flex-col items-start">
                          <input class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white" type="text" id="voucher" name="voucher" placeholder="Masukkan Kode Promo Anda" required/>
                        </div>
                      </div>
                      <button type="button" id="btn-check" class="flex items-center justify-center rounded-md bg-primary-5400 py-2 px-4 text-xs font-semibold text-white hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-75"> Gunakan </button>
                    </div>
                    <div class="pt-2 text-xs text-red-500"></div>


                  </div>
                </div>
                
                
                
     <div class="rounded-xl bg-murky-800 shadow-2xl jumpToWhatsApp" id="whatsappp">
 
        <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 6 </div>
                   <h3 class="flex w-full items-center justify-between rounded-tr-xl text-sm/6 bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> No. WhatsApp </h3>
                 </div>
    <div class="p-4 sm:px-6">
        <label for="nomor" class="block text-xs font-medium text-white pb-2">No. WhatsApp</label>
        <div class="PhoneInput">
          
            <input
            type="number"
            id="nomor"
            autocomplete="off"
            name="whatsapp"
            placeholder="Contoh 08213456789"
            class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
            value=""
            id="phoneNumberInput"
        />

        </div>
        <span class="text-xxs italic">**Nomor ini akan dihubungi jika terjadi masalah</span>
        
    <p class="flex items-center gap-2 rounded-md bg-primary-5400 px-4 py-2.5 text-xs/6">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M12 16v-4"></path>
        <path d="M12 8h.01"></path>
    </svg>
    <span>Bukti transaksi akan kami kirim ke whatsapp yang kamu isi di atas.</span>
</p>
    </div>

</div>

                
                
                      <div class="inset-x-0 bottom-0 z-10  !mt-0 shad sticky bottom-0 rounded-t-lg pb-4 flex flex-col gap-4 bg-background">
                  <div class=" space-y-0">
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm  rounded-lg md:hidden initial-element" style="display: flex;">
                      <div class="flex w-full flex-col space-y-0">
                        <div class="rounded-md p-4">
                                 <div class="text-center">Belum ada item produk yang dipilih.</div>
                        </div>
                      </div>
                    </div>
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm text-secondary-foreground md:hidden selected-element " style="display: none;">
                      <div class="mb-1 aspect-square timmel-5">
                        <img alt="icon" sizes="100vw" src="{{ asset($kategori->thumbnail) }}" width="80" height="100" decoding="async" data-nimg="1" class="aspect-square timmel-5 rounded-lg object-cover" loading="lazy" style="color: transparent">
                      </div>
                      <div class="flex w-full flex-col space-y-1 ml-3">
                          
                        <div class="text-xs font-semibold cana select glowing-text">{{ $kategori->nama }}</div>
                        <div class="flex items-center gap-2 pt-0.5 font-semibold">
                            
                        <p class="text-xs font-semibold text-warning text-amber-300 selected-order"></p><span>-</span>
                            <div class="text-xs  select text-white" id="pesan"></div></div>
                        
                        <p class="text-xxs italic text-murky-300">**Waktu proses instan</p>
                        <div class="flex w-full items-center">
                          <p class="text-xs italic select"></p>
                        </div>
                      </div>
                    </div>
                    
                      <div class="mt-4"></div>
                    <div class="relative">
                      <button class="inline-flex items-center justify-center rounded-md bg-primary-5400 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-75 btn-order relative flex w-full gap-2 overflow-hidden" type="button" id="order-check">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-4 w-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <span>Pesan Sekarang!</span>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="mt-4 block rounded-xl bg-murky-800 shadow-2xl md:hidden">
                    <div class="flex border-b border-murky-600">
                        <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b bg-primary-500  to-primary-600 px-3 py-2 text-xl font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4">
                                <path
                                    fill-rule="evenodd"
                                    d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                         <h3
            class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">
            Ulasan</h3>
                    </div>
                    
                     <div class="flow-root p-6">
                      
                        @php
                        $ratings = DB::table('ratings')->where('kategori_id', $kategori->id)->get();
                    
                        $totalStars = 0;
                        $totalReviews = $ratings->count();
                        $positiveReviews = 0;
                    
                        foreach ($ratings as $rating) {
                            $totalStars += $rating->bintang;
                            if ($rating->bintang >= 4) {
                                $positiveReviews++;
                            }
                        }
                    
                        if ($totalReviews > 0) {
                            $averageRating = $totalStars / $totalReviews;
                            $satisfactionPercentage = ($positiveReviews / $totalReviews) * 100;
                        } else {
                            $averageRating = 0; 
                            $satisfactionPercentage = 0;
                        }
                        @endphp
                    
                        <div class="flex flex-col  overflow-hidden ">
                            <div class="mx-6 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-8 w-8 flex-shrink-0 text-yellow-400">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                </svg>
                                <div><span class="text-5xl text-besar">{{ number_format($averageRating, 1) }}</span> <span> / </span><span>5.0</span></div>
                            </div>
                            <div class="flex flex-col gap-1">
                               
                        <div class="mx-6 flex items-center justify-center text-xs font-bold">{{ number_format($satisfactionPercentage, 0) }}% pembeli merasa puas dengan produk ini.</div>
                        <div class="mx-6 flex items-center justify-center gap-2 text-xs">Dari {{ $totalReviews }} Ulasan.</div>
                            </div>
                        </div>
                        @php
                        $totalRatings = [
                            '5' => $ratings->where('bintang', 5)->count(),
                            '4' => $ratings->where('bintang', 4)->count(),
                            '3' => $ratings->where('bintang', 3)->count(),
                            '2' => $ratings->where('bintang', 2)->count(),
                            '1' => $ratings->where('bintang', 1)->count(),
                        ];
                        @endphp
                    
                    
                        <div class="flex flex-col  overflow-hidden pt-6">
                            @foreach($totalRatings as $rating => $count)
                            @php
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                            @endphp
                            <ul class="rating-list" style="list-style-type: none; padding-left: 0;">
                                <li class="rating-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                    <div class="rating-value" style="width: 30px; text-align: right; margin-right: 10px;">
                                        {{ $rating }}
                                    </div>
                                    <div class="star-rating" style="display: flex; align-items: center; margin-right: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="height: 20px; width: 20px; color: #ffc107;">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="bar" style="flex-grow: 1; height: 10px; background-color: #ddd; border-radius: 5px; overflow: hidden;">
                                        <div class="progress" style="height: 100%; background-color: #ffc107; border-radius: 5px; width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="count" style="width: 50px; margin-left: 0px; text-align: right;">{{ $count }}</div>
                                </li>
                            </ul>

                            @endforeach
                        </div>
                    
                        @if($ratings->isEmpty())
                        <div class="py-4">
                            <div class="rounded-md border-l-4 border-yellow-400 bg-yellow-100 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-yellow-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3"><p class="text-sm text-yellow-700">Belum ada ulasan dan penilaian.</p></div>
                                </div>
                            </div>
                        </div>
                        @else
                       
                <div class="mt-6"><p class="text-sm text-secondary-foreground">Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya tentang pengalamanmu.</p></div>
                         <hr>
                <div class="flow-root pt-5">
                    <div class="-my-6 divide-y">
                         @foreach($ratings->reverse()->take(5) as $rating)
                        <div class="py-3">
                            <div class="flex items-center">
                                <div class="w-full">
                                    <div class="flex items-start justify-between">
                                        @php
                                        $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                                        if(!$username && isset($rating->no_pembeli)) {
                                            $username = $rating->no_pembeli;
                                        }
                                        $usernameLength = strlen($username);
                                        $sensorLength = $usernameLength <= 5 ? 2 : 4;
                                        $start = floor(($usernameLength - $sensorLength) / 2);
                                        $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                                        @endphp
                                        <h4 class="mt-0.5 text-xs font-bold text-white">{{ $censoredUsername }}</h4>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $i <= $rating->bintang ? 'currentColor' : 'white' }}" aria-hidden="true" class="text-yellow-400 h-4 w-4 flex-shrink-0">
                                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="sr-only">{{ $rating->bintang }} dari 5 bintang</p>
                                </div>
                            </div>
                            <div class="flex w-full justify-between pt-1 text-xxs">
                                <span>{{ $rating->layanan }}</span>
                                <span>{{ $rating->created_at }}</span>
                            </div>
                            <div class="text-murky-20 mt-1 space-y-6 text-xs italic">“{{ $rating->comment }}”</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
               <div class="flex justify-end pt-5 mt-5">
                   
    <a
        class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent/75 hover:text-accent-foreground h-8 rounded-md px-4 bg-secondary/50 pr-3 flex items-center gap-2"
        type="button"
        href="/id/reviews"
        style="outline: none;"
    >
        <span>Lihat semua ulasan</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4">
            <path d="M5 12h14"></path>
            <path d="m12 5 7 7-7 7"></path>
        </svg>
    </a>
</div>

                    </div>
                </div>
            </ul>
            
        @elseif($kategori->tipe === 'jokigendong')
        
           
      <ul class="col-span-3 flex flex-col space-y-8 md:col-span-2">
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-input">
                         <input type="hidden" id="nominal">
                    <input type="hidden" id="metode">
                    <input type="hidden" id="ktg_tipe" value="{{ $kategori->tipe }}">
                
                  
   <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">1</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Masukkan Data Akun Kamu</h3>
                    </div>
                </div>
              
                    @php
                        if($kategori->field_2 !== null){
                            $field2Values = explode(',', (string) ($kategori->field_2 ?? ''));
                            $selectValue = isset($field2Values[2]) ? trim($field2Values[2]) : null;
                        }
                        
                            $fieldSelectTitle = explode(',', (string) ($kategori->field_select_title ?? ''));
                            $fieldSelect = explode(',', (string) ($kategori->field_select ?? ''));
                            $field1Values = explode(',', (string) ($kategori->field_1 ?? ''));
                        @endphp
                   @if($kategori->field_2 !== null)
                             <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                @if($kategori->require_user_id ?? true)
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input 
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" 
                                            type="{{ $field1Values[2] }}" 
                                            id="user_id" name="user_id" 
                                            placeholder="{{ $field1Values[1] }}"/> 
                                    </div>
                                </div>
                                @endif
                                @if($selectValue == "select")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2"> {{ $field2Values[0] }}</label>
                                        <select class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" id="zone">
                                            <option value="">{{ $field2Values[1] }}</option>
                                            @foreach($fieldSelectTitle as $key => $fst)
                                                <option value="{{ $fieldSelect[$key] }}">{{ $fst }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($selectValue == "text" || $selectValue == "number" || $selectValue == "password")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2">{{ $field2Values[0] }}</label>
                                        <div class="flex flex-col items-start">
                                            <input
                                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                                type="{{ $field2Values[2] }}"
                                                name="zone_id" id="zone"
                                                placeholder="{{ $field2Values[1] }}"/>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                        @elseif(in_array($kategori->tipe,['jokigendong']))
                        
                        <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
        <div>
            <label for="nickname_joki" class="block text-xs font-medium text-white pb-2">User ID & Nick Name</label>
            <div class="flex flex-col items-start">
                <input
                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                    type="text"
                    id="nickname_joki" name="nickname_joki"
                    placeholder="User ID & Nick Name"
                    required
                />
            </div>
        </div>
        
        <div>
            <label for="loginvia_joki" class="block text-xs font-medium text-white pb-2">Role</label>
            <select
                id="loginvia_joki"
                name="loginvia_joki"
                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                required
            >
                 <option value="">Pilih Role</option>
                <option value="jungler">Jungler</option>
                <option value="roamer">Roamer</option>
                <option value="midlaner">Mid Lane</option>
                <option value="explaner">Exp Lane</option>
                <option value="goldlaner">Gold Lane</option>
            </select>
        </div>
          <div>
            <label for="tglmain_joki" class="block text-xs font-medium text-white pb-2">Tanggal Main</label>
            <div class="flex flex-col items-start">
                <input
                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                    type="text"
                    id="tglmain_joki" name="tglmain_joki"
                    placeholder="Ketikan Tanggal Main"
                    required
                />
            </div>
        </div>
          <div>
            <label for="jambooking_joki" class="block text-xs font-medium text-white pb-2">Jam Booking</label>
            <div class="flex flex-col items-start">
                <input
                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                    type="text"
                    id="jambooking_joki" name="jambooking_joki"
                    placeholder="Ketikan Jam Booking"
                    required
                />
            </div>
        </div>
          <div>
            <label for="catatan_joki" class="block text-xs font-medium text-white pb-2">Catatan Untuk Penjoki</label>
            <div class="flex flex-col items-start">
                <input
                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                    type="text"
                    id="catatan_joki" name="catatan_joki"
                    placeholder="Ketikan Catatan Untuk Penjoki"
                    required
                />
            </div>
        </div>
    </div>
                        
                        @else
                            <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                            type="{{ $field1Values[2] }}"
                                            id="user_id" name="user_id"
                                            placeholder="{{ $field1Values[1] }}"/> 
                                    </div>
                                </div>
                            </div>
                        @endif

					     <div class="px-4 pb-4 text-[10px] sm:px-6 sm:pb-6">
                            <div>
                            <p><em>@safeHtml($kategori->deskripsi_field)</em></p>
                        </div>
                        
                            </div>
                </div>
                    <!--end section input-->
     
            

<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('skeleton-loader').style.display = 'none';
            document.getElementById('itemList').classList.remove('hidden');
        }, 1500);
    });
</script>
              <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-nominal">
                 <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 2 </div>
                   <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Nominal </h3>
                 </div>
                 <div id="skeleton-loader" class="skeleton-loader grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 px-4 mt-4 py-4">
                @for ($i = 0; $i < 12; $i++)
                    <div class="ph-item melpaaaaaa">  
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            
                 <style>
        .scroll-container {
            display: flex;
            overflow-x: auto;
            padding: 1rem 0;
            white-space: nowrap;
            scrollbar-width: thin;
        }

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

       .button-3d {
    background: linear-gradient(145deg, var(--warna_2), var(--warna_3));
    border-radius: 12px;
    color: #f2efef;
    font-weight: bold;
    margin: 0 5px;
    padding: 7px 20px;
    transition: transform 0.3s;
    display: inline-block;
    cursor: pointer;
}

        .button-3d:active {
            transform: translateY(4px);
            
        }
        
       .rate {
    background-color: var(--warna_1);
    box-shadow: 0 0 6px 1px var(--warna_1);
    color: #fffbfb;
    padding: 0 .5em;
    font-weight: 800;
    text-align: center;
    border-radius: 1em;
}
@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    40% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.asdasdwe_2353_Sdfsdccxxx_Xx3979b {
    position: absolute;
    top: -4px;
    right: 0;
    background-color: #FF3956;
    color: #1f1f1f;
    background: linear-gradient(45deg, #92918f, #b6b6b6, #e4e4e4, #8c8c8c, #f8f8f8, #b3b3b3, #636363, rgba(255, 255, 255, 0.9) 80%, #dcdbd6, #b6b6b5, #9e9e9e, #d0d0d0, #c8c8c8, #a3a3a2, #bebebe);
    background-size: 700% 200%;
    animation: gradientAnimation 2.5s linear infinite;
}

    </style>
              <div id="paketList" x-data="{ selectedPaket: 'all', selectedProduct: '' }" class="p-4 sm:p-6">
                  
                  <h3 class="font-semibold mt-4">📦 Pilih Paket</h3>
        <div class="scroll-container">
            <button @click="selectedPaket = 'all'" class="button-3d">🎮 Semua</button>
            @foreach($pakets as $paket)
            <button @click="selectedPaket = {{ $loop->index }}" class="button-3d">{{ $paket['nama'] }}</button>
            @endforeach
        </div>
        <div id="itemList" class="flex flex-col space-y-4 sm:p-1">
            <div x-show="selectedPaket === 'all'">
                @foreach($pakets as $paket) 
                <section>
                    <h3 class="font-semibold  mt-4">{{ $paket['nama'] }}</h3>
                    <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                        <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                            @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                            <div x-bind:class="{ 'ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                                <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                                @php
                                $currentDateTime = now();
                            @endphp
                            
                            <span class="flex flex-1">
                                <span class="flex flex-col justify-between">
                                    <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
                                    <div>
                                        @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                                            <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </span>
                            
                                @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                    <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
                                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                        <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                                            {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
                                        </div>
                                    </div>
                                @endif
                            </span>

                                @if($nom['product_logo'])
                                <div class="flex aspect-square w-8 items-center">
                                    <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                                </div>
                                @endif
                                <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endforeach
            </div>
            @foreach($pakets as $paket) 
            <section x-show="selectedPaket === {{ $loop->index }}" x-transition>
                <h3 class="font-semibold">{{ $paket['nama'] }}</h3>
                <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                    <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                        @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                        <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                            <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                          @php
    $currentDateTime = now();
@endphp

<span class="flex flex-1">
    <span class="flex flex-col justify-between">
        <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
        <div>
            @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @else
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @endif
        </div>
    </span>

    @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
        <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
            <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
            </div>
        </div>
    @endif
</span>

                            @if($nom['product_logo'])
                            <div class="flex aspect-square w-8 items-center">
                                <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                            </div>
                            @endif
                            <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endforeach
        </div>
    </div>
               </div>
               
               
          

                      @if(in_array($kategori->tipe,['jokigendong']))
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="quantity">
  
   <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 3 </div>
                <h3 class="flex w-full items-center justify-between text-sm/6 rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Jumlah Pembelian </h3>
              </div>
              
  <div class="p-4 sm:px-6 sm:pb-6">
    <div class="flex items-center gap-x-4">
      <div class="flex-1">
        <div class="flex flex-col items-start">
         <input
                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent disabled:cursor-not-allowed disabled:opacity-75"
                type="number" name="qty" id="qty" value="1" min="1" max="30" disabled required
                oninput="validateQtyInput(this)"
            />
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" id="incrementBtn" class="flex items-center justify-center rounded-md bg-murky-200 p-1.5 text-murky-800 disabled:cursor-not-allowed disabled:opacity-75">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
          </svg>
        </button>
        <button type="button" id="decrementBtn" class="flex items-center justify-center rounded-md bg-murky-200 p-1.5 text-murky-800 disabled:cursor-not-allowed disabled:opacity-75">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>
                @endif
           
            <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-payment-channel">
              <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 4 </div>
                <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Metode Pembayaran </h3>
              </div>
              
                 <div id="skeleton-loaderr" class="skeleton-loader grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 px-4 mt-4 py-4">
                @for ($i = 0; $i < 4; $i++)
                    <div class="ph-item melpaaaaaa">
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
              <dl id="paymentList" class="flex w-full flex-col space-y-4 p-4 sm:p-6 hidden" x-data="{ selected: null, paymentSelected: '' }">

                  <!--QRIS-->
                @foreach($pay_method as $p) 
                    @if($p->isType('qris'))
                        <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{$p->name}}', 'bg-murky-200': paymentSelected !== '{{$p->name}}' }" class="relative flex cursor-pointer method-list rounded-xl border border-transparent bg-murky-200 p-4 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" role="radio" aria-checked="false" method-id="{{$p->name}}" name="paymentMethod" @click="paymentSelected = '{{$p->name}}'">
                            <div class="flex items-center gap-2 max-w-xs">
                                <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->name}}" class="peer hidden" />
                                <label for="method_{{$p->id}}"></label>
                                <img src="{{ $p->image_url }}" alt="qris" width="55" height="40" />
                                <div>
                                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{$p->name}}</span>
                                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{$p->name}}">Rp 0</p>
                                </div>
                            </div>
                            <div class="max-w-xs">
                                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
                                </div>
                            </div>
                            <div class="flex aspect-square w-8 items-center">
                                <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                    <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                                </div>
                            </div>
                        </div>
                    @endif 
                @endforeach
                
                <!--end QRIS-->
                
                
                <!-- E-Wallet -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-1" type="button" @click="selected !== 3 ? selected = 3 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-1">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>E-Wallet</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 3 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700 " x-ref="container1" x-bind:style="selected == 3 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out " id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === '{{$p->code}}', 'grayscale': paymentSelected !== '{{$p->code}}' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 3 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 3 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                
              
                <!-- Virtual Account -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-2" type="button" @click="selected !== 5 ? selected = 5 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-2">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Virtual Account</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 5 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container2" x-bind:style="selected == 5 ? 'max-height: ' + $refs.container2.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-2">
                        <div id="radiogroup-2" role="radiogroup" aria-labelledby="label-2">
                          <div id="virtualAccountList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo5" x-bind:style="selected == 5 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 5 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                <!-- Convenience Store -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-3" type="button" @click="selected !== 4 ? selected = 4 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-3">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Convenience Store</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 4 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container3" x-bind:style="selected == 4 ? 'max-height: ' + $refs.container3.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-3">
                        <div id="radiogroup-3" role="radiogroup" aria-labelledby="label-3">
                          <div id="convenienceStoreList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh" id="">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo4" x-bind:style="selected == 4 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 4 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
              </dl>
            </div>

                 <div class="rounded-xl bg-murky-800 shadow-2xl" id="promooo">
                 
                     <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 5 </div>
                <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Kode Promo </h3>
              </div>
                  <div class="p-4 sm:px-6 sm:pb-6">
                    <label for="voucher" class="block text-xs font-medium text-white pb-2">Kode Promo</label>
                    <div class="flex items-center space-x-2">
                      <div class="grow">
                        <div class="flex flex-col items-start">
                          <input class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white" type="text" id="voucher" name="voucher" placeholder="Masukkan Kode Promo Anda" required/>
                        </div>
                      </div>
                      <button type="button" id="btn-check" class="flex items-center justify-center rounded-md bg-primary-5400 py-2 px-4 text-xs font-semibold text-white hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-75"> Gunakan </button>
                    </div>
                    <div class="pt-2 text-xs text-red-500"></div>


                  </div>
                </div>
                
                
                
     <div class="rounded-xl bg-murky-800 shadow-2xl jumpToWhatsApp" id="whatsappp">
 
        <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 6 </div>
                   <h3 class="flex w-full items-center justify-between rounded-tr-xl text-sm/6 bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> No. WhatsApp </h3>
                 </div>
    <div class="p-4 sm:px-6">
        <label for="nomor" class="block text-xs font-medium text-white pb-2">No. WhatsApp</label>
        <div class="PhoneInput">
          
            <input
            type="number"
            id="nomor"
            autocomplete="off"
            name="whatsapp"
            placeholder="Contoh 08213456789"
            class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
            value=""
            id="phoneNumberInput"
        />

        </div>
        <span class="text-xxs italic">**Nomor ini akan dihubungi jika terjadi masalah</span>
        
    <p class="flex items-center gap-2 rounded-md bg-primary-5400 px-4 py-2.5 text-xs/6">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M12 16v-4"></path>
        <path d="M12 8h.01"></path>
    </svg>
    <span>Bukti transaksi akan kami kirim ke whatsapp yang kamu isi di atas.</span>
</p>
    </div>

</div>

                
                
                      <div class="inset-x-0 bottom-0 z-10  !mt-0 shad sticky bottom-0 rounded-t-lg pb-4 flex flex-col gap-4 bg-background">
                  <div class=" space-y-0">
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm  rounded-lg md:hidden initial-element" style="display: flex;">
                      <div class="flex w-full flex-col space-y-0">
                        <div class="rounded-md p-4">
                                 <div class="text-center">Belum ada item produk yang dipilih.</div>
                        </div>
                      </div>
                    </div>
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm text-secondary-foreground md:hidden selected-element " style="display: none;">
                      <div class="mb-1 aspect-square timmel-5">
                        <img alt="icon" sizes="100vw" src="{{ asset($kategori->thumbnail) }}" width="80" height="100" decoding="async" data-nimg="1" class="aspect-square timmel-5 rounded-lg object-cover" loading="lazy" style="color: transparent">
                      </div>
                      <div class="flex w-full flex-col space-y-1 ml-3">
                          
                        <div class="text-xs font-semibold cana select glowing-text">{{ $kategori->nama }}</div>
                        <div class="flex items-center gap-2 pt-0.5 font-semibold">
                            
                        <p class="text-xs font-semibold text-warning text-amber-300 selected-order"></p><span>-</span>
                            <div class="text-xs  select text-white" id="pesan"></div></div>
                        
                        <p class="text-xxs italic text-murky-300">**Waktu proses instan</p>
                        <div class="flex w-full items-center">
                          <p class="text-xs italic select"></p>
                        </div>
                      </div>
                    </div>
                    
                      <div class="mt-4"></div>
                    <div class="relative">
                      <button class="inline-flex items-center justify-center rounded-md bg-primary-5400 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-75 btn-order relative flex w-full gap-2 overflow-hidden" type="button" id="order-check">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-4 w-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <span>Pesan Sekarang!</span>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="mt-4 block rounded-xl bg-murky-800 shadow-2xl md:hidden">
                    <div class="flex border-b border-murky-600">
                        <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b bg-primary-500  to-primary-600 px-3 py-2 text-xl font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4">
                                <path
                                    fill-rule="evenodd"
                                    d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                         <h3
            class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">
            Ulasan</h3>
                    </div>
                    
                     <div class="flow-root p-6">
                      
                        @php
                        $ratings = DB::table('ratings')->where('kategori_id', $kategori->id)->get();
                    
                        $totalStars = 0;
                        $totalReviews = $ratings->count();
                        $positiveReviews = 0;
                    
                        foreach ($ratings as $rating) {
                            $totalStars += $rating->bintang;
                            if ($rating->bintang >= 4) {
                                $positiveReviews++;
                            }
                        }
                    
                        if ($totalReviews > 0) {
                            $averageRating = $totalStars / $totalReviews;
                            $satisfactionPercentage = ($positiveReviews / $totalReviews) * 100;
                        } else {
                            $averageRating = 0; 
                            $satisfactionPercentage = 0;
                        }
                        @endphp
                    
                        <div class="flex flex-col  overflow-hidden ">
                            <div class="mx-6 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-8 w-8 flex-shrink-0 text-yellow-400">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                </svg>
                                <div><span class="text-5xl text-besar">{{ number_format($averageRating, 1) }}</span> <span> / </span><span>5.0</span></div>
                            </div>
                            <div class="flex flex-col gap-1">
                               
                        <div class="mx-6 flex items-center justify-center text-xs font-bold">{{ number_format($satisfactionPercentage, 0) }}% pembeli merasa puas dengan produk ini.</div>
                        <div class="mx-6 flex items-center justify-center gap-2 text-xs">Dari {{ $totalReviews }} Ulasan.</div>
                            </div>
                        </div>
                        @php
                        $totalRatings = [
                            '5' => $ratings->where('bintang', 5)->count(),
                            '4' => $ratings->where('bintang', 4)->count(),
                            '3' => $ratings->where('bintang', 3)->count(),
                            '2' => $ratings->where('bintang', 2)->count(),
                            '1' => $ratings->where('bintang', 1)->count(),
                        ];
                        @endphp
                    
                    
                        <div class="flex flex-col  overflow-hidden pt-6">
                            @foreach($totalRatings as $rating => $count)
                            @php
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                            @endphp
                            <ul class="rating-list" style="list-style-type: none; padding-left: 0;">
                                <li class="rating-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                    <div class="rating-value" style="width: 30px; text-align: right; margin-right: 10px;">
                                        {{ $rating }}
                                    </div>
                                    <div class="star-rating" style="display: flex; align-items: center; margin-right: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="height: 20px; width: 20px; color: #ffc107;">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="bar" style="flex-grow: 1; height: 10px; background-color: #ddd; border-radius: 5px; overflow: hidden;">
                                        <div class="progress" style="height: 100%; background-color: #ffc107; border-radius: 5px; width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="count" style="width: 50px; margin-left: 0px; text-align: right;">{{ $count }}</div>
                                </li>
                            </ul>

                            @endforeach
                        </div>
                    
                        @if($ratings->isEmpty())
                        <div class="py-4">
                            <div class="rounded-md border-l-4 border-yellow-400 bg-yellow-100 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-yellow-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3"><p class="text-sm text-yellow-700">Belum ada ulasan dan penilaian.</p></div>
                                </div>
                            </div>
                        </div>
                        @else
                       
                <div class="mt-6"><p class="text-sm text-secondary-foreground">Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya tentang pengalamanmu.</p></div>
                         <hr>
                <div class="flow-root pt-5">
                    <div class="-my-6 divide-y">
                         @foreach($ratings->reverse()->take(5) as $rating)
                        <div class="py-3">
                            <div class="flex items-center">
                                <div class="w-full">
                                    <div class="flex items-start justify-between">
                                        @php
                                        $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                                        if(!$username && isset($rating->no_pembeli)) {
                                            $username = $rating->no_pembeli;
                                        }
                                        $usernameLength = strlen($username);
                                        $sensorLength = $usernameLength <= 5 ? 2 : 4;
                                        $start = floor(($usernameLength - $sensorLength) / 2);
                                        $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                                        @endphp
                                        <h4 class="mt-0.5 text-xs font-bold text-white">{{ $censoredUsername }}</h4>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $i <= $rating->bintang ? 'currentColor' : 'white' }}" aria-hidden="true" class="text-yellow-400 h-4 w-4 flex-shrink-0">
                                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="sr-only">{{ $rating->bintang }} dari 5 bintang</p>
                                </div>
                            </div>
                            <div class="flex w-full justify-between pt-1 text-xxs">
                                <span>{{ $rating->layanan }}</span>
                                <span>{{ $rating->created_at }}</span>
                            </div>
                            <div class="text-murky-20 mt-1 space-y-6 text-xs italic">“{{ $rating->comment }}”</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
               <div class="flex justify-end pt-5 mt-5">
                   
    <a
        class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent/75 hover:text-accent-foreground h-8 rounded-md px-4 bg-secondary/50 pr-3 flex items-center gap-2"
        type="button"
        href="/id/reviews"
        style="outline: none;"
    >
        <span>Lihat semua ulasan</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4">
            <path d="M5 12h14"></path>
            <path d="m12 5 7 7-7 7"></path>
        </svg>
    </a>
</div>

                    </div>
                </div>
            </ul>
        @elseif($kategori->tipe === 'giftskin')
        
        
      <ul class="col-span-3 flex flex-col space-y-8 md:col-span-2">
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-input">
                         <input type="hidden" id="nominal">
                    <input type="hidden" id="metode">
                    <input type="hidden" id="ktg_tipe" value="{{ $kategori->tipe }}">
                    <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">1</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Masukkan Data Akun Kamu</h3>
                    </div>
                </div>
                    
                    @php
                        if($kategori->field_2 !== null){
                            $field2Values = explode(',', (string) ($kategori->field_2 ?? ''));
                            $selectValue = isset($field2Values[2]) ? trim($field2Values[2]) : null;
                        }
                        
                            $fieldSelectTitle = explode(',', (string) ($kategori->field_select_title ?? ''));
                            $fieldSelect = explode(',', (string) ($kategori->field_select ?? ''));
                            $field1Values = explode(',', (string) ($kategori->field_1 ?? ''));
                        @endphp
                   @if($kategori->field_2 !== null)
                            <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input 
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" 
                                            type="{{ $field1Values[2] }}" 
                                            id="user_id" name="user_id" 
                                            placeholder="{{ $field1Values[1] }}" 
                                            value="{{ auth()->check() ? Auth::user()->idgame2 : '' }}"/> 
                                    </div>
                                </div>
                                @if($selectValue == "select")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2"> {{ $field2Values[0] }}</label>
                                        <select class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" id="zone">
                                            <option value="">{{ $field2Values[1] }}</option>
                                            @foreach($fieldSelectTitle as $key => $fst)
                                                <option value="{{ $fieldSelect[$key] }}">{{ $fst }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($selectValue == "text" || $selectValue == "number" || $selectValue == "password")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2">{{ $field2Values[0] }}</label>
                                        <div class="flex flex-col items-start">
                                            <input
                                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                                type="{{ $field2Values[2] }}"
                                                name="zone_id" id="zone"
                                                placeholder="{{ $field2Values[1] }}" 
                                                value="{{ auth()->check() ? Auth::user()->servergame : '' }}"/>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
    
                        @elseif(in_array($kategori->tipe,['giftskin']))
                        
                        <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
        <div>
            <label for="nickname_joki" class="block text-xs font-medium text-white pb-2">User ID & Nick Name</label>
            <div class="flex flex-col items-start">
                <input
                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                    type="text"
                    id="nickname_joki" name="nickname_joki"
                    placeholder="User ID & Nick Name"
                    required
                />
            </div>
        </div>
        <div>
            <label for="catatan_joki" class="block text-xs font-medium text-white pb-2">Nama Skin/Item/Kharisma</label>
            <div class="flex flex-col items-start">
                <input
                    class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-black placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                    type="text"
                    id="catatan_joki" name="catatan_joki"
                    placeholder="Ketikan Nama Skin/Item/Kharisma"
                    required
                />
            </div>
        </div>
    </div>
                        
                        @else
                            <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                            type="{{ $field1Values[2] }}"
                                            id="user_id" name="user_id"
                                            placeholder="{{ $field1Values[1] }}" 
                                            value="{{ auth()->check() ? Auth::user()->idgame : '' }}"/> 
                                    </div>
                                </div>
                            </div>
                        @endif

					     <div class="px-4 pb-4 text-[10px] sm:px-6 sm:pb-6">
                            <div>
                            <p><em>@safeHtml($kategori->deskripsi_field)</em></p>
                        </div>
                        
                            </div>
                </div>
                    <!--end section input-->
                    
                        

                
					
					

                
                
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('skeleton-loader').style.display = 'none';
            document.getElementById('itemList').classList.remove('hidden');
        }, 1500);
    });
</script>
              <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-nominal">
                 <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 2 </div>
                   <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Nominal </h3>
                 </div>
                 <div id="skeleton-loader" class="skeleton-loader grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 px-4 mt-4 py-4">
                @for ($i = 0; $i < 12; $i++)
                    <div class="ph-item melpaaaaaa">  
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            
                 <style>
        .scroll-container {
            display: flex;
            overflow-x: auto;
            padding: 1rem 0;
            white-space: nowrap;
            scrollbar-width: thin;
        }

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

       .button-3d {
    background: linear-gradient(145deg, var(--warna_2), var(--warna_3));
    border-radius: 12px;
    color: #f2efef;
    font-weight: bold;
    margin: 0 5px;
    padding: 7px 20px;
    transition: transform 0.3s;
    display: inline-block;
    cursor: pointer;
}

        .button-3d:active {
            transform: translateY(4px);
            
        }
        
       .rate {
    background-color: var(--warna_1);
    box-shadow: 0 0 6px 1px var(--warna_1);
    color: #fffbfb;
    padding: 0 .5em;
    font-weight: 800;
    text-align: center;
    border-radius: 1em;
}
@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    40% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.asdasdwe_2353_Sdfsdccxxx_Xx3979b {
    position: absolute;
    top: -4px;
    right: 0;
    background-color: #FF3956;
    color: #1f1f1f;
    background: linear-gradient(45deg, #92918f, #b6b6b6, #e4e4e4, #8c8c8c, #f8f8f8, #b3b3b3, #636363, rgba(255, 255, 255, 0.9) 80%, #dcdbd6, #b6b6b5, #9e9e9e, #d0d0d0, #c8c8c8, #a3a3a2, #bebebe);
    background-size: 700% 200%;
    animation: gradientAnimation 2.5s linear infinite;
}

    </style>
              <div id="paketList" x-data="{ selectedPaket: 'all', selectedProduct: '' }" class="p-4 sm:p-6">
                  
                  <h3 class="font-semibold mt-4">📦 Pilih Paket</h3>
        <div class="scroll-container">
            <button @click="selectedPaket = 'all'" class="button-3d">🎮 Semua</button>
            @foreach($pakets as $paket)
            <button @click="selectedPaket = {{ $loop->index }}" class="button-3d">{{ $paket['nama'] }}</button>
            @endforeach
        </div>
        <div id="itemList" class="flex flex-col space-y-4 sm:p-1">
            <div x-show="selectedPaket === 'all'">
                @foreach($pakets as $paket) 
                <section>
                    <h3 class="font-semibold  mt-4">{{ $paket['nama'] }}</h3>
                    <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                        <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                            @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                            <div x-bind:class="{ 'ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                                <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                                @php
                                $currentDateTime = now();
                            @endphp
                            
                            <span class="flex flex-1">
                                <span class="flex flex-col justify-between">
                                    <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
                                    <div>
                                        @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                                            <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </span>
                            
                                @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                    <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
                                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                        <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                                            {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
                                        </div>
                                    </div>
                                @endif
                            </span>

                                @if($nom['product_logo'])
                                <div class="flex aspect-square w-8 items-center">
                                    <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                                </div>
                                @endif
                                <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endforeach
            </div>
            @foreach($pakets as $paket) 
            <section x-show="selectedPaket === {{ $loop->index }}" x-transition>
                <h3 class="font-semibold">{{ $paket['nama'] }}</h3>
                <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                    <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                        @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                        <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                            <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                          @php
    $currentDateTime = now();
@endphp

<span class="flex flex-1">
    <span class="flex flex-col justify-between">
        <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
        <div>
            @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @else
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @endif
        </div>
    </span>

    @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
        <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
            <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
            </div>
        </div>
    @endif
</span>

                            @if($nom['product_logo'])
                            <div class="flex aspect-square w-8 items-center">
                                <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                            </div>
                            @endif
                            <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endforeach
        </div>
    </div>
               </div>
               
               
    
            <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-payment-channel">
              <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-4 py-2 text-xl font-semibold"> 3 </div>
                <h3 class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Metode Pembayaran </h3>
              </div>
              <dl id="paymentList" class="flex w-full flex-col space-y-4 p-4 sm:p-6" x-data="{ selected: null, paymentSelected: '' }">
                  
                <!--saldo1-->

<div class="relative flex cursor-pointer rounded-xl border border-transparent bg-murky-200 p-3 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out">
    <div class="flex items-center gap-2 max-w-xs">
        
       <img src="/assets/logo/coin.webp" alt="Coin" width="45" height="40" />
          @foreach($pay_method as $p) 
            @if($p->tipe == 'SALDO')
                <div>
                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">FIstore COIN</span>
                    <p class="block text-xxs text-murky-800 sm:text-xs" id="{{$p->code}}">Rp 0</p>
                </div>
            @endif 
        @endforeach
    </div>
    <div class="max-w-xs">
        <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
            <!--<span class="text-xs text-rose-800">Saldo Account tidak mencukupi</span>-->
        </div>
    </div>
    <div class="flex aspect-square w-8 items-center">
        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
            <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
            <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
        </div>
    </div>
</div>          
              <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>

        <button class="w-full disabled:opacity-75" id="disclosure-button-:rbb:" type="button" @click="selected !== 7 ? selected = 7 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-:rc8:">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>QRIS</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 7 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container1" x-bind:style="selected == 7 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('qris')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out " id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                    <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === '{{$p->code}}', 'grayscale': paymentSelected !== '{{$p->code}}' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 7 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 7 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('qris')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                
                
                
                
                
                <!-- E-Wallet -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-1" type="button" @click="selected !== 3 ? selected = 3 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-1">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>E-Wallet</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 3 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700 " x-ref="container1" x-bind:style="selected == 3 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out " id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === '{{$p->code}}', 'grayscale': paymentSelected !== '{{$p->code}}' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 3 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 3 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                
              
                <!-- Virtual Account -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-2" type="button" @click="selected !== 5 ? selected = 5 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-2">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Virtual Account</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 5 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container2" x-bind:style="selected == 5 ? 'max-height: ' + $refs.container2.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-2">
                        <div id="radiogroup-2" role="radiogroup" aria-labelledby="label-2">
                          <div id="virtualAccountList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo5" x-bind:style="selected == 5 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 5 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                <!-- Convenience Store -->
                  
                 <!-- Convenience Store -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-3" type="button" @click="selected !== 4 ? selected = 4 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-3">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Convenience Store</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 4 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container3" x-bind:style="selected == 4 ? 'max-height: ' + $refs.container3.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-3">
                        <div id="radiogroup-3" role="radiogroup" aria-labelledby="label-3">
                          <div id="convenienceStoreList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh" id="">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo4" x-bind:style="selected == 4 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 4 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
               </dl>
            </div>
                 <div class="rounded-xl bg-murky-800 shadow-2xl" id="promooo">
                  <div class="flex border-b border-murky-600">
                    <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b bg-primary-500  px-4 py-2 text-xl font-semibold">5</div>
                    <h3 class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Kode Promo</h3>
                  </div>
                  <div class="p-4 sm:px-6 sm:pb-6">
                    <label for="voucher" class="block text-xs font-medium text-white pb-2">Kode Promo</label>
                    <div class="flex items-center space-x-2">
                      <div class="grow">
                        <div class="flex flex-col items-start">
                          <input class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" type="text" id="voucher" name="voucher" placeholder="Masukkan Kode Promo Anda" required/>
                        </div>
                      </div>
                      <button type="button" id="btn-check" class="flex items-center justify-center rounded-md bg-primary-5400 py-2 px-4 text-xs font-semibold text-white hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-75"> Gunakan </button>
                    </div>
                    <div class="pt-2 text-xs text-red-500"></div>


                  </div>
                </div>
                
                
            
                
                
                
                
                
                
                
                
                
                
                <div class="rounded-xl bg-murky-800 shadow-2xl jumpToWhatsApp" id="whatsappp">
    <div class="flex border-b border-murky-600">
        <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b bg-primary-500 px-4 py-2 text-xl font-semibold">6</div>
        <h3 class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">No. WhatsApp</h3>
    </div>
    <div class="p-4 sm:px-6">
        <label for="nomor" class="block text-xs font-medium text-white pb-2">No. WhatsApp</label>
      
        <span class="text-xxs italic">**Nomor ini akan dihubungi jika terjadi masalah</span>
        
    <p class="flex items-center gap-2 rounded-md bg-card px-4 py-2.5 text-xs/6">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M12 16v-4"></path>
        <path d="M12 8h.01"></path>
    </svg>
    <span>Bukti transaksi akan kami kirim ke whatsapp yang kamu isi di atas.</span>
</p>
    </div>

</div>

                
                
                
                
               
                <div class="sticky inset-x-0 bottom-0 z-10 -mx-4 !mt-0">
                  <div class="container space-y-0 py-3">
                    <div class="flex items-start justify-start space-x-2 bg-secondary py-2 px-4 rounded-xl md:hidden initial-element" style="display: flex;">
                      <div class="flex w-full flex-col space-y-0">
                        <div class="rounded-md p-4">
                          <div class="flex">
                            <div class="ml-3">
                              <p class="text-sm text-white-700">Belum ada item produk yang dipilih.</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-start justify-start space-x-2 rounded-lg  p-4 text-sm bg-secondary md:hidden selected-element" style="display: none;">
                      <div class="mb-1 aspect-square timmel-5">
                        <img alt="icon" sizes="100vw" src="{{ asset($kategori->thumbnail) }}" width="80" height="100" decoding="async" data-nimg="1" class="aspect-square timmel-5 rounded-lg object-cover" loading="lazy" style="color: transparent">
                      </div>
                      <div class="flex w-full flex-col space-y-1">
                        <div class="text-xs cana select glowing-text selected-order"> Pilih layanan terlebih dahulu</div>
                        <div class="flex items-center gap-2 pt-0.5 font-semibold">
                            
                        <p class="text-xs font-semibold text-warning text-amber-300 selected-order"></p><span>-</span>
                            <div class="text-xs  select text-white" id="pesan"></div></div>
                        
                        <p class="text-xxs italic text-murky-300">**Waktu proses instan</p>
                        <div class="flex w-full items-center">
                          <p class="text-xs italic select"></p>
                        </div>
                      </div>
                    </div>
                    
                      <div class="mt-4"></div>
                    <div class="relative">
                      <button class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-75 btn-order relative flex w-full gap-2 overflow-hidden" type="button" id="order-check">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-4 w-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <span>Pesan Sekarang!</span>
                      </button>
                    </div>
                  </div>
                </div>
                 <div class="mt-4 block rounded-xl bg-murky-800 shadow-2xl md:hidden">
                    <div class="flex border-b border-murky-600">
                        <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b bg-primary-500  to-primary-600 px-4 py-2 text-xl font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4">
                                <path
                                    fill-rule="evenodd"
                                    d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Ulasan</h3>
                    </div>
                    
                     <div class="flow-root p-6">
                      
                        @php
                        $ratings = DB::table('ratings')->where('kategori_id', $kategori->id)->get();
                    
                        $totalStars = 0;
                        $totalReviews = $ratings->count();
                        $positiveReviews = 0;
                    
                        foreach ($ratings as $rating) {
                            $totalStars += $rating->bintang;
                            if ($rating->bintang >= 4) {
                                $positiveReviews++;
                            }
                        }
                    
                        if ($totalReviews > 0) {
                            $averageRating = $totalStars / $totalReviews;
                            $satisfactionPercentage = ($positiveReviews / $totalReviews) * 100;
                        } else {
                            $averageRating = 0; 
                            $satisfactionPercentage = 0;
                        }
                        @endphp
                    
                        <div class="flex flex-col  overflow-hidden ">
                            <div class="mx-6 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-8 w-8 flex-shrink-0 text-yellow-400">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                </svg>
                                <div><span class="text-5xl text-besar">{{ number_format($averageRating, 1) }}</span> <span> / </span><span>5.0</span></div>
                            </div>
                            <div class="flex flex-col gap-1">
                               
                        <div class="mx-6 flex items-center justify-center text-xs font-bold">{{ number_format($satisfactionPercentage, 0) }}% pembeli merasa puas dengan produk ini.</div>
                        <div class="mx-6 flex items-center justify-center gap-2 text-xs">Dari {{ $totalReviews }} Ulasan.</div>
                            </div>
                        </div>
                        @php
                        $totalRatings = [
                            '5' => $ratings->where('bintang', 5)->count(),
                            '4' => $ratings->where('bintang', 4)->count(),
                            '3' => $ratings->where('bintang', 3)->count(),
                            '2' => $ratings->where('bintang', 2)->count(),
                            '1' => $ratings->where('bintang', 1)->count(),
                        ];
                        @endphp
                    
                    
                        <div class="flex flex-col  overflow-hidden pt-6">
                            @foreach($totalRatings as $rating => $count)
                            @php
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                            @endphp
                            <ul class="rating-list" style="list-style-type: none; padding-left: 0;">
                                <li class="rating-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                    
                                        {{ $rating }}
                                    <div class="star-rating" style="display: flex; align-items: center; margin-right: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="height: 20px; width: 20px; color: #ffc107;">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="bar" style="flex-grow: 1; height: 10px; background-color: #ddd; border-radius: 5px; overflow: hidden;">
                                        <div class="progress" style="height: 100%; background-color: #ffc107; border-radius: 5px; width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="count" style="width: 50px; margin-left: -25px; text-align: right;">{{ $count }}</div>
                                </li>
                            </ul>
                            @endforeach
                        </div>
                    
                        @if($ratings->isEmpty())
                        <div class="py-4">
                            <div class="rounded-md border-l-4 border-yellow-400 bg-yellow-100 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-yellow-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3"><p class="text-sm text-yellow-700">Belum ada ulasan dan penilaian.</p></div>
                                </div>
                            </div>
                        </div>
                        @else
                       
                <div class="mt-6"><p class="text-sm text-secondary-foreground">Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya tentang pengalamanmu.</p></div>
                         <hr>
                <div class="flow-root pt-5">
                    <div class="-my-6 divide-y">
                         @foreach($ratings->reverse()->take(5) as $rating)
                        <div class="py-3">
                            <div class="flex items-center">
                                <div class="w-full">
                                    <div class="flex items-start justify-between">
                                        @php
                                        $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                                        if(!$username && isset($rating->no_pembeli)) {
                                            $username = $rating->no_pembeli;
                                        }
                                        $usernameLength = strlen($username);
                                        $sensorLength = $usernameLength <= 5 ? 2 : 4;
                                        $start = floor(($usernameLength - $sensorLength) / 2);
                                        $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                                        @endphp
                                        <h4 class="mt-0.5 text-xs font-bold text-white">{{ $censoredUsername }}</h4>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $i <= $rating->bintang ? 'currentColor' : 'white' }}" aria-hidden="true" class="text-yellow-400 h-4 w-4 flex-shrink-0">
                                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="sr-only">{{ $rating->bintang }} dari 5 bintang</p>
                                </div>
                            </div>
                            <div class="flex w-full justify-between pt-1 text-xxs">
                                <span>{{ $rating->layanan }}</span>
                                <span>{{ $rating->created_at }}</span>
                            </div>
                            <div class="text-murky-20 mt-1 space-y-6 text-xs italic">“{{ $rating->comment }}”</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
               <div class="flex justify-end pt-5 mt-5">
                   
    <a
        class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent/75 hover:text-accent-foreground h-8 rounded-md px-4 bg-secondary/50 pr-3 flex items-center gap-2"
        type="button"
        href="/id/reviews"
        style="outline: none;"
    >
        <span>Lihat semua ulasan</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4">
            <path d="M5 12h14"></path>
            <path d="m12 5 7 7-7 7"></path>
        </svg>
    </a>
</div>

                    </div>
                </div>
            </ul>
        
        
        @elseif($kategori->tipe === 'vilogml')
        
      <ul class="col-span-3 flex flex-col space-y-8 md:col-span-2">
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-input">
                         <input type="hidden" id="nominal">
                    <input type="hidden" id="metode">
                    <input type="hidden" id="ktg_tipe" value="{{ $kategori->tipe }}">
                
                  
   <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">1</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Masukkan Data Akun Kamu</h3>
                    </div>
                </div>
              
                    @php
                        if($kategori->field_2 !== null){
                            $field2Values = explode(',', (string) ($kategori->field_2 ?? ''));
                            $selectValue = isset($field2Values[2]) ? trim($field2Values[2]) : null;
                        }
                        
                            $fieldSelectTitle = explode(',', (string) ($kategori->field_select_title ?? ''));
                            $fieldSelect = explode(',', (string) ($kategori->field_select ?? ''));
                            $field1Values = explode(',', (string) ($kategori->field_1 ?? ''));
                        @endphp
                   @if($kategori->field_2 !== null)
                             <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                @if($kategori->require_user_id ?? true)
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input 
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" 
                                            type="{{ $field1Values[2] }}" 
                                            id="user_id" name="user_id" 
                                            placeholder="{{ $field1Values[1] }}"/> 
                                    </div>
                                </div>
                                @endif
                                @if($selectValue == "select")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2"> {{ $field2Values[0] }}</label>
                                        <select class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent" id="zone">
                                            <option value="">{{ $field2Values[1] }}</option>
                                            @foreach($fieldSelectTitle as $key => $fst)
                                                <option value="{{ $fieldSelect[$key] }}">{{ $fst }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($selectValue == "text" || $selectValue == "number" || $selectValue == "password")
                                    <div>
                                        <label for="zone" class="block text-xs font-medium text-white pb-2">{{ $field2Values[0] }}</label>
                                        <div class="flex flex-col items-start">
                                            <input
                                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                                type="{{ $field2Values[2] }}"
                                                name="zone_id" id="zone"
                                                placeholder="{{ $field2Values[1] }}"/>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                        @elseif(in_array($kategori->tipe,['vilogml']))
                       <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                        <div>
                            <label for="email_joki" class="block text-xs font-medium text-white pb-2">Email</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="email"
                                    id="email_joki"
                                    name="email_joki"
                                    placeholder="Ketikan Email"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="password_joki" class="block text-xs font-medium text-white pb-2">Password</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="password"
                                    id="password_joki"
                                    name="password_joki"
                                    placeholder="Ketikan Password"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="loginvia_joki" class="block text-xs font-medium text-white pb-2">Login Via</label>
                            <select
                                id="loginvia_joki"
                                name="loginvia_joki"
                                class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                required
                            >
                                <option value="" disabled selected>Login Via</option>
                                <option value="moonton">Moonton (Rekomendasi)</option>
                                <option value="vk">VK</option>
                                <option value="tiktok">Tiktok</option>
                                <option value="facebook">Facebook</option>
                            </select>
                        </div>
                        <div>
                            <label for="nickname_joki" class="block text-xs font-medium text-white pb-2">User ID</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="text"
                                    id="nickname_joki"
                                    name="nickname_joki"
                                    placeholder="Ketikan User ID"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="request_joki" class="block text-xs font-medium text-white pb-2">Server ID</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="text"
                                    id="request_joki"
                                    name="request_joki"
                                    placeholder="Ketikan Server ID"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label for="catatan_joki" class="block text-xs font-medium text-white pb-2">Catatan</label>
                            <div class="flex flex-col items-start">
                                <input
                                    class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
                                    type="text"
                                    id="catatan_joki"
                                    name="catatan_joki"
                                    placeholder="Catatan"
                                    required
                                />
                            </div>
                        </div>
                    </div>
    
                        
                        @else
                            <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"
                                            type="{{ $field1Values[2] }}"
                                            id="user_id" name="user_id"
                                            placeholder="{{ $field1Values[1] }}"/> 
                                    </div>
                                </div>
                            </div>
                        @endif

					     <div class="px-4 pb-4 text-[10px] sm:px-6 sm:pb-6">
                            <div>
                            <p><em>@safeHtml($kategori->deskripsi_field)</em></p>
                        </div>
                        
                            </div>
                </div>
                    <!--end section input-->
     
    

<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('skeleton-loader').style.display = 'none';
            document.getElementById('itemList').classList.remove('hidden');
        }, 1500);
    });
</script>
              <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-nominal">
                 <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 2 </div>
                   <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Nominal </h3>
                 </div>
                 <div id="skeleton-loader" class="skeleton-loader grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 px-4 mt-4 py-4">
                @for ($i = 0; $i < 12; $i++)
                    <div class="ph-item melpaaaaaa">  
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            
                 <style>
        .scroll-container {
            display: flex;
            overflow-x: auto;
            padding: 1rem 0;
            white-space: nowrap;
            scrollbar-width: thin;
        }

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

       .button-3d {
    background: linear-gradient(145deg, var(--warna_2), var(--warna_3));
    border-radius: 12px;
    color: #f2efef;
    font-weight: bold;
    margin: 0 5px;
    padding: 7px 20px;
    transition: transform 0.3s;
    display: inline-block;
    cursor: pointer;
}

        .button-3d:active {
            transform: translateY(4px);
            
        }
        
       .rate {
    background-color: var(--warna_1);
    box-shadow: 0 0 6px 1px var(--warna_1);
    color: #fffbfb;
    padding: 0 .5em;
    font-weight: 800;
    text-align: center;
    border-radius: 1em;
}
@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    40% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.asdasdwe_2353_Sdfsdccxxx_Xx3979b {
    position: absolute;
    top: -4px;
    right: 0;
    background-color: #FF3956;
    color: #1f1f1f;
    background: linear-gradient(45deg, #92918f, #b6b6b6, #e4e4e4, #8c8c8c, #f8f8f8, #b3b3b3, #636363, rgba(255, 255, 255, 0.9) 80%, #dcdbd6, #b6b6b5, #9e9e9e, #d0d0d0, #c8c8c8, #a3a3a2, #bebebe);
    background-size: 700% 200%;
    animation: gradientAnimation 2.5s linear infinite;
}

    </style>
              <div id="paketList" x-data="{ selectedPaket: 'all', selectedProduct: '' }" class="p-4 sm:p-6">
                  
                  <h3 class="font-semibold mt-4">📦 Pilih Paket</h3>
        <div class="scroll-container">
            <button @click="selectedPaket = 'all'" class="button-3d">🎮 Semua</button>
            @foreach($pakets as $paket)
            <button @click="selectedPaket = {{ $loop->index }}" class="button-3d">{{ $paket['nama'] }}</button>
            @endforeach
        </div>
        <div id="itemList" class="flex flex-col space-y-4 sm:p-1">
            <div x-show="selectedPaket === 'all'">
                @foreach($pakets as $paket) 
                <section>
                    <h3 class="font-semibold  mt-4">{{ $paket['nama'] }}</h3>
                    <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                        <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                            @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                            <div x-bind:class="{ 'ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                                <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                                @php
                                $currentDateTime = now();
                            @endphp
                            
                            <span class="flex flex-1">
                                <span class="flex flex-col justify-between">
                                    <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
                                    <div>
                                        @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                                            <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </span>
                            
                                @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                    <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
                                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                        <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                                            {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
                                        </div>
                                    </div>
                                @endif
                            </span>

                                @if($nom['product_logo'])
                                <div class="flex aspect-square w-8 items-center">
                                    <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                                </div>
                                @endif
                                <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endforeach
            </div>
            @foreach($pakets as $paket) 
            <section x-show="selectedPaket === {{ $loop->index }}" x-transition>
                <h3 class="font-semibold">{{ $paket['nama'] }}</h3>
                <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                    <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                        @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                        <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                            <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                          @php
    $currentDateTime = now();
@endphp

<span class="flex flex-1">
    <span class="flex flex-col justify-between">
        <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
        <div>
            @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @else
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @endif
        </div>
    </span>

    @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
        <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                        <div class="popular-tag-content">
                                            <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b">
                                                ðŸ”¥PROMO</div>
                                        </div>
                                        <div class="popular-tag-overlay"></div>
                                    </div>
        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
            <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
            </div>
        </div>
    @endif
</span>

                            @if($nom['product_logo'])
                            <div class="flex aspect-square w-8 items-center">
                                <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                            </div>
                            @endif
                            <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endforeach
        </div>
    </div>
               </div>
               
               
          

                      @if(in_array($kategori->tipe,['vilogml']))
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="quantity">
  
   <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 3 </div>
                <h3 class="flex w-full items-center justify-between text-sm/6 rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Jumlah Pembelian </h3>
              </div>
              
  <div class="p-4 sm:px-6 sm:pb-6">
    <div class="flex items-center gap-x-4">
      <div class="flex-1">
        <div class="flex flex-col items-start">
         <input
                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent disabled:cursor-not-allowed disabled:opacity-75"
                type="number" name="qty" id="qty" value="1" min="1" max="30" disabled required
                oninput="validateQtyInput(this)"
            />
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" id="incrementBtn" class="flex items-center justify-center rounded-md bg-murky-200 p-1.5 text-murky-800 disabled:cursor-not-allowed disabled:opacity-75">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
          </svg>
        </button>
        <button type="button" id="decrementBtn" class="flex items-center justify-center rounded-md bg-murky-200 p-1.5 text-murky-800 disabled:cursor-not-allowed disabled:opacity-75">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>
                @endif
           
            <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-payment-channel">
              <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 4 </div>
                <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Pilih Metode Pembayaran </h3>
              </div>
              
                 <div id="skeleton-loaderr" class="skeleton-loader grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 px-4 mt-4 py-4">
                @for ($i = 0; $i < 4; $i++)
                    <div class="ph-item melpaaaaaa">
                        <div class="ph-col-12">
                            <div class="ph-picture"></div>
                            <div class="ph-row">
                                <div class="ph-col-12"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
              <dl id="paymentList" class="flex w-full flex-col space-y-4 p-4 sm:p-6 hidden" x-data="{ selected: null, paymentSelected: '' }">
                  
      
                
                  <!--QRIS-->
                @foreach($pay_method as $p) 
                    @if($p->isType('qris'))
                        <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" class="relative flex cursor-pointer method-list rounded-xl border border-transparent bg-murky-200 p-4 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" role="radio" aria-checked="false" method-id="{{$p->code}}" name="paymentMethod" @click="paymentSelected = '{{$p->code}}'">
                            <div class="flex items-center gap-2 max-w-xs">
                                <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                                <label for="method_{{$p->id}}"></label>
                                <img src="{{ $p->image_url }}" alt="qris" width="55" height="40" />
                                <div>
                                    <span class="block font-bjcredits text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{$p->name}}</span>
                                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{$p->code}}">Rp 0</p>
                                </div>
                            </div>
                            <div class="max-w-xs">
                                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
                                </div>
                            </div>
                            <div class="flex aspect-square w-8 items-center">
                                <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                    <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                                </div>
                            </div>
                        </div>
                    @endif 
                @endforeach
                
                <!--end QRIS-->
                
                
                <!-- E-Wallet -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-1" type="button" @click="selected !== 3 ? selected = 3 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-1">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>E-Wallet</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 3 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700 " x-ref="container1" x-bind:style="selected == 3 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out " id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === '{{$p->code}}', 'grayscale': paymentSelected !== '{{$p->code}}' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 3 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 3 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                
              
                <!-- Virtual Account -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-2" type="button" @click="selected !== 5 ? selected = 5 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-2">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Virtual Account</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 5 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container2" x-bind:style="selected == 5 ? 'max-height: ' + $refs.container2.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-2">
                        <div id="radiogroup-2" role="radiogroup" aria-labelledby="label-2">
                          <div id="virtualAccountList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo5" x-bind:style="selected == 5 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 5 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                <!-- Convenience Store -->
                  
                 <!-- Convenience Store -->
                <div class="flex w-full transform flex-col justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header" data-state="">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-3" type="button" @click="selected !== 4 ? selected = 4 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-3">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Convenience Store</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 4 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container3" x-bind:style="selected == 4 ? 'max-height: ' + $refs.container3.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-3">
                        <div id="radiogroup-3" role="radiogroup" aria-labelledby="label-3">
                          <div id="convenienceStoreList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" method-id="{{$p->code}}" class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-xl border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="radio-group-{{$p->code}}" role="radio" aria-checked="false" method-id="{{$p->code}}" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" tabindex="0" aria-labelledby="label-{{$p->code}}:" aria-describedby="description-{{$p->code}}" @click="paymentSelected = '{{$p->code}}'">
                              <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                              <label for="method_{{$p->id}}"></label>
                              <span class="flex w-full">
                                <span class="flex w-full flex-col justify-between">
                                  <div>
                                    <span class="block text-xs font-semibold text-murky-800">
                                      {{$p->name}}
                                    </span>
                                    <span class="mt-0 flex items-center text-xxs text-murky-600">{{$p->keterangan}}</span>
                                     <hr>
                                  </div>
                                  <div class="flex w-full items-center justify-between">
                                    <div class="mt-1">
                                      <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800  text-dark.meltihhh" id="">
                                        <h6 class="hargapembayaran" id="{{$p->code}}"></h6>
                                      </div>
                                    </div>
                                    <div class="relative aspect-[6/2] w-10">
                                      <img src="{{$p->image_url}}" x-bind:class="{ 'grayscale-0': paymentSelected === 'QRIS', 'grayscale': paymentSelected !== 'QRIS' }" class="object-scale-down grayscale-0" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                                    </div>
                                  </div>
                                </span>
                              </span>
                            </div> @endif @endforeach </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo4" x-bind:style="selected == 4 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 4 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
              </dl>
            </div>

                 <div class="rounded-xl bg-murky-800 shadow-2xl" id="promooo">
                 
                     <div class="flex border-b border-murky-600">
                <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 5 </div>
                <h3 class="flex w-full items-center text-sm/6 justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> Kode Promo </h3>
              </div>
                  <div class="p-4 sm:px-6 sm:pb-6">
                    <label for="voucher" class="block text-xs font-medium text-white pb-2">Kode Promo</label>
                    <div class="flex items-center space-x-2">
                      <div class="grow">
                        <div class="flex flex-col items-start">
                          <input class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white" type="text" id="voucher" name="voucher" placeholder="Masukkan Kode Promo Anda" required/>
                        </div>
                      </div>
                      <button type="button" id="btn-check" class="flex items-center justify-center rounded-md bg-primary-5400 py-2 px-4 text-xs font-semibold text-white hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-75"> Gunakan </button>
                    </div>
                    <div class="pt-2 text-xs text-red-500"></div>

                  </div>
                </div>
                
                
                
     <div class="rounded-xl bg-murky-800 shadow-2xl jumpToWhatsApp" id="whatsappp">
 
        <div class="flex border-b border-murky-600">
                   <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b from-primary-400 to-primary-600 px-3 py-2 text-xl font-semibold"> 6 </div>
                   <h3 class="flex w-full items-center justify-between rounded-tr-xl text-sm/6 bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4"> No. WhatsApp </h3>
                 </div>
    <div class="p-4 sm:px-6">
        <label for="nomor" class="block text-xs font-medium text-white pb-2">No. WhatsApp</label>
        <div class="PhoneInput">
          
            <input
            type="number"
            id="nomor"
            autocomplete="off"
            name="whatsapp"
            placeholder="Contoh 08213456789"
            class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white"
            value=""
            id="phoneNumberInput"
        />

        </div>
        <span class="text-xxs italic">**Nomor ini akan dihubungi jika terjadi masalah</span>
        
    <p class="flex items-center gap-2 rounded-md bg-primary-5400 px-4 py-2.5 text-xs/6">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M12 16v-4"></path>
        <path d="M12 8h.01"></path>
    </svg>
    <span>Bukti transaksi akan kami kirim ke whatsapp yang kamu isi di atas.</span>
</p>
    </div>

</div>

                
                
                      <div class="inset-x-0 bottom-0 z-10  !mt-0 shad sticky bottom-0 rounded-t-lg pb-4 flex flex-col gap-4 bg-background">
                  <div class=" space-y-0">
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm  rounded-lg md:hidden initial-element" style="display: flex;">
                      <div class="flex w-full flex-col space-y-0">
                        <div class="rounded-md p-4">
                                 <div class="text-center">Belum ada item produk yang dipilih.</div>
                        </div>
                      </div>
                    </div>
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm text-secondary-foreground md:hidden selected-element " style="display: none;">
                      <div class="mb-1 aspect-square timmel-5">
                        <img alt="icon" sizes="100vw" src="{{ asset($kategori->thumbnail) }}" width="80" height="100" decoding="async" data-nimg="1" class="aspect-square timmel-5 rounded-lg object-cover" loading="lazy" style="color: transparent">
                      </div>
                      <div class="flex w-full flex-col space-y-1 ml-3">
                          
                        <div class="text-xs font-semibold cana select glowing-text">{{ $kategori->nama }}</div>
                        <div class="flex items-center gap-2 pt-0.5 font-semibold">
                            
                        <p class="text-xs font-semibold text-warning text-amber-300 selected-order"></p><span>-</span>
                            <div class="text-xs  select text-white" id="pesan"></div></div>
                        
                        <p class="text-xxs italic text-murky-300">**Waktu proses instan</p>
                        <div class="flex w-full items-center">
                          <p class="text-xs italic select"></p>
                        </div>
                      </div>
                    </div>
                    
                      <div class="mt-4"></div>
                    <div class="relative">
                      <button class="inline-flex items-center justify-center rounded-md bg-primary-5400 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-75 btn-order relative flex w-full gap-2 overflow-hidden" type="button" id="order-check">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-4 w-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <span>Pesan Sekarang!</span>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="mt-4 block rounded-xl bg-murky-800 shadow-2xl md:hidden">
                    <div class="flex border-b border-murky-600">
                        <div class="flex items-center justify-center rounded-tl-xl bg-gradient-to-b bg-primary-500  to-primary-600 px-3 py-2 text-xl font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4">
                                <path
                                    fill-rule="evenodd"
                                    d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                         <h3
            class="flex w-full items-center justify-between rounded-tr-xl bg-gradient-to-b from-murky-800 to-murky-800 px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">
            Ulasan</h3>
                    </div>
                    
                     <div class="flow-root p-6">
                      
                        @php
                        $ratings = DB::table('ratings')->where('kategori_id', $kategori->id)->get();
                    
                        $totalStars = 0;
                        $totalReviews = $ratings->count();
                        $positiveReviews = 0;
                    
                        foreach ($ratings as $rating) {
                            $totalStars += $rating->bintang;
                            if ($rating->bintang >= 4) {
                                $positiveReviews++;
                            }
                        }
                    
                        if ($totalReviews > 0) {
                            $averageRating = $totalStars / $totalReviews;
                            $satisfactionPercentage = ($positiveReviews / $totalReviews) * 100;
                        } else {
                            $averageRating = 0; 
                            $satisfactionPercentage = 0;
                        }
                        @endphp
                    
                        <div class="flex flex-col  overflow-hidden ">
                            <div class="mx-6 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-8 w-8 flex-shrink-0 text-yellow-400">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                </svg>
                                <div><span class="text-5xl text-besar">{{ number_format($averageRating, 1) }}</span> <span> / </span><span>5.0</span></div>
                            </div>
                            <div class="flex flex-col gap-1">
                               
                        <div class="mx-6 flex items-center justify-center text-xs font-bold">{{ number_format($satisfactionPercentage, 0) }}% pembeli merasa puas dengan produk ini.</div>
                        <div class="mx-6 flex items-center justify-center gap-2 text-xs">Dari {{ $totalReviews }} Ulasan.</div>
                            </div>
                        </div>
                        @php
                        $totalRatings = [
                            '5' => $ratings->where('bintang', 5)->count(),
                            '4' => $ratings->where('bintang', 4)->count(),
                            '3' => $ratings->where('bintang', 3)->count(),
                            '2' => $ratings->where('bintang', 2)->count(),
                            '1' => $ratings->where('bintang', 1)->count(),
                        ];
                        @endphp
                    
                    
                        <div class="flex flex-col  overflow-hidden pt-6">
                            @foreach($totalRatings as $rating => $count)
                            @php
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                            @endphp
                            <ul class="rating-list" style="list-style-type: none; padding-left: 0;">
                                <li class="rating-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                    <div class="rating-value" style="width: 30px; text-align: right; margin-right: 10px;">
                                        {{ $rating }}
                                    </div>
                                    <div class="star-rating" style="display: flex; align-items: center; margin-right: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="height: 20px; width: 20px; color: #ffc107;">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="bar" style="flex-grow: 1; height: 10px; background-color: #ddd; border-radius: 5px; overflow: hidden;">
                                        <div class="progress" style="height: 100%; background-color: #ffc107; border-radius: 5px; width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="count" style="width: 50px; margin-left: 0px; text-align: right;">{{ $count }}</div>
                                </li>
                            </ul>

                            @endforeach
                        </div>
                    
                        @if($ratings->isEmpty())
                        <div class="py-4">
                            <div class="rounded-md border-l-4 border-yellow-400 bg-yellow-100 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-yellow-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3"><p class="text-sm text-yellow-700">Belum ada ulasan dan penilaian.</p></div>
                                </div>
                            </div>
                        </div>
                        @else
                       
                <div class="mt-6"><p class="text-sm text-secondary-foreground">Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya tentang pengalamanmu.</p></div>
                         <hr>
                <div class="flow-root pt-5">
                    <div class="-my-6 divide-y">
                         @foreach($ratings->reverse()->take(5) as $rating)
                        <div class="py-3">
                            <div class="flex items-center">
                                <div class="w-full">
                                    <div class="flex items-start justify-between">
                                        @php
                                        $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                                        if(!$username && isset($rating->no_pembeli)) {
                                            $username = $rating->no_pembeli;
                                        }
                                        $usernameLength = strlen($username);
                                        $sensorLength = $usernameLength <= 5 ? 2 : 4;
                                        $start = floor(($usernameLength - $sensorLength) / 2);
                                        $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                                        @endphp
                                        <h4 class="mt-0.5 text-xs font-bold text-white">{{ $censoredUsername }}</h4>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $i <= $rating->bintang ? 'currentColor' : 'white' }}" aria-hidden="true" class="text-yellow-400 h-4 w-4 flex-shrink-0">
                                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="sr-only">{{ $rating->bintang }} dari 5 bintang</p>
                                </div>
                            </div>
                            <div class="flex w-full justify-between pt-1 text-xxs">
                                <span>{{ $rating->layanan }}</span>
                                <span>{{ $rating->created_at }}</span>
                            </div>
                            <div class="text-murky-20 mt-1 space-y-6 text-xs italic">“{{ $rating->comment }}”</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
               <div class="flex justify-end pt-5 mt-5">
                   
    <a
        class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent/75 hover:text-accent-foreground h-8 rounded-md px-4 bg-secondary/50 pr-3 flex items-center gap-2"
        type="button"
        href="/id/reviews"
        style="outline: none;"
    >
        <span>Lihat semua ulasan</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4">
            <path d="M5 12h14"></path>
            <path d="m12 5 7 7-7 7"></path>
        </svg>
    </a>
</div>

                    </div>
                </div>
            </ul>
        @endif
        
    @else
      <ul class="col-span-3 flex flex-col space-y-8 md:col-span-2">
                <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-input">
                         <input type="hidden" id="nominal">
                    <input type="hidden" id="metode">
                    <input type="hidden" id="ktg_tipe" value="{{ $kategori->tipe }}">
                   
                    
                     <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">1</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Masukkan Data Akun Kamu</h3>
                    </div>
                </div>
                    @php
                        if($kategori->field_2 !== null){
                            $field2Values = explode(',', (string) ($kategori->field_2 ?? ''));
                            $selectValue = isset($field2Values[2]) ? trim($field2Values[2]) : null;
                        }
                        
                            $fieldSelectTitle = explode(',', (string) ($kategori->field_select_title ?? ''));
                            $fieldSelect = explode(',', (string) ($kategori->field_select ?? ''));
                            $field1Values = explode(',', (string) ($kategori->field_1 ?? ''));
                        @endphp
                   @if($kategori->field_2 !== null)
                            <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div><label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                        <div class="flex flex-col items-start"><input class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                type="{{ $field1Values[2] }}" id="user_id" name="user_id" placeholder="{{ $field1Values[1] }}" /></div>
                    </div>
                                @if($selectValue == "select")
                                <div><label for="zone" class="block text-xs font-medium text-white pb-2"> {{ $field2Values[0] }}</label><select class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                            id="zone"><option value="">{{ $field2Values[1] }}</option> @foreach($fieldSelectTitle as $key => $fst)<option value="{{ $fieldSelect[$key] }}">{{ $fst }}</option>@endforeach</select></div>
                                @elseif($selectValue == "text" || $selectValue == "number" || $selectValue == "password")
                                 <div><label for="zone" class="block text-xs font-medium text-white pb-2">{{ $field2Values[0] }}</label>
                        <div class="flex flex-col items-start"><input class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                type="{{ $field2Values[2] }}" name="zone_id" id="zone" placeholder="{{ $field2Values[1] }}" /></div>
                    </div>
                    <div id="nickname-display" class="text-xs text-green-500 mt-1 font-bold"></div>
                                @endif
                            </div>
                        
                        @else
                           <div class="grid grid-cols-2 gap-4 p-4 sm:px-6 sm:pb-4">
                                <div>
                                    <label for="user_id" class="block text-xs font-medium text-white pb-2">{{ $field1Values[0] }}</label>
                                    <div class="flex flex-col items-start">
                                        <input
                                            class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                            type="{{ $field1Values[2] }}"
                                            id="user_id" name="user_id"
                                            placeholder="{{ $field1Values[1] }}"/> 
                                            <div id="nickname-display" class="text-xs text-green-500 mt-1 font-bold"></div>
                                    </div>
                                </div>
                            </div>
                        @endif

					     <div class="px-4 pb-4 text-[10px] sm:px-6 sm:pb-6">
                        <div class="flex items-center gap-2 rounded-md bg-primary-5400 px-4 py-3 text-xs/6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
                              <circle cx="12" cy="12" r="10"></circle>
                              <path d="M12 16v-4"></path>
                              <path d="M12 8h.01"></path>
                            </svg>
                            <div>
                              <p style="color: #000;">
                                <p><em>@safeHtml($kategori->deskripsi_field)</em></p>
                              </p>
                            </div>
                        </div>

                    </div>
                </div>
                    <!--end section input-->
                    
                        

                
              <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-nominal">
                 <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">2</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Pilih Nominal Topup</h3>
                    </div>
                </div>
              <div id="paketList" x-data="{ selectedPaket: 'all', selectedProduct: '' }" class="p-4 sm:p-6">
                  
                  <h3 class="font-semibold mt-4">📦 Pilih Paket</h3>
        <div class="scroll-container">
            <button @click="selectedPaket = 'all'" class="button-3d">🎮 Semua</button>
            @foreach($pakets as $paket)
            <button @click="selectedPaket = {{ $loop->index }}" class="button-3d">{{ $paket['nama'] }}</button>
            @endforeach
        </div>
        <div id="itemList" class="flex flex-col space-y-4 sm:p-1">
            <div x-show="selectedPaket === 'all'">
                @foreach($pakets as $paket) 
                <section>
                    <h3 class="font-semibold  mt-4">{{ $paket['nama'] }}</h3>
                    <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                        <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                            @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                            <div x-bind:class="{ 'ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                                <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                                @php
                                $currentDateTime = now();
                            @endphp
                            
                            <span class="flex flex-1">
                                <span class="flex flex-col justify-between">
                                    <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
                                    <div>
                                        @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                                            <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </span>
                            
                                @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                                    <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                                <div class="popular-tag-content"><img src="/assets/image/discc.gif" alt="discount" class="h-5 w-5 " style="filter: drop-shadow(rgba(255, 255, 255, 0.5) 0px 0px 0.75rem);" />
                                                    <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b"> PROMO</div>
                                                </div>
                                                <div class="popular-tag-overlay"></div>
                                            </div>
                                    <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                        <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
                                        <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                                            {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
                                        </div>
                                    </div>
                                @endif
                            </span>

                                @if($nom['product_logo'])
                                <div class="flex aspect-square w-8 items-center">
                                    <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                                </div>
                                @endif
                                <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endforeach
            </div>
            @foreach($pakets as $paket) 
            <section x-show="selectedPaket === {{ $loop->index }}" x-transition>
                <h3 class="font-semibold">{{ $paket['nama'] }}</h3>
                <div id="radiogroup-{{ $loop->index }}" role="radiogroup" aria-labelledby="label-{{ $loop->index }}">
                    <div id="specialList" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3" role="none">
                        @foreach(collect($paket['layanan'])->sortBy('harga') as $nom)
                        <div x-bind:class="{ 'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': selectedProduct === '{{ $nom['id'] }}', 'bg-murky-200': selectedProduct !== '{{ $nom['id'] }}' }" data-layanan="{{ $nom['layanan'] }}" class="relative flex product-list cursor-pointer rounded-xl border border-transparent bg-murky-200 p-2.5 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out" id="product-{{ $nom['id'] }}" product-id="{{ $nom['id'] }}" role="radio" aria-checked="false" name="nominal" value="{{ $nom['id'] }}" tabindex="0" aria-labelledby="label-{{ $nom['id'] }}" aria-describedby="description-{{ $nom['id'] }}" @click="selectedProduct = '{{ $nom['id'] }}'">
                            <input type="radio" name="nominal" value="{{ $nom['id'] }}" class="peer hidden" />
                          @php
    $currentDateTime = now();
@endphp

<span class="flex flex-1">
    <span class="flex flex-col justify-between">
        <span class="trunc block text-xs text-murky-800 font-semibold" id="namalayanan">{{ $nom['layanan'] }}</span>
        <div>
            @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga_flash_sale'], 0, ',', '.') }}</span>
                <span class="flex items-center text-xs font-semibold italic line-through decoration-[0.9px] text-murky-600 decoration-destructive">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @else
                <span class="mt-1 flex items-center text-xs font-semibold text-murky-600 harga">Rp&nbsp;{{ number_format($nom['harga'], 0, ',', '.') }}</span>
            @endif
        </div>
    </span>

    @if($nom['is_flash_sale'] == 1 && $nom['expired_flash_sale'] >= $currentDateTime)
        <div class="populaasdasdasdawrwr-t4124t3523ag-con42324124tainer3p423ath">
                                                <div class="popular-tag-content"><img src="/assets/image/discc.gif" alt="discount" class="h-5 w-5 " style="filter: drop-shadow(rgba(255, 255, 255, 0.5) 0px 0px 0.75rem);" />
                                                    <div class="rate asdasdwe_2353_Sdfsdccxxx_Xx3979b"> PROMO</div>
                                                </div>
                                                <div class="popular-tag-overlay"></div>
                                            </div>
        <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
            <div class="absolute top-0 left-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute bottom-0 right-0 bg-orange-700 h-2 w-2"></div>
            <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-foreground">
                {{ number_format(($nom['harga'] - $nom['harga_flash_sale']) / $nom['harga'] * 100, 0) }}% OFF
            </div>
        </div>
    @endif
</span>

                            @if($nom['product_logo'])
                            <div class="flex aspect-square w-8 items-center">
                                <img alt="{{ $nom['layanan'] }}" fetchpriority="high" width="300" height="300" decoding="async" data-nimg="1" class="object-contain object-right" sizes="80vh" src="{{ asset($nom['product_logo']) }}" style="color: transparent;" />
                            </div>
                            @endif
                            <div x-bind:class="{ 'block': selectedProduct === '{{ $nom['id'] }}', 'hidden': selectedProduct !== '{{ $nom['id'] }}' }"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endforeach
        </div>
    </div>
               </div>
               
             <div class="popup-structure popup-slide flex min-h-full items-center justify-center p-4 text-center sm:p-0" id="popupppp">
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative w-full transform overflow-hidden rounded-lg bg-murky-900 text-left shadow-xl transition-all sm:my-8 sm:max-w-3xl !rounded-2xl opacity-100 translate-y-0 sm:scale-100" id="headlessui-dialog-panel-weekly-diamond-pass" data-headlessui-state="open">
                <div class="absolute right-0 top-0 block pr-4 pt-4">
                   
                </div>
                <div class="w-full pb-4 flex flex-col items-center justify-center">
                    <h2 class="max-w-xl pt-1 text-center text-sm font-semibold text-white mt-4">WEEKLY DIAMOND PASS</h2>
                    <div class="object-center mt-4 flex items-center justify-center">
                        <img src="/assets/image/wdp.webp" class="object-center" style="color: transparent; width: 30%; height: auto;" alt="" />
                    </div>
                    <div class="w-full max-w-none prose prose-sm px-4 py-4 text-xs text-white flex items-center justify-center">
                        <div class="border-murky-600 prose prose-sm flex w-full flex-col rounded-xl border border-dashed px-4 py-3 text-xs text-foreground prose-p:my-0">
                            <div>
                                <p class="selectable-text copyable-text iq0m558w g0rxnol2" dir="ltr"><span class="selectable-text copyable-text">Informasi Penting Sebelum Pembelian Weekly Daily Pass</span></p>
                                <p class="selectable-text copyable-text iq0m558w g0rxnol2" dir="ltr">
                                    <span class="selectable-text copyable-text g33ro0j9 ag95hn57">1.</span><span class="selectable-text copyable-text"> Batas Maksimal Pembelian: Pembelian tidak diizinkan jika telah melebihi batas 70 hari.<br /></span>
                                    <span class="selectable-text copyable-text g33ro0j9 ag95hn57">2.</span><span class="selectable-text copyable-text"> Kuota Pembelian: Perhatikan kuota tersedia. Contoh: (5/10) berarti Anda dapat membeli 5 kali lagi.<br /></span>
                                    <span class="selectable-text copyable-text g33ro0j9 ag95hn57">3.</span>
                                    <span class="selectable-text copyable-text"> Bonus dari Pembelian: Setiap pembelian Weekly Diamond Pass menyelesaikan misi topup 100 dan langsung memberikan 80 Diamonds.<br /></span>
                                    <span class="selectable-text copyable-text g33ro0j9 ag95hn57">4.</span>
                                    <span class="selectable-text copyable-text"> Kebijakan Refund: Jika pembelian dilakukan setelah mencapai batas maksimal (0/10), uang akan dikembalikan 90% sebagai konsekuensi.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-center bg-secondary px-4 py-2 rounded-xl">
                        <button
                            class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-8 "
                            type="button"
                            name="popup"
                            id="closePopupButton" <!-- Add an ID to the button -->
                        >
                            OK, Saya Mengerti
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


            <div class="rounded-xl bg-murky-800 shadow-2xl" id="section-payment-channel">
              <div class="flex border-b border-murky-600">
                    <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                        <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">3</span></div>
                        <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Pilih Metode Pembayaran</h3>
                    </div>
                </div>
              
                 <div id="skeleton-loaderr" class="skeleton-loader grid grid-cols-1 gap-4 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 px-4 mt-4 py-4">
                
            </div>
              <dl id="paymentList" class="flex w-full flex-col space-y-4 p-4 sm:p-6 hidden" x-data="{ selected: null, paymentSelected: '' }">
                  
                <!--saldo1-->
            @if(Auth::check())
                @foreach($pay_method as $p) 
                    @if($p->tipe == 'SALDO')
                    <div x-bind:class="{ 'bg-white': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" class="relative flex cursor-pointer method-list rounded-md border border-transparent bg-murky-200 p-3 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out melpa-sabled disabled"
                        role="radio" aria-checked="false" method-id="{{$p->code}}" name="paymentMethod" @click="paymentSelected = '{{$p->code}}'">
                        <div class="flex items-center gap-2 max-w-xs"><input type="radio" id="method_92" name="paymentMethod" value="{{$p->code}}" class="peer hidden" /><label for="method_92"></label><img src="{{$p->image_url}}" alt="Coin" width="35" height="40" />
                            <div><span class="block text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{ $p->name }}</span>
                                <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{$p->code}}">Rp 0</p>
                            </div>
                        </div>
                        <div class="max-w-xs">
                            <div class="relative text-sm font-semibold text-murky-800 sm:text-base"></div>
                        </div>
                        <div class="flex aspect-square w-8 items-center">
                            <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                                <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                                <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                            </div>
                        </div>
                        <div class="popular-tag-container">
                            <div class="popular-tag-content">
                                <div class="rate">🔥LEBIH SIMPLE</div>
                            </div>
                            <div class="popular-tag-overlay"></div>
                        </div>
                    </div>
                    @endif 
                @endforeach
            
            @endif


                <!--saldo1-->
               
             
                  @foreach($pay_method as $p)
                   @if($p->isType('qris') && $p->isEnabled())
                  <div x-bind:class="{ 'bg-white bj-shadow': paymentSelected === '{{$p->code}}', 'bg-murky-200': paymentSelected !== '{{$p->code}}' }" class="relative flex cursor-pointer method-list rounded-md border border-transparent bg-murky-200 p-4 shadow-sm outline-none md:p-4 hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out melpa-sabled disabled" role="radio" aria-checked="false" method-id="{{$p->code}}" name="paymentMethod" @click="paymentSelected = '{{$p->code}}'">
                            <div class="flex items-center gap-2 max-w-xs">
                                <input type="radio" id="method_{{$p->id}}" name="paymentMethod" value="{{$p->code}}" class="peer hidden" />
                                <label for="method_{{$p->id}}"></label>
                                <img src="{{ $p->image_url }}" alt="qris" width="35" height="10" />
                                <div>
                                    <span class="block  text-xs font-semibold text-murky-800 sm:text-sm" id="headlessui-label-:riu:">{{$p->name}}</span>
                                    <p class="block text-xxs text-murky-800 sm:text-xs hargapembayaran" id="{{$p->code}}">Rp 0</p>
                                    
                                </div>
                            </div>
                            <div class="max-w-xs">
                                <div class="relative text-sm font-semibold text-murky-800 sm:text-base">
                                </div>
                            </div>
                            <div class="flex aspect-square w-8 items-center">
                                <div class="w-[4rem] absolute aspect-square -top-[9px] -right-[9px] overflow-hidden rounded-sm">
                                    <div class="absolute top-0 left-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute bottom-0 right-0 bg-orange-500 h-2 w-2"></div>
                                    <div class="absolute block w-square-diagonal py-1 text-center text-xxs font-semibold uppercase bottom-0 right-0 rotate-45 origin-bottom-right shadow-sm bg-orange-500 text-white">BEST PRICE</div>
                                </div>
                                <div class="popular-tag-container">
                            <div class="popular-tag-content">
                                <div class="rate">⚡LEBIH CEPAT</div>
                            </div>
                            <div class="popular-tag-overlay"></div>
                        </div>
                            </div>
                        </div>
                        
                        @endif
                                @endforeach
                
                
                <div class="flex w-full transform flex-col melpa-sabled disabled justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-1" type="button" @click="selected !== 3 ? selected = 3 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-1">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>E-Wallet</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 3 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700 " x-ref="container1" x-bind:style="selected == 3 ? 'max-height: ' + $refs.container1.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-1">
                        <div id="radiogroup-1" role="radiogroup" aria-labelledby="label-1">
                          <div id="eWalletList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none">
                              @foreach($pay_method as $p)
                                  @if($p->isType('e-walet') && $p->isEnabled())
                                    <!-- Active Payment Method -->
                                    <div 
                                      x-bind:class="{ 
                                        'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{ $p->code }}', 
                                        'bg-murky-200': paymentSelected !== '{{ $p->code }}' 
                                      }"
                                      class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-md border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out"
                                      id="radio-group-{{ $p->code }}"
                                      role="radio"
                                      aria-checked="false"
                                      method-id="{{ $p->code }}"
                                      name="paymentMethod"
                                      value="{{ $p->code }}"
                                      tabindex="0"
                                      aria-labelledby="label-{{ $p->code }}"
                                      aria-describedby="description-{{ $p->code }}"
                                      @click="paymentSelected = '{{ $p->code }}'"
                                    >
                                      <input 
                                        type="radio" 
                                        id="method_{{ $p->id }}" 
                                        name="paymentMethod" 
                                        value="{{ $p->code }}" 
                                        class="peer hidden" 
                                      />
                                      <label for="method_{{ $p->id }}"></label>
                                      <span class="flex w-full">
                                        <span class="flex w-full flex-col justify-between">
                                          <div>
                                            <span class="block text-xs font-semibold text-murky-800">
                                              {{ $p->name }}
                                            </span>
                                            <span class="mt-0 flex items-center text-xxs text-murky-600">{{ $p->keterangan }}</span>
                                            <hr>
                                          </div>
                                          <div class="flex w-full items-center justify-between">
                                            <div class="mt-1">
                                              <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800">
                                                <h6 class="hargapembayaran" id="{{ $p->code }}"></h6>
                                              </div>
                                            </div>
                                            <div class="relative aspect-[6/2] w-10">
                                              <img 
                                                src="{{ $p->image_url }}" 
                                                x-bind:class="{ 
                                                  'grayscale-0': paymentSelected === '{{ $p->code }}', 
                                                  'grayscale': paymentSelected !== '{{ $p->code }}' 
                                                }" 
                                                class="object-scale-down grayscale-0" 
                                                style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" 
                                              />
                                            </div>
                                          </div>
                                        </span>
                                      </span>
                                    </div>
                                
                                  @endif
                                @endforeach

                            </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo3" x-bind:style="selected == 3 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 3 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('e-walet')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
                
              
                <div class="flex w-full transform flex-col melpa-sabled disabled justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-2" type="button" @click="selected !== 5 ? selected = 5 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-2">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Virtual Account</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 5 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container2" x-bind:style="selected == 5 ? 'max-height: ' + $refs.container2.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-2">
                        <div id="radiogroup-2" role="radiogroup" aria-labelledby="label-2">
                          <div id="virtualAccountList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> 
                          
                      
                              @foreach($pay_method as $p)
                                  @if($p->isType('virtual-account') && $p->isEnabled())
                                    <!-- Active Payment Method -->
                                    <div 
                                      x-bind:class="{ 
                                        'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{ $p->code }}', 
                                        'bg-murky-200': paymentSelected !== '{{ $p->code }}' 
                                      }"
                                      class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-md border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out"
                                      id="radio-group-{{ $p->code }}"
                                      role="radio"
                                      aria-checked="false"
                                      method-id="{{ $p->code }}"
                                      name="paymentMethod"
                                      value="{{ $p->code }}"
                                      tabindex="0"
                                      aria-labelledby="label-{{ $p->code }}"
                                      aria-describedby="description-{{ $p->code }}"
                                      @click="paymentSelected = '{{ $p->code }}'"
                                    >
                                      <input 
                                        type="radio" 
                                        id="method_{{ $p->id }}" 
                                        name="paymentMethod" 
                                        value="{{ $p->code }}" 
                                        class="peer hidden" 
                                      />
                                      <label for="method_{{ $p->id }}"></label>
                                      <span class="flex w-full">
                                        <span class="flex w-full flex-col justify-between">
                                          <div>
                                            <span class="block text-xs font-semibold text-murky-800">
                                              {{ $p->name }}
                                            </span>
                                            <span class="mt-0 flex items-center text-xxs text-murky-600">{{ $p->keterangan }}</span>
                                            <hr>
                                          </div>
                                          <div class="flex w-full items-center justify-between">
                                            <div class="mt-1">
                                              <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800">
                                                <h6 class="hargapembayaran" id="{{ $p->code }}"></h6>
                                              </div>
                                            </div>
                                            <div class="relative aspect-[6/2] w-10">
                                              <img 
                                                src="{{ $p->image_url }}" 
                                                x-bind:class="{ 
                                                  'grayscale-0': paymentSelected === '{{ $p->code }}', 
                                                  'grayscale': paymentSelected !== '{{ $p->code }}' 
                                                }" 
                                                class="object-scale-down grayscale-0" 
                                                style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" 
                                              />
                                            </div>
                                          </div>
                                        </span>
                                      </span>
                                    </div>
                                
                                  @endif
                                @endforeach
                            
                            </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo5" x-bind:style="selected == 5 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 5 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('virtual-account')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
            
                
                
                <div class="flex w-full transform flex-col melpa-sabled disabled justify-between rounded-xl bg-murky-600 text-left text-sm font-medium text-white duration-300 focus:outline-none accordion-header">
                  <dt>
                    <button class="w-full disabled:opacity-75" id="disclosure-button-3" type="button" @click="selected !== 4 ? selected = 4 : selected = null" aria-expanded="false" aria-controls="disclosure-panel-3">
                      <div class="flex w-full justify-between px-4 py-2">
                        <span class="transform text-base font-medium leading-7 duration-300">
                          <div>Convenience Store</div>
                        </span>
                        <span class="ml-6 flex h-7 items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 transform duration-300" x-bind:class="selected == 4 ? 'rotate-180' : 'rotate-0'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                          </svg>
                        </span>
                      </div>
                    </button>
                    <div class="relative overflow-hidden transition-all max-h-0 duration-700" x-ref="container3" x-bind:style="selected == 4 ? 'max-height: ' + $refs.container3.scrollHeight + 'px' : 'max-height: 0'" style="max-height: 239px;">
                      <div class="px-4 pt-2 pb-4 text-sm text-murky-300" id="disclosure-panel-3">
                        <div id="radiogroup-3" role="radiogroup" aria-labelledby="label-3">
                          <div id="convenienceStoreList" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-2 xl:grid-cols-3" role="none"> 
                          
                               @foreach($pay_method as $p)
                                  @if($p->isType('convenience-store') && $p->isEnabled())
                                    <!-- Active Payment Method -->
                                    <div 
                                      x-bind:class="{ 
                                        'bg-white bj-shadow ring-2 ring-primary-500 ring-offset-2 ring-offset-murky-800 duration-300 ease-in-out': paymentSelected === '{{ $p->code }}', 
                                        'bg-murky-200': paymentSelected !== '{{ $p->code }}' 
                                      }"
                                      class="method-list relative flex cursor-pointer overflow-hidden payment-method rounded-md border border-transparent p-2.5 shadow-sm outline-none md:p-4 bg-white bj-shadow hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 duration-300 ease-in-out"
                                      id="radio-group-{{ $p->code }}"
                                      role="radio"
                                      aria-checked="false"
                                      method-id="{{ $p->code }}"
                                      name="paymentMethod"
                                      value="{{ $p->code }}"
                                      tabindex="0"
                                      aria-labelledby="label-{{ $p->code }}"
                                      aria-describedby="description-{{ $p->code }}"
                                      @click="paymentSelected = '{{ $p->code }}'"
                                    >
                                      <input 
                                        type="radio" 
                                        id="method_{{ $p->id }}" 
                                        name="paymentMethod" 
                                        value="{{ $p->code }}" 
                                        class="peer hidden" 
                                      />
                                      <label for="method_{{ $p->id }}"></label>
                                      <span class="flex w-full">
                                        <span class="flex w-full flex-col justify-between">
                                          <div>
                                            <span class="block text-xs font-semibold text-murky-800">
                                              {{ $p->name }}
                                            </span>
                                            <span class="mt-0 flex items-center text-xxs text-murky-600">{{ $p->keterangan }}</span>
                                            <hr>
                                          </div>
                                          <div class="flex w-full items-center justify-between">
                                            <div class="mt-1">
                                              <div class="relative z-30 mt-0 text-xs font-semibold leading-4 text-murky-800">
                                                <h6 class="hargapembayaran" id="{{ $p->code }}"></h6>
                                              </div>
                                            </div>
                                            <div class="relative aspect-[6/2] w-10">
                                              <img 
                                                src="{{ $p->image_url }}" 
                                                x-bind:class="{ 
                                                  'grayscale-0': paymentSelected === '{{ $p->code }}', 
                                                  'grayscale': paymentSelected !== '{{ $p->code }}' 
                                                }" 
                                                class="object-scale-down grayscale-0" 
                                                style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" 
                                              />
                                            </div>
                                          </div>
                                        </span>
                                      </span>
                                    </div>
                                
                                 @endif
                                @endforeach
                            
                            
                            </div>
                        </div>
                      </div>
                    </div>
                    <div class="relative overflow-hidden transition-all max-h-0 w-full rounded-b-md bg-murky-300" x-ref="logo4" x-bind:style="selected == 4 ? 'max-height: 0' : 'max-height: 30px'" x-bind:class="selected == 4 ? 'px-0 py-0' : 'px-4 pt-2.5 pb-5'">
                      <div class="flex justify-end gap-x-2"> @foreach($pay_method as $p) @if($p->isType('convenience-store')) <div class="relative aspect-[6/2] w-10">
                          <img class="object-scale-down" src="{{$p->image_url}}" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" alt="{{$p->name}}" />
                        </div> @endif @endforeach </div>
                    </div>
                  </dt>
                </div>
              </dl>
            </div>
                
        
                 <div class="rounded-xl bg-murky-800 shadow-2xl" id="promooo">
                  
                   <div class="flex border-b border-murky-600">
                            <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                                <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">4</span></div>
                                <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Kode Promo</h3>
                            </div>
                        </div>
                        <div class="p-4 sm:px-6 sm:pb-6"><label for="voucher" class="block text-xs font-medium text-white pb-2">Kode Promo</label>
                            <div class="flex items-center space-x-2">
                                <div class="grow">
                                    <div class="flex flex-col items-start"><input class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                            type="text" id="voucher" name="voucher" placeholder="Masukkan Kode Promo Anda" required/></div>
                                </div><button type="button" id="btn-check" class="flex items-center justify-center rounded-md bg-primary-5400 py-2 px-4 text-xs font-semibold text-white hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-75"> Gunakan </button></div>
                            <div
                                class="pt-2 text-xs text-red-500"></div>
                    </div>
                </div>
                
                
            
                
                
                
                
                
                
                
                
                
                
     <div class="rounded-xl bg-murky-800 shadow-2xl jumpToWhatsApp" id="whatsappp">
 
        <div class="flex border-b border-murky-600">
                            <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                                <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><span class="font-bold text-xl italic">5</span></div>
                                <h3 class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Nomor WhatsApp</h3>
                            </div>
                        </div>
                        <div class="p-4 sm:px-6"><label for="nomor" class="block text-xs font-medium text-white pb-2">Nomor WhatsApp</label><input type="number" id="nomor" autocomplete="off" name="whatsapp" placeholder="Masukkan Nomor WhatsApp Anda" class="relative block w-full appearance-none border border-murky-600 bg-melpa-800 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 rounded-md"
                                value="" id="phoneNumberInput" /><span class="text-xxs italic">**Contoh : 0821xxxxxxxx</span>
                            <p class="flex items-center gap-2 rounded-md bg-primary-5400 px-4 py-3 text-xs/6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4">
                              <circle cx="12" cy="12" r="10"></circle>
                              <path d="M12 16v-4"></path>
                              <path d="M12 8h.01"></path>
                            </svg>
                            <span>Bukti transaksi akan kami kirim ke whatsapp yang kamu isi di atas.</span>
                        </p>
                    </div>

                </div>

                
                
                
                
                 <div class="sticky inset-x-0 bottom-0 z-10 shad sticky bottom-0 rounded-md pb-4 flex flex-col gap-4 ">
                  <div class=" space-y-0">
                    <div class="rounded-lg border border-dashed bg-secondary p-2 text-sm rounded-md md:hidden initial-element" style="display: flex;">
                                <div class="flex w-full flex-col space-y-0">
                                    <div class="rounded-md p-4">
                                        <div class="text-center">Belum ada item produk yang dipilih.</div>
                                    </div>
                                </div>
                            </div>
                            
                    <div class="rounded-lg border border-dashed bg-secondary p-2 rounded-md text-sm text-secondary-foreground md:hidden selected-element " style="display: none;">
                                <div class="mb-1 aspect-square timmel-5"><img alt="icon" sizes="100vw" src="{{ asset($kategori->thumbnail) }}" width="80" height="100" decoding="async" data-nimg="1" class="aspect-square timmel-5 rounded-lg object-cover" loading="lazy"
                                        style="color: transparent"></div>
                                <div class="flex w-full flex-col space-y-1 ml-3">
                                    <div class="text-xs font-semibold selected-order">{{ $kategori->nama }}</div>
                                    <div class="flex items-center gap-2 pt-0.5 font-semibold">
                                        <p class="text-xs font-semibold text-warning text-amber-300 selected-order"></p><span>-</span>
                                        <div class="text-xs select text-white" id="pesan"></div>
                                    </div>
                                    <p class="text-xxs italic">**Waktu proses instan</p>
                                    <div class="flex w-full items-center">
                                        <p class="text-xs italic select"></p>
                                    </div>
                                </div>
                            </div>
                    
                      <div class="mt-4"></div>
                    <div class="relative">
                      <button class="inline-flex items-center justify-center rounded-md bg-primary-5400 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-75 btn-order relative flex w-full gap-2 overflow-hidden" type="button" id="order-check">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag h-4 w-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <span>Pesan Sekarang!</span>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="mt-4 block rounded-xl bg-murky-800 shadow-2xl md:hidden">
                    <div class="flex border-b border-murky-600">
                            <div class="flex flex-row items-center gap-1 bg-[#ffc007] text-darkColor rounded-md">
                                <div class="items-center justify-start flex bg-gradient-to-b from-murky-800 to-murky-800 clip-path-number p-4 h-12 w-16" style="border-top-left-radius: 12px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" ></path></svg></div>
                                <h3
                                    class="px-2 py-2 text-base font-semibold leading-6 text-white sm:px-4">Ulasan</h3>
                            </div>
                        </div>
                    
                     <div class="flow-root p-6">
                      
                        @php
                        $ratings = DB::table('ratings')->where('kategori_id', $kategori->id)->get();
                    
                        $totalStars = 0;
                        $totalReviews = $ratings->count();
                        $positiveReviews = 0;
                    
                        foreach ($ratings as $rating) {
                            $totalStars += $rating->bintang;
                            if ($rating->bintang >= 4) {
                                $positiveReviews++;
                            }
                        }
                    
                        if ($totalReviews > 0) {
                            $averageRating = $totalStars / $totalReviews;
                            $satisfactionPercentage = ($positiveReviews / $totalReviews) * 100;
                        } else {
                            $averageRating = 0; 
                            $satisfactionPercentage = 0;
                        }
                        @endphp
                    
                        <div class="flex flex-col  overflow-hidden ">
                            <div class="mx-6 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-8 w-8 flex-shrink-0 text-yellow-400">
                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                </svg>
                                <div><span class="text-5xl text-besar">{{ number_format($averageRating, 1) }}</span> <span> / </span><span>5.0</span></div>
                            </div>
                            <div class="flex flex-col gap-1">
                               
                        <div class="mx-6 flex items-center justify-center text-xs font-bold">{{ number_format($satisfactionPercentage, 0) }}% pembeli merasa puas dengan produk ini.</div>
                        <div class="mx-6 flex items-center justify-center gap-2 text-xs">Dari {{ $totalReviews }} Ulasan.</div>
                            </div>
                        </div>
                        @php
                        $totalRatings = [
                            '5' => $ratings->where('bintang', 5)->count(),
                            '4' => $ratings->where('bintang', 4)->count(),
                            '3' => $ratings->where('bintang', 3)->count(),
                            '2' => $ratings->where('bintang', 2)->count(),
                            '1' => $ratings->where('bintang', 1)->count(),
                        ];
                        @endphp
                    
                    
                        <div class="flex flex-col  overflow-hidden pt-6">
                            @foreach($totalRatings as $rating => $count)
                            @php
                            $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                            @endphp
                            <ul class="rating-list" style="list-style-type: none; padding-left: 0;">
                                <li class="rating-item" style="display: flex; align-items: center; margin-bottom: 5px;">
                                    <div class="rating-value" style="width: 30px; text-align: right; margin-right: 10px;">
                                        {{ $rating }}
                                    </div>
                                    <div class="star-rating" style="display: flex; align-items: center; margin-right: 10px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="height: 20px; width: 20px; color: #ffc107;">
                                            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="bar" style="flex-grow: 1; height: 10px; background-color: #ddd; border-radius: 5px; overflow: hidden;">
                                        <div class="progress" style="height: 100%; background-color: #ffc107; border-radius: 5px; width: {{ $percentage }}%;"></div>
                                    </div>
                                    <div class="count" style="width: 50px; margin-left: 0px; text-align: right;">{{ $count }}</div>
                                </li>
                            </ul>

                            @endforeach
                        </div>
                    
                        @if($ratings->isEmpty())
                        <div class="py-4">
                            <div class="rounded-md border-l-4 border-yellow-400 bg-yellow-100 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-yellow-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3"><p class="text-sm text-yellow-700">Belum ada ulasan dan penilaian.</p></div>
                                </div>
                            </div>
                        </div>
                        @else
                       
                <div class="mt-6"><p class="text-sm text-secondary-foreground">Apakah kamu menyukai produk ini? Beri tahu kami dan calon pembeli lainnya tentang pengalamanmu.</p></div>
                         <hr>
                <div class="flow-root pt-5">
                    <div class="-my-6 divide-y">
                         @foreach($ratings->reverse()->take(5) as $rating)
                        <div class="py-3">
                            <div class="flex items-center">
                                <div class="w-full">
                                    <div class="flex items-start justify-between">
                                        @php
                                        $username = $rating->username ?? $rating->no_pembeli ?? 'Guest';
                                        if(!$username && isset($rating->no_pembeli)) {
                                            $username = $rating->no_pembeli;
                                        }
                                        $usernameLength = strlen($username);
                                        $sensorLength = $usernameLength <= 5 ? 2 : 4;
                                        $start = floor(($usernameLength - $sensorLength) / 2);
                                        $censoredUsername = substr_replace($username, str_repeat('*', $sensorLength), $start, $sensorLength);
                                        @endphp
                                        <h4 class="mt-0.5 text-xs font-bold text-white">{{ $censoredUsername }}</h4>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $i <= $rating->bintang ? 'currentColor' : 'white' }}" aria-hidden="true" class="text-yellow-400 h-4 w-4 flex-shrink-0">
                                                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="sr-only">{{ $rating->bintang }} dari 5 bintang</p>
                                </div>
                            </div>
                            <div class="flex w-full justify-between pt-1 text-xxs">
                                <span>{{ $rating->layanan }}</span>
                                <span>{{ $rating->created_at }}</span>
                            </div>
                            <div class="text-murky-20 mt-1 space-y-6 text-xs italic">“{{ $rating->comment }}”</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
               <div class="flex justify-end pt-5 mt-5">
                   
                        <a
                            class="inline-flex items-center justify-center whitespace-nowrap text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent/75 hover:text-accent-foreground h-8 rounded-md px-4 bg-secondary/50 pr-3 flex items-center gap-2"
                            type="button"
                            href="/id/reviews"
                            style="outline: none;"
                        >
                            <span>Lihat semua ulasan</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right h-4 w-4">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                    </div>
                </div>
            </ul>
        @endif
          </div>
             </div>
            </div>
          </ul>
        </div>
      </div>
      <div id="react-notif"></div>
        <style>
            .disabled {
                pointer-events: none;
                opacity: 0.5;
                color: gray;
            }
        </style>
         <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            var closePopupButtonId = "closePopupButton";
            var popupStructureClass = ".popup-structure";
            var specialListId = "specialList";
        </script>
        <script src="{{ asset('/assets/js/kb2aiuhdoiaujsd.js') }}"></script>
        <script src="{{ asset('/assets/js/kb2asidoaiwusave.js') }}"></script>
        <script>
            window.csrfToken = "{{ csrf_token() }}";
            window.kategoriKode = "{{ $kategori->kode }}";
            window.routes = {
                confirmationPrice: "{{ route('ajax.price') }}",
                confirmationUrl: "{{ route('ajax.confirmation') }}",
                checkAccount: "{{ route('ajax.check-account') }}",
                orderedUrl: "{{ route('ordered') }}",
                checkVoucher: "{{ route('check.voucher') }}"
            };
            @auth
            window.userPointBalance = {{ Auth::user()->point_balance ?? 0 }};
            @else
            window.userPointBalance = 0;
            @endauth
        </script>
        <script src="{{ asset('/assets/js/newkbrorder.js') }}?v={{ time() }}"></script>

<!-- ===== POINT WIDGET SCRIPT ===== -->
<script>
(function() {
    // Inject point widget CSS
    var style = document.createElement('style');
    style.textContent = `
        .point-widget {
            margin: 16px 0;
            transition: all 0.3s ease;
        }
        .pw-header-container {
            display: flex;
            border-bottom: 1px solid #4b5563; /* border-murky-600 */
        }
        .pw-header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to bottom, var(--warna_3), var(--warna_4)); /* from-primary-400 to-primary-600 */
            padding: 8px 12px;
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
            border-top-left-radius: 0.75rem;
        }
        .pw-header-title {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            background: var(--warna_4); /* from-murky-800 */
            padding: 8px 16px;
            font-size: 1rem;
            line-height: 1.5rem;
            font-weight: 600;
            color: #fff;
            border-top-right-radius: 0.75rem;
        }
        .pw-body {
            padding: 16px 24px;
            background-color: var(--warna_4);
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }
        .pw-balance {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #e5e7eb;
            margin-bottom: 16px;
        }
        .pw-balance strong {
            color: #fff;
            font-weight: 700;
        }
        .pw-slider-container {
            padding: 0;
        }
        .pw-slider {
            width: 100%;
            height: 6px;
            border-radius: 4px;
            background: #374151;
            outline: none;
            -webkit-appearance: none;
            cursor: pointer;
        }
        .pw-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #f97316;
            cursor: pointer;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
            border: 2px solid #fff;
            transition: transform 0.1s;
        }
        .pw-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }
        .pw-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #f97316;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
        }
        .pw-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-top: 16px;
            color: #d1d5db;
        }
        .pw-discount {
            color: #4ade80;
            font-weight: 700;
        }
        .pw-limit {
            text-align: left;
            color: #9ca3af;
            font-size: 11px;
            margin-top: 8px;
            font-style: italic;
        }
    `;
    document.head.appendChild(style);

    // Inject point widget HTML into page
    var widgetHtml = `
        <div class="point-widget rounded-xl shadow-2xl">
            <div class="pw-header-container">
                <div class="pw-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/></svg>
                </div>
                <h3 class="pw-header-title">Gunakan Poin Kamu</h3>
            </div>
            <div class="pw-body">
                <div class="pw-balance">
                    <span>Saldo Aktif:</span>
                    <span><strong class="pw-bal">0</strong> Poin (<span class="pw-bal-rp font-medium text-warning">Rp 0</span>)</span>
                </div>
                <div class="pw-slider-container">
                    <input type="range" class="pw-slider" min="0" max="{{ Auth::user()->point_balance ?? 0 }}" value="0" step="1">
                </div>
                <div class="pw-info">
                    <span>Poin Dipakai: <strong class="pw-use text-white">0</strong></span>
                    <span class="pw-discount">Hemat: <strong class="pw-save">Rp 0</strong></span>
                </div>
                <div class="pw-limit pw-limit-text"></div>
                <input type="hidden" name="use_point" class="pw-input" value="0">
            </div>
        </div>
    `;

    // Append widget to section-nominal or payment area
    function renderPointWidget() {
        console.log("[Point Widget] renderPointWidget dipanggil. Saldo:", window.userPointBalance);
        
        var whatsappSection = document.querySelectorAll('#whatsappp');
        console.log("[Point Widget] Ditemukan " + whatsappSection.length + " elemen #whatsappp");
        
        whatsappSection.forEach(function(wa) {
            if (window.userPointBalance > 0) {
                // Ensure it's not appended multiple times
                if (!wa.previousElementSibling || !wa.previousElementSibling.classList.contains('point-widget')) {
                    console.log("[Point Widget] Meng-append widget html sebelum #whatsappp.");
                    wa.insertAdjacentHTML('beforebegin', widgetHtml);
                } else {
                    console.log("[Point Widget] Widget sudah exist di DOM.");
                }
            } else {
                console.log("[Point Widget] User tidak memiliki point, widget dilewati.");
            }
        });
        
        if (window.userPointBalance > 0) {
            initPointWidget();
        }
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            renderPointWidget();
        });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderPointWidget);
    } else {
        renderPointWidget();
    }

    window.lastBaseHarga = 0;
    window.lastMethods = null;

    function initPointWidget() {
        function formatRp(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        document.querySelectorAll('.point-widget').forEach(function(widget) {
            var slider = widget.querySelector('.pw-slider');
            var balEl = widget.querySelector('.pw-bal');
            var balRpEl = widget.querySelector('.pw-bal-rp');
            var useEl = widget.querySelector('.pw-use');
            var saveEl = widget.querySelector('.pw-save');
            var hiddenInput = widget.querySelector('.pw-input');

            if (balEl) balEl.textContent = window.userPointBalance.toLocaleString('id-ID');

            if (slider) {
                slider.addEventListener('input', function() {
                    var pointValue = parseInt(slider.getAttribute('data-point-value') || 100);
                    var v = parseInt(slider.value);
                    if (useEl) useEl.textContent = v.toLocaleString('id-ID');
                    if (saveEl) saveEl.textContent = formatRp(v * pointValue);
                    if (hiddenInput) hiddenInput.value = v;

                    // Sync the other slider
                    document.querySelectorAll('.point-widget').forEach(other => {
                        if (other !== widget) {
                            var otherSlider = other.querySelector('.pw-slider');
                            if (otherSlider && otherSlider.value !== slider.value) {
                                otherSlider.value = slider.value;
                                if (other.querySelector('.pw-use')) other.querySelector('.pw-use').textContent = v.toLocaleString('id-ID');
                                if (other.querySelector('.pw-save')) other.querySelector('.pw-save').textContent = formatRp(v * pointValue);
                                if (other.querySelector('.pw-input')) other.querySelector('.pw-input').value = v;
                            }
                        }
                    });

                    if (typeof window.refreshOrderPrice === 'function') {
                        window.refreshOrderPrice();
                    } else if (typeof updatePrice === 'function') {
                        var currentQty = Math.max(1, parseInt($("#qty").val() || 1));
                        updatePrice(currentQty);
                    }
                });
            }
        });

        // Called when price AJAX returns point_info
        window.updatePointWidget = function(pointInfo) {
            var widgets = document.querySelectorAll('.point-widget');
            
            var pointValue = pointInfo && pointInfo.point_value ? pointInfo.point_value : 100;
            // Limit the max points that can be chosen to the actual points the user has
            // Or the limit set by the server (whichever is smaller)
            var maxAllowedByServer = pointInfo && pointInfo.max_points ? pointInfo.max_points : 0;
            var maxPoints = Math.min(maxAllowedByServer, window.userPointBalance);
            
            var balance = window.userPointBalance;

            widgets.forEach(function(widget) {
                var slider = widget.querySelector('.pw-slider');
                var balRpEl = widget.querySelector('.pw-bal-rp');
                var limitEl = widget.querySelector('.pw-limit');
                var useEl = widget.querySelector('.pw-use');
                var saveEl = widget.querySelector('.pw-save');
                var hiddenInput = widget.querySelector('.pw-input');

                if (balRpEl) balRpEl.textContent = formatRp(balance * pointValue);
                
                if (slider) {
                    slider.max = maxPoints;
                    var current = Math.min(parseInt(slider.value || 0), maxPoints);
                    slider.value = current;
                    
                    if (useEl) useEl.textContent = current.toLocaleString('id-ID');
                    if (saveEl) saveEl.textContent = formatRp(current * pointValue);
                    if (hiddenInput) hiddenInput.value = current;
                }
                
                if (limitEl) limitEl.textContent = '**Maks. penggunaan ' + maxPoints.toLocaleString('id-ID') + ' poin';
            });
        };
    }

    // Intercept AJAX responses from product-list click to update widget
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxSuccess(function(event, xhr, settings, data) {
            if (settings.url && settings.url.includes('ajax/price')) {
                if (data) {
                    if (data.harga !== undefined && data.methods !== undefined) {
                        window.lastBaseHarga = data.harga;
                        window.lastMethods = data.methods;
                    }
                    if (data.point_info && typeof window.updatePointWidget === 'function') {
                        document.querySelectorAll('.pw-slider').forEach(function(slider) {
                            slider.setAttribute('data-point-value', data.point_info.point_value);
                        });
                        window.updatePointWidget(data.point_info);
                    }
                }
            }
        });
    }
})();
</script>

@if(in_array($kategori->kode, ['mobile-legends']))
<script type="text/javascript">document.addEventListener("DOMContentLoaded",(function(){let e=document.getElementById("closePopupButton"),t=document.querySelector(".popup-structure");e.addEventListener("click",(function(){t.style.display="none",localStorage.setItem("hidePopup","true")})),"true"===localStorage.getItem("hidePopup")&&(t.style.display="none"),document.getElementById("specialList").addEventListener("click",(function(e){let n=e.target.closest(".product-list");if(n){n.getAttribute("data-layanan").toLowerCase().includes("weekly diamond pass")&&(t.style.display="block")}}))})),document.addEventListener("DOMContentLoaded",(function(){let e=document.querySelectorAll(".popup-slide"),t=!1;e.length>0&&(e[0].classList.add("show"),t=!0),document.addEventListener("click",(function(n){Array.from(e).some((e=>e.contains(n.target)))||(t=!0)}))}));</script>
@endif
@include('../footer')
@push('custom_script')
@if(in_array($kategori->tipe, ['joki', 'jokigendong' ,'vilogml' ]))
<script>
const PAYMENT_FEES = {
    'DANA': { percentage: 0.3, fixed: 0 },
    'SHOPEEPAY': { percentage: 0.025, fixed: 0 },
    'OVOPUSH': { percentage: 0.025, fixed: 0 },
    'ASTRAPAY': { percentage: 0.025, fixed: 0 },
    'LINKAJA': { percentage: 0.03, fixed: 0 },
    'GOPAY': { percentage: 0.03, fixed: 0 },
    'QRIS': { percentage: 0.007, fixed: 100 },
    'QRISREALTIME': { percentage: 0.017, fixed: 0 },
    'QRIS_CUSTOM': { percentage: 0.027, fixed: 0 },
    'BCAVA': { percentage: 0, fixed: 4200 },
    'BNIVA': { percentage: 0, fixed: 3500 },
    'MANDIRIVA': { percentage: 0, fixed: 3500 },
    'BSIVA': { percentage: 0, fixed: 3500 },
    'BNCVA': { percentage: 0, fixed: 3000 },
    'PERMATAVA': { percentage: 0, fixed: 2000 },
    'CIMBVA': { percentage: 0, fixed: 2500 },
    'DANAMONVA': { percentage: 0, fixed: 2500 },
    'ALFAMART': { percentage: 0, fixed: 3000 },
    'INDOMARET': { percentage: 0, fixed: 3000 },
    'ALFAMIDI': { percentage: 0, fixed: 3000 }
};

function calculatePrice(basePrice, paymentMethod, pointDiscount = 0) {
    if (!PAYMENT_FEES[paymentMethod]) {
        return Math.max(1000, basePrice - pointDiscount);
    }

    const fee = PAYMENT_FEES[paymentMethod];
    const percentageFee = basePrice * (fee.percentage || 0);
    const fixedFee = fee.fixed || 0;

    return Math.max(1000, basePrice + percentageFee + fixedFee - pointDiscount);
}

function updatePrice(qty) {
    qty = Math.max(1, qty);
    const productId = $(".product-list.active").attr("product-id");
    $("#nominal").val(productId);

    $.ajax({
        url: "{{ route('ajax.price') }}",
        dataType: "json",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            nominal: productId,
            qty: qty,
            ktg_tipe: $("#ktg_tipe").val(),
            voucher: $("#voucher").val(),
            use_point: parseInt(document.querySelector('.pw-input')?.value || 0),
            payment_method: $("input[name='paymentMethod']:checked").val()
        },
        success: function(response) {
            const basePrice = response.harga;
            const paymentMethod = $("input[name='paymentMethod']:checked").val();
            const pointDiscount = parseFloat(response.point_discount || 0);
            let finalPrice = calculatePrice(basePrice, paymentMethod, pointDiscount);
            
            // Update UI
            $(".text-xs.font-semibold.text-warning.selected-order").text(formatToRupiah(finalPrice));
            updateQtyDisplay(qty);
            
            // Update payment method prices
            updatePaymentMethodPrices(basePrice, pointDiscount);
        }
    });
}

function updatePaymentMethodPrices(basePrice, pointDiscount = 0) {
    $('.method-list').each(function() {
        const methodCode = $(this).attr('method-id');
        let finalPrice = calculatePrice(basePrice, methodCode, pointDiscount);
        $(this).find('.hargapembayaran').text(formatToRupiah(finalPrice));
    });
}

// Event listeners
$('.payment-method').on('click', function() {
    // Re-trigger price update to recalculate everything properly include points
    var currentQty = Math.max(1, parseInt($("#qty").val() || 1));
    updatePrice(currentQty);
});

function updateQtyDisplay(t) {
    $("#qty-display").text(`{{ $kategori->nama }} x ${t} Qty`);
}

$(".product-list").click(function() {
    let t = $(this).attr("product-id");
    $(".product-list").removeClass("active");
    $(this).addClass("active");
    $("#nominal").val(t);
    $.ajax({
        url: "{{ route('ajax.price') }}",
        dataType: "json",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            nominal: t,
            qty: $("#qty").val(),
            ktg_tipe: $("#ktg_tipe").val(),
            voucher: $("#voucher").val(),
            use_point: parseInt(document.querySelector('.pw-input')?.value || 0),
            payment_method: $("input[name='paymentMethod']:checked").val()
        },
        success: function(t) {
            var a = Math.max(1, parseInt($("#qty").val() || 1));
            const basePrice = t.harga;
            const paymentMethod = $("input[name='paymentMethod']:checked").val();
            const pointDiscount = parseFloat(t.point_discount || 0);
            let finalPrice = calculatePrice(basePrice, paymentMethod, pointDiscount);

            $(".text-xs.font-semibold.text-warning.selected-order").text(formatToRupiah(finalPrice));
            updateQtyDisplay(a);
            updatePaymentMethodPrices(basePrice, pointDiscount);
        }
    });
});

$("#incrementBtn").on("click", function() {
    var t = Math.max(1, parseInt($("#qty").val()));
    t < 30 && (t++, $("#qty").val(t), updatePrice(t), updateQtyDisplay(t));
});

$("#decrementBtn").on("click", function() {
    var t = Math.max(1, parseInt($("#qty").val()));
    t > 1 && (t--, $("#qty").val(t), updatePrice(t), updateQtyDisplay(t));
});

updateQtyDisplay(Math.max(1, parseInt($("#qty").val())));
</script>
@endif
@endpush
@endsection
