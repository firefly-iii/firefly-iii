<?php

/**
 * SafeExchangeRateConverter.php
 * Copyright (c) 2024 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Currency;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class SafeExchangeRateConverter
 * 
 * Provides safe currency conversion with proper error handling,
 * validation, and consistency checks.
 */
class SafeExchangeRateConverter
{
    private bool $ignoreSettings = false;
    private array $rateCache = [];
    private int $queryCount = 0;
    private UserGroup $userGroup;
    private bool $strictMode = true;

    public function __construct()
    {
        if (auth()->check()) {
            $this->userGroup = auth()->user()->userGroup;
        }
    }

    /**
     * Convert amount between currencies with validation.
     *
     * @throws FireflyException When conversion cannot be performed safely
     */
    public function convert(
        TransactionCurrency $from,
        TransactionCurrency $to,
        Carbon $date,
        string $amount
    ): string {
        // Validate input amount
        $this->validateAmount($amount);
        
        if (false === $this->enabled()) {
            Log::debug('SafeExchangeRateConverter: disabled, return amount as is.');
            return $amount;
        }

        // Same currency, no conversion needed
        if ($from->id === $to->id) {
            return $amount;
        }

        // Get rate with validation
        $rate = $this->getCurrencyRate($from, $to, $date);
        
        // Validate the rate
        $this->validateRate($rate, $from, $to, $date);
        
        // Perform conversion with precision handling
        $converted = $this->performConversion($amount, $rate, $to->decimal_places);
        
        // Validate result
        $this->validateConversionResult($converted, $amount, $from, $to);
        
        return $converted;
    }

    /**
     * Get currency rate with proper error handling.
     *
     * @throws FireflyException When rate cannot be determined
     */
    public function getCurrencyRate(
        TransactionCurrency $from,
        TransactionCurrency $to,
        Carbon $date
    ): string {
        if (false === $this->enabled()) {
            return '1';
        }

        if ($from->id === $to->id) {
            return '1';
        }

        // Try to get rate with locking for consistency
        $rate = $this->getRateWithLock($from, $to, $date);
        
        if ('0' === $rate || null === $rate) {
            if ($this->strictMode) {
                throw new FireflyException(
                    sprintf(
                        'No exchange rate available from %s to %s on %s',
                        $from->code,
                        $to->code,
                        $date->format('Y-m-d')
                    )
                );
            }
            
            Log::warning(sprintf(
                'No exchange rate found for %s to %s on %s, using 1',
                $from->code,
                $to->code,
                $date->format('Y-m-d')
            ));
            
            return '1';
        }

        return $rate;
    }

    /**
     * Get rate with database locking for consistency.
     */
    private function getRateWithLock(
        TransactionCurrency $from,
        TransactionCurrency $to,
        Carbon $date
    ): ?string {
        $cacheKey = $this->getCacheKey($from, $to, $date);
        
        // Check memory cache first
        if (isset($this->rateCache[$cacheKey])) {
            return $this->rateCache[$cacheKey];
        }
        
        // Check persistent cache
        $cached = Cache::get($cacheKey);
        if (null !== $cached) {
            $this->rateCache[$cacheKey] = $cached;
            return $cached;
        }
        
        // Get from database with lock
        $rate = DB::transaction(function () use ($from, $to, $date) {
            return $this->getFromDatabase($from->id, $to->id, $date->format('Y-m-d'));
        });
        
        if (null === $rate) {
            // Try reverse rate
            $reverseRate = DB::transaction(function () use ($from, $to, $date) {
                return $this->getFromDatabase($to->id, $from->id, $date->format('Y-m-d'));
            });
            
            if (null !== $reverseRate && bccomp('0', $reverseRate) !== 0) {
                $rate = bcdiv('1', $reverseRate, 12);
            }
        }
        
        if (null === $rate) {
            // Try triangulation through EUR
            $rate = $this->triangulateRate($from, $to, $date);
        }
        
        if (null !== $rate && bccomp('0', $rate) !== 0) {
            // Cache the result
            Cache::put($cacheKey, $rate, now()->addHours(24));
            $this->rateCache[$cacheKey] = $rate;
        }
        
        return $rate;
    }

    /**
     * Get rate from database with proper locking.
     */
    private function getFromDatabase(int $fromId, int $toId, string $date): ?string
    {
        if ($fromId === $toId) {
            return '1';
        }

        /** @var null|CurrencyExchangeRate $result */
        $result = $this->userGroup->currencyExchangeRates()
            ->where('from_currency_id', $fromId)
            ->where('to_currency_id', $toId)
            ->where('date', '<=', $date)
            ->orderBy('date', 'DESC')
            ->lockForShare() // Prevent concurrent updates
            ->first();
        
        $this->queryCount++;
        
        if (null === $result) {
            return null;
        }
        
        $rate = (string) $result->rate;
        
        if ('' === $rate || bccomp('0', $rate) === 0) {
            return null;
        }
        
        return $rate;
    }

