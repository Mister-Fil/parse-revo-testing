<?php

namespace Parser;

use Exception;
use Swoole\Http\Request;
use Swoole\Table;
use Symfony\Component\DomCrawler\Crawler;

class Serp
{

    /**
     * @var string
     */
    private string $baseYandexURL = "https://yandex.ru/search/touch/?service=www.yandex&ui=webmobileapp.yandex&numdoc=50&lr=213&p=0&text=";

    /**
     * @var array|bool[]
     */
    private array $tlds = [
        "рф" => true,
        "com.ru" => true,
        "exnet.su" => true,
        "net.ru" => true,
        "org.ru" => true,
        "pp.ru" => true,
        "ru" => true,
        "ru.net" => true,
        "su" => true,
        "aero" => true,
        "asia" => true,
        "biz" => true,
        "com" => true,
        "info" => true,
        "mobi" => true,
        "name" => true,
        "net" => true,
        "org" => true,
        "pro" => true,
        "tel" => true,
        "travel" => true,
        "xxx" => true,
        "adygeya.ru" => true,
        "adygeya.su" => true,
        "arkhangelsk.su" => true,
        "balashov.su" => true,
        "bashkiria.ru" => true,
        "bashkiria.su" => true,
        "bir.ru" => true,
        "bryansk.su" => true,
        "cbg.ru" => true,
        "dagestan.ru" => true,
        "dagestan.su" => true,
        "grozny.ru" => true,
        "ivanovo.su" => true,
        "kalmykia.ru" => true,
        "kalmykia.su" => true,
        "kaluga.su" => true,
        "karelia.su" => true,
        "khakassia.su" => true,
        "krasnodar.su" => true,
        "kurgan.su" => true,
        "lenug.su" => true,
        "marine.ru" => true,
        "mordovia.ru" => true,
        "mordovia.su" => true,
        "msk.ru" => true,
        "msk.su" => true,
        "murmansk.su" => true,
        "mytis.ru" => true,
        "nalchik.ru" => true,
        "nalchik.su" => true,
        "nov.ru" => true,
        "nov.su" => true,
        "obninsk.su" => true,
        "penza.su" => true,
        "pokrovsk.su" => true,
        "pyatigorsk.ru" => true,
        "sochi.su" => true,
        "spb.ru" => true,
        "spb.su" => true,
        "togliatti.su" => true,
        "troitsk.su" => true,
        "tula.su" => true,
        "tuva.su" => true,
        "vladikavkaz.ru" => true,
        "vladikavkaz.su" => true,
        "vladimir.ru" => true,
        "vladimir.su" => true,
        "vologda.su" => true,
        "ad" => true,
        "ae" => true,
        "af" => true,
        "ai" => true,
        "al" => true,
        "am" => true,
        "aq" => true,
        "as" => true,
        "at" => true,
        "aw" => true,
        "ax" => true,
        "az" => true,
        "ba" => true,
        "be" => true,
        "bg" => true,
        "bh" => true,
        "bi" => true,
        "bj" => true,
        "bm" => true,
        "bo" => true,
        "bs" => true,
        "bt" => true,
        "ca" => true,
        "cc" => true,
        "cd" => true,
        "cf" => true,
        "cg" => true,
        "ch" => true,
        "ci" => true,
        "cl" => true,
        "cm" => true,
        "cn" => true,
        "co" => true,
        "co.ao" => true,
        "co.bw" => true,
        "co.ck" => true,
        "co.fk" => true,
        "co.id" => true,
        "co.il" => true,
        "co.in" => true,
        "co.ke" => true,
        "co.ls" => true,
        "co.mz" => true,
        "co.no" => true,
        "co.nz" => true,
        "co.th" => true,
        "co.tz" => true,
        "co.uk" => true,
        "co.uz" => true,
        "co.za" => true,
        "co.zm" => true,
        "co.zw" => true,
        "com.ai" => true,
        "com.ar" => true,
        "com.au" => true,
        "com.bd" => true,
        "com.bn" => true,
        "com.br" => true,
        "com.cn" => true,
        "com.cy" => true,
        "com.eg" => true,
        "com.et" => true,
        "com.fj" => true,
        "com.gh" => true,
        "com.gn" => true,
        "com.gt" => true,
        "com.gu" => true,
        "com.hk" => true,
        "com.jm" => true,
        "com.kh" => true,
        "com.kw" => true,
        "com.lb" => true,
        "com.lr" => true,
        "com.mt" => true,
        "com.mv" => true,
        "com.ng" => true,
        "com.ni" => true,
        "com.np" => true,
        "com.nr" => true,
        "com.om" => true,
        "com.pa" => true,
        "com.pl" => true,
        "com.py" => true,
        "com.qa" => true,
        "com.sa" => true,
        "com.sb" => true,
        "com.sg" => true,
        "com.sv" => true,
        "com.sy" => true,
        "com.tr" => true,
        "com.tw" => true,
        "com.ua" => true,
        "com.uy" => true,
        "com.ve" => true,
        "com.vi" => true,
        "com.vn" => true,
        "com.ye" => true,
        "cr" => true,
        "cu" => true,
        "cx" => true,
        "cz" => true,
        "de" => true,
        "dj" => true,
        "dk" => true,
        "dm" => true,
        "do" => true,
        "dz" => true,
        "ec" => true,
        "ee" => true,
        "es" => true,
        "eu" => true,
        "fi" => true,
        "fo" => true,
        "fr" => true,
        "ga" => true,
        "gd" => true,
        "ge" => true,
        "gf" => true,
        "gg" => true,
        "gi" => true,
        "gl" => true,
        "gm" => true,
        "gp" => true,
        "gr" => true,
        "gs" => true,
        "gy" => true,
        "hk" => true,
        "hm" => true,
        "hn" => true,
        "hr" => true,
        "ht" => true,
        "hu" => true,
        "ie" => true,
        "im" => true,
        "in" => true,
        "in.ua" => true,
        "io " => true,
        "ir" => true,
        "is" => true,
        "it" => true,
        "je" => true,
        "jo" => true,
        "jp" => true,
        "kg" => true,
        "ki" => true,
        "kiev.ua" => true,
        "kn" => true,
        "kr" => true,
        "ky" => true,
        "kz" => true,
        "li" => true,
        "lk" => true,
        "lt" => true,
        "lu" => true,
        "lv" => true,
        "ly" => true,
        "ma" => true,
        "mc" => true,
        "md" => true,
        "me.uk" => true,
        "mg" => true,
        "mk" => true,
        "mo" => true,
        "mp" => true,
        "ms" => true,
        "mt" => true,
        "mu" => true,
        "mw" => true,
        "mx" => true,
        "my" => true,
        "na" => true,
        "nc" => true,
        "net.cn" => true,
        "nf" => true,
        "ng" => true,
        "nl" => true,
        "no" => true,
        "nu" => true,
        "nz" => true,
        "org.cn" => true,
        "org.uk" => true,
        "pe" => true,
        "ph" => true,
        "pk" => true,
        "pl" => true,
        "pn" => true,
        "pr" => true,
        "ps" => true,
        "pt" => true,
        "re" => true,
        "ro" => true,
        "rs" => true,
        "rw" => true,
        "sd" => true,
        "se" => true,
        "sg" => true,
        "si" => true,
        "sk" => true,
        "sl" => true,
        "sm" => true,
        "sn" => true,
        "so" => true,
        "sr" => true,
        "st" => true,
        "sz" => true,
        "tc" => true,
        "td" => true,
        "tg" => true,
        "tj" => true,
        "tk" => true,
        "tl" => true,
        "tm" => true,
        "tn" => true,
        "to" => true,
        "tt" => true,
        "tw" => true,
        "ua" => true,
        "ug" => true,
        "uk" => true,
        "us" => true,
        "vc" => true,
        "vg" => true,
        "vn" => true,
        "vu" => true,
        "ws" => true,
        "academy" => true,
        "accountant" => true,
        "accountants" => true,
        "actor" => true,
        "adult" => true,
        "africa" => true,
        "agency" => true,
        "airforce" => true,
        "apartments" => true,
        "app" => true,
        "army" => true,
        "art" => true,
        "associates" => true,
        "attorney" => true,
        "auction" => true,
        "audio" => true,
        "auto" => true,
        "band" => true,
        "bank" => true,
        "bar" => true,
        "bargains" => true,
        "bayern" => true,
        "beer" => true,
        "berlin" => true,
        "best" => true,
        "bet" => true,
        "bid" => true,
        "bike" => true,
        "bingo" => true,
        "bio" => true,
        "black" => true,
        "blackfriday" => true,
        "blog" => true,
        "blue" => true,
        "boutique" => true,
        "broker" => true,
        "brussels" => true,
        "build" => true,
        "builders" => true,
        "business" => true,
        "buzz" => true,
        "cab" => true,
        "cafe" => true,
        "cam" => true,
        "camera" => true,
        "camp" => true,
        "capital" => true,
        "car" => true,
        "cards" => true,
        "care" => true,
        "career" => true,
        "careers" => true,
        "cars" => true,
        "casa " => true,
        "cash" => true,
        "casino" => true,
        "cat" => true,
        "catering" => true,
        "center" => true,
        "ceo" => true,
        "charity" => true,
        "chat" => true,
        "cheap" => true,
        "christmas" => true,
        "church" => true,
        "city" => true,
        "claims" => true,
        "cleaning" => true,
        "click" => true,
        "clinic" => true,
        "clothing" => true,
        "cloud" => true,
        "club" => true,
        "coach" => true,
        "codes" => true,
        "coffee" => true,
        "college" => true,
        "cologne" => true,
        "community" => true,
        "company" => true,
        "computer" => true,
        "condos" => true,
        "construction" => true,
        "consulting" => true,
        "contractors" => true,
        "cooking" => true,
        "cool" => true,
        "coop" => true,
        "country" => true,
        "coupons" => true,
        "courses" => true,
        "credit" => true,
        "creditcard" => true,
        "cricket" => true,
        "cruises" => true,
        "dance" => true,
        "date" => true,
        "dating" => true,
        "deals" => true,
        "degree" => true,
        "delivery" => true,
        "democrat" => true,
        "dental" => true,
        "dentist" => true,
        "desi" => true,
        "design" => true,
        "diamonds" => true,
        "diet" => true,
        "digital" => true,
        "direct" => true,
        "directory" => true,
        "discount" => true,
        "doctor" => true,
        "dog" => true,
        "domains" => true,
        "download" => true,
        "earth" => true,
        "education" => true,
        "email" => true,
        "energy" => true,
        "engineer" => true,
        "engineering" => true,
        "enterprises" => true,
        "equipment" => true,
        "estate" => true,
        "events" => true,
        "exchange" => true,
        "expert" => true,
        "exposed" => true,
        "express" => true,
        "fail" => true,
        "faith" => true,
        "family" => true,
        "fans" => true,
        "farm" => true,
        "fashion" => true,
        "film" => true,
        "finance" => true,
        "financial" => true,
        "fish" => true,
        "fishing" => true,
        "fit" => true,
        "fitness" => true,
        "flights" => true,
        "florist" => true,
        "flowers" => true,
        "fm" => true,
        "football" => true,
        "forex" => true,
        "forsale" => true,
        "foundation" => true,
        "fun" => true,
        "fund" => true,
        "furniture" => true,
        "futbol" => true,
        "fyi" => true,
        "gallery" => true,
        "game" => true,
        "games" => true,
        "garden" => true,
        "gent" => true,
        "gift" => true,
        "gifts" => true,
        "gives" => true,
        "glass" => true,
        "global" => true,
        "gmbh" => true,
        "gold" => true,
        "golf" => true,
        "graphics" => true,
        "gratis" => true,
        "green" => true,
        "gripe" => true,
        "group" => true,
        "guide" => true,
        "guitars" => true,
        "guru" => true,
        "haus" => true,
        "healthcare" => true,
        "help" => true,
        "hiphop" => true,
        "hockey" => true,
        "holdings" => true,
        "holiday" => true,
        "horse" => true,
        "hospital" => true,
        "host" => true,
        "hosting" => true,
        "house" => true,
        "how" => true,
        "immo" => true,
        "immobilien" => true,
        "industries" => true,
        "ink" => true,
        "institute" => true,
        "insure" => true,
        "international" => true,
        "investments" => true,
        "irish" => true,
        "jewelry" => true,
        "jobs" => true,
        "juegos" => true,
        "kaufen" => true,
        "kim" => true,
        "kitchen" => true,
        "kiwi" => true,
        "land" => true,
        "lawyer" => true,
        "lease" => true,
        "legal" => true,
        "life" => true,
        "lighting" => true,
        "limited" => true,
        "limo" => true,
        "link" => true,
        "live" => true,
        "llc" => true,
        "loan" => true,
        "loans" => true,
        "lol" => true,
        "london" => true,
        "love" => true,
        "ltd" => true,
        "luxe" => true,
        "luxury" => true,
        "maison" => true,
        "management" => true,
        "market" => true,
        "marketing" => true,
        "mba" => true,
        "media" => true,
        "memorial" => true,
        "men" => true,
        "menu" => true,
        "miami" => true,
        "moda" => true,
        "moe" => true,
        "mom" => true,
        "money" => true,
        "mortgage" => true,
        "moscow" => true,
        "movie" => true,
        "navy" => true,
        "network" => true,
        "news" => true,
        "ninja" => true,
        "observer" => true,
        "one" => true,
        "onl" => true,
        "online" => true,
        "ooo" => true,
        "page" => true,
        "paris" => true,
        "partners" => true,
        "parts" => true,
        "party" => true,
        "pet" => true,
        "photo" => true,
        "photography" => true,
        "photos" => true,
        "pics" => true,
        "pictures" => true,
        "pink" => true,
        "pizza" => true,
        "plumbing" => true,
        "plus" => true,
        "poker" => true,
        "press" => true,
        "productions" => true,
        "promo" => true,
        "properties" => true,
        "property" => true,
        "protection" => true,
        "pub" => true,
        "qpon" => true,
        "racing" => true,
        "radio" => true,
        "radio.am" => true,
        "radio.fm" => true,
        "realty" => true,
        "recipes" => true,
        "red" => true,
        "rehab" => true,
        "reisen" => true,
        "rent" => true,
        "rentals" => true,
        "repair" => true,
        "report" => true,
        "republican" => true,
        "rest" => true,
        "restaurant" => true,
        "review" => true,
        "reviews" => true,
        "rich" => true,
        "rip" => true,
        "rocks" => true,
        "rodeo" => true,
        "run" => true,
        "sale" => true,
        "salon" => true,
        "sarl" => true,
        "school" => true,
        "schule" => true,
        "science" => true,
        "security" => true,
        "services" => true,
        "sex" => true,
        "sexy" => true,
        "shiksha" => true,
        "shoes" => true,
        "shop" => true,
        "shopping" => true,
        "show" => true,
        "singles" => true,
        "site" => true,
        "ski" => true,
        "soccer" => true,
        "social" => true,
        "software" => true,
        "solar" => true,
        "solutions" => true,
        "soy" => true,
        "space" => true,
        "sport" => true,
        "store" => true,
        "stream" => true,
        "studio" => true,
        "study" => true,
        "style" => true,
        "sucks" => true,
        "supplies" => true,
        "supply" => true,
        "support" => true,
        "surf" => true,
        "surgery" => true,
        "systems" => true,
        "tatar" => true,
        "tattoo" => true,
        "tax" => true,
        "taxi" => true,
        "team" => true,
        "tech" => true,
        "technology" => true,
        "tennis" => true,
        "theater" => true,
        "theatre" => true,
        "tickets" => true,
        "tienda" => true,
        "tips" => true,
        "tires" => true,
        "tirol" => true,
        "today" => true,
        "tools" => true,
        "top" => true,
        "tours" => true,
        "town" => true,
        "toys" => true,
        "trade" => true,
        "trading" => true,
        "training" => true,
        "tube" => true,
        "tv" => true,
        "university" => true,
        "uno" => true,
        "vacations" => true,
        "vegas" => true,
        "ventures" => true,
        "vet" => true,
        "viajes" => true,
        "video" => true,
        "villas" => true,
        "vin" => true,
        "vip" => true,
        "vision" => true,
        "vodka" => true,
        "vote" => true,
        "voting" => true,
        "voto" => true,
        "voyage" => true,
        "watch" => true,
        "webcam" => true,
        "website" => true,
        "wedding" => true,
        "wien" => true,
        "wiki" => true,
        "win" => true,
        "wine" => true,
        "work" => true,
        "works" => true,
        "world" => true,
        "wtf" => true,
        "xyz" => true,
        "yoga" => true,
        "zone" => true,
        "дети" => true,
        "москва" => true,
        "онлайн" => true,
        "орг" => true,
        "рус" => true,
        "сайт" => true,
    ];

