<?php
/**
 * ExpandedForm.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support;

use Amount as Amt;
use Carbon\Carbon;
use Eloquent;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Form;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use Log;
use RuntimeException;
use Throwable;

/**
 * Class ExpandedForm.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @codeCoverageIgnore
 */
class ExpandedForm
{
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
     * @param mixed $value
     * @param array $options
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
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function amount(string $name, $value = null, array $options = null): string
    {
        return $this->currencyField($name, 'amount', $value, $options);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function amountNoCurrency(string $name, $value = null, array $options = null): string
    {
        $options         = $options ?? [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['currency'], $options['placeholder']);

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = round($value, 8);
        }
        try {
            $html = view('form.amount-no-currency', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render amountNoCurrency(): %s', $e->getMessage()));
            $html = 'Could not render amountNoCurrency.';
        }

        return $html;
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
     * @throws FireflyException
     */
    public function balance(string $name, $value = null, array $options = null): string
    {
        return $this->currencyField($name, 'balance', $value, $options);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public function balanceAll(string $name, $value = null, array $options = null): string
    {
        return $this->allCurrencyField($name, 'balance', $value, $options);
    }

    /**
     * @param string $name
     * @param int $value
     * @param mixed $checked
     * @param array $options
     *
     * @return string
     *
     */
    public function checkbox(string $name, int $value = null, $checked = null, array $options = null): string
    {
        $options            = $options ?? [];
        $value              = $value ?? 1;
        $options['checked'] = true === $checked;

        if (app('session')->has('preFilled')) {
            $preFilled          = session('preFilled');
            $options['checked'] = $preFilled[$name] ?? $options['checked'];
        }

        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);

        unset($options['placeholder'], $options['autocomplete'], $options['class']);
        try {
            $html = view('form.checkbox', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render checkbox(): %s', $e->getMessage()));
            $html = 'Could not render checkbox.';
        }

        return $html;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function currencyList(string $name, $value = null, array $options = null): string
    {
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        // get all currencies:
        $list  = $currencyRepos->get();
        $array = [];
        /** @var TransactionCurrency $currency */
        foreach ($list as $currency) {
            $array[$currency->id] = $currency->name . ' (' . $currency->symbol . ')';
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function currencyListEmpty(string $name, $value = null, array $options = null): string
    {
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        // get all currencies:
        $list  = $currencyRepos->get();
        $array = [
            0 => (string)trans('firefly.no_currency'),
        ];
        /** @var TransactionCurrency $currency */
        foreach ($list as $currency) {
            $array[$currency->id] = $currency->name . ' (' . $currency->symbol . ')';
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function date(string $name, $value = null, array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        unset($options['placeholder']);
        try {
            $html = view('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render date(): %s', $e->getMessage()));
            $html = 'Could not render date.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param array $options
     *
     * @return string
     *
     */
    public function file(string $name, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        try {
            $html = view('form.file', compact('classes', 'name', 'label', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render file(): %s', $e->getMessage()));
            $html = 'Could not render file.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function integer(string $name, $value = null, array $options = null): string
    {
        $options         = $options ?? [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = '1';
        try {
            $html = view('form.integer', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render integer(): %s', $e->getMessage()));
            $html = 'Could not render integer.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function location(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        try {
            $html = view('form.location', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render location(): %s', $e->getMessage()));
            $html = 'Could not render location.';
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

    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param \Illuminate\Support\Collection $set
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function makeSelectList(Collection $set): array
    {
        $selectList = [];
        $fields     = ['title', 'name', 'description'];
        /** @var Eloquent $entry */
        foreach ($set as $entry) {
            $entryId = (int)$entry->id;
            $title   = null;

            foreach ($fields as $field) {
                if (isset($entry->$field) && null === $title) {
                    $title = $entry->$field;
                }
            }
            $selectList[$entryId] = $title;
        }

        return $selectList;
    }

    /**
     * @param \Illuminate\Support\Collection $set
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function makeSelectListWithEmpty(Collection $set): array
    {
        $selectList    = [];
        $selectList[0] = '(none)';
        $fields        = ['title', 'name', 'description'];
        /** @var Eloquent $entry */
        foreach ($set as $entry) {
            $entryId = (int)$entry->id;
            $title   = null;

            foreach ($fields as $field) {
                if (isset($entry->$field) && null === $title) {
                    $title = $entry->$field;
                }
            }
            $selectList[$entryId] = $title;
        }

        return $selectList;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function nonSelectableAmount(string $name, $value = null, array $options = null): string
    {
        $label            = $this->label($name, $options);
        $options          = $this->expandOptionArray($name, $label, $options);
        $classes          = $this->getHolderClasses($name);
        $value            = $this->fillFieldValue($name, $value);
        $options['step']  = 'any';
        $selectedCurrency = $options['currency'] ?? Amt::getDefaultCurrency();
        unset($options['currency'], $options['placeholder']);

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = round($value, $selectedCurrency->decimal_places);
        }
        try {
            $html = view('form.non-selectable-amount', compact('selectedCurrency', 'classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render nonSelectableAmount(): %s', $e->getMessage()));
            $html = 'Could not render nonSelectableAmount.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function number(string $name, $value = null, array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['placeholder']);
        try {
            $html = view('form.number', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render number(): %s', $e->getMessage()));
            $html = 'Could not render number.';
        }

        return $html;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return string
     *
     */
    public function optionsList(string $type, string $name): string
    {
        try {
            $html = view('form.options', compact('type', 'name'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render select(): %s', $e->getMessage()));
            $html = 'Could not render optionsList.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param array $options
     *
     * @return string
     *
     */
    public function password(string $name, array $options = null): string
    {

        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        try {
            $html = view('form.password', compact('classes', 'name', 'label', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render password(): %s', $e->getMessage()));
            $html = 'Could not render password.';
        }

        return $html;
    }

    /**
     * Function to render a percentage.
     *
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function percentage(string $name, $value = null, array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['placeholder']);
        try {
            $html = view('form.percentage', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render percentage(): %s', $e->getMessage()));
            $html = 'Could not render percentage.';
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
    public function piggyBankList(string $name, $value = null, array $options = null): string
    {

        // make repositories
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);
        $piggyBanks = $repository->getPiggyBanksWithAmount();
        $array      = [
            0 => (string)trans('firefly.none_in_select_list'),
        ];
        /** @var PiggyBank $piggy */
        foreach ($piggyBanks as $piggy) {
            $array[$piggy->id] = $piggy->name;
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function ruleGroupList(string $name, $value = null, array $options = null): string
    {
        /** @var RuleGroupRepositoryInterface $groupRepos */
        $groupRepos = app(RuleGroupRepositoryInterface::class);

        // get all currencies:
        $list  = $groupRepos->get();
        $array = [];
        /** @var RuleGroup $group */
        foreach ($list as $group) {
            $array[$group->id] = $group->title;
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * @param string $name
     * @param null $value
     * @param array|null $options
     *
     * @return HtmlString
     */
    public function ruleGroupListWithEmpty(string $name, $value = null, array $options = null): HtmlString
    {
        $options          = $options ?? [];
        $options['class'] = 'form-control';
        /** @var RuleGroupRepositoryInterface $groupRepos */
        $groupRepos = app(RuleGroupRepositoryInterface::class);

        // get all currencies:
        $list  = $groupRepos->get();
        $array = [
            0 => (string)trans('firefly.none_in_select_list'),
        ];
        /** @var RuleGroup $group */
        foreach ($list as $group) {
            if (isset($options['hidden']) && (int)$options['hidden'] !== $group->id) {
                $array[$group->id] = $group->title;
            }
        }

        return Form::select($name, $array, $value, $options);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @param array $options
     *
     * @return string
     */
    public function select(string $name, array $list = null, $selected = null, array $options = null): string
    {
        $list     = $list ?? [];
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);
        unset($options['autocomplete'], $options['placeholder']);
        try {
            $html = view('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render select(): %s', $e->getMessage()));
            $html = 'Could not render select.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function staticText(string $name, $value, array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        try {
            $html = view('form.static', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render staticText(): %s', $e->getMessage()));
            $html = 'Could not render staticText.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function tags(string $name, $value = null, array $options = null): string
    {
        $label                = $this->label($name, $options);
        $options              = $this->expandOptionArray($name, $label, $options);
        $classes              = $this->getHolderClasses($name);
        $value                = $this->fillFieldValue($name, $value);
        $options['data-role'] = 'tagsinput';
        try {
            $html = view('form.tags', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render tags(): %s', $e->getMessage()));
            $html = 'Could not render tags.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function text(string $name, $value = null, array $options = null): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        try {
            $html = view('form.text', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render text(): %s', $e->getMessage()));
            $html = 'Could not render text.';
        }

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     *
     */
    public function textarea(string $name, $value = null, array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['rows'] = 4;

        if (null === $value) {
            $value = '';
        }

        try {
            $html = view('form.textarea', compact('classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render textarea(): %s', $e->getMessage()));
            $html = 'Could not render textarea.';
        }

        return $html;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string $name
     * @param string $view
     * @param mixed $value
     * @param array $options
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function allCurrencyField(string $name, string $view, $value = null, array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = $options['currency'] ?? Amt::getDefaultCurrency();
        /** @var Collection $currencies */
        $currencies = app('amount')->getAllCurrencies();
        unset($options['currency'], $options['placeholder']);

        // perhaps the currency has been sent to us in the field $amount_currency_id_$name (amount_currency_id_amount)
        $preFilled      = session('preFilled');
        $key            = 'amount_currency_id_' . $name;
        $sentCurrencyId = isset($preFilled[$key]) ? (int)$preFilled[$key] : $defaultCurrency->id;

        Log::debug(sprintf('Sent currency ID is %d', $sentCurrencyId));

        // find this currency in set of currencies:
        foreach ($currencies as $currency) {
            if ($currency->id === $sentCurrencyId) {
                $defaultCurrency = $currency;
                Log::debug(sprintf('default currency is now %s', $defaultCurrency->code));
                break;
            }
        }

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = round($value, $defaultCurrency->decimal_places);
        }
        try {
            $html = view('form.' . $view, compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render currencyField(): %s', $e->getMessage()));
            $html = 'Could not render currencyField.';
        }

        return $html;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string $name
     * @param string $view
     * @param mixed $value
     * @param array $options
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function currencyField(string $name, string $view, $value = null, array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = $options['currency'] ?? Amt::getDefaultCurrency();
        /** @var Collection $currencies */
        $currencies = app('amount')->getCurrencies();
        unset($options['currency'], $options['placeholder']);

        // perhaps the currency has been sent to us in the field $amount_currency_id_$name (amount_currency_id_amount)
        $preFilled      = session('preFilled');
        $key            = 'amount_currency_id_' . $name;
        $sentCurrencyId = isset($preFilled[$key]) ? (int)$preFilled[$key] : $defaultCurrency->id;

        Log::debug(sprintf('Sent currency ID is %d', $sentCurrencyId));

        // find this currency in set of currencies:
        foreach ($currencies as $currency) {
            if ($currency->id === $sentCurrencyId) {
                $defaultCurrency = $currency;
                Log::debug(sprintf('default currency is now %s', $defaultCurrency->code));
                break;
            }
        }

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = round($value, $defaultCurrency->decimal_places);
        }
        try {
            $html = view('form.' . $view, compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render currencyField(): %s', $e->getMessage()));
            $html = 'Could not render currencyField.';
        }

        return $html;
    }

    /**
     * @param       $name
     * @param       $label
     * @param array $options
     *
     * @return array
     */
    protected function expandOptionArray(string $name, $label, array $options = null): array
    {
        $options                 = $options ?? [];
        $name                    = str_replace('[]', '', $name);
        $options['class']        = 'form-control';
        $options['id']           = 'ffInput_' . $name;
        $options['autocomplete'] = 'off';
        $options['placeholder']  = ucfirst($label);

        return $options;
    }

    /**
     * @param string $name
     * @param        $value
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function fillFieldValue(string $name, $value = null)
    {
        if (app('session')->has('preFilled')) {
            $preFilled = session('preFilled');
            $value     = isset($preFilled[$name]) && null === $value ? $preFilled[$name] : $value;
        }

        try {
            if (null !== request()->old($name)) {
                $value = request()->old($name);
            }
        } catch (RuntimeException $e) {
            // don't care about session errors.
            Log::debug(sprintf('Run time: %s', $e->getMessage()));
        }

        if ($value instanceof Carbon) {
            $value = $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function getHolderClasses(string $name): string
    {
        // Get errors from session:
        /** @var MessageBag $errors */
        $errors  = session('errors');
        $classes = 'form-group';

        if (null !== $errors && $errors->has($name)) {
            $classes = 'form-group has-error has-feedback';
        }

        return $classes;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param $name
     * @param $options
     *
     * @return mixed
     */
    protected function label(string $name, array $options = null): string
    {
        $options = $options ?? [];
        if (isset($options['label'])) {
            return $options['label'];
        }
        $name = str_replace('[]', '', $name);

        return (string)trans('form.' . $name);
    }
}
