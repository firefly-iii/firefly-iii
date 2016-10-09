<?php
/**
 * CacheProperties.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;


use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Preferences as Prefs;

/**
 * Class CacheProperties
 *
 * @package FireflyIII\Support
 */
class CacheProperties
{

    /** @var  string */
    protected $md5 = '';
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
            $this->addProperty(Prefs::lastActivity());
        }
    }

    /**
     * @param $property
     */
    public function addProperty($property)
    {
        $this->properties->push($property);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return Cache::get($this->md5);
    }

    /**
     * @return string
     */
    public function getMd5(): string
    {
        return $this->md5;
    }

    /**
     * @return bool
     */
    public function has(): bool
    {
        if (getenv('APP_ENV') == 'testing') {
            return false;
        }
        $this->md5();

        return Cache::has($this->md5);
    }

    /**
     * @param $data
     */
    public function store($data)
    {
        Cache::forever($this->md5, $data);
    }

    /**
     * @return void
     */
    private function md5()
    {
        foreach ($this->properties as $property) {

            if ($property instanceof Collection || $property instanceof EloquentCollection) {
                $this->md5 .= json_encode($property->toArray());
                continue;
            }
            if ($property instanceof Carbon) {
                $this->md5 .= $property->toRfc3339String();
                continue;
            }
            if (is_object($property)) {
                $this->md5 .= $property->__toString();
            }

            $this->md5 .= json_encode($property);
        }

        $this->md5 = md5($this->md5);
    }
}
