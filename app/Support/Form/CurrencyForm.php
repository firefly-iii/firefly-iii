<?php

/**
 * CurrencyForm.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support\Form;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class CurrencyForm
 *
 * All currency related form methods.
 */
class CurrencyForm
{
    use FormSupport;

    /**
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function amount(string $name, $value = null, ?array $options = null): string
    {
        return $this->currencyField($name, 'amount', $value, $options);
    }

    /**
     * @throws FireflyException
     *
     * @phpstan-param view-string $view
     */
    protected function currencyField(string $name, string $view, mixed $value = null, ?array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = $options['currency'] ?? app('amount')->getDefaultCurrency();

        /** @var Collection $currencies */
        $currencies      = app('amount')->getCurrencies();
        unset($options['currency'], $options['placeholder']);
        // perhaps the currency has been sent to us in the field $amount_currency_id_$name (amount_currency_id_amount)
        $preFilled       = session('preFilled');
        if (!is_array($preFilled)) {
            $preFilled = [];
        }
        $key             = 'amount_currency_id_'.$name;
        $sentCurrencyId  = array_key_exists($key, $preFilled) ? (int) $preFilled[$key] : $defaultCurrency->id;

        app('log')->debug(sprintf('Sent currency ID is %d', $sentCurrencyId));

        // find this currency in set of currencies:
        foreach ($currencies as $currency) {
            if ($currency->id === $sentCurrencyId) {
                $defaultCurrency = $currency;
                app('log')->debug(sprintf('default currency is now %s', $defaultCurrency->code));

                break;
            }
        }

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = app('steam')->bcround($value, $defaultCurrency->decimal_places);
        }

        try {
            $html = view('form.'.$view, compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render currencyField(): %s', $e->getMessage()));
            $html = 'Could not render currencyField.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * TODO describe and cleanup.
     *
     * @param mixed $value
     *
     * @throws FireflyException
     */
    public function balanceAll(string $name, $value = null, ?array $options = null): string
    {
        return $this->allCurrencyField($name, 'balance', $value, $options);
    }

    /**
     * TODO describe and cleanup
     *
     * @param mixed $value
     *
     * @throws FireflyException
     */
    protected function allCurrencyField(string $name, string $view, $value = null, ?array $options = null): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = $options['currency'] ?? app('amount')->getDefaultCurrency();

        /** @var Collection $currencies */
        $currencies      = app('amount')->getAllCurrencies();
        unset($options['currency'], $options['placeholder']);

        // perhaps the currency has been sent to us in the field $amount_currency_id_$name (amount_currency_id_amount)
        $preFilled       = session('preFilled');
        if (!is_array($preFilled)) {
            $preFilled = [];
        }
        $key             = 'amount_currency_id_'.$name;
        $sentCurrencyId  = array_key_exists($key, $preFilled) ? (int) $preFilled[$key] : $defaultCurrency->id;

        app('log')->debug(sprintf('Sent currency ID is %d', $sentCurrencyId));

        // find this currency in set of currencies:
        foreach ($currencies as $currency) {
            if ($currency->id === $sentCurrencyId) {
                $defaultCurrency = $currency;
                app('log')->debug(sprintf('default currency is now %s', $defaultCurrency->code));

                break;
            }
        }

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = app('steam')->bcround($value, $defaultCurrency->decimal_places);
        }

        try {
            $html = view('form.'.$view, compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render currencyField(): %s', $e->getMessage()));
            $html = 'Could not render currencyField.';

            throw new FireflyException($html, 0, $e);
        }

        return $html;
    }

    /**
     * TODO cleanup and describe
     *
     * @param mixed $value
     */
    public function currencyList(string $name, $value = null, ?array $options = null): string
    {
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        // get all currencies:
        $list          = $currencyRepos->get();
        $array         = [];

        /** @var TransactionCurrency $currency */
        foreach ($list as $currency) {
            $array[$currency->id] = $currency->name.' ('.$currency->symbol.')';
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * TODO cleanup and describe
     *
     * @param mixed $value
     */
    public function currencyListEmpty(string $name, $value = null, ?array $options = null): string
    {
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        // get all currencies:
        $list          = $currencyRepos->get();
        $array         = [
            0 => (string) trans('firefly.no_currency'),
        ];

        /** @var TransactionCurrency $currency */
        foreach ($list as $currency) {
            $array[$currency->id] = $currency->name.' ('.$currency->symbol.')';
        }

        return $this->select($name, $array, $value, $options);
    }
}
