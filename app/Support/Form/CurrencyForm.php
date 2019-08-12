<?php
/**
 * CurrencyForm.php
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


use Amount as Amt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class CurrencyForm
 *
 * All currency related form methods.
 *
 * TODO cleanup and describe.
 */
class CurrencyForm
{
    use FormSupport;

    /**
     * TODO cleanup and describe.
     * @param string $name
     * @param mixed  $value
     * @param array  $options
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
     * TODO cleanup and describe.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $options
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
     * TODO describe and cleanup.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $options
     *
     * @return string
     * @throws FireflyException
     */
    public function balanceAll(string $name, $value = null, array $options = null): string
    {
        return $this->allCurrencyField($name, 'balance', $value, $options);
    }

    /**
     * TODO cleanup and describe better.
     *
     * @param string $name
     * @param string $view
     * @param mixed  $value
     * @param array  $options
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

    /**
     * @param string $name
     * @param string $view
     * @param mixed  $value
     * @param array  $options
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

}