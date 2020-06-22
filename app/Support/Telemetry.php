<?php
declare(strict_types=1);
/**
 * Telemetry.php
 * Copyright (c) 2020 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\Telemetry as TelemetryModel;
use FireflyIII\Support\System\GeneratesInstallationId;
use Illuminate\Database\QueryException;
use JsonException;
use Log;

/**
 * Class Telemetry
 */
class Telemetry
{
    use GeneratesInstallationId;
    /**
     * Feature telemetry stores a $value for the given $feature.
     * Will only store the given $feature / $value combination once.
     *
     *
     * Examples:
     * - execute-cli-command [value]
     * - use-help-pages
     * - has-created-bill
     * - first-time-install
     * - more
     *
     * Its use should be limited to exotic and strange use cases in Firefly III.
     * Because time and date are logged as well, useful to track users' evolution in Firefly III.
     *
     * Any meta-data stored is strictly non-financial.
     *
     * @param string $key
     * @param string $value
     */
    public function feature(string $key, string $value): void
    {
        if (false === config('firefly.send_telemetry') || false === config('firefly.feature_flags.telemetry')) {
            // hard stop if not allowed to do telemetry.
            // do nothing!
            return;
        }

        Log::info(sprintf('Logged telemetry feature "%s" with value "%s".', $key, $value));
        if (!$this->hasEntry('feature', $key, $value)) {
            $this->storeEntry('feature', $key, $value);
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $days
     */
    public function recurring(string $key, string $value, int $days): void
    {
        if (false === config('firefly.send_telemetry') || false === config('firefly.feature_flags.telemetry')) {
            // hard stop if not allowed to do telemetry.
            // do nothing!
            return;
        }

        $cutoffDate = Carbon::today()->subDays($days);
        if (!$this->hasRecentEntry('recurring', $key, $value, $cutoffDate)) {
            $this->storeEntry('recurring', $key, $value);
        }
    }

    /**
     * String telemetry stores a string value as a telemetry entry. Values could include:
     *
     * - "php-version", "php7.3"
     * - "os-version", "linux"
     *
     * Any meta-data stored is strictly non-financial.
     *
     * @param string $name
     * @param string $value
     */
    public function string(string $name, string $value): void
    {
        if (false === config('firefly.send_telemetry') || false === config('firefly.feature_flags.telemetry')) {
            // hard stop if not allowed to do telemetry.
            // do nothing!
            return;
        }
        Log::info(sprintf('Logged telemetry string "%s" with value "%s".', $name, $value));

        $this->storeEntry('string', $name, $value);
    }

    /**
     * @param string $type
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    private function hasEntry(string $type, string $key, string $value): bool
    {
        try {
            $jsonEncoded = json_encode($value, JSON_THROW_ON_ERROR, 512);
        } catch (JsonException $e) {
            Log::error(sprintf('JSON Exception encoding the following value: %s: %s', $value, $e->getMessage()));
            $jsonEncoded = [];
        }
        try {
            $count = TelemetryModel
                ::where('type', $type)
                ->where('key', $key)
                ->where('value', $jsonEncoded)
                ->count();
        } catch (QueryException|Exception $e) {
            $count = 0;
        }

        return $count > 0;
    }

    /**
     * @param string $type
     * @param string $key
     * @param string $value
     * @param Carbon $date
     *
     * @return bool
     */
    private function hasRecentEntry(string $type, string $key, string $value, Carbon $date): bool
    {
        try {
            $jsonEncoded = json_encode($value, JSON_THROW_ON_ERROR, 512);
        } catch (JsonException $e) {
            Log::error(sprintf('JSON Exception encoding the following value: %s: %s', $value, $e->getMessage()));
            $jsonEncoded = [];
        }

        return TelemetryModel
                   ::where('type', $type)
                   ->where('key', $key)
                   ->where('created_at', '>=', $date->format('Y-m-d H:i:s'))
                   ->where('value', $jsonEncoded)
                   ->count() > 0;
    }

    /**
     * Store new entry in DB.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     */
    private function storeEntry(string $type, string $name, string $value): void
    {
        $this->generateInstallationId();
        $config         = app('fireflyconfig')->get('installation_id', null);
        $installationId = null !== $config ? $config->data : 'empty';
        try {
            TelemetryModel::create(
                [
                    'installation_id' => $installationId,
                    'key'             => $name,
                    'type'            => $type,
                    'value'           => $value,
                ]
            );
        } catch (QueryException|Exception $e) {
            // ignore.
        }
    }
}
