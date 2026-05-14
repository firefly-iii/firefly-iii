<?php

/*
 * BoiProvider.php
 *
 * Bank of Israel (https://boi.org.il).
 * JSON via PublicApi, no key. Only latest rates are exposed.
 *
 * Endpoint:
 *   https://boi.org.il/PublicApi/GetExchangeRates  (Accept: application/json)
 *
 * Response shape:
 *   {"exchangeRates":[
 *     {"key":"USD","currentExchangeRate":2.908,"unit":1,"lastUpdate":"2026-05-13T..."},
 *     {"key":"JPY","currentExchangeRate":1.8434,"unit":100,...},
 *     ...
 *   ]}
 *
 * Meaning: unit units of key = currentExchangeRate ILS. We normalise and emit
 * foreign -> ILS.
 *
 * Historical lookups are not supported by this endpoint: for past dates BoI
 * publishes them through SDMX (XML), but day-granularity is the same value.
 * For practical Firefly III use we fetch the latest snapshot regardless of
 * the requested $date when $date is today; for past dates we still call the
 * same endpoint (BoI returns the last business day's rates).
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

final class BoiProvider extends AbstractNationalRateProvider
{
    private const string ENDPOINT = 'https://boi.org.il/PublicApi/GetExchangeRates';

    public static function country(): string
    {
        return 'IL';
    }

    public static function base(): string
    {
        return 'ILS';
    }

    public static function name(): string
    {
        return 'BoI';
    }

    public function fetchRates(Carbon $date): array
    {
        $body = $this->httpGet(self::ENDPOINT);
        if (null === $body) {
            return [];
        }

        try {
            $parsed = json_decode($body, true);
        } catch (JsonException $e) {
            Log::warning(sprintf('[BoI] Invalid JSON: %s', $e->getMessage()));

            return [];
        }
        if (!is_array($parsed) || !isset($parsed['exchangeRates']) || !is_array($parsed['exchangeRates'])) {
            return [];
        }

        $base   = self::base();
        $quotes = [];
        foreach ($parsed['exchangeRates'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = isset($row['key']) ? strtoupper((string) $row['key']) : '';
            $rate = isset($row['currentExchangeRate']) ? (float) $row['currentExchangeRate'] : 0.0;
            $unit = isset($row['unit']) ? (float) $row['unit'] : 1.0;
            if ('' === $code || $rate <= 0.0 || $unit <= 0.0) {
                continue;
            }

            // perBaseUnit: how many ILS per 1 unit of $code.
            $perBaseUnit = $this->perUnit($rate, $unit);

            $when = $date->copy()->startOfDay();
            if (isset($row['lastUpdate'])) {
                $parsedDate = Carbon::parse((string) $row['lastUpdate']);
                $when       = $parsedDate->startOfDay();
            }

            $quotes[] = new RateQuote(
                fromCode: $code,
                toCode: $base,
                date: $when,
                rate: $perBaseUnit,
            );
        }

        return $quotes;
    }
}
