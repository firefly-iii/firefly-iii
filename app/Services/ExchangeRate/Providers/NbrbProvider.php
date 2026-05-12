<?php

/*
 * NbrbProvider.php
 *
 * National Bank of the Republic of Belarus (https://api.nbrb.by).
 * Publishes BYN-based rates as JSON, no API key required.
 *
 * Endpoint (daily rates, periodicity=0 = daily):
 *   https://api.nbrb.by/exrates/rates?ondate=YYYY-MM-DD&periodicity=0
 *
 * Response item shape:
 *   {
 *     "Cur_ID": 145,
 *     "Date": "2026-05-12T00:00:00",
 *     "Cur_Abbreviation": "USD",
 *     "Cur_Scale": 1,
 *     "Cur_OfficialRate": 3.2451
 *   }
 *
 * Meaning: Cur_Scale units of Cur_Abbreviation = Cur_OfficialRate BYN.
 * We normalise to "1 BYN = X foreign" by inverting (Cur_Scale / Cur_OfficialRate).
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

final class NbrbProvider extends AbstractNationalRateProvider
{
    private const string ENDPOINT = 'https://api.nbrb.by/exrates/rates';

    public static function country(): string
    {
        return 'BY';
    }

    public static function base(): string
    {
        return 'BYN';
    }

    public static function name(): string
    {
        return 'NBRB';
    }

    public function fetchRates(Carbon $date): array
    {
        $url  = sprintf(
            '%s?ondate=%s&periodicity=0',
            self::ENDPOINT,
            $date->format('Y-m-d'),
        );
        $body = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        try {
            $items = json_decode($body, true);
        } catch (JsonException $e) {
            Log::warning(sprintf('[NBRB] Invalid JSON: %s', $e->getMessage()));

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
            $code  = isset($item['Cur_Abbreviation']) ? (string) $item['Cur_Abbreviation'] : '';
            $scale = isset($item['Cur_Scale']) ? (float) $item['Cur_Scale'] : 0.0;
            $rate  = isset($item['Cur_OfficialRate']) ? (float) $item['Cur_OfficialRate'] : 0.0;
            if ('' === $code || $scale <= 0.0 || $rate <= 0.0) {
                continue;
            }

            // perBaseUnit: how many BYN per 1 unit of $code.
            $perBaseUnit = $this->perUnit($rate, $scale);
            // We want: 1 BYN -> X $code  =>  invert.
            $byn2foreign = 1.0 / $perBaseUnit;

            $quotes[] = new RateQuote(
                fromCode: $base,
                toCode: $code,
                date: $date->copy()->startOfDay(),
                rate: $byn2foreign,
            );
        }

        return $quotes;
    }
}
