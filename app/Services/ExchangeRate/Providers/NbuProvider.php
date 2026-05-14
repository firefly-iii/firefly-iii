<?php

/*
 * NbuProvider.php
 *
 * National Bank of Ukraine (https://bank.gov.ua).
 * JSON statistic endpoint, no key.
 *
 * Endpoint:
 *   https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json
 *   (latest known business day; honours ?date=YYYYMMDD for historicals)
 *
 * Response shape:
 *   [{"r030":840,"txt":"...","rate":41.2345,"cc":"USD","exchangedate":"13.05.2026"}, ...]
 *
 * Meaning: 1 unit of $cc = rate UAH (NBU always quotes "per 1 foreign").
 * We emit foreign -> UAH.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

final class NbuProvider extends AbstractNationalRateProvider
{
    public static function country(): string
    {
        return 'UA';
    }

    public static function base(): string
    {
        return 'UAH';
    }

    public static function name(): string
    {
        return 'NBU';
    }

    public function fetchRates(Carbon $date): array
    {
        $url  = sprintf(
            'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json&date=%s',
            $date->format('Ymd'),
        );
        $body = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        try {
            $items = json_decode($body, true);
        } catch (JsonException $e) {
            Log::warning(sprintf('[NBU] Invalid JSON: %s', $e->getMessage()));

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
            $code = isset($item['cc']) ? strtoupper((string) $item['cc']) : '';
            $rate = isset($item['rate']) ? (float) $item['rate'] : 0.0;
            if ('' === $code || $rate <= 0.0) {
                continue;
            }

            $exchangeDate = isset($item['exchangedate']) ? (string) $item['exchangedate'] : '';
            $when         = '' !== $exchangeDate
                ? Carbon::createFromFormat('d.m.Y', $exchangeDate, config('app.timezone'))
                : $date->copy();
            if (!$when instanceof Carbon) {
                $when = $date->copy();
            }

            $quotes[] = new RateQuote(
                fromCode: $code,
                toCode: $base,
                date: $when->startOfDay(),
                rate: $rate,
            );
        }

        return $quotes;
    }
}
