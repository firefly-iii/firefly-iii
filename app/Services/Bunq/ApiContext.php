<?php
/**
 * ApiContext.php
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

namespace FireflyIII\Services\Bunq;

use bunq\Context\ApiContext as BunqApiContext;
use bunq\Context\BunqContext;
use bunq\Util\BunqEnumApiEnvironmentType;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use Log;
use Tests\Object\FakeApiContext;

/**
 * Special class to hide away bunq's static initialisation methods.
 *
 * Class ApiContext
 * @codeCoverageIgnore
 */
class ApiContext
{

    /**
     * @param BunqEnumApiEnvironmentType $environmentType
     * @param string                     $apiKey
     * @param string                     $description
     * @param array                      $permittedIps
     * @param string|null                $proxyUrl
     *
     * @throws FireflyException
     * @return BunqApiContext|FakeApiContext
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function create(BunqEnumApiEnvironmentType $environmentType, string $apiKey, string $description, array $permittedIps, string $proxyUrl = null
    ) {
        $permittedIps = $permittedIps ?? [];
        try {
            $context = BunqApiContext::create($environmentType, $apiKey, $description, $permittedIps, $proxyUrl);
        } catch (Exception $e) {
            $message = $e->getMessage();
            Log::error($message);
            Log::error($e->getTraceAsString());

            if (stripos($message, 'Generating a new private key failed')) {
                $message = 'Could not generate key-material. Please make sure OpenSSL is installed and configured: http://bit.ly/FF3-openSSL';
            }
            throw new FireflyException($message);
        }

        return $context;
    }

    /**
     * @throws FireflyException
     *
     * @param string $jsonString
     */
    public function fromJson(string $jsonString): void
    {
        try {
            $apiContext = BunqApiContext::fromJson($jsonString);
            BunqContext::loadApiContext($apiContext);
        } catch (Exception $e) {
            $message = $e->getMessage();
            Log::error($message);
            Log::error($e->getTraceAsString());

            if (stripos($message, 'Generating a new private key failed')) {
                $message = 'Could not generate key-material. Please make sure OpenSSL is installed and configured: http://bit.ly/FF3-openSSL';
            }
            throw new FireflyException($message);
        }
    }
}
