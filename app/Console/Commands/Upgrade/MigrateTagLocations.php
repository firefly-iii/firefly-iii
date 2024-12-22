<?php

/**
 * MigrateTagLocations.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use Illuminate\Console\Command;

/**
 * Class MigrateTagLocations
 */
class MigrateTagLocations extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '500_migrate_tag_locations';

    protected $description          = 'Migrate tag locations.';

    protected $signature            = 'firefly-iii:migrate-tag-locations {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        $this->migrateTagLocations();
        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
    }

    private function migrateTagLocations(): void
    {
        $tags = Tag::get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            if ($this->hasLocationDetails($tag)) {
                $this->migrateLocationDetails($tag);
            }
        }
    }

    private function hasLocationDetails(Tag $tag): bool
    {
        return null !== $tag->latitude && null !== $tag->longitude && null !== $tag->zoomLevel;
    }

    private function migrateLocationDetails(Tag $tag): void
    {
        $location             = new Location();
        $location->longitude  = $tag->longitude;
        $location->latitude   = $tag->latitude;
        $location->zoom_level = $tag->zoomLevel;
        $location->locatable()->associate($tag);
        $location->save();

        $tag->longitude       = null;
        $tag->latitude        = null;
        $tag->zoomLevel       = null;
        $tag->save();
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
