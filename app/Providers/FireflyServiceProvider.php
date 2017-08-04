<?php
/**
 * FireflyServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Providers;

use FireflyIII\Export\Processor;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Generator\Chart\Basic\ChartJsGenerator;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Attachments\AttachmentHelper;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Chart\MetaPieChart;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\FiscalHelper;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Helpers\Help\Help;
use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Helpers\Report\BalanceReportHelper;
use FireflyIII\Helpers\Report\BalanceReportHelperInterface;
use FireflyIII\Helpers\Report\BudgetReportHelper;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Helpers\Report\PopupReport;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Repositories\User\UserRepository;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Amount;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\FireflyConfig;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Password\PwndVerifier;
use FireflyIII\Support\Password\Verifier;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\Support\Twig\AmountFormat;
use FireflyIII\Support\Twig\General;
use FireflyIII\Support\Twig\Journal;
use FireflyIII\Support\Twig\PiggyBank;
use FireflyIII\Support\Twig\Rule;
use FireflyIII\Support\Twig\Transaction;
use FireflyIII\Support\Twig\Translation;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Twig;
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
        $config = app('config');
        Twig::addExtension(new Functions($config));
        Twig::addExtension(new PiggyBank);
        Twig::addExtension(new General);
        Twig::addExtension(new Journal);
        Twig::addExtension(new Translation);
        Twig::addExtension(new Transaction);
        Twig::addExtension(new Rule);
        Twig::addExtension(new AmountFormat);
    }

    /**
     *
     */
    public function register()
    {
        $this->app->bind(
            'preferences', function () {
            return new Preferences;
        }
        );

        $this->app->bind(
            'fireflyconfig', function () {
            return new FireflyConfig;
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

        // chart generator:
        $this->app->bind(GeneratorInterface::class, ChartJsGenerator::class);

        // chart builder
        $this->app->bind(
            MetaPieChartInterface::class,
            function (Application $app) {
                /** @var MetaPieChart $chart */
                $chart = app(MetaPieChart::class);
                if ($app->auth->check()) {
                    $chart->setUser(auth()->user());
                }

                return $chart;
            }
        );

        // other generators
        $this->app->bind(ProcessorInterface::class, Processor::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AttachmentHelperInterface::class, AttachmentHelper::class);

        // more generators:
        $this->app->bind(PopupReportInterface::class, PopupReport::class);
        $this->app->bind(HelpInterface::class, Help::class);
        $this->app->bind(ReportHelperInterface::class, ReportHelper::class);
        $this->app->bind(FiscalHelperInterface::class, FiscalHelper::class);
        $this->app->bind(BalanceReportHelperInterface::class, BalanceReportHelper::class);
        $this->app->bind(BudgetReportHelperInterface::class, BudgetReportHelper::class);

        // password verifier thing
        $this->app->bind(Verifier::class, PwndVerifier::class);
    }

}
