<?php

namespace Firefly\Helper\Form;

use Illuminate\Events\Dispatcher;

/**
 * Class FormTrigger
 *
 * @package Firefly\Helper\Form
 */
class FormTrigger
{

    public function registerFormExtensions()
    {
        \Form::macro(
            'budget', function () {
                $helper = new FormHelper;

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