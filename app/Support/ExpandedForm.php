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
use Illuminate\Support\MessageBag;
use Log;
use RuntimeException;
use Session;

/**
 * Class ExpandedForm.
 *
 */
class ExpandedForm
{
    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function activeAssetAccountList(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        $assetAccounts   = $repository->getActiveAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($assetAccounts as $account) {
            $balance    = app('steam')->balance($account, new Carbon);
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
            $currency   = $currencyRepos->findNull($currencyId);
            $role       = $repository->getMetaValue($account, 'accountRole');
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
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function amount(string $name, $value = null, array $options = null): string
    {
        return $this->currencyField($name, 'amount', $value, $options);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
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

        $html = view('form.amount-no-currency', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function amountSmall(string $name, $value = null, array $options = null): string
    {
        return $this->currencyField($name, 'amount-small', $value, $options);
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
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
            $role = $repository->getMetaValue($account, 'accountRole');
            if (null === $role) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }
            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name;
        }

        unset($options['class']);
        $html = view('form.assetAccountCheckList', compact('classes', 'selected', 'name', 'label', 'options', 'grouped'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function assetAccountList(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        // make repositories
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        $assetAccounts   = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $grouped         = [];
        // group accounts:
        /** @var Account $account */
        foreach ($assetAccounts as $account) {
            $balance    = app('steam')->balance($account, new Carbon);
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
            $currency   = $currencyRepos->findNull($currencyId);
            $role       = $repository->getMetaValue($account, 'accountRole');
            if (0 === \strlen($role)) {
                $role = 'no_account_type'; // @codeCoverageIgnore
            }
            if (null === $currency) {
                $currency = $defaultCurrency;
            }

            $key                         = (string)trans('firefly.opt_group_' . $role);
            $grouped[$key][$account->id] = $account->name . ' (' . app('amount')->formatAnything($currency, $balance, false) . ')';
        }
        $res = $this->select($name, $grouped, $value, $options);

        return $res;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function balance(string $name, $value = null, array $options = null): string
    {
        return $this->currencyField($name, 'balance', $value, $options);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $name
     * @param int    $value
     * @param mixed  $checked
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function checkbox(string $name, int $value = null, $checked = null, array $options = null): string
    {
        $options            = $options ?? [];
        $value              = $value ?? 1;
        $options['checked'] = true === $checked ? true : false;

        if (Session::has('preFilled')) {
            $preFilled          = session('preFilled');
            $options['checked'] = $preFilled[$name] ?? $options['checked'];
        }

        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);

        unset($options['placeholder'], $options['autocomplete'], $options['class']);

        $html = view('form.checkbox', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function currencyList(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
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
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function currencyListEmpty(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
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
        $res = $this->select($name, $array, $value, $options);

        return $res;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function date(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        unset($options['placeholder']);
        $html = view('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function file(string $name, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = view('form.file', compact('classes', 'name', 'label', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function integer(string $name, $value = null, array $options = null): string
    {
        $options         = $options ?? [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = '1';
        $html            = view('form.integer', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function location(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = view('form.location', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param \Illuminate\Support\Collection $set
     *
     * @return array
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

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param string $name
     * @param array  $list
     * @param mixed  $selected
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function multiRadio(string $name, array $list = null, $selected = null, array $options = null): string
    {
        $options  = $options ?? [];
        $list     = $list ?? [];
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);

        unset($options['class']);
        $html = view('form.multiRadio', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \Throwable
     */
    public function nonSelectableAmount(string $name, $value = null, array $options = null): string
    {
        $options          = $options ?? [];
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

        $html = view('form.non-selectable-amount', compact('selectedCurrency', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \Throwable
     */
    public function nonSelectableBalance(string $name, $value = null, array $options = null): string
    {
        $options          = $options ?? [];
        $label            = $this->label($name, $options);
        $options          = $this->expandOptionArray($name, $label, $options);
        $classes          = $this->getHolderClasses($name);
        $value            = $this->fillFieldValue($name, $value);
        $options['step']  = 'any';
        $selectedCurrency = $options['currency'] ?? Amt::getDefaultCurrency();
        unset($options['currency'], $options['placeholder']);

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $decimals = $selectedCurrency->decimal_places ?? 2;
            $value    = round($value, $decimals);
        }

        $html = view('form.non-selectable-amount', compact('selectedCurrency', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function number(string $name, $value = null, array $options = null): string
    {
        $options         = $options ?? [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['placeholder']);

        $html = view('form.number', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return string
     * @throws \Throwable
     */
    public function optionsList(string $type, string $name): string
    {
        return view('form.options', compact('type', 'name'))->render();
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function password(string $name, array $options = null): string
    {

        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = view('form.password', compact('classes', 'name', 'label', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function piggyBankList(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
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
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     */
    public function ruleGroupList(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
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
     * @param mixed   $value
     * @param array   $options
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function ruleGroupListWithEmpty(string $name, $value = null, array $options = null)
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
     * @param array  $list
     * @param mixed   $selected
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function select(string $name, array $list = null, $selected = null, array $options = null): string
    {
        $list     = $list ?? [];
        $options  = $options ?? [];
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);
        unset($options['autocomplete'], $options['placeholder']);
        $html = view('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function staticText(string $name, $value, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = view('form.static', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function tags(string $name, $value = null, array $options = null): string
    {
        $options              = $options ?? [];
        $label                = $this->label($name, $options);
        $options              = $this->expandOptionArray($name, $label, $options);
        $classes              = $this->getHolderClasses($name);
        $value                = $this->fillFieldValue($name, $value);
        $options['data-role'] = 'tagsinput';
        $html                 = view('form.tags', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function text(string $name, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = view('form.text', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws \Throwable
     */
    public function textarea(string $name, $value = null, array $options = null): string
    {
        $options         = $options ?? [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['rows'] = 4;
        $html            = view('form.textarea', compact('classes', 'name', 'label', 'value', 'options'))->render();

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
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    protected function fillFieldValue(string $name, $value)
    {
        if (Session::has('preFilled')) {
            $preFilled = session('preFilled');
            $value     = isset($preFilled[$name]) && null === $value ? $preFilled[$name] : $value;
        }
        try {
            if (null !== request()->old($name)) {
                $value = request()->old($name);
            }
        } catch (RuntimeException $e) {
            // don't care about session errors.
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

    /**
     * @param $name
     * @param $options
     *
     * @return mixed
     */
    protected function label(string $name, array $options): string
    {
        if (isset($options['label'])) {
            return $options['label'];
        }
        $name = str_replace('[]', '', $name);

        return (string)trans('form.' . $name);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $name
     * @param string $view
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \Throwable
     */
    private function currencyField(string $name, string $view, $value = null, array $options = null): string
    {
        $options = $options ?? [];
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = $options['currency'] ?? Amt::getDefaultCurrency();
        $currencies      = app('amount')->getAllCurrencies();
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

        $html = view('form.' . $view, compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }
}
