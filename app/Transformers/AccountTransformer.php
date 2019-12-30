<?php
/**
 * AccountTransformer.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Transformers;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends AbstractTransformer
{
    /** @var AccountRepositoryInterface */
    protected $repository;

    /**
     *
     * AccountTransformer constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }

        $this->repository = app(AccountRepositoryInterface::class);
    }

    /**
     * Transform the account.
     *
     * @param Account $account
     *
     * @return array
     */
    public function transform(Account $account): array
    {
        $this->repository->setUser($account->user);

        // get account type:
        $fullType      = $this->repository->getAccountType($account);
        $accountType   = (string)config(sprintf('firefly.shortNamesByFullName.%s', $fullType));
        $liabilityType = (string)config(sprintf('firefly.shortLiabilityNameByFullName.%s', $fullType));
        $liabilityType = '' === $liabilityType ? null : $liabilityType;

        // get account role (will only work if the type is asset.
        $accountRole = $this->getAccountRole($account, $accountType);
        $date        = $this->getDate();

        [$currencyId, $currencyCode, $currencySymbol, $decimalPlaces] = $this->getCurrency($account);
        [$creditCardType, $monthlyPaymentDate] = $this->getCCInfo($account, $accountRole, $accountType);
        [$openingBalance, $openingBalanceDate] = $this->getOpeningBalance($account, $accountType, $decimalPlaces);
        [$interest, $interestPeriod] = $this->getInterest($account, $accountType);

        $liabilityAmount = null;
        $liabilityStart  = null;
        if (null !== $liabilityType) {
            $liabilityAmount = $openingBalance;
            $liabilityStart  = $openingBalanceDate;
        }
        $includeNetWorth = '0' !== $this->repository->getMetaValue($account, 'include_net_worth');
        $longitude       = null;
        $latitude        = null;
        $zoomLevel       = null;
        $location        = $this->repository->getLocation($account);
        if (null !== $location) {
            $longitude = $location->longitude;
            $latitude  = $location->latitude;
            $zoomLevel = $location->zoom_level;
        }

        return [
            'id'                      => (int)$account->id,
            'created_at'              => $account->created_at->toAtomString(),
            'updated_at'              => $account->updated_at->toAtomString(),
            'active'                  => $account->active,
            'name'                    => $account->name,
            'type'                    => $accountType,
            'account_role'            => $accountRole,
            'currency_id'             => $currencyId,
            'currency_code'           => $currencyCode,
            'currency_symbol'         => $currencySymbol,
            'currency_decimal_places' => $decimalPlaces,
            'current_balance'         => round(app('steam')->balance($account, $date), $decimalPlaces),
            'current_balance_date'    => $date->format('Y-m-d'),
            'notes'                   => $this->repository->getNoteText($account),
            'monthly_payment_date'    => $monthlyPaymentDate,
            'credit_card_type'        => $creditCardType,
            'account_number'          => $this->repository->getMetaValue($account, 'account_number'),
            'iban'                    => '' === $account->iban ? null : $account->iban,
            'bic'                     => $this->repository->getMetaValue($account, 'BIC'),
            'virtual_balance'         => round($account->virtual_balance, $decimalPlaces),
            'opening_balance'         => $openingBalance,
            'opening_balance_date'    => $openingBalanceDate,
            'liability_type'          => $liabilityType,
            'liability_amount'        => $liabilityAmount,
            'liability_start_date'    => $liabilityStart,
            'interest'                => $interest,
            'interest_period'         => $interestPeriod,
            'include_net_worth'       => $includeNetWorth,
            'longitude'               => $longitude,
            'latitude'                => $latitude,
            'zoom_level'              => $zoomLevel,
            'links'                   => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];
    }

    /**
     * @param Account $account
     *
     * @param string  $accountType
     *
     * @return string|null
     */
    private function getAccountRole(Account $account, string $accountType): ?string
    {
        $accountRole = $this->repository->getMetaValue($account, 'account_role');
        if ('asset' !== $accountType || '' === (string)$accountRole) {
            $accountRole = null;
        }

        return $accountRole;
    }

    /**
     * @param Account     $account
     * @param string|null $accountRole
     * @param string      $accountType
     *
     * @return array
     */
    private function getCCInfo(Account $account, ?string $accountRole, string $accountType): array
    {
        $monthlyPaymentDate = null;
        $creditCardType     = null;
        if ('ccAsset' === $accountRole && 'asset' === $accountType) {
            $creditCardType     = $this->repository->getMetaValue($account, 'cc_type');
            $monthlyPaymentDate = $this->repository->getMetaValue($account, 'cc_monthly_payment_date');
        }

        return [$creditCardType, $monthlyPaymentDate];
    }

    /**
     * @param Account $account
     *
     * @return array
     */
    private function getCurrency(Account $account): array
    {
        $currency       = $this->repository->getAccountCurrency($account);
        $currencyId     = null;
        $currencyCode   = null;
        $decimalPlaces  = 2;
        $currencySymbol = null;
        if (null !== $currency) {
            $currencyId     = $currency->id;
            $currencyCode   = $currency->code;
            $decimalPlaces  = $currency->decimal_places;
            $currencySymbol = $currency->symbol;
        }

        return [$currencyId, $currencyCode, $currencySymbol, $decimalPlaces];
    }

    /**
     * @return Carbon
     */
    private function getDate(): Carbon
    {
        $date = new Carbon;
        if (null !== $this->parameters->get('date')) {
            $date = $this->parameters->get('date');
        }

        return $date;
    }

    /**
     * @param Account $account
     * @param string  $accountType
     *
     * @return array
     */
    private function getInterest(Account $account, string $accountType): array
    {
        $interest       = null;
        $interestPeriod = null;
        if ('liabilities' === $accountType) {
            $interest       = $this->repository->getMetaValue($account, 'interest');
            $interestPeriod = $this->repository->getMetaValue($account, 'interest_period');
        }

        return [$interest, $interestPeriod];
    }

    /**
     * @param Account $account
     * @param string  $accountType
     *
     * @param int     $decimalPlaces
     *
     * @return array
     */
    private function getOpeningBalance(Account $account, string $accountType, int $decimalPlaces): array
    {
        $openingBalance     = null;
        $openingBalanceDate = null;
        if (in_array($accountType, ['asset', 'liabilities'], true)) {
            $amount             = $this->repository->getOpeningBalanceAmount($account);
            $openingBalance     = null === $amount ? null : round($amount, $decimalPlaces);
            $openingBalanceDate = $this->repository->getOpeningBalanceDate($account);
        }

        return [$openingBalance, $openingBalanceDate];
    }
}
