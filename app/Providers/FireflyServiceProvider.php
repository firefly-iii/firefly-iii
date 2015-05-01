<?php

namespace FireflyIII\Providers;

use App;
use FireflyIII\Models\Account;
use FireflyIII\Support\Amount;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Support\ServiceProvider;
use Route;
use Twig;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use TwigBridge\Extension\Loader\Functions;
use Validator;

/**
 * Class FireflyServiceProvider
 *
 * @package FireflyIII\Providers
 */
class FireflyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Validator::resolver(
            function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
        /*
         * Default Twig configuration:
         */
        $config = App::make('config');
        Twig::addExtension(new Functions($config));

        /*
         * Amount::format
         */
        $filter = new Twig_SimpleFilter(
            'formatAmount', function ($string) {
            return App::make('amount')->format($string);
        }, ['is_safe' => ['html']]
        );
        Twig::addFilter($filter);

        /*
         * Amount::formatJournal
         */
        $filter = new Twig_SimpleFilter(
            'formatJournal', function ($journal) {
            return App::make('amount')->formatJournal($journal);
        }, ['is_safe' => ['html']]
        );
        Twig::addFilter($filter);

        /*
         * Steam::balance()
         */

        $filter = new Twig_SimpleFilter(
            'balance', function (Account $account = null) {
            if (is_null($account)) {
                return 'NULL';
            }

            return App::make('amount')->format(App::make('steam')->balance($account));
            //return App::make('steam')->balance($account);
        },['is_safe' => ['html']]
        );
        Twig::addFilter($filter);


        /*
         * Current active route.
         */
        $filter = new Twig_SimpleFilter(
            'activeRoute', function ($string) {
            if (Route::getCurrentRoute()->getName() == $string) {
                return 'active';
            }

            return '';
        }
        );
        Twig::addFilter($filter);

        /*
         * Amount::getCurrencyCode()
         */
        $function = new Twig_SimpleFunction(
            'getCurrencyCode', function () {
            return App::make('amount')->getCurrencyCode();
        }
        );
        Twig::addFunction($function);


        /*
         * env
         */
        $function = new Twig_SimpleFunction(
            'env', function ($a, $b) {
            return env($a, $b);
        }
        );
        Twig::addFunction($function);

    }

    public function register()
    {


        $this->app->bind(
            'preferences', function () {
            return new Preferences;
        }
        );
        $this->app->bind(
            'navigation', function () {
            return new Navigation;
        }
        );
        $this->app->bind(
            'amount', function () {
            return new Amount;
        }
        );

        $this->app->bind(
            'steam', function () {
            return new Steam;
        }
        );
        $this->app->bind(
            'expandedform', function () {
            return new ExpandedForm;
        }
        );

        $this->app->bind('FireflyIII\Repositories\Account\AccountRepositoryInterface', 'FireflyIII\Repositories\Account\AccountRepository');
        $this->app->bind('FireflyIII\Repositories\Budget\BudgetRepositoryInterface', 'FireflyIII\Repositories\Budget\BudgetRepository');
        $this->app->bind('FireflyIII\Repositories\Category\CategoryRepositoryInterface', 'FireflyIII\Repositories\Category\CategoryRepository');
        $this->app->bind('FireflyIII\Repositories\Journal\JournalRepositoryInterface', 'FireflyIII\Repositories\Journal\JournalRepository');
        $this->app->bind('FireflyIII\Repositories\Bill\BillRepositoryInterface', 'FireflyIII\Repositories\Bill\BillRepository');
        $this->app->bind('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface', 'FireflyIII\Repositories\PiggyBank\PiggyBankRepository');
        $this->app->bind('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface', 'FireflyIII\Repositories\Currency\CurrencyRepository');
        $this->app->bind('FireflyIII\Repositories\Tag\TagRepositoryInterface', 'FireflyIII\Repositories\Tag\TagRepository');
        $this->app->bind('FireflyIII\Support\Search\SearchInterface', 'FireflyIII\Support\Search\Search');


        $this->app->bind('FireflyIII\Helpers\Help\HelpInterface', 'FireflyIII\Helpers\Help\Help');
        $this->app->bind('FireflyIII\Helpers\Reminders\ReminderHelperInterface', 'FireflyIII\Helpers\Reminders\ReminderHelper');
        $this->app->bind('FireflyIII\Helpers\Report\ReportHelperInterface', 'FireflyIII\Helpers\Report\ReportHelper');
        $this->app->bind('FireflyIII\Helpers\Report\ReportQueryInterface', 'FireflyIII\Helpers\Report\ReportQuery');


    }

}
