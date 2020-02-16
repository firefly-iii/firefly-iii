<?php
/**
 * CacheProperties.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support;

use Cache;
use Illuminate\Support\Collection;

/**
 * Class CacheProperties.
 * @codeCoverageIgnore
 */
class CacheProperties
{
    /** @var string */
    protected $hash = '';
    /** @var Collection */
    protected $properties;

    /**
     *
     */
    public function __construct()
    {
        $this->properties = new Collection;
        if (auth()->check()) {
            $this->addProperty(auth()->user()->id);
            $this->addProperty(app('preferences')->lastActivity());
        }
    }

    /**
     * @param $property
     */
    public function addProperty($property): void
    {
        $this->properties->push($property);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return Cache::get($this->hash);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return bool
     */
    public function has(): bool
    {
        if ('testing' === config('app.env')) {
            return false;
        }
        $this->hash();

        return Cache::has($this->hash);
    }

    /**
     * @param $data
     */
    public function store($data): void
    {
        Cache::forever($this->hash, $data);
    }

    /**
     */
    private function hash(): void
    {
        $content = '';
        foreach ($this->properties as $property) {
            $content .= json_encode($property);
        }
        $this->hash = substr(sha1($content), 0, 16);
    }
}
