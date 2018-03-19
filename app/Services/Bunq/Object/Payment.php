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

namespace FireflyIII\Services\Bunq\Object;
use Carbon\Carbon;


/**
 * Class Payment
 */
class Payment extends BunqObject
{
    /** @var int */
    private $id;
    /** @var Carbon */
    private $created;
    /** @var Carbon */
    private $updated;
    /** @var int */
    private $monetaryAccountId;
    /** @var Amount */
    private $amount;
    /** @var string */
    private $description;
    /** @var string */
    private $type;
    /** @var string */
    private $merchantReference;
    /** @var LabelMonetaryAccount */
    private $counterParty;
    /** @var array */
    private $attachments = [];
    /** @var string */
    private $subType;

    /**
     * Payment constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->created = new Carbon();

        var_dump($data);
        exit;
    }

}
