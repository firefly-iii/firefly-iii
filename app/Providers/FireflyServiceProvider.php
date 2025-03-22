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

use FireflyIII\Generator\Chart\Basic\ChartJsGenerator;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Generator\Webhook\StandardMessageGenerator;
use FireflyIII\Helpers\Attachments\AttachmentHelper;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelper;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Helpers\Report\NetWorth;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Helpers\Report\PopupReport;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Helpers\Webhook\Sha3SignatureGenerator;
use FireflyIII\Helpers\Webhook\SignatureGeneratorInterface;
use FireflyIII\Repositories\AuditLogEntry\ALERepository;
use FireflyIII\Repositories\AuditLogEntry\ALERepositoryInterface;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepository;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepository;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepository;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Repositories\UserGroup\UserGroupRepository;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Repositories\Webhook\WebhookRepository;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequest;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequestInterface;
use FireflyIII\Services\Password\PwndVerifierV2;
use FireflyIII\Services\Password\Verifier;
use FireflyIII\Services\Webhook\StandardWebhookSender;
use FireflyIII\Services\Webhook\WebhookSenderInterface;
use FireflyIII\Support\Amount;
use FireflyIII\Support\Balance;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\FireflyConfig;
use FireflyIII\Support\Form\AccountForm;
use FireflyIII\Support\Form\CurrencyForm;
use FireflyIII\Support\Form\PiggyBankForm;
use FireflyIII\Support\Form\RuleForm;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use FireflyIII\TransactionRules\Engine\SearchRuleEngine;
use FireflyIII\TransactionRules\Expressions\ActionExpressionLanguageProvider;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class FireflyServiceProvider.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function register(): void
    {
        $this->app->bind(
            'preferences',
            static function () {
                return new Preferences();
            }
        );

        $this->app->bind(
            'fireflyconfig',
            static function () {
                return new FireflyConfig();
            }
        );
        $this->app->bind(
            'navigation',
            static function () {
                return new Navigation();
            }
        );
        $this->app->bind(
            'amount',
            static function () {
                return new Amount();
            }
        );

        $this->app->bind(
            'steam',
            static function () {
                return new Steam();
            }
        );
        $this->app->bind(
            'balance',
            static function () {
                return new Balance();
            }
        );
        $this->app->bind(
            'expandedform',
            static function () {
                return new ExpandedForm();
            }
        );

        $this->app->bind(
            'accountform',
            static function () {
                return new AccountForm();
            }
        );
        $this->app->bind(
            'currencyform',
            static function () {
                return new CurrencyForm();
            }
        );

        $this->app->bind(
            'piggybankform',
            static function () {
                return new PiggyBankForm();
            }
        );

        $this->app->bind(
            'ruleform',
            static function () {
                return new RuleForm();
            }
        );

        // chart generator:
        $this->app->bind(GeneratorInterface::class, ChartJsGenerator::class);
        // other generators
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TransactionTypeRepositoryInterface::class, TransactionTypeRepository::class);

        $this->app->bind(AttachmentHelperInterface::class, AttachmentHelper::class);
        $this->app->bind(ALERepositoryInterface::class, ALERepository::class);

        $this->app->bind(
            ObjectGroupRepositoryInterface::class,
            static function (Application $app) {
                /** @var ObjectGroupRepository $repository */
                $repository = app(ObjectGroupRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            WebhookRepositoryInterface::class,
            static function (Application $app) {
                /** @var WebhookRepository $repository */
                $repository = app(WebhookRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // rule expression language
        $this->app->singleton(
            ExpressionLanguage::class,
            static function () {
                $expressionLanguage = new ExpressionLanguage();
                $expressionLanguage->registerProvider(new ActionExpressionLanguageProvider());

                return $expressionLanguage;
            }
        );

        $this->app->bind(
            RuleEngineInterface::class,
            static function (Application $app) {
                /** @var SearchRuleEngine $engine */
                $engine = app(SearchRuleEngine::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $engine->setUser(auth()->user());
                }

                return $engine;
            }
        );

        $this->app->bind(
            UserGroupRepositoryInterface::class,
            static function (Application $app) {
                /** @var UserGroupRepository $repository */
                $repository = app(UserGroupRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // more generators:
        $this->app->bind(PopupReportInterface::class, PopupReport::class);
        $this->app->bind(ReportHelperInterface::class, ReportHelper::class);
        $this->app->bind(FiscalHelperInterface::class, FiscalHelper::class);
        $this->app->bind(UpdateRequestInterface::class, UpdateRequest::class);

        // webhooks:
        $this->app->bind(MessageGeneratorInterface::class, StandardMessageGenerator::class);
        $this->app->bind(SignatureGeneratorInterface::class, Sha3SignatureGenerator::class);
        $this->app->bind(WebhookSenderInterface::class, StandardWebhookSender::class);

        // password verifier thing
        $this->app->bind(Verifier::class, PwndVerifierV2::class);

        // net worth thing.
        $this->app->bind(NetWorthInterface::class, NetWorth::class);
    }
}
