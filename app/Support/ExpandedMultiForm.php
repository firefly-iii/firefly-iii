<?php
/**
 * ExpandedMultiForm.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;

use Amount as Amt;
use Carbon\Carbon;
use Illuminate\Support\MessageBag;
use Input;
use RuntimeException;
use Session;

/**
 * Class ExpandedMultiForm
 *
 * @package FireflyIII\Support
 */
class ExpandedMultiForm
{

    /**
     * @param string $name
     * @param int    $index
     * @param null   $value
     * @param array  $options
     *
     * @return string
     */
    public function amount(string $name, int $index, $value = null, array $options = []): string
    {
        $label                       = $this->label($name, $options);
        $options                     = $this->expandOptionArray($name, $index, $label, $options);
        $classes                     = $this->getHolderClasses($name, $index);
        $value                       = $this->fillFieldValue($name, $index, $value);
        $options['step']             = 'any';
        $options['min']              = '0.01';
        $defaultCurrency             = isset($options['currency']) ? $options['currency'] : Amt::getDefaultCurrency();
        $currencies                  = Amt::getAllCurrencies();
        $options['data-hiddenfield'] = 'amount_currency_id_' . $name . '_' . $index;
        unset($options['currency']);
        unset($options['placeholder']);
        $html = view('form.multi.amount', compact('defaultCurrency', 'index', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param string $name
     * @param int    $index
     * @param array  $list
     * @param null   $selected
     * @param array  $options
     *
     * @return string
     */
    public function select(string $name, int $index, array $list = [], $selected = null, array $options = []): string
    {
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $index, $label, $options);
        $classes  = $this->getHolderClasses($name, $index);
        $selected = $this->fillFieldValue($name, $index, $selected);
        unset($options['autocomplete']);
        unset($options['placeholder']);
        $html = view('form.multi.select', compact('classes', 'index', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param int    $index
     * @param null   $value
     * @param array  $options
     *
     * @return string
     */
    public function text(string $name, int $index, $value = null, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $index, $label, $options);
        $classes = $this->getHolderClasses($name, $index);
        $value   = $this->fillFieldValue($name, $index, $value);
        $html    = view('form.multi.text', compact('classes', 'name', 'index', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param string $name
     * @param int    $index
     * @param string $label
     * @param array  $options
     *
     * @return array
     */
    protected function expandOptionArray(string $name, int $index, string $label, array $options): array
    {
        $options['class']        = 'form-control';
        $options['id']           = 'ffInput_' . $name . '_' . $index;
        $options['autocomplete'] = 'off';
        $options['placeholder']  = ucfirst($label);

        return $options;
    }

    /**
     * @param string $name
     * @param int    $index
     * @param        $value
     *
     * @return mixed
     */
    protected function fillFieldValue(string $name, int $index, $value)
    {
        if (Session::has('preFilled')) {
            $preFilled = session('preFilled');
            $value     = isset($preFilled[$name][$index]) && is_null($value) ? $preFilled[$name][$index] : $value;
        }
        try {
            if (!is_null(Input::old($name)[$index])) {
                $value = Input::old($name)[$index];
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
     * @param string $name
     * @param int    $index
     *
     * @return string
     */
    protected function getHolderClasses(string $name, int $index): string
    {
        /*
       * Get errors from session:
       */
        /** @var MessageBag $errors */
        $errors  = session('errors');
        $classes = 'form-group';
        $set     = [];

        if (!is_null($errors)) {
            $set = $errors->get($name . '.' . $index);
        }

        if (!is_null($errors) && count($set) > 0) {
            $classes = 'form-group has-error has-feedback';
        }

        return $classes;
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return string
     */
    protected function label(string $name, array $options): string
    {
        if (isset($options['label'])) {
            return $options['label'];
        }

        return strval(trans('form.' . $name));

    }
}