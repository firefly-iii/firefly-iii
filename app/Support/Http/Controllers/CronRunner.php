<?php

/**
 * CronRunner.php
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Cronjobs\AutoBudgetCronjob;
use FireflyIII\Support\Cronjobs\BillWarningCronjob;
use FireflyIII\Support\Cronjobs\ExchangeRatesCronjob;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use FireflyIII\Support\Cronjobs\WebhookCronjob;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Trait CronRunner
 */
trait CronRunner
{
    protected UserRepositoryInterface $repository;

    protected function billWarningCronJob(User $user, bool $force, Carbon $date): array
    {
        /** @var BillWarningCronjob $billWarning */
        $billWarning = app(BillWarningCronjob::class);
        $billWarning->setForce($force);
        // if the user is owner, run for all users.
        $users       = new Collection([$user]);
        if ($user->hasRole('owner')) {
            $this->repository = app(UserRepositoryInterface::class);
            $users            = $this->repository->allAvailable();
        }
        $billWarning->setUsers($users);
        $billWarning->setDate($date);

        try {
            $billWarning->fire();
        } catch (FireflyException $e) {
            return ['job_fired' => false, 'job_succeeded' => false, 'job_errored' => true, 'message' => $e->getMessage()];
        }

        return [
            'job_fired'     => $billWarning->jobFired,
            'job_succeeded' => $billWarning->jobSucceeded,
            'job_errored'   => $billWarning->jobErrored,
            'message'       => $billWarning->message,
        ];
    }

    protected function exchangeRatesCronJob(User $user, bool $force, Carbon $date): array
    {
        /** @var ExchangeRatesCronjob $exchangeRates */
        $exchangeRates = app(ExchangeRatesCronjob::class);
        $exchangeRates->setForce($force);

        // if the user is owner, run for all users.
        $users         = new Collection([$user]);
        if ($user->hasRole('owner')) {
            $this->repository = app(UserRepositoryInterface::class);
            $users            = $this->repository->allAvailable();
        }
        $exchangeRates->setUsers($users);
        $exchangeRates->setDate($date);

        try {
            $exchangeRates->fire();
        } catch (FireflyException $e) {
            return ['job_fired' => false, 'job_succeeded' => false, 'job_errored' => true, 'message' => $e->getMessage()];
        }

        return [
            'job_fired'     => $exchangeRates->jobFired,
            'job_succeeded' => $exchangeRates->jobSucceeded,
            'job_errored'   => $exchangeRates->jobErrored,
            'message'       => $exchangeRates->message,
        ];
    }

    protected function runAutoBudget(User $user, bool $force, Carbon $date): array
    {
        /** @var AutoBudgetCronjob $autoBudget */
        $autoBudget = app(AutoBudgetCronjob::class);
        $autoBudget->setForce($force);
        // if the user is owner, run for all users.
        $users      = new Collection([$user]);
        if ($user->hasRole('owner')) {
            $this->repository = app(UserRepositoryInterface::class);
            $users            = $this->repository->allAvailable();
        }
        $autoBudget->setUsers($users);
        $autoBudget->setDate($date);

        try {
            $autoBudget->fire();
        } catch (FireflyException $e) {
            return ['job_fired' => false, 'job_succeeded' => false, 'job_errored' => true, 'message' => $e->getMessage()];
        }

        return [
            'job_fired'     => $autoBudget->jobFired,
            'job_succeeded' => $autoBudget->jobSucceeded,
            'job_errored'   => $autoBudget->jobErrored,
            'message'       => $autoBudget->message,
        ];
    }

    protected function runRecurring(User $user, bool $force, Carbon $date): array
    {
        /** @var RecurringCronjob $recurring */
        $recurring = app(RecurringCronjob::class);
        $recurring->setForce($force);

        // if the user is owner, run for all users.
        $users     = new Collection([$user]);
        if ($user->hasRole('owner')) {
            $this->repository = app(UserRepositoryInterface::class);
            $users            = $this->repository->allAvailable();
        }
        $recurring->setUsers($users);
        $recurring->setDate($date);

        try {
            $recurring->fire();
        } catch (FireflyException $e) {
            return ['job_fired' => false, 'job_succeeded' => false, 'job_errored' => true, 'message' => $e->getMessage()];
        }

        return [
            'job_fired'     => $recurring->jobFired,
            'job_succeeded' => $recurring->jobSucceeded,
            'job_errored'   => $recurring->jobErrored,
            'message'       => $recurring->message,
        ];
    }

    protected function webhookCronJob(User $user, bool $force, Carbon $date): array
    {
        /** @var WebhookCronjob $webhook */
        $webhook = app(WebhookCronjob::class);
        $webhook->setForce($force);
        // if the user is owner, run for all users.
        $users   = new Collection([$user]);
        if ($user->hasRole('owner')) {
            $this->repository = app(UserRepositoryInterface::class);
            $users            = $this->repository->allAvailable();
        }
        $webhook->setUsers($users);
        $webhook->setDate($date);

        try {
            $webhook->fire();
        } catch (FireflyException $e) {
            return ['job_fired' => false, 'job_succeeded' => false, 'job_errored' => true, 'message' => $e->getMessage()];
        }

        return [
            'job_fired'     => $webhook->jobFired,
            'job_succeeded' => $webhook->jobSucceeded,
            'job_errored'   => $webhook->jobErrored,
            'message'       => $webhook->message,
        ];
    }
}
