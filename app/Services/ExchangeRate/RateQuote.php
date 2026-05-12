<?php

/*
 * RateQuote.php
 *
 * This file is part of the Firefly III fork that adds national bank
 * exchange-rate providers (lapytko/firefly-iii).
 *
 * Licensed under the GNU Affero General Public License v3 or later.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use Carbon\Carbon;

/**
 * Immutable value object representing a single exchange rate quote
 * produced by a national bank provider.
 *
 *   1 unit of $fromCode (base) = $rate units of $toCode (quote).
 *
 * Providers are expected to normalise rates to "per 1 unit of base"
 * regardless of the original publication scale (e.g. NBRB publishes
 * "10 RUB = X BYN" — the provider must divide before constructing
 * the DTO).
 */
final class RateQuote
{
    public function __construct(
        public readonly string $fromCode,
        public readonly string $toCode,
        public readonly Carbon $date,
        public readonly float $rate,
    ) {
    }
}
