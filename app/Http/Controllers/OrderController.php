<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Voucher;
use App\Models\Pembelian;
use App\Models\Rating;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Models\User;
use App\Models\Method;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\digiFlazzController;
use App\Http\Controllers\ApiCheckController;
use App\Http\Controllers\PaydisiniController;
use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\MoogoldController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Libraries\Provider\ElitediasProvider;

class OrderController extends Controller
{
    public function create(Kategori $kategori)
    {


        if (in_array($kategori->tipe, ['game', 'voucher', 'pulsa', 'app', 'populer'])) {

            $data = Kategori::where('kode', $kategori->kode)->join('custom_inputs', 'kategoris.id', 'custom_inputs.kategori_id')->select('custom_inputs.field_1 AS field_1', 'custom_inputs.field_2 AS field_2', 'custom_inputs.field_select_title AS field_select_title', 'custom_inputs.field_select AS field_select', 'nama', 'sub_nama', 'server_id', 'thumbnail', 'kategoris.id AS id', 'kode',  'deskripsi_game', 'deskripsi_field', 'banner', 'tipe')->first();
            if ($data == null) return back();
        } else {

            $data = Kategori::where('kode', $kategori->kode)->select('nama', 'sub_nama', 'server_id', 'thumbnail', 'kategoris.id AS id', 'kode', 'deskripsi_game', 'deskripsi_field', 'banner', 'tipe')->first();
            if ($data == null) return back();
        }


        if (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $layanan = Layanan::where('kategori_id', $data->id)->where('status', 'available')->select('id', 'layanan', 'harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo')->orderBy('harga', 'asc')->get();
            } else if (Auth::user()->role == "Platinum") {
                $layanan = Layanan::where('kategori_id', $data->id)->where('status', 'available')->select('id', 'layanan', 'harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo')->orderBy('harga', 'asc')->get();
            } else if (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $layanan = Layanan::where('kategori_id', $data->id)->where('status', 'available')->select('id', 'layanan', 'harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo')->orderBy('harga', 'asc')->get();
            }
        } else {
            $layanan = Layanan::where('kategori_id', $data->id)->where('status', 'available')->select('id', 'layanan', 'product_logo', 'harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo')->orderBy('harga', 'asc')->get();
        }

        $ratings = DB::table('ratings')
            ->join('pembelians', 'ratings.rating_id', '=', 'pembelians.order_id')
            ->join('pembayarans', 'ratings.rating_id', '=', 'pembayarans.order_id')
            ->select(
                'ratings.bintang',
                'ratings.comment',
                'ratings.id',
                'ratings.created_at',
                'pembelians.username',
                'pembelians.layanan',
                'pembayarans.no_pembeli'
            )
            ->orderByDesc('ratings.id')
            ->limit(10)
            ->get();


        $pakets = [];
        $userRole = Auth::check() ? Auth::user()->role : null;

        foreach (Paket::all() as $paket) {
            $layananIds = $paket->layanan->pluck('id')->toArray();
            $layananData = Layanan::whereIn('id', $layananIds)
                ->where('kategori_id', $data->id)
                ->where(function ($query) use ($userRole) {
                    if ($userRole == 'Member') {
                        $query->where('harga_member', '>', 0);
                    } elseif ($userRole == 'Platinum') {
                        $query->where('harga_platinum', '>', 0);
                    } elseif ($userRole == 'Gold' || $userRole == 'Admin') {
                        $query->where('harga_gold', '>', 0);
                    } else {
                        $query->where('harga', '>', 0);
                    }
                })
                ->get();

            $l = [];
            foreach ($layananData as $lyn) {
                $paketLayanan = PaketLayanan::where('paket_id', $paket->id)
                    ->where('layanan_id', $lyn->id)
                    ->first();

                if ($paketLayanan) {
                    if ($userRole == 'Member') {
                        $harga = $lyn->harga_member;
                    } elseif ($userRole == 'Platinum') {
                        $harga = $lyn->harga_platinum;
                    } elseif ($userRole == 'Gold' || $userRole == 'Admin') {
                        $harga = $lyn->harga_gold;
                    } else {
                        $harga = $lyn->harga;
                    }

                    $lynData = [
                        'id' => $lyn->id,
                        'layanan' => $lyn->layanan,
                        'product_logo' => $paketLayanan->product_logo,
                        'harga' => $harga,  // Use the dynamically set price
                        'is_flash_sale' => $lyn->is_flash_sale,
                        'expired_flash_sale' => $lyn->expired_flash_sale,
                        'harga_flash_sale' => $lyn->harga_flash_sale,
                        'updated_at' => $lyn->updated_at,
                    ];

                    $l[] = $lynData;
                }
            }

            if (!empty($l)) {
                $pakets[] = [
                    'nama' => $paket->nama,
                    'layanan' => $l,
                ];
            }
        }


        $flashsale = Layanan::join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
            ->select('kategoris.thumbnail AS gmr_thumb', 'kategoris.kode AS kode_game', 'layanans.*')
            ->where('layanans.is_flash_sale', 1)
            ->where('layanans.expired_flash_sale', '>=', now())
            ->where('layanans.stock_flash_sale', '>', 0)
            ->get();



