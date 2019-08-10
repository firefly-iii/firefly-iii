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
use FireflyIII\Support\Form\FormSupport;
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
    use FormSupport;
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
}
