<?php

/*
 * NorgesBankProvider.php
 *
 * Norges Bank (https://www.norges-bank.no).
 * SDMX 2.1 open data API, no key. We use the CSV format for simplicity.
 *
 * Endpoint:
 *   https://data.norges-bank.no/api/data/EXR/B..NOK.SP?format=csv
 *       &startPeriod=YYYY-MM-DD&endPeriod=YYYY-MM-DD
 *
 * Format (semicolon-separated, with header):
 *   FREQ;Frequency;BASE_CUR;...;UNIT_MULT;Unit Multiplier;...;TIME_PERIOD;OBS_VALUE
 *
 * Meaning: BASE_CUR is "1 BASE_CUR" (or 100 if Unit Multiplier is "Hundreds")
 *          = OBS_VALUE NOK. We normalise and emit BASE_CUR -> NOK.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;

final class NorgesBankProvider extends AbstractNationalRateProvider
{
    public static function country(): string
    {
        return 'NO';
    }

    public static function base(): string
    {
        return 'NOK';
    }

    public static function name(): string
    {
        return 'NorgesBank';
    }

    public function fetchRates(Carbon $date): array
    {
        // Pull a 10-day window ending on $date so we always hit the closest
        // business-day fixing, regardless of weekends/holidays.
        $end   = $date->copy()->startOfDay();
        $start = $end->copy()->subDays(10);
        $url   = sprintf(
            'https://data.norges-bank.no/api/data/EXR/B..NOK.SP?format=csv&startPeriod=%s&endPeriod=%s',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        );
        $body  = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        $lines = preg_split("/\r?\n/", trim($body));
        if (false === $lines || count($lines) < 2) {
            return [];
        }

        // Build a column-name => index map from the header row.
        $columns = $this->parseCsvRow($lines[0]);
        $idx     = array_flip($columns);
        foreach (['BASE_CUR', 'UNIT_MULT', 'TIME_PERIOD', 'OBS_VALUE'] as $required) {
            if (!isset($idx[$required])) {
                Log::warning(sprintf('[NorgesBank] Missing column %s in CSV header.', $required));

                return [];
            }
        }

        $base = self::base();
        // Pick the latest TIME_PERIOD <= $date for each currency.
        $best = [];
        for ($i = 1, $n = count($lines); $i < $n; ++$i) {
            if ('' === $lines[$i]) {
                continue;
            }
            $row     = $this->parseCsvRow($lines[$i]);
            $cur     = isset($row[$idx['BASE_CUR']]) ? strtoupper((string) $row[$idx['BASE_CUR']]) : '';
            $unitStr = isset($row[$idx['UNIT_MULT']]) ? (string) $row[$idx['UNIT_MULT']] : '0';
            $period  = isset($row[$idx['TIME_PERIOD']]) ? (string) $row[$idx['TIME_PERIOD']] : '';
            $value   = isset($row[$idx['OBS_VALUE']]) ? (float) $row[$idx['OBS_VALUE']] : 0.0;
            if ('' === $cur || '' === $period || $value <= 0.0) {
                continue;
            }
            $unit = 10 ** (int) $unitStr; // 0 -> 1, 1 -> 10, 2 -> 100, ...
            if (isset($best[$cur]) && $best[$cur]['period'] >= $period) {
                continue;
            }
            $best[$cur] = ['period' => $period, 'value' => $value, 'unit' => (float) $unit];
        }

        $quotes = [];
        foreach ($best as $code => $info) {
            $when = Carbon::createFromFormat('Y-m-d', $info['period'], config('app.timezone'));
            if (!$when instanceof Carbon) {
                continue;
            }
            // perBaseUnit: how many NOK per 1 unit of $code.
            $perBaseUnit = $this->perUnit($info['value'], $info['unit']);

            $quotes[] = new RateQuote(
                fromCode: (string) $code,
                toCode: $base,
                date: $when->startOfDay(),
                rate: $perBaseUnit,
            );
        }

        return $quotes;
    }

    /**
     * Norges Bank CSV uses ; as a separator and double-quotes around fields
     * that contain a separator. The values we need (codes, numbers, dates)
     * are never quoted, so a fast manual split is enough.
     *
     * @return string[]
     */
    private function parseCsvRow(string $line): array
    {
        return array_map('trim', explode(';', $line));
    }
}
