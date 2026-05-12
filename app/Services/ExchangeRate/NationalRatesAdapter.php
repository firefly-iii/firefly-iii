<?php

/*
 * NationalRatesAdapter.php
 *
 * Persists RateQuote objects produced by national-bank providers into
 * the existing `currency_exchange_rates` table.
 *
 * Strategy (consistent with FireflyIII\Jobs\DownloadExchangeRates):
 *   - rates are stored per user via CurrencyRepositoryInterface::setExchangeRate;
 *   - currencies not yet in the database, or disabled ones, are skipped;
 *   - duplicates for the same (user, from, to, date) tuple are skipped;
 *   - both directions are persisted (BYN→USD and USD→BYN) so existing
 *     ExchangeRateConverter logic keeps working unchanged.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\ExchangeRate\Providers\NationalRateProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class NationalRatesAdapter
{
    /** @var array<string, ?TransactionCurrency> */
    private array $currencyCache = [];

    private Collection $users;

    public function __construct(
        private readonly CurrencyRepositoryInterface $currencyRepository,
        UserRepositoryInterface $userRepository,
    ) {
        $this->users = $userRepository->all();
    }

    /**
     * Fetch rates from $provider for $date and persist them.
     *
     * Returns the number of (user × direction) rows actually inserted.
     */
    public function pullAndStore(NationalRateProviderInterface $provider, Carbon $date): int
    {
        $providerName = $provider::name();
        Log::info(sprintf('[NationalRatesAdapter] Pulling rates from %s for %s', $providerName, $date->format('Y-m-d')));

        $quotes = $provider->fetchRates($date);
        if ([] === $quotes) {
            Log::info(sprintf('[NationalRatesAdapter] %s returned no quotes.', $providerName));

            return 0;
        }

        $baseCurrency = $this->resolveCurrency($provider::base());
        if (!$baseCurrency instanceof TransactionCurrency) {
            Log::warning(sprintf(
                '[NationalRatesAdapter] Base currency %s for provider %s is missing or disabled — nothing to save.',
                $provider::base(),
                $providerName,
            ));

            return 0;
        }

        $written = 0;
        foreach ($quotes as $quote) {
            if ($quote->fromCode !== $baseCurrency->code) {
                // Provider contract violation — base must equal fromCode.
                Log::warning(sprintf(
                    '[NationalRatesAdapter] %s produced quote with fromCode=%s, expected %s. Skipped.',
                    $providerName,
                    $quote->fromCode,
                    $baseCurrency->code,
                ));

                continue;
            }
            $toCurrency = $this->resolveCurrency($quote->toCode);
            if (!$toCurrency instanceof TransactionCurrency) {
                continue;
            }
            if ($toCurrency->id === $baseCurrency->id) {
                continue;
            }
            if ($quote->rate <= 0.0) {
                continue;
            }

            $written += $this->storeBothDirections($baseCurrency, $toCurrency, $quote->date, $quote->rate);
        }

        Log::info(sprintf(
            '[NationalRatesAdapter] %s: %d quotes processed, %d new rows written.',
            $providerName,
            count($quotes),
            $written,
        ));

        return $written;
    }

    /**
     * Writes the rate in both directions (from→to and to→from) for every
     * known user, skipping any (user, from, to, date) tuple that already exists.
     */
    private function storeBothDirections(
        TransactionCurrency $base,
        TransactionCurrency $foreign,
        Carbon $date,
        float $rate,
    ): int {
        $written = 0;
        $inverse = 1.0 / $rate;

        foreach ($this->users as $user) {
            $this->currencyRepository->setUser($user);

            // base -> foreign
            if (!$this->currencyRepository->getExchangeRate($base, $foreign, $date) instanceof CurrencyExchangeRate) {
                $this->currencyRepository->setExchangeRate($base, $foreign, $date, $rate);
                ++$written;
            }
            // foreign -> base
            if (!$this->currencyRepository->getExchangeRate($foreign, $base, $date) instanceof CurrencyExchangeRate) {
                $this->currencyRepository->setExchangeRate($foreign, $base, $date, $inverse);
                ++$written;
            }
        }

        return $written;
    }

    private function resolveCurrency(string $code): ?TransactionCurrency
    {
        $key = strtoupper($code);
        if (array_key_exists($key, $this->currencyCache)) {
            return $this->currencyCache[$key];
        }

        $currency = $this->currencyRepository->findByCode($key);
        if (!$currency instanceof TransactionCurrency) {
            Log::debug(sprintf('[NationalRatesAdapter] Currency %s not in DB — skipped.', $key));
            $this->currencyCache[$key] = null;

            return null;
        }
        if (false === $currency->enabled) {
            Log::debug(sprintf('[NationalRatesAdapter] Currency %s is disabled — skipped.', $key));
            $this->currencyCache[$key] = null;

            return null;
        }
        $this->currencyCache[$key] = $currency;

        return $currency;
    }
}
