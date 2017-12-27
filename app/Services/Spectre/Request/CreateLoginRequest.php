<?php
/**
 * CreateLoginRequest.php
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

declare(strict_types=1);

namespace FireflyIII\Services\Spectre\Request;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\SpectreProvider;
use FireflyIII\Services\Spectre\Object\Customer;

/**
 * Class CreateLoginRequest
 */
class CreateLoginRequest extends SpectreRequest
{
    /** @var Customer */
    private $customer;
    /** @var array */
    private $mandatoryFields = [];
    /** @var SpectreProvider */
    private $provider;

    /**
     *
     * @throws FireflyException
     */
    public function call(): void
    {
        // add mandatory fields to login object
        $data = [
            'customer_id'            => $this->customer->getId(),
            'country_code'           => $this->provider->country_code,
            'provider_code'          => $this->provider->code,
            'credentials'            => $this->buildCredentials(),
            'daily_refresh'          => true,
            'fetch_type'             => 'recent',
            'include_fake_providers' => true,
        ];
        $uri  = '/api/v3/logins';
        $response = $this->sendSignedSpectrePost($uri, $data);
        echo '<pre>';
        print_r($response);
        exit;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @param array $mandatoryFields
     */
    public function setMandatoryFields(array $mandatoryFields): void
    {
        $this->mandatoryFields = $mandatoryFields;
    }

    /**
     * @param SpectreProvider $provider
     */
    public function setProvider(SpectreProvider $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    private function buildCredentials(): array
    {
        $return = [];
        /** @var array $requiredField */
        foreach ($this->provider->data['required_fields'] as $requiredField) {
            $fieldName = $requiredField['name'];
            if (!isset($this->mandatoryFields[$fieldName])) {
                throw new FireflyException(sprintf('Mandatory field "%s" is missing from job.', $fieldName));
            }
            $return[$fieldName] = $this->mandatoryFields[$fieldName];
        }

        return $return;
    }


}