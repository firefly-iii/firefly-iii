<?php

/*
 * NbpProvider.php
 *
 * Narodowy Bank Polski (https://api.nbp.pl).
 * Table A: mid exchange rates of foreign currencies vs PLN. JSON, no key.
 *
 * Endpoints:
 *   - latest:      https://api.nbp.pl/api/exchangerates/tables/A/?format=json
 *   - by date:     https://api.nbp.pl/api/exchangerates/tables/A/{YYYY-MM-DD}/?format=json
 *
 * Response shape (table A):
 *   [
 *     {
 *       "table": "A",
 *       "no": "...",
 *       "effectiveDate": "2026-05-13",
 *       "rates": [
 *         {"currency": "...", "code": "USD", "mid": 3.7234},
 *         ...
 *       ]
 *     }
 *   ]
 *
 * Meaning: mid is "1 unit of $code = mid PLN". We emit foreign -> PLN.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

final class NbpProvider extends AbstractNationalRateProvider
{
    public static function country(): string
    {
        return 'PL';
    }

    public static function base(): string
    {
        return 'PLN';
    }

    public static function name(): string
    {
        return 'NBP';
    }

    public function fetchRates(Carbon $date): array
    {
        $today = Carbon::today(config('app.timezone'));
        $url   = $date->lt($today)
            ? sprintf('https://api.nbp.pl/api/exchangerates/tables/A/%s/?format=json', $date->format('Y-m-d'))
            : 'https://api.nbp.pl/api/exchangerates/tables/A/?format=json';

        $body = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        try {
            $parsed = json_decode($body, true);
        } catch (JsonException $e) {
            Log::warning(sprintf('[NBP] Invalid JSON: %s', $e->getMessage()));

            return [];
        }
        if (!is_array($parsed) || !isset($parsed[0]['rates']) || !is_array($parsed[0]['rates'])) {
            return [];
        }

        $effective = isset($parsed[0]['effectiveDate']) ? (string) $parsed[0]['effectiveDate'] : '';
        $when      = '' !== $effective
            ? Carbon::createFromFormat('Y-m-d', $effective, config('app.timezone'))
            : $date->copy();
        if (!$when instanceof Carbon) {
            $when = $date->copy();
        }
        $when->startOfDay();

        $base   = self::base();
        $quotes = [];
        foreach ($parsed[0]['rates'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = isset($row['code']) ? strtoupper((string) $row['code']) : '';
            $mid  = isset($row['mid']) ? (float) $row['mid'] : 0.0;
            if ('' === $code || $mid <= 0.0) {
                continue;
            }
            $quotes[] = new RateQuote(
                fromCode: $code,
                toCode: $base,
                date: $when->copy(),
                rate: $mid,
            );
        }

        return $quotes;
    }
}
