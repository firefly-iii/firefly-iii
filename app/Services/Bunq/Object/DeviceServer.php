<?php
/**
 * DeviceServer.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
