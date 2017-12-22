<?php

declare(strict_types=1);
/**
 * GetSpectreProviders.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Jobs;

use FireflyIII\Models\Configuration;
use FireflyIII\Models\SpectreProvider;
use FireflyIII\Services\Spectre\Request\ListProvidersRequest;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

/**
 * Class GetSpectreProviders
 */
class GetSpectreProviders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        Log::debug('Constructed job GetSpectreProviders');
    }

    /**
     * Execute the job.
     *
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Exception
     */
    public function handle()
    {
        /** @var Configuration $configValue */
        $configValue = app('fireflyconfig')->get('spectre_provider_download', 0);
        $now         = time();
        if ($now - intval($configValue->data) < 86400) {
            Log::debug(sprintf('Difference is %d, so will NOT execute job.', ($now - intval($configValue->data))));

            return;
        }
        Log::debug(sprintf('Difference is %d, so will execute job.', ($now - intval($configValue->data))));

        // get user

        // fire away!
        $request = new ListProvidersRequest($this->user);
        $request->call();

        // store all providers:
        $providers = $request->getProviders();
        foreach ($providers as $provider) {
            // find provider?
            $dbProvider = SpectreProvider::where('spectre_id', $provider['id'])->first();
            if (is_null($dbProvider)) {
                $dbProvider = new SpectreProvider;
            }
            // update fields:
            $dbProvider->spectre_id      = $provider['id'];
            $dbProvider->code            = $provider['code'];
            $dbProvider->mode            = $provider['mode'];
            $dbProvider->status          = $provider['status'];
            $dbProvider->interactive     = 1 === $provider['interactive'];
            $dbProvider->automatic_fetch = 1 === $provider['automatic_fetch'];
            $dbProvider->country_code    = $provider['country_code'];
            $dbProvider->data            = $provider;
            $dbProvider->save();
            Log::debug(sprintf('Stored provider #%d under ID #%d', $provider['id'], $dbProvider->id));
        }

        app('fireflyconfig')->set('spectre_provider_download', time());

        return;
    }
}
