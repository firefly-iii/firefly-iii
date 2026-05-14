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
 *   1 unit of $fromCode = $rate units of $toCode.
 *
 * Providers MUST involve their own base currency (provider::base()) in
 * either $fromCode or $toCode and normalise the rate to "per 1 unit of
 * $fromCode" — the publication scale (e.g. NBRB's "10 RUB = X BYN")
 * must be divided out before constructing the DTO.
 *
 * Use the natural publication direction of the source to avoid
 * floating-point drift from double inversion:
 *   - ECB publishes "1 EUR = X foreign"  → from=EUR,  to=foreign
 *   - NBRB / CBR publish "1 foreign = X base" → from=foreign, to=base
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
