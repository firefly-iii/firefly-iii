<?php

namespace Firefly\Form;


use Firefly\Exception\FireflyException;
use Illuminate\Support\MessageBag;

class Form
{

    /**
     * @param $name
     * @param null $value
     * @param array $options
     * @return string
     * @throws FireflyException
     */
    public static function ffAmount($name, $value = null, array $options = [])
    {
        $options['step'] = 'any';
        $options['min'] = '0.01';
        return self::ffInput('number', $name, $value, $options);

    }

    public static function ffDate($name, $value = null, array $options = [])
    {
        return self::ffInput('date', $name, $value, $options);
    }

    /**
     * @param $name
     * @param array $list
     * @param null $selected
     * @param array $options
     * @return string
     * @throws FireflyException
     */
    public static function ffSelect($name, array $list = [], $selected = null, array $options = [])
    {
        return self::ffInput('select', $name, $selected, $options, $list);
    }

    /**
     * @param $name
     * @param null $value
     * @param array $options
     * @return string
     * @throws FireflyException
     */
    public static function ffText($name, $value = null, array $options = array())
    {
        return self::ffInput('text', $name, $value, $options);

    }

    /**
     * @param $type
     * @param $name
     * @param null $value
     * @param array $options
     * @param array $list
     * @return string
     * @throws FireflyException
     */
    public static function ffInput($type, $name, $value = null, array $options = array(), $list = [])
    {
        /*
         * add some defaults to this method:
         */
        $options['class'] = 'form-control';
        $options['id'] = 'ffInput_' . $name;
        $options['autocomplete'] = 'off';
        $options['type'] = 'text';

        /*
         * Make label and placeholder look nice.
         */
        $options['placeholder'] = isset($options['label']) ? $options['label'] : ucfirst($name);
        $options['label'] = isset($options['label']) ? $options['label'] : ucfirst($name);
        if ($name == 'account_id') {
            $options['label'] = 'Asset account';
        }
        $options['label'] = str_replace(['_'], [' '], $options['label']);
        $options['placeholder'] = str_replace(['_'], [' '], $options['placeholder']);

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
        $label = isset($options['label']) ? $options['label'] : ucfirst($name);
        $html = '<div class="' . $classes . '">';
        $html .= '<label for="' . $options['id'] . '" class="col-sm-4 control-label">' . $label . '</label>';
        $html .= '<div class="col-sm-8">';


        /*
         * Switch input type:
         */
        switch ($type) {
            case 'text':
                $html .= \Form::input('text', $name, $value, $options);
                break;
            case 'number':
                $html .= '<div class="input-group"><div class="input-group-addon">&euro;</div>';
                $html .= \Form::input('number', $name, $value, $options);
                $html .= '</div>';
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