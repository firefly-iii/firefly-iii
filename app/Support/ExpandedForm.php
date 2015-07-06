<?php

namespace FireflyIII\Support;

use Amount as Amt;
use Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Input;
use RuntimeException;
use Session;
use View;

/**
 * Class ExpandedForm
 *
 * @package FireflyIII\Support
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
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
        unset($options['currency']);
        unset($options['placeholder']);
        $html = View::make('form.amount', compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;

    }

    /**
     * @param $name
     * @param $options
     *
     * @return mixed
     */
    protected function label($name, $options)
    {
        if (isset($options['label'])) {
            return $options['label'];
        }

        return trans('form.' . $name);

    }

    /**
     * @param       $name
     * @param       $label
     * @param array $options
     *
     * @return array
     */
    protected function expandOptionArray($name, $label, array $options)
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
    protected function getHolderClasses($name)
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
    protected function fillFieldValue($name, $value)
    {
        if (Session::has('preFilled')) {
            $preFilled = Session::get('preFilled');
            $value     = isset($preFilled[$name]) && is_null($value) ? $preFilled[$name] : $value;
        }
        // @codeCoverageIgnoreStart
        try {
            if (!is_null(Input::old($name))) {
                $value = Input::old($name);
            }
        } catch (RuntimeException $e) {
            // don't care about session errors.
        }

        // @codeCoverageIgnoreEnd

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
        unset($options['currency']);
        unset($options['placeholder']);
        $html = View::make('form.balance', compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

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
        unset($options['placeholder']);
        $html = View::make('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();

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
            $entryId = intval($entry->id);
            $title   = null;

            foreach ($fields as $field) {
                if (isset($entry->$field)) {
                    $title = $entry->$field;
                }
            }
            $selectList[$entryId] = $title;
        }

        return $selectList;
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
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
        $previousValue = null;

        // @codeCoverageIgnoreStart
        try {
            $previousValue = Input::old('post_submit_action');
        } catch (RuntimeException $e) {
            // don't care
        }
        // @codeCoverageIgnoreEnd

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
        unset($options['autocomplete']);
        unset($options['placeholder']);
        $html = View::make('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

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
     * @param array $options
     *
     * @return string
     */
    public function file($name, array $options = [])
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = View::make('form.file', compact('classes', 'name', 'label', 'options'))->render();

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
