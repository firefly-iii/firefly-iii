<?php
/**
 * GetSpectreTokenTrait.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Support\Import\Information;

use FireflyIII\Models\ImportJob;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use Log;

/**
 * Trait GetSpectreTokenTrait
 * @codeCoverageIgnore
 */
trait GetSpectreTokenTrait
{
    /**
     * @param ImportJob $importJob
     * @param Customer  $customer
     *
     * @return Token
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    protected function getToken(ImportJob $importJob, Customer $customer): Token
    {
        Log::debug('Now in GetSpectreTokenTrait::ChooseLoginsHandler::getToken()');
        /** @var CreateTokenRequest $request */
        $request = app(CreateTokenRequest::class);
        $request->setUser($importJob->user);
        $request->setUri(route('import.job.status.index', [$importJob->key]));
        $request->setCustomer($customer);
        $request->call();
        Log::debug('Call to get token is finished');

        return $request->getToken();
    }
}
