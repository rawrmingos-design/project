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

        if (isset($request->voucher)) {
            $voucher = Voucher::where('kode', $request->voucher)->first();
            if ($voucher) {
                if ($voucher->stock > 0) {
                    $potongan = $data->harga * ($voucher->promo / 100);
                    if ($potongan > $voucher->max_potongan) {
                        $potongan = $voucher->max_potongan;
                    }
                    $data->harga -= $potongan;
                }
            }
        }

        $methods = Method::select('code', 'fee_percent', 'fix_fee', 'min_pembelian', 'max_pembelian')
            ->get()
            ->keyBy('code');
        Log::info('Payment methods retrieved', ['methods' => $methods, 'data' => $data]);
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
        if (isset($request->voucher)) {
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


            $dataMethod = Method::where('code', $request->payment_method)
                ->select('name', 'payment', 'tipe', 'code')
                ->first();
            Log::info('Payment method retrieved', ['method' => $dataMethod]);
            if ($request->payment_method == "11" || $request->payment_method == "17") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (0.70 / 100));
            } elseif ($request->payment_method == "20") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (0.90 / 100));
            } elseif ($request->payment_method == "23") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2 / 100));
            } elseif ($request->payment_method == "13") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "12" || $request->payment_method == "14") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "1") {
                $dataLayanan->harga = $dataLayanan->harga + 4900;
            } elseif ($request->payment_method == "4") {
                $dataLayanan->harga = $dataLayanan->harga + 4000;
            } elseif ($request->payment_method == "2" || $request->payment_method == "3" || $request->payment_method == "5" || $request->payment_method == "7" || $request->payment_method == "8") {
                $dataLayanan->harga = $dataLayanan->harga + 2500;
            } elseif ($request->payment_method == "9" || $request->payment_method == "10") {
                $dataLayanan->harga = $dataLayanan->harga + 3500;
            } elseif ($request->payment_method == "18" || $request->payment_method == "19") {
                $dataLayanan->harga = $dataLayanan->harga + 2500;
            } elseif ($request->payment_method == "21") {
                $dataLayanan->harga = $dataLayanan->harga + 1500;
            } elseif ($request->payment_method == "22") {
                $dataLayanan->harga = $dataLayanan->harga + 3500;
            } elseif ($request->payment_method == "QRISREALTIME") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (1.70 / 100));
            } elseif ($request->payment_method == "QRIS2" || $request->payment_method == "QRIS2") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (0.7 / 100) + 750);
            } elseif ($request->payment_method == "QRIS_CUSTOM"  || $request->payment_method == "QRIS_CUSTOM") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2.70 / 100));
            } elseif ($request->payment_method == "SHOPEEPAY_REALTIME" || $request->payment_method == "SHOPEEPAY_REALTIME") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "DANA_REALTIME" || $request->payment_method == "DANA_REALTIME") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3.20 / 100));
            } elseif ($request->payment_method == "GOPAY" || $request->payment_method == "LINKAJA") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "DANA" || $request->payment_method == "SHOPEEPAY" || $request->payment_method == "OVO" || $request->payment_method == "ASTRAPAY") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "VIRGO") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2 / 100));
            } elseif ($request->payment_method == "BCAVA") {
                $dataLayanan->harga = $dataLayanan->harga + 4250;
            } elseif ($request->payment_method == "BNIVA" || $request->payment_method == "MANDIRIVA" || $request->payment_method == "BSIVA" || $request->payment_method == "BRIVA" || $request->payment_method == "OTHERBANKVA") {
                $dataLayanan->harga = $dataLayanan->harga + 4250;
            } elseif ($request->payment_method == "BNCVA" || $request->payment_method == "PERMATAVAA") {
                $dataLayanan->harga = $dataLayanan->harga + 3000;
            } elseif ($request->payment_method == "CIMBVA" || $request->payment_method == "DANAMONVA") {
                $dataLayanan->harga = $dataLayanan->harga + 2500;
            } elseif ($request->payment_method == "PERMATAVA") {
                $dataLayanan->harga = $dataLayanan->harga + 2000;
            } elseif ($request->payment_method == "ALFAMART" || $request->payment_method == "INDOMARET" || $request->payment_method == "ALFAMIDI") {
                $dataLayanan->harga = $dataLayanan->harga + 3000;
            } else {
                $dataLayanan->harga = $dataLayanan->harga;
            }


            $sendData = "
                <div class='mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-700'>
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' aria-hidden='true' class='h-6 w-6 text-emerald-500'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M4.5 12.75l6 6 9-13.5'></path>
                    </svg>
                </div>
                
                <h3 class='text-lg font-bold leading-6 mt-4'>Buat Pesanan</h3>
                <div class='my-3 mt-3'>
                    <p class='text-sm'>Pastikan data akun Anda dan produk yang Anda pilih valid dan sesuai.</p>
                </div>
                
                <div class='mt-4' style='background-color: #494949; padding: 12px; border-radius: 10px;'>
                    <div class='flex items-center gap-2'>
                        <div class='divider'></div>
                        <h4 class='shrink-0 pr-4 text-sm font-semibold'>Data Player</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>User ID</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $request->uid;

            if (isset($request->zone) && !empty($request->zone)) {
                $sendData .= " ";
            }

            $sendData .= "</h4></div>";

            if (isset($request->zone) && !empty($request->zone)) {
                $sendData .= "
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Zone</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $request->zone . "</h4>
                    </div>";
            }

            $sendData .= "
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Username</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold nick' id='nick'>" . urldecode($username) . "</h4>
                    </div>
                    <br>
                    <div class='flex items-center gap-2'>
                        <div class='divider'></div>
                        <h4 class='shrink-0 pr-4 text-sm font-semibold'>Ringkasan Pembelian</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Item</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $item->layanan . "</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Product</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $produk->nama . "</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Price</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "</h4>
                    </div>
                <div class='flex justify-between'>
                    <h4 class='shrink-0 pr-4 text-sm'>Payment</h4>
                    <h4 class='shrink-0 pr-4 text-sm font-bold truncatee'>
                       " . strtoupper($dataMethod->name) . "
                    </h4>
                </div>
                </div>";

            return response()->json([
                'status' => true,
                'data' => $sendData
            ]);
        } else {

            if ($request->payment_method == "11" || $request->payment_method == "17") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (0.70 / 100));
            } elseif ($request->payment_method == "20") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (0.90 / 100));
            } elseif ($request->payment_method == "23") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2 / 100));
            } elseif ($request->payment_method == "13") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "12" || $request->payment_method == "14") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "1") {
                $dataLayanan->harga = $dataLayanan->harga + 4900;
            } elseif ($request->payment_method == "4") {
                $dataLayanan->harga = $dataLayanan->harga + 4000;
            } elseif ($request->payment_method == "2" || $request->payment_method == "3" || $request->payment_method == "5" || $request->payment_method == "7" || $request->payment_method == "8") {
                $dataLayanan->harga = $dataLayanan->harga + 2500;
            } elseif ($request->payment_method == "9" || $request->payment_method == "10") {
                $dataLayanan->harga = $dataLayanan->harga + 3500;
            } elseif ($request->payment_method == "18" || $request->payment_method == "19") {
                $dataLayanan->harga = $dataLayanan->harga + 2500;
            } elseif ($request->payment_method == "21") {
                $dataLayanan->harga = $dataLayanan->harga + 1500;
            } elseif ($request->payment_method == "22") {
                $dataLayanan->harga = $dataLayanan->harga + 3500;
            } elseif ($request->payment_method == "QRISREALTIME") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (1.70 / 100));
            } elseif ($request->payment_method == "QRIS2" || $request->payment_method == "QRIS2") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (0.7 / 100) + 750);
            } elseif ($request->payment_method == "QRIS_CUSTOM"  || $request->payment_method == "QRIS_CUSTOM") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2.70 / 100));
            } elseif ($request->payment_method == "SHOPEEPAY_REALTIME" || $request->payment_method == "SHOPEEPAY_REALTIME") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "DANA_REALTIME" || $request->payment_method == "DANA_REALTIME") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3.20 / 100));
            } elseif ($request->payment_method == "GOPAY" || $request->payment_method == "LINKAJA") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (3 / 100));
            } elseif ($request->payment_method == "DANA" || $request->payment_method == "SHOPEEPAY" || $request->payment_method == "OVOPUSH" || $request->payment_method == "ASTRAPAY") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2.5 / 100));
            } elseif ($request->payment_method == "VIRGO") {
                $dataLayanan->harga = $dataLayanan->harga + ($dataLayanan->harga * (2 / 100));
            } elseif ($request->payment_method == "BCAVA") {
                $dataLayanan->harga = $dataLayanan->harga + 4200;
            } elseif ($request->payment_method == "BNIVA" || $request->payment_method == "MANDIRIVA" || $request->payment_method == "BSIVA") {
                $dataLayanan->harga = $dataLayanan->harga + 3500;
            } elseif ($request->payment_method == "BNCVA" || $request->payment_method == "PERMATAVAA") {
                $dataLayanan->harga = $dataLayanan->harga + 3000;
            } elseif ($request->payment_method == "CIMBVA" || $request->payment_method == "DANAMONVA") {
                $dataLayanan->harga = $dataLayanan->harga + 2500;
            } elseif ($request->payment_method == "PERMATAVA") {
                $dataLayanan->harga = $dataLayanan->harga + 2000;
            } elseif ($request->payment_method == "ALFAMART" || $request->payment_method == "INDOMARET" || $request->payment_method == "ALFAMIDI") {
                $dataLayanan->harga = $dataLayanan->harga + 3000;
            } else {
                $dataLayanan->harga = $dataLayanan->harga;
            }

            if ($request->ktg_tipe === 'jokigendong') {
                $sendData = "<div class='mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-700'>
        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' aria-hidden='true' class='h-6 w-6 text-emerald-500'>
        <path stroke-linecap='round' stroke-linejoin='round' d='M4.5 12.75l6 6 9-13.5'></path>
        </svg>
        </div>
        
        <h3 class='text-lg font-bold leading-6 mt-4'>Buat Pesanan</h3>
        <div class='my-3 mt-3'><p class='text-sm '>Pastikan data akun jokigendong yang anda pilih valid dan sesuai.</p></div>
        
        <div class='' style='background-color: #494949;padding: 12px;border-radius: 10px;'>
        <div class='flex items-center gap-2'>
        <div class='divider' style='border-color: #fff;'></div><h4 class='shrink-0 pr-4 text-sm font-semibold'>Data jokigendong</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Nickname</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->nickname_joki</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Role</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->loginvia_joki</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Tanggal Main</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->tglmain_joki</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Jam Booking</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->jambooking_joki</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Catatan</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->catatan_joki</h4></div>
        <br>
        <div class='flex items-center gap-2'>
        <div class='divider' style='border-color: #fff;'>
        </div><h4 class='shrink-0 pr-4 text-sm font-semibold '>Ringkasan Pembelian</h4></div>
        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Price</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>Rp. " . number_format($dataLayanan->harga, 0, '.', ',') .
                    "</h4></div>
        </div>";
            } elseif ($request->ktg_tipe === 'joki') {
                $sendData = "<div class='mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-700'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' aria-hidden='true' class='h-6 w-6 text-emerald-500'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M4.5 12.75l6 6 9-13.5'></path>
                        </svg>
                        </div>
                        
                        <h3 class='text-lg font-bold leading-6 mt-4'>Buat Pesanan</h3>
                        <div class='my-3 mt-3'><p class='text-sm '>Pastikan data akun Joki yang anda pilih valid dan sesuai.</p></div>
                        
                        <div class='' style='background-color: #494949;padding: 12px;border-radius: 10px;'>
                        <div class='flex items-center gap-2'>
                        <div class='divider' style='border-color: #fff;'></div><h4 class='shrink-0 pr-4 text-sm font-semibold'>Data Joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Email</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->email_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Password</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->password_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Login Via</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->loginvia_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Nickname</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->nickname_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Request</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->request_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Catatan</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->catatan_joki</h4></div>
                        <br>
                        <div class='flex items-center gap-2'>
                        <div class='divider' style='border-color: #fff;'>
                        </div><h4 class='shrink-0 pr-4 text-sm font-semibold '>Ringkasan Pembelian</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Price</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>Rp. " . number_format($dataLayanan->harga, 0, '.', ',') .
                    "</h4></div>
                        </div>";
            } elseif ($request->ktg_tipe === 'vilogml') {
                $sendData = "<div class='mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-700'>
                        <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' aria-hidden='true' class='h-6 w-6 text-emerald-500'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M4.5 12.75l6 6 9-13.5'></path>
                        </svg>
                        </div>
                        
                        <h3 class='text-lg font-bold leading-6 mt-4'>Buat Pesanan</h3>
                        <div class='my-3 mt-3'><p class='text-sm '>Pastikan data Vilog ML yang anda pilih valid dan sesuai.</p></div>
                        
                        <div class='' style='background-color: #494949;padding: 12px;border-radius: 10px;'>
                        <div class='flex items-center gap-2'>
                        <div class='divider' style='border-color: #fff;'></div><h4 class='shrink-0 pr-4 text-sm font-semibold'>Data Joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Email</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->email_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Password</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->password_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Login Via</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->loginvia_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>User ID</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->nickname_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Server ID</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->request_joki</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Catatan</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>$request->catatan_joki</h4></div>
                        <br>
                        <div class='flex items-center gap-2'>
                        <div class='divider' style='border-color: #fff;'>
                        </div><h4 class='shrink-0 pr-4 text-sm font-semibold '>Ringkasan Pembelian</h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Item</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>" . $produk->nama . "
                        </h4></div>
                        <div class='flex justify-between'><h4 class='shrink-0 pr-4 text-sm '>Price</h4><h4 class='shrink-0 pr-4 text-sm font-bold'>Rp. " . number_format($dataLayanan->harga, 0, '.', ',') .
                    "</h4></div>
                        </div>";
            } else {
                $sendData = "
                <div class='mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-700'>
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' aria-hidden='true' class='h-6 w-6 text-emerald-500'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M4.5 12.75l6 6 9-13.5'></path>
                    </svg>
                </div>
                
                <h3 class='text-lg font-bold leading-6 mt-4'>Buat Pesanan</h3>
                <div class='my-3 mt-3'>
                    <p class='text-sm'>Pastikan data akun Anda dan produk yang Anda pilih valid dan sesuai.</p>
                </div>
                
                <div class='mt-4' style='background-color: #494949; padding: 12px; border-radius: 10px;'>
                    <div class='flex items-center gap-2'>
                        <div class='divider'></div>
                        <h4 class='shrink-0 pr-4 text-sm font-semibold'>Data Player</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>User ID</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $request->uid;

                if (isset($request->zone) && !empty($request->zone)) {
                    $sendData .= " ";
                }

                $sendData .= "</h4></div>";

                if (isset($request->zone) && !empty($request->zone)) {
                    $sendData .= "
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Zone</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $request->zone . "</h4>
                    </div>";
                }

                $sendData .= "
                    <div class='flex justify-between'>
                    </div>
                    <br>
                    <div class='flex items-center gap-2'>
                        <div class='divider'></div>
                        <h4 class='shrink-0 pr-4 text-sm font-semibold'>Ringkasan Pembelian</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Item</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $item->layanan . "</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Product</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>" . $produk->nama . "</h4>
                    </div>
                    <div class='flex justify-between'>
                        <h4 class='shrink-0 pr-4 text-sm'>Price</h4>
                        <h4 class='shrink-0 pr-4 text-sm font-bold'>Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "</h4>
                    </div>
                </div>";
            }





            return response()->json([
                'status' => true,
                'data' => $sendData
            ]);
        }
    }

    public function store(Request $request)
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
                'qty' => 'required|numeric|max:30',
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
                'qty' => 'required|numeric|max:30',
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


        if (Auth::check()) {
            if (Auth::user()->role == "Member") {
                $dataLayanan = Layanan::where('id', $request->service)->select('layanan', 'harga_member AS harga', 'kategori_id', 'provider_id', 'provider', 'profit_member AS profit', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            } else if (Auth::user()->role == "Platinum") {
                $dataLayanan = Layanan::where('id', $request->service)->select('layanan', 'harga_platinum AS harga', 'kategori_id', 'provider_id', 'provider', 'profit_platinum AS profit', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            } else if (Auth::user()->role == "Gold" || Auth::user()->role == "Admin") {
                $dataLayanan = Layanan::where('id', $request->service)->select('layanan', 'harga_gold AS harga', 'kategori_id', 'provider_id', 'provider', 'profit_gold AS profit', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
            }
        } else {
            $dataLayanan = Layanan::where('id', $request->service)->select('layanan', 'harga AS harga', 'kategori_id', 'provider_id', 'provider', 'profit', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale')->first();
        }

        if ($dataLayanan->is_flash_sale == 1 && $dataLayanan->expired_flash_sale >= date('Y-m-d H:i:s') && $dataLayanan->stock_flash_sale > 0) {
            $sisa = $dataLayanan->stock_flash_sale - 1;
            $updatesisa = Layanan::where('id', $request->service)->update(['stock_flash_sale' => $sisa]);
            $dataLayanan->harga = $dataLayanan->harga_flash_sale;
        }

        if (in_array($request->ktg_tipe, ['joki', 'jokigendong', 'vilogml'])) {
            $qty = $request->qty;
            if ($qty <= 0) {
                $qty = 1;
            }

            $dataLayanan->harga *= $qty;
        }



        if (isset($request->voucher)) {
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

                    $dataLayanan->harga = round($dataLayanan->harga - $potongan);
                    $voucher->decrement('stock');
                }
            }
        }


        $kategori = Kategori::where('id', $dataLayanan->kategori_id)->select('kode')->first();
        $prefik = DB::table('setting_webs')->where('id', 1)->first();
        $unik = date('Hs');
        $characters = '0123456789';
        $code = '';

        for ($i = 0; $i < 8; $i++) {
            $randomIndex = rand(0, strlen($characters) - 1);
            $code .= $characters[$randomIndex];
        }
        $kode_unik = $code;
        $order_id = $prefik->order_prefik . $unik . $kode_unik;
        $tokopay = new TokoPayController();
        $tripay = new TriPayController();
        $rand = rand(1, 1000);
        $no_pembayaran = '';
        $amount = '';
        $reference = '';
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $dataMethod = Method::where('code', $request->payment_method)->select('name', 'payment', 'tipe', 'code')->first();


        if ($request->payment_method == "SALDO") {
            $amount = $dataLayanan->harga;
        } else if ($request->payment_method == "OVO") {
            $amount = $dataLayanan->harga + $rand;
            $reference = '';
            if ($request->payment_method == "OVO") {
                $no_pembayaran = $api->ovo_admin;
                if ($amount < 10000) {
                    return response()->json(['status' => false, 'data' => 'Minimum jumlah pembayaran untuk metode pembayaran ini adalah Rp 10.000']);
                }
            } else {
                $no_pembayaran = $api->gopay_admin;
                if ($amount < 1000) {
                    return response()->json(['status' => false, 'data' => 'Minimum jumlah pembayaran untuk metode pembayaran ini adalah Rp 1.000']);
                }
            }
        } else {
            if ($dataMethod->payment == "tokopay") {
                $tokopayres = $tokopay->createOrder($dataLayanan->harga, $order_id, $request->payment_method);
                Log::info($tokopayres);
                if ($tokopayres['status'] != 'Success') return response()->json(['status' => false, 'data' => 'error']);

                if (isset($tokopayres['data'])) {
                    $no_pembayaran = $tokopayres['data']['pay_url'];
                    if (isset($tokopayres['data']['nomor_va'])) {
                        $no_pembayaran = $tokopayres['data']['nomor_va'];
                    } else if (isset($tokopayres['data']['qr_link'])) {
                        $no_pembayaran = $tokopayres['data']['qr_link'];
                    } else if (isset($tokopayres['data']['checkout_url'])) {
                        $no_pembayaran = $tokopayres['data']['checkout_url'];
                    }

                    $reference = $tokopayres['data']['trx_id'];
                    $amount = $tokopayres['data']['total_bayar'];
                }
            } else if ($dataMethod->payment == "tripay") {
                $listchannel = [];
                $channelResponse = $tripay->channel();
                Log::info('Tripay channel response', ['response' => $channelResponse]);
                if (isset($channelResponse->data) && is_array($channelResponse->data)) {
                    foreach ($channelResponse->data as $channel) {
                        if (isset($channel->code)) {
                            array_push($listchannel, $channel->code);
                        }
                    }
                }

                if (!in_array($request->payment_method, $listchannel)) {
                    return response()->json([
                        'status' => false,
                        'data'   => "Tipe pembayaran tidak sah"
                    ]);
                }

                $tripayres = $tripay->request($order_id, $dataLayanan->harga, $request->payment_method, $order_id . '@email.com', $request->nomor);

                if (!isset($tripayres['success']) || $tripayres['success'] != true) {
                    return response()->json(['status' => false, 'data' => $tripayres['msg'] ?? 'Gagal memproses pembayaran']);
                }

                $no_pembayaran = $tripayres['no_pembayaran'] ?? null;
                $reference = $tripayres['reference'] ?? null;
                $amount = $tripayres['amount'] ?? null;
            }
        }


        if ($request->payment_method == "SALDO") {

            $pesan =
                "*Pembayaran Berhasil*\n\n" .
                "No Invoice: *$order_id*\n" .
                "Layanan: *$dataLayanan->layanan*\n" .
                "ID : *$request->uid*\n" .
                "Server : *$request->zone*\n" .
                "Nickname : *$request->nickname*\n" .
                "Harga: *Rp. " . number_format($amount, 0, '.', ',') . "*\n" .
                "Status Pembayaran: *Dibayar*\n" .
                "Metode Pembayaran: *$dataMethod->name*\n\n" .
                "*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\n" .
                "INI ADALAH PESAN OTOMATIS";
        } else {
            $pesan =
                "*Menunggu Pembayaran*\n\n" .
                "No Invoice: *$order_id*\n" .
                "Layanan: *$dataLayanan->layanan*\n" .
                "ID : *$request->uid*\n" .
                "Server : *$request->zone*\n" .
                "Nickname : *$request->nickname*\n" .
                "Harga: *Rp. " . number_format($amount, 0, '.', ',') . "*\n" .
                "Status: *Menunggu Pembayaran*\n" .
                "Metode Pembayaran: *$dataMethod->name*\n" .
                "Kode Bayar / Nomor VA : *" . $no_pembayaran . "*\n\n" .

                "*Harap Dibayar Sebelum 3 Jam!* Segera lakukan pembayaran sesuai dengan kode bayar / nomor VA yang tercantum. Pastikan nominal pembayaran juga sesuai dengan total bayar.\n\n" .
                "*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\n" .
                "INI ADALAH PESAN OTOMATIS";
        }

        $tipe = '';

        if ($request->ktg_tipe == 'joki') {
            $tipe = 'joki';
        } else if ($request->ktg_tipe == 'voucher') {
            $tipe = 'voucher';
        } else if ($request->ktg_tipe == 'vilogml') {
            $tipe = 'vilogml';
        } else if ($request->ktg_tipe == 'jokigendong') {
            $tipe = 'jokigendong';
        } else {
            $tipe = 'game';
        }



        if ($request->payment_method != "SALDO") {

            $requestPesan = $this->msg($request->nomor, $pesan);
            $ipController = new IPAddressController();
            $ipAddress = $ipController->getIPAddress($request);


            $pembelian = new Pembelian();
            $pembelian->order_id = $order_id;
            $pembelian->user_id = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong'  && $request->ktg_tipe !== 'vilogml') ? $request->uid : '-';
            $pembelian->zone = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong'  && $request->ktg_tipe !== 'vilogml') ? $request->zone : '-';
            $pembelian->nickname = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong'  && $request->ktg_tipe !== 'vilogml') ? $request->nickname : ($request->ktg_tipe !== 'joki' ? $request->nickname_joki : '-');

            $pembelian->status = 'Pending';
            $pembelian->tipe_transaksi = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong' && $request->ktg_tipe !== 'vilogml') ? $tipe : $request->ktg_tipe;
            $pembelian->layanan = $dataLayanan->layanan;
            $pembelian->harga = $amount;
            $pembelian->profit = $amount * $dataLayanan->profit / 100;
            $pembelian->ip_address = $ipAddress;
            $pembelian->save();

            $pembayaran = new Pembayaran();
            $pembayaran->order_id = $order_id;
            $pembayaran->harga = $amount;
            $pembayaran->no_pembayaran = $no_pembayaran;
            $pembayaran->no_pembeli = $request->nomor;
            $pembayaran->status = 'Belum Lunas';
            $pembayaran->metode = $request->payment_method;
            $pembayaran->reference = $reference;
            $pembayaran->save();


            if ($request->ktg_tipe == 'joki' || $request->ktg_tipe == 'jokigendong' || $request->ktg_tipe == 'vilogml') {
                $jokian = DB::table('data_joki')->insert([
                    'order_id' => $order_id,
                    'email_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->email_joki : '-',
                    'password_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->password_joki : '-',
                    'loginvia_joki' => $request->loginvia_joki,
                    'nickname_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->nickname_joki : '-',
                    'request_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->request_joki : '-',
                    'catatan_joki' => $request->catatan_joki,

                    'tglmain_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->tglmain_joki,
                    'jambooking_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->jambooking_joki,
                    'qty' => $request->qty,
                    'status_joki' => 'Pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } else if ($request->payment_method == "SALDO") {
            $userKey = 'user_transaction_' . Auth::user()->id;

            // Cek apakah pengguna mencoba melakukan spam transaksi
            if (Cache::has($userKey)) {
                Log::info('Pengguna mencoba spam transaksi: ' . Auth::user()->username);
                return response()->json(['status' => false, 'data' => 'Respons Spam']);
            }

            // Set cache agar pengguna tidak bisa melakukan transaksi lagi dalam 1 menit
            Cache::put($userKey, true, now()->addMinutes(1));

            // Mulai transaksi database
            DB::beginTransaction();

            // Ambil transaksi terakhir pengguna
            $latestOrder = Pembelian::where('username', Auth::user()->username)
                ->where('created_at', '<', now())
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($latestOrder) {
                $latestPayment = Pembelian::where('username', $latestOrder->username)
                    ->latest()
                    ->first();

                if ($latestPayment) {
                    $latestPaymentDate = new \DateTime($latestPayment->created_at);
                    $diffrentTime = $latestPaymentDate->diff(new \DateTime(now()));
                    $totalMinutes = ($diffrentTime->days * 24 * 60) + ($diffrentTime->h * 60) + $diffrentTime->i;

                    // Cek apakah ada order yang dibuat dalam 1 menit terakhir
                    if ($totalMinutes <= 1) {
                        DB::rollBack();
                        Log::warning('Pengguna ' . Auth::user()->username . ' mencoba transaksi terlalu cepat.');
                        return response()->json(['status' => false, 'data' => 'Harap tunggu 1 menit sebelum melakukan transaksi lagi.']);
                    }
                }
            }

            // Cek saldo pengguna
            $user = User::where('username', Auth::user()->username)->lockForUpdate()->first();
            if ($dataLayanan->harga > $user->balance) {
                DB::rollBack();
                Log::warning('Pengguna ' . Auth::user()->username . ' mencoba transaksi dengan saldo tidak mencukupi.');
                return response()->json(['status' => false, 'data' => 'Saldo anda tidak mencukupi']);
            }

            // Update saldo pengguna
            $newBalance = $user->balance - $dataLayanan->harga;
            $user->update(['balance' => $newBalance]);

            DB::commit();
            Cache::forget($userKey);

            // Log saldo terbaru pengguna
            Log::info('Saldo pengguna ' . Auth::user()->username . ' setelah transaksi: ' . $newBalance);

            if ($dataLayanan->provider == "digiflazz") {
                $digi = new digiFlazzController;
                $random_part = Str::random(18, '123456789');
                $provider_order_id = 'WEJIZY' . $random_part;
                $order = $digi->order($request->uid, $request->zone, $dataLayanan->provider_id, $provider_order_id);

                if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "apigames") {
                $provider_order_id = rand(1, 10000);
                $apigames = new ApiGamesController;
                $order = $apigames->order($request->uid, $request->zone, $dataLayanan->provider_id, $provider_order_id);

                if ($order['data']['status'] == "Sukses") {
                    $order['transactionId'] = $provider_order_id;
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "vip") {
                $vip = new VipResellerController;
                $order = $vip->order($request->uid, $request->zone, $dataLayanan->provider_id);

                if ($order['result']) {
                    $order['status'] = true;
                    $provider_order_id = $order['data']['trxid'];
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "bangjeff") {
                $bangjeffo = new BangJeffController;

                $requestData = [
                    [
                        'name' => 'ID',
                        'value' => $request->uid
                    ]
                ];

                if ($request->has('zone')) {
                    $requestData[] = [
                        'name' => 'Server',
                        'value' => $request->zone
                    ];
                }

                $order = $bangjeffo->order($dataLayanan->provider_id, $order_id, 1, $requestData);

                if ($order['error'] == false) {
                    $provider_order_id = $order['data']['invoiceNumber'];
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "topupedia") {
                $topupedia = new TopupediaController;

                $requestData = [
                    [
                        'name' => 'ID',
                        'value' => $request->uid
                    ]
                ];

                if ($request->has('zone')) {
                    $requestData[] = [
                        'name' => 'Server',
                        'value' => $request->zone
                    ];
                }

                $order = $topupedia->order($dataLayanan->provider_id, $order_id, 1, $requestData);

                if ($order['error'] == false) {
                    $provider_order_id = $order['data']['invoiceNumber'];
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "moogold") {
                $moo = new MoogoldController();
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = 'WEJIZY-MG' . $random_part;
                $order = $moo->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                Log::info('callback moogold', $order);
                if (isset($order['status'])) {
                    $provider_order_id = $order['order_id'];
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "gameshop") {
                $gameshop =  new GameShopProvider;
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = 'WEJIZY-GS' . $random_part;
                $order = $gameshop->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                Log::info('callback gameshop ' . json_encode($order));
                if (isset($order['data']['order_no'])) {
                    $provider_order_id = $order['data']['order_no'];
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "strleyashop") {
                $strleyashop =  new StrleyaShopProvider;
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = 'WEJIZY-SS' . $random_part;
                $order = $strleyashop->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                Log::info('callback strleyashop ' . json_encode($order));
                if (isset($order['order_details']['bot_order_id'])) {
                    $provider_order_id = $order['order_details']['bot_order_id'];
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "yezzpay") {
                $yezzpay =  new YezzpayProvider;
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
                $order = $yezzpay->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                Log::info('response order yezzpay ' . json_encode($order));
                if (isset($order['data']['trx_id'])) {
                    $provider_order_id = $provider_order_id;
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "elitedias") {
                $elitedias =  new EliteDiasProvider;
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = 'WEJIZY-ED' . $random_part;
                $order = $elitedias->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                Log::info('response order elitedias ' . json_encode($order));
                if (isset($order['order_id'])) {
                    $provider_order_id = $order['order_id'];
                    $order['status'] = true;
                } else {
                    $order['status'] = false;
                }
            } else if ($dataLayanan->provider == "joki") {
                $provider_order_id = '';
                $order['status'] = true;
            } else if ($dataLayanan->provider == "jokigendong") {
                $provider_order_id = '';
                $order['status'] = true;
            } else if ($dataLayanan->provider == "vilogml") {
                $provider_order_id = '';
                $order['status'] = true;
            }


            if ($order['status']) {
                $pesanSukses =
                    "*Pembelian Sukses*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan: *$dataLayanan->layanan*\n" .
                    "ID : *$request->uid*\n" .
                    "Server : *$request->zone*\n" .
                    "Nickname : *$request->nickname*\n" .
                    "Harga: *Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Sukses*\n" .
                    "Metode Pembayaran: *$dataMethod->name*\n\n" .
                    "*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\n" .
                    "INI ADALAH PESAN OTOMATIS";

                $pesanSuksesAdmin =
                    "*Pembelian Sukses*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan: *$dataLayanan->layanan*\n" .
                    "ID : *$request->uid*\n" .
                    "Server : *$request->zone*\n" .
                    "Nickname : *$request->nickname*\n" .
                    "Harga: *Rp. " . number_format($dataLayanan->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Sukses*\n" .
                    "Metode Pembayaran: *$dataMethod->name*\n\n" .

                    "*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\n" .
                    "INI ADALAH PESAN OTOMATIS";

                $requestPesanSukses = $this->msg($request->nomor, $pesanSukses);
                $requestPesanSuksesAdmin = $this->msg($api->nomor_admin, $pesanSuksesAdmin);

                $ipController = new IPAddressController();
                $ipAddress = $ipController->getIPAddress($request);




                $pembelian = new Pembelian();
                $pembelian->username = Auth::user()->username;
                $pembelian->order_id = $order_id;
                $pembelian->user_id = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong' && $request->ktg_tipe !== 'vilogml') ? $request->uid : '-';
                $pembelian->zone = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong' && $request->ktg_tipe !== 'vilogml') ? $request->zone : '-';
                $pembelian->nickname = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong' && $request->ktg_tipe !== 'vilogml') ? $request->nickname : '-';
                $pembelian->log = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong' && $request->ktg_tipe !== 'vilogml') ? json_encode($order) : '';
                $pembelian->status = ($request->ktg_tipe !== 'joki' && $request->ktg_tipe !== 'jokigendong' && $request->ktg_tipe !== 'vilogml') ? 'Proses' : 'Proses';

                $pembelian->layanan = $dataLayanan->layanan;
                $pembelian->harga = $dataLayanan->harga;
                $pembelian->profit = $dataLayanan->harga * $dataLayanan->profit / 100;
                $pembelian->provider_order_id = $provider_order_id ? $provider_order_id : "";
                $pembelian->tipe_transaksi = $tipe;
                $pembelian->ip_address = $ipAddress;
                $pembelian->save();

                $pembayaran = new Pembayaran();
                $pembayaran->order_id = $order_id;
                $pembayaran->harga = $dataLayanan->harga;
                $pembayaran->no_pembayaran = "Balance Payment";
                $pembayaran->no_pembeli = $request->nomor;
                $pembayaran->status = 'Lunas';
                $pembayaran->metode = $request->payment_method;
                $pembayaran->reference = $reference;
                $pembayaran->save();


                if ($request->ktg_tipe == 'joki' || $request->ktg_tipe == 'jokigendong' || $request->ktg_tipe == 'vilogml') {
                    $jokian = DB::table('data_joki')->insert([
                        'order_id' => $order_id,
                        'email_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->email_joki : '-',
                        'password_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->password_joki : '-',
                        'loginvia_joki' => $request->loginvia_joki,
                        'nickname_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->nickname_joki : '-',
                        'request_joki' => $request->ktg_tipe !== 'jokigendong' ? $request->request_joki : '-',
                        'catatan_joki' => $request->catatan_joki,

                        'tglmain_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->tglmain_joki,
                        'jambooking_joki' => $request->ktg_tipe !== 'jokigendong' ? '-' : $request->jambooking_joki,
                        'qty' => $request->qty,
                        'status_joki' => 'Proses',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'data' => 'Server Error'
                ]);
            }
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
            $finalPrice = $basePrice;
            if ($paymentMethod == "11" || $paymentMethod == "17") {
                $finalPrice += ($basePrice * (0.70 / 100));
            } elseif ($paymentMethod == "20") {
                $finalPrice += ($basePrice * (0.90 / 100));
            } elseif ($paymentMethod == "23") {
                $finalPrice += ($basePrice * (2 / 100));
            } elseif ($paymentMethod == "13") {
                $finalPrice += ($basePrice * (3 / 100));
            } elseif ($paymentMethod == "12" || $paymentMethod == "14") {
                $finalPrice += ($basePrice * (3 / 100));
            } elseif ($paymentMethod == "1") {
                $finalPrice += 4900;
            } elseif ($paymentMethod == "4") {
                $finalPrice += 4000;
            } elseif ($paymentMethod == "2" || $paymentMethod == "3" || $paymentMethod == "5" || $paymentMethod == "7" || $paymentMethod == "8") {
                $finalPrice += 2500;
            } elseif ($paymentMethod == "9" || $paymentMethod == "10") {
                $finalPrice += 3500;
            } elseif ($paymentMethod == "18" || $paymentMethod == "19") {
                $finalPrice += 2500;
            } elseif ($paymentMethod == "21") {
                $finalPrice += 1500;
            } elseif ($paymentMethod == "22") {
                $finalPrice += 3500;
            } elseif ($paymentMethod == "QRISREALTIME") {
                $finalPrice += ($basePrice(1.70 / 100));
            } elseif ($paymentMethod == "QRIS2") {
                $finalPrice += ($basePrice * (0.7 / 100) + 750);
            } elseif ($paymentMethod == "QRIS_CUSTOM") {
                $finalPrice += ($basePrice * (2.70 / 100));
            } elseif ($paymentMethod == "SHOPEEPAY_REALTIME") {
                $finalPrice += ($basePrice * (3 / 100));
            } elseif ($paymentMethod == "DANA_REALTIME") {
                $finalPrice += ($basePrice * (3.20 / 100));
            } elseif (in_array($paymentMethod, ["GOPAY", "LINKAJA"])) {
                $finalPrice += ($basePrice * (3 / 100));
            } elseif (in_array($paymentMethod, ["DANA", "SHOPEEPAY", "OVOPUSH", "ASTRAPAY"])) {
                $finalPrice += ($basePrice * (2.5 / 100));
            } elseif ($paymentMethod == "VIRGO") {
                $finalPrice += ($basePrice * (2 / 100));
            } elseif ($paymentMethod == "BCAVA") {
                $finalPrice += 4200;
            } elseif (in_array($paymentMethod, ["BNIVA", "MANDIRIVA", "BSIVA"])) {
                $finalPrice += 3500;
            } elseif (in_array($paymentMethod, ["BNCVA", "PERMATAVAA"])) {
                $finalPrice += 3000;
            } elseif (in_array($paymentMethod, ["CIMBVA", "DANAMONVA"])) {
                $finalPrice += 2500;
            } elseif ($paymentMethod == "PERMATAVA") {
                $finalPrice += 2000;
            } elseif (in_array($paymentMethod, ["ALFAMART", "INDOMARET", "ALFAMIDI"])) {
                $finalPrice += 3000;
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
}