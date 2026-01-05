<div class='mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-700'>
    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' aria-hidden='true' class='h-6 w-6 text-emerald-500'>
        <path stroke-linecap='round' stroke-linejoin='round' d='M4.5 12.75l6 6 9-13.5'></path>
    </svg>
</div>

<h3 class='text-lg font-bold leading-6 mt-4'>Buat Pesanan</h3>
<div class='my-3 mt-3'>
    @if($request->ktg_tipe === 'jokigendong')
        <p class='text-sm'>Pastikan data akun jokigendong yang anda pilih valid dan sesuai.</p>
    @elseif($request->ktg_tipe === 'joki')
        <p class='text-sm'>Pastikan data akun Joki yang anda pilih valid dan sesuai.</p>
    @elseif($request->ktg_tipe === 'vilogml')
        <p class='text-sm'>Pastikan data Vilog ML yang anda pilih valid dan sesuai.</p>
    @else
        <p class='text-sm'>Pastikan data akun Anda dan produk yang Anda pilih valid dan sesuai.</p>
    @endif
</div>

<div class='mt-4' style='background-color: #494949; padding: 12px; border-radius: 10px;'>
    <div class='flex items-center gap-2'>
        <div class='divider'></div>
        <h4 class='shrink-0 pr-4 text-sm font-semibold'>
            @if($request->ktg_tipe === 'jokigendong') Data jokigendong
            @elseif($request->ktg_tipe === 'joki') Data Joki
            @elseif($request->ktg_tipe === 'vilogml') Data Joki
            @else Data Player
            @endif
        </h4>
    </div>

    @if($request->ktg_tipe === 'jokigendong')
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Nickname</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->nickname_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Role</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->loginvia_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Tanggal Main</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->tglmain_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Jam Booking</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->jambooking_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Catatan</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->catatan_joki }}</h4></div>
    @elseif($request->ktg_tipe === 'joki')
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Email</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->email_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Password</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->password_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Login Via</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->loginvia_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Nickname</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->nickname_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Request</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->request_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Catatan</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->catatan_joki }}</h4></div>
    @elseif($request->ktg_tipe === 'vilogml')
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Email</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->email_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Password</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->password_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Login Via</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->loginvia_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>User ID</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->nickname_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Server ID</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->request_joki }}</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm'>Catatan</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->catatan_joki }}</h4></div>
    @else
        <div class='flex justify-between'>
            <h4 class='shrink-0 pr-4 text-sm'>User ID</h4>
            <h4 class='shrink-0 pr-4 text-sm font-bold'>
                {{ $request->uid }} {{ $request->zone ?? '' }}
            </h4>
        </div>
        @if(isset($request->zone) && !empty($request->zone))
        <div class='flex justify-between'>
            <h4 class='shrink-0 pr-4 text-sm'>Zone</h4>
            <h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $request->zone }}</h4>
        </div>
        @endif
        <div class='flex justify-between'>
            <h4 class='shrink-0 pr-4 text-sm'>Username</h4>
            <h4 class='shrink-0 pr-4 text-sm font-bold nick' id='nick'>{{ urldecode($username) }}</h4>
        </div>
    @endif

    <br>
    <div class='flex items-center gap-2'>
        <div class='divider'></div>
        <h4 class='shrink-0 pr-4 text-sm font-semibold'>Ringkasan Pembelian</h4>
    </div>
    
    <div class='flex justify-between'>
        <h4 class='shrink-0 pr-4 text-sm'>Item</h4>
        <h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $item->layanan }}</h4>
    </div>
    
    <div class='flex justify-between'>
        <h4 class='shrink-0 pr-4 text-sm'>Product</h4>
        <h4 class='shrink-0 pr-4 text-sm font-bold'>{{ $produk->nama }}</h4>
    </div>
    
    <div class='flex justify-between'>
        <h4 class='shrink-0 pr-4 text-sm'>Price</h4>
        <h4 class='shrink-0 pr-4 text-sm font-bold'>Rp. {{ number_format($dataLayanan->harga, 0, '.', ',') }}</h4>
    </div>

    @if(!in_array($request->ktg_tipe, ['jokigendong', 'joki', 'vilogml']))
    <div class='flex justify-between'>
        <h4 class='shrink-0 pr-4 text-sm'>Payment</h4>
        <h4 class='shrink-0 pr-4 text-sm font-bold truncatee'>
            {{ strtoupper($dataMethod->name) }}
        </h4>
    </div>
    @endif
</div>
