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
    public static function ffInteger($name, $value = null, array $options = [])
    {
        $options['step'] = '1';
        return self::ffInput('number', $name, $value, $options);

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
        $options['checked'] = $checked ? true : null;
        return self::ffInput('checkbox', $name, $value, $options);
    }

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
        $options['step'] = 'any';
        $options['min']  = '0.01';
        return self::ffInput('amount', $name, $value, $options);

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
        $options['step'] = 'any';
        return self::ffInput('amount', $name, $value, $options);

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
        return self::ffInput('date', $name, $value, $options);
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
        $options['data-role'] = 'tagsinput';
        return self::ffInput('text', $name, $value, $options);
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffSelect($name, array $list = [], $selected = null, array $options = [])
    {
        return self::ffInput('select', $name, $selected, $options, $list);
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffText($name, $value = null, array $options = array())
    {
        return self::ffInput('text', $name, $value, $options);

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
        $labels = [
            'amount_min'      => 'Amount (min)',
            'amount_max'      => 'Amount (max)',
            'match'           => 'Matches on',
            'repeat_freq'     => 'Repetition',
            'account_from_id' => 'Account from',
            'account_to_id'   => 'Account to',
            'account_id'      => 'Asset account'
        ];

        return isset($labels[$name]) ? $labels[$name] : str_replace('_', ' ', ucfirst($name));

    }

    /**
     * Return buttons for update/validate/return.
     *
     * @param $type
     * @param $name
     */
    public static function ffOptionsList($type, $name)
    {
        $previousValue = \Input::old('post_submit_action');
        $previousValue = is_null($previousValue) ? 'store' : $previousValue;
        /*
         * Store.
         */
        $store = '';
        switch ($type) {
            case 'create':
                $store = '<div class="form-group"><label for="default" class="col-sm-4 control-label">Store</label>';
                $store .= '<div class="col-sm-8"><div class="radio"><label>';
                $store .= \Form::radio('post_submit_action', 'store', $previousValue == 'store');
                $store .= 'Store ' . $name . '</label></div></div></div>';
                break;
            case 'update':
                $store = '<div class="form-group"><label for="default" class="col-sm-4 control-label">Store</label>';
                $store .= '<div class="col-sm-8"><div class="radio"><label>';
                $store .= \Form::radio('post_submit_action', 'update', $previousValue == 'store');
                $store .= 'Update ' . $name . '</label></div></div></div>';
                break;
            default:
                throw new FireflyException('Cannot create ffOptionsList for option (store) ' . $type);
                break;
        }

        /*
         * validate is always the same:
         */
        $validate = '<div class="form-group"><label for="validate_only" class="col-sm-4 control-label">Validate only';
        $validate .= '</label><div class="col-sm-8"><div class="radio"><label>';
        $validate .= \Form::radio('post_submit_action', 'validate_only', $previousValue == 'validate_only');
        $validate .= 'Only validate, do not save</label></div></div></div>';

        /*
         * Store & return:
         */
        switch ($type) {
            case 'create':
                $return = '<div class="form-group"><label for="return_to_form" class="col-sm-4 control-label">';
                $return .= 'Return here</label><div class="col-sm-8"><div class="radio"><label>';
                $return .= \Form::radio('post_submit_action', 'create_another', $previousValue == 'create_another');
                $return .= 'After storing, return here to create another one.</label></div></div></div>';
                break;
            case 'update':
                $return = '<div class="form-group"><label for="return_to_edit" class="col-sm-4 control-label">';
                $return .= 'Return here</label><div class="col-sm-8"><div class="radio"><label>';
                $return .= \Form::radio('post_submit_action', 'return_to_edit', $previousValue == 'return_to_edit');
                $return .= 'After updating, return here.</label></div></div></div>';
                break;
            default:
                throw new FireflyException('Cannot create ffOptionsList for option (store+return) ' . $type);
                break;
        }
        return $store . $validate . $return;
    }

    /**
     * @param       $type
     * @param       $name
     * @param null  $value
     * @param array $options
     * @param array $list
     *
     * @return string
     * @throws FireflyException
     */
    public static function ffInput($type, $name, $value = null, array $options = array(), $list = [])
    {
        /*
         * add some defaults to this method:
         */
        $options['class']        = 'form-control';
        $options['id']           = 'ffInput_' . $name;
        $options['autocomplete'] = 'off';
        $label                   = self::label($name, $options);
        /*
         * Make label and placeholder look nice.
         */
        $options['placeholder'] = ucfirst($name);

        /*
         * Get prefilled value:
         */
        if (\Session::has('prefilled')) {
            $prefilled = \Session::get('prefilled');
            $value     = isset($prefilled[$name]) && is_null($value) ? $prefilled[$name] : $value;
        }

        /*
         * Get the value.
         */
        if (!is_null(\Input::old($name))) {
            /*
             * Old value overrules $value.
             */
            $value = \Input::old($name);
        }

        /*
         * Get errors, warnings and successes from session:
         */
        /** @var MessageBag $errors */
        $errors = \Session::get('errors');

        /** @var MessageBag $warnings */
        $warnings = \Session::get('warnings');

        /** @var MessageBag $successes */
        $successes = \Session::get('successes');


        /*
         * If errors, add some more classes.
         */
        switch (true) {
            case (!is_null($errors) && $errors->has($name)):
                $classes = 'form-group has-error has-feedback';
                break;
            case (!is_null($warnings) && $warnings->has($name)):
                $classes = 'form-group has-warning has-feedback';
                break;
            case (!is_null($successes) && $successes->has($name)):
                $classes = 'form-group has-success has-feedback';
                break;
            default:
                $classes = 'form-group';
                break;
        }

        /*
         * Add some HTML.
         */
        $html = '<div class="' . $classes . '">';
        $html .= '<label for="' . $options['id'] . '" class="col-sm-4 control-label">' . $label . '</label>';
        $html .= '<div class="col-sm-8">';


        /*
         * Switch input type:
         */
        unset($options['label']);
        switch ($type) {
            case 'text':
                $html .= \Form::input('text', $name, $value, $options);
                break;
            case 'amount':
                $html .= '<div class="input-group"><div class="input-group-addon">&euro;</div>';
                $html .= \Form::input('number', $name, $value, $options);
                $html .= '</div>';
                break;
            case 'number':
                $html .= \Form::input('number', $name, $value, $options);
                break;
            case 'checkbox':
                $checked = $options['checked'];
                unset($options['checked'], $options['placeholder'], $options['autocomplete'], $options['class']);
                $html .= '<div class="checkbox"><label>';
                $html .= \Form::checkbox($name, $value, $checked, $options);
                $html .= '</label></div>';


                break;
            case 'date':
                $html .= \Form::input('date', $name, $value, $options);
                break;
            case 'select':
                $html .= \Form::select($name, $list, $value, $options);
                break;
            default:
                throw new FireflyException('Cannot handle type "' . $type . '" in FFFormBuilder.');
                break;
        }

        /*
         * If errors, respond to them:
         */

        if (!is_null($errors)) {
            if ($errors->has($name)) {
                $html .= '<span class="glyphicon glyphicon-remove form-control-feedback"></span>';
                $html .= '<p class="text-danger">' . e($errors->first($name)) . '</p>';
            }
        }
        unset($errors);
        /*
         * If warnings, respond to them:
         */

        if (!is_null($warnings)) {
            if ($warnings->has($name)) {
                $html .= '<span class="glyphicon glyphicon-warning-sign form-control-feedback"></span>';
                $html .= '<p class="text-warning">' . e($warnings->first($name)) . '</p>';
            }
        }
        unset($warnings);

        /*
         * If successes, respond to them:
         */

        if (!is_null($successes)) {
            if ($successes->has($name)) {
                $html .= '<span class="glyphicon glyphicon-ok form-control-feedback"></span>';
                $html .= '<p class="text-success">' . e($successes->first($name)) . '</p>';
            }
        }
        unset($successes);

        $html .= '</div>';
        $html .= '</div>';

        return $html;

    }
}