    /**
     * @var array|bool[]
     */
    private array $blackList = [
        'yandex.ru' => true,
        'market.yandex.ru' => true,
        'm.market.yandex.ru' => true,
        'yabs.yandex.ru' => true,
        'o.yandex.ru' => true,
    ];

    /**
     * @var array|string[]
     */
    private array $items = [];

    /**
     * @param Request $request
     * @param Table $table
     * @param array $headers
     * @return array|bool
     * @throws Exception
     */
    public function parseYandex(Request $request, Table $table, array $headers): array|bool
    {
        $keySearchMd5 = md5($request->get['search']);
        if ($table->exists($keySearchMd5)) {
            $body = $table->get($keySearchMd5, 'body');
        } else {
            $body = (string)$this->fetchYandexResponse($request->get['search'], $headers);
            $table->set($keySearchMd5, [
                'search' => $request->get['search'],
                'body' => $body,
            ]);
        }
        if ($this->parseYandexResponse($body) > 0) {
            return $this->items;
        } else {
            return false;
        }
    }

    /**
     * @param string $search
     * @param array $headers
     * @return bool|string
     * @throws Exception
     */
    private function fetchYandexResponse(string $search, array $headers): bool|string
    {
        $curl = curl_init();
        $curl_options = [
            CURLOPT_URL => $this->baseYandexURL . urlencode($search),
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        curl_setopt_array($curl, $curl_options);

        if (false === ($result = curl_exec($curl))) {
//            dump(curl_error($curl));
            throw new Exception('Http request failed');
        }

        curl_close($curl);
        return $result;
    }

    /**
     * @param string $body
     * @return int
     */
    private function parseYandexResponse(string $body): int
    {
        $serpItems = (new Crawler($body))->filter('div.serp-item')->each(fn(Crawler $node, $i) => new Crawler($node->outerHtml()));

        $i = 0;
        /** @var Crawler $serpItem */
        foreach ($serpItems as $serpItem) {
            $aExists = $serpItem->filterXPath('//body/div')->attr('data-fast-name');
            $cidExists = $serpItem->filterXPath('//body/div')->attr('data-cid');
            $lExists = $serpItem->filter('div.Label')->count();
            $oExists = $serpItem->filter('span.organic__advLabel')->count();
//            dump('data-cid: ' . $cidExists);
            if (!$lExists && !$aExists && !$oExists && $cidExists) {
                $linkCrawler = $serpItem->filter('a.Link');
                if ($linkCrawler->count() < 1) {
                    continue;
                }

                $link = $linkCrawler->link();
                $uri = $link->getUri();
                $dcStr = $linkCrawler->attr("data-counter");

                if (!$dcStr) {
                    continue;
                }

                $dc = json_decode($dcStr);
                if (count($dc) < 2 && $dc[0] != 'rc') {
                    continue;
                }
                $urlStr = $dc[1];

                $parseUrl = parse_url($uri);

                if (empty($parseUrl['host']) || !empty($this->blackList[strtolower($parseUrl['host'])])) {
                    continue;
                }

                $this->items[] = [
//                    'Uri' => $uri,
//                    'ParseUrl' => $parseUrl,
                    'Scheme' => $parseUrl['scheme'] ?? 'http',
                    'Host' => $this->getRootDomain($parseUrl['host']),
                    'Url' => $urlStr,
                ];
                $i++;
            }
        }
        return $i;
    }

    /**
     * @param string $domain
     * @return string
     */
    private function getRootDomain(string $domain): string
    {
        $domain = strtolower($domain);
        $parts = explode('.', $domain);
        if (count($parts) < 3) {
            return $domain;
        }

        if (!empty($this->tlds[implode('.', array_slice($parts, -(count($parts) - 2)))])) {
            return implode('.', array_slice($parts, -(count($parts) - 3)));
        }

        return implode('.', array_slice($parts, -(count($parts) - 2)));
    }

}