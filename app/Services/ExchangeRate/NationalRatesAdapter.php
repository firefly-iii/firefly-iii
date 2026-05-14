<?php

/*
 * NationalRatesAdapter.php
 *
 * Persists RateQuote objects produced by national-bank providers into
 * the existing `currency_exchange_rates` table.
 *
 * Scope: rates are written ONLY for users whose effective country
 * (resolved via UserCountryResolver) matches the provider's country.
 * That keeps the rates of unrelated administrations untouched when
 * multiple national sources run on the same instance.
 *
 * Behaviour:
 *   - currencies not yet in the database, or disabled ones, are skipped;
 *   - duplicates for the same (user, from, to, date) tuple are skipped;
 *   - both directions are persisted (e.g. BYN→USD and USD→BYN) so
 *     ExchangeRateConverter keeps working unchanged.
 */

declare(strict_types=1);

namespace FireflyIII\Services\ExchangeRate;

use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\ExchangeRate\Providers\NationalRateProviderInterface;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

final class NationalRatesAdapter
{
    /** @var array<string, ?TransactionCurrency> */
    private array $currencyCache = [];

    public function __construct(
        private readonly CurrencyRepositoryInterface $currencyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserCountryResolver $resolver,
    ) {
    }

    /**
     * Fetch rates from $provider for $date and persist them for every
     * user whose effective country equals the provider's country.
     *
     * Returns the number of (user × direction) rows actually inserted.
     */
    public function pullAndStore(NationalRateProviderInterface $provider, Carbon $date): int
    {
        $providerCountry = strtoupper($provider::country());
        $providerName    = $provider::name();
        Log::info(sprintf(
            '[NationalRatesAdapter] Pulling rates from %s (%s) for %s',
            $providerName,
            $providerCountry,
            $date->format('Y-m-d'),
        ));

        $targetUsers = $this->usersForCountry($providerCountry);
        if ([] === $targetUsers) {
            Log::info(sprintf(
                '[NationalRatesAdapter] No users matched country %s — skipping %s.',
                $providerCountry,
                $providerName,
            ));

            return 0;
        }

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
            if ($toCurrency->id === $baseCurrency->id || $quote->rate <= 0.0) {
                continue;
            }

            $written += $this->storeBothDirections(
                $targetUsers,
                $baseCurrency,
                $toCurrency,
                $quote->date,
                $quote->rate,
            );
        }

        Log::info(sprintf(
            '[NationalRatesAdapter] %s: %d quotes processed, %d new rows written across %d users.',
            $providerName,
            count($quotes),
            $written,
            count($targetUsers),
        ));

        return $written;
    }

    /**
     * @param User[] $users
     */
    private function storeBothDirections(
        array $users,
        TransactionCurrency $base,
        TransactionCurrency $foreign,
        Carbon $date,
        float $rate,
    ): int {
        $written = 0;
        $inverse = 1.0 / $rate;

        foreach ($users as $user) {
            $this->currencyRepository->setUser($user);

            if (!$this->currencyRepository->getExchangeRate($base, $foreign, $date) instanceof CurrencyExchangeRate) {
                $this->currencyRepository->setExchangeRate($base, $foreign, $date, $rate);
                ++$written;
            }
            if (!$this->currencyRepository->getExchangeRate($foreign, $base, $date) instanceof CurrencyExchangeRate) {
                $this->currencyRepository->setExchangeRate($foreign, $base, $date, $inverse);
                ++$written;
            }
        }

        return $written;
    }

    /**
     * Users whose effective country (UserGroup or preference fallback)
     * equals the provider's country.
     *
     * @return User[]
     */
    private function usersForCountry(string $countryCode): array
    {
        $target = strtoupper($countryCode);
        $hits   = [];
        foreach ($this->userRepository->all() as $user) {
            if ($this->resolver->forUser($user) === $target) {
                $hits[] = $user;
            }
        }

        return $hits;
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