    /**
     * Triangulate rate through EUR.
     */
    private function triangulateRate(
        TransactionCurrency $from,
        TransactionCurrency $to,
        Carbon $date
    ): ?string {
        try {
            $euro = Amount::getTransactionCurrencyByCode('EUR');
        } catch (FireflyException $e) {
            Log::warning('Could not get EUR for triangulation: ' . $e->getMessage());
            return null;
        }
        
        if ($euro->id === $from->id || $euro->id === $to->id) {
            return null;
        }
        
        $fromToEur = $this->getFromDatabase($from->id, $euro->id, $date->format('Y-m-d'));
        $eurToTo = $this->getFromDatabase($euro->id, $to->id, $date->format('Y-m-d'));
        
        if (null === $fromToEur || null === $eurToTo) {
            return null;
        }
        
        if (bccomp('0', $fromToEur) === 0 || bccomp('0', $eurToTo) === 0) {
            return null;
        }
        
        return bcmul($fromToEur, $eurToTo, 12);
    }

    /**
     * Validate amount before conversion.
     *
     * @throws FireflyException
     */
    private function validateAmount(string $amount): void
    {
        if ('' === $amount) {
            throw new FireflyException('Cannot convert empty amount');
        }
        
        // Check for scientific notation and convert
        if (stripos($amount, 'e') !== false) {
            $amount = sprintf('%.12f', (float) $amount);
        }
        
        // Validate it's a valid number
        if (!is_numeric($amount)) {
            throw new FireflyException(sprintf('Invalid amount for conversion: %s', $amount));
        }
        
        // Check for reasonable bounds (prevent overflow)
        if (bccomp($amount, '999999999999999') > 0) {
            throw new FireflyException('Amount too large for safe conversion');
        }
    }

    /**
     * Validate exchange rate.
     *
     * @throws FireflyException
     */
    private function validateRate(string $rate, TransactionCurrency $from, TransactionCurrency $to, Carbon $date): void
    {
        if (bccomp('0', $rate) === 0) {
            throw new FireflyException(
                sprintf('Exchange rate is zero for %s to %s on %s', $from->code, $to->code, $date->format('Y-m-d'))
            );
        }
        
        // Check for unreasonable rates (likely data errors)
        if (bccomp($rate, '0.0000001') < 0 || bccomp($rate, '10000000') > 0) {
            Log::warning(sprintf(
                'Suspicious exchange rate %s for %s to %s on %s',
                $rate,
                $from->code,
                $to->code,
                $date->format('Y-m-d')
            ));
        }
    }

    /**
     * Perform the actual conversion with proper precision.
     */
    private function performConversion(string $amount, string $rate, int $decimalPlaces): string
    {
        // Use higher precision for intermediate calculation
        $result = bcmul($amount, $rate, $decimalPlaces + 4);
        
        // Round to target precision
        return Steam::bcround($result, $decimalPlaces);
    }

    /**
     * Validate conversion result.
     *
     * @throws FireflyException
     */
    private function validateConversionResult(
        string $result,
        string $original,
        TransactionCurrency $from,
        TransactionCurrency $to
    ): void {
        if (bccomp($result, '0') === 0 && bccomp($original, '0') !== 0) {
            throw new FireflyException(
                sprintf('Conversion resulted in zero amount from %s %s', $original, $from->code)
            );
        }
        
        // Check for overflow
        if (strlen($result) > 30) {
            throw new FireflyException('Conversion result too large');
        }
    }

    /**
     * Get cache key for rate.
     */
    private function getCacheKey(TransactionCurrency $from, TransactionCurrency $to, Carbon $date): string
    {
        return sprintf('safe_cer-%d-%d-%s', $from->id, $to->id, $date->format('Y-m-d'));
    }

    /**
     * Check if conversion is enabled.
     */
    public function enabled(): bool
    {
        return false !== config('cer.enabled') || true === $this->ignoreSettings;
    }

    /**
     * Set strict mode (throw exceptions vs return defaults).
     */
    public function setStrictMode(bool $strict): void
    {
        $this->strictMode = $strict;
    }

    /**
     * Set user group.
     */
    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    /**
     * Set ignore settings flag.
     */
    public function setIgnoreSettings(bool $ignoreSettings): void
    {
        $this->ignoreSettings = $ignoreSettings;
    }

    /**
     * Get query count for debugging.
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    /**
     * Clear rate cache.
     */
    public function clearCache(): void
    {
        $this->rateCache = [];
    }
}