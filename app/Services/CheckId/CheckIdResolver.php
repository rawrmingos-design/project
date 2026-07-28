<?php

namespace App\Services\CheckId;

use App\Http\Controllers\ApiCheckController;
use App\Models\Kategori;
use App\Models\Layanan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckIdResolver
{
    private const SUPPORTED_CODES = [
        'arena-breakout', 'mobile-legends', 'magic-chess-go-go', 'magic-chess-gogo', 'magic-chess-gogo-up',
        'free-fire', '8-ball-pool', 'point-blank', 'arena-of-valor', 'genshin-impact', 'dragon-raja',
        'valorant', 'metal-slug-awakening', 'sausage-man', 'ea-sports-fc-mobile', 'undawn',
        'call-of-duty-mobile', 'pubg-mobile-tp', 'honor-of-kings-tp', 'honkai-star-rail',
        'steam-wallet-code-indonesia', 'free-fire-max', 'astra-knights-of-veda', 'au2-mobile',
        'advent-of-godlegends', 'aether-gazer', 'among-heroes-fantasy-samkok', 'angel-squad-dg',
        'aov-dg', 'arcane-saga', 'arena-mania-magic-heroes', 'asphalt-9-legends', 'atlantica-online-dg',
        'astral-guardians-cyber-fantasy', 'auto-chess', 'azur-lane', 'bleach-mobile-3d', 'badlanders',
        'barbarq', 'battlenet-dg', 'be-the-king-judge-destiny', 'bermuda', 'bigo-live',
        'bigo-live-voucher', 'bilibili-dg', 'bioskop-online', 'blade-x-odyssey-of-heroes',
        'bleach-mobile-3d-dg', 'blizzard-gift-card-dg', 'blood-strike', 'boxing-star-dg',
        'captain-tsubasa-ace', 'captain-tsubasa-dream-team', 'city-of-crime-gang-wars',
        'clash-royale', 'clash-of-clans', 'cooking-adventure', 'crasher-origin',
        'dead-target-zombie-games-3d', 'dg-mini-games-dg', 'dark-continent-mist', 'diablo-immortal',
        'garena-dg', 'ragnarok-m-eternal-love-big-cat-coin', 'laplace-m', 'speed-drifters',
        'era-of-celestials', 'higgs-domino', 'heroes-evolved', 'lifeafter', 'marvel-snap', 'hago',
        'tom-and-jerry-chase', 'one-punch-man-the-strongest', 'ludo-club', 'league-of-legends',
        'league-of-legends-wild-rift-dg', 'state-of-survival', 'ys-6-mobile-vng', 'tower-of-fantasy-a',
        'stumble-guys', 'honkai-impact-3', 'goddes-victory-nikke-tp', 'ragnarok-x-next-generation',
        'revelation-infinite-journey', 'lita', 'teen-patti-gold', 'hay-day', 'zepeto', 'kings-choice',
        'harry-potter-magic-awakened', 'life-makeover', 'brawl-stars', 'growtopia', 'identity-v',
        'farlight-84', 'football-master-2', 'eos-red', 'eggy-party', 'snowbreak-containment-zone',
        'rhythm-hive', 'teamfight-tactics-mobile', 'punishing-gray-raven', 'octopath-traveler-cotc',
        'love-and-deepspace', 'pixel-gun-3d', 'the-legend-of-neverland-dg',
        'heroic-uncle-kim-idle-rpg', 'world-war-heroes', 'moonlight-blade-m', 'king-of-avalon',
    ];

    private const GAME_NAMES = [
        'mobile-legends' => 'Mobile Legends',
        'magic-chess-go-go' => 'Magic Chess Go Go',
        'magic-chess-gogo' => 'Magic Chess Go Go',
        'magic-chess-gogo-up' => 'Magic Chess Go Go',
        'free-fire' => 'Free Fire',
        'free-fire-max' => 'Free Fire MAX',
        'honkai-star-rail' => 'Honkai Star Rail',
        'genshin-impact' => 'Genshin Impact',
        'valorant' => 'Valorant',
        'pubg-mobile-tp' => 'PUBG Mobile',
        'honor-of-kings-tp' => 'Honor of Kings',
        'garena-dg' => 'Garena Shell',
        'higgs-domino' => 'Higgs Domino',
        'point-blank' => 'Point Blank',
        'arena-of-valor' => 'Arena of Valor',
        'dragon-raja' => 'Dragon Raja',
        'call-of-duty-mobile' => 'Call of Duty Mobile',
        '8-ball-pool' => '8 Ball Pool',
        'metal-slug-awakening' => 'Metal Slug Awakening',
        'sausage-man' => 'Sausage Man',
        'ea-sports-fc-mobile' => 'FC Mobile',
        'undawn' => 'Undawn',
        'steam-wallet-code-indonesia' => 'Steam Wallet Code - Indonesia',
        'astra-knights-of-veda' => 'ASTRA: Knights of Veda',
        'au2-mobile' => 'AU2 Mobile',
        'advent-of-godlegends' => 'Advent of God:Legends',
        'aether-gazer' => 'Aether Gazer',
        'among-heroes-fantasy-samkok' => 'Among Heroes: Fantasy Samkok',
        'angel-squad-dg' => 'Angel Squad (DG)',
        'aov-dg' => 'AoV (DG)',
        'arcane-saga' => 'Arcane Saga',
        'arena-breakout' => 'Arena Breakout',
        'arena-mania-magic-heroes' => 'Arena Mania: Magic Heroes',
        'asphalt-9-legends' => 'Asphalt 9: Legends',
        'astral-guardians-cyber-fantasy' => 'Astral Guardians: Cyber Fantasy',
        'atlantica-online-dg' => 'Atlantica Online (DG)',
        'auto-chess' => 'Auto Chess',
        'azur-lane' => 'Azur Lane',
        'bleach-mobile-3d' => 'BLEACH Mobile 3D',
        'badlanders' => 'Badlanders',
        'barbarq' => 'BarbarQ',
        'battlenet-dg' => 'Battlenet (DG)',
        'be-the-king-judge-destiny' => 'Be The King: Judge Destiny',
        'bigo-live' => 'Bigo Live',
        'bigo-live-voucher' => 'Bigo Live Voucher',
        'bilibili-dg' => 'Bilibili (DG)',
        'bioskop-online' => 'Bioskop Online',
        'blade-x-odyssey-of-heroes' => 'Blade X: Odyssey of Heroes',
        'bleach-mobile-3d-dg' => 'Bleach Mobile 3D (DG)',
        'blizzard-gift-card-dg' => 'Blizzard Gift Card (DG)',
        'blood-strike' => 'Blood Strike',
        'boxing-star-dg' => 'Boxing Star (DG)',
        'brawl-stars' => 'Brawl Stars',
        'captain-tsubasa-ace' => 'Captain Tsubasa: Ace',
        'captain-tsubasa-dream-team' => 'Captain Tsubasa: Dream Team',
        'city-of-crime-gang-wars' => 'City of Crime: Gang Wars',
        'clash-royale' => 'Clash Royale',
        'clash-of-clans' => 'Clash of Clans',
        'cooking-adventure' => 'Cooking Adventure',
        'crasher-origin' => 'Crasher Origin',
        'dead-target-zombie-games-3d' => 'DEAD TARGET: Zombie Games 3D',
        'dg-mini-games-dg' => 'DG Mini Games (DG)',
        'dark-continent-mist' => 'Dark Continent: Mist',
        'diablo-immortal' => 'Diablo: Immortal',
        'ragnarok-m-eternal-love-big-cat-coin' => 'Ragnarok M: Eternal Love Big Cat Coin',
        'laplace-m' => 'Laplace M',
        'speed-drifters' => 'Speed Drifters',
        'era-of-celestials' => 'Era of Celestials',
        'heroes-evolved' => 'Heroes Evolved',
        'lifeafter' => 'LifeAfter',
        'marvel-snap' => 'MARVEL SNAP',
        'hago' => 'Hago',
        'tom-and-jerry-chase' => 'Tom and Jerry: Chase',
        'one-punch-man-the-strongest' => 'ONE PUNCH MAN: The Strongest',
        'ludo-club' => 'Ludo Club',
        'league-of-legends-wild-rift-dg' => 'League of Legends : Wild Rift (DG)',
        'league-of-legends' => 'League of Legends',
        'state-of-survival' => 'State of Survival',
        'ys-6-mobile-vng' => 'YS 6 Mobile VNG',
        'tower-of-fantasy-a' => 'Tower of Fantasy (Slow)',
        'stumble-guys' => 'Stumble Guys',
        'honkai-impact-3' => 'Honkai Impact 3',
        'goddes-victory-nikke-tp' => 'Goddes Victory: Nikke (FAST)',
        'ragnarok-x-next-generation' => 'Ragnarok X: Next Generation',
        'revelation-infinite-journey' => 'Revelation: Infinite Journey',
        'lita' => 'Lita',
        'teen-patti-gold' => 'Teen Patti Gold',
        'hay-day' => 'Hay Day',
        'zepeto' => 'ZEPETO',
        'kings-choice' => 'Kings Choice',
        'harry-potter-magic-awakened' => 'Harry Potter: Magic Awakened',
        'life-makeover' => 'Life Makeover',
        'growtopia' => 'Growtopia',
        'identity-v' => 'Identity V',
        'farlight-84' => 'Farlight 84',
        'football-master-2' => 'Football Master 2',
        'eos-red' => 'EOS RED',
        'eggy-party' => 'EGGY PARTY',
        'snowbreak-containment-zone' => 'Snowbreak: Containment Zone',
        'rhythm-hive' => 'Rhythm Hive',
        'teamfight-tactics-mobile' => 'Teamfight Tactics Mobile',
        'punishing-gray-raven' => 'Punishing: Gray Raven',
        'octopath-traveler-cotc' => 'OCTOPATH TRAVELER: CotC',
        'love-and-deepspace' => 'Love and Deepspace',
        'pixel-gun-3d' => 'Pixel Gun 3D',
        'the-legend-of-neverland-dg' => 'The Legend of Neverland (DG)',
        'heroic-uncle-kim-idle-rpg' => 'Heroic Uncle Kim: Idle RPG',
        'world-war-heroes' => 'World War Heroes',
        'moonlight-blade-m' => 'Moonlight Blade M',
        'king-of-avalon' => 'King of Avalon',
    ];

    private const ZONELESS_CODES = [
        'free-fire', 'free-fire-max', 'pubg-mobile-tp', 'honor-of-kings-tp', 'point-blank',
        'arena-of-valor', 'genshin-impact', 'dragon-raja', 'call-of-duty-mobile', '8-ball-pool',
        'valorant', 'metal-slug-awakening', 'sausage-man', 'ea-sports-fc-mobile', 'undawn',
        'steam-wallet-code-indonesia', 'au2-mobile', 'aether-gazer', 'angel-squad-dg', 'aov-dg',
        'arcane-saga', 'arena-breakout', 'atlantica-online-dg', 'auto-chess', 'battlenet-dg',
        'bigo-live', 'bigo-live-voucher', 'bilibili-dg', 'bioskop-online', 'blade-x-odyssey-of-heroes',
        'blizzard-gift-card-dg', 'boxing-star-dg', 'brawl-stars', 'captain-tsubasa-ace',
        'captain-tsubasa-dream-team', 'city-of-crime-gang-wars', 'clash-royale', 'clash-of-clans',
        'dead-target-zombie-games-3d', 'dg-mini-games-dg', 'diablo-immortal', 'garena-dg',
        'ragnarok-m-eternal-love-big-cat-coin', 'laplace-m', 'speed-drifters', 'higgs-domino',
        'heroes-evolved', 'marvel-snap', 'hago', 'one-punch-man-the-strongest', 'ludo-club',
        'league-of-legends-wild-rift-dg', 'league-of-legends', 'state-of-survival', 'ys-6-mobile-vng',
        'tower-of-fantasy-a', 'stumble-guys', 'honkai-impact-3', 'revelation-infinite-journey', 'lita',
        'teen-patti-gold', 'hay-day', 'zepeto', 'kings-choice', 'life-makeover', 'brawl-stars',
        'growtopia', 'identity-v', 'farlight-84', 'football-master-2', 'eggy-party', 'rhythm-hive',
        'teamfight-tactics-mobile', 'love-and-deepspace', 'pixel-gun-3d', 'the-legend-of-neverland-dg',
        'heroic-uncle-kim-idle-rpg', 'world-war-heroes', 'moonlight-blade-m', 'king-of-avalon',
    ];

    public function isZoneless(string $categoryCode): bool
    {
        $categoryCode = $this->normalizeCode($categoryCode);

        if (in_array($categoryCode, self::ZONELESS_CODES, true)) {
            return true;
        }

        if ($item = $this->getCatalogItem($categoryCode)) {
            return isset($item['hasZoneId']) && $item['hasZoneId'] === false;
        }

        return false;
    }

    public function resolveForCategory(
        Kategori|string $category,
        string $uid,
        ?string $zone = null,
        ?Layanan $layanan = null,
    ): array {
        $resolvedCategory = $category instanceof Kategori
            ? $category
            : Kategori::query()->where('kode', $this->normalizeCode($category))->first();

        $categoryCode = $this->normalizeCode($resolvedCategory?->kode ?? (string) $category);
        $categoryType = strtolower(trim((string) ($resolvedCategory?->tipe ?? 'game')));

        if (! in_array($categoryType, ['game', 'populer'], true)) {
            return [
                'status' => ['code' => 204, 'message' => 'Account validation skipped'],
                'skip_check' => true,
            ];
        }

        if (! $this->supports($categoryCode) && ! $this->hasDynamicCheckIdInquiry($layanan)) {
            return ['status' => ['code' => 400, 'message' => 'Game not supported for validation']];
        }

        $gameName = $this->gameName($categoryCode);
        $zoneForCheck = $this->zoneForCheck($categoryCode, $zone);

        return (new ApiCheckController($layanan))->check($uid, $zoneForCheck, $gameName);
    }

    private function fetchCatalog(): array
    {
        return Cache::remember('checkid_catalog_v1', now()->addDay(), function () {
            $baseUrl = rtrim(trim((string) config('providers.check_id.selfhosted.base_url', 'https://cekid.jasakoding.web.id')), '/');
            $apiKey = trim((string) config('providers.check_id.selfhosted.api_key', ''));

            try {
                $req = Http::connectTimeout(3)->timeout(5);
                if ($apiKey !== '') {
                    $req->withHeaders(['x-api-key' => $apiKey]);
                }

                $response = $req->get($baseUrl . '/api/');

                if ($response->successful()) {
                    $json = $response->json();
                    if (isset($json['data']) && is_array($json['data'])) {
                        return collect($json['data'])->keyBy('slug')->toArray();
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("ApiCheckController:fetchCatalog - Failed to fetch check id catalog", ['message' => $e->getMessage()]);
            }
            return [];
        });
    }

    private function getCatalogItem(string $categoryCode): ?array
    {
        $catalog = $this->fetchCatalog();
        return $catalog[$categoryCode] ?? null;
    }

    private function supports(string $categoryCode): bool
    {
        if ($this->getCatalogItem($categoryCode) !== null) {
            return true;
        }
        return in_array($categoryCode, self::SUPPORTED_CODES, true);
    }

    private function gameName(string $categoryCode): string
    {
        if ($item = $this->getCatalogItem($categoryCode)) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
        return self::GAME_NAMES[$categoryCode]
            ?? ucwords(str_replace(['-', '_'], ' ', $categoryCode));
    }

    private function zoneForCheck(string $categoryCode, ?string $zone): ?string
    {
        if ($this->isZoneless($categoryCode)) {
            return null;
        }

        $zone = trim((string) $zone);

        return $zone !== '' ? $zone : null;
    }

    private function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[\s_]+/', '-', $code) ?? '';

        return trim($code, '-');
    }

    private function hasDynamicCheckIdInquiry(?Layanan $layanan): bool
    {
        return $layanan !== null
            && (bool) ($layanan->check_id_enabled ?? false)
            && strtolower(trim((string) ($layanan->check_id_provider ?? ''))) === 'digiflazz'
            && trim((string) ($layanan->check_id_provider_sku ?? '')) !== '';
    }
}
