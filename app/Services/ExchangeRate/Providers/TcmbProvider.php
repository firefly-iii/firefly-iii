<?php

/*
 * TcmbProvider.php
 *
 * Central Bank of the Republic of Türkiye (https://www.tcmb.gov.tr).
 * Daily XML, no key.
 *
 * Endpoints:
 *   - today:       https://www.tcmb.gov.tr/kurlar/today.xml
 *   - by date:     https://www.tcmb.gov.tr/kurlar/YYYYMM/DDMMYYYY.xml
 *
 * Each <Currency> element has:
 *   <Unit>1</Unit>
 *   <Kod>USD</Kod>
 *   <ForexBuying>...</ForexBuying>
 *   <ForexSelling>...</ForexSelling>
 *
 * We use the average of ForexBuying and ForexSelling as the mid-market
 * rate. Some currencies only publish BanknoteBuying/Selling — fall back
 * accordingly. Meaning: Unit units of Kod = rate TRY. We emit foreign -> TRY.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

final class TcmbProvider extends AbstractNationalRateProvider
{
    public static function country(): string
    {
        return 'TR';
    }

    public static function base(): string
    {
        return 'TRY';
    }

    public static function name(): string
    {
        return 'TCMB';
    }

    public function fetchRates(Carbon $date): array
    {
        $today = Carbon::today(config('app.timezone'));
        $url   = $date->lt($today)
            ? sprintf(
                'https://www.tcmb.gov.tr/kurlar/%s/%s.xml',
                $date->format('Ym'),
                $date->format('dmY'),
            )
            : 'https://www.tcmb.gov.tr/kurlar/today.xml';

        $body = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if (!$xml instanceof SimpleXMLElement) {
            Log::warning('[TCMB] Failed to parse XML response.');

            return [];
        }

        // Try to extract the publication date from the Tarih_Date root attrs.
        $when     = $date->copy()->startOfDay();
        $dateAttr = (string) ($xml['Date'] ?? '');
        if ('' !== $dateAttr) {
            $parsed = Carbon::createFromFormat('m/d/Y', $dateAttr, config('app.timezone'));
            if ($parsed instanceof Carbon) {
                $when = $parsed->startOfDay();
            }
        }

        $base   = self::base();
        $quotes = [];
        foreach ($xml->Currency as $currency) {
            $code = strtoupper(trim((string) $currency['Kod']));
            $unit = (float) trim((string) $currency->Unit);
            if ('' === $code || $unit <= 0.0) {
                continue;
            }

            $buy   = (float) trim((string) $currency->ForexBuying);
            $sell  = (float) trim((string) $currency->ForexSelling);
            if ($buy <= 0.0 && $sell <= 0.0) {
                $buy  = (float) trim((string) $currency->BanknoteBuying);
                $sell = (float) trim((string) $currency->BanknoteSelling);
            }
            $mid = ($buy > 0.0 && $sell > 0.0) ? ($buy + $sell) / 2.0 : max($buy, $sell);
            if ($mid <= 0.0) {
                continue;
            }

            $perBaseUnit = $this->perUnit($mid, $unit);

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
