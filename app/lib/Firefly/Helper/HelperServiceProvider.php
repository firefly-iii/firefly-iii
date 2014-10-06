<?php
namespace Firefly\Helper;

use Illuminate\Support\ServiceProvider;

/**
 * Class HelperServiceProvider
 *
 * @package Firefly\Helper
 */
class HelperServiceProvider extends ServiceProvider
{


    /**
     * Triggered automatically by Laravel
     */
    public function register()
    {
        // controllers:
        $this->app->bind(
            'Firefly\Helper\Controllers\AccountInterface',
            'Firefly\Helper\Controllers\Account'
        );
        $this->app->bind(
            'Firefly\Helper\Controllers\ChartInterface',
            'Firefly\Helper\Controllers\Chart'
        );

        $this->app->bind(
            'Firefly\Helper\Controllers\JsonInterface',
            'Firefly\Helper\Controllers\Json'
        );

        $this->app->bind(
            'Firefly\Helper\Controllers\RecurringInterface',
            'Firefly\Helper\Controllers\Recurring'
        );

        $this->app->bind(
            'Firefly\Helper\Controllers\SearchInterface',
            'Firefly\Helper\Controllers\Search'
        );

        $this->app->bind(
            'Firefly\Helper\Controllers\TransactionInterface',
            'Firefly\Helper\Controllers\Transaction'
        );

        $this->app->bind(
            'Firefly\Helper\Controllers\CategoryInterface',
            'Firefly\Helper\Controllers\Category'
        );

        $this->app->bind(
            'Firefly\Helper\Controllers\BudgetInterface',
            'Firefly\Helper\Controllers\Budget'
        );

        // mail:
        $this->app->bind(
            'Firefly\Helper\Email\EmailHelperInterface',
            'Firefly\Helper\Email\EmailHelper'
        );

        // settings:
        $this->app->bind(
            'Firefly\Helper\Preferences\PreferencesHelperInterface',
            'Firefly\Helper\Preferences\PreferencesHelper'
        );
        // settings:
        $this->app->bind(
            'Firefly\Helper\Toolkit\ToolkitInterface',
            'Firefly\Helper\Toolkit\Toolkit'
        );
    }

}