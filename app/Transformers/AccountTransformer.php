<?php
/**
 * AccountTransformer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
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
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
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
        $accountType = $this->repository->getAccountType($account);

        // get account role (will only work if the type is asset. TODO test me.
        $accountRole = $this->repository->getMetaValue($account, 'accountRole');
        if ($accountType !== AccountType::ASSET || '' === (string)$accountRole) {
            $accountRole = null;
        }

        // get currency. If not 0, get from repository. TODO test me.
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

        $date = new Carbon;
        if (null !== $this->parameters->get('date')) {
            $date = $this->parameters->get('date');
        }

        $monthlyPaymentDate = null;
        $creditCardType     = null;
        if ('ccAsset' === $accountRole && $accountType === AccountType::ASSET) {
            $creditCardType     = $this->repository->getMetaValue($account, 'ccType');
            $monthlyPaymentDate = $this->repository->getMetaValue($account, 'ccMonthlyPaymentDate');
        }

        $openingBalance     = null;
        $openingBalanceDate = null;
        if (\in_array($accountType, [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE], true)) {
            $amount             = $this->repository->getOpeningBalanceAmount($account);
            $openingBalance     = null === $amount ? null : round($amount, $decimalPlaces);
            $openingBalanceDate = $this->repository->getOpeningBalanceDate($account);
        }
        $interest        = $this->repository->getMetaValue($account, 'interest');
        $interestPeriod  = $this->repository->getMetaValue($account, 'interest_period');
        $includeNetworth = '0' !== $this->repository->getMetaValue($account, 'include_net_worth');

        $data = [
            'id'                   => (int)$account->id,
            'created_at'           => $account->created_at->toAtomString(),
            'updated_at'           => $account->updated_at->toAtomString(),
            'active'               => $account->active,
            'name'                 => $account->name,
            'type'                 => $accountType,
            'account_role'         => $accountRole,
            'currency_id'          => $currencyId,
            'currency_code'        => $currencyCode,
            'currency_symbol'      => $currencySymbol,
            'currency_dp'          => $decimalPlaces,
            'current_balance'      => round(app('steam')->balance($account, $date), $decimalPlaces),
            'current_balance_date' => $date->format('Y-m-d'),
            'notes'                => $this->repository->getNoteText($account),
            'monthly_payment_date' => $monthlyPaymentDate,
            'credit_card_type'     => $creditCardType,
            'account_number'       => $this->repository->getMetaValue($account, 'accountNumber'),
            'iban'                 => '' === $account->iban ? null : $account->iban,
            'bic'                  => $this->repository->getMetaValue($account, 'BIC'),
            'virtual_balance'      => round($account->virtual_balance, $decimalPlaces),
            'opening_balance'      => $openingBalance,
            'opening_balance_date' => $openingBalanceDate,
            'liability_type'       => $accountType,
            'liability_amount'     => $openingBalance,
            'liability_start_date' => $openingBalanceDate,
            'interest'             => $interest,
            'interest_period'      => $interestPeriod,
            'include_net_worth'    => $includeNetworth,
            'links'                => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];

        return $data;
    }
}
