<?php
/**
 * Payment.php
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


use bunq\Model\Generated\Endpoint\BunqResponsePaymentList;
use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use Exception;
use FireflyIII\Exceptions\FireflyException;

/**
 * Class Payment
 * @codeCoverageIgnore
 */
class Payment
{
    /**
     * @param int|null   $monetaryAccountId
     * @param array|null $params
     * @param array|null $customHeaders
     *
     * @throws FireflyException
     * @return BunqResponsePaymentList
     */
    public function listing(int $monetaryAccountId = null, array $params = null, array $customHeaders = null): BunqResponsePaymentList
    {
        $monetaryAccountId = $monetaryAccountId ?? 0;
        $params            = $params ?? [];
        $customHeaders     = $customHeaders ?? [];
        try {
            $result = BunqPayment::listing($monetaryAccountId, $params, $customHeaders);
        } catch (Exception $e) {
            throw new FireflyException($e->getMessage());
        }

        return $result;


    }

}
