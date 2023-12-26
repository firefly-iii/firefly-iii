<?php

/*
 * AccountTransformer.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Transformers\V2;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends AbstractTransformer
{
    private array               $accountMeta;
    private array               $accountTypes;
    private array               $balances;
    private array               $convertedBalances;
    private array               $currencies;
    private TransactionCurrency $default;

    /**
     * @throws FireflyException
     */
    public function collectMetaData(Collection $objects): void
    {
        $this->currencies        = [];
        $this->accountMeta       = [];
        $this->accountTypes      = [];
        $this->balances          = app('steam')->balancesByAccounts($objects, $this->getDate());
        $this->convertedBalances = app('steam')->balancesByAccountsConverted($objects, $this->getDate());

        /** @var CurrencyRepositoryInterface $repository */
        $repository    = app(CurrencyRepositoryInterface::class);
        $this->default = app('amount')->getDefaultCurrency();

        // get currencies:
        $accountIds  = $objects->pluck('id')->toArray();
        $meta        = AccountMeta::whereIn('account_id', $accountIds)
            ->where('name', 'currency_id')
            ->get(['account_meta.id', 'account_meta.account_id', 'account_meta.name', 'account_meta.data'])
        ;
        $currencyIds = $meta->pluck('data')->toArray();

        $currencies = $repository->getByIds($currencyIds);
        foreach ($currencies as $currency) {
            $id                    = $currency->id;
            $this->currencies[$id] = $currency;
        }
        foreach ($meta as $entry) {
            $id                                   = $entry->account_id;
            $this->accountMeta[$id][$entry->name] = $entry->data;
        }
        // get account types:
        // select accounts.id, account_types.type from account_types left join accounts on accounts.account_type_id = account_types.id;
        $accountTypes = AccountType::leftJoin('accounts', 'accounts.account_type_id', '=', 'account_types.id')
            ->whereIn('accounts.id', $accountIds)
            ->get(['accounts.id', 'account_types.type'])
        ;

        /** @var AccountType $row */
        foreach ($accountTypes as $row) {
            $this->accountTypes[$row->id] = (string)config(sprintf('firefly.shortNamesByFullName.%s', $row->type));
        }
    }

    /**
     * Transform the account.
     */
    public function transform(Account $account): array
    {
        $id = $account->id;

        // various meta
        $accountRole = $this->accountMeta[$id]['account_role'] ?? null;
        $accountType = $this->accountTypes[$id];
        $order       = $account->order;

        // no currency? use default
        $currency = $this->default;
        if (0 !== (int)$this->accountMeta[$id]['currency_id']) {
            $currency = $this->currencies[(int)$this->accountMeta[$id]['currency_id']];
        }
        // amounts and calculation.
        $balance       = $this->balances[$id] ?? null;
        $nativeBalance = $this->convertedBalances[$id]['native_balance'] ?? null;

        // no order for some accounts:
        if (!in_array(strtolower($accountType), ['liability', 'liabilities', 'asset'], true)) {
            $order = null;
        }

        return [
            'id'                      => (string)$account->id,
            'created_at'              => $account->created_at->toAtomString(),
            'updated_at'              => $account->updated_at->toAtomString(),
            'active'                  => $account->active,
            'order'                   => $order,
            'name'                    => $account->name,
            'iban'                    => '' === $account->iban ? null : $account->iban,
            'type'                    => strtolower($accountType),
            'account_role'            => $accountRole,
            'currency_id'             => (string)$currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,

            'native_currency_id'             => (string)$this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,

            // balance:
            'current_balance'                => $balance,
            'native_current_balance'         => $nativeBalance,
            'current_balance_date'           => $this->getDate(),

            // more meta

            //            'notes'                   => $this->repository->getNoteText($account),
            //            'monthly_payment_date'    => $monthlyPaymentDate,
            //            'credit_card_type'        => $creditCardType,
            //            'account_number'          => $this->repository->getMetaValue($account, 'account_number'),
            //            'bic'                     => $this->repository->getMetaValue($account, 'BIC'),
            //            'virtual_balance'         => number_format((float) $account->virtual_balance, $decimalPlaces, '.', ''),
            //            'opening_balance'         => $openingBalance,
            //            'opening_balance_date'    => $openingBalanceDate,
            //            'liability_type'          => $liabilityType,
            //            'liability_direction'     => $liabilityDirection,
            //            'interest'                => $interest,
            //            'interest_period'         => $interestPeriod,
            //            'current_debt'            => $this->repository->getMetaValue($account, 'current_debt'),
            //            'include_net_worth'       => $includeNetWorth,
            //            'longitude'               => $longitude,
            //            'latitude'                => $latitude,
            //            'zoom_level'              => $zoomLevel,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/'.$account->id,
                ],
            ],
        ];
    }

    private function getDate(): Carbon
    {
        $date = today(config('app.timezone'));
        if (null !== $this->parameters->get('date')) {
            $date = $this->parameters->get('date');
        }

        return $date;
    }
}
