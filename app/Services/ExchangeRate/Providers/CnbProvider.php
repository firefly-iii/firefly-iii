<?php

/*
 * CnbProvider.php
 *
 * Czech National Bank (https://www.cnb.cz).
 * Pipe-separated TXT, no key.
 *
 * Endpoints:
 *   - latest: https://www.cnb.cz/en/financial-markets/foreign-exchange-market/central-bank-exchange-rate-fixing/central-bank-exchange-rate-fixing/daily.txt
 *   - by date: ...daily.txt?date=DD.MM.YYYY
 *
 * Format:
 *   line 1: "13 May 2026 #90"
 *   line 2: "Country|Currency|Amount|Code|Rate"
 *   line N: "Australia|dollar|1|AUD|15.071"
 *
 * Meaning: Amount units of Code = Rate CZK. We normalise to "1 Code = X CZK"
 * and emit foreign -> CZK.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;

final class CnbProvider extends AbstractNationalRateProvider
{
    private const string ENDPOINT = 'https://www.cnb.cz/en/financial-markets/foreign-exchange-market/central-bank-exchange-rate-fixing/central-bank-exchange-rate-fixing/daily.txt';

    public static function country(): string
    {
        return 'CZ';
    }

    public static function base(): string
    {
        return 'CZK';
    }

    public static function name(): string
    {
        return 'CNB';
    }

    public function fetchRates(Carbon $date): array
    {
        $url  = sprintf('%s?date=%s', self::ENDPOINT, $date->format('d.m.Y'));
        $body = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        $lines = preg_split("/\r?\n/", trim($body));
        if (false === $lines || count($lines) < 3) {
            Log::warning('[CNB] Response too short, cannot parse.');

            return [];
        }

        // Line 1: "DD MMM YYYY #N"
        $when = $date->copy()->startOfDay();
        if (preg_match('/^(\d{1,2}\s+\w+\s+\d{4})\b/', $lines[0], $m)) {
            $parsed = Carbon::createFromFormat('j M Y', $m[1], config('app.timezone'));
            if ($parsed instanceof Carbon) {
                $when = $parsed->startOfDay();
            }
        }

        $base   = self::base();
        $quotes = [];
        // Skip header (line 0 = date, line 1 = column names).
        for ($i = 2, $n = count($lines); $i < $n; ++$i) {
            $parts = explode('|', $lines[$i]);
            if (5 !== count($parts)) {
                continue;
            }
            [$country, $currency, $amount, $code, $rate] = $parts;
            $code   = strtoupper(trim($code));
            $amount = (float) trim($amount);
            $rate   = (float) trim($rate);
            if ('' === $code || $amount <= 0.0 || $rate <= 0.0) {
                continue;
            }

            // perBaseUnit: how many CZK for 1 unit of $code.
            $perBaseUnit = $this->perUnit($rate, $amount);

            $quotes[] = new RateQuote(
                fromCode: $code,
                toCode: $base,
                date: $when->copy(),
                rate: $perBaseUnit,
            );
        }

        return $quotes;
    }
}
