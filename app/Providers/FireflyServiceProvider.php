<?php

namespace FireflyIII\Providers;

use App;
use FireflyIII\Support\Amount;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\Support\Twig\Budget;
use FireflyIII\Support\Twig\General;
use FireflyIII\Support\Twig\Journal;
use FireflyIII\Support\Twig\PiggyBank;
use FireflyIII\Support\Twig\Translation;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Support\ServiceProvider;
use Twig;
use TwigBridge\Extension\Loader\Functions;
use Validator;

/**
 * Class FireflyServiceProvider
 *
 * @package FireflyIII\Providers
 * @codeCoverageIgnore
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
        Twig::addExtension(new PiggyBank);
        Twig::addExtension(new General);
        Twig::addExtension(new Journal);
        Twig::addExtension(new Budget);
        Twig::addExtension(new Translation);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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

        // make Google charts:
        //$this->app->bind('FireflyIII\Generator\Chart\Account\AccountChartGenerator', 'FireflyIII\Generator\Chart\Account\GoogleAccountChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\Account\AccountChartGenerator', 'FireflyIII\Generator\Chart\Account\ChartJsAccountChartGenerator');

        //$this->app->bind('FireflyIII\Generator\Chart\Bill\BillChartGenerator', 'FireflyIII\Generator\Chart\Bill\GoogleBillChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\Bill\BillChartGenerator', 'FireflyIII\Generator\Chart\Bill\ChartJsBillChartGenerator');

        //$this->app->bind('FireflyIII\Generator\Chart\Budget\BudgetChartGenerator', 'FireflyIII\Generator\Chart\Budget\GoogleBudgetChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\Budget\BudgetChartGenerator', 'FireflyIII\Generator\Chart\Budget\ChartJsBudgetChartGenerator');

        //$this->app->bind('FireflyIII\Generator\Chart\Category\CategoryChartGenerator', 'FireflyIII\Generator\Chart\Category\GoogleCategoryChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\Category\CategoryChartGenerator', 'FireflyIII\Generator\Chart\Category\ChartJsCategoryChartGenerator');

        //$this->app->bind('FireflyIII\Generator\Chart\PiggyBank\PiggyBankChartGenerator',
        // 'FireflyIII\Generator\Chart\PiggyBank\GooglePiggyBankChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\PiggyBank\PiggyBankChartGenerator', 'FireflyIII\Generator\Chart\PiggyBank\ChartJsPiggyBankChartGenerator');

        //$this->app->bind('FireflyIII\Generator\Chart\Report\ReportChartGenerator', 'FireflyIII\Generator\Chart\Report\GoogleReportChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\Report\ReportChartGenerator', 'FireflyIII\Generator\Chart\Report\ChartJsReportChartGenerator');


        $this->app->bind('FireflyIII\Helpers\Help\HelpInterface', 'FireflyIII\Helpers\Help\Help');
        $this->app->bind('FireflyIII\Helpers\Report\ReportHelperInterface', 'FireflyIII\Helpers\Report\ReportHelper');
        $this->app->bind('FireflyIII\Helpers\Report\ReportQueryInterface', 'FireflyIII\Helpers\Report\ReportQuery');


    }

}
