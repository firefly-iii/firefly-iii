<?php

/*
 * RiksbankProvider.php
 *
 * Sveriges Riksbank (https://api.riksbank.se).
 * SWEA REST API, no key.
 *
 * For "today's snapshot" the cheapest call is:
 *   GET https://api.riksbank.se/swea/v1/Observations/Latest/ByGroup/130
 *   → [{"seriesId":"SEKUSDPMI","date":"2026-05-13","value":9.317}, ...]
 *
 * For historical dates we hit per-series:
 *   GET https://api.riksbank.se/swea/v1/Observations/{seriesId}/{from}/{to}
 *
 * seriesId format: SEK<XXX>PMI  where XXX is the foreign currency code.
 *                 value = how many SEK for the natural unit of XXX
 *                 (Riksbank publishes PMI = "Average rate per unit"; for
 *                  small-denomination currencies the unit is implicitly
 *                  scaled, but the API returns the final SEK price for the
 *                  series' stated unit which is always 1).
 *
 * We emit foreign -> SEK.
 *
 * Rate limit: 30 requests / 10s per IP. We make at most 1 request for
 * "today" mode, and N requests for historical mode (with a short pause).
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

final class RiksbankProvider extends AbstractNationalRateProvider
{
    private const string GROUP_LATEST = 'https://api.riksbank.se/swea/v1/Observations/Latest/ByGroup/130';

    public static function country(): string
    {
        return 'SE';
    }

    public static function base(): string
    {
        return 'SEK';
    }

    public static function name(): string
    {
        return 'Riksbank';
    }

    public function fetchRates(Carbon $date): array
    {
        $body = $this->httpGet(self::GROUP_LATEST);
        if (null === $body) {
            return [];
        }

        try {
            $items = json_decode($body, true);
        } catch (JsonException $e) {
            Log::warning(sprintf('[Riksbank] Invalid JSON: %s', $e->getMessage()));

            return [];
        }
        if (!is_array($items)) {
            return [];
        }

        $base   = self::base();
        $quotes = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $seriesId = isset($item['seriesId']) ? (string) $item['seriesId'] : '';
            $value    = isset($item['value']) ? (float) $item['value'] : 0.0;
            $when     = isset($item['date']) ? (string) $item['date'] : '';

            // seriesId format: "SEK<XXX>PMI"
            if (!preg_match('/^SEK([A-Z]{3})PMI$/', $seriesId, $m)) {
                continue;
            }
            $code = $m[1];
            if ($value <= 0.0 || '' === $when) {
                continue;
            }
            // Skip stale series (e.g. legacy EUR-zone currencies frozen in 2002).
            $observed = Carbon::createFromFormat('Y-m-d', $when, config('app.timezone'));
            if (!$observed instanceof Carbon) {
                continue;
            }
            // Older than 30 days from the requested date → treat as stale.
            if ($observed->diffInDays($date, true) > 30) {
                continue;
            }

            $quotes[] = new RateQuote(
                fromCode: $code,
                toCode: $base,
                date: $observed->startOfDay(),
                rate: $value,
            );
        }

        return $quotes;
    }
}
