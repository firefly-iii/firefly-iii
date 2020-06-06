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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Cronjobs\AutoBudgetCronjob;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use FireflyIII\Support\Cronjobs\TelemetryCronjob;

/**
 * Trait CronRunner
 */
trait CronRunner
{
    /**
     * @return string
     */
    protected function runRecurring(): string
    {
        /** @var RecurringCronjob $recurring */
        $recurring = app(RecurringCronjob::class);
        try {
            $result = $recurring->fire();
        } catch (FireflyException $e) {
            return $e->getMessage();
        }
        if (false === $result) {
            return 'The recurring transaction cron job did not fire.';
        }

        return 'The recurring transaction cron job fired successfully.';
    }

    /**
     * @return string
     */
    protected function runTelemetry(): string {
        /** @var TelemetryCronjob $telemetry */
        $telemetry = app(TelemetryCronjob::class);
        try {
            $result = $telemetry->fire();
        } catch (FireflyException $e) {
            return $e->getMessage();
        }
        if (false === $result) {
            return 'The telemetry cron job did not fire.';
        }

        return 'The telemetry cron job fired successfully.';
    }

    /**
     * @return string
     */
    protected function runAutoBudget(): string
    {
        /** @var AutoBudgetCronjob $autoBudget */
        $autoBudget = app(AutoBudgetCronjob::class);
        try {
            $result = $autoBudget->fire();
        } catch (FireflyException $e) {
            return $e->getMessage();
        }
        if (false === $result) {
            return 'The auto budget cron job did not fire.';
        }

        return 'The auto budget cron job fired successfully.';
    }

}
