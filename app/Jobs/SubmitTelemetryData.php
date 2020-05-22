<?php
declare(strict_types=1);
/**
 * SubmitTelemetryData.php
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

namespace FireflyIII\Jobs;


use Carbon\Carbon;
use FireflyIII\Models\Telemetry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;

/**
 * Class SubmitTelemetryData
 */
class SubmitTelemetryData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Carbon The current date */
    private $date;
    /** @var bool Force the transaction to be created no matter what. */
    private $force;

    /**
     * Create a new job instance.
     *
     * @codeCoverageIgnore
     *
     * @param Carbon $date
     */
    public function __construct(?Carbon $date)
    {
        $this->date = $date;
        Log::debug('Created new SubmitTelemetryData');
    }

    /**
     *
     */
    public function handle(): void
    {
        $url = sprintf('%s/submit', config('firefly.telemetry_endpoint'));
        Log::debug(sprintf('Will submit telemetry to endpoint: %s', $url));

        $telemetry = $this->collectTelemetry();

        if (0 === $telemetry->count()) {
            Log::debug('Nothing to submit.');

            return;
        }

        $json = $this->parseJson($telemetry);
        $client  = new Client;
        $options = [
            'body'    => json_encode($json, JSON_THROW_ON_ERROR, 512),
            'headers' => [
                'Content-Type'    => 'application/json',
                'Accept'          => 'application/json',
                'connect_timeout' => 3.14,
                'User-Agent'      => sprintf('FireflyIII/%s', config('firefly.version')),
            ],
        ];
        try {
            $result = $client->post($url, $options);
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            Log::error('Could not submit telemetry.');
            return;
        }
        $body       = (string) $result->getBody();
        $statusCode = $result->getStatusCode();
        Log::info(sprintf('Result of submission [%d]: %s', $statusCode, $body));
        if (200 === $statusCode) {
            // mark as submitted:
            $this->markAsSubmitted($telemetry);
        }
    }

    /**
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->date = $date;
    }

    /**
     * @param bool $force
     */
    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    /**
     * @return Collection
     */
    private function collectTelemetry(): Collection
    {
        $collection = Telemetry::whereNull('submitted')->take(50)->get();
        Log::debug(sprintf('Found %d entry(s) to submit', $collection->count()));

        return $collection;
    }

    /**
     * @param Collection $telemetry
     */
    private function markAsSubmitted(Collection $telemetry): void
    {
        $telemetry->each(
            static function (Telemetry $entry) {
                $entry->submitted = new Carbon;
                $entry->save();
            }
        );
    }

    /**
     * @param Collection $telemetry
     *
     * @return array
     */
    private function parseJson(Collection $telemetry): array
    {
        $array = [];
        /** @var Telemetry $entry */
        foreach ($telemetry as $entry) {
            $array[] = [
                'installation_id' => $entry->installation_id,
                'collected_at'    => $entry->created_at->format('r'),
                'type'            => $entry->type,
                'key'             => $entry->key,
                'value'           => $entry->value,
            ];
        }

        return $array;
    }

}
