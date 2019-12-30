<?php

/**
 * Request.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Http\Requests\Request as FireflyIIIRequest;

/**
 * Class Request.
 *
 * Technically speaking this class does not have to be extended like this but who knows what the future brings.
 *
 */
class Request extends FireflyIIIRequest
{
    /**
     * @return array
     */
    public function getAllAccountData(): array
    {
        $active          = true;
        $includeNetWorth = true;
        $hasLocation     = false;
        if (null !== $this->get('active')) {
            $active = $this->boolean('active');
        }
        if (null !== $this->get('include_net_worth')) {
            $includeNetWorth = $this->boolean('include_net_worth');
        }
        if ($this->has('longitude') && $this->has('latitude') && $this->has('zoom_level')) {
            $hasLocation = true;
        }
        $data = [
            'name'                    => $this->string('name'),
            'active'                  => $active,
            'include_net_worth'       => $includeNetWorth,
            'account_type'            => $this->string('type'),
            'account_type_id'         => null,
            'currency_id'             => $this->integer('currency_id'),
            'currency_code'           => $this->string('currency_code'),
            'virtual_balance'         => $this->string('virtual_balance'),
            'iban'                    => $this->string('iban'),
            'BIC'                     => $this->string('bic'),
            'account_number'          => $this->string('account_number'),
            'account_role'            => $this->string('account_role'),
            'opening_balance'         => $this->string('opening_balance'),
            'opening_balance_date'    => $this->date('opening_balance_date'),
            'cc_type'                 => $this->string('credit_card_type'),
            'cc_Monthly_payment_date' => $this->string('monthly_payment_date'),
            'notes'                   => $this->nlString('notes'),
            'interest'                => $this->string('interest'),
            'interest_period'         => $this->string('interest_period'),
            'has_location'            => $hasLocation,
            'longitude'               => $this->string('longitude'),
            'latitude'                => $this->string('latitude'),
            'zoom_level'              => $this->integer('zoom_level'),
        ];

        if ('liability' === $data['account_type']) {
            $data['opening_balance']      = bcmul($this->string('liability_amount'), '-1');
            $data['opening_balance_date'] = $this->date('liability_start_date');
            $data['account_type']         = $this->string('liability_type');
            $data['account_type_id']      = null;
        }

        return $data;
    }
}
