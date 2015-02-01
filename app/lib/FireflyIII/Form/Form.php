<?php

namespace FireflyIII\Form;


use FireflyIII\Exception\FireflyException;
use Illuminate\Support\MessageBag;

/**
 * Class Form
 *
 * @package FireflyIII\Form
 */
class Form
{

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffAmount($name, $value = null, array $options = [])
    {
        $label           = self::label($name, $options);
        $options         = self::expandOptionArray($name, $label, $options);
        $classes         = self::getHolderClasses($name);
        $value           = self::fillFieldValue($name, $value);
        $options['step'] = 'any';
        $options['min']  = '0.01';
        $defaultCurrency = isset($options['currency']) ? $options['currency'] : \Amount::getDefaultCurrency();
        $currencies      = \TransactionCurrency::orderBy('code', 'ASC')->get();
        $html            = \View::make('form.amount', compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param $name
     * @param $options
     *
     * @return string
     */
    public static function label($name, $options)
    {
        if (isset($options['label'])) {
            return $options['label'];
        }
        $labels = ['amount_min'      => 'Amount (min)', 'amount_max' => 'Amount (max)', 'match' => 'Matches on', 'repeat_freq' => 'Repetition',
                   'account_from_id' => 'Account from', 'account_to_id' => 'Account to', 'account_id' => 'Asset account', 'budget_id' => 'Budget'
                   , 'piggy_bank_id' => 'Piggy bank'];


        return isset($labels[$name]) ? $labels[$name] : str_replace('_', ' ', ucfirst($name));

    }

    /**
     * @param       $name
     * @param       $label
     * @param array $options
     *
     * @return array
     */
    public static function expandOptionArray($name, $label, array $options)
    {
        $options['class']        = 'form-control';
        $options['id']           = 'ffInput_' . $name;
        $options['autocomplete'] = 'off';
        $options['placeholder']  = ucfirst($label);

        return $options;
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function getHolderClasses($name)
    {
        /*
       * Get errors, warnings and successes from session:
       */
        /** @var MessageBag $errors */
        $errors = \Session::get('errors');

        /** @var MessageBag $successes */
        $successes = \Session::get('successes');

        switch (true) {
            case (!is_null($errors) && $errors->has($name)):
                $classes = 'form-group has-error has-feedback';
                break;
            case (!is_null($successes) && $successes->has($name)):
                $classes = 'form-group has-success has-feedback';
                break;
            default:
                $classes = 'form-group';
                break;
        }

        return $classes;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public static function fillFieldValue($name, $value)
    {
        if (\Session::has('preFilled')) {
            $preFilled = \Session::get('preFilled');
            $value     = isset($preFilled[$name]) && is_null($value) ? $preFilled[$name] : $value;
        }
        if (!is_null(\Input::old($name))) {
            $value = \Input::old($name);
        }

        return $value;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffBalance($name, $value = null, array $options = [])
    {
        $label           = self::label($name, $options);
        $options         = self::expandOptionArray($name, $label, $options);
        $classes         = self::getHolderClasses($name);
        $value           = self::fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = isset($options['currency']) ? $options['currency'] : \Amount::getDefaultCurrency();
        $currencies      = \TransactionCurrency::orderBy('code', 'ASC')->get();
        $html            = \View::make('form.balance', compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param int   $value
     * @param null  $checked
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffCheckbox($name, $value = 1, $checked = null, $options = [])
    {
        $options['checked'] = $checked === true ? true : null;
        $label              = self::label($name, $options);
        $options            = self::expandOptionArray($name, $label, $options);
        $classes            = self::getHolderClasses($name);
        $value              = self::fillFieldValue($name, $value);

        unset($options['placeholder'], $options['autocomplete'], $options['class']);

        $html = \View::make('form.checkbox', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffDate($name, $value = null, array $options = [])
    {
        $label   = self::label($name, $options);
        $options = self::expandOptionArray($name, $label, $options);
        $classes = self::getHolderClasses($name);
        $value   = self::fillFieldValue($name, $value);
        $html    = \View::make('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffInteger($name, $value = null, array $options = [])
    {
        $label           = self::label($name, $options);
        $options         = self::expandOptionArray($name, $label, $options);
        $classes         = self::getHolderClasses($name);
        $value           = self::fillFieldValue($name, $value);
        $options['step'] = '1';
        $html            = \View::make('form.integer', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * Return buttons for update/validate/return.
     *
     * @param $type
     * @param $name
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffOptionsList($type, $name)
    {
        $previousValue = \Input::old('post_submit_action');
        $previousValue = is_null($previousValue) ? 'store' : $previousValue;
        $html          = \View::make('form.options', compact('type', 'name', 'previousValue'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
     */
    public static function ffSelect($name, array $list = [], $selected = null, array $options = [])
    {
        $label    = self::label($name, $options);
        $options  = self::expandOptionArray($name, $label, $options);
        $classes  = self::getHolderClasses($name);
        $selected = self::fillFieldValue($name, $selected);
        $html     = \View::make('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffTags($name, $value = null, array $options = [])
    {
        $label                = self::label($name, $options);
        $options              = self::expandOptionArray($name, $label, $options);
        $classes              = self::getHolderClasses($name);
        $value                = self::fillFieldValue($name, $value);
        $options['data-role'] = 'tagsinput';
        $html                 = \View::make('form.tags', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffText($name, $value = null, array $options = [])
    {
        $label   = self::label($name, $options);
        $options = self::expandOptionArray($name, $label, $options);
        $classes = self::getHolderClasses($name);
        $value   = self::fillFieldValue($name, $value);
        $html    = \View::make('form.text', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }
}
