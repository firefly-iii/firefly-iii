<?php

/*
 * NbkProvider.php
 *
 * National Bank of the Republic of Kazakhstan (https://nationalbank.kz).
 * RSS-shaped XML feed, no key.
 *
 * Endpoint:
 *   https://nationalbank.kz/rss/rates_all.xml  — current day, all rates
 *
 * Each <item> contains:
 *   <title>USD</title>
 *   <pubDate>14.05.2026</pubDate>
 *   <description>522.16</description>
 *   <quant>1</quant>
 *
 * Meaning: quant units of title = description KZT. We normalise and emit
 * foreign -> KZT.
 *
 * The "all" endpoint only exposes today's rates; historical access requires
 * scraping HTML, so for past dates we still pull today's snapshot and tag
 * the quotes with the requested date if it equals pubDate, otherwise we
 * keep the pubDate the bank actually published for traceability.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

final class NbkProvider extends AbstractNationalRateProvider
{
    private const string ENDPOINT = 'https://nationalbank.kz/rss/rates_all.xml';

    public static function country(): string
    {
        return 'KZ';
    }

    public static function base(): string
    {
        return 'KZT';
    }

    public static function name(): string
    {
        return 'NBK';
    }

    public function fetchRates(Carbon $date): array
    {
        $body = $this->httpGet(self::ENDPOINT);
        if (null === $body) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if (!$xml instanceof SimpleXMLElement || !isset($xml->channel)) {
            Log::warning('[NBK] Failed to parse XML response.');

            return [];
        }

        $base   = self::base();
        $quotes = [];
        foreach ($xml->channel->item as $item) {
            $code     = strtoupper(trim((string) $item->title));
            $rate     = (float) trim((string) $item->description);
            $quantRaw = trim((string) $item->quant);
            $quant    = $quantRaw === '' ? 1.0 : (float) $quantRaw;
            $pubDate  = trim((string) $item->pubDate);
            if ('' === $code || $rate <= 0.0 || $quant <= 0.0) {
                continue;
            }
            $when = $date->copy()->startOfDay();
            if ('' !== $pubDate) {
                $parsed = Carbon::createFromFormat('d.m.Y', $pubDate, config('app.timezone'));
                if ($parsed instanceof Carbon) {
                    $when = $parsed->startOfDay();
                }
            }

            // perBaseUnit: how many KZT per 1 unit of $code.
            $perBaseUnit = $this->perUnit($rate, $quant);

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
