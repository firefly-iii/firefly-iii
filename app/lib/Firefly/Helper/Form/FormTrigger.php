<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 25/07/14
 * Time: 21:04
 */

namespace Firefly\Helper\Form;

use Illuminate\Events\Dispatcher;
/**
 * Class FormTrigger
 *
 * @package Firefly\Helper\Form
 */
class FormTrigger {

    public function registerFormExtensions() {
        \Form::macro(
            'budget', function () {
                $helper = new \Firefly\Helper\Form\FormHelper;
                return $helper->budget();
            }
        );
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('laravel.booted', 'Firefly\Helper\Form\FormTrigger@registerFormExtensions');

    }

} 