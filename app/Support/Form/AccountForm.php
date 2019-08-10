<?php
/**
 * AccountForm.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Form;


use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;
use Throwable;

/**
 * Class AccountForm
 *
 * All form methods that are account related.
 *
 * TODO describe all methods.
 * TODO optimize repositories and methods.
 */
class AccountForm
{
    use FormSupport;

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function activeAssetAccountList(string $name, $value = null, array $options = null): string
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        $accountList     = $repository->getActiveAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance    = app('steam')->balance($account, new Carbon);
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
            $currency   = $currencyRepos->findNull($currencyId);
            $role       = $repository->getMetaValue($account, 'account_role');
            if ('' === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            if (null === $currency) {
                $currency = $defaultCurrency;
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $this->select($name, $grouped, $value, $options);
    }



    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function activeLongAccountList(string $name, $value = null, array $options = null): string
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository      = app(AccountRepositoryInterface::class);
        $accountList     = $repository->getActiveAccountsByType(
            [AccountType::ASSET, AccountType::DEFAULT, AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN,]
        );
        $liabilityTypes  = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:

        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance = app('steam')->balance($account, new Carbon);

            $currency = $repository->getAccountCurrency($account) ?? $defaultCurrency;

            $role = $repository->getMetaValue($account, 'account_role');

            if ('' === $role && !in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type; // @codeCoverageIgnore
            }
            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }


        return $this->select($name, $grouped, $value, $options);
    }

    /**
     * Grouped dropdown list of all accounts that are valid as the destination of a withdrawal.
     *
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function activeWithdrawalDestinations(string $name, $value = null, array $options = null): string
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $accountList     = $repository->getActiveAccountsByType(
            [
                AccountType::MORTGAGE,
                AccountType::DEBT,
                AccountType::CREDITCARD,
                AccountType::LOAN,
                AccountType::EXPENSE,
            ]
        );
        $liabilityTypes  = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];

        // add cash account first:
        $cash                     = $repository->getCashAccount();
        $key                      = (string)trans('firefly.cash_account_type');
        $grouped[$key][$cash->id] = sprintf('(%s)', (string)trans('firefly.cash'));

        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance  = app('steam')->balance($account, new Carbon);
            $currency = $repository->getAccountCurrency($account) ?? $defaultCurrency;
            $role     = (string)$repository->getMetaValue($account, 'account_role');
            if ('' === $role && !in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }
            if ('no_account_type' === $role && AccountType::EXPENSE === $account->accountType->type) {
                $role = 'expense_account'; // @codeCoverageIgnore

            }

            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type; // @codeCoverageIgnore
            }

            if (null === $currency) {
                $currency = $defaultCurrency;
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $this->select($name, $grouped, $value, $options);
    }

    /**
     * Grouped dropdown list of all accounts that are valid as the destination of a withdrawal.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function activeDepositDestinations(string $name, $value = null, array $options = null): string
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $accountList     = $repository->getActiveAccountsByType(
            [
                AccountType::MORTGAGE,
                AccountType::DEBT,
                AccountType::CREDITCARD,
                AccountType::LOAN,
                AccountType::REVENUE,
            ]
        );
        $liabilityTypes  = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];

        // add cash account first:
        $cash                     = $repository->getCashAccount();
        $key                      = (string)trans('firefly.cash_account_type');
        $grouped[$key][$cash->id] = sprintf('(%s)', (string)trans('firefly.cash'));

        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance  = app('steam')->balance($account, new Carbon);
            $currency = $repository->getAccountCurrency($account) ?? $defaultCurrency;
            $role     = (string)$repository->getMetaValue($account, 'account_role');
            if ('' === $role && !in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }
            if ('no_account_type' === $role && AccountType::REVENUE === $account->accountType->type) {
                $role = 'revenue_account'; // @codeCoverageIgnore

            }

            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type; // @codeCoverageIgnore
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $this->select($name, $grouped, $value, $options);
    }


    /**
     * @param string $name
     * @param array $options
     *
     * @return string
     *
     */
    public function assetAccountCheckList(string $name, array $options = null): string
    {
        $options  = $options ?? [];
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = request()->old($name) ?? [];

        // get all asset accounts:
        /** @var AccountRepositoryInterface $repository */
        $repository    = app(AccountRepositoryInterface::class);
        $assetAccounts = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $grouped       = [];
        // group accounts:
        /** @var Account $account */
        foreach ($assetAccounts as $account) {
            $role = $repository->getMetaValue($account, 'account_role');
            if (null === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }
            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name;
        }

        unset($options['class']);
        try {
            $html = view('form.assetAccountCheckList', compact('classes', 'selected', 'name', 'label', 'options', 'grouped'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render assetAccountCheckList(): %s', $e->getMessage()));
            $html = 'Could not render assetAccountCheckList.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function assetAccountList(string $name, $value = null, array $options = null): string
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        $accountList     = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance    = app('steam')->balance($account, new Carbon);
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
            $currency   = $currencyRepos->findNull($currencyId);
            $role       = (string)$repository->getMetaValue($account, 'account_role');
            if ('' === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            if (null === $currency) {
                $currency = $defaultCurrency;
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $this->select($name, $grouped, $value, $options);
    }


    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function longAccountList(string $name, $value = null, array $options = null): string
    {
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        $accountList     = $repository->getAccountsByType(
            [AccountType::ASSET, AccountType::DEFAULT, AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN,]
        );
        $liabilityTypes  = [AccountType::MORTGAGE, AccountType::DEBT, AccountType::CREDITCARD, AccountType::LOAN];
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $balance    = app('steam')->balance($account, new Carbon);
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
            $currency   = $currencyRepos->findNull($currencyId);
            $role       = (string)$repository->getMetaValue($account, 'account_role'); // TODO bad form for currency
            if ('' === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }

            if (in_array($account->accountType->type, $liabilityTypes, true)) {
                $role = 'l_' . $account->accountType->type; // @codeCoverageIgnore
            }

            if (null === $currency) {
                $currency = $defaultCurrency;
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }

        return $this->select($name, $grouped, $value, $options);
    }
}