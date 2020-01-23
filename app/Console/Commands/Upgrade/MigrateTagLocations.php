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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Models\Location;
use FireflyIII\Models\Tag;
use Illuminate\Console\Command;

/**
 * Class MigrateTagLocations
 */
class MigrateTagLocations extends Command
{

    public const CONFIG_NAME = '500_migrate_tag_locations';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate tag locations.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:migrate-tag-locations {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        $this->migrateTagLocations();
        $this->markAsExecuted();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Migrated tag locations in %s seconds.', $end));

        return 0;
    }

    /**
     * @param Tag $tag
     *
     * @return bool
     */
    private function hasLocationDetails(Tag $tag): bool
    {
        return null !== $tag->latitude && null !== $tag->longitude && null !== $tag->zoomLevel;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    /**
     * @param Tag $tag
     */
    private function migrateLocationDetails(Tag $tag): void
    {
        $location             = new Location;
        $location->longitude  = $tag->longitude;
        $location->latitude   = $tag->latitude;
        $location->zoom_level = $tag->zoomLevel;
        $location->locatable()->associate($tag);
        $location->save();

        $tag->longitude = null;
        $tag->latitude  = null;
        $tag->zoomLevel = null;
        $tag->save();
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


}
