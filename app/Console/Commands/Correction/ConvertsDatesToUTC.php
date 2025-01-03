<?php

/*
 * ConvertsDatesToUTC.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);
/*
 * ConvertDatesToUTC.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

namespace FireflyIII\Console\Commands\Correction;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ConvertsDatesToUTC extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert stored dates to UTC.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'correction:convert-to-utc';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->friendlyWarning('Please do not use this command right now.');

        return 0;

        /**
         * @var string $model
         * @var array  $fields
         */
        foreach (CorrectsTimezoneInformation::$models as $model => $fields) {
            $this->ConvertModeltoUTC($model, $fields);
        }
        // tell the system we are now in UTC mode.
        FireflyConfig::set('utc', true);

        return Command::SUCCESS;
    }

    private function ConvertModeltoUTC(string $model, array $fields): void
    {
        /** @var string $field */
        foreach ($fields as $field) {
            $this->convertFieldtoUTC($model, $field);
        }
    }

    private function convertFieldtoUTC(string $model, string $field): void
    {
        $this->info(sprintf('Converting %s.%s to UTC', $model, $field));
        $shortModel    = str_replace('FireflyIII\Models\\', '', $model);
        $timezoneField = sprintf('%s_tz', $field);
        $items         = new Collection();
        $timeZone      = config('app.timezone');

        try {
            $items = $model::where($timezoneField, $timeZone)->get();
        } catch (QueryException $e) {
            $this->friendlyError(sprintf('Cannot find timezone information to field "%s" of model "%s". Field does not exist.', $field, $shortModel));
            Log::error($e->getMessage());
        }
        if (0 === $items->count()) {
            $this->friendlyPositive(sprintf('All timezone information is UTC in field "%s" of model "%s".', $field, $shortModel));

            return;
        }
        $this->friendlyInfo(sprintf('Converting field "%s" of model "%s" to UTC.', $field, $shortModel));
        $items->each(
            function ($item) use ($field, $timezoneField): void {
                /** @var Carbon $date */
                $date                   = Carbon::parse($item->{$field}, $item->{$timezoneField});
                $date->setTimezone('UTC');
                $item->{$field}         = $date->format('Y-m-d H:i:s'); // @phpstan-ignore-line
                $item->{$timezoneField} = 'UTC'; // @phpstan-ignore-line
                $item->save();
            }
        );
    }
}
