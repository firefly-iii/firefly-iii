<?php
namespace FireflyIII;

use FireflyIII\Shared\Toolkit\Date;
use FireflyIII\Shared\Toolkit\Filter;
use FireflyIII\Shared\Toolkit\Form;
use FireflyIII\Shared\Toolkit\Navigation;
use FireflyIII\Shared\Toolkit\Reminders;
use FireflyIII\Shared\Toolkit\Steam;
use FireflyIII\Shared\Validation\FireflyValidator;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

/**
 * Class FF3ServiceProvider
 *
 * @package FireflyIII
 */
class FF3ServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->app->validator->resolver(
            function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
    }

    /**
     * Return the services bla bla.
     *
     * @return array
     */
    public function provides()
    {
        return ['reminders', 'filters', 'datekit', 'navigation'];
    }

    /**
     * Triggered automatically by Laravel
     */
    public function register()
    {
        // FORMAT:
        #$this->app->bind('Interface', 'Class');
        $this->registerFacades();
        $this->registerInterfaces();
        $this->registerAliases();


    }

    public function registerFacades()
    {
        $this->app->bind(
            'reminders', function () {
            return new Reminders;
        }
        );
        $this->app->bind(
            'filter', function () {
            return new Filter;
        }
        );
        $this->app->bind(
            'datekit', function () {
            return new Date;
        }
        );
        $this->app->bind(
            'navigation', function () {
            return new Navigation;
        }
        );
        $this->app->bind(
            'ffform', function () {
            return new Form;
        }
        );
        $this->app->bind(
            'steam', function () {
            return new Steam;
        }
        );
    }

    public function registerInterfaces()
    {
        // preferences:
        $this->app->bind('FireflyIII\Shared\Preferences\PreferencesInterface', 'FireflyIII\Shared\Preferences\Preferences');

        // registration and user mail:
        $this->app->bind('FireflyIII\Shared\Mail\RegistrationInterface', 'FireflyIII\Shared\Mail\Registration');

        // reports
        $this->app->bind('FireflyIII\Report\ReportInterface', 'FireflyIII\Report\Report');

        // chart
        $this->app->bind('FireflyIII\Chart\ChartInterface', 'FireflyIII\Chart\Chart');
    }

    public function registerAliases()
    {
        // Shortcut so developers don't need to add an Alias in app/config/app.php
        $this->app->booting(
            function () {
                $loader = AliasLoader::getInstance();
                $loader->alias('Reminders', 'FireflyIII\Shared\Facade\Reminders');
                $loader->alias('Filter', 'FireflyIII\Shared\Facade\Filter');
                $loader->alias('DateKit', 'FireflyIII\Shared\Facade\DateKit');
                $loader->alias('Navigation', 'FireflyIII\Shared\Facade\Navigation');
                $loader->alias('FFForm', 'FireflyIII\Shared\Facade\FFForm');
                $loader->alias('Steam', 'FireflyIII\Shared\Facade\Steam');
            }
        );
    }

}