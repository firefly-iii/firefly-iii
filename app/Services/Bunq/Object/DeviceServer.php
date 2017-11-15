<?php
/**
 * DeviceServer.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

use Carbon\Carbon;
use FireflyIII\Services\Bunq\Id\DeviceServerId;

class DeviceServer extends BunqObject
{
    /** @var  Carbon */
    private $created;
    /** @var  string */
    private $description;
    /** @var  DeviceServerId */
    private $id;
    /** @var  string */
    private $ip;
    /** @var  string */
    private $status;
    /** @var  Carbon */
    private $updated;

    public function __construct(array $data)
    {
        $id = new DeviceServerId();
        $id->setId($data['id']);
        $this->id          = $id;
        $this->created     = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated     = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->ip          = $data['ip'];
        $this->description = $data['description'];
        $this->status      = $data['status'];
    }

    /**
     * @return DeviceServerId
     */
    public function getId(): DeviceServerId
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }
}
