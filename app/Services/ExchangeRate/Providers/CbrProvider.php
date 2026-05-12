<?php

/*
 * CbrProvider.php
 *
 * Central Bank of the Russian Federation (https://www.cbr.ru).
 * Publishes RUB-based rates as XML, no API key required.
 *
 * Endpoint (daily rates):
 *   https://www.cbr.ru/scripts/XML_daily.asp?date_req=DD/MM/YYYY
 *
 * Response shape:
 *   <ValCurs Date="12.05.2026" name="Foreign Currency Market">
 *     <Valute ID="R01235">
 *       <NumCode>840</NumCode>
 *       <CharCode>USD</CharCode>
 *       <Nominal>1</Nominal>
 *       <Name>Доллар США</Name>
 *       <Value>92,4513</Value>
 *       <VunitRate>92,4513</VunitRate>
 *     </Valute>
 *     ...
 *   </ValCurs>
 *
 * Meaning: Nominal units of CharCode = Value RUB (decimal comma).
 * We normalise to "1 RUB = X foreign" by inverting (Nominal / Value).
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

final class CbrProvider extends AbstractNationalRateProvider
{
    private const string ENDPOINT = 'https://www.cbr.ru/scripts/XML_daily.asp';

    public static function country(): string
    {
        return 'RU';
    }

    public static function base(): string
    {
        return 'RUB';
    }

    public static function name(): string
    {
        return 'CBR';
    }

    public function fetchRates(Carbon $date): array
    {
        $url  = sprintf('%s?date_req=%s', self::ENDPOINT, $date->format('d/m/Y'));
        $body = $this->httpGet($url);
        if (null === $body) {
            return [];
        }

        // CBR returns windows-1251; normalise to UTF-8 for SimpleXML.
        if (!str_contains($body, 'encoding="UTF-8"')) {
            $converted = @iconv('windows-1251', 'UTF-8//IGNORE', $body);
            if (false !== $converted) {
                $body = preg_replace(
                    '/encoding="[^"]+"/',
                    'encoding="UTF-8"',
                    $converted,
                    1,
                );
            }
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $body);
        if (!$xml instanceof SimpleXMLElement) {
            Log::warning('[CBR] Failed to parse XML response.');

            return [];
        }

        $base   = self::base();
        $quotes = [];
        foreach ($xml->Valute as $valute) {
            $code    = (string) $valute->CharCode;
            $nominal = (float) str_replace(',', '.', (string) $valute->Nominal);
            $value   = (float) str_replace(',', '.', (string) $valute->Value);
            if ('' === $code || $nominal <= 0.0 || $value <= 0.0) {
                continue;
            }

            $perBaseUnit = $this->perUnit($value, $nominal); // RUB per 1 foreign
            $rub2foreign = 1.0 / $perBaseUnit;

            $quotes[] = new RateQuote(
                fromCode: $base,
                toCode: $code,
                date: $date->copy()->startOfDay(),
                rate: $rub2foreign,
            );
        }

        return $quotes;
    }
}
