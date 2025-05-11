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

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Class CacheProperties.
 */
class CacheProperties
{
    protected string     $hash = '';
    protected Collection $properties;

    public function __construct()
    {
        $this->properties = new Collection();
        if (auth()->check()) {
            $this->addProperty(auth()->user()->id);
            $this->addProperty(app('preferences')->lastActivity());
        }
    }

    /**
     * @param mixed $property
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

    public function getHash(): string
    {
        return $this->hash;
    }

    public function has(): bool
    {
        if ('testing' === config('app.env')) {
            return false;
        }
        $this->hash();

        return Cache::has($this->hash);
    }

    private function hash(): void
    {
        $content    = '';
        foreach ($this->properties as $property) {
            try {
                $content = sprintf('%s%s', $content, \Safe\json_encode($property, JSON_THROW_ON_ERROR));
            } catch (\JsonException) {
                // @ignoreException
                $content = sprintf('%s%s', $content, hash('sha256', (string) time()));
            }
        }
        $this->hash = substr(hash('sha256', $content), 0, 16);
    }

    /**
     * @param mixed $data
     */
    public function store($data): void
    {
        Cache::forever($this->hash, $data);
    }
}
