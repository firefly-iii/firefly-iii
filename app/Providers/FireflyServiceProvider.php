<?php
/**
 * FireflyServiceProvider.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Providers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\ChartJsGenerator;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Attachments\AttachmentHelper;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelper;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Helpers\Help\Help;
use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Helpers\Report\NetWorth;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Helpers\Report\PopupReport;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepository;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use FireflyIII\Repositories\Telemetry\TelemetryRepository;
use FireflyIII\Repositories\Telemetry\TelemetryRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepository;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepository;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Currency\ExchangeRateInterface;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequest;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequestInterface;
use FireflyIII\Services\IP\IpifyOrg;
use FireflyIII\Services\IP\IPRetrievalInterface;
use FireflyIII\Services\Password\PwndVerifierV2;
use FireflyIII\Services\Password\Verifier;
use FireflyIII\Support\Amount;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\FireflyConfig;
use FireflyIII\Support\Form\AccountForm;
use FireflyIII\Support\Form\CurrencyForm;
use FireflyIII\Support\Form\PiggyBankForm;
use FireflyIII\Support\Form\RuleForm;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\Support\Telemetry;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use FireflyIII\TransactionRules\Engine\SearchRuleEngine;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Validator;

/**
 *
 * Class FireflyServiceProvider.
 *
 * @codeCoverageIgnore
 *
 */
class FireflyServiceProvider extends ServiceProvider
{
    /**
     * Start provider.
     */
    public function boot(): void
    {
        Validator::resolver(
            static function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
    }

    /**
     * Register stuff.
     *
     */
    public function register(): void
    {
        $this->app->bind(
            'preferences',
            static function () {
                return new Preferences;
            }
        );

        $this->app->bind(
            'fireflyconfig',
            static function () {
                return new FireflyConfig;
            }
        );
        $this->app->bind(
            'navigation',
            static function () {
                return new Navigation;
            }
        );
        $this->app->bind(
            'amount',
            static function () {
                return new Amount;
            }
        );

        $this->app->bind(
            'steam',
            static function () {
                return new Steam;
            }
        );
        $this->app->bind(
            'expandedform',
            static function () {
                return new ExpandedForm;
            }
        );

        $this->app->bind(
            'accountform',
            static function () {
                return new AccountForm;
            }
        );
        $this->app->bind(
            'currencyform',
            static function () {
                return new CurrencyForm;
            }
        );

        $this->app->bind(
            'piggybankform',
            static function () {
                return new PiggyBankForm;
            }
        );

        $this->app->bind(
            'ruleform',
            static function () {
                return new RuleForm;
            }
        );

        $this->app->bind(
            'telemetry',
            static function () {
                return new Telemetry;
            }
        );

        // chart generator:
        $this->app->bind(GeneratorInterface::class, ChartJsGenerator::class);


        // other generators
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TransactionTypeRepositoryInterface::class, TransactionTypeRepository::class);

        $this->app->bind(AttachmentHelperInterface::class, AttachmentHelper::class);


        $this->app->bind(
            ObjectGroupRepositoryInterface::class,
            static function (Application $app) {
                /** @var ObjectGroupRepository $repository */
                $repository = app(ObjectGroupRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            RuleEngineInterface::class,
            static function (Application $app) {
                /** @var SearchRuleEngine $engine */
                $engine = app(SearchRuleEngine::class);
                if ($app->auth->check()) {
                    $engine->setUser(auth()->user());
                }

                return $engine;
            }
        );

        // more generators:
        $this->app->bind(PopupReportInterface::class, PopupReport::class);
        $this->app->bind(HelpInterface::class, Help::class);
        $this->app->bind(ReportHelperInterface::class, ReportHelper::class);
        $this->app->bind(FiscalHelperInterface::class, FiscalHelper::class);
        $this->app->bind(UpdateRequestInterface::class, UpdateRequest::class);
        $this->app->bind(TelemetryRepositoryInterface::class, TelemetryRepository::class);

        $class = (string) config(sprintf('firefly.cer_providers.%s', (string) config('firefly.cer_provider')));
        if ('' === $class) {
            throw new FireflyException('Invalid currency exchange rate provider. Cannot continue.');
        }
        $this->app->bind(ExchangeRateInterface::class, $class);

        // password verifier thing
        $this->app->bind(Verifier::class, PwndVerifierV2::class);

        // IP thing:
        $this->app->bind(IPRetrievalInterface::class, IpifyOrg::class);

        // net worth thing.
        $this->app->bind(NetWorthInterface::class, NetWorth::class);
    }
}