        return view('template.order', [
            'title' => $data->nama,
            'kategori' => $data,
            'nominal' => $layanan,
            'pakets' => $pakets,
            'harga' => $layanan,
            'ratings' => $ratings,
            'flashsale' => $flashsale,
            'pay_method' => \App\Models\Method::all()
        ]);
    }

    public function price(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $data = Layanan::where('id', $request->nominal)
                    ->select('harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            } elseif (Auth::user()->role == "Platinum") {
                $data = Layanan::where('id', $request->nominal)
                    ->select('harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            } elseif (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $data = Layanan::where('id', $request->nominal)
                    ->select('harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                    ->first();
            }
        } else {
            $data = Layanan::where('id', $request->nominal)
                ->select('harga AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                ->first();
        }

        if ($data->is_flash_sale == 1 && $data->expired_flash_sale >= date('Y-m-d H:i:s') && $data->stock_flash_sale > 0) {
            $data->harga = $data->harga_flash_sale;
        }

        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty;
            if ($qty <= 0) {
                $qty = 1;
            }

            $data->harga *= $qty;
        }


        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            if ($voucher && $voucher->stock > 0) {
                $potongan = $data->harga * ($voucher->promo / 100);
                if ($potongan > $voucher->max_potongan) {
                    $potongan = $voucher->max_potongan;
                }
                $data->harga -= $potongan;
            }
        }

        // OPTIMIZATION: Cache methods query for 60 minutes to reduce DB load
        $methods = \Illuminate\Support\Facades\Cache::remember('payment_methods_price_calc', 60 * 60, function () {
            return Method::select('code', 'fee_percent', 'fix_fee', 'min_pembelian', 'max_pembelian')
                ->get()
                ->keyBy('code');
        });

        // REMOVED: Log::info to prevent I/O latency
        
        return response()->json([
            'status' => true,
            'harga' => $data->harga,
            'methods' => $methods
        ]);
    }


    public function confirm(Request $request)
    {

        if ($request->ktg_tipe === 'jokigendong') {
            $request->validate([
                'nickname_joki' => 'required|string|max:255',
                'tglmain_joki' => 'required|string|max:255',
                'jambooking_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required',
                'catatan_joki' => 'required',
                'service' => 'required|numeric',
                'payment_method' => 'required',
                'nomor' => 'required|numeric',
            ]);
        } elseif ($request->ktg_tipe === 'joki') {
            $request->validate([
                'email_joki' => 'required|string|max:255',
                'password_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required|string|max:255',
                'nickname_joki' => 'required|string|max:255',
                'request_joki' => 'required|string|max:255',
                'catatan_joki' => 'required|string|max:255',
                'service' => 'required|numeric',
                'payment_method' => 'required',
                'nomor' => 'required|numeric',
            ]);
        } elseif ($request->ktg_tipe === 'vilogml') {
            $request->validate([
                'email_joki' => 'required|string|max:255',
                'password_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required|string|max:255',
                'nickname_joki' => 'required|string|max:255',
                'request_joki' => 'required|string|max:255',
                'catatan_joki' => 'required|string|max:255',
                'service' => 'required|numeric',
                'payment_method' => 'required',
                'nomor' => 'required|numeric',
            ]);
        } else {
            $request->validate([
                'uid' => 'required|max:25',
                'service' => 'required|numeric',
                'payment_method' => 'required',
                'nomor' => 'required|numeric',
            ]);
        }

        $item = Layanan::where('id', $request->service)->first();
        $produk = Kategori::where('id', $item->kategori_id)->first();

        // cek data
        if (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $dataLayanan = Layanan::where('id', $request->service)->select('harga_member AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            } else if (Auth::user()->role == "Platinum") {
                $dataLayanan = Layanan::where('id', $request->service)->select('harga_platinum AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            } else if (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $dataLayanan = Layanan::where('id', $request->service)->select('harga_gold AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            }
        } else {
            $dataLayanan = Layanan::where('id', $request->service)->select('harga AS harga', 'kategori_id', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
        }
        if ($dataLayanan->is_flash_sale == 1 && $dataLayanan->expired_flash_sale >= date('Y-m-d H:i:s') && $dataLayanan->stock_flash_sale > 0) {

            $dataLayanan->harga = $dataLayanan->harga_flash_sale;
        }
        // qty
        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty;
            if ($qty <= 0) {
                $qty = 1;
            }

            $dataLayanan->harga *= $qty;
        }
        // voucher
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();

            if (!$voucher) {
                $dataLayanan->harga = $dataLayanan->harga;
            } else {
                if ($voucher->stock == 0) {
                    $dataLayanan->harga = $dataLayanan->harga;
                } else {
                    $potongan = $dataLayanan->harga * ($voucher->promo / 100);
                    if ($potongan > $voucher->max_potongan) {
                        $potongan = $voucher->max_potongan;
                    }

                    $dataLayanan->harga = $dataLayanan->harga - $potongan;
                }
            }
        }


        $dataKategori = Kategori::where('id', $dataLayanan->kategori_id)->select('kode')->first();
        $apicheck = new ApiCheckController();

        $daftarGameValidasi = [
            'arena-breakout',
            'mobile-legends',
            'free-fire',
            '8-ball-pool',
            'point-blank',
            'arena-of-valor',
            'genshin-impact',
            'dragon-raja',
            'valorant',
            'metal-slug-awakening',
            'sausage-man',
            'ea-sports-fc-mobile',
            'undawn',
            'call-of-duty-mobile',
            'pubg-mobile-tp',
            'honor-of-kings-tp',
            'honkai-star-rail',
            'steam-wallet-code-indonesia',
            'free-fire-max',
            'astra-knights-of-veda ',
            'au2-mobile',
            'advent-of-godlegends',
            'aether-gazer',
            'among-heroes-fantasy-samkok',
            'angel-squad-dg',
            'aov-dg',
            'arcane-saga',
            'arena-breakout',
            'arena-mania-magic-heroes',
            'asphalt-9-legends',
            'atlantica-online-dg',
            'astral-guardians-cyber-fantasy',
            'auto-chess',
            'azur-lane',
            'bleach-mobile-3d',
            'badlanders',
            'barbarq',
            'battlenet-dg',
            'be-the-king-judge-destiny',
            'bermuda',
            'bigo-live',
            'bigo-live-voucher',
            'Bilibili-dg',
            'bioskop-online',
            'blade-x-odyssey-of-heroes',
            'bleach-mobile-3d-dg',
            'blizzard-gift-card-dg',
            'blood-strike',
            'boxing-star-dg',
            'captain-tsubasa-ace',
            'captain-tsubasa-dream-team',
            'city-of-crime-gang-wars',
            'clash-royale',
            'clash-of-clans',
            'cooking-adventure',
            'crasher-origin',
            'dead-target-zombie-games-3d',
            'dg-mini-games-dg',
            'dark-continent-mist',
            'diablo-immortal',
            'garena-dg',
            'ragnarok-m-eternal-love-big-cat-coin',
            'laplace-m',
            'speed-drifters',
            'era-of-celestials',
            'higgs-domino',
            'heroes-evolved',
            'lifeafter',
            'marvel-snap',
            'hago',
            'tom-and-jerry-chase',
            'one-punch-man-the-strongest',
            'dragon-raja',
            'ludo-club',
            'league-of-legends',
            'league-of-legends-wild-rift-dg',
            'state-of-survival',
            'ys-6-mobile-vng',
            'tower-of-fantasy-a',
            'stumble-guys',
            'honkai-impact-3',
            'goddes-victory-nikke-tp',
            'ragnarok-x-next-generation',
            'revelation-infinite-journey',
            'lita',
            'teen-patti-gold',
            'hay-day',
            'zepeto',
            'kings-choice',
            'harry-potter-magic-awakened',
            'life-makeover',
            'brawl-stars',
            'growtopia',
            'identity-v',
            'farlight-84',
            'football-master-2',
            'eos-red',
            'eggy-party',
            'snowbreak-containment-zone',
            'rhythm-hive',
            'asphalt-9-legends',
            'teamfight-tactics-mobile',
            'blood-strike',
            'punishing-gray-raven',
            'octopath-traveler-cotc',
            'love-and-deepspace',
            'pixel-gun-3d',
            'the-legend-of-neverland-dg',
            'heroic-uncle-kim-idle-rpg',
            'world-war-heroes',
            'moonlight-blade-m',
            'king-of-avalon'
        ];

        if (in_array($dataKategori->kode, $daftarGameValidasi)) {
            $data = [];
            if ($dataKategori->kode == 'mobile-legends') {
                $data = $apicheck->check($request->uid, $request->zone, 'Mobile Legends');
            } else if ($dataKategori->kode == "free-fire") {
                $data = $apicheck->check($request->uid, null, 'Free Fire');
            } else if ($dataKategori->kode == "free-fire-max") {
                $data = $apicheck->check($request->uid, null, 'Free Fire MAX');
            } else if ($dataKategori->kode == "honkai-star-rail") {
                $data = $apicheck->check($request->uid, $request->zone, 'Honkai: Star Rail');
            } else if ($dataKategori->kode == "pubg-mobile-tp") {
                $data = $apicheck->check($request->uid, null, 'PUBG Mobile');
            } else if ($dataKategori->kode == "honor-of-kings-tp") {
                $data = $apicheck->check($request->uid, null, 'Honor of Kings');
            } else if ($dataKategori->kode == "point-blank") {
                $data = $apicheck->check($request->uid, null, 'Point Blank');
            } else if ($dataKategori->kode == "arena-of-valor") {
                $data = $apicheck->check($request->uid, null, 'Arena of Valor');
            } else if ($dataKategori->kode == "genshin-impact") {
                $data = $apicheck->check($request->uid, null, 'Genshin Impact');
            } else if ($dataKategori->kode == "dragon-raja") {
                $data = $apicheck->check($request->uid, null, 'Dragon Raja');
            } else if ($dataKategori->kode == "super-sus") {
                $data = $apicheck->check($request->uid, null, 'Super Sus');
            } elseif ($dataKategori->kode == "call-of-duty-mobile") {
                $data = $apicheck->check($request->uid, null, 'Call of Duty Mobile');
            } elseif ($dataKategori->kode == "8-ball-pool") {
                $data = $apicheck->check($request->uid, null, '8 Ball Pool');
            } elseif ($dataKategori->kode == "valorant") {
                $data = $apicheck->check($request->uid, null, 'Valorant');
            } elseif ($dataKategori->kode == "metal-slug-awakening") {
                $data = $apicheck->check($request->uid, null, 'Metal Slug Awakening');
            } elseif ($dataKategori->kode == "sausage-man") {
                $data = $apicheck->check($request->uid, null, 'Sausage Man');
            } elseif ($dataKategori->kode == "ea-sports-fc-mobile") {
                $data = $apicheck->check($request->uid, null, 'FC Mobile');
            } elseif ($dataKategori->kode == "undawn") {
                $data = $apicheck->check($request->uid, null, 'Undawn');
            } elseif ($dataKategori->kode == "steam-wallet-code-indonesia") {
                $data = $apicheck->check($request->uid, null, 'Steam Wallet Code - Indonesia');
            } elseif ($dataKategori->kode == "astra-knights-of-veda") {
                $data = $apicheck->check($request->uid, $request->zone, 'ASTRA: Knights of Veda');
            } elseif ($dataKategori->kode == "au2-mobile") {
                $data = $apicheck->check($request->uid, null, 'AU2 Mobile');
            } elseif ($dataKategori->kode == "advent-of-godlegends") {
                $data = $apicheck->check($request->uid, $request->zone, 'Advent of God:Legends');
            } elseif ($dataKategori->kode == "aether-gazer") {
                $data = $apicheck->check($request->uid, null, 'Aether Gazer');
            } elseif ($dataKategori->kode == "among-heroes-fantasy-samkok") {
                $data = $apicheck->check($request->uid, $request->zone, 'Among Heroes: Fantasy Samkok');
            } elseif ($dataKategori->kode == "angel-squad-dg") {
                $data = $apicheck->check($request->uid, null, 'Angel Squad (DG)');
            } elseif ($dataKategori->kode == "aov-dg") {
                $data = $apicheck->check($request->uid, null, 'AoV (DG)');
            } elseif ($dataKategori->kode == "arcane-saga") {
                $data = $apicheck->check($request->uid, null, 'Arcane Saga');
            } elseif ($dataKategori->kode == "arena-breakout") {
                $data = $apicheck->check($request->uid, null, 'Arena Breakout');
            } elseif ($dataKategori->kode == "arena-mania-magic-heroes") {
                $data = $apicheck->check($request->uid, $request->zone, 'Arena Mania: Magic Heroes');
            } elseif ($dataKategori->kode == "asphalt-9-legends") {
                $data = $apicheck->check($request->uid, $request->zone, 'Asphalt 9: Legends');
            } elseif ($dataKategori->kode == "astral-guardians-cyber-fantasy") {
                $data = $apicheck->check($request->uid, $request->zone, 'Astral Guardians: Cyber Fantasy');
            } elseif ($dataKategori->kode == "atlantica-online-dg") {
                $data = $apicheck->check($request->uid, null, 'Atlantica Online (DG)');
            } elseif ($dataKategori->kode == "auto-chess") {
                $data = $apicheck->check($request->uid, null, 'Auto Chess ');
            } elseif ($dataKategori->kode == "azur-lane") {
                $data = $apicheck->check($request->uid, $request->zone, 'Azur Lane');
            } elseif ($dataKategori->kode == "bleach-mobile-3d") {
                $data = $apicheck->check($request->uid, $request->zpne, 'BLEACH Mobile 3D');
            } elseif ($dataKategori->kode == "badlanders") {
                $data = $apicheck->check($request->uid, $request->zone, 'Badlanders');
            } elseif ($dataKategori->kode == "barbarq") {
                $data = $apicheck->check($request->uid, $request->zone, 'BarbarQ');
            } elseif ($dataKategori->kode == "battlenet-dg") {
                $data = $apicheck->check($request->uid, null, 'Battlenet (DG)');
            } elseif ($dataKategori->kode == "be-the-king-judge-destiny") {
                $data = $apicheck->check($request->uid, $request->zone, 'Be The King: Judge Destiny');
            } elseif ($dataKategori->kode == "bigo-live") {
                $data = $apicheck->check($request->uid, null, 'Bigo Live');
            } elseif ($dataKategori->kode == "bigo-live-voucher") {
                $data = $apicheck->check($request->uid, null, 'Bigo Live Voucher');
            } elseif ($dataKategori->kode == "Bilibili-dg") {
                $data = $apicheck->check($request->uid, null, 'Bilibili (DG)');
            } elseif ($dataKategori->kode == "bioskop-online") {
                $data = $apicheck->check($request->uid, null, 'Bioskop Online');
            } elseif ($dataKategori->kode == "blade-x-odyssey-of-heroes") {
                $data = $apicheck->check($request->uid, null, 'Blade X: Odyssey of Heroes');
            } elseif ($dataKategori->kode == "bleach-mobile-3d-dg") {
                $data = $apicheck->check($request->uid, $request->zone, 'Bleach Mobile 3D (DG)');
            } elseif ($dataKategori->kode == "blizzard-gift-card-dg") {
                $data = $apicheck->check($request->uid, null, 'Blizzard Gift Card (DG)');
            } elseif ($dataKategori->kode == "blood-strike") {
                $data = $apicheck->check($request->uid, $request->zone == 1 ? 1 : null, 'Blood Strike');
            } elseif ($dataKategori->kode == "boxing-star-dg") {
                $data = $apicheck->check($request->uid, null, 'Boxing Star (DG)');
            } elseif ($dataKategori->kode == "brawl-stars") {
                $data = $apicheck->check($request->uid, null, 'Brawl Stars');
            } elseif ($dataKategori->kode == "captain-tsubasa-ace") {
                $data = $apicheck->check($request->uid, null, 'Captain Tsubasa: Ace');
            } elseif ($dataKategori->kode == "captain-tsubasa-dream-team") {
                $data = $apicheck->check($request->uid, null, 'Captain Tsubasa: Dream Team');
            } elseif ($dataKategori->kode == "city-of-crime-gang-wars") {
                $data = $apicheck->check($request->uid, null, 'City of Crime: Gang Wars');
            } elseif ($dataKategori->kode == "clash-royale") {
                $data = $apicheck->check($request->uid, null, 'Clash Royale');
            } elseif ($dataKategori->kode == "clash-of-clans") {
                $data = $apicheck->check($request->uid, null, 'Clash of Clans');
            } elseif ($dataKategori->kode == "cloud-song-saga-of-skywalkers") {
                $data = $apicheck->check($request->uid, null, 'Cloud Song: Saga of Skywalkers');
            } elseif ($dataKategori->kode == "cooking-adventure") {
                $data = $apicheck->check($request->uid, $request->zone, 'Cooking Adventure');
            } elseif ($dataKategori->kode == "crasher-origin") {
                $data = $apicheck->check($request->uid, $request->zone, 'Crasher Origin');
            } elseif ($dataKategori->kode == "dead-target-zombie-games-3d") {
                $data = $apicheck->check($request->uid, null, 'DEAD TARGET: Zombie Games 3D');
            } elseif ($dataKategori->kode == "dg-mini-games-dg") {
                $data = $apicheck->check($request->uid, null, 'DG Mini Games (DG)');
            } elseif ($dataKategori->kode == "dark-continent-mist") {
                $data = $apicheck->check($request->uid, $request->zone, 'Dark Continent: Mist');
            } elseif ($dataKategori->kode == "diablo-immortal") {
                $data = $apicheck->check($request->uid, null, 'Diablo: Immortal');
            } elseif ($dataKategori->kode == "discord-subscription") {
                $data = $apicheck->check($request->uid, null, 'Discord Subscription');
            } elseif ($dataKategori->kode == "garena-dg") {
                $data = $apicheck->check($request->uid, null, 'Top Up Garena Shell (DG)');
            } elseif ($dataKategori->kode == "ragnarok-m-eternal-love-big-cat-coin") {
                $data = $apicheck->check($request->uid, null, 'Ragnarok M: Eternal Love Big Cat Coin');
            } elseif ($dataKategori->kode == "laplace-m") {
                $data = $apicheck->check($request->uid, null, 'Laplace M');
            } elseif ($dataKategori->kode == "speed-drifters") {
                $data = $apicheck->check($request->uid, null, 'Speed Drifters');
            } elseif ($dataKategori->kode == "era-of-celestials") {
                $data = $apicheck->check($request->uid, $request->zone, 'Era of Celestials');
            } elseif ($dataKategori->kode == "higgs-domino") {
                $data = $apicheck->check($request->uid, null, 'Higgs Domino');
            } elseif ($dataKategori->kode == "heroes-evolved") {
                $data = $apicheck->check($request->uid, null, 'Heroes Evolved');
            } elseif ($dataKategori->kode == "lifeafter") {
                $data = $apicheck->check($request->uid, $request->zone, 'LifeAfter');
            } elseif ($dataKategori->kode == "scroll-of-onmyoji-sakura-and-sword") {
                $data = $apicheck->check($request->uid, $request->zone, 'Scroll of Onmyoji: Sakura & Sword');
            } elseif ($dataKategori->kode == "marvel-snap") {
                $data = $apicheck->check($request->uid, null, 'MARVEL SNAP');
            } elseif ($dataKategori->kode == "hago") {
                $data = $apicheck->check($request->uid, null, 'Hago');
            } elseif ($dataKategori->kode == "tom-and-jerry-chase") {
                $data = $apicheck->check($request->uid, $request->zone, 'Tom and Jerry: Chase');
            } elseif ($dataKategori->kode == "one-punch-man-the-strongest") {
                $data = $apicheck->check($request->uid, null, 'ONE PUNCH MAN: The Strongest');
            } elseif ($dataKategori->kode == "dragon-raja") {
                $data = $apicheck->check($request->uid, null, 'Dragon Raja');
            } elseif ($dataKategori->kode == "ludo-club") {
                $data = $apicheck->check($request->uid, null, 'Ludo Club');
            } elseif ($dataKategori->kode == "league-of-legends-wild-rift-dg") {
                $data = $apicheck->check($request->uid, null, 'League of Legends : Wild Rift (DG)');
            } elseif ($dataKategori->kode == "league-of-legends") {
                $data = $apicheck->check($request->uid, null, 'League of Legends');
            } elseif ($dataKategori->kode == "state-of-survival") {
                $data = $apicheck->check($request->uid, null, 'State of Survival');
            } elseif ($dataKategori->kode == "ys-6-mobile-vng") {
                $data = $apicheck->check($request->uid, null, 'YS 6 Mobile VNG');
            } elseif ($dataKategori->kode == "tower-of-fantasy-a") {
                $data = $apicheck->check($request->uid, null, 'Tower of Fantasy (Slow)');
            } elseif ($dataKategori->kode == "mu-origin-3") {
                $data = $apicheck->check($request->uid, null, 'MU ORIGIN 3');
            } elseif ($dataKategori->kode == "stumble-guys") {
                $data = $apicheck->check($request->uid, null, 'Stumble Guys');
            } elseif ($dataKategori->kode == "honkai-impact-3") {
                $data = $apicheck->check($request->uid, null, 'Honkai Impact 3');
            } elseif ($dataKategori->kode == "goddes-victory-nikke-tp") {
                $data = $apicheck->check($request->uid, $request->zone, 'Goddes Victory: Nikke (FAST)');
            } elseif ($dataKategori->kode == "ragnarok-retro-dg") {
                $data = $apicheck->check($request->uid, null, 'Ragnarok Retro (DG)');
            } elseif ($dataKategori->kode == "ragnarok-x-next-generation") {
                $data = $apicheck->check($request->uid, $request->zone, 'Ragnarok X: Next Generation');
            } elseif ($dataKategori->kode == "revelation-infinite-journey") {
                $data = $apicheck->check($request->uid, null, 'Revelation: Infinite Journey');
            } elseif ($dataKategori->kode == "lita") {
                $data = $apicheck->check($request->uid, null, 'Lita');
            } elseif ($dataKategori->kode == "teen-patti-gold") {
                $data = $apicheck->check($request->uid, null, 'Teen Patti Gold');
            } elseif ($dataKategori->kode == "hay-day") {
                $data = $apicheck->check($request->uid, null, 'Hay Day');
            } elseif ($dataKategori->kode == "zepeto") {
                $data = $apicheck->check($request->uid, null, 'ZEPETO');
            } elseif ($dataKategori->kode == "kings-choice") {
                $data = $apicheck->check($request->uid, null, 'Kings Choice');
            } elseif ($dataKategori->kode == "harry-potter-magic-awakened") {
                $data = $apicheck->check($request->uid, $request->zone, 'Harry Potter: Magic Awakened');
            } elseif ($dataKategori->kode == "life-makeover") {
                $data = $apicheck->check($request->uid, null, 'Life Makeover');
            } elseif ($dataKategori->kode == "brawl-stars") {
                $data = $apicheck->check($request->uid, null, 'Brawl Stars');
            } elseif ($dataKategori->kode == "growtopia") {
                $data = $apicheck->check($request->uid, null, 'Growtopia');
            } elseif ($dataKategori->kode == "identity-v") {
                $data = $apicheck->check($request->uid, null, 'Identity V');
            } elseif ($dataKategori->kode == "farlight-84") {
                $data = $apicheck->check($request->uid, null, 'Farlight 84');
            } elseif ($dataKategori->kode == "football-master-2") {
                $data = $apicheck->check($request->uid, null, 'Football Master 2');
            } elseif ($dataKategori->kode == "eos-red") {
                $data = $apicheck->check($request->uid, $request->zone, 'EOS RED');
            } elseif ($dataKategori->kode == "eggy-party") {
                $data = $apicheck->check($request->uid, null, 'EGGY PARTY');
            } elseif ($dataKategori->kode == "snowbreak-containment-zone") {
                $data = $apicheck->check($request->uid, $request->zone, 'Snowbreak: Containment Zone');
            } elseif ($dataKategori->kode == "rhythm-hive") {
                $data = $apicheck->check($request->uid, null, 'Rhythm Hive');
            } elseif ($dataKategori->kode == "asphalt-9-legends") {
                $data = $apicheck->check($request->uid, null, 'Asphalt 9: Legends');
            } elseif ($dataKategori->kode == "teamfight-tactics-mobile") {
                $data = $apicheck->check($request->uid, null, 'Teamfight Tactics Mobile');
            } elseif ($dataKategori->kode == "blood-strike") {
                $data = $apicheck->check($request->uid, $request->zone, 'Blood Strike');
            } elseif ($dataKategori->kode == "punishing-gray-raven") {
                $data = $apicheck->check($request->uid, $request->zone, 'Punishing: Gray Raven');
            } elseif ($dataKategori->kode == "octopath-traveler-cotc") {
                $data = $apicheck->check($request->uid, $request->zone, 'OCTOPATH TRAVELER: CotC');
            } elseif ($dataKategori->kode == "love-and-deepspace") {
                $data = $apicheck->check($request->uid, null, 'Love and Deepspace');
            } elseif ($dataKategori->kode == "pixel-gun-3d") {
                $data = $apicheck->check($request->uid, null, 'Pixel Gun 3D');
            } elseif ($dataKategori->kode == "the-legend-of-neverland-dg") {
                $data = $apicheck->check($request->uid, null, 'The Legend of Neverland (DG)');
            } elseif ($dataKategori->kode == "heroic-uncle-kim-idle-rpg") {
                $data = $apicheck->check($request->uid, null, 'Heroic Uncle Kim: Idle RPG');
            } elseif ($dataKategori->kode == "world-war-heroes") {
                $data = $apicheck->check($request->uid, null, 'World War Heroes');
            } elseif ($dataKategori->kode == "moonlight-blade-m") {
                $data = $apicheck->check($request->uid, null, 'Moonlight Blade M');
            } elseif ($dataKategori->kode == "king-of-avalon") {
                $data = $apicheck->check($request->uid, null, 'King of Avalon');
            } else {
            $data = $apicheck->check($request->uid, $request->zone, $dataKategori->kode);
            }
            
            if (!isset($data['status']['code']) || $data['status']['code'] !== 200 || empty($data['data']['username'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User ID tidak ditemukan atau tidak valid. Silakan periksa kembali.'
                ]);
            }

            $username = isset($data['data']['username']) ? $data['data']['username'] : 'Not Found.';


        }

        // Initialize username if not set (for games not in validation list)
        if (!isset($username)) {
            $username = null;
        }

        $dataMethod = Method::where('code', $request->payment_method)
            ->select('name', 'payment', 'tipe', 'code', 'fee_percent', 'fix_fee')
            ->first();
       
        if ($dataMethod) {
            // Formula: Price + FixFee + (Price * FeePercent / 100)
            $fee = $dataMethod->fix_fee + ($dataLayanan->harga * ($dataMethod->fee_percent / 100));
            $dataLayanan->harga += $fee;
        }

        $sendData = view('template.components.order_confirmation', compact(
            'request', 'dataLayanan', 'dataMethod', 'produk', 'item', 'username'
        ))->render();

        return response()->json([
            'status' => true,
            'data' => $sendData
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validation
        $this->validateOrder($request);

        // 2. Initial Setup
        if (Auth::check()) {
            $role = Auth::user()->role;
            $column = match($role) {
                'Member' => 'harga_member',
                'Platinum' => 'harga_platinum',
                'Gold', 'Admin' => 'harga_gold',
                default => 'harga'
            };
            $profitCol = match($role) {
                'Member' => 'profit_member',
                'Platinum' => 'profit_platinum',
                'Gold', 'Admin' => 'profit_gold',
                default => 'profit'
            };
            
            $dataLayanan = Layanan::where('id', $request->service)
                ->select('layanan', "$column AS harga", 'kategori_id', 'provider_id', 'provider', "$profitCol AS profit", 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                ->first();
        } else {
            $dataLayanan = Layanan::where('id', $request->service)
                ->select('layanan', 'harga AS harga', 'kategori_id', 'provider_id', 'provider', 'profit', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')
                ->first();
        }

        if (!$dataLayanan) return response()->json(['status' => false, 'data' => 'Layanan tidak ditemukan']);

        // Flash Sale Logic
        if ($dataLayanan->is_flash_sale == 1 && $dataLayanan->expired_flash_sale >=now() && $dataLayanan->stock_flash_sale > 0) {
            Layanan::where('id', $request->service)->decrement('stock_flash_sale');
            $dataLayanan->harga = $dataLayanan->harga_flash_sale;
        }

        // Joki Quantity Logic
        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty > 0 ? $request->qty : 1;
            $dataLayanan->harga *= $qty;
        }

        // Voucher Logic (Calculation Only)
        if ($request->voucher) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            Log::info('Voucher found', ['voucher' => $voucher]);
            if ($voucher && $voucher->stock > 0) {
                $potongan = $dataLayanan->harga * ($voucher->promo / 100);
                if ($potongan > $voucher->max_potongan) $potongan = $voucher->max_potongan;
                
                if ($voucher->mintrx && $dataLayanan->harga < $voucher->mintrx) {
                     return response()->json([
                        'status' => false, 
                        'data' => 'Minimal transaksi untuk voucher ini adalah Rp ' . number_format($voucher->mintrx, 0, ',', '.')
                    ]);
                }
                $dataLayanan->harga = round($dataLayanan->harga - $potongan);
            }
        }

        // Generate Order ID
        $setting = DB::table('setting_webs')->where('id', 1)->first();
        $order_id = $setting->order_prefik . date('Hs') . Str::random(8); // Simplified random
        
        // Payment Method Info
        $dataMethod = Method::where('code', $request->payment_method)->first();
        
        // 3. Process based on Payment Method
        if ($request->payment_method == "SALDO") {
            // --- BALANCE PAYMENT FLOW ---
            if (!Auth::check()) return response()->json(['status' => false, 'data' => 'Harap login terlebih dahulu']);

            $userKey = 'user_transaction_' . Auth::id();
            if (Cache::has($userKey)) return response()->json(['status' => false, 'data' => 'Transaksi terlalu cepat, harap tunggu sebentar.']);
            Cache::put($userKey, true, now()->addMinutes(1));

            DB::beginTransaction();
            try {
                // Rate Limiting Check (Last transaction < 1 min)
                $lastOrder = Pembelian::where('username', Auth::user()->username)->latest()->first();
                if ($lastOrder && $lastOrder->created_at->diffInMinutes(now()) < 1) {
                    throw new \Exception('Harap tunggu 1 menit sebelum transaksi lagi.');
                }

                $user = User::where('username', Auth::user()->username)->lockForUpdate()->first();
                if ($dataLayanan->harga > $user->balance) {
                    throw new \Exception('Saldo tidak mencukupi');
                }

                // Voucher Stock Decrement
                if ($request->voucher) {
                    $voucher = Voucher::where('kode', $request->voucher)->lockForUpdate()->first();
                    if (!$voucher || $voucher->stock <= 0) throw new \Exception('Voucher habis');
                    $voucher->decrement('stock');
                }

                // Deduct Balance
                $user->decrement('balance', $dataLayanan->harga);
                // Process Game Provider
                $providerResult = $this->processGameProvider($dataLayanan, $request, $order_id);
                // Create Record
                $tipe = match($request->ktg_tipe) {
                    'joki' => 'joki', 'voucher' => 'voucher', 'vilogml' => 'vilogml', 'jokigendong' => 'jokigendong', default => 'game'
                };
                
                // IP Address
                $ipController = new IPAddressController();
                $ipAddress = $ipController->getIPAddress($request);

                $status_pembelian = $providerResult['status'] ? 'Proses' : 'Pending'; // Or whatever default for failed/pending provider
                $provider_order_id = $providerResult['provider_order_id'];
                $log_data = json_encode($providerResult['order_data']);

                $this->createOrderRecord(
                    $request, $dataLayanan, $order_id, $dataLayanan->harga, $dataMethod, 
                    'Lunas', 'Balance Payment', '', $status_pembelian, 
                    $provider_order_id, $log_data, $ipAddress, $tipe
                );

                DB::commit();
                Cache::forget($userKey);

                // Send Success Message
                $pesanSukses = "*Pembelian Sukses*\n\nNo Invoice: *$order_id*\nLayanan: *$dataLayanan->layanan*\nID : *$request->uid*\nServer : *$request->zone*\nNickname : *$request->nickname*\nHarga: *Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "*\nStatus Pembelian: *Sukses*\nMetode Pembayaran: *SALDO*\n\n*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\nINI ADALAH PESAN OTOMATIS";
                $this->msg($request->nomor, $pesanSukses);

            } catch (\Exception $e) {
                DB::rollBack();
                Cache::forget($userKey);
                return response()->json(['status' => false, 'data' => $e->getMessage()]);
            }

        } else {
            // --- EXTERNAL PAYMENT GATEWAY FLOW ---
            $amount = $dataLayanan->harga;
            $no_pembayaran = '';
            $reference = '';

            // Gateway Processing
            $gatewayResult = ['status' => false, 'msg' => 'Metode pembayaran tidak tersedia'];
            
            if ($dataMethod->payment == "tokopay") {
                $tokopay = new TokoPayController();
                $res = $tokopay->createOrder($amount, $order_id, $request->payment_method);
                if ($res['status'] == 'Success') {
                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $res['data']['nomor_va'] ?? $res['data']['qr_link'] ?? $res['data']['checkout_url'] ?? $res['data']['pay_url'],
                        'reference' => $res['data']['trx_id'],
                        'amount' => $res['data']['total_bayar']
                    ];
                }
            } else if ($dataMethod->payment == "tripay") {
                $tripay = new TriPayController();
                $res = $tripay->request($order_id, $amount, $request->payment_method, $order_id . '@email.com', $request->nomor);
                if ($res['success']) {
                    $gatewayResult = [
                        'status' => true,
                        'no_pembayaran' => $res['no_pembayaran'],
                        'reference' => $res['reference'],
                        'amount' => $res['amount']
                    ];
                } else {
                     $gatewayResult['msg'] = $res['msg'];
                }
            }

            if (!$gatewayResult['status']) {
                return response()->json(['status' => false, 'data' => $gatewayResult['msg'] ?? 'Gagal memproses pembayaran']);
            }

            $amount = $gatewayResult['amount'];
            $no_pembayaran = $gatewayResult['no_pembayaran'];
            $reference = $gatewayResult['reference'];

            // Create Record (Pending)
            $tipe = match($request->ktg_tipe) {
                'joki' => 'joki', 'voucher' => 'voucher', 'vilogml' => 'vilogml', 'jokigendong' => 'jokigendong', default => 'game'
            };
            $ipController = new IPAddressController();
            $ipAddress = $ipController->getIPAddress($request);

            $this->createOrderRecord(
                $request, $dataLayanan, $order_id, $amount, $dataMethod, 
                'Belum Lunas', $no_pembayaran, $reference, 'Pending', 
                '', '', $ipAddress, $tipe
            );

            // Send Pending Message
            $pesanPending = "*Menunggu Pembayaran*\n\nNo Invoice: *$order_id*\nLayanan: *$dataLayanan->layanan*\nID : *$request->uid*\nServer : *$request->zone*\nNickname : *$request->nickname*\nHarga: *Rp. " . number_format($amount, 0, '.', ',') . "*\nStatus: *Menunggu Pembayaran*\nMetode Pembayaran: *$dataMethod->name*\nKode Bayar / Nomor VA : *" . $no_pembayaran . "*\n\n*Harap Dibayar Sebelum 3 Jam!*\n\n*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\nINI ADALAH PESAN OTOMATIS";
            $this->msg($request->nomor, $pesanPending);
        }

        return response()->json([
            'status' => true,
            'order_id' => $order_id
        ]);
    }

    public function msg($nomor, $msg)

    {
         $api = \DB::table('setting_webs')->where('id', 1)->first();
        $apiUrl = 'https://api.fonnte.com/send';
        $token = $api->wa_key;
    
        $postData = [
            'target' => $nomor,
            'message' => $msg,
            'countryCode' => '62',
        ];
    
        $headers = [
            'Authorization: ' . $token,
        ];
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
        ]);
    
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
        curl_close($curl);
    
        if ($statusCode === 200) {
            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'response' => $response,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send message',
                'response' => $response,
            ];
        }
    }

    public function getPrice(Request $request)
    {
        try {
            $layanan = Layanan::where('provider_id', $request->nominal)->first();
            if (!$layanan) {
                throw new \Exception('Layanan tidak ditemukan');
            }
            $qty = $request->qty ? intval($request->qty) : 1;
            $paymentMethod = $request->payment_method;
            $promo = $request->promo ?? null;

            // Hitung harga dasar
            $basePrice = $layanan->harga * $qty;

            // Hitung fee payment method (logic harus sama persis dengan ringkasan modal!)
            // Hitung fee payment method (dynamic from DB)
            $method = Method::where('code', $paymentMethod)->first();
            if ($method) {
                $finalPrice += $method->fix_fee + ($basePrice * ($method->fee_percent / 100));
            }

            // Promo logic jika ada
            if ($promo && $promo == 'DISKON10') {
                $finalPrice = $finalPrice * 0.9;
            }

            return response()->json([
                'success' => true,
                'harga' => round($finalPrice),
                'harga_format' => 'Rp. ' . number_format($finalPrice, 0, ',', '.')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkAccount(Request $request)
    {
        // 1. Validate Input
        $request->validate([
            'uid' => 'required',
            'kategori_kode' => 'required',
        ]);

        $kategoriKode = $request->kategori_kode;
        $uid = $request->uid;
        $zone = $request->zone;

        // 2. Define Supported Games
        $daftarGameValidasi = [
            'arena-breakout', 'mobile-legends', 'free-fire', '8-ball-pool', 'point-blank',
            'arena-of-valor', 'genshin-impact', 'dragon-raja', 'valorant', 'metal-slug-awakening',
            'sausage-man', 'ea-sports-fc-mobile', 'undawn', 'call-of-duty-mobile', 'pubg-mobile-tp',
            'honor-of-kings-tp', 'honkai-star-rail', 'steam-wallet-code-indonesia', 'free-fire-max',
            'astra-knights-of-veda ', 'au2-mobile', 'advent-of-godlegends', 'aether-gazer',
            'among-heroes-fantasy-samkok', 'angel-squad-dg', 'aov-dg', 'arcane-saga', 'arena-breakout',
            'arena-mania-magic-heroes', 'asphalt-9-legends', 'atlantica-online-dg',
            'astral-guardians-cyber-fantasy', 'auto-chess', 'azur-lane', 'bleach-mobile-3d',
            'badlanders', 'barbarq', 'battlenet-dg', 'be-the-king-judge-destiny', 'bermuda',
            'bigo-live', 'bigo-live-voucher', 'Bilibili-dg', 'bioskop-online', 'blade-x-odyssey-of-heroes',
            'bleach-mobile-3d-dg', 'blizzard-gift-card-dg', 'blood-strike', 'boxing-star-dg',
            'captain-tsubasa-ace', 'captain-tsubasa-dream-team', 'city-of-crime-gang-wars',
            'clash-royale', 'clash-of-clans', 'cooking-adventure', 'crasher-origin',
            'dead-target-zombie-games-3d', 'dg-mini-games-dg', 'dark-continent-mist',
            'diablo-immortal', 'garena-dg', 'ragnarok-m-eternal-love-big-cat-coin', 'laplace-m',
            'speed-drifters', 'era-of-celestials', 'higgs-domino', 'heroes-evolved', 'lifeafter',
            'marvel-snap', 'hago', 'tom-and-jerry-chase', 'one-punch-man-the-strongest', 'dragon-raja',
            'ludo-club', 'league-of-legends', 'league-of-legends-wild-rift-dg', 'state-of-survival',
            'ys-6-mobile-vng', 'tower-of-fantasy-a', 'stumble-guys', 'honkai-impact-3',
            'goddes-victory-nikke-tp', 'ragnarok-x-next-generation', 'revelation-infinite-journey',
            'lita', 'teen-patti-gold', 'hay-day', 'zepeto', 'kings-choice', 'harry-potter-magic-awakened',
            'life-makeover', 'brawl-stars', 'growtopia', 'identity-v', 'farlight-84', 'football-master-2',
            'eos-red', 'eggy-party', 'snowbreak-containment-zone', 'rhythm-hive', 'asphalt-9-legends',
            'teamfight-tactics-mobile', 'punishing-gray-raven', 'octopath-traveler-cotc',
            'love-and-deepspace', 'pixel-gun-3d', 'the-legend-of-neverland-dg', 'heroic-uncle-kim-idle-rpg',
            'world-war-heroes', 'moonlight-blade-m', 'king-of-avalon'
        ];

        if (!in_array($kategoriKode, $daftarGameValidasi)) {
             return response()->json([
                'status' => ['code' => 400, 'message' => 'Game not supported for validation']
            ]);
        }

        // 3. Map Category Code to Game Name for API
        $apicheck = new ApiCheckController();
        $data = [];

        // Simplified mapping based on common patterns
        switch($kategoriKode) {
            case 'mobile-legends': $gameName = 'Mobile Legends'; break;
            case 'free-fire': $gameName = 'Free Fire'; break;
            case 'free-fire-max': $gameName = 'Free Fire MAX'; break;
            case 'honkai-star-rail': $gameName = 'Honkai Star Rail'; break;
            case 'genshin-impact': $gameName = 'Genshin Impact'; break;
            case 'valorant': $gameName = 'Valorant'; break; 
            case 'pubg-mobile-tp': $gameName = 'PUBG Mobile'; break;
            case 'honor-of-kings-tp': $gameName = 'Honor of Kings'; break;
            case 'garena-dg': $gameName = 'Garena Shell'; break;
            case 'higgs-domino': $gameName = 'Higgs Domino'; break;
            default:
                 // Fallback: Try converting slug to Title Case
                 $gameName = ucwords(str_replace('-', ' ', $kategoriKode));
                 break;
        }

        // Use the API Check
        $data = $apicheck->check($uid, $zone, $gameName);
        
        return response()->json($data);
    }
    private function validateOrder(Request $request)
    {
        $rules = [
            'service' => 'required|numeric',
            'payment_method' => 'required',
            'nomor' => 'required|numeric',
            'voucher' => 'string',
        ];

        if ($request->ktg_tipe === 'jokigendong') {
            $rules += [
                'nickname_joki' => 'required|string|max:255',
                'tglmain_joki' => 'required|string|max:255',
                'jambooking_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required',
                'catatan_joki' => 'required',
            ];
        } elseif ($request->ktg_tipe === 'joki' || $request->ktg_tipe === 'vilogml') {
            $rules += [
                'email_joki' => 'required|string|max:255',
                'password_joki' => 'required|string|max:255',
                'loginvia_joki' => 'required|string|max:255',
                'nickname_joki' => 'required|string|max:255',
                'request_joki' => 'required|string|max:255',
                'catatan_joki' => 'required|string|max:255',
                'qty' => 'required|numeric|max:30',
            ];
        } else {
            $rules['uid'] = 'required|max:25';
        }

        $request->validate($rules);
    }

    private function processGameProvider($dataLayanan, $request, $order_id)
    {
        $provider_order_id = '';
        $status = false;
        $order = [];

        try {
            switch ($dataLayanan->provider) {
                case "digiflazz":
                    $digi = new digiFlazzController;
                    $order = $digi->order($request->uid, $request->zone, $dataLayanan->provider_id, $order_id);
                    $status = in_array($order['data']['status'], ["Pending", "Sukses"]);
                    Log::info('Digiflazz Order: ', ['order' => $order, 'status' => $status]);
                    break;

                case "apigames":
                    $apigames = new ApiGamesController;
                    $order = $apigames->order($request->uid, $request->zone, $dataLayanan->provider_id, $order_id);
                    if ($order['data']['status'] == "Sukses") {
                        $order['transactionId'] = $order_id;
                        $status = true;
                    }
                    break;

                case "vip":
                    $vip = new VipResellerController;
                    $order = $vip->order($request->uid, $request->zone, $dataLayanan->provider_id);
                    if ($order['result']) {
                        $status = true;
                        $provider_order_id = $order['data']['trxid'];
                    }
                    break;

                case "bangjeff":
                    $bangjeffo = new BangJeffController;
                    $requestData = [['name' => 'ID', 'value' => $request->uid]];
                    if ($request->has('zone')) $requestData[] = ['name' => 'Server', 'value' => $request->zone];
                    
                    $order = $bangjeffo->order($dataLayanan->provider_id, $order_id, 1, $requestData);
                    if ($order['error'] == false) {
                        $provider_order_id = $order['data']['invoiceNumber'];
                        $status = true;
                    }
                    break;

                case "topupedia":
                    $topupedia = new TopupediaController;
                    $requestData = [['name' => 'ID', 'value' => $request->uid]];
                    if ($request->has('zone')) $requestData[] = ['name' => 'Server', 'value' => $request->zone];
                    
                    $order = $topupedia->order($dataLayanan->provider_id, $order_id, 1, $requestData);
                    if ($order['error'] == false) {
                        $provider_order_id = $order['data']['invoiceNumber'];
                        $status = true;
                    }
                    break;

                case "moogold":
                    $moo = new MoogoldController();
                    $provider_order_id = 'WEJIZY-MG' . mt_rand(100000, 999999);
                    $order = $moo->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                    if (isset($order['status'])) {
                        $provider_order_id = $order['order_id'];
                        $status = true;
                    }
                    break;

                case "gameshop":
                    $gameshop = new GameShopProvider;
                    $provider_order_id = 'WEJIZY-GS' . mt_rand(100000, 999999);
                    $order = $gameshop->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                    if (isset($order['data']['order_no'])) {
                        $provider_order_id = $order['data']['order_no'];
                        $status = true;
                    }
                    break;

                case "strleyashop":
                    $strleyashop = new StrleyaShopProvider;
                    $provider_order_id = 'WEJIZY-SS' . mt_rand(100000, 999999);
                    $order = $strleyashop->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                    if (isset($order['order_details']['bot_order_id'])) {
                        $provider_order_id = $order['order_details']['bot_order_id'];
                        $status = true;
                    }
                    break;

                case "yezzpay":
                    $yezzpay = new YezzpayProvider;
                    $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
                    $order = $yezzpay->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                    if (isset($order['data']['trx_id'])) {
                        $status = true;
                    }
                    break;

                case "elitedias":
                    $elitedias = new EliteDiasProvider;
                    $provider_order_id = 'WEJIZY-ED' . mt_rand(100000, 999999);
                    $order = $elitedias->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                    if (isset($order['order_id'])) {
                        $provider_order_id = $order['order_id'];
                        $status = true;
                    }
                    break;

                case "joki":
                case "jokigendong":
                case "vilogml":
                    $status = true;
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Provider Order Error: ' . $e->getMessage());
            $status = false;
        }

        return [
            'status' => $status,
            'provider_order_id' => $provider_order_id,
            'order_data' => $order
        ];
    }

    private function createOrderRecord($request, $dataLayanan, $order_id, $amount, $dataMethod, $status_pembayaran, $no_pembayaran, $reference, $order_status, $provider_order_id = '', $order_log = '', $ipAddress, $tipe) {
        $user_id = Auth::check() ? Auth::user()->username : null; // Consistent with original code
        
        $pembelian = new Pembelian();
        $pembelian->username = $user_id; 
        $pembelian->order_id = $order_id;
        
        // Define standard values
        $is_joki = in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml']);
        
        $pembelian->user_id = !$is_joki ? $request->uid : '-';
        $pembelian->zone = !$is_joki ? $request->zone : '-';
        $pembelian->nickname = !$is_joki ? $request->nickname : ($request->ktg_tipe !== 'joki' ? $request->nickname_joki : '-');
        
        $pembelian->log = $order_log;
        $pembelian->status = $order_status; // 'Pending' or 'Proses'
        $pembelian->tipe_transaksi = $tipe;
        
        $pembelian->layanan = $dataLayanan->layanan;
        $pembelian->harga = $amount;
        $pembelian->profit = $amount * $dataLayanan->profit / 100;
        $pembelian->provider_order_id = $provider_order_id;
        $pembelian->ip_address = $ipAddress;
        $pembelian->voucher = $request->voucher ?? null;
        $pembelian->save();

        $pembayaran = new Pembayaran();
        $pembayaran->order_id = $order_id;
        $pembayaran->harga = $amount;
        $pembayaran->no_pembayaran = $no_pembayaran;
        $pembayaran->no_pembeli = $request->nomor;
        $pembayaran->status = $status_pembayaran; // 'Belum Lunas' or 'Lunas'
        $pembayaran->metode = $request->payment_method;
        $pembayaran->reference = $reference;
        $pembayaran->save();

        if ($is_joki) {
            DB::table('data_joki')->insert([
                'order_id' => $order_id,
                'email_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->email_joki : '-',
                'password_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->password_joki : '-',
                'loginvia_joki' => $request->loginvia_joki,
                'nickname_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->nickname_joki : '-',
                'request_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->request_joki : '-',
                'catatan_joki' => $request->catatan_joki,

                'tglmain_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->tglmain_joki,
                'jambooking_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->jambooking_joki,
                'qty' => $request->qty ?? 1,
                'status_joki' => $order_status == 'Proses' ? 'Proses' : 'Pending', // Sync with order status
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}