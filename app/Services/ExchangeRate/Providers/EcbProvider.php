<?php

/*
 * EcbProvider.php
 *
 * European Central Bank (https://www.ecb.europa.eu).
 * Publishes EUR-based daily reference rates as XML, no key required.
 *
 * Endpoints:
 *   - latest day:  https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml
 *   - 90 days:     https://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist-90d.xml
 *   - full series: https://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist.xml
 *
 * For historical lookups we use eurofxref-hist-90d.xml (covers all
 * realistic cron schedules and avoids downloading the multi-MB full file).
 *
 * Response shape:
 *   <gesmes:Envelope ...>
 *     <Cube>
 *       <Cube time="2026-05-12">
 *         <Cube currency="USD" rate="1.0843"/>
 *         ...
 *       </Cube>
 *     </Cube>
 *   </gesmes:Envelope>
 *
 * Each rate means: 1 EUR = $rate $currency. Already in the desired form.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

final class EcbProvider extends AbstractNationalRateProvider
{
    private const string DAILY_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
    private const string HIST_URL  = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist-90d.xml';

    public static function country(): string
    {
        return 'EU';
    }

    public static function base(): string
    {
        return 'EUR';
    }

    public static function name(): string
    {
        return 'ECB';
    }

    public function fetchRates(Carbon $date): array
    {
        $today  = Carbon::today(config('app.timezone'));
        $useHist = $date->lt($today);
        $url     = $useHist ? self::HIST_URL : self::DAILY_URL;
        $body    = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if (!$xml instanceof SimpleXMLElement) {
            Log::warning('[ECB] Failed to parse XML response.');

            return [];
        }

        // Namespaces: gesmes + default ECB namespace.
        $namespaces = $xml->getNamespaces(true);
        $default    = $namespaces[''] ?? 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref';
        $xml->registerXPathNamespace('e', $default);

        // Find the <Cube time="..."> closest to (but not after) $date.
        $dayNodes = $xml->xpath('//e:Cube[@time]');
        if (false === $dayNodes || 0 === count($dayNodes)) {
            return [];
        }

        $targetDay = $date->format('Y-m-d');
        $chosen    = null;
        $chosenDay = null;
        foreach ($dayNodes as $dayNode) {
            $time = (string) $dayNode['time'];
            if ('' === $time) {
                continue;
            }
            if ($time > $targetDay) {
                continue; // never reach into the future
            }
            if (null === $chosenDay || $time > $chosenDay) {
                $chosen    = $dayNode;
                $chosenDay = $time;
            }
        }
        if (!$chosen instanceof SimpleXMLElement || null === $chosenDay) {
            return [];
        }

        $base   = self::base();
        $when   = Carbon::createFromFormat('Y-m-d', $chosenDay, config('app.timezone'));
        if (!$when instanceof Carbon) {
            return [];
        }
        $when->startOfDay();

        $quotes = [];
        foreach ($chosen->children($default) as $rateNode) {
            $code = (string) $rateNode['currency'];
            $rate = (float) $rateNode['rate'];
            if ('' === $code || $rate <= 0.0) {
                continue;
            }

            $quotes[] = new RateQuote(
                fromCode: $base,
                toCode: $code,
                date: $when->copy(),
                rate: $rate,
            );
        }

        return $quotes;
    }
}
