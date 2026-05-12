<?php

/*
 * NationalRateProviderInterface.php
 *
 * Contract for national bank exchange-rate providers.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate\Providers;

use Carbon\Carbon;
use FireflyIII\Services\ExchangeRate\RateQuote;

interface NationalRateProviderInterface
{
    /**
     * ISO-3166 alpha-2 country code this provider serves (e.g. "BY", "RU", "EU").
     */
    public static function country(): string;

    /**
     * ISO-4217 currency code which acts as the base of the published rates
     * (e.g. NBRB → BYN, CBR → RUB, ECB → EUR).
     *
     * All returned RateQuote objects use this code in `fromCode`.
     */
    public static function base(): string;

    /**
     * Stable identifier used in logs and admin UI.
     */
    public static function name(): string;

    /**
     * Fetch quotes for the given date.
     *
     * Implementations MUST:
     *   - return rates normalised "per 1 unit of base currency";
     *   - use the requested $date when the API supports historical lookups,
     *     otherwise fall back to the latest published date;
     *   - never throw on missing/unsupported currency codes — just skip them;
     *   - return an empty array on transport/parsing failures (and log).
     *
     * @return RateQuote[]
     */
    public function fetchRates(Carbon $date): array;
}
