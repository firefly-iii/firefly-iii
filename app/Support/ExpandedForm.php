<?php

namespace FireflyIII\Support;

use Amount as Amt;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Input;
use Session;
use View;

/**
 * Class ExpandedForm
 *
 * @package FireflyIII\Support
 */
class ExpandedForm
{

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function amount($name, $value = null, array $options = [])
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $options['min']  = '0.01';
        $defaultCurrency = isset($options['currency']) ? $options['currency'] : Amt::getDefaultCurrency();
        $currencies      = Amt::getAllCurrencies();
        $html            = View::make('form.amount', compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param $name
     * @param $options
     *
     * @return mixed
     */
    public function label($name, $options)
    {
        if (isset($options['label'])) {
            return $options['label'];
        }
        $labels = [
            'amount_min'           => 'Amount (min)',
            'amount_max'           => 'Amount (max)',
            'match'                => 'Matches on',
            'repeat_freq'          => 'Repetition',
            'account_from_id'      => 'Account from',
            'account_to_id'        => 'Account to',
            'account_id'           => 'Asset account',
            'budget_id'            => 'Budget',
            'openingBalance'       => 'Opening balance',
            'tagMode'              => 'Tag mode',
            'tagPosition'          => 'Tag location',
            'virtualBalance'       => 'Virtual balance',
            'longitude_latitude'   => 'Location',
            'targetamount'         => 'Target amount',
            'accountRole'          => 'Account role',
            'openingBalanceDate'   => 'Opening balance date',
            'ccType'               => 'Credit card payment plan',
            'ccMonthlyPaymentDate' => 'Credit card monthly payment date',
            'piggy_bank_id'        => 'Piggy bank'];


        return isset($labels[$name]) ? $labels[$name] : str_replace('_', ' ', ucfirst($name));

    }

    /**
     * @param       $name
     * @param       $label
     * @param array $options
     *
     * @return array
     */
    public function expandOptionArray($name, $label, array $options)
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
    public function getHolderClasses($name)
    {
        /*
       * Get errors from session:
       */
        /** @var MessageBag $errors */
        $errors  = Session::get('errors');
        $classes = 'form-group';

        if (!is_null($errors) && $errors->has($name)) {
            $classes = 'form-group has-error has-feedback';
        }

        return $classes;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function fillFieldValue($name, $value)
    {
        if (Session::has('preFilled')) {
            $preFilled = Session::get('preFilled');
            $value     = isset($preFilled[$name]) && is_null($value) ? $preFilled[$name] : $value;
        }
        if (!is_null(Input::old($name))) {
            $value = Input::old($name);
        }

        return $value;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function balance($name, $value = null, array $options = [])
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = isset($options['currency']) ? $options['currency'] : Amt::getDefaultCurrency();
        $currencies      = Amt::getAllCurrencies();
        $html            = View::make('form.balance', compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param int   $value
     * @param null  $checked
     * @param array $options
     *
     * @return string
     */
    public function checkbox($name, $value = 1, $checked = null, $options = [])
    {
        $options['checked'] = $checked === true ? true : null;
        $label              = $this->label($name, $options);
        $options            = $this->expandOptionArray($name, $label, $options);
        $classes            = $this->getHolderClasses($name);
        $value              = $this->fillFieldValue($name, $value);

        unset($options['placeholder'], $options['autocomplete'], $options['class']);

        $html = View::make('form.checkbox', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function date($name, $value = null, array $options = [])
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = View::make('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function integer($name, $value = null, array $options = [])
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = '1';
        $html            = View::make('form.integer', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function location($name, $value = null, array $options = [])
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = View::make('form.location', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     *
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param \Illuminate\Support\Collection $set
     * @param bool                           $addEmpty
     *
     * @return mixed
     */
    public function makeSelectList(Collection $set, $addEmpty = false)
    {
        $selectList = [];
        if ($addEmpty) {
            $selectList[0] = '(none)';
        }
        $fields = ['title', 'name', 'description'];
        /** @var Eloquent $entry */
        foreach ($set as $entry) {
            $id    = intval($entry->id);
            $title = null;

            foreach ($fields as $field) {
                if (isset($entry->$field)) {
                    $title = $entry->$field;
                }
            }
            $selectList[$id] = $title;
        }

        return $selectList;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function month($name, $value = null, array $options = [])
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = View::make('form.month', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
     * @internal param null $value
     */
    public function multiRadio($name, array $list = [], $selected = null, array $options = [])
    {
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);

        unset($options['class']);
        $html = View::make('form.multiRadio', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param $type
     * @param $name
     *
     * @return string
     */
    public function optionsList($type, $name)
    {
        $previousValue = Input::old('post_submit_action');
        $previousValue = is_null($previousValue) ? 'store' : $previousValue;
        $html          = View::make('form.options', compact('type', 'name', 'previousValue'))->render();

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
    public function select($name, array $list = [], $selected = null, array $options = [])
    {
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);
        $html     = View::make('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function tags($name, $value = null, array $options = [])
    {
        $label                = $this->label($name, $options);
        $options              = $this->expandOptionArray($name, $label, $options);
        $classes              = $this->getHolderClasses($name);
        $value                = $this->fillFieldValue($name, $value);
        $options['data-role'] = 'tagsinput';
        $html                 = View::make('form.tags', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function text($name, $value = null, array $options = [])
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = View::make('form.text', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     */
    public function textarea($name, $value = null, array $options = [])
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['rows'] = 4;
        $html            = View::make('form.textarea', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }
}